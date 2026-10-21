<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\InterviewRepository;
use App\Repositories\ApplicationRepository;
use App\Services\NotificationService;
use App\Services\EmailService;
use App\Services\NotificationOutboxService;

class InterviewService extends BaseService
{
    protected InterviewRepository $interviewRepo;
    protected ApplicationRepository $applicationRepo;
    protected NotificationService $notificationService;
    protected EmailService $emailService;
    protected NotificationOutboxService $outboxService;

    public function __construct(
        InterviewRepository $interviewRepo,
        ApplicationRepository $applicationRepo,
        NotificationService $notificationService,
        EmailService $emailService,
        NotificationOutboxService $outboxService
    ) {
        parent::__construct();
        $this->interviewRepo = $interviewRepo;
        $this->applicationRepo = $applicationRepo;
        $this->notificationService = $notificationService;
        $this->emailService = $emailService;
        $this->outboxService = $outboxService;
    }

    /**
     * Schedule a new interview for an application.
     *
     * @param int $applicationId
     * @param int $scheduledBy (employer ID)
     * @param array $data {
     *     @var string $scheduled_at (Y-m-d H:i:s)
     *     @var int $duration_minutes (default 30)
     *     @var string $interview_type (video, phone, in_person)
     *     @var string|null $meeting_link
     *     @var string|null $location
     *     @var string|null $notes
     * }
     * @return array|null
     * @throws \Exception
     */
    public function schedule(int $applicationId, int $scheduledBy, array $data): ?array
    {
        $this->clearErrors();

        // Validate input
        $this->validateScheduleInput($data);
        if ($this->fails()) {
            throw new \Exception($this->getFirstError());
        }

        // Check application exists
        $application = $this->applicationRepo->findById($applicationId);
        if (!$application) {
            $this->logError("Application not found", ['application_id' => $applicationId]);
            throw new \Exception("Application not found.");
        }

        // Optional: verify that the employer owns the job of this application
        // This could be added if needed (e.g., $application['job_employer_id'] != $scheduledBy)

        // Prevent duplicate scheduled interview
        if ($this->interviewRepo->hasScheduledInterview($applicationId)) {
            $this->logWarning("Attempt to schedule duplicate interview", ['application_id' => $applicationId]);
            throw new \Exception("A scheduled interview already exists for this application.");
        }

        // Prepare interview data
        $interviewData = [
            'application_id'   => $applicationId,
            'scheduled_by'     => $scheduledBy,
            'scheduled_at'     => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'] ?? 30,
            'interview_type'   => $data['interview_type'],
            'meeting_link'     => $data['meeting_link'] ?? null,
            'location'         => $data['location'] ?? null,
            'notes'            => $data['notes'] ?? null,
            'status'           => 'scheduled',
        ];

        $this->interviewRepo->beginTransaction();
        try {
            $id = $this->interviewRepo->create($interviewData);
            if (!$id) {
                throw new \Exception("Failed to create interview record.");
            }

            $interview = $this->interviewRepo->findById($id);
            if (!$interview) {
                throw new \Exception("Failed to retrieve created interview.");
            }

            // Log success
            $this->logInfo("Interview scheduled", [
                'interview_id' => $id,
                'application_id' => $applicationId,
                'scheduled_by' => $scheduledBy,
                'scheduled_at' => $data['scheduled_at']
            ]);

            // Send notification (creates in‑app notification and attempts email)
            $this->sendInterviewNotification($interview, $application, 'scheduled');

            $this->interviewRepo->commit();
            return $interview;
        } catch (\Exception $e) {
            $this->interviewRepo->rollback();
            $this->logError("Failed to schedule interview", [
                'error' => $e->getMessage(),
                'application_id' => $applicationId
            ]);
            throw $e;
        }
    }

    /**
     * Reschedule an existing interview.
     *
     * @param int $interviewId
     * @param array $newData
     * @return array|null
     * @throws \Exception
     */
    public function reschedule(int $interviewId, array $newData): ?array
    {
        $this->clearErrors();

        // Find existing interview
        $interview = $this->interviewRepo->findById($interviewId);
        if (!$interview) {
            throw new \Exception("Interview not found.");
        }

        // Only allow rescheduling if interview is still 'scheduled'
        if ($interview['status'] !== 'scheduled') {
            throw new \Exception("Cannot reschedule a cancelled or completed interview.");
        }

        // Validate new data if provided
        if (isset($newData['scheduled_at'])) {
            if (strtotime($newData['scheduled_at']) <= time()) {
                throw new \Exception("Interview date must be in the future.");
            }
        }
        if (isset($newData['duration_minutes'])) {
            $this->validateRange($newData['duration_minutes'], 'duration_minutes', 15, 240);
        }
        if (isset($newData['interview_type'])) {
            $this->validateInArray($newData['interview_type'], 'interview_type', ['video', 'phone', 'in_person']);
        }
        if ($this->fails()) {
            throw new \Exception($this->getFirstError());
        }

        // Build update data
        $updateData = [];
        $allowed = ['scheduled_at', 'duration_minutes', 'interview_type', 'meeting_link', 'location', 'notes'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $newData)) {
                $updateData[$field] = $newData[$field];
            }
        }

        if (empty($updateData)) {
            return $interview; // Nothing to update
        }

        $this->interviewRepo->beginTransaction();
        try {
            $updated = $this->interviewRepo->update($interviewId, $updateData);
            if (!$updated) {
                throw new \Exception("Failed to reschedule interview.");
            }

            $updatedInterview = $this->interviewRepo->findById($interviewId);
            
            // Get the application for the notification
            $application = $this->applicationRepo->findById($interview['application_id']);
            if (!$application) {
                throw new \Exception("Application not found for interview.");
            }

            $this->logInfo("Interview rescheduled", [
                'interview_id' => $interviewId,
                'old_data' => $interview,
                'new_data' => $updateData
            ]);

            // Send reschedule notification
            $this->sendInterviewNotification($updatedInterview, $application, 'rescheduled');

            $this->interviewRepo->commit();
            return $updatedInterview;
        } catch (\Exception $e) {
            $this->interviewRepo->rollback();
            $this->logError("Failed to reschedule interview", [
                'error' => $e->getMessage(),
                'interview_id' => $interviewId
            ]);
            throw $e;
        }
    }

    /**
     * Cancel an interview.
     *
     * @param int $interviewId
     * @param string|null $reason
     * @return bool
     * @throws \Exception
     */
    public function cancel(int $interviewId, ?string $reason = null): bool
    {
        $interview = $this->interviewRepo->findById($interviewId);
        if (!$interview) {
            throw new \Exception("Interview not found.");
        }

        if ($interview['status'] === 'cancelled') {
            return true; // Already cancelled
        }

        $this->interviewRepo->beginTransaction();
        try {
            $success = $this->interviewRepo->cancel($interviewId, $reason);
            if (!$success) {
                throw new \Exception("Failed to cancel interview.");
            }
            
            // Get the application for the notification
            $application = $this->applicationRepo->findById($interview['application_id']);
            if (!$application) {
                throw new \Exception("Application not found for interview.");
            }
            
            // Get the updated interview after cancellation
            $cancelledInterview = $this->interviewRepo->findById($interviewId);

            $this->logInfo("Interview cancelled", [
                'interview_id' => $interviewId,
                'reason' => $reason
            ]);

            // Send cancellation notification
            $this->sendInterviewNotification($cancelledInterview, $application, 'cancelled', $reason);

            $this->interviewRepo->commit();
            return true;
        } catch (\Exception $e) {
            $this->interviewRepo->rollback();
            $this->logError("Failed to cancel interview", [
                'error' => $e->getMessage(),
                'interview_id' => $interviewId
            ]);
            throw $e;
        }
    }

    /**
     * Mark an interview as completed.
     *
     * @param int $interviewId
     * @return bool
     * @throws \Exception
     */
    public function complete(int $interviewId): bool
    {
        $interview = $this->interviewRepo->findById($interviewId);
        if (!$interview) {
            throw new \Exception("Interview not found.");
        }

        if ($interview['status'] === 'completed') {
            return true;
        }

        if ($interview['status'] !== 'scheduled') {
            throw new \Exception("Cannot complete an interview that is cancelled.");
        }

        $this->interviewRepo->beginTransaction();
        try {
            $success = $this->interviewRepo->complete($interviewId);
            if (!$success) {
                throw new \Exception("Failed to mark interview as completed.");
            }

            $this->logInfo("Interview completed", ['interview_id' => $interviewId]);

            // Get application for notification
            $application = $this->applicationRepo->findById($interview['application_id']);
            if ($application) {
                $this->sendInterviewNotification($interview, $application, 'completed');
            }

            $this->interviewRepo->commit();
            return true;
        } catch (\Exception $e) {
            $this->interviewRepo->rollback();
            $this->logError("Failed to complete interview", [
                'error' => $e->getMessage(),
                'interview_id' => $interviewId
            ]);
            throw $e;
        }
    }

    /**
     * Get an interview by ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        return $this->interviewRepo->findById($id);
    }

    /**
     * Get all interviews for an application.
     *
     * @param int $applicationId
     * @return array
     */
    public function getByApplication(int $applicationId): array
    {
        return $this->interviewRepo->findByApplication($applicationId);
    }

    /**
     * Get all interviews scheduled by an employer.
     *
     * @param int $employerId
     * @return array
     */
    public function getByEmployer(int $employerId): array
    {
        return $this->interviewRepo->findByEmployer($employerId);
    }

    /**
     * Get upcoming interviews (future) for an employer or all.
     *
     * @param int|null $employerId
     * @return array
     */
    public function getUpcoming(?int $employerId = null): array
    {
        return $this->interviewRepo->findUpcoming($employerId);
    }

    /**
     * Get past interviews for an employer or all.
     *
     * @param int|null $employerId
     * @return array
     */
    public function getPast(?int $employerId = null): array
    {
        return $this->interviewRepo->findPast($employerId);
    }

    /**
     * Get interviews with full details (including applicant and job).
     *
     * @param int|null $employerId
     * @return array
     */
    public function getWithDetails(?int $employerId = null): array
    {
        return $this->interviewRepo->getInterviewsWithDetails($employerId);
    }

    /**
     * Check if an application has a scheduled interview.
     *
     * @param int $applicationId
     * @return bool
     */
    public function hasScheduledInterview(int $applicationId): bool
    {
        return $this->interviewRepo->hasScheduledInterview($applicationId);
    }

    /**
     * Get counts by status for an employer.
     *
     * @param int $employerId
     * @return array
     */
    public function getCountsByStatusForEmployer(int $employerId): array
    {
        return $this->interviewRepo->countByStatusForEmployer($employerId);
    }

    public function findByJobSeeker(int $jobSeekerId): array
    {
        return $this->interviewRepo->findByJobSeeker($jobSeekerId);
    }

    /**
     * Get counts by status for a job seeker.
     *
     * @param int $jobSeekerId
     * @return array
     */
    public function getCountsByStatusForJobSeeker(int $jobSeekerId): array
    {
        return $this->interviewRepo->countByStatusForJobSeeker($jobSeekerId);
    }

    /**
     * Send notification for an interview event.
     * Creates an in-app notification and attempts to send an email directly.
     * If direct email fails, the email is queued in the outbox.
     *
     * @param array $interview
     * @param array $application
     * @param string $action
     * @param string|null $reason
     * @return void
     */
    private function sendInterviewNotification(array $interview, array $application, string $action, ?string $reason = null): void
    {
        $interviewWithDetails = $this->interviewRepo->getByIdWithDetails($interview['id']);
        
        if (!$interviewWithDetails) {
            $this->logError("Failed to get interview details for notification", [
                'interview_id' => $interview['id']
            ]);
            return;
        }
        
        // Format user‑friendly message based on the action
        $message = $this->formatInterviewNotificationMessage($action, $interviewWithDetails, $reason);
        $title = $this->getInterviewNotificationTitle($action);
        $candidateUserId = $interviewWithDetails['user_id'];
        
        // Create in‑app notification
        $notificationId = $this->notificationService->create(
            $candidateUserId,
            'interview',
            $title,
            $message,
            $interview['scheduled_by'],
            'view_application',
            ['application_id' => $application['id']],
            $interview['id'],
            'interviews'
        );
        
        // Prepare email data
        $candidateEmail = $interviewWithDetails['candidate_email'] ?? null;
        if (!$candidateEmail) {
            $this->logWarning("No candidate email found for interview notification", [
                'interview_id' => $interview['id'],
                'user_id' => $candidateUserId
            ]);
            return;
        }
        
        $candidateName = trim(
            ($interviewWithDetails['candidate_first_name'] ?? '') . ' ' .
            ($interviewWithDetails['candidate_last_name'] ?? '')
        );
        if (empty($candidateName)) {
            $candidateName = $candidateEmail;
        }
        
        $jobTitle = $interviewWithDetails['job_title'] ?? 'the position';
        $companyName = $interviewWithDetails['employer_company_name'] ?? 'the employer';
        $scheduledAt = isset($interviewWithDetails['scheduled_at'])
            ? date('F j, Y \a\t g:i A', strtotime($interviewWithDetails['scheduled_at']))
            : '';
        
        // Build action URL for the email button (same as in‑app action)
        $actionUrl = $this->notificationService->generateActionUrl('view_application', ['application_id' => $application['id']]);
        
        // Try to send email directly
        $emailSent = $this->emailService->sendInterviewNotification(
            $candidateEmail,
            $candidateName,
            $jobTitle,
            $companyName,
            $action,
            $scheduledAt,
            $interviewWithDetails['duration_minutes'] ?? 30,
            $interviewWithDetails['interview_type'] ?? '',
            $interviewWithDetails['meeting_link'] ?? null,
            $interviewWithDetails['location'] ?? null,
            $interviewWithDetails['notes'] ?? null,
            $reason,
            $actionUrl
        );
        
        // If direct send failed, queue via outbox
        if (!$emailSent) {
            $this->logWarning("Direct email failed, queuing interview notification", [
                'interview_id' => $interview['id'],
                'action' => $action,
                'recipient' => $candidateEmail
            ]);
            
            // Build payload for outbox (matching the email template)
            $emailVars = [
                'candidate_name' => $candidateName,
                'job_title' => $jobTitle,
                'company_name' => $companyName,
                'action' => $action,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => $interviewWithDetails['duration_minutes'] ?? 30,
                'interview_type' => $interviewWithDetails['interview_type'] ?? '',
                'meeting_link' => $interviewWithDetails['meeting_link'] ?? null,
                'location' => $interviewWithDetails['location'] ?? null,
                'notes' => $interviewWithDetails['notes'] ?? null,
                'cancellation_reason' => $reason,
                'action_url' => $actionUrl
            ];
            
            $this->outboxService->sendEmail(
                $candidateUserId,
                'interview_notification',
                $emailVars,
                $notificationId
            );
        } else {
            $this->logInfo("Interview notification email sent directly", [
                'interview_id' => $interview['id'],
                'action' => $action,
                'recipient' => $candidateEmail
            ]);
        }
        
        $this->logInfo("Interview notification processed", [
            'interview_id' => $interview['id'],
            'application_id' => $application['id'],
            'candidate_user_id' => $candidateUserId,
            'action' => $action,
            'reason' => $reason,
            'email_direct_sent' => $emailSent
        ]);
    }

    /**
     * Format a user-friendly notification message based on the interview action.
     *
     * @param string $action
     * @param array $interviewDetails
     * @param string|null $reason
     * @return string
     */
    private function formatInterviewNotificationMessage(string $action, array $interviewDetails, ?string $reason = null): string
    {
        $jobTitle = $interviewDetails['job_title'] ?? 'the position';
        $companyName = $interviewDetails['employer_company_name'] ?? 'the employer';
        $scheduledAt = isset($interviewDetails['scheduled_at']) 
            ? date('F j, Y \a\t g:i A', strtotime($interviewDetails['scheduled_at']))
            : '';
        
        switch ($action) {
            case 'scheduled':
                $message = "Great news! Your interview for {$jobTitle} at {$companyName} has been scheduled for {$scheduledAt}. ";
                $message .= "Please check the interview details and prepare accordingly.";
                break;
                
            case 'rescheduled':
                $message = "Your interview for {$jobTitle} at {$companyName} has been rescheduled to {$scheduledAt}. ";
                $message .= "Please review the updated interview details.";
                break;
                
            case 'cancelled':
                $message = "Your interview for {$jobTitle} at {$companyName} has been cancelled.";
                if ($reason) {
                    $message .= " Reason: {$reason}";
                }
                $message .= " We apologize for any inconvenience. You may contact the employer for more information.";
                break;
                
            case 'completed':
                $message = "Your interview for {$jobTitle} at {$companyName} has been marked as completed. ";
                $message .= "Thank you for your time. The employer will be in touch with their decision soon.";
                break;
                
            case 'reminder':
                $message = "Reminder: Your interview for {$jobTitle} at {$companyName} is scheduled for {$scheduledAt}. ";
                $message .= "Please ensure you're prepared and on time.";
                break;
                
            default:
                $message = "There's an update regarding your interview for {$jobTitle} at {$companyName}. ";
                $message .= "Please check your dashboard for details.";
                break;
        }
        
        return $message;
    }

    /**
     * Get the notification title based on the interview action.
     *
     * @param string $action
     * @return string
     */
    private function getInterviewNotificationTitle(string $action): string
    {
        return match ($action) {
            'scheduled' => 'Interview Scheduled',
            'rescheduled' => 'Interview Rescheduled',
            'cancelled' => 'Interview Cancelled',
            'completed' => 'Interview Completed',
            'reminder' => 'Interview Reminder',
            default => 'Interview Update',
        };
    }

    /**
     * Validate input data for scheduling an interview.
     *
     * @param array $data
     * @return void
     */
    protected function validateScheduleInput(array $data): void
    {
        // Required fields
        $this->validateRequired($data, ['scheduled_at', 'interview_type']);
        if ($this->fails()) {
            return;
        }

        // scheduled_at must be in the future
        if (strtotime($data['scheduled_at']) <= time()) {
            $this->addError('scheduled_at', 'Interview date must be in the future.');
        }

        // interview_type must be valid
        $this->validateInArray($data['interview_type'], 'interview_type', ['video', 'phone', 'in_person']);

        // duration_minutes (if provided) must be between 15 and 240
        if (isset($data['duration_minutes'])) {
            $this->validateRange($data['duration_minutes'], 'duration_minutes', 15, 240);
        }

        // interview_type specific requirements
        if ($data['interview_type'] === 'video' && empty($data['meeting_link'])) {
            $this->addError('meeting_link', 'Meeting link is required for video interviews.');
        }
        if ($data['interview_type'] === 'in_person' && empty($data['location'])) {
            $this->addError('location', 'Location is required for in-person interviews.');
        }
    }
}
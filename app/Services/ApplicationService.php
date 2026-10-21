<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\ApplicationRepository;
use App\Repositories\JobRepository;
use App\Repositories\JobSeekerRepository;
use App\Services\NotificationService;
use App\Services\EmailService;
use App\Services\NotificationOutboxService;
use Exception;

/**
 * ApplicationService - Manages all job application business logic
 * 
 * Business rules:
 * - A user can only apply once per job
 * - Cannot apply to closed jobs
 * - Cannot apply to own jobs
 * - Applications require resume (unless quick apply enabled)
 */
class ApplicationService extends BaseService
{
    private ApplicationRepository $appRepo;
    private JobRepository $jobRepo;
    private JobSeekerRepository $jobSeekerRepo;
    private NotificationService $notificationService;
    private EmailService $emailService;
    private NotificationOutboxService $outboxService;

    public function __construct(
        ApplicationRepository $appRepo,
        JobRepository $jobRepo,
        JobSeekerRepository $jobSeekerRepo,
        NotificationService $notificationService,
        EmailService $emailService,
        NotificationOutboxService $outboxService
    ) {
        parent::__construct();
        $this->appRepo = $appRepo;
        $this->jobRepo = $jobRepo;
        $this->jobSeekerRepo = $jobSeekerRepo;
        $this->notificationService = $notificationService;
        $this->emailService = $emailService;
        $this->outboxService = $outboxService;
    }

    /**
     * Apply for a job
     */
    public function applyForJob(int $userId, int $jobId, string $coverLetter = null): ?array
    {
        $this->logInfo('Applying for job', ['user_id' => $userId, 'job_id' => $jobId]);
        $this->clearErrors();

        try {
            // Check if job exists and is published
            $job = $this->jobRepo->findById($jobId);

            if (!$job) {
                $this->addError('job', 'Job not found');
                return null;
            }

            if ($job['status'] !== JobRepository::STATUS_PUBLISHED) {
                $this->addError('job', 'This job is not accepting applications');
                return null;
            }

            // Only block the account that actually posted this job.
            if ((int) ($job['posted_by'] ?? 0) === $userId) {
                $this->addError('job', 'You cannot apply to your own job');
                return null;
            }

            // Check if already applied
            if ($this->appRepo->hasApplied($userId, $jobId)) {
                $this->addError('application', 'You have already applied to this job');
                return null;
            }

            // Use the CV saved on the authenticated user's profile.
            $jobSeekerProfile = $this->jobSeekerRepo->findByUser($userId);
            $resumePath = $jobSeekerProfile['resume_url'] ?? null;

            // Check resume requirement
            if ($job['requires_resume'] && empty($resumePath)) {
                $this->addError('resume', 'Resume is required for this job');
                return null;
            }

            // Validate cover letter length
            if ($coverLetter && strlen($coverLetter) > 5000) {
                $this->addError('cover_letter', 'Cover letter cannot exceed 5000 characters');
                return null;
            }

            // Create application
            $applicationId = $this->appRepo->apply($jobId, $userId, $coverLetter, $resumePath);

            if (!$applicationId) {
                $this->addError('application', 'Failed to submit application');
                return null;
            }

            $this->logInfo('Application submitted successfully', [
                'user_id' => $userId,
                'job_id' => $jobId,
                'application_id' => $applicationId
            ]);

            // Fetch application details for notifications
            $application = $this->appRepo->getApplicationWithDetails($applicationId);
            if ($application) {
                $employerId = $application['employer_id'];
                $jobTitle = $application['job_title'] ?? 'a job';
                $candidateName = $application['candidate_name'] ?? 'Someone';

                // Create in-app notification for employer
                $title = 'New Application';
                $message = "{$candidateName} has applied for the position of {$jobTitle}.";

                $this->notificationService->create(
                    $employerId,
                    NotificationService::TYPE_JOB_APPLICATION,
                    $title,
                    $message,
                    $userId,                    // actor = jobseeker
                    'job_applicants',           // action_key
                    ['job_id' => $jobId],       // action_params
                    $jobId,                     // notifiable_id (job)
                    'job'                       // notifiable_type
                );

                $this->logInfo('Employer notification created for new application', [
                    'employer_id' => $employerId,
                    'job_id' => $jobId,
                    'application_id' => $applicationId
                ]);
            }

            return $this->appRepo->getApplicationWithDetails($applicationId);

        } catch (Exception $e) {
            $this->logError('Exception in applyForJob', ['error' => $e->getMessage()]);
            $this->addError('application', 'An error occurred while submitting your application');
            return null;
        }
    }

    /**
     * Get user's applications
     */
    public function getUserApplications(int $userId, array $status = null, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $applications = $this->appRepo->getUserApplications($userId, $status, $perPage, $offset);
        $total = $this->appRepo->countUserApplications($userId, $status);

        return [
            'data' => $applications,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get job applications (for employers)
     */
    public function getJobApplications(int $employerId, int $jobId, array $status = null, int $page = 1, int $perPage = 20): array
    {
        $this->logInfo('Checking job ownership', ['job_id' => $jobId, 'employer_id' => $employerId]);
        // Verify job ownership
        $job = $this->jobRepo->findById($jobId);
        $this->logInfo('Fetched job result:', ['job' => $job, 'job_id' => $jobId]);
        
        if (!$job) {
            $this->logInfo('Job not found', ['job_id' => $jobId, 'employer_id' => $employerId]);
            return ['data' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0];
        }
        
        if ($job['employer_id'] != $employerId) {
            $this->logInfo('Access denied - job belongs to different employer', [
                'job_id' => $jobId, 
                'job_employer_id' => $job['employer_id'], 
                'requesting_employer_id' => $employerId
            ]);
            return ['data' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage, 'total_pages' => 0];
        }

        $offset = ($page - 1) * $perPage;

        $applications = $this->appRepo->getJobApplications($jobId, $status, $perPage, $offset);
        $total = $this->appRepo->countJobApplications($jobId, $status);

        // Fallback: if application did not include a resume_path, use the jobseeker profile uploaded CV URL
        foreach ($applications as &$app) {
            if (empty($app['resume_path']) && !empty($app['profile_resume_url'])) {
                $app['resume_path'] = $app['profile_resume_url'];
            }
        }
        unset($app);

        return [
            'data' => $applications,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    public function getEmployerApplicationResumePath(int $employerId, int $applicationId): ?string
    {
        $application = $this->appRepo->getApplicationWithDetails($applicationId);
        if ($application === null) {
            $this->addError('application', 'Application not found');
            return null;
        }

        if ((int) ($application['employer_id'] ?? 0) !== $employerId) {
            $this->addError('application', 'You do not have permission to access this resume');
            return null;
        }

        $resumePath = $application['resume_path'] ?? $application['resume_url'] ?? null;
        if (!is_string($resumePath) || trim($resumePath) === '') {
            $this->addError('resume', 'No resume is attached to this application');
            return null;
        }

        return $resumePath;
    }

    /**
     * Update application status
     */
    public function updateApplicationStatus(int $employerId, int $applicationId, string $status, ?string $employerNotes = null)
    {
        $this->logInfo('Updating application status', [
            'application_id' => $applicationId,
            'status' => $status
        ]);

        // Get application details
        $application = $this->appRepo->getApplicationWithDetails($applicationId);

        if (!$application) {
            $this->addError('application', 'Application not found');
            return false;
        }

        // Verify job ownership
        $job = $this->jobRepo->findById($application['job_id']);
        if (!$job || $job['employer_id'] != $employerId) {
            $this->addError('application', 'You do not have permission to update this application');
            return false;
        }

        // Validate status
        $allowedStatuses = [
            ApplicationRepository::STATUS_REVIEWED,
            ApplicationRepository::STATUS_SHORTLISTED,
            ApplicationRepository::STATUS_REJECTED,
            ApplicationRepository::STATUS_ACCEPTED
        ];

        if (!in_array($status, $allowedStatuses)) {
            $this->addError('status', 'Invalid status');
            return false;
        }

        // Update status
        $result = $this->appRepo->updateStatus($applicationId, $status, $employerNotes);

        if ($result) {
            $this->logInfo('Application status updated', [
                'application_id' => $applicationId,
                'new_status' => $status
            ]);

            // Send notification to jobseeker
            $this->sendApplicationStatusNotification($application, $status, $employerId);
        }

        return $result;
    }

    /**
     * Send in-app notification and email (with fallback to outbox) for application status update.
     */
    private function sendApplicationStatusNotification(array $application, string $status, int $employerId): void
    {
        $jobseekerId = $application['user_id'];
        $jobTitle = $application['job_title'] ?? 'a job';
        $companyName = $application['company_name'] ?? 'the employer';
        $candidateName = $application['candidate_name'] ?? 'there';
        $candidateEmail = $application['email'] ?? null;

        // Determine title and message based on status
        $title = match($status) {
            ApplicationRepository::STATUS_SHORTLISTED => 'Application Shortlisted',
            ApplicationRepository::STATUS_REJECTED    => 'Application Not Selected',
            ApplicationRepository::STATUS_ACCEPTED    => 'Application Accepted',
            ApplicationRepository::STATUS_REVIEWED    => 'Application Reviewed',
            default => 'Application Update',
        };

        $message = match($status) {
            ApplicationRepository::STATUS_SHORTLISTED => "Congratulations! You have been shortlisted for the position of {$jobTitle} at {$companyName}. The employer will contact you soon.",
            ApplicationRepository::STATUS_REJECTED    => "We regret to inform you that your application for {$jobTitle} at {$companyName} was not selected. We wish you the best in your job search.",
            ApplicationRepository::STATUS_ACCEPTED    => "Congratulations! You have been accepted for the position of {$jobTitle} at {$companyName}. Please check for further instructions.",
            ApplicationRepository::STATUS_REVIEWED    => "Your application for {$jobTitle} at {$companyName} has been reviewed. The employer will update you soon.",
            default => "Your application status for {$jobTitle} at {$companyName} has been updated to {$status}.",
        };

        // Create in-app notification for jobseeker
        $notificationId = $this->notificationService->create(
            $jobseekerId,
            NotificationService::TYPE_JOB_APPLICATION,
            $title,
            $message,
            $employerId,                      // actor = employer
            'view_application',               // action_key
            ['application_id' => $application['id']], // action_params
            $application['id'],               // notifiable_id (application)
            'application'                     // notifiable_type
        );

        // If we have an email, attempt to send directly; fallback to outbox
        if ($candidateEmail) {
            $emailSent = $this->emailService->sendApplicationStatusNotification(
                $candidateEmail,
                $candidateName,
                $jobTitle,
                $companyName,
                $status,
                $message,
                $this->notificationService->generateActionUrl('view_application', ['application_id' => $application['id']])
            );

            if (!$emailSent) {
                // Queue via outbox
                $emailVars = [
                    'candidate_name' => $candidateName,
                    'job_title' => $jobTitle,
                    'company_name' => $companyName,
                    'status' => $status,
                    'message' => $message,
                    'action_url' => $this->notificationService->generateActionUrl('view_application', ['application_id' => $application['id']])
                ];
                $this->outboxService->sendEmail($jobseekerId, 'application_status', $emailVars, $notificationId);
                $this->logInfo('Application status email queued in outbox', ['application_id' => $application['id'], 'user_id' => $jobseekerId]);
            } else {
                $this->logInfo('Application status email sent directly', ['application_id' => $application['id'], 'user_id' => $jobseekerId]);
            }
        } else {
            $this->logWarning('No email address found for jobseeker', ['user_id' => $jobseekerId]);
        }
    }

    /**
     * Get employer's applications across all jobs
     */
    public function getEmployerApplications(int $employerId, array $status = null, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $applications = $this->appRepo->getEmployerApplications($employerId, $status, $perPage, $offset);
        $total = $this->appRepo->countEmployerApplications($employerId, $status);

        foreach ($applications as &$app) {
            if (empty($app['resume_path']) && !empty($app['profile_resume_url'])) {
                $app['resume_path'] = $app['profile_resume_url'];
            }
        }
        unset($app);

        return [
            'data' => $applications,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Mark application as viewed
     */
    public function markApplicationViewed(int $employerId, int $applicationId): bool
    {
        $application = $this->appRepo->getApplicationWithDetails($applicationId);

        if (!$application) {
            return false;
        }

        $job = $this->jobRepo->findById($application['job_id']);
        if (!$job || $job['employer_id'] != $employerId) {
            return false;
        }

        return $this->appRepo->markAsViewed($applicationId);
    }

    /**
     * Withdraw application
     */
    public function withdrawApplication(int $userId, int $applicationId): bool
    {
        $application = $this->appRepo->findById($applicationId);

        if (!$application || $application['user_id'] != $userId) {
            $this->addError('application', 'Application not found');
            return false;
        }

        // Only pending applications can be withdrawn
        if ($application['status'] !== ApplicationRepository::STATUS_PENDING) {
            $this->addError('application', 'Cannot withdraw application that has already been reviewed');
            return false;
        }

        return $this->appRepo->withdraw($applicationId, $userId);
    }

    /**
     * Get application statistics for employer dashboard
     */
    public function getEmployerApplicationStats(int $employerId): array
    {
        // Get all jobs for this employer
        $jobs = $this->jobRepo->getEmployerJobs($employerId);
        $jobIds = array_column($jobs, 'id');

        if (empty($jobIds)) {
            return [
                'total_applications' => 0,
                'pending' => 0,
                'reviewed' => 0,
                'shortlisted' => 0,
                'rejected' => 0,
                'accepted' => 0,
                'by_job' => []
            ];
        }

        // Get aggregate stats
        $placeholders = implode(',', array_fill(0, count($jobIds), '?'));

        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
                    SUM(CASE WHEN status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted
                FROM applications 
                WHERE job_id IN ({$placeholders})";

        $aggregate = $this->appRepo->fetchOne($sql, $jobIds);

        // Get stats per job
        $sql = "SELECT 
                    j.id, j.title,
                    COUNT(a.id) as total,
                    SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN a.status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
                    SUM(CASE WHEN a.status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted,
                    SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END) as accepted
                FROM jobs j
                LEFT JOIN applications a ON j.id = a.job_id
                WHERE j.employer_id = :employer_id
                GROUP BY j.id
                ORDER BY total DESC";

        $byJob = $this->appRepo->fetchAll($sql, [':employer_id' => $employerId]);

        return [
            'total_applications' => (int) ($aggregate['total'] ?? 0),
            'pending' => (int) ($aggregate['pending'] ?? 0),
            'reviewed' => (int) ($aggregate['reviewed'] ?? 0),
            'shortlisted' => (int) ($aggregate['shortlisted'] ?? 0),
            'rejected' => (int) ($aggregate['rejected'] ?? 0),
            'accepted' => (int) ($aggregate['accepted'] ?? 0),
            'by_job' => $byJob
        ];
    }
}
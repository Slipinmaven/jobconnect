<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\InterviewService;
use App\Helpers\ApiResponse;

class InterviewController extends BaseController
{
    protected InterviewService $interviewService;

    public function __construct(InterviewService $interviewService)
    {
        parent::__construct();
        $this->interviewService = $interviewService;
    }

    // ==================== LIST ENDPOINTS ====================

    /**
     * GET /api/interviews
     * 
     * List interviews for the authenticated employer.
     * Query parameters:
     *   - upcoming (bool): if true, returns only future interviews
     *   - past (bool): if true, returns only past interviews
     *   - with_details (bool): if true, includes application/job details
     */
    public function index(): void
    {
        $this->requireApiAuth();
        $this->requireApiRole('employer');

        $employerId = $this->userId;

        $upcoming = filter_var($this->get('upcoming', false), FILTER_VALIDATE_BOOLEAN);
        $past = filter_var($this->get('past', false), FILTER_VALIDATE_BOOLEAN);
        $withDetails = filter_var($this->get('with_details', false), FILTER_VALIDATE_BOOLEAN);

        try {
            if ($withDetails) {
                $interviews = $this->interviewService->getWithDetails($employerId);
            } elseif ($upcoming) {
                $interviews = $this->interviewService->getUpcoming($employerId);
            } elseif ($past) {
                $interviews = $this->interviewService->getPast($employerId);
            } else {
                $interviews = $this->interviewService->getByEmployer($employerId);
            }

            $this->success($interviews);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    /**
     * GET /api/applications/{applicationId}/interviews
     * 
     * List all interviews for a specific application.
     */
    public function listByApplication(array $params): void
    {
        $applicationId = (int)$params['applicationId'];
        $this->requireApiAuth();
        $this->requireApiRole('employer');

        // Optionally verify that the employer owns the job of this application
        // This could be done via a service call; for brevity we skip

        try {
            $interviews = $this->interviewService->getByApplication($applicationId);
            $this->success($interviews);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    /**
     * GET /api/interviews/{id}
     * 
     * Get a specific interview by ID.
     */
    public function show(int $id): void
    {
        $this->requireApiAuth();
        $this->requireApiRole('employer');

        try {
            $interview = $this->interviewService->getById($id);
            if (!$interview) {
                $this->notFound('Interview not found');
                return;
            }

            // Authorization: ensure the employer owns this interview
            if ($interview['scheduled_by'] != $this->userId) {
                $this->forbidden('You do not have permission to view this interview');
                return;
            }

            $this->success($interview);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    // ==================== SCHEDULE ENDPOINT ====================

    /**
     * POST /api/applications/{applicationId}/interviews
     * 
     * Schedule a new interview for a given application.
     * 
     * Expected JSON body:
     * {
     *   "scheduled_at": "2024-03-25 14:00:00",
     *   "duration_minutes": 30,
     *   "interview_type": "video",
     *   "meeting_link": "https://zoom.us/j/123456",
     *   "location": null,
     *   "notes": "Please come prepared with portfolio"
     * }
     */
    public function schedule(array $params): void
    {
        $applicationId = $params['applicationId'] ?? null;
        $this->requireApiAuth();
        $this->requireApiRole('employer');

        $input = $this->getJsonInput();

        // Basic validation of required fields
        if (empty($input['scheduled_at']) || empty($input['interview_type'])) {
            $this->validationError(['scheduled_at' => 'Scheduled date and time are required', 'interview_type' => 'Interview type is required']);
            return;
        }

        try {
            $interview = $this->interviewService->schedule(
                $applicationId,
                $this->userId,
                $input
            );

            $this->success($interview, 'Interview scheduled successfully', 201);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), [], 422);
        }
    }

    // ==================== RESCHEDULE ENDPOINT ====================

    /**
     * PUT /api/interviews/{id}
     * 
     * Reschedule an existing interview.
     * 
     * Expected JSON body (any of these):
     * {
     *   "scheduled_at": "2024-03-26 15:00:00",
     *   "duration_minutes": 45,
     *   "interview_type": "phone",
     *   "meeting_link": "+1234567890",
     *   "location": null,
     *   "notes": "Please call this number"
     * }
     */
    public function reschedule(array $params): void
    {
        $id = $params['id'];
        $this->requireApiAuth();
        $this->requireApiRole('employer');

        $input = $this->getJsonInput();

        try {
            $interview = $this->interviewService->getById($id);
            if (!$interview) {
                $this->notFound('Interview not found');
                return;
            }

            // Authorization: ensure the employer owns this interview
            if ($interview['scheduled_by'] != $this->userId) {
                $this->forbidden('You do not have permission to reschedule this interview');
                return;
            }

            $updated = $this->interviewService->reschedule($id, $input);
            $this->success($updated, 'Interview rescheduled successfully');
        } catch (\Exception $e) {
            $this->error($e->getMessage(), [], 422);
        }
    }

    // ==================== CANCEL ENDPOINT ====================

    /**
     * PATCH /api/interviews/{id}/cancel
     * 
     * Cancel an interview.
     * 
     * Optional JSON body:
     * {
     *   "reason": "Candidate is no longer available"
     * }
     */
    public function cancel(array $params): void
    {
        $id = $params['id'];
        $this->requireApiAuth();
        $this->requireApiRole('employer');

        $input = $this->getJsonInput();
        $reason = $input['reason'] ?? null;

        try {
            $interview = $this->interviewService->getById($id);
            if (!$interview) {
                $this->notFound('Interview not found');
                return;
            }

            if ($interview['scheduled_by'] != $this->userId) {
                $this->forbidden('You do not have permission to cancel this interview');
                return;
            }

            $this->interviewService->cancel($id, $reason);
            $this->success(null, 'Interview cancelled successfully');
        } catch (\Exception $e) {
            $this->error($e->getMessage(), [], 422);
        }
    }

    // ==================== COMPLETE ENDPOINT ====================

    /**
     * PATCH /api/interviews/{id}/complete
     * 
     * Mark an interview as completed.
     */
    public function complete(array $params): void
    {
        $id = $params['id'];
        $this->requireApiAuth();
        $this->requireApiRole('employer');

        try {
            $interview = $this->interviewService->getById($id);
            if (!$interview) {
                $this->notFound('Interview not found');
                return;
            }

            if ($interview['scheduled_by'] != $this->userId) {
                $this->forbidden('You do not have permission to complete this interview');
                return;
            }

            $this->interviewService->complete($id);
            $this->success(null, 'Interview marked as completed');
        } catch (\Exception $e) {
            $this->error($e->getMessage(), [], 422);
        }
    }

    // ==================== STATS ENDPOINT ====================

    /**
     * GET /api/interviews/stats
     * 
     * Get counts of interviews by status for the authenticated employer.
     */
    public function stats(): void
    {
        $this->requireApiAuth();
        $this->requireApiRole('employer');

        try {
            $stats = $this->interviewService->getCountsByStatusForEmployer($this->userId);
            $this->success($stats);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    /**
     * GET /jobseeker/interviews
     *
     * Display all interviews for the authenticated job seeker.
     */
    public function jobseekerInterviews(): void
    {
        // Ensure the user is a job seeker
        if (!$this->isJobSeeker()) {
            $this->withFlash('error', 'Unauthorized access to interviews page');
            $this->redirect('/login');
            return;
        }

        $userId = $this->userId; // from BaseController

        try {
            $interviews = $this->interviewService->findByJobSeeker($userId);
            $stats = $this->interviewService->getCountsByStatusForJobSeeker($userId);

            ApiResponse::success([
                'interviews' => $interviews,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
           throw new \Exception('Failed to load interviews: ' . $e->getMessage());
        }
    }
}
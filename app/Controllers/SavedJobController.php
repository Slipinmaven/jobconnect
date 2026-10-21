<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\SavedJobService;
use App\Helpers\ApiResponse;
use Exception;

/**
 * SavedJobController - Handles saving/unsaving jobs for users
 *
 * Endpoints:
 * - GET    /api/user/saved-jobs          List user's saved jobs (paginated)
 * - POST   /api/jobs/{id}/toggle-save    Toggle save status for a job
 * - GET    /api/jobs/{id}/is-saved       Check if a job is saved by current user
 *
 * All endpoints require authentication.
 */
class SavedJobController extends BaseController
{
    private SavedJobService $savedJobService;

    public function __construct(SavedJobService $savedJobService)
    {
        parent::__construct();
        $this->savedJobService = $savedJobService;
    }

    /**
     * Get current user's saved jobs (paginated)
     *
     * @return void
     */
    public function index(): void
    {
        try {
            if (!$this->isAuthenticated()) {
                ApiResponse::unauthorized('Please login to view saved jobs');
                return;
            }

            $page = (int) $this->get('page', 1);
            $perPage = (int) $this->get('per_page', 20);

            $savedJobs = $this->savedJobService->getUserSavedJobs($this->userId, $page, $perPage);

            ApiResponse::success($savedJobs, 'Saved jobs retrieved successfully');
        } catch (Exception $e) {
            error_log('Error fetching saved jobs: ' . $e->getMessage());
            ApiResponse::serverError('Failed to fetch saved jobs');
        }
    }

    /**
     * Toggle save status for a job
     *
     * @param int $jobId
     * @return void
     */
    public function toggle(array $params): void
    {
        try {
          $jobId = (int) $params['jobId'];
            if (!$this->isAuthenticated()) {
                ApiResponse::unauthorized('Please login to save jobs');
                return;
            }

            $result = $this->savedJobService->toggleSaveJob($this->userId, $jobId);

            if (!$result['success']) {
                $errors = $this->savedJobService->getErrors();
                ApiResponse::error('Failed to toggle saved job', $errors, 422);
                return;
            }

            $message = $result['saved'] ? 'Job saved successfully' : 'Job removed from saved';
            ApiResponse::success($result, $message);
        } catch (Exception $e) {
            error_log('Error toggling saved job: ' . $e->getMessage());
            ApiResponse::serverError('Failed to toggle saved job');
        }
    }

    /**
     * Check if a job is saved by the current user
     *
     * @param int $jobId
     * @return void
     */
    public function check(array $params): void
    {
        try {
            $jobId = (int) $params['jobId'];
            if (!$this->isAuthenticated()) {
                ApiResponse::unauthorized('Please login to check saved status');
                return;
            }

            $saved = $this->savedJobService->isSaved($this->userId, $jobId);
            $count = $this->savedJobService->countSavedJobs($this->userId);

            ApiResponse::success([
                'saved' => $saved,
                'count' => $count
            ], 'Saved status retrieved');
        } catch (Exception $e) {
            error_log('Error checking saved job: ' . $e->getMessage());
            ApiResponse::serverError('Failed to check saved status');
        }
    }
}
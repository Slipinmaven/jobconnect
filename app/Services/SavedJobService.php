<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\SavedJobRepository;
use Exception;

/**
 * SavedJobService - Manages saved jobs business logic
 *
 * Business rules:
 * - A user can save a job only once
 * - Cannot save a job that doesn't exist (handled by repository)
 * - Returns consistent paginated structure for listing saved jobs
 */
class SavedJobService extends BaseService
{
    private SavedJobRepository $savedJobRepo;

    public function __construct(SavedJobRepository $savedJobRepo)
    {
        parent::__construct();
        $this->savedJobRepo = $savedJobRepo;
    }

    /**
     * Save a job for a user
     *
     * @param int $userId
     * @param int $jobId
     * @return array|null The saved job record (or simple success flag) on success, null on failure
     */
    public function saveJob(int $userId, int $jobId): ?array
    {
        $this->logInfo('Saving job', ['user_id' => $userId, 'job_id' => $jobId]);
        $this->clearErrors();

        try {
            // Check if already saved
            if ($this->savedJobRepo->isSaved($userId, $jobId)) {
                $this->addError('job', 'Job already saved');
                return null;
            }

            // Attempt to save
            $saved = $this->savedJobRepo->saveJob($userId, $jobId);

            if (!$saved) {
                $this->addError('save', 'Failed to save job');
                return null;
            }

            $this->logInfo('Job saved successfully', ['user_id' => $userId, 'job_id' => $jobId]);

            // Return the saved job details (optional)
            // For consistency, return an array with minimal info
            return [
                'user_id' => $userId,
                'job_id' => $jobId,
                'saved_at' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            $this->logError('Exception in saveJob', ['error' => $e->getMessage()]);
            $this->addError('save', 'An error occurred while saving the job');
            return null;
        }
    }

    /**
     * Unsave (remove) a job for a user
     *
     * @param int $userId
     * @param int $jobId
     * @return bool
     */
    public function unsaveJob(int $userId, int $jobId): bool
    {
        $this->logInfo('Unsaving job', ['user_id' => $userId, 'job_id' => $jobId]);
        $this->clearErrors();

        try {
            // Check if actually saved
            if (!$this->savedJobRepo->isSaved($userId, $jobId)) {
                $this->addError('job', 'Job not saved');
                return false;
            }

            $removed = $this->savedJobRepo->unsaveJob($userId, $jobId);

            if (!$removed) {
                $this->addError('save', 'Failed to remove saved job');
                return false;
            }

            $this->logInfo('Job unsaved successfully', ['user_id' => $userId, 'job_id' => $jobId]);
            return true;

        } catch (Exception $e) {
            $this->logError('Exception in unsaveJob', ['error' => $e->getMessage()]);
            $this->addError('save', 'An error occurred while unsaving the job');
            return false;
        }
    }

    /**
     * Toggle save status for a job
     *
     * @param int $userId
     * @param int $jobId
     * @return array ['saved' => bool, 'count' => int] (count is total saved jobs for user, optional)
     */
    public function toggleSaveJob(int $userId, int $jobId): array
    {
        $this->clearErrors();

        $isSaved = $this->savedJobRepo->isSaved($userId, $jobId);

        if ($isSaved) {
            $success = $this->unsaveJob($userId, $jobId);
            $saved = false;
        } else {
            $result = $this->saveJob($userId, $jobId);
            $success = !is_null($result);
            $saved = true;
        }

        $count = $this->countSavedJobs($userId);

        return [
            'saved' => $saved,
            'count' => $count,
            'success' => $success
        ];
    }

    /**
     * Check if a user has saved a specific job
     *
     * @param int $userId
     * @param int $jobId
     * @return bool
     */
    public function isSaved(int $userId, int $jobId): bool
    {
        return $this->savedJobRepo->isSaved($userId, $jobId);
    }

    /**
     * Get all saved job IDs for a user
     *
     * @param int $userId
     * @return array
     */
    public function getSavedJobIds(int $userId): array
    {
        return $this->savedJobRepo->getUserSavedJobIds($userId);
    }

    /**
     * Get saved jobs for a user with full details (paginated)
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getUserSavedJobs(int $userId, int $page = 1, int $perPage = 20): array
    {
        $this->logInfo('Fetching saved jobs for user', ['user_id' => $userId, 'page' => $page, 'per_page' => $perPage]);

        // Validate pagination parameters
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        // Get paginated results from repository
        $result = $this->savedJobRepo->getUserSavedJobsWithDetails($userId, $page, $perPage);

        // Decode JSON fields (if repository doesn't already do it)
        if (!empty($result['data'])) {
            foreach ($result['data'] as &$job) {
                $job['requirements'] = $this->decodeField($job['requirements'] ?? null);
                $job['responsibilities'] = $this->decodeField($job['responsibilities'] ?? null);
            }
        }

        return $result;
    }

    /**
     * Count total saved jobs for a user
     *
     * @param int $userId
     * @return int
     */
    public function countSavedJobs(int $userId): int
    {
        return $this->savedJobRepo->countUserSavedJobs($userId);
    }

    /**
     * Get list of user IDs who saved a particular job
     *
     * @param int $jobId
     * @return array
     */
    public function getUsersWhoSavedJob(int $jobId): array
    {
        return $this->savedJobRepo->getUsersWhoSavedJob($jobId);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Decode JSON fields (if needed). This replicates the decodeField method from JobRepository.
     * You can move this to a trait or base class if used elsewhere.
     *
     * @param mixed $value
     * @return mixed
     */
    private function decodeField($value)
    {
        if (empty($value)) {
            return null;
        }

        // If already an array, return as-is
        if (is_array($value)) {
            return $value;
        }

        // If it's a string, try to decode it
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        return $value;
    }
}
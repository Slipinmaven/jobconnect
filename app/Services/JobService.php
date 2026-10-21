<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\JobRepository;
use App\Repositories\ApplicationRepository;
use App\Repositories\JobCategoryMapRepository;
use App\Repositories\JobCategoryRepository;
use Exception;

/**
 * JobService - Manages all job-related business logic
 * 
 * Business rules:
 * - Only employers can post jobs
 * - Jobs can be drafted before publishing
 * - Published jobs are visible to job seekers
 * - Closed jobs cannot receive new applications
 */
class JobService extends BaseService
{
    private JobRepository $jobRepo;
    private ApplicationRepository $appRepo;
    private JobCategoryMapRepository $jobCatMapRepo;
    private JobCategoryRepository $jobCatRepo;

    public function __construct(
        JobRepository $jobRepo,
        ApplicationRepository $appRepo,
        JobCategoryMapRepository $jobCatMapRepo,
        JobCategoryRepository $jobCatRepo
    ) {
        parent::__construct();
        $this->jobRepo = $jobRepo;
        $this->appRepo = $appRepo;
        $this->jobCatMapRepo = $jobCatMapRepo;
        $this->jobCatRepo = $jobCatRepo;
    }

    // ==================== JOB CRUD ====================

    /**
     * Create a new job
     */
    public function createJob(int $employerId, int $postedBy, array $data): ?int
    {
        $this->logInfo('Creating job', ['employer_id' => $employerId]);
        $this->clearErrors();

        try {
            // Validate required fields
            $required = ['title', 'description', 'job_type'];
            if (!$this->validateRequired($data, $required)) {
                return null;
            }

            // Validate job type
            $allowedTypes = ['full_time', 'part_time', 'contract', 'internship', 'temporary'];
            if (!$this->validateInArray($data['job_type'], 'job_type', $allowedTypes)) {
                return null;
            }

            // Validate experience level if provided
            if (!empty($data['experience_level'])) {
                $allowedLevels = ['entry', 'junior', 'mid', 'senior', 'lead'];
                if (!$this->validateInArray($data['experience_level'], 'experience_level', $allowedLevels)) {
                    $this->logInfo('Invalid experience level', ['value' => $data['experience_level']]);
                    return null;
                }
            }

            // Validate salary
            if (
                !empty($data['salary_min']) && !empty($data['salary_max']) &&
                $data['salary_min'] > $data['salary_max']
            ) {
                $this->addError('salary', 'Minimum salary cannot exceed maximum salary');
                return null;
            }

            // Prepare job data
            $jobData = [
                'employer_id' => $employerId,
                'posted_by' => $postedBy,
                'title' => trim($data['title']),
                'description' => trim($data['description']),
                'requirements' => $data['requirements'] ?? null,
                'responsibilities' => $data['responsibilities'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'is_remote' => $data['is_remote'] ?? 0,
                'job_type' => $data['job_type'],
                'experience_level' => $data['experience_level'] ?? null,
                'salary_min' => $data['salary_min'] ?? null,
                'salary_max' => $data['salary_max'] ?? null,
                'currency' => $data['currency'] ?? 'KES',
                'requires_resume' => $data['requires_resume'] ?? 1,
                'expires_at' => $data['expires_at'] ?? null,
                'allow_past_deadline' => $data['allow_past_deadline'] ?? 0,
                'status' => $data['status'] ?? JobRepository::STATUS_DRAFT
            ];
            $this->logInfo('Job data prepared for creation', ['job_data' => $jobData]);

            $jobId = $this->jobRepo->createJob($jobData);

            if (!$jobId) {
                $this->addError('job', 'Failed to create job');
                return null;
            }

            $this->logInfo('Job created successfully', ['job_id' => $jobId]);
            return $jobId;

        } catch (Exception $e) {
            $this->logError('Exception in createJob', ['error' => $e->getMessage()]);
            $this->addError('job', 'An error occurred while creating the job');
            return null;
        }
    }

    public function getJobById(int $jobId): ?array
    {
        return $this->jobRepo->findById($jobId);
    }

    public function getJobDataById(int $jobId): ?array
    {
        $job = $this->jobRepo->getJobById($jobId);
        
        if (!$job) {
            return null;
        }
        $categories = $this->jobCatMapRepo->getJobCategories($jobId);
        $job['categories'] = $categories;
        return $job;
    }

    /**
     * Update job
     */
    public function updateJob(int $jobId, int $employerId, array $data): bool
    {
        $this->logInfo('Updating job', ['job_id' => $jobId, 'employer_id' => $employerId]);
        $this->clearErrors();

        try {
            // Verify job ownership
            $job = $this->jobRepo->findById($jobId);
            if (!$job || $job['employer_id'] != $employerId) {
                $this->addError('job', 'Job not found or you do not have permission');
                return false;
            }

            // Prevent editing of closed jobs
            if ($job['status'] === JobRepository::STATUS_CLOSED) {
                $this->addError('job', 'Cannot edit a closed job');
                return false;
            }

            // Validate job type if provided
            if (!empty($data['job_type'])) {
                $allowedTypes = ['full_time', 'part_time', 'contract', 'internship', 'temporary'];
                if (!$this->validateInArray($data['job_type'], 'job_type', $allowedTypes)) {
                    return false;
                }
            }

            // Validate salary
            $salaryMin = $data['salary_min'] ?? $job['salary_min'];
            $salaryMax = $data['salary_max'] ?? $job['salary_max'];
            if ($salaryMin && $salaryMax && $salaryMin > $salaryMax) {
                $this->addError('salary', 'Minimum salary cannot exceed maximum salary');
                return false;
            }

            return $this->jobRepo->updateJob($jobId, $data);

        } catch (Exception $e) {
            $this->logError('Exception in updateJob', ['error' => $e->getMessage()]);
            $this->addError('job', 'An error occurred while updating the job');
            return false;
        }
    }

    /**
     * Publish job
     */
    public function publishJob(int $jobId, int $employerId): bool
    {
        $this->logInfo('Publishing job', ['job_id' => $jobId]);

        $job = $this->jobRepo->findById($jobId);
        if (!$job || $job['employer_id'] != $employerId) {
            $this->addError('job', 'Job not found');
            return false;
        }

        if ($job['status'] === JobRepository::STATUS_PUBLISHED) {
            $this->addError('job', 'Job is already published');
            return false;
        }

        return $this->jobRepo->publishJob($jobId);
    }

    /**
     * Close job
     */
    public function closeJob(int $jobId, int $employerId): bool
    {
        $this->logInfo('Closing job', ['job_id' => $jobId]);

        $job = $this->jobRepo->findById($jobId);
        if (!$job || $job['employer_id'] != $employerId) {
            $this->addError('job', 'Job not found');
            return false;
        }

        return $this->jobRepo->closeJob($jobId);
    }

    /**
     * Reopen job
     */
    public function reopenJob(int $jobId, int $employerId): bool
    {
        $this->logInfo('Reopening job', ['job_id' => $jobId]);

        $job = $this->jobRepo->findById($jobId);
        if (!$job || $job['employer_id'] != $employerId) {
            $this->addError('job', 'Job not found');
            return false;
        }

        if ($job['status'] !== JobRepository::STATUS_CLOSED) {
            $this->addError('job', 'Only closed jobs can be reopened');
            return false;
        }

        return $this->jobRepo->reopenJob($jobId);
    }

    /**
     * Toggle close/reopen status
     */
    public function toggleJobStatus(int $jobId, int $employerId): array
    {
        $this->logInfo('Toggling job status', ['job_id' => $jobId]);

        $job = $this->jobRepo->findById($jobId);
        if (!$job || $job['employer_id'] != $employerId) {
            $this->addError('job', 'Job not found');
            return ['success' => false, 'status' => null];
        }

        if ($job['status'] === JobRepository::STATUS_CLOSED) {
            $success = $this->jobRepo->reopenJob($jobId);
            return ['success' => $success, 'status' => JobRepository::STATUS_PUBLISHED];
        }

        $success = $this->jobRepo->closeJob($jobId);
        return ['success' => $success, 'status' => JobRepository::STATUS_CLOSED];
    }

    /**
     * Delete job
     */
    public function deleteJob(int $jobId, int $employerId): bool
    {
        $this->logInfo('Deleting job', ['job_id' => $jobId]);

        $job = $this->jobRepo->findById($jobId);
        if (!$job || $job['employer_id'] != $employerId) {
            $this->addError('job', 'Job not found');
            return false;
        }

        // Check if there are applications
        $appCount = $this->appRepo->countJobApplications($jobId);
        if ($appCount > 0) {
            $this->addError('job', 'Cannot delete job with existing applications');
            return false;
        }

        return $this->jobRepo->delete($jobId);
    }

    // ==================== JOB VIEWING ====================

    /**
     * Get published job by slug (with view tracking)
     */
    public function getPublishedJob(string $slug, ?int $userId = null): ?array
    {
        $job = $this->jobRepo->findBySlug($slug);

        if (!$job) {
            return null;
        }

        // Track view
        $this->trackJobView($job['id'], $userId);

        // Check if user has applied
        if ($userId) {
            $job['has_applied'] = $this->appRepo->hasApplied($userId, $job['id']);
        }

        return $job;
    }

    /**
     * Track job view
     */
    private function trackJobView(int $jobId, ?int $userId = null): void
    {
        // Increment job view count
        $this->jobRepo->incrementViews($jobId);

    }

    /**
     * Get jobs with filters
     */
    public function getJobs(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $jobs = $this->jobRepo->getPublishedJobs($filters, $perPage, $offset);
        $total = $this->jobRepo->countPublishedJobs($filters);

        return [
            'data' => $jobs,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get employer jobs
     */
    public function getEmployerJobs(int $employerId, ?array $status, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;


        $jobs = $this->jobRepo->getEmployerJobs($employerId, $status, $perPage, $offset);
        $total = $this->jobRepo->countEmployerJobs($employerId, $status);

        // Add application stats for each job
        foreach ($jobs as &$job) {
            $job['application_stats'] = $this->appRepo->getJobApplicationStats($job['id']);
        }

        return [
            'data' => $jobs,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get featured jobs
     */
    public function getFeaturedJobs(int $limit = 6): array
    {
        return $this->jobRepo->getFeaturedJobs($limit);
    }

    /**
     * Get similar jobs
     */
    public function getSimilarJobs(int $jobId, int $limit = 5): array
    {
        return $this->jobRepo->getSimilarJobs($jobId, $limit);
    }

    public function searchJobs(string $query, ?array $filters = [], int $limit = 20, int $offset = 0): array
{
    // Optional: sanitize query, validate filters, etc.
    
    // Get results from repository
    $results = $this->jobRepo->searchJobs($query, $filters, $limit, $offset);
    
    // Get total count for pagination (you'll need a separate count method in repository)
    $total = $this->jobRepo->countSearchResults($query, $filters);
    
    return [
        'data' => $results,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'pages' => ceil($total / $limit)
    ];
}

     /**
     * Handle profile photo upload for job seeker
     */

    /**
     * Get a specific job for an employer (ensures ownership)
     */
    public function getEmployerJob(int $jobId, int $employerId): ?array
    {
        $sql = "SELECT j.*, e.company_name,
                       COALESCE(NULLIF(e.avatar, ''), NULLIF(u.avatar, '')) AS employer_avatar,
                       (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as applications_count,
                       (SELECT COUNT(*) FROM comments c WHERE c.commentable_type = 'job' AND c.commentable_id = j.id) as comments_count,
                       (SELECT COUNT(*) FROM likes lk WHERE lk.likeable_type = 'job' AND lk.likeable_id = j.id) as likes_count,
                       (SELECT COUNT(*) FROM saved_jobs sj WHERE sj.job_id = j.id) as saves_count
                FROM jobs j
                LEFT JOIN employer_profiles e ON e.id = j.employer_id
                LEFT JOIN users u ON u.id = e.user_id
                WHERE j.id = :job_id AND j.employer_id = :employer_id
                LIMIT 1";

        $job = $this->jobRepo->fetchOne($sql, [
            ':job_id' => $jobId,
            ':employer_id' => $employerId
        ]);

        if (!$job) {
            return null;
        }

        // Decode requirements and responsibilities (simple array conversion)
        $job['requirements'] = is_array($job['requirements']) ? $job['requirements'] : ($job['requirements'] ? explode("\n", $job['requirements']) : []);
        $job['responsibilities'] = is_array($job['responsibilities']) ? $job['responsibilities'] : ($job['responsibilities'] ? explode("\n", $job['responsibilities']) : []);

        $categories = $this->jobCatMapRepo->getJobCategories($jobId);
        $job['categories'] = $categories;
        $job['skills'] = []; // Initialize empty skills array
        return $job;
    }

    /**
     * Get public job details for any user without employer ownership requirement
     */
    public function getPublicJob(int $jobId): ?array
    {
        $sql = "SELECT j.*, e.company_name,
                       COALESCE(NULLIF(e.avatar, ''), NULLIF(u.avatar, '')) AS employer_avatar,
                       (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as applications_count,
                       (SELECT COUNT(*) FROM comments c WHERE c.commentable_type = 'job' AND c.commentable_id = j.id) as comments_count,
                       (SELECT COUNT(*) FROM likes lk WHERE lk.likeable_type = 'job' AND lk.likeable_id = j.id) as likes_count,
                       (SELECT COUNT(*) FROM saved_jobs sj WHERE sj.job_id = j.id) as saves_count
                FROM jobs j
                LEFT JOIN employer_profiles e ON e.id = j.employer_id
                LEFT JOIN users u ON u.id = e.user_id
                WHERE j.id = :job_id
                LIMIT 1";

        $job = $this->jobRepo->fetchOne($sql, [
            ':job_id' => $jobId
        ]);

        if (!$job) {
            return null;
        }

        // Decode requirements and responsibilities (simple array conversion)
        $job['requirements'] = is_array($job['requirements']) ? $job['requirements'] : ($job['requirements'] ? explode("\n", $job['requirements']) : []);
        $job['responsibilities'] = is_array($job['responsibilities']) ? $job['responsibilities'] : ($job['responsibilities'] ? explode("\n", $job['responsibilities']) : []);

        $categories = $this->jobCatMapRepo->getJobCategories($jobId);
        $job['categories'] = $categories;
        $job['skills'] = []; // Initialize empty skills array
        return $job;
    }

    /**
     * Get job statistics for employer dashboard
     */
    public function getEmployerJobStatistics(int $employerId): array
    {
        $stats = $this->jobRepo->getJobStatistics($employerId);

        // Calculate application rate
        if ($stats['total_views'] > 0) {
            $stats['application_rate'] = round(
                ($stats['total_applications'] / $stats['total_views']) * 100,
                2
            );
        } else {
            $stats['application_rate'] = 0;
        }

        return $stats;
    }
}
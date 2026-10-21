<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\JobService;
use App\Services\JobCategoryService;
use App\Services\AuthService;
use App\Helpers\ApiResponse;
use Exception;

/**
 * JobController - Handles all job-related HTTP requests
 */
class JobController extends BaseController
{
    private JobService $jobService;
    private JobCategoryService $categoryService;
    private AuthService $authService;

    public function __construct(
        JobService $jobService,
        JobCategoryService $categoryService,
        AuthService $authService
    ) {
        parent::__construct();
        $this->jobService = $jobService;
        $this->categoryService = $categoryService;
        $this->authService = $authService;
    }

    /**
     * Helper to get query parameter
     */
    private function getQueryParam(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Helper to get request body
     */
    private function getRequestBody(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    /**
     * GET /api/categories - Get all job categories
     */
    public function getCategories(): void
    {
        try {
            $categories = $this->categoryService->getAllCategories();
            ApiResponse::success($categories, 'Categories retrieved successfully');
        } catch (Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/jobs - Get jobs with filters
     */
    public function getJobs(): void
    {
        try {
            $page = (int)($this->getQueryParam('page', 1));
            $perPage = (int)($this->getQueryParam('per_page', 20));
            
            $filters = [
                'search' => $this->getQueryParam('search'),
                'job_type' => $this->getQueryParam('job_type'),
                'experience_level' => $this->getQueryParam('experience_level'),
                'is_remote' => $this->getQueryParam('is_remote') === 'true' ? 1 : null,
                'category_id' => $this->getQueryParam('category_id'),
                'location' => $this->getQueryParam('location')
            ];
            
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });
            
            $result = $this->jobService->getJobs($filters, $page, $perPage);
            ApiResponse::success($result, 'Jobs retrieved successfully');
            
        } catch (Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }
    /**
 * Search jobs with filters and pagination
 * 
 * GET /api/jobs/search?q=developer&page=1&per_page=20&job_type=full_time&is_remote=1&experience_level=mid&location=NewYork
 */
public function searchJobs(): void
{
    try {
        // Get search query (required)
        $query = trim($this->getQueryParam('q', ''));
        if (empty($query)) {
            ApiResponse::error('Search query is required', [], 422);
            return;
        }
        if (strlen($query) < 2) {
            ApiResponse::error('Search query must be at least 2 characters', [], 422);
            return;
        }

        // Pagination
        $page = (int) $this->getQueryParam('page', 1);
        $perPage = (int) $this->getQueryParam('per_page', 20);
        $offset = ($page - 1) * $perPage;

        // Additional filters
        $filters = [
            'job_type'        => $this->getQueryParam('job_type'),
            'experience_level'=> $this->getQueryParam('experience_level'),
            'is_remote'       => $this->getQueryParam('is_remote') === 'true' ? 1 : null,
            'category_id'     => $this->getQueryParam('category_id'),
            'location_id'     => $this->getQueryParam('location_id'),
            'salary_min'      => $this->getQueryParam('salary_min'),
            'salary_max'      => $this->getQueryParam('salary_max'),
            'currency'        => $this->getQueryParam('currency')
        ];
        // Remove null/empty filters
        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');

        // Call service method
        $result = $this->jobService->searchJobs($query, $filters, $perPage, $offset);

        ApiResponse::success($result, 'Jobs retrieved successfully');
    } catch (Exception $e) {
        error_log('Job search error: ' . $e->getMessage());
        ApiResponse::serverError('Failed to search jobs');
    }
}

    /**
     * GET /api/jobs/featured - Get featured jobs
     */
    public function getFeaturedJobs(): void
    {
        try {
            $limit = (int)($this->getQueryParam('limit', 6));
            $jobs = $this->jobService->getFeaturedJobs($limit);
            ApiResponse::success($jobs, 'Featured jobs retrieved successfully');
        } catch (Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/jobs/{slug} - Get single job by slug
     */
    public function getJob(string $slug): void
    {
        try {
            $userId = $this->authService->getCurrentUserId();
            $job = $this->jobService->getPublishedJob($slug, $userId);
            
            if (!$job) {
                ApiResponse::notFound('Job not found');
                return;
            }
            
            ApiResponse::success($job, 'Job retrieved successfully');
            
        } catch (Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/jobs/{id}/similar - Get similar jobs
     */
    public function getSimilarJobs(int $id): void
    {
        try {
            $limit = (int)($this->getQueryParam('limit', 5));
            $jobs = $this->jobService->getSimilarJobs($id, $limit);
            ApiResponse::success($jobs, 'Similar jobs retrieved successfully');
        } catch (Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }

    /**
     * POST /api/jobs - Create new job
     */
    public function createJob(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                ApiResponse::unauthorized('Unauthorized');
                return;
            }
            
            $employerId = $user['employer_id'] ?? null;
            if (!$employerId) {
                ApiResponse::forbidden('Only employers can create jobs');
                return;
            }
            
            $data = $this->getRequestBody();
            
            //Handle arrays from form
            if (isset($data['requirements']) && is_array($data['requirements'])) {
                $data['requirements'] = implode("\n", $data['requirements']);
            }
            
            if (isset($data['responsibilities']) && is_array($data['responsibilities'])) {
                $data['responsibilities'] = implode("\n", $data['responsibilities']);
            }
            
            $jobId = $this->jobService->createJob($employerId, $user['id'], $data);
            
            if (!$jobId) {
                $errors = $this->jobService->getErrors();
                ApiResponse::validationError($errors, $errors['job'] ?? 'Failed to create job');
                return;
            }
            
            // Handle categories using JobCategoryService
            if (!empty($data['categories']) && is_array($data['categories'])) {
                foreach ($data['categories'] as $categoryId) {
                    $this->categoryService->addJobCategory($jobId, $categoryId);
                }
            }
            
            ApiResponse::success($data, 'Job created successfully');
            
        } catch (Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/jobs/{id} - Update job
     */
    public function updateJob(array $params): void
    {
        try {
            $id = $params['id'];
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                ApiResponse::unauthorized('Unauthorized');
                $this->redirect('/login');
            }
            
            $employerId = $user['employer_id'] ?? null;
            if (!$employerId) {
                ApiResponse::forbidden('Only employers can update jobs');
                return;
            }
            
            $data = $this->getRequestBody();
            
            // // Handle arrays
            if (isset($data['requirements']) && is_array($data['requirements'])) {
                $data['requirements'] = implode("\n", $data['requirements']);
            }
            
            if (isset($data['responsibilities']) && is_array($data['responsibilities'])) {
                $data['responsibilities'] = implode("\n", $data['responsibilities']);
            }
            
             $result = $this->jobService->updateJob($id, $employerId, $data);
            
            if (!$result) {
                $errors = $this->jobService->getErrors();
                ApiResponse::validationError($errors, $errors['job'] ?? 'Failed to update job');
                return;
            }
            
            // Update categories if provided
            if (isset($data['categories']) && is_array($data['categories'])) {
                // Remove existing categories
                $this->categoryService->removeAllJobCategories($id);
                // Add new categories
                foreach ($data['categories'] as $categoryId) {
                   $this->categoryService->addJobCategory($id, $categoryId);
                }
            }
            
            ApiResponse::success('Job updated successfully');
            
        } catch (Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }

    /**
     * DELETE /api/jobs/{id} - Delete job
     */
    public function deleteJob(array $params): void
    {
        try {
            $id = $params['id'];
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                ApiResponse::unauthorized('Unauthorized');
                return;
            }
            
            $employerId = $user['employer_id'] ?? null;
            if (!$employerId) {
                ApiResponse::forbidden('Only employers can delete jobs');
                return;
            }
            
            $result = $this->jobService->deleteJob($id, $employerId);
            
            if (!$result) {
                $errors = $this->jobService->getErrors();
                ApiResponse::validationError($errors, $errors['job'] ?? 'Failed to delete job');
                return;
            }
            
            ApiResponse::success(null, 'Job deleted successfully');
            
        } catch (Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }

    /**
     * POST /api/jobs/{id}/publish - Publish job
     */
    public function publishJob(int $id): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                ApiResponse::unauthorized('Unauthorized');
                return;
            }
            
            $employerId = $user['employer_id'] ?? null;
            if (!$employerId) {
                ApiResponse::forbidden('Only employers can publish jobs');
                return;
            }
            
            $result = $this->jobService->publishJob($id, $employerId);
            
            if (!$result) {
                $errors = $this->jobService->getErrors();
                ApiResponse::validationError($errors, $errors['job'] ?? 'Failed to publish job');
                return;
            }
            
            $job = $this->jobService->getJobDataById($id);
            ApiResponse::success($job, 'Job published successfully');
            
        } catch (Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }

    /**
     * POST /api/jobs/{id}/close - Close job
     */
    public function closeJob(array $params): void
    {
        try {
            $id = $params['id'];
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                ApiResponse::unauthorized('Unauthorized');
                return;
            }
            
            $employerId = $user['employer_id'] ?? null;
            if (!$employerId) {
                ApiResponse::forbidden('Only employers can close jobs');
                return;
            }
            
            $result = $this->jobService->toggleJobStatus($id, $employerId);

            if (!$result['success']) {
                $errors = $this->jobService->getErrors();
                ApiResponse::validationError($errors, $errors['job'] ?? 'Failed to update job status');
                return;
            }

            $job = $this->jobService->getJobDataById($id);
            $message = $result['status'] === 'closed'
                ? 'Job ' .$job['title'] . ' successfully'
                : 'Job ' .$job['title'] . ' reopened successfully';

            ApiResponse::success($job, $message);
            
        } catch (Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/employer/jobs - Get employer jobs
     */
    public function getEmployerJobs(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                ApiResponse::unauthorized('Unauthorized');
                return;
            }
            
            $employerId = $user['employer_id'] ?? null;
            if (!$employerId) {
                ApiResponse::forbidden('Only employers can view their jobs');
                return;
            }
            
            $page = (int)($this->getQueryParam('page', 1));
            $perPage = (int)($this->getQueryParam('per_page', 20));
            $status = $this->getQueryParam('status');
            $statusArray = $status ? explode(',', $status) : null;
            
            $result = $this->jobService->getEmployerJobs($employerId, $statusArray, $page, $perPage);
            ApiResponse::success($result, 'Employer jobs retrieved successfully');
            
        } catch (Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }

    /**
     * Get Employer Single Job
     */
    public function getEmployerJob(array $params){
        try{
            $id = $params['id'];
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                ApiResponse::unauthorized('Unauthorized');
                return;
            }
            
            $employerId = $user['employer_id'] ?? null;
            if (!$employerId) {
                ApiResponse::forbidden('Only employers can view their jobs');
                return;
            }
            $result = $this->jobService->getJobDataById($id);
            if($result){
                ApiResponse::success($result,'Fetched Job details');
            }

        }
        catch(Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/employer/stats - Get employer job statistics
     */
    public function getEmployerStats(): void
    {
        try {
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                ApiResponse::unauthorized('Unauthorized');
                return;
            }
            
            $employerId = $user['employer_id'] ?? null;
            if (!$employerId) {
                ApiResponse::forbidden('Only employers can view stats');
                return;
            }
            
            $stats = $this->jobService->getEmployerJobStatistics($employerId);
            ApiResponse::success($stats, 'Job statistics retrieved successfully');
            
        } catch (Exception $e) {
            ApiResponse::serverError($e->getMessage());
        }
    }
}
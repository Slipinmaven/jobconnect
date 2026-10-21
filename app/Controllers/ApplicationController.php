<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\ApplicationService;
use App\Services\EmployerService;
use App\Helpers\ApiResponse;

/**
 * ApplicationController - Handles all job application HTTP requests
 * 
 * Endpoints:
 * - POST   /api/jobs/{jobId}/apply                 Submit application
 * - GET    /api/applications/user                  Get user's applications
 * - GET    /api/jobs/{jobId}/applications          Get applications for a job (employer)
 * - PUT    /api/applications/{id}/status           Update application status (employer)
 * - POST   /api/applications/{id}/view             Mark application as viewed (employer)
 * - POST   /api/applications/{id}/withdraw         Withdraw application (jobseeker)
 * - GET    /api/applications/stats                 Get employer application statistics
 * 
 * @package App\Controllers
 */
class ApplicationController extends BaseController
{
    private ApplicationService $applicationService;
    private EmployerService $employerService;

    public function __construct(ApplicationService $applicationService, EmployerService $employerService)
    {
        parent::__construct();
        $this->applicationService = $applicationService;
        $this->employerService = $employerService;
    }

    // ==================== APPLICATIONS ====================

    /**
     * Apply for a job
     * 
     * @param int $jobId Job ID
     * @return mixed
     */
    public function apply(array $params)
    {
        try {
            $jobId = $params['jobId'] ?? null;
            if (!$jobId) {
                return ApiResponse::error('Job ID is required', [], 422);
            }
            // Require authentication
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to apply for jobs');
            }

            // Get input
            $input = $this->getJsonInput();

            // Apply for job
            $application = $this->applicationService->applyForJob(
                $this->userId,
                $jobId,
                $input['cover_letter'] ?? null
            );

            if (!$application) {
                return ApiResponse::error(
                    'Failed to submit application',
                    $this->applicationService->getErrors(),
                    422
                );
            }

            return ApiResponse::success($application, 'Application submitted successfully', 201);

        } catch (\Throwable $e) {
            error_log('Error applying for job: ' . $e->getMessage());
            return ApiResponse::error('Failed to submit application', [], 500);
        }
    }

    public function employerApplications()
    {
        try {
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to view applications');
            }

            $page = (int) ($this->get('page') ?? 1);
            $perPage = (int) ($this->get('per_page') ?? 20);

            $applications = $this->applicationService->getEmployerApplications(
                $this->userId,
                null,
                $page,
                $perPage
            );
            $this->applicationService->clearErrors(); // Clear any previous errors

            if ($this->applicationService->hasErrors()) {
                return ApiResponse::error(
                    'Failed to fetch applications',
                    $this->applicationService->getErrors(),
                    422
                );
            }

            return ApiResponse::success($applications);

        } catch (\Throwable $e) {
            error_log('Error fetching employer applications: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch applications', [], 500);
        }
    }

    /**
     * Get current user's applications
     * 
     * @return mixed
     */
    public function myApplications()
    {
        try {
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to view your applications');
            }

            $status = $this->get('status') ? explode(',', $this->get('status')) : null;
            $page = (int) ($this->get('page') ?? 1);
            $perPage = (int) ($this->get('per_page') ?? 20);

            $applications = $this->applicationService->getUserApplications(
                $this->userId,
                $status,
                $page,
                $perPage
            );

            return ApiResponse::success($applications);

        } catch (\Throwable $e) {
            error_log('Error fetching user applications: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch applications', [], 500);
        }
    }

    /**
     * Get applications for a specific job (employer only)
     * 
     * @param int $jobId Job ID
     * @return mixed
     */
    public function jobApplications(array $params)
    {
        try {
            $jobId = $params['jobId'] ?? null;
            $employerId = (int)$this->employerService->getEmployerId($this->userId);
            if (!$jobId) {
                return ApiResponse::error('Job ID is required', [], 422);
            }
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to view applications');
            }

            // Check if user is employer (job owner)
            $status = $this->get('status') ? explode(',', $this->get('status')) : null;
            $page = (int) ($this->get('page') ?? 1);
            $perPage = (int) ($this->get('per_page') ?? 20);

            $applications = $this->applicationService->getJobApplications(
                $employerId,
                $jobId,
                $status,
                $page,
                $perPage
            );

            if ($this->applicationService->hasErrors()) {
                return ApiResponse::error(
                    'Failed to fetch applications',
                    $this->applicationService->getErrors(),
                    422
                );
            }

            return ApiResponse::success($applications);

        } catch (\Throwable $e) {
            error_log('Error fetching job applications: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch applications', [], 500);
        }
    }

    public function downloadApplicationResume(array $params)
    {
        try {
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to download this resume');
            }

            $applicationId = (int) ($params['id'] ?? 0);
            if ($applicationId <= 0) {
                return ApiResponse::error('Application ID is required', [], 422);
            }

            $employerId = (int) $this->employerService->getEmployerId($this->userId);
            if ($employerId <= 0) {
                return ApiResponse::error('Employer account required', [], 403);
            }

            $resumePath = $this->applicationService->getEmployerApplicationResumePath(
                $employerId,
                $applicationId
            );
            if ($resumePath === null) {
                return ApiResponse::error(
                    'Could not access this resume',
                    $this->applicationService->getErrors(),
                    404
                );
            }

            $relativePath = ltrim((string) (parse_url($resumePath, PHP_URL_PATH) ?? ''), '/');
            if (
                !str_starts_with($relativePath, 'uploads/cvs/')
                || str_contains($relativePath, '\\')
                || $relativePath !== 'uploads/cvs/' . basename($relativePath)
            ) {
                return ApiResponse::error('Resume file not found', [], 404);
            }

            $projectRoot = dirname(__DIR__, 2);
            $fullPath = null;
            foreach ([
                $projectRoot . '/public/' . $relativePath,
                $projectRoot . '/app/public/' . $relativePath
            ] as $candidate) {
                $realPath = realpath($candidate);
                if ($realPath !== false && is_file($realPath)) {
                    $fullPath = $realPath;
                    break;
                }
            }

            if ($fullPath === null) {
                return ApiResponse::error('Resume file not found', [], 404);
            }

            $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($fullPath));
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($fullPath));
            readfile($fullPath);
            exit;
        } catch (\Throwable $e) {
            error_log('Error downloading application resume: ' . $e->getMessage());
            return ApiResponse::error('Failed to download resume', [], 500);
        }
    }

    /**
     * Update application status (employer only)
     * 
     * @param int $applicationId Application ID
     * @return mixed
     */
    public function updateStatus(array $params)
    {
        try {
            $applicationId = $params['id'] ?? null;
            if (!$applicationId) {
                return ApiResponse::error('Application ID is required', [], 422);
            }
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to update status');
            }

            $input = $this->getJsonInput();

            if (empty($input['status'])) {
                return ApiResponse::error('Status is required', [], 422);
            }
            $employerId = (int) $this->employerService->getEmployerId($this->userId);
            $result = $this->applicationService->updateApplicationStatus(
                $employerId,
                $applicationId,
                $input['status'],
                $input['employer_notes'] ?? null
            );

            if (!$result) {
                return ApiResponse::error(
                    'Failed to update status',
                    $this->applicationService->getErrors(),
                    422
                );
            }

            return ApiResponse::success(null, 'Application status updated successfully');

        } catch (\Throwable $e) {
            error_log('Error updating application status: ' . $e->getMessage());
            return ApiResponse::error('Failed to update status', [], 500);
        }
    }

    /**
     * Mark application as viewed (employer only)
     * 
     * @param int $applicationId Application ID
     * @return mixed
     */
    public function markViewed(array $params)
    {
        try {
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to mark as viewed');
            }

            $applicationId = (int) ($params['id'] ?? 0);
            if ($applicationId <= 0) {
                return ApiResponse::error('Application ID is required', [], 422);
            }

            $result = $this->applicationService->markApplicationViewed(
                $this->userId,
                $applicationId
            );

            if (!$result) {
                return ApiResponse::error(
                    'Failed to mark as viewed',
                    $this->applicationService->getErrors(),
                    422
                );
            }

            return ApiResponse::success(null, 'Application marked as viewed');

        } catch (\Throwable $e) {
            error_log('Error marking application as viewed: ' . $e->getMessage());
            return ApiResponse::error('Failed to mark as viewed', [], 500);
        }
    }

    /**
     * Withdraw application (jobseeker only)
     * 
     * @param int $applicationId Application ID
     * @return mixed
     */
    public function withdraw(array $params)
    {
        try {
            $applicationId = (int)$params['id'] ?? null;
            if (!$applicationId) {
                return ApiResponse::error('Application ID is required', [], 422);
            }

            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to withdraw application');
            }

            $result = $this->applicationService->withdrawApplication(
                $this->userId,
                $applicationId
            );

            if (!$result) {
                return ApiResponse::error(
                    'Failed to withdraw application',
                    $this->applicationService->getErrors(),
                    422
                );
            }

            return ApiResponse::success(null, 'Application withdrawn successfully');

        } catch (\Throwable $e) {
            error_log('Error withdrawing application: ' . $e->getMessage());
            return ApiResponse::error('Failed to withdraw application', [], 500);
        }
    }

    // ==================== STATISTICS ====================

    /**
     * Get application statistics for employer dashboard
     * 
     * @return mixed
     */
    public function stats()
    {
        try {
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to view stats');
            }

            $stats = $this->applicationService->getEmployerApplicationStats($this->userId);

            return ApiResponse::success($stats);

        } catch (\Throwable $e) {
            error_log('Error fetching application stats: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch statistics', [], 500);
        }
    }

    // ==================== HELPER METHODS ====================

   function uploadFile($file, $targetDir, $allowedTypes = ['pdf', 'doc', 'docx'], $maxSize = 5242880) {
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload failed with error code: ' . $file['error']];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['error' => 'File too large. Max size: ' . ($maxSize / 1048576) . 'MB'];
    }
    
    // Validate file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        return ['error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes)];
    }
    
    // Create directory if needed
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = rtrim($targetDir, '/') . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => $targetPath, 'filename' => $filename];
    }
    
    return ['error' => 'Failed to save file'];
}
}
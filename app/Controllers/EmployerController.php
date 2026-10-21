<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\IndustryService;
use App\Services\EmployerService;
use App\Services\SessionManager;
use App\Services\JobService;
use App\Services\NotificationService;
use App\Helpers\ApiResponse;

class EmployerController extends BaseController
{
    private IndustryService $industryService;
    private EmployerService $employerService;
    private JobService $jobService;
    private SessionManager $session;
    private NotificationService $notificationService;

    public function __construct(
        IndustryService $industryService,
        EmployerService $employerService,
        JobService $jobService,
        SessionManager $session,
        NotificationService $notificationService
    ) {
        parent::__construct();
        $this->industryService = $industryService;
        $this->employerService = $employerService;
        $this->jobService = $jobService;
        $this->session = $session;
        $this->notificationService = $notificationService;
    }

    /**
     * Show employer profile setup wizard
     */

    /**
     * Handle profile setup form submission (API endpoint)
     * 
     * Expected POST data:
     * - type: "company" or "individual"
     * - name: company name or individual name
     * - description: about the company/individual
     * - industry_id: selected industry ID
     * - industry_slug: selected industry slug
     * - contact_email: email address
     * - contact_phone: phone number (optional)
     * - contact_name: contact person name (optional)
     * - website: website URL (optional)
     * - logo: file upload (optional)
     * - has_logo: "1" or "0"
     */
    public function setupProfile()
    {
        try {
            // Require authentication for API
            $this->requireApiAuth();

            // Check if it's a POST request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->error('Method not allowed', [], 405);
            }

            // Get JSON input if it's a JSON request, otherwise use POST
            $input = $this->wantsJson() ? $this->getJsonInput() : $_POST;

            // Validate required fields
            $required = ['type', 'name', 'description', 'industry_id', 'contact_email'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    return $this->validationError(
                        [$field => ucfirst(str_replace('_', ' ', $field)) . ' is required'],
                        'Validation failed'
                    );
                }
            }

            // Validate email format
            if (!filter_var($input['contact_email'], FILTER_VALIDATE_EMAIL)) {
                return $this->validationError(
                    ['contact_email' => 'Invalid email format'],
                    'Validation failed'
                );
            }

            // Validate type
            if (!in_array($input['type'], ['company', 'individual'])) {
                return $this->validationError(
                    ['type' => 'Type must be either company or individual'],
                    'Validation failed'
                );
            }

            // Accept the previous "logo" field while older clients may still use it.
            $avatarUpload = $_FILES['avatar'] ?? $_FILES['logo'] ?? null;
            $logoPath = null;
            if ($avatarUpload !== null && isset($avatarUpload['error']) && $avatarUpload['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($avatarUpload['error'] !== UPLOAD_ERR_OK) {
                    return $this->error('Logo upload failed', [], 400);
                }

                $logoPath = $this->handleLogoUpload($avatarUpload);
                if (!$logoPath) {
                    return $this->error('Failed to upload logo', [], 500);
                }
            }

            // Prepare profile data
            $profileData = [
                'user_id' => $this->userId,
                'type' => $input['type'],
                'name' => trim($input['name']),
                'description' => trim($input['description']),
                'industry_id' => (int) $input['industry_id'],
                'industry_slug' => $input['industry_slug'] ?? '',
                'contact_email' => trim($input['contact_email']),
                'contact_phone' => trim($input['contact_phone'] ?? ''),
                'contact_name' => trim($input['contact_name'] ?? ''),
                'website' => trim($input['website'] ?? ''),
                'avatar' => $logoPath,
                'has_logo' => !empty($logoPath) ? 1 : 0,
                'status' => 'active',
                'profile_completed' => 1
            ];

            // Save profile using service
            $result = $this->employerService->saveProfile($this->userId, $profileData);

            if (!$result) {
                $errors = $this->employerService->getErrors();
                return $this->validationError($errors, 'Failed to create profile');
            }

            // Log success
            error_log("Profile created successfully for user: {$this->userId}");

            // Return success response
            return $this->success(
                ['redirect' => '/employer/dashboard'],
                'Profile created successfully'
            );

        } catch (\Exception $e) {
            error_log('Error in setupProfile: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return $this->error('An error occurred while creating your profile', [], 500);
        }
    }

    /**
     * Handle logo upload
     * 
     * @param array $file $_FILES['logo']
     * @return string|null Path to uploaded file or null on failure
     */
    private function handleLogoUpload(array $file): ?string
    {
        try {
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                error_log('Invalid file type: ' . $file['type']);
                return null;
            }

            // Validate file size (max 2MB)
            $maxSize = 2 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                error_log('File too large: ' . $file['size']);
                return null;
            }

            // Create upload directory if it doesn't exist
            $uploadDir = BASE_PATH . '/public/uploads/employer_logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'employer_' . $this->userId . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                error_log('Failed to move uploaded file');
                return null;
            }

            // Return relative path for database storage
            return '/uploads/employer_logos/' . $filename;

        } catch (\Exception $e) {
            error_log('Error handling logo upload: ' . $e->getMessage());
            return null;
        }

    }

    public function showDashboard()
    {
        if (!$this->isEmployer()) {
            return $this->redirect('/');
        }

        return $this->view('employer/dashboard', [
            'title' => 'Employer Dashboard - JobConnect'
        ], 'dashboard');
    }

    public function showJobs()
    {
        // Check authentication
        if (!$this->userId) {
            return $this->redirect('/login');
        }

        // Get employer ID
        $employerId = $this->employerService->getEmployerId($this->userId);

        // If no employer ID, user is not an employer
        if (!$employerId) {
            $_SESSION['flash']['error'] = 'No employer profile found. Please complete your employer profile first.';
            return $this->redirect('/employer/profile-setup');
        }

        // Get pagination parameters from URL
        $page = (int) ($this->get('page') ?? 1);
        $perPage = (int) ($this->get('per_page') ?? 10);
        $status = $this->get('status'); // Optional status filter (published, draft, closed)

        // Validate page (minimum 1)
        $page = max(1, $page);

        // Validate per_page (limit to reasonable values)
        $perPage = min(max(10, $perPage), 50);

   

        return $this->view('employer/jobs', [
            'title' => 'My Jobs - JobConnect',
            'css' => ['employer/jobs'],
            'js' => ['employer/jobs'],
 
            'current_status' => $status,
            'status_options' => [
                'all' => 'All Jobs',
                'published' => 'Published',
                'draft' => 'Draft',
                'closed' => 'Closed'
            ]
        ], 'dashboard');
    }

    public function showCreateJob()
    {
        if (!$this->isEmployer()) {
            return $this->redirect('/');
        }

        return $this->view('employer/jobs/job', [
            'title' => 'Create Job - JobConnect',
            'css' => ['employer/createJob'],
            'js' => ['employer/createJob'],
        ]);
    }
    public function showEditJob(array $params)
    {
        if (!$this->isEmployer()) {
            return $this->redirect('/');
        }

        $jobId = isset($params['id']) ? (int)$params['id'] : 0;
        if ($jobId <= 0) {
            $_SESSION['flash']['error'] = 'Invalid job ID.';
            return $this->redirect('/employer/jobs');
        }

        return $this->view('employer/jobs/job', [
            'title' => 'Edit Job - JobConnect',
            'css' => ['employer/createJob'],
            'js' => ['employer/createJob']
        ]);
    }

    public function showJobDetails(array $params)
    {
        $jobId = isset($params['id']) ? (int)$params['id'] : 0;
        if ($jobId <= 0) {
            $_SESSION['flash']['error'] = 'Invalid job ID.';
            return $this->redirect('/');
        }

        // Check if user is authenticated employer who owns this job
        $isEmployer = $this->isEmployer();
        $employerId = null;
        $job = null;

        if ($isEmployer) {
            // Try to get as employer (with full stats and management access)
            $employerId = $this->employerService->getEmployerId($this->userId);
            if ($employerId) {
                $job = $this->jobService->getEmployerJob($jobId, (int)$employerId);
            }
        }

        // If not employer or doesn't own job, get public version
        if (!$job) {
            $job = $this->jobService->getPublicJob($jobId);
        }

        if (!$job) {
            $_SESSION['flash']['error'] = 'Job not found.';
            return $this->redirect('/');
        }

        // Check if job is published (public access requires published status)
        if (!$isEmployer && $job['status'] !== 'published') {
            $_SESSION['flash']['error'] = 'Job is not available.';
            return $this->redirect('/');
        }

        return $this->view('employer/jobs/details', [
            'title' => 'Job Details - JobConnect',
            'css' => ['employer/jobDetails'],
            'js' => ['employer/jobDetails'],
            'job' => $job
        ], 'dashboard');
    }

    public function showApplications()
    {
        if (!$this->isEmployer()) {
            $_SESSION['flash']['error'] = 'Unauthorized access to applications page';
            return $this->redirect('/');
        }

        return $this->view('employer/applications', [
            'title' => 'Job Applications - JobConnect',
            'css' => ['employer/applications'],
            'js' => ['employer/applications']
        ], 'dashboard');
    }

    public function showInterviews()
    {
        if (!$this->isEmployer()) {
            $_SESSION['flash']['error'] = 'Unauthorized access to interviews page';
            return $this->redirect('/');
        }

        return $this->view('employer/interviews', [
            'title' => 'Job Interviews - JobConnect',
            'css' => ['employer/interviews'],
            'js' => ['employer/interviews']
        ], 'dashboard');
    }

    public function showSettings()
    {
        if (!$this->isEmployer()) {
            $_SESSION['flash']['error'] = 'Unauthorized access to settings page';
            return $this->back();
        }

        return $this->view('employer/settings', [
            'title' => 'Settings - JobConnect',
            'css' => ['employer/settings'],
        ], 'dashboard');
    }

    public function getSettings()
    {
        $this->requireApiAuth();
        $this->requireApiRole('employer');

        try {
            $settings = $this->employerService->getSettings($this->userId);
            $this->success($settings);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    public function showNotifications(){
        if (!$this->isEmployer()) {
            $_SESSION['flash']['error'] = 'Unauthorized access to notifications page';
            return $this->redirect('/login');
        }

        return $this->view('employer/notifications', [
            'title' => 'Notifications - JobConnect',
            'css' => ['employer/notifications'],
            'js' => ['employer/notifications']
        ], 'dashboard');
    }

    public function getEmployerInfo(){
        $this->requireApiAuth();
        $profileData = $this->employerService->getProfile($this->userId);
        if ($profileData === null) {
            return $this->error('Profile not found', [], 404);
        }
        $profile = $profileData['profile'] ?? [];
        $user = $profileData['user'] ?? [];
        $notifications = $this->notificationService->getUnreadCount($this->userId);
        return $this->success([
            'profile' => $profile,
            'user' => $user,
            'unread_notifications' => $notifications,
        ], 'Employer info retrieved successfully');

    }

    /**
     * Save user theme preference
     * POST /api/employer/save-theme
     * Body: { "theme": "light" | "dark" }
     */
    public function saveTheme()
    {
        try {
            // Require authentication
            $this->requireApiAuth();

            // Check if it's a POST request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->error('Method not allowed', [], 405);
            }

            // Get JSON input
            $input = $this->getJsonInput();
            $theme = $input['theme'] ?? null;

            // Validate theme value
            if (!$theme || !in_array($theme, ['light', 'dark'])) {
                return $this->validationError(['theme' => 'Theme must be "light" or "dark"']);
            }

            // Store in session
            $_SESSION['user_theme'] = $theme;

            return $this->success(['theme' => $theme], 'Theme preference saved successfully');

        } catch (\Exception $e) {
            error_log('Error saving theme: ' . $e->getMessage());
            return $this->error('An error occurred while saving theme', [], 500);
        }
    }

    /**
     * Update employer company information
     * POST /api/employer/update-profile
     */
    public function updateProfile()
    {
        try {
            $this->requireApiAuth();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->error('Method not allowed', [], 405);
            }

            $input = $this->getJsonInput();

            // Basic validation
            if (empty($input['company_name'] ?? null) && empty($input['employer_type'] ?? null)) {
                return $this->validationError(['profile' => 'At least company name or type is required']);
            }
            $employerId = (int) $this->employerService->getEmployerId($this->userId);

            // Update via service
            $result = $this->employerService->updateProfile($employerId, $input);

            if (!$result) {
                $errors = $this->employerService->getErrors();
                return $this->validationError($errors, 'Failed to update profile');
            }

            return $this->success(null, 'Company profile updated successfully');

        } catch (\Exception $e) {
            error_log('Error updating employer profile: ' . $e->getMessage());
            return $this->error('An error occurred while updating profile', [], 500);
        }
    }
}
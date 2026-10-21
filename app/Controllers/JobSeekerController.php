<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Services\JobSeekerService;
use App\Services\NotificationService;
use App\Helpers\ApiResponse;

class JobSeekerController extends BaseController
{
    private JobSeekerService $jobSeekerService;
    private NotificationService $notificationService;

    public function __construct(JobSeekerService $jobSeekerService, NotificationService $notificationService)
    {
        parent::__construct();
        $this->jobSeekerService = $jobSeekerService;
        $this->notificationService = $notificationService;
    }

    public function saveProfileFromWizard()
    {
        // Check authentication and jobseeker role
        if (!$this->isJobSeeker()) {
            return;
        }

        try {
            // Get JSON input
            $input = $this->getJsonInput();

            // Let the service handle validation and saving
            $result = $this->jobSeekerService->submitWizardProfile($this->userId, $input);

            if (!$result) {
                $errors = $this->jobSeekerService->getErrors();
                return ApiResponse::error('Failed to save profile', $errors, 422);
            }

            return ApiResponse::success(null, 'Profile created successfully');

        } catch (\Throwable $e) {
            error_log('Error saving profile: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return ApiResponse::error('Failed to update profile: ' . $e->getMessage(), [], 500);
        }
    }

    protected function isJobSeeker(): bool
    {
        // Check authentication
        $userId = $this->isAuthenticated();
        if (!$userId) {
            ApiResponse::unauthorized(['success' => false, 'message' => 'Unauthorized']);
            return false;
        }

        // Check if user has jobseeker role - use the parent method or session data
        // DON'T call $this->isJobSeeker() again!
        if (!$this->hasRole('jobseeker')) {
            ApiResponse::forbidden(['success' => false, 'message' => 'Forbidden: Access is allowed for job seekers only']);
            return false;
        }

        return true;
    }

    /**
     * Get jobseeker profile
     */
    public function getProfile()
    {
        try {
            // Require authentication
            $this->requireApiAuth();

            // Check if it's a GET request
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                return $this->error('Method not allowed', [], 405);
            }

            // Get profile from repository
            $profile = $this->jobSeekerService->getProfile($this->userId);

            if (!$profile) {
                return $this->error('Profile not found', [], 404);
            }

            return $this->success($profile, 'Profile retrieved successfully');

        } catch (\Exception $e) {
            error_log('Error getting profile: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving profile', [], 500);
        }
    }

    /**
     * Handle profile photo upload
     */
    public function uploadProfilePhoto()
    {
        try {
            // Require authentication
            $this->requireApiAuth();

            // Check if it's a POST request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->error('Method not allowed', [], 405);
            }

            // Handle photo upload if present
            $photoPath = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $photoPath = $this->handleProfilePhotoUpload($_FILES['photo']);
                if (!$photoPath) {
                    return $this->error('Failed to upload profile photo', [], 500);
                }
            } else {
                return $this->error('No photo file provided', [], 400);
            }

            // Update user's avatar
            $result = $this->jobSeekerService->updateProfilePhoto($this->userId, $photoPath);

            if (!$result) {
                $errors = $this->jobSeekerService->getErrors();
                return $this->validationError($errors, 'Failed to update profile photo');
            }

            $avatarUrl = '/' . ltrim($photoPath, '/');
            return $this->success(
                ['photo_path' => $avatarUrl, 'avatar_url' => $avatarUrl],
                'Profile photo updated successfully'
            );

        } catch (\Exception $e) {
            error_log('Error uploading profile photo: ' . $e->getMessage());
            return $this->error('An error occurred while uploading profile photo', [], 500);
        }
    }

    /**
     * Handle profile photo upload
     * 
     * @param array $file $_FILES['photo']
     * @return string|null Path to uploaded file or null on failure
     */
    private function handleProfilePhotoUpload(array $file): ?string
    {
        try {
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                error_log('Invalid file type: ' . $file['type']);
                return null;
            }

            // Validate file size (max 5MB)
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxSize) {
                error_log('File too large: ' . $file['size']);
                return null;
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $this->userId . '_' . time() . '.' . $extension;
            $uploadDir = dirname(__DIR__, 2) . '/public/uploads/profile_photos';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                    error_log('Failed to create profile photo directory: ' . $uploadDir);
                    return null;
                }
            }

            // Move uploaded file
            $fullPath = $uploadDir . '/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $fullPath)) {
                return '/uploads/profile_photos/' . $filename;
            } else {
                error_log('Failed to move uploaded file to: ' . $fullPath);
                return null;
            }

        } catch (\Exception $e) {
            error_log('Exception in handleProfilePhotoUpload: ' . $e->getMessage());
            return null;
        }
    }

    public function getUserInfo(){
        $this->requireApiAuth();
        $profileData = $this->jobSeekerService->getProfile($this->userId);
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
        ], 'User info retrieved successfully');
        
    }

    public function showDashboard()
    {
        if (!$this->isJobSeeker()) {
            $this->withFlash('error', 'Unauthorized access to dashboard');
            return $this->redirect('/login');
        }

        // Render jobseeker dashboard view
        return $this->view('jobseeker/dashboard', [
            'title' => 'Job Seeker Dashboard',
            'css' => ['jobseeker/dashboard'],
        ], 'dashboard');
    }

    public function showJobs()
    {
        if (!$this->isJobSeeker()) {
            $this->withFlash('error', 'Unauthorized access to jobs page');
            return $this->redirect('/login');
        }

        // Render jobseeker jobs view
        return $this->view('jobseeker/jobs', [
            'title' => 'Job Seeker Jobs',
            'css' => ['jobseeker/jobs'],
            'js' => ['jobseeker/jobs']
        ], 'dashboard');
    }

    public function showApplications()
    {
        if (!$this->isJobSeeker()) {
            $this->withFlash('error', 'Unauthorized access to applications page');
            return $this->redirect('/login');
        }

        // Render jobseeker applications view
        return $this->view('jobseeker/applications', [
            'title' => 'My Applications',
            'js' => ['jobseeker/applications']
        ], 'dashboard');
    }

    public function showSettings()
    {
        if (!$this->isJobSeeker()) {
            $this->withFlash('error', 'Unauthorized access to settings page');
            return $this->redirect('/login');
        }

        // Render jobseeker settings view
        return $this->view('jobseeker/settings', [
            'title' => 'Account Settings',
            'js' => ['jobseeker/settings']
        ], 'dashboard');
    }

    public function getSettings()
    {
        try {
            // Require authentication
            $this->requireApiAuth();

            // Check if it's a GET request
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                return $this->error('Method not allowed', [], 405);
            }

            // Get settings from service
            $settings = $this->jobSeekerService->getProfile($this->userId);

            if ($settings === null) {
                return $this->error('Settings not found', [], 404);
            }

            return $this->success($settings, 'Settings retrieved successfully');

        } catch (\Exception $e) {
            error_log('Error getting settings: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving settings', [], 500);
        }
    }

    public function showSavedJobs()
    {
        if (!$this->isJobSeeker()) {
            $this->withFlash('error', 'Unauthorized access to saved jobs page');
            return $this->redirect('/login');
        }

        // Render jobseeker saved jobs view
        return $this->view('jobseeker/savedJobs', [
            'title' => 'Saved Jobs',
            'css' => ['jobseeker/savedJobs'],
            'js' => ['jobseeker/savedJobs']
        ], 'dashboard');
    }

    public function showNotifications()
    {
        if (!$this->isJobSeeker()) {
            $this->withFlash('error', 'Unauthorized access to notifications page');
            return $this->redirect('/login');
        }

        // Render jobseeker notifications view
        return $this->view('jobseeker/notifications', [
            'title' => 'Notifications',
            'js' => ['jobseeker/notifications']
        ], 'dashboard');
    }
public function showInterviews()
{
    if (!$this->isJobSeeker()) {
        $this->withFlash('error', 'Unauthorized access to interviews page');
        return $this->redirect('/login');
    }

    $profileData = $this->jobSeekerService->getProfile($this->userId);
    if ($profileData === null) {
        $this->withFlash('error', 'Profile not found');
        return $this->back();
    }

    // The profile data is nested under 'profile' key
    $profile = $profileData['profile'] ?? [];

    $firstName = $profile['first_name'] ?? '';
    $lastName = $profile['last_name'] ?? '';
    $name = trim($firstName . ' ' . $lastName);
    if ($name === '') {
        $name = $user['email'] ?? 'User'; // fallback to email
    }
    $prof_title = $profile['professional_title'] ?? '';

    // Render jobseeker interviews view
    return $this->view('jobseeker/interviews', [
        'title' => 'My Interviews',
        'prof_title' => $prof_title,
        'name' => $name,
        'js' => ['jobseeker/interviews']
    ], 'dashboard');
}

    /**
     * Handle CV upload
     */
    public function uploadCV()
    {
        try {
            // Require authentication
            $this->requireApiAuth();

            // Check if it's a POST request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->error('Method not allowed', [], 405);
            }

            // Handle CV upload if present
            $cvPath = null;
            if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
                $cvPath = $this->handleCVUpload($_FILES['resume']);
                if (!$cvPath) {
                    return $this->error('Failed to upload CV', [], 500);
                }
            } else {
                return $this->error('No CV file provided', [], 400);
            }

            // Update user's CV
            $result = $this->jobSeekerService->updateCV($this->userId, $cvPath);

            if (!$result) {
                $errors = $this->jobSeekerService->getErrors();
                return $this->validationError($errors, 'Failed to update CV');
            }

            return $this->success(
                ['cv_path' => $cvPath],
                'CV uploaded successfully'
            );

        } catch (\Exception $e) {
            error_log('Error uploading CV: ' . $e->getMessage());
            return $this->error('An error occurred while uploading CV', [], 500);
        }
    }

    /**
     * Handle CV download
     */
    public function downloadCV()
    {
        try {
            // Require authentication
            $this->requireApiAuth();

            // Get user's CV path
            $cvPath = $this->jobSeekerService->getCVPath($this->userId);

            if (!$cvPath) {
                return $this->error('No CV found', [], 404);
            }

            $fullPath = $this->resolveCVFilePath($cvPath);

            if ($fullPath === null) {
                return $this->error('CV file not found', [], 404);
            }

            // Get filename from path
            $filename = basename($cvPath);

            // Set headers for download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($fullPath));

            // Output file
            readfile($fullPath);
            exit;

        } catch (\Exception $e) {
            error_log('Error downloading CV: ' . $e->getMessage());
            return $this->error('An error occurred while downloading CV', [], 500);
        }
    }

    /**
     * Handle CV deletion
     */
    public function deleteCV()
    {
        try {
            // Require authentication
            $this->requireApiAuth();

            // Check if it's a POST request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->error('Method not allowed', [], 405);
            }

            // Get current CV path before deletion
            $cvPath = $this->jobSeekerService->getCVPath($this->userId);

            // Delete CV from database
            $result = $this->jobSeekerService->deleteCV($this->userId);

            if (!$result) {
                $errors = $this->jobSeekerService->getErrors();
                return $this->validationError($errors, 'Failed to delete CV');
            }

            // Delete physical file if it exists
            if ($cvPath) {
                $fullPath = __DIR__ . '/../public/' . $cvPath;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            return $this->success(null, 'CV deleted successfully');

        } catch (\Exception $e) {
            error_log('Error deleting CV: ' . $e->getMessage());
            return $this->error('An error occurred while deleting CV', [], 500);
        }
    }

    /**
     * Save user theme preference
     * POST /api/jobseeker/save-theme
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
     * Update user work preferences
     * POST /api/jobseeker/update-preferences
     * Body: { "preferences": { "openToWork": bool, "remote": bool, "relocation": bool } }
     */
    public function updatePreferences()
    {
        try {
            $this->requireApiAuth();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->error('Method not allowed', [], 405);
            }

            $input = $this->getJsonInput();
            $preferences = $input['preferences'] ?? [];

            // Validate preferences exist
            if (empty($preferences)) {
                return $this->validationError(['preferences' => 'Preferences data is required']);
            }

            // Update preferences via service
            $result = $this->jobSeekerService->updatePreferences($this->userId, $preferences);

            if (!$result) {
                $errors = $this->jobSeekerService->getErrors();
                return $this->validationError($errors, 'Failed to update preferences');
            }

            return $this->success(['preferences' => $preferences], 'Preferences updated successfully');

        } catch (\Exception $e) {
            error_log('Error updating preferences: ' . $e->getMessage());
            return $this->error('An error occurred while updating preferences', [], 500);
        }
    }

    /**
     * Update career information
     * POST /api/jobseeker/update-career
     * Body: { "yearsExperience": int }
     */
    public function updateCareer()
    {
        try {
            $this->requireApiAuth();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->error('Method not allowed', [], 405);
            }

            $input = $this->getJsonInput();

            // Update career via service
            $result = $this->jobSeekerService->updateCareer($this->userId, $input);

            if (!$result) {
                $errors = $this->jobSeekerService->getErrors();
                return $this->validationError($errors, 'Failed to update career information');
            }

            return $this->success(null, 'Career information updated successfully');

        } catch (\Exception $e) {
            error_log('Error updating career: ' . $e->getMessage());
            return $this->error('An error occurred while updating career information', [], 500);
        }
    }

    /**
     * Update profile basic info
     * POST /api/jobseeker/update-profile
     * Body: { "personal": { "firstName": string, "lastName": string, "professionalTitle": string }, "summary": string }
     */
    public function updateProfile()
    {
        try {
            $this->requireApiAuth();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->error('Method not allowed', [], 405);
            }

            $input = $this->getJsonInput();

            // Validate required fields
            $errors = [];
            if (empty($input['personal']['firstName'] ?? null)) {
                $errors['firstName'] = 'First name is required';
            }
            if (empty($input['personal']['lastName'] ?? null)) {
                $errors['lastName'] = 'Last name is required';
            }
            if (empty($input['personal']['professionalTitle'] ?? null)) {
                $errors['professionalTitle'] = 'Professional title is required';
            }
            if (empty($input['summary'] ?? null)) {
                $errors['summary'] = 'Professional summary is required';
            }

            if (!empty($errors)) {
                return $this->validationError($errors);
            }

            // Update profile via service
            $result = $this->jobSeekerService->updateProfile($this->userId, $input);

            if (!$result) {
                $errors = $this->jobSeekerService->getErrors();
                return $this->validationError($errors, 'Failed to update profile');
            }

            return $this->success(null, 'Profile updated successfully');

        } catch (\Exception $e) {
            error_log('Error updating profile: ' . $e->getMessage());
            return $this->error('An error occurred while updating profile', [], 500);
        }
    }

    /**
     * Handle CV upload
     *
     * @param array $file $_FILES['cv']
     * @return string|null Path to uploaded file or null on failure
     */
    private function handleCVUpload(array $file): ?string
    {
        try {
            // Validate file type
            $allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            if (!in_array($file['type'], $allowedTypes)) {
                error_log('Invalid file type: ' . $file['type']);
                return null;
            }

            // Validate file size (max 5MB)
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxSize) {
                error_log('File too large: ' . $file['size']);
                return null;
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'cv_' . $this->userId . '_' . time() . '.' . $extension;
            $uploadPath = '/uploads/cvs/' . $filename;
            $uploadDir = dirname(__DIR__, 2) . '/public/uploads/cvs';

            // Ensure upload directory exists
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                    error_log('Failed to create CV upload directory: ' . $uploadDir);
                    return null;
                }
            }

            // Move uploaded file
            $fullPath = $uploadDir . '/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $fullPath)) {
                return $uploadPath;
            } else {
                error_log('Failed to move uploaded file to: ' . $fullPath);
                return null;
            }

        } catch (\Exception $e) {
            error_log('Exception in handleCVUpload: ' . $e->getMessage());
            return null;
        }
    }

    private function resolveCVFilePath(string $cvPath): ?string
    {
        $relativePath = ltrim((string) (parse_url($cvPath, PHP_URL_PATH) ?? ''), '/');
        if (
            !str_starts_with($relativePath, 'uploads/cvs/')
            || str_contains($relativePath, '\\')
            || $relativePath !== 'uploads/cvs/' . basename($relativePath)
        ) {
            return null;
        }

        $projectRoot = dirname(__DIR__, 2);
        foreach ([
            $projectRoot . '/public/' . $relativePath,
            $projectRoot . '/app/public/' . $relativePath
        ] as $candidate) {
            $realPath = realpath($candidate);
            if ($realPath !== false && is_file($realPath)) {
                return $realPath;
            }
        }

        return null;
    }

}

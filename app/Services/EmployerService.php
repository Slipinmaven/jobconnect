<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\EmployerRepository;
use App\Repositories\UserRepository;
use App\Repositories\IndustryRepository;
use App\Repositories\UserLocationRepository;
use Exception;

/**
 * EmployerService - Handles all employer business logic
 * 
 * BUSINESS RULES:
 * - Profile must be at least 60% complete to post jobs
 * - Company name and contact email are required
 * - Premium employers get featured placement
 * - Only verified employers can post premium jobs
 */
class EmployerService extends BaseService
{
    private EmployerRepository $employerRepo;
    private UserRepository $userRepo;
    private IndustryRepository $industryRepo;
    private UserLocationRepository $locationRepo;
    private $employerId = null;

    public function __construct(
        EmployerRepository $employerRepo,
        UserRepository $userRepo,
        IndustryRepository $industryRepo,
        UserLocationRepository $locationRepo
    ) {
        parent::__construct();
        $this->employerRepo = $employerRepo;
        $this->userRepo = $userRepo;
        $this->industryRepo = $industryRepo;
        $this->locationRepo = $locationRepo;
    }

    // ==================== PROFILE MANAGEMENT ====================

    /**
     * Create or update employer profile
     * 
     * @param int $userId
     * @param array $data
     * @return array|null
     */

    public function getEmployerId(int $userId): ?int
    {
        if ($this->employerId !== null) {
            return $this->employerId;
        }

        $profile = $this->employerRepo->findByUser($userId);

        if ($profile) {
            $this->employerId = $profile['id'];
            return $this->employerId;
        }

        // Return null when user is not an employer
        return null;
    }
   public function saveProfile(int $userId, array $data): ?array
{
    $this->logInfo('Saving employer profile', [
        'user_id' => $userId,
        'has_avatar' => !empty($data['avatar'])
    ]);
    $this->clearErrors();

    try {
        // Define allowed fields for sanitization - these are the FORM field names
        $allowedFields = [
            'type',              // 'company' or 'individual' (from form)
            'name',              // company name or individual name
            'description',       // company description
            'industry_id',       // selected industry ID
            'industry_slug',     // industry slug for URLs
            'contact_email',     // contact email
            'contact_phone',     // contact phone (optional)
            'contact_name',      // contact person name (optional)
            'website',           // website URL (optional)
            'avatar',            // uploaded company logo path
            'has_logo'           // whether logo was uploaded
        ];

        // Sanitize input (keeping form field names)
        $data = $this->sanitize($data, $allowedFields);

        // Validate required fields
        $required = ['type', 'name', 'description', 'industry_id', 'contact_email'];
        if (!$this->validateRequired($data, $required)) {
            $this->logError('Required fields missing', [
                'required' => $required,
                'provided' => array_keys($data)
            ]);
            return null;
        }

        // Validate email
        if (!empty($data['contact_email']) && !$this->validateEmail($data['contact_email'])) {
            $this->addError('contact_email', 'Invalid email format');
            return null;
        }

        // Validate website if provided
        if (!empty($data['website']) && !$this->validateUrl($data['website'])) {
            $this->addError('website', 'Invalid website URL');
            return null;
        }

        // Validate employer type
        if (!empty($data['type'])) {
            $allowedTypes = ['company', 'individual'];
            if (!$this->validateInArray($data['type'], 'type', $allowedTypes)) {
                return null;
            }
        }

        // Validate industry
        if (!empty($data['industry_id'])) {
            $industry = $this->industryRepo->findById($data['industry_id']);
            if (!$industry) {
                $this->addError('industry_id', 'Selected industry does not exist');
                return null;
            }
        }

        $location = $this->locationRepo->getLatestLocation($userId);
        if ($location) {
            $data['user_location_id'] = $location['id'];
        }

        // Prepare data for repository - THIS IS WHERE WE MAP TO DATABASE FIELD NAMES
        $repoData = [
            'employer_type' => $data['type'],              // form 'type' → db 'employer_type'
            'company_name' => $data['name'],                // form 'name' → db 'company_name'
            'company_description' => $data['description'],  // form 'description' → db 'company_description'
            'industry_id' => $data['industry_id'],
            'location_id' => $data['user_location_id'] ?? null,
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'] ?? '',
            'contact_name' => $data['contact_name'] ?? '',
            'website' => $data['website'] ?? '',
            'avatar' => $data['avatar'] ?? null,
            'is_active' => 1
        ];

        // Save profile using repository - passing database field names
        $profileId = $this->employerRepo->createOrUpdateProfile($userId, $repoData);

        if (!$profileId) {
            $this->logError('Failed to save employer profile', ['user_id' => $userId]);
            $this->addError('profile', 'Failed to save profile');
            return null;
        }

        $this->logInfo('Employer profile saved successfully', [
            'user_id' => $userId,
            'profile_id' => $profileId
        ]);

        return $this->getProfile($userId);

    } catch (Exception $e) {
        $this->logError('Exception in saveProfile', [
            'user_id' => $userId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        $this->addError('profile', 'An error occurred while saving profile');
        return null;
    }
}

    public function getSettings($userId){
        $employerId = (int) $this->getEmployerId($userId);
        if($employerId === null){
            $this->logError('Employer ID not found for user', ['user_id' => $userId]);
            return null;
        }   
        $user = $this->userRepo->findById($userId); 
        $profile = $this->employerRepo->getCompleteProfile($employerId);
        return [
            'user_info' => $user,
            'profile' => $profile
        ];
    }

/**
 * Validate URL format
 * 
 * @param string $url URL to validate
 * @return bool Valid or not
 */
private function validateUrl(string $url): bool
{
    // Allow empty URLs
    if (empty($url)) {
        return true;
    }
    
    // Add scheme if missing
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = 'http://' . $url;
    }
    
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

    /**
     * Get complete employer profile
     * 
     * @param int $userId
     * @return array|null
     */
    public function getProfile(int $userId): ?array
    {
        $this->logInfo('Fetching employer profile', ['user_id' => $userId]);

        try {
            $profile = $this->employerRepo->findByUser($userId);

            if (!$profile) {
                $this->logInfo('No profile found for user', ['user_id' => $userId]);
                return null;
            }

            $completeProfile = $this->employerRepo->getCompleteProfile($profile['id']);

            if ($completeProfile) {
                // Add completion status
                $completeProfile['completion'] = $this->employerRepo->getCompletionStatus($profile['id']);
                
                // Add user data
                $user = $this->userRepo->findById($userId);
                $completeProfile['user'] = $user;
            }

            return $completeProfile;

        } catch (Exception $e) {
            $this->logError('Exception in getProfile', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if employer can post jobs
     * 
     * @param int $userId
     * @return bool
     */
    public function canPostJobs(int $userId): bool
    {
        $profile = $this->employerRepo->findByUser($userId);
        
        if (!$profile) {
            return false;
        }

        // Must have at least 60% profile completion to post jobs
        return $this->employerRepo->isProfileComplete($profile['id']);
    }

    /**
     * Check if employer is verified
     * 
     * @param int $userId
     * @return bool
     */
    public function isVerified(int $userId): bool
    {
        $profile = $this->employerRepo->findByUser($userId);
        
        if (!$profile) {
            return false;
        }

        return (bool)($profile['is_verified'] ?? false);
    }

    // ==================== PROFILE UPDATES ====================

    /**
     * Update company logo
     * 
     * @param int $userId
     * @param string $logoPath
     * @return bool
     */
    public function updateLogo(int $userId, string $logoPath): bool
    {
        $this->logInfo('Updating company logo', ['user_id' => $userId]);

        try {
            $result = $this->userRepo->updateAvatar($userId, $logoPath);

            if ($result) {
                $this->logInfo('Logo updated successfully', [
                    'user_id' => $userId
                ]);
            }

            return $result;

        } catch (Exception $e) {
            $this->logError('Exception in updateLogo', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update contact information
     * 
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function updateContactInfo(int $userId, array $data): bool
    {
        $this->logInfo('Updating contact info', ['user_id' => $userId]);
        $this->clearErrors();

        try {
            $profile = $this->employerRepo->findByUser($userId);
            
            if (!$profile) {
                $this->addError('profile', 'Profile not found');
                return false;
            }

            // Validate email if provided
            if (!empty($data['contact_email']) && !$this->validateEmail($data['contact_email'])) {
                return false;
            }

            // Validate website if provided
            if (!empty($data['website']) && !$this->validateUrl($data['website'])) {
                $this->addError('website', 'Invalid website URL');
                return false;
            }

            $updateData = [
                'contact_email' => $data['contact_email'] ?? $profile['contact_email'],
                'contact_phone' => $data['contact_phone'] ?? $profile['contact_phone'],
                'contact_name' => $data['contact_name'] ?? $profile['contact_name'],
                'website' => $data['website'] ?? $profile['website']
            ];

            $result = $this->employerRepo->update($profile['id'], $updateData);

            if ($result) {
                $this->logInfo('Contact info updated successfully', [
                    'user_id' => $userId,
                    'profile_id' => $profile['id']
                ]);
            }

            return $result;

        } catch (Exception $e) {
            $this->logError('Exception in updateContactInfo', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update location
     * 
     * @param int $userId
     * @param int $locationId
     * @return bool
     */
    public function updateLocation(int $userId, int $locationId): bool
    {
        $this->logInfo('Updating employer location', ['user_id' => $userId, 'location_id' => $locationId]);

        try {
            // Check if location exists and belongs to user
            $location = $this->locationRepo->findById($locationId);
            if (!$location) {
                $this->addError('location_id', 'Location not found');
                return false;
            }

            if ($location['user_id'] != $userId) {
                $this->addError('location_id', 'Location does not belong to you');
                return false;
            }

            $profile = $this->employerRepo->findByUser($userId);
            if (!$profile) {
                $this->addError('profile', 'Profile not found');
                return false;
            }

            return $this->employerRepo->update($profile['id'], ['user_location_id' => $locationId]);

        } catch (Exception $e) {
            $this->logError('Exception in updateLocation', [
                'user_id' => $userId,
                'location_id' => $locationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    // ==================== PREMIUM MANAGEMENT ====================

    /**
     * Upgrade employer to premium
     * 
     * @param int $userId
     * @param string $plan
     * @param int $months
     * @return bool
     */
    public function upgradeToPremium(int $userId, string $plan, int $months = 1): bool
    {
        $this->logInfo('Upgrading employer to premium', [
            'user_id' => $userId,
            'plan' => $plan,
            'months' => $months
        ]);

        try {
            $profile = $this->employerRepo->findByUser($userId);
            
            if (!$profile) {
                $this->addError('profile', 'Profile not found');
                return false;
            }

            // Calculate expiration date
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$months} months"));

            // Process payment logic would go here
            // ...

            $result = $this->employerRepo->updatePremiumStatus($profile['id'], true, $expiresAt);

            if ($result) {
                $this->logInfo('Employer upgraded to premium', [
                    'user_id' => $userId,
                    'profile_id' => $profile['id'],
                    'expires_at' => $expiresAt
                ]);
            }

            return $result;

        } catch (Exception $e) {
            $this->logError('Exception in upgradeToPremium', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check premium status
     * 
     * @param int $userId
     * @return bool
     */
    public function isPremium(int $userId): bool
    {
        $profile = $this->employerRepo->findByUser($userId);
        
        if (!$profile) {
            return false;
        }

        // Check if premium and not expired
        if (!$profile['is_premium']) {
            return false;
        }

        if ($profile['premium_expires_at'] && strtotime($profile['premium_expires_at']) < time()) {
            // Premium expired, update status
            $this->employerRepo->updatePremiumStatus($profile['id'], false, null);
            return false;
        }

        return true;
    }

    /**
     * Get premium expiry date
     * 
     * @param int $userId
     * @return string|null
     */
    public function getPremiumExpiry(int $userId): ?string
    {
        $profile = $this->employerRepo->findByUser($userId);
        
        if (!$profile || !$profile['is_premium']) {
            return null;
        }

        return $profile['premium_expires_at'];
    }

    // ==================== VERIFICATION ====================

    /**
     * Request verification
     * 
     * @param int $userId
     * @param array $documents
     * @return bool
     */
    public function requestVerification(int $userId, array $documents): bool
    {
        $this->logInfo('Requesting employer verification', ['user_id' => $userId]);

        try {
            $profile = $this->employerRepo->findByUser($userId);
            
            if (!$profile) {
                $this->addError('profile', 'Profile not found');
                return false;
            }

            // Check if profile is complete enough for verification
            $completion = $this->employerRepo->getCompletionStatus($profile['id']);
            if ($completion['percentage'] < 80) {
                $this->addError('profile', 'Profile must be at least 80% complete for verification');
                return false;
            }

            // Store verification documents logic would go here
            // ...

            // Send notification to admin
            $this->logInfo('Verification requested', [
                'user_id' => $userId,
                'profile_id' => $profile['id']
            ]);

            return true;

        } catch (Exception $e) {
            $this->logError('Exception in requestVerification', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Verify employer (admin only)
     * 
     * @param int $userId
     * @return bool
     */
    public function verifyEmployer(int $userId): bool
    {
        $this->logInfo('Verifying employer', ['user_id' => $userId]);

        try {
            $profile = $this->employerRepo->findByUser($userId);
            
            if (!$profile) {
                $this->addError('profile', 'Profile not found');
                return false;
            }

            $result = $this->employerRepo->verifyEmployer($profile['id']);

            if ($result) {
                $this->logInfo('Employer verified successfully', [
                    'user_id' => $userId,
                    'profile_id' => $profile['id']
                ]);

                // Send notification to employer
                // ...
            }

            return $result;

        } catch (Exception $e) {
            $this->logError('Exception in verifyEmployer', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    // ==================== SEARCH ====================

    /**
     * Search employers
     * 
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function search(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $this->logInfo('Searching employers', $filters);

        try {
            $offset = ($page - 1) * $perPage;

            $results = $this->employerRepo->search($filters, $perPage, $offset);
            $total = $this->employerRepo->countSearch($filters);

            // Format results for display
            foreach ($results as &$result) {
                $result['completion_percentage'] = $this->employerRepo->calculateCompletion($result['id']);
                $result['is_premium_active'] = $this->isPremiumActive($result);
                $result['display_name'] = $result['company_name'] ?? 'Unnamed Company';
            }

            return [
                'data' => $results,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ];

        } catch (Exception $e) {
            $this->logError('Exception in search', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 0
            ];
        }
    }

    /**
     * Get featured employers for homepage
     * 
     * @param int $limit
     * @return array
     */
    public function getFeaturedEmployers(int $limit = 6): array
    {
        try {
            return $this->employerRepo->getFeatured($limit);
        } catch (Exception $e) {
            $this->logError('Exception in getFeaturedEmployers', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ==================== DASHBOARD ====================

    /**
     * Get employer dashboard data
     * 
     * @param int $userId
     * @return array|null
     */
    public function getDashboard(int $userId): ?array
    {
        $this->logInfo('Fetching employer dashboard', ['user_id' => $userId]);

        try {
            $profile = $this->employerRepo->findByUser($userId);
            
            if (!$profile) {
                return null;
            }

            $stats = $this->employerRepo->getEmployerStats($profile['id']);
            $completion = $this->employerRepo->getCompletionStatus($profile['id']);
            $isPremium = $this->isPremium($userId);

            return [
                'profile' => $profile,
                'stats' => $stats,
                'completion' => $completion,
                'is_premium' => $isPremium,
                'premium_expiry' => $isPremium ? $this->getPremiumExpiry($userId) : null,
                'recent_activity' => $this->getRecentActivity($profile['id'])
            ];

        } catch (Exception $e) {
            $this->logError('Exception in getDashboard', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get recent employer activity
     * 
     * @param int $employerId
     * @param int $limit
     * @return array
     */
    private function getRecentActivity(int $employerId, int $limit = 10): array
    {
        // This would fetch recent jobs posted, applications received, etc.
        // For now, return empty array
        return [];
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if premium is active (not expired)
     * 
     * @param array $profile
     * @return bool
     */
    private function isPremiumActive(array $profile): bool
    {
        if (!$profile['is_premium']) {
            return false;
        }

        if ($profile['premium_expires_at'] && strtotime($profile['premium_expires_at']) < time()) {
            return false;
        }

        return true;
    }

    /**
     * Validate URL
     * 
     * @param string $url
     * @return bool
     */
    // ==================== STATISTICS ====================

    /**
     * Get employer statistics for admin dashboard
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        try {
            return $this->employerRepo->getStatistics();
        } catch (Exception $e) {
            $this->logError('Exception in getStatistics', ['error' => $e->getMessage()]);
            return [
                'total_employers' => 0,
                'verified' => 0,
                'premium' => 0,
                'active' => 0,
                'avg_completion' => 0,
                'by_type' => [],
                'top_industries' => []
            ];
        }
    }

    /**
     * Get employers with expiring premium
     * 
     * @param int $days
     * @return array
     */
    public function getExpiringPremium(int $days = 7): array
    {
        try {
            return $this->employerRepo->getExpiringSoon($days);
        } catch (Exception $e) {
            $this->logError('Exception in getExpiringPremium', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get top employers by jobs posted
     * 
     * @param int $limit
     * @return array
     */
    public function getTopEmployers(int $limit = 10): array
    {
        try {
            return $this->employerRepo->getTopEmployers($limit);
        } catch (Exception $e) {
            $this->logError('Exception in getTopEmployers', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Update employer profile
     * 
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function updateProfile(int $userId, array $data): bool
    {
        try {
            $employerId = $this->getEmployerId($userId);
            if (!$employerId) {
                $this->addError('employer', 'Employer profile not found');
                return false;
            }

            $updateData = [];

            // Update company name if provided
            if (isset($data['company_name'])) {
                $updateData['company_name'] = trim($data['company_name']);
            }

            // Update employer type if provided
            if (isset($data['employer_type'])) {
                $updateData['employer_type'] = $data['employer_type'];
            }

            // Update industry if provided
            if (isset($data['industry_id'])) {
                $updateData['industry_id'] = $data['industry_id'];
            }

            // Update website if provided
            if (isset($data['website'])) {
                $updateData['website'] = trim($data['website']);
            }

            // Update company description if provided
            if (isset($data['company_description'])) {
                $updateData['company_description'] = trim($data['company_description']);
            }

            if (empty($updateData)) {
                return true;
            }

            return $this->employerRepo->update((int)$employerId, $updateData);

        } catch (Exception $e) {
            $this->logError('Exception in updateProfile', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $this->addError('profile', 'An error occurred while updating profile');
            return false;
        }
    }
}
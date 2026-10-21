<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;
use PDOException;
use Exception;

/**
 * EmployerRepository - Manages employer profiles and related data
 * 
 * Handles:
 * - Employer profile CRUD
 * - Company information
 * - Profile completion tracking
 * - Employer search and filtering
 * - Premium status management
 */
class EmployerRepository extends BaseRepository
{
    protected string $table = 'employer_profiles';
    protected string $primaryKey = 'id';

    // Profile completion weights (total 100%)
    private const WEIGHTS = [
        'company_info' => 40,        // company name, employer type, industry
        'contact_info' => 30,         // contact email, phone, person
        'description' => 20,           // company description
        'location' => 10              // location
    ];

    // Minimum required for profile to be considered "complete"
    private const MIN_COMPLETE_PERCENTAGE = 60;

    /**
     * Create or update employer profile
     *
     * @param int $userId
     * @param array $data
     * @return int|null Profile ID
     */
    public function createOrUpdateProfile(int $userId, array $data): ?int
    {
        try {
            // Check if profile exists
            $existing = $this->findByUser($userId);
            
            $profileData = [
                'user_id' => $userId,
                'owner_user_id' => $data['owner_user_id'] ?? $userId,
                'employer_type' => $data['employer_type'] ?? null,
                'company_name' => $data['company_name'] ?? null,
                'company_description' => $data['company_description'] ?? null,
                'industry_id' => $data['industry_id'] ?? null,
                'user_location_id' => $data['user_location_id'] ?? null,
                'website' => $data['website'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'contact_name' => $data['contact_name'] ?? null,
                'avatar' => $data['avatar'] ?? ($existing['avatar'] ?? null),
                'is_active' => $data['is_active'] ?? 1,
                'is_verified' => $data['is_verified'] ?? 0,
                'profile_completed' => $data['profile_completed'] ?? 0,
                'is_premium' => $data['is_premium'] ?? 0,
                'premium_expires_at' => $data['premium_expires_at'] ?? null
            ];

            if ($existing) {
                // Update existing profile
                $this->update($existing['id'], $profileData);
                $profileId = (int) $existing['id'];
            } else {
                // Create new profile
                $profileId = $this->create($profileData);
                
                if (!$profileId) {
                    return null;
                }
            }

            if (!empty($data['avatar'])) {
                $this->execute(
                    "UPDATE {$this->table} SET avatar = :avatar WHERE id = :id",
                    [':avatar' => $data['avatar'], ':id' => $profileId]
                );

                $savedAvatar = $this->fetchOne(
                    "SELECT avatar FROM {$this->table} WHERE id = :id LIMIT 1",
                    [':id' => $profileId]
                );

                if (($savedAvatar['avatar'] ?? null) !== $data['avatar']) {
                    error_log("EmployerRepository - Avatar path was not persisted for profile {$profileId}");
                    return null;
                }
            }

            // Calculate and update completion percentage after confirming the profile data was saved.
            $completion = $this->calculateCompletion($profileId);
            $this->update($profileId, ['profile_completion' => $completion]);

            return $profileId;
        } catch (PDOException $e) {
            error_log("Failed to create/update employer profile: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find employer profile by user ID
     *
     * @param int $userId
     * @return array|null
     */
    public function findByUser(int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id LIMIT 1";
        return $this->fetchOne($sql, [':user_id' => $userId]);
    }

    /**
     * Get complete employer profile with related data
     *
     * @param int $employerId
     * @return array|null
     */
    public function getCompleteProfile(int $employerId): ?array
    {
        $profile = $this->findById($employerId);
        
        if (!$profile) {
            return null;
        }

        return [
            'profile' => $profile,
            'industry' => $this->getIndustry($profile['industry_id']),
            'location' => $this->getLocation($profile['user_location_id']),
            'stats' => $this->getEmployerStats($employerId)
        ];
    }

    /**
     * Get industry details
     *
     * @param int|null $industryId
     * @return array|null
     */
    public function getIndustry(?int $industryId): ?array
    {
        if (!$industryId) {
            return null;
        }

        $sql = "SELECT * FROM industries WHERE id = :id";
        return $this->fetchOne($sql, [':id' => $industryId]);
    }

    /**
     * Get location details
     *
     * @param int|null $locationId
     * @return array|null
     */
    public function getLocation(?int $locationId): ?array
    {
        if (!$locationId) {
            return null;
        }

        $sql = "SELECT * FROM user_locations WHERE id = :id";
        return $this->fetchOne($sql, [':id' => $locationId]);
    }

    /**
     * Get employer statistics (jobs posted, active jobs, etc.)
     *
     * @param int $employerId
     * @return array
     */
    public function getEmployerStats(int $employerId): array
    {
        $stats = [];

        // Total jobs posted
        $sql = "SELECT COUNT(*) as total FROM jobs WHERE employer_id = :employer_id";
        $result = $this->fetchOne($sql, [':employer_id' => $employerId]);
        $stats['total_jobs'] = (int)($result['total'] ?? 0);

        // Active jobs (assuming 'published' is your active status from your jobs table)
        $sql = "SELECT COUNT(*) as total FROM jobs 
                WHERE employer_id = :employer_id AND status = 'published'";
        $result = $this->fetchOne($sql, [':employer_id' => $employerId]);
        $stats['active_jobs'] = (int)($result['total'] ?? 0);

        // Total applications received
        $sql = "SELECT COUNT(DISTINCT a.id) as total 
                FROM applications a
                INNER JOIN jobs j ON a.job_id = j.id
                WHERE j.employer_id = :employer_id";
        $result = $this->fetchOne($sql, [':employer_id' => $employerId]);
        $stats['total_applications'] = (int)($result['total'] ?? 0);

        // Applications by status
        $statuses = ['pending', 'reviewed', 'shortlisted', 'rejected', 'hired'];
        $stats['applications_by_status'] = [];
        
        foreach ($statuses as $status) {
            $sql = "SELECT COUNT(DISTINCT a.id) as total 
                    FROM applications a
                    INNER JOIN jobs j ON a.job_id = j.id
                    WHERE j.employer_id = :employer_id AND a.status = :status";
            $result = $this->fetchOne($sql, [
                ':employer_id' => $employerId,
                ':status' => $status
            ]);
            $stats['applications_by_status'][$status] = (int)($result['total'] ?? 0);
        }

        // Recent applications (last 30 days)
        $sql = "SELECT COUNT(DISTINCT a.id) as total 
                FROM applications a
                INNER JOIN jobs j ON a.job_id = j.id
                WHERE j.employer_id = :employer_id 
                AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = $this->fetchOne($sql, [':employer_id' => $employerId]);
        $stats['recent_applications_30d'] = (int)($result['total'] ?? 0);

        // Unviewed applications
        $sql = "SELECT COUNT(DISTINCT a.id) as total 
                FROM applications a
                INNER JOIN jobs j ON a.job_id = j.id
                WHERE j.employer_id = :employer_id 
                AND a.viewed_at IS NULL";
        $result = $this->fetchOne($sql, [':employer_id' => $employerId]);
        $stats['unviewed_applications'] = (int)($result['total'] ?? 0);

        // Jobs with most applications
        $sql = "SELECT DISTINCT j.id, j.title, COUNT(DISTINCT a.id) as application_count
                FROM jobs j
                LEFT JOIN applications a ON j.id = a.job_id
                WHERE j.employer_id = :employer_id
                GROUP BY j.id, j.title
                ORDER BY application_count DESC
                LIMIT 5";
        $stats['top_jobs'] = $this->fetchAll($sql, [':employer_id' => $employerId]);

        return $stats;
    }

    /**
     * Search employers by company name, industry, location
     *
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function search(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT DISTINCT ep.*, i.name as industry_name, ul.city, ul.country,
                       (SELECT COUNT(*) FROM jobs j WHERE j.employer_id = ep.id) as jobs_count
                FROM {$this->table} ep
                LEFT JOIN industries i ON ep.industry_id = i.id
                LEFT JOIN user_locations ul ON ep.user_location_id = ul.id
                WHERE 1=1";
        
        $params = [];

        // Filter by active employers
        if (!isset($filters['include_inactive']) || !$filters['include_inactive']) {
            $sql .= " AND ep.is_active = 1";
        }

        // Filter by company name
        if (!empty($filters['company_name'])) {
            $sql .= " AND ep.company_name LIKE :company_name";
            $params[':company_name'] = '%' . $filters['company_name'] . '%';
        }

        // Filter by industry
        if (!empty($filters['industry_id'])) {
            $sql .= " AND ep.industry_id = :industry_id";
            $params[':industry_id'] = $filters['industry_id'];
        }

        // Filter by location
        if (!empty($filters['location'])) {
            $sql .= " AND (ul.city LIKE :location OR ul.country LIKE :location)";
            $params[':location'] = '%' . $filters['location'] . '%';
        }

        // Filter by premium status
        if (!empty($filters['is_premium'])) {
            $sql .= " AND ep.is_premium = 1";
        }

        // Filter by verified status
        if (!empty($filters['is_verified'])) {
            $sql .= " AND ep.is_verified = 1";
        }

        $sql .= " GROUP BY ep.id ORDER BY ep.is_premium DESC, ep.company_name";
        $sql .= $this->formatLimitOffset($limit, $offset);

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count total employers matching filters
     *
     * @param array $filters
     * @return int
     */
    public function countSearch(array $filters = []): int
    {
        $sql = "SELECT COUNT(DISTINCT ep.id) as total 
                FROM {$this->table} ep
                LEFT JOIN industries i ON ep.industry_id = i.id
                LEFT JOIN user_locations ul ON ep.user_location_id = ul.id
                WHERE 1=1";
        
        $params = [];

        if (!isset($filters['include_inactive']) || !$filters['include_inactive']) {
            $sql .= " AND ep.is_active = 1";
        }

        if (!empty($filters['company_name'])) {
            $sql .= " AND ep.company_name LIKE :company_name";
            $params[':company_name'] = '%' . $filters['company_name'] . '%';
        }

        if (!empty($filters['industry_id'])) {
            $sql .= " AND ep.industry_id = :industry_id";
            $params[':industry_id'] = $filters['industry_id'];
        }

        if (!empty($filters['location'])) {
            $sql .= " AND (ul.city LIKE :location OR ul.country LIKE :location)";
            $params[':location'] = '%' . $filters['location'] . '%';
        }

        $result = $this->fetchOne($sql, $params);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Get featured/premium employers
     *
     * @param int $limit
     * @return array
     */
    public function getFeatured(int $limit = 10): array
    {
        $sql = "SELECT DISTINCT ep.*, i.name as industry_name, ul.city, ul.country
                FROM {$this->table} ep
                LEFT JOIN industries i ON ep.industry_id = i.id
                LEFT JOIN user_locations ul ON ep.user_location_id = ul.id
                WHERE ep.is_premium = 1 
                AND ep.is_active = 1
                AND (ep.premium_expires_at IS NULL OR ep.premium_expires_at > NOW())
                GROUP BY ep.id
                ORDER BY ep.company_name";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql);
    }

    /**
     * Get recently joined employers
     *
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 10): array
    {
        $sql = "SELECT DISTINCT ep.*, i.name as industry_name, ul.city, ul.country
                FROM {$this->table} ep
                LEFT JOIN industries i ON ep.industry_id = i.id
                LEFT JOIN user_locations ul ON ep.user_location_id = ul.id
                WHERE ep.is_active = 1
                GROUP BY ep.id
                ORDER BY ep.created_at DESC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql);
    }

    /**
     * Update premium status
     *
     * @param int $employerId
     * @param bool $isPremium
     * @param string|null $expiresAt
     * @return bool
     */
    public function updatePremiumStatus(int $employerId, bool $isPremium, ?string $expiresAt = null): bool
    {
        $data = [
            'is_premium' => $isPremium ? 1 : 0,
            'premium_expires_at' => $expiresAt
        ];

        return $this->update($employerId, $data);
    }

    /**
     * Verify employer
     *
     * @param int $employerId
     * @return bool
     */
    public function verifyEmployer(int $employerId): bool
    {
        return $this->update($employerId, ['is_verified' => 1]);
    }

    /**
     * Toggle active status
     *
     * @param int $employerId
     * @param bool $isActive
     * @return bool
     */
    public function setActive(int $employerId, bool $isActive): bool
    {
        return $this->update($employerId, ['is_active' => $isActive ? 1 : 0]);
    }



    // ==================== PROFILE COMPLETION ====================

    /**
     * Calculate profile completion percentage
     *
     * @param int $employerId
     * @return int
     */
    public function calculateCompletion(int $employerId): int
    {
        $profile = $this->findById($employerId);
        if (!$profile) {
            return 0;
        }

        $total = 0;

        // Company Info (30%)
        $companyInfoScore = 0;
        if (!empty($profile['company_name'])) $companyInfoScore += 40;
        if (!empty($profile['employer_type'])) $companyInfoScore += 30;
        if (!empty($profile['industry_id'])) $companyInfoScore += 30;
        $total += (self::WEIGHTS['company_info'] * $companyInfoScore / 100);

        // Contact Info (25%)
        $contactScore = 0;
        if (!empty($profile['contact_email'])) $contactScore += 40;
        if (!empty($profile['contact_phone'])) $contactScore += 30;
        if (!empty($profile['contact_name'])) $contactScore += 30;
        $total += (self::WEIGHTS['contact_info'] * $contactScore / 100);

        // Description (20%)
        if (!empty($profile['company_description'])) {
            $descLength = strlen($profile['company_description']);
            if ($descLength >= 200) {
                $total += self::WEIGHTS['description'];
            } else if ($descLength >= 100) {
                $total += self::WEIGHTS['description'] * 0.7;
            } else if ($descLength >= 50) {
                $total += self::WEIGHTS['description'] * 0.4;
            }
        }

        // Location (15% -> now 10%)
        if (!empty($profile['user_location_id'])) {
            $total += self::WEIGHTS['location'];
        }

        return min(100, (int)round($total));
    }

    /**
     * Check if profile is complete
     *
     * @param int $employerId
     * @return bool
     */
    public function isProfileComplete(int $employerId): bool
    {
        $profile = $this->findById($employerId);
        if (!$profile) {
            return false;
        }

        return ($profile['profile_completion'] ?? 0) >= self::MIN_COMPLETE_PERCENTAGE;
    }

    /**
     * Get completion status with details
     *
     * @param int $employerId
     * @return array
     */
    public function getCompletionStatus(int $employerId): array
    {
        $profile = $this->findById($employerId);
        if (!$profile) {
            return [
                'percentage' => 0,
                'is_complete' => false,
                'missing' => ['profile_not_created']
            ];
        }

        $missing = [];

        if (empty($profile['company_name'])) {
            $missing[] = 'company_name';
        }
        if (empty($profile['employer_type'])) {
            $missing[] = 'employer_type';
        }
        if (empty($profile['industry_id'])) {
            $missing[] = 'industry';
        }
        if (empty($profile['contact_email'])) {
            $missing[] = 'contact_email';
        }
        if (empty($profile['company_description'])) {
            $missing[] = 'description';
        }
        if (empty($profile['user_location_id'])) {
            $missing[] = 'location';
        }

        return [
            'percentage' => $profile['profile_completion'] ?? 0,
            'is_complete' => ($profile['profile_completion'] ?? 0) >= self::MIN_COMPLETE_PERCENTAGE,
            'missing' => $missing
        ];
    }

    // ==================== STATISTICS ====================

    /**
     * Get employer statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $stats = [];

        // Total employers
        $result = $this->fetchOne("SELECT COUNT(*) as total FROM {$this->table}");
        $stats['total_employers'] = $result['total'] ?? 0;

        // Verified employers
        $result = $this->fetchOne("SELECT COUNT(*) as total FROM {$this->table} WHERE is_verified = 1");
        $stats['verified'] = $result['total'] ?? 0;

        // Premium employers
        $result = $this->fetchOne("SELECT COUNT(*) as total FROM {$this->table} WHERE is_premium = 1");
        $stats['premium'] = $result['total'] ?? 0;

        // Active employers
        $result = $this->fetchOne("SELECT COUNT(*) as total FROM {$this->table} WHERE is_active = 1");
        $stats['active'] = $result['total'] ?? 0;

        // Average profile completion
        $result = $this->fetchOne("SELECT AVG(profile_completion) as avg FROM {$this->table}");
        $stats['avg_completion'] = round($result['avg'] ?? 0);

        // By employer type
        $sql = "SELECT employer_type, COUNT(*) as count 
                FROM {$this->table} 
                WHERE employer_type IS NOT NULL
                GROUP BY employer_type";
        $stats['by_type'] = $this->fetchAll($sql);

        // Top industries
        $sql = "SELECT DISTINCT i.name, COUNT(ep.id) as count
                FROM {$this->table} ep
                JOIN industries i ON ep.industry_id = i.id
                GROUP BY ep.industry_id, i.name
                ORDER BY count DESC
                LIMIT 10";
        $stats['top_industries'] = $this->fetchAll($sql);

        return $stats;
    }

    /**
     * Get employers expiring soon (premium)
     *
     * @param int $days
     * @return array
     */
    public function getExpiringSoon(int $days = 7): array
    {
        $sql = "SELECT DISTINCT ep.*, u.email
                FROM {$this->table} ep
                JOIN users u ON ep.user_id = u.id
                WHERE ep.is_premium = 1
                AND ep.premium_expires_at IS NOT NULL
                AND ep.premium_expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY)
                GROUP BY ep.id
                ORDER BY ep.premium_expires_at";
        
        return $this->fetchAll($sql, [':days' => $days]);
    }

    /**
     * Get employers with most jobs
     *
     * @param int $limit
     * @return array
     */
    public function getTopEmployers(int $limit = 10): array
    {
        $sql = "SELECT DISTINCT ep.*, COUNT(DISTINCT j.id) as jobs_count
                FROM {$this->table} ep
                LEFT JOIN jobs j ON ep.id = j.employer_id AND j.status = 'published'
                WHERE ep.is_active = 1
                GROUP BY ep.id
                ORDER BY jobs_count DESC, ep.company_name";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql);
    }
}
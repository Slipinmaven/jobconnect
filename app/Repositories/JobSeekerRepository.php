<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;
use PDOException;

/**
 * JobSeekerRepository - Manages jobseeker profiles and related data
 * 
 * Handles:
 * - Jobseeker profile CRUD
 * - Skills management
 * - Education history
 * - Work experience
 * - Profile completion tracking
 */
class JobSeekerRepository extends BaseRepository
{
    protected string $table = 'jobseeker_profiles';
    protected string $primaryKey = 'id';

    // Profile completion weights (total 100%)
    private const WEIGHTS = [
        'personal_info' => 15,      // name, professional title
        'summary' => 10,             // about me
        'skills' => 25,              // skills with proficiency
        'experience' => 20,           // work experience
        'education' => 15,            // education
        'preferences' => 15           // location, remote, etc.
    ];

    // Minimum required for profile to be considered "complete"
    private const MIN_COMPLETE_PERCENTAGE = 70;

    /**
     * Create or update jobseeker profile
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
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'professional_title' => $data['professional_title'] ?? null,
                'summary' => $data['summary'] ?? null,
                'user_location_id' => $data['user_location_id'] ?? null,
                'years_experience' => $data['years_experience'] ?? null,
                'salary_expectation' => $data['salary_expectation'] ?? null,
                'salary_currency' => $data['salary_currency'] ?? 'KES',
                'open_to_work' => $data['open_to_work'] ?? 1,
                'remote_preference' => $data['remote_preference'] ?? 0,
                'relocation_willing' => $data['relocation_willing'] ?? 0,
                'resume_url' => $data['resume_url'] ?? null
            ];

            if ($existing) {
                // Update existing profile
                $this->update($existing['id'], $profileData);
                
                // Calculate and update completion percentage
                $completion = $this->calculateCompletion($existing['id']);
                $this->update($existing['id'], ['profile_completion' => $completion]);
                
                return (int)$existing['id'];
            } else {
                // Create new profile
                $profileId = $this->create($profileData);
                
                if (!$profileId) {
                    error_log(
                        "JobSeekerRepository: profile insert returned no generated ID for user_id={$userId}; "
                        . "verify jobseeker_profiles.id is an AUTO_INCREMENT primary key"
                    );
                    return null;
                }

                // Calculate and update completion percentage
                $completion = $this->calculateCompletion($profileId);
                $this->update($profileId, ['profile_completion' => $completion]);
                
                return $profileId;
            }
        } catch (PDOException $e) {
            error_log("Failed to create/update jobseeker profile: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find jobseeker profile by user ID
     *
     * @param int $userId
     * @return array|null
     */
    public function findByUser(int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id LIMIT 1";
        return $this->fetchOne($sql, [':user_id' => $userId]);
    }

    public function updateAvatarForUser(int $userId, string $avatarPath): bool
    {
        $profile = $this->findByUser($userId);
        if ($profile === null) {
            return true;
        }

        $this->execute(
            "UPDATE {$this->table} SET avatar = :avatar WHERE user_id = :user_id",
            [':avatar' => $avatarPath, ':user_id' => $userId]
        );

        $updatedProfile = $this->findByUser($userId);
        return ($updatedProfile['avatar'] ?? null) === $avatarPath;
    }

    /**
     * Get complete jobseeker profile with all related data
     *
     * @param int $jobseekerId
     * @return array|null
     */
    public function getCompleteProfile(int $jobseekerId): ?array
    {
        $profile = $this->findById($jobseekerId);
        
        if (!$profile) {
            return null;
        }

        return [
            'profile' => $profile,
            'skills' => $this->getSkills($jobseekerId),
            'experience' => $this->getExperience($jobseekerId),
            'education' => $this->getEducation($jobseekerId),
            'location' => $this->getLocation($profile['user_location_id'])
        ];
    }

    // ==================== SKILLS MANAGEMENT ====================

    /**
     * Add a skill to jobseeker profile
     *
     * @param int $jobseekerId
     * @param int $skillId
     * @param string $proficiency
     * @param int|null $yearsExperience
     * @return bool
     */
    public function addSkill(int $jobseekerId, int $skillId, string $proficiency = 'intermediate', ?int $yearsExperience = null): bool
    {
        try {
            $sql = "INSERT INTO jobseeker_skills (jobseeker_id, skill_id, proficiency, years_experience) 
                    VALUES (:jobseeker_id, :skill_id, :proficiency, :years_experience)
                    ON DUPLICATE KEY UPDATE 
                    proficiency = VALUES(proficiency),
                    years_experience = VALUES(years_experience)";
            
            return $this->execute($sql, [
                ':jobseeker_id' => $jobseekerId,
                ':skill_id' => $skillId,
                ':proficiency' => $proficiency,
                ':years_experience' => $yearsExperience
            ]);
        } catch (PDOException $e) {
            error_log("Failed to add skill: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a skill from jobseeker profile
     *
     * @param int $jobseekerId
     * @param int $skillId
     * @return bool
     */
    public function removeSkill(int $jobseekerId, int $skillId): bool
    {
        return $this->execute(
            "DELETE FROM jobseeker_skills WHERE jobseeker_id = :jobseeker_id AND skill_id = :skill_id",
            [':jobseeker_id' => $jobseekerId, ':skill_id' => $skillId]
        );
    }

    /**
     * Get all skills for a jobseeker
     * FIXED: Added DISTINCT and GROUP BY to prevent duplicates
     *
     * @param int $jobseekerId
     * @return array
     */
    public function getSkills(int $jobseekerId): array
    {
        $sql = "SELECT DISTINCT js.*, s.name as skill_name, s.category_id, sc.name as category_name, sc.icon
                FROM jobseeker_skills js
                INNER JOIN skills s ON js.skill_id = s.id
                LEFT JOIN skill_categories sc ON s.category_id = sc.id
                WHERE js.jobseeker_id = :jobseeker_id
                GROUP BY js.id
                ORDER BY sc.sort_order, s.name";
        
        return $this->fetchAll($sql, [':jobseeker_id' => $jobseekerId]);
    }

    /**
     * Bulk update skills (replace all)
     *
     * @param int $jobseekerId
     * @param array $skills Array of [skill_id, proficiency, years_experience]
     * @return bool
     */
    public function updateSkills(int $jobseekerId, array $skills): bool
    {
        try {
            $this->beginTransaction();

            // Clear existing skills
            $this->execute("DELETE FROM jobseeker_skills WHERE jobseeker_id = ?", [$jobseekerId]);

            // Add new skills
            foreach ($skills as $skill) {
                $this->addSkill(
                    $jobseekerId,
                    $skill['skill_id'],
                    $skill['proficiency'] ?? 'intermediate',
                    $skill['years_experience'] ?? null
                );
            }

            $this->commit();
            
            // Update profile completion
            $completion = $this->calculateCompletion($jobseekerId);
            $this->update($jobseekerId, ['profile_completion' => $completion]);
            
            return true;
        } catch (PDOException $e) {
            $this->rollback();
            error_log("Failed to update skills: " . $e->getMessage());
            return false;
        }
    }

    // ==================== EXPERIENCE MANAGEMENT ====================

    /**
     * Add work experience
     *
     * @param int $jobseekerId
     * @param array $data
     * @return int|null Experience ID
     */
    public function addExperience(int $jobseekerId, array $data): ?int
    {
        try {
            $sql = "INSERT INTO jobseeker_experiences 
                    (jobseeker_id, company_name, role_title, start_year, end_year, currently_working, description) 
                    VALUES 
                    (:jobseeker_id, :company_name, :role_title, :start_year, :end_year, :currently_working, :description)";
            
            $success = $this->execute($sql, [
                ':jobseeker_id' => $jobseekerId,
                ':company_name' => $data['company_name'],
                ':role_title' => $data['role_title'],
                ':start_year' => $data['start_year'],
                ':end_year' => ($data['currently_working'] ?? false) ? null : ($data['end_year'] ?? null),
                ':currently_working' => $data['currently_working'] ?? 0,
                ':description' => $data['description'] ?? null
            ]);

            if ($success) {
                $experienceId = $this->lastInsertId();
                
                // Update profile completion
                $completion = $this->calculateCompletion($jobseekerId);
                $this->update($jobseekerId, ['profile_completion' => $completion]);
                
                return $experienceId;
            }
            return null;
        } catch (PDOException $e) {
            error_log("Failed to add experience: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update work experience
     *
     * @param int $experienceId
     * @param array $data
     * @return bool
     */
    public function updateExperience(int $experienceId, array $data): bool
    {
        $sql = "UPDATE jobseeker_experiences SET 
                company_name = :company_name,
                role_title = :role_title,
                start_year = :start_year,
                end_year = :end_year,
                currently_working = :currently_working,
                description = :description
                WHERE id = :id";
        
        return $this->execute($sql, [
            ':id' => $experienceId,
            ':company_name' => $data['company_name'],
            ':role_title' => $data['role_title'],
            ':start_year' => $data['start_year'],
            ':end_year' => ($data['currently_working'] ?? false) ? null : ($data['end_year'] ?? null),
            ':currently_working' => $data['currently_working'] ?? 0,
            ':description' => $data['description'] ?? null
        ]);
    }

    /**
     * Remove work experience
     *
     * @param int $jobseekerId
     * @param int $experienceId
     * @return bool
     */
    public function removeExperience(int $jobseekerId, int $experienceId): bool
    {
        $result = $this->execute(
            "DELETE FROM jobseeker_experiences WHERE id = :id AND jobseeker_id = :jobseeker_id",
            [':id' => $experienceId, ':jobseeker_id' => $jobseekerId]
        );
        
        if ($result) {
            // Update profile completion
            $completion = $this->calculateCompletion($jobseekerId);
            $this->update($jobseekerId, ['profile_completion' => $completion]);
        }
        
        return $result;
    }

    /**
     * Get all work experiences for a jobseeker
     *
     * @param int $jobseekerId
     * @return array
     */
    public function getExperience(int $jobseekerId): array
    {
        $sql = "SELECT DISTINCT * FROM jobseeker_experiences 
            WHERE jobseeker_id = :jobseeker_id 
            GROUP BY id
            ORDER BY 
                CASE WHEN currently_working = 1 THEN 0 ELSE 1 END,
                COALESCE(end_year, 9999) DESC,
                start_year DESC";

        return $this->fetchAll($sql, [':jobseeker_id' => $jobseekerId]);
    }

    // ==================== EDUCATION MANAGEMENT ====================

    /**
     * Add education
     *
     * @param int $jobseekerId
     * @param array $data
     * @return int|null Education ID
     */
    public function addEducation(int $jobseekerId, array $data): ?int
    {
        try {
            $sql = "INSERT INTO jobseeker_educations 
                    (jobseeker_id, school_id, education_level, field_of_study, grade, start_year, end_year, currently_studying, expected_graduation_year, description) 
                    VALUES 
                    (:jobseeker_id, :school_id, :education_level, :field_of_study, :grade, :start_year, :end_year, :currently_studying, :expected_graduation_year, :description)";
            
            $success = $this->execute($sql, [
                ':jobseeker_id' => $jobseekerId,
                ':school_id' => $data['school_id'],
                ':education_level' => $data['education_level'],
                ':field_of_study' => $data['field_of_study'] ?? null,
                ':grade' => $data['grade'] ?? null,
                ':start_year' => $data['start_year'],
                ':end_year' => ($data['currently_studying'] ?? false) ? null : ($data['end_year'] ?? null),
                ':currently_studying' => $data['currently_studying'] ?? 0,
                ':expected_graduation_year' => $data['expected_graduation_year'] ?? null,
                ':description' => $data['description'] ?? null
            ]);

            if ($success) {
                $educationId = $this->lastInsertId();
                
                // Update profile completion
                $completion = $this->calculateCompletion($jobseekerId);
                $this->update($jobseekerId, ['profile_completion' => $completion]);
                
                return $educationId;
            }
            return null;
        } catch (PDOException $e) {
            error_log("Failed to add education: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update education
     *
     * @param int $educationId
     * @param array $data
     * @return bool
     */
    public function updateEducation(int $educationId, array $data): bool
    {
        $sql = "UPDATE jobseeker_educations SET 
                school_id = :school_id,
                education_level = :education_level,
                field_of_study = :field_of_study,
                grade = :grade,
                start_year = :start_year,
                end_year = :end_year,
                currently_studying = :currently_studying,
                expected_graduation_year = :expected_graduation_year,
                description = :description
                WHERE id = :id";
        
        return $this->execute($sql, [
            ':id' => $educationId,
            ':school_id' => $data['school_id'],
            ':education_level' => $data['education_level'],
            ':field_of_study' => $data['field_of_study'] ?? null,
            ':grade' => $data['grade'] ?? null,
            ':start_year' => $data['start_year'],
            ':end_year' => ($data['currently_studying'] ?? false) ? null : ($data['end_year'] ?? null),
            ':currently_studying' => $data['currently_studying'] ?? 0,
            ':expected_graduation_year' => $data['expected_graduation_year'] ?? null,
            ':description' => $data['description'] ?? null
        ]);
    }

    /**
     * Remove education
     *
     * @param int $jobseekerId
     * @param int $educationId
     * @return bool
     */
    public function removeEducation(int $jobseekerId, int $educationId): bool
    {
        $result = $this->execute(
            "DELETE FROM jobseeker_educations WHERE id = :id AND jobseeker_id = :jobseeker_id",
            [':id' => $educationId, ':jobseeker_id' => $jobseekerId]
        );
        
        if ($result) {
            // Update profile completion
            $completion = $this->calculateCompletion($jobseekerId);
            $this->update($jobseekerId, ['profile_completion' => $completion]);
        }
        
        return $result;
    }

    /**
     * Get all education for a jobseeker
     * FIXED: Added DISTINCT and GROUP BY to prevent duplicates
     *
     * @param int $jobseekerId
     * @return array
     */
    public function getEducation(int $jobseekerId): array
    {
        $sql = "SELECT DISTINCT je.*, s.name as school_name, s.type as school_type, s.city, s.country
            FROM jobseeker_educations je
            INNER JOIN schools s ON je.school_id = s.id
            WHERE je.jobseeker_id = :jobseeker_id
            GROUP BY je.id
            ORDER BY 
                CASE WHEN je.currently_studying = 1 THEN 0 ELSE 1 END,
                COALESCE(je.end_year, 9999) DESC,
                je.start_year DESC";

        return $this->fetchAll($sql, [':jobseeker_id' => $jobseekerId]);
    }

    // ==================== LOCATION MANAGEMENT ====================

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
     * Update jobseeker location
     *
     * @param int $jobseekerId
     * @param int $locationId
     * @return bool
     */
    public function updateLocation(int $jobseekerId, int $locationId): bool
    {
        $result = $this->execute(
            "UPDATE {$this->table} SET user_location_id = :location_id WHERE id = :jobseeker_id",
            [':jobseeker_id' => $jobseekerId, ':location_id' => $locationId]
        );
        
        if ($result) {
            // Update profile completion
            $completion = $this->calculateCompletion($jobseekerId);
            $this->update($jobseekerId, ['profile_completion' => $completion]);
        }
        
        return $result;
    }

    // ==================== PROFILE COMPLETION ====================

    /**
     * Calculate profile completion percentage
     *
     * @param int $jobseekerId
     * @return int
     */
    public function calculateCompletion(int $jobseekerId): int
    {
        $profile = $this->findById($jobseekerId);
        if (!$profile) {
            return 0;
        }

        $total = 0;

        // Personal Info (15%)
        if (!empty($profile['first_name']) && !empty($profile['last_name'])) {
            $total += self::WEIGHTS['personal_info'] * 0.5; // names = 7.5%
        }
        if (!empty($profile['professional_title'])) {
            $total += self::WEIGHTS['personal_info'] * 0.5; // title = 7.5%
        }

        // Summary (10%)
        if (!empty($profile['summary']) && strlen($profile['summary']) > 50) {
            $total += self::WEIGHTS['summary'];
        }

        // Skills (25%)
        $skills = $this->getSkills($jobseekerId);
        $skillCount = count($skills);
        
        if ($skillCount >= 5) {
            $total += self::WEIGHTS['skills'];
        } else if ($skillCount >= 3) {
            $total += self::WEIGHTS['skills'] * 0.7;
        } else if ($skillCount >= 1) {
            $total += self::WEIGHTS['skills'] * 0.3;
        }

        // Experience (20%)
        $experience = $this->getExperience($jobseekerId);
        $expCount = count($experience);
        
        if ($expCount >= 2) {
            $total += self::WEIGHTS['experience'];
        } else if ($expCount >= 1) {
            $total += self::WEIGHTS['experience'] * 0.6;
        }

        // Education (15%)
        $education = $this->getEducation($jobseekerId);
        $eduCount = count($education);
        
        if ($eduCount >= 1) {
            $total += self::WEIGHTS['education'];
        }

        // Preferences (10%)
        $prefCount = 0;
        if (!empty($profile['user_location_id'])) $prefCount++;
        if (isset($profile['remote_preference'])) $prefCount++;
        if (isset($profile['relocation_willing'])) $prefCount++;
        if (!empty($profile['open_to_work'])) $prefCount++;
        if (!empty($profile['salary_expectation'])) $prefCount++;
        
        $total += (self::WEIGHTS['preferences'] / 5) * $prefCount;

        return min(100, (int)round($total));
    }

    /**
     * Check if profile is complete enough for job applications
     *
     * @param int $jobseekerId
     * @return bool
     */
    public function isProfileComplete(int $jobseekerId): bool
    {
        $profile = $this->findById($jobseekerId);
        if (!$profile) {
            return false;
        }

        return ($profile['profile_completion'] ?? 0) >= self::MIN_COMPLETE_PERCENTAGE;
    }

    /**
     * Get profile completion status with details
     *
     * @param int $jobseekerId
     * @return array
     */
    public function getCompletionStatus(int $jobseekerId): array
    {
        $profile = $this->findById($jobseekerId);
        if (!$profile) {
            return [
                'percentage' => 0,
                'is_complete' => false,
                'missing' => ['profile_not_created']
            ];
        }

        $missing = [];

        if (empty($profile['first_name']) || empty($profile['last_name'])) {
            $missing[] = 'full_name';
        }
        if (empty($profile['professional_title'])) {
            $missing[] = 'professional_title';
        }
        if (empty($profile['summary']) || strlen($profile['summary']) < 50) {
            $missing[] = 'summary';
        }

        $skills = $this->getSkills($jobseekerId);
        if (count($skills) < 3) {
            $missing[] = 'skills';
        }

        $experience = $this->getExperience($jobseekerId);
        if (count($experience) < 1) {
            $missing[] = 'experience';
        }

        $education = $this->getEducation($jobseekerId);
        if (count($education) < 1) {
            $missing[] = 'education';
        }

        if (empty($profile['user_location_id'])) {
            $missing[] = 'location';
        }

        return [
            'percentage' => $profile['profile_completion'],
            'is_complete' => ($profile['profile_completion'] ?? 0) >= self::MIN_COMPLETE_PERCENTAGE,
            'missing' => $missing
        ];
    }

    // ==================== SEARCH & FILTERS ====================

    /**
     * Search jobseekers by skills, title, location
     * FIXED: Added proper GROUP BY and DISTINCT
     *
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function search(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT DISTINCT jp.*, 
                CONCAT(jp.first_name, ' ', jp.last_name) as full_name,
                ul.city, ul.country,
                (SELECT COUNT(DISTINCT id) FROM jobseeker_skills WHERE jobseeker_id = jp.id) as skill_count
                FROM {$this->table} jp
                LEFT JOIN user_locations ul ON jp.user_location_id = ul.id
                LEFT JOIN jobseeker_skills js ON jp.id = js.jobseeker_id
                LEFT JOIN skills s ON js.skill_id = s.id
                WHERE 1=1";
        
        $params = [];

        // Filter by open to work
        if (!isset($filters['open_to_work']) || $filters['open_to_work'] !== false) {
            $sql .= " AND jp.open_to_work = 1";
        }

        // Filter by skills
        if (!empty($filters['skills'])) {
            $placeholders = implode(',', array_fill(0, count($filters['skills']), '?'));
            $sql .= " AND s.id IN ($placeholders)";
            $params = array_merge($params, $filters['skills']);
        }

        // Filter by title
        if (!empty($filters['title'])) {
            $sql .= " AND jp.professional_title LIKE ?";
            $params[] = '%' . $filters['title'] . '%';
        }

        // Filter by location
        if (!empty($filters['location'])) {
            $sql .= " AND (ul.city LIKE ? OR ul.country LIKE ?)";
            $params[] = '%' . $filters['location'] . '%';
            $params[] = '%' . $filters['location'] . '%';
        }

        // Filter by remote preference
        if (!empty($filters['remote'])) {
            $sql .= " AND jp.remote_preference = 1";
        }

        // Filter by relocation willingness
        if (!empty($filters['relocation'])) {
            $sql .= " AND jp.relocation_willing = 1";
        }

        // Filter by years experience
        if (!empty($filters['min_experience'])) {
            $sql .= " AND jp.years_experience >= ?";
            $params[] = $filters['min_experience'];
        }

        $sql .= " GROUP BY jp.id ORDER BY jp.profile_completion DESC, jp.updated_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count total jobseekers matching filters
     * FIXED: Uses COUNT(DISTINCT) for accurate counts
     *
     * @param array $filters
     * @return int
     */
    public function countSearch(array $filters = []): int
    {
        $sql = "SELECT COUNT(DISTINCT jp.id) as total 
                FROM {$this->table} jp
                LEFT JOIN user_locations ul ON jp.user_location_id = ul.id
                LEFT JOIN jobseeker_skills js ON jp.id = js.jobseeker_id
                LEFT JOIN skills s ON js.skill_id = s.id
                WHERE 1=1";
        
        $params = [];

        // Apply same filters as search method
        if (!isset($filters['open_to_work']) || $filters['open_to_work'] !== false) {
            $sql .= " AND jp.open_to_work = 1";
        }

        if (!empty($filters['skills'])) {
            $placeholders = implode(',', array_fill(0, count($filters['skills']), '?'));
            $sql .= " AND s.id IN ($placeholders)";
            $params = array_merge($params, $filters['skills']);
        }

        if (!empty($filters['title'])) {
            $sql .= " AND jp.professional_title LIKE ?";
            $params[] = '%' . $filters['title'] . '%';
        }

        if (!empty($filters['location'])) {
            $sql .= " AND (ul.city LIKE ? OR ul.country LIKE ?)";
            $params[] = '%' . $filters['location'] . '%';
            $params[] = '%' . $filters['location'] . '%';
        }

        $result = $this->fetchOne($sql, $params);
        return (int)($result['total'] ?? 0);
    }

    // ==================== STATISTICS ====================

    /**
     * Get jobseeker statistics
     * FIXED: Uses COUNT(DISTINCT) for accurate counts
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $stats = [];

        // Total jobseekers
        $result = $this->fetchOne("SELECT COUNT(DISTINCT id) as total FROM {$this->table}");
        $stats['total'] = $result['total'] ?? 0;

        // Active jobseekers (open to work)
        $result = $this->fetchOne("SELECT COUNT(DISTINCT id) as total FROM {$this->table} WHERE open_to_work = 1");
        $stats['active'] = $result['total'] ?? 0;

        // Average profile completion
        $result = $this->fetchOne("SELECT AVG(profile_completion) as avg FROM {$this->table}");
        $stats['avg_completion'] = round($result['avg'] ?? 0);

        // By remote preference
        $result = $this->fetchOne("SELECT COUNT(DISTINCT id) as total FROM {$this->table} WHERE remote_preference = 1");
        $stats['remote_ready'] = $result['total'] ?? 0;

        // By relocation willingness
        $result = $this->fetchOne("SELECT COUNT(DISTINCT id) as total FROM {$this->table} WHERE relocation_willing = 1");
        $stats['relocation_ready'] = $result['total'] ?? 0;

        // Top skills
        $sql = "SELECT s.name, COUNT(DISTINCT js.jobseeker_id) as count 
                FROM jobseeker_skills js
                INNER JOIN skills s ON js.skill_id = s.id
                GROUP BY js.skill_id
                ORDER BY count DESC
                LIMIT 10";
        $stats['top_skills'] = $this->fetchAll($sql);

        return $stats;
    }

    // ==================== CV MANAGEMENT ====================

    /**
     * Update CV path for jobseeker
     *
     * @param int $userId
     * @param string $cvPath
     * @return bool
     */
    public function updateCV(int $userId, string $cvPath): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET resume_url = :cv_path WHERE user_id = :user_id";
            $result = $this->execute($sql, [
                ':cv_path' => $cvPath,
                ':user_id' => $userId
            ]);

            return $result > 0;
        } catch (PDOException $e) {
            error_log("Failed to update CV: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete CV for jobseeker (set resume_url to null)
     *
     * @param int $userId
     * @return bool
     */
    public function deleteCV(int $userId): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET resume_url = NULL WHERE user_id = :user_id";
            $result = $this->execute($sql, [':user_id' => $userId]);

            return $result > 0;
        } catch (PDOException $e) {
            error_log("Failed to delete CV: " . $e->getMessage());
            return false;
        }
    }
}
<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\UserRepository;
use App\Repositories\SkillRepository;
use App\Repositories\SkillCategoryRepository;
use App\Repositories\SchoolRepository;

/**
 * AdminService - Handles all admin operations
 * 
 * Manages:
 * - User blocking/unblocking
 * - Skills management
 * - Schools management
 * - Admin dashboard statistics
 */
class AdminService extends BaseService
{
    private UserRepository $userRepo;
    private SkillRepository $skillRepo;
    private SkillCategoryRepository $skillCategoryRepo;
    private SchoolRepository $schoolRepo;

    public function __construct(
        UserRepository $userRepo,
        SkillRepository $skillRepo,
        SkillCategoryRepository $skillCategoryRepo,
        SchoolRepository $schoolRepo
    ) {
        parent::__construct();
        $this->userRepo = $userRepo;
        $this->skillRepo = $skillRepo;
        $this->skillCategoryRepo = $skillCategoryRepo;
        $this->schoolRepo = $schoolRepo;
    }

    // ==================== USER MANAGEMENT ====================

    /**
     * Get all users with pagination
     *
     * @param int $page
     * @param int $perPage
     * @param array $filters
     * @return array
     */
    public function getAllUsers(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        try {
            // Get all users (will need to implement pagination in repository)
            $allUsers = $this->userRepo->findAll('created_at DESC');

            // Apply filters
            $filtered = $allUsers;

            if (!empty($filters['role'])) {
                $filtered = array_filter($filtered, fn($u) => $u['role'] === $filters['role']);
            }

            if (!empty($filters['status'])) {
                if ($filters['status'] === 'blocked') {
                    $filtered = array_filter($filtered, fn($u) => $u['is_blocked'] === 1);
                } elseif ($filters['status'] === 'active') {
                    $filtered = array_filter($filtered, fn($u) => $u['is_blocked'] === 0);
                }
            }

            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $filtered = array_filter($filtered, fn($u) => 
                    stripos($u['email'], $search) !== false || 
                    stripos($u['first_name'] ?? '', $search) !== false || 
                    stripos($u['last_name'] ?? '', $search) !== false
                );
            }

            if (!empty($filters['verified'])) {
                if ($filters['verified'] === 'yes') {
                    $filtered = array_filter($filtered, fn($u) => !empty($u['email_verified_at']));
                } else {
                    $filtered = array_filter($filtered, fn($u) => empty($u['email_verified_at']));
                }
            }

            // Reindex array
            $filtered = array_values($filtered);
            $total = count($filtered);

            // Apply pagination
            $offset = ($page - 1) * $perPage;
            $users = array_slice($filtered, $offset, $perPage);

            return [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $perPage)
            ];
        } catch (\Exception $e) {
            $this->logError('Error fetching users', ['error' => $e->getMessage()]);
            $this->addError('users', 'Failed to fetch users');
            return ['users' => [], 'total' => 0, 'page' => 1, 'pages' => 0];
        }
    }

    /**
     * Block a user until a specific datetime
     *
     * @param int $userId
     * @param string $blockedUntil DateTime string (Y-m-d H:i:s)
     * @param string $reason Block reason
     * @return bool
     */
    public function blockUserUntil(int $userId, string $blockedUntil, string $reason = ''): bool
    {
        try {
            $user = $this->userRepo->findById($userId);
            if (!$user) {
                $this->addError('user', 'User not found');
                return false;
            }

            // Validate datetime format
            if (!strtotime($blockedUntil)) {
                $this->addError('blockedUntil', 'Invalid datetime format');
                return false;
            }

            $result = $this->userRepo->update($userId, [
                'is_blocked' => 1,
                'blocked_until' => $blockedUntil
            ]);

            if ($result) {
                $this->logInfo('User blocked', [
                    'user_id' => $userId,
                    'blocked_until' => $blockedUntil,
                    'reason' => $reason
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->logError('Exception in blockUserUntil', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $this->addError('block', 'Failed to block user');
            return false;
        }
    }

    /**
     * Unblock a user
     *
     * @param int $userId
     * @return bool
     */
    public function unblockUser(int $userId): bool
    {
        try {
            $user = $this->userRepo->findById($userId);
            if (!$user) {
                $this->addError('user', 'User not found');
                return false;
            }

            $result = $this->userRepo->unblockUser($userId);

            if ($result) {
                $this->logInfo('User unblocked', ['user_id' => $userId]);
            }

            return $result;
        } catch (\Exception $e) {
            $this->logError('Exception in unblockUser', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $this->addError('unblock', 'Failed to unblock user');
            return false;
        }
    }

    /**
     * Bulk block users until datetime
     *
     * @param array $userIds
     * @param string $blockedUntil
     * @return int Number of blocked users
     */
    public function bulkBlockUsers(array $userIds, string $blockedUntil): int
    {
        try {
            if (!strtotime($blockedUntil)) {
                $this->addError('blockedUntil', 'Invalid datetime format');
                return 0;
            }

            $count = 0;
            foreach ($userIds as $userId) {
                $result = $this->userRepo->update($userId, [
                    'is_blocked' => 1,
                    'blocked_until' => $blockedUntil
                ]);
                if ($result) {
                    $count++;
                }
            }

            if ($count > 0) {
                $this->logInfo('Bulk blocked users', ['count' => $count, 'blocked_until' => $blockedUntil]);
            }

            return $count;
        } catch (\Exception $e) {
            $this->logError('Exception in bulkBlockUsers', ['error' => $e->getMessage()]);
            $this->addError('bulk_block', 'Failed to bulk block users');
            return 0;
        }
    }

    /**
     * Bulk unblock users
     *
     * @param array $userIds
     * @return int Number of unblocked users
     */
    public function bulkUnblockUsers(array $userIds): int
    {
        try {
            $count = 0;
            foreach ($userIds as $userId) {
                $result = $this->userRepo->unblockUser($userId);
                if ($result) {
                    $count++;
                }
            }

            if ($count > 0) {
                $this->logInfo('Bulk unblocked users', ['count' => $count]);
            }

            return $count;
        } catch (\Exception $e) {
            $this->logError('Exception in bulkUnblockUsers', ['error' => $e->getMessage()]);
            $this->addError('bulk_unblock', 'Failed to bulk unblock users');
            return 0;
        }
    }

    /**
     * Get user statistics
     *
     * @return array
     */
    public function getUserStats(): array
    {
        try {
            $total = $this->userRepo->count([]);
            $blocked = $this->userRepo->count(['is_blocked' => 1]);
            $verified = count(array_filter($this->userRepo->findAll(), fn($u) => !empty($u['email_verified_at'])));

            return [
                'total' => $total,
                'blocked' => $blocked,
                'verified' => $verified,
                'unverified' => $total - $verified
            ];
        } catch (\Exception $e) {
            $this->logError('Error fetching user stats', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ==================== SKILLS MANAGEMENT ====================

    /**
     * Get all skills with pagination
     *
     * @param int $page
     * @param int $perPage
     * @param array $filters
     * @return array
     */
    public function getAllSkills(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        try {
            $offset = ($page - 1) * $perPage;

            // Get all skills with categories
            $allSkills = $this->skillRepo->getAllWithCategories();

            // Apply filters
            if (!empty($filters['category_id'])) {
                $allSkills = array_filter($allSkills, fn($s) => $s['category_id'] == $filters['category_id']);
            }

            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $allSkills = array_filter($allSkills, fn($s) => stripos($s['name'], $search) !== false);
            }

            $total = count($allSkills);
            $skills = array_slice($allSkills, $offset, $perPage, true);

            return [
                'skills' => array_values($skills),
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $perPage)
            ];
        } catch (\Exception $e) {
            $this->logError('Error fetching skills', ['error' => $e->getMessage()]);
            return ['skills' => [], 'total' => 0, 'page' => 1, 'pages' => 0];
        }
    }

    /**
     * Create a new skill
     *
     * @param array $data
     * @return int|null Skill ID
     */
    public function createSkill(array $data): ?int
    {
        try {
            // Validate name
            if (empty($data['name'])) {
                $this->addError('name', 'Skill name is required');
                return null;
            }

            // Check if skill already exists
            $existing = $this->skillRepo->findByName($data['name']);
            if ($existing) {
                $this->addError('name', 'Skill already exists');
                return null;
            }

            $skillData = [
                'name' => trim($data['name']),
                'category_id' => $data['category_id'] ?? null,
                'description' => trim($data['description'] ?? '')
            ];

            $skillId = $this->skillRepo->create($skillData);

            if ($skillId) {
                $this->logInfo('Skill created', ['skill_id' => $skillId, 'name' => $data['name']]);
            }

            return $skillId;
        } catch (\Exception $e) {
            $this->logError('Exception in createSkill', ['error' => $e->getMessage()]);
            $this->addError('skill', 'Failed to create skill');
            return null;
        }
    }

    /**
     * Update a skill
     *
     * @param int $skillId
     * @param array $data
     * @return bool
     */
    public function updateSkill(int $skillId, array $data): bool
    {
        try {
            $skill = $this->skillRepo->findById($skillId);
            if (!$skill) {
                $this->addError('skill', 'Skill not found');
                return false;
            }

            $updateData = [];

            if (!empty($data['name']) && $data['name'] !== $skill['name']) {
                // Check if new name already exists
                $existing = $this->skillRepo->findByName($data['name']);
                if ($existing && $existing['id'] !== $skillId) {
                    $this->addError('name', 'Skill name already exists');
                    return false;
                }
                $updateData['name'] = trim($data['name']);
            }

            if (isset($data['category_id'])) {
                $updateData['category_id'] = $data['category_id'] ?: null;
            }

            if (isset($data['description'])) {
                $updateData['description'] = trim($data['description']);
            }

            if (empty($updateData)) {
                return true;
            }

            return $this->skillRepo->update($skillId, $updateData);
        } catch (\Exception $e) {
            $this->logError('Exception in updateSkill', ['skill_id' => $skillId, 'error' => $e->getMessage()]);
            $this->addError('skill', 'Failed to update skill');
            return false;
        }
    }

    /**
     * Delete a skill
     *
     * @param int $skillId
     * @return bool
     */
    public function deleteSkill(int $skillId): bool
    {
        try {
            $skill = $this->skillRepo->findById($skillId);
            if (!$skill) {
                $this->addError('skill', 'Skill not found');
                return false;
            }

            return $this->skillRepo->delete($skillId);
        } catch (\Exception $e) {
            $this->logError('Exception in deleteSkill', ['skill_id' => $skillId, 'error' => $e->getMessage()]);
            $this->addError('skill', 'Failed to delete skill');
            return false;
        }
    }

    /**
     * Get all skill categories
     *
     * @return array
     */
    public function getSkillCategories(): array
    {
        try {
            // Get all categories - need to check if method exists
            $categories = $this->skillCategoryRepo->findAll();
            return $categories;
        } catch (\Exception $e) {
            $this->logError('Error fetching skill categories', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ==================== SCHOOLS MANAGEMENT ====================

    /**
     * Get all schools with pagination
     *
     * @param int $page
     * @param int $perPage
     * @param array $filters
     * @return array
     */
    public function getAllSchools(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        try {
            $offset = ($page - 1) * $perPage;

            // Get all schools
            $allSchools = $this->schoolRepo->findAll('name ASC');

            // Apply filters
            if (!empty($filters['type'])) {
                $allSchools = array_filter($allSchools, fn($s) => $s['type'] === $filters['type']);
            }

            if (!empty($filters['country'])) {
                $allSchools = array_filter($allSchools, fn($s) => $s['country'] === $filters['country']);
            }

            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $allSchools = array_filter($allSchools, fn($s) => stripos($s['name'], $search) !== false);
            }

            // Reindex array
            $allSchools = array_values($allSchools);
            $total = count($allSchools);

            // Apply pagination
            $schools = array_slice($allSchools, $offset, $perPage);

            return [
                'schools' => $schools,
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $perPage)
            ];
        } catch (\Exception $e) {
            $this->logError('Error fetching schools', ['error' => $e->getMessage()]);
            return ['schools' => [], 'total' => 0, 'page' => 1, 'pages' => 0];
        }
    }

    /**
     * Create a new school
     *
     * @param array $data
     * @return int|null School ID
     */
    public function createSchool(array $data): ?int
    {
        try {
            // Validate required fields
            if (empty($data['name'])) {
                $this->addError('name', 'School name is required');
                return null;
            }

            // Check if school already exists
            $existing = $this->schoolRepo->findByName($data['name']);
            if ($existing) {
                $this->addError('name', 'School already exists');
                return null;
            }

            $schoolData = [
                'name' => trim($data['name']),
                'slug' => $this->generateSlug($data['name']),
                'type' => $data['type'] ?? 'university',
                'country' => $data['country'] ?? 'Kenya'
            ];

            $schoolId = $this->schoolRepo->create($schoolData);

            if ($schoolId) {
                $this->logInfo('School created', ['school_id' => $schoolId, 'name' => $data['name']]);
            }

            return $schoolId;
        } catch (\Exception $e) {
            $this->logError('Exception in createSchool', ['error' => $e->getMessage()]);
            $this->addError('school', 'Failed to create school');
            return null;
        }
    }

    /**
     * Update a school
     *
     * @param int $schoolId
     * @param array $data
     * @return bool
     */
    public function updateSchool(int $schoolId, array $data): bool
    {
        try {
            $school = $this->schoolRepo->findById($schoolId);
            if (!$school) {
                $this->addError('school', 'School not found');
                return false;
            }

            $updateData = [];

            if (!empty($data['name']) && $data['name'] !== $school['name']) {
                // Check if new name already exists
                $existing = $this->schoolRepo->findByName($data['name']);
                if ($existing && $existing['id'] !== $schoolId) {
                    $this->addError('name', 'School name already exists');
                    return false;
                }
                $updateData['name'] = trim($data['name']);
                $updateData['slug'] = $this->generateSlug($data['name']);
            }

            if (isset($data['type'])) {
                $updateData['type'] = $data['type'];
            }

            if (isset($data['country'])) {
                $updateData['country'] = $data['country'];
            }

            if (empty($updateData)) {
                return true;
            }

            return $this->schoolRepo->update($schoolId, $updateData);
        } catch (\Exception $e) {
            $this->logError('Exception in updateSchool', ['school_id' => $schoolId, 'error' => $e->getMessage()]);
            $this->addError('school', 'Failed to update school');
            return false;
        }
    }

    /**
     * Delete a school
     *
     * @param int $schoolId
     * @return bool
     */
    public function deleteSchool(int $schoolId): bool
    {
        try {
            $school = $this->schoolRepo->findById($schoolId);
            if (!$school) {
                $this->addError('school', 'School not found');
                return false;
            }

            return $this->schoolRepo->delete($schoolId);
        } catch (\Exception $e) {
            $this->logError('Exception in deleteSchool', ['school_id' => $schoolId, 'error' => $e->getMessage()]);
            $this->addError('school', 'Failed to delete school');
            return false;
        }
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Generate slug from name
     *
     * @param string $name
     * @return string
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    /**
     * Get admin dashboard statistics
     *
     * @return array
     */
    public function getDashboardStats(): array
    {
        try {
            $allUsers = $this->userRepo->findAll();
            $totalUsers = count($allUsers);
            $blockedUsers = count(array_filter($allUsers, fn($u) => $u['is_blocked'] === 1));
            $totalSkills = $this->skillRepo->count([]);
            $totalSchools = $this->schoolRepo->count([]);

            return [
                'users' => [
                    'total' => $totalUsers
                ],
                'blocked_users' => $blockedUsers,
                'total_skills' => $totalSkills,
                'total_schools' => $totalSchools,
            ];
        } catch (\Exception $e) {
            $this->logError('Error fetching dashboard stats', ['error' => $e->getMessage()]);
            return [];
        }
    }
}

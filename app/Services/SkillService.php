<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\SkillRepository;
use App\Repositories\SkillCategoryRepository;

/**
 * SkillService - Manages skills and categories business logic for the webapp
 * 
 * PURPOSE: Handles all skill-related operations, validation, and business rules
 * 
 * FUNCTIONALITY:
 * - Skill CRUD operations with validation
 * - Category CRUD operations with validation
 * - Skill search and filtering
 * - Category management with ordering
 * - Popular skills tracking
 * - Skill suggestions based on job titles
 */
class SkillService extends BaseService
{
    private SkillRepository $skillRepo;
    private SkillCategoryRepository $categoryRepo;

    /**
     * @var array Allowed fields for skill creation/update
     */
    private array $allowedSkillFields = ['name', 'slug', 'category_id', 'description', 'is_active'];

    /**
     * @var array Allowed fields for category creation/update
     */
    private array $allowedCategoryFields = ['name', 'slug', 'sort_order'];

    public function __construct(
        SkillRepository $skillRepo,
        SkillCategoryRepository $categoryRepo
    ) {
        parent::__construct();
        $this->skillRepo = $skillRepo;
        $this->categoryRepo = $categoryRepo;
    }

    // ==================== SKILL METHODS ====================

    /**
     * Get all skills with optional filtering
     * 
     * @param array $filters Optional filters (category_id, search, is_active)
     * @return array Skills with category details
     */
    public function getAllSkills(array $filters = []): array
    {
        try {
            $this->logInfo('Fetching skills', ['filters' => $filters]);

            if (!empty($filters['search'])) {
                $skills = $this->skillRepo->search(
                    $filters['search'],
                    $filters['category_id'] ?? null,
                    $filters['limit'] ?? 50
                );
            } elseif (!empty($filters['category_id'])) {
                $skills = $this->skillRepo->getByCategory($filters['category_id']);
            } else {
                $skills = $this->skillRepo->getAllWithCategories();
            }

            // Filter by active status if specified
            if (isset($filters['is_active']) && !empty($skills)) {
                $skills = array_filter($skills, fn($s) => ($s['is_active'] ?? 1) == $filters['is_active']);
                $skills = array_values($skills);
            }

            return $skills;

        } catch (\Exception $e) {
            $this->logError('Failed to fetch skills', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get skill by ID
     * 
     * @param int $id Skill ID
     * @return array|null Skill with category details or null
     */
    public function getSkillById(int $id): ?array
    {
        try {
            if ($id <= 0) {
                $this->addError('id', 'Invalid skill ID');
                return null;
            }

            $skill = $this->skillRepo->findById($id);

            if (!$skill) {
                $this->addError('id', 'Skill not found');
                return null;
            }

            return $skill;

        } catch (\Exception $e) {
            $this->logError('Failed to get skill by ID', ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get skill by slug
     * 
     * @param string $slug Skill slug
     * @return array|null Skill with category details or null
     */
    public function getSkillBySlug(string $slug): ?array
    {
        try {
            if (empty($slug)) {
                $this->addError('slug', 'Slug is required');
                return null;
            }

            $skills = $this->skillRepo->findWhere(['slug' => $slug]);
            
            if (empty($skills)) {
                $this->addError('slug', 'Skill not found');
                return null;
            }

            return $skills[0];

        } catch (\Exception $e) {
            $this->logError('Failed to get skill by slug', ['slug' => $slug, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create a new skill
     * 
     * @param array $data Skill data
     * @return array|null Created skill or null on failure
     */
    public function createSkill(array $data): ?array
    {
        try {
            $this->logInfo('Creating new skill', ['data' => $data]);

            // Validate required fields
            if (!$this->validateRequired($data, ['name'])) {
                return null;
            }

            // Sanitize input
            $data = $this->sanitize($data, $this->allowedSkillFields);

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSkillSlug($data['name']);
            }

            // Validate name length
            if (!$this->validateLength($data['name'], 'name', 2, 100)) {
                return null;
            }

            // Validate slug format
            if (!$this->validateSlugFormat($data['slug'])) {
                $this->addError('slug', 'Slug can only contain lowercase letters, numbers, and hyphens');
                return null;
            }

            // Check if skill already exists
            $existing = $this->skillRepo->findByName($data['name']);
            if ($existing) {
                $this->addError('name', 'A skill with this name already exists');
                return null;
            }

            // Validate category if provided
            if (!empty($data['category_id'])) {
                $category = $this->categoryRepo->findById($data['category_id']);
                if (!$category) {
                    $this->addError('category_id', 'Selected category does not exist');
                    return null;
                }
            }

            // Set default values
            $data['is_active'] = $data['is_active'] ?? 1;

            // Create the skill
            $id = $this->skillRepo->create($data);

            if (!$id) {
                $this->logError('Failed to create skill in database', ['data' => $data]);
                $this->addError('general', 'Failed to create skill');
                return null;
            }

            $skill = $this->skillRepo->findById($id);
            $this->logInfo('Skill created successfully', ['id' => $id, 'name' => $data['name']]);

            return $skill;

        } catch (\Exception $e) {
            $this->logError('Exception in createSkill', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('general', 'An error occurred while creating skill');
            return null;
        }
    }

    /**
     * Update an existing skill
     * 
     * @param int $id Skill ID
     * @param array $data Updated skill data
     * @return bool Success
     */
    public function updateSkill(int $id, array $data): bool
    {
        try {
            $this->logInfo('Updating skill', ['id' => $id, 'data' => $data]);

            // Check if skill exists
            $existing = $this->skillRepo->findById($id);
            if (!$existing) {
                $this->addError('id', 'Skill not found');
                return false;
            }

            // Sanitize input
            $data = $this->sanitize($data, $this->allowedSkillFields);

            // Validate name if provided
            if (isset($data['name'])) {
                if (!$this->validateLength($data['name'], 'name', 2, 100)) {
                    return false;
                }

                // Check if name already exists (excluding current skill)
                $nameCheck = $this->skillRepo->findByName($data['name']);
                if ($nameCheck && $nameCheck['id'] != $id) {
                    $this->addError('name', 'Skill name already exists');
                    return false;
                }
            }

            // Handle slug if provided or if name changed
            if (isset($data['slug'])) {
                if (!$this->validateSlugFormat($data['slug'])) {
                    $this->addError('slug', 'Slug can only contain lowercase letters, numbers, and hyphens');
                    return false;
                }
            } elseif (isset($data['name']) && $data['name'] !== $existing['name']) {
                $data['slug'] = $this->generateSkillSlug($data['name']);
            }

            // Validate category if provided
            if (isset($data['category_id'])) {
                if (!empty($data['category_id'])) {
                    $category = $this->categoryRepo->findById($data['category_id']);
                    if (!$category) {
                        $this->addError('category_id', 'Selected category does not exist');
                        return false;
                    }
                }
            }

            // Perform update
            $success = $this->skillRepo->update($id, $data);

            if ($success) {
                $this->logInfo('Skill updated successfully', ['id' => $id]);
            } else {
                $this->logWarning('Skill update failed', ['id' => $id]);
                $this->addError('general', 'Failed to update skill');
            }

            return $success;

        } catch (\Exception $e) {
            $this->logError('Exception in updateSkill', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->addError('general', 'An error occurred while updating skill');
            return false;
        }
    }

    /**
     * Delete a skill
     * 
     * @param int $id Skill ID
     * @return bool Success
     */
    public function deleteSkill(int $id): bool
    {
        try {
            $this->logInfo('Deleting skill', ['id' => $id]);

            // Check if skill exists
            $existing = $this->skillRepo->findById($id);
            if (!$existing) {
                $this->addError('id', 'Skill not found');
                return false;
            }

            // Check if skill is in use by job seekers
            if ($this->isSkillInUse($id)) {
                $this->addError('general', 'Cannot delete skill that is used by job seekers');
                return false;
            }

            $success = $this->skillRepo->delete($id);

            if ($success) {
                $this->logInfo('Skill deleted successfully', ['id' => $id]);
            } else {
                $this->logWarning('Skill deletion failed', ['id' => $id]);
                $this->addError('general', 'Failed to delete skill');
            }

            return $success;

        } catch (\Exception $e) {
            $this->logError('Exception in deleteSkill', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->addError('general', 'An error occurred while deleting skill');
            return false;
        }
    }

    /**
     * Toggle skill active status
     * 
     * @param int $id Skill ID
     * @return bool Success
     */
    public function toggleSkillStatus(int $id): bool
    {
        try {
            $skill = $this->skillRepo->findById($id);
            if (!$skill) {
                $this->addError('id', 'Skill not found');
                return false;
            }

            $newStatus = !($skill['is_active'] ?? true);
            $success = $this->skillRepo->update($id, ['is_active' => $newStatus]);

            if ($success) {
                $this->logInfo('Skill status toggled', [
                    'id' => $id,
                    'new_status' => $newStatus
                ]);
            }

            return $success;

        } catch (\Exception $e) {
            $this->logError('Failed to toggle skill status', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get popular skills
     * 
     * @param int $limit Number of skills to return
     * @return array Popular skills with usage counts
     */
    public function getPopularSkills(int $limit = 20): array
    {
        try {
            return $this->skillRepo->getPopular($limit);
        } catch (\Exception $e) {
            $this->logError('Failed to get popular skills', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Search skills
     * 
     * @param string $query Search query
     * @param int|null $categoryId Optional category filter
     * @param int $limit Results limit
     * @return array Matching skills
     */
    public function searchSkills(string $query, ?int $categoryId = null, int $limit = 20): array
    {
        try {
            if (strlen($query) < 2) {
                return [];
            }

            return $this->skillRepo->search($query, $categoryId, $limit);

        } catch (\Exception $e) {
            $this->logError('Failed to search skills', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get autocomplete suggestions
     * 
     * @param string $query Search query
     * @param int $limit Results limit
     * @return array Skill suggestions for autocomplete
     */
    public function autocompleteSkills(string $query, int $limit = 10): array
    {
        try {
            if (strlen($query) < 1) {
                return [];
            }

            return $this->skillRepo->autocomplete($query, $limit);

        } catch (\Exception $e) {
            $this->logError('Failed to get autocomplete suggestions', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get skill suggestions based on job title
     * 
     * @param string $jobTitle Job title to suggest skills for
     * @param int $limit Number of suggestions
     * @return array Suggested skills
     */
    public function suggestSkillsForJobTitle(string $jobTitle, int $limit = 10): array
    {
        try {
            if (empty($jobTitle)) {
                return $this->getPopularSkills($limit);
            }

            return $this->skillRepo->suggestForJobTitle($jobTitle, $limit);

        } catch (\Exception $e) {
            $this->logError('Failed to suggest skills for job title', [
                'job_title' => $jobTitle,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get related skills
     * 
     * @param int $skillId Skill ID to find related skills for
     * @param int $limit Number of related skills
     * @return array Related skills
     */
    public function getRelatedSkills(int $skillId, int $limit = 5): array
    {
        try {
            $skill = $this->skillRepo->findById($skillId);
            if (!$skill) {
                $this->addError('skill_id', 'Skill not found');
                return [];
            }

            return $this->skillRepo->getRelatedSkills($skillId, $limit);

        } catch (\Exception $e) {
            $this->logError('Failed to get related skills', [
                'skill_id' => $skillId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    // ==================== CATEGORY METHODS ====================

    /**
     * Get all categories
     * 
     * @param bool $withSkillCount Include skill count
     * @return array All categories
     */
    public function getAllCategories(bool $withSkillCount = false): array
    {
        try {
            if ($withSkillCount) {
                return $this->categoryRepo->getCategoriesWithSkillCount();
            }

            return $this->categoryRepo->getAllOrdered();

        } catch (\Exception $e) {
            $this->logError('Failed to get categories', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get category by ID
     * 
     * @param int $id Category ID
     * @return array|null Category or null
     */
    public function getCategoryById(int $id): ?array
    {
        try {
            if ($id <= 0) {
                $this->addError('id', 'Invalid category ID');
                return null;
            }

            $category = $this->categoryRepo->findById($id);

            if (!$category) {
                $this->addError('id', 'Category not found');
                return null;
            }

            return $category;

        } catch (\Exception $e) {
            $this->logError('Failed to get category by ID', ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get category by slug
     * 
     * @param string $slug Category slug
     * @return array|null Category or null
     */
    public function getCategoryBySlug(string $slug): ?array
    {
        try {
            if (empty($slug)) {
                $this->addError('slug', 'Slug is required');
                return null;
            }

            return $this->categoryRepo->findBySlug($slug);

        } catch (\Exception $e) {
            $this->logError('Failed to get category by slug', ['slug' => $slug, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get category with its skills
     * 
     * @param int $id Category ID
     * @return array|null Category with skills or null
     */
    public function getCategoryWithSkills(int $id): ?array
    {
        try {
            return $this->categoryRepo->getCategoryWithSkills($id);
        } catch (\Exception $e) {
            $this->logError('Failed to get category with skills', ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create a new category
     * 
     * @param array $data Category data
     * @return array|null Created category or null
     */
    public function createCategory(array $data): ?array
    {
        try {
            $this->logInfo('Creating new category', ['data' => $data]);

            // Validate required fields
            if (!$this->validateRequired($data, ['name'])) {
                return null;
            }

            // Sanitize input
            $data = $this->sanitize($data, $this->allowedCategoryFields);

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateCategorySlug($data['name']);
            }

            // Validate name length
            if (!$this->validateLength($data['name'], 'name', 2, 100)) {
                return null;
            }

            // Validate slug format
            if (!$this->validateSlugFormat($data['slug'])) {
                $this->addError('slug', 'Slug can only contain lowercase letters, numbers, and hyphens');
                return null;
            }

            // Check if slug already exists
            $existing = $this->categoryRepo->findBySlug($data['slug']);
            if ($existing) {
                $this->addError('slug', 'A category with this slug already exists');
                return null;
            }

            // Set default sort_order if not provided
            if (!isset($data['sort_order'])) {
                $categories = $this->categoryRepo->getAllOrdered();
                $data['sort_order'] = count($categories) > 0 
                    ? (max(array_column($categories, 'sort_order')) + 10)
                    : 0;
            }

            // Create the category
            $id = $this->categoryRepo->create($data);

            if (!$id) {
                $this->logError('Failed to create category in database', ['data' => $data]);
                $this->addError('general', 'Failed to create category');
                return null;
            }

            $category = $this->categoryRepo->findById($id);
            $this->logInfo('Category created successfully', ['id' => $id, 'name' => $data['name']]);

            return $category;

        } catch (\Exception $e) {
            $this->logError('Exception in createCategory', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('general', 'An error occurred while creating category');
            return null;
        }
    }

    /**
     * Update an existing category
     * 
     * @param int $id Category ID
     * @param array $data Updated category data
     * @return bool Success
     */
    public function updateCategory(int $id, array $data): bool
    {
        try {
            $this->logInfo('Updating category', ['id' => $id, 'data' => $data]);

            // Check if category exists
            $existing = $this->categoryRepo->findById($id);
            if (!$existing) {
                $this->addError('id', 'Category not found');
                return false;
            }

            // Sanitize input
            $data = $this->sanitize($data, $this->allowedCategoryFields);

            // Validate name if provided
            if (isset($data['name'])) {
                if (!$this->validateLength($data['name'], 'name', 2, 100)) {
                    return false;
                }
            }

            // Handle slug
            if (isset($data['slug'])) {
                if (!$this->validateSlugFormat($data['slug'])) {
                    $this->addError('slug', 'Slug can only contain lowercase letters, numbers, and hyphens');
                    return false;
                }

                // Check if slug already exists (excluding current)
                $slugCheck = $this->categoryRepo->findBySlug($data['slug']);
                if ($slugCheck && $slugCheck['id'] != $id) {
                    $this->addError('slug', 'This slug already exists');
                    return false;
                }
            } elseif (isset($data['name']) && $data['name'] !== $existing['name']) {
                $data['slug'] = $this->generateCategorySlug($data['name']);
            }

            // Perform update
            $success = $this->categoryRepo->update($id, $data);

            if ($success) {
                $this->logInfo('Category updated successfully', ['id' => $id]);
            } else {
                $this->logWarning('Category update failed', ['id' => $id]);
                $this->addError('general', 'Failed to update category');
            }

            return $success;

        } catch (\Exception $e) {
            $this->logError('Exception in updateCategory', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->addError('general', 'An error occurred while updating category');
            return false;
        }
    }

    /**
     * Delete a category
     * 
     * @param int $id Category ID
     * @param bool $reassignSkills Whether to reassign skills to another category
     * @param int|null $targetCategoryId Target category ID for reassignment
     * @return bool Success
     */
    public function deleteCategory(int $id, bool $reassignSkills = false, ?int $targetCategoryId = null): bool
    {
        try {
            $this->logInfo('Deleting category', [
                'id' => $id,
                'reassign_skills' => $reassignSkills,
                'target' => $targetCategoryId
            ]);

            // Check if category exists
            $existing = $this->categoryRepo->findById($id);
            if (!$existing) {
                $this->addError('id', 'Category not found');
                return false;
            }

            // Check if category has skills
            $skillsInCategory = $this->skillRepo->getByCategory($id);
            $hasSkills = !empty($skillsInCategory);

            if ($hasSkills && !$reassignSkills) {
                $this->addError('general', 'Cannot delete category that contains skills. Reassign skills first.');
                return false;
            }

            if ($reassignSkills && $targetCategoryId) {
                // Validate target category
                $targetCategory = $this->categoryRepo->findById($targetCategoryId);
                if (!$targetCategory) {
                    $this->addError('target_category_id', 'Target category not found');
                    return false;
                }

                // Reassign skills
                $skillIds = array_column($skillsInCategory, 'id');
                $reassigned = $this->skillRepo->assignCategory($skillIds, $targetCategoryId);
                $this->logInfo('Skills reassigned', ['count' => $reassigned, 'to_category' => $targetCategoryId]);
            } elseif ($reassignSkills && !$targetCategoryId) {
                // Set category_id to NULL for all skills
                foreach ($skillsInCategory as $skill) {
                    $this->skillRepo->update($skill['id'], ['category_id' => null]);
                }
                $this->logInfo('Skills unassigned', ['count' => count($skillsInCategory)]);
            }

            // Delete the category
            $success = $this->categoryRepo->delete($id);

            if ($success) {
                $this->logInfo('Category deleted successfully', ['id' => $id]);
            } else {
                $this->logWarning('Category deletion failed', ['id' => $id]);
                $this->addError('general', 'Failed to delete category');
            }

            return $success;

        } catch (\Exception $e) {
            $this->logError('Exception in deleteCategory', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->addError('general', 'An error occurred while deleting category');
            return false;
        }
    }

    /**
     * Reorder categories
     * 
     * @param array $order Array of category IDs in desired order
     * @return bool Success
     */
    public function reorderCategories(array $order): bool
    {
        try {
            $this->logInfo('Reordering categories', ['order' => $order]);

            if (empty($order)) {
                $this->addError('order', 'Order cannot be empty');
                return false;
            }

            // Validate all categories exist
            foreach ($order as $categoryId) {
                $category = $this->categoryRepo->findById($categoryId);
                if (!$category) {
                    $this->addError('category', "Category ID {$categoryId} not found");
                    return false;
                }
            }

            $success = $this->categoryRepo->reorderCategories($order);

            if ($success) {
                $this->logInfo('Categories reordered successfully');
            } else {
                $this->logWarning('Failed to reorder categories');
                $this->addError('general', 'Failed to reorder categories');
            }

            return $success;

        } catch (\Exception $e) {
            $this->logError('Exception in reorderCategories', [
                'error' => $e->getMessage()
            ]);
            $this->addError('general', 'An error occurred while reordering categories');
            return false;
        }
    }

    /**
     * Get categories with skill counts
     * 
     * @return array Categories with skill counts
     */
    public function getCategoriesWithSkillCounts(): array
    {
        try {
            return $this->categoryRepo->getCategoriesWithSkillCount();
        } catch (\Exception $e) {
            $this->logError('Failed to get categories with skill counts', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get popular categories
     * 
     * @param int $limit Number of categories
     * @return array Popular categories with usage counts
     */
    public function getPopularCategories(int $limit = 10): array
    {
        try {
            return $this->categoryRepo->getPopularCategories($limit);
        } catch (\Exception $e) {
            $this->logError('Failed to get popular categories', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get empty categories (no skills)
     * 
     * @return array Categories with no skills
     */
    public function getEmptyCategories(): array
    {
        try {
            return $this->categoryRepo->getEmptyCategories();
        } catch (\Exception $e) {
            $this->logError('Failed to get empty categories', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ==================== BULK OPERATIONS ====================

    /**
     * Bulk import skills
     * 
     * @param array $skills Array of skill data
     * @return array Import results with counts and errors
     */
    public function bulkImportSkills(array $skills): array
    {
        $result = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        try {
            $this->logInfo('Starting bulk skill import', ['count' => count($skills)]);
            $this->clearErrors();

            foreach ($skills as $index => $skillData) {
                // Handle both string and array input
                if (is_string($skillData)) {
                    $skillData = ['name' => $skillData];
                }

                // Validate required fields
                if (empty($skillData['name'])) {
                    $result['failed']++;
                    $result['errors'][] = "Row {$index}: Missing skill name";
                    continue;
                }

                // Check if skill exists
                $existing = $this->skillRepo->findByName($skillData['name']);
                if ($existing) {
                    $result['skipped']++;
                    continue;
                }

                // Create skill
                $created = $this->createSkill($skillData);

                if ($created) {
                    $result['success']++;
                } else {
                    $result['failed']++;
                    $result['errors'][] = "Row {$index}: Failed to create skill - " . $this->getFirstError();
                    $this->clearErrors();
                }
            }

            $this->logInfo('Bulk skill import completed', $result);

        } catch (\Exception $e) {
            $this->logError('Bulk skill import failed', ['error' => $e->getMessage()]);
            $result['errors'][] = 'Import failed: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Bulk import categories
     * 
     * @param array $categories Array of category names
     * @return array Import results
     */
    public function bulkImportCategories(array $categories): array
    {
        $result = [
            'created' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        try {
            $this->logInfo('Starting bulk category import', ['count' => count($categories)]);

            foreach ($categories as $index => $categoryName) {
                if (empty($categoryName)) {
                    $result['errors'][] = "Row {$index}: Empty category name";
                    continue;
                }

                // Generate slug
                $slug = $this->generateCategorySlug($categoryName);

                // Check if exists
                $existing = $this->categoryRepo->findBySlug($slug);
                if ($existing) {
                    $result['skipped']++;
                    continue;
                }

                // Create category
                $created = $this->createCategory(['name' => $categoryName]);

                if ($created) {
                    $result['created']++;
                } else {
                    $result['errors'][] = "Row {$index}: Failed to create category";
                }
            }

            $this->logInfo('Bulk category import completed', $result);

        } catch (\Exception $e) {
            $this->logError('Bulk category import failed', ['error' => $e->getMessage()]);
            $result['errors'][] = 'Import failed: ' . $e->getMessage();
        }

        return $result;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Validate slug format
     * 
     * @param string $slug Slug to validate
     * @return bool
     */
    private function validateSlugFormat(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }

    /**
     * Generate skill slug
     * 
     * @param string $name Skill name
     * @return string
     */
    private function generateSkillSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->skillSlugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Generate category slug
     * 
     * @param string $name Category name
     * @return string
     */
    private function generateCategorySlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Check if skill slug exists
     * 
     * @param string $slug Slug to check
     * @return bool
     */
    private function skillSlugExists(string $slug): bool
    {
        $skills = $this->skillRepo->findWhere(['slug' => $slug]);
        return !empty($skills);
    }

    /**
     * Check if skill is in use by job seekers
     * 
     * @param int $skillId Skill ID
     * @return bool
     */
    private function isSkillInUse(int $skillId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM jobseeker_skills WHERE skill_id = :skill_id";
        $result = $this->skillRepo->fetchOne($sql, [':skill_id' => $skillId]);
        return ($result['count'] ?? 0) > 0;
    }

    // ==================== STATISTICS ====================

    /**
     * Get comprehensive skill statistics
     * 
     * @return array Statistics
     */
    public function getStatistics(): array
    {
        try {
            $totalSkills = $this->skillRepo->count();
            $totalCategories = $this->categoryRepo->count();
            
            $skillsByCategory = $this->getCategoriesWithSkillCounts();
            $popularSkills = $this->getPopularSkills(10);
            $popularCategories = $this->getPopularCategories(5);
            
            $orphanedSkills = $this->skillRepo->getOrphanedSkills();
            $emptyCategories = $this->getEmptyCategories();

            // Skills added in last 30 days
            $sql = "SELECT COUNT(*) as count FROM skills WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $recentSkills = $this->skillRepo->fetchOne($sql)['count'] ?? 0;

            return [
                'total_skills' => $totalSkills,
                'total_categories' => $totalCategories,
                'skills_by_category' => $skillsByCategory,
                'popular_skills' => $popularSkills,
                'popular_categories' => $popularCategories,
                'orphaned_skills_count' => count($orphanedSkills),
                'empty_categories_count' => count($emptyCategories),
                'recent_skills_30d' => $recentSkills,
                'orphaned_skills' => $orphanedSkills,
                'empty_categories' => $emptyCategories
            ];

        } catch (\Exception $e) {
            $this->logError('Failed to get statistics', ['error' => $e->getMessage()]);
            return [
                'total_skills' => 0,
                'total_categories' => 0,
                'skills_by_category' => [],
                'popular_skills' => [],
                'popular_categories' => [],
                'orphaned_skills_count' => 0,
                'empty_categories_count' => 0,
                'recent_skills_30d' => 0,
                'orphaned_skills' => [],
                'empty_categories' => []
            ];
        }
    }

    /**
     * Export skills data
     * 
     * @param string $format Export format (csv, json)
     * @return mixed Exported data
     */
    public function exportSkills(string $format = 'json')
    {
        try {
            $skills = $this->skillRepo->getAllWithCategories();
            
            if ($format === 'csv') {
                return $this->formatAsCsv($skills);
            }
            
            return $skills;

        } catch (\Exception $e) {
            $this->logError('Failed to export skills', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Format skills as CSV
     * 
     * @param array $skills Skills data
     * @return string CSV string
     */
    private function formatAsCsv(array $skills): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Headers
        fputcsv($output, ['ID', 'Name', 'Slug', 'Category', 'Category ID', 'Created At']);
        
        // Data
        foreach ($skills as $skill) {
            fputcsv($output, [
                $skill['id'],
                $skill['name'],
                $skill['slug'] ?? '',
                $skill['category_name'] ?? 'Uncategorized',
                $skill['category_id'] ?? '',
                $skill['created_at'] ?? ''
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
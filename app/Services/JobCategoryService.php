<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\JobCategoryRepository;
use App\Repositories\JobCategoryMapRepository;
use InvalidArgumentException;
use RuntimeException;

/**
 * JobCategoryService - Business logic for job categories
 * 
 * PURPOSE: Handles category operations, validation, and business rules
 * 
 * FEATURES:
 * - Category CRUD operations with validation
 * - Category tree management
 * - Category assignment to jobs
 * - Category statistics and reporting
 */
class JobCategoryService extends BaseService
{
    private JobCategoryRepository $categoryRepo;
    private JobCategoryMapRepository $mapRepo;
    
    public function __construct(
        JobCategoryRepository $categoryRepo,
        JobCategoryMapRepository $mapRepo
    ) {
        parent::__construct();
        $this->categoryRepo = $categoryRepo;
        $this->mapRepo = $mapRepo;
    }
    
    // ==================== CATEGORY CRUD ====================
    
    /**
     * Get category by ID
     */
    public function getCategory(int $id): ?array
    {
        return $this->categoryRepo->findById($id);
    }
    
    /**
     * Get category by slug
     */
    public function getCategoryBySlug(string $slug): ?array
    {
        return $this->categoryRepo->findBySlug($slug);
    }
    
    /**
     * Get all categories
     */
    public function getAllCategories(bool $onlyActive = true, string $orderBy = 'sort_order ASC, name ASC'): array
    {
        if ($onlyActive) {
            return $this->categoryRepo->findActive($orderBy);
        }
        
        return $this->categoryRepo->findAll($orderBy);
    }
    
    /**
     * Get category tree (hierarchical)
     */
    public function getCategoryTree(bool $onlyActive = true): array
    {
        return $this->categoryRepo->getTree($onlyActive);
    }
    
    /**
     * Get root categories
     */
    public function getRootCategories(bool $onlyActive = true): array
    {
        return $this->categoryRepo->findRootCategories($onlyActive);
    }
    
    /**
     * Get child categories
     */
    public function getChildCategories(int $parentId, bool $onlyActive = true): array
    {
        $parent = $this->categoryRepo->findById($parentId);
        
        if (!$parent) {
            $this->addError('parent_id', 'Parent category not found');
            return [];
        }
        
        return $this->categoryRepo->findChildren($parentId, $onlyActive);
    }
    
    /**
     * Create a new category
     * 
     * @param array $data Category data
     * @return int|null New category ID
     */
    public function createCategory(array $data): ?int
    {
        $this->clearErrors();
        
        // Validate required fields
        $this->validateRequired($data, ['name', 'slug']);
        
        if ($this->fails()) {
            $this->logError('Category validation failed', ['errors' => $this->getErrors()]);
            return null;
        }
        
        // Validate name length
        $this->validateLength($data['name'], 'name', 2, 150);
        
        // Validate slug format and uniqueness
        if (!$this->validateSlug($data['slug'])) {
            $this->addError('slug', 'Slug must be URL-friendly (lowercase letters, numbers, and hyphens only)');
        }
        
        // Check slug uniqueness
        $existingSlug = $this->categoryRepo->findBySlug($data['slug'], false);
        $this->validateUnique(
            fn() => !empty($existingSlug),
            'slug',
            'Slug already exists'
        );
        
        // Validate parent if provided
        if (!empty($data['parent_id'])) {
            $parent = $this->categoryRepo->findById($data['parent_id']);
            if (!$parent) {
                $this->addError('parent_id', 'Parent category does not exist');
            }
        }
        
        if ($this->fails()) {
            $this->logError('Category validation failed', ['data' => $data, 'errors' => $this->getErrors()]);
            return null;
        }
        
        // Prepare data
        $categoryData = $this->sanitize($data, [
            'name', 'slug', 'parent_id', 'description', 'is_active', 'sort_order'
        ]);
        
        // Set defaults if not provided
        if (!isset($categoryData['is_active'])) {
            $categoryData['is_active'] = 1;
        }
        
        if (!isset($categoryData['sort_order'])) {
            $categoryData['sort_order'] = 0;
        }
        
        // Create category
        $result = $this->categoryRepo->create($categoryData);
        
        if ($result) {
            $this->logInfo('Category created', [
                'id' => $result,
                'name' => $data['name'],
                'slug' => $data['slug']
            ]);
        } else {
            $this->logError('Failed to create category', $data);
        }
        
        return $result;
    }
    
    /**
     * Update category
     * 
     * @param int $id Category ID
     * @param array $data Category data
     * @return bool True if successful
     */
    public function updateCategory(int $id, array $data): bool
    {
        $this->clearErrors();
        
        // Check if category exists
        $category = $this->categoryRepo->findById($id);
        if (!$category) {
            $this->addError('id', 'Category not found');
            $this->logError('Category not found for update', ['id' => $id]);
            return false;
        }
        
        // Validate slug if provided
        if (!empty($data['slug'])) {
            if (!$this->validateSlug($data['slug'])) {
                $this->addError('slug', 'Slug must be URL-friendly (lowercase letters, numbers, and hyphens only)');
            }
            
            // Check slug uniqueness (excluding current category)
            $existingSlug = $this->categoryRepo->findBySlug($data['slug'], false);
            if ($existingSlug && $existingSlug['id'] != $id) {
                $this->addError('slug', 'Slug already exists');
            }
        }
        
        // Validate name length if provided
        if (!empty($data['name'])) {
            $this->validateLength($data['name'], 'name', 2, 150);
        }
        
        // Validate parent if provided (can't set parent to itself or descendant)
        if (!empty($data['parent_id'])) {
            if ($data['parent_id'] == $id) {
                $this->addError('parent_id', 'Category cannot be its own parent');
            } else {
                $parent = $this->categoryRepo->findById($data['parent_id']);
                if (!$parent) {
                    $this->addError('parent_id', 'Parent category does not exist');
                }
                
                // Check if parent is not a descendant (prevent circular references)
                if ($this->isDescendant($data['parent_id'], $id)) {
                    $this->addError('parent_id', 'Cannot set parent as descendant');
                }
            }
        }
        
        if ($this->fails()) {
            $this->logError('Category validation failed for update', [
                'id' => $id,
                'data' => $data,
                'errors' => $this->getErrors()
            ]);
            return false;
        }
        
        // Sanitize data
        $updateData = $this->sanitize($data, [
            'name', 'slug', 'parent_id', 'description', 'is_active', 'sort_order'
        ]);
        
        // Update category
        $result = $this->categoryRepo->update($id, $updateData);
        
        if ($result) {
            $this->logInfo('Category updated', ['id' => $id, 'data' => $updateData]);
        } else {
            $this->logError('Failed to update category', ['id' => $id, 'data' => $updateData]);
        }
        
        return $result;
    }
    
    /**
     * Delete category
     * 
     * @param int $id Category ID
     * @param bool $force Force delete even if has children (moves children to parent)
     * @return bool True if successful
     */
    public function deleteCategory(int $id, bool $force = false): bool
    {
        $this->clearErrors();
        
        // Check if category exists
        $category = $this->categoryRepo->findById($id);
        if (!$category) {
            $this->addError('id', 'Category not found');
            return false;
        }
        
        // Check if category has children
        $hasChildren = $this->categoryRepo->hasChildren($id, false);
        
        if ($hasChildren) {
            if ($force) {
                // Move children to parent category
                $parentId = $category['parent_id'];
                $this->reassignChildren($id, $parentId);
                $this->logInfo('Category children reassigned before deletion', [
                    'category_id' => $id,
                    'new_parent_id' => $parentId
                ]);
            } else {
                $this->addError('id', 'Cannot delete category with children. Use force delete to reassign children.');
                return false;
            }
        }
        
        // Delete category
        $result = $this->categoryRepo->delete($id);
        
        if ($result) {
            $this->logInfo('Category deleted', ['id' => $id, 'name' => $category['name']]);
        } else {
            $this->logError('Failed to delete category', ['id' => $id]);
        }
        
        return $result;
    }
    
    /**
     * Reassign children of a category to another parent
     * 
     * @param int $categoryId Category being deleted
     * @param int|null $newParentId New parent ID (null for root)
     */
    private function reassignChildren(int $categoryId, ?int $newParentId): void
    {
        $children = $this->categoryRepo->findChildren($categoryId, false);
        
        foreach ($children as $child) {
            $this->categoryRepo->update($child['id'], ['parent_id' => $newParentId]);
        }
    }
    
    /**
     * Check if a category is a descendant of another
     */
    private function isDescendant(int $categoryId, int $potentialAncestorId): bool
    {
        $category = $this->categoryRepo->findById($categoryId);
        
        if (!$category || !$category['parent_id']) {
            return false;
        }
        
        $currentParentId = $category['parent_id'];
        
        while ($currentParentId !== null) {
            if ($currentParentId == $potentialAncestorId) {
                return true;
            }
            
            $parent = $this->categoryRepo->findById($currentParentId);
            if (!$parent) {
                break;
            }
            
            $currentParentId = $parent['parent_id'];
        }
        
        return false;
    }
    
    /**
     * Validate slug format
     */
    private function validateSlug(string $slug): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
    }
    
    /**
     * Toggle category active status
     */
    public function toggleActive(int $id): bool
    {
        $category = $this->categoryRepo->findById($id);
        
        if (!$category) {
            $this->addError('id', 'Category not found');
            return false;
        }
        
        $newStatus = $category['is_active'] == 1 ? 0 : 1;
        $result = $this->categoryRepo->update($id, ['is_active' => $newStatus]);
        
        if ($result) {
            $this->logInfo('Category status toggled', [
                'id' => $id,
                'new_status' => $newStatus
            ]);
        }
        
        return $result;
    }
    
    /**
     * Bulk update sort orders
     */
    public function updateSortOrders(array $orders): bool
    {
        $this->clearErrors();
        
        if (empty($orders)) {
            return true;
        }
        
        // Validate all categories exist
        foreach (array_keys($orders) as $id) {
            $category = $this->categoryRepo->findById($id);
            if (!$category) {
                $this->addError('id', "Category ID {$id} not found");
                return false;
            }
        }
        
        $result = $this->categoryRepo->updateSortOrders($orders);
        
        if ($result) {
            $this->logInfo('Sort orders updated', ['count' => count($orders)]);
        } else {
            $this->logError('Failed to update sort orders', $orders);
        }
        
        return $result;
    }
    
    /**
     * Get category with breadcrumbs
     */
    public function getCategoryWithBreadcrumbs(int $categoryId, bool $onlyActive = true): ?array
    {
        $category = $this->categoryRepo->getWithAncestors($categoryId, $onlyActive);
        
        if (!$category) {
            $this->addError('id', 'Category not found');
            return null;
        }
        
        $category['breadcrumbs'] = $this->categoryRepo->getBreadcrumbs($categoryId, $onlyActive);
        
        return $category;
    }
    
    /**
     * Get category with its children tree
     */
    public function getCategoryTreeBranch(int $categoryId, bool $onlyActive = true): ?array
    {
        $category = $this->categoryRepo->getWithDescendants($categoryId, $onlyActive);
        
        if (!$category) {
            $this->addError('id', 'Category not found');
            return null;
        }
        
        return $category;
    }
    
    /**
     * Search categories
     */
    public function searchCategories(string $search, bool $onlyActive = true, int $limit = 20): array
    {
        if (strlen(trim($search)) < 2) {
            $this->addError('search', 'Search term must be at least 2 characters');
            return [];
        }
        
        return $this->categoryRepo->searchByName($search, $onlyActive, $limit);
    }
    
    /**
     * Get categories with job counts
     */
    public function getCategoriesWithJobCounts(bool $onlyActive = true): array
    {
        return $this->categoryRepo->getWithJobCounts($onlyActive);
    }
    
    /**
     * Get sibling categories
     */
    public function getSiblingCategories(int $categoryId, bool $onlyActive = true): array
    {
        $category = $this->categoryRepo->findById($categoryId);
        
        if (!$category) {
            $this->addError('id', 'Category not found');
            return [];
        }
        
        return $this->categoryRepo->getSiblings($categoryId, $onlyActive);
    }
    
    /**
     * Check if category has jobs assigned
     */
    public function hasJobs(int $categoryId): bool
    {
        $count = $this->mapRepo->countJobsInCategory($categoryId, false);
        return $count > 0;
    }
    
    /**
     * Get category statistics
     */
    public function getCategoryStats(int $categoryId): array
    {
        $category = $this->categoryRepo->findById($categoryId);
        
        if (!$category) {
            return [
                'success' => false,
                'error' => 'Category not found'
            ];
        }
        
        $stats = [
            'category' => $category,
            'job_count' => $this->mapRepo->countJobsInCategory($categoryId, false),
            'active_job_count' => $this->mapRepo->countJobsInCategory($categoryId, true),
            'child_count' => count($this->categoryRepo->findChildren($categoryId, false)),
            'active_child_count' => count($this->categoryRepo->findChildren($categoryId, true)),
            'has_children' => $this->categoryRepo->hasChildren($categoryId, false),
            'has_active_children' => $this->categoryRepo->hasChildren($categoryId, true)
        ];
        
        $stats['success'] = true;
        
        return $stats;
    }
    
    /**
     * Bulk create categories
     */
    public function bulkCreateCategories(array $categories): array
    {
        $results = [
            'success' => [],
            'failed' => []
        ];
        
        foreach ($categories as $category) {
            $id = $this->createCategory($category);
            
            if ($id) {
                $results['success'][] = ['id' => $id, 'data' => $category];
            } else {
                $results['failed'][] = [
                    'data' => $category,
                    'errors' => $this->getErrors()
                ];
                $this->clearErrors();
            }
        }
        
        $this->logInfo('Bulk category creation completed', [
            'success_count' => count($results['success']),
            'failed_count' => count($results['failed'])
        ]);
        
        return $results;
    }
    
    /**
     * Export categories to array (for API or reports)
     */
    public function exportCategories(bool $onlyActive = true, bool $includeStats = false): array
    {
        $categories = $this->getAllCategories($onlyActive);
        
        if ($includeStats) {
            foreach ($categories as &$category) {
                $category['job_count'] = $this->mapRepo->countJobsInCategory($category['id'], true);
            }
        }
        
        return [
            'total' => count($categories),
            'categories' => $categories
        ];
    }

    /**
     * Add a job category mapping
     */
    public function addJobCategory(int $jobId, int $categoryId): bool
    {
        return $this->mapRepo->assignCategory($jobId, $categoryId);
    }

    /**
     * Remove all job category mappings for a job
     */
    public function removeAllJobCategories(int $jobId): bool
    {
        return $this->mapRepo->removeAllCategories($jobId);
    }
}
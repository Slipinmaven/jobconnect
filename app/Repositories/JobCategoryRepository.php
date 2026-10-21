<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;

/**
 * JobCategoryRepository - Handles all database operations for job_categories table
 * 
 * PURPOSE: Manages all database queries related to job categories
 * 
 * FEATURES:
 * - Hierarchical category management (parent-child relationships)
 * - Active/inactive category filtering
 * - Sorting functionality
 * - Slug-based lookups for SEO-friendly URLs
 * - Category tree building
 */
class JobCategoryRepository extends BaseRepository
{
    protected string $table = 'job_categories';
    protected string $primaryKey = 'id';
    
    // Additional fields for common queries
    private const ACTIVE = 1;
    private const INACTIVE = 0;
    
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }
    
    // ==================== CUSTOM QUERIES ====================
    
    /**
     * Find category by slug (for SEO-friendly URLs)
     * 
     * @param string $slug Category slug
     * @param bool $onlyActive Only return active categories
     * @return array|null Category data or null
     */
    public function findBySlug(string $slug, bool $onlyActive = true): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug = :slug";
        
        if ($onlyActive) {
            $sql .= " AND is_active = :is_active";
            return $this->fetchOne($sql, [
                ':slug' => $slug,
                ':is_active' => self::ACTIVE
            ]);
        }
        
        return $this->fetchOne($sql, [':slug' => $slug]);
    }
    
    /**
     * Find all active categories
     * 
     * @param string $orderBy Sort order
     * @return array List of active categories
     */
    public function findActive(string $orderBy = 'sort_order ASC, name ASC'): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = :is_active 
                ORDER BY {$orderBy}";
        
        return $this->fetchAll($sql, [':is_active' => self::ACTIVE]);
    }
    
    /**
     * Find root categories (categories without parent)
     * 
     * @param bool $onlyActive Only return active categories
     * @param string $orderBy Sort order
     * @return array Root categories
     */
    public function findRootCategories(bool $onlyActive = true, string $orderBy = 'sort_order ASC, name ASC'): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE parent_id IS NULL";
        
        if ($onlyActive) {
            $sql .= " AND is_active = :is_active";
            $params = [':is_active' => self::ACTIVE];
        } else {
            $params = [];
        }
        
        $sql .= " ORDER BY {$orderBy}";
        
        return $this->fetchAll($sql, $params);
    }
    
    /**
     * Find child categories for a given parent
     * 
     * @param int $parentId Parent category ID
     * @param bool $onlyActive Only return active categories
     * @param string $orderBy Sort order
     * @return array Child categories
     */
    public function findChildren(int $parentId, bool $onlyActive = true, string $orderBy = 'sort_order ASC, name ASC'): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE parent_id = :parent_id";
        
        if ($onlyActive) {
            $sql .= " AND is_active = :is_active";
            $params = [
                ':parent_id' => $parentId,
                ':is_active' => self::ACTIVE
            ];
        } else {
            $params = [':parent_id' => $parentId];
        }
        
        $sql .= " ORDER BY {$orderBy}";
        
        return $this->fetchAll($sql, $params);
    }
    
    /**
     * Get category with its ancestors (parent chain)
     * 
     * @param int $categoryId Category ID
     * @param bool $onlyActive Only include active categories
     * @return array Category with ancestors
     */
    public function getWithAncestors(int $categoryId, bool $onlyActive = true): ?array
    {
        $category = $this->findById($categoryId);
        
        if (!$category) {
            return null;
        }
        
        // Check if we need to filter by active status
        if ($onlyActive && $category['is_active'] != self::ACTIVE) {
            return null;
        }
        
        $ancestors = [];
        $currentId = $category['parent_id'];
        
        // Build ancestor chain
        while ($currentId !== null) {
            $ancestor = $this->findById($currentId);
            if (!$ancestor) {
                break;
            }
            
            // Stop if we hit an inactive ancestor when onlyActive is true
            if ($onlyActive && $ancestor['is_active'] != self::ACTIVE) {
                return null;
            }
            
            $ancestors[] = $ancestor;
            $currentId = $ancestor['parent_id'];
        }
        
        $category['ancestors'] = array_reverse($ancestors); // Reverse to get from root to immediate parent
        
        return $category;
    }
    
    /**
     * Get category with its descendants (complete tree branch)
     * 
     * @param int $categoryId Category ID
     * @param bool $onlyActive Only include active categories
     * @return array Category with descendants
     */
    public function getWithDescendants(int $categoryId, bool $onlyActive = true): ?array
    {
        $category = $this->findById($categoryId);
        
        if (!$category) {
            return null;
        }
        
        if ($onlyActive && $category['is_active'] != self::ACTIVE) {
            return null;
        }
        
        $category['children'] = $this->getDescendantsRecursive($categoryId, $onlyActive);
        
        return $category;
    }
    
    /**
     * Recursive function to get all descendants
     * 
     * @param int $parentId Parent category ID
     * @param bool $onlyActive Only include active categories
     * @return array Nested tree structure
     */
    private function getDescendantsRecursive(int $parentId, bool $onlyActive): array
    {
        $children = $this->findChildren($parentId, $onlyActive);
        
        foreach ($children as &$child) {
            $child['children'] = $this->getDescendantsRecursive($child['id'], $onlyActive);
        }
        
        return $children;
    }
    
    /**
     * Build complete category tree
     * 
     * @param bool $onlyActive Only include active categories
     * @return array Complete hierarchical tree
     */
    public function getTree(bool $onlyActive = true): array
    {
        $roots = $this->findRootCategories($onlyActive);
        
        foreach ($roots as &$root) {
            $root['children'] = $this->getDescendantsRecursive($root['id'], $onlyActive);
        }
        
        return $roots;
    }
    
    /**
     * Find categories by name (partial match)
     * 
     * @param string $search Search term
     * @param bool $onlyActive Only return active categories
     * @param int $limit Maximum results
     * @return array Matching categories
     */
    public function searchByName(string $search, bool $onlyActive = true, int $limit = 20): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE name LIKE :search";
        
        if ($onlyActive) {
            $sql .= " AND is_active = :is_active";
        }
        
        $sql .= " ORDER BY name ASC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);
        
        $params = [':search' => "%{$search}%"];
        
        if ($onlyActive) {
            $params[':is_active'] = self::ACTIVE;
        }
        
        return $this->fetchAll($sql, $params);
    }
    
    /**
     * Get categories with job counts
     * 
     * @param bool $onlyActive Only count active categories
     * @return array Categories with job_count field
     */
    public function getWithJobCounts(bool $onlyActive = true): array
    {
        $sql = "SELECT c.*, COUNT(j.id) as job_count 
                FROM {$this->table} c
                LEFT JOIN jobs j ON j.category_id = c.id AND j.is_active = 1
                WHERE 1=1";
        
        if ($onlyActive) {
            $sql .= " AND c.is_active = :is_active";
            $params = [':is_active' => self::ACTIVE];
        } else {
            $params = [];
        }
        
        $sql .= " GROUP BY c.id 
                  ORDER BY c.sort_order ASC, c.name ASC";
        
        return $this->fetchAll($sql, $params);
    }
    
    /**
     * Update sort order for multiple categories
     * 
     * @param array $orders Array of [id => sort_order] pairs
     * @return bool True if all updates succeeded
     */
    public function updateSortOrders(array $orders): bool
    {
        $this->beginTransaction();
        
        try {
            foreach ($orders as $id => $sortOrder) {
                $sql = "UPDATE {$this->table} SET sort_order = :sort_order WHERE id = :id";
                $success = $this->execute($sql, [
                    ':sort_order' => $sortOrder,
                    ':id' => $id
                ]);
                
                if (!$success) {
                    $this->rollback();
                    return false;
                }
            }
            
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            error_log("Failed to update sort orders: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Bulk activate/deactivate categories
     * 
     * @param array $ids Category IDs to update
     * @param bool $active New active status
     * @return int Number of affected rows
     */
    public function bulkSetActive(array $ids, bool $active): int
    {
        if (empty($ids)) {
            return 0;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $isActiveValue = $active ? self::ACTIVE : self::INACTIVE;
        
        $sql = "UPDATE {$this->table} 
                SET is_active = ? 
                WHERE id IN ({$placeholders})";
        
        $params = array_merge([$isActiveValue], $ids);
        
        $this->execute($sql, $params);
        return $this->rowCount();
    }
    
    /**
     * Check if category has children
     * 
     * @param int $categoryId Category ID
     * @param bool $onlyActive Only check active children
     * @return bool True if has children
     */
    public function hasChildren(int $categoryId, bool $onlyActive = true): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE parent_id = :parent_id";
        
        if ($onlyActive) {
            $sql .= " AND is_active = :is_active";
            $params = [
                ':parent_id' => $categoryId,
                ':is_active' => self::ACTIVE
            ];
        } else {
            $params = [':parent_id' => $categoryId];
        }
        
        $result = $this->fetchOne($sql, $params);
        return ($result['count'] ?? 0) > 0;
    }
    
    /**
     * Get sibling categories
     * 
     * @param int $categoryId Category ID
     * @param bool $onlyActive Only return active categories
     * @return array Sibling categories
     */
    public function getSiblings(int $categoryId, bool $onlyActive = true): array
    {
        $category = $this->findById($categoryId);
        
        if (!$category) {
            return [];
        }
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE parent_id IS NOT NULL";
        
        if ($category['parent_id'] === null) {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE parent_id IS NULL";
        } else {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE parent_id = :parent_id";
        }
        
        $sql .= " AND id != :current_id";
        
        if ($onlyActive) {
            $sql .= " AND is_active = :is_active";
        }
        
        $sql .= " ORDER BY sort_order ASC, name ASC";
        
        $params = [':current_id' => $categoryId];
        
        if ($category['parent_id'] !== null) {
            $params[':parent_id'] = $category['parent_id'];
        }
        
        if ($onlyActive) {
            $params[':is_active'] = self::ACTIVE;
        }
        
        return $this->fetchAll($sql, $params);
    }
    
    /**
     * Override delete to check for children
     * 
     * @param int $id Category ID
     * @return bool True if deleted successfully
     * @throws \RuntimeException If category has children
     */
    public function delete(int $id): bool
    {
        // Check if category has children
        if ($this->hasChildren($id, false)) {
            throw new \RuntimeException("Cannot delete category with children. Delete or reassign children first.");
        }
        
        return parent::delete($id);
    }
    
    /**
     * Get category path as breadcrumb array
     * 
     * @param int $categoryId Category ID
     * @param bool $onlyActive Only include active categories
     * @return array Breadcrumb array from root to current
     */
    public function getBreadcrumbs(int $categoryId, bool $onlyActive = true): array
    {
        $category = $this->getWithAncestors($categoryId, $onlyActive);
        
        if (!$category) {
            return [];
        }
        
        $breadcrumbs = $category['ancestors'] ?? [];
        $breadcrumbs[] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'slug' => $category['slug']
        ];
        
        return $breadcrumbs;
    }
}
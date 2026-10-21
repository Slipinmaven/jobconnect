<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;

/**
 * SkillCategoryRepository - Handles all database queries for skill categories
 * 
 * PURPOSE: Manages skill categories for organizing skills in job seeker profiles
 * 
 * TABLE STRUCTURE:
 * - id: int(11) primary key auto_increment
 * - name: varchar(100) category name
 * - slug: varchar(120) unique URL-friendly identifier
 * - sort_order: int(11) display order (default 0)
 * - created_at: timestamp
 * 
 * FUNCTIONALITY:
 * - Fetch all categories ordered by sort_order
 * - Search categories by name
 * - Find by slug for URL lookups
 * - Get categories with their associated skills
 */
class SkillCategoryRepository extends BaseRepository
{
    protected string $table = 'skill_categories';
    protected string $primaryKey = 'id';

    /**
     * Find category by slug
     * 
     * Used for URL lookups like /skills/category/web-development
     * 
     * @param string $slug URL-friendly slug
     * @return array|null Category data or null if not found
     */
    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug = :slug LIMIT 1";
        return $this->fetchOne($sql, [':slug' => $slug]);
    }

    /**
     * Get all categories ordered by sort_order
     * 
     * Used for dropdowns, filters, and navigation in the job seeker wizard
     * 
     * @param bool $withIdAsKey If true, returns array with id as key
     * @return array All categories
     */
    public function getAllOrdered(bool $withIdAsKey = false): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY sort_order ASC, name ASC";
        $categories = $this->fetchAll($sql);
        
        if ($withIdAsKey) {
            $formatted = [];
            foreach ($categories as $category) {
                $formatted[$category['id']] = $category['name'];
            }
            return $formatted;
        }
        
        return $categories;
    }

    /**
     * Search categories by name
     * 
     * Used for live search in job seeker profile wizard
     * 
     * @param string $searchTerm Search query
     * @param int $limit Maximum results to return
     * @return array Matching categories
     */
    public function searchByName(string $searchTerm, int $limit = 50): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE name LIKE :search 
                ORDER BY sort_order ASC, name ASC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);
        
        return $this->fetchAll($sql, [
            ':search' => '%' . $searchTerm . '%'
        ]);
    }

    /**
     * Get categories with their skills count
     * 
     * Used to show how many skills are in each category
     * 
     * @return array Categories with skill counts
     */
    public function getCategoriesWithSkillCount(): array
    {
        $sql = "SELECT c.*, COUNT(s.id) as skills_count 
                FROM {$this->table} c
                LEFT JOIN skills s ON c.id = s.category_id
                GROUP BY c.id
                ORDER BY c.sort_order ASC, c.name ASC";
        
        return $this->fetchAll($sql);
    }

    /**
     * Get category with its skills
     * 
     * Used when displaying a specific category page
     * 
     * @param int $categoryId Category ID
     * @return array|null Category with skills array or null if not found
     */
    public function getCategoryWithSkills(int $categoryId): ?array
    {
        // First get the category
        $category = $this->findById($categoryId);
        
        if (!$category) {
            return null;
        }
        
        // Then get skills in this category
        $sql = "SELECT id, name, slug FROM skills 
                WHERE category_id = :category_id
                ORDER BY name ASC";
        
        $skills = $this->fetchAll($sql, [':category_id' => $categoryId]);
        
        $category['skills'] = $skills;
        return $category;
    }

    /**
     * Get multiple categories with their skills
     * 
     * Used for batch loading in the job seeker wizard
     * 
     * @param array $categoryIds Array of category IDs
     * @return array Categories with their skills
     */
    public function getCategoriesWithSkills(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        
        // Get categories
        $sql = "SELECT * FROM {$this->table} 
                WHERE id IN ({$placeholders}) 
                ORDER BY sort_order ASC, name ASC";
        $categories = $this->fetchAll($sql, $categoryIds);
        
        // Get all skills for these categories
        $sql = "SELECT s.*, c.name as category_name 
                FROM skills s
                JOIN {$this->table} c ON s.category_id = c.id
                WHERE s.category_id IN ({$placeholders})
                ORDER BY s.name ASC";
        $skills = $this->fetchAll($sql, $categoryIds);
        
        // Group skills by category
        $skillsByCategory = [];
        foreach ($skills as $skill) {
            $skillsByCategory[$skill['category_id']][] = $skill;
        }
        
        // Attach skills to categories
        foreach ($categories as &$category) {
            $category['skills'] = $skillsByCategory[$category['id']] ?? [];
        }
        
        return $categories;
    }

    /**
     * Get popular categories (most used skills)
     * 
     * @param int $limit Number of categories to return
     * @return array Popular categories with usage count
     */
    public function getPopularCategories(int $limit = 10): array
    {
        $sql = "SELECT c.*, COUNT(js.skill_id) as usage_count 
                FROM {$this->table} c
                LEFT JOIN skills s ON c.id = s.category_id
                LEFT JOIN jobseeker_skills js ON s.id = js.skill_id
                GROUP BY c.id
                HAVING usage_count > 0
                ORDER BY usage_count DESC, c.sort_order ASC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql);
    }

    /**
     * Create a new category
     * 
     * @param array $data Category data (name, slug, sort_order optional)
     * @return int|null New category ID or null on failure
     */
    public function createCategory(array $data): ?int
    {
        // Set default sort_order if not provided
        if (!isset($data['sort_order'])) {
            // Get max sort_order and add 10
            $sql = "SELECT MAX(sort_order) as max_sort FROM {$this->table}";
            $result = $this->fetchOne($sql);
            $data['sort_order'] = ($result['max_sort'] ?? 0) + 10;
        }
        
        return $this->create($data);
    }

    /**
     * Update category sort order
     * 
     * @param int $id Category ID
     * @param int $sortOrder New sort order
     * @return bool Success
     */
    public function updateSortOrder(int $id, int $sortOrder): bool
    {
        return $this->update($id, ['sort_order' => $sortOrder]);
    }

    /**
     * Reorder categories (batch update)
     * 
     * @param array $order Array of [id => sort_order] pairs
     * @return bool Success
     */
    public function reorderCategories(array $order): bool
    {
        try {
            $this->beginTransaction();
            
            foreach ($order as $id => $sortOrder) {
                $this->update($id, ['sort_order' => $sortOrder]);
            }
            
            $this->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->rollback();
            error_log("Failed to reorder categories: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get categories grouped by first letter
     * 
     * Useful for directory-style listings
     * 
     * @return array Categories grouped A-Z
     */
    public function getGroupedByLetter(): array
    {
        $categories = $this->getAllOrdered();
        $grouped = [];
        
        foreach ($categories as $category) {
            $firstLetter = strtoupper(substr($category['name'], 0, 1));
            
            if (!isset($grouped[$firstLetter])) {
                $grouped[$firstLetter] = [];
            }
            
            $grouped[$firstLetter][] = $category;
        }
        
        return $grouped;
    }

    /**
     * Find or create category by name
     * 
     * Useful when importing data or accepting user input
     * 
     * @param string $name Category name
     * @return array Category data (existing or new)
     */
    public function findOrCreateByName(string $name): array
    {
        // Generate slug from name
        $slug = $this->generateSlug($name);
        
        // Try to find existing
        $existing = $this->findWhere(['slug' => $slug]);
        
        if (!empty($existing)) {
            return $existing[0];
        }
        
        // Create new
        $id = $this->createCategory([
            'name' => $name,
            'slug' => $slug
        ]);
        
        return $this->findById($id);
    }

    /**
     * Generate URL-friendly slug from name
     * 
     * @param string $name Category name
     * @return string URL-friendly slug
     */
    private function generateSlug(string $name): string
    {
        // Convert to lowercase
        $slug = strtolower($name);
        
        // Replace spaces and special chars with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');
        
        // Check if slug exists and make it unique if needed
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->exists(['slug' => $slug])) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Get category statistics
     * 
     * @return array Stats about categories
     */
    public function getStatistics(): array
    {
        $stats = [];
        
        // Total categories
        $stats['total_categories'] = $this->count();
        
        // Categories with most skills
        $sql = "SELECT c.name, COUNT(s.id) as skill_count 
                FROM {$this->table} c
                LEFT JOIN skills s ON c.id = s.category_id
                GROUP BY c.id
                HAVING skill_count > 0
                ORDER BY skill_count DESC
                LIMIT 5";
        $stats['top_categories'] = $this->fetchAll($sql);
        
        // Recently added categories
        $sql = "SELECT * FROM {$this->table} 
                ORDER BY created_at DESC 
                LIMIT 5";
        $stats['recent'] = $this->fetchAll($sql);
        
        return $stats;
    }

    /**
     * Get categories that have no skills
     * 
     * @return array Empty categories
     */
    public function getEmptyCategories(): array
    {
        $sql = "SELECT c.* FROM {$this->table} c
                LEFT JOIN skills s ON c.id = s.category_id
                WHERE s.id IS NULL
                ORDER BY c.sort_order ASC, c.name ASC";
        
        return $this->fetchAll($sql);
    }

    /**
     * Bulk update categories
     * 
     * @param array $categories Array of category data with ids
     * @return int Number of updated categories
     */
    public function bulkUpdate(array $categories): int
    {
        $updated = 0;
        
        try {
            $this->beginTransaction();
            
            foreach ($categories as $category) {
                if (!isset($category['id'])) {
                    continue;
                }
                
                $data = array_intersect_key($category, array_flip(['name', 'slug', 'sort_order']));
                if (!empty($data) && $this->update($category['id'], $data)) {
                    $updated++;
                }
            }
            
            $this->commit();
            
        } catch (\Exception $e) {
            $this->rollback();
            error_log("Failed to bulk update categories: " . $e->getMessage());
        }
        
        return $updated;
    }

    /**
     * Delete category and optionally reassign its skills
     * 
     * @param int $id Category ID to delete
     * @param int|null $reassignTo Category ID to reassign skills to (null = delete skills)
     * @return bool Success
     */
    public function deleteCategory(int $id, ?int $reassignTo = null): bool
    {
        try {
            $this->beginTransaction();
            
            if ($reassignTo) {
                // Reassign skills to another category
                $sql = "UPDATE skills SET category_id = :new_category WHERE category_id = :old_category";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':new_category' => $reassignTo, ':old_category' => $id]);
            } else {
                // Delete skills in this category (or set category_id to NULL)
                $sql = "UPDATE skills SET category_id = NULL WHERE category_id = :category_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':category_id' => $id]);
            }
            
            // Delete the category
            $success = $this->delete($id);
            
            $this->commit();
            return $success;
            
        } catch (\Exception $e) {
            $this->rollback();
            error_log("Failed to delete category: " . $e->getMessage());
            return false;
        }
    }
}
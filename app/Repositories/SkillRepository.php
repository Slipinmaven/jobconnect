<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;
use PDOException;
use Exception;

/**
 * SkillRepository - Manages skills and skill categories
 * 
 * Handles:
 * - Skills CRUD
 * - Skill categories CRUD
 * - Skills by category
 * - Skill search
 * - Popular skills
 */
class SkillRepository extends BaseRepository
{
    protected string $table = 'skills';
    protected string $primaryKey = 'id';

    /**
     * Find skill by ID with category
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT DISTINCT s.*, sc.name as category_name, sc.icon as category_icon, sc.slug as category_slug
                FROM {$this->table} s
                LEFT JOIN skill_categories sc ON s.category_id = sc.id
                WHERE s.id = :id
                GROUP BY s.id";
        
        return $this->fetchOne($sql, [':id' => $id]);
    }

    /**
     * Find skill by name (case insensitive)
     *
     * @param string $name
     * @return array|null
     */
    public function findByName(string $name): ?array
    {
        $sql = "SELECT DISTINCT s.*, sc.name as category_name, sc.icon as category_icon
                FROM {$this->table} s
                LEFT JOIN skill_categories sc ON s.category_id = sc.id
                WHERE LOWER(s.name) = LOWER(:name)
                GROUP BY s.id";
        
        return $this->fetchOne($sql, [':name' => $name]);
    }

    /**
     * Get all skills with category details
     *
     * @param string $orderBy
     * @return array
     */
    public function getAllWithCategories(string $orderBy = 'sc.sort_order, s.name'): array
    {
        $sql = "SELECT DISTINCT s.*, sc.name as category_name, sc.icon as category_icon, sc.slug as category_slug
                FROM {$this->table} s
                LEFT JOIN skill_categories sc ON s.category_id = sc.id
                GROUP BY s.id
                ORDER BY {$orderBy}";
        
        return $this->fetchAll($sql);
    }

    /**
     * Get skills by category ID
     *
     * @param int $categoryId
     * @return array
     */
    public function getByCategory(int $categoryId): array
    {
        $sql = "SELECT DISTINCT s.*, sc.name as category_name, sc.icon as category_icon
                FROM {$this->table} s
                LEFT JOIN skill_categories sc ON s.category_id = sc.id
                WHERE s.category_id = :category_id
                GROUP BY s.id
                ORDER BY s.name";
        
        return $this->fetchAll($sql, [':category_id' => $categoryId]);
    }

    /**
     * Search skills by name
     *
     * @param string $query
     * @param int|null $categoryId
     * @param int $limit
     * @return array
     */
    public function search(string $query, ?int $categoryId = null, int $limit = 20): array
    {
        $sql = "SELECT DISTINCT s.*, sc.name as category_name, sc.icon as category_icon
                FROM {$this->table} s
                LEFT JOIN skill_categories sc ON s.category_id = sc.id
                WHERE s.name LIKE :query";
        
        $params = [':query' => "%{$query}%"];

        if ($categoryId) {
            $sql .= " AND s.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }

        $sql .= " GROUP BY s.id
                  ORDER BY 
                    CASE 
                        WHEN s.name LIKE :exact_match THEN 0 
                        ELSE 1 
                    END,
                    s.name";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        $params[':exact_match'] = $query;

        return $this->fetchAll($sql, $params);
    }

    /**
     * Get popular skills (most used by jobseekers)
     *
     * @param int $limit
     * @return array
     */
    public function getPopular(int $limit = 10): array
    {
        $sql = "SELECT s.*, sc.name as category_name, sc.icon as category_icon,
                       COUNT(DISTINCT js.jobseeker_id) as usage_count
                FROM {$this->table} s
                LEFT JOIN skill_categories sc ON s.category_id = sc.id
                LEFT JOIN jobseeker_skills js ON s.id = js.skill_id
                GROUP BY s.id
                ORDER BY usage_count DESC, s.name";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql);
    }

    /**
     * Get skills by proficiency level for a jobseeker
     *
     * @param int $jobseekerId
     * @param string $proficiency
     * @return array
     */
    public function getByProficiency(int $jobseekerId, string $proficiency): array
    {
        $sql = "SELECT DISTINCT s.*, js.proficiency, js.years_experience,
                       sc.name as category_name, sc.icon as category_icon
                FROM {$this->table} s
                INNER JOIN jobseeker_skills js ON s.id = js.skill_id
                LEFT JOIN skill_categories sc ON s.category_id = sc.id
                WHERE js.jobseeker_id = :jobseeker_id
                AND js.proficiency = :proficiency
                GROUP BY s.id
                ORDER BY s.name";
        
        return $this->fetchAll($sql, [
            ':jobseeker_id' => $jobseekerId,
            ':proficiency' => $proficiency
        ]);
    }

    /**
     * Create a new skill
     *
     * @param array $data
     * @return int|null
     */
    public function createSkill(array $data): ?int
    {
        try {
            // Check if skill already exists
            $existing = $this->findByName($data['name']);
            if ($existing) {
                return null; // Skill already exists
            }

            return $this->create([
                'name' => $data['name'],
                'slug' => $this->generateSlug($data['name']),
                'category_id' => $data['category_id'] ?? null,
                'description' => $data['description'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Failed to create skill: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update skill
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateSkill(int $id, array $data): bool
    {
        $allowedFields = ['name', 'slug', 'category_id', 'description'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        // Regenerate slug if name changes
        if (isset($updateData['name']) && empty($updateData['slug'])) {
            $updateData['slug'] = $this->generateSlug($updateData['name']);
        }
        
        if (empty($updateData)) {
            return false;
        }

        return $this->update($id, $updateData);
    }

    /**
     * Delete skill (will cascade to jobseeker_skills)
     *
     * @param int $id
     * @return bool
     */
    public function deleteSkill(int $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Get skills count by category
     *
     * @return array
     */
    public function getCountByCategory(): array
    {
        $sql = "SELECT sc.id, sc.name as category_name, sc.icon, 
                       COUNT(DISTINCT s.id) as skill_count
                FROM skill_categories sc
                LEFT JOIN {$this->table} s ON sc.id = s.category_id
                GROUP BY sc.id
                ORDER BY sc.sort_order";
        
        return $this->fetchAll($sql);
    }

    /**
     * Get recently added skills
     *
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 10): array
    {
        $sql = "SELECT DISTINCT s.*, sc.name as category_name, sc.icon as category_icon
                FROM {$this->table} s
                LEFT JOIN skill_categories sc ON s.category_id = sc.id
                GROUP BY s.id
                ORDER BY s.created_at DESC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql);
    }

    /**
     * Bulk import skills
     *
     * @param array $skills Array of skill names with optional category
     * @return array Results [created, skipped, errors]
     */
    public function bulkImport(array $skills): array
    {
        $results = [
            'created' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        try {
            $this->beginTransaction();

            foreach ($skills as $skill) {
                try {
                    $name = is_array($skill) ? $skill['name'] : $skill;
                    $categoryId = is_array($skill) ? ($skill['category_id'] ?? null) : null;

                    // Check if exists
                    $existing = $this->findByName($name);
                    if ($existing) {
                        $results['skipped']++;
                        continue;
                    }

                    // Create skill
                    $created = $this->create([
                        'name' => $name,
                        'slug' => $this->generateSlug($name),
                        'category_id' => $categoryId
                    ]);

                    if ($created) {
                        $results['created']++;
                    } else {
                        $results['errors'][] = "Failed to create skill: {$name}";
                    }

                } catch (Exception $e) {
                    $results['errors'][] = "Error with skill {$name}: " . $e->getMessage();
                }
            }

            $this->commit();
            return $results;

        } catch (Exception $e) {
            $this->rollback();
            error_log("Bulk import failed: " . $e->getMessage());
            $results['errors'][] = "Bulk import failed: " . $e->getMessage();
            return $results;
        }
    }

    // ==================== SKILL CATEGORIES ====================

    /**
     * Get all skill categories
     *
     * @return array
     */
    public function getAllCategories(): array
    {
        $sql = "SELECT * FROM skill_categories ORDER BY sort_order, name";
        return $this->fetchAll($sql);
    }

    /**
     * Get category by ID
     *
     * @param int $id
     * @return array|null
     */
    public function getCategoryById(int $id): ?array
    {
        $sql = "SELECT * FROM skill_categories WHERE id = :id";
        return $this->fetchOne($sql, [':id' => $id]);
    }

    /**
     * Get category by slug
     *
     * @param string $slug
     * @return array|null
     */
    public function getCategoryBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM skill_categories WHERE slug = :slug";
        return $this->fetchOne($sql, [':slug' => $slug]);
    }

    /**
     * Create skill category
     *
     * @param array $data
     * @return int|null
     */
    public function createCategory(array $data): ?int
    {
        try {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['name']);
            }

            // Check if slug already exists
            $existing = $this->getCategoryBySlug($data['slug']);
            if ($existing) {
                return null;
            }

            // Insert directly into skill_categories table
            $sql = "INSERT INTO skill_categories (name, slug, icon, sort_order, created_at) 
                    VALUES (:name, :slug, :icon, :sort_order, NOW())";
            
            $success = $this->execute($sql, [
                ':name' => $data['name'],
                ':slug' => $data['slug'],
                ':icon' => $data['icon'] ?? null,
                ':sort_order' => $data['sort_order'] ?? 0
            ]);

            if ($success) {
                return (int)$this->pdo->lastInsertId();
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Failed to create skill category: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update skill category
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateCategory(int $id, array $data): bool
    {
        try {
            $allowedFields = ['name', 'slug', 'icon', 'sort_order'];
            $sets = [];
            $params = [':id' => $id];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $sets[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $data[$field];
                }
            }

            // Regenerate slug if name changes and no slug provided
            if (isset($data['name']) && empty($data['slug'])) {
                $sets[] = "slug = :slug";
                $params[':slug'] = $this->generateSlug($data['name']);
            }

            if (empty($sets)) {
                return false;
            }

            $sql = "UPDATE skill_categories SET " . implode(', ', $sets) . " WHERE id = :id";
            
            return $this->execute($sql, $params);
        } catch (PDOException $e) {
            error_log("Failed to update skill category: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete skill category (will set category_id to NULL in skills)
     *
     * @param int $id
     * @return bool
     */
    public function deleteCategory(int $id): bool
    {
        try {
            $this->beginTransaction();

            // Set category_id to NULL for skills in this category
            $this->execute(
                "UPDATE {$this->table} SET category_id = NULL WHERE category_id = :category_id",
                [':category_id' => $id]
            );

            // Delete category
            $result = $this->execute(
                "DELETE FROM skill_categories WHERE id = :id",
                [':id' => $id]
            );

            $this->commit();
            return $result;

        } catch (Exception $e) {
            $this->rollback();
            error_log("Failed to delete category: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get categories with skill counts
     *
     * @return array
     */
    public function getCategoriesWithCounts(): array
    {
        $sql = "SELECT sc.*, COUNT(DISTINCT s.id) as skills_count
                FROM skill_categories sc
                LEFT JOIN {$this->table} s ON sc.id = s.category_id
                GROUP BY sc.id
                ORDER BY sc.sort_order, sc.name";
        
        return $this->fetchAll($sql);
    }

    /**
     * Reorder categories
     *
     * @param array $order Array of category IDs in desired order
     * @return bool
     */
    public function reorderCategories(array $order): bool
    {
        try {
            $this->beginTransaction();

            foreach ($order as $index => $categoryId) {
                $this->execute(
                    "UPDATE skill_categories SET sort_order = :sort_order WHERE id = :id",
                    [':sort_order' => ($index + 1) * 10, ':id' => $categoryId]
                );
            }

            $this->commit();
            return true;

        } catch (Exception $e) {
            $this->rollback();
            error_log("Failed to reorder categories: " . $e->getMessage());
            return false;
        }
    }

    // ==================== SUGGESTIONS ====================

    /**
     * Get skill suggestions based on job title
     *
     * @param string $jobTitle
     * @param int $limit
     * @return array
     */
    public function suggestForJobTitle(string $jobTitle, int $limit = 10): array
    {
        $keywords = explode(' ', strtolower($jobTitle));
        
        $sql = "SELECT DISTINCT s.*, sc.name as category_name, sc.icon as category_icon,
                       COUNT(DISTINCT js.jobseeker_id) as popularity
                FROM {$this->table} s
                LEFT JOIN skill_categories sc ON s.category_id = sc.id
                LEFT JOIN jobseeker_skills js ON s.id = js.skill_id
                WHERE ";
        
        $conditions = [];
        $params = [];
        
        foreach ($keywords as $index => $keyword) {
            if (strlen($keyword) < 3) continue;
            $conditions[] = "LOWER(s.name) LIKE :keyword{$index}";
            $params[":keyword{$index}"] = "%{$keyword}%";
        }
        
        if (empty($conditions)) {
            // Fallback to popular skills
            return $this->getPopular($limit);
        }
        
        $sql .= implode(' OR ', $conditions);
        $sql .= " GROUP BY s.id ORDER BY popularity DESC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql, $params);
    }

    /**
     * Get related skills (skills often chosen together)
     *
     * @param int $skillId
     * @param int $limit
     * @return array
     */
    public function getRelatedSkills(int $skillId, int $limit = 5): array
    {
        $sql = "SELECT s2.*, sc.name as category_name, sc.icon as category_icon,
                       COUNT(DISTINCT js1.jobseeker_id) as co_occurrence
                FROM jobseeker_skills js1
                INNER JOIN jobseeker_skills js2 ON js1.jobseeker_id = js2.jobseeker_id
                INNER JOIN {$this->table} s2 ON js2.skill_id = s2.id
                LEFT JOIN skill_categories sc ON s2.category_id = sc.id
                WHERE js1.skill_id = :skill_id
                AND js2.skill_id != :skill_id
                GROUP BY s2.id
                ORDER BY co_occurrence DESC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql, [
            ':skill_id' => $skillId
        ]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Generate URL slug from name
     *
     * @param string $name
     * @return string
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Get or create skill by name
     *
     * @param string $name
     * @param int|null $categoryId
     * @return int|null Skill ID
     */
    public function getOrCreate(string $name, ?int $categoryId = null): ?int
    {
        $existing = $this->findByName($name);
        if ($existing) {
            return $existing['id'];
        }

        return $this->createSkill([
            'name' => $name,
            'category_id' => $categoryId
        ]);
    }

    /**
     * Get skills with no category (orphaned)
     *
     * @return array
     */
    public function getOrphanedSkills(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE category_id IS NULL ORDER BY name";
        return $this->fetchAll($sql);
    }

    /**
     * Assign category to multiple skills
     *
     * @param array $skillIds
     * @param int $categoryId
     * @return int Number of skills updated
     */
    public function assignCategory(array $skillIds, int $categoryId): int
    {
        if (empty($skillIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
        $params = array_merge([$categoryId], $skillIds);

        $sql = "UPDATE {$this->table} SET category_id = ? WHERE id IN ({$placeholders})";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }

    // ==================== STATISTICS ====================

    /**
     * Get skill statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $stats = [];

        // Total skills
        $result = $this->fetchOne("SELECT COUNT(DISTINCT id) as total FROM {$this->table}");
        $stats['total_skills'] = $result['total'] ?? 0;

        // Total categories
        $result = $this->fetchOne("SELECT COUNT(DISTINCT id) as total FROM skill_categories");
        $stats['total_categories'] = $result['total'] ?? 0;

        // Most popular skills
        $stats['most_popular'] = $this->getPopular(5);

        // Skills per category
        $stats['skills_per_category'] = $this->getCountByCategory();

        // Recently added
        $stats['recently_added'] = $this->getRecent(5);

        // Orphaned skills
        $result = $this->fetchOne("SELECT COUNT(DISTINCT id) as total FROM {$this->table} WHERE category_id IS NULL");
        $stats['orphaned_skills'] = $result['total'] ?? 0;

        return $stats;
    }

    /**
     * Get autocomplete suggestions
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function autocomplete(string $query, int $limit = 10): array
    {
        $sql = "SELECT DISTINCT s.id, s.name, sc.name as category, sc.icon
                FROM {$this->table} s
                LEFT JOIN skill_categories sc ON s.category_id = sc.id
                WHERE s.name LIKE :query
                GROUP BY s.id
                ORDER BY 
                    CASE 
                        WHEN s.name LIKE :exact THEN 0
                        ELSE 1
                    END,
                    name";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql, [
            ':query' => "%{$query}%",
            ':exact' => $query
        ]);
    }
}
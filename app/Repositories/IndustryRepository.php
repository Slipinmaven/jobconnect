<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;

/**
 * IndustryRepository - Handles all database queries for industries
 * 
 * PURPOSE: Manages industry data for employer profiles, job listings, etc.
 * 
 * FUNCTIONALITY:
 * - Fetch all industries for dropdowns
 * - Search/filter industries by name
 * - Find by slug for URL-friendly lookups
 * - Get popular/featured industries
 */
class IndustryRepository extends BaseRepository
{
    protected string $table = 'industries';
    protected string $primaryKey = 'id';

    /**
     * Find industry by slug
     * 
     * Used for URL lookups like /industry/technology
     * 
     * @param string $slug URL-friendly slug
     * @return array|null Industry data or null if not found
     */
    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug = :slug LIMIT 1";
        return $this->fetchOne($sql, [':slug' => $slug]);
    }

    /**
     * Search industries by name
     * 
     * Used for live search in employer profile setup
     * 
     * @param string $searchTerm Search query
     * @param int $limit Maximum results to return
     * @return array Matching industries
     */
    public function searchByName(string $searchTerm, int $limit = 20): array
    {
        $sql = "SELECT DISTINCT * FROM {$this->table} 
                WHERE name LIKE :search 
                ORDER BY name ASC";
        
        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql, [
            ':search' => '%' . $searchTerm . '%'
        ]);
    }

    /**
     * Get all industries ordered by name
     * 
     * Used for dropdowns and select lists
     * 
     * @param bool $withIdAsKey If true, returns array with id as key
     * @return array All industries
     */
    public function getAllForDropdown(bool $withIdAsKey = false): array
    {
        $industries = $this->findAll('name ASC');
        
        if ($withIdAsKey) {
            // Format: [id => name] for select dropdowns
            $formatted = [];
            foreach ($industries as $industry) {
                $formatted[$industry['id']] = $industry['name'];
            }
            return $formatted;
        }
        
        return $industries;
    }

    /**
     * Get industries by IDs
     * 
     * @param array $ids Array of industry IDs
     * @return array Industries matching the IDs
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT DISTINCT * FROM {$this->table} 
                WHERE id IN ({$placeholders}) 
                ORDER BY name ASC";
        
        return $this->fetchAll($sql, $ids);
    }

    /**
     * Get popular industries (most used in job listings)
     * 
     * Note: This requires a job_industries pivot table
     * 
     * @param int $limit Number of industries to return
     * @return array Popular industries with job count
     */
    public function getPopularWithJobCount(int $limit = 10): array
    {
        $sql = "SELECT DISTINCT i.*, COUNT(DISTINCT ji.job_id) as job_count 
                FROM {$this->table} i
                LEFT JOIN job_industries ji ON i.id = ji.industry_id
                GROUP BY i.id
                HAVING job_count > 0
                ORDER BY job_count DESC, i.name ASC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql);
    }

    /**
     * Create multiple industries at once
     * 
     * Used for seeding or bulk imports
     * 
     * @param array $industries Array of [name, slug] pairs
     * @return int Number of industries created
     */
    public function createMultiple(array $industries): int
    {
        $created = 0;
        
        try {
            $this->beginTransaction();
            
            foreach ($industries as $industry) {
                // Check if slug already exists
                if (!$this->exists(['slug' => $industry['slug']])) {
                    $this->create([
                        'name' => $industry['name'],
                        'slug' => $industry['slug']
                    ]);
                    $created++;
                }
            }
            
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            error_log("Failed to create multiple industries: " . $e->getMessage());
            throw $e;
        }
        
        return $created;
    }

    /**
     * Get industries starting with letter
     * 
     * Useful for alphabetical browsing
     * 
     * @param string $letter Single letter
     * @return array Industries starting with that letter
     */
    public function getByFirstLetter(string $letter): array
    {
        $sql = "SELECT DISTINCT * FROM {$this->table} 
                WHERE name LIKE :letter 
                ORDER BY name ASC";
        
        return $this->fetchAll($sql, [':letter' => $letter . '%']);
    }

    /**
     * Get industry statistics
     * 
     * @return array Stats about industries
     */
    public function getStatistics(): array
    {
        $stats = [];
        
        // Total industries
        $stats['total'] = $this->count();
        
        // Industries with most employers (requires employers table with industry_id)
        $sql = "SELECT DISTINCT i.name, COUNT(DISTINCT e.id) as employer_count 
                FROM {$this->table} i
                LEFT JOIN employers e ON i.id = e.industry_id
                GROUP BY i.id
                HAVING employer_count > 0
                ORDER BY employer_count DESC
                LIMIT 5";
        $stats['top_employer_industries'] = $this->fetchAll($sql);
        
        // Recently added industries
        $sql = "SELECT DISTINCT * FROM {$this->table} 
                ORDER BY created_at DESC 
                LIMIT 5";
        $stats['recent'] = $this->fetchAll($sql);
        
        return $stats;
    }

    /**
     * Find or create industry by name
     * 
     * Useful when importing data or accepting user input
     * 
     * @param string $name Industry name
     * @return array Industry data (existing or new)
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
        $id = $this->create([
            'name' => $name,
            'slug' => $slug
        ]);
        
        return $this->findById($id);
    }

    /**
     * Generate URL-friendly slug from name
     * 
     * @param string $name Industry name
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
     * Get industries grouped by first letter
     * 
     * Useful for directory-style listings
     * 
     * @return array Industries grouped A-Z
     */
    public function getGroupedByLetter(): array
    {
        $industries = $this->findAll('name ASC');
        $grouped = [];
        
        foreach ($industries as $industry) {
            $firstLetter = strtoupper(substr($industry['name'], 0, 1));
            
            if (!isset($grouped[$firstLetter])) {
                $grouped[$firstLetter] = [];
            }
            
            $grouped[$firstLetter][] = $industry;
        }
        
        return $grouped;
    }

    /**
     * Update industry slug
     * 
     * @param int $id Industry ID
     * @param string $newSlug New slug
     * @return bool Success
     */
    public function updateSlug(int $id, string $newSlug): bool
    {
        // Ensure slug is unique
        $existing = $this->findWhere(['slug' => $newSlug]);
        
        foreach ($existing as $industry) {
            if ($industry['id'] != $id) {
                throw new \InvalidArgumentException("Slug '{$newSlug}' already exists");
            }
        }
        
        return $this->update($id, ['slug' => $newSlug]);
    }

    /**
     * Get industries with similar names
     * 
     * @param string $name Industry name to compare
     * @param int $limit Maximum results
     * @return array Similar industries
     */
    public function getSimilar(string $name, int $limit = 5): array
    {
        $sql = "SELECT DISTINCT * FROM {$this->table} 
                WHERE SOUNDEX(name) = SOUNDEX(:name)
                OR name LIKE :like
                ORDER BY name ASC";
        
        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql, [
            ':name' => $name,
            ':like' => '%' . $name . '%'
        ]);
    }

    /**
     * Get orphaned industries (no employers, no jobs)
     * 
     * @return array Unused industries
     */
    public function getOrphaned(): array
    {
        $sql = "SELECT DISTINCT i.* FROM {$this->table} i
                LEFT JOIN employers e ON i.id = e.industry_id
                LEFT JOIN job_industries ji ON i.id = ji.industry_id
                WHERE e.id IS NULL AND ji.job_id IS NULL
                ORDER BY i.name ASC";
        
        return $this->fetchAll($sql);
    }

    /**
     * Get total count of industries (optionally with search)
     */
    public function countBySearch(?string $search = null): int
    {
        if ($search) {
            $sql = "SELECT COUNT(DISTINCT id) as total FROM {$this->table} WHERE name LIKE :search";
            $result = $this->fetchOne($sql, [':search' => '%' . $search . '%']);
            return (int)($result['total'] ?? 0);
        } else {
            return $this->count();
        }
    }

    /**
     * Get paginated list (optionally with search)
     */
    public function searchPaginated(?string $search, int $offset, int $limit): array
    {
        $params = [':limit' => $limit, ':offset' => $offset];
        $where = '';
        
        if ($search) {
            $where = "WHERE name LIKE :search ";
            $params[':search'] = '%' . $search . '%';
        }
        
        $sql = "SELECT DISTINCT * FROM {$this->table} 
                {$where}
                ORDER BY name ASC";
        $sql .= $this->formatLimitOffset($limit, $offset);
        
        return $this->fetchAll($sql, $params);
    }
    
    public function findAllPaginated(int $offset, int $limit, ?string $search = null): array
    {
        $params = [];
        $where = '';

        if ($search) {
            $where = "WHERE name LIKE :search ";
            $params[':search'] = '%' . $search . '%';
        }

        $sql = "SELECT DISTINCT * FROM {$this->table} 
                {$where}
                ORDER BY name ASC";
        $sql .= $this->formatLimitOffset($limit, $offset);

        return $this->fetchAll($sql, $params);
    }
    
    /**
     * Merge one industry into another
     * 
     * Moves all references from source to target, then deletes source
     * 
     * @param int $sourceId Industry to merge from (will be deleted)
     * @param int $targetId Industry to merge into
     * @return bool Success
     */
    public function mergeIndustries(int $sourceId, int $targetId): bool
    {
        try {
            $this->beginTransaction();
            
            // Update employers (if employers table exists)
            $sql = "UPDATE employers SET industry_id = :target WHERE industry_id = :source";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':target' => $targetId, ':source' => $sourceId]);
            
            // Update job_industries pivot table
            $sql = "UPDATE job_industries SET industry_id = :target WHERE industry_id = :source";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':target' => $targetId, ':source' => $sourceId]);
            
            // Delete source industry
            $this->delete($sourceId);
            
            $this->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->rollback();
            error_log("Failed to merge industries: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCount(?string $search = null): int
    {
        if ($search) {
            $sql = "SELECT COUNT(DISTINCT id) as total FROM {$this->table} WHERE name LIKE :search";
            $result = $this->fetchOne($sql, [':search' => '%' . $search . '%']);
            return (int)($result['total'] ?? 0);
        }
        
        // Fallback to base count with no conditions
        return parent::count([]);
    }
}
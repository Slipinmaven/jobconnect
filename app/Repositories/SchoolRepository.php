<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;
use PDOException;
use Exception;
/**
 * SchoolRepository - Manages schools/educational institutions
 * 
 * Handles:
 * - Schools CRUD
 * - School search and filtering
 * - Schools by type (high_school, university, college, bootcamp)
 * - School suggestions
 * - Location-based filtering
 */
class SchoolRepository extends BaseRepository
{
    protected string $table = 'schools';
    protected string $primaryKey = 'id';

    /**
     * Find school by ID with full details
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        return $this->fetchOne($sql, [':id' => $id]);
    }

    /**
     * Find school by slug
     *
     * @param string $slug
     * @return array|null
     */
    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug = :slug";
        return $this->fetchOne($sql, [':slug' => $slug]);
    }

    /**
     * Find school by name (case insensitive)
     *
     * @param string $name
     * @return array|null
     */
    public function findByName(string $name): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE LOWER(name) = LOWER(:name)";
        return $this->fetchOne($sql, [':name' => $name]);
    }

    /**
     * Search schools by name with filters
     *
     * @param string $query
     * @param array $filters
     * @param int $limit
     * @return array
     */
    public function search(string $query, array $filters = [], int $limit = 20): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE name LIKE :query";
        
        $params = [':query' => "%{$query}%"];

        // Filter by type
        if (!empty($filters['type'])) {
            if (is_array($filters['type'])) {
                $placeholders = implode(',', array_fill(0, count($filters['type']), '?'));
                $sql .= " AND type IN ({$placeholders})";
                $params = array_merge($params, $filters['type']);
            } else {
                $sql .= " AND type = :type";
                $params[':type'] = $filters['type'];
            }
        }

        // Filter by country
        if (!empty($filters['country'])) {
            $sql .= " AND country = :country";
            $params[':country'] = $filters['country'];
        }

        // Filter by city
        if (!empty($filters['city'])) {
            $sql .= " AND city LIKE :city";
            $params[':city'] = "%{$filters['city']}%";
        }

        $sql .= " ORDER BY 
                    CASE 
                        WHEN name LIKE :exact THEN 0 
                        ELSE 1 
                    END,
                    name";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        $params[':exact'] = $query;

        return $this->fetchAll($sql, $params);
    }

    /**
     * Get schools by type
     *
     * @param string $type
     * @param string $orderBy
     * @return array
     */
    public function getByType(string $type, string $orderBy = 'name'): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE type = :type 
                ORDER BY {$orderBy}";
        
        return $this->fetchAll($sql, [':type' => $type]);
    }

    /**
     * Get schools by country
     *
     * @param string $country
     * @param string|null $type
     * @return array
     */
    public function getByCountry(string $country, ?string $type = null): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE country = :country";
        
        $params = [':country' => $country];

        if ($type) {
            $sql .= " AND type = :type";
            $params[':type'] = $type;
        }

        $sql .= " ORDER BY name";

        return $this->fetchAll($sql, $params);
    }

    /**
     * Get schools by city
     *
     * @param string $city
     * @param string|null $country
     * @return array
     */
    public function getByCity(string $city, ?string $country = null): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE city = :city";
        
        $params = [':city' => $city];

        if ($country) {
            $sql .= " AND country = :country";
            $params[':country'] = $country;
        }

        $sql .= " ORDER BY name";

        return $this->fetchAll($sql, $params);
    }

    /**
     * Get popular schools (most used by jobseekers)
     *
     * @param int $limit
     * @param string|null $type
     * @return array
     */
    public function getPopular(int $limit = 10, ?string $type = null): array
    {
        $sql = "SELECT s.*, COUNT(je.id) as usage_count
                FROM {$this->table} s
                LEFT JOIN jobseeker_educations je ON s.id = je.school_id
                WHERE 1=1";
        
        $params = [];

        if ($type) {
            $sql .= " AND s.type = :type";
            $params[':type'] = $type;
        }

        $sql .= " GROUP BY s.id
                  ORDER BY usage_count DESC, s.name";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql, $params);
    }

    /**
     * Get recently added schools
     *
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 10): array
    {
        $sql = "SELECT * FROM {$this->table} 
                ORDER BY created_at DESC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql);
    }

    /**
     * Create a new school
     *
     * @param array $data
     * @return int|null
     */
    public function createSchool(array $data): ?int
    {
        try {
            // Check if school already exists
            $existing = $this->findByName($data['name']);
            if ($existing) {
                return null; // School already exists
            }

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['name']);
            }

            // Validate type
            $allowedTypes = ['high_school', 'university', 'college', 'bootcamp', 'other'];
            if (!in_array($data['type'], $allowedTypes)) {
                $data['type'] = 'other';
            }

            return $this->create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'type' => $data['type'],
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Failed to create school: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update school
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateSchool(int $id, array $data): bool
    {
        $allowedFields = ['name', 'slug', 'type', 'city', 'country'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        // Regenerate slug if name changes
        if (!empty($data['name']) && empty($data['slug'])) {
            $updateData['slug'] = $this->generateSlug($data['name']);
        }
        
        if (empty($updateData)) {
            return false;
        }

        return $this->update($id, $updateData);
    }

    /**
     * Delete school (will cascade to jobseeker_educations)
     *
     * @param int $id
     * @return bool
     */
    public function deleteSchool(int $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Bulk import schools
     *
     * @param array $schools Array of school data
     * @return array Results [created, skipped, errors]
     */
    public function bulkImport(array $schools): array
    {
        $results = [
            'created' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        try {
            $this->beginTransaction();

            foreach ($schools as $school) {
                try {
                    // Check required fields
                    if (empty($school['name']) || empty($school['type'])) {
                        $results['errors'][] = "Missing required fields for school: " . json_encode($school);
                        continue;
                    }

                    // Check if exists
                    $existing = $this->findByName($school['name']);
                    if ($existing) {
                        $results['skipped']++;
                        continue;
                    }

                    // Generate slug
                    $slug = $school['slug'] ?? $this->generateSlug($school['name']);

                    // Create school
                    $created = $this->create([
                        'name' => $school['name'],
                        'slug' => $slug,
                        'type' => $school['type'],
                        'city' => $school['city'] ?? null,
                        'country' => $school['country'] ?? null
                    ]);

                    if ($created) {
                        $results['created']++;
                    } else {
                        $results['errors'][] = "Failed to create school: {$school['name']}";
                    }

                } catch (Exception $e) {
                    $results['errors'][] = "Error with school {$school['name']}: " . $e->getMessage();
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

    /**
     * Get schools grouped by country
     *
     * @return array
     */
    public function getGroupedByCountry(): array
    {
        $sql = "SELECT country, 
                       COUNT(*) as school_count,
                       GROUP_CONCAT(DISTINCT type) as types
                FROM {$this->table} 
                WHERE country IS NOT NULL
                GROUP BY country
                ORDER BY country";
        
        $results = $this->fetchAll($sql);
        
        // Add schools for each country
        foreach ($results as &$country) {
            $country['schools'] = $this->getByCountry($country['country']);
        }
        
        return $results;
    }

    /**
     * Get schools grouped by type
     *
     * @return array
     */
    public function getGroupedByType(): array
    {
        $types = ['high_school', 'university', 'college', 'bootcamp', 'other'];
        $result = [];

        foreach ($types as $type) {
            $result[$type] = [
                'count' => $this->count(['type' => $type]),
                'schools' => $this->getByType($type, 'name')
            ];
        }

        return $result;
    }

    /**
     * Get autocomplete suggestions
     *
     * @param string $query
     * @param string|null $type
     * @param int $limit
     * @return array
     */
    public function autocomplete(string $query, ?string $type = null, int $limit = 10): array
    {
        $sql = "SELECT id, name, type, city, country,
                       CONCAT(name, ' (', city, ', ', country, ')') as display_name
                FROM {$this->table} 
                WHERE name LIKE :query";
        
        $params = [':query' => "%{$query}%"];

        if ($type) {
            $sql .= " AND type = :type";
            $params[':type'] = $type;
        }

        $sql .= " ORDER BY 
                    CASE 
                        WHEN name LIKE :exact THEN 0 
                        ELSE 1 
                    END,
                    name";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        $params[':exact'] = $query;

        return $this->fetchAll($sql, $params);
    }

    /**
     * Get schools by name pattern (for fuzzy matching)
     *
     * @param string $pattern
     * @param int $limit
     * @return array
     */
    public function findSimilar(string $pattern, int $limit = 5): array
    {
        // Use SOUNDEX for fuzzy matching (MySQL)
        $sql = "SELECT *, 
                       SOUNDEX(name) as name_soundex,
                       SOUNDEX(:pattern) as pattern_soundex
                FROM {$this->table} 
                WHERE SOUNDEX(name) = SOUNDEX(:pattern)
                   OR name LIKE :like_pattern
                ORDER BY 
                    CASE 
                        WHEN name LIKE :exact THEN 0
                        WHEN name LIKE :starts_with THEN 1
                        ELSE 2
                    END,
                    name";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);
        
        return $this->fetchAll($sql, [
            ':pattern' => $pattern,
            ':like_pattern' => "%{$pattern}%",
            ':exact' => $pattern,
            ':starts_with' => "{$pattern}%"
        ]);
    }

    /**
     * Get or create school by name
     *
     * @param string $name
     * @param string $type
     * @param string|null $city
     * @param string|null $country
     * @return int|null School ID
     */
    public function getOrCreate(string $name, string $type, ?string $city = null, ?string $country = null): ?int
    {
        // Try to find exact match first
        $existing = $this->findByName($name);
        if ($existing) {
            return $existing['id'];
        }

        // Try to find similar
        $similar = $this->findSimilar($name, 1);
        if (!empty($similar)) {
            return $similar[0]['id'];
        }

        // Create new
        return $this->createSchool([
            'name' => $name,
            'type' => $type,
            'city' => $city,
            'country' => $country
        ]);
    }

    /**
     * Get statistics about schools
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $stats = [];

        // Total schools
        $result = $this->fetchOne("SELECT COUNT(*) as total FROM {$this->table}");
        $stats['total_schools'] = $result['total'] ?? 0;

        // Count by type
        $sql = "SELECT type, COUNT(*) as count 
                FROM {$this->table} 
                GROUP BY type 
                ORDER BY count DESC";
        $stats['by_type'] = $this->fetchAll($sql);

        // Count by country
        $sql = "SELECT country, COUNT(*) as count 
                FROM {$this->table} 
                WHERE country IS NOT NULL 
                GROUP BY country 
                ORDER BY count DESC 
                LIMIT 10";
        $stats['top_countries'] = $this->fetchAll($sql);

        // Most used schools (in education history)
        $sql = "SELECT s.name, s.type, s.city, s.country, COUNT(je.id) as usage_count
                FROM {$this->table} s
                LEFT JOIN jobseeker_educations je ON s.id = je.school_id
                GROUP BY s.id
                ORDER BY usage_count DESC
                LIMIT 10";
        $stats['most_used'] = $this->fetchAll($sql);

        // Recently added
        $stats['recently_added'] = $this->getRecent(5);

        return $stats;
    }

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
     * Get distinct countries
     *
     * @return array
     */
    public function getCountries(): array
    {
        $sql = "SELECT DISTINCT country 
                FROM {$this->table} 
                WHERE country IS NOT NULL 
                ORDER BY country";
        
        return array_column($this->fetchAll($sql), 'country');
    }

    /**
     * Get distinct cities by country
     *
     * @param string $country
     * @return array
     */
    public function getCitiesByCountry(string $country): array
    {
        $sql = "SELECT DISTINCT city 
                FROM {$this->table} 
                WHERE country = :country AND city IS NOT NULL
                ORDER BY city";
        
        return array_column($this->fetchAll($sql, [':country' => $country]), 'city');
    }

    /**
     * Validate school data
     *
     * @param array $data
     * @return bool
     */
    public function validate(array $data): bool
    {
        if (empty($data['name'])) {
            return false;
        }

        $allowedTypes = ['high_school', 'university', 'college', 'bootcamp', 'other'];
        if (!empty($data['type']) && !in_array($data['type'], $allowedTypes)) {
            return false;
        }

        return true;
    }

    /**
     * Merge duplicate schools
     *
     * @param int $keepId School ID to keep
     * @param array $mergeIds School IDs to merge into keepId
     * @return bool
     */
    public function mergeSchools(int $keepId, array $mergeIds): bool
    {
        try {
            $this->beginTransaction();

            // Update all education records to point to the kept school
            $placeholders = implode(',', array_fill(0, count($mergeIds), '?'));
            $params = array_merge([$keepId], $mergeIds);
            
            $this->execute(
                "UPDATE jobseeker_educations SET school_id = ? WHERE school_id IN ({$placeholders})",
                $params
            );

            // Delete duplicate schools
            $this->execute(
                "DELETE FROM {$this->table} WHERE id IN ({$placeholders})",
                $mergeIds
            );

            $this->commit();
            return true;

        } catch (Exception $e) {
            $this->rollback();
            error_log("Failed to merge schools: " . $e->getMessage());
            return false;
        }
    }
}
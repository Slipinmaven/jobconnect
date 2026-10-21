<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;

/**
 * JobCategoryMapRepository - Handles all database operations for job_category_map table
 * 
 * PURPOSE: Manages the many-to-many relationship between jobs and categories
 * 
 * FEATURES:
 * - Assign categories to jobs
 * - Manage primary category designation
 * - Bulk operations for job-category associations
 * - Category assignment management
 * - Efficient queries for job-category relationships
 * 
 * TABLE STRUCTURE:
 * - job_id: ID of the job
 * - category_id: ID of the category
 * - is_primary: Whether this is the primary category for the job
 * - created_at: When the association was created
 * - updated_at: When the association was last updated
 */
class JobCategoryMapRepository extends BaseRepository
{
    protected string $table = 'job_category_map';
    protected string $primaryKey = 'job_id'; // Composite primary key (job_id, category_id)
    
    // Constants for primary category designation
    private const IS_PRIMARY = 1;
    private const NOT_PRIMARY = 0;
    
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }
    
    // ==================== BASIC ASSIGNMENT OPERATIONS ====================
    
    /**
     * Assign a category to a job
     * 
     * @param int $jobId Job ID
     * @param int $categoryId Category ID
     * @param bool $isPrimary Whether this is the primary category
     * @return bool True if assignment successful
     */
    public function assignCategory(int $jobId, int $categoryId, bool $isPrimary = false): bool
    {
        // Check if the association already exists
        if ($this->isCategoryAssigned($jobId, $categoryId)) {
            // Update existing association
            return $this->updatePrimaryStatus($jobId, $categoryId, $isPrimary);
        }
        
        // If setting as primary, ensure no other primary exists for this job
        if ($isPrimary) {
            $this->removePrimaryCategory($jobId);
        }
        
        $data = [
            'job_id' => $jobId,
            'category_id' => $categoryId,
            'is_primary' => $isPrimary ? self::IS_PRIMARY : self::NOT_PRIMARY,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $sql = "INSERT INTO {$this->table} (job_id, category_id, is_primary, created_at, updated_at) 
                VALUES (:job_id, :category_id, :is_primary, :created_at, :updated_at)";
        
        return $this->execute($sql, $data);
    }
    
    /**
     * Remove a category from a job
     * 
     * @param int $jobId Job ID
     * @param int $categoryId Category ID
     * @return bool True if removal successful
     */
    public function removeCategory(int $jobId, int $categoryId): bool
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE job_id = :job_id AND category_id = :category_id";
        
        return $this->execute($sql, [
            ':job_id' => $jobId,
            ':category_id' => $categoryId
        ]);
    }
    
    /**
     * Assign multiple categories to a job
     * 
     * @param int $jobId Job ID
     * @param array $categoryIds Array of category IDs
     * @param int|null $primaryCategoryId Category ID to set as primary (optional)
     * @return array Successfully assigned categories
     */
    public function assignCategories(int $jobId, array $categoryIds, ?int $primaryCategoryId = null): array
    {
        $assigned = [];
        
        $this->beginTransaction();
        
        try {
            // Remove existing assignments if we're doing a full replacement
            if ($primaryCategoryId !== null) {
                $this->removeAllCategories($jobId);
            }
            
            foreach ($categoryIds as $categoryId) {
                $isPrimary = ($primaryCategoryId !== null && $categoryId == $primaryCategoryId);
                
                if ($this->assignCategory($jobId, $categoryId, $isPrimary)) {
                    $assigned[] = $categoryId;
                }
            }
            
            $this->commit();
            return $assigned;
        } catch (\Exception $e) {
            $this->rollback();
            error_log("Failed to assign categories to job {$jobId}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Remove all category assignments for a job
     * 
     * @param int $jobId Job ID
     * @return bool True if removal successful
     */
    public function removeAllCategories(int $jobId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE job_id = :job_id";
        return $this->execute($sql, [':job_id' => $jobId]);
    }
    
    // ==================== PRIMARY CATEGORY MANAGEMENT ====================
    
    /**
     * Set a category as primary for a job
     * 
     * @param int $jobId Job ID
     * @param int $categoryId Category ID
     * @return bool True if successful
     */
    public function setPrimaryCategory(int $jobId, int $categoryId): bool
    {
        $this->beginTransaction();
        
        try {
            // First, remove any existing primary designation
            $this->removePrimaryCategory($jobId);
            
            // Then set the new primary category
            $sql = "UPDATE {$this->table} 
                    SET is_primary = :is_primary, updated_at = :updated_at 
                    WHERE job_id = :job_id AND category_id = :category_id";
            
            $result = $this->execute($sql, [
                ':is_primary' => self::IS_PRIMARY,
                ':updated_at' => date('Y-m-d H:i:s'),
                ':job_id' => $jobId,
                ':category_id' => $categoryId
            ]);
            
            // If the category wasn't assigned yet, assign it as primary
            if ($this->rowCount() === 0) {
                return $this->assignCategory($jobId, $categoryId, true);
            }
            
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            error_log("Failed to set primary category for job {$jobId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove primary category designation from a job
     * 
     * @param int $jobId Job ID
     * @return bool True if successful
     */
    public function removePrimaryCategory(int $jobId): bool
    {
        $sql = "UPDATE {$this->table} 
                SET is_primary = :is_primary, updated_at = :updated_at 
                WHERE job_id = :job_id AND is_primary = :current_primary";
        
        return $this->execute($sql, [
            ':is_primary' => self::NOT_PRIMARY,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':job_id' => $jobId,
            ':current_primary' => self::IS_PRIMARY
        ]);
    }
    
    /**
     * Get the primary category for a job
     * 
     * @param int $jobId Job ID
     * @return array|null Primary category info or null
     */
    public function getPrimaryCategory(int $jobId): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE job_id = :job_id AND is_primary = :is_primary 
                LIMIT 1";
        
        return $this->fetchOne($sql, [
            ':job_id' => $jobId,
            ':is_primary' => self::IS_PRIMARY
        ]);
    }
    
    // ==================== QUERY OPERATIONS ====================
    
    /**
     * Get all categories for a job
     * 
     * @param int $jobId Job ID
     * @param bool $includeCategoryDetails Whether to join category table for details
     * @return array Categories assigned to the job
     */
    public function getJobCategories(int $jobId, bool $includeCategoryDetails = false): array
    {
        if ($includeCategoryDetails) {
            $sql = "SELECT m.*, c.name, c.slug, c.description, c.parent_id, c.is_active as category_active
                    FROM {$this->table} m
                    INNER JOIN job_categories c ON m.category_id = c.id
                    WHERE m.job_id = :job_id
                    ORDER BY m.is_primary DESC, c.name ASC";
        } else {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE job_id = :job_id 
                    ORDER BY is_primary DESC";
        }
        
        return $this->fetchAll($sql, [':job_id' => $jobId]);
    }
    
    /**
     * Get all jobs for a category
     * 
     * @param int $categoryId Category ID
     * @param bool $includeJobDetails Whether to join job table for details
     * @param int $limit Maximum number of jobs to return
     * @param int $offset Pagination offset
     * @return array Jobs assigned to the category
     */
    public function getCategoryJobs(int $categoryId, bool $includeJobDetails = false, int $limit = 50, int $offset = 0): array
    {
        if ($includeJobDetails) {
            $sql = "SELECT m.*, j.title, j.description, j.location, j.salary_min, j.salary_max, 
                           j.company_id, j.is_active as job_active, j.posted_date
                    FROM {$this->table} m
                    INNER JOIN jobs j ON m.job_id = j.id
                    WHERE m.category_id = :category_id
                    ORDER BY m.is_primary DESC, j.posted_date DESC";
        } else {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE category_id = :category_id 
                    ORDER BY is_primary DESC";
        }
        
        $sql .= $this->formatLimitOffset($limit, $offset);

        return $this->fetchAll($sql, [
            ':category_id' => $categoryId
        ]);
    }
    
    /**
     * Check if a category is assigned to a job
     * 
     * @param int $jobId Job ID
     * @param int $categoryId Category ID
     * @return bool True if assigned
     */
    public function isCategoryAssigned(int $jobId, int $categoryId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE job_id = :job_id AND category_id = :category_id";
        
        $result = $this->fetchOne($sql, [
            ':job_id' => $jobId,
            ':category_id' => $categoryId
        ]);
        
        return ($result['count'] ?? 0) > 0;
    }
    
    /**
     * Count jobs in a category
     * 
     * @param int $categoryId Category ID
     * @param bool $onlyActiveJobs Whether to count only active jobs
     * @return int Number of jobs
     */
    public function countJobsInCategory(int $categoryId, bool $onlyActiveJobs = false): int
    {
        if ($onlyActiveJobs) {
            $sql = "SELECT COUNT(*) as count 
                    FROM {$this->table} m
                    INNER JOIN jobs j ON m.job_id = j.id
                    WHERE m.category_id = :category_id AND j.is_active = 1";
        } else {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                    WHERE category_id = :category_id";
        }
        
        $result = $this->fetchOne($sql, [':category_id' => $categoryId]);
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Count categories for a job
     * 
     * @param int $jobId Job ID
     * @return int Number of categories assigned
     */
    public function countCategoriesForJob(int $jobId): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE job_id = :job_id";
        $result = $this->fetchOne($sql, [':job_id' => $jobId]);
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Get jobs that have a primary category but are missing secondary categories
     * 
     * @param int $minCategories Minimum number of categories (excluding primary)
     * @return array Jobs with insufficient categories
     */
    public function getJobsWithInsufficientCategories(int $minCategories = 1): array
    {
        $sql = "SELECT job_id, COUNT(*) as category_count, 
                       MAX(CASE WHEN is_primary = 1 THEN 1 ELSE 0 END) as has_primary
                FROM {$this->table}
                GROUP BY job_id
                HAVING has_primary = 1 AND category_count <= :min_categories";
        
        return $this->fetchAll($sql, [':min_categories' => $minCategories]);
    }
    
    // ==================== BULK OPERATIONS ====================
    
    /**
     * Bulk assign categories to multiple jobs
     * 
     * @param array $assignments Array of [job_id => [category_ids, primary_category_id]]
     * @return array Results with success and failure counts
     */
    public function bulkAssignCategories(array $assignments): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        $this->beginTransaction();
        
        try {
            foreach ($assignments as $jobId => $assignment) {
                $categoryIds = $assignment['categories'] ?? [];
                $primaryCategoryId = $assignment['primary'] ?? null;
                
                $assigned = $this->assignCategories($jobId, $categoryIds, $primaryCategoryId);
                
                if (count($assigned) === count($categoryIds)) {
                    $results['success']++;
                    $results['details'][$jobId] = ['status' => 'success', 'assigned' => $assigned];
                } else {
                    $results['failed']++;
                    $results['details'][$jobId] = ['status' => 'failed', 'assigned' => $assigned];
                }
            }
            
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            error_log("Bulk category assignment failed: " . $e->getMessage());
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Copy categories from one job to another
     * 
     * @param int $sourceJobId Source job ID
     * @param int $targetJobId Target job ID
     * @param bool $replaceExisting Whether to replace existing categories on target
     * @return bool True if successful
     */
    public function copyCategories(int $sourceJobId, int $targetJobId, bool $replaceExisting = false): bool
    {
        $this->beginTransaction();
        
        try {
            if ($replaceExisting) {
                $this->removeAllCategories($targetJobId);
            }
            
            $sourceCategories = $this->getJobCategories($sourceJobId);
            
            foreach ($sourceCategories as $category) {
                $this->assignCategory(
                    $targetJobId, 
                    $category['category_id'], 
                    (bool)$category['is_primary']
                );
            }
            
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            error_log("Failed to copy categories from job {$sourceJobId} to {$targetJobId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update primary status for an existing assignment
     * 
     * @param int $jobId Job ID
     * @param int $categoryId Category ID
     * @param bool $isPrimary New primary status
     * @return bool True if successful
     */
    public function updatePrimaryStatus(int $jobId, int $categoryId, bool $isPrimary): bool
    {
        if ($isPrimary) {
            $this->removePrimaryCategory($jobId);
        }
        
        $sql = "UPDATE {$this->table} 
                SET is_primary = :is_primary, updated_at = :updated_at 
                WHERE job_id = :job_id AND category_id = :category_id";
        
        return $this->execute($sql, [
            ':is_primary' => $isPrimary ? self::IS_PRIMARY : self::NOT_PRIMARY,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':job_id' => $jobId,
            ':category_id' => $categoryId
        ]);
    }
    
    // ==================== VALIDATION & UTILITY METHODS ====================
    
    /**
     * Validate that a job has exactly one primary category (if any categories assigned)
     * 
     * @param int $jobId Job ID
     * @return bool True if valid (0 or 1 primary categories)
     */
    public function validatePrimaryCategory(int $jobId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE job_id = :job_id AND is_primary = :is_primary";
        
        $result = $this->fetchOne($sql, [
            ':job_id' => $jobId,
            ':is_primary' => self::IS_PRIMARY
        ]);
        
        $primaryCount = (int)($result['count'] ?? 0);
        
        // Get total categories
        $totalCount = $this->countCategoriesForJob($jobId);
        
        // If there are categories, there should be exactly 1 primary
        if ($totalCount > 0) {
            return $primaryCount === 1;
        }
        
        // If no categories, there should be 0 primary
        return $primaryCount === 0;
    }
    
    /**
     * Get all categories with job counts (for dashboard/reporting)
     * 
     * @param bool $onlyActiveJobs Whether to count only active jobs
     * @return array Categories with job counts
     */
    public function getCategoryStats(bool $onlyActiveJobs = true): array
    {
        $sql = "SELECT c.id, c.name, c.slug, 
                       COUNT(DISTINCT m.job_id) as job_count,
                       COUNT(CASE WHEN m.is_primary = 1 THEN 1 END) as primary_job_count
                FROM job_categories c
                LEFT JOIN {$this->table} m ON c.id = m.category_id";
        
        if ($onlyActiveJobs) {
            $sql .= " LEFT JOIN jobs j ON m.job_id = j.id AND j.is_active = 1";
            $sql .= " WHERE j.id IS NOT NULL OR m.job_id IS NULL";
        }
        
        $sql .= " GROUP BY c.id, c.name, c.slug
                  ORDER BY job_count DESC, c.name ASC";
        
        return $this->fetchAll($sql);
    }
    
    /**
     * Get the most popular categories (by job count)
     * 
     * @param int $limit Number of categories to return
     * @param bool $onlyActiveJobs Whether to count only active jobs
     * @return array Top categories
     */
    public function getTopCategories(int $limit = 10, bool $onlyActiveJobs = true): array
    {
        $sql = "SELECT c.id, c.name, c.slug, COUNT(m.job_id) as job_count
                FROM job_categories c
                INNER JOIN {$this->table} m ON c.id = m.category_id";
        
        if ($onlyActiveJobs) {
            $sql .= " INNER JOIN jobs j ON m.job_id = j.id AND j.is_active = 1";
        }
        
        $sql .= " GROUP BY c.id, c.name, c.slug
                  ORDER BY job_count DESC, c.name ASC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);
        
        return $this->fetchAll($sql);
    }
}
<?php

namespace App\Repositories;

use App\Core\BaseRepository;

class SavedJobRepository extends BaseRepository
{
    protected string $table = 'saved_jobs';
    protected string $primaryKey = 'id';

    /**
     * Save a job for a user.
     * 
     * @param int $userId
     * @param int $jobId
     * @return bool True if saved, false if already saved or error
     */
    public function saveJob(int $userId, int $jobId): bool
    {
        // Check if already saved
        if ($this->isSaved($userId, $jobId)) {
            return false;
        }

        $sql = "INSERT INTO {$this->table} (user_id, job_id, created_at) 
                VALUES (:user_id, :job_id, NOW())";

        return $this->execute($sql, [
            ':user_id' => $userId,
            ':job_id'  => $jobId
        ]);
    }

    /**
     * Remove a saved job for a user.
     * 
     * @param int $userId
     * @param int $jobId
     * @return bool True if removed, false if not found or error
     */
    public function unsaveJob(int $userId, int $jobId): bool
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE user_id = :user_id AND job_id = :job_id";

        return $this->execute($sql, [
            ':user_id' => $userId,
            ':job_id'  => $jobId
        ]);
    }

    /**
     * Check if a user has saved a specific job.
     * 
     * @param int $userId
     * @param int $jobId
     * @return bool
     */
    public function isSaved(int $userId, int $jobId): bool
    {
        $sql = "SELECT COUNT(DISTINCT id) as count FROM {$this->table} 
                WHERE user_id = :user_id AND job_id = :job_id";

        $result = $this->fetchOne($sql, [
            ':user_id' => $userId,
            ':job_id'  => $jobId
        ]);

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Get all saved job IDs for a user.
     * 
     * @param int $userId
     * @return array List of job IDs
     */
    public function getUserSavedJobIds(int $userId): array
    {
        $sql = "SELECT DISTINCT job_id FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC";
        $rows = $this->fetchAll($sql, [':user_id' => $userId]);

        return array_column($rows, 'job_id');
    }

    /**
     * Get saved jobs for a user with full job details (join with jobs table).
     * OPTIMIZED: Removed heavy subqueries, fetch counts separately
     * 
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @return array Paginated result ['data' => [], 'total' => 0, 'page' => 1, 'per_page' => 20, 'total_pages' => 0]
     */
    public function getUserSavedJobsWithDetails(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        // Get total count
        $countSql = "SELECT COUNT(DISTINCT id) as total FROM {$this->table} WHERE user_id = :user_id";
        $countResult = $this->fetchOne($countSql, [':user_id' => $userId]);
        $total = (int) ($countResult['total'] ?? 0);

        // If no saved jobs, return early
        if ($total === 0) {
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 0
            ];
        }

        // Get jobs with details (join with jobs, employer_profiles, locations)
        $sql = "SELECT DISTINCT j.*, e.company_name, e.employer_type, e.avatar,
                       l.city, l.country
                FROM {$this->table} sj
                INNER JOIN jobs j ON sj.job_id = j.id
                LEFT JOIN employer_profiles e ON j.employer_id = e.id
                LEFT JOIN locations l ON j.location_id = l.id
                WHERE sj.user_id = :user_id
                GROUP BY j.id
                ORDER BY sj.created_at DESC";

        $sql .= $this->formatLimitOffset($perPage, $offset);

        $jobs = $this->fetchAll($sql, [':user_id' => $userId]);

        // Fetch counts separately to avoid cartesian product
        if (!empty($jobs)) {
            $jobIds = array_column($jobs, 'id');
            $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
            
            // Get application counts
            $appSql = "SELECT job_id, COUNT(DISTINCT id) as count 
                       FROM applications 
                       WHERE job_id IN ({$placeholders}) 
                       GROUP BY job_id";
            $appCounts = $this->fetchAll($appSql, $jobIds);
            $appMap = [];
            foreach ($appCounts as $count) {
                $appMap[$count['job_id']] = $count['count'];
            }
            
            // Get comment counts
            $commentSql = "SELECT commentable_id, COUNT(DISTINCT id) as count 
                           FROM comments 
                           WHERE commentable_type = 'job' AND commentable_id IN ({$placeholders}) 
                           GROUP BY commentable_id";
            $commentCounts = $this->fetchAll($commentSql, $jobIds);
            $commentMap = [];
            foreach ($commentCounts as $count) {
                $commentMap[$count['commentable_id']] = $count['count'];
            }
            
            // Get like counts
            $likeSql = "SELECT likeable_id, COUNT(DISTINCT id) as count 
                        FROM likes 
                        WHERE likeable_type = 'job' AND likeable_id IN ({$placeholders}) 
                        GROUP BY likeable_id";
            $likeCounts = $this->fetchAll($likeSql, $jobIds);
            $likeMap = [];
            foreach ($likeCounts as $count) {
                $likeMap[$count['likeable_id']] = $count['count'];
            }
            
            // Attach counts to jobs and decode JSON fields
            foreach ($jobs as &$job) {
                $job['applications_count'] = $appMap[$job['id']] ?? 0;
                $job['comments_count'] = $commentMap[$job['id']] ?? 0;
                $job['likes_count'] = $likeMap[$job['id']] ?? 0;
                
                // Decode JSON fields (convert to arrays)
                $job['requirements'] = $this->decodeField($job['requirements'] ?? null);
                $job['responsibilities'] = $this->decodeField($job['responsibilities'] ?? null);
            }
        }

        return [
            'data' => $jobs,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Count how many jobs a user has saved.
     * 
     * @param int $userId
     * @return int
     */
    public function countUserSavedJobs(int $userId): int
    {
        $sql = "SELECT COUNT(DISTINCT id) as total FROM {$this->table} WHERE user_id = :user_id";
        $result = $this->fetchOne($sql, [':user_id' => $userId]);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Get the IDs of users who saved a particular job.
     * 
     * @param int $jobId
     * @return array List of user IDs
     */
    public function getUsersWhoSavedJob(int $jobId): array
    {
        $sql = "SELECT DISTINCT user_id FROM {$this->table} WHERE job_id = :job_id";
        $rows = $this->fetchAll($sql, [':job_id' => $jobId]);
        return array_column($rows, 'user_id');
    }

    /**
     * Get saved job IDs with pagination (for API).
     * 
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getSavedJobIdsPaginated(int $userId, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT DISTINCT job_id FROM {$this->table} 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC";
        
        $limit = max(0, min(1000, $limit));
        $offset = max(0, $offset);
        $sql .= $this->formatLimitOffset($limit, $offset);
        
        $rows = $this->fetchAll($sql, [':user_id' => $userId]);
        return array_column($rows, 'job_id');
    }

    /**
     * Check if multiple jobs are saved at once (batch check).
     * 
     * @param int $userId
     * @param array $jobIds
     * @return array Array of job IDs that are saved
     */
    public function getSavedStatusBatch(int $userId, array $jobIds): array
    {
        if (empty($jobIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
        $sql = "SELECT DISTINCT job_id FROM {$this->table} 
                WHERE user_id = ? AND job_id IN ({$placeholders})";
        
        $params = array_merge([$userId], $jobIds);
        $rows = $this->fetchAll($sql, $params);
        
        return array_column($rows, 'job_id');
    }

    /**
     * Get saved jobs count by job (for job popularity).
     * 
     * @param int $jobId
     * @return int
     */
    public function getSaveCountByJob(int $jobId): int
    {
        $sql = "SELECT COUNT(DISTINCT id) as total FROM {$this->table} WHERE job_id = :job_id";
        $result = $this->fetchOne($sql, [':job_id' => $jobId]);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Get multiple jobs save counts at once (batch).
     * 
     * @param array $jobIds
     * @return array Array of [job_id => count]
     */
    public function getSaveCountsBatch(array $jobIds): array
    {
        if (empty($jobIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
        $sql = "SELECT job_id, COUNT(DISTINCT id) as count 
                FROM {$this->table} 
                WHERE job_id IN ({$placeholders}) 
                GROUP BY job_id";
        
        $results = $this->fetchAll($sql, $jobIds);
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['job_id']] = (int) $row['count'];
        }
        
        // Fill in missing job IDs with 0
        foreach ($jobIds as $jobId) {
            if (!isset($counts[$jobId])) {
                $counts[$jobId] = 0;
            }
        }
        
        return $counts;
    }

    /**
     * Delete all saved jobs for a user.
     * 
     * @param int $userId
     * @return bool
     */
    public function deleteAllForUser(int $userId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";
        return $this->execute($sql, [':user_id' => $userId]);
    }

    /**
     * Delete all saved jobs for a specific job (when job is deleted).
     * 
     * @param int $jobId
     * @return bool
     */
    public function deleteAllForJob(int $jobId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE job_id = :job_id";
        return $this->execute($sql, [':job_id' => $jobId]);
    }

    /**
     * Get most saved jobs (popular jobs).
     * 
     * @param int $limit
     * @return array
     */
    public function getMostSavedJobs(int $limit = 10): array
    {
        $sql = "SELECT j.id, j.title, j.slug, COUNT(DISTINCT sj.id) as save_count
                FROM jobs j
                INNER JOIN {$this->table} sj ON j.id = sj.job_id
                WHERE j.status = 'published'
                GROUP BY j.id
                ORDER BY save_count DESC";
        
        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);
        
        return $this->fetchAll($sql);
    }

    /**
     * Decode a field that might be JSON or plain text.
     *
     * @param mixed $field
     * @return array
     */
    private function decodeField($field): array
    {
        if (empty($field)) {
            return [];
        }

        // Attempt JSON decode
        $decoded = json_decode($field, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // If it's not valid JSON, treat as plain text and split by newlines
        if (strpos($field, "\n") !== false) {
            $lines = preg_split('/\r\n|\r|\n/', $field);
            $lines = array_filter(array_map('trim', $lines), 'strlen');
            return array_values($lines);
        }

        // Single string, return as one-element array
        return [$field];
    }
}
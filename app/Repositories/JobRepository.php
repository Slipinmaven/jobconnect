<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;
use PDOException;

/**
 * JobRepository - Manages all job-related database operations
 * 
 * Handles:
 * - Job CRUD operations
 * - Job search and filtering
 * - Job statistics
 * - Job publishing status
 */
class JobRepository extends BaseRepository
{
    protected string $table = 'jobs';
    protected string $primaryKey = 'id';

    // Job status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED = 'closed';

    // Job type constants
    public const TYPE_FULL_TIME = 'full_time';
    public const TYPE_PART_TIME = 'part_time';
    public const TYPE_CONTRACT = 'contract';
    public const TYPE_INTERNSHIP = 'internship';
    public const TYPE_TEMPORARY = 'temporary';

    // Experience level constants
    public const EXP_ENTRY = 'entry';
    public const EXP_JUNIOR = 'junior';
    public const EXP_MID = 'mid';
    public const EXP_SENIOR = 'senior';
    public const EXP_LEAD = 'lead';

    /**
     * Find job by slug
     */
    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT DISTINCT j.*, e.company_name, e.employer_type, e.avatar,
                       l.city, l.country
                FROM {$this->table} j
                LEFT JOIN employer_profiles e ON j.employer_id = e.id
                LEFT JOIN locations l ON j.location_id = l.id
                WHERE j.slug = :slug AND j.status = :status
                GROUP BY j.id
                LIMIT 1";

        return $this->fetchOne($sql, [
            ':slug' => $slug,
            ':status' => self::STATUS_PUBLISHED
        ]);
    }

    /**
     * Get published jobs with filters
     * OPTIMIZED: Removed subqueries that cause duplicates
     */
    public function getPublishedJobs(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        // First get base job data
        $sql = "SELECT DISTINCT j.*, e.company_name, e.employer_type, e.avatar,
                       l.city, l.country
                FROM {$this->table} j
                LEFT JOIN employer_profiles e ON j.employer_id = e.id
                LEFT JOIN locations l ON j.location_id = l.id
                WHERE j.status = :status";

        $params = [':status' => self::STATUS_PUBLISHED];

        // Apply filters
        if (!empty($filters['search'])) {
            $sql .= " AND (j.title LIKE :search OR j.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['job_type'])) {
            $sql .= " AND j.job_type = :job_type";
            $params[':job_type'] = $filters['job_type'];
        }

        if (!empty($filters['experience_level'])) {
            $sql .= " AND j.experience_level = :experience_level";
            $params[':experience_level'] = $filters['experience_level'];
        }

        if (!empty($filters['location'])) {
            $sql .= " AND (l.city LIKE :location OR l.country LIKE :location)";
            $params[':location'] = '%' . $filters['location'] . '%';
        }

        if (isset($filters['is_remote']) && $filters['is_remote']) {
            $sql .= " AND j.is_remote = 1";
        }

        if (!empty($filters['salary_min'])) {
            $sql .= " AND j.salary_max >= :salary_min";
            $params[':salary_min'] = $filters['salary_min'];
        }

        // Group by to prevent duplicates from multiple locations/employer matches
        $sql .= " GROUP BY j.id";

        // Order by
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        $sql .= " ORDER BY j.{$orderBy} {$orderDir}";
        $sql .= $this->formatLimitOffset($limit, $offset);

        $jobs = $this->fetchAll($sql, $params);

        // Fetch counts separately to avoid cartesian products
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
            
            // Get save counts
            $saveSql = "SELECT job_id, COUNT(DISTINCT id) as count 
                        FROM saved_jobs 
                        WHERE job_id IN ({$placeholders}) 
                        GROUP BY job_id";
            $saveCounts = $this->fetchAll($saveSql, $jobIds);
            $saveMap = [];
            foreach ($saveCounts as $count) {
                $saveMap[$count['job_id']] = $count['count'];
            }
            
            // Attach counts to jobs
            foreach ($jobs as &$job) {
                $job['applications_count'] = $appMap[$job['id']] ?? 0;
                $job['comments_count'] = $commentMap[$job['id']] ?? 0;
                $job['likes_count'] = $likeMap[$job['id']] ?? 0;
                $job['saves_count'] = $saveMap[$job['id']] ?? 0;
            }
        }

        return $jobs;
    }

    /**
     * Count published jobs with filters
     */
    public function countPublishedJobs(array $filters = []): int
    {
        $sql = "SELECT COUNT(DISTINCT j.id) as total FROM {$this->table} j
                LEFT JOIN locations l ON j.location_id = l.id
                WHERE j.status = :status";

        $params = [':status' => self::STATUS_PUBLISHED];

        if (!empty($filters['search'])) {
            $sql .= " AND (j.title LIKE :search OR j.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['job_type'])) {
            $sql .= " AND j.job_type = :job_type";
            $params[':job_type'] = $filters['job_type'];
        }

        if (!empty($filters['experience_level'])) {
            $sql .= " AND j.experience_level = :experience_level";
            $params[':experience_level'] = $filters['experience_level'];
        }

        $result = $this->fetchOne($sql, $params);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Get job by ID with decoded fields
     */
    public function getJobById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $job = $this->fetchOne($sql, [':id' => $id]);

        if (!$job) {
            return null;
        }

        // Decode requirements and responsibilities
        $job['requirements'] = $this->decodeField($job['requirements'] ?? null);
        $job['responsibilities'] = $this->decodeField($job['responsibilities'] ?? null);

        return $job;
    }

    /**
     * Decode a field that might be JSON or plain text (newline-separated)
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

    /**
     * Encode a field for storage (array -> JSON string)
     */
    private function encodeField($field): ?string
    {
        if (empty($field)) {
            return null; // store NULL in DB
        }

        if (is_array($field)) {
            return json_encode($field, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // If it's already a string, return as-is (though we expect arrays)
        return $field;
    }

    /**
     * Get jobs by employer
     */
    public function getEmployerJobs(int $employerId, ?array $status = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT DISTINCT j.*, 
                       (SELECT COUNT(DISTINCT id) FROM applications WHERE job_id = j.id) as applications_count
                FROM {$this->table} j
                WHERE j.employer_id = :employer_id";

        $params = [':employer_id' => $employerId];

        if ($status !== null && !empty($status)) {
            $placeholders = implode(',', array_fill(0, count($status), '?'));
            $sql .= " AND j.status IN ({$placeholders})";
            $params = array_merge($params, $status);
        }

        $sql .= " GROUP BY j.id ORDER BY j.created_at DESC";
        $sql .= $this->formatLimitOffset($limit, $offset);

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count employer jobs
     */
    public function countEmployerJobs(int $employerId, ?array $status = null): int
    {
        $sql = "SELECT COUNT(DISTINCT id) as total FROM {$this->table} WHERE employer_id = :employer_id";
        $params = [':employer_id' => $employerId];

        if ($status !== null && !empty($status)) {
            $placeholders = implode(',', array_fill(0, count($status), '?'));
            $sql .= " AND status IN ({$placeholders})";
            $params = array_merge($params, $status);
        }

        $result = $this->fetchOne($sql, $params);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Create a new job
     */
    public function createJob(array $data): ?int
    {
        try {
            $this->beginTransaction();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['title']);
            }

            // Set default values
            $data['status'] = $data['status'] ?? self::STATUS_DRAFT;
            $data['views_count'] = 0;

            // Encode requirements and responsibilities
            $data['requirements'] = $this->encodeField($data['requirements'] ?? null);
            $data['responsibilities'] = $this->encodeField($data['responsibilities'] ?? null);

            error_log('Job data prepared for creation: ' . json_encode(['job_data' => $data]));

            $jobId = $this->create($data);

            $this->commit();
            return $jobId;

        } catch (PDOException $e) {
            $this->rollback();
            error_log("Failed to create job: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update job
     */
    public function updateJob(int $jobId, array $data): bool
    {
        try {
            // Regenerate slug if title changed
            if (isset($data['title'])) {
                $existing = $this->findById($jobId);
                if ($existing && $existing['title'] !== $data['title']) {
                    $data['slug'] = $this->generateSlug($data['title'], $jobId);
                }
            }

            // Remove categories if present (not stored in jobs table)
            unset($data['categories']);

            // Encode requirements and responsibilities before updating
            if (isset($data['requirements'])) {
                $data['requirements'] = $this->encodeField($data['requirements']);
            }
            if (isset($data['responsibilities'])) {
                $data['responsibilities'] = $this->encodeField($data['responsibilities']);
            }

            return $this->update($jobId, $data);

        } catch (PDOException $e) {
            error_log("Failed to update job: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Publish a job
     */
    public function publishJob(int $jobId): bool
    {
        return $this->update($jobId, [
            'status' => self::STATUS_PUBLISHED,
            'posted_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Close a job
     */
    public function closeJob(int $jobId): bool
    {
        return $this->update($jobId, [
            'status' => self::STATUS_CLOSED,
            'expires_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Reopen a closed job
     */
    public function reopenJob(int $jobId): bool
    {
        return $this->update($jobId, [
            'status' => self::STATUS_PUBLISHED,
            'expires_at' => null
        ]);
    }

    /**
     * Increment view count
     */
    public function incrementViews(int $jobId): void
    {
        $this->execute(
            "UPDATE {$this->table} SET views_count = views_count + 1 WHERE id = :id",
            [':id' => $jobId]
        );
    }

    /**
     * Search jobs with full-text matching on title, description, company, etc.
     * OPTIMIZED for InfinityFree - removed heavy subqueries
     *
     * @param string $query   Search term
     * @param array|null $filters (ignored in this version, but kept for future use)
     * @param int $limit       Results per page
     * @param int $offset      Pagination offset
     * @return array
     */
    public function searchJobs(string $query, ?array $filters = [], int $limit = 20, int $offset = 0): array
    {
        // Allowed statuses: published and closed
        $statuses = [self::STATUS_PUBLISHED, self::STATUS_CLOSED];
        $statusPlaceholders = implode(',', array_fill(0, count($statuses), '?'));

        $sql = "SELECT DISTINCT j.*, e.company_name, e.employer_type, e.avatar,
                       l.city, l.country
                FROM {$this->table} j
                LEFT JOIN employer_profiles e ON j.employer_id = e.id
                LEFT JOIN locations l ON j.location_id = l.id
                WHERE j.status IN ($statusPlaceholders)
                  AND (j.title LIKE ? OR j.description LIKE ? OR e.company_name LIKE ? 
                       OR j.job_type LIKE ? OR j.experience_level LIKE ? 
                       OR j.salary_min LIKE ? OR j.salary_max LIKE ? 
                       OR l.city LIKE ? OR l.country LIKE ?)
                GROUP BY j.id
                ORDER BY j.created_at DESC
                LIMIT ? OFFSET ?";

        // Build parameters in the exact order of the placeholders:
        $likePattern = "%$query%";
        $params = array_merge(
            $statuses,
            array_fill(0, 9, $likePattern),
            [(int) $limit, (int) $offset]
        );

        $jobs = $this->fetchAll($sql, $params);

        if ($jobs) {
            // Fetch counts separately
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
            
            foreach ($jobs as &$job) {
                $job['applications_count'] = $appMap[$job['id']] ?? 0;
                $job['requirements'] = $this->decodeField($job['requirements'] ?? null);
                $job['responsibilities'] = $this->decodeField($job['responsibilities'] ?? null);
            }
        }

        return $jobs ?? [];
    }

    /**
     * Count total search results for pagination.
     *
     * @param string $query
     * @param array|null $filters
     * @return int
     */
    public function countSearchResults(string $query, ?array $filters = []): int
    {
        $statuses = [self::STATUS_PUBLISHED, self::STATUS_CLOSED];
        $statusPlaceholders = implode(',', array_fill(0, count($statuses), '?'));

        $sql = "SELECT COUNT(DISTINCT j.id) as total
                FROM {$this->table} j
                LEFT JOIN employer_profiles e ON j.employer_id = e.id
                LEFT JOIN locations l ON j.location_id = l.id
                WHERE j.status IN ($statusPlaceholders)
                  AND (j.title LIKE ? OR j.description LIKE ? OR e.company_name LIKE ? 
                       OR j.job_type LIKE ? OR j.experience_level LIKE ? 
                       OR j.salary_min LIKE ? OR j.salary_max LIKE ? 
                       OR l.city LIKE ? OR l.country LIKE ?)";

        $likePattern = "%$query%";
        $params = array_merge($statuses, array_fill(0, 9, $likePattern));

        $result = $this->fetchOne($sql, $params);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Get featured jobs (most active)
     */
    public function getFeaturedJobs(int $limit = 6): array
    {
        $sql = "SELECT DISTINCT j.*, e.company_name, e.employer_type,
                       (SELECT COUNT(DISTINCT id) FROM applications WHERE job_id = j.id) as applications_count
                FROM {$this->table} j
                LEFT JOIN employer_profiles e ON j.employer_id = e.user_id
                WHERE j.status = :status 
                AND j.posted_at IS NOT NULL
                GROUP BY j.id
                ORDER BY 
                    (SELECT COUNT(DISTINCT id) FROM applications WHERE job_id = j.id) DESC,
                    j.views_count DESC,
                    j.created_at DESC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql, [
            ':status' => self::STATUS_PUBLISHED
        ]);
    }

    /**
     * Get similar jobs (based on title, industry, location)
     * OPTIMIZED: Simplified to prevent heavy queries
     */
    public function getSimilarJobs(int $jobId, int $limit = 5): array
    {
        $job = $this->findById($jobId);

        if (!$job) {
            return [];
        }

        // Extract keywords from title
        $keywords = explode(' ', $job['title']);
        $titleConditions = [];
        $params = [':status' => self::STATUS_PUBLISHED, ':job_id' => $jobId];

        foreach ($keywords as $index => $keyword) {
            if (strlen($keyword) > 3 && $index < 3) { // Limit to first 3 keywords
                $titleConditions[] = "j.title LIKE :keyword{$index}";
                $params[":keyword{$index}"] = '%' . $keyword . '%';
            }
        }

        $titleSql = !empty($titleConditions) ? " OR " . implode(' OR ', $titleConditions) : "";

        $sql = "SELECT DISTINCT j.*, e.company_name, e.employer_type
                FROM {$this->table} j
                LEFT JOIN employer_profiles e ON j.employer_id = e.user_id
                WHERE j.status = :status 
                AND j.id != :job_id
                AND (j.experience_level = :exp_level OR j.job_type = :job_type{$titleSql})
                GROUP BY j.id
                ORDER BY j.created_at DESC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        $params[':exp_level'] = $job['experience_level'];
        $params[':job_type'] = $job['job_type'];

        return $this->fetchAll($sql, $params);
    }

    /**
     * Get job statistics
     */
    public function getJobStatistics(int $employerId): array
    {
        // Get job stats
        $jobSql = "SELECT 
                COUNT(DISTINCT id) as total_jobs,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as active_jobs,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_jobs,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_jobs,
                COALESCE(SUM(views_count), 0) as total_views
            FROM {$this->table}
            WHERE employer_id = :employer_id";

        $jobStats = $this->fetchOne($jobSql, [':employer_id' => $employerId]) ?: [];

        // Get applications count separately (more readable)
        $appSql = "SELECT COUNT(DISTINCT a.id) as total_applications 
               FROM applications a 
               WHERE a.job_id IN (SELECT id FROM jobs WHERE employer_id = :employer_id)";

        $appStats = $this->fetchOne($appSql, [':employer_id' => $employerId]) ?: [];

        return [
            'total_jobs' => (int) ($jobStats['total_jobs'] ?? 0),
            'active_jobs' => (int) ($jobStats['active_jobs'] ?? 0),
            'draft_jobs' => (int) ($jobStats['draft_jobs'] ?? 0),
            'closed_jobs' => (int) ($jobStats['closed_jobs'] ?? 0),
            'total_views' => (int) ($jobStats['total_views'] ?? 0),
            'total_applications' => (int) ($appStats['total_applications'] ?? 0)
        ];
    }

    /**
     * Generate unique slug
     */
    private function generateSlug(string $title, ?int $excludeId = null): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = preg_replace('/-+/', '-', $slug);

        $originalSlug = $slug;
        $counter = 1;

        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE slug = :slug";
        if ($excludeId) {
            $sql .= " AND id != :id";
        }

        while (true) {
            $params = [':slug' => $slug];
            if ($excludeId) {
                $params[':id'] = $excludeId;
            }
            $result = $this->fetchOne($sql, $params);
            if (($result['count'] ?? 0) == 0) {
                break;
            }
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
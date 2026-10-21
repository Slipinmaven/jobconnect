<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;
use PDOException;

/**
 * ApplicationRepository - Manages all job application operations
 * 
 * Handles:
 * - Application CRUD operations
 * - Application status management
 * - Application filtering and search
 * - Employer notes and review tracking
 */
class ApplicationRepository extends BaseRepository
{
    protected string $table = 'applications';
    protected string $primaryKey = 'id';

    // Application status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_SHORTLISTED = 'shortlisted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ACCEPTED = 'accepted';

    /**
     * Apply for a job
     */
    public function apply(int $jobId, int $userId, ?string $coverLetter = null, ?string $resumePath = null): ?int
    {
        try {
            // Check if already applied
            $existing = $this->findByUserAndJob($userId, $jobId);
            if ($existing) {
                return null;
            }

            $data = [
                'job_id' => $jobId,
                'user_id' => $userId,
                'cover_letter' => $coverLetter,
                'resume_path' => $resumePath,
                'status' => self::STATUS_PENDING,
                'applied_at' => date('Y-m-d H:i:s')
            ];

            return $this->create($data);

        } catch (PDOException $e) {
            error_log("Failed to create application: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find application by user and job
     */
    public function findByUserAndJob(int $userId, int $jobId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND job_id = :job_id LIMIT 1";
        return $this->fetchOne($sql, [':user_id' => $userId, ':job_id' => $jobId]);
    }

    /**
     * Get applications by user
     */
    public function getUserApplications(int $userId, ?array $status = null, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT DISTINCT a.*, j.title, j.slug, j.job_type, j.experience_level, j.salary_min, j.salary_max,
                       e.id as employer_id, e.company_name, e.employer_type, e.avatar as company_avatar
                FROM {$this->table} a
                JOIN jobs j ON a.job_id = j.id
                LEFT JOIN employer_profiles e ON j.employer_id = e.id
                WHERE a.user_id = :user_id";

        $params = [':user_id' => $userId];

        if ($status !== null && !empty($status)) {
            $placeholders = implode(',', array_fill(0, count($status), '?'));
            $sql .= " AND a.status IN ({$placeholders})";
            $params = array_merge($params, $status);
        }

        $sql .= " GROUP BY a.id ORDER BY a.created_at DESC";
        $sql .= $this->formatLimitOffset($limit, $offset);

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count user applications
     */
    public function countUserApplications(int $userId, ?array $status = null): int
    {
        $sql = "SELECT COUNT(DISTINCT a.id) as total FROM {$this->table} a WHERE a.user_id = :user_id";
        $params = [':user_id' => $userId];

        if ($status !== null && !empty($status)) {
            $placeholders = implode(',', array_fill(0, count($status), '?'));
            $sql .= " AND a.status IN ({$placeholders})";
            $params = array_merge($params, $status);
        }

        $result = $this->fetchOne($sql, $params);
        return (int) ($result['total'] ?? 0);
    }

    public function getEmployerApplications(int $userId, ?array $status = null, ?int $limit = null, ?int $offset = null): array
    {
        $sql = "SELECT DISTINCT a.*, j.title as job_title, j.slug as job_slug,
                       u.email, js.first_name, js.last_name, js.professional_title,
                       js.resume_url as profile_resume_url
                FROM {$this->table} a
                JOIN jobs j ON a.job_id = j.id
                JOIN users u ON a.user_id = u.id
                LEFT JOIN jobseeker_profiles js ON u.id = js.user_id
                WHERE j.posted_by = :employer_id";

        $params = [':employer_id' => $userId];

        // Handle status filter with named placeholders
        if (!empty($status)) {
            $statusPlaceholders = [];
            foreach ($status as $i => $value) {
                $key = ":status$i";
                $statusPlaceholders[] = $key;
                $params[$key] = $value;
            }
            $sql .= " AND a.status IN (" . implode(',', $statusPlaceholders) . ")";
        }

        $sql .= " GROUP BY a.id ORDER BY a.created_at DESC";

        if ($limit !== null || $offset !== null) {
            $sql .= $this->formatLimitOffset($limit ?? 0, $offset ?? 0);
        }

        return $this->fetchAll($sql, $params);
    }

    public function countEmployerApplications(int $userId, ?array $status = null): int
    {
        $sql = "SELECT COUNT(DISTINCT a.id) as total FROM {$this->table} a
                JOIN jobs j ON a.job_id = j.id
                WHERE j.employer_id = :employer_id";

        $params = [':employer_id' => $userId];

        if ($status !== null && !empty($status)) {
            $placeholders = implode(',', array_fill(0, count($status), '?'));
            $sql .= " AND a.status IN ({$placeholders})";
            $params = array_merge($params, $status);
        }

        $result = $this->fetchOne($sql, $params);
        return (int) ($result['total'] ?? 0);
    }
    
    /**
     * Get applications for a job (for employers)
     */
    public function getJobApplications(int $jobId, ?array $status = null, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT DISTINCT a.*, u.email,
                       js.first_name, js.last_name, js.professional_title,
                       js.resume_url as profile_resume_url
                FROM {$this->table} a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN jobseeker_profiles js ON u.id = js.user_id
                WHERE a.job_id = :job_id";

        $params = [':job_id' => $jobId];

        if ($status !== null && !empty($status)) {
            $placeholders = implode(',', array_fill(0, count($status), '?'));
            $sql .= " AND a.status IN ({$placeholders})";
            $params = array_merge($params, $status);
        }

        $sql .= " GROUP BY a.id ORDER BY 
                    CASE a.status 
                        WHEN 'pending' THEN 1 
                        WHEN 'reviewed' THEN 2 
                        WHEN 'shortlisted' THEN 3 
                        WHEN 'accepted' THEN 4 
                        WHEN 'rejected' THEN 5 
                    END,
                    a.created_at DESC";
        $sql .= $this->formatLimitOffset($limit, $offset);

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count job applications
     */
    public function countJobApplications(int $jobId, ?array $status = null): int
    {
        $sql = "SELECT COUNT(DISTINCT a.id) as total FROM {$this->table} a WHERE a.job_id = :job_id";
        $params = [':job_id' => $jobId];

        if ($status !== null && !empty($status)) {
            $placeholders = implode(',', array_fill(0, count($status), '?'));
            $sql .= " AND a.status IN ({$placeholders})";
            $params = array_merge($params, $status);
        }

        $result = $this->fetchOne($sql, $params);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Update application status
     */
    public function updateStatus(int $applicationId, string $status, ?string $employerNotes = null): ?array
    {
        $data = ['status' => $status];

        if ($employerNotes !== null) {
            $data['employer_notes'] = $employerNotes;
        }

        // Set reviewed_at only if status is reviewed and it's not already set
        if ($status === self::STATUS_REVIEWED) {
            $current = $this->findById($applicationId);
            if ($current && empty($current['reviewed_at'])) {
                $data['reviewed_at'] = date('Y-m-d H:i:s');
            }
        }

        $updated = $this->update($applicationId, $data);
        if (!$updated) {
            return null;
        }

        return $this->getApplicationWithDetails($applicationId);
    }
    
    /**
     * Mark application as viewed
     */
    public function markAsViewed(int $applicationId): bool
    {
        $sql = "UPDATE {$this->table} SET viewed_at = NOW() WHERE id = :id AND viewed_at IS NULL";
        return $this->execute($sql, [':id' => $applicationId]);
    }

    /**
     * Withdraw application
     */
    public function withdraw(int $applicationId, int $userId): bool
    {
        $sql = "UPDATE {$this->table} SET status = 'withdrawn', withdrawn_at = NOW() 
                WHERE id = :id AND user_id = :user_id AND status = 'pending'";
        return $this->execute($sql, [':id' => $applicationId, ':user_id' => $userId]);
    }

    /**
     * Get application statistics for a job
     */
    public function getJobApplicationStats(int $jobId): array
    {
        $sql = "SELECT 
                    COUNT(DISTINCT a.id) as total,
                    SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN a.status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
                    SUM(CASE WHEN a.status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted,
                    SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                    SUM(CASE WHEN a.viewed_at IS NOT NULL THEN 1 ELSE 0 END) as viewed,
                    SUM(CASE WHEN a.viewed_at IS NULL THEN 1 ELSE 0 END) as unviewed
                FROM {$this->table} a
                WHERE a.job_id = :job_id";

        return $this->fetchOne($sql, [':job_id' => $jobId]) ?: [];
    }

    /**
     * Get applications by status for employer
     */
    public function getEmployerApplicationsByStatus(int $employerId, string $status, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT DISTINCT a.*, j.title as job_title, j.slug as job_slug,
                       u.email, js.first_name, js.last_name, js.professional_title
                FROM {$this->table} a
                JOIN jobs j ON a.job_id = j.id
                JOIN users u ON a.user_id = u.id
                LEFT JOIN jobseeker_profiles js ON u.id = js.user_id
                WHERE j.employer_id = :employer_id AND a.status = :status
                GROUP BY a.id
                ORDER BY a.created_at DESC";

        $limit = max(0, min(1000, $limit));
        $offset = max(0, $offset);
        $sql .= $this->formatLimitOffset($limit, $offset);

        return $this->fetchAll($sql, [
            ':employer_id' => $employerId,
            ':status' => $status
        ]);
    }

    /**
     * Get recent applications for employer (last 30 days)
     */
    public function getRecentEmployerApplications(int $employerId, int $days = 30): array
    {
        $sql = "SELECT DISTINCT a.*, j.title as job_title, u.email,
                       js.first_name, js.last_name
                FROM {$this->table} a
                JOIN jobs j ON a.job_id = j.id
                JOIN users u ON a.user_id = u.id
                LEFT JOIN jobseeker_profiles js ON u.id = js.user_id
                WHERE j.employer_id = :employer_id 
                AND a.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY a.id
                ORDER BY a.created_at DESC
                LIMIT 100";

        return $this->fetchAll($sql, [
            ':employer_id' => $employerId,
            ':days' => $days
        ]);
    }

    /**
     * Check if user has already applied to a job
     */
    public function hasApplied(int $userId, int $jobId): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE user_id = :user_id AND job_id = :job_id LIMIT 1";
        $result = $this->fetchOne($sql, [':user_id' => $userId, ':job_id' => $jobId]);
        return !empty($result);
    }

    /**
     * Get application by ID with job details
     */
    public function getApplicationWithDetails(int $applicationId): ?array
    {
        $sql = "SELECT DISTINCT a.*, j.title as job_title, j.slug as job_slug, j.employer_id,
                       e.company_name, e.employer_type,
                       u.email, js.first_name, js.last_name, js.professional_title, js.resume_url
                FROM {$this->table} a
                JOIN jobs j ON a.job_id = j.id
                JOIN users u ON a.user_id = u.id
                LEFT JOIN employer_profiles e ON j.employer_id = e.user_id
                LEFT JOIN jobseeker_profiles js ON u.id = js.user_id
                WHERE a.id = :id
                GROUP BY a.id";

        return $this->fetchOne($sql, [':id' => $applicationId]);
    }
}
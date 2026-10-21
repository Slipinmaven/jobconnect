<?php

namespace App\Repositories;

use App\Core\BaseRepository;

class InterviewRepository extends BaseRepository
{
    protected string $table = 'interviews';
    protected string $primaryKey = 'id';

    /**
     * Create a new interview.
     *
     * @param array $data
     * @return int|null The new ID or null
     */
    public function create(array $data): ?int
    {
        // Ensure required fields are present
        $required = ['application_id', 'scheduled_by', 'scheduled_at', 'interview_type'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return null;
            }
        }

        // Add default duration if not set
        if (!isset($data['duration_minutes'])) {
            $data['duration_minutes'] = 30;
        }

        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'scheduled';
        }

        // Let BaseRepository handle timestamps
        return parent::create($data);
    }

    /**
     * Update an interview.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        // Prevent changing application_id or scheduled_by
        unset($data['application_id'], $data['scheduled_by']);

        return parent::update($id, $data);
    }

    /**
     * Find all interviews for a specific application.
     *
     * @param int $applicationId
     * @return array
     */
    public function findByApplication(int $applicationId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE application_id = :app_id ORDER BY scheduled_at DESC";
        return $this->fetchAll($sql, [':app_id' => $applicationId]);
    }

    /**
     * Find the latest scheduled interview for an application.
     *
     * @param int $applicationId
     * @return array|null
     */
    public function findLatestByApplication(int $applicationId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE application_id = :app_id ORDER BY scheduled_at DESC LIMIT 1";
        return $this->fetchOne($sql, [':app_id' => $applicationId]);
    }

    /**
     * Find all interviews scheduled by a specific employer.
     *
     * @param int $employerId
     * @return array
     */
    public function findByEmployer(int $employerId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE scheduled_by = :emp_id ORDER BY scheduled_at DESC";
        return $this->fetchAll($sql, [':emp_id' => $employerId]);
    }

    /**
     * Find upcoming interviews (scheduled_at > now and status = 'scheduled').
     *
     * @param int|null $employerId Optional filter by employer
     * @return array
     */
    public function findUpcoming(?int $employerId = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE scheduled_at > NOW() AND status = 'scheduled'";
        $params = [];

        if ($employerId !== null) {
            $sql .= " AND scheduled_by = :emp_id";
            $params[':emp_id'] = $employerId;
        }

        $sql .= " ORDER BY scheduled_at ASC";

        return $this->fetchAll($sql, $params);
    }

    /**
     * Find past interviews (scheduled_at <= now).
     *
     * @param int|null $employerId Optional filter by employer
     * @return array
     */
    public function findPast(?int $employerId = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE scheduled_at <= NOW()";
        $params = [];

        if ($employerId !== null) {
            $sql .= " AND scheduled_by = :emp_id";
            $params[':emp_id'] = $employerId;
        }

        $sql .= " ORDER BY scheduled_at DESC";

        return $this->fetchAll($sql, $params);
    }

    /**
     * Check if an application already has a scheduled interview (future).
     *
     * @param int $applicationId
     * @return bool
     */
    public function hasScheduledInterview(int $applicationId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE application_id = :app_id 
                AND status = 'scheduled' 
                AND scheduled_at > NOW()";

        $result = $this->fetchOne($sql, [':app_id' => $applicationId]);

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Cancel an interview (update status to 'cancelled').
     *
     * @param int $id
     * @param string|null $reason Optional cancellation reason to append to notes
     * @return bool
     */
    public function cancel(int $id, ?string $reason = null): bool
    {
        $interview = $this->findById($id);
        if (!$interview) {
            return false;
        }

        $data = ['status' => 'cancelled'];

        if ($reason) {
            $currentNotes = $interview['notes'] ?? '';
            $data['notes'] = trim($currentNotes . "\n[Cancelled: $reason]");
        }

        return $this->update($id, $data);
    }

    /**
     * Find interviews for a jobseeker with all related details.
     * FIXED: Added DISTINCT and GROUP BY to prevent duplicates
     *
     * @param int $userId
     * @return array
     */
    public function findByJobseeker(int $userId): array
    {
        $sql = "SELECT DISTINCT i.*, a.job_id, j.title as job_title, 
                       ep.company_name as employer_company_name, 
                       ep.contact_email as employer_contact_email, 
                       ep.contact_phone as employer_contact_phone, 
                       ep.employer_type as employer_type, 
                       ep.avatar as employer_avatar
                FROM {$this->table} i
                INNER JOIN applications a ON i.application_id = a.id
                INNER JOIN jobs j ON a.job_id = j.id
                INNER JOIN users u ON i.scheduled_by = u.id
                LEFT JOIN employer_profiles ep ON i.scheduled_by = ep.user_id
                WHERE a.user_id = :user_id
                GROUP BY i.id
                ORDER BY i.scheduled_at DESC";
        return $this->fetchAll($sql, [':user_id' => $userId]);
    }

    /**
     * Count interviews by status for a jobseeker.
     * FIXED: Uses COUNT(DISTINCT) for accurate counts
     *
     * @param int $userId
     * @return array
     */
    public function countByStatusForJobseeker(int $userId): array
    {
        $sql = "SELECT i.status, COUNT(DISTINCT i.id) as count 
                FROM {$this->table} i
                INNER JOIN applications a ON i.application_id = a.id
                WHERE a.user_id = :user_id
                GROUP BY i.status";

        $results = $this->fetchAll($sql, [':user_id' => $userId]);

        $counts = ['scheduled' => 0, 'completed' => 0, 'cancelled' => 0];
        foreach ($results as $row) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int)$row['count'];
            }
        }
        return $counts;
    }

    /**
     * Mark interview as completed.
     *
     * @param int $id
     * @return bool
     */
    public function complete(int $id): bool
    {
        return $this->update($id, ['status' => 'completed']);
    }

    /**
     * Delete an interview.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return parent::delete($id);
    }

    /**
     * Count interviews by status for a given employer.
     * FIXED: Uses COUNT(DISTINCT) for accurate counts
     *
     * @param int $employerId
     * @return array ['scheduled' => count, 'completed' => count, 'cancelled' => count]
     */
    public function countByStatusForEmployer(int $employerId): array
    {
        $sql = "SELECT status, COUNT(DISTINCT id) as count 
                FROM {$this->table} 
                WHERE scheduled_by = :emp_id 
                GROUP BY status";

        $results = $this->fetchAll($sql, [':emp_id' => $employerId]);

        $counts = ['scheduled' => 0, 'completed' => 0, 'cancelled' => 0];
        foreach ($results as $row) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int)$row['count'];
            }
        }

        return $counts;
    }

    /**
     * Get interviews with application and job details (join).
     * FIXED: Added DISTINCT and GROUP BY to prevent duplicates
     *
     * @param int|null $employerId Optional filter
     * @return array
     */
    public function getInterviewsWithDetails(?int $employerId = null): array
    {
        $sql = "SELECT DISTINCT i.*, 
                       a.id as application_id, a.job_id, a.user_id, a.status as application_status, 
                       u.email as candidate_email,
                       j.title as job_title, 
                       js.first_name as candidate_first_name, 
                       js.last_name as candidate_last_name, 
                       js.professional_title as candidate_professional_title, 
                       js.avatar as candidate_avatar
                FROM {$this->table} i
                INNER JOIN applications a ON i.application_id = a.id
                INNER JOIN users u ON a.user_id = u.id
                LEFT JOIN jobs j ON a.job_id = j.id
                LEFT JOIN jobseeker_profiles js ON a.user_id = js.user_id";

        $params = [];
        if ($employerId !== null) {
            $sql .= " WHERE i.scheduled_by = :emp_id";
            $params[':emp_id'] = $employerId;
        }

        $sql .= " GROUP BY i.id ORDER BY i.scheduled_at DESC";

        return $this->fetchAll($sql, $params);
    }

    /**
     * Get interview by ID with all related details.
     * FIXED: Added DISTINCT and GROUP BY to prevent duplicates
     *
     * @param int $id
     * @return array|null
     */
    public function getByIdWithDetails(int $id): ?array
    {
        $sql = "SELECT DISTINCT i.*, 
                       a.id as application_id, a.job_id, a.user_id, a.status as application_status, 
                       u.email as candidate_email,
                       j.title as job_title, 
                       js.first_name as candidate_first_name, 
                       js.last_name as candidate_last_name, 
                       js.professional_title as candidate_professional_title, 
                       js.avatar as candidate_avatar, 
                       ep.company_name as employer_company_name, 
                       ep.contact_email as employer_contact_email, 
                       ep.contact_phone as employer_contact_phone, 
                       ep.employer_type as employer_type, 
                       ep.avatar as employer_avatar
                FROM {$this->table} i
                INNER JOIN applications a ON i.application_id = a.id
                INNER JOIN users u ON a.user_id = u.id
                LEFT JOIN jobs j ON a.job_id = j.id
                LEFT JOIN jobseeker_profiles js ON a.user_id = js.user_id
                LEFT JOIN employer_profiles ep ON i.scheduled_by = ep.user_id
                WHERE i.id = :id
                GROUP BY i.id";

        return $this->fetchOne($sql, [':id' => $id]);
    }

    /**
     * Get interviews for employer dashboard with full details.
     * OPTIMIZED for InfinityFree - produces EXACT same output but prevents 502 errors
     *
     * @param int $employerId
     * @return array
     */
    public function getEmployerInterviews(int $employerId): array
    {
        // Step 1: Get base interview data with minimal joins
        $sql = "SELECT i.*, a.job_id, a.user_id as candidate_user_id,
                       j.title as job_title
                FROM interviews i
                INNER JOIN applications a ON i.application_id = a.id
                INNER JOIN jobs j ON a.job_id = j.id
                WHERE i.scheduled_by = :employer_id
                GROUP BY i.id
                ORDER BY i.scheduled_at DESC";
        
        $interviews = $this->fetchAll($sql, [':employer_id' => $employerId]);
        
        if (empty($interviews)) {
            return [];
        }
        
        // Step 2: Get employer profile (same for all interviews)
        $employerSql = "SELECT company_name, contact_email, contact_phone, 
                               employer_type, avatar 
                        FROM employer_profiles 
                        WHERE user_id = :employer_id 
                        LIMIT 1";
        $employer = $this->fetchOne($employerSql, [':employer_id' => $employerId]);
        
        // Step 3: Get candidate profiles for unique user IDs
        $candidateIds = array_unique(array_column($interviews, 'candidate_user_id'));
        $candidates = [];
        
        if (!empty($candidateIds)) {
            $placeholders = implode(',', array_fill(0, count($candidateIds), '?'));
            $candidateSql = "SELECT user_id, first_name, last_name, email 
                             FROM jobseeker_profiles 
                             WHERE user_id IN ({$placeholders})";
            $candidateResults = $this->fetchAll($candidateSql, $candidateIds);
            
            foreach ($candidateResults as $candidate) {
                $candidates[$candidate['user_id']] = $candidate;
            }
        }
        
        // Step 4: Combine all data to match original output format
        foreach ($interviews as &$interview) {
            // Add employer data
            $interview['employer_company_name'] = $employer['company_name'] ?? null;
            $interview['employer_contact_email'] = $employer['contact_email'] ?? null;
            $interview['employer_contact_phone'] = $employer['contact_phone'] ?? null;
            $interview['employer_type'] = $employer['employer_type'] ?? null;
            $interview['employer_avatar'] = $employer['avatar'] ?? null;
            
            // Add candidate data (matches original output)
            $candidate = $candidates[$interview['candidate_user_id']] ?? null;
            if ($candidate) {
                $interview['candidate_name'] = trim(($candidate['first_name'] ?? '') . ' ' . ($candidate['last_name'] ?? ''));
                $interview['candidate_email'] = $candidate['email'] ?? null;
            } else {
                $interview['candidate_name'] = null;
                $interview['candidate_email'] = null;
            }
        }
        
        return $interviews;
    }
}
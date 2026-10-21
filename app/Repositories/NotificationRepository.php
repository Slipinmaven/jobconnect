<?php

namespace App\Repositories;

use App\Core\BaseRepository;

class NotificationRepository extends BaseRepository
{
    protected string $table = 'notifications';
    protected string $primaryKey = 'id';

    /**
     * Create a new notification.
     *
     * @param array $data Notification data (user_id, type, notifiable_id, notifiable_type, actor_id, title, message, action_url, action_key)
     * @return int|null The new notification ID or null on failure
     */
    public function createNotification(array $data): ?int
    {
        return $this->create($data);
    }

    /**
     * Get notifications for a user with pagination.
     * FIXED: Added role-based JOIN conditions to prevent duplicates
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getUserNotifications(int $userId, int $limit = 20, int $offset = 0): array
    {
        $sql = "
            SELECT 
                n.*,
                -- Actor details
                actor_user.role AS actor_role,
                CASE 
                    WHEN actor_user.role = 'employer' THEN ep.company_name
                    WHEN actor_user.role = 'jobseeker' THEN CONCAT(jp.first_name, ' ', jp.last_name)
                    ELSE actor_user.email
                END AS actor_name,
                ep.company_name AS actor_company_name, 
                ep.employer_type AS actor_employer_type, 
                COALESCE(ep.avatar, jp.avatar, actor_user.avatar) as actor_avatar,
                jp.first_name AS actor_first_name,
                jp.last_name AS actor_last_name,
                -- Recipient details (optional)
                recipient_user.role AS recipient_role
            FROM {$this->table} n
            LEFT JOIN users actor_user ON n.actor_id = actor_user.id
            LEFT JOIN employer_profiles ep ON actor_user.id = ep.user_id AND actor_user.role = 'employer'
            LEFT JOIN jobseeker_profiles jp ON actor_user.id = jp.user_id AND actor_user.role = 'jobseeker'
            LEFT JOIN users recipient_user ON n.user_id = recipient_user.id
            WHERE n.user_id = :user_id
            GROUP BY n.id
            ORDER BY n.created_at DESC
        ";

        $limit = max(0, min(1000, $limit));
        $offset = max(0, $offset);

        $sql .= $this->formatLimitOffset($limit, $offset);

        $params = [
            ':user_id' => $userId
        ];

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count total notifications for a user.
     *
     * @param int $userId
     * @return int
     */
    public function countUserNotifications(int $userId): int
    {
        $sql = "SELECT COUNT(DISTINCT id) as total FROM {$this->table} WHERE user_id = :user_id";
        $result = $this->fetchOne($sql, [':user_id' => $userId]);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Count unread notifications for a user.
     *
     * @param int $userId
     * @return int
     */
    public function countUnread(int $userId): int
    {
        $sql = "SELECT COUNT(DISTINCT id) as total FROM {$this->table}
                WHERE user_id = :user_id AND is_read = 0";
        $result = $this->fetchOne($sql, [':user_id' => $userId]);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Mark a notification as read.
     *
     * @param int $notificationId
     * @return bool
     */
    public function markAsRead(int $notificationId): bool
    {
        $sql = "UPDATE {$this->table}
                SET is_read = 1, read_at = NOW()
                WHERE id = :id";
        return $this->execute($sql, [':id' => $notificationId]);
    }

    /**
     * Mark all notifications for a user as read.
     *
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead(int $userId): bool
    {
        $sql = "UPDATE {$this->table}
                SET is_read = 1, read_at = NOW()
                WHERE user_id = :user_id AND is_read = 0";
        return $this->execute($sql, [':user_id' => $userId]);
    }

    /**
     * Get a single notification by ID (optionally with ownership check).
     *
     * @param int $id
     * @param int|null $userId If provided, only return if owned by this user
     * @return array|null
     */
    public function findById(int $id, ?int $userId = null): ?array
    {
        $sql = "SELECT n.*,
                       actor_user.role AS actor_role,
                       CASE 
                           WHEN actor_user.role = 'employer' THEN ep.company_name
                           WHEN actor_user.role = 'jobseeker' THEN CONCAT(jp.first_name, ' ', jp.last_name)
                           ELSE actor_user.email
                       END AS actor_name,
                       ep.company_name AS actor_company_name,
                       COALESCE(ep.avatar, jp.avatar, actor_user.avatar) as actor_avatar
                FROM {$this->table} n
                LEFT JOIN users actor_user ON n.actor_id = actor_user.id
                LEFT JOIN employer_profiles ep ON actor_user.id = ep.user_id AND actor_user.role = 'employer'
                LEFT JOIN jobseeker_profiles jp ON actor_user.id = jp.user_id AND actor_user.role = 'jobseeker'
                WHERE n.id = :id";
        
        $params = [':id' => $id];
        
        if ($userId !== null) {
            $sql .= " AND n.user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        $sql .= " GROUP BY n.id";
        
        return $this->fetchOne($sql, $params);
    }

    /**
     * Delete a notification (hard delete).
     *
     * @param int $id
     * @param int|null $userId Optional user ID to ensure ownership
     * @return bool
     */
    public function deleteNotification(int $id, ?int $userId = null): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $params = [':id' => $id];
        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        return $this->execute($sql, $params);
    }

    /**
     * Get unread notifications with actor details (optimized).
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUnreadNotifications(int $userId, int $limit = 10): array
    {
        $sql = "
            SELECT 
                n.*,
                actor_user.role AS actor_role,
                CASE 
                    WHEN actor_user.role = 'employer' THEN ep.company_name
                    WHEN actor_user.role = 'jobseeker' THEN CONCAT(jp.first_name, ' ', jp.last_name)
                    ELSE actor_user.email
                END AS actor_name,
                COALESCE(ep.avatar, jp.avatar, actor_user.avatar) as actor_avatar
            FROM {$this->table} n
            LEFT JOIN users actor_user ON n.actor_id = actor_user.id
            LEFT JOIN employer_profiles ep ON actor_user.id = ep.user_id AND actor_user.role = 'employer'
            LEFT JOIN jobseeker_profiles jp ON actor_user.id = jp.user_id AND actor_user.role = 'jobseeker'
            WHERE n.user_id = :user_id AND n.is_read = 0
            GROUP BY n.id
            ORDER BY n.created_at DESC
        ";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql, [':user_id' => $userId]);
    }

    /**
     * Get notifications by type for a user.
     *
     * @param int $userId
     * @param string $type
     * @param int $limit
     * @return array
     */
    public function getNotificationsByType(int $userId, string $type, int $limit = 20): array
    {
        $sql = "
            SELECT 
                n.*,
                actor_user.role AS actor_role,
                CASE 
                    WHEN actor_user.role = 'employer' THEN ep.company_name
                    WHEN actor_user.role = 'jobseeker' THEN CONCAT(jp.first_name, ' ', jp.last_name)
                    ELSE actor_user.email
                END AS actor_name
            FROM {$this->table} n
            LEFT JOIN users actor_user ON n.actor_id = actor_user.id
            LEFT JOIN employer_profiles ep ON actor_user.id = ep.user_id AND actor_user.role = 'employer'
            LEFT JOIN jobseeker_profiles jp ON actor_user.id = jp.user_id AND actor_user.role = 'jobseeker'
            WHERE n.user_id = :user_id AND n.type = :type
            GROUP BY n.id
            ORDER BY n.created_at DESC
        ";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql, [
            ':user_id' => $userId,
            ':type' => $type
        ]);
    }

    /**
     * Count notifications by type for a user.
     *
     * @param int $userId
     * @param string $type
     * @return int
     */
    public function countByType(int $userId, string $type): int
    {
        $sql = "SELECT COUNT(DISTINCT id) as total FROM {$this->table}
                WHERE user_id = :user_id AND type = :type";
        $result = $this->fetchOne($sql, [
            ':user_id' => $userId,
            ':type' => $type
        ]);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Delete old notifications (older than specified days).
     *
     * @param int $days
     * @return int Number of deleted notifications
     */
    public function deleteOldNotifications(int $days = 30): int
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $this->execute($sql, [':days' => $days]);
        return $this->rowCount();
    }

    /**
     * Get notification statistics for a user.
     *
     * @param int $userId
     * @return array
     */
    public function getNotificationStats(int $userId): array
    {
        $sql = "SELECT 
                    COUNT(DISTINCT id) as total,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                    SUM(CASE WHEN type = 'interview' THEN 1 ELSE 0 END) as interview_count,
                    SUM(CASE WHEN type = 'job_application' THEN 1 ELSE 0 END) as application_count,
                    SUM(CASE WHEN type = 'message' THEN 1 ELSE 0 END) as message_count
                FROM {$this->table}
                WHERE user_id = :user_id";
        
        $result = $this->fetchOne($sql, [':user_id' => $userId]);
        return [
            'total' => (int) ($result['total'] ?? 0),
            'unread' => (int) ($result['unread'] ?? 0),
            'interview' => (int) ($result['interview_count'] ?? 0),
            'application' => (int) ($result['application_count'] ?? 0),
            'message' => (int) ($result['message_count'] ?? 0)
        ];
    }

    /**
     * Get notifications with pagination and filtering (advanced).
     *
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getNotifications(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = "
            SELECT 
                n.*,
                actor_user.role AS actor_role,
                CASE 
                    WHEN actor_user.role = 'employer' THEN ep.company_name
                    WHEN actor_user.role = 'jobseeker' THEN CONCAT(jp.first_name, ' ', jp.last_name)
                    ELSE actor_user.email
                END AS actor_name
            FROM {$this->table} n
            LEFT JOIN users actor_user ON n.actor_id = actor_user.id
            LEFT JOIN employer_profiles ep ON actor_user.id = ep.user_id AND actor_user.role = 'employer'
            LEFT JOIN jobseeker_profiles jp ON actor_user.id = jp.user_id AND actor_user.role = 'jobseeker'
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= " AND n.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND n.type = :type";
            $params[':type'] = $filters['type'];
        }

        if (isset($filters['is_read'])) {
            $sql .= " AND n.is_read = :is_read";
            $params[':is_read'] = $filters['is_read'];
        }

        if (!empty($filters['from_date'])) {
            $sql .= " AND n.created_at >= :from_date";
            $params[':from_date'] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $sql .= " AND n.created_at <= :to_date";
            $params[':to_date'] = $filters['to_date'];
        }

        $sql .= " GROUP BY n.id ORDER BY n.created_at DESC";

        $limit = max(0, min(1000, $limit));
        $offset = max(0, $offset);
        $sql .= $this->formatLimitOffset($limit, $offset);

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count notifications with filters.
     *
     * @param array $filters
     * @return int
     */
    public function countNotifications(array $filters = []): int
    {
        $sql = "SELECT COUNT(DISTINCT n.id) as total FROM {$this->table} n WHERE 1=1";
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= " AND n.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND n.type = :type";
            $params[':type'] = $filters['type'];
        }

        if (isset($filters['is_read'])) {
            $sql .= " AND n.is_read = :is_read";
            $params[':is_read'] = $filters['is_read'];
        }

        $result = $this->fetchOne($sql, $params);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Bulk create notifications.
     *
     * @param array $notifications Array of notification data
     * @return int Number of notifications created
     */
    public function bulkCreate(array $notifications): int
    {
        $created = 0;
        
        try {
            $this->beginTransaction();
            
            foreach ($notifications as $notification) {
                $result = $this->create($notification);
                if ($result !== null) {
                    $created++;
                }
            }
            
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            error_log("Failed to bulk create notifications: " . $e->getMessage());
        }
        
        return $created;
    }

    /**
     * Delete all notifications for a user.
     *
     * @param int $userId
     * @return bool
     */
    public function deleteAllForUser(int $userId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";
        return $this->execute($sql, [':user_id' => $userId]);
    }
}
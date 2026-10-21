<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\LikeableType;
use PDO;
use PDOException;

/**
 * LikeRepository - Manages polymorphic likes for any content type
 * 
 * Handles:
 * - Like/unlike operations for any entity type
 * - Like count management
 * - User like status checking
 * - Bulk like operations
 * 
 * @package App\Repositories
 */
class LikeRepository extends BaseRepository
{
    protected string $table = 'likes';
    protected string $primaryKey = 'id';

    // ==================== LIKE OPERATIONS ====================

    /**
     * Like an entity
     * 
     * @param string $type Entity type (job, comment, feed, etc.)
     * @param int $entityId Entity ID
     * @param int $userId User ID
     * @return bool
     */
    public function like(string $type, int $entityId, int $userId): bool
    {
        try {
            // Validate type
            if (!LikeableType::isValid($type)) {
                return false;
            }

            // Check if already liked
            if ($this->hasLiked($type, $entityId, $userId)) {
                return false;
            }

            $sql = "INSERT INTO {$this->table} (likeable_type, likeable_id, user_id) 
                    VALUES (:type, :entity_id, :user_id)";

            return $this->execute($sql, [
                ':type' => $type,
                ':entity_id' => $entityId,
                ':user_id' => $userId
            ]);

        } catch (PDOException $e) {
            error_log("Failed to like: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unlike an entity
     * 
     * @param string $type Entity type
     * @param int $entityId Entity ID
     * @param int $userId User ID
     * @return bool
     */
    public function unlike(string $type, int $entityId, int $userId): bool
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE likeable_type = :type 
                AND likeable_id = :entity_id 
                AND user_id = :user_id";

        return $this->execute($sql, [
            ':type' => $type,
            ':entity_id' => $entityId,
            ':user_id' => $userId
        ]);
    }

    /**
     * Toggle like status (like if not liked, unlike if liked)
     * 
     * @param string $type Entity type
     * @param int $entityId Entity ID
     * @param int $userId User ID
     * @return array ['liked' => bool, 'likes_count' => int]
     */
    public function toggleLike(string $type, int $entityId, int $userId): array
    {
        try {
            $this->beginTransaction();

            $hasLiked = $this->hasLiked($type, $entityId, $userId);

            if ($hasLiked) {
                $success = $this->unlike($type, $entityId, $userId);
                $liked = false;
            } else {
                $success = $this->like($type, $entityId, $userId);
                $liked = true;
            }

            $this->commit();

            // Get updated like count
            $likesCount = $this->getLikeCount($type, $entityId);

            return [
                'success' => $success,
                'liked' => $liked,
                'likes_count' => $likesCount
            ];

        } catch (PDOException $e) {
            $this->rollback();
            error_log("Failed to toggle like: " . $e->getMessage());
            return [
                'success' => false,
                'liked' => false,
                'likes_count' => 0
            ];
        }
    }

    // ==================== QUERY METHODS ====================

    /**
     * Check if user has liked an entity
     * 
     * @param string $type Entity type
     * @param int $entityId Entity ID
     * @param int $userId User ID
     * @return bool
     */
    public function hasLiked(string $type, int $entityId, int $userId): bool
    {
        $sql = "SELECT 1 FROM {$this->table} 
                WHERE likeable_type = :type 
                AND likeable_id = :entity_id 
                AND user_id = :user_id 
                LIMIT 1";

        $result = $this->fetchOne($sql, [
            ':type' => $type,
            ':entity_id' => $entityId,
            ':user_id' => $userId
        ]);

        return !empty($result);
    }

    /**
     * Get like count for an entity
     * 
     * @param string $type Entity type
     * @param int $entityId Entity ID
     * @return int
     */
    public function getLikeCount(string $type, int $entityId): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE likeable_type = :type 
                AND likeable_id = :entity_id";

        $result = $this->fetchOne($sql, [
            ':type' => $type,
            ':entity_id' => $entityId
        ]);

        return (int) ($result['total'] ?? 0);
    }

    /**
     * Get like counts for multiple entities (bulk)
     * 
     * @param string $type Entity type
     * @param array $entityIds Array of entity IDs
     * @return array Like counts keyed by entity ID
     */
    public function getLikeCounts(string $type, array $entityIds): array
    {
        if (empty($entityIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($entityIds), '?'));

        $sql = "SELECT likeable_id, COUNT(*) as like_count 
                FROM {$this->table} 
                WHERE likeable_type = ? 
                AND likeable_id IN ({$placeholders})
                GROUP BY likeable_id";

        $params = array_merge([$type], $entityIds);
        $results = $this->fetchAll($sql, $params);

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['likeable_id']] = (int) $row['like_count'];
        }

        return $counts;
    }

    /**
     * Get users who liked an entity
     * 
     * @param string $type Entity type
     * @param int $entityId Entity ID
     * @param int $limit Maximum users to return
     * @return array Users with their details
     */
    public function getLikingUsers(string $type, int $entityId, int $limit = 50): array
    {
        $sql = "SELECT l.user_id, u.username, u.avatar, u.email, u.role,
                       CASE 
                           WHEN u.role = 'jobseeker' THEN CONCAT(COALESCE(jp.first_name, ''), ' ', COALESCE(jp.last_name, ''))
                           WHEN u.role = 'employer' THEN ep.company_name
                           ELSE u.email
                       END as display_name
                FROM {$this->table} l
                JOIN users u ON l.user_id = u.id
                LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
                LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                WHERE l.likeable_type = :type 
                AND l.likeable_id = :entity_id
                ORDER BY l.created_at DESC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql, [
            ':type' => $type,
            ':entity_id' => $entityId
        ]);
    }

    /**
     * Get entities liked by a user
     * 
     * @param int $userId User ID
     * @param string|null $type Optional entity type filter
     * @param int $limit Maximum results
     * @param int $offset Offset for pagination
     * @return array Liked entities
     */
    public function getUserLikes(int $userId, ?string $type = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id";

        $params = [':user_id' => $userId];

        if ($type && LikeableType::isValid($type)) {
            $sql .= " AND likeable_type = :type";
            $params[':type'] = $type;
        }

        $sql .= " ORDER BY created_at DESC";
        $sql .= $this->formatLimitOffset($limit, $offset);

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count how many entities a user has liked
     * 
     * @param int $userId User ID
     * @param string|null $type Optional entity type filter
     * @return int
     */
    public function countUserLikes(int $userId, ?string $type = null): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE user_id = :user_id";

        $params = [':user_id' => $userId];

        if ($type && LikeableType::isValid($type)) {
            $sql .= " AND likeable_type = :type";
            $params[':type'] = $type;
        }

        $result = $this->fetchOne($sql, $params);
        return (int) ($result['total'] ?? 0);
    }

    // ==================== BULK OPERATIONS ====================

    /**
     * Get like status for multiple entities (bulk)
     * 
     * @param string $type Entity type
     * @param array $entityIds Array of entity IDs
     * @param int $userId User ID
     * @return array Like status keyed by entity ID
     */
    public function getLikeStatus(string $type, array $entityIds, int $userId): array
    {
        if (empty($entityIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($entityIds), '?'));

        $sql = "SELECT likeable_id FROM {$this->table} 
                WHERE likeable_type = ? 
                AND likeable_id IN ({$placeholders})
                AND user_id = ?";

        $params = array_merge([$type], $entityIds, [$userId]);
        $results = $this->fetchAll($sql, $params);

        $status = [];
        foreach ($entityIds as $id) {
            $status[$id] = false;
        }

        foreach ($results as $row) {
            $status[$row['likeable_id']] = true;
        }

        return $status;
    }

    // ==================== STATISTICS ====================

    /**
     * Get overall like statistics
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        $stats = [];

        // Total likes
        $result = $this->fetchOne("SELECT COUNT(*) as total FROM {$this->table}");
        $stats['total_likes'] = (int) ($result['total'] ?? 0);

        // Likes by type
        $sql = "SELECT likeable_type, COUNT(*) as count 
                FROM {$this->table} 
                GROUP BY likeable_type 
                ORDER BY count DESC";
        $stats['by_type'] = $this->fetchAll($sql);

        // Most liked entities
        $sql = "SELECT likeable_type, likeable_id, COUNT(*) as like_count 
                FROM {$this->table} 
                GROUP BY likeable_type, likeable_id 
                ORDER BY like_count DESC 
                LIMIT 10";
        $stats['most_liked'] = $this->fetchAll($sql);

        // Most active likers
        $sql = "SELECT user_id, COUNT(*) as like_count 
                FROM {$this->table} 
                GROUP BY user_id 
                ORDER BY like_count DESC 
                LIMIT 10";
        $stats['top_likers'] = $this->fetchAll($sql);

        // Likes in last 30 days
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM {$this->table} 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at) 
                ORDER BY date ASC";
        $stats['daily_likes'] = $this->fetchAll($sql);

        return $stats;
    }

    // ==================== MAINTENANCE ====================

    /**
     * Delete all likes for an entity (when entity is deleted)
     * 
     * @param string $type Entity type
     * @param int $entityId Entity ID
     * @return bool
     */
    public function deleteAllForEntity(string $type, int $entityId): bool
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE likeable_type = :type 
                AND likeable_id = :entity_id";

        return $this->execute($sql, [
            ':type' => $type,
            ':entity_id' => $entityId
        ]);
    }

    /**
     * Delete all likes by a user
     * 
     * @param int $userId User ID
     * @return bool
     */
    public function deleteAllByUser(int $userId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";
        return $this->execute($sql, [':user_id' => $userId]);
    }

    /**
     * Get orphaned likes (likes for entities that no longer exist)
     * This requires joining with content tables
     * 
     * @param string $type Entity type
     * @param string $contentTable Content table name
     * @param string $contentIdField Content ID field name
     * @return array
     */
    public function getOrphanedLikes(string $type, string $contentTable, string $contentIdField = 'id'): array
    {
        $sql = "SELECT l.* FROM {$this->table} l
                LEFT JOIN {$contentTable} c ON l.likeable_id = c.{$contentIdField}
                WHERE l.likeable_type = :type 
                AND c.{$contentIdField} IS NULL";

        return $this->fetchAll($sql, [':type' => $type]);
    }

    /**
     * Clean up orphaned likes (delete likes for deleted content)
     * 
     * @param string $type Entity type
     * @param string $contentTable Content table name
     * @param string $contentIdField Content ID field name
     * @return int Number of deleted records
     */
    public function cleanupOrphanedLikes(string $type, string $contentTable, string $contentIdField = 'id'): int
    {
        $sql = "DELETE l FROM {$this->table} l
                LEFT JOIN {$contentTable} c ON l.likeable_id = c.{$contentIdField}
                WHERE l.likeable_type = :type 
                AND c.{$contentIdField} IS NULL";

        $this->execute($sql, [':type' => $type]);
        return $this->rowCount();
    }
}
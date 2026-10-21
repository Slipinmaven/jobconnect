<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Enums\CommentableType;
use PDO;
use PDOException;

/**
 * CommentRepository - Manages all comment operations for polymorphic comments
 * 
 * Handles:
 * - CRUD operations for comments
 * - Nested replies (threaded comments)
 * - Like/unlike functionality
 * - Comment moderation
 * - Pagination with intelligent limits
 * - Proper name handling for job seekers vs employers
 * 
 * @package App\Repositories
 */
class CommentRepository extends BaseRepository
{
    protected string $table = 'comments';
    protected string $primaryKey = 'id';

    // Status constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_HIDDEN = 'hidden';
    public const STATUS_DELETED = 'deleted';

    // Sort options
    public const SORT_NEWEST = 'newest';
    public const SORT_POPULAR = 'popular';
    public const SORT_MOST_REPLIED = 'most_replied';
    public const SORT_OLDEST = 'oldest';

    // Pagination limits
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT = 50;
    private const DEFAULT_REPLIES_LIMIT = 3;

    // ==================== CREATE ====================

    /**
     * Create a new comment
     */
    public function createComment(
        string $type,
        int $entityId,
        int $userId,
        string $content,
        ?int $parentId = null,
        ?string $mediaType = null,
        ?string $mediaUrl = null,
        ?array $mediaMetadata = null
    ): ?int {
        try {
            $this->beginTransaction();

            $sql = "INSERT INTO {$this->table} 
                    (commentable_type, commentable_id, user_id, parent_id, content, 
                     media_type, media_url, media_metadata) 
                    VALUES 
                    (:type, :entity_id, :user_id, :parent_id, :content,
                     :media_type, :media_url, :media_metadata)";

            $success = $this->execute($sql, [
                ':type' => $type,
                ':entity_id' => $entityId,
                ':user_id' => $userId,
                ':parent_id' => $parentId,
                ':content' => $content,
                ':media_type' => $mediaType ?? 'none',
                ':media_url' => $mediaUrl,
                ':media_metadata' => $mediaMetadata ? json_encode($mediaMetadata) : null
            ]);

            if (!$success) {
                throw new PDOException('Failed to insert comment');
            }

            $commentId = $this->lastInsertId();

            // Update replies count on parent comment
            if ($parentId) {
                $this->incrementRepliesCount($parentId);
            }

            $this->commit();
            return $commentId;

        } catch (PDOException $e) {
            $this->rollback();
            error_log("Failed to create comment: " . $e->getMessage());
            return null;
        }
    }

    // ==================== READ (WITH PAGINATION) ====================

    /**
     * Get comments for an entity with pagination
     */
    public function getComments(
        string $type,
        int $entityId,
        ?int $userId = null,
        int $page = 1,
        int $perPage = self::DEFAULT_LIMIT,
        string $sort = self::SORT_NEWEST
    ): array {
        // Cap perPage at max limit
        $perPage = min($perPage, self::MAX_LIMIT);
        $offset = max(0, ($page - 1) * $perPage);

        // Build ORDER BY clause
        $orderBy = match ($sort) {
            self::SORT_POPULAR => "c.is_pinned DESC, c.likes_count DESC, c.created_at DESC",
            self::SORT_MOST_REPLIED => "c.is_pinned DESC, c.replies_count DESC, c.created_at DESC",
            self::SORT_OLDEST => "c.is_pinned DESC, c.created_at ASC",
            default => "c.is_pinned DESC, c.created_at DESC",
        };

        // Get top-level comments with proper name handling
        $sql = "SELECT DISTINCT 
                    c.*,
                    u.id as user_id,
                    u.email,
                    u.role,
                    COALESCE(NULLIF(u.avatar, ''), NULLIF(jp.avatar, ''), NULLIF(ep.avatar, '')) as avatar,
                    -- Handle display name based on role
                    CASE 
                        WHEN u.role = 'jobseeker' THEN CONCAT(COALESCE(jp.first_name, ''), ' ', COALESCE(jp.last_name, ''))
                        WHEN u.role = 'employer' THEN ep.company_name
                        ELSE u.email
                    END as display_name,
                    -- For fallback to email if no name found
                    COALESCE(
                        CASE 
                            WHEN u.role = 'jobseeker' THEN CONCAT(COALESCE(jp.first_name, ''), ' ', COALESCE(jp.last_name, ''))
                            WHEN u.role = 'employer' THEN ep.company_name
                            ELSE NULL
                        END,
                        u.email
                    ) as full_name,
                    (SELECT COUNT(*) FROM likes WHERE likeable_id = c.id AND likeable_type = 'comment') as like_count,
                    :user_has_liked as user_has_liked
                FROM {$this->table} c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
                LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                WHERE c.commentable_type = :type
                  AND c.commentable_id = :entity_id
                  AND c.parent_id IS NULL
                  AND c.is_deleted = 0
                GROUP BY c.id
                ORDER BY {$orderBy}";

        $limit = max(0, min(1000, $perPage));
        $offset = max(0, $offset);
        $sql .= $this->formatLimitOffset($limit, $offset);

        $params = [
            ':type' => $type,
            ':entity_id' => $entityId,
            ':user_has_liked' => 0
        ];

        $comments = $this->fetchAll($sql, $params);
        error_log("The comments fetched are " . print_r($comments, true));

        // Load first few replies and check like status
        foreach ($comments as &$comment) {
            if ($userId) {
                $comment['user_has_liked'] = $this->userHasLiked($comment['id'], $userId);
            }
            // Load only first 3 replies (TikTok style)
            $comment['replies'] = $this->getReplies($comment['id'], $userId, self::DEFAULT_REPLIES_LIMIT);
            $comment['has_more_replies'] = $this->hasMoreReplies($comment['id'], self::DEFAULT_REPLIES_LIMIT);
        }

        // Get total count for pagination
        $total = $this->countComments($type, $entityId);
        error_log("Total comments count: " . $total);

        return [
            'data' => $comments,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1
            ],
            'sort' => $sort
        ];
    }

    /**
     * Get replies for a specific comment
     */
    public function getReplies(int $commentId, ?int $userId = null, int $limit = 10, int $offset = 0): array
    {
        $limit = min($limit, 50);

        $sql = "SELECT DISTINCT 
                    c.*,
                    u.id as user_id,
                    u.email,
                    u.role,
                    COALESCE(NULLIF(u.avatar, ''), NULLIF(jp.avatar, ''), NULLIF(ep.avatar, '')) as avatar,
                    CASE 
                        WHEN u.role = 'jobseeker' THEN CONCAT(COALESCE(jp.first_name, ''), ' ', COALESCE(jp.last_name, ''))
                        WHEN u.role = 'employer' THEN ep.company_name
                        ELSE u.email
                    END as display_name,
                    COALESCE(
                        CASE 
                            WHEN u.role = 'jobseeker' THEN CONCAT(COALESCE(jp.first_name, ''), ' ', COALESCE(jp.last_name, ''))
                            WHEN u.role = 'employer' THEN ep.company_name
                            ELSE NULL
                        END,
                        u.email
                    ) as full_name,
                    (SELECT COUNT(*) FROM likes WHERE likeable_id = c.id AND likeable_type = 'comment') as like_count,
                    :user_has_liked as user_has_liked
                FROM {$this->table} c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
                LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                WHERE c.parent_id = :parent_id
                  AND c.is_deleted = 0
                GROUP BY c.id
                ORDER BY c.created_at ASC";

        $limit = max(0, min(1000, $limit));
        $offset = max(0, $offset);
        $sql .= $this->formatLimitOffset($limit, $offset);

        $params = [
            ':parent_id' => $commentId,
            ':user_has_liked' => 0
        ];

        $replies = $this->fetchAll($sql, $params);

        // Check like status for each reply
        foreach ($replies as &$reply) {
            if ($userId) {
                $reply['user_has_liked'] = $this->userHasLiked($reply['id'], $userId);
            }
        }

        return $replies;
    }

    /**
     * Load more replies (for "Load More" button)
     */
    public function loadMoreReplies(int $commentId, ?int $userId = null, int $offset = 0, int $limit = 10): array
    {
        $limit = min($limit, 50);
        $replies = $this->getReplies($commentId, $userId, $limit, $offset);
        $hasMore = $this->hasMoreReplies($commentId, $offset + $limit);

        return [
            'data' => $replies,
            'has_more' => $hasMore,
            'next_offset' => $offset + $limit,
            'loaded_count' => $offset + count($replies)
        ];
    }

    /**
     * Get comment by ID with user details
     */
    public function getCommentWithUser(int $commentId, ?int $userId = null): ?array
    {
        $sql = "SELECT DISTINCT 
                    c.*,
                    u.id as user_id,
                    u.email,
                    u.role,
                    COALESCE(NULLIF(u.avatar, ''), NULLIF(jp.avatar, ''), NULLIF(ep.avatar, '')) as avatar,
                    CASE 
                        WHEN u.role = 'jobseeker' THEN CONCAT(COALESCE(jp.first_name, ''), ' ', COALESCE(jp.last_name, ''))
                        WHEN u.role = 'employer' THEN ep.company_name
                        ELSE u.email
                    END as display_name,
                    COALESCE(
                        CASE 
                            WHEN u.role = 'jobseeker' THEN CONCAT(COALESCE(jp.first_name, ''), ' ', COALESCE(jp.last_name, ''))
                            WHEN u.role = 'employer' THEN ep.company_name
                            ELSE NULL
                        END,
                        u.email
                    ) as full_name,
                    (SELECT COUNT(*) FROM likes WHERE likeable_id = c.id AND likeable_type = 'comment') as like_count,
                    :user_has_liked as user_has_liked
                FROM {$this->table} c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
                LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                WHERE c.id = :id AND c.is_deleted = 0
                GROUP BY c.id";

        $params = [':id' => $commentId, ':user_has_liked' => 0];
        $comment = $this->fetchOne($sql, $params);

        if ($comment && $userId) {
            $comment['user_has_liked'] = $this->userHasLiked($commentId, $userId);
        }

        return $comment;
    }

    /**
     * Get comments by user
     */
    public function getCommentsByUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT DISTINCT 
                    c.*,
                    u.id as user_id,
                    u.email,
                    u.role,
                    u.avatar,
                    CASE 
                        WHEN u.role = 'jobseeker' THEN CONCAT(COALESCE(jp.first_name, ''), ' ', COALESCE(jp.last_name, ''))
                        WHEN u.role = 'employer' THEN ep.company_name
                        ELSE u.email
                    END as display_name,
                    (SELECT COUNT(*) FROM likes WHERE likeable_id = c.id AND likeable_type = 'comment') as like_count
                FROM {$this->table} c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
                LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                WHERE c.user_id = :user_id
                  AND c.is_deleted = 0
                GROUP BY c.id
                ORDER BY c.created_at DESC";

        $limit = max(0, min(1000, $limit));
        $offset = max(0, $offset);
        $sql .= $this->formatLimitOffset($limit, $offset);

        return $this->fetchAll($sql, [':user_id' => $userId]);
    }

    /**
     * Get recent comments across all content (for activity feed)
     */
    public function getRecentComments(int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT DISTINCT 
                    c.*,
                    u.id as user_id,
                    u.email,
                    u.role,
                    u.avatar,
                    CASE 
                        WHEN u.role = 'jobseeker' THEN CONCAT(COALESCE(jp.first_name, ''), ' ', COALESCE(jp.last_name, ''))
                        WHEN u.role = 'employer' THEN ep.company_name
                        ELSE u.email
                    END as display_name,
                    (SELECT COUNT(*) FROM likes WHERE likeable_id = c.id AND likeable_type = 'comment') as like_count
                FROM {$this->table} c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
                LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                WHERE c.is_deleted = 0
                GROUP BY c.id
                ORDER BY c.created_at DESC";

        $limit = max(0, min(1000, $limit));
        $offset = max(0, $offset);
        $sql .= $this->formatLimitOffset($limit, $offset);

        return $this->fetchAll($sql);
    }

    /**
     * Get comments that mention a user
     */
    public function getCommentsMentioningUser(int $userId, int $limit = 50): array
    {
        $sql = "SELECT DISTINCT 
                    c.*,
                    u.id as user_id,
                    u.email,
                    u.role,
                    u.avatar,
                    CASE 
                        WHEN u.role = 'jobseeker' THEN CONCAT(COALESCE(jp.first_name, ''), ' ', COALESCE(jp.last_name, ''))
                        WHEN u.role = 'employer' THEN ep.company_name
                        ELSE u.email
                    END as display_name,
                    (SELECT COUNT(*) FROM likes WHERE likeable_id = c.id AND likeable_type = 'comment') as like_count
                FROM {$this->table} c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
                LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                WHERE c.content LIKE :mention
                  AND c.is_deleted = 0
                GROUP BY c.id
                ORDER BY c.created_at DESC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql, [
            ':mention' => '%@' . $userId . '%'
        ]);
    }

    /**
     * Get top commenters for an entity
     */
    public function getTopCommenters(string $type, int $entityId, int $limit = 10): array
    {
        $sql = "SELECT 
                    u.id as user_id,
                    u.email,
                    u.role,
                    u.avatar,
                    CASE 
                        WHEN u.role = 'jobseeker' THEN CONCAT(COALESCE(jp.first_name, ''), ' ', COALESCE(jp.last_name, ''))
                        WHEN u.role = 'employer' THEN ep.company_name
                        ELSE u.email
                    END as display_name,
                    COUNT(DISTINCT c.id) as comment_count,
                    SUM(c.likes_count) as total_likes_received
                FROM {$this->table} c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
                LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                WHERE c.commentable_type = :type
                  AND c.commentable_id = :entity_id
                  AND c.is_deleted = 0
                GROUP BY u.id
                ORDER BY comment_count DESC, total_likes_received DESC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql, [':type' => $type, ':entity_id' => $entityId]);
    }

    // ==================== UPDATE (With Count Management) ====================

    /**
     * Update a comment
     */
    public function updateComment(int $commentId, int $userId, string $content, ?string $mediaType = null, ?string $mediaUrl = null, ?array $mediaMetadata = null): bool
    {
        $sql = "UPDATE {$this->table} SET 
                    content = :content,
                    media_type = :media_type,
                    media_url = :media_url,
                    media_metadata = :media_metadata,
                    is_edited = 1,
                    edited_at = NOW()
                WHERE id = :id AND user_id = :user_id AND is_deleted = 0";

        return $this->execute($sql, [
            ':id' => $commentId,
            ':user_id' => $userId,
            ':content' => $content,
            ':media_type' => $mediaType ?? 'none',
            ':media_url' => $mediaUrl,
            ':media_metadata' => $mediaMetadata ? json_encode($mediaMetadata) : null
        ]);
    }

    /**
     * Soft delete a comment
     */
    public function deleteComment(int $commentId, int $userId): bool
    {
        try {
            $this->beginTransaction();

            // Get comment before deletion to update parent counts
            $comment = $this->findById($commentId);

            $sql = "UPDATE {$this->table} SET 
                        is_deleted = 1, 
                        deleted_at = NOW(),
                        content = '[deleted]'
                    WHERE id = :id AND user_id = :user_id AND is_deleted = 0";

            $result = $this->execute($sql, [':id' => $commentId, ':user_id' => $userId]);

            if ($result && $this->rowCount() > 0 && $comment && $comment['parent_id']) {
                // Decrement replies count on parent
                $this->decrementRepliesCount($comment['parent_id']);
            }

            $this->commit();
            return $result && $this->rowCount() > 0;

        } catch (PDOException $e) {
            $this->rollback();
            error_log("Failed to delete comment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Like a comment (updates both tables)
     */
    public function likeComment(int $commentId, int $userId): bool
    {
        try {
            $this->beginTransaction();

            // Check if already liked
            if ($this->userHasLiked($commentId, $userId)) {
                $this->commit();
                return false;
            }

            // Insert like
            $sql = "INSERT INTO likes (likeable_id, likeable_type, user_id) VALUES (:comment_id, 'comment', :user_id)";
            $success = $this->execute($sql, [':comment_id' => $commentId, ':user_id' => $userId]);

            if ($success) {
                // Increment the stored likes count
                $this->incrementLikesCount($commentId);
            }

            $this->commit();
            return $success;

        } catch (PDOException $e) {
            $this->rollback();
            error_log("Failed to like comment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unlike a comment (updates both tables)
     */
    public function unlikeComment(int $commentId, int $userId): bool
    {
        try {
            $this->beginTransaction();

            // Delete like
            $sql = "DELETE FROM likes WHERE likeable_id = :comment_id AND likeable_type = 'comment' AND user_id = :user_id";
            $success = $this->execute($sql, [':comment_id' => $commentId, ':user_id' => $userId]);

            if ($success && $this->rowCount() > 0) {
                // Decrement the stored likes count
                $this->decrementLikesCount($commentId);
            }

            $this->commit();
            return $success;

        } catch (PDOException $e) {
            $this->rollback();
            error_log("Failed to unlike comment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Increment likes count (internal - maintains denormalized count)
     */
    public function incrementLikesCount(int $commentId): bool
    {
        $sql = "UPDATE {$this->table} SET likes_count = likes_count + 1 WHERE id = :id";
        return $this->execute($sql, [':id' => $commentId]);
    }

    /**
     * Decrement likes count (internal - maintains denormalized count)
     */
    public function decrementLikesCount(int $commentId): bool
    {
        $sql = "UPDATE {$this->table} SET likes_count = likes_count - 1 WHERE id = :id AND likes_count > 0";
        return $this->execute($sql, [':id' => $commentId]);
    }

    /**
     * Increment replies count
     */
    public function incrementRepliesCount(int $commentId): bool
    {
        $sql = "UPDATE {$this->table} SET replies_count = replies_count + 1 WHERE id = :id";
        return $this->execute($sql, [':id' => $commentId]);
    }

    /**
     * Decrement replies count
     */
    public function decrementRepliesCount(int $commentId): bool
    {
        $sql = "UPDATE {$this->table} SET replies_count = replies_count - 1 WHERE id = :id AND replies_count > 0";
        return $this->execute($sql, [':id' => $commentId]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if user has liked a comment
     */
    public function userHasLiked(int $commentId, int $userId): bool
    {
        $sql = "SELECT 1 FROM likes WHERE likeable_id = :comment_id AND likeable_type = 'comment' AND user_id = :user_id LIMIT 1";
        $result = $this->fetchOne($sql, [':comment_id' => $commentId, ':user_id' => $userId]);
        return !empty($result);
    }

    /**
     * Check if comment has more replies
     */
    public function hasMoreReplies(int $commentId, int $loadedCount): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE parent_id = :parent_id AND is_deleted = 0";
        $result = $this->fetchOne($sql, [':parent_id' => $commentId]);
        return ($result['count'] ?? 0) > $loadedCount;
    }

    /**
     * Count total comments for an entity (top-level only)
     */
    public function countComments(string $type, int $entityId): int
    {
        $sql = "SELECT COUNT(DISTINCT c.id) as total FROM {$this->table} c
                WHERE c.commentable_type = :type 
                  AND c.commentable_id = :entity_id 
                  AND c.parent_id IS NULL 
                  AND c.is_deleted = 0";
        $result = $this->fetchOne($sql, [':type' => $type, ':entity_id' => $entityId]);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Find comment by ID with soft delete check
     */
    public function findById(int $commentId, bool $includeDeleted = false): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        if (!$includeDeleted) {
            $sql .= " AND is_deleted = 0";
        }
        return $this->fetchOne($sql, [':id' => $commentId]);
    }

    /**
     * Get comment statistics for an entity
     */
    public function getCommentStats(string $type, int $entityId): array
    {
        $sql = "SELECT 
                    COUNT(DISTINCT c.id) as total_comments,
                    COUNT(DISTINCT c.user_id) as unique_commenters,
                    SUM(c.likes_count) as total_likes,
                    AVG(c.likes_count) as avg_likes,
                    MAX(c.created_at) as latest_comment,
                    MIN(c.created_at) as first_comment
                FROM {$this->table} c
                WHERE c.commentable_type = :type
                  AND c.commentable_id = :entity_id
                  AND c.parent_id IS NULL
                  AND c.is_deleted = 0";

        return $this->fetchOne($sql, [':type' => $type, ':entity_id' => $entityId]) ?: [];
    }

    // ==================== MAINTENANCE METHODS ====================

    /**
     * Recalculate and fix likes_count for all comments (run via cron)
     */
    public function syncLikesCounts(): int
    {
        $sql = "UPDATE {$this->table} c
                SET c.likes_count = (
                    SELECT COUNT(*) FROM likes cl 
                    WHERE cl.likeable_id = c.id AND cl.likeable_type = 'comment'
                )
                WHERE c.is_deleted = 0";

        $this->execute($sql);
        return $this->rowCount();
    }

    /**
     * Recalculate and fix replies_count for all comments
     */
    public function syncRepliesCounts(): int
    {
        $sql = "UPDATE {$this->table} c
                SET c.replies_count = (
                    SELECT COUNT(*) FROM {$this->table} child 
                    WHERE child.parent_id = c.id AND child.is_deleted = 0
                )
                WHERE c.is_deleted = 0";

        $this->execute($sql);
        return $this->rowCount();
    }

    /**
     * Get comments with mismatched counts (for debugging)
     */
    public function findMismatchedCounts(): array
    {
        $sql = "SELECT 
                    c.id,
                    c.likes_count as stored_likes,
                    (SELECT COUNT(*) FROM likes WHERE likeable_id = c.id AND likeable_type = 'comment') as actual_likes,
                    c.replies_count as stored_replies,
                    (SELECT COUNT(*) FROM {$this->table} WHERE parent_id = c.id AND is_deleted = 0) as actual_replies
                FROM {$this->table} c
                WHERE c.is_deleted = 0
                  AND (c.likes_count != (SELECT COUNT(*) FROM likes WHERE likeable_id = c.id AND likeable_type = 'comment')
                    OR c.replies_count != (SELECT COUNT(*) FROM {$this->table} WHERE parent_id = c.id AND is_deleted = 0))";

        return $this->fetchAll($sql);
    }

    /**
     * Get comments by status for moderation
     */
    public function getCommentsByStatus(?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT DISTINCT 
                    c.*,
                    u.id as user_id,
                    u.email,
                    u.role,
                    u.avatar,
                    CASE 
                        WHEN u.role = 'jobseeker' THEN CONCAT(COALESCE(jp.first_name, ''), ' ', COALESCE(jp.last_name, ''))
                        WHEN u.role = 'employer' THEN ep.company_name
                        ELSE u.email
                    END as display_name,
                    (SELECT COUNT(*) FROM likes WHERE likeable_id = c.id AND likeable_type = 'comment') as like_count
                FROM {$this->table} c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
                LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                WHERE 1=1";

        $params = [];

        if ($status === self::STATUS_HIDDEN) {
            $sql .= " AND c.is_hidden = 1 AND c.is_deleted = 0";
        } elseif ($status === self::STATUS_DELETED) {
            $sql .= " AND c.is_deleted = 1";
        } else {
            $sql .= " AND c.is_hidden = 0 AND c.is_deleted = 0";
        }

        $sql .= " GROUP BY c.id ORDER BY c.created_at DESC";

        $limit = max(0, min(1000, $limit));
        $offset = max(0, $offset);
        $sql .= $this->formatLimitOffset($limit, $offset);

        return $this->fetchAll($sql, $params);
    }

    /**
     * Count comments by status for moderation
     */
    public function countCommentsByStatus(?string $status = null): int
    {
        $sql = "SELECT COUNT(DISTINCT c.id) as total FROM {$this->table} c WHERE 1=1";
        $params = [];

        if ($status === self::STATUS_HIDDEN) {
            $sql .= " AND c.is_hidden = 1 AND c.is_deleted = 0";
        } elseif ($status === self::STATUS_DELETED) {
            $sql .= " AND c.is_deleted = 1";
        } else {
            $sql .= " AND c.is_hidden = 0 AND c.is_deleted = 0";
        }

        $result = $this->fetchOne($sql, $params);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Count search results
     */
    public function countSearchResults(string $query): int
    {
        $sql = "SELECT COUNT(DISTINCT c.id) as total FROM {$this->table} c
                WHERE MATCH(c.content_text) AGAINST(:query IN NATURAL LANGUAGE MODE)
                  AND c.is_deleted = 0";

        $result = $this->fetchOne($sql, [':query' => $query]);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Search comments
     */
    public function searchComments(string $query, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT DISTINCT 
                    c.*,
                    u.id as user_id,
                    u.email,
                    u.role,
                    u.avatar,
                    CASE 
                        WHEN u.role = 'jobseeker' THEN CONCAT(COALESCE(jp.first_name, ''), ' ', COALESCE(jp.last_name, ''))
                        WHEN u.role = 'employer' THEN ep.company_name
                        ELSE u.email
                    END as display_name,
                    MATCH(c.content_text) AGAINST(:query IN NATURAL LANGUAGE MODE) as relevance
                FROM {$this->table} c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
                LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                WHERE MATCH(c.content_text) AGAINST(:query IN NATURAL LANGUAGE MODE)
                  AND c.is_deleted = 0
                GROUP BY c.id
                ORDER BY relevance DESC";

        $limit = max(0, min(1000, $limit));
        $offset = max(0, $offset);
        $sql .= $this->formatLimitOffset($limit, $offset);

        return $this->fetchAll($sql, [
            ':query' => $query
        ]);
    }
}
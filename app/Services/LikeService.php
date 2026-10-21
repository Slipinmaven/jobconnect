<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\LikeRepository;
use App\Enums\LikeableType;
use Exception;

/**
 * LikeService - Handles all like business logic for polymorphic content
 * 
 * Business rules:
 * - Users cannot like their own content
 * - Rate limiting to prevent spam
 * - Notifications for likes (optional)
 * - Prevent duplicate likes
 * 
 * @package App\Services
 */
class LikeService extends BaseService
{
    private LikeRepository $likeRepo;

    // Rate limiting
    private const MAX_LIKES_PER_MINUTE = 10;

    public function __construct(LikeRepository $likeRepo)
    {
        parent::__construct();
        $this->likeRepo = $likeRepo;
    }

    // ==================== CORE OPERATIONS ====================

    /**
     * Like an entity
     * 
     * @param string $type Entity type (job, comment, feed, etc.)
     * @param int $entityId Entity ID
     * @param int $userId User ID
     * @param int $contentOwnerId Owner of the content being liked (for notifications)
     * @return array Like result
     */
    public function like(string $type, int $entityId, int $userId, ?int $contentOwnerId = null): array
    {
        $this->logInfo('Liking content', [
            'type' => $type,
            'entity_id' => $entityId,
            'user_id' => $userId
        ]);
        $this->clearErrors();

        try {
            // Validate type
            if (!LikeableType::isValid($type)) {
                $this->addError('type', 'Invalid content type');
                return ['success' => false, 'liked' => false, 'likes_count' => 0];
            }

            // Check if user is liking their own content
            if ($contentOwnerId && $contentOwnerId == $userId) {
                $this->addError('like', 'You cannot like your own content');
                return ['success' => false, 'liked' => false, 'likes_count' => 0];
            }

            // Check rate limiting
            if (!$this->checkRateLimit($userId)) {
                $this->addError('rate_limit', 'You are liking too quickly. Please wait a moment.');
                return ['success' => false, 'liked' => false, 'likes_count' => 0];
            }

            // Check if already liked
            if ($this->likeRepo->hasLiked($type, $entityId, $userId)) {
                $this->addError('like', 'You have already liked this content');
                return [
                    'success' => false,
                    'liked' => true,
                    'likes_count' => $this->likeRepo->getLikeCount($type, $entityId)
                ];
            }

            // Perform like
            $success = $this->likeRepo->like($type, $entityId, $userId);

            if (!$success) {
                $this->addError('like', 'Failed to like content');
                return ['success' => false, 'liked' => false, 'likes_count' => 0];
            }

            // Get updated like count
            $likesCount = $this->likeRepo->getLikeCount($type, $entityId);

            // Send notification to content owner
            if ($contentOwnerId && $contentOwnerId != $userId) {
                $this->sendLikeNotification($contentOwnerId, $type, $entityId, $userId);
            }

            $this->logInfo('Content liked successfully', [
                'type' => $type,
                'entity_id' => $entityId,
                'likes_count' => $likesCount
            ]);

            return [
                'success' => true,
                'liked' => true,
                'likes_count' => $likesCount
            ];

        } catch (Exception $e) {
            $this->logError('Exception in like', ['error' => $e->getMessage()]);
            $this->addError('like', 'An error occurred while liking');
            return ['success' => false, 'liked' => false, 'likes_count' => 0];
        }
    }

    /**
     * Unlike an entity
     * 
     * @param string $type Entity type
     * @param int $entityId Entity ID
     * @param int $userId User ID
     * @return array Unlike result
     */
    public function unlike(string $type, int $entityId, int $userId): array
    {
        $this->logInfo('Unliking content', [
            'type' => $type,
            'entity_id' => $entityId,
            'user_id' => $userId
        ]);
        $this->clearErrors();

        try {
            // Validate type
            if (!LikeableType::isValid($type)) {
                $this->addError('type', 'Invalid content type');
                return ['success' => false, 'liked' => true, 'likes_count' => 0];
            }

            // Check if already liked
            if (!$this->likeRepo->hasLiked($type, $entityId, $userId)) {
                $this->addError('like', 'You have not liked this content');
                return [
                    'success' => false,
                    'liked' => false,
                    'likes_count' => $this->likeRepo->getLikeCount($type, $entityId)
                ];
            }

            // Perform unlike
            $success = $this->likeRepo->unlike($type, $entityId, $userId);

            if (!$success) {
                $this->addError('like', 'Failed to unlike content');
                return ['success' => false, 'liked' => true, 'likes_count' => 0];
            }

            // Get updated like count
            $likesCount = $this->likeRepo->getLikeCount($type, $entityId);

            $this->logInfo('Content unliked successfully', [
                'type' => $type,
                'entity_id' => $entityId,
                'likes_count' => $likesCount
            ]);

            return [
                'success' => true,
                'liked' => false,
                'likes_count' => $likesCount
            ];

        } catch (Exception $e) {
            $this->logError('Exception in unlike', ['error' => $e->getMessage()]);
            $this->addError('like', 'An error occurred while unliking');
            return ['success' => false, 'liked' => true, 'likes_count' => 0];
        }
    }

    /**
     * Toggle like status (like if not liked, unlike if liked)
     * 
     * @param string $type Entity type
     * @param int $entityId Entity ID
     * @param int $userId User ID
     * @param int|null $contentOwnerId Owner of the content being liked
     * @return array Toggle result
     */
    public function toggleLike(string $type, int $entityId, int $userId, ?int $contentOwnerId = null): array
    {
        $this->logInfo('Toggling like', [
            'type' => $type,
            'entity_id' => $entityId,
            'user_id' => $userId
        ]);

        try {
            // Check if already liked
            $hasLiked = $this->likeRepo->hasLiked($type, $entityId, $userId);

            if ($hasLiked) {
                return $this->unlike($type, $entityId, $userId);
            } else {
                return $this->like($type, $entityId, $userId, $contentOwnerId);
            }

        } catch (Exception $e) {
            $this->logError('Exception in toggleLike', ['error' => $e->getMessage()]);
            return ['success' => false, 'liked' => false, 'likes_count' => 0];
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
        return $this->likeRepo->hasLiked($type, $entityId, $userId);
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
        return $this->likeRepo->getLikeCount($type, $entityId);
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

        return $this->likeRepo->getLikeCounts($type, $entityIds);
    }

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

        return $this->likeRepo->getLikeStatus($type, $entityIds, $userId);
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
        return $this->likeRepo->getLikingUsers($type, $entityId, $limit);
    }

    /**
     * Get entities liked by a user
     * 
     * @param int $userId User ID
     * @param string|null $type Optional entity type filter
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Liked entities with pagination
     */
    public function getUserLikes(int $userId, ?string $type = null, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $likes = $this->likeRepo->getUserLikes($userId, $type, $perPage, $offset);
        $total = $this->likeRepo->countUserLikes($userId, $type);

        return [
            'data' => $likes,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1
            ]
        ];
    }

    // ==================== STATISTICS ====================

    /**
     * Get overall like statistics
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->likeRepo->getStatistics();
    }

    /**
     * Get like statistics for a specific entity
     * 
     * @param string $type Entity type
     * @param int $entityId Entity ID
     * @return array
     */
    public function getEntityStatistics(string $type, int $entityId): array
    {
        $likesCount = $this->getLikeCount($type, $entityId);
        $likingUsers = $this->getLikingUsers($type, $entityId, 5);

        return [
            'total_likes' => $likesCount,
            'recent_likers' => $likingUsers,
            'has_liked' => false // This should be set by the caller with user context
        ];
    }

    // ==================== MAINTENANCE ====================

    /**
     * Clean up orphaned likes for a specific entity type
     * 
     * @param string $type Entity type
     * @param string $contentTable Content table name
     * @param string $contentIdField Content ID field name
     * @return int Number of deleted records
     */
    public function cleanupOrphanedLikes(string $type, string $contentTable, string $contentIdField = 'id'): int
    {
        $this->logInfo('Cleaning up orphaned likes', ['type' => $type]);

        return $this->likeRepo->cleanupOrphanedLikes($type, $contentTable, $contentIdField);
    }

    /**
     * Delete all likes for an entity
     * 
     * @param string $type Entity type
     * @param int $entityId Entity ID
     * @return bool
     */
    public function deleteAllForEntity(string $type, int $entityId): bool
    {
        $this->logInfo('Deleting all likes for entity', [
            'type' => $type,
            'entity_id' => $entityId
        ]);

        return $this->likeRepo->deleteAllForEntity($type, $entityId);
    }

    /**
     * Delete all likes by a user
     * 
     * @param int $userId User ID
     * @return bool
     */
    public function deleteAllByUser(int $userId): bool
    {
        $this->logInfo('Deleting all likes by user', ['user_id' => $userId]);

        return $this->likeRepo->deleteAllByUser($userId);
    }

    // ==================== VALIDATION HELPERS ====================

    /**
     * Check rate limit for user
     * 
     * @param int $userId User ID
     * @return bool
     */
    private function checkRateLimit(int $userId): bool
    {
        // Count likes in last minute
        $sql = "SELECT COUNT(*) as count FROM likes 
                WHERE user_id = :user_id 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)";

        $result = $this->likeRepo->fetchOne($sql, [':user_id' => $userId]);
        $likeCount = (int) ($result['count'] ?? 0);

        return $likeCount < self::MAX_LIKES_PER_MINUTE;
    }

    // ==================== NOTIFICATIONS ====================

    /**
     * Send notification for like
     * 
     * @param int $recipientId User ID to notify
     * @param string $type Entity type liked
     * @param int $entityId Entity ID liked
     * @param int $likerId User who liked
     */
    private function sendLikeNotification(int $recipientId, string $type, int $entityId, int $likerId): void
    {
        // Get liker details
        $liker = $this->getUserDetails($likerId);

        if (!$liker) {
            return;
        }

        // Get entity details (this would be implemented based on your content tables)
        $entityTitle = $this->getEntityTitle($type, $entityId);

        $this->logInfo('Like notification would be sent', [
            'recipient_id' => $recipientId,
            'liker_id' => $likerId,
            'liker_name' => $liker['display_name'],
            'type' => $type,
            'entity_id' => $entityId,
            'entity_title' => $entityTitle
        ]);

        // TODO: Implement actual notification when NotificationService is ready
        // $this->notificationService->sendLikeNotification($recipientId, $liker, $type, $entityId, $entityTitle);
    }

    /**
     * Get user details for notification
     * 
     * @param int $userId
     * @return array|null
     */
    private function getUserDetails(int $userId): ?array
    {
        $sql = "SELECT u.id, u.email, u.role, u.avatar,
                       CASE 
                           WHEN u.role = 'jobseeker' THEN CONCAT(COALESCE(jp.first_name, ''), ' ', COALESCE(jp.last_name, ''))
                           WHEN u.role = 'employer' THEN ep.company_name
                           ELSE u.email
                       END as display_name
                FROM users u
                LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
                LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                WHERE u.id = :user_id";

        $result = $this->likeRepo->fetchOne($sql, [':user_id' => $userId]);
        return $result ?: null;
    }

    /**
     * Get entity title for notification
     * 
     * @param string $type Entity type
     * @param int $entityId Entity ID
     * @return string|null
     */
    private function getEntityTitle(string $type, int $entityId): ?string
    {
        // This would query the appropriate content table based on type
        // For now, return a placeholder
        return match ($type) {
            'job' => $this->getJobTitle($entityId),
            'comment' => $this->getCommentPreview($entityId),
            'feed' => $this->getFeedTitle($entityId),
            default => null
        };
    }

    /**
     * Get job title (placeholder - implement with JobRepository)
     */
    private function getJobTitle(int $jobId): ?string
    {
        // TODO: Inject JobRepository and get title
        return "Job #{$jobId}";
    }

    /**
     * Get comment preview (placeholder)
     */
    private function getCommentPreview(int $commentId): ?string
    {
        // TODO: Get comment preview
        return "Comment #{$commentId}";
    }

    /**
     * Get feed title (placeholder)
     */
    private function getFeedTitle(int $feedId): ?string
    {
        // TODO: Get feed title
        return "Post #{$feedId}";
    }
}
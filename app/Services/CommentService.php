<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\CommentRepository;
use App\Enums\CommentableType;
use Exception;

/**
 * CommentService - Handles all comment business logic
 * 
 * Business rules:
 * - Users can only edit/delete their own comments
 * - Cannot reply to deleted comments
 * - Cannot like own comments
 * - Comments require content (min 2 chars, max 1000 chars)
 * - Rate limiting for spam prevention
 * 
 * @package App\Services
 */
class CommentService extends BaseService
{
    private CommentRepository $commentRepo;

    // Validation limits
    private const MIN_CONTENT_LENGTH = 2;
    private const MAX_CONTENT_LENGTH = 1000;
    private const MAX_COMMENTS_PER_MINUTE = 5;

    // Sort options (mirrored from repository for controller access)
    public const SORT_NEWEST = 'newest';
    public const SORT_POPULAR = 'popular';
    public const SORT_MOST_REPLIED = 'most_replied';
    public const SORT_OLDEST = 'oldest';

    public function __construct(CommentRepository $commentRepo)
    {
        parent::__construct();
        $this->commentRepo = $commentRepo;
    }

    // ==================== COMMENT CRUD ====================

    /**
     * Create a new comment
     */
    public function createComment(
        string $type,
        int $entityId,
        int $userId,
        string $content,
        ?int $parentId = null,
        ?array $media = null
    ): ?array {
        $this->logInfo('Creating comment', [
            'type' => $type,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'parent_id' => $parentId
        ]);
        $this->clearErrors();

        try {
            // Validate commentable type
            if (!CommentableType::isValid($type)) {
                $this->addError('type', 'Invalid comment type');
                return null;
            }

            // Validate content
            if (!$this->validateContent($content)) {
                return null;
            }

            // Check rate limiting
            if (!$this->checkRateLimit($userId)) {
                $this->addError('rate_limit', 'You are commenting too quickly. Please wait a moment.');
                return null;
            }

            // Validate parent comment if replying
            if ($parentId) {
                $parentComment = $this->commentRepo->findById($parentId);

                if (!$parentComment) {
                    $this->addError('parent_id', 'The comment you are replying to does not exist');
                    return null;
                }

                if ($parentComment['is_deleted']) {
                    $this->addError('parent_id', 'Cannot reply to a deleted comment');
                    return null;
                }

                // Prevent replying to replies (max depth 1)
                if ($parentComment['parent_id']) {
                    $this->addError('parent_id', 'Cannot reply to a reply (max depth reached)');
                    return null;
                }
            }

            // Sanitize content
            $content = $this->sanitizeContent($content);

            // Prepare media data
            $mediaType = $media['type'] ?? null;
            $mediaUrl = $media['url'] ?? null;
            $mediaMetadata = $media['metadata'] ?? null;

            // Create comment
            $commentId = $this->commentRepo->createComment(
                $type,
                $entityId,
                $userId,
                $content,
                $parentId,
                $mediaType,
                $mediaUrl,
                $mediaMetadata
            );

            if (!$commentId) {
                $this->addError('comment', 'Failed to create comment');
                return null;
            }

            // Get the created comment with user details
            $comment = $this->commentRepo->getCommentWithUser($commentId, $userId);

            // Send notification to parent comment author (if replying and not self)
            if ($parentId && $parentComment && $parentComment['user_id'] != $userId) {
                $this->sendReplyNotification($parentComment['user_id'], $comment);
            }

            $this->logInfo('Comment created successfully', ['comment_id' => $commentId]);
            return $comment;

        } catch (Exception $e) {
            $this->logError('Exception in createComment', ['error' => $e->getMessage()]);
            $this->addError('comment', 'An error occurred while creating the comment');
            return null;
        }
    }

    /**
     * Update a comment
     */
    public function updateComment(int $commentId, int $userId, string $content, ?array $media = null): bool
    {
        $this->logInfo('Updating comment', ['comment_id' => $commentId, 'user_id' => $userId]);
        $this->clearErrors();

        try {
            // Get existing comment
            $comment = $this->commentRepo->findById($commentId);

            if (!$comment) {
                $this->addError('comment', 'Comment not found');
                return false;
            }

            // Check ownership
            if ($comment['user_id'] != $userId) {
                $this->addError('permission', 'You can only edit your own comments');
                return false;
            }

            // Check if comment is deleted
            if ($comment['is_deleted']) {
                $this->addError('comment', 'Cannot edit a deleted comment');
                return false;
            }

            // Validate content
            if (!$this->validateContent($content)) {
                return false;
            }

            // Sanitize content
            $content = $this->sanitizeContent($content);

            // Prepare media data
            $mediaType = $media['type'] ?? $comment['media_type'];
            $mediaUrl = $media['url'] ?? $comment['media_url'];
            $mediaMetadata = $media['metadata'] ?? $comment['media_metadata'];

            // Update comment
            $result = $this->commentRepo->updateComment(
                $commentId,
                $userId,
                $content,
                $mediaType,
                $mediaUrl,
                $mediaMetadata ? json_decode($mediaMetadata, true) : null
            );

            if (!$result) {
                $this->addError('comment', 'Failed to update comment');
                return false;
            }

            $this->logInfo('Comment updated successfully', ['comment_id' => $commentId]);
            return true;

        } catch (Exception $e) {
            $this->logError('Exception in updateComment', ['error' => $e->getMessage()]);
            $this->addError('comment', 'An error occurred while updating the comment');
            return false;
        }
    }

    /**
     * Delete a comment (soft delete)
     */
    public function deleteComment(int $commentId, int $userId): bool
    {
        $this->logInfo('Deleting comment', ['comment_id' => $commentId, 'user_id' => $userId]);
        $this->clearErrors();

        try {
            // Get existing comment
            $comment = $this->commentRepo->findById($commentId);

            if (!$comment) {
                $this->addError('comment', 'Comment not found');
                return false;
            }

            // Check ownership
            if ($comment['user_id'] != $userId) {
                $this->addError('permission', 'You can only delete your own comments');
                return false;
            }

            // Check if already deleted
            if ($comment['is_deleted']) {
                $this->addError('comment', 'Comment already deleted');
                return false;
            }

            $result = $this->commentRepo->deleteComment($commentId, $userId);

            if (!$result) {
                $this->addError('comment', 'Failed to delete comment');
                return false;
            }

            $this->logInfo('Comment deleted successfully', ['comment_id' => $commentId]);
            return true;

        } catch (Exception $e) {
            $this->logError('Exception in deleteComment', ['error' => $e->getMessage()]);
            $this->addError('comment', 'An error occurred while deleting the comment');
            return false;
        }
    }

    // ==================== COMMENT LIKES ====================

    /**
     * Toggle like on a comment
     */
    public function toggleLike(int $commentId, int $userId): array
    {
        $this->logInfo('Toggling like', ['comment_id' => $commentId, 'user_id' => $userId]);

        try {
            // Get comment
            $comment = $this->commentRepo->findById($commentId);

            if (!$comment) {
                return ['success' => false, 'liked' => false, 'likes_count' => 0];
            }

            // Prevent liking own comment
            if ($comment['user_id'] == $userId) {
                return ['success' => false, 'liked' => false, 'likes_count' => $comment['likes_count']];
            }

            // Check if already liked
            $hasLiked = $this->commentRepo->userHasLiked($commentId, $userId);

            if ($hasLiked) {
                $success = $this->commentRepo->unlikeComment($commentId, $userId);
                $liked = false;
            } else {
                $success = $this->commentRepo->likeComment($commentId, $userId);
                $liked = true;

                // Send notification to comment author (if not self)
                if ($success && $comment['user_id'] != $userId) {
                    $this->sendLikeNotification($comment['user_id'], $comment);
                }
            }

            // Get updated likes count
            $updatedComment = $this->commentRepo->findById($commentId);
            $likesCount = $updatedComment['likes_count'] ?? 0;

            return [
                'success' => $success,
                'liked' => $liked,
                'likes_count' => $likesCount
            ];

        } catch (Exception $e) {
            $this->logError('Exception in toggleLike', ['error' => $e->getMessage()]);
            return ['success' => false, 'liked' => false, 'likes_count' => 0];
        }
    }

    /**
     * Check if user has liked a comment
     */
    public function hasLiked(int $commentId, int $userId): bool
    {
        return $this->commentRepo->userHasLiked($commentId, $userId);
    }

    // ==================== GET COMMENTS ====================

    /**
     * Get comments for an entity
     */
    public function getComments(
        string $type,
        int $entityId,
        ?int $userId = null,
        int $page = 1,
        ?int $perPage = null,
        string $sort = CommentRepository::SORT_NEWEST
    ): array {
        $this->logInfo('Fetching comments', compact('type', 'entityId', 'page'));

        // Validate type
        if (!CommentableType::isValid($type)) {
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage ?? 20,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_next' => false,
                    'has_prev' => false
                ],
                'sort' => $sort
            ];
        }

        // Use default per page if not specified
        if ($perPage === null) {
            $enumType = CommentableType::from($type);
            $perPage = $enumType->defaultPerPage();
        }

        return $this->commentRepo->getComments($type, $entityId, $userId, $page, $perPage, $sort);
    }

    /**
     * Load more replies for a comment (AJAX)
     */
    public function loadMoreReplies(int $commentId, ?int $userId = null, int $offset = 0, int $limit = 10): array
    {
        return $this->commentRepo->loadMoreReplies($commentId, $userId, $offset, $limit);
    }

    /**
     * Get a single comment
     */
    public function getComment(int $commentId, ?int $userId = null): ?array
    {
        return $this->commentRepo->getCommentWithUser($commentId, $userId);
    }

    /**
     * Get comments by user
     */
    public function getUserComments(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $comments = $this->commentRepo->getCommentsByUser($userId, $perPage, $offset);

        // Get total count (using count from the method or fallback)
        $total = count($comments);

        return [
            'data' => $comments,
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

    /**
     * Get recent comments (activity feed)
     */
    public function getRecentComments(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $comments = $this->commentRepo->getRecentComments($perPage, $offset);

        // Get total count (approximate from query results)
        $total = count($comments);

        return [
            'data' => $comments,
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

    /**
     * Get comments that mention a user
     */
    public function getMentions(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $comments = $this->commentRepo->getCommentsMentioningUser($userId, $perPage);

        // Apply offset manually since repository doesn't support offset
        $comments = array_slice($comments, $offset, $perPage);
        $total = count($this->commentRepo->getCommentsMentioningUser($userId, 1000));

        return [
            'data' => $comments,
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

    // ==================== MODERATION ====================

    /**
     * Hide a comment (moderator action)
     * Note: Your repository doesn't have hideComment, so using update method
     */
    public function hideComment(int $commentId, int $moderatorId): bool
    {
        $this->logInfo('Hiding comment', ['comment_id' => $commentId, 'moderator_id' => $moderatorId]);

        if (!$this->isModerator($moderatorId)) {
            $this->addError('permission', 'Only moderators can hide comments');
            return false;
        }

        return $this->commentRepo->update($commentId, ['is_hidden' => 1]);
    }

    /**
     * Unhide a comment (moderator action)
     */
    public function unhideComment(int $commentId, int $moderatorId): bool
    {
        $this->logInfo('Unhiding comment', ['comment_id' => $commentId, 'moderator_id' => $moderatorId]);

        if (!$this->isModerator($moderatorId)) {
            $this->addError('permission', 'Only moderators can unhide comments');
            return false;
        }

        return $this->commentRepo->update($commentId, ['is_hidden' => 0]);
    }

    /**
     * Pin a comment (moderator action)
     */
    public function pinComment(int $commentId, int $moderatorId): bool
    {
        $this->logInfo('Pinning comment', ['comment_id' => $commentId, 'moderator_id' => $moderatorId]);

        if (!$this->isModerator($moderatorId)) {
            $this->addError('permission', 'Only moderators can pin comments');
            return false;
        }

        // First, get the comment to know its entity
        $comment = $this->commentRepo->findById($commentId);
        if (!$comment) {
            $this->addError('comment', 'Comment not found');
            return false;
        }

        // Unpin any other pinned comments on the same entity
        $sql = "UPDATE {$this->commentRepo->getTable()} SET is_pinned = 0, pinned_at = NULL 
                WHERE commentable_type = :type AND commentable_id = :entity_id AND is_pinned = 1";
        $this->commentRepo->execute($sql, [
            ':type' => $comment['commentable_type'],
            ':entity_id' => $comment['commentable_id']
        ]);

        // Pin the selected comment
        return $this->commentRepo->update($commentId, [
            'is_pinned' => 1,
            'pinned_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Unpin a comment (moderator action)
     */
    public function unpinComment(int $commentId, int $moderatorId): bool
    {
        $this->logInfo('Unpinning comment', ['comment_id' => $commentId, 'moderator_id' => $moderatorId]);

        if (!$this->isModerator($moderatorId)) {
            $this->addError('permission', 'Only moderators can unpin comments');
            return false;
        }

        return $this->commentRepo->update($commentId, ['is_pinned' => 0, 'pinned_at' => null]);
    }

    // ==================== STATISTICS ====================

    /**
     * Get comment statistics for an entity
     */
    public function getStats(string $type, int $entityId): array
    {
        return $this->commentRepo->getCommentStats($type, $entityId);
    }

    /**
     * Get total comment count for an entity
     */
    public function getCommentCount(string $type, int $entityId): int
    {
        return $this->commentRepo->countComments($type, $entityId);
    }

    /**
     * Get top commenters for an entity
     */
    public function getTopCommenters(string $type, int $entityId, int $limit = 10): array
    {
        return $this->commentRepo->getTopCommenters($type, $entityId, $limit);
    }

    // ==================== SEARCH ====================

    /**
     * Search comments
     */
    public function searchComments(string $query, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $results = $this->commentRepo->searchComments($query, $perPage, $offset);
        $total = $this->commentRepo->countSearchResults($query);

        return [
            'data' => $results,
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

    // ==================== ADMIN ====================

    /**
     * Get admin dashboard statistics
     */
    public function getAdminStatistics(): array
    {
        $stats = [];

        // Get comments by status
        $activeComments = $this->commentRepo->getCommentsByStatus(null, 1000);
        $hiddenComments = $this->commentRepo->getCommentsByStatus('hidden', 1000);
        $deletedComments = $this->commentRepo->getCommentsByStatus('deleted', 1000);

        $stats['active_comments'] = count($activeComments);
        $stats['hidden_comments'] = count($hiddenComments);
        $stats['deleted_comments'] = count($deletedComments);
        $stats['total_comments'] = $stats['active_comments'] + $stats['hidden_comments'] + $stats['deleted_comments'];

        // Get statistics by type
        $stats['by_type'] = $this->getStatsByType();

        // Get top commenters
        $stats['top_commenters'] = $this->getTopCommentersAll();

        return $stats;
    }

    /**
     * Get statistics by commentable type
     */
    private function getStatsByType(): array
    {
        $types = CommentableType::toArray();
        $stats = [];

        foreach ($types as $type) {
            $count = $this->commentRepo->countComments($type, 0); // This won't work as expected
            // Since countComments requires entityId, we need a different approach
            // Using a SQL query directly
            $sql = "SELECT COUNT(*) as total, AVG(likes_count) as avg_likes, COUNT(DISTINCT user_id) as unique_users
                    FROM comments 
                    WHERE commentable_type = :type AND parent_id IS NULL AND is_deleted = 0";
            $result = $this->commentRepo->fetchOne($sql, [':type' => $type]);

            $stats[] = [
                'type' => $type,
                'total_comments' => (int) ($result['total'] ?? 0),
                'avg_likes' => round($result['avg_likes'] ?? 0, 2),
                'unique_commenters' => (int) ($result['unique_users'] ?? 0)
            ];
        }

        // Sort by total_comments descending
        usort($stats, fn($a, $b) => $b['total_comments'] - $a['total_comments']);

        return $stats;
    }

    /**
     * Get top commenters across all content
     */
    private function getTopCommentersAll(int $limit = 10): array
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
                    COUNT(c.id) as comment_count,
                    SUM(c.likes_count) as total_likes_received
                FROM comments c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
                LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                WHERE c.is_deleted = 0
                GROUP BY u.id
                ORDER BY comment_count DESC, total_likes_received DESC";

        $limit = max(0, min(1000, $limit));
        $sql .= " LIMIT {$limit}";

        return $this->commentRepo->fetchAll($sql);
    }

    /**
     * Get comments for moderation
     */
    public function getCommentsForModeration(?string $status = null, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;

        $comments = $this->commentRepo->getCommentsByStatus($status, $perPage, $offset);
        $total = $this->commentRepo->countCommentsByStatus($status);

        return [
            'data' => $comments,
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

    // ==================== VALIDATION HELPERS ====================

    /**
     * Validate comment content
     */
    private function validateContent(string $content): bool
    {
        $trimmed = trim($content);
        $length = mb_strlen($trimmed, 'UTF-8');

        if ($length < self::MIN_CONTENT_LENGTH) {
            $this->addError('content', 'Comment must be at least ' . self::MIN_CONTENT_LENGTH . ' characters');
            return false;
        }

        if ($length > self::MAX_CONTENT_LENGTH) {
            $this->addError('content', 'Comment cannot exceed ' . self::MAX_CONTENT_LENGTH . ' characters');
            return false;
        }

        // Check for spammy content
        if ($this->isSpammy($trimmed)) {
            $this->addError('content', 'Your comment looks spammy. Please revise.');
            return false;
        }

        return true;
    }

    /**
     * Check if content looks spammy
     */
    private function isSpammy(string $content): bool
    {
        // Check for too many links
        $linkCount = preg_match_all('/https?:\/\//', $content);
        if ($linkCount > 3) {
            return true;
        }

        // Check for all caps (with minimum length)
        if (mb_strlen($content) > 20 && strtoupper($content) === $content) {
            return true;
        }

        return false;
    }

    /**
     * Sanitize comment content
     */
    private function sanitizeContent(string $content): string
    {
        // Remove excessive whitespace
        $content = preg_replace('/\s+/', ' ', trim($content));

        // Basic HTML escaping
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        return $content;
    }

    /**
     * Check rate limit for user
     */
    private function checkRateLimit(int $userId): bool
    {
        // TODO: Implement rate limiting with Redis or database
        return true;
    }

    /**
     * Check if user is moderator
     */
    private function isModerator(int $userId): bool
    {
        // TODO: Check user role from session or database
        return $userId === 1;
    }

    // ==================== NOTIFICATIONS ====================

    /**
     * Send notification for reply
     */
    private function sendReplyNotification(int $recipientId, array $comment): void
    {
        $this->logInfo('Reply notification would be sent', [
            'recipient_id' => $recipientId,
            'comment_id' => $comment['id']
        ]);
    }

    /**
     * Send notification for like
     */
    private function sendLikeNotification(int $recipientId, array $comment): void
    {
        $this->logInfo('Like notification would be sent', [
            'recipient_id' => $recipientId,
            'comment_id' => $comment['id']
        ]);
    }
}
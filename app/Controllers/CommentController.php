<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\CommentService;
use App\Helpers\ApiResponse;
use App\Enums\CommentableType;

/**
 * CommentController - Handles all comment-related HTTP requests
 * 
 * Endpoints:
 * - GET    /api/comments/{type}/{id}          Get comments for entity
 * - POST   /api/comments/{type}/{id}          Create comment
 * - PUT    /api/comments/{id}                 Update comment
 * - DELETE /api/comments/{id}                 Delete comment
 * - POST   /api/comments/{id}/like            Toggle like
 * - GET    /api/comments/{id}/replies         Load more replies
 * - GET    /api/comments/user/{userId}        Get user's comments
 * - GET    /api/comments/recent               Get recent comments
 * - GET    /api/comments/mentions             Get mentions for current user
 * - POST   /api/comments/{id}/pin             Pin comment (moderator)
 * - POST   /api/comments/{id}/hide            Hide comment (moderator)
 * - GET    /api/comments/stats/{type}/{id}    Get comment stats
 * - GET    /api/comments/search               Search comments
 * 
 * @package App\Controllers
 */
class CommentController extends BaseController
{
    private CommentService $commentService;

    public function __construct(CommentService $commentService)
    {
        parent::__construct();
        $this->commentService = $commentService;
    }

    // ==================== MAIN COMMENTS ENDPOINTS ====================

    /**
     * Get comments for an entity
     * 
     * @param string $type Entity type (job, feed, profile, etc.)
     * @param int $id Entity ID
     * @return mixed
     */
    public function index(array $params)
    {
        try {
            $type = (string) ($params['type'] ?? null);
            $id = (int) ($params['id'] ?? null);
            //Validate entity type
            if (!CommentableType::isValid($type)) {
                return ApiResponse::error('Invalid comment type', [], 400);
            }

            $page = (int) ($this->get('page') ?? 1);
            $perPage = (int) ($this->get('per_page') ?? 20);
            $sort = $this->get('sort') ?? CommentService::SORT_NEWEST;

            // Validate sort option
            $validSorts = ['newest', 'popular', 'most_replied', 'oldest'];
            if (!in_array($sort, $validSorts)) {
                $sort = CommentService::SORT_NEWEST;
            }

            $comments = $this->commentService->getComments(
                $type,
                $id,
                $this->userId,
                $page,
                $perPage,
                $sort
            );

            return ApiResponse::success($comments, 'Comments fetched successfully');

        } catch (\Throwable $e) {
            error_log('Error fetching comments: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch comments', [], 500);
        }
    }

    /**
     * Create a new comment
     * 
     * @param string $type Entity type
     * @param int $id Entity ID
     * @return mixed
     */
    public function store(array $params)
    {
        try { 
            $type = (string) ($params['type'] ?? null);
            $id = (int) ($params['id'] ?? null);
            // Require authentication
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to comment');
            }

            // Validate entity type
            if (!CommentableType::isValid($type)) {
                return ApiResponse::error('Invalid comment type', [], 400);
            }

            // Get input
            $input = $this->getJsonInput();

            // Validate required fields
            if (empty($input['content'])) {
                return ApiResponse::error('Comment content is required', [], 422);
            }

            // Create comment
            $comment = $this->commentService->createComment(
                $type,
                $id,
                $this->userId,
                $input['content'],
                $input['parent_id'] ?? null,
                $input['media'] ?? null
            );

            if (!$comment) {
                return ApiResponse::error(
                    'Failed to create comment',
                    $this->commentService->getErrors(),
                    422
                );
            }

            return ApiResponse::success($comment, 'Comment created successfully', 201);

        } catch (\Throwable $e) {
            error_log('Error creating comment: ' . $e->getMessage());
            return ApiResponse::error('Failed to create comment', [], 500);
        }
    }

    /**
     * Update a comment
     * 
     * @param int $id Comment ID
     * @return mixed
     */
    public function update(int $id)
    {
        try {
            // Require authentication
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to edit comment');
            }

            // Get input
            $input = $this->getJsonInput();

            if (empty($input['content'])) {
                return ApiResponse::error('Comment content is required', [], 422);
            }

            // Update comment
            $result = $this->commentService->updateComment(
                $id,
                $this->userId,
                $input['content'],
                $input['media'] ?? null
            );

            if (!$result) {
                return ApiResponse::error(
                    'Failed to update comment',
                    $this->commentService->getErrors(),
                    422
                );
            }

            return ApiResponse::success(null, 'Comment updated successfully');

        } catch (\Throwable $e) {
            error_log('Error updating comment: ' . $e->getMessage());
            return ApiResponse::error('Failed to update comment', [], 500);
        }
    }

    /**
     * Delete a comment
     * 
     * @param int $id Comment ID
     * @return mixed
     */
    public function destroy(int $id)
    {
        try {
            // Require authentication
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to delete comment');
            }

            $result = $this->commentService->deleteComment($id, $this->userId);

            if (!$result) {
                return ApiResponse::error(
                    'Failed to delete comment',
                    $this->commentService->getErrors(),
                    422
                );
            }

            return ApiResponse::success(null, 'Comment deleted successfully');

        } catch (\Throwable $e) {
            error_log('Error deleting comment: ' . $e->getMessage());
            return ApiResponse::error('Failed to delete comment', [], 500);
        }
    }

    // ==================== LIKES ====================

    /**
     * Toggle like on a comment
     * 
     * @param int $id Comment ID
     * @return mixed
     */
    public function toggleLike(int $id)
    {
        try {
            // Require authentication
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to like comments');
            }

            $result = $this->commentService->toggleLike($id, $this->userId);

            if (!$result['success']) {
                return ApiResponse::error('Failed to toggle like', [], 422);
            }

            return ApiResponse::success($result);

        } catch (\Throwable $e) {
            error_log('Error toggling like: ' . $e->getMessage());
            return ApiResponse::error('Failed to toggle like', [], 500);
        }
    }

    // ==================== REPLIES ====================

    /**
     * Load more replies for a comment
     * 
     * @param int $id Comment ID
     * @return mixed
     */
    public function loadReplies(int $id)
    {
        try {
            $offset = (int) ($this->get('offset') ?? 0);
            $limit = (int) ($this->get('limit') ?? 10);

            $replies = $this->commentService->loadMoreReplies($id, $this->userId, $offset, $limit);

            return ApiResponse::success($replies);

        } catch (\Throwable $e) {
            error_log('Error loading replies: ' . $e->getMessage());
            return ApiResponse::error('Failed to load replies', [], 500);
        }
    }

    // ==================== USER COMMENTS ====================

    /**
     * Get comments by user
     * 
     * @param int $userId User ID
     * @return mixed
     */
    public function userComments(int $userId)
    {
        try {
            $page = (int) ($this->get('page') ?? 1);
            $perPage = (int) ($this->get('per_page') ?? 20);

            // Check if requesting own comments or is admin
            if ($this->userId != $userId && !$this->isAdmin()) {
                return ApiResponse::forbidden('You can only view your own comments');
            }

            $comments = $this->commentService->getUserComments($userId, $page, $perPage);

            return ApiResponse::success($comments);

        } catch (\Throwable $e) {
            error_log('Error fetching user comments: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch comments', [], 500);
        }
    }

    /**
     * Get recent comments (activity feed)
     * 
     * @return mixed
     */
    public function recent()
    {
        try {
            $page = (int) ($this->get('page') ?? 1);
            $perPage = (int) ($this->get('per_page') ?? 20);

            $comments = $this->commentService->getRecentComments($page, $perPage);

            return ApiResponse::success($comments);

        } catch (\Throwable $e) {
            error_log('Error fetching recent comments: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch recent comments', [], 500);
        }
    }

    /**
     * Get mentions for current user
     * 
     * @return mixed
     */
    public function mentions()
    {
        try {
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to view mentions');
            }

            $page = (int) ($this->get('page') ?? 1);
            $perPage = (int) ($this->get('per_page') ?? 20);

            $comments = $this->commentService->getMentions($this->userId, $page, $perPage);

            return ApiResponse::success($comments);

        } catch (\Throwable $e) {
            error_log('Error fetching mentions: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch mentions', [], 500);
        }
    }

    // ==================== MODERATION ====================

    /**
     * Pin a comment (moderator only)
     * 
     * @param int $id Comment ID
     * @return mixed
     */
    public function pin(int $id)
    {
        try {
            if (!$this->isAdmin()) {
                return ApiResponse::forbidden('Only administrators can pin comments');
            }

            $result = $this->commentService->pinComment($id, $this->userId);

            if (!$result) {
                return ApiResponse::error(
                    'Failed to pin comment',
                    $this->commentService->getErrors(),
                    422
                );
            }

            return ApiResponse::success(null, 'Comment pinned successfully');

        } catch (\Throwable $e) {
            error_log('Error pinning comment: ' . $e->getMessage());
            return ApiResponse::error('Failed to pin comment', [], 500);
        }
    }

    /**
     * Unpin a comment (moderator only)
     * 
     * @param int $id Comment ID
     * @return mixed
     */
    public function unpin(int $id)
    {
        try {
            if (!$this->isAdmin()) {
                return ApiResponse::forbidden('Only administrators can unpin comments');
            }

            $result = $this->commentService->unpinComment($id, $this->userId);

            if (!$result) {
                return ApiResponse::error(
                    'Failed to unpin comment',
                    $this->commentService->getErrors(),
                    422
                );
            }

            return ApiResponse::success(null, 'Comment unpinned successfully');

        } catch (\Throwable $e) {
            error_log('Error unpinning comment: ' . $e->getMessage());
            return ApiResponse::error('Failed to unpin comment', [], 500);
        }
    }

    /**
     * Hide a comment (moderator only)
     * 
     * @param int $id Comment ID
     * @return mixed
     */
    public function hide(int $id)
    {
        try {
            if (!$this->isAdmin()) {
                return ApiResponse::forbidden('Only administrators can hide comments');
            }

            $result = $this->commentService->hideComment($id, $this->userId);

            if (!$result) {
                return ApiResponse::error(
                    'Failed to hide comment',
                    $this->commentService->getErrors(),
                    422
                );
            }

            return ApiResponse::success(null, 'Comment hidden successfully');

        } catch (\Throwable $e) {
            error_log('Error hiding comment: ' . $e->getMessage());
            return ApiResponse::error('Failed to hide comment', [], 500);
        }
    }

    /**
     * Unhide a comment (moderator only)
     * 
     * @param int $id Comment ID
     * @return mixed
     */
    public function unhide(int $id)
    {
        try {
            if (!$this->isAdmin()) {
                return ApiResponse::forbidden('Only administrators can unhide comments');
            }

            $result = $this->commentService->unhideComment($id, $this->userId);

            if (!$result) {
                return ApiResponse::error(
                    'Failed to unhide comment',
                    $this->commentService->getErrors(),
                    422
                );
            }

            return ApiResponse::success(null, 'Comment unhidden successfully');

        } catch (\Throwable $e) {
            error_log('Error unhiding comment: ' . $e->getMessage());
            return ApiResponse::error('Failed to unhide comment', [], 500);
        }
    }

    // ==================== STATISTICS ====================

    /**
     * Get comment statistics for an entity
     * 
     * @param string $type Entity type
     * @param int $id Entity ID
     * @return mixed
     */
    public function stats(string $type, int $id)
    {
        try {
            if (!CommentableType::isValid($type)) {
                return ApiResponse::error('Invalid comment type', [], 400);
            }

            $stats = $this->commentService->getStats($type, $id);

            return ApiResponse::success($stats);

        } catch (\Throwable $e) {
            error_log('Error fetching comment stats: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch stats', [], 500);
        }
    }

    /**
     * Get top commenters for an entity
     * 
     * @param string $type Entity type
     * @param int $id Entity ID
     * @return mixed
     */
    public function topCommenters(string $type, int $id)
    {
        try {
            if (!CommentableType::isValid($type)) {
                return ApiResponse::error('Invalid comment type', [], 400);
            }

            $limit = (int) ($this->get('limit') ?? 10);
            $commenters = $this->commentService->getTopCommenters($type, $id, $limit);

            return ApiResponse::success($commenters);

        } catch (\Throwable $e) {
            error_log('Error fetching top commenters: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch top commenters', [], 500);
        }
    }

    // ==================== SEARCH ====================

    /**
     * Search comments
     * 
     * @return mixed
     */
    public function search()
    {
        try {
            $query = $this->get('q');

            if (empty($query)) {
                return ApiResponse::error('Search query is required', [], 422);
            }

            if (strlen($query) < 2) {
                return ApiResponse::error('Search query must be at least 2 characters', [], 422);
            }

            $page = (int) ($this->get('page') ?? 1);
            $perPage = (int) ($this->get('per_page') ?? 20);

            $results = $this->commentService->searchComments($query, $page, $perPage);

            return ApiResponse::success($results);

        } catch (\Throwable $e) {
            error_log('Error searching comments: ' . $e->getMessage());
            return ApiResponse::error('Failed to search comments', [], 500);
        }
    }

    // ==================== ADMIN ====================

    /**
     * Get admin dashboard statistics
     * 
     * @return mixed
     */
    public function adminStats()
    {
        try {
            if (!$this->isAdmin()) {
                return ApiResponse::forbidden('Only administrators can view admin stats');
            }

            $stats = $this->commentService->getAdminStatistics();

            return ApiResponse::success($stats);

        } catch (\Throwable $e) {
            error_log('Error fetching admin stats: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch admin stats', [], 500);
        }
    }

    /**
     * Get comments for moderation
     * 
     * @return mixed
     */
    public function moderationQueue()
    {
        try {
            if (!$this->isAdmin()) {
                return ApiResponse::forbidden('Only administrators can access moderation queue');
            }

            $status = $this->get('status');
            $page = (int) ($this->get('page') ?? 1);
            $perPage = (int) ($this->get('per_page') ?? 50);

            $comments = $this->commentService->getCommentsForModeration($status, $page, $perPage);

            return ApiResponse::success($comments);

        } catch (\Throwable $e) {
            error_log('Error fetching moderation queue: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch moderation queue', [], 500);
        }
    }

    // ==================== HELPER METHODS ====================

  

}
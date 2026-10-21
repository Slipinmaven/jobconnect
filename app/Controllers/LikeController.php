<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Helpers\ApiResponse;
use App\Services\LikeService;

/**
 * LikeController - Handles all like-related API endpoints
 */
class LikeController extends BaseController
{
    private LikeService $likeService;

    public function __construct(LikeService $likeService)
    {
        parent::__construct();
        $this->likeService = $likeService;
    }

    // ==================== CORE OPERATIONS ====================

    /**
     * Like an entity
     * POST /api/like
     *
     * Request body (JSON): {
     *   "type": "job|comment|feed",
     *   "entity_id": 123,
     *   "content_owner_id": 456 (optional, for notifications)
     * }
     */
    public function like(): void
    {
        $this->requireApiAuth();

        $input = $this->getJsonInput();

        $type = $input['type'] ?? null;
        $entityId = (int) ($input['entity_id'] ?? 0);
        $contentOwnerId = isset($input['content_owner_id']) ? (int) $input['content_owner_id'] : null;

        if (!$type || !$entityId) {
            ApiResponse::validationError(['type' => 'Type is required', 'entity_id' => 'Entity ID is required']);
            return;
        }

        $result = $this->likeService->like($type, $entityId, $this->userId, $contentOwnerId);

        if ($result['success']) {
            ApiResponse::success([
                'liked' => $result['liked'],
                'likes_count' => $result['likes_count']
            ], 'Liked successfully');
        } else {
            $errors = $this->likeService->getErrors() ?: ['like' => $result['errors'] ?? 'Failed to like'];
            ApiResponse::validationError($errors, $result['message'] ?? 'Like failed');
        }
    }

    /**
     * Unlike an entity
     * POST /api/unlike
     *
     * Request body (JSON): {
     *   "type": "job|comment|feed",
     *   "entity_id": 123
     * }
     */
    public function unlike(): void
    {
        $this->requireApiAuth();

        $input = $this->getJsonInput();

        $type = $input['type'] ?? null;
        $entityId = (int) ($input['entity_id'] ?? 0);

        if (!$type || !$entityId) {
            ApiResponse::validationError(['type' => 'Type is required', 'entity_id' => 'Entity ID is required']);
            return;
        }

        $result = $this->likeService->unlike($type, $entityId, $this->userId);

        if ($result['success']) {
            ApiResponse::success([
                'liked' => $result['liked'],
                'likes_count' => $result['likes_count']
            ], 'Unliked successfully');
        } else {
            $errors = $this->likeService->getErrors() ?: ['like' => $result['errors'] ?? 'Failed to unlike'];
            ApiResponse::validationError($errors, $result['message'] ?? 'Unlike failed');
        }
    }

    /**
     * Toggle like status (like if not liked, unlike if liked)
     * POST /api/like/toggle
     *
     * Request body (JSON): {
     *   "type": "job|comment|feed",
     *   "entity_id": 123,
     *   "content_owner_id": 456 (optional, for notifications)
     * }
     */
    public function toggle(): void
    {
        $this->requireApiAuth();

        $input = $this->getJsonInput();

        $type = $input['type'] ?? null;
        $entityId = (int) ($input['entity_id'] ?? 0);
        $contentOwnerId = isset($input['content_owner_id']) ? (int) $input['content_owner_id'] : null;

        if (!$type || !$entityId) {
            ApiResponse::validationError(['type' => 'Type is required', 'entity_id' => 'Entity ID is required']);
            return;
        }

        $result = $this->likeService->toggleLike($type, $entityId, $this->userId, $contentOwnerId);

        if ($result['success']) {
            ApiResponse::success([
                'liked' => $result['liked'],
                'likes_count' => $result['likes_count']
            ], $result['liked'] ? 'Liked successfully' : 'Unliked successfully');
        } else {
            $errors = $this->likeService->getErrors() ?: ['like' => $result['errors'] ?? 'Toggle failed'];
            ApiResponse::validationError($errors, $result['message'] ?? 'Toggle failed');
        }
    }

    // ==================== QUERY METHODS ====================

    /**
     * Check if the authenticated user has liked an entity
     * GET /api/like/status?type=job&entity_id=123
     */
    public function status(): void
    {
        $this->requireApiAuth();

        $type = $this->get('type');
        $entityId = (int) $this->get('entity_id', 0);

        if (!$type || !$entityId) {
            ApiResponse::validationError(['type' => 'Type is required', 'entity_id' => 'Entity ID is required']);
            return;
        }

        $hasLiked = $this->likeService->hasLiked($type, $entityId, $this->userId);

        ApiResponse::success([
            'liked' => $hasLiked
        ]);
    }

    /**
     * Get like count for a single entity
     * GET /api/like/count?type=job&entity_id=123
     */
    public function count(): void
    {
        $type = $this->get('type');
        $entityId = (int) $this->get('entity_id', 0);

        if (!$type || !$entityId) {
            ApiResponse::validationError(['type' => 'Type is required', 'entity_id' => 'Entity ID is required']);
            return;
        }

        $count = $this->likeService->getLikeCount($type, $entityId);

        ApiResponse::success([
            'likes_count' => $count
        ]);
    }

    /**
     * Get like counts for multiple entities
     * GET /api/like/counts?type=job&entity_ids[]=1&entity_ids[]=2
     */
    public function counts(): void
    {
        $type = $this->get('type');
        $entityIds = $this->get('entity_ids');

        if (!$type) {
            ApiResponse::validationError(['type' => 'Type is required']);
            return;
        }

        if (!is_array($entityIds)) {
            $entityIds = array_filter(explode(',', $this->get('entity_ids', '')));
        }

        if (empty($entityIds)) {
            ApiResponse::validationError(['entity_ids' => 'At least one entity ID is required']);
            return;
        }

        $counts = $this->likeService->getLikeCounts($type, array_map('intval', $entityIds));

        ApiResponse::success([
            'counts' => $counts
        ]);
    }

    /**
     * Get like statuses for multiple entities (for authenticated user)
     * GET /api/like/statuses?type=job&entity_ids[]=1&entity_ids[]=2
     */
    public function statuses(): void
    {
        $this->requireApiAuth();

        $type = $this->get('type');
        $entityIds = $this->get('entity_ids');

        if (!$type) {
            ApiResponse::validationError(['type' => 'Type is required']);
            return;
        }

        if (!is_array($entityIds)) {
            $entityIds = array_filter(explode(',', $this->get('entity_ids', '')));
        }

        if (empty($entityIds)) {
            ApiResponse::validationError(['entity_ids' => 'At least one entity ID is required']);
            return;
        }

        $statuses = $this->likeService->getLikeStatus($type, array_map('intval', $entityIds), $this->userId);

        ApiResponse::success([
            'statuses' => $statuses
        ]);
    }

    /**
     * Get users who liked an entity
     * GET /api/like/users?type=job&entity_id=123&limit=10
     */
    public function users(): void
    {
        $type = $this->get('type');
        $entityId = (int) $this->get('entity_id', 0);
        $limit = (int) $this->get('limit', 50);

        if (!$type || !$entityId) {
            ApiResponse::validationError(['type' => 'Type is required', 'entity_id' => 'Entity ID is required']);
            return;
        }

        $users = $this->likeService->getLikingUsers($type, $entityId, $limit);

        ApiResponse::success([
            'users' => $users
        ]);
    }

    /**
     * Get entities liked by the authenticated user
     * GET /api/user/likes?type=job&page=1&per_page=20
     */
    public function userLikes(): void
    {
        $this->requireApiAuth();

        $type = $this->get('type'); // optional
        $page = (int) $this->get('page', 1);
        $perPage = (int) $this->get('per_page', 20);

        $result = $this->likeService->getUserLikes($this->userId, $type ?: null, $page, $perPage);

        ApiResponse::success($result);
    }

    // ==================== ADMIN ENDPOINTS ====================

    /**
     * Get overall like statistics
     * GET /api/admin/like/statistics
     */
    public function statistics(): void
    {
        $this->requireApiRole('admin');

        $stats = $this->likeService->getStatistics();

        ApiResponse::success($stats);
    }

    /**
     * Clean up orphaned likes for a specific entity type
     * POST /api/admin/like/cleanup
     *
     * Request body (JSON): {
     *   "type": "job",
     *   "content_table": "jobs",
     *   "content_id_field": "id"
     * }
     */
    public function cleanup(): void
    {
        $this->requireApiRole('admin');

        $input = $this->getJsonInput();

        $type = $input['type'] ?? null;
        $contentTable = $input['content_table'] ?? null;
        $contentIdField = $input['content_id_field'] ?? 'id';

        if (!$type || !$contentTable) {
            ApiResponse::validationError(['type' => 'Type is required', 'content_table' => 'Content table is required']);
            return;
        }

        $deleted = $this->likeService->cleanupOrphanedLikes($type, $contentTable, $contentIdField);

        ApiResponse::success([
            'deleted' => $deleted
        ], "Cleaned up {$deleted} orphaned likes");
    }

    /**
     * Delete all likes for a specific entity
     * DELETE /api/admin/like/entity
     *
     * Request body (JSON): {
     *   "type": "job",
     *   "entity_id": 123
     * }
     */
    public function deleteEntityLikes(): void
    {
        $this->requireApiRole('admin');

        $input = $this->getJsonInput();

        $type = $input['type'] ?? null;
        $entityId = (int) ($input['entity_id'] ?? 0);

        if (!$type || !$entityId) {
            ApiResponse::validationError(['type' => 'Type is required', 'entity_id' => 'Entity ID is required']);
            return;
        }

        $success = $this->likeService->deleteAllForEntity($type, $entityId);

        if ($success) {
            ApiResponse::success(null, 'All likes for entity deleted');
        } else {
            ApiResponse::error('Failed to delete likes for entity', [], 500);
        }
    }

    /**
     * Delete all likes by a user
     * DELETE /api/admin/like/user
     *
     * Request body (JSON): {
     *   "user_id": 123
     * }
     */
    public function deleteUserLikes(): void
    {
        $this->requireApiRole('admin');

        $input = $this->getJsonInput();

        $userId = (int) ($input['user_id'] ?? 0);

        if (!$userId) {
            ApiResponse::validationError(['user_id' => 'User ID is required']);
            return;
        }

        $success = $this->likeService->deleteAllByUser($userId);

        if ($success) {
            ApiResponse::success(null, 'All likes by user deleted');
        } else {
            ApiResponse::error('Failed to delete likes by user', [], 500);
        }
    }
}
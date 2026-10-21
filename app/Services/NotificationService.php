<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\NotificationRepository;

class NotificationService extends BaseService
{
    private NotificationRepository $notificationRepo;

    // Notification types – used in the 'type' column
    public const TYPE_JOB_APPLICATION = 'job_application';
    public const TYPE_COMMENT         = 'comment';
    public const TYPE_LIKE            = 'like';
    public const TYPE_SYSTEM          = 'system';
    public const TYPE_JOB_EXPIRY      = 'job_expiry';

    public function __construct(NotificationRepository $notificationRepo)
    {
        parent::__construct();
        $this->notificationRepo = $notificationRepo;
    }

    // ==================== CREATE NOTIFICATION ====================

    /**
     * Create a notification with generated action URL.
     *
     * @param int $userId        Target user ID
     * @param string $type       Notification type (use class constants)
     * @param string $title      Short title
     * @param string $message    Full message text
     * @param int|null $actorId  User who triggered the notification (optional)
     * @param string|null $actionKey    Key to determine the URL pattern
     * @param array $actionParams        Parameters to fill the URL pattern
     * @param int|null $notifiableId     Related entity ID (e.g., job ID, comment ID)
     * @param string|null $notifiableType Related entity class/type (e.g., 'job', 'comment')
     * @return int|null Notification ID on success, null on failure
     */
    public function create(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?int $actorId = null,
        ?string $actionKey = null,
        array $actionParams = [],
        ?int $notifiableId = null,
        ?string $notifiableType = null
    ): ?int {
        $this->logInfo('Creating notification', [
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title
        ]);
        $this->clearErrors();

        // Basic validation
        if (empty($userId)) {
            $this->addError('user_id', 'User ID is required');
            return null;
        }
        if (empty($type)) {
            $this->addError('type', 'Notification type is required');
            return null;
        }
        if (empty($title)) {
            $this->addError('title', 'Title is required');
            return null;
        }

        // Generate action URL based on the key and parameters
        $actionUrl = $this->generateActionUrl($actionKey, $actionParams);

        $data = [
            'user_id'         => $userId,
            'type'            => $type,
            'notifiable_id'   => $notifiableId,
            'notifiable_type' => $notifiableType,
            'actor_id'        => $actorId,
            'title'           => $title,
            'message'         => $message,
            'action_url'      => $actionUrl,
            'action_key'      => $actionKey,
            'is_read'         => 0,
        ];

        $id = $this->notificationRepo->createNotification($data);
        if ($id) {
            $this->logInfo('Notification created', ['id' => $id]);
        } else {
            $this->logError('Failed to create notification', $data);
            $this->addError('notification', 'Could not create notification');
        }
        return $id;
    }

    /**
     * Generate a URL based on the action key and parameters.
     * Add or modify patterns here to support new notification types.
     *
     * @param string|null $actionKey
     * @param array $params
     * @return string|null
     */
    public function generateActionUrl(?string $actionKey, array $params): ?string
    {
        if (empty($actionKey)) {
            return null;
        }

        $routes = [
            'view_job'          => '/jobs/%d',
            'view_application'  => '/applications/%d',
            'view_comment'      => '/comments/%d',
            'accept_application'=> '/applications/%d/accept',
            'reject_application'=> '/applications/%d/reject',
            'profile'           => '/profile/%d',
            'job_applicants'    => '/jobs/%d/applicants',
        ];

        if (!isset($routes[$actionKey])) {
            return null;
        }

        // Ensure we have enough parameters
        $pattern = $routes[$actionKey];
        $count = substr_count($pattern, '%d');
        if (count($params) < $count) {
            $this->logWarning('Missing parameters for action URL', [
                'action_key' => $actionKey,
                'required'   => $count,
                'given'      => count($params)
            ]);
            return null;
        }

        return vsprintf($pattern, array_slice($params, 0, $count));
    }

    // ==================== FETCH NOTIFICATIONS ====================

    /**
     * Get paginated notifications for a user.
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getUserNotifications(int $userId, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $notifications = $this->notificationRepo->getUserNotifications($userId, $perPage, $offset);
        $total = $this->notificationRepo->countUserNotifications($userId);
        $unreadCount = $this->notificationRepo->countUnread($userId);

        return [
            'data'         => $notifications,
            'total'        => $total,
            'unread_count' => $unreadCount,
            'page'         => $page,
            'per_page'     => $perPage,
            'total_pages'  => ceil($total / $perPage)
        ];
    }

    /**
     * Get a single notification by ID (with optional ownership check).
     *
     * @param int $id
     * @param int|null $userId
     * @return array|null
     */
    public function getNotification(int $id, ?int $userId = null): ?array
    {
        return $this->notificationRepo->findById($id, $userId);
    }

    // ==================== READ / UNREAD ====================

    /**
     * Mark a single notification as read (with ownership check).
     *
     * @param int $userId
     * @param int $notificationId
     * @return bool
     */
    public function markAsRead(int $userId, int $notificationId): bool
    {
        $notification = $this->notificationRepo->findById($notificationId, $userId);
        if (!$notification) {
            $this->addError('notification', 'Notification not found or not owned by user');
            return false;
        }
        if ($notification['is_read']) {
            return true; // already read
        }
        return $this->notificationRepo->markAsRead($notificationId);
    }

    /**
     * Mark all unread notifications for a user as read.
     *
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead(int $userId): bool
    {
        return $this->notificationRepo->markAllAsRead($userId);
    }

    // ==================== DELETE ====================

    /**
     * Delete a notification (with ownership check).
     *
     * @param int $userId
     * @param int $notificationId
     * @return bool
     */
    public function delete(int $userId, int $notificationId): bool
    {
        $deleted = $this->notificationRepo->deleteNotification($notificationId, $userId);
        if (!$deleted) {
            $this->addError('notification', 'Could not delete notification');
        }
        return $deleted;
    }

    // ==================== UTILITIES ====================

    /**
     * Get unread count for a user.
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->notificationRepo->countUnread($userId);
    }
}
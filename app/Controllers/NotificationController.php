<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\NotificationService;
use App\Helpers\ApiResponse;
use Exception;

/**
 * NotificationController - Handles all notification HTTP requests
 *
 * Endpoints:
 * - GET    /api/notifications                  Get current user's notifications (paginated)
 * - POST   /api/notifications/{id}/read        Mark a notification as read
 * - POST   /api/notifications/read-all         Mark all notifications as read
 * - DELETE /api/notifications/{id}             Delete a notification
 *
 * @package App\Controllers
 */
class NotificationController extends BaseController
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Get current user's notifications (paginated)
     *
     * @return mixed
     */
    public function index()
    {
        try {
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to view notifications');
            }

            $page = (int) ($this->get('page') ?? 1);
            $perPage = (int) ($this->get('per_page') ?? 20);

            $notifications = $this->notificationService->getUserNotifications(
                $this->userId,
                $page,
                $perPage
            );

            return ApiResponse::success($notifications, 'Notifications retrieved successfully');
        } catch (Exception $e) {
            error_log('Error fetching notifications: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch notifications', [], 500);
        }
    }

    /**
     * Mark a single notification as read
     *
     * @param int $id Notification ID
     * @return mixed
     */
    public function markRead(int $id)
    {
        try {
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to mark notifications');
            }

            $result = $this->notificationService->markAsRead($this->userId, $id);

            if (!$result) {
                return ApiResponse::error(
                    'Failed to mark notification as read',
                    $this->notificationService->getErrors(),
                    422
                );
            }

            return ApiResponse::success(null, 'Notification marked as read');
        } catch (Exception $e) {
            error_log('Error marking notification as read: ' . $e->getMessage());
            return ApiResponse::error('Failed to mark notification as read', [], 500);
        }
    }

    /**
     * Mark all notifications as read for the current user
     *
     * @return mixed
     */
    public function markAllRead()
    {
        try {
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to mark notifications');
            }

            $result = $this->notificationService->markAllAsRead($this->userId);

            if (!$result) {
                return ApiResponse::error(
                    'Failed to mark all notifications as read',
                    $this->notificationService->getErrors(),
                    422
                );
            }

            return ApiResponse::success(null, 'All notifications marked as read');
        } catch (Exception $e) {
            error_log('Error marking all notifications as read: ' . $e->getMessage());
            return ApiResponse::error('Failed to mark all notifications as read', [], 500);
        }
    }

    /**
     * Delete a notification
     *
     * @param int $id Notification ID
     * @return mixed
     */
    public function destroy(int $id)
    {
        try {
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login to delete notifications');
            }

            $result = $this->notificationService->delete($this->userId, $id);

            if (!$result) {
                return ApiResponse::error(
                    'Failed to delete notification',
                    $this->notificationService->getErrors(),
                    422
                );
            }

            return ApiResponse::success(null, 'Notification deleted successfully');
        } catch (Exception $e) {
            error_log('Error deleting notification: ' . $e->getMessage());
            return ApiResponse::error('Failed to delete notification', [], 500);
        }
    }

    /**
     * Get unread count (optional convenience endpoint)
     *
     * @return mixed
     */
    public function unreadCount()
    {
        try {
            if (!$this->isAuthenticated()) {
                return ApiResponse::unauthorized('Please login');
            }

            $count = $this->notificationService->getUnreadCount($this->userId);

            return ApiResponse::success(['unread_count' => $count], 'Unread count retrieved');
        } catch (Exception $e) {
            error_log('Error fetching unread count: ' . $e->getMessage());
            return ApiResponse::error('Failed to fetch unread count', [], 500);
        }
    }
}
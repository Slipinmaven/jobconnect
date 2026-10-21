<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\NotificationOutboxRepository;

/**
 * NotificationOutboxService - Manages notification outbox operations
 * 
 * Provides a simple interface for queuing notifications with standardized payload format.
 */
class NotificationOutboxService extends BaseService
{
    private NotificationOutboxRepository $outboxRepo;

    public function __construct(NotificationOutboxRepository $outboxRepo)
    {
        parent::__construct();
        $this->outboxRepo = $outboxRepo;
    }

    /**
     * Send an email notification
     * 
     * @param int $userId User ID
     * @param string $template Email template name
     * @param array $vars Template variables
     * @param int|null $notificationId Optional notification ID
     * @return int Outbox ID
     */
    public function sendEmail(int $userId, string $template, array $vars, ?int $notificationId = null): int
    {
        $payload = [
            'email_template' => $template,
            'email_vars' => $vars
        ];

        return $this->outboxRepo->enqueue($notificationId, $userId, 'email', $payload);
    }

    /**
     * Send a generic notification (future use for SMS, push, etc.)
     * 
     * @param int $userId User ID
     * @param string $channel Notification channel (email, sms, push)
     * @param array $payload Notification payload
     * @param int|null $notificationId Optional notification ID
     * @return int Outbox ID
     */
    public function sendNotification(int $userId, string $channel, array $payload, ?int $notificationId = null): int
    {
        return $this->outboxRepo->enqueue($notificationId, $userId, $channel, $payload);
    }
}
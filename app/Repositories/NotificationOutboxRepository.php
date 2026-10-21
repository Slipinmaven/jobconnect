<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;

class NotificationOutboxRepository extends BaseRepository
{
    protected string $table = 'notification_outbox';

    public function enqueue(?int $notificationId, int $userId, string $channel, array $payload, ?string $nextAttemptAt = null): int
{
    // Sanitize payload
    array_walk_recursive($payload, function(&$value) {
        if (is_string($value)) {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        }
    });
    
    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // Check encoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON encoding error: " . json_last_error_msg());
        error_log("Problematic payload: " . print_r($payload, true));
        throw new \InvalidArgumentException("Invalid payload: " . json_last_error_msg());
    }
    
    // Validate JSON using our custom method
    if (!$this->isValidJson($jsonPayload)) {
        error_log("JSON validation failed: " . json_last_error_msg());
        error_log("Invalid JSON string: " . $jsonPayload);
        throw new \InvalidArgumentException("Generated JSON is not valid: " . json_last_error_msg());
    }

    $stmt = $this->pdo->prepare(
        "INSERT INTO notification_outbox (
            notification_id, user_id, channel, payload,
            next_attempt_at
        ) VALUES (?, ?, ?, ?, ?)"
    );

    try {
        $stmt->execute([
            $notificationId,
            $userId,
            $channel,
            $jsonPayload,
            $nextAttemptAt
        ]);
    } catch (PDOException $e) {
        error_log("Failed to insert notification. Payload: " . $jsonPayload);
        error_log("Error: " . $e->getMessage());
        throw $e;
    }

    return (int)$this->pdo->lastInsertId();
}
    public function claim(string $channel, string $workerId, int $limit = 50): array
    {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
            SELECT id, notification_id, user_id, channel, payload, attempts
            FROM notification_outbox
            WHERE channel = ?
              AND delivered_at IS NULL
              AND locked_at IS NULL
              AND (next_attempt_at IS NULL OR next_attempt_at <= NOW())
            ORDER BY id ASC
            LIMIT ?
            FOR UPDATE
        ");

            $stmt->bindValue(1, $channel);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$rows) {
                $this->pdo->commit();
                return [];
            }

            $ids = array_column($rows, 'id');
            $in = implode(',', array_fill(0, count($ids), '?'));

            $lock = $this->pdo->prepare("
            UPDATE notification_outbox
            SET locked_at = NOW(), locked_by = ?
            WHERE id IN ($in)
        ");

            $lock->execute(array_merge([$workerId], $ids));

            $this->pdo->commit();

            foreach ($rows as &$r) {
                $r['attempts'] = (int) $r['attempts'];
                $r['payload'] = json_decode($r['payload'], true) ?: [];
            }

            return $rows;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function markDelivered(int $id): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE notification_outbox
             SET 
                 delivered_at = NOW(),
                 attempts = attempts + 1,
                 last_error = NULL,
                 next_attempt_at = NULL,
                 locked_at = NULL,
                 locked_by = NULL
             WHERE id = ?"
        );
        $stmt->execute([$id]);

        // Optional: log for debugging
        error_log("Job $id marked as delivered with " . $stmt->rowCount() . " rows updated");
    }

    public function markFailed(int $id, int $currentAttempts, string $error): void
    {
        // Backoff: 1m, 5m, 30m, 2h, 1d...
        $minutes = match (true) {
            $currentAttempts <= 0 => 1,
            $currentAttempts === 1 => 5,
            $currentAttempts === 2 => 30,
            $currentAttempts === 3 => 120,
            default => 1440, // 24 hours
        };

        $stmt = $this->pdo->prepare(
            "UPDATE notification_outbox
             SET 
                 attempts = attempts + 1,
                 last_error = ?,
                 next_attempt_at = DATE_ADD(NOW(), INTERVAL $minutes MINUTE),
                 locked_at = NULL,
                 locked_by = NULL
             WHERE id = ?"
        );
        $stmt->execute([$error, $id]);

        error_log("Job $id marked as failed (attempt " . ($currentAttempts + 1) . "), next retry in $minutes minutes");
    }

    public function unlockStuck(int $minutesStuck = 10): int
    {
        $stmt = $this->pdo->prepare(
            "UPDATE notification_outbox
             SET locked_at = NULL,
                 locked_by = NULL
             WHERE delivered_at IS NULL
               AND locked_at IS NOT NULL
               AND locked_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        $stmt->execute([$minutesStuck]);

        $unlocked = $stmt->rowCount();
        if ($unlocked > 0) {
            error_log("Unlocked $unlocked stuck jobs");
        }
        return $unlocked;
    }
    // Add this method to your NotificationOutboxRepository class
private function isValidJson($string): bool
{
    if (is_null($string)) {
        return false;
    }
    
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}
}

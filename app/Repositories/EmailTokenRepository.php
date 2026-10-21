<?php

namespace App\Repositories;

use App\Core\BaseRepository;

class EmailTokenRepository extends BaseRepository
{
    protected string $table = 'email_tokens';

    public function findValidToken(string $token): ?array
    {
        error_log("========== TESTING findValidToken ==========");
        error_log("Looking for token: " . $token);

        $stmt = $this->pdo->prepare(
            "SELECT id, user_id FROM email_tokens
         WHERE token = ? AND used_at IS NULL AND expires_at > ?"
        );
        $stmt->execute([$token, gmdate('Y-m-d H:i:s')]);
        $result = $stmt->fetch();

        // Log the result BEFORE returning
        error_log("Result: " . print_r($result, true));
        error_log("==========================================");

        // Also write to file
        file_put_contents(
            __DIR__ . '/../../storage/logs/token_test.log',
            "[" . date('Y-m-d H:i:s') . "] Token: $token, Result: " . print_r($result, true) . PHP_EOL,
            FILE_APPEND
        );

        return $result ?: null; // Return null if no result
    }
    public function invalidateAllForUser(int $userId, string $type)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE email_tokens SET used_at = NOW() , is_active = 0
            WHERE user_id = ? AND token_type = ? AND used_at IS NULL"
        );
        return $stmt->execute([$userId, $type]);
    }

    public function markUsed(int $id)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE email_tokens SET used_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    public function someMethod()
    {
        // Test if error_log works at all
        error_log("TEST: This is a test log message");

        // Test file writing
        file_put_contents(
            __DIR__ . '/../../storage/logs/test.log',
            "Test write at " . date('Y-m-d H:i:s') . PHP_EOL,
            FILE_APPEND
        );
    }
}

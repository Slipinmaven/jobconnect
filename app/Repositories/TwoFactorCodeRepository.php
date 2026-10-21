<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;

/**
 * TwoFactorCodeRepository - Manages 2FA codes for login, password reset, email verification
 * 
 * SECURITY FEATURES:
 * - Codes are stored hashed (never plaintext)
 * - Automatic expiration
 * - Attempt limiting to prevent brute force
 * - Type-specific codes (login_2fa, password_reset, email_verification)
 */
class TwoFactorCodeRepository extends BaseRepository
{
    protected string $table = 'two_factor_codes';
    protected string $primaryKey = 'id';
    
    // Maximum attempts before code is invalidated
    private const MAX_ATTEMPTS = 3;
    
    /**
     * Generate and store a new 2FA code
     * 
     * @param int $userId User ID
     * @param string $type Code type (login_2fa, password_reset, email_verification)
     * @param string $codeHash Hashed code
     * @param int $expiresInMinutes Code lifetime
     * @return int|null New code ID
     */
public function createCode(int $userId, string $type, string $codeHash, int $expiresInMinutes = 10): ?int
    {
        // Invalidate any existing unused codes of same type for this user
        $this->invalidateUserCodes($userId, $type);
        
        // Use direct SQL with NOW() to ensure MySQL time is used
        $sql = "INSERT INTO {$this->table} 
                (user_id, type, code_hash, expires_at, attempts, created_at) 
                VALUES 
                (:user_id, :type, :code_hash, DATE_ADD(NOW(), INTERVAL :expires_minutes MINUTE), 0, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            ':user_id' => $userId,
            ':type' => $type,
            ':code_hash' => $codeHash,
            ':expires_minutes' => $expiresInMinutes
        ]);
        
        if ($success) {
            return (int)$this->pdo->lastInsertId();
        }
        
        return null;
    }
    
    /**
     * Verify a 2FA code
     * 
     * @param int $userId User ID
     * @param string $type Code type
     * @param string $codeHash Hashed code to verify
     * @return bool True if valid
     */
    public function verifyCode(int $userId, string $type, string $codeHash): bool
    {
        // Find valid code
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id 
                AND type = :type 
                AND expires_at > NOW() 
                AND attempts < :max_attempts
                ORDER BY created_at DESC 
                LIMIT 1";
        
        $code = $this->fetchOne($sql, [
            ':user_id' => $userId,
            ':type' => $type,
            ':max_attempts' => self::MAX_ATTEMPTS
        ]);
        
        if (!$code) {
            return false;
        }
        
        // Increment attempts
        $this->incrementAttempts($code['id']);
        
        // Verify hash
        if ($code['code_hash'] === $codeHash) {
            // Delete used code
            $this->delete($code['id']);
            return true;
        }
        
        return false;
    }

    /**
     * Validate a code without deleting it (non-destructive check)
     * Used for checking if a token is valid before executing the actual operation
     * 
     * @param int $userId User ID
     * @param string $type Code type
     * @param string $codeHash Hashed code to validate
     * @return bool True if valid and not expired
     */
public function validateCode(int $userId, string $type, string $codeHash): bool
{
    try {
        // Start transaction
        $this->pdo->beginTransaction();
        
        // Find valid code
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id 
                AND type = :type 
                AND expires_at > NOW() 
                AND attempts < :max_attempts
                AND used = 0
                ORDER BY created_at DESC 
                LIMIT 1";
        
        $code = $this->fetchOne($sql, [
            ':user_id' => $userId,
            ':type' => $type,
            ':max_attempts' => self::MAX_ATTEMPTS
        ]);
        
        if (!$code) {
            error_log("No valid code found for user $userId and type $type");
            $this->pdo->rollBack();
            return false;
        }
        
        // Verify hash (use password_verify if hashed with password_hash)
        $isValid = hash_equals($code['code_hash'], $codeHash);
        
        if ($isValid) {
            // Mark as used on success
            $updateSql = "UPDATE {$this->table} 
                         SET used = 1, used_at = NOW() 
                         WHERE id = :id";
            $this->execute($updateSql, [':id' => $code['id']]);
            $this->pdo->commit();
            error_log("Code validated successfully for user $userId and type $type");
            return true;
        } else {
            // Increment attempts on failure
            $updateSql = "UPDATE {$this->table} 
                         SET attempts = attempts + 1 
                         WHERE id = :id";
            $this->execute($updateSql, [':id' => $code['id']]);
            $this->pdo->commit();
            error_log("Code validation failed for user $userId and type $type. Attempts incremented.");
            return false;
        }
        
    } catch (\Exception $e) {
        $this->pdo->rollBack();
        error_log("Code validation error: " . $e->getMessage());
        return false;
    }
}
    /**
     * Increment attempt counter for a code
     */
    private function incrementAttempts(int $codeId): void
    {
        $sql = "UPDATE {$this->table} 
                SET attempts = attempts + 1 
                WHERE id = :id";
        
        $this->execute($sql, [':id' => $codeId]);
    }
    
    /**
     * Invalidate all unused codes of a specific type for a user
     */
    public function invalidateUserCodes(int $userId, string $type): int
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE user_id = :user_id 
                AND type = :type 
                AND expires_at > NOW()";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':type' => $type
        ]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Clean up expired codes
     * 
     * @return int Number of deleted codes
     */
    public function cleanupExpired(): int
    {
        $sql = "DELETE FROM {$this->table} WHERE expires_at <= NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    /**
     * Get recent codes for a user (for debugging/audit)
     */
    public function getUserCodes(int $userId, ?string $type = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $params = [':user_id' => $userId];
        
        if ($type) {
            $sql .= " AND type = :type";
            $params[':type'] = $type;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 10";
        
        return $this->fetchAll($sql, $params);
    }
    
    /**
     * Check if user has a valid code of a specific type
     */
    public function hasValidCode(int $userId, string $type): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE user_id = :user_id 
                AND type = :type 
                AND expires_at > NOW() 
                AND attempts < :max_attempts";
        
        $result = $this->fetchOne($sql, [
            ':user_id' => $userId,
            ':type' => $type,
            ':max_attempts' => self::MAX_ATTEMPTS
        ]);
        
        return ($result['count'] ?? 0) > 0;
    }
    
    /**
     * Get statistics about code usage
     */
    public function getCodeStats(): array
    {
        $stats = [
            'total_active' => 0,
            'by_type' => [],
            'avg_attempts' => 0
        ];
        
        // Total active codes
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE expires_at > NOW()";
        $result = $this->fetchOne($sql);
        $stats['total_active'] = $result['count'] ?? 0;
        
        // Count by type
        $sql = "SELECT type, COUNT(*) as count FROM {$this->table} GROUP BY type";
        $stats['by_type'] = $this->fetchAll($sql);
        
        // Average attempts
        $sql = "SELECT AVG(attempts) as avg FROM {$this->table}";
        $result = $this->fetchOne($sql);
        $stats['avg_attempts'] = round($result['avg'] ?? 0, 2);
        
        return $stats;
    }
}
<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;

/**
 * SessionRepository - Manages user sessions
 * 
 * SECURITY FEATURES:
 * - Token hashing for secure storage
 * - Automatic expiration handling
 * - Device fingerprinting for security
 * - Session revocation capability
 */
class SessionRepository extends BaseRepository
{
    protected string $table = 'sessions';
    protected string $primaryKey = 'id';
    
    /**
     * Create a new session
     * 
     * @param int $userId User ID
     * @param string $tokenHash Hashed session token
     * @param array $data Additional session data (ip, user_agent, fingerprint)
     * @param int $expiresInMinutes Session lifetime in minutes
     * @return int|null New session ID
     */
    public function createSession(int $userId, string $tokenHash, array $data = [], int $expiresInMinutes = 60): ?int
    {
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresInMinutes} minutes"));
        
        $sessionData = [
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'fingerprint' => $data['fingerprint'] ?? null,
            'expires_at' => $expiresAt,
            'last_activity' => $now,
            'is_revoked' => 0
        ];
        
        return $this->create($sessionData);
    }
    
    /**
     * Find valid session by token
     */
    public function findByToken(string $tokenHash): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE token_hash = :token_hash 
                AND expires_at > NOW() 
                AND is_revoked = 0 
                LIMIT 1";
        
        return $this->fetchOne($sql, [':token_hash' => $tokenHash]);
    }
    
    /**
     * Get all active sessions for a user
     */
    public function getUserActiveSessions(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id 
                AND expires_at > NOW() 
                AND is_revoked = 0 
                ORDER BY last_activity DESC";
        
        return $this->fetchAll($sql, [':user_id' => $userId]);
    }
    
    /**
     * Update session activity (refresh token)
     */
    public function updateActivity(int $sessionId): bool
    {
        return $this->update($sessionId, [
            'last_activity' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Revoke a specific session
     */
    public function revokeSession(int $sessionId): bool
    {
        return $this->update($sessionId, [
            'is_revoked' => 1
        ]);
    }
    
    /**
     * Revoke all sessions for a user except current one
     */
    public function revokeAllExcept(int $userId, int $currentSessionId): int
    {
        $sql = "UPDATE {$this->table} 
                SET is_revoked = 1 
                WHERE user_id = :user_id 
                AND id != :current_session_id 
                AND is_revoked = 0";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':current_session_id' => $currentSessionId
        ]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Revoke all sessions for a user
     */
    public function revokeAllForUser(int $userId): bool
    {
        return $this->update($userId, ['is_revoked' => 1], 'user_id');
    }
    
    /**
     * Clean up expired sessions
     * 
     * @return int Number of deleted sessions
     */
    public function deleteExpired(): int
    {
        $sql = "DELETE FROM {$this->table} WHERE expires_at <= NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    /**
     * Get session statistics
     */
    public function getSessionStats(): array
    {
        $stats = [
            'total_active' => 0,
            'unique_users' => 0,
            'expired_today' => 0,
            'by_user_agent' => []
        ];
        
        // Total active sessions
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE expires_at > NOW() AND is_revoked = 0";
        $result = $this->fetchOne($sql);
        $stats['total_active'] = $result['count'] ?? 0;
        
        // Unique users with active sessions
        $sql = "SELECT COUNT(DISTINCT user_id) as count FROM {$this->table} WHERE expires_at > NOW() AND is_revoked = 0";
        $result = $this->fetchOne($sql);
        $stats['unique_users'] = $result['count'] ?? 0;
        
        // Expired today
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE DATE(expires_at) = CURDATE()";
        $result = $this->fetchOne($sql);
        $stats['expired_today'] = $result['count'] ?? 0;
        
        // Group by user agent (simplified)
        $sql = "SELECT 
                    CASE 
                        WHEN user_agent LIKE '%Mobile%' THEN 'Mobile'
                        WHEN user_agent LIKE '%Windows%' THEN 'Windows'
                        WHEN user_agent LIKE '%Mac%' THEN 'Mac'
                        ELSE 'Other'
                    END as device_type,
                    COUNT(*) as count 
                FROM {$this->table} 
                WHERE expires_at > NOW() 
                GROUP BY device_type";
        
        $stats['by_user_agent'] = $this->fetchAll($sql);
        
        return $stats;
    }
    
    /**
     * Override update to handle different conditions
     */
    public function update(int $id, array $data, string $keyField = 'id'): bool
    {
        if ($keyField === 'user_id') {
            $sql = "UPDATE {$this->table} SET ";
            $sets = [];
            foreach (array_keys($data) as $column) {
                $sets[] = "{$column} = :{$column}";
            }
            $sql .= implode(', ', $sets) . " WHERE user_id = :user_id";
            
            $params = array_merge($data, ['user_id' => $id]);
            return $this->execute($sql, $params);
        }
        
        return parent::update($id, $data);
    }
}
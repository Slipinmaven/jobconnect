<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;
use PDOException;

/**
 * UserRepository - Handles all user database operations
 * 
 * SECURITY FEATURES:
 * - Prepared statements for all queries (prevents SQL injection)
 * - Returns only safe data (excludes sensitive fields by default)
 * - Uses consistent data types and validation
 * - Handles edge cases gracefully
 * 
 * DATA SANITIZATION:
 * - Never returns password hashes in public methods
 * - Returns only fields safe for external use
 * - Consistent date/time handling
 */
class UserRepository extends BaseRepository
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    
    /**
     * Fields that are safe to return in public contexts
     * Excludes sensitive data like password_hash, totp_secret
     */
    private array $safeFields = [
        'id', 'email', 'role', 'auth_provider', 'google_id',
        'email_verified_at', 'theme', 'two_factor_enabled',
        'is_blocked', 'blocked_until', 'failed_login_attempts',
        'last_failed_login', 'created_at', 'updated_at', 'avatar'
    ];
    
    // ==================== USER-SPECIFIC QUERIES ====================
    
    /**
     * Find user by email
     * 
     * @param string $email User's email
     * @param bool $includeSensitive Whether to include sensitive fields (for authentication)
     * @return array|null User data or null if not found
     */
    public function findByEmail(string $email, bool $includeSensitive = false): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $user = $this->fetchOne($sql, [':email' => $email]);
        
        if (!$user) {
            return null;
        }
        
        return $includeSensitive ? $user : $this->filterSensitiveData($user);
    }
    
    /**
     * Find user by Google ID
     */
    public function findByGoogleId(string $googleId, bool $includeSensitive = false): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE google_id = :google_id AND auth_provider = 'google' LIMIT 1";
        $user = $this->fetchOne($sql, [':google_id' => $googleId]);
        
        if (!$user) {
            return null;
        }
        
        return $includeSensitive ? $user : $this->filterSensitiveData($user);
    }
    
    /**
     * Find user with active session
     */
    public function findBySessionToken(string $tokenHash): ?array
    {
        $sql = "SELECT u.* FROM {$this->table} u
                INNER JOIN sessions s ON u.id = s.user_id
                WHERE s.token_hash = :token_hash 
                AND s.expires_at > NOW() 
                AND s.is_revoked = 0
                LIMIT 1";
        
        $user = $this->fetchOne($sql, [':token_hash' => $tokenHash]);
        
        return $user ? $this->filterSensitiveData($user) : null;
    }
    
    /**
     * Create a new user
     * 
     * @param array $userData User data (email, password_hash, role, etc.)
     * @return int|null New user ID or null on failure
     */
    public function createUser(array $userData): ?int
    {
        // Ensure required fields are present (password may be null for OAuth)
        $requiredFields = ['email'];
        foreach ($requiredFields as $field) {
            if (!isset($userData[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if (!isset($userData['password_hash']) && empty($userData['auth_provider'])) {
            throw new \InvalidArgumentException("Missing required field: password_hash");
        }
        
        // Set defaults for optional fields
        $defaults = [
            'role' => 'jobseeker',
            'auth_provider' => 'local',
            'theme' => 'dark',
            'two_factor_enabled' => 0,
            'totp_enabled' => 0,
            'is_blocked' => 0,
            'failed_login_attempts' => 0
        ];
        
        $userData = array_merge($defaults, $userData);
        
        // Validate role
        if (!in_array($userData['role'], ['jobseeker', 'employer', 'admin'])) {
            throw new \InvalidArgumentException("Invalid role specified");
        }
        
        return $this->create($userData);
    }
    
    /**
     * Update user's last login information
     */
    public function updateLoginInfo(int $userId, ?string $ipAddress = null): bool
    {
        $data = [
            'failed_login_attempts' => 0,
            'last_failed_login' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($userId, $data);
    }
    
    /**
     * Increment failed login attempts
     */
    public function incrementFailedAttempts(string $email): void
    {
        $user = $this->findByEmail($email, true);
        if (!$user) {
            return;
        }
        
        $attempts = ($user['failed_login_attempts'] ?? 0) + 1;
        $now = date('Y-m-d H:i:s');
        
        $sql = "UPDATE {$this->table} 
                SET failed_login_attempts = :attempts, 
                    last_failed_login = :now 
                WHERE id = :id";
        
        $this->execute($sql, [
            ':attempts' => $attempts,
            ':now' => $now,
            ':id' => $user['id']
        ]);
        
        // Block user if too many attempts (e.g., 5 attempts)
        if ($attempts >= 5) {
            $this->blockUser($user['id'], 15); // Block for 15 minutes
        }
    }
    
    /**
     * Block a user temporarily
     * 
     * @param int $userId User ID
     * @param int $minutes Minutes to block for
     */
    public function blockUser(int $userId, int $minutes = 15): bool
    {
        $blockedUntil = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
        
        return $this->update($userId, [
            'is_blocked' => 1,
            'blocked_until' => $blockedUntil
        ]);
    }
    
    /**
     * Unblock a user
     */
    public function unblockUser(int $userId): bool
    {
        return $this->update($userId, [
            'is_blocked' => 0,
            'blocked_until' => null,
            'failed_login_attempts' => 0
        ]);
    }
    
    /**
     * Check if user is blocked
     */
    public function isBlocked(int $userId): bool
    {
        $user = $this->findById($userId);
        if (!$user || !$user['is_blocked']) {
            return false;
        }
        
        // Check if block has expired
        if ($user['blocked_until'] && strtotime($user['blocked_until']) < time()) {
            $this->unblockUser($userId);
            return false;
        }
        
        return true;
    }
    
    /**
     * Verify user email
     */
    public function verifyEmail(int $userId): bool
    {
        return $this->update($userId, [
            'email_verified_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Update user's password
     * 
     * @param int $userId User ID
     * @param string $newPasswordHash New password hash
     */
    public function updatePassword(int $userId, string $newPasswordHash): bool
    {
        return $this->update($userId, [
            'password_hash' => $newPasswordHash,
            'auth_provider' => 'local' // Reset to local if it was OAuth
        ]);
    }
    
    /**
     * Enable/disable two-factor authentication
     */
    public function setTwoFactorStatus(int $userId, bool $enabled, ?string $totpSecret = null): bool
    {
        $data = [
            'two_factor_enabled' => $enabled ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($totpSecret !== null) {
            $data['totp_secret'] = $enabled ? $totpSecret : null;
            $data['totp_enabled'] = $enabled ? 1 : 0;
        }
        
        return $this->update($userId, $data);
    }
    
    /**
     * Update user's theme preference
     */
    public function updateTheme(int $userId, string $theme): bool
    {
        if (!in_array($theme, ['light', 'dark'])) {
            throw new \InvalidArgumentException("Invalid theme preference");
        }
        
        return $this->update($userId, ['theme' => $theme]);
    }
    
    /**
     * Update user's role
     */
    public function updateRole(int $userId, string $role): bool
    {
        if (!in_array($role, ['jobseeker', 'employer', 'admin'])) {
            throw new \InvalidArgumentException("Invalid role");
        }
        
        return $this->update($userId, ['role' => $role]);
    }
    
    /**
     * Get users with their latest session info
     */
    public function getUsersWithSessions(): array
    {
        $sql = "SELECT u.*, 
                       s.last_activity as last_session_activity,
                       s.ip_address as last_ip,
                       s.user_agent as last_user_agent
                FROM {$this->table} u
                LEFT JOIN sessions s ON u.id = s.user_id
                WHERE s.last_activity = (
                    SELECT MAX(last_activity) 
                    FROM sessions s2 
                    WHERE s2.user_id = u.id
                ) OR s.id IS NULL";
        
        $users = $this->fetchAll($sql);
        
        return array_map([$this, 'filterSensitiveData'], $users);
    }
    
    /**
     * Get users by role
     */
    public function findByRole(string $role): array
    {
        $users = $this->findWhere(['role' => $role]);
        return array_map([$this, 'filterSensitiveData'], $users);
    }
    
    /**
     * Get unverified users (registered but email not verified)
     */
    public function getUnverifiedUsers(int $olderThanHours = 24): array
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$olderThanHours} hours"));
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE email_verified_at IS NULL 
                AND created_at < :cutoff
                ORDER BY created_at DESC";
        
        $users = $this->fetchAll($sql, [':cutoff' => $cutoff]);
        
        return array_map([$this, 'filterSensitiveData'], $users);
    }
    
    /**
     * Delete unverified users older than X days (cleanup task)
     */
    public function deleteUnverifiedUsers(int $olderThanDays = 7): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$olderThanDays} days"));
        
        $sql = "DELETE FROM {$this->table} 
                WHERE email_verified_at IS NULL 
                AND created_at < :cutoff";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cutoff' => $cutoff]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats(): array
    {
        $stats = [
            'total' => 0,
            'verified' => 0,
            'unverified' => 0,
            'blocked' => 0,
            'two_factor_enabled' => 0,
            'by_role' => [
                'jobseeker' => 0,
                'employer' => 0,
                'admin' => 0
            ],
            'by_auth_provider' => [
                'local' => 0,
                'google' => 0
            ]
        ];
        
        // Total users
        $stats['total'] = $this->count();
        
        // Verified users
        $stats['verified'] = $this->count(['email_verified_at IS NOT NULL' => null]);
        
        // Unverified users
        $stats['unverified'] = $this->count(['email_verified_at IS NULL' => null]);
        
        // Blocked users
        $stats['blocked'] = $this->count(['is_blocked' => 1]);
        
        // 2FA enabled
        $stats['two_factor_enabled'] = $this->count(['two_factor_enabled' => 1]);
        
        // Count by role
        $sql = "SELECT role, COUNT(*) as count FROM {$this->table} GROUP BY role";
        $roleCounts = $this->fetchAll($sql);
        foreach ($roleCounts as $row) {
            $stats['by_role'][$row['role']] = (int)$row['count'];
        }
        
        // Count by auth provider
        $sql = "SELECT auth_provider, COUNT(*) as count FROM {$this->table} GROUP BY auth_provider";
        $providerCounts = $this->fetchAll($sql);
        foreach ($providerCounts as $row) {
            $stats['by_auth_provider'][$row['auth_provider']] = (int)$row['count'];
        }
        
        return $stats;
    }
    
    /**
     * Search users by email or role
     */
    public function searchUsers(string $query, ?string $role = null, int $limit = 20): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email LIKE :query";
        $params = [':query' => "%{$query}%"];
        
        if ($role) {
            $sql .= " AND role = :role";
            $params[':role'] = $role;
        }
        
        $sql .= " ORDER BY created_at DESC";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        $users = $this->fetchAll($sql, $params);
        
        return array_map([$this, 'filterSensitiveData'], $users);
    }
    
    // ==================== BULK OPERATIONS ====================
    
    /**
     * Bulk update user roles
     * 
     * @param array $userIds Array of user IDs
     * @param string $newRole New role for all users
     * @return int Number of users updated
     */
    public function bulkUpdateRole(array $userIds, string $newRole): int
    {
        if (empty($userIds)) {
            return 0;
        }
        
        if (!in_array($newRole, ['jobseeker', 'employer', 'admin'])) {
            throw new \InvalidArgumentException("Invalid role");
        }
        
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = "UPDATE {$this->table} SET role = ?, updated_at = NOW() WHERE id IN ({$placeholders})";
        
        $params = array_merge([$newRole], $userIds);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Bulk block/unblock users
     */
    public function bulkSetBlocked(array $userIds, bool $blocked, ?int $minutes = 15): int
    {
        if (empty($userIds)) {
            return 0;
        }
        
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        
        if ($blocked) {
            $blockedUntil = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
            $sql = "UPDATE {$this->table} 
                    SET is_blocked = 1, blocked_until = ?, updated_at = NOW() 
                    WHERE id IN ({$placeholders})";
            $params = array_merge([$blockedUntil], $userIds);
        } else {
            $sql = "UPDATE {$this->table} 
                    SET is_blocked = 0, blocked_until = NULL, failed_login_attempts = 0, updated_at = NOW() 
                    WHERE id IN ({$placeholders})";
            $params = $userIds;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    // ==================== HELPER METHODS ====================
    
    /**
     * Filter out sensitive data from user array
     * 
     * @param array $user Full user data
     * @return array User data with sensitive fields removed
     */
    private function filterSensitiveData(array $user): array
    {
        return array_intersect_key($user, array_flip($this->safeFields));
    }
    
    /**
     * Override findById to filter sensitive data
     */
    public function findById(int $id): ?array
    {
        $user = parent::findById($id);
        return $user ? $this->filterSensitiveData($user) : null;
    }
    
    /**
     * Override findAll to filter sensitive data
     */
    public function findAll(string $orderBy = 'id DESC', ?int $limit = null): array
    {
        $users = parent::findAll($orderBy, $limit);
        return array_map([$this, 'filterSensitiveData'], $users);
    }
    
    /**
     * Override findWhere to filter sensitive data
     */
    public function findWhere(array $conditions, string $orderBy = 'id DESC'): array
    {
        $users = parent::findWhere($conditions, $orderBy);
        return array_map([$this, 'filterSensitiveData'], $users);
    }
    
    /**
     * Get user for authentication (includes sensitive data)
     * This should ONLY be called from AuthService
     */
    public function getForAuthentication(string $email): ?array
    {
        return $this->findByEmail($email, true);
    }

    /**
     * Get user by ID for authentication (includes sensitive data like password_hash)
     * This should ONLY be called from AuthService for operations that need password verification
     */
    public function getForAuthenticationById(int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Validate user exists and is not blocked
     */
    public function validateUserStatus(int $userId): array
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            return ['valid' => false, 'reason' => 'User not found'];
        }
        
        if ($this->isBlocked($userId)) {
            $blockedUntil = $user['blocked_until'] ?? 'unknown';
            return ['valid' => false, 'reason' => "User is blocked until {$blockedUntil}"];
        }
        
        return ['valid' => true, 'user' => $user];
    }
    
    /**
     * Update user's avatar
     */
    public function updateAvatar(int $userId, ?string $avatarPath): bool
    {
        $this->execute(
            "UPDATE {$this->table} SET avatar = :avatar WHERE {$this->primaryKey} = :id",
            [':avatar' => $avatarPath, ':id' => $userId]
        );

        $user = $this->fetchOne(
            "SELECT avatar FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1",
            [':id' => $userId]
        );

        return $user !== null && ($user['avatar'] ?? null) === $avatarPath;
    }
}
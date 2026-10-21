<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\SessionRepository;
use App\Repositories\TrustedDeviceRepository;
use App\Repositories\TwoFactorCodeRepository;

/**
 * TokenService - Handles ONLY token operations
 * 
 * RESPONSIBILITIES:
 * - Generate secure random tokens
 * - Hash tokens for storage
 * - Create tokens in database (via existing repositories)
 * - Verify tokens (valid, not expired, not revoked)
 * - Refresh tokens (extend expiry with new token)
 * - Revoke tokens
 * 
 * This service ONLY deals with token data in the database.
 * Uses the repositories that were already created.
 */
class TokenService extends BaseService
{
    private SessionRepository $sessionRepo;
    private TrustedDeviceRepository $trustedDeviceRepo;
    private TwoFactorCodeRepository $twoFactorRepo;
    
    // Token expiry times (in seconds)
    private const SESSION_EXPIRY = 604800;      // 7 days
    private const REMEMBER_ME_EXPIRY = 2592000; // 30 days

    public function __construct(
        SessionRepository $sessionRepo,
        TrustedDeviceRepository $trustedDeviceRepo,
        TwoFactorCodeRepository $twoFactorRepo
    ) {
        parent::__construct();
        $this->sessionRepo = $sessionRepo;
        $this->trustedDeviceRepo = $trustedDeviceRepo;
        $this->twoFactorRepo = $twoFactorRepo;
    }

    // ==================== TOKEN GENERATION ====================

    /**
     * Generate a secure random token
     */
    public function generateToken(int $bytes = 32): string
    {
        return parent::generateToken($bytes);
    }

    /**
     * Generate a numeric code (for 2FA, verification)
     */
    public function generateNumericCode(int $length = 6): string
    {
        return parent::generateNumericCode($length);
    }

    /**
     * Hash a token for storage
     */
    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Generate device fingerprint
     */
    public function generateDeviceFingerprint(array $data): string
    {
        $components = [];
        
        if (!empty($data['user_agent'])) {
            $components[] = $data['user_agent'];
        }
        
        if (!empty($data['ip_address'])) {
            $ipParts = explode('.', $data['ip_address']);
            if (count($ipParts) === 4) {
                $components[] = $ipParts[0] . '.' . $ipParts[1] . '.' . $ipParts[2];
            }
        }
        
        return $this->hashToken(implode('|', $components));
    }

    // ==================== SESSION TOKEN OPERATIONS ====================

    /**
     * Create a session token
     */
    public function createSessionToken(int $userId, array $data, bool $rememberMe = false): array
    {
        $rawToken = $this->generateToken();
        $hashedToken = $this->hashToken($rawToken);
        
        $expirySeconds = $rememberMe ? self::REMEMBER_ME_EXPIRY : self::SESSION_EXPIRY;
        $expiresAt = date('Y-m-d H:i:s', time() + $expirySeconds);
        
        $sessionData = [
            'user_id' => $userId,
            'token_hash' => $hashedToken,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'fingerprint' => $data['fingerprint'] ?? null,
            'expires_at' => $expiresAt,
            'last_activity' => date('Y-m-d H:i:s'),
            'is_revoked' => 0
        ];
        
        $sessionId = $this->sessionRepo->create($sessionData);
        
        return [
            'token' => $rawToken,
            'session_id' => $sessionId,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Find a session token by its hash
     */
    public function findSessionToken(string $rawToken): ?array
    {
        if (empty($rawToken)) {
            return null;
        }
        
        $hashedToken = $this->hashToken($rawToken);
        return $this->sessionRepo->findByToken($hashedToken);
    }

    /**
     * Verify a session token
     * 
     * @return array|false Returns token data if valid, false otherwise
     */
    public function verifySessionToken(string $rawToken, ?string $fingerprint = null)
    {
        $token = $this->findSessionToken($rawToken);
        
        if (!$token) {
            return false;
        }
        
        // Check if revoked
        if ($token['is_revoked']) {
            return false;
        }
        
        // Check expiry
        if (strtotime($token['expires_at']) < time()) {
            return false;
        }
        
        // Verify fingerprint if provided
        if ($fingerprint !== null && $token['fingerprint'] !== $fingerprint) {
            return false;
        }
        
        return [
            'id' => $token['id'],
            'user_id' => $token['user_id'],
            'expires_at' => $token['expires_at']
        ];
    }

    /**
     * Refresh a session token (generate new token, extend expiry)
     * 
     * @param string $currentToken The current valid token
     * @param bool $rememberMe Whether to use remember me expiry
     * @return array|null New token data or null if failed
     */
    public function refreshSessionToken(string $currentToken, bool $rememberMe = false): ?array
    {
        // First verify the current token is valid (without fingerprint check)
        $token = $this->findSessionToken($currentToken);
        
        if (!$token) {
            return null;
        }
        
        // Check if revoked
        if ($token['is_revoked']) {
            return null;
        }
        
        // Check expiry (allow refresh even if expired? Usually not)
        if (strtotime($token['expires_at']) < time()) {
            return null;
        }
        
        // Generate new token
        $newRawToken = $this->generateToken();
        $newHashedToken = $this->hashToken($newRawToken);
        
        // Calculate new expiry
        $expirySeconds = $rememberMe ? self::REMEMBER_ME_EXPIRY : self::SESSION_EXPIRY;
        $expiresAt = date('Y-m-d H:i:s', time() + $expirySeconds);
        
        // Update the session with new token and expiry
        $updated = $this->sessionRepo->update($token['id'], [
            'token_hash' => $newHashedToken,
            'expires_at' => $expiresAt,
            'last_activity' => date('Y-m-d H:i:s')
        ]);
        
        if (!$updated) {
            return null;
        }
        
        return [
            'token' => $newRawToken,
            'session_id' => $token['id'],
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Update token last activity
     */
    public function updateTokenActivity(int $tokenId): bool
    {
        return $this->sessionRepo->update($tokenId, [
            'last_activity' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Revoke a specific token
     */
    public function revokeToken(string $rawToken): bool
    {
        $token = $this->findSessionToken($rawToken);
        
        if (!$token) {
            return false;
        }
        
        return $this->sessionRepo->update($token['id'], ['is_revoked' => 1]);
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllUserTokens(int $userId): bool
    {
        return $this->sessionRepo->update($userId, ['is_revoked' => 1], 'user_id');
    }

    // ==================== 2FA CODE OPERATIONS ====================

    /**
     * Create a 2FA code (for login, password reset, email verification)
     */
    public function createTwoFactorCode(int $userId, string $type, int $expiryMinutes = 10): array
    {
        $rawCode = $this->generateNumericCode();
        $hashedCode = $this->hashToken($rawCode);
        
        $codeId = $this->twoFactorRepo->createCode($userId, $type, $hashedCode, $expiryMinutes);
        
        return [
            'code' => $rawCode,
            'code_id' => $codeId
        ];
    }

    /**
     * Verify a 2FA code (destructive - deletes after verification)
     */
    public function verifyTwoFactorCode(int $userId, string $type, string $rawCode): bool
    {
        $hashedCode = $this->hashToken($rawCode);
        error_log("Verifying 2FA code for user $userId, type $type, code: $rawCode, hashed code: $hashedCode");         
        return $this->twoFactorRepo->verifyCode($userId, $type, $hashedCode);
    }

    /**
     * Check if a 2FA code is valid without deleting it (non-destructive)
     * Used for verifying tokens before executing the actual operation
     */
    public function validateTwoFactorCode(int $userId, string $type, string $rawCode): bool
    {
        $hashedCode = $this->hashToken($rawCode);
        return $this->twoFactorRepo->validateCode($userId, $type, $hashedCode);
    }

    /**
     * Check if user has a valid code
     */
    public function hasValidCode(int $userId, string $type): bool
    {
        return $this->twoFactorRepo->hasValidCode($userId, $type);
    }

    /**
     * Invalidate all codes for a user
     */
    public function invalidateUserCodes(int $userId, string $type): int
    {
        return $this->twoFactorRepo->invalidateUserCodes($userId, $type);
    }

    // ==================== TRUSTED DEVICE TOKENS ====================

    /**
     * Create a trusted device token
     */
    public function createTrustedDeviceToken(int $userId, string $fingerprint): ?int
    {
        // Check if token already exists
        $existing = $this->trustedDeviceRepo->findDevice($userId, $fingerprint);
        
        if ($existing) {
            // Update last used
            $this->trustedDeviceRepo->update($existing['id'], [
                'last_used' => date('Y-m-d H:i:s')
            ]);
            return $existing['id'];
        }
        
        // Create new token
        return $this->trustedDeviceRepo->create([
            'user_id' => $userId,
            'device_fingerprint' => $fingerprint,
            'last_used' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Find a trusted device token
     */
    public function findTrustedDeviceToken(int $userId, string $fingerprint): ?array
    {
        return $this->trustedDeviceRepo->findDevice($userId, $fingerprint);
    }

    /**
     * Verify a trusted device token
     */
    public function verifyTrustedDeviceToken(int $userId, string $fingerprint): bool
    {
        $token = $this->findTrustedDeviceToken($userId, $fingerprint);
        
        if (!$token) {
            return false;
        }
        
        // Update last used
        $this->trustedDeviceRepo->update($token['id'], [
            'last_used' => date('Y-m-d H:i:s')
        ]);
        
        return true;
    }

    /**
     * Delete a trusted device token
     */
    public function deleteTrustedDeviceToken(int $userId, string $fingerprint): bool
    {
        return $this->trustedDeviceRepo->deleteWhere([
            'user_id' => $userId,
            'device_fingerprint' => $fingerprint
        ]);
    }

    // ==================== CLEANUP (for cron jobs) ====================

    /**
     * Delete expired session tokens
     */
    public function deleteExpiredSessionTokens(): int
    {
        return $this->sessionRepo->deleteExpired();
    }

    /**
     * Delete expired 2FA codes
     */
    public function deleteExpiredTwoFactorCodes(): int
    {
        return $this->twoFactorRepo->cleanupExpired();
    }

    /**
     * Delete old trusted devices
     */
    public function deleteOldTrustedDevices(int $daysInactive = 90): int
    {
        return $this->trustedDeviceRepo->cleanupOldDevices($daysInactive);
    }
}
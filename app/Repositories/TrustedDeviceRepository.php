<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;

/**
 * TrustedDeviceRepository - Manages trusted devices for 2FA remember me
 * 
 * PURPOSE:
 * - Allows users to mark devices as trusted to bypass 2FA
 * - Uses device fingerprinting for security
 * - Automatic cleanup of old devices
 */
class TrustedDeviceRepository extends BaseRepository
{
    protected string $table = 'trusted_devices';
    protected string $primaryKey = 'id';
    
    /**
     * Add a trusted device
     */
    public function addTrustedDevice(int $userId, string $deviceFingerprint): ?int
    {
        // Check if device already exists
        $existing = $this->findDevice($userId, $deviceFingerprint);
        
        if ($existing) {
            // Update last used time
            $this->update($existing['id'], [
                'last_used' => date('Y-m-d H:i:s')
            ]);
            return $existing['id'];
        }
        
        // Create new trusted device
        return $this->create([
            'user_id' => $userId,
            'device_fingerprint' => $deviceFingerprint,
            'last_used' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Check if device is trusted
     */
    public function isDeviceTrusted(int $userId, string $deviceFingerprint): bool
    {
        $device = $this->findDevice($userId, $deviceFingerprint);
        
        if (!$device) {
            return false;
        }
        
        // Update last used time
        $this->update($device['id'], [
            'last_used' => date('Y-m-d H:i:s')
        ]);
        
        return true;
    }
    
    /**
     * Find specific trusted device
     */
    public function findDevice(int $userId, string $deviceFingerprint): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id AND device_fingerprint = :fingerprint 
                LIMIT 1";
        
        return $this->fetchOne($sql, [
            ':user_id' => $userId,
            ':fingerprint' => $deviceFingerprint
        ]);
    }
    
    /**
     * Get all trusted devices for a user
     */
    public function getUserDevices(int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id 
                ORDER BY last_used DESC";
        
        return $this->fetchAll($sql, [':user_id' => $userId]);
    }
    
    /**
     * Remove a trusted device
     */
    public function removeDevice(int $userId, string $deviceFingerprint): bool
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE user_id = :user_id AND device_fingerprint = :fingerprint";
        
        return $this->execute($sql, [
            ':user_id' => $userId,
            ':fingerprint' => $deviceFingerprint
        ]);
    }
    
    /**
     * Remove all trusted devices for a user
     */
    public function removeAllForUser(int $userId): bool
    {
        return $this->deleteWhere(['user_id' => $userId]);
    }
    
    /**
     * Clean up old trusted devices (not used in 90 days)
     */
    public function cleanupOldDevices(int $daysInactive = 90): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$daysInactive} days"));
        
        $sql = "DELETE FROM {$this->table} WHERE last_used < :cutoff";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cutoff' => $cutoff]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Get device statistics
     */
    public function getDeviceStats(int $userId): array
    {
        $total = $this->count(['user_id' => $userId]);
        
        // Get last used devices
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id 
                ORDER BY last_used DESC 
                LIMIT 5";
        
        $recentDevices = $this->fetchAll($sql, [':user_id' => $userId]);
        
        return [
            'total_devices' => $total,
            'recent_devices' => $recentDevices
        ];
    }
}
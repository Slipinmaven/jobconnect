<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use PDO;

/**
 * UserLocationRepository - Tracks user login locations
 * 
 * PURPOSE:
 * - Security monitoring (detect unusual locations)
 * - Analytics (user demographics)
 * - Compliance (data residency requirements)
 */
class UserLocationRepository extends BaseRepository
{
    protected string $table = 'user_locations';
    protected string $primaryKey = 'id';
    
    /**
     * Record a user location
     * 
     * @param int $userId User ID
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param array $locationData Additional location data
     * @return int|null New location record ID
     */
    public function recordLocation(int $userId, float $latitude, float $longitude, array $locationData = []): ?int
    {
        // Validate coordinates
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new \InvalidArgumentException("Invalid coordinates");
        }
        
        $data = [
            'user_id' => $userId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'city' => $locationData['city'] ?? null,
            'region' => $locationData['region'] ?? null,
            'country' => $locationData['country'] ?? null,
            'ip_address' => $locationData['ip_address'] ?? null
        ];
        
        return $this->create($data);
    }
    
    /**
     * Get user's location history
     */
    public function getUserLocations(int $userId, int $limit = 50): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC";
        
        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql, [
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Get user's most recent location
     */
    public function getLatestLocation(int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT 1";
        
        return $this->fetchOne($sql, [':user_id' => $userId]);
    }
    
    /**
     * Find locations within radius of a point
     * 
     * @param float $centerLat Center latitude
     * @param float $centerLng Center longitude
     * @param float $radiusKm Radius in kilometers
     * @param int $limit Maximum results
     */
    public function findNearby(float $centerLat, float $centerLng, float $radiusKm, int $limit = 100): array
    {
        // Haversine formula for distance calculation
        $sql = "SELECT *, 
                (6371 * acos(cos(radians(:center_lat)) * 
                cos(radians(latitude)) * 
                cos(radians(longitude) - radians(:center_lng)) + 
                sin(radians(:center_lat2)) * 
                sin(radians(latitude)))) AS distance 
                FROM {$this->table} 
                HAVING distance < :radius 
                ORDER BY distance";

        $limit = max(0, min(1000, $limit));
        $sql .= $this->formatLimitOffset($limit, 0);

        return $this->fetchAll($sql, [
            ':center_lat' => $centerLat,
            ':center_lat2' => $centerLat,
            ':center_lng' => $centerLng,
            ':radius' => $radiusKm
        ]);
    }
    
    /**
     * Get unique countries where users have logged in from
     */
    public function getUniqueCountries(): array
    {
        $sql = "SELECT DISTINCT country, COUNT(*) as user_count 
                FROM {$this->table} 
                WHERE country IS NOT NULL 
                GROUP BY country 
                ORDER BY user_count DESC";
        
        return $this->fetchAll($sql);
    }
    
    /**
     * Get location statistics
     */
    public function getLocationStats(): array
    {
        $stats = [
            'total_records' => 0,
            'unique_users' => 0,
            'unique_countries' => 0,
            'top_cities' => [],
            'top_countries' => []
        ];
        
        // Total records
        $stats['total_records'] = $this->count();
        
        // Unique users
        $sql = "SELECT COUNT(DISTINCT user_id) as count FROM {$this->table}";
        $result = $this->fetchOne($sql);
        $stats['unique_users'] = $result['count'] ?? 0;
        
        // Unique countries
        $sql = "SELECT COUNT(DISTINCT country) as count FROM {$this->table} WHERE country IS NOT NULL";
        $result = $this->fetchOne($sql);
        $stats['unique_countries'] = $result['count'] ?? 0;
        
        // Top cities
        $sql = "SELECT city, country, COUNT(*) as count 
                FROM {$this->table} 
                WHERE city IS NOT NULL 
                GROUP BY city, country 
                ORDER BY count DESC 
                LIMIT 10";
        $stats['top_cities'] = $this->fetchAll($sql);
        
        // Top countries
        $sql = "SELECT country, COUNT(*) as count 
                FROM {$this->table} 
                WHERE country IS NOT NULL 
                GROUP BY country 
                ORDER BY count DESC 
                LIMIT 10";
        $stats['top_countries'] = $this->fetchAll($sql);
        
        return $stats;
    }
    
    /**
     * Get users who logged in from multiple countries (potential security risk)
     */
    public function getUsersWithMultipleCountries(int $minCountries = 3): array
    {
        $sql = "SELECT user_id, COUNT(DISTINCT country) as country_count 
                FROM {$this->table} 
                WHERE country IS NOT NULL 
                GROUP BY user_id 
                HAVING country_count >= :min_countries 
                ORDER BY country_count DESC";
        
        return $this->fetchAll($sql, [':min_countries' => $minCountries]);
    }
    
    /**
     * Clean up old location records
     * 
     * @param int $keepDays Number of days to keep
     * @return int Number of deleted records
     */
    public function cleanupOldRecords(int $keepDays = 90): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$keepDays} days"));
        
        $sql = "DELETE FROM {$this->table} WHERE created_at < :cutoff";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cutoff' => $cutoff]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Export user location data (for GDPR compliance)
     */
    public function exportUserData(int $userId): array
    {
        $sql = "SELECT latitude, longitude, city, region, country, ip_address, created_at 
                FROM {$this->table} 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC";
        
        return $this->fetchAll($sql, [':user_id' => $userId]);
    }
}
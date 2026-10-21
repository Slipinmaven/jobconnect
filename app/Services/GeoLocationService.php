<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\UserLocationRepository;

/**
 * GeoLocationService - Manages user location data from IP addresses
 * 
 * RESPONSIBILITIES:
 * - Get location data from IP address using free APIs
 * - Parse and normalize location data
 * - Store location history for users
 * - Detect location changes for security
 * - Format location data for user display
 * 
 * USES FREE APIS:
 * - ip-api.com (free, no API key required, 45 requests/minute)
 * - ipapi.co (free tier, requires API key for higher limits)
 * - ipinfo.io (free tier, requires token)
 */
class GeoLocationService extends BaseService
{
    private UserLocationRepository $locationRepo;
    
    // API endpoints (free tier)
    private const API_IP_API = 'http://ip-api.com/json/';
    private const API_IPAPI_CO = 'https://ipapi.co/';
    private const API_IPINFO = 'https://ipinfo.io/';
    
    // Cache location data for 24 hours to avoid rate limits
    private const CACHE_TTL = 86400;
    
    // Default location for localhost/development
    private const DEFAULT_LOCATION = [
        'country' => 'Localhost',
        'country_code' => 'DEV',
        'region' => 'Development',
        'city' => 'Local',
        'latitude' => 0,
        'longitude' => 0,
        'timezone' => 'UTC',
        'isp' => 'Local Network'
    ];

    public function __construct(UserLocationRepository $locationRepo)
    {
        parent::__construct();
        $this->locationRepo = $locationRepo;
    }

    // ==================== PUBLIC METHODS ====================

    /**
     * Get location from IP address
     * 
     * @param string $ip IP address
     * @param bool $useCache Whether to use cached data
     * @return array Location data
     */
    public function getLocationFromIp(string $ip, bool $useCache = true): array
    {
        // Handle localhost/development IPs
        if ($this->isLocalIp($ip)) {
            return self::DEFAULT_LOCATION;
        }

        // Check cache if enabled
        if ($useCache) {
            $cached = $this->getCachedLocation($ip);
            if ($cached) {
                return $cached;
            }
        }

        // Try APIs in order of preference
        $location = $this->tryIpApi($ip);
        
        if (!$location) {
            $location = $this->tryIpapiCo($ip);
        }
        
        if (!$location) {
            $location = $this->tryIpInfo($ip);
        }

        // If all APIs fail, return basic data
        if (!$location) {
            $location = $this->getBasicLocationData($ip);
        }

        // Cache the result
        $this->cacheLocation($ip, $location);

        return $location;
    }

    /**
     * Record user location from request
     * 
     * @param int $userId User ID
     * @param array $requestData Request data with IP and optional location
     * @return array Recorded location data
     */
    public function recordUserLocation(int $userId, array $requestData): array
    {
        $ip = $requestData['ip_address'] ?? $this->getClientIp();
        
        // Get location from IP
        $location = $this->getLocationFromIp($ip);
        
        // Add request-specific data
        $location['ip_address'] = $ip;
        $location['user_agent'] = $requestData['user_agent'] ?? null;
        
        // Store in database
        $locationId = $this->locationRepo->recordLocation(
            $userId,
            $location['latitude'],
            $location['longitude'],
            [
                'city' => $location['city'],
                'region' => $location['region'],
                'country' => $location['country'],
                'ip_address' => $ip
            ]
        );
        
        $location['id'] = $locationId;
        $location['recorded_at'] = date('Y-m-d H:i:s');
        
        return $location;
    }

    /**
     * Get user's location history
     * 
     * @param int $userId User ID
     * @param int $limit Number of records to return
     * @return array Location history
     */
    public function getUserLocationHistory(int $userId, int $limit = 50): array
    {
        $locations = $this->locationRepo->getUserLocations($userId, $limit);
        
        // Format for display
        return array_map(function($loc) {
            return [
                'id' => $loc['id'],
                'city' => $loc['city'] ?? 'Unknown',
                'region' => $loc['region'] ?? 'Unknown',
                'country' => $loc['country'] ?? 'Unknown',
                'latitude' => $loc['latitude'],
                'longitude' => $loc['longitude'],
                'ip_address' => $loc['ip_address'],
                'time' => $loc['created_at'],
                'formatted' => $this->formatLocationDisplay($loc)
            ];
        }, $locations);
    }

    /**
     * Get user's current/last known location
     */
    public function getCurrentUserLocation(int $userId): ?array
    {
        $location = $this->locationRepo->getLatestLocation($userId);
        
        if (!$location) {
            return null;
        }
        
        return [
            'city' => $location['city'] ?? 'Unknown',
            'region' => $location['region'] ?? 'Unknown',
            'country' => $location['country'] ?? 'Unknown',
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'ip_address' => $location['ip_address'],
            'last_seen' => $location['created_at']
        ];
    }

    /**
     * Detect if login is from a new location
     * 
     * @param int $userId User ID
     * @param string $ip Current IP
     * @return array Detection result
     */
    public function detectNewLocation(int $userId, string $ip): array
    {
        // Get current location
        $currentLocation = $this->getLocationFromIp($ip);
        
        // Get last 5 locations
        $history = $this->locationRepo->getUserLocations($userId, 5);
        
        if (empty($history)) {
            return [
                'is_new' => true,
                'is_first' => true,
                'location' => $currentLocation
            ];
        }
        
        // Check if this location exists in history
        foreach ($history as $past) {
            if ($this->locationsAreSimilar($currentLocation, $past)) {
                return [
                    'is_new' => false,
                    'is_first' => false,
                    'location' => $currentLocation,
                    'last_seen' => $past['created_at']
                ];
            }
        }
        
        // New location detected
        return [
            'is_new' => true,
            'is_first' => false,
            'location' => $currentLocation,
            'previous_location' => $this->formatLocationDisplay($history[0])
        ];
    }

    /**
     * Get location statistics
     */
    public function getLocationStats(): array
    {
        return $this->locationRepo->getLocationStats();
    }

    /**
     * Get users who logged in from multiple countries (security)
     */
    public function getUsersWithMultipleLocations(int $minLocations = 3): array
    {
        return $this->locationRepo->getUsersWithMultipleCountries($minLocations);
    }

    // ==================== API METHODS ====================

    /**
     * Try ip-api.com (free, no key required)
     */
    private function tryIpApi(string $ip): ?array
    {
        try {
            $url = self::API_IP_API . $ip . '?fields=status,country,countryCode,region,city,lat,lon,timezone,isp,query';
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_USERAGENT => 'JobConnect/1.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                
                if ($data && $data['status'] === 'success') {
                    return [
                        'ip' => $data['query'],
                        'country' => $data['country'],
                        'country_code' => $data['countryCode'],
                        'region' => $data['region'],
                        'city' => $data['city'],
                        'latitude' => $data['lat'],
                        'longitude' => $data['lon'],
                        'timezone' => $data['timezone'],
                        'isp' => $data['isp'] ?? null,
                        'source' => 'ip-api.com'
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logError('ip-api.com failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }
        
        return null;
    }

    /**
     * Try ipapi.co (free tier, 1000 requests/day)
     */
    private function tryIpapiCo(string $ip): ?array
    {
        try {
            $url = self::API_IPAPI_CO . $ip . '/json/';
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_USERAGENT => 'JobConnect/1.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                
                if ($data && !isset($data['error'])) {
                    return [
                        'ip' => $data['ip'],
                        'country' => $data['country_name'],
                        'country_code' => $data['country_code'],
                        'region' => $data['region'] ?? $data['region_code'],
                        'city' => $data['city'],
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                        'timezone' => $data['timezone'],
                        'isp' => $data['org'] ?? null,
                        'source' => 'ipapi.co'
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logError('ipapi.co failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }
        
        return null;
    }

    /**
     * Try ipinfo.io (free tier with token)
     */
    private function tryIpInfo(string $ip): ?array
    {
        // You can add a token here if you have one
        $token = ''; // Add your ipinfo.io token if available
        
        try {
            $url = self::API_IPINFO . $ip . ($token ? '?token=' . $token : '');
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_USERAGENT => 'JobConnect/1.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                
                if ($data) {
                    // Parse location (ipinfo returns "lat,lon")
                    $loc = explode(',', $data['loc'] ?? '0,0');
                    
                    return [
                        'ip' => $data['ip'],
                        'country' => $data['country'],
                        'country_code' => $data['country'],
                        'region' => $data['region'],
                        'city' => $data['city'],
                        'latitude' => floatval($loc[0] ?? 0),
                        'longitude' => floatval($loc[1] ?? 0),
                        'timezone' => $data['timezone'] ?? null,
                        'isp' => $data['org'] ?? null,
                        'source' => 'ipinfo.io'
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logError('ipinfo.io failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }
        
        return null;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if IP is local/development
     */
    private function isLocalIp(string $ip): bool
    {
        $localIps = [
            '127.0.0.1',
            '::1',
            'localhost',
            '0.0.0.0'
        ];
        
        if (in_array($ip, $localIps)) {
            return true;
        }
        
        // Check private IP ranges
        if (preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $ip)) {
            return true;
        }
        
        return false;
    }

    /**
     * Get basic location data when APIs fail
     */
    private function getBasicLocationData(string $ip): array
    {
        // Try to get country from IP range (very basic)
        $country = $this->guessCountryFromIp($ip);
        
        return [
            'ip' => $ip,
            'country' => $country,
            'country_code' => $country,
            'region' => 'Unknown',
            'city' => 'Unknown',
            'latitude' => 0,
            'longitude' => 0,
            'timezone' => 'UTC',
            'isp' => null,
            'source' => 'basic'
        ];
    }

    /**
     * Very basic IP to country guess (only for fallback)
     */
    private function guessCountryFromIp(string $ip): string
    {
        // This is extremely basic - just for demonstration
        // In production, use a proper IP database
        
        if (strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
            return 'Private';
        }
        
        return 'Unknown';
    }

    /**
     * Check if two locations are similar (same city/region)
     */
    private function locationsAreSimilar(array $loc1, array $loc2): bool
    {
        // If both have city and region
        if (!empty($loc1['city']) && !empty($loc2['city'])) {
            return $loc1['city'] === $loc2['city'] && $loc1['region'] === $loc2['region'];
        }
        
        // Fall back to coordinate proximity (within ~10km)
        if (isset($loc1['latitude'], $loc2['latitude'])) {
            $distance = $this->calculateDistance(
                $loc1['latitude'], $loc1['longitude'],
                $loc2['latitude'], $loc2['longitude']
            );
            return $distance < 10; // within 10km
        }
        
        return false;
    }

    /**
     * Calculate distance between two coordinates (km)
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    /**
     * Format location for display
     */
    private function formatLocationDisplay(array $location): string
    {
        $parts = [];
        
        if (!empty($location['city']) && $location['city'] !== 'Unknown') {
            $parts[] = $location['city'];
        }
        
        if (!empty($location['region']) && $location['region'] !== 'Unknown') {
            $parts[] = $location['region'];
        }
        
        if (!empty($location['country']) && $location['country'] !== 'Unknown') {
            $parts[] = $location['country'];
        }
        
        if (empty($parts)) {
            return 'Unknown Location';
        }
        
        return implode(', ', $parts);
    }

    /**
     * Get cached location data
     */
    private function getCachedLocation(string $ip): ?array
    {
        $cacheFile = $this->getCachePath($ip);
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < self::CACHE_TTL) {
            $data = file_get_contents($cacheFile);
            return json_decode($data, true);
        }
        
        return null;
    }

    /**
     * Cache location data
     */
    private function cacheLocation(string $ip, array $location): void
    {
        $cacheFile = $this->getCachePath($ip);
        $cacheDir = dirname($cacheFile);
        
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        file_put_contents($cacheFile, json_encode($location));
    }

    /**
     * Get cache file path for IP
     */
    private function getCachePath(string $ip): string
    {
        $hash = md5($ip);
        $path = __DIR__ . '/../../storage/cache/geolocation/';
        return $path . substr($hash, 0, 2) . '/' . $hash . '.json';
    }

    /**
     * Get client IP from server data
     */
    // ==================== USER-FRIENDLY METHODS ====================

    /**
     * Get user-friendly location string
     */
    public function getLocationString(array $location): string
    {
        return $this->formatLocationDisplay($location);
    }

    /**
     * Get flag emoji for country code
     */
    public function getCountryFlag(string $countryCode): string
    {
        if (strlen($countryCode) !== 2) {
            return '🏳️';
        }
        
        // Convert country code to flag emoji
        $code = strtoupper($countryCode);
        $flag = '';
        
        for ($i = 0; $i < 2; $i++) {
            $flag .= mb_chr(127397 + ord($code[$i]));
        }
        
        return $flag;
    }

    /**
     * Get timezone for location
     */
    public function getLocalTime(string $timezone): string
    {
        try {
            $tz = new \DateTimeZone($timezone);
            $now = new \DateTime('now', $tz);
            return $now->format('H:i');
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Export user location data (for GDPR)
     */
    public function exportUserData(int $userId): array
    {
        return $this->locationRepo->exportUserData($userId);
    }

    /**
     * Clean up old location records
     */
    public function cleanupOldRecords(int $keepDays = 90): int
    {
        return $this->locationRepo->cleanupOldRecords($keepDays);
    }
}
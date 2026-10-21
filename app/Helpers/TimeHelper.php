<?php

namespace App\Helpers;

use DateTime;
use DateTimeZone;
use Exception;

class TimeHelper
{
    /**
     * Default application timezone
     */
    const DEFAULT_TIMEZONE = 'UTC';
    
    /**
     * User's timezone (can be set per user)
     */
    private static $userTimezone = null;
    
    /**
     * Database timezone setting (default to UTC)
     */
    private static $dbTimezone = 'UTC';
    
    /**
     * Set database timezone
     */
    public static function setDbTimezone($timezone)
    {
        self::$dbTimezone = $timezone;
    }
    
    /**
     * Check if a datetime string is in UTC format
     * 
     * @param string $datetime The datetime string to check
     * @return bool True if datetime appears to be in UTC
     */
    public static function isUTC($datetime)
    {
        if (empty($datetime)) {
            return false;
        }
        
        // Check if the string ends with Z (ISO 8601 UTC indicator)
        if (substr($datetime, -1) === 'Z') {
            return true;
        }
        
        // Check if string contains timezone offset
        if (preg_match('/[+-]\d{2}:?\d{2}$/', $datetime)) {
            return false; // Has explicit offset, not UTC
        }
        
        // For MySQL datetime format (Y-m-d H:i:s), assume it's UTC if we're storing UTC
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $datetime)) {
            // Check if db timezone is set to UTC (default to true for safety)
            return self::$dbTimezone === 'UTC';
        }
        
        return false;
    }
    
    /**
     * Set user's timezone
     */
    public static function setUserTimezone($timezone)
    {
        self::$userTimezone = $timezone;
    }
    
    /**
     * Get user's timezone or fallback to default
     */
    public static function getUserTimezone()
    {
        return self::$userTimezone ?: self::DEFAULT_TIMEZONE;
    }
    
    /**
     * Smart UTC to Local conversion - prevents double conversion
     * 
     * @param string $datetime The datetime string (could be UTC or local)
     * @param string $format Output format (default: 'Y-m-d H:i:s')
     * @return string Formatted local datetime
     */
    public static function utcToLocal($datetime, $format = 'Y-m-d H:i:s')
    {
        if (empty($datetime)) {
            return '';
        }
        
        try {
            // If datetime is already in local timezone, return as is
            if (!self::isUTC($datetime)) {
                // Already in local time, just format it
                $date = new DateTime($datetime, new DateTimeZone(self::getUserTimezone()));
                return $date->format($format);
            }
            
            // Convert from UTC to local
            $date = new DateTime($datetime, new DateTimeZone('UTC'));
            $date->setTimezone(new DateTimeZone(self::getUserTimezone()));
            return $date->format($format);
        } catch (Exception $e) {
            return $datetime;
        }
    }
    
    /**
     * Smart Local to UTC conversion - prevents double conversion
     * 
     * @param string $datetime The datetime string (could be UTC or local)
     * @param string $format Output format (default: 'Y-m-d H:i:s')
     * @return string UTC datetime
     */
    public static function localToUtc($datetime, $format = 'Y-m-d H:i:s')
    {
        if (empty($datetime)) {
            return '';
        }
        
        try {
            // If datetime is already in UTC, return as is
            if (self::isUTC($datetime)) {
                $date = new DateTime($datetime, new DateTimeZone('UTC'));
                return $date->format($format);
            }
            
            // Convert from local to UTC
            $date = new DateTime($datetime, new DateTimeZone(self::getUserTimezone()));
            $date->setTimezone(new DateTimeZone('UTC'));
            return $date->format($format);
        } catch (Exception $e) {
            return $datetime;
        }
    }
    
    /**
     * Get detailed time information including status relative to current time
     * 
     * @param string $datetime The datetime string (UTC format)
     * @return array Detailed time information
     */
    public static function getTimeInfo($datetime)
    {
        if (empty($datetime)) {
            return [
                'status' => 'unknown',
                'message' => 'No date provided',
                'is_past' => false,
                'is_future' => false,
                'is_current' => false,
                'formatted' => 'N/A',
                'relative' => 'N/A',
                'timestamp' => null
            ];
        }
        
        try {
            // Convert to local for comparison
            $localDateStr = self::utcToLocal($datetime);
            $date = new DateTime($localDateStr, new DateTimeZone(self::getUserTimezone()));
            $now = new DateTime('now', new DateTimeZone(self::getUserTimezone()));
            
            $diffSeconds = $date->getTimestamp() - $now->getTimestamp();
            $isPast = $diffSeconds < 0;
            $isFuture = $diffSeconds > 0;
            $isCurrent = abs($diffSeconds) < 60; // Within 1 minute
            
            // Determine status
            $status = 'unknown';
            $statusMessage = '';
            
            if ($isPast) {
                $status = 'past';
                $statusMessage = self::getPastTimeMessage(abs($diffSeconds));
            } elseif ($isFuture) {
                $status = 'future';
                $statusMessage = self::getFutureTimeMessage($diffSeconds);
            } elseif ($isCurrent) {
                $status = 'current';
                $statusMessage = 'Happening now';
            }
            
            return [
                'status' => $status,
                'message' => $statusMessage,
                'is_past' => $isPast,
                'is_future' => $isFuture,
                'is_current' => $isCurrent,
                'formatted' => self::format($datetime),
                'relative' => self::relativeTime($datetime),
                'timestamp' => $date->getTimestamp(),
                'datetime_local' => $localDateStr,
                'datetime_utc' => $datetime,
                'diff_seconds' => $diffSeconds
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Invalid date format',
                'is_past' => false,
                'is_future' => false,
                'is_current' => false,
                'formatted' => $datetime,
                'relative' => 'Invalid date',
                'timestamp' => null
            ];
        }
    }
    
    /**
     * Get user-friendly message for past times
     * 
     * @param int $seconds Seconds since the time passed
     * @return string User-friendly message
     */
    private static function getPastTimeMessage($seconds)
    {
        if ($seconds < 60) {
            return 'Just passed';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return "Passed {$minutes} minute" . ($minutes != 1 ? 's' : '') . " ago";
        } elseif ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            return "Passed {$hours} hour" . ($hours != 1 ? 's' : '') . " ago";
        } elseif ($seconds < 604800) {
            $days = floor($seconds / 86400);
            return "Passed {$days} day" . ($days != 1 ? 's' : '') . " ago";
        } else {
            $weeks = floor($seconds / 604800);
            return "Passed {$weeks} week" . ($weeks != 1 ? 's' : '') . " ago";
        }
    }
    
    /**
     * Get user-friendly message for future times
     * 
     * @param int $seconds Seconds until the time
     * @return string User-friendly message
     */
    private static function getFutureTimeMessage($seconds)
    {
        if ($seconds < 60) {
            return 'Happening very soon';
        } elseif ($seconds < 3600) {
            $minutes = ceil($seconds / 60);
            return "In {$minutes} minute" . ($minutes != 1 ? 's' : '');
        } elseif ($seconds < 86400) {
            $hours = ceil($seconds / 3600);
            return "In {$hours} hour" . ($hours != 1 ? 's' : '');
        } elseif ($seconds < 604800) {
            $days = ceil($seconds / 86400);
            return "In {$days} day" . ($days != 1 ? 's' : '');
        } else {
            $weeks = ceil($seconds / 604800);
            return "In {$weeks} week" . ($weeks != 1 ? 's' : '');
        }
    }
    
    /**
     * Format datetime for display with user-friendly output
     * 
     * @param string $utcDateTime UTC datetime string
     * @param bool $showTime Show time (default: true)
     * @param bool $showRelative Show relative time (e.g., "2 hours ago")
     * @return string Formatted datetime
     */
    public static function format($utcDateTime, $showTime = true, $showRelative = false)
    {
        if (empty($utcDateTime)) {
            return 'N/A';
        }
        
        try {
            $localDate = self::utcToLocal($utcDateTime);
            $date = new DateTime($localDate, new DateTimeZone(self::getUserTimezone()));
            $now = new DateTime('now', new DateTimeZone(self::getUserTimezone()));
            
            if ($showRelative) {
                $diff = $now->getTimestamp() - $date->getTimestamp();
                
                if ($diff < 60) {
                    return 'Just now';
                } elseif ($diff < 3600) {
                    $minutes = floor($diff / 60);
                    return $minutes . ' minute' . ($minutes != 1 ? 's' : '') . ' ago';
                } elseif ($diff < 86400) {
                    $hours = floor($diff / 3600);
                    return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
                } elseif ($diff < 604800) {
                    $days = floor($diff / 86400);
                    return $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
                } elseif ($diff < 2592000) {
                    $weeks = floor($diff / 604800);
                    return $weeks . ' week' . ($weeks != 1 ? 's' : '') . ' ago';
                }
            }
            
            $format = $showTime ? 'M j, Y g:i A' : 'M j, Y';
            return $date->format($format);
        } catch (Exception $e) {
            return $utcDateTime;
        }
    }
    
    /**
     * Get relative time string (e.g., "2 hours ago", "tomorrow at 2:30 PM")
     * 
     * @param string $utcDateTime UTC datetime string
     * @return string Relative time description
     */
    public static function relativeTime($utcDateTime)
    {
        if (empty($utcDateTime)) {
            return 'N/A';
        }
        
        try {
            $localDate = self::utcToLocal($utcDateTime);
            $date = new DateTime($localDate, new DateTimeZone(self::getUserTimezone()));
            $now = new DateTime('now', new DateTimeZone(self::getUserTimezone()));
            $diff = $date->getTimestamp() - $now->getTimestamp();
            
            // Future dates (upcoming interviews)
            if ($diff > 0) {
                if ($diff < 3600) {
                    $minutes = ceil($diff / 60);
                    return 'in ' . $minutes . ' minute' . ($minutes != 1 ? 's' : '');
                } elseif ($diff < 86400) {
                    $hours = ceil($diff / 3600);
                    return 'in ' . $hours . ' hour' . ($hours != 1 ? 's' : '');
                } elseif ($diff < 172800) {
                    return 'tomorrow at ' . $date->format('g:i A');
                } elseif ($diff < 604800) {
                    $days = ceil($diff / 86400);
                    return 'in ' . $days . ' day' . ($days != 1 ? 's' : '');
                } else {
                    return $date->format('M j, Y \a\t g:i A');
                }
            }
            // Past dates
            else {
                $diff = abs($diff);
                if ($diff < 60) {
                    return 'Just now';
                } elseif ($diff < 3600) {
                    $minutes = floor($diff / 60);
                    return $minutes . ' minute' . ($minutes != 1 ? 's' : '') . ' ago';
                } elseif ($diff < 86400) {
                    $hours = floor($diff / 3600);
                    return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
                } elseif ($diff < 604800) {
                    $days = floor($diff / 86400);
                    return $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
                } else {
                    return $date->format('M j, Y');
                }
            }
        } catch (Exception $e) {
            return $utcDateTime;
        }
    }
    
    /**
     * Format interview datetime for display
     * 
     * @param string $utcDateTime UTC datetime string
     * @return array Interview time information with formatted strings
     */
    public static function formatInterviewDateTime($utcDateTime)
    {
        if (empty($utcDateTime)) {
            return [
                'formatted' => 'Date TBD',
                'status' => 'unknown',
                'message' => 'Not scheduled',
                'is_past' => false,
                'is_future' => false,
                'is_today' => false,
                'is_tomorrow' => false,
                'time' => 'N/A',
                'date' => 'N/A'
            ];
        }
        
        try {
            $localDate = self::utcToLocal($utcDateTime);
            $date = new DateTime($localDate, new DateTimeZone(self::getUserTimezone()));
            $now = new DateTime('now', new DateTimeZone(self::getUserTimezone()));
            $diff = $date->getTimestamp() - $now->getTimestamp();
            
            // Check if it's today or tomorrow
            $isToday = $date->format('Y-m-d') === $now->format('Y-m-d');
            $isTomorrow = $date->format('Y-m-d') === $now->modify('+1 day')->format('Y-m-d');
            
            // Reset now to avoid modification issues
            $now = new DateTime('now', new DateTimeZone(self::getUserTimezone()));
            
            $formatted = '';
            $status = '';
            $message = '';
            $isPast = $diff < 0;
            $isFuture = $diff > 0;
            
            if ($isPast) {
                $status = 'past';
                $message = self::getPastTimeMessage(abs($diff));
                $formatted = 'Passed: ' . $date->format('M j, Y \a\t g:i A');
            } elseif ($diff < 3600 && $diff > 0) {
                $status = 'very_soon';
                $minutes = ceil($diff / 60);
                $message = "Starts in {$minutes} minute" . ($minutes != 1 ? 's' : '');
                $formatted = $message;
            } elseif ($isToday) {
                $status = 'today';
                $message = 'Today at ' . $date->format('g:i A');
                $formatted = $message;
            } elseif ($isTomorrow) {
                $status = 'tomorrow';
                $message = 'Tomorrow at ' . $date->format('g:i A');
                $formatted = $message;
            } elseif ($diff < 604800) {
                $status = 'this_week';
                $message = $date->format('l') . ' at ' . $date->format('g:i A');
                $formatted = $message;
            } else {
                $status = 'future';
                $message = $date->format('M j, Y \a\t g:i A');
                $formatted = $message;
            }
            
            return [
                'formatted' => $formatted,
                'status' => $status,
                'message' => $message,
                'is_past' => $isPast,
                'is_future' => $isFuture,
                'is_today' => $isToday,
                'is_tomorrow' => $isTomorrow,
                'time' => $date->format('g:i A'),
                'date' => $date->format('M j, Y'),
                'datetime_utc' => $utcDateTime,
                'datetime_local' => $localDate
            ];
        } catch (Exception $e) {
            return [
                'formatted' => $utcDateTime,
                'status' => 'error',
                'message' => 'Invalid date',
                'is_past' => false,
                'is_future' => false,
                'is_today' => false,
                'is_tomorrow' => false,
                'time' => 'N/A',
                'date' => 'N/A'
            ];
        }
    }
    
    /**
     * Check if a datetime is in the past
     * 
     * @param string $utcDateTime UTC datetime string
     * @return bool
     */
    public static function isPast($utcDateTime)
    {
        if (empty($utcDateTime)) {
            return false;
        }
        
        try {
            $localDate = self::utcToLocal($utcDateTime);
            $date = new DateTime($localDate, new DateTimeZone(self::getUserTimezone()));
            $now = new DateTime('now', new DateTimeZone(self::getUserTimezone()));
            return $date < $now;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if a datetime is in the future
     * 
     * @param string $utcDateTime UTC datetime string
     * @return bool
     */
    public static function isFuture($utcDateTime)
    {
        return !self::isPast($utcDateTime) && !empty($utcDateTime);
    }
    
    /**
     * Get current UTC timestamp
     * 
     * @return string Current UTC datetime
     */
    public static function now()
    {
        $date = new DateTime('now', new DateTimeZone('UTC'));
        return $date->format('Y-m-d H:i:s');
    }
    
    /**
     * Get current user local timestamp
     * 
     * @return string Current local datetime
     */
    public static function nowLocal()
    {
        $date = new DateTime('now', new DateTimeZone(self::getUserTimezone()));
        return $date->format('Y-m-d H:i:s');
    }
    
    /**
     * Get timezone offset for user
     * 
     * @return string Timezone offset (e.g., "+02:00")
     */
    public static function getTimezoneOffset()
    {
        $date = new DateTime('now', new DateTimeZone(self::getUserTimezone()));
        return $date->format('P');
    }
    
    /**
     * Format date only (no time)
     * 
     * @param string $utcDateTime UTC datetime string
     * @return string Formatted date
     */
    public static function formatDate($utcDateTime)
    {
        return self::format($utcDateTime, false);
    }
    
    /**
     * Format time only (no date)
     * 
     * @param string $utcDateTime UTC datetime string
     * @return string Formatted time
     */
    public static function formatTime($utcDateTime)
    {
        if (empty($utcDateTime)) {
            return 'N/A';
        }
        
        try {
            $localDate = self::utcToLocal($utcDateTime);
            $date = new DateTime($localDate, new DateTimeZone(self::getUserTimezone()));
            return $date->format('g:i A');
        } catch (Exception $e) {
            return $utcDateTime;
        }
    }
}
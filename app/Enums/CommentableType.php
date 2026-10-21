<?php

namespace App\Enums;

/**
 * CommentableType - Defines all entity types that can have comments
 */
enum CommentableType: string
{
    case JOB = 'job';
    case FEED = 'feed';
    case PROFILE = 'profile';
    case ARTICLE = 'article';
    case BLOG = 'blog';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::JOB => 'Job',
            self::FEED => 'Feed Post',
            self::PROFILE => 'Profile',
            self::ARTICLE => 'Article',
            self::BLOG => 'Blog Post',
        };
    }

    /**
     * Get default number of comments to fetch per page
     */
    public function defaultPerPage(): int
    {
        return match ($this) {
            self::JOB => 20,      // Jobs can have many comments
            self::FEED => 15,     // Feed posts show fewer
            self::PROFILE => 10,  // Profiles show limited
            default => 20,
        };
    }

    /**
     * Get maximum comments allowed to fetch (API limit)
     */
    public function maxPerPage(): int
    {
        return 50; // Never fetch more than 50 at once
    }

    /**
     * Get maximum nested replies depth
     */
    public function maxReplyDepth(): int
    {
        return 3; // TikTok-style: comment → reply → reply (max 3 levels)
    }

    /**
     * Check if a string is a valid commentable type
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::toArray());
    }

    /**
     * Get all available commentable types as array
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all types with labels
     */
    public static function withLabels(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->label();
        }
        return $result;
    }
}
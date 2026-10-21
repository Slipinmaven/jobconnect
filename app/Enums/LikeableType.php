<?php

namespace App\Enums;

/**
 * LikeableType - Defines all entity types that can be liked
 */
enum LikeableType: string
{
    case JOB = 'job';
    case COMMENT = 'comment';
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
            self::COMMENT => 'Comment',
            self::FEED => 'Feed Post',
            self::PROFILE => 'Profile',
            self::ARTICLE => 'Article',
            self::BLOG => 'Blog Post',
        };
    }

    /**
     * Get the icon for this entity type
     */
    public function icon(): string
    {
        return match ($this) {
            self::JOB => 'fa-briefcase',
            self::COMMENT => 'fa-comment',
            self::FEED => 'fa-newspaper',
            self::PROFILE => 'fa-user',
            self::ARTICLE => 'fa-file-alt',
            self::BLOG => 'fa-blog',
        };
    }

    /**
     * Check if a string is a valid likeable type
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::toArray());
    }

    /**
     * Get all available likeable types as array
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

    /**
     * Get all types with icons
     */
    public static function withIcons(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->icon();
        }
        return $result;
    }
}
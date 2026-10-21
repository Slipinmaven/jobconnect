<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case EMPLOYER = 'employer';
    case JOBSEEKER = 'jobseeker';

    /**
     * Get the display name for the role
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::EMPLOYER => 'Employer',
            self::JOBSEEKER => 'Job Seeker',
        };
    }

    /**
     * Get the description for the role
     */
    public function description(): string
    {
        return match ($this) {
            self::ADMIN => 'Full system access and management',
            self::EMPLOYER => 'Can post jobs and manage applications',
            self::JOBSEEKER => 'Can browse jobs and apply',
        };
    }

    /**
     * Check if role has admin privileges
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Check if role can post jobs
     */
    public function canPostJobs(): bool
    {
        return match ($this) {
            self::ADMIN, self::EMPLOYER => true,
            self::JOBSEEKER => false,
        };
    }

    /**
     * Get all available roles as array
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all roles with their labels
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}

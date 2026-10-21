<?php

namespace App\DTOs;

use App\Core\BaseDTO;

/**
 * JobSeekerProfileDTO - Transfers jobseeker profile data
 */
class JobSeekerProfileDTO extends BaseDTO
{
    public ?string $first_name = null;
    public ?string $last_name = null;
    public ?string $professional_title = null;
    public ?string $summary = null;
    public ?int $location_id = null;
    public ?int $years_experience = null;
    public ?float $salary_expectation = null;
    public ?string $salary_currency = null;
    public ?bool $open_to_work = null;
    public ?bool $remote_preference = null;
    public ?bool $relocation_willing = null;

    /**
     * Validate jobseeker profile
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->first_name !== null && empty(trim($this->first_name))) {
            $errors['first_name'] = 'First name cannot be empty';
        }

        if ($this->last_name !== null && empty(trim($this->last_name))) {
            $errors['last_name'] = 'Last name cannot be empty';
        }

        if ($this->years_experience !== null) {
            if ($this->years_experience < 0) {
                $errors['years_experience'] = 'Years experience cannot be negative';
            }
            if ($this->years_experience > 50) {
                $errors['years_experience'] = 'Years experience cannot exceed 50';
            }
        }

        if ($this->salary_expectation !== null && $this->salary_expectation < 0) {
            $errors['salary_expectation'] = 'Salary expectation cannot be negative';
        }

        return $errors;
    }

    /**
     * Get full name
     */
    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}

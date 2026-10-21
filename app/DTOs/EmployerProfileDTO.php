<?php

namespace App\DTOs;

use App\Core\BaseDTO;

/**
 * EmployerProfileDTO - Transfers employer profile data
 */
class EmployerProfileDTO extends BaseDTO
{
    public ?string $company_name = null;
    public ?string $company_description = null;
    public ?int $industry_id = null;
    public ?int $location_id = null;
    public ?string $website = null;
    public ?string $contact_email = null;
    public ?string $contact_phone = null;
    public ?string $contact_name = null;
    public ?string $employer_type = null;

    /**
     * Validate employer profile
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->company_name !== null && empty(trim($this->company_name))) {
            $errors['company_name'] = 'Company name cannot be empty';
        }

        if ($this->contact_email !== null && !filter_var($this->contact_email, FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = 'Invalid email format';
        }

        if ($this->website !== null && $this->website !== '') {
            if (!filter_var($this->website, FILTER_VALIDATE_URL)) {
                $errors['website'] = 'Invalid website URL';
            }
        }

        return $errors;
    }
}

<?php

namespace App\DTOs;

use App\Core\BaseDTO;

/**
 * Test DTO for demonstration
 */
class TestDTO extends BaseDTO
{
    public ?string $name = null;
    public ?string $email = null;
    public ?int $age = null;
    public ?bool $active = null;

    /**
     * Validate the DTO
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->name !== null && strlen($this->name) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }

        if ($this->email !== null && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if ($this->age !== null && ($this->age < 0 || $this->age > 150)) {
            $errors['age'] = 'Age must be between 0 and 150';
        }

        return $errors;
    }
}

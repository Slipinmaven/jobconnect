<?php

namespace App\Core;

/**
 * BaseDTO - All DTOs extend this
 * 
 * PURPOSE: Data Transfer Objects ensure type safety and validation
 * 
 * WHY USE DTOs:
 * - No more associative arrays with magic strings
 * - Type safety (int, string, etc. are enforced)
 * - Validation built-in
 * - Self-documenting code
 * - IDE autocompletion works
 * 
 * HOW IT WORKS:
 * 1. Create a DTO class with typed properties
 * 2. Use fromRequest() to create from input
 * 3. Validate before processing
 * 4. Pass DTO to services instead of raw arrays
 */
abstract class BaseDTO
{
    /**
     * Create DTO from request data
     */
    public static function fromRequest(array $data): static
    {
        $dto = new static();

        foreach ($data as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->$key = $value;
            }
        }

        return $dto;
    }

    /**
     * Convert DTO to array (excluding null values)
     */
    public function toArray(): array
    {
        $array = [];
        foreach (get_object_vars($this) as $key => $value) {
            if ($value !== null) {
                $array[$key] = $value;
            }
        }
        return $array;
    }

    /**
     * Validate the DTO - override in child classes
     */
    public function validate(): array
    {
        return []; // No validation by default
    }

    /**
     * Check if DTO is valid
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}

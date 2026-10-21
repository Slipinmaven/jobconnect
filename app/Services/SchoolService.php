<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\SchoolRepository;
use Exception;

/**
 * SchoolService - Handles business logic for schools/educational institutions
 * 
 * Business rules:
 * - School names must be unique
 * - Schools must have a valid type
 * - Duplicate schools should be prevented
 * - School slugs must be URL-friendly
 * - Location data (city/country) is optional but recommended
 */
class SchoolService extends BaseService
{
    private SchoolRepository $schoolRepository;

    /**
     * Allowed school types
     */
    private const ALLOWED_TYPES = ['high_school', 'university', 'college', 'bootcamp', 'other'];

    public function __construct(SchoolRepository $schoolRepository)
    {
        parent::__construct();
        $this->schoolRepository = $schoolRepository;
    }

    /**
     * Get school by ID with validation
     *
     * @param int $id
     * @return array|null
     * 
          */
    
    public function getAllSchools(): array
    {
        $this->logInfo('Fetching all schools');

        return $this->schoolRepository->findAll();
    }
    public function getSchool(int $id): ?array
    {
        $this->logInfo('Fetching school', ['id' => $id]);

        $school = $this->schoolRepository->findById($id);

        if (!$school) {
            $this->logWarning('School not found', ['id' => $id]);
            return null;
        }

        return $school;
    }

    /**
     * Get school by slug
     *
     * @param string $slug
     * @return array|null
     */
    public function getSchoolBySlug(string $slug): ?array
    {
        $this->logInfo('Fetching school by slug', ['slug' => $slug]);

        return $this->schoolRepository->findBySlug($slug);
    }

    /**
     * Search schools with filters
     *
     * @param string $query
     * @param array $filters
     * @param int $limit
     * @return array
     */
    public function searchSchools(string $query, array $filters = [], int $limit = 20): array
    {
        $this->logInfo('Searching schools', [
            'query' => $query,
            'filters' => $filters,
            'limit' => $limit
        ]);

        // Validate filters
        if (!empty($filters['type'])) {
            $this->validateSchoolType($filters['type']);
        }

        return $this->schoolRepository->search($query, $filters, $limit);
    }

    /**
     * Create a new school
     *
     * @param array $data
     * @return array|null Returns school data with 'id' and 'created' status
     */
    public function createSchool(array $data): ?array
    {
        $this->clearErrors();
        $this->logInfo('Attempting to create school', ['data' => $data]);

        // Validate required fields
        if (!$this->validateRequired($data, ['name', 'type'])) {
            $this->logError('School creation failed - missing required fields', [
                'errors' => $this->getErrors()
            ]);
            return null;
        }

        // Validate school type
        if (!$this->validateSchoolType($data['type'])) {
            return null;
        }

        // Validate name length
        if (!$this->validateLength($data['name'], 'name', 2, 200)) {
            return null;
        }

        // Validate location if provided
        if (!empty($data['city']) && !$this->validateLength($data['city'], 'city', 2, 100)) {
            return null;
        }

        if (!empty($data['country']) && !$this->validateLength($data['country'], 'country', 2, 100)) {
            return null;
        }

        // Check if school already exists (by name)
        $existing = $this->schoolRepository->findByName($data['name']);
        if ($existing) {
            $this->addError('name', 'A school with this name already exists');
            $this->logWarning('School creation failed - name already exists', [
                'name' => $data['name'],
                'existing_id' => $existing['id']
            ]);
            return [
                'id' => $existing['id'],
                'created' => false,
                'message' => 'School already exists',
                'school' => $existing
            ];
        }

        // Create the school
        $id = $this->schoolRepository->createSchool($data);

        if (!$id) {
            $this->addError('general', 'Failed to create school');
            $this->logError('School creation failed - repository returned null');
            return null;
        }

        $this->logInfo('School created successfully', ['id' => $id]);

        return [
            'id' => $id,
            'created' => true,
            'message' => 'School created successfully',
            'school' => $this->schoolRepository->findById($id)
        ];
    }

    /**
     * Update an existing school
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateSchool(int $id, array $data): bool
    {
        $this->clearErrors();
        $this->logInfo('Attempting to update school', ['id' => $id, 'data' => $data]);

        // Check if school exists
        $existing = $this->schoolRepository->findById($id);
        if (!$existing) {
            $this->addError('id', 'School not found');
            $this->logWarning('School update failed - school not found', ['id' => $id]);
            return false;
        }

        // Validate type if provided
        if (!empty($data['type']) && !$this->validateSchoolType($data['type'])) {
            return false;
        }

        // Validate name if provided
        if (!empty($data['name'])) {
            if (!$this->validateLength($data['name'], 'name', 2, 200)) {
                return false;
            }

            // Check if new name conflicts with existing school
            $nameExists = $this->schoolRepository->findByName($data['name']);
            if ($nameExists && $nameExists['id'] != $id) {
                $this->addError('name', 'Another school with this name already exists');
                $this->logWarning('School update failed - name already exists', [
                    'name' => $data['name'],
                    'conflicting_id' => $nameExists['id']
                ]);
                return false;
            }
        }

        // Validate location if provided
        if (!empty($data['city']) && !$this->validateLength($data['city'], 'city', 2, 100)) {
            return false;
        }

        if (!empty($data['country']) && !$this->validateLength($data['country'], 'country', 2, 100)) {
            return false;
        }

        // Perform update
        $success = $this->schoolRepository->updateSchool($id, $data);

        if ($success) {
            $this->logInfo('School updated successfully', ['id' => $id]);
        } else {
            $this->logError('School update failed', ['id' => $id]);
            $this->addError('general', 'Failed to update school');
        }

        return $success;
    }

    /**
     * Delete a school
     *
     * @param int $id
     * @return bool
     */
    public function deleteSchool(int $id): bool
    {
        $this->logInfo('Attempting to delete school', ['id' => $id]);

        // Check if school exists
        $existing = $this->schoolRepository->findById($id);
        if (!$existing) {
            $this->logWarning('School deletion failed - school not found', ['id' => $id]);
            return false;
        }

        // Check if school is in use (optional - you might want to prevent deletion if used)
        // This would require a method to check usage in jobseeker_educations

        $success = $this->schoolRepository->deleteSchool($id);

        if ($success) {
            $this->logInfo('School deleted successfully', ['id' => $id]);
        } else {
            $this->logError('School deletion failed', ['id' => $id]);
        }

        return $success;
    }

    /**
     * Get schools by type
     *
     * @param string $type
     * @return array
     */
    public function getSchoolsByType(string $type): array
    {
        if (!$this->validateSchoolType($type)) {
            return [];
        }

        return $this->schoolRepository->getByType($type);
    }

    /**
     * Get schools by country
     *
     * @param string $country
     * @param string|null $type
     * @return array
     */
    public function getSchoolsByCountry(string $country, ?string $type = null): array
    {
        $this->validateLength($country, 'country', 2, 100);

        if ($type && !$this->validateSchoolType($type)) {
            return [];
        }

        return $this->schoolRepository->getByCountry($country, $type);
    }

    /**
     * Get schools by city
     *
     * @param string $city
     * @param string|null $country
     * @return array
     */
    public function getSchoolsByCity(string $city, ?string $country = null): array
    {
        $this->validateLength($city, 'city', 2, 100);

        return $this->schoolRepository->getByCity($city, $country);
    }

    /**
     * Get popular schools
     *
     * @param int $limit
     * @param string|null $type
     * @return array
     */
    public function getPopularSchools(int $limit = 10, ?string $type = null): array
    {
        $this->validateRange($limit, 'limit', 1, 100);

        if ($type && !$this->validateSchoolType($type)) {
            return [];
        }

        return $this->schoolRepository->getPopular($limit, $type);
    }

    /**
     * Get recently added schools
     *
     * @param int $limit
     * @return array
     */
    public function getRecentSchools(int $limit = 10): array
    {
        $this->validateRange($limit, 'limit', 1, 100);

        return $this->schoolRepository->getRecent($limit);
    }

    /**
     * Get autocomplete suggestions
     *
     * @param string $query
     * @param string|null $type
     * @param int $limit
     * @return array
     */
    public function autocomplete(string $query, ?string $type = null, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $this->validateRange($limit, 'limit', 1, 50);

        if ($type && !$this->validateSchoolType($type)) {
            return [];
        }

        return $this->schoolRepository->autocomplete($query, $type, $limit);
    }

    /**
     * Get or create a school (useful for forms/imports)
     *
     * @param string $name
     * @param string $type
     * @param string|null $city
     * @param string|null $country
     * @return array|null
     */
    public function findOrCreateSchool(string $name, string $type, ?string $city = null, ?string $country = null): ?array
    {
        $this->logInfo('Find or create school', [
            'name' => $name,
            'type' => $type,
            'city' => $city,
            'country' => $country
        ]);

        // Validate
        if (!$this->validateRequired(['name' => $name, 'type' => $type], ['name', 'type'])) {
            return null;
        }

        if (!$this->validateSchoolType($type)) {
            return null;
        }

        $id = $this->schoolRepository->getOrCreate($name, $type, $city, $country);

        if (!$id) {
            $this->logError('Find or create school failed');
            return null;
        }

        return [
            'id' => $id,
            'school' => $this->schoolRepository->findById($id)
        ];
    }

    /**
     * Bulk import schools
     *
     * @param array $schools
     * @return array
     */
    public function bulkImportSchools(array $schools): array
    {
        $this->logInfo('Bulk importing schools', ['count' => count($schools)]);

        // Validate each school before import
        $validSchools = [];
        $validationErrors = [];

        foreach ($schools as $index => $school) {
            $this->clearErrors();

            if (!$this->validateRequired($school, ['name', 'type'])) {
                $validationErrors[] = [
                    'index' => $index,
                    'school' => $school,
                    'errors' => $this->getErrors()
                ];
                continue;
            }

            if (!$this->validateSchoolType($school['type'])) {
                $validationErrors[] = [
                    'index' => $index,
                    'school' => $school,
                    'errors' => $this->getErrors()
                ];
                continue;
            }

            $validSchools[] = $school;
        }

        if (!empty($validationErrors)) {
            $this->logWarning('Bulk import validation failed', [
                'valid_count' => count($validSchools),
                'error_count' => count($validationErrors)
            ]);
        }

        // Perform bulk import
        $results = $this->schoolRepository->bulkImport($validSchools);

        // Add validation errors to results
        $results['validation_errors'] = $validationErrors;
        $results['total_processed'] = count($schools);

        $this->logInfo('Bulk import completed', [
            'created' => $results['created'],
            'skipped' => $results['skipped'],
            'errors' => count($results['errors']),
            'validation_errors' => count($validationErrors)
        ]);

        return $results;
    }

    /**
     * Get school statistics
     *
     * @return array
     */
    public function getSchoolStatistics(): array
    {
        $this->logInfo('Fetching school statistics');

        return $this->schoolRepository->getStatistics();
    }

    /**
     * Get grouped schools by country
     *
     * @return array
     */
    public function getSchoolsGroupedByCountry(): array
    {
        return $this->schoolRepository->getGroupedByCountry();
    }

    /**
     * Get grouped schools by type
     *
     * @return array
     */
    public function getSchoolsGroupedByType(): array
    {
        return $this->schoolRepository->getGroupedByType();
    }

    /**
     * Get distinct countries
     *
     * @return array
     */
    public function getCountries(): array
    {
        return $this->schoolRepository->getCountries();
    }

    /**
     * Get cities by country
     *
     * @param string $country
     * @return array
     */
    public function getCitiesByCountry(string $country): array
    {
        $this->validateLength($country, 'country', 2, 100);

        return $this->schoolRepository->getCitiesByCountry($country);
    }

    /**
     * Find similar schools
     *
     * @param string $name
     * @param int $limit
     * @return array
     */
    public function findSimilarSchools(string $name, int $limit = 5): array
    {
        $this->validateLength($name, 'name', 2, 200);
        $this->validateRange($limit, 'limit', 1, 20);

        return $this->schoolRepository->findSimilar($name, $limit);
    }

    /**
     * Merge duplicate schools
     *
     * @param int $keepId
     * @param array $mergeIds
     * @return bool
     */
    public function mergeDuplicateSchools(int $keepId, array $mergeIds): bool
    {
        $this->logInfo('Merging schools', ['keep_id' => $keepId, 'merge_ids' => $mergeIds]);

        // Validate IDs
        if (empty($mergeIds)) {
            $this->addError('mergeIds', 'No schools to merge');
            return false;
        }

        if (in_array($keepId, $mergeIds)) {
            $this->addError('mergeIds', 'Cannot merge a school into itself');
            return false;
        }

        // Check if keep school exists
        $keepSchool = $this->schoolRepository->findById($keepId);
        if (!$keepSchool) {
            $this->addError('keepId', 'School to keep not found');
            return false;
        }

        // Check if all merge schools exist
        foreach ($mergeIds as $id) {
            $school = $this->schoolRepository->findById($id);
            if (!$school) {
                $this->addError('mergeIds', "School with ID {$id} not found");
                return false;
            }
        }

        $success = $this->schoolRepository->mergeSchools($keepId, $mergeIds);

        if ($success) {
            $this->logInfo('Schools merged successfully', [
                'keep_id' => $keepId,
                'merged_count' => count($mergeIds)
            ]);
        } else {
            $this->logError('School merge failed');
        }

        return $success;
    }

    /**
     * Validate school type
     *
     * @param string $type
     * @return bool
     */
    private function validateSchoolType(string $type): bool
    {
        return $this->validateInArray($type, 'type', self::ALLOWED_TYPES);
    }

    /**
     * Get all allowed school types
     *
     * @return array
     */
    public function getAllowedTypes(): array
    {
        return self::ALLOWED_TYPES;
    }

    /**
     * Check if a school is in use (has education records)
     *
     * @param int $id
     * @return bool
     */
    public function isSchoolInUse(int $id): bool
    {
        // This would need a method in the repository to check usage
        // For now, we can check if it appears in the popular schools list with usage > 0
        $popular = $this->schoolRepository->getPopular(100);

        foreach ($popular as $school) {
            if ($school['id'] == $id && ($school['usage_count'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }
}
<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\IndustryRepository;

/**
 * IndustryService - Handles business logic for industries
 * 
 * PURPOSE: Validates industry data, applies business rules, and coordinates
 * between controllers and the industry repository
 * 
 * FUNCTIONALITY:
 * - Validate industry data before saving
 * - Handle industry search and filtering logic
 * - Process bulk operations
 * - Generate slugs with uniqueness checks
 * - Log all industry operations
 */
class IndustryService extends BaseService
{
    private IndustryRepository $industryRepo;

    /**
     * @var array Allowed fields for industry creation/update
     */
    private array $allowedFields = ['name', 'slug'];

    public function __construct(IndustryRepository $industryRepo)
    {
        parent::__construct();
        $this->industryRepo = $industryRepo;
    }

    // ==================== CRUD OPERATIONS WITH VALIDATION ====================

    /**
     * Create a new industry
     * 
     * @param array $data Industry data (name, slug)
     * @return array|null Created industry data or null on failure
     */
    public function createIndustry(array $data): ?array
    {
        try {
            $this->logInfo('Attempting to create industry', ['data' => $data]);

            // Validate required fields
            if (!$this->validateRequired($data, ['name'])) {
                $this->logWarning('Industry creation failed: missing required fields', [
                    'errors' => $this->getErrors()
                ]);
                return null;
            }

            // Sanitize input
            $data = $this->sanitize($data, $this->allowedFields);

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name']);
            }

            // Validate name length
            if (!$this->validateLength($data['name'], 'name', 2, 100)) {
                return null;
            }

            // Validate slug format
            if (!$this->validateSlugFormat($data['slug'])) {
                $this->addError('slug', 'Slug can only contain lowercase letters, numbers, and hyphens');
                return null;
            }

            // Check uniqueness
            if (!$this->validateUniqueSlug($data['slug'])) {
                return null;
            }

            // Create the industry
            $id = $this->industryRepo->create($data);

            if (!$id) {
                $this->logError('Failed to create industry in database', ['data' => $data]);
                $this->addError('general', 'Failed to create industry');
                return null;
            }

            $industry = $this->industryRepo->findById($id);
            $this->logInfo('Industry created successfully', ['id' => $id, 'name' => $data['name']]);

            return $industry;

        } catch (\Exception $e) {
            $this->logError('Exception in createIndustry', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('general', 'An error occurred while creating industry');
            return null;
        }
    }

    /**
     * Update an existing industry
     * 
     * @param int $id Industry ID
     * @param array $data Updated industry data
     * @return bool Success
     */
    public function updateIndustry(int $id, array $data): bool
    {
        try {
            $this->logInfo('Attempting to update industry', ['id' => $id, 'data' => $data]);

            // Check if industry exists
            $existing = $this->industryRepo->findById($id);
            if (!$existing) {
                $this->addError('id', 'Industry not found');
                return false;
            }

            // Sanitize input
            $data = $this->sanitize($data, $this->allowedFields);

            // Validate name if provided
            if (isset($data['name'])) {
                if (!$this->validateLength($data['name'], 'name', 2, 100)) {
                    return false;
                }
            }

            // Handle slug if provided or if name changed
            if (isset($data['slug'])) {
                if (!$this->validateSlugFormat($data['slug'])) {
                    $this->addError('slug', 'Slug can only contain lowercase letters, numbers, and hyphens');
                    return false;
                }
                
                // Check uniqueness (excluding current industry)
                if (!$this->validateUniqueSlug($data['slug'], $id)) {
                    return false;
                }
            } elseif (isset($data['name']) && $data['name'] !== $existing['name']) {
                // Auto-generate slug from new name
                $data['slug'] = $this->generateUniqueSlug($data['name'], $id);
            }

            // Perform update
            $success = $this->industryRepo->update($id, $data);

            if ($success) {
                $this->logInfo('Industry updated successfully', ['id' => $id]);
            } else {
                $this->logWarning('Industry update failed', ['id' => $id]);
                $this->addError('general', 'Failed to update industry');
            }

            return $success;

        } catch (\Exception $e) {
            $this->logError('Exception in updateIndustry', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->addError('general', 'An error occurred while updating industry');
            return false;
        }
    }

    /**
     * Delete an industry
     * 
     * @param int $id Industry ID
     * @return bool Success
     */
    public function deleteIndustry(int $id): bool
    {
        try {
            $this->logInfo('Attempting to delete industry', ['id' => $id]);

            // Check if industry exists
            $existing = $this->industryRepo->findById($id);
            if (!$existing) {
                $this->addError('id', 'Industry not found');
                return false;
            }

            // Check if industry is in use
            if ($this->isIndustryInUse($id)) {
                $this->addError('general', 'Cannot delete industry that is in use by employers or jobs');
                return false;
            }

            $success = $this->industryRepo->delete($id);

            if ($success) {
                $this->logInfo('Industry deleted successfully', ['id' => $id]);
            } else {
                $this->logWarning('Industry deletion failed', ['id' => $id]);
                $this->addError('general', 'Failed to delete industry');
            }

            return $success;

        } catch (\Exception $e) {
            $this->logError('Exception in deleteIndustry', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->addError('general', 'An error occurred while deleting industry');
            return false;
        }
    }

    // ==================== SEARCH AND RETRIEVAL ====================

    /**
     * Get all industries for dropdown
     * 
     * @param string|null $search Optional search term
     * @return array Industries formatted for dropdown
     */
    public function getIndustriesForDropdown(?string $search = null): array
    {
        try {
            if ($search && strlen($search) >= 2) {
                $industries = $this->industryRepo->searchByName($search);
            } else {
                $industries = $this->industryRepo->getAllForDropdown();
            }

            $this->logInfo('Retrieved industries for dropdown', [
                'count' => count($industries),
                'search' => $search
            ]);

            return $industries;

        } catch (\Exception $e) {
            $this->logError('Failed to get industries for dropdown', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get industry by ID with validation
     * 
     * @param int $id Industry ID
     * @return array|null Industry data or null if not found
     */
    public function getIndustryById(int $id): ?array
    {
        try {
            if ($id <= 0) {
                $this->addError('id', 'Invalid industry ID');
                return null;
            }

            $industry = $this->industryRepo->findById($id);

            if (!$industry) {
                $this->addError('id', 'Industry not found');
                return null;
            }

            return $industry;

        } catch (\Exception $e) {
            $this->logError('Failed to get industry by ID', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get industry by slug
     * 
     * @param string $slug Industry slug
     * @return array|null Industry data or null if not found
     */
    public function getIndustryBySlug(string $slug): ?array
    {
        try {
            if (empty($slug)) {
                $this->addError('slug', 'Slug is required');
                return null;
            }

            $industry = $this->industryRepo->findBySlug($slug);

            if (!$industry) {
                $this->addError('slug', 'Industry not found');
                return null;
            }

            return $industry;

        } catch (\Exception $e) {
            $this->logError('Failed to get industry by slug', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get popular industries with job counts
     * 
     * @param int $limit Number of industries to return
     * @return array Popular industries
     */
    public function getPopularIndustries(int $limit = 10): array
    {
        try {
            $industries = $this->industryRepo->getPopularWithJobCount($limit);
            
            $this->logInfo('Retrieved popular industries', [
                'count' => count($industries),
                'limit' => $limit
            ]);

            return $industries;

        } catch (\Exception $e) {
            $this->logError('Failed to get popular industries', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get industries grouped by first letter
     * 
     * @return array Industries grouped A-Z
     */
    public function getGroupedIndustries(): array
    {
        try {
            return $this->industryRepo->getGroupedByLetter();
        } catch (\Exception $e) {
            $this->logError('Failed to get grouped industries', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    // ==================== BULK OPERATIONS ====================

    /**
     * Bulk create industries
     * 
     * @param array $industries Array of industry names
     * @return array Result with counts and errors
     */
    public function bulkCreateIndustries(array $industries): array
    {
        $result = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        try {
            $this->logInfo('Starting bulk industry creation', ['count' => count($industries)]);
            $this->clearErrors();

            foreach ($industries as $index => $industryData) {
                // Handle both string and array input
                if (is_string($industryData)) {
                    $industryData = ['name' => $industryData];
                }

                $created = $this->createIndustry($industryData);

                if ($created) {
                    $result['success']++;
                } else {
                    $result['failed']++;
                    $result['errors'][] = [
                        'index' => $index,
                        'data' => $industryData,
                        'errors' => $this->getErrors()
                    ];
                    $this->clearErrors();
                }
            }

            $this->logInfo('Bulk industry creation completed', $result);

        } catch (\Exception $e) {
            $this->logError('Bulk industry creation failed', [
                'error' => $e->getMessage()
            ]);
            $result['errors'][] = [
                'general' => 'Bulk operation failed: ' . $e->getMessage()
            ];
        }

        return $result;
    }

    /**
 * Get all industries with pagination
 * 
 * @param int $page Page number (1-indexed)
 * @param int $perPage Items per page
 * @param string|null $search Optional search term
 * @return array Paginated results with 'data', 'total', 'per_page', 'current_page', 'last_page'
 */
public function getAllPaginated(int $page = 1, int $perPage = 20, ?string $search = null): array
{
    try {
        $offset = ($page - 1) * $perPage;
        
        $total = $this->industryRepo->getCount($search);
        $data = $this->industryRepo->findAllPaginated($offset, $perPage, $search);
        
        $lastPage = $total > 0 ? ceil($total / $perPage) : 1;
        
        $this->logInfo('Retrieved paginated industries', [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'search' => $search
        ]);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage
        ];
    } catch (\Exception $e) {
        $this->logError('Failed to get paginated industries', [
            'page' => $page,
            'perPage' => $perPage,
            'search' => $search,
            'error' => $e->getMessage()
        ]);
        return [
            'data' => [],
            'total' => 0,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => 1
        ];
    }
}
    // ==================== VALIDATION HELPERS ====================

    /**
     * Validate slug format
     * 
     * @param string $slug Slug to validate
     * @return bool Valid or not
     */
    private function validateSlugFormat(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }

    /**
     * Validate slug uniqueness
     * 
     * @param string $slug Slug to check
     * @param int|null $excludeId Industry ID to exclude from check
     * @return bool Unique or not
     */
    private function validateUniqueSlug(string $slug, ?int $excludeId = null): bool
    {
        $existing = $this->industryRepo->findWhere(['slug' => $slug]);

        if (empty($existing)) {
            return true;
        }

        // If we're updating and the existing record is the same one
        if ($excludeId && $existing[0]['id'] == $excludeId) {
            return true;
        }

        $this->addError('slug', 'This slug already exists');
        return false;
    }

    /**
     * Generate a unique slug from a name
     * 
     * @param string $name Source name
     * @param int|null $excludeId Industry ID to exclude
     * @return string Unique slug
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = $this->slugify($name);
        $originalSlug = $slug;
        $counter = 1;

        while (!$this->validateUniqueSlug($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Convert string to URL-friendly slug
     * 
     * @param string $string String to slugify
     * @return string Slug
     */
    private function slugify(string $string): string
    {
        // Convert to lowercase
        $slug = strtolower($string);
        
        // Replace non-alphanumeric characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');
        
        return $slug;
    }

    /**
     * Check if industry is in use by employers or jobs
     * 
     * @param int $id Industry ID
     * @return bool True if in use
     */
    private function isIndustryInUse(int $id): bool
    {
        // This would check related tables
        // You'll need to implement these checks based on your schema
        
        // Example:
        // $employerCount = $this->db->query(
        //     "SELECT COUNT(*) FROM employers WHERE industry_id = ?", 
        //     [$id]
        // )->fetchColumn();
        // 
        // if ($employerCount > 0) return true;
        
        return false; // Placeholder
    }

    // ==================== EXPORT/IMPORT ====================

    /**
     * Export industries to array format
     * 
     * @return array Industries ready for export
     */
    public function exportIndustries(): array
    {
        try {
            $industries = $this->industryRepo->findAll('name ASC');
            
            return array_map(function($industry) {
                return [
                    'id' => $industry['id'],
                    'name' => $industry['name'],
                    'slug' => $industry['slug'],
                    'created_at' => $industry['created_at']
                ];
            }, $industries);

        } catch (\Exception $e) {
            $this->logError('Failed to export industries', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Import industries from array
     * 
     * @param array $data Import data
     * @param bool $overwrite Whether to overwrite existing
     * @return array Import results
     */
    public function importIndustries(array $data, bool $overwrite = false): array
    {
        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        try {
            $this->logInfo('Starting industry import', [
                'count' => count($data),
                'overwrite' => $overwrite
            ]);

            foreach ($data as $index => $item) {
                try {
                    // Validate required fields
                    if (empty($item['name'])) {
                        $result['errors'][] = "Row {$index}: Missing name";
                        $result['skipped']++;
                        continue;
                    }

                    // Check if exists
                    $slug = $item['slug'] ?? $this->slugify($item['name']);
                    $existing = $this->industryRepo->findWhere(['slug' => $slug]);

                    if (!empty($existing)) {
                        if ($overwrite) {
                            // Update existing
                            $updated = $this->updateIndustry($existing[0]['id'], [
                                'name' => $item['name'],
                                'slug' => $slug
                            ]);
                            
                            if ($updated) {
                                $result['updated']++;
                            } else {
                                $result['errors'][] = "Row {$index}: Update failed";
                                $result['skipped']++;
                            }
                        } else {
                            $result['skipped']++;
                        }
                    } else {
                        // Create new
                        $created = $this->createIndustry([
                            'name' => $item['name'],
                            'slug' => $slug
                        ]);
                        
                        if ($created) {
                            $result['created']++;
                        } else {
                            $result['errors'][] = "Row {$index}: Creation failed";
                            $result['skipped']++;
                        }
                    }

                } catch (\Exception $e) {
                    $result['errors'][] = "Row {$index}: " . $e->getMessage();
                    $result['skipped']++;
                }
            }

            $this->logInfo('Industry import completed', $result);

        } catch (\Exception $e) {
            $this->logError('Industry import failed', [
                'error' => $e->getMessage()
            ]);
            $result['errors'][] = 'Import failed: ' . $e->getMessage();
        }

        return $result;
    }
}
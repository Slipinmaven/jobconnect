<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\IndustryService;

class IndustryController extends BaseController
{
        private IndustryService $industryService;

    public function __construct(IndustryService $industryService)
    {
        parent::__construct();
        $this->industryService = $industryService;
    }
    // ==================== WEB ROUTES ====================

    /**
     * Display a listing of industries (admin view)
     * GET /admin/industries
     */
    public function index(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $search = $this->get('search');
        $page = (int)$this->get('page', 1);
        $perPage = 20;

        // Assuming the service has a paginated method; if not, we can add one.
        // For now, we'll fetch all and paginate in view, but better to implement pagination in service.
        $industries = $this->industryService->getAllPaginated($page, $perPage, $search);

        $this->view('admin/industries/index', [
            'industries' => $industries,
            'search' => $search,
            'page' => $page,
        ]);
    }

    /**
     * Show form to create a new industry
     * GET /admin/industries/create
     */
    public function create(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $this->view('admin/industries/create');
    }

    /**
     * Store a newly created industry
     * POST /admin/industries
     */
    public function store(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $token = $this->post('_token');
        if (!$this->verifyCsrf($token)) {
            $this->withFlash('error', 'Invalid CSRF token')->back();
            return;
        }

        $data = [
            'name' => trim($this->post('name')),
            'slug' => trim($this->post('slug')),
        ];

        $created = $this->industryService->createIndustry($data);

        if ($created) {
            $this->withFlash('success', 'Industry created successfully')->redirect('/admin/industries');
        } else {
            $errors = $this->industryService->getErrors();
            $this->withInput($data)->withErrors($errors)->redirect('/admin/industries/create');
        }
    }

    /**
     * Show form to edit an industry
     * GET /admin/industries/edit/{id}
     */
    public function edit(int $id): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $industry = $this->industryService->getIndustryById($id);
        if (!$industry) {
            $this->withFlash('error', 'Industry not found')->redirect('/admin/industries');
            return;
        }

        $this->view('admin/industries/edit', [
            'industry' => $industry,
        ]);
    }

    /**
     * Update an industry
     * POST /admin/industries/update/{id}
     */
    public function update(int $id): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $token = $this->post('_token');
        if (!$this->verifyCsrf($token)) {
            $this->withFlash('error', 'Invalid CSRF token')->back();
            return;
        }

        $data = [];
        $name = trim($this->post('name'));
        if ($name !== '') {
            $data['name'] = $name;
        }
        $slug = trim($this->post('slug'));
        if ($slug !== '') {
            $data['slug'] = $slug;
        }

        $updated = $this->industryService->updateIndustry($id, $data);

        if ($updated) {
            $this->withFlash('success', 'Industry updated successfully')->redirect('/admin/industries');
        } else {
            $errors = $this->industryService->getErrors();
            $this->withInput($data)->withErrors($errors)->redirect('/admin/industries/edit/' . $id);
        }
    }

    /**
     * Delete an industry
     * POST /admin/industries/delete/{id}
     */
    public function delete(int $id): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $token = $this->post('_token');
        if (!$this->verifyCsrf($token)) {
            $this->withFlash('error', 'Invalid CSRF token')->back();
            return;
        }

        $deleted = $this->industryService->deleteIndustry($id);

        if ($deleted) {
            $this->withFlash('success', 'Industry deleted successfully')->redirect('/admin/industries');
        } else {
            $errors = $this->industryService->getErrors();
            $this->withFlash('error', $errors['general'] ?? 'Failed to delete industry')->redirect('/admin/industries');
        }
    }

    // ==================== PUBLIC ROUTES (WEB) ====================

    /**
     * Show a single industry (public)
     * GET /industries/{slug}
     */
    public function show(string $slug): void
    {
        $industry = $this->industryService->getIndustryBySlug($slug);
        if (!$industry) {
            $this->view('errors/404', [], 'error');
            return;
        }

        // Get jobs in this industry (assuming you have a JobService)
        // This would be a separate service call.

        $this->view('industries/show', [
            'industry' => $industry,
        ]);
    }

    /**
     * Get industries for dropdown (public, usually AJAX)
     * GET /industries/dropdown?search=...
     */
    public function dropdown(): void
    {
        $search = $this->get('search');
        $industries = $this->industryService->getIndustriesForDropdown($search);

        if ($this->wantsJson()) {
            $this->json($industries);
        } else {
            // For non-AJAX, we could render a partial or redirect
            $this->partial('partials/industry-dropdown', ['industries' => $industries]);
        }
    }

    /**
     * Get popular industries (public)
     * GET /industries/popular?limit=10
     */
    public function popular(): void
    {
        $limit = (int)$this->get('limit', 10);
        $industries = $this->industryService->getPopularIndustries($limit);

        if ($this->wantsJson()) {
            $this->json($industries);
        } else {
            $this->partial('partials/popular-industries', ['industries' => $industries]);
        }
    }

    /**
     * Get industries grouped by letter (public)
     * GET /industries/grouped
     */
    public function grouped(): void
    {
        $industries = $this->industryService->getGroupedIndustries();

        if ($this->wantsJson()) {
            $this->json($industries);
        } else {
            $this->view('industries/grouped', ['groups' => $industries]);
        }
    }

    // ==================== API ROUTES ====================

    /**
     * API: List all industries
     * GET /api/industries
     */
    public function apiIndex(): void
    {
        $this->requireApiAuth();

        $search = $this->get('search');
        $page = (int)$this->get('page', 1);
        $limit = (int)$this->get('limit', 20);

        // Assuming the service has a method to get paginated results
        $industries = $this->industryService->getAllPaginated($page, $limit, $search);

        $this->success($industries);
    }

    /**
     * API: Get a single industry
     * GET /api/industries/{id}
     */
    public function apiShow(int $id): void
    {
        $this->requireApiAuth();

        $industry = $this->industryService->getIndustryById($id);
        if (!$industry) {
            $this->notFound('Industry not found');
        }

        $this->success($industry);
    }

    /**
     * API: Create an industry
     * POST /api/industries
     */
    public function apiStore(): void
    {
        $this->requireApiRole('admin');

        $data = $this->getJsonInput();

        if (empty($data['name'])) {
            $this->validationError(['name' => 'Name is required']);
        }

        $created = $this->industryService->createIndustry($data);

        if ($created) {
            $this->success($created, 'Industry created', 201);
        } else {
            $errors = $this->industryService->getErrors();
            $this->validationError($errors, 'Failed to create industry');
        }
    }

    /**
     * API: Update an industry
     * PUT /api/industries/{id}
     */
    public function apiUpdate(int $id): void
    {
        $this->requireApiRole('admin');

        $data = $this->getJsonInput();

        $updated = $this->industryService->updateIndustry($id, $data);

        if ($updated) {
            $industry = $this->industryService->getIndustryById($id);
            $this->success($industry, 'Industry updated');
        } else {
            $errors = $this->industryService->getErrors();
            $this->validationError($errors, 'Failed to update industry');
        }
    }

    /**
     * API: Delete an industry
     * DELETE /api/industries/{id}
     */
    public function apiDelete(int $id): void
    {
        $this->requireApiRole('admin');

        $deleted = $this->industryService->deleteIndustry($id);

        if ($deleted) {
            $this->success(null, 'Industry deleted');
        } else {
            $errors = $this->industryService->getErrors();
            $this->error('Failed to delete industry', $errors, 422);
        }
    }

    /**
     * API: Bulk create industries
     * POST /api/industries/bulk
     */
    public function apiBulkStore(): void
    {
        $this->requireApiRole('admin');

        $data = $this->getJsonInput();
        $industries = $data['industries'] ?? [];

        if (!is_array($industries) || empty($industries)) {
            $this->validationError(['industries' => 'Invalid or empty industries array']);
        }

        $result = $this->industryService->bulkCreateIndustries($industries);

        $this->success($result, 'Bulk create completed');
    }

    /**
     * API: Export industries
     * GET /api/industries/export
     */
    public function apiExport(): void
    {
        $this->requireApiRole('admin');

        $export = $this->industryService->exportIndustries();

        $this->success($export);
    }

    /**
     * API: Import industries
     * POST /api/industries/import
     */
    public function apiImport(): void
    {
        $this->requireApiRole('admin');

        $data = $this->getJsonInput();
        $industries = $data['industries'] ?? [];
        $overwrite = (bool)($data['overwrite'] ?? false);

        if (!is_array($industries) || empty($industries)) {
            $this->validationError(['industries' => 'Invalid or empty industries array']);
        }

        $result = $this->industryService->importIndustries($industries, $overwrite);

        $this->success($result, 'Import completed');
    }
}
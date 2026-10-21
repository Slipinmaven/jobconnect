<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\JobCategoryService;
use App\Helpers\ApiResponse;
use App\Helpers\ErrorHandler;

/**
 * JobCategoryController - Handles all job category related requests
 * 
 * PURPOSE: Manages HTTP requests for job categories
 * 
 * ACCESS CONTROL:
 * - View: All authenticated users + public for active categories
 * - Create/Update/Delete: Admin only
 */
class JobCategoryController extends BaseController
{
    private JobCategoryService $categoryService;
    
    public function __construct(JobCategoryService $categoryService)
    {
        parent::__construct();
        $this->categoryService = $categoryService;
    }
    
    // ==================== PUBLIC VIEWS ====================
    
    /**
     * Display list of categories (web view)
     * Access: Public
     */
    public function index(): void
    {
        // Get categories with job counts for display
        $categories = $this->categoryService->getCategoriesWithJobCounts(true);
        
        $this->view('categories/index', [
            'categories' => $categories,
            'title' => 'Job Categories'
        ]);
    }
    
    /**
     * Display category tree (web view)
     * Access: Public
     */
    public function tree(): void
    {
        $categoryTree = $this->categoryService->getCategoryTree(true);
        
        $this->view('categories/tree', [
            'categoryTree' => $categoryTree,
            'title' => 'Browse Jobs by Category'
        ]);
    }
    
    /**
     * Display single category with its jobs (web view)
     * Access: Public
     */
    public function show(string $slug): void
    {
        $category = $this->categoryService->getCategoryBySlug($slug);
        
        if (!$category) {
            ErrorHandler::notFound('Category not found');
            return;
        }
        
        // Get category with breadcrumbs
        $categoryWithBreadcrumbs = $this->categoryService->getCategoryWithBreadcrumbs($category['id']);
        
        // Get child categories
        $childCategories = $this->categoryService->getChildCategories($category['id'], true);
        
        // Get jobs in this category (you'll need a job service for this)
        // $jobs = $this->jobService->getJobsByCategory($category['id']);
        
        $this->view('categories/show', [
            'category' => $categoryWithBreadcrumbs,
            'childCategories' => $childCategories,
            'title' => $category['name'] . ' Jobs'
        ]);
    }
    
    // ==================== API ENDPOINTS (Public/Protected) ====================
    
    /**
     * API: Get all categories
     * Access: Public
     */
    public function apiIndex(): void
    {
        $onlyActive = $this->get('active', 'true') === 'true';
        $includeStats = $this->get('stats', 'false') === 'true';
        
        $data = $this->categoryService->exportCategories($onlyActive, $includeStats);
        
        ApiResponse::success($data);
    }
    
    /**
     * API: Get category tree
     * Access: Public
     */
    public function apiTree(): void
    {
        $onlyActive = $this->get('active', 'true') === 'true';
        $tree = $this->categoryService->getCategoryTree($onlyActive);
        
        ApiResponse::success($tree, 'Category tree retrieved successfully');
    }
    
    /**
     * API: Get single category by ID or slug
     * Access: Public
     */
    public function apiShow(string $identifier): void
    {
        // Check if identifier is numeric (ID) or string (slug)
        $category = is_numeric($identifier) 
            ? $this->categoryService->getCategory((int)$identifier)
            : $this->categoryService->getCategoryBySlug($identifier);
        
        if (!$category) {
            ApiResponse::notFound('Category not found');
            return;
        }
        
        $includeStats = $this->get('stats', 'false') === 'true';
        
        if ($includeStats) {
            $stats = $this->categoryService->getCategoryStats($category['id']);
            $response = $stats;
        } else {
            $response = ['category' => $category];
        }
        
        ApiResponse::success($response);
    }
    
    /**
     * API: Search categories
     * Access: Public
     */
    public function apiSearch(): void
    {
        $query = $this->get('q', '');
        $onlyActive = $this->get('active', 'true') === 'true';
        $limit = (int)$this->get('limit', 20);
        
        if (strlen($query) < 2) {
            ApiResponse::validationError(['q' => 'Search term must be at least 2 characters']);
            return;
        }
        
        $results = $this->categoryService->searchCategories($query, $onlyActive, $limit);
        
        ApiResponse::success([
            'query' => $query,
            'total' => count($results),
            'results' => $results
        ]);
    }
    
    // ==================== ADMIN ONLY (Web Views) ====================
    
    /**
     * Admin: Category management dashboard
     * Access: Admin only
     */
    public function adminIndex(): void
    {
        $this->requireRole('admin');
        
        $categories = $this->categoryService->getAllCategories(false);
        $categoryTree = $this->categoryService->getCategoryTree(false);
        
        $this->view('admin/categories/index', [
            'categories' => $categories,
            'categoryTree' => $categoryTree,
            'title' => 'Manage Categories'
        ]);
    }
    
    /**
     * Admin: Create category form
     * Access: Admin only
     */
    public function create(): void
    {
        $this->requireRole('admin');
        
        $parentCategories = $this->categoryService->getAllCategories(false);
        
        $this->view('admin/categories/create', [
            'parentCategories' => $parentCategories,
            'title' => 'Create New Category'
        ]);
    }
    
    /**
     * Admin: Store new category
     * Access: Admin only
     */
    public function store(): void
    {
        $this->requireRole('admin');
        
        // Verify CSRF token
        $token = $this->post('_token');
        if (!$this->verifyCsrf($token)) {
            $this->withFlash('error', 'Invalid security token')
                 ->withInput()
                 ->back();
            return;
        }
        
        $data = [
            'name' => $this->post('name'),
            'slug' => $this->post('slug'),
            'parent_id' => $this->post('parent_id') ?: null,
            'description' => $this->post('description'),
            'is_active' => $this->post('is_active') ? 1 : 0,
            'sort_order' => (int)$this->post('sort_order', 0)
        ];
        
        $categoryId = $this->categoryService->createCategory($data);
        
        if ($categoryId) {
            $this->withFlash('success', 'Category created successfully')
                 ->redirect('/admin/categories');
        } else {
            $errors = $this->categoryService->getErrors();
            $this->withFlash('error', 'Failed to create category')
                 ->withErrors($errors)
                 ->withInput()
                 ->back();
        }
    }
    
    /**
     * Admin: Edit category form
     * Access: Admin only
     */
    public function edit(int $id): void
    {
        $this->requireRole('admin');
        
        $category = $this->categoryService->getCategory($id);
        
        if (!$category) {
            $this->withFlash('error', 'Category not found')
                 ->redirect('/admin/categories');
            return;
        }
        
        $parentCategories = $this->categoryService->getAllCategories(false);
        
        $this->view('admin/categories/edit', [
            'category' => $category,
            'parentCategories' => $parentCategories,
            'title' => 'Edit Category'
        ]);
    }
    
    /**
     * Admin: Update category
     * Access: Admin only
     */
    public function update(int $id): void
    {
        $this->requireRole('admin');
        
        // Verify CSRF token
        $token = $this->post('_token');
        if (!$this->verifyCsrf($token)) {
            $this->withFlash('error', 'Invalid security token')
                 ->withInput()
                 ->back();
            return;
        }
        
        $data = [
            'name' => $this->post('name'),
            'slug' => $this->post('slug'),
            'parent_id' => $this->post('parent_id') ?: null,
            'description' => $this->post('description'),
            'is_active' => $this->post('is_active') ? 1 : 0,
            'sort_order' => (int)$this->post('sort_order', 0)
        ];
        
        $result = $this->categoryService->updateCategory($id, $data);
        
        if ($result) {
            $this->withFlash('success', 'Category updated successfully')
                 ->redirect('/admin/categories');
        } else {
            $errors = $this->categoryService->getErrors();
            $this->withFlash('error', 'Failed to update category')
                 ->withErrors($errors)
                 ->withInput()
                 ->back();
        }
    }
    
    /**
     * Admin: Delete category
     * Access: Admin only
     */
    public function delete(int $id): void
    {
        $this->requireRole('admin');
        
        // Verify CSRF token
        $token = $this->post('_token');
        if (!$this->verifyCsrf($token)) {
            $this->withFlash('error', 'Invalid security token')->back();
            return;
        }
        
        $force = $this->post('force') === 'true';
        
        try {
            $result = $this->categoryService->deleteCategory($id, $force);
            
            if ($result) {
                $this->withFlash('success', 'Category deleted successfully');
            } else {
                $errors = $this->categoryService->getErrors();
                $errorMessage = $errors['id'] ?? 'Failed to delete category';
                $this->withFlash('error', $errorMessage);
            }
        } catch (\Exception $e) {
            $this->withFlash('error', 'An error occurred while deleting the category');
        }
        
        $this->back();
    }
    
    // ==================== ADMIN ONLY (API Endpoints) ====================
    
    /**
     * API Admin: Create category
     * Access: Admin only
     */
    public function apiStore(): void
    {
        $this->requireApiRole('admin');
        
        $data = $this->getJsonInput();
        
        // Validate required fields
        if (empty($data['name']) || empty($data['slug'])) {
            ApiResponse::validationError(['name' => 'Name and slug are required']);
            return;
        }
        
        $categoryId = $this->categoryService->createCategory($data);
        
        if ($categoryId) {
            $category = $this->categoryService->getCategory($categoryId);
            ApiResponse::success($category, 'Category created successfully', 201);
        } else {
            $errors = $this->categoryService->getErrors();
            ApiResponse::validationError($errors, 'Failed to create category');
        }
    }
    
    /**
     * API Admin: Update category
     * Access: Admin only
     */
    public function apiUpdate(int $id): void
    {
        $this->requireApiRole('admin');
        
        $data = $this->getJsonInput();
        
        $result = $this->categoryService->updateCategory($id, $data);
        
        if ($result) {
            $category = $this->categoryService->getCategory($id);
            ApiResponse::success($category, 'Category updated successfully');
        } else {
            $errors = $this->categoryService->getErrors();
            ApiResponse::validationError($errors, 'Failed to update category');
        }
    }
    
    /**
     * API Admin: Delete category
     * Access: Admin only
     */
    public function apiDelete(int $id): void
    {
        $this->requireApiRole('admin');
        
        $force = $this->get('force', 'false') === 'true';
        
        try {
            $result = $this->categoryService->deleteCategory($id, $force);
            
            if ($result) {
                ApiResponse::success(null, 'Category deleted successfully');
            } else {
                $errors = $this->categoryService->getErrors();
                $errorMessage = $errors['id'] ?? 'Failed to delete category';
                ApiResponse::error($errorMessage, $errors, 400);
            }
        } catch (\Exception $e) {
            ApiResponse::serverError('An error occurred while deleting the category');
        }
    }
    
    /**
     * API Admin: Bulk update sort orders
     * Access: Admin only
     */
    public function apiUpdateSortOrders(): void
    {
        $this->requireApiRole('admin');
        
        $data = $this->getJsonInput();
        
        if (!isset($data['orders']) || !is_array($data['orders'])) {
            ApiResponse::validationError(['orders' => 'Orders array is required']);
            return;
        }
        
        $result = $this->categoryService->updateSortOrders($data['orders']);
        
        if ($result) {
            ApiResponse::success(null, 'Sort orders updated successfully');
        } else {
            $errors = $this->categoryService->getErrors();
            ApiResponse::error('Failed to update sort orders', $errors, 400);
        }
    }
    
    /**
     * API Admin: Bulk toggle category status
     * Access: Admin only
     */
    public function apiBulkToggle(): void
    {
        $this->requireApiRole('admin');
        
        $data = $this->getJsonInput();
        
        if (!isset($data['ids']) || !is_array($data['ids'])) {
            ApiResponse::validationError(['ids' => 'Category IDs array is required']);
            return;
        }
        
        $results = [];
        foreach ($data['ids'] as $id) {
            $result = $this->categoryService->toggleActive((int)$id);
            $results[$id] = $result;
        }
        
        ApiResponse::success([
            'processed' => count($data['ids']),
            'results' => $results
        ], 'Categories updated successfully');
    }
    
    /**
     * API Admin: Get category statistics
     * Access: Admin only
     */
    public function apiStats(): void
    {
        $this->requireApiRole('admin');
        
        $categories = $this->categoryService->getCategoriesWithJobCounts(false);
        
        $stats = [
            'total_categories' => count($categories),
            'active_categories' => count(array_filter($categories, fn($c) => $c['is_active'] == 1)),
            'inactive_categories' => count(array_filter($categories, fn($c) => $c['is_active'] == 0)),
            'root_categories' => count($this->categoryService->getRootCategories(false)),
            'categories_with_jobs' => count(array_filter($categories, fn($c) => ($c['job_count'] ?? 0) > 0)),
            'total_jobs_assigned' => array_sum(array_column($categories, 'job_count')),
            'categories' => $categories
        ];
        
        ApiResponse::success($stats);
    }
}
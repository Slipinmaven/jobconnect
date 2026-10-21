<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\AdminService;
use App\Helpers\ApiResponse;

/**
 * AdminController - Handles all admin operations
 * 
 * Manages:
 * - User blocking/unblocking
 * - Skills CRUD
 * - Schools CRUD
 * - Admin dashboard
 */
class AdminController extends BaseController
{
    private AdminService $adminService;

    public function __construct(AdminService $adminService)
    {
        parent::__construct();
        $this->adminService = $adminService;
    }

    // ==================== DASHBOARD ====================

    /**
     * Admin dashboard
     * GET /admin/dashboard
     */
    public function showDashboard(): void
    {
        $this->requireRole('admin');

        $stats = $this->adminService->getDashboardStats();

        $this->view('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'stats' => $stats
        ]);
    }

    // ==================== USER MANAGEMENT ====================

    /**
     * List users
     * GET /admin/users
     */
    public function showUsers(): void
    {
        $this->requireRole('admin');

        $page = (int)$this->get('page', 1);
        $filters = [
            'role' => $this->get('role'),
            'status' => $this->get('status'),
            'search' => $this->get('search'),
            'verified' => $this->get('verified')
        ];

        $result = $this->adminService->getAllUsers($page, 20, $filters);

        $this->view('admin/users/index', [
            'title' => 'Manage Users',
            'users' => $result['users'],
            'pagination' => [
                'page' => $result['page'],
                'total' => $result['total'],
                'pages' => $result['pages']
            ],
            'filters' => $filters
        ]);
    }

    /**
     * API: Get users list
     * GET /api/admin/users
     */
    public function apiGetUsers(): void
    {
        $this->requireApiRole('admin');

        $page = (int)$this->get('page', 1);
        $filters = [
            'role' => $this->get('role'),
            'status' => $this->get('status'),
            'search' => $this->get('search'),
            'verified' => $this->get('verified')
        ];

        $result = $this->adminService->getAllUsers($page, 20, $filters);

        ApiResponse::success($result, 'Users retrieved successfully');
    }

    /**
     * API: Block user until datetime
     * POST /api/admin/users/{id}/block-until
     */
    public function apiBlockUserUntil(int $id): void
    {
        $this->requireApiRole('admin');

        $data = $this->getJsonInput();

        if (empty($data['blocked_until'])) {
            ApiResponse::validationError(['blocked_until' => 'Blocked until datetime is required']);
            return;
        }

        $result = $this->adminService->blockUserUntil($id, $data['blocked_until'], $data['reason'] ?? '');

        if ($result) {
            ApiResponse::success(null, 'User blocked successfully');
        } else {
            $errors = $this->adminService->getErrors();
            ApiResponse::error('Failed to block user', $errors, 422);
        }
    }

    /**
     * API: Unblock user
     * POST /api/admin/users/{id}/unblock
     */
    public function apiUnblockUser(int $id): void
    {
        $this->requireApiRole('admin');

        $result = $this->adminService->unblockUser($id);

        if ($result) {
            ApiResponse::success(null, 'User unblocked successfully');
        } else {
            $errors = $this->adminService->getErrors();
            ApiResponse::error('Failed to unblock user', $errors, 422);
        }
    }

    /**
     * API: Bulk block users
     * POST /api/admin/users/bulk-block
     */
    public function apiBulkBlockUsers(): void
    {
        $this->requireApiRole('admin');

        $data = $this->getJsonInput();

        if (empty($data['user_ids']) || !is_array($data['user_ids'])) {
            ApiResponse::validationError(['user_ids' => 'User IDs array is required']);
            return;
        }

        if (empty($data['blocked_until'])) {
            ApiResponse::validationError(['blocked_until' => 'Blocked until datetime is required']);
            return;
        }

        $count = $this->adminService->bulkBlockUsers($data['user_ids'], $data['blocked_until']);

        ApiResponse::success(['blocked' => $count], "Blocked {$count} users successfully");
    }

    /**
     * API: Bulk unblock users
     * POST /api/admin/users/bulk-unblock
     */
    public function apiBulkUnblockUsers(): void
    {
        $this->requireApiRole('admin');

        $data = $this->getJsonInput();

        if (empty($data['user_ids']) || !is_array($data['user_ids'])) {
            ApiResponse::validationError(['user_ids' => 'User IDs array is required']);
            return;
        }

        $count = $this->adminService->bulkUnblockUsers($data['user_ids']);

        ApiResponse::success(['unblocked' => $count], "Unblocked {$count} users successfully");
    }

    // ==================== SKILLS MANAGEMENT ====================

    /**
     * List skills
     * GET /admin/skills
     */
    public function showSkills(): void
    {
        $this->requireRole('admin');

        $page = (int)$this->get('page', 1);
        $filters = [
            'category_id' => $this->get('category_id'),
            'search' => $this->get('search')
        ];

        $result = $this->adminService->getAllSkills($page, 20, $filters);
        $categories = $this->adminService->getSkillCategories();

        $this->view('admin/skills/index', [
            'title' => 'Manage Skills',
            'skills' => $result['skills'],
            'categories' => $categories,
            'pagination' => [
                'page' => $result['page'],
                'total' => $result['total'],
                'pages' => $result['pages']
            ],
            'filters' => $filters
        ]);
    }

    /**
     * Show create skill form
     * GET /admin/skills/create
     */
    public function showCreateSkill(): void
    {
        $this->requireRole('admin');

        $categories = $this->adminService->getSkillCategories();

        $this->view('admin/skills/create', [
            'title' => 'Create Skill',
            'categories' => $categories
        ]);
    }

    /**
     * Show edit skill form
     * GET /admin/skills/{id}/edit
     */
    public function showEditSkill(int $id): void
    {
        $this->requireRole('admin');

        // You'll need to add a getSkill method to AdminService
        $categories = $this->adminService->getSkillCategories();

        $this->view('admin/skills/edit', [
            'title' => 'Edit Skill',
            'skill_id' => $id,
            'categories' => $categories
        ]);
    }

    /**
     * API: Get all skills
     * GET /api/admin/skills
     */
    public function apiGetSkills(): void
    {
        $this->requireApiRole('admin');

        $page = (int)$this->get('page', 1);
        $filters = [
            'category_id' => $this->get('category_id'),
            'search' => $this->get('search')
        ];

        $result = $this->adminService->getAllSkills($page, 20, $filters);

        ApiResponse::success($result, 'Skills retrieved successfully');
    }

    /**
     * API: Create skill
     * POST /api/admin/skills
     */
    public function apiCreateSkill(): void
    {
        $this->requireApiRole('admin');

        $data = $this->getJsonInput();

        if (empty($data['name'])) {
            ApiResponse::validationError(['name' => 'Skill name is required']);
            return;
        }

        $skillId = $this->adminService->createSkill($data);

        if ($skillId) {
            ApiResponse::success(['id' => $skillId], 'Skill created successfully', 201);
        } else {
            $errors = $this->adminService->getErrors();
            ApiResponse::error('Failed to create skill', $errors, 422);
        }
    }

    /**
     * API: Update skill
     * PUT /api/admin/skills/{id}
     */
    public function apiUpdateSkill(int $id): void
    {
        $this->requireApiRole('admin');

        $data = $this->getJsonInput();

        $result = $this->adminService->updateSkill($id, $data);

        if ($result) {
            ApiResponse::success(null, 'Skill updated successfully');
        } else {
            $errors = $this->adminService->getErrors();
            ApiResponse::error('Failed to update skill', $errors, 422);
        }
    }

    /**
     * API: Delete skill
     * DELETE /api/admin/skills/{id}
     */
    public function apiDeleteSkill(int $id): void
    {
        $this->requireApiRole('admin');

        $result = $this->adminService->deleteSkill($id);

        if ($result) {
            ApiResponse::success(null, 'Skill deleted successfully');
        } else {
            $errors = $this->adminService->getErrors();
            ApiResponse::error('Failed to delete skill', $errors, 422);
        }
    }

    // ==================== SCHOOLS MANAGEMENT ====================

    /**
     * List schools
     * GET /admin/schools
     */
    public function showSchools(): void
    {
        $this->requireRole('admin');

        $page = (int)$this->get('page', 1);
        $filters = [
            'type' => $this->get('type'),
            'country' => $this->get('country'),
            'search' => $this->get('search')
        ];

        $result = $this->adminService->getAllSchools($page, 20, $filters);

        $this->view('admin/schools/index', [
            'title' => 'Manage Schools',
            'schools' => $result['schools'],
            'pagination' => [
                'page' => $result['page'],
                'total' => $result['total'],
                'pages' => $result['pages']
            ],
            'filters' => $filters
        ]);
    }

    /**
     * Show create school form
     * GET /admin/schools/create
     */
    public function showCreateSchool(): void
    {
        $this->requireRole('admin');

        $this->view('admin/schools/create', [
            'title' => 'Create School'
        ]);
    }

    /**
     * Show edit school form
     * GET /admin/schools/{id}/edit
     */
    public function showEditSchool(int $id): void
    {
        $this->requireRole('admin');

        $this->view('admin/schools/edit', [
            'title' => 'Edit School',
            'school_id' => $id
        ]);
    }

    /**
     * API: Get all schools
     * GET /api/admin/schools
     */
    public function apiGetSchools(): void
    {
        $this->requireApiRole('admin');

        $page = (int)$this->get('page', 1);
        $filters = [
            'type' => $this->get('type'),
            'country' => $this->get('country'),
            'search' => $this->get('search')
        ];

        $result = $this->adminService->getAllSchools($page, 20, $filters);

        ApiResponse::success($result, 'Schools retrieved successfully');
    }

    /**
     * API: Create school
     * POST /api/admin/schools
     */
    public function apiCreateSchool(): void
    {
        $this->requireApiRole('admin');

        $data = $this->getJsonInput();

        if (empty($data['name'])) {
            ApiResponse::validationError(['name' => 'School name is required']);
            return;
        }

        $schoolId = $this->adminService->createSchool($data);

        if ($schoolId) {
            ApiResponse::success(['id' => $schoolId], 'School created successfully', 201);
        } else {
            $errors = $this->adminService->getErrors();
            ApiResponse::error('Failed to create school', $errors, 422);
        }
    }

    /**
     * API: Update school
     * PUT /api/admin/schools/{id}
     */
    public function apiUpdateSchool(int $id): void
    {
        $this->requireApiRole('admin');

        $data = $this->getJsonInput();

        $result = $this->adminService->updateSchool($id, $data);

        if ($result) {
            ApiResponse::success(null, 'School updated successfully');
        } else {
            $errors = $this->adminService->getErrors();
            ApiResponse::error('Failed to update school', $errors, 422);
        }
    }

    /**
     * API: Delete school
     * DELETE /api/admin/schools/{id}
     */
    public function apiDeleteSchool(int $id): void
    {
        $this->requireApiRole('admin');

        $result = $this->adminService->deleteSchool($id);

        if ($result) {
            ApiResponse::success(null, 'School deleted successfully');
        } else {
            $errors = $this->adminService->getErrors();
            ApiResponse::error('Failed to delete school', $errors, 422);
        }
    }

    // ==================== DASHBOARD API ====================

    /**
     * API: Get dashboard statistics
     * GET /api/admin/dashboard/stats
     */
    public function apiGetDashboardStats(): void
    {
        $this->requireApiRole('admin');

        $stats = $this->adminService->getDashboardStats();

        ApiResponse::success($stats, 'Dashboard statistics retrieved successfully');
    }
}

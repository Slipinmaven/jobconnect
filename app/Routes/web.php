<?php
use App\Controllers\EmployerController;
use App\Controllers\JobSeekerController;
use App\Controllers\JobController;
use App\Controllers\JobCategoryController;
use App\Controllers\ApplicationController;
use App\Controllers\CommentController;
use App\Controllers\SavedJobController;
use App\Controllers\LikeController; 
use FastRoute\RouteCollector;
use App\Controllers\AuthController;
use App\Controllers\NotificationController;
use App\Controllers\IndustryController;
use App\Controllers\InterviewController;
use App\Controllers\AdminController;

return function (RouteCollector $r) {
    // Register page
    $r->get('/register', [AuthController::class, 'showRegister']);
    $r->post('/api/register', [AuthController::class, 'register']);

    //Email Verification Page
    $r->get('/verify', [AuthController::class, 'showVerify']);
    $r->post('/api/verify-email', [AuthController::class, 'verifyEmail']);
    $r->get('/reverify', [AuthController::class, 'showReverify']);
    $r->post('/api/reverifyEmail', [AuthController::class, 'reverifyEmail']);

    //Password Reset Page
    $r->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
    $r->post('/api/forgot-password', [AuthController::class, 'forgotPassword']);
    $r->post('/api/verify-reset-token', [AuthController::class, 'verifyResetToken']);
    $r->post('/api/reset-password', [AuthController::class, 'resetPassword']);

    //Login Page
    $r->get('/login', [AuthController::class, 'showLogin']);
    $r->post('/api/login', [AuthController::class, 'login']);

    //Back 
    $r->get('/back', [AuthController::class, 'goBack']);
    $r->get('/api/jobseeker/info', ['JobSeekerController', 'getUserInfo']);

    // Google OAuth
    $r->get('/auth/google', [AuthController::class, 'redirectToGoogle']);
    $r->get('/auth/callback', [AuthController::class, 'googleCallback']);

    //Profile setup
    $r->get('/employer/profile-setup', [AuthController::class,'showEmployerProfileSetup']);
    $r->post('/api/employer/profile-setup', [EmployerController::class,'setupProfile']);
    $r->post('/api/employer/update-profile', [EmployerController::class,'updateProfile']);
    $r->get('/api/employer/settings', [EmployerController::class,'getSettings']);
    $r->get('/api/employer/info', [EmployerController::class,'getEmployerInfo']);
    $r->post('/api/employer/save-theme', [EmployerController::class,'saveTheme']);
    $r->get('/jobseeker/profile-setup', [AuthController::class,'showJobseekerProfileSetup']);
    $r->post('/api/jobseeker/profile-setup', [JobSeekerController::class,'saveProfileFromWizard']);
    $r->post('/api/jobseeker/upload-profile-photo', [JobSeekerController::class,'uploadProfilePhoto']);
    $r->post('/api/jobseeker/upload-cv', [JobSeekerController::class,'uploadCV']);
    $r->get('/api/jobseeker/download-cv', [JobSeekerController::class,'downloadCV']);
    $r->post('/api/jobseeker/delete-cv', [JobSeekerController::class,'deleteCV']);
    $r->post('/api/jobseeker/save-theme', [JobSeekerController::class,'saveTheme']);
    $r->post('/api/jobseeker/update-profile', [JobSeekerController::class,'updateProfile']);
    $r->post('/api/jobseeker/update-preferences', [JobSeekerController::class,'updatePreferences']);
    $r->post('/api/jobseeker/update-career', [JobSeekerController::class,'updateCareer']);
    $r->get('/api/jobseeker/profile', [JobSeekerController::class,'getProfile']);
    $r->get('/api/jobseeker/settings', [JobSeekerController::class,'getSettings']);
    $r->get('/jobseeker/saved', [JobSeekerController::class,'showSavedJobs']);
    $r->get('/jobseeker/notifications', [JobSeekerController::class,'showNotifications']);

    //Dashboards
    $r->get('/employer/dashboard', [EmployerController::class,'showDashboard']);
    $r->get('/employer/settings', [EmployerController::class,'showSettings']);
    $r->get('/employer/notifications', [EmployerController::class, 'showNotifications']);
    $r->get('/jobseeker/dashboard', [JobSeekerController::class,'showDashboard']);
    $r->get('/jobseeker/settings', [JobSeekerController::class,'showSettings']);
    $r->get('/jobseeker/interviews', [JobSeekerController::class,'showInterviews']);

    $r->get('/api/schools', [AuthController::class, 'getSchools']);

    // ==================== JOB CATEGORY ROUTES ====================
    
    // Public category views (web)
    $r->get('/categories', [JobCategoryController::class, 'index']);
    $r->get('/categories/tree', [JobCategoryController::class, 'tree']);
    $r->get('/categories/{slug:[a-z0-9-]+}', [JobCategoryController::class, 'show']);
    
    // Public category API endpoints - KEEP THIS ONE
    $r->get('/api/categories', [JobCategoryController::class, 'apiIndex']);
    $r->get('/api/categories/tree', [JobCategoryController::class, 'apiTree']);
    $r->get('/api/categories/search', [JobCategoryController::class, 'apiSearch']);
    $r->get('/api/categories/{identifier:[a-z0-9-]+}', [JobCategoryController::class, 'apiShow']);
    
    // Admin category management (web views)
    $r->get('/admin/categories', [JobCategoryController::class, 'adminIndex']);
    $r->get('/admin/categories/create', [JobCategoryController::class, 'create']);
    $r->post('/admin/categories', [JobCategoryController::class, 'store']);
    $r->get('/admin/categories/{id:\d+}/edit', [JobCategoryController::class, 'edit']);
    $r->post('/admin/categories/{id:\d+}', [JobCategoryController::class, 'update']);
    $r->delete('/admin/categories/{id:\d+}', [JobCategoryController::class, 'delete']);

 // ==================== INDUSTRY ROUTES ====================

// Public industry views (web) – static routes first
$r->get('/industries/popular', [IndustryController::class, 'popular']);
$r->get('/industries/grouped', [IndustryController::class, 'grouped']);
$r->get('/industries/dropdown', [IndustryController::class, 'dropdown']); // AJAX

// Variable route for industry detail – must come after static routes
$r->get('/industries/{slug:[a-z0-9-]+}', [IndustryController::class, 'show']);

// Admin industry management (web)
$r->get('/admin/industries', [IndustryController::class, 'index']);
$r->get('/admin/industries/create', [IndustryController::class, 'create']);
$r->post('/admin/industries', [IndustryController::class, 'store']);
$r->get('/admin/industries/edit/{id:\d+}', [IndustryController::class, 'edit']);
$r->post('/admin/industries/update/{id:\d+}', [IndustryController::class, 'update']);
$r->post('/admin/industries/delete/{id:\d+}', [IndustryController::class, 'delete']);

// Industry API endpoints (all under /api/industries) – static routes first
$r->get('/api/industries', [IndustryController::class, 'apiIndex']);           // list
$r->post('/api/industries', [IndustryController::class, 'apiStore']);         // create
$r->get('/api/industries/popular', [IndustryController::class, 'popular']);    // popular (API)
$r->get('/api/industries/grouped', [IndustryController::class, 'grouped']);    // grouped (API)
$r->get('/api/industries/dropdown', [IndustryController::class, 'dropdown']);   // dropdown (API)
$r->post('/api/industries/bulk', [IndustryController::class, 'apiBulkStore']); // bulk create
$r->get('/api/industries/export', [IndustryController::class, 'apiExport']);   // export
$r->post('/api/industries/import', [IndustryController::class, 'apiImport']);   // import

// Variable routes for single industry – must come after static routes
$r->get('/api/industries/{id:\d+}', [IndustryController::class, 'apiShow']);  // single
$r->put('/api/industries/{id:\d+}', [IndustryController::class, 'apiUpdate']); // update
$r->delete('/api/industries/{id:\d+}', [IndustryController::class, 'apiDelete']); // delete
    
    // Admin category API endpoints
    $r->post('/api/admin/categories', [JobCategoryController::class, 'apiStore']);
    $r->put('/api/admin/categories/{id:\d+}', [JobCategoryController::class, 'apiUpdate']);
    $r->delete('/api/admin/categories/{id:\d+}', [JobCategoryController::class, 'apiDelete']);
    $r->put('/api/admin/categories/sort-orders', [JobCategoryController::class, 'apiUpdateSortOrders']);
    $r->post('/api/admin/categories/bulk-toggle', [JobCategoryController::class, 'apiBulkToggle']);
    $r->get('/api/admin/categories/stats', [JobCategoryController::class, 'apiStats']);

    // ==================== JOB ROUTES ====================
    
    // Public job API
    $r->get('/api/jobs', [JobController::class, 'getJobs']);
    $r->get('/api/jobs/featured', [JobController::class, 'getFeaturedJobs']);
    $r->get('/api/jobs/search', [JobController::class, 'searchJobs']);
    $r->get('/api/jobs/{slug:[a-z0-9-]+}', [JobController::class, 'getJob']);
    $r->get('/api/jobs/{id:\d+}/similar', [JobController::class, 'getSimilarJobs']);

    // ==================== SAVED JOBS ====================
$r->get('/api/user/saved-jobs', [SavedJobController::class, 'index']);
$r->post('/api/jobs/{jobId}/toggle-save', [SavedJobController::class, 'toggle']);
$r->get('/api/jobs/{jobId}/is-saved', [SavedJobController::class, 'check']);
    
    // ==================== APPLICATIONS ====================
$r->post('/api/jobs/{jobId}/apply', [ApplicationController::class, 'apply']);
$r->get('/api/applications/user', [ApplicationController::class, 'myApplications']);
$r->get('/api/jobs/{jobId}/applications', [ApplicationController::class, 'jobApplications']);
$r->get('/api/applications/{id:\d+}/resume', [ApplicationController::class, 'downloadApplicationResume']);
$r->put('/api/applications/{id}/status', [ApplicationController::class, 'updateStatus']);
$r->post('/api/applications/{id}/view', [ApplicationController::class, 'markViewed']);
$r->post('/api/applications/{id}/withdraw', [ApplicationController::class, 'withdraw']);
$r->get('/api/applications/stats', [ApplicationController::class, 'stats']);
$r->get('/api/employer/applications', [ApplicationController::class, 'employerApplications']);
    
    // Protected job API routes
    $r->post('/api/jobs', [JobController::class, 'createJob']);
    $r->put('/api/jobs/{id:\d+}', [JobController::class, 'updateJob']);
    $r->delete('/api/jobs/{id:\d+}', [JobController::class, 'deleteJob']);
    $r->post('/api/jobs/{id:\d+}/publish', [JobController::class, 'publishJob']);
    $r->post('/api/jobs/{id:\d+}/close', [JobController::class, 'closeJob']);
    
    // Employer job API
    $r->get('/api/employer/jobs', [JobController::class, 'getEmployerJobs']);
    $r->get('/api/employer/job/{id:\d+}', [JobController::class,'getEmployerJob']);
    $r->post('/api/employer/jobs/{id:\d+}/close', [JobController::class, 'closeJob']);
    $r->get('/api/employer/stats', [JobController::class, 'getEmployerStats']);
    
    // Employer job management (views)
    $r->get('/employer/jobs', [EmployerController::class,'showJobs']);
    $r->get('/employer/jobs/create', [EmployerController::class,'showCreateJob']);
    $r->post('/api/employer/jobs', [EmployerController::class,'createJob']);
    $r->get('/employer/jobs/edit/{id:\d+}', [EmployerController::class,'showEditJob']);
    $r->post('/api/employer/jobs/{id:\d+}', [EmployerController::class,'updateJob']);
    $r->get('/employer/jobs/view/{id:\d+}', [EmployerController::class,'showJobDetails']);
    $r->get('/api/jobseeker/interviews', [InterviewController::class,'jobseekerInterviews']);
    $r->get('/api/jobseeker/interviews/stats', [InterviewController::class,'jobseekerInterviewStats']);

    $r->get('/employer/applications', [EmployerController::class,'showApplications']);
    $r->get('/employer/interviews', [EmployerController::class,'showInterviews']);

    // ==================== JOBSEEKER ROUTES ==================== //
    $r->get('/jobseeker/jobs', [JobSeekerController::class,'showJobs']);
    $r->get('/jobseeker/applications', [JobSeekerController::class,'showApplications']);


    // ==================== SOCIAL ACTIONS ROUTES ==================== //
    // API routes
$r->post('/api/like', [LikeController::class, 'like']);
$r->post('/api/unlike', [LikeController::class, 'unlike']);
$r->post('/api/like/toggle', [LikeController::class, 'toggle']);

$r->get('/api/like/status', [LikeController::class, 'status']);
$r->get('/api/like/count', [LikeController::class, 'count']);
$r->get('/api/like/counts', [LikeController::class, 'counts']);
$r->get('/api/like/statuses', [LikeController::class, 'statuses']);
$r->get('/api/like/users', [LikeController::class, 'users']);
$r->get('/api/user/likes', [LikeController::class, 'userLikes']);

// Admin routes
$r->get('/api/admin/like/statistics', [LikeController::class, 'statistics']);
$r->post('/api/admin/like/cleanup', [LikeController::class, 'cleanup']);
$r->delete('/api/admin/like/entity', [LikeController::class, 'deleteEntityLikes']);
$r->delete('/api/admin/like/user', [LikeController::class, 'deleteUserLikes']);


// ==================== MAIN COMMENTS ====================
// Static routes first (before variable routes to avoid shadowing)
$r->get('/api/comments/search', [CommentController::class, 'search']);
$r->get('/api/comments/recent', [CommentController::class, 'recent']);
$r->get('/api/comments/mentions', [CommentController::class, 'mentions']);
$r->get('/api/comments/admin/stats', [CommentController::class, 'adminStats']);
$r->get('/api/comments/admin/moderation', [CommentController::class, 'moderationQueue']);

// Variable routes after static routes
$r->get('/api/comments/{type}/{id}', [CommentController::class, 'index']);
$r->post('/api/comments/{type}/{id}', [CommentController::class, 'store']);
$r->put('/api/comments/{id}', [CommentController::class, 'update']);
$r->delete('/api/comments/{id}', [CommentController::class, 'destroy']);

// ==================== LIKES ====================
$r->post('/api/comments/{id}/like', [CommentController::class, 'toggleLike']);

// ==================== REPLIES ====================
$r->get('/api/comments/{id}/replies', [CommentController::class, 'loadReplies']);

// ==================== USER COMMENTS ====================
$r->get('/api/comments/user/{userId}', [CommentController::class, 'userComments']);

// ==================== MODERATION ====================
$r->post('/api/comments/{id}/pin', [CommentController::class, 'pin']);
$r->post('/api/comments/{id}/unpin', [CommentController::class, 'unpin']);
$r->post('/api/comments/{id}/hide', [CommentController::class, 'hide']);
$r->post('/api/comments/{id}/unhide', [CommentController::class, 'unhide']);

// ==================== STATISTICS ====================
$r->get('/api/comments/stats/{type}/{id}', [CommentController::class, 'stats']);
$r->get('/api/comments/top-commenters/{type}/{id}', [CommentController::class, 'topCommenters']);


// ==================== NOTIFICATIONS ====================
$r->get('/api/notifications', [NotificationController::class, 'index']);
$r->get('/api/notifications/unread-count', [NotificationController::class, 'unreadCount']);
$r->post('/api/notifications/{id}/read', [NotificationController::class, 'markRead']);
$r->post('/api/notifications/read-all', [NotificationController::class, 'markAllRead']);
$r->delete('/api/notifications/{id}', [NotificationController::class, 'destroy']);

// ==================== INTERVIEWS ====================
$r->get('/api/interviews/stats', [InterviewController::class, 'stats']);
$r->get('/api/interviews', [InterviewController::class, 'index']);
$r->get('/api/interviews/{id}', [InterviewController::class, 'show']);
$r->put('/api/interviews/{id}', [InterviewController::class, 'reschedule']);
$r->patch('/api/interviews/{id}/cancel', [InterviewController::class, 'cancel']);
$r->patch('/api/interviews/{id}/complete', [InterviewController::class, 'complete']);
$r->get('/api/applications/{applicationId}/interviews', [InterviewController::class, 'listByApplication']);
$r->post('/api/applications/{applicationId}/interviews', [InterviewController::class, 'schedule']);

    // ==================== ADMIN ROUTES ====================
    
    // Admin dashboard & views (web pages)
    $r->get('/admin/dashboard', [AdminController::class, 'showDashboard']);
    $r->get('/admin/users', [AdminController::class, 'showUsers']);
    $r->get('/admin/skills', [AdminController::class, 'showSkills']);
    $r->get('/admin/skills/create', [AdminController::class, 'showCreateSkill']);
    $r->get('/admin/skills/{id:\d+}/edit', [AdminController::class, 'showEditSkill']);
    $r->get('/admin/schools', [AdminController::class, 'showSchools']);
    $r->get('/admin/schools/create', [AdminController::class, 'showCreateSchool']);
    $r->get('/admin/schools/{id:\d+}/edit', [AdminController::class, 'showEditSchool']);
    
    // Admin API endpoints - Users
    $r->get('/api/admin/users', [AdminController::class, 'apiGetUsers']);
    $r->post('/api/admin/users/{id:\d+}/block-until', [AdminController::class, 'apiBlockUserUntil']);
    $r->post('/api/admin/users/{id:\d+}/unblock', [AdminController::class, 'apiUnblockUser']);
    $r->post('/api/admin/users/bulk-block', [AdminController::class, 'apiBulkBlockUsers']);
    $r->post('/api/admin/users/bulk-unblock', [AdminController::class, 'apiBulkUnblockUsers']);
    
    // Admin API endpoints - Skills
    $r->get('/api/admin/skills', [AdminController::class, 'apiGetSkills']);
    $r->post('/api/admin/skills', [AdminController::class, 'apiCreateSkill']);
    $r->put('/api/admin/skills/{id:\d+}', [AdminController::class, 'apiUpdateSkill']);
    $r->delete('/api/admin/skills/{id:\d+}', [AdminController::class, 'apiDeleteSkill']);
    
    // Admin API endpoints - Schools
    $r->get('/api/admin/schools', [AdminController::class, 'apiGetSchools']);
    $r->post('/api/admin/schools', [AdminController::class, 'apiCreateSchool']);
    $r->put('/api/admin/schools/{id:\d+}', [AdminController::class, 'apiUpdateSchool']);
    $r->delete('/api/admin/schools/{id:\d+}', [AdminController::class, 'apiDeleteSchool']);
    
    // Admin API endpoints - Dashboard
    $r->get('/api/admin/dashboard/stats', [AdminController::class, 'apiGetDashboardStats']);

    // Logout
    $r->post('/api/logout', [AuthController::class, 'logout']);
    $r->get('/logout', [AuthController::class, 'logout']);
    $r->get('/', [AuthController::class, 'showLandingPage']);
};
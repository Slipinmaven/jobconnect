<div class="jobs-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="header-left">
            <div class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </div>
            <div class="page-title">
                <h1>Job Postings</h1>
                <p>Manage your job listings, track performance, and take action</p>
            </div>
        </div>
        <button class="post-job-btn" id="postJobBtn" onclick="createNewJob()">
            <i class="fas fa-plus"></i> Post New Job
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid" id="statsGrid">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5;">
                <i class="fas fa-briefcase" style="color:#10b981;"></i>
            </div>
            <div class="stat-value" id="totalJobs"><?= number_format($jobStats['total_jobs'] ?? 0) ?></div>
            <div class="stat-label">Total Jobs</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff;">
                <i class="fas fa-eye" style="color:#3b82f6;"></i>
            </div>
            <div class="stat-value" id="totalViews"><?= number_format($jobStats['total_views'] ?? 0) ?></div>
            <div class="stat-label">Total Views</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef3c7;">
                <i class="fas fa-file-alt" style="color:#f59e0b;"></i>
            </div>
            <div class="stat-value" id="totalApps"><?= number_format($jobStats['total_applications'] ?? 0) ?></div>
            <div class="stat-label">Applications</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2;">
                <i class="fas fa-chart-line" style="color:#ef4444;"></i>
            </div>
            <div class="stat-value" id="applyRate">
                <?php
                $views = $jobStats['total_views'] ?? 0;
                $apps = $jobStats['total_applications'] ?? 0;
                $rate = $views > 0 ? round(($apps / $views) * 100) : 0;
                echo $rate . '%';
                ?>
            </div>
            <div class="stat-label">Apply Rate</div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <div class="filter-group">
            <span class="filter-chip active" data-status="all">All Jobs</span>
            <span class="filter-chip" data-status="published">Published</span>
            <span class="filter-chip" data-status="draft">Draft</span>
            <span class="filter-chip" data-status="closed">Closed</span>
        </div>
        <select class="sort-select" id="sortSelect">
            <option value="latest">Latest First</option>
            <option value="most-viewed">Most Viewed</option>
            <option value="most-applied">Most Applied</option>
            <option value="expiring-soon">Expiring Soon</option>
        </select>
    </div>

    <!-- Jobs Grid (Card Layout) -->
    <div class="jobs-grid" id="jobsGrid">
    </div>
</div>
<script>
// Pass PHP data to JavaScript
window.jobsData = <?= json_encode($jobs['data'] ?? []) ?>;
    window.jobStats = <?= json_encode($jobStats ?? []) ?>;
</script>
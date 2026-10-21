
<div class="saved-jobs-page">

    <!-- ═══════════════════ PAGE HEADER ═══════════════════ -->
    <div class="sj-header">
        <div class="sj-header-left">
            <div class="sj-header-icon">
                <i class="fa-solid fa-bookmark"></i>
            </div>
            <div>
                <h1 class="sj-title">Saved Jobs</h1>
                <p class="sj-subtitle">Jobs you've bookmarked for later — all in one place</p>
            </div>
        </div>
        <div class="sj-header-actions">
            <div class="sj-search-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    type="text"
                    id="sjSearchInput"
                    placeholder="Search saved jobs..."
                    autocomplete="off"
                />
            </div>
            <button class="btn btn-secondary btn-sm" id="sjClearAll" title="Remove all saved jobs">
                <i class="fa-solid fa-trash-can"></i>
                <span>Clear All</span>
            </button>
        </div>
    </div>

    <!-- ═══════════════════ STATS BAR ═══════════════════ -->
    <div class="sj-stats-bar" id="sjStatsBar">
        <div class="sj-stat">
            <i class="fa-solid fa-layer-group"></i>
            <span><strong id="sjTotalCount">—</strong> saved</span>
        </div>
        <div class="sj-stat">
            <i class="fa-solid fa-file-lines" style="color:var(--warning-color)"></i>
            <span><strong id="sjCvCount">—</strong> require a CV</span>
        </div>
        <div class="sj-stat">
            <i class="fa-solid fa-circle-check" style="color:var(--success-color)"></i>
            <span><strong id="sjOpenCount">—</strong> no CV needed</span>
        </div>
        <div class="sj-stat">
            <i class="fa-solid fa-clock-rotate-left" style="color:var(--danger-color)"></i>
            <span><strong id="sjExpiringCount">—</strong> closing soon</span>
        </div>
    </div>

    <!-- ═══════════════════ FILTER TABS ═══════════════════ -->
    <div class="sj-filters">
        <button class="sj-filter-btn active" data-filter="all">
            <i class="fa-solid fa-border-all"></i> All
        </button>
        <button class="sj-filter-btn" data-filter="no_cv">
            <i class="fa-solid fa-circle-check"></i> Apply Now
        </button>
        <button class="sj-filter-btn" data-filter="cv_required">
            <i class="fa-solid fa-file-lines"></i> CV Required
        </button>
        <button class="sj-filter-btn" data-filter="expiring">
            <i class="fa-solid fa-fire"></i> Closing Soon
        </button>
    </div>

    <!-- ═══════════════════ JOBS GRID ═══════════════════ -->
    <div class="sj-grid" id="sjGrid">
        <!-- Skeleton cards shown while loading -->
        <div class="sj-card glossy-placeholder">
            <div class="sj-card-top"><div class="sj-logo"></div><div class="sj-company-info"><span></span><span></span></div></div>
            <div class="sj-job-title"></div>
            <div class="sj-meta-row"></div>
            <div class="sj-desc"></div>
            <div class="sj-actions"></div>
        </div>
        <div class="sj-card glossy-placeholder">
            <div class="sj-card-top"><div class="sj-logo"></div><div class="sj-company-info"><span></span><span></span></div></div>
            <div class="sj-job-title"></div>
            <div class="sj-meta-row"></div>
            <div class="sj-desc"></div>
            <div class="sj-actions"></div>
        </div>
        <div class="sj-card glossy-placeholder">
            <div class="sj-card-top"><div class="sj-logo"></div><div class="sj-company-info"><span></span><span></span></div></div>
            <div class="sj-job-title"></div>
            <div class="sj-meta-row"></div>
            <div class="sj-desc"></div>
            <div class="sj-actions"></div>
        </div>
    </div>

    <!-- ═══════════════════ EMPTY STATE ═══════════════════ -->
    <div class="sj-empty" id="sjEmpty" style="display:none">
        <div class="sj-empty-icon">
            <i class="fa-regular fa-bookmark"></i>
        </div>
        <h2>No saved jobs yet</h2>
        <p>When you bookmark a job from the listings page, it'll show up here so you can come back to it whenever you're ready.</p>
        <a href="/jobseeker/jobs" class="btn btn-primary">
            <i class="fa-solid fa-briefcase"></i> Browse Jobs
        </a>
    </div>

    <!-- No results from filter/search -->
    <div class="sj-no-results" id="sjNoResults" style="display:none">
        <i class="fa-solid fa-filter-circle-xmark"></i>
        <p>No jobs match your current filter. Try a different one.</p>
        <button class="btn btn-secondary btn-sm" id="sjResetFilter">
            <i class="fa-solid fa-rotate-left"></i> Show all saved jobs
        </button>
    </div>

</div>

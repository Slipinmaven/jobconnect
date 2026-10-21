<div class="job-details-page">
    <?php
    $userRole = $_SESSION['user_role'] ?? 'guest';
    $isEmployer = $userRole === 'employer';
    $isJobseeker = $userRole === 'jobseeker';
    ?>

    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="header-left">
            <div class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </div>
            <div class="page-title">
                <h1>Job Details</h1>
                <p>View and manage your job posting</p>
            </div>
        </div>
        <div class="header-actions">
            <?php if ($isEmployer): ?>
            <button class="btn-secondary" onclick="editJob(<?= $job['id'] ?>)">
                <i class="fas fa-edit"></i> Edit Job
            </button>
            <?php endif; ?>
            <button class="btn-primary" onclick="window.history.back()">
                <i class="fas fa-arrow-left"></i> Back to Jobs
            </button>
        </div>
    </div>

    <!-- Job Details Card -->
    <div class="job-details-card">
        <div class="job-header">
            <div class="job-title-section">
                <h2 class="job-title"><?= htmlspecialchars($job['title']) ?></h2>
                <div class="job-meta">
                    <span class="job-type">
                        <i class="fas fa-briefcase"></i>
                        <?= ucfirst(str_replace('_', ' ', $job['job_type'] ?? 'full_time')) ?>
                    </span>
                    <span class="job-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php if ($job['is_remote'] ?? false): ?>
                            Remote
                        <?php elseif ($job['location'] ?? null): ?>
                            <?= htmlspecialchars($job['location']) ?>
                        <?php else: ?>
                            Location TBD
                        <?php endif; ?>
                    </span>
                    <span class="job-salary">
                        <i class="fas fa-dollar-sign"></i>
                        <?php
                        $min = $job['salary_min'] ?? null;
                        $max = $job['salary_max'] ?? null;
                        $currency = $job['salary_currency'] ?? 'USD';
                        if ($min && $max) {
                            echo htmlspecialchars($currency) . ' ' . number_format($min) . ' - ' . number_format($max);
                        } elseif ($min) {
                            echo htmlspecialchars($currency) . ' ' . number_format($min) . '+';
                        } elseif ($max) {
                            echo 'Up to ' . htmlspecialchars($currency) . ' ' . number_format($max);
                        } else {
                            echo 'Competitive';
                        }
                        ?>
                    </span>
                </div>
            </div>
            <div class="job-status">
                <span class="status-badge status-<?= $job['status'] ?>">
                    <?= ucfirst($job['status']) ?>
                </span>
            </div>
        </div>

        <!-- Job Stats -->
        <div class="job-stats">
            <div class="stat-item">
                <div class="stat-value"><?= number_format($job['likes_count'] ?? 0) ?></div>
                <div class="stat-label">Likes</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= number_format($job['comments_count'] ?? 0) ?></div>
                <div class="stat-label">Comments</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= number_format($job['saves_count'] ?? 0) ?></div>
                <div class="stat-label">Saved</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">
                    <?php
                    $applications = $job['applications_count'] ?? 0;
                    echo number_format($applications);
                    ?>
                </div>
                <div class="stat-label">Applications</div>
            </div>
        </div>

        <!-- Job Description -->
        <div class="job-section">
            <h3 class="section-title">
                <i class="fas fa-file-alt"></i> Job Description
            </h3>
            <div class="job-description">
                <?= nl2br(htmlspecialchars($job['description'])) ?>
            </div>
        </div>

        <!-- Requirements -->
        <?php if (!empty($job['requirements'])): ?>
        <div class="job-section">
            <h3 class="section-title">
                <i class="fas fa-check-circle"></i> Requirements
            </h3>
            <div class="job-requirements">
                <?php
                $reqs = $job['requirements'];
                if (is_array($reqs)) {
                    $items = array_filter($reqs, function($item) { return !empty(trim($item)); });
                } else {
                    $items = array_filter(array_map('trim', explode("\n", $reqs ?? "")), function($item) { return !empty($item); });
                }
                
                if (!empty($items)):
                ?>
                <ul class="requirements-list">
                    <?php foreach ($items as $req): ?>
                    <li><?= htmlspecialchars(trim($req)) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="text-muted">No specific requirements listed.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Responsibilities -->
        <?php if (!empty($job['responsibilities'])): ?>
        <div class="job-section">
            <h3 class="section-title">
                <i class="fas fa-tasks"></i> Responsibilities
            </h3>
            <div class="job-responsibilities">
                <?php
                $resp = $job['responsibilities'];
                if (is_array($resp)) {
                    $items = array_filter($resp, function($item) { return !empty(trim($item)); });
                } else {
                    $items = array_filter(array_map('trim', explode("\n", $resp ?? "")), function($item) { return !empty($item); });
                }
                
                if (!empty($items)):
                ?>
                <ul class="responsibilities-list">
                    <?php foreach ($items as $resp_item): ?>
                    <li><?= htmlspecialchars(trim($resp_item)) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="text-muted">No specific responsibilities listed.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Benefits -->
        <?php if (!empty($job['benefits'])): ?>
        <div class="job-section">
            <h3 class="section-title">
                <i class="fas fa-star"></i> Benefits
            </h3>
            <div class="job-benefits">
                <?= nl2br(htmlspecialchars($job['benefits'])) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Job Details Grid -->
        <div class="job-details-grid">
            <div class="detail-item">
                <label>Experience Level</label>
                <span><?= ucfirst(str_replace('_', ' ', $job['experience_level'] ?? 'entry')) ?></span>
            </div>
            <div class="detail-item">
                <label>Education Required</label>
                <span>
                    <?php
                    $education = $job['education_level'] ?? 'any';
                    echo ucfirst(str_replace('_', ' ', $education));
                    ?>
                </span>
            </div>
            <div class="detail-item">
                <label>Industry</label>
                <span>
                    <?php
                    $industry = $job['industry_name'] ?? 'Not specified';
                    echo htmlspecialchars($industry);
                    ?>
                </span>
            </div>
            <div class="detail-item">
                <label>Application Deadline</label>
                <span>
                    <?php
                    if ($job['application_deadline'] ?? null) {
                        echo date('M j, Y', strtotime($job['application_deadline']));
                    } else {
                        echo 'No deadline';
                    }
                    ?>
                </span>
            </div>
            <div class="detail-item">
                <label>Posted Date</label>
                <span><?= date('M j, Y', strtotime($job['created_at'])) ?></span>
            </div>
            <div class="detail-item">
                <label>Last Updated</label>
                <span>
                    <?php
                    if (($job['updated_at'] ?? null) && ($job['updated_at'] != ($job['created_at'] ?? null))) {
                        echo date('M j, Y', strtotime($job['updated_at']));
                    } else {
                        echo 'Never';
                    }
                    ?>
                </span>
            </div>
        </div>

        <!-- Skills -->
        <?php if (!empty($job['skills'])): ?>
        <div class="job-section">
            <h3 class="section-title">
                <i class="fas fa-tools"></i> Required Skills
            </h3>
            <div class="skills-list">
                <?php foreach ($job['skills'] as $skill): ?>
                <span class="skill-tag"><?= htmlspecialchars($skill['name']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <?php if ($isEmployer): ?>
        <div class="job-actions">
            <?php if ($job['status'] === 'draft'): ?>
                <button class="btn-success" onclick="publishJob(<?= $job['id'] ?>)">
                    <i class="fas fa-upload"></i> Publish Job
                </button>
            <?php elseif ($job['status'] === 'published'): ?>
                <button class="btn-warning" onclick="closeJob(<?= $job['id'] ?>)">
                    <i class="fas fa-times"></i> Close Job
                </button>
            <?php endif; ?>
            <button class="btn-secondary" onclick="duplicateJob(<?= $job['id'] ?>)">
                <i class="fas fa-copy"></i> Duplicate
            </button>
            <button class="btn-danger" onclick="deleteJob(<?= $job['id'] ?>)">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
        <?php endif; ?>

        <!-- Interaction Buttons -->
        <div class="job-interactions">
            <button class="interaction-btn like-btn" id="likeBtn" onclick="toggleLike(<?= $job['id'] ?>)" title="Like this job">
                <i class="fas fa-heart"></i>
                <span id="likeCount"><?= $job['likes_count'] ?? 0 ?></span>
            </button>
            <button class="interaction-btn comment-btn" id="commentBtn" onclick="openComments(<?= $job['id'] ?>)" title="View comments">
                <i class="fas fa-comments"></i>
                <span id="commentCount"><?= $job['comments_count'] ?? 0 ?></span>
            </button>
            <button class="interaction-btn save-btn" id="saveBtn" onclick="toggleSave(<?= $job['id'] ?>)" title="Save this job">
                <i class="fas fa-bookmark"></i>
                <span id="saveCount"><?= $job['saves_count'] ?? 0 ?></span>
            </button>
        </div>
    </div>

    <!-- Comments Sidebar -->
    <aside class="comment-section" id="commentSection" aria-label="Comments panel">
        <!-- Sidebar header -->
        <div class="comment-header">
            <h2><i class="fas fa-comments"></i> <span id="commentHeaderTitle">Comments</span></h2>
            <button class="comment-close-btn" id="closeCommentBtn" aria-label="Close comments">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Sort toolbar -->
        <div class="comment-toolbar">
            <select id="comment-sort" class="comment-sort-select" aria-label="Sort comments">
                <option value="newest">Newest first</option>
                <option value="oldest">Oldest first</option>
                <option value="top">Most liked</option>
            </select>
        </div>

        <!-- Comments list -->
        <div class="comments-container"></div>

        <!-- Add comment -->
        <div class="add-comment-area">
            <div class="add-comment-avatar" id="addCommentAvatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="add-comment-form">
                <div class="comment-input-wrap">
                    <textarea
                        class="comment-input"
                        placeholder="Share your thoughts…"
                        maxlength="1000"
                        rows="1"
                    ></textarea>
                    <button type="button" class="comment-submit-btn" aria-label="Post comment">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </aside>

    <!-- Dim overlay (mobile) -->
    <div class="comment-overlay" id="commentOverlay"></div>
</div>

<script>
// Pass job data to JavaScript
window.jobData = <?= json_encode($job) ?>;
</script>
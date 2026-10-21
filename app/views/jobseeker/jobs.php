<div class="main-section">
    
<main class="main-content" id="jobsMainContent">
    <div class="jobs-container">

        <!-- ── Page Header ────────────────────────────────────────── -->
        <div class="jobs-header animate__animated animate__fadeInDown">
            <div class="jobs-header-left">
                <div class="jobs-header-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div>
                    <h1 class="jobs-title">Browse Jobs</h1>
                    <p class="jobs-subtitle">Find your next opportunity</p>
                </div>
            </div>
        </div>

        <!-- ── Search ─────────────────────────────────────────────── -->
        <div class="jobs-search-wrap animate__animated animate__fadeIn">
            <i class="fas fa-search"></i>
            <input
                type="text"
                id="searchInput"
                class="jobs-search"
                placeholder="Search by job title, company, skill…"
                autocomplete="off"
            >
            <span class="jobs-search-clear" id="searchClear" style="display:none;">
                <i class="fas fa-times"></i>
            </span>
        </div>

        <!-- ── Jobs Grid ───────────────────────────────────────────── -->
        <div id="jobsGrid" class="jobs-grid"></div>

    </div><!-- /.jobs-container -->
</main>

<!-- ═══════════════════════════════════════════════════════════════
     COMMENTS SIDEBAR
═══════════════════════════════════════════════════════════════ -->
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
    <div id="comments-container" class="comments-container"></div>

    <!-- Add comment -->
    <div class="add-comment-area">
        <div class="add-comment-avatar" id="addCommentAvatar">
            <i class="fas fa-user"></i>
        </div>
        <form id="add-comment-form" class="add-comment-form" novalidate>
            <div class="comment-input-wrap">
                <textarea
                    id="comment-input"
                    class="comment-input"
                    placeholder="Share your thoughts…"
                    maxlength="1000"
                    rows="1"
                ></textarea>
                <button type="submit" class="comment-submit-btn" aria-label="Post comment">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
    </div>
</aside>

<!-- Dim overlay (mobile) -->
<div class="comment-overlay" id="commentOverlay"></div>

</div>
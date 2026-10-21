<div class="notifications-page">

    <!-- Page Header -->
    <div class="notif-header">
        <div class="notif-header-left">
            <h1 class="notif-title">
                <i class="fas fa-bell"></i>
                Notifications
                <span class="notif-badge" id="unreadBadge"></span>
            </h1>
            <p class="notif-subtitle">Stay up to date with your job applications and interviews</p>
        </div>
        <div class="notif-header-actions">
            <button class="btn btn-secondary btn-sm" id="markAllReadBtn">
                <i class="fas fa-check-double"></i>
                Mark all as read
            </button>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="notif-filters">
        <button class="filter-tab active" data-filter="all">
            <i class="fas fa-th-list"></i>
            All
            <span class="filter-count" id="countAll">0</span>
        </button>
        <button class="filter-tab" data-filter="unread">
            <i class="fas fa-circle-dot"></i>
            Unread
            <span class="filter-count" id="countUnread">0</span>
        </button>
        <button class="filter-tab" data-filter="interview">
            <i class="fas fa-calendar-check"></i>
            Interviews
            <span class="filter-count" id="countInterview">0</span>
        </button>
        <button class="filter-tab" data-filter="job_application">
            <i class="fas fa-file-alt"></i>
            Applications
            <span class="filter-count" id="countApplication">0</span>
        </button>
        <button class="filter-tab" data-filter="job">
            <i class="fas fa-briefcase"></i>
            Jobs
            <span class="filter-count" id="countJob">0</span>
        </button>
        <button class="filter-tab" data-filter="system">
            <i class="fas fa-shield-halved"></i>
            System
            <span class="filter-count" id="countSystem">0</span>
        </button>
    </div>

    <!-- Bulk Actions Bar (shown when selections exist) -->
    <div class="bulk-bar" id="bulkBar">
        <span id="selectedCount">0 selected</span>
        <div class="bulk-actions">
            <button class="btn btn-secondary btn-sm" id="bulkMarkReadBtn">
                <i class="fas fa-check"></i> Mark read
            </button>
            <button class="btn btn-danger btn-sm" id="bulkDeleteBtn">
                <i class="fas fa-trash"></i> Delete
            </button>
            <button class="btn btn-secondary btn-sm" id="cancelSelectBtn">
                Cancel
            </button>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="notif-list-container">
        <!-- Loading State -->
        <div class="notif-loading" id="notifLoading">
            <div class="loading-shimmer"></div>
            <div class="loading-shimmer short"></div>
            <div class="loading-shimmer"></div>
            <div class="loading-shimmer short"></div>
            <div class="loading-shimmer"></div>
        </div>

        <!-- Empty State -->
        <div class="notif-empty" id="notifEmpty" style="display:none;">
            <div class="empty-icon">
                <i class="fas fa-bell-slash"></i>
            </div>
            <h3>You're all caught up!</h3>
            <p>No notifications to show here.</p>
        </div>

        <!-- Notifications grouped list -->
        <div id="notifGroups"></div>

        <!-- Load More -->
        <div class="load-more-container" id="loadMoreContainer" style="display:none;">
            <button class="btn btn-secondary" id="loadMoreBtn">
                <i class="fas fa-arrow-down"></i>
                Load more notifications
            </button>
        </div>
    </div>

</div>

<style>
/* ===== NOTIFICATIONS PAGE ===== */
.notifications-page {
    flex: 1;
    padding: 2rem;
    max-width: 860px;
    margin: 0 auto;
    width: 100%;
    padding-bottom: 4rem;
}

/* Header */
.notif-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.75rem;
    flex-wrap: wrap;
}

.notif-title {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 1.75rem;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
    font-family: var(--font-heading);
}

.notif-title i {
    color: var(--primary-color);
    font-size: 1.5rem;
}

.notif-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--danger-color);
    color: white;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    line-height: 1;
}

.notif-badge:empty { display: none; }

.notif-subtitle {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.notif-header-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
    margin-top: 0.25rem;
}

/* Filter Tabs */
.notif-filters {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
    overflow-x: auto;
    padding-bottom: 0.25rem;
    scrollbar-width: none;
}
.notif-filters::-webkit-scrollbar { display: none; }

.filter-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 1rem;
    border-radius: 999px;
    border: 1.5px solid var(--border-color);
    background: var(--bg-secondary);
    color: var(--text-secondary);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    font-family: var(--font-primary);
}

.filter-tab:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.filter-tab.active {
    background: var(--gradient-primary);
    color: white;
    border-color: transparent;
    box-shadow: var(--shadow-md);
}

.filter-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.25);
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    line-height: 1;
}

.filter-tab:not(.active) .filter-count {
    background: var(--bg-tertiary);
    color: var(--text-muted);
}

/* Bulk Bar */
.bulk-bar {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: var(--bg-secondary);
    border: 1.5px solid var(--primary-color);
    border-radius: 10px;
    margin-bottom: 1rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
    gap: 1rem;
    flex-wrap: wrap;
    animation: slideIn 0.2s ease;
}

.bulk-bar.visible {
    display: flex;
}

.bulk-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* Loading Shimmer */
.notif-loading {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.loading-shimmer {
    height: 80px;
    border-radius: 12px;
    background: linear-gradient(110deg, var(--bg-secondary) 8%, var(--bg-tertiary) 18%, var(--bg-secondary) 33%);
    background-size: 200% 100%;
    animation: shimmer 1.5s linear infinite;
}

.loading-shimmer.short { height: 50px; width: 60%; }

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

/* Empty State */
.notif-empty {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-muted);
}

.empty-icon {
    width: 80px;
    height: 80px;
    background: var(--bg-secondary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
    font-size: 2rem;
    color: var(--text-muted);
    border: 2px solid var(--border-color);
}

.notif-empty h3 {
    color: var(--text-secondary);
    margin-bottom: 0.4rem;
}

/* Group Headers */
.notif-group-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-muted);
    margin: 1.5rem 0 0.6rem;
    padding-left: 0.25rem;
}

.notif-group-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border-color);
}

/* Notification Item */
.notif-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 1.1rem;
    background: var(--bg-secondary);
    border: 1.5px solid var(--border-color);
    border-radius: 12px;
    margin-bottom: 0.6rem;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    overflow: hidden;
}

.notif-item:hover {
    border-color: var(--primary-color);
    box-shadow: var(--shadow-sm);
    transform: translateX(2px);
}

.notif-item.unread {
    border-left: 3px solid var(--primary-color);
    background: var(--bg-primary);
}

[data-theme="dark"] .notif-item.unread {
    background: var(--bg-secondary);
}

.notif-item.selected {
    border-color: var(--primary-color);
    background: rgba(67, 97, 238, 0.05);
}

/* Checkbox */
.notif-checkbox-wrap {
    display: flex;
    align-items: center;
    padding-top: 0.15rem;
    flex-shrink: 0;
}

.notif-checkbox {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    border: 1.5px solid var(--border-color);
    background: var(--bg-primary);
    cursor: pointer;
    accent-color: var(--primary-color);
}

/* Icon */
.notif-icon-wrap {
    flex-shrink: 0;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.notif-icon-wrap.type-interview {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}
.notif-icon-wrap.type-job_application {
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
}
.notif-icon-wrap.type-job {
    background: rgba(245, 158, 11, 0.12);
    color: #f59e0b;
}
.notif-icon-wrap.type-comment {
    background: rgba(139, 92, 246, 0.12);
    color: #8b5cf6;
}
.notif-icon-wrap.type-like {
    background: rgba(239, 68, 68, 0.12);
    color: #ef4444;
}
.notif-icon-wrap.type-system {
    background: rgba(107, 114, 128, 0.12);
    color: #6b7280;
}

/* Content */
.notif-content {
    flex: 1;
    min-width: 0;
}

.notif-top-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.2rem;
}

.notif-title-text {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text-primary);
    line-height: 1.3;
}

.notif-time {
    font-size: 0.78rem;
    color: var(--text-muted);
    white-space: nowrap;
    flex-shrink: 0;
}

.notif-message {
    font-size: 0.875rem;
    color: var(--text-secondary);
    line-height: 1.5;
    margin-bottom: 0.5rem;
}

/* Status Badge */
.notif-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.status-scheduled, .status-rescheduled {
    background: rgba(59, 130, 246, 0.12);
    color: #3b82f6;
}
.status-cancelled {
    background: rgba(239, 68, 68, 0.12);
    color: #ef4444;
}
.status-accepted, .status-shortlisted, .status-completed {
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
}
.status-reviewed {
    background: rgba(245, 158, 11, 0.12);
    color: #f59e0b;
}
.status-not_selected, .status-rejected {
    background: rgba(107, 114, 128, 0.12);
    color: #6b7280;
}

/* Actions */
.notif-actions {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
    opacity: 0;
    transition: opacity 0.2s;
}

.notif-item:hover .notif-actions {
    opacity: 1;
}

.notif-action-btn {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    border: none;
    background: var(--bg-tertiary);
    color: var(--text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    transition: all 0.15s;
}

.notif-action-btn:hover {
    background: var(--primary-color);
    color: white;
}

.notif-action-btn.delete:hover {
    background: var(--danger-color);
    color: white;
}

/* Unread dot */
.unread-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--primary-color);
    flex-shrink: 0;
    margin-top: 0.35rem;
}

/* Load more */
.load-more-container {
    text-align: center;
    padding: 1.5rem 0;
}

/* Mobile */
@media (max-width: 768px) {
    .notifications-page {
        padding: 1rem;
        padding-bottom: 3rem;
    }

    .notif-header {
        margin-bottom: 1.25rem;
    }

    .notif-title {
        font-size: 1.4rem;
    }

    .notif-item {
        padding: 0.85rem;
    }

    .notif-actions {
        opacity: 1;
    }

    .notif-icon-wrap {
        width: 36px;
        height: 36px;
        font-size: 0.95rem;
    }
}

@media (max-width: 480px) {
    .notif-top-row {
        flex-direction: column;
        gap: 0.1rem;
    }

    .notif-time {
        font-size: 0.72rem;
    }
}
</style>
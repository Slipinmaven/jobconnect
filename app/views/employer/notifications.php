
<?php
// Employer notifications page, rendered through the dashboard layout.
$pageTitle = 'My Notifications';
ob_start();
?>

<style>
/* ===== JOBSEEKER NOTIFICATIONS PAGE ===== */
.jn-page {
    max-width: 1200px;
    width: 100%;
    margin: 0 auto;
    padding: 1.75rem 1rem 4rem;
}

/* ---- Header ---- */
.jn-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.75rem;
    flex-wrap: wrap;
}
.jn-header-left h1 {
    font-family: var(--font-heading);
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.jn-header-left h1 .bell-icon {
    width: 34px;
    height: 34px;
    background: var(--gradient-primary);
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.9rem;
    flex-shrink: 0;
}
.jn-header-left p {
    color: var(--text-muted);
    font-size: 0.82rem;
    margin: 0;
}
.jn-header-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
}

/* ---- Tabs ---- */
.jn-tabs {
    display: flex;
    gap: 0.2rem;
    margin-bottom: 1.25rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 0.25rem;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.jn-tabs::-webkit-scrollbar { display: none; }
.jn-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.8rem;
    border: none;
    border-radius: 7px;
    background: transparent;
    color: var(--text-muted);
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.18s;
    font-family: var(--font-primary);
    position: relative;
}
.jn-tab:hover { background: var(--bg-tertiary); color: var(--text-primary); }
.jn-tab.active { background: var(--gradient-primary); color: #fff; box-shadow: var(--shadow-sm); }
.jn-tab .jn-badge {
    font-size: 0.68rem;
    font-weight: 700;
    min-width: 17px;
    height: 17px;
    padding: 0 0.3rem;
    border-radius: 99px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.28);
    line-height: 1;
}
.jn-tab:not(.active) .jn-badge {
    background: var(--primary-color);
    color: #fff;
}

/* ---- Toolbar ---- */
.jn-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.875rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.jn-toolbar-left {
    font-size: 0.8rem;
    color: var(--text-muted);
}
.jn-toolbar-left strong { color: var(--text-primary); }
.jn-toolbar-right { display: flex; gap: 0.35rem; }

/* ---- Group label ---- */
.jn-group-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    color: var(--text-muted);
    padding: 0.75rem 0.5rem 0.3rem;
}

/* ---- Notification item ---- */
.jn-item {
    display: grid;
    grid-template-columns: 44px 1fr auto;
    gap: 0.75rem;
    align-items: start;
    padding: 0.85rem 0.75rem;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.15s;
    position: relative;
    margin-bottom: 2px;
    border: 1px solid transparent;
    text-decoration: none;
    color: inherit;
}
.jn-item:hover { background: var(--bg-secondary); border-color: var(--border-color); }
.jn-item.unread {
    background: var(--bg-secondary);
    border-color: var(--border-color);
}
.jn-item.unread::after {
    content: '';
    position: absolute;
    left: 0; top: 50%;
    transform: translateY(-50%);
    width: 3px; height: 55%;
    background: var(--gradient-primary);
    border-radius: 0 3px 3px 0;
}

/* Icon circle */
.jn-icon {
    width: 40px; height: 40px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem;
    flex-shrink: 0;
    margin-top: 1px;
}
/* Icon colours */
.jn-icon-interview    { background: rgba(59,130,246,.12);  color: #3b82f6; }
.jn-icon-application  { background: rgba(16,185,129,.12);  color: #10b981; }
.jn-icon-job          { background: rgba(245,158,11,.12);  color: #f59e0b; }
.jn-icon-like         { background: rgba(239,68,68,.12);   color: #ef4444; }
.jn-icon-comment      { background: rgba(139,92,246,.12);  color: #8b5cf6; }
.jn-icon-message      { background: rgba(6,182,212,.12);   color: #06b6d4; }
.jn-icon-security     { background: rgba(239,68,68,.12);   color: #ef4444; }
.jn-icon-system       { background: rgba(107,114,128,.12); color: #6b7280; }
[data-theme="dark"] .jn-icon-interview    { background: rgba(59,130,246,.2); }
[data-theme="dark"] .jn-icon-application  { background: rgba(16,185,129,.2); }
[data-theme="dark"] .jn-icon-job          { background: rgba(245,158,11,.2); }
[data-theme="dark"] .jn-icon-like         { background: rgba(239,68,68,.2); }
[data-theme="dark"] .jn-icon-comment      { background: rgba(139,92,246,.2); }
[data-theme="dark"] .jn-icon-message      { background: rgba(6,182,212,.2); }
[data-theme="dark"] .jn-icon-security     { background: rgba(239,68,68,.2); }
[data-theme="dark"] .jn-icon-system       { background: rgba(107,114,128,.2); }

/* Body */
.jn-body { min-width: 0; }
.jn-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.18rem;
    line-height: 1.4;
}
.jn-item:not(.unread) .jn-title { font-weight: 500; color: var(--text-secondary); }
.jn-actor {
    font-size: 0.75rem;
    color: var(--primary-color);
    font-weight: 500;
    margin-bottom: 0.2rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}
.jn-actor i { font-size: 0.65rem; opacity: 0.7; }
.jn-msg {
    font-size: 0.78rem;
    color: var(--text-muted);
    line-height: 1.5;
    margin-bottom: 0.3rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.jn-meta {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
}
.jn-time { font-size: 0.73rem; color: var(--text-muted); }
.jn-pill {
    font-size: 0.65rem;
    font-weight: 600;
    padding: 0.1rem 0.4rem;
    border-radius: 99px;
    text-transform: capitalize;
    background: var(--bg-tertiary);
    color: var(--text-muted);
}
.jn-pill.interview   { background: rgba(59,130,246,.1);  color: #3b82f6; }
.jn-pill.application { background: rgba(16,185,129,.1);  color: #10b981; }
.jn-pill.job         { background: rgba(245,158,11,.1);  color: #f59e0b; }
.jn-pill.like        { background: rgba(239,68,68,.1);   color: #ef4444; }
.jn-pill.comment     { background: rgba(139,92,246,.1);  color: #8b5cf6; }
.jn-pill.security    { background: rgba(239,68,68,.1);   color: #ef4444; }
.jn-pill.new         { background: var(--primary-color); color: #fff; }

/* Right actions */
.jn-actions {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    opacity: 0;
    transition: opacity 0.15s;
    flex-shrink: 0;
}
.jn-item:hover .jn-actions { opacity: 1; }
.jn-act-btn {
    width: 27px; height: 27px;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: var(--text-muted);
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem;
    transition: background 0.15s, color 0.15s;
}
.jn-act-btn:hover { background: var(--bg-tertiary); color: var(--text-primary); }
.jn-act-btn.del:hover { color: var(--danger-color); }

/* ---- Empty ---- */
.jn-empty {
    text-align: center;
    padding: 4rem 1rem 3rem;
    color: var(--text-muted);
}
.jn-empty-icon {
    font-size: 2.8rem;
    margin-bottom: 0.875rem;
    opacity: 0.3;
    display: block;
}
.jn-empty p { font-size: 0.88rem; margin: 0; }
.jn-empty small { font-size: 0.78rem; opacity: 0.7; }

/* ---- Skeleton ---- */
.jn-skel {
    display: grid;
    grid-template-columns: 44px 1fr;
    gap: 0.75rem;
    padding: 0.85rem 0.75rem;
    border-radius: 10px;
    margin-bottom: 2px;
}
.jn-skel-circle { width: 40px; height: 40px; border-radius: 50%; }
.jn-skel-lines { display: flex; flex-direction: column; gap: 6px; padding-top: 2px; }
.jn-skel-line { border-radius: 4px; height: 10px; }

/* ---- Pagination ---- */
.jn-pager {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}
.jn-pager-info { font-size: 0.78rem; color: var(--text-muted); padding: 0 0.25rem; }

/* ---- Mobile ---- */
@media (max-width: 580px) {
    .jn-header { flex-direction: column; }
    .jn-item { grid-template-columns: 40px 1fr; }
    .jn-actions { display: none; }   /* use long-press / swipe UX instead */
    .jn-item { position: relative; }
    .jn-item::before {
        content: '';
        position: absolute;
        right: 0.75rem; top: 50%;
        transform: translateY(-50%);
        width: 6px; height: 6px;
        border-radius: 50%;
        background: var(--primary-color);
        opacity: 0;
    }
    .jn-item.unread::before { opacity: 1; }
    .jn-msg { -webkit-line-clamp: 1; }
    .jn-tab { padding: 0.38rem 0.55rem; font-size: 0.75rem; }
}
</style>

<div class="jn-page">

    <!-- Header -->
    <div class="jn-header">
        <div class="jn-header-left">
            <h1>
                <span class="bell-icon"><i class="fas fa-bell"></i></span>
                Notifications
            </h1>
            <p id="jn-summary">All caught up</p>
        </div>
        <div class="jn-header-actions">
            <button class="btn btn-sm btn-secondary" id="jn-mark-all" onclick="markAllRead()" disabled>
                <i class="fas fa-check-double"></i>
                <span class="d-none-xs">Mark all read</span>
            </button>
            <button class="btn btn-sm btn-secondary" id="jn-refresh" onclick="refreshFeed()" title="Refresh">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="jn-tabs" id="jn-tabs" role="tablist" aria-label="Filter notifications">
        <button class="jn-tab active" data-filter="all" onclick="switchTab(this)" role="tab">
            <i class="fas fa-th-large"></i> All
            <span class="jn-badge" id="badge-all"></span>
        </button>
        <button class="jn-tab" data-filter="interview" onclick="switchTab(this)" role="tab">
            <i class="fas fa-calendar-check"></i> Interviews
            <span class="jn-badge" id="badge-interview"></span>
        </button>
        <button class="jn-tab" data-filter="job_application" onclick="switchTab(this)" role="tab">
            <i class="fas fa-file-alt"></i> Applications
            <span class="jn-badge" id="badge-job_application"></span>
        </button>
        <button class="jn-tab" data-filter="job" onclick="switchTab(this)" role="tab">
            <i class="fas fa-briefcase"></i> Jobs
            <span class="jn-badge" id="badge-job"></span>
        </button>
        <button class="jn-tab" data-filter="like" onclick="switchTab(this)" role="tab">
            <i class="fas fa-heart"></i> Likes
            <span class="jn-badge" id="badge-like"></span>
        </button>
        <button class="jn-tab" data-filter="comment" onclick="switchTab(this)" role="tab">
            <i class="fas fa-comment-dots"></i> Comments
            <span class="jn-badge" id="badge-comment"></span>
        </button>
        <button class="jn-tab" data-filter="security" onclick="switchTab(this)" role="tab">
            <i class="fas fa-shield-alt"></i> Security
            <span class="jn-badge" id="badge-security"></span>
        </button>
    </div>

    <!-- Toolbar -->
    <div class="jn-toolbar">
        <div class="jn-toolbar-left" id="jn-count-label">&nbsp;</div>
        <div class="jn-toolbar-right">
            <button class="btn btn-sm btn-secondary" id="jn-clear-read" onclick="clearRead()" style="display:none;">
                <i class="fas fa-trash-alt"></i> Clear read
            </button>
        </div>
    </div>

    <!-- List -->
    <div id="jn-list"></div>

    <!-- Pagination -->
    <div class="jn-pager" id="jn-pager"></div>

</div>

<script>
(function () {

    /* =====================================================
       STATE
    ===================================================== */
    let all       = [];
    let filter    = 'all';
    let page      = 1;
    const PER     = 15;

    /* =====================================================
       BOOT
    ===================================================== */
    fetchNotifications(true);

    /* =====================================================
       FETCH
    ===================================================== */
    async function fetchNotifications(silent = false) {
        if (!silent) skeleton();
        const btn = document.getElementById('jn-refresh');
        btn.disabled = true;

        try {
            // Fetch enough to paginate client-side (API paginates server-side too)
            const r = await fetch('/api/notifications?page=1&per_page=100', {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            const json = await r.json();

            all = json.data?.data ?? json.data ?? [];
            render();
            updateSummary(json.data?.unread_count ?? all.filter(n => !n.is_read).length, json.data?.total ?? all.length);
            refreshBadges();
            if (!silent) Toast && Toast.success('Notifications loaded');
        } catch (e) {
            console.error(e);
            document.getElementById('jn-list').innerHTML = emptyState(
                'fa-wifi',
                'Could not load notifications',
                '<a href="#" onclick="fetchNotifications();return false;">Tap to retry</a>'
            );
            Toast && Toast.error('Failed to load notifications');
        } finally {
            btn.disabled = false;
        }
    }

    /* =====================================================
       RENDER
    ===================================================== */
    function render() {
        const filtered = filtered_list();
        const paged    = paginated(filtered);
        const list     = document.getElementById('jn-list');
        const lbl      = document.getElementById('jn-count-label');

        if (!filtered.length) {
            list.innerHTML = emptyState('fa-bell-slash', noResultMsg(), '');
            lbl.innerHTML  = '';
            document.getElementById('jn-pager').innerHTML = '';
            document.getElementById('jn-clear-read').style.display = 'none';
            return;
        }

        const unreadCount = filtered.filter(n => !n.is_read).length;
        lbl.innerHTML = `Showing <strong>${paged.length}</strong> of <strong>${filtered.length}</strong>${unreadCount ? ` &bull; <strong>${unreadCount}</strong> unread` : ''}`;

        document.getElementById('jn-clear-read').style.display =
            filtered.some(n => n.is_read) ? '' : 'none';
        document.getElementById('jn-mark-all').disabled = !unreadCount;

        // Group by time bucket
        const groups = groupItems(paged);
        list.innerHTML = Object.entries(groups).map(([label, items]) =>
            `<div class="jn-group">
                <div class="jn-group-label">${label}</div>
                ${items.map(item).join('')}
            </div>`
        ).join('');

        pager(filtered.length);

        // Kick relative-time updater if main.js didn't already
        if (typeof initRelativeTimeUpdates === 'function') {
            updateRelativeTimeElements('[data-utc-timestamp]');
        }
    }

    /* Build one notification row */
    function item(n) {
        const t     = typeConfig(n.type);
        const time  = typeof utcToRelativeTime === 'function'
                        ? utcToRelativeTime(n.created_at)
                        : n.created_at;
        const actor = actorLabel(n);
        const unread = !+n.is_read;
        const pillCls = n.type || 'system';
        const url   = n.action_url || '';

        return `
        <div class="jn-item${unread ? ' unread' : ''}"
             id="jn-${n.id}"
             data-id="${n.id}"
             onclick="handleClick(event,${n.id},'${esc(url)}')"
             role="article"
             aria-label="${esc(n.title)}">

            <div class="jn-icon jn-icon-${t.css}">
                <i class="${t.icon}"></i>
            </div>

            <div class="jn-body">
                <div class="jn-title">${esc(n.title)}</div>
                ${actor ? `<div class="jn-actor"><i class="fas fa-building"></i>${esc(actor)}</div>` : ''}
                <div class="jn-msg">${esc(n.message || '')}</div>
                <div class="jn-meta">
                    <span class="jn-time" data-utc-timestamp="${n.created_at}">${time}</span>
                    <span class="jn-pill ${pillCls}">${friendlyType(n.type)}</span>
                    ${unread ? '<span class="jn-pill new">New</span>' : ''}
                </div>
            </div>

            <div class="jn-actions" onclick="event.stopPropagation()">
                ${unread ? `<button class="jn-act-btn" title="Mark as read" onclick="markRead(${n.id})"><i class="fas fa-check"></i></button>` : ''}
                ${url    ? `<button class="jn-act-btn" title="View details" onclick="nav('${esc(url)}')"><i class="fas fa-arrow-right"></i></button>` : ''}
                <button class="jn-act-btn del" title="Remove" onclick="remove(${n.id})"><i class="fas fa-trash"></i></button>
            </div>
        </div>`;
    }

    function skeleton() {
        document.getElementById('jn-list').innerHTML = Array(6).fill(0).map(() => `
            <div class="jn-skel glossy-placeholder">
                <div class="jn-skel-circle glossy-placeholder" style="width:40px;height:40px;border-radius:50%;"></div>
                <div class="jn-skel-lines">
                    <div class="jn-skel-line glossy-placeholder" style="width:55%;"></div>
                    <div class="jn-skel-line glossy-placeholder" style="width:85%;"></div>
                    <div class="jn-skel-line glossy-placeholder" style="width:38%;"></div>
                </div>
            </div>`).join('');
    }

    function pager(total) {
        const pages = Math.ceil(total / PER);
        const el    = document.getElementById('jn-pager');
        if (pages <= 1) { el.innerHTML = ''; return; }
        el.innerHTML = `
            <button class="btn btn-sm btn-secondary" ${page <= 1 ? 'disabled' : ''} onclick="goPage(${page-1})">
                <i class="fas fa-chevron-left"></i>
            </button>
            <span class="jn-pager-info">Page ${page} of ${pages}</span>
            <button class="btn btn-sm btn-secondary" ${page >= pages ? 'disabled' : ''} onclick="goPage(${page+1})">
                <i class="fas fa-chevron-right"></i>
            </button>`;
    }

    function emptyState(icon, title, sub) {
        return `<div class="jn-empty">
            <i class="fas ${icon} jn-empty-icon"></i>
            <p>${title}</p>
            ${sub ? `<small>${sub}</small>` : ''}
        </div>`;
    }

    /* =====================================================
       ACTIONS
    ===================================================== */
    async function markRead(id) {
        try {
            const r = await fetch(`/api/notifications/${id}/read`, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' }
            });
            if (!r.ok) throw new Error();
            const n = all.find(x => x.id === id);
            if (n) n.is_read = 1;
            render(); refreshBadges();
            Toast && Toast.success('Marked as read');
        } catch { Toast && Toast.error('Could not mark as read'); }
    }

    window.markAllRead = async function () {
        try {
            const r = await fetch('/api/notifications/read-all', {
                method: 'POST', credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' }
            });
            if (!r.ok) throw new Error();
            all.forEach(n => n.is_read = 1);
            render(); refreshBadges();
            Toast && Toast.success('All notifications marked as read');
        } catch { Toast && Toast.error('Something went wrong'); }
    };

    async function remove(id) {
        const ok = await confirmPromise('Remove this notification?', 'error');
        if (!ok) return;
        try {
            const r = await fetch(`/api/notifications/${id}`, {
                method: 'DELETE', credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!r.ok) throw new Error();
            all = all.filter(x => x.id !== id);
            render(); refreshBadges();
            Toast && Toast.success('Notification removed');
        } catch { Toast && Toast.error('Could not remove notification'); }
    }

    window.clearRead = async function () {
        const readOnes = all.filter(n => n.is_read);
        if (!readOnes.length) return;
        const ok = await confirmPromise(`Remove ${readOnes.length} read notification${readOnes.length > 1 ? 's' : ''}?`, 'error');
        if (!ok) return;
        try {
            await Promise.all(readOnes.map(n =>
                fetch(`/api/notifications/${n.id}`, {
                    method: 'DELETE', credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
            ));
            all = all.filter(n => !n.is_read);
            render(); refreshBadges();
            Toast && Toast.success(`Removed ${readOnes.length} notification${readOnes.length > 1 ? 's' : ''}`);
        } catch { Toast && Toast.error('Some removals failed'); }
    };

    /* =====================================================
       CLICK / NAVIGATION
    ===================================================== */
    window.handleClick = function (e, id, url) {
        if (e.target.closest('.jn-actions')) return;
        // Mark read silently
        const n = all.find(x => x.id === id);
        if (n && !+n.is_read) {
            fetch(`/api/notifications/${id}/read`, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(() => { n.is_read = 1; render(); refreshBadges(); });
        }
        if (url && url !== 'null') window.location.href = url;
    };

    window.nav = function (url) { if (url) window.location.href = url; };

    /* =====================================================
       TABS + FILTER
    ===================================================== */
    window.switchTab = function (btn) {
        document.querySelectorAll('.jn-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        filter = btn.dataset.filter;
        page   = 1;
        render();
    };

    function filtered_list() {
        return filter === 'all' ? all : all.filter(n => n.type === filter);
    }

    /* =====================================================
       PAGINATION
    ===================================================== */
    window.goPage = function (p) {
        page = p;
        render();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    function paginated(arr) {
        const s = (page - 1) * PER;
        return arr.slice(s, s + PER);
    }

    /* =====================================================
       GROUPING
    ===================================================== */
    function groupItems(items) {
        const now   = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const yday  = new Date(today); yday.setDate(today.getDate() - 1);
        const week  = new Date(today); week.setDate(today.getDate() - 7);

        const g = { Today: [], Yesterday: [], 'This Week': [], Older: [] };
        items.forEach(n => {
            const d = parseTS(n.created_at);
            if (!d) { g.Older.push(n); return; }
            const day = new Date(d.getFullYear(), d.getMonth(), d.getDate());
            if (+day === +today)     g.Today.push(n);
            else if (+day === +yday) g.Yesterday.push(n);
            else if (d >= week)      g['This Week'].push(n);
            else                     g.Older.push(n);
        });
        return Object.fromEntries(Object.entries(g).filter(([,v]) => v.length));
    }

    /* =====================================================
       BADGES + SUMMARY
    ===================================================== */
    function refreshBadges() {
        const unread = all.filter(n => !+n.is_read);
        const types  = ['interview','job_application','job','like','comment','security'];
        setBadge('all', unread.length);
        types.forEach(t => setBadge(t, unread.filter(n => n.type === t).length));
    }

    function setBadge(type, count) {
        const el = document.getElementById(`badge-${type}`);
        if (!el) return;
        el.textContent = count || '';
        el.style.display = count ? '' : 'none';
    }

    function updateSummary(unread, total) {
        document.getElementById('jn-summary').textContent =
            unread > 0
                ? `You have ${unread} new notification${unread > 1 ? 's' : ''}`
                : `All caught up · ${total} notification${total !== 1 ? 's' : ''}`;
    }

    function noResultMsg() {
        if (filter === 'all') return "You're all caught up!";
        return `No ${friendlyType(filter).toLowerCase()} notifications yet`;
    }

    /* =====================================================
       HELPERS
    ===================================================== */
    function typeConfig(type) {
        const map = {
            interview:       { icon: 'fas fa-calendar-check', css: 'interview' },
            job_application: { icon: 'fas fa-file-alt',       css: 'application' },
            job:             { icon: 'fas fa-briefcase',       css: 'job' },
            like:            { icon: 'fas fa-heart',           css: 'like' },
            comment:         { icon: 'fas fa-comment-dots',    css: 'comment' },
            message:         { icon: 'fas fa-envelope',        css: 'message' },
            security:        { icon: 'fas fa-shield-alt',      css: 'security' },
            system:          { icon: 'fas fa-cog',             css: 'system' },
        };
        return map[type] ?? { icon: 'fas fa-bell', css: 'system' };
    }

    function friendlyType(type) {
        const labels = {
            interview:       'Interview',
            job_application: 'Application',
            job:             'Job Alert',
            like:            'Like',
            comment:         'Comment',
            message:         'Message',
            security:        'Security',
            system:          'System',
        };
        return labels[type] ?? (type ? type.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()) : 'Notification');
    }

    /* Build a human-readable actor line */
    function actorLabel(n) {
        if (!n.actor_id) return '';
        // Prefer first+last if available, fall back to actor_name or company name
        if (n.actor_first_name && n.actor_last_name) return `${n.actor_first_name} ${n.actor_last_name}`;
        if (n.actor_name) return n.actor_name;
        if (n.actor_company_name) return n.actor_company_name;
        return '';
    }

    function parseTS(str) {
        if (!str) return null;
        try {
            return str.includes('T') || str.includes('Z') ? new Date(str) : new Date(str + ' UTC');
        } catch { return null; }
    }

    function esc(s) {
        if (!s) return '';
        return String(s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }

    function confirmPromise(msg, type = 'error') {
        return new Promise(resolve => {
            if (typeof Confirm !== 'undefined') {
                Confirm[type]({
                    heading: 'Are you sure?',
                    message: msg,
                    confirmText: 'Yes, remove',
                    cancelText: 'Cancel',
                    onConfirm: () => resolve(true),
                    onCancel:  () => resolve(false),
                });
            } else {
                resolve(window.confirm(msg));
            }
        });
    }

    /* =====================================================
       PUBLIC API
    ===================================================== */
    window.refreshFeed = function () { fetchNotifications(false); };
    window.markRead    = markRead;
    window.remove      = remove;

    /* Auto-refresh every 90 s silently */
    setInterval(() => fetchNotifications(true), 90000);

    /* Kick relative time updates via main.js */
    if (typeof initRelativeTimeUpdates === 'function') {
        initRelativeTimeUpdates('[data-utc-timestamp]', 60000);
    }

})();
</script>

<?php
$content = ob_get_clean();
echo $content;
?>

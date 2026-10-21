<div class="ja-page">

<style>
/* ═══════════════════════════════════════════
   JOBSEEKER – MY APPLICATIONS PAGE
   All selectors scoped under .ja-page
═══════════════════════════════════════════ */

.ja-page {
  flex: 1;
  min-height: 100vh;
  background: var(--bg-primary);
  padding: 2rem 2rem 5rem;
  font-family: var(--font-primary);
  overflow-x: hidden;
}

/* ── Page header ─────────────────────────── */
.ja-page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1.75rem;
}
.ja-title-block h1 {
  font-size: 1.65rem;
  font-weight: 700;
  color: var(--text-primary);
  font-family: var(--font-heading);
  margin: 0 0 .25rem;
  line-height: 1.2;
  display: flex;
  align-items: center;
  gap: .6rem;
}
.ja-title-icon {
  width: 36px; height: 36px;
  background: var(--gradient-primary);
  border-radius: 9px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: .9rem;
  flex-shrink: 0;
}
.ja-title-block p { color: var(--text-muted); font-size: .85rem; margin: 0; }

/* ── Stats bar ───────────────────────────── */
.ja-stats-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(148px, 1fr));
  gap: 1rem;
  margin-bottom: 1.75rem;
}
.ja-stat-card {
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: .95rem 1.1rem;
  display: flex;
  align-items: center;
  gap: .85rem;
  transition: transform .2s, box-shadow .2s;
}
.ja-stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }

.ja-stat-icon {
  width: 38px; height: 38px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  font-size: .95rem; flex-shrink: 0;
}
.si-all      { background:#ede9fe; color:#7c3aed; }
.si-pending  { background:#fef3c7; color:#d97706; }
.si-reviewed { background:#dbeafe; color:#2563eb; }
.si-accepted { background:#d1fae5; color:#059669; }
.si-rejected { background:#fee2e2; color:#dc2626; }
.si-response { background:#cffafe; color:#0891b2; }

[data-theme="dark"] .si-all      { background:#2e1065; color:#a78bfa; }
[data-theme="dark"] .si-pending  { background:#451a03; color:#fbbf24; }
[data-theme="dark"] .si-reviewed { background:#1e3a5f; color:#60a5fa; }
[data-theme="dark"] .si-accepted { background:#064e3b; color:#34d399; }
[data-theme="dark"] .si-rejected { background:#7f1d1d; color:#f87171; }
[data-theme="dark"] .si-response { background:#083344; color:#67e8f9; }

.ja-stat-num   { font-size: 1.45rem; font-weight: 700; color: var(--text-primary); line-height: 1; font-family: var(--font-heading); }
.ja-stat-label { font-size: .7rem;   color: var(--text-muted); margin-top: .2rem; white-space: nowrap; }

/* ── Controls ────────────────────────────── */
.ja-controls {
  display: flex; gap: .7rem; align-items: center;
  flex-wrap: wrap; margin-bottom: 1.1rem;
}
.ja-search-wrap { position: relative; flex: 1; min-width: 210px; }
.ja-search-wrap i {
  position: absolute; left: 11px; top: 50%;
  transform: translateY(-50%); color: var(--text-muted);
  font-size: .8rem; pointer-events: none;
}
.ja-search-input {
  width: 100%; padding: .62rem 1rem .62rem 2.2rem;
  border: 1.5px solid var(--border-color); border-radius: 8px;
  background: var(--bg-primary); color: var(--text-primary);
  font-size: .85rem; font-family: var(--font-primary);
  transition: border-color .2s, box-shadow .2s;
}
.ja-search-input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(67,97,238,.12); }

.ja-sel {
  padding: .62rem 1.9rem .62rem .8rem;
  border: 1.5px solid var(--border-color); border-radius: 8px;
  background: var(--bg-primary); color: var(--text-primary);
  font-size: .8rem; font-family: var(--font-primary); cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right .6rem center;
  transition: border-color .2s;
}
.ja-sel:focus { outline: none; border-color: var(--primary-color); }

.ja-view-toggle {
  display: flex; gap: .2rem;
  background: var(--bg-secondary); border: 1px solid var(--border-color);
  border-radius: 8px; padding: .2rem;
}
.ja-vbtn {
  width: 31px; height: 31px; border-radius: 6px; border: none;
  background: transparent; color: var(--text-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: .82rem; transition: background .15s, color .15s;
}
.ja-vbtn.active { background: var(--bg-primary); color: var(--primary-color); box-shadow: var(--shadow-sm); }

/* ── Status chips ────────────────────────── */
.ja-chips { display: flex; gap: .45rem; flex-wrap: wrap; margin-bottom: 1.4rem; }
.ja-chip {
  padding: .35rem .85rem; border-radius: 50px;
  font-size: .75rem; font-weight: 500;
  border: 1.5px solid var(--border-color);
  background: var(--bg-secondary); color: var(--text-secondary);
  cursor: pointer; transition: all .15s; white-space: nowrap;
  display: inline-flex; align-items: center; gap: .3rem;
}
.ja-chip:hover  { border-color: var(--primary-color); color: var(--primary-color); }
.ja-chip.active { background: var(--gradient-primary); color: #fff; border-color: transparent; }

/* ── Error banner ────────────────────────── */
.ja-error-banner {
  background: #fee2e2; border: 1px solid #fca5a5; border-radius: 12px;
  padding: 1.1rem 1.4rem; display: flex; align-items: center;
  gap: .9rem; color: #991b1b; margin-bottom: 1.4rem;
}
[data-theme="dark"] .ja-error-banner { background: #7f1d1d; border-color: #b91c1c; color: #fca5a5; }
.ja-error-banner i { font-size: 1.1rem; flex-shrink: 0; }
.ja-retry-btn {
  margin-left: auto; padding: .38rem .85rem; border-radius: 7px;
  border: 1px solid currentColor; background: transparent; color: inherit;
  font-size: .78rem; font-weight: 600; cursor: pointer;
  font-family: var(--font-primary); white-space: nowrap; transition: opacity .15s;
}
.ja-retry-btn:hover { opacity: .7; }

/* ── List / Grid containers ──────────────── */
.ja-list { display: flex; flex-direction: column; gap: .9rem; }
.ja-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px,1fr)); gap: .9rem; }

/* ── Application card ────────────────────── */
.ja-card {
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 14px;
  padding: 1.1rem 1.35rem;
  display: flex; gap: .95rem; align-items: flex-start;
  transition: box-shadow .2s, transform .2s;
  position: relative; overflow: hidden; cursor: pointer;
}
/* Left accent bar */
.ja-card::before {
  content: ''; position: absolute;
  left: 0; top: 0; bottom: 0; width: 4px;
  border-radius: 14px 0 0 14px;
}
.ja-card[data-status="pending"]::before    { background: #f59e0b; }
.ja-card[data-status="reviewed"]::before   { background: #3b82f6; }
.ja-card[data-status="shortlisted"]::before{ background: #8b5cf6; }
.ja-card[data-status="interview"]::before  { background: #06b6d4; }
.ja-card[data-status="accepted"]::before   { background: #10b981; }
.ja-card[data-status="rejected"]::before   { background: #ef4444; }
.ja-card[data-status="withdrawn"]::before  { background: #94a3b8; }
.ja-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); }

/* Grid mode: accent on top */
.ja-grid .ja-card              { flex-direction: column; }
.ja-grid .ja-card::before      { width: 100%; height: 4px; bottom: auto; border-radius: 14px 14px 0 0; }

.ja-card-logo {
  width: 44px; height: 44px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; color: #fff; font-weight: 700;
  font-family: var(--font-heading); flex-shrink: 0;
  position: relative; overflow: hidden;
}
.ja-card-logo img {
  position: absolute; inset: 0; width: 100%; height: 100%;
  object-fit: cover;
}
.ja-card-body { flex: 1; min-width: 0; }

.ja-card-top {
  display: flex; align-items: flex-start;
  justify-content: space-between; gap: .5rem; margin-bottom: .4rem;
}
.ja-card-title {
  font-size: .92rem; font-weight: 600;
  color: var(--text-primary); font-family: var(--font-heading);
  margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* Status badge */
.ja-badge {
  display: inline-flex; align-items: center; gap: .28rem;
  padding: .2rem .55rem; border-radius: 50px;
  font-size: .65rem; font-weight: 700; white-space: nowrap; flex-shrink: 0;
  text-transform: uppercase; letter-spacing: .05em;
}
.ja-badge.pending    { background:#fef3c7; color:#92400e; }
.ja-badge.reviewed   { background:#dbeafe; color:#1e40af; }
.ja-badge.shortlisted{ background:#ede9fe; color:#5b21b6; }
.ja-badge.interview  { background:#cffafe; color:#164e63; }
.ja-badge.accepted   { background:#d1fae5; color:#065f46; }
.ja-badge.rejected   { background:#fee2e2; color:#991b1b; }
.ja-badge.withdrawn  { background:#f1f5f9; color:#475569; }
[data-theme="dark"] .ja-badge.pending    { background:#451a03; color:#fcd34d; }
[data-theme="dark"] .ja-badge.reviewed   { background:#1e3a5f; color:#93c5fd; }
[data-theme="dark"] .ja-badge.shortlisted{ background:#2e1065; color:#c4b5fd; }
[data-theme="dark"] .ja-badge.interview  { background:#083344; color:#67e8f9; }
[data-theme="dark"] .ja-badge.accepted   { background:#064e3b; color:#6ee7b7; }
[data-theme="dark"] .ja-badge.rejected   { background:#7f1d1d; color:#fca5a5; }
[data-theme="dark"] .ja-badge.withdrawn  { background:#1e293b; color:#94a3b8; }

.ja-card-company {
  font-size: .8rem; color: var(--text-muted);
  margin-bottom: .5rem;
  display: flex; align-items: center; gap: .4rem; flex-wrap: wrap;
}
.ja-resp-pill {
  display: inline-flex; align-items: center; gap: .28rem;
  padding: .15rem .5rem; border-radius: 50px;
  font-size: .65rem; font-weight: 600;
}
.ja-resp-pill.yes { background:#d1fae5; color:#065f46; }
.ja-resp-pill.no  { background:#f1f5f9; color:#64748b; }
[data-theme="dark"] .ja-resp-pill.yes { background:#064e3b; color:#6ee7b7; }
[data-theme="dark"] .ja-resp-pill.no  { background:#1e293b; color:#94a3b8; }

.ja-card-meta { display: flex; gap: .75rem; flex-wrap: wrap; margin-bottom: .65rem; }
.ja-meta-item { display: flex; align-items: center; gap: .28rem; font-size: .73rem; color: var(--text-muted); }
.ja-meta-item i { font-size: .62rem; color: var(--primary-light); }
.ja-salary {
  display: inline-flex; align-items: center; gap: .22rem;
  padding: .15rem .5rem; background: var(--bg-tertiary);
  border-radius: 50px; font-size: .7rem; font-weight: 600; color: var(--success-color);
}
.ja-card-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding-top: .65rem; border-top: 1px solid var(--border-color);
  gap: .5rem; flex-wrap: wrap;
}
.ja-card-date { font-size: .7rem; color: var(--text-muted); display: flex; align-items: center; gap: .28rem; }
.ja-card-actions { display: flex; gap: .3rem; }
.ja-action-btn {
  padding: .28rem .65rem; border-radius: 6px;
  font-size: .7rem; font-weight: 500;
  border: 1px solid var(--border-color);
  background: var(--bg-primary); color: var(--text-secondary);
  cursor: pointer; transition: all .15s;
  display: inline-flex; align-items: center; gap: .28rem;
  font-family: var(--font-primary); text-decoration: none; line-height: 1.4;
}
.ja-action-btn:hover             { background: var(--bg-tertiary); border-color: var(--primary-color); color: var(--primary-color); }
.ja-action-btn.danger:hover      { border-color: var(--danger-color); color: var(--danger-color); background: #fee2e2; }
[data-theme="dark"] .ja-action-btn.danger:hover { background: #7f1d1d; }

/* ── Skeleton ─────────────────────────────── */
.ja-skel-card {
  background: var(--bg-secondary); border: 1px solid var(--border-color);
  border-radius: 14px; padding: 1.1rem 1.35rem;
  display: flex; gap: .95rem; align-items: flex-start;
}
.ja-skel {
  background: linear-gradient(110deg, var(--bg-tertiary) 8%, var(--bg-secondary) 18%, var(--bg-tertiary) 33%);
  background-size: 200% 100%;
  animation: ja-shimmer 1.4s linear infinite; border-radius: 6px;
}
@keyframes ja-shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
.ja-skel-logo { width:44px; height:44px; border-radius:10px; flex-shrink:0; }
.ja-skel-body { flex:1; }
.ja-skel-line { height:12px; margin-bottom:9px; }

/* ── Empty state ──────────────────────────── */
.ja-empty {
  text-align: center; padding: 3.5rem 2rem;
  background: var(--bg-secondary); border: 1px solid var(--border-color);
  border-radius: 16px;
}
.ja-empty-icon {
  width: 68px; height: 68px; border-radius: 50%;
  background: var(--bg-tertiary);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 1.1rem; font-size: 1.6rem; color: var(--text-muted);
}
.ja-empty h3   { color: var(--text-primary); margin-bottom: .45rem; font-family: var(--font-heading); }
.ja-empty p    { color: var(--text-muted); font-size: .85rem; max-width: 300px; margin: 0 auto 1.4rem; }

/* ── Detail drawer ────────────────────────── */
.ja-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.45);
  z-index: 998; opacity: 0; pointer-events: none; transition: opacity .25s;
}
.ja-overlay.open { opacity: 1; pointer-events: all; }

.ja-drawer {
  position: fixed; top: 0; right: 0; bottom: 0;
  width: min(500px, 100vw);
  background: var(--bg-primary);
  border-left: 1px solid var(--border-color);
  z-index: 999; transform: translateX(100%);
  transition: transform .3s cubic-bezier(.4,0,.2,1);
  display: flex; flex-direction: column;
  box-shadow: var(--shadow-xl);
}
.ja-drawer.open { transform: translateX(0); }

.ja-drawer-head {
  padding: 1rem 1.35rem;
  border-bottom: 1px solid var(--border-color);
  display: flex; align-items: center; gap: .7rem; flex-shrink: 0;
}
.ja-drawer-head h3 { font-size: .95rem; font-family: var(--font-heading); margin: 0; flex: 1; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ja-drawer-close {
  width: 30px; height: 30px; border-radius: 7px;
  border: 1px solid var(--border-color); background: var(--bg-secondary);
  color: var(--text-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: .85rem; transition: background .15s; flex-shrink: 0;
}
.ja-drawer-close:hover { background: var(--bg-tertiary); color: var(--text-primary); }

.ja-drawer-body { flex: 1; overflow-y: auto; padding: 1.35rem; scroll-behavior: smooth; }
.ja-drawer-body::-webkit-scrollbar { width: 4px; }
.ja-drawer-body::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 10px; }

.ja-dsect { margin-bottom: 1.5rem; }
.ja-dsect-title {
  font-size: .62rem; font-weight: 700; letter-spacing: .09em;
  text-transform: uppercase; color: var(--text-muted); margin-bottom: .7rem;
}
.ja-drow {
  display: flex; justify-content: space-between; align-items: center;
  padding: .5rem 0; border-bottom: 1px solid var(--border-color); gap: 1rem;
}
.ja-drow:last-child { border-bottom: none; }
.ja-drow-label { font-size: .78rem; color: var(--text-muted); display: flex; align-items: center; gap: .38rem; }
.ja-drow-label i { width: 13px; text-align: center; color: var(--primary-light); }
.ja-drow-val   { font-size: .82rem; font-weight: 500; color: var(--text-primary); text-align: right; }

/* Timeline */
.ja-tl { position: relative; padding-left: 1.3rem; }
.ja-tl::before { content:''; position:absolute; left:6px; top:8px; bottom:8px; width:2px; background:var(--border-color); }
.ja-tl-step { position: relative; padding-bottom: 1rem; }
.ja-tl-step:last-child { padding-bottom: 0; }
.ja-tl-dot {
  width: 14px; height: 14px; border-radius: 50%;
  background: var(--border-color); border: 2px solid var(--bg-primary);
  position: absolute; left: -1.38rem; top: 2px;
}
.ja-tl-dot.done    { background: var(--primary-color); }
.ja-tl-dot.current { background: #10b981; }
.ja-tl-dot.fail    { background: var(--danger-color); }
.ja-tl-name { font-size: .82rem; font-weight: 600; color: var(--text-primary); }
.ja-tl-date { font-size: .7rem; color: var(--text-muted); margin-top: .08rem; }

/* Interview cards in drawer */
.ja-iv-card {
  background: var(--bg-tertiary); border-radius: 10px;
  padding: .85rem 1rem; margin-bottom: .7rem;
}
.ja-iv-card:last-child { margin-bottom: 0; }
.ja-iv-title { font-size: .82rem; font-weight: 600; color: var(--text-primary); margin-bottom: .45rem; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.ja-iv-row   { display: flex; align-items: flex-start; gap: .38rem; font-size: .76rem; color: var(--text-muted); margin-bottom: .28rem; line-height: 1.4; }
.ja-iv-row i { width: 13px; color: var(--primary-light); margin-top: 1px; flex-shrink: 0; }
.ja-iv-row a { color: var(--primary-color); text-decoration: none; }
.ja-iv-row a:hover { text-decoration: underline; }

.ja-drawer-foot {
  padding: .9rem 1.35rem;
  border-top: 1px solid var(--border-color);
  display: flex; gap: .65rem; flex-shrink: 0;
}
.ja-drawer-foot .btn { flex: 1; text-align: center; justify-content: center; font-size: .82rem; }

/* ── Notes block ──────────────────────────── */
.ja-notes-block {
  background: var(--bg-tertiary); border-radius: 9px;
  padding: .8rem .95rem; font-size: .82rem;
  color: var(--text-secondary); line-height: 1.6;
}

/* ── Pagination bar ───────────────────────── */
.ja-pagination {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: .75rem; margin-top: 1.5rem;
  padding-top: 1.25rem; border-top: 1px solid var(--border-color);
}
.ja-page-info { font-size: .8rem; color: var(--text-muted); }
.ja-page-btns { display: flex; gap: .4rem; }
.ja-page-btn {
  padding: .35rem .8rem; border-radius: 7px;
  border: 1px solid var(--border-color);
  background: var(--bg-secondary); color: var(--text-secondary);
  font-size: .78rem; font-weight: 500; cursor: pointer;
  font-family: var(--font-primary); transition: all .15s;
  display: inline-flex; align-items: center; gap: .3rem;
}
.ja-page-btn:hover:not(:disabled) { border-color: var(--primary-color); color: var(--primary-color); background: var(--bg-primary); }
.ja-page-btn:disabled { opacity: .4; cursor: default; }
.ja-page-btn.current { background: var(--gradient-primary); color: #fff; border-color: transparent; }

/* ── Responsive ───────────────────────────── */
@media (max-width: 640px) {
  .ja-page { padding: 1rem 1rem 4rem; }
  .ja-stats-bar { grid-template-columns: repeat(2,1fr); }
  .ja-card-top { flex-direction: column; gap: .35rem; }
}
</style>


<!-- PAGE HEADER -->
<div class="ja-page-header">
  <div class="ja-title-block">
    <h1>
      <span class="ja-title-icon"><i class="fas fa-file-alt"></i></span>
      My Applications
    </h1>
    <p>Track every role you've applied to and stay on top of your progress</p>
  </div>
  <a href="/jobseeker/jobs" class="btn btn-primary btn-sm">
    <i class="fas fa-search" style="margin-right:.4rem;"></i>Browse Jobs
  </a>
</div>

<!-- STATS BAR -->
<div class="ja-stats-bar" id="ja-stats-bar">
  <div class="ja-skel-card"><div class="ja-skel ja-skel-logo"></div><div class="ja-skel-body"><div class="ja-skel ja-skel-line" style="width:40%;"></div><div class="ja-skel ja-skel-line" style="width:70%;height:10px;"></div></div></div>
  <div class="ja-skel-card"><div class="ja-skel ja-skel-logo"></div><div class="ja-skel-body"><div class="ja-skel ja-skel-line" style="width:40%;"></div><div class="ja-skel ja-skel-line" style="width:70%;height:10px;"></div></div></div>
  <div class="ja-skel-card"><div class="ja-skel ja-skel-logo"></div><div class="ja-skel-body"><div class="ja-skel ja-skel-line" style="width:40%;"></div><div class="ja-skel ja-skel-line" style="width:70%;height:10px;"></div></div></div>
  <div class="ja-skel-card"><div class="ja-skel ja-skel-logo"></div><div class="ja-skel-body"><div class="ja-skel ja-skel-line" style="width:40%;"></div><div class="ja-skel ja-skel-line" style="width:70%;height:10px;"></div></div></div>
</div>

<!-- CONTROLS -->
<div class="ja-controls">
  <div class="ja-search-wrap">
    <i class="fas fa-search"></i>
    <input type="text" class="ja-search-input" id="ja-search" placeholder="Search by job title or company…">
  </div>

  <select class="ja-sel" id="ja-sort">
    <option value="newest">Newest First</option>
    <option value="oldest">Oldest First</option>
    <option value="salary_hi">Highest Salary</option>
    <option value="salary_lo">Lowest Salary</option>
  </select>

  <select class="ja-sel" id="ja-type">
    <option value="">All Types</option>
    <option value="full_time">Full-time</option>
    <option value="part_time">Part-time</option>
    <option value="contract">Contract</option>
    <option value="internship">Internship</option>
    <option value="freelance">Freelance</option>
  </select>

  <div class="ja-view-toggle">
    <button class="ja-vbtn active" id="ja-vlist" title="List view"><i class="fas fa-list"></i></button>
    <button class="ja-vbtn"        id="ja-vgrid" title="Grid view"><i class="fas fa-th-large"></i></button>
  </div>
</div>

<!-- STATUS CHIPS -->
<div class="ja-chips" id="ja-chips">
  <button class="ja-chip active" data-status=""><i class="fas fa-layer-group"></i> All</button>
  <button class="ja-chip" data-status="pending"><i class="fas fa-hourglass-half"></i> Pending</button>
  <button class="ja-chip" data-status="reviewed"><i class="fas fa-eye"></i> Reviewed</button>
  <button class="ja-chip" data-status="shortlisted"><i class="fas fa-star"></i> Shortlisted</button>
  <button class="ja-chip" data-status="interview"><i class="fas fa-calendar-check"></i> Interview</button>
  <button class="ja-chip" data-status="accepted"><i class="fas fa-check-circle"></i> Accepted</button>
  <button class="ja-chip" data-status="rejected"><i class="fas fa-times-circle"></i> Rejected</button>
  <button class="ja-chip" data-status="withdrawn"><i class="fas fa-undo"></i> Withdrawn</button>
</div>

<!-- ERROR BANNER -->
<div class="ja-error-banner" id="ja-error" style="display:none;">
  <i class="fas fa-exclamation-circle"></i>
  <span id="ja-error-msg">Something went wrong. Please try again.</span>
  <button class="ja-retry-btn" id="ja-retry"><i class="fas fa-redo" style="margin-right:.3rem;"></i>Retry</button>
</div>

<!-- APPLICATION LIST -->
<div id="ja-wrap" class="ja-list">
  <div class="ja-skel-card"><div class="ja-skel ja-skel-logo"></div><div class="ja-skel-body"><div class="ja-skel ja-skel-line" style="width:55%;"></div><div class="ja-skel ja-skel-line" style="width:35%;"></div><div class="ja-skel ja-skel-line" style="width:70%;"></div></div></div>
  <div class="ja-skel-card"><div class="ja-skel ja-skel-logo"></div><div class="ja-skel-body"><div class="ja-skel ja-skel-line" style="width:60%;"></div><div class="ja-skel ja-skel-line" style="width:40%;"></div><div class="ja-skel ja-skel-line" style="width:65%;"></div></div></div>
  <div class="ja-skel-card"><div class="ja-skel ja-skel-logo"></div><div class="ja-skel-body"><div class="ja-skel ja-skel-line" style="width:50%;"></div><div class="ja-skel ja-skel-line" style="width:30%;"></div><div class="ja-skel ja-skel-line" style="width:75%;"></div></div></div>
</div>

<!-- PAGINATION -->
<div id="ja-pagination" style="display:none;" class="ja-pagination">
  <span class="ja-page-info" id="ja-page-info"></span>
  <div class="ja-page-btns" id="ja-page-btns"></div>
</div>


<!-- ════════════════════════════════
     DETAIL DRAWER
════════════════════════════════ -->
<div class="ja-overlay" id="ja-overlay"></div>

<aside class="ja-drawer" id="ja-drawer" aria-hidden="true" role="dialog" aria-label="Application details">
  <div class="ja-drawer-head">
    <h3 id="ja-drawer-title">Application Details</h3>
    <button class="ja-drawer-close" id="ja-drawer-close" aria-label="Close"><i class="fas fa-times"></i></button>
  </div>
  <div class="ja-drawer-body" id="ja-drawer-body"></div>
  <div class="ja-drawer-foot" id="ja-drawer-foot"></div>
</aside>


<!-- ════════════════════════════════
     JAVASCRIPT
════════════════════════════════ -->
<script>
(function () {
  'use strict';

  /* ─── Endpoints (all match your routes.php) ──────────── */
  const API = {
    myApplications : '/api/applications/user',           // GET  – paginated list
    stats          : '/api/applications/stats',          // GET  – aggregate counts
    withdraw       : id    => `/api/applications/${id}/withdraw`,           // POST
    interviews     : appId => `/api/applications/${appId}/interviews`,      // GET
  };

  /* ─── State ────────────────────────────────────────────── */
  let allApps    = [];          // current page data from server
  let pagination = {};          // { total, page, per_page, total_pages }
  let activeStatus = '';
  let viewMode     = 'list';
  let query        = '';
  let sortMode     = 'newest';
  let typeFilter   = '';
  let openAppId    = null;

  /* ─── DOM ──────────────────────────────────────────────── */
  const wrap       = document.getElementById('ja-wrap');
  const statsBar   = document.getElementById('ja-stats-bar');
  const overlay    = document.getElementById('ja-overlay');
  const drawer     = document.getElementById('ja-drawer');
  const dTitle     = document.getElementById('ja-drawer-title');
  const dBody      = document.getElementById('ja-drawer-body');
  const dFoot      = document.getElementById('ja-drawer-foot');
  const errBanner  = document.getElementById('ja-error');
  const errMsg     = document.getElementById('ja-error-msg');
  const retryBtn   = document.getElementById('ja-retry');
  const searchInp  = document.getElementById('ja-search');
  const sortSel    = document.getElementById('ja-sort');
  const typeSel    = document.getElementById('ja-type');
  const chipsWrap  = document.getElementById('ja-chips');
  const pagWrap    = document.getElementById('ja-pagination');
  const pagInfo    = document.getElementById('ja-page-info');
  const pagBtns    = document.getElementById('ja-page-btns');

  /* ─── Utils ────────────────────────────────────────────── */
  const esc = s => String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  const cap = s => s ? s.charAt(0).toUpperCase()+s.slice(1) : '';

  function fmt(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', {day:'numeric',month:'short',year:'numeric'});
  }
  function timeAgo(d) {
    if (!d) return '';
    const days = Math.floor((Date.now()-new Date(d))/86400000);
    if (days===0) return 'Today';
    if (days===1) return 'Yesterday';
    if (days<7)   return days+' days ago';
    if (days<30)  return Math.floor(days/7)+'w ago';
    return Math.floor(days/30)+'mo ago';
  }
  function fmtSalary(min,max) {
    const f = n => n>=1000 ? '$'+(n/1000).toFixed(0)+'k' : '$'+n;
    if (!min&&!max) return null;
    if (!min) return f(max);
    if (!max) return f(min);
    return f(min)+' – '+f(max);
  }
  const JT = {full_time:'Full-time',part_time:'Part-time',contract:'Contract',internship:'Internship',freelance:'Freelance'};
  const EX = {entry:'Entry Level',mid:'Mid Level',senior:'Senior Level',lead:'Lead',executive:'Executive'};
  const fmtType = t => JT[t]||t||'—';
  const fmtExp  = e => EX[e]||e||'—';

  function statusIcon(s) {
    return {pending:'fa-hourglass-half',reviewed:'fa-eye',shortlisted:'fa-star',
            interview:'fa-calendar-check',accepted:'fa-check-circle',
            rejected:'fa-times-circle',withdrawn:'fa-undo'}[s]||'fa-circle';
  }
  function hasResponse(app) {
    return ['reviewed','shortlisted','interview','accepted','rejected'].includes(app.status);
  }
  function initials(n) {
    return (n||'?').split(' ').slice(0,2).map(w=>w[0]).join('').toUpperCase();
  }
  function logoGrad(n) {
    const p=['#4361ee,#3a0ca3','#ef4444,#dc2626','#10b981,#059669',
             '#f59e0b,#d97706','#8b5cf6,#7c3aed','#06b6d4,#0891b2',
             '#ec4899,#db2777','#f97316,#ea580c'];
    return 'linear-gradient(135deg,'+p[(n&&n.charCodeAt(0)||0)%p.length]+')';
  }
  function companyAvatar(app, size) {
    const name = app.company_name || 'Unknown Company';
    const avatar = app.company_avatar
      ? `<img src="${esc(app.company_avatar)}" alt="" onerror="this.remove()">`
      : '';
    return `<div class="ja-card-logo" style="width:${size}px;height:${size}px;background:${logoGrad(name)}">${initials(name)}${avatar}</div>`;
  }

  /* ─── Generic API fetch ─────────────────────────────────── */
  // All your endpoints return: { success, statusCode, message, data }
  async function apiFetch(url, opts) {
    const res  = await fetch(url, opts);
    const json = await res.json();
    // { success: false, statusCode: 4xx, message: "...", data: null }
    if (!json.success) throw new Error(json.message || 'Request failed ('+res.status+')');
    return json;  // { success: true, statusCode: 200, message: "...", data: {...} }
  }

  /* ─── Load stats from dedicated endpoint ────────────────── */
  // GET /api/applications/stats
  // data shape will vary – we read what's there and fall back gracefully
  async function loadStats() {
    try {
      const json = await apiFetch(API.stats);
      renderStats(json.data || {});
    } catch (_) {
      // Non-critical – derive counts from the loaded apps instead
      derivedStats();
    }
  }

  function derivedStats() {
    const c = {};
    allApps.forEach(a => { c[a.status]=(c[a.status]||0)+1; });
    renderStats({
      total    : pagination.total || allApps.length,
      pending  : c.pending  || 0,
      reviewed : c.reviewed || 0,
      accepted : c.accepted || 0,
      rejected : c.rejected || 0,
      responded: allApps.filter(hasResponse).length,
    });
  }

  function renderStats(s) {
    const defs = [
      {cls:'si-all',     icon:'fa-layer-group',    label:'Total Applied',  val: s.total     ?? allApps.length},
      {cls:'si-pending', icon:'fa-hourglass-half', label:'Pending Review', val: s.pending   ?? 0},
      {cls:'si-reviewed',icon:'fa-eye',            label:'Reviewed',       val: s.reviewed  ?? 0},
      {cls:'si-accepted',icon:'fa-check-circle',   label:'Accepted',       val: s.accepted  ?? 0},
      {cls:'si-rejected',icon:'fa-times-circle',   label:'Rejected',       val: s.rejected  ?? 0},
      {cls:'si-response',icon:'fa-bell',           label:'Got Responses',  val: s.responded ?? allApps.filter(hasResponse).length},
    ];
    statsBar.innerHTML = defs.map(d=>`
      <div class="ja-stat-card">
        <div class="ja-stat-icon ${d.cls}"><i class="fas ${d.icon}"></i></div>
        <div>
          <div class="ja-stat-num">${d.val}</div>
          <div class="ja-stat-label">${d.label}</div>
        </div>
      </div>`).join('');
  }

  /* ─── Load applications ─────────────────────────────────── */
  // GET /api/applications/user
  // Returns: { success, statusCode, message,
  //            data: { data: [...], total, page, per_page, total_pages } }
  async function loadApps(page) {
    page = page || 1;
    errBanner.style.display = 'none';
    showSkeletons();

    const params = new URLSearchParams({ page });
    if (activeStatus) params.set('status', activeStatus);
    if (typeFilter)   params.set('job_type', typeFilter);
    if (query)        params.set('search', query);
    if (sortMode)     params.set('sort', sortMode);

    try {
      const json = await apiFetch(API.myApplications+'?'+params.toString());
      const d    = json.data || {};
      allApps    = d.data         || [];
      pagination = {
        total      : d.total       || 0,
        page       : d.page        || 1,
        per_page   : d.per_page    || 20,
        total_pages: d.total_pages || 1,
      };

      render();
      renderPagination();
      derivedStats();   // show counts from this page immediately
      loadStats();      // then enrich from the stats endpoint
    } catch (err) {
      showError(err.message || 'Could not load your applications. Please try again.');
    }
  }

  /* ─── Filter & sort (client-side within current page) ────── */
  // Primary filtering is server-side via query params above.
  // This provides instant client-side feedback while paginating.
  function filtered() {
    return allApps.slice().sort((a,b)=>{
      if (sortMode==='newest')    return new Date(b.applied_at)-new Date(a.applied_at);
      if (sortMode==='oldest')    return new Date(a.applied_at)-new Date(b.applied_at);
      if (sortMode==='salary_hi') return (b.salary_max||0)-(a.salary_max||0);
      if (sortMode==='salary_lo') return (a.salary_min||0)-(b.salary_min||0);
      return 0;
    });
  }

  /* ─── Render application cards ──────────────────────────── */
  function render() {
    wrap.className = viewMode==='grid' ? 'ja-grid' : 'ja-list';
    const apps = filtered();

    if (!apps.length) {
      wrap.innerHTML = `
        <div class="ja-empty">
          <div class="ja-empty-icon"><i class="fas fa-folder-open"></i></div>
          <h3>${activeStatus||query||typeFilter ? 'No applications match your filters' : "You haven't applied to any jobs yet"}</h3>
          <p>${activeStatus||query||typeFilter ? 'Try clearing your filters to see all applications.' : 'Start browsing open roles and submit your first application.'}</p>
          ${activeStatus||query||typeFilter
            ? `<button class="btn btn-secondary btn-sm" id="ja-clear"><i class="fas fa-times" style="margin-right:.4rem;"></i>Clear Filters</button>`
            : `<a href="/jobseeker/jobs" class="btn btn-primary btn-sm"><i class="fas fa-search" style="margin-right:.4rem;"></i>Browse Jobs</a>`}
        </div>`;
      document.getElementById('ja-clear')?.addEventListener('click', clearFilters);
      return;
    }

    wrap.innerHTML = apps.map(cardHtml).join('');
    bindCardEvents();
  }

  function cardHtml(app) {
    const salary    = fmtSalary(app.salary_min, app.salary_max);
    const responded = hasResponse(app);
    const canWd     = !['withdrawn','rejected','accepted'].includes(app.status);
    return `
    <div class="ja-card" data-status="${esc(app.status)}" data-id="${app.id}">
      ${companyAvatar(app, 44)}
      <div class="ja-card-body">
        <div class="ja-card-top">
          <h4 class="ja-card-title" title="${esc(app.title)}">${esc(app.title)}</h4>
          <span class="ja-badge ${esc(app.status)}">
            <i class="fas ${statusIcon(app.status)}"></i> ${cap(app.status)}
          </span>
        </div>
        <div class="ja-card-company">
          <i class="fas fa-building" style="font-size:.68rem;"></i>
          <strong style="color:var(--text-secondary);">${esc(app.company_name||'Unknown Company')}</strong>
          <span class="ja-resp-pill ${responded?'yes':'no'}">
            <i class="fas ${responded?'fa-bell':'fa-clock'}"></i>
            ${responded?'Responded':'Awaiting response'}
          </span>
        </div>
        <div class="ja-card-meta">
          <span class="ja-meta-item"><i class="fas fa-briefcase"></i>${fmtType(app.job_type)}</span>
          <span class="ja-meta-item"><i class="fas fa-signal"></i>${fmtExp(app.experience_level)}</span>
          ${salary ? `<span class="ja-salary"><i class="fas fa-dollar-sign" style="font-size:.58rem;"></i>${salary}</span>` : ''}
        </div>
        <div class="ja-card-footer">
          <span class="ja-card-date"><i class="fas fa-calendar-alt"></i>Applied ${timeAgo(app.applied_at)}</span>
          <div class="ja-card-actions">
            <button class="ja-action-btn ja-open-detail" data-id="${app.id}">
              <i class="fas fa-info-circle"></i> Details
            </button>
            ${canWd ? `<button class="ja-action-btn danger ja-do-withdraw" data-id="${app.id}" data-title="${esc(app.title)}">
              <i class="fas fa-undo"></i> Withdraw
            </button>` : ''}
            <a href="/jobseeker/jobs#${esc(app.slug||'')}" class="ja-action-btn" title="View listing">
              <i class="fas fa-external-link-alt"></i>
            </a>
          </div>
        </div>
      </div>
    </div>`;
  }

  function bindCardEvents() {
    wrap.querySelectorAll('.ja-card').forEach(card => {
      card.addEventListener('click', e => {
        if (e.target.closest('.ja-action-btn')) return;
        openDrawer(+card.dataset.id);
      });
    });
    wrap.querySelectorAll('.ja-open-detail').forEach(btn => {
      btn.addEventListener('click', e => { e.stopPropagation(); openDrawer(+btn.dataset.id); });
    });
    wrap.querySelectorAll('.ja-do-withdraw').forEach(btn => {
      btn.addEventListener('click', e => { e.stopPropagation(); doWithdraw(+btn.dataset.id, btn.dataset.title); });
    });
  }

  /* ─── Pagination ────────────────────────────────────────── */
  function renderPagination() {
    const { total, page, per_page, total_pages } = pagination;
    if (!total || total_pages <= 1) { pagWrap.style.display='none'; return; }

    const from = (page-1)*per_page+1;
    const to   = Math.min(page*per_page, total);
    pagInfo.textContent = `Showing ${from}–${to} of ${total} applications`;

    let html = `<button class="ja-page-btn" id="ja-prev" ${page<=1?'disabled':''}><i class="fas fa-chevron-left"></i> Prev</button>`;
    for (let p=1; p<=total_pages; p++) {
      if (total_pages>7 && p>2 && p<total_pages-1 && Math.abs(p-page)>1) {
        if (p===3||p===total_pages-2) html += `<span style="align-self:center;color:var(--text-muted);padding:0 .2rem;">…</span>`;
        continue;
      }
      html += `<button class="ja-page-btn ${p===page?'current':''}" data-page="${p}">${p}</button>`;
    }
    html += `<button class="ja-page-btn" id="ja-next" ${page>=total_pages?'disabled':''}> Next <i class="fas fa-chevron-right"></i></button>`;
    pagBtns.innerHTML = html;
    pagWrap.style.display = 'flex';

    pagBtns.querySelectorAll('[data-page]').forEach(b =>
      b.addEventListener('click', () => loadApps(+b.dataset.page))
    );
    pagBtns.querySelector('#ja-prev')?.addEventListener('click', () => loadApps(page-1));
    pagBtns.querySelector('#ja-next')?.addEventListener('click', () => loadApps(page+1));
  }

  /* ─── Withdraw ──────────────────────────────────────────── */
  // POST /api/applications/{id}/withdraw
  // Returns: { success, statusCode, message, data: {...} }
  async function doWithdraw(id, title) {
    const ok = await window.Confirm?.show({
      heading    : 'Withdraw Application',
      message    : `Withdraw your application for "${title}"?`,
      details    : 'This action cannot be undone. You may re-apply if the position is still open.',
      type       : 'warning',
      confirmText: 'Yes, Withdraw',
      cancelText : 'Keep Application',
    });
    if (!ok) return;

    try {
      await apiFetch(API.withdraw(id), {
        method : 'POST',
        headers: {'Content-Type':'application/json'},
      });
      window.Toast?.success('Application withdrawn successfully.');
      if (openAppId===id) closeDrawer();
      // Reload current page to reflect server state
      loadApps(pagination.page||1);
    } catch (err) {
      window.Toast?.error(err.message || 'Could not withdraw. Please try again.');
    }
  }

  /* ─── Load interviews for an application ────────────────── */
  // GET /api/applications/{applicationId}/interviews
  // Returns: { success, statusCode, message, data: [...] }
  async function loadInterviews(appId) {
    try {
      const json = await apiFetch(API.interviews(appId));
      const d = json.data;
      if (Array.isArray(d)) return d;
      if (d && Array.isArray(d.data)) return d.data;
      return [];
    } catch (_) {
      return [];
    }
  }

  /* ─── Open detail drawer ────────────────────────────────── */
  async function openDrawer(id) {
    const app = allApps.find(a=>a.id===id);
    if (!app) return;
    openAppId = id;

    dTitle.textContent = app.title;
    dBody.innerHTML = `<div style="text-align:center;padding:2rem;color:var(--text-muted);">
      <i class="fas fa-spinner fa-spin" style="font-size:1.4rem;"></i>
      <div style="margin-top:.7rem;font-size:.82rem;">Loading details…</div>
    </div>`;
    dFoot.innerHTML = '';

    overlay.classList.add('open');
    drawer.classList.add('open');
    drawer.setAttribute('aria-hidden','false');

    const interviews = await loadInterviews(id);
    if (openAppId!==id) return; // closed while loading

    const salary    = fmtSalary(app.salary_min, app.salary_max);
    const responded = hasResponse(app);
    const canWd     = !['withdrawn','rejected','accepted'].includes(app.status);

    // ── Timeline ──
    const tlSteps = [
      {name:'Applied',   date:app.applied_at,   dot:'done'},
      {name:'Viewed',    date:app.viewed_at,     dot:app.viewed_at   ? 'done' : ''},
      {name:'Reviewed',  date:app.reviewed_at,   dot:app.reviewed_at ? (app.status==='rejected'?'fail':'done') : ''},
    ];
    if (interviews.length || app.status==='interview') {
      tlSteps.push({name:'Interview', date:interviews[0]?.scheduled_at||null, dot:'current'});
    }
    if (app.status==='accepted')  tlSteps.push({name:'Accepted',  date:app.updated_at,   dot:'done'});
    if (app.status==='rejected')  tlSteps.push({name:'Rejected',  date:app.updated_at,   dot:'fail'});
    if (app.status==='withdrawn') tlSteps.push({name:'Withdrawn', date:app.withdrawn_at, dot:'fail'});

    const tlHtml = tlSteps.map(s=>`
      <div class="ja-tl-step">
        <div class="ja-tl-dot ${s.dot}"></div>
        <div>
          <div class="ja-tl-name">${s.name}</div>
          <div class="ja-tl-date">${s.date ? fmt(s.date) : 'Pending'}</div>
        </div>
      </div>`).join('');

    // ── Interviews section ──
    const ivHtml = interviews.length ? `
      <div class="ja-dsect">
        <div class="ja-dsect-title"><i class="fas fa-calendar-check" style="margin-right:.35rem;color:var(--primary-light);"></i>Scheduled Interviews (${interviews.length})</div>
        ${interviews.map(iv=>`
          <div class="ja-iv-card">
            <div class="ja-iv-title">
              ${esc(iv.type||'Interview')}
              ${iv.status ? `<span class="ja-badge ${esc(iv.status)}">${cap(iv.status)}</span>` : ''}
            </div>
            <div class="ja-iv-row"><i class="fas fa-calendar"></i>${fmt(iv.scheduled_at)}</div>
            ${iv.duration_minutes ? `<div class="ja-iv-row"><i class="fas fa-clock"></i>${iv.duration_minutes} minutes</div>` : ''}
            ${iv.location        ? `<div class="ja-iv-row"><i class="fas fa-map-marker-alt"></i>${esc(iv.location)}</div>` : ''}
            ${iv.meeting_link    ? `<div class="ja-iv-row"><i class="fas fa-video"></i><a href="${esc(iv.meeting_link)}" target="_blank" rel="noopener">Join Meeting</a></div>` : ''}
            ${iv.notes           ? `<div class="ja-iv-row"><i class="fas fa-sticky-note"></i><span>${esc(iv.notes)}</span></div>` : ''}
          </div>`).join('')}
      </div>` : '';

    // ── Employer notes ──
    const notesHtml = app.employer_notes ? `
      <div class="ja-dsect">
        <div class="ja-dsect-title"><i class="fas fa-comment-alt" style="margin-right:.35rem;color:var(--primary-light);"></i>Employer Notes</div>
        <div class="ja-notes-block">${esc(app.employer_notes)}</div>
      </div>` : '';

    dBody.innerHTML = `
      <!-- Overview -->
      <div class="ja-dsect">
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
          ${companyAvatar(app, 46)}
          <div style="min-width:0;">
            <div style="font-weight:600;color:var(--text-primary);font-size:.92rem;margin-bottom:.18rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(app.title)}</div>
            <div style="font-size:.78rem;color:var(--text-muted);">${esc(app.company_name||'Unknown Company')}</div>
          </div>
          <span class="ja-badge ${esc(app.status)}" style="margin-left:auto;flex-shrink:0;">
            <i class="fas ${statusIcon(app.status)}"></i> ${cap(app.status)}
          </span>
        </div>
        <div class="ja-drow">
          <span class="ja-drow-label"><i class="fas fa-bell"></i>Employer Response</span>
          <span class="ja-resp-pill ${responded?'yes':'no'}">
            <i class="fas ${responded?'fa-check':'fa-clock'}"></i>${responded?'Received':'Not yet'}
          </span>
        </div>
        <div class="ja-drow"><span class="ja-drow-label"><i class="fas fa-calendar-plus"></i>Applied On</span><span class="ja-drow-val">${fmt(app.applied_at)}</span></div>
        ${app.reviewed_at ? `<div class="ja-drow"><span class="ja-drow-label"><i class="fas fa-calendar-check"></i>Reviewed On</span><span class="ja-drow-val">${fmt(app.reviewed_at)}</span></div>` : ''}
        <div class="ja-drow"><span class="ja-drow-label"><i class="fas fa-sync-alt"></i>Last Updated</span><span class="ja-drow-val">${fmt(app.updated_at)}</span></div>
      </div>

      <!-- Job details -->
      <div class="ja-dsect">
        <div class="ja-dsect-title"><i class="fas fa-briefcase" style="margin-right:.35rem;color:var(--primary-light);"></i>Job Details</div>
        <div class="ja-drow"><span class="ja-drow-label"><i class="fas fa-tag"></i>Job Type</span><span class="ja-drow-val">${fmtType(app.job_type)}</span></div>
        <div class="ja-drow"><span class="ja-drow-label"><i class="fas fa-signal"></i>Experience</span><span class="ja-drow-val">${fmtExp(app.experience_level)}</span></div>
        ${salary ? `<div class="ja-drow"><span class="ja-drow-label"><i class="fas fa-dollar-sign"></i>Salary Range</span><span class="ja-drow-val" style="color:var(--success-color);font-weight:600;">${salary}/yr</span></div>` : ''}
        <div class="ja-drow"><span class="ja-drow-label"><i class="fas fa-building"></i>Employer Type</span><span class="ja-drow-val">${cap(app.employer_type||'company')}</span></div>
      </div>

      <!-- Timeline -->
      <div class="ja-dsect">
        <div class="ja-dsect-title"><i class="fas fa-stream" style="margin-right:.35rem;color:var(--primary-light);"></i>Application Timeline</div>
        <div class="ja-tl">${tlHtml}</div>
      </div>

      ${ivHtml}
      ${notesHtml}
    `;

    // Footer
    dFoot.innerHTML = `
      <a href="/jobseeker/jobs#${esc(app.slug||'')}" class="btn btn-secondary btn-sm" style="text-decoration:none;">
        <i class="fas fa-external-link-alt" style="margin-right:.35rem;"></i>View Listing
      </a>
      ${canWd ? `<button class="btn btn-danger btn-sm" id="ja-d-withdraw" data-id="${app.id}" data-title="${esc(app.title)}">
        <i class="fas fa-undo" style="margin-right:.35rem;"></i>Withdraw
      </button>` : ''}`;

    document.getElementById('ja-d-withdraw')?.addEventListener('click', () => {
      doWithdraw(app.id, app.title);
    });
  }

  function closeDrawer() {
    openAppId = null;
    overlay.classList.remove('open');
    drawer.classList.remove('open');
    drawer.setAttribute('aria-hidden','true');
  }

  /* ─── Helpers ───────────────────────────────────────────── */
  function showSkeletons() {
    wrap.className = 'ja-list';
    wrap.innerHTML = [1,2,3].map(()=>`
      <div class="ja-skel-card">
        <div class="ja-skel ja-skel-logo"></div>
        <div class="ja-skel-body">
          <div class="ja-skel ja-skel-line" style="width:55%;"></div>
          <div class="ja-skel ja-skel-line" style="width:35%;"></div>
          <div class="ja-skel ja-skel-line" style="width:70%;"></div>
        </div>
      </div>`).join('');
    pagWrap.style.display='none';
  }

  function showError(msg) {
    wrap.innerHTML = '';
    errMsg.textContent = msg;
    errBanner.style.display = 'flex';
    pagWrap.style.display = 'none';
  }

  function clearFilters() {
    activeStatus = '';
    query        = '';
    typeFilter   = '';
    sortMode     = 'newest';
    searchInp.value = '';
    sortSel.value   = 'newest';
    typeSel.value   = '';
    chipsWrap.querySelectorAll('.ja-chip').forEach(c => c.classList.toggle('active', c.dataset.status===''));
    loadApps(1);
  }

  /* ─── Event listeners ───────────────────────────────────── */

  // Status chips
  chipsWrap.addEventListener('click', e => {
    const chip = e.target.closest('.ja-chip');
    if (!chip) return;
    activeStatus = chip.dataset.status;
    chipsWrap.querySelectorAll('.ja-chip').forEach(c=>c.classList.toggle('active',c===chip));
    loadApps(1);
  });

  // Search – debounced, re-queries server
  let stTimer;
  searchInp.addEventListener('input', () => {
    clearTimeout(stTimer);
    stTimer = setTimeout(() => { query = searchInp.value.trim(); loadApps(1); }, 350);
  });

  // Sort – re-queries server so all pages are sorted
  sortSel.addEventListener('change', () => { sortMode = sortSel.value; loadApps(1); });

  // Type filter
  typeSel.addEventListener('change', () => { typeFilter = typeSel.value; loadApps(1); });

  // View toggle (client-side only)
  document.getElementById('ja-vlist').addEventListener('click', () => {
    viewMode = 'list';
    document.getElementById('ja-vlist').classList.add('active');
    document.getElementById('ja-vgrid').classList.remove('active');
    render();
  });
  document.getElementById('ja-vgrid').addEventListener('click', () => {
    viewMode = 'grid';
    document.getElementById('ja-vgrid').classList.add('active');
    document.getElementById('ja-vlist').classList.remove('active');
    render();
  });

  // Drawer close
  document.getElementById('ja-drawer-close').addEventListener('click', closeDrawer);
  overlay.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', e => { if (e.key==='Escape' && openAppId) closeDrawer(); });

  // Retry
  retryBtn.addEventListener('click', () => loadApps(1));

  /* ─── Boot ──────────────────────────────────────────────── */
  loadApps(1);

})();
</script>

</div>
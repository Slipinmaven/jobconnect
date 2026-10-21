<div class="ed-page">

<style>

.ed-page {
  flex: 1;
  min-height: 100vh;
  background: var(--bg-primary);
  padding: 1.75rem 2rem 6rem;
  font-family: var(--font-primary);
  overflow-x: hidden;
}

/* Page header */
.ed-header {
  display: flex; align-items: flex-start;
  justify-content: space-between; gap: 1rem;
  flex-wrap: wrap; margin-bottom: 1.75rem;
}
.ed-header-left h1 {
  font-size: 1.6rem; font-weight: 700;
  color: var(--text-primary); font-family: var(--font-heading);
  margin: 0 0 .2rem; display: flex; align-items: center; gap: .6rem;
}
.ed-hi-icon {
  width: 36px; height: 36px; border-radius: 9px;
  background: var(--gradient-primary);
  display: inline-flex; align-items: center; justify-content: center;
  color: #fff; font-size: .85rem; flex-shrink: 0;
}
.ed-header-left p { color: var(--text-muted); font-size: .85rem; margin: 0; }
.ed-header-right  { display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; }

/* Refresh btn */
.ed-refresh-btn {
  padding: .52rem 1rem; border-radius: 8px;
  border: 1.5px solid var(--border-color);
  background: var(--bg-secondary); color: var(--text-secondary);
  font-size: .8rem; font-weight: 500; cursor: pointer;
  display: inline-flex; align-items: center; gap: .4rem;
  font-family: var(--font-primary); transition: all .15s;
}
.ed-refresh-btn:hover { border-color: var(--primary-color); color: var(--primary-color); }
.ed-refresh-btn.spinning i { animation: ed-spin 1s linear infinite; }
@keyframes ed-spin { to { transform: rotate(360deg); } }

.ed-last-updated { font-size: .72rem; color: var(--text-muted); display: flex; align-items: center; gap: .3rem; }

/* Error banner */
.ed-error-banner {
  background: #fee2e2; border: 1px solid #fca5a5; border-radius: 12px;
  padding: .9rem 1.25rem; display: none; align-items: center;
  gap: .85rem; color: #991b1b; margin-bottom: 1.5rem; font-size: .85rem;
}
[data-theme="dark"] .ed-error-banner { background: #7f1d1d; border-color: #b91c1c; color: #fca5a5; }
.ed-error-banner.show { display: flex; }
.ed-retry-btn {
  margin-left: auto; padding: .3rem .75rem; border-radius: 7px;
  border: 1px solid currentColor; background: transparent; color: inherit;
  font-size: .75rem; font-weight: 600; cursor: pointer;
  font-family: var(--font-primary); transition: opacity .15s; white-space: nowrap;
}
.ed-retry-btn:hover { opacity: .7; }

/* Alert banner */
.ed-alert-banner {
  display: none;
  background: linear-gradient(135deg,rgba(245,158,11,.09),rgba(217,119,6,.05));
  border: 1px solid rgba(245,158,11,.35);
  border-radius: 12px; padding: .85rem 1.25rem;
  margin-bottom: 1.5rem; align-items: center; gap: .9rem;
}
[data-theme="dark"] .ed-alert-banner { background: rgba(245,158,11,.07); }
.ed-alert-banner.show { display: flex; }
.ed-alert-banner i { color: #d97706; font-size: 1rem; flex-shrink: 0; }
.ed-alert-banner strong { color: var(--text-primary); font-size: .85rem; }
.ed-alert-banner span   { font-size: .78rem; color: var(--text-muted); }

/* KPI grid */
.ed-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px,1fr));
  gap: 1rem; margin-bottom: 1.75rem;
}
.ed-kpi {
  background: var(--bg-secondary); border: 1px solid var(--border-color);
  border-radius: 14px; padding: 1.15rem 1.25rem;
  display: flex; flex-direction: column; gap: .6rem;
  transition: transform .2s, box-shadow .2s;
  position: relative; overflow: hidden;
}
.ed-kpi::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: var(--kpi-accent, var(--gradient-primary));
}
.ed-kpi:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.ed-kpi-top { display: flex; align-items: center; justify-content: space-between; }
.ed-kpi-icon {
  width: 36px; height: 36px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  font-size: .88rem; flex-shrink: 0;
}
.kpi-blue   { background:#dbeafe; color:#2563eb; --kpi-accent:linear-gradient(90deg,#2563eb,#3b82f6); }
.kpi-violet { background:#ede9fe; color:#7c3aed; --kpi-accent:linear-gradient(90deg,#7c3aed,#8b5cf6); }
.kpi-green  { background:#d1fae5; color:#059669; --kpi-accent:linear-gradient(90deg,#059669,#10b981); }
.kpi-amber  { background:#fef3c7; color:#d97706; --kpi-accent:linear-gradient(90deg,#d97706,#f59e0b); }
.kpi-teal   { background:#cffafe; color:#0891b2; --kpi-accent:linear-gradient(90deg,#0891b2,#06b6d4); }
.kpi-rose   { background:#ffe4e6; color:#e11d48; --kpi-accent:linear-gradient(90deg,#e11d48,#f43f5e); }
[data-theme="dark"] .kpi-blue   { background:#1e3a5f; color:#60a5fa; }
[data-theme="dark"] .kpi-violet { background:#2e1065; color:#a78bfa; }
[data-theme="dark"] .kpi-green  { background:#064e3b; color:#34d399; }
[data-theme="dark"] .kpi-amber  { background:#451a03; color:#fbbf24; }
[data-theme="dark"] .kpi-teal   { background:#083344; color:#67e8f9; }
[data-theme="dark"] .kpi-rose   { background:#4c0519; color:#fb7185; }

.ed-kpi-trend {
  font-size: .65rem; font-weight: 700; padding: .18rem .5rem;
  border-radius: 50px; display: inline-flex; align-items: center; gap: .25rem;
}
.ed-kpi-trend.up   { background:#d1fae5; color:#059669; }
.ed-kpi-trend.down { background:#fee2e2; color:#dc2626; }
.ed-kpi-trend.neu  { background:var(--bg-tertiary); color:var(--text-muted); }
[data-theme="dark"] .ed-kpi-trend.up   { background:#064e3b; color:#6ee7b7; }
[data-theme="dark"] .ed-kpi-trend.down { background:#7f1d1d; color:#fca5a5; }

.ed-kpi-num   { font-size: 2rem; font-weight: 700; color: var(--text-primary); line-height: 1; font-family: var(--font-heading); }
.ed-kpi-label { font-size: .73rem; color: var(--text-muted); font-weight: 500; }

/* Two-column body */
.ed-body {
  display: grid; grid-template-columns: 1fr 360px;
  gap: 1.25rem; align-items: start;
}
@media (max-width: 1100px) { .ed-body { grid-template-columns: 1fr; } }

/* Card */
.ed-card {
  background: var(--bg-secondary); border: 1px solid var(--border-color);
  border-radius: 14px; margin-bottom: 1.25rem; overflow: hidden;
}
.ed-card-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.1rem 1.3rem; border-bottom: 1px solid var(--border-color);
  gap: .75rem; flex-wrap: wrap;
}
.ed-card-head h2 {
  font-size: .9rem; font-weight: 700; color: var(--text-primary);
  font-family: var(--font-heading); margin: 0;
  display: flex; align-items: center; gap: .5rem;
}
.ed-card-head h2 i { color: var(--primary-color); font-size: .85rem; }
.ed-card-body { padding: 1.1rem 1.3rem; }

.ed-view-all {
  font-size: .75rem; font-weight: 600; color: var(--primary-color);
  text-decoration: none; display: flex; align-items: center; gap: .3rem;
  transition: gap .15s;
}
.ed-view-all:hover { gap: .5rem; }

/* Job row */
.ed-job-row {
  display: flex; align-items: center; gap: .85rem;
  padding: .8rem 0; border-bottom: 1px solid var(--border-color);
  transition: background .12s;
}
.ed-job-row:last-child { border-bottom: none; padding-bottom: 0; }

.ed-job-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.ed-job-dot.published { background:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.18); }
.ed-job-dot.draft     { background:#94a3b8; }
.ed-job-dot.closed    { background:#ef4444; }
.ed-job-dot.expired   { background:#f59e0b; }

.ed-job-info { flex: 1; min-width: 0; }
.ed-job-title { font-size: .86rem; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: .18rem; }
.ed-job-meta  { font-size: .73rem; color: var(--text-muted); display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.ed-job-meta span { display: flex; align-items: center; gap: .25rem; }

.ed-badge {
  display: inline-flex; align-items: center; gap: .25rem;
  padding: .2rem .55rem; border-radius: 50px;
  font-size: .64rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; flex-shrink: 0;
}
.ed-badge.published { background:#d1fae5; color:#065f46; }
.ed-badge.expired   { background:#fef3c7; color:#92400e; }
.ed-badge.draft     { background:#f1f5f9; color:#475569; }
.ed-badge.full_time { background:#dbeafe; color:#1e40af; }
.ed-badge.part_time { background:#ede9fe; color:#5b21b6; }
[data-theme="dark"] .ed-badge.published { background:#064e3b; color:#6ee7b7; }
[data-theme="dark"] .ed-badge.expired   { background:#451a03; color:#fcd34d; }

.ed-job-apps { font-size: .78rem; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: .3rem; flex-shrink: 0; }

/* Interview row */
.ed-iv-row {
  display: flex; align-items: flex-start; gap: .85rem;
  padding: .85rem 0; border-bottom: 1px solid var(--border-color);
}
.ed-iv-row:last-child { border-bottom: none; }
.ed-iv-date-block {
  width: 44px; flex-shrink: 0; text-align: center;
  background: var(--gradient-primary); border-radius: 9px; padding: .45rem .2rem; color: #fff;
}
.ed-iv-date-block .day   { font-size: 1.1rem; font-weight: 700; line-height: 1; font-family: var(--font-heading); }
.ed-iv-date-block .month { font-size: .6rem; font-weight: 600; opacity: .85; text-transform: uppercase; letter-spacing: .04em; }
.ed-iv-info { flex: 1; min-width: 0; }
.ed-iv-title { font-size: .85rem; font-weight: 600; color: var(--text-primary); margin-bottom: .2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ed-iv-meta  { font-size: .73rem; color: var(--text-muted); display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.ed-iv-meta span { display: flex; align-items: center; gap: .25rem; }

.ed-iv-status {
  display: inline-flex; align-items: center; gap: .25rem;
  padding: .2rem .55rem; border-radius: 50px;
  font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; flex-shrink: 0;
}
.ed-iv-status.scheduled   { background:#cffafe; color:#164e63; }
.ed-iv-status.completed   { background:#d1fae5; color:#065f46; }
.ed-iv-status.cancelled   { background:#f1f5f9; color:#475569; }
.ed-iv-status.rescheduled { background:#fef3c7; color:#92400e; }
[data-theme="dark"] .ed-iv-status.scheduled  { background:#083344; color:#67e8f9; }
[data-theme="dark"] .ed-iv-status.completed  { background:#064e3b; color:#6ee7b7; }
[data-theme="dark"] .ed-iv-status.rescheduled{ background:#451a03; color:#fcd34d; }

.ed-iv-countdown {
  font-size: .68rem; font-weight: 700; padding: .18rem .5rem;
  border-radius: 50px; display: inline-flex; align-items: center; gap: .25rem; white-space: nowrap;
}
.ed-iv-countdown.soon   { background:rgba(245,158,11,.12); color:#d97706; border:1px solid rgba(245,158,11,.3); }
.ed-iv-countdown.normal { background:rgba(6,182,212,.1); color:#0891b2; border:1px solid rgba(6,182,212,.25); }
.ed-iv-countdown.past   { background:var(--bg-tertiary); color:var(--text-muted); border:1px solid var(--border-color); }
[data-theme="dark"] .ed-iv-countdown.soon   { color:#fbbf24; }
[data-theme="dark"] .ed-iv-countdown.normal { color:#67e8f9; }

/* Funnel */
.ed-funnel { display: flex; flex-direction: column; gap: .55rem; }
.ed-funnel-row { display: flex; align-items: center; gap: .75rem; }
.ed-funnel-label { font-size: .78rem; color: var(--text-secondary); width: 90px; flex-shrink: 0; display: flex; align-items: center; gap: .35rem; }
.ed-funnel-label i { font-size: .68rem; width: 12px; text-align: center; }
.ed-funnel-bar-wrap { flex: 1; height: 8px; background: var(--bg-tertiary); border-radius: 4px; overflow: hidden; }
.ed-funnel-fill { height: 100%; border-radius: 4px; transition: width .6s ease; }
.ed-funnel-count { font-size: .78rem; font-weight: 700; color: var(--text-primary); width: 24px; text-align: right; flex-shrink: 0; }
.ed-funnel-row.pending     .ed-funnel-fill { background:#f59e0b; }
.ed-funnel-row.reviewed    .ed-funnel-fill { background:#3b82f6; }
.ed-funnel-row.shortlisted .ed-funnel-fill { background:#8b5cf6; }
.ed-funnel-row.rejected    .ed-funnel-fill { background:#ef4444; }
.ed-funnel-row.accepted    .ed-funnel-fill { background:#10b981; }
.ed-funnel-row.pending     .ed-funnel-label i { color:#d97706; }
.ed-funnel-row.reviewed    .ed-funnel-label i { color:#2563eb; }
.ed-funnel-row.shortlisted .ed-funnel-label i { color:#7c3aed; }
.ed-funnel-row.rejected    .ed-funnel-label i { color:#dc2626; }
.ed-funnel-row.accepted    .ed-funnel-label i { color:#059669; }

/* Quick actions */
.ed-actions-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
.ed-action-btn {
  display: flex; flex-direction: column; align-items: center; gap: .45rem;
  padding: .85rem .5rem; border-radius: 11px;
  border: 1.5px solid var(--border-color); background: var(--bg-primary);
  color: var(--text-secondary); cursor: pointer;
  font-family: var(--font-primary); font-size: .75rem; font-weight: 600;
  text-decoration: none; text-align: center; transition: all .18s;
}
.ed-action-btn i { font-size: 1.15rem; color: var(--primary-color); }
.ed-action-btn:hover { border-color: var(--primary-color); background: var(--bg-secondary); color: var(--primary-color); transform: translateY(-2px); box-shadow: var(--shadow-sm); }

/* Expiry list */
.ed-expiry-row {
  display: flex; align-items: center; gap: .75rem;
  padding: .65rem 0; border-bottom: 1px solid var(--border-color);
}
.ed-expiry-row:last-child { border-bottom: none; }
.ed-expiry-days {
  width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: .68rem; font-weight: 700; text-align: center; line-height: 1.2;
}
.ed-expiry-days.critical { background:#fee2e2; color:#dc2626; }
.ed-expiry-days.warn     { background:#fef3c7; color:#d97706; }
.ed-expiry-days.ok       { background:#d1fae5; color:#059669; }
[data-theme="dark"] .ed-expiry-days.critical { background:#7f1d1d; color:#fca5a5; }
[data-theme="dark"] .ed-expiry-days.warn     { background:#451a03; color:#fcd34d; }
[data-theme="dark"] .ed-expiry-days.ok       { background:#064e3b; color:#6ee7b7; }
.ed-expiry-info { flex: 1; min-width: 0; }
.ed-expiry-title { font-size: .83rem; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: .1rem; }
.ed-expiry-sub   { font-size: .7rem; color: var(--text-muted); }

/* Skeleton */
.ed-skel {
  background: linear-gradient(110deg,var(--bg-tertiary) 8%,var(--bg-secondary) 18%,var(--bg-tertiary) 33%);
  background-size: 200% 100%;
  animation: ed-shimmer 1.4s linear infinite; border-radius: 7px;
}
@keyframes ed-shimmer { 0%{background-position:-200% 0}100%{background-position:200% 0} }

/* Empty */
.ed-empty { text-align: center; padding: 2rem 1rem; }
.ed-empty i { font-size: 1.75rem; color: var(--text-muted); display: block; margin-bottom: .65rem; }
.ed-empty p { font-size: .82rem; color: var(--text-muted); margin: 0; }

/* Responsive */
@media (max-width: 640px) {
  .ed-page { padding: 1rem 1rem 5rem; }
  .ed-kpi-grid { grid-template-columns: repeat(2,1fr); }
  .ed-kpi-num  { font-size: 1.6rem; }
}
@media (max-width: 400px) { .ed-kpi-grid { grid-template-columns: 1fr; } }
</style>


<!-- ERROR BANNER -->
<div class="ed-error-banner" id="ed-error">
  <i class="fas fa-exclamation-circle"></i>
  <span id="ed-error-msg">Some data failed to load.</span>
  <button class="ed-retry-btn" id="ed-retry"><i class="fas fa-redo" style="margin-right:.3rem;"></i>Retry</button>
</div>

<!-- URGENT INTERVIEW ALERT -->
<div class="ed-alert-banner" id="ed-alert">
  <i class="fas fa-bell"></i>
  <div>
    <strong id="ed-alert-text"></strong><br>
    <span id="ed-alert-sub"></span>
  </div>
</div>

<!-- PAGE HEADER -->
<div class="ed-header">
  <div class="ed-header-left">
    <h1><span class="ed-hi-icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard</h1>
    <p id="ed-greeting">Loading your dashboard…</p>
  </div>
  <div class="ed-header-right">
    <span class="ed-last-updated" id="ed-last-updated" style="display:none;">
      <i class="fas fa-sync-alt"></i><span id="ed-lut-text"></span>
    </span>
    <button class="ed-refresh-btn" id="ed-refresh-btn">
      <i class="fas fa-sync-alt"></i> Refresh
    </button>
  </div>
</div>

<!-- KPI GRID -->
<div class="ed-kpi-grid" id="ed-kpi-grid">
  <div class="ed-kpi"><div class="ed-kpi-top"><div class="ed-skel" style="width:36px;height:36px;border-radius:9px;"></div><div class="ed-skel" style="width:50px;height:18px;border-radius:50px;"></div></div><div class="ed-skel" style="width:55px;height:30px;margin-top:.5rem;"></div><div class="ed-skel" style="width:80px;height:10px;margin-top:.4rem;"></div></div>
  <div class="ed-kpi"><div class="ed-kpi-top"><div class="ed-skel" style="width:36px;height:36px;border-radius:9px;"></div><div class="ed-skel" style="width:50px;height:18px;border-radius:50px;"></div></div><div class="ed-skel" style="width:55px;height:30px;margin-top:.5rem;"></div><div class="ed-skel" style="width:80px;height:10px;margin-top:.4rem;"></div></div>
  <div class="ed-kpi"><div class="ed-kpi-top"><div class="ed-skel" style="width:36px;height:36px;border-radius:9px;"></div><div class="ed-skel" style="width:50px;height:18px;border-radius:50px;"></div></div><div class="ed-skel" style="width:55px;height:30px;margin-top:.5rem;"></div><div class="ed-skel" style="width:80px;height:10px;margin-top:.4rem;"></div></div>
  <div class="ed-kpi"><div class="ed-kpi-top"><div class="ed-skel" style="width:36px;height:36px;border-radius:9px;"></div><div class="ed-skel" style="width:50px;height:18px;border-radius:50px;"></div></div><div class="ed-skel" style="width:55px;height:30px;margin-top:.5rem;"></div><div class="ed-skel" style="width:80px;height:10px;margin-top:.4rem;"></div></div>
  <div class="ed-kpi"><div class="ed-kpi-top"><div class="ed-skel" style="width:36px;height:36px;border-radius:9px;"></div><div class="ed-skel" style="width:50px;height:18px;border-radius:50px;"></div></div><div class="ed-skel" style="width:55px;height:30px;margin-top:.5rem;"></div><div class="ed-skel" style="width:80px;height:10px;margin-top:.4rem;"></div></div>
</div>

<!-- BODY -->
<div class="ed-body">

  <!-- LEFT -->
  <div class="ed-left">

    <div class="ed-card">
      <div class="ed-card-head">
        <h2><i class="fas fa-briefcase"></i> Active Job Listings</h2>
        <a href="/employer/jobs" class="ed-view-all">View all <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="ed-card-body" id="ed-jobs-list">
        <div class="ed-skel" style="height:54px;margin-bottom:8px;border-radius:8px;"></div>
        <div class="ed-skel" style="height:54px;margin-bottom:8px;border-radius:8px;"></div>
        <div class="ed-skel" style="height:54px;border-radius:8px;"></div>
      </div>
    </div>

    <div class="ed-card">
      <div class="ed-card-head">
        <h2><i class="fas fa-calendar-check"></i> Upcoming Interviews</h2>
        <a href="/employer/interviews" class="ed-view-all">View all <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="ed-card-body" id="ed-interviews-list">
        <div class="ed-skel" style="height:60px;margin-bottom:8px;border-radius:8px;"></div>
        <div class="ed-skel" style="height:60px;border-radius:8px;"></div>
      </div>
    </div>

    <div class="ed-card">
      <div class="ed-card-head">
        <h2><i class="fas fa-filter"></i> Application Pipeline</h2>
        <a href="/employer/applications" class="ed-view-all">Manage <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="ed-card-body" id="ed-funnel-body">
        <div class="ed-skel" style="height:14px;margin-bottom:10px;"></div>
        <div class="ed-skel" style="height:14px;margin-bottom:10px;width:80%;"></div>
        <div class="ed-skel" style="height:14px;margin-bottom:10px;width:60%;"></div>
        <div class="ed-skel" style="height:14px;width:40%;"></div>
      </div>
    </div>

  </div>

  <!-- SIDEBAR -->
  <div class="ed-sidebar">

    <div class="ed-card">
      <div class="ed-card-head"><h2><i class="fas fa-bolt"></i> Quick Actions</h2></div>
      <div class="ed-card-body">
        <div class="ed-actions-grid">
          <a href="/employer/jobs/create"  class="ed-action-btn"><i class="fas fa-plus-circle"></i>Post a Job</a>
          <a href="/employer/applications" class="ed-action-btn"><i class="fas fa-users"></i>Applications</a>
          <a href="/employer/interviews"   class="ed-action-btn"><i class="fas fa-calendar-alt"></i>Interviews</a>
          <a href="/employer/settings"     class="ed-action-btn"><i class="fas fa-cog"></i>Settings</a>
        </div>
      </div>
    </div>

    <div class="ed-card">
      <div class="ed-card-head">
        <h2><i class="fas fa-hourglass-half"></i> Expiring Soon</h2>
        <a href="/employer/jobs" class="ed-view-all">All jobs <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="ed-card-body" id="ed-expiry-list">
        <div class="ed-skel" style="height:42px;margin-bottom:8px;border-radius:8px;"></div>
        <div class="ed-skel" style="height:42px;border-radius:8px;"></div>
      </div>
    </div>

    <div class="ed-card">
      <div class="ed-card-head"><h2><i class="fas fa-chart-pie"></i> Interview Breakdown</h2></div>
      <div class="ed-card-body" id="ed-iv-breakdown">
        <div class="ed-skel" style="height:80px;border-radius:8px;"></div>
      </div>
    </div>

  </div>
</div>


<script>
(function () {
  'use strict';

  /* Endpoints — all return { success, message, data } */
  const API = {
    interviews  : '/api/interviews',         // GET → data: [ ...interviews (UTC timestamps) ]
    featuredJobs: '/api/jobs/featured',       // GET → data: [ ...jobs ]
    appStats    : '/api/applications/stats',  // GET → data: { total_applications, pending, reviewed, shortlisted, rejected, accepted, by_job }
    employerJobs: '/api/employer/jobs',       // GET → data: [ ...] or paginated
    settings    : '/api/employer/settings',   // GET → data: { user_info, profile: { profile, industry, stats } }
  };

  /* ── UTC helpers ──────────────────────────────── */
  // Server stores all datetimes as UTC strings "YYYY-MM-DD HH:MM:SS"
  // Append 'Z' to force correct UTC parsing in all browsers
  function utc(str) {
    if (!str) return null;
    const d = new Date(str.trim().replace(' ', 'T') + 'Z');
    return isNaN(d) ? null : d;
  }

  const fmtDate  = s => { const d=utc(s); return d ? d.toLocaleDateString(undefined,{day:'numeric',month:'short',year:'numeric'}) : '—'; };
  const fmtTime  = s => { const d=utc(s); return d ? d.toLocaleTimeString(undefined,{hour:'2-digit',minute:'2-digit'}) : ''; };
  const dayNum   = s => { const d=utc(s); return d ? d.getDate() : '?'; };
  const shortMon = s => { const d=utc(s); return d ? d.toLocaleDateString(undefined,{month:'short'}).toUpperCase() : ''; };

  function daysUntil(s) {
    const d = utc(s);
    if (!d) return null;
    return Math.ceil((d - Date.now()) / 86400000);
  }
  function hoursUntil(s) {
    const d = utc(s);
    if (!d) return null;
    return (d - Date.now()) / 3600000;
  }
  function countdown(s) {
    const h = hoursUntil(s);
    if (h === null) return null;
    if (h < 0)  return { label:'Past',              cls:'past' };
    if (h < 2)  return { label:'Starting soon!',    cls:'soon' };
    if (h < 24) return { label:'Today '+fmtTime(s), cls:'soon' };
    const d = Math.ceil(h/24);
    return { label:'In '+d+' day'+(d>1?'s':''), cls: d<=3?'soon':'normal' };
  }

  const esc = s => String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  const cap = s => s ? s.charAt(0).toUpperCase()+s.slice(1) : '';

  const JOB_TYPE = {full_time:'Full-time',part_time:'Part-time',contract:'Contract',internship:'Internship'};
  const IV_TYPE  = {phone:'Phone',video:'Video',in_person:'In Person',technical:'Technical',panel:'Panel'};
  const IV_ICON  = {phone:'fa-phone',video:'fa-video',in_person:'fa-building',technical:'fa-laptop-code',panel:'fa-users'};

  function fmtSalary(min,max,cur) {
    const c=cur||'USD', f=n=>n>=1000?c+' '+(n/1000).toFixed(0)+'k':c+' '+n;
    if(!min&&!max) return null;
    if(!min) return f(max); if(!max) return f(min);
    return f(min)+' – '+f(max);
  }

  function greeting() {
    const h=new Date().getHours();
    return h<12?'Good morning':h<17?'Good afternoon':'Good evening';
  }

  /* Generic fetch */
  async function apiFetch(url) {
    const res=await fetch(url); const json=await res.json();
    if (!json.success) throw new Error(json.message||'Failed');
    return json;
  }

  function extractArr(data) {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    if (Array.isArray(data.data)) return data.data;
    if (Array.isArray(data.jobs)) return data.jobs;
    return [];
  }

  /* DOM */
  const $ = id => document.getElementById(id);

  /* ── Load all ──────────────────────────────────── */
  async function loadAll() {
    setSpinner(true);
    $('ed-error').classList.remove('show');

    const [ivR,jobR,appR,empR,setR] = await Promise.allSettled([
      apiFetch(API.interviews),
      apiFetch(API.featuredJobs),
      apiFetch(API.appStats),
      apiFetch(API.employerJobs),
      apiFetch(API.settings),
    ]);

    const iv       = ivR.status  === 'fulfilled' ? (Array.isArray(ivR.value.data) ? ivR.value.data : []) : [];
    const featured = jobR.status === 'fulfilled' ? extractArr(jobR.value.data) : [];
    const appStats = appR.status === 'fulfilled' ? (appR.value.data||{}) : {};
    const empJobs  = empR.status === 'fulfilled' ? extractArr(empR.value.data) : [];
    const settings = setR.status === 'fulfilled' ? setR.value.data : null;

    const jobs = empJobs.length ? empJobs : featured;

    const failed = [ivR,jobR,appR].filter(r=>r.status==='rejected').length;
    if (failed) {
      $('ed-error-msg').textContent = failed+' data source'+(failed>1?'s':'')+' failed. Some panels may be incomplete.';
      $('ed-error').classList.add('show');
    }

    /* Derive profile stats — settings has nested shape */
    const profileStats = settings?.profile?.stats || settings?.stats || {};
    const companyName  = settings?.profile?.profile?.company_name || settings?.profile?.company_name || '';

    $('ed-greeting').textContent = greeting()+(companyName?', '+companyName:'')+' — here\'s what\'s happening today';

    renderKPIs(jobs, iv, appStats, profileStats);
    renderJobs(jobs);
    renderInterviews(iv);
    renderFunnel(appStats);
    renderExpiry(jobs);
    renderIvBreakdown(iv);
    renderAlertBanner(iv);

    $('ed-lut-text').textContent = ' Updated '+new Date().toLocaleTimeString(undefined,{hour:'2-digit',minute:'2-digit'});
    $('ed-last-updated').style.display = 'flex';
    setSpinner(false);
  }

  /* ── KPIs ─────────────────────────────────────── */
  function renderKPIs(jobs, iv, appStats, ps) {
    const activeJobs   = jobs.filter(j=>j.status==='published').length;
    const totalJobs    = ps.total_jobs          ?? jobs.length;
    const totalApps    = ps.total_applications  ?? appStats.total_applications ?? 0;
    const recent30     = ps.recent_applications_30d ?? 0;
    const unviewed     = ps.unviewed_applications   ?? 0;
    const scheduledIvs = iv.filter(i=>i.status==='scheduled'||i.status==='rescheduled').length;
    const expiringSoon = jobs.filter(j=>{ const d=daysUntil(j.expires_at); return d!==null&&d>=0&&d<=7; }).length;

    const kpis = [
      { icon:'fa-briefcase',      cls:'kpi-blue',   num:activeJobs,    label:'Active Jobs',            trend:{ label:totalJobs+' total', dir:'neu' } },
      { icon:'fa-users',          cls:'kpi-violet',  num:totalApps,     label:'Total Applications',     trend:recent30?{ label:'+'+recent30+' (30d)', dir:'up' }:null },
      { icon:'fa-bell',           cls:'kpi-amber',   num:unviewed,      label:'Awaiting Review',        trend:unviewed>0?{ label:'Needs attention', dir:'down' }:{ label:'All reviewed', dir:'up' } },
      { icon:'fa-calendar-check', cls:'kpi-teal',    num:scheduledIvs,  label:'Scheduled Interviews',   trend:null },
      { icon:'fa-hourglass-half', cls:'kpi-rose',    num:expiringSoon,  label:'Expiring in 7 Days',     trend:expiringSoon>0?{ label:'Needs renewing', dir:'down' }:{ label:'All clear', dir:'up' } },
    ];

    $('ed-kpi-grid').innerHTML = kpis.map(k=>`
      <div class="ed-kpi">
        <div class="ed-kpi-top">
          <div class="ed-kpi-icon ${k.cls}"><i class="fas ${k.icon}"></i></div>
          ${k.trend?`<span class="ed-kpi-trend ${k.trend.dir}">${k.trend.dir==='up'?'<i class="fas fa-arrow-up"></i>':k.trend.dir==='down'?'<i class="fas fa-arrow-down"></i>':'<i class="fas fa-minus"></i>'}${esc(k.trend.label)}</span>`:'<span></span>'}
        </div>
        <div class="ed-kpi-num">${k.num}</div>
        <div class="ed-kpi-label">${k.label}</div>
      </div>`).join('');
  }

  /* ── Jobs list ────────────────────────────────── */
  function renderJobs(jobs) {
    const sorted = [...jobs].sort((a,b)=>{
      const o={published:0,draft:1,closed:2}; return (o[a.status]??3)-(o[b.status]??3);
    }).slice(0,6);

    if (!sorted.length) {
      $('ed-jobs-list').innerHTML=`<div class="ed-empty"><i class="fas fa-briefcase"></i><p>No jobs posted yet. <a href="/employer/jobs/create" style="color:var(--primary-color);">Post your first job →</a></p></div>`;
      return;
    }

    $('ed-jobs-list').innerHTML = sorted.map(j=>{
      const salary  = fmtSalary(j.salary_min,j.salary_max,j.currency);
      const expDays = daysUntil(j.expires_at);
      const expired = expDays!==null&&expDays<0;
      const expCls  = !expired&&expDays!==null&&expDays<=2?'color:#e11d48;':!expired&&expDays!==null&&expDays<=7?'color:#d97706;':'';
      const expTxt  = expired?' · Expired':expDays!==null&&expDays<=7?` · Expires in ${expDays}d`:expDays!==null?` · Expires ${fmtDate(j.expires_at)}`:'';
      const dotCls  = expired?'expired':j.status;
      const badgeCls= expired?'expired':j.status==='published'?'published':'draft';
      const badgeTxt= expired?'Expired':cap(j.status);
      const apps    = j.applications_count??0;
      return `
      <div class="ed-job-row">
        <div class="ed-job-dot ${dotCls}"></div>
        <div class="ed-job-info">
          <div class="ed-job-title">${esc(j.title)}</div>
          <div class="ed-job-meta">
            <span><i class="fas fa-tag" style="font-size:.6rem;color:var(--primary-light);"></i>${JOB_TYPE[j.job_type]||cap(j.job_type)}</span>
            ${j.is_remote?'<span><i class="fas fa-wifi" style="font-size:.6rem;color:#0891b2;"></i>Remote</span>':''}
            ${salary?`<span><i class="fas fa-dollar-sign" style="font-size:.6rem;color:#059669;"></i>${esc(salary)}</span>`:''}
            ${expTxt?`<span style="${expCls}">${expTxt}</span>`:''}
          </div>
        </div>
        <span class="ed-badge ${badgeCls}">${badgeTxt}</span>
        <span class="ed-job-apps"><i class="fas fa-users" style="font-size:.62rem;"></i>${apps}</span>
      </div>`;
    }).join('');
  }

  /* ── Interviews ───────────────────────────────── */
  function renderInterviews(iv) {
    const upcoming = iv
      .filter(i=>(i.status==='scheduled'||i.status==='rescheduled')&&hoursUntil(i.scheduled_at)>=0)
      .sort((a,b)=>utc(a.scheduled_at)-utc(b.scheduled_at))
      .slice(0,5);

    if (!upcoming.length) {
      $('ed-interviews-list').innerHTML=`<div class="ed-empty"><i class="fas fa-calendar-check"></i><p>No upcoming interviews scheduled.</p></div>`;
      return;
    }

    $('ed-interviews-list').innerHTML = upcoming.map(i=>{
      const cd=countdown(i.scheduled_at);
      return `
      <div class="ed-iv-row">
        <div class="ed-iv-date-block">
          <div class="day">${dayNum(i.scheduled_at)}</div>
          <div class="month">${shortMon(i.scheduled_at)}</div>
        </div>
        <div class="ed-iv-info">
          <div class="ed-iv-title">
            Application #${i.application_id}
            ${i.meeting_link?`<a href="${esc(i.meeting_link)}" target="_blank" rel="noopener" style="margin-left:.4rem;font-size:.68rem;color:#0891b2;font-weight:600;text-decoration:none;" onclick="event.stopPropagation()"><i class="fas fa-video"></i> Join</a>`:''}
          </div>
          <div class="ed-iv-meta">
            <span><i class="fas ${IV_ICON[i.interview_type]||'fa-calendar'}"></i>${IV_TYPE[i.interview_type]||cap(i.interview_type)}</span>
            <span><i class="fas fa-clock"></i>${fmtTime(i.scheduled_at)}</span>
            ${i.duration_minutes?`<span><i class="fas fa-hourglass-half"></i>${i.duration_minutes}m</span>`:''}
          </div>
          ${cd?`<div style="margin-top:.3rem;"><span class="ed-iv-countdown ${cd.cls}"><i class="fas fa-stopwatch"></i>${cd.label}</span></div>`:''}
        </div>
        <span class="ed-iv-status ${i.status}">${cap(i.status)}</span>
      </div>`;
    }).join('');
  }

  /* ── Application funnel ───────────────────────── */
  function renderFunnel(stats) {
    const stages=[
      {key:'pending',    label:'Pending',    icon:'fa-clock'},
      {key:'reviewed',   label:'Reviewed',   icon:'fa-eye'},
      {key:'shortlisted',label:'Shortlisted',icon:'fa-star'},
      {key:'rejected',   label:'Rejected',   icon:'fa-times-circle'},
      {key:'accepted',   label:'Accepted',   icon:'fa-check-circle'},
    ];
    const total=stats.total_applications||0;
    const hasAny=stages.some(s=>(stats[s.key]||0)>0);

    if (!hasAny&&!total) {
      $('ed-funnel-body').innerHTML=`<div class="ed-empty"><i class="fas fa-inbox"></i><p>No applications received yet.</p></div>`;
      return;
    }

    const funnelRows=stages.map(s=>{
      const count=stats[s.key]||0;
      const pct=total?Math.round(count/total*100):0;
      return `<div class="ed-funnel-row ${s.key}">
        <div class="ed-funnel-label"><i class="fas ${s.icon}"></i>${s.label}</div>
        <div class="ed-funnel-bar-wrap"><div class="ed-funnel-fill" style="width:${pct}%"></div></div>
        <div class="ed-funnel-count">${count}</div>
      </div>`;
    }).join('');

    const byJobRows=(stats.by_job||[]).filter(bj=>(bj.count||bj.application_count)>0).slice(0,4).map(bj=>`
      <div style="display:flex;align-items:center;gap:.6rem;padding:.38rem 0;border-bottom:1px solid var(--border-color);">
        <div style="flex:1;font-size:.78rem;color:var(--text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(bj.job_title||bj.title||'Job #'+(bj.job_id||bj.id))}</div>
        <div style="font-size:.78rem;font-weight:700;color:var(--primary-color);">${bj.count||bj.application_count||0}</div>
      </div>`).join('');

    $('ed-funnel-body').innerHTML = `
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
        <span style="font-size:.8rem;color:var(--text-muted);">Total applications</span>
        <span style="font-size:1.2rem;font-weight:700;color:var(--text-primary);font-family:var(--font-heading);">${total}</span>
      </div>
      <div class="ed-funnel">${funnelRows}</div>
      ${byJobRows?`<div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border-color);"><div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:.55rem;">By Job</div>${byJobRows}</div>`:''}`;
  }

  /* ── Expiry sidebar ───────────────────────────── */
  function renderExpiry(jobs) {
    const expiring=jobs
      .filter(j=>j.status==='published')
      .map(j=>({...j,_d:daysUntil(j.expires_at)}))
      .filter(j=>j._d!==null&&j._d>=0&&j._d<=14)
      .sort((a,b)=>a._d-b._d).slice(0,5);

    if (!expiring.length) {
      $('ed-expiry-list').innerHTML=`<div class="ed-empty" style="padding:1rem;"><i class="fas fa-check-circle" style="color:#10b981;"></i><p>No jobs expiring in 14 days.</p></div>`;
      return;
    }
    $('ed-expiry-list').innerHTML=expiring.map(j=>{
      const cls=j._d<=2?'critical':j._d<=5?'warn':'ok';
      const lbl=j._d===0?'Today':j._d+'d';
      return `
      <div class="ed-expiry-row">
        <div class="ed-expiry-days ${cls}">${lbl}</div>
        <div class="ed-expiry-info">
          <div class="ed-expiry-title">${esc(j.title)}</div>
          <div class="ed-expiry-sub">${fmtDate(j.expires_at)}</div>
        </div>
        <a href="/employer/jobs/edit/${j.id}" style="font-size:.7rem;color:var(--primary-color);font-weight:600;white-space:nowrap;text-decoration:none;flex-shrink:0;">Renew</a>
      </div>`;
    }).join('');
  }

  /* ── Interview breakdown sidebar ──────────────── */
  function renderIvBreakdown(iv) {
    if (!iv.length) {
      $('ed-iv-breakdown').innerHTML=`<div class="ed-empty" style="padding:1rem;"><i class="fas fa-calendar-times"></i><p>No interview data yet.</p></div>`;
      return;
    }
    const typeCounts={}, statusCounts={};
    iv.forEach(i=>{
      typeCounts[i.interview_type]=(typeCounts[i.interview_type]||0)+1;
      statusCounts[i.status]=(statusCounts[i.status]||0)+1;
    });
    const total=iv.length;
    const typeRows=Object.entries(typeCounts).sort((a,b)=>b[1]-a[1]).map(([t,c])=>{
      const pct=Math.round(c/total*100);
      return `<div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem;">
        <i class="fas ${IV_ICON[t]||'fa-calendar'}" style="width:14px;text-align:center;color:var(--primary-light);font-size:.78rem;"></i>
        <span style="font-size:.78rem;color:var(--text-secondary);flex:1;">${IV_TYPE[t]||cap(t)}</span>
        <div style="width:70px;height:6px;background:var(--bg-tertiary);border-radius:3px;overflow:hidden;"><div style="height:100%;width:${pct}%;background:var(--gradient-primary);border-radius:3px;"></div></div>
        <span style="font-size:.75rem;font-weight:700;color:var(--text-primary);width:20px;text-align:right;">${c}</span>
      </div>`;
    }).join('');

    const statusPills=Object.entries(statusCounts).map(([s,c])=>
      `<span class="ed-iv-status ${s}" style="font-size:.63rem;">${cap(s)}: ${c}</span>`
    ).join('');

    $('ed-iv-breakdown').innerHTML=typeRows+`<div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.85rem;padding-top:.85rem;border-top:1px solid var(--border-color);">${statusPills}</div>`;
  }

  /* ── Urgent interview alert ───────────────────── */
  function renderAlertBanner(iv) {
    const urgent=iv
      .filter(i=>(i.status==='scheduled'||i.status==='rescheduled'))
      .filter(i=>{ const h=hoursUntil(i.scheduled_at); return h!==null&&h>=0&&h<=24; })
      .sort((a,b)=>utc(a.scheduled_at)-utc(b.scheduled_at));

    if (!urgent.length) { $('ed-alert').classList.remove('show'); return; }
    const next=urgent[0];
    const h=Math.max(0,Math.round(hoursUntil(next.scheduled_at)));
    $('ed-alert-text').textContent=`You have ${urgent.length} interview${urgent.length>1?'s':''} in the next 24 hours`;
    $('ed-alert-sub').textContent=`Nearest: ${IV_TYPE[next.interview_type]||cap(next.interview_type||'')} interview at ${fmtTime(next.scheduled_at)} local time (${h===0?'starting now':h+'h away'})`;
    $('ed-alert').classList.add('show');
  }

  /* ── Helpers ──────────────────────────────────── */
  function setSpinner(on) {
    const btn=$('ed-refresh-btn');
    btn.classList.toggle('spinning',on); btn.disabled=on;
    btn.innerHTML=on?'<i class="fas fa-sync-alt"></i> Loading…':'<i class="fas fa-sync-alt"></i> Refresh';
  }

  $('ed-refresh-btn').addEventListener('click', loadAll);
  $('ed-retry').addEventListener('click', loadAll);
  setInterval(loadAll, 5*60*1000); // auto-refresh every 5 min

  loadAll();
})();
</script>

</div>

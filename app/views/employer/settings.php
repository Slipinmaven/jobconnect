<div class="es-page">

<style>
/* ════════════════════════════════════════════
   EMPLOYER SETTINGS PAGE  ·  es-page scope
   Aesthetic: Authoritative editorial —
   deep ink anchors, amber/gold accent,
   structured grid layout, confident spacing.
════════════════════════════════════════════ */

.es-page {
  flex: 1;
  min-height: 100vh;
  background: var(--bg-primary);
  padding: 2rem 2rem 6rem;
  font-family: var(--font-primary);
  overflow-x: hidden;
}

/* ── Hero ─────────────────────────────────── */
.es-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.25rem;
  flex-wrap: wrap;
  margin-bottom: 2rem;
  padding-bottom: 1.75rem;
  border-bottom: 1px solid var(--border-color);
}
.es-hero-left { display: flex; align-items: center; gap: 1.25rem; }

.es-logo-wrap { position: relative; flex-shrink: 0; }
.es-logo {
  width: 76px; height: 76px; border-radius: 14px;
  object-fit: cover;
  border: 2px solid var(--border-color);
  box-shadow: 0 0 0 3px var(--bg-primary), 0 0 0 5px var(--primary-color);
  display: block;
}
.es-logo-placeholder {
  width: 76px; height: 76px; border-radius: 14px;
  background: var(--gradient-primary);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.6rem; font-weight: 700; color: #fff;
  font-family: var(--font-heading);
  box-shadow: 0 0 0 3px var(--bg-primary), 0 0 0 5px var(--primary-color);
}
.es-logo-edit {
  position: absolute; bottom: -4px; right: -4px;
  width: 26px; height: 26px; border-radius: 50%;
  background: var(--gradient-primary); color: #fff;
  border: 2px solid var(--bg-primary);
  display: flex; align-items: center; justify-content: center;
  font-size: .62rem; cursor: pointer;
  transition: transform .2s, box-shadow .2s;
}
.es-logo-edit:hover { transform: scale(1.15); box-shadow: 0 2px 8px rgba(67,97,238,.4); }
#es-logo-input { display: none; }

.es-hero-info h1 {
  font-size: 1.55rem; font-weight: 700;
  color: var(--text-primary); font-family: var(--font-heading);
  margin: 0 0 .2rem;
}
.es-hero-info p { color: var(--text-muted); font-size: .85rem; margin: 0 0 .5rem; }
.es-hero-badges { display: flex; gap: .45rem; flex-wrap: wrap; }
.es-hbadge {
  display: inline-flex; align-items: center; gap: .28rem;
  padding: .2rem .6rem; border-radius: 50px;
  font-size: .68rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: .05em;
}
.es-hbadge.verified   { background: #d1fae5; color: #065f46; }
.es-hbadge.unverified { background: #fef3c7; color: #92400e; }
.es-hbadge.premium    { background: linear-gradient(135deg,#f59e0b,#d97706); color: #fff; }
.es-hbadge.company    { background: #dbeafe; color: #1e40af; }
.es-hbadge.individual { background: #ede9fe; color: #5b21b6; }
[data-theme="dark"] .es-hbadge.verified   { background: #064e3b; color: #6ee7b7; }
[data-theme="dark"] .es-hbadge.unverified { background: #451a03; color: #fcd34d; }
[data-theme="dark"] .es-hbadge.company    { background: #1e3a5f; color: #93c5fd; }
[data-theme="dark"] .es-hbadge.individual { background: #2e1065; color: #c4b5fd; }

.es-completion-wrap { display: flex; flex-direction: column; align-items: flex-end; gap: .4rem; }
.es-completion-label { font-size: .73rem; color: var(--text-muted); font-weight: 500; }
.es-completion-bar   { width: 160px; height: 6px; background: var(--bg-tertiary); border-radius: 3px; overflow: hidden; }
.es-completion-fill  { height: 100%; border-radius: 3px; background: var(--gradient-primary); transition: width .6s ease; }
.es-completion-pct   { font-size: .82rem; font-weight: 700; color: var(--primary-color); }

/* ── Layout ───────────────────────────────── */
.es-layout {
  display: grid;
  grid-template-columns: 220px 1fr;
  gap: 1.5rem;
  align-items: start;
}
@media (max-width: 860px) {
  .es-layout { grid-template-columns: 1fr; }
  .es-sidenav { display: flex; flex-wrap: wrap; flex-direction: row; gap: .4rem; border-right: none; padding-right: 0; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; }
}

/* ── Sidenav ──────────────────────────────── */
.es-sidenav { position: sticky; top: 1rem; border-right: 1px solid var(--border-color); padding-right: 1.25rem; }
.es-nav-item {
  display: flex; align-items: center; gap: .7rem;
  padding: .6rem .85rem; border-radius: 9px;
  font-size: .85rem; font-weight: 500;
  color: var(--text-secondary); cursor: pointer;
  transition: background .15s, color .15s;
  border: none; background: transparent;
  font-family: var(--font-primary); width: 100%;
  text-align: left; margin-bottom: .15rem;
}
.es-nav-item i { width: 16px; text-align: center; font-size: .85rem; color: var(--text-muted); transition: color .15s; }
.es-nav-item:hover { background: var(--bg-secondary); color: var(--text-primary); }
.es-nav-item:hover i { color: var(--primary-color); }
.es-nav-item.active { background: var(--primary-light, #e0e7ff); color: var(--primary-color); font-weight: 600; }
[data-theme="dark"] .es-nav-item.active { background: rgba(67,97,238,.18); }
.es-nav-item.active i { color: var(--primary-color); }
.es-nav-divider { height: 1px; background: var(--border-color); margin: .6rem 0; }
.es-nav-item.danger:hover { background: #fee2e2; color: var(--danger-color); }
[data-theme="dark"] .es-nav-item.danger:hover { background: #7f1d1d; color: #f87171; }
.es-nav-item.danger:hover i { color: var(--danger-color); }

/* ── Sections ─────────────────────────────── */
.es-section { display: none; }
.es-section.active { display: block; animation: es-fadein .22s ease; }
@keyframes es-fadein { from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)} }

/* ── Card ─────────────────────────────────── */
.es-card {
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 14px; padding: 1.5rem;
  margin-bottom: 1.25rem;
  position: relative; overflow: hidden;
}
.es-card-title {
  font-size: .95rem; font-weight: 700;
  color: var(--text-primary); font-family: var(--font-heading);
  margin: 0 0 1.25rem;
  display: flex; align-items: center; gap: .55rem;
}
.es-card-title i { color: var(--primary-color); font-size: .9rem; }

/* Loading overlay */
.es-card-loading {
  position: absolute; inset: 0; background: rgba(255,255,255,.72);
  display: none; align-items: center; justify-content: center;
  border-radius: 14px; z-index: 10;
}
[data-theme="dark"] .es-card-loading { background: rgba(15,23,42,.72); }
.es-card-loading.show { display: flex; }
.es-card-loading i { font-size: 1.4rem; color: var(--primary-color); animation: es-spin 1s linear infinite; }
@keyframes es-spin { to { transform: rotate(360deg); } }

/* ── Stats grid ───────────────────────────── */
.es-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
  gap: .85rem;
  margin-bottom: 1.5rem;
}
.es-stat {
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 11px; padding: .9rem 1rem;
  display: flex; align-items: center; gap: .75rem;
  transition: transform .2s, box-shadow .2s;
}
.es-stat:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.es-stat-icon {
  width: 36px; height: 36px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  font-size: .88rem; flex-shrink: 0;
}
.si-jobs  { background: #dbeafe; color: #2563eb; }
.si-apps  { background: #ede9fe; color: #7c3aed; }
.si-new   { background: #cffafe; color: #0891b2; }
.si-unseen{ background: #fef3c7; color: #d97706; }
[data-theme="dark"] .si-jobs  { background: #1e3a5f; color: #60a5fa; }
[data-theme="dark"] .si-apps  { background: #2e1065; color: #a78bfa; }
[data-theme="dark"] .si-new   { background: #083344; color: #67e8f9; }
[data-theme="dark"] .si-unseen{ background: #451a03; color: #fbbf24; }
.es-stat-num   { font-size: 1.35rem; font-weight: 700; color: var(--text-primary); line-height: 1; font-family: var(--font-heading); }
.es-stat-label { font-size: .68rem; color: var(--text-muted); margin-top: .18rem; }

/* ── Top jobs mini-table ──────────────────── */
.es-top-jobs { display: flex; flex-direction: column; gap: .5rem; }
.es-top-job {
  display: flex; align-items: center; gap: .75rem;
  padding: .65rem .85rem;
  background: var(--bg-primary); border: 1px solid var(--border-color);
  border-radius: 9px; transition: box-shadow .15s;
}
.es-top-job:hover { box-shadow: var(--shadow-sm); }
.es-top-job-rank {
  width: 22px; height: 22px; border-radius: 50%;
  background: var(--gradient-primary); color: #fff;
  font-size: .65rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; font-family: var(--font-heading);
}
.es-top-job-title { flex: 1; font-size: .83rem; font-weight: 500; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.es-top-job-count {
  font-size: .72rem; font-weight: 700; padding: .18rem .55rem;
  border-radius: 50px; background: #ede9fe; color: #5b21b6; flex-shrink: 0;
}
[data-theme="dark"] .es-top-job-count { background: #2e1065; color: #c4b5fd; }

/* Application status mini-bar */
.es-app-status-row {
  display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .75rem;
}
.es-app-pill {
  display: inline-flex; align-items: center; gap: .28rem;
  padding: .25rem .65rem; border-radius: 50px; font-size: .72rem; font-weight: 600;
}
.es-app-pill.pending    { background: #fef3c7; color: #92400e; }
.es-app-pill.reviewed   { background: #dbeafe; color: #1e40af; }
.es-app-pill.shortlisted{ background: #ede9fe; color: #5b21b6; }
.es-app-pill.rejected   { background: #fee2e2; color: #991b1b; }
.es-app-pill.hired      { background: #d1fae5; color: #065f46; }
[data-theme="dark"] .es-app-pill.pending    { background: #451a03; color: #fcd34d; }
[data-theme="dark"] .es-app-pill.reviewed   { background: #1e3a5f; color: #93c5fd; }
[data-theme="dark"] .es-app-pill.shortlisted{ background: #2e1065; color: #c4b5fd; }
[data-theme="dark"] .es-app-pill.rejected   { background: #7f1d1d; color: #fca5a5; }
[data-theme="dark"] .es-app-pill.hired      { background: #064e3b; color: #6ee7b7; }

/* ── Form elements ────────────────────────── */
.es-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.es-form-grid.cols-1 { grid-template-columns: 1fr; }
@media (max-width: 640px) { .es-form-grid { grid-template-columns: 1fr; } }

.es-field { display: flex; flex-direction: column; gap: .38rem; }
.es-field.span2 { grid-column: span 2; }
@media (max-width: 640px) { .es-field.span2 { grid-column: span 1; } }

.es-label { font-size: .72rem; font-weight: 700; color: var(--text-secondary); letter-spacing: .04em; text-transform: uppercase; }
.es-input, .es-select, .es-textarea {
  padding: .65rem .9rem;
  border: 1.5px solid var(--border-color); border-radius: 9px;
  background: var(--bg-primary); color: var(--text-primary);
  font-size: .875rem; font-family: var(--font-primary);
  transition: border-color .2s, box-shadow .2s; width: 100%;
}
.es-input:focus, .es-select:focus, .es-textarea:focus {
  outline: none; border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(67,97,238,.1);
}
.es-input.err { border-color: var(--danger-color); box-shadow: 0 0 0 3px rgba(239,68,68,.1); }
.es-textarea { resize: vertical; min-height: 90px; line-height: 1.55; }
.es-select {
  appearance: none; cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right .8rem center; padding-right: 2.2rem;
}
.es-hint { font-size: .72rem; color: var(--text-muted); }

/* Input with icon */
.es-iicon { position: relative; }
.es-iicon i { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: .8rem; pointer-events: none; }
.es-iicon .es-input { padding-left: 2.4rem; }

/* ── Security rows ────────────────────────── */
.es-sec-row {
  display: flex; align-items: center; gap: 1rem;
  padding: .95rem 0; border-bottom: 1px solid var(--border-color); flex-wrap: wrap;
}
.es-sec-row:last-child { border-bottom: none; }
.es-sec-icon {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: .9rem; flex-shrink: 0;
}
.es-sec-icon.blue  { background: #dbeafe; color: #2563eb; }
.es-sec-icon.green { background: #d1fae5; color: #059669; }
.es-sec-icon.amber { background: #fef3c7; color: #d97706; }
.es-sec-icon.red   { background: #fee2e2; color: #dc2626; }
[data-theme="dark"] .es-sec-icon.blue  { background: #1e3a5f; color: #60a5fa; }
[data-theme="dark"] .es-sec-icon.amber { background: #451a03; color: #fbbf24; }
[data-theme="dark"] .es-sec-icon.green { background: #064e3b; color: #34d399; }
[data-theme="dark"] .es-sec-icon.red   { background: #7f1d1d; color: #f87171; }
.es-sec-info { flex: 1; min-width: 0; }
.es-sec-info h4 { font-size: .88rem; font-weight: 600; color: var(--text-primary); margin: 0 0 .15rem; }
.es-sec-info p  { font-size: .78rem; color: var(--text-muted); margin: 0; }

/* ── Buttons ──────────────────────────────── */
.es-btn-row { display: flex; gap: .65rem; align-items: center; flex-wrap: wrap; margin-top: 1.25rem; }

.es-save-btn {
  padding: .65rem 1.5rem; border-radius: 9px;
  background: var(--gradient-primary); color: #fff;
  border: none; font-size: .875rem; font-weight: 600;
  font-family: var(--font-primary); cursor: pointer;
  display: inline-flex; align-items: center; gap: .45rem;
  transition: opacity .15s, transform .15s, box-shadow .2s;
}
.es-save-btn:hover { opacity: .9; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(67,97,238,.3); }
.es-save-btn:active { transform: none; }
.es-save-btn:disabled { opacity: .5; cursor: not-allowed; transform: none; }

.es-cancel-btn {
  padding: .65rem 1.1rem; border-radius: 9px;
  background: var(--bg-secondary); color: var(--text-secondary);
  border: 1px solid var(--border-color); font-size: .875rem; font-weight: 500;
  font-family: var(--font-primary); cursor: pointer; transition: background .15s;
}
.es-cancel-btn:hover { background: var(--bg-tertiary); }

/* ── Appearance Section ──────────────────── */
.es-appearance-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 0; border-bottom: 1px solid var(--border-color);
  gap: 1rem; margin-bottom: 1rem;
}
.es-appearance-row:last-of-type { border-bottom: none; margin-bottom: 0; }

.es-appearance-info h4 {
  font-size: .9rem; font-weight: 600; color: var(--text-primary);
  margin: 0 0 .25rem; font-family: var(--font-heading);
}
.es-appearance-info p {
  font-size: .8rem; color: var(--text-muted); margin: 0;
}

/* Toggle Switch */
.es-toggle-switch {
  position: relative; display: inline-flex;
  width: 50px; height: 26px;
  cursor: pointer; flex-shrink: 0;
}
.es-toggle-switch input { display: none; }

.es-toggle-switch-slider {
  position: absolute; inset: 0;
  background: var(--border-color); border-radius: 13px;
  transition: background .3s ease, box-shadow .2s;
  display: flex; align-items: center; padding: 0 2px;
}

.es-toggle-switch-slider::after {
  content: '';
  width: 22px; height: 22px; border-radius: 50%;
  background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,.1);
  transition: transform .3s ease;
  display: block;
}

.es-toggle-switch input:checked + .es-toggle-switch-slider {
  background: var(--primary-color); box-shadow: 0 0 0 3px rgba(67,97,238,.1);
}
.es-toggle-switch input:checked + .es-toggle-switch-slider::after {
  transform: translateX(24px);
}

.es-toggle-switch:hover .es-toggle-switch-slider {
  box-shadow: 0 0 0 2px rgba(67,97,238,.15);
}

/* ── Inline form ──────────────────────────── */
.es-inline-form {
  background: var(--bg-tertiary); border: 1px solid var(--border-color);
  border-radius: 11px; padding: 1.1rem; margin-top: .75rem;
  display: none; animation: es-fadein .18s ease;
}
.es-inline-form.show { display: block; }
.es-inline-title { font-size: .82rem; font-weight: 700; color: var(--text-primary); margin-bottom: .9rem; display: flex; align-items: center; gap: .4rem; }

/* ── Danger zone ──────────────────────────── */
.es-danger-zone {
  border: 1.5px solid var(--danger-color); border-radius: 14px;
  padding: 1.5rem; margin-top: .5rem;
  background: rgba(239,68,68,.03);
}
[data-theme="dark"] .es-danger-zone { background: rgba(239,68,68,.06); }
.es-danger-title { font-size: .95rem; font-weight: 700; color: var(--danger-color); font-family: var(--font-heading); margin: 0 0 1rem; display: flex; align-items: center; gap: .55rem; }

/* ── Skeleton ─────────────────────────────── */
.es-skel {
  background: linear-gradient(110deg,var(--bg-tertiary) 8%,var(--bg-secondary) 18%,var(--bg-tertiary) 33%);
  background-size: 200% 100%;
  animation: es-shimmer 1.4s linear infinite; border-radius: 7px;
}
@keyframes es-shimmer { 0%{background-position:-200% 0}100%{background-position:200% 0} }

/* ── Error banner ─────────────────────────── */
.es-page-error {
  background: #fee2e2; border: 1px solid #fca5a5; border-radius: 12px;
  padding: 1rem 1.4rem; display: none; align-items: center;
  gap: .9rem; color: #991b1b; margin-bottom: 1.5rem;
}
[data-theme="dark"] .es-page-error { background: #7f1d1d; border-color: #b91c1c; color: #fca5a5; }
.es-page-error.show { display: flex; }
.es-retry-btn {
  margin-left: auto; padding: .32rem .75rem; border-radius: 7px;
  border: 1px solid currentColor; background: transparent; color: inherit;
  font-size: .78rem; font-weight: 600; cursor: pointer; font-family: var(--font-primary);
  transition: opacity .15s;
}
.es-retry-btn:hover { opacity: .7; }

/* ── Responsive ───────────────────────────── */
@media (max-width: 640px) {
  .es-page { padding: 1rem 1rem 5rem; }
  .es-hero  { flex-direction: column; align-items: flex-start; }
  .es-completion-wrap { align-items: flex-start; }
}
</style>


<!-- PAGE ERROR -->
<div class="es-page-error" id="es-page-error">
  <i class="fas fa-exclamation-circle"></i>
  <span id="es-page-error-msg">Failed to load your settings.</span>
  <button class="es-retry-btn" id="es-retry"><i class="fas fa-redo" style="margin-right:.3rem;"></i>Retry</button>
</div>

<!-- HERO -->
<div class="es-hero" id="es-hero">
  <div class="es-hero-left">
    <div class="es-logo-wrap">
      <div class="es-logo-placeholder es-skel" style="box-shadow:none;" id="es-logo-display"></div>
      <button class="es-logo-edit" id="es-logo-btn" title="Change company logo"><i class="fas fa-camera"></i></button>
      <input type="file" id="es-logo-input" accept="image/*">
    </div>
    <div class="es-hero-info">
      <h1 id="es-hero-name"><span class="es-skel" style="width:200px;height:22px;display:block;"></span></h1>
      <p  id="es-hero-sub"><span class="es-skel" style="width:150px;height:12px;display:block;margin-bottom:8px;"></span></p>
      <div class="es-hero-badges" id="es-hero-badges"></div>
    </div>
  </div>
  <div class="es-completion-wrap" id="es-completion-wrap" style="display:none;">
    <span class="es-completion-label">Profile Completion</span>
    <div class="es-completion-bar"><div class="es-completion-fill" id="es-completion-fill" style="width:0%"></div></div>
    <span class="es-completion-pct" id="es-completion-pct">0%</span>
  </div>
</div>


<!-- LAYOUT -->
<div class="es-layout">

  <!-- SIDENAV -->
  <nav class="es-sidenav">
    <button class="es-nav-item active" data-section="overview">
      <i class="fas fa-chart-bar"></i> Overview
    </button>
    <button class="es-nav-item" data-section="company">
      <i class="fas fa-building"></i> Company Info
    </button>
    <button class="es-nav-item" data-section="contact">
      <i class="fas fa-address-card"></i> Contact Details
    </button>
    <button class="es-nav-item" data-section="appearance">
      <i class="fas fa-palette"></i> Appearance
    </button>
    <div class="es-nav-divider"></div>
    <button class="es-nav-item" data-section="security">
      <i class="fas fa-shield-alt"></i> Security
    </button>
    <div class="es-nav-divider"></div>
    <button class="es-nav-item danger" id="es-logout-btn">
      <i class="fas fa-sign-out-alt"></i> Sign Out
    </button>
  </nav>


  <!-- SECTIONS -->
  <div id="es-sections">

    <!-- ═══ OVERVIEW ═══ -->
    <div class="es-section active" id="sec-overview">

      <!-- Quick stats -->
      <div class="es-stats-grid" id="es-stats-grid">
        <!-- populated by JS -->
        <div class="es-stat"><div class="es-skel" style="width:36px;height:36px;border-radius:9px;flex-shrink:0;"></div><div style="flex:1;"><div class="es-skel" style="width:40px;height:20px;margin-bottom:5px;"></div><div class="es-skel" style="width:70px;height:10px;"></div></div></div>
        <div class="es-stat"><div class="es-skel" style="width:36px;height:36px;border-radius:9px;flex-shrink:0;"></div><div style="flex:1;"><div class="es-skel" style="width:40px;height:20px;margin-bottom:5px;"></div><div class="es-skel" style="width:70px;height:10px;"></div></div></div>
        <div class="es-stat"><div class="es-skel" style="width:36px;height:36px;border-radius:9px;flex-shrink:0;"></div><div style="flex:1;"><div class="es-skel" style="width:40px;height:20px;margin-bottom:5px;"></div><div class="es-skel" style="width:70px;height:10px;"></div></div></div>
        <div class="es-stat"><div class="es-skel" style="width:36px;height:36px;border-radius:9px;flex-shrink:0;"></div><div style="flex:1;"><div class="es-skel" style="width:40px;height:20px;margin-bottom:5px;"></div><div class="es-skel" style="width:70px;height:10px;"></div></div></div>
      </div>

      <!-- Application breakdown -->
      <div class="es-card">
        <h2 class="es-card-title"><i class="fas fa-inbox"></i> Applications by Status</h2>
        <div id="es-app-status-wrap">
          <div class="es-skel" style="height:30px;width:60%;"></div>
        </div>
      </div>

      <!-- Top performing jobs -->
      <div class="es-card">
        <h2 class="es-card-title"><i class="fas fa-fire"></i> Top Performing Jobs</h2>
        <div class="es-top-jobs" id="es-top-jobs">
          <div class="es-skel" style="height:48px;"></div>
          <div class="es-skel" style="height:48px;"></div>
        </div>
      </div>

    </div><!-- /sec-overview -->


    <!-- ═══ COMPANY INFO ═══ -->
    <div class="es-section" id="sec-company">
      <div class="es-card" id="card-company">
        <div class="es-card-loading"><i class="fas fa-circle-notch"></i></div>
        <h2 class="es-card-title"><i class="fas fa-building"></i> Company Details</h2>

        <div class="es-form-grid">
          <div class="es-field span2">
            <label class="es-label">Company Name</label>
            <input type="text" class="es-input" id="c-name" placeholder="Your company or organisation name">
          </div>
          <div class="es-field">
            <label class="es-label">Employer Type</label>
            <select class="es-select" id="c-type">
              <option value="company">Company / Organisation</option>
              <option value="individual">Individual / Freelancer</option>
              <option value="agency">Recruitment Agency</option>
            </select>
          </div>
          <div class="es-field">
            <label class="es-label">Industry</label>
            <select class="es-select" id="c-industry">
              <option value="">Loading industries…</option>
            </select>
          </div>
          <div class="es-field span2">
            <label class="es-label">Company Website</label>
            <div class="es-iicon">
              <i class="fas fa-globe"></i>
              <input type="url" class="es-input" id="c-website" placeholder="https://yourcompany.com">
            </div>
          </div>
          <div class="es-field span2">
            <label class="es-label">About Your Company</label>
            <textarea class="es-textarea" id="c-description" placeholder="Tell job seekers what your company does, its culture, and what makes it a great place to work…"></textarea>
          </div>
        </div>

        <div class="es-btn-row">
          <button class="es-save-btn" id="save-company"><i class="fas fa-check"></i> Save Changes</button>
        </div>
      </div>
    </div><!-- /sec-company -->


    <!-- ═══ CONTACT DETAILS ═══ -->
    <div class="es-section" id="sec-contact">
      <div class="es-card" id="card-contact">
        <div class="es-card-loading"><i class="fas fa-circle-notch"></i></div>
        <h2 class="es-card-title"><i class="fas fa-address-card"></i> Contact Information</h2>
        <p style="font-size:.82rem;color:var(--text-muted);margin:-.5rem 0 1.25rem;">This information may be shown to candidates who have interviews or accepted applications.</p>

        <div class="es-form-grid">
          <div class="es-field">
            <label class="es-label">Contact Person Name</label>
            <input type="text" class="es-input" id="ct-name" placeholder="Who should candidates contact?">
          </div>
          <div class="es-field">
            <label class="es-label">Contact Email</label>
            <div class="es-iicon">
              <i class="fas fa-envelope"></i>
              <input type="email" class="es-input" id="ct-email" placeholder="hiring@yourcompany.com">
            </div>
          </div>
          <div class="es-field">
            <label class="es-label">Contact Phone</label>
            <div class="es-iicon">
              <i class="fas fa-phone"></i>
              <input type="tel" class="es-input" id="ct-phone" placeholder="+254 700 000000">
            </div>
          </div>
        </div>

        <div class="es-btn-row">
          <button class="es-save-btn" id="save-contact"><i class="fas fa-check"></i> Save Contact Info</button>
        </div>
      </div>
    </div><!-- /sec-contact -->

    <!-- ═══ APPEARANCE ═══ -->
    <div class="es-section" id="sec-appearance">
      <div class="es-card">
        <h2 class="es-card-title">
          <i class="fas fa-palette"></i> Appearance
        </h2>

        <div class="es-appearance-row">
          <div class="es-appearance-info">
            <h4>Dark Mode</h4>
            <p>Use a dark theme to reduce eye strain in low light environments.</p>
          </div>
          <label class="es-toggle-switch">
            <input type="checkbox" id="es-pref-dark-mode">
            <span class="es-toggle-switch-slider"></span>
          </label>
        </div>

        <div class="es-btn-row">
          <button class="es-save-btn" id="es-save-appearance">
            <i class="fas fa-check"></i> Save Appearance
          </button>
        </div>
      </div>
    </div><!-- /sec-appearance -->

    <!-- ═══ SECURITY ═══ -->
    <div class="es-section" id="sec-security">

      <div class="es-card">
        <h2 class="es-card-title"><i class="fas fa-shield-alt"></i> Account Security</h2>

        <div class="es-sec-row">
          <div class="es-sec-icon blue"><i class="fas fa-envelope"></i></div>
          <div class="es-sec-info">
            <h4>Email Address</h4>
            <p id="sec-email">Loading…</p>
          </div>
          <span id="sec-verified-badge" style="font-size:.7rem;font-weight:700;padding:.2rem .6rem;border-radius:50px;"></span>
        </div>

        <div class="es-sec-row">
          <div class="es-sec-icon green"><i class="fas fa-lock"></i></div>
          <div class="es-sec-info">
            <h4>Password</h4>
            <p id="sec-auth-hint">Update your login password.</p>
          </div>
          <button class="es-save-btn" style="padding:.42rem .9rem;font-size:.76rem;" id="sec-pwd-toggle">
            <i class="fas fa-key"></i> Change
          </button>
        </div>

        <!-- Password reset form -->
        <div class="es-inline-form" id="es-pwd-form">
          <div class="es-inline-title"><i class="fas fa-key" style="color:var(--primary-color);"></i> Change Your Password</div>
          <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:.9rem;">We'll send a password reset link to your email address.</p>
          <div class="es-btn-row" style="margin-top:.4rem;">
            <button class="es-save-btn" id="sec-send-reset"><i class="fas fa-paper-plane"></i> Send Reset Link</button>
            <button class="es-cancel-btn" id="sec-pwd-cancel">Cancel</button>
          </div>
        </div>

        <div class="es-sec-row">
          <div class="es-sec-icon amber"><i class="fas fa-sign-in-alt"></i></div>
          <div class="es-sec-info">
            <h4>Sign-In Method</h4>
            <p id="sec-provider">Loading…</p>
          </div>
        </div>

        <div class="es-sec-row">
          <div class="es-sec-icon blue"><i class="fas fa-calendar-alt"></i></div>
          <div class="es-sec-info">
            <h4>Member Since</h4>
            <p id="sec-since">Loading…</p>
          </div>
        </div>
      </div>

      <!-- Danger Zone -->
      <div class="es-danger-zone">
        <div class="es-danger-title"><i class="fas fa-exclamation-triangle"></i> Danger Zone</div>
        <div class="es-sec-row" style="padding-top:0;">
          <div class="es-sec-icon red"><i class="fas fa-user-slash"></i></div>
          <div class="es-sec-info">
            <h4>Sign Out Everywhere</h4>
            <p>Sign out of all active sessions across all devices.</p>
          </div>
          <button class="es-save-btn" style="background:var(--gradient-danger);padding:.42rem .9rem;font-size:.76rem;" id="sec-logout-all">
            <i class="fas fa-sign-out-alt"></i> Sign Out All
          </button>
        </div>
      </div>

    </div><!-- /sec-security -->

  </div><!-- /es-sections -->

</div><!-- /es-layout -->


<!-- ════════════════════════════════
     JAVASCRIPT
════════════════════════════════ -->
<script>
(function () {
  'use strict';

  /* ─── API Endpoints ──────────────────────────────── */
  const API = {
    settings       : '/api/employer/settings',         // GET  → settings data
    saveProfile    : '/api/employer/profile-setup',    // POST → wizard profile setup
    updateProfile  : '/api/employer/update-profile',   // POST → update company info
    uploadLogo     : '/api/jobseeker/upload-profile-photo', // POST multipart (shared)
    saveTheme      : '/api/employer/save-theme',       // POST → theme preference
    industries     : '/api/industries',                // GET  → industries list
    logout         : '/api/logout',                    // POST
    resetPwd       : '/api/forgot-password',           // POST → reset password
  };

  /* ─── State ──────────────────────────────────────── */
  let settings     = null;
  let industryList = [];

  /* ─── DOM helpers ────────────────────────────────── */
  const $   = id => document.getElementById(id);
  const esc = s  => String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  const cap = s  => s ? s.charAt(0).toUpperCase()+s.slice(1) : '';

  function fmt(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB',{day:'numeric',month:'long',year:'numeric'});
  }

  /* ─── Generic fetch ──────────────────────────────── */
  // All endpoints return { success, message, data }
  async function apiFetch(url, opts = {}) {
    // Set default headers for JSON requests
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(opts.headers || {})
    };
    
    const res  = await fetch(url, { ...opts, headers });
    const json = await res.json();
    if (!json.success) throw new Error(json.message || 'Request failed ('+res.status+')');
    return json;
  }

  /* ─── Toast / Confirm ────────────────────────────── */
  const toast = {
    ok  : m => window.Toast?.success(m),
    err : m => window.Toast?.error(m),
    info: m => window.Toast?.info(m),
  };
  async function confirm(opts) {
    if (window.Confirm?.show) return window.Confirm.show(opts);
    return window.confirm(opts.message||'Are you sure?');
  }

  /* ─── Button helpers ─────────────────────────────── */
  function setBtnLoading(btn, loading, label) {
    if (!btn) return;
    btn.disabled = loading;
    btn.innerHTML = loading
      ? '<i class="fas fa-circle-notch fa-spin"></i> Saving…'
      : `<i class="fas fa-check"></i> ${label||'Save Changes'}`;
  }

  function cardLoading(id, show) {
    $(id)?.querySelector('.es-card-loading')?.style.setProperty('display', show ? 'flex' : 'none');
  }

  /* ─── Section navigation ─────────────────────────── */
  document.querySelectorAll('.es-nav-item[data-section]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.es-nav-item[data-section]').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.es-section').forEach(s => s.classList.remove('active'));
      btn.classList.add('active');
      $('sec-'+btn.dataset.section)?.classList.add('active');
    });
  });

  /* ─── Load settings ──────────────────────────────── */
  // GET /api/employer/settings
  // Returns: { success, message, data: { user_info, profile: { profile, industry, location, stats } } }
  async function loadSettings() {
    $('es-page-error').classList.remove('show');
    try {
      const json = await apiFetch(API.settings);
      settings = json.data;

      // Normalise the nested structure:
      // The response can come in two shapes depending on which endpoint was called:
      // Shape A (from /api/employer/settings): data.user_info + data.profile.profile + data.profile.industry + data.profile.stats
      // Shape B (from /api/employer/profile direct): data.profile + data.industry + data.stats
      const d = settings;
      settings._user    = d.user_info || {};
      settings._profile = d.profile?.profile || d.profile || {};
      settings._industry= d.profile?.industry || d.industry || null;
      settings._stats   = d.profile?.stats    || d.stats   || {};

      populateAll();
      loadIndustries();
    } catch (err) {
      $('es-page-error-msg').textContent = err.message || 'Could not load settings.';
      $('es-page-error').classList.add('show');
    }
  }

  /* ─── Populate all sections ──────────────────────── */
  function populateAll() {
    const P = settings._profile;
    const U = settings._user;
    const S = settings._stats;

    // ── Hero ──
    $('es-hero-name').textContent = P.company_name || 'Your Company';
    $('es-hero-sub').textContent  = settings._industry?.name || 'No industry set';

    // Logo
    const logoEl = $('es-logo-display');
    const logoSrc = P.avatar || U.avatar || '';
    if (logoSrc) {
      logoEl.outerHTML = `<img src="${esc(logoSrc)}" class="es-logo" id="es-logo-display" alt="Company logo" onerror="this.style.display='none'">`;
    } else {
      logoEl.className = 'es-logo-placeholder';
      logoEl.textContent = (P.company_name||'C').charAt(0).toUpperCase();
    }

    // Badges
    const badgesEl = $('es-hero-badges');
    const badges = [];
    badges.push(`<span class="es-hbadge ${P.employer_type==='company'?'company':'individual'}">${P.employer_type==='company'?'Company':'Individual'}</span>`);
    if (P.is_verified) badges.push('<span class="es-hbadge verified"><i class="fas fa-check-circle"></i> Verified</span>');
    else               badges.push('<span class="es-hbadge unverified"><i class="fas fa-clock"></i> Pending Verification</span>');
    if (P.is_premium)  badges.push('<span class="es-hbadge premium"><i class="fas fa-crown"></i> Premium</span>');
    badgesEl.innerHTML = badges.join('');

    // Completion
    const pct = Math.min(100, Math.max(0, parseInt(P.profile_completion)||0));
    $('es-completion-fill').style.width = pct+'%';
    $('es-completion-pct').textContent  = pct+'%';
    $('es-completion-wrap').style.display = 'flex';

    // ── Overview stats ──
    $('es-stats-grid').innerHTML = `
      <div class="es-stat"><div class="es-stat-icon si-jobs"><i class="fas fa-briefcase"></i></div><div><div class="es-stat-num">${S.total_jobs||0}</div><div class="es-stat-label">Total Jobs</div></div></div>
      <div class="es-stat"><div class="es-stat-icon si-jobs" style="background:#d1fae5;color:#059669;"><i class="fas fa-check-circle"></i></div><div><div class="es-stat-num">${S.active_jobs||0}</div><div class="es-stat-label">Active Jobs</div></div></div>
      <div class="es-stat"><div class="es-stat-icon si-apps"><i class="fas fa-users"></i></div><div><div class="es-stat-num">${S.total_applications||0}</div><div class="es-stat-label">Total Applications</div></div></div>
      <div class="es-stat"><div class="es-stat-icon si-new"><i class="fas fa-calendar-check"></i></div><div><div class="es-stat-num">${S.recent_applications_30d||0}</div><div class="es-stat-label">Last 30 Days</div></div></div>
      <div class="es-stat"><div class="es-stat-icon si-unseen"><i class="fas fa-bell"></i></div><div><div class="es-stat-num">${S.unviewed_applications||0}</div><div class="es-stat-label">Unreviewed</div></div></div>`;

    // Application status breakdown
    const abs = S.applications_by_status || {};
    const statuses = ['pending','reviewed','shortlisted','rejected','hired'];
    const pillsHtml = statuses.map(s => abs[s] > 0 ? `
      <span class="es-app-pill ${s}"><i class="fas ${s==='hired'?'fa-handshake':s==='rejected'?'fa-times-circle':s==='shortlisted'?'fa-star':s==='reviewed'?'fa-eye':'fa-clock'}"></i> ${cap(s)}: ${abs[s]}</span>` : '').filter(Boolean).join('');
    $('es-app-status-wrap').innerHTML = pillsHtml ||
      '<p style="font-size:.82rem;color:var(--text-muted);">No applications yet.</p>';

    // Top jobs
    const topJobs = S.top_jobs || [];
    $('es-top-jobs').innerHTML = topJobs.length
      ? topJobs.slice(0,5).map((j,i) => `
        <div class="es-top-job">
          <div class="es-top-job-rank">${i+1}</div>
          <div class="es-top-job-title">${esc(j.title)}</div>
          <div class="es-top-job-count"><i class="fas fa-users" style="font-size:.6rem;margin-right:.25rem;"></i>${j.application_count}</div>
        </div>`).join('')
      : '<p style="font-size:.82rem;color:var(--text-muted);">No jobs posted yet.</p>';

    // ── Company form ──
    $('c-name').value        = P.company_name        || '';
    $('c-type').value        = P.employer_type        || 'company';
    $('c-website').value     = P.website              || '';
    $('c-description').value = P.company_description  || '';
    // industry select will be set after industries load

    // ── Contact form ──
    $('ct-name').value  = P.contact_name  || '';
    $('ct-email').value = P.contact_email || '';
    $('ct-phone').value = P.contact_phone || '';

    // ── Security ──
    $('sec-email').textContent    = U.email || '—';
    $('sec-verified-badge').textContent = U.email_verified_at ? '✓ Verified' : 'Not verified';
    Object.assign($('sec-verified-badge').style, {
      background: U.email_verified_at ? '#d1fae5' : '#fef3c7',
      color     : U.email_verified_at ? '#065f46' : '#92400e',
    });
    const provMap = { google:'Signed in with Google', email:'Password login', local:'Password login' };
    $('sec-provider').textContent = provMap[U.auth_provider] || cap(U.auth_provider||'') || '—';
    $('sec-since').textContent    = fmt(U.created_at);
    if (U.auth_provider === 'google') {
      $('sec-auth-hint').textContent = 'Your account uses Google sign-in. Set a standalone password via the reset link.';
    }

    // ── Theme / Appearance ──
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    $('es-pref-dark-mode').checked = currentTheme === 'dark';
  }

  /* ─── Load industries ────────────────────────────── */
  // GET /api/industries
  // Returns: { success, message, data: [...] | { data: [...], ... } }
  async function loadIndustries() {
    try {
      const json = await apiFetch(API.industries);
      industryList = Array.isArray(json.data) ? json.data
                   : (Array.isArray(json.data?.data) ? json.data.data : []);
      const sel = $('c-industry');
      sel.innerHTML = '<option value="">Select industry…</option>' +
        industryList.map(i => `<option value="${i.id}">${esc(i.name)}</option>`).join('');
      // Set current value
      const currentId = settings._profile?.industry_id || settings._industry?.id || '';
      if (currentId) sel.value = currentId;
    } catch (_) {
      $('c-industry').innerHTML = '<option value="">Could not load industries</option>';
    }
  }

  /* ─── Save company info ──────────────────────────── */
  // POST /api/employer/profile-setup
  // Body: FormData with company fields
  // Returns: { success, message, data }
  async function saveSection(btnId, fields) {
    const btn = $(btnId);
    setBtnLoading(btn, true);
    try {
      const body = new FormData();
      Object.entries(fields).forEach(([k,v]) => { if (v !== null && v !== undefined) body.append(k, v); });
      await apiFetch(API.saveProfile, { method:'POST', body });
      toast.ok('Changes saved successfully!');
      // Re-fetch to sync
      const json = await apiFetch(API.settings);
      settings = json.data;
      settings._user    = json.data.user_info || {};
      settings._profile = json.data.profile?.profile || json.data.profile || {};
      settings._industry= json.data.profile?.industry || json.data.industry || null;
      settings._stats   = json.data.profile?.stats    || json.data.stats   || {};
      populateAll();
      loadIndustries();
    } catch (err) {
      toast.err(err.message || 'Could not save. Please try again.');
    } finally {
      setBtnLoading(btn, false);
    }
  }

  $('save-company').addEventListener('click', async () => {
    const btn = $('save-company');
    setBtnLoading(btn, true, 'Save Company Info');
    
    const companyData = {
      company_name: $('c-name').value.trim(),
      employer_type: $('c-type').value,
      industry_id: $('c-industry').value || null,
      website: $('c-website').value.trim() || null,
      company_description: $('c-description').value.trim() || null,
    };
    
    try {
      await apiFetch(API.updateProfile, { method: 'POST', body: JSON.stringify(companyData), headers: { 'Content-Type': 'application/json' } });
      toast.ok('Company information saved successfully!');
      const json = await apiFetch(API.settings);
      settings = json.data;
      settings._user    = json.data.user_info || {};
      settings._profile = json.data.profile?.profile || json.data.profile || {};
      settings._industry= json.data.profile?.industry || json.data.industry || null;
      settings._stats   = json.data.profile?.stats    || json.data.stats   || {};
      populateAll();
    } catch (err) {
      toast.err(err.message || 'Could not save company information.');
    } finally {
      setBtnLoading(btn, false, 'Save Company Info');
    }
  });

  $('save-contact').addEventListener('click', () => {
    saveSection('save-contact', {
      contact_name : $('ct-name').value.trim(),
      contact_email: $('ct-email').value.trim(),
      contact_phone: $('ct-phone').value.trim(),
    });
  });

  /* ─── Logo upload ────────────────────────────────── */
  // POST /api/jobseeker/upload-profile-photo (multipart, shared endpoint)
  // Returns: { success, message, data: { avatar_url } | { avatar } }
  $('es-logo-btn').addEventListener('click', () => $('es-logo-input').click());

  $('es-logo-input').addEventListener('change', async e => {
    const file = e.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) { toast.err('Please select an image file.'); return; }
    if (file.size > 5*1024*1024)         { toast.err('Image must be under 5 MB.'); return; }

    const btn = $('es-logo-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:.62rem;"></i>';
    btn.disabled  = true;

    try {
      const body = new FormData();
      body.append('photo', file);
      const json = await apiFetch(API.uploadLogo, { method:'POST', body });
      const src  = json.data?.avatar_url || json.data?.avatar || '';
      if (src) {
        const el = $('es-logo-display');
        if (el?.tagName === 'IMG') { el.src = src+'?t='+Date.now(); }
        else { el.outerHTML = `<img src="${esc(src)}?t=${Date.now()}" class="es-logo" id="es-logo-display" alt="Company logo">`; }
      }
      toast.ok('Company logo updated!');
    } catch (err) {
      toast.err(err.message || 'Upload failed. Please try again.');
    } finally {
      btn.innerHTML = '<i class="fas fa-camera"></i>';
      btn.disabled  = false;
      e.target.value = '';
    }
  });

  /* ─── Theme Appearance Save ──────────────────────── */
  $('es-save-appearance').addEventListener('click', async () => {
    const btn = $('es-save-appearance');
    const isDarkMode = $('es-pref-dark-mode').checked;
    const theme = isDarkMode ? 'dark' : 'light';
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Saving…';
    try {
      await apiFetch(API.saveTheme, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ theme })
      });
      
      // Apply theme immediately to the document
      const html = document.documentElement;
      html.setAttribute('data-theme', theme);
      
      toast.ok('Theme preference saved!');
    } catch (err) {
      toast.err(err.message || 'Could not save theme preference.');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-check"></i> Save Appearance';
    }
  });

  /* ─── Security: password reset ───────────────────── */
  // POST /api/forgot-password  { email }
  // Returns: { success, message, data }
  $('sec-pwd-toggle').addEventListener('click', () => {
    const f = $('es-pwd-form');
    f.classList.toggle('show');
  });
  $('sec-pwd-cancel').addEventListener('click', () => $('es-pwd-form').classList.remove('show'));

  $('sec-send-reset').addEventListener('click', async () => {
    const email = settings?._user?.email;
    if (!email) { toast.err('No email address found.'); return; }
    const btn = $('sec-send-reset');
    setBtnLoading(btn, true, 'Sending...');
    try {
      await apiFetch(API.resetPwd, {
        method : 'POST',
        headers: {'Content-Type':'application/json'},
        body   : JSON.stringify({ email }),
      });
      
      toast.ok('✓ Reset link sent to your email!');
      $('es-pwd-form').classList.remove('show');
      
      // Logout and redirect with message after 1.5 seconds
      setTimeout(async () => {
        try {
          await apiFetch(API.logout, { method:'POST', headers:{'Content-Type':'application/json'} });
        } catch (e) {}
        window.location.href = '/login?message=reset-link-sent';
      }, 1500);
    } catch (err) {
      toast.err(err.message || 'Could not send reset link.');
    } finally {
      setBtnLoading(btn, false, 'Send Reset Link');
    }
  });

  /* ─── Logout ─────────────────────────────────────── */
  // POST /api/logout
  // Returns: { success, message }
  async function doLogout() {
    const ok = await confirm({
      heading:'Sign Out', message:'Are you sure you want to sign out?',
      type:'warning', confirmText:'Yes, Sign Out', cancelText:'Stay',
    });
    if (!ok) return;
    try {
      await apiFetch(API.logout, { method:'POST', headers:{'Content-Type':'application/json'} });
    } finally {
      window.location.href = '/login';
    }
  }

  $('es-logout-btn').addEventListener('click', doLogout);

  $('sec-logout-all').addEventListener('click', async () => {
    const ok = await confirm({
      heading    : 'Sign Out Everywhere',
      message    : 'This will sign you out of all devices and sessions, including this one.',
      type       : 'warning',
      confirmText: 'Sign Out All Devices',
      cancelText : 'Cancel',
    });
    if (!ok) return;
    try {
      await apiFetch(API.logout, { method:'POST', headers:{'Content-Type':'application/json'} });
    } finally {
      window.location.href = '/login';
    }
  });

  /* ─── Retry ──────────────────────────────────────── */
  $('es-retry').addEventListener('click', loadSettings);

  /* ─── Boot ───────────────────────────────────────── */
  loadSettings();

})();
</script>

</div>
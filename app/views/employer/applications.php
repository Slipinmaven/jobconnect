<!-- applications.view.php — injected as $content into the main layout -->
<!-- Expected: $css = 'employer/applications', $js = 'employer/applications' -->

<style>
/* ─────────────────────────────────────────
   DESIGN SYSTEM — Applications Page
   Aesthetic: Editorial / Command-Centre
   Muted slate palette + electric indigo accent
   Fonts: shared site typography
   ───────────────────────────────────────── */

:root {
  --a-accent:        #4f46e5;
  --a-accent-light:  #818cf8;
  --a-accent-dim:    rgba(79,70,229,.12);
  --a-green:         #059669;
  --a-green-dim:     rgba(5,150,105,.12);
  --a-amber:         #d97706;
  --a-amber-dim:     rgba(217,119,6,.12);
  --a-red:           #dc2626;
  --a-red-dim:       rgba(220,38,38,.12);
  --a-violet:        #7c3aed;
  --a-violet-dim:    rgba(124,58,237,.12);
  --a-blue:          #2563eb;
  --a-blue-dim:      rgba(37,99,235,.12);

  --a-font-heading:  var(--font-heading);
  --a-font-body:     var(--font-primary);
  --a-font-mono:     var(--font-primary);

  --a-radius-s:  6px;
  --a-radius-m:  12px;
  --a-radius-l:  18px;
  --a-radius-xl: 26px;

  --a-transition: 200ms cubic-bezier(.4,0,.2,1);
}

/* ── Reset within scope ── */
.ap-root *, .ap-root *::before, .ap-root *::after { box-sizing: border-box; }
.ap-root button { cursor: pointer; font-family: var(--a-font-body); }
.ap-root input, .ap-root select, .ap-root textarea {
  font-family: var(--a-font-body);
  color: var(--text-primary);
}

/* ── Root wrapper ── */
.ap-root {
  flex: 1;
  display: flex;
  flex-direction: column;
  width: 100%;
  min-width: 0;
  max-width: 100%;
  min-height: 100dvh;
  background: var(--bg-primary);
  font-family: var(--a-font-body);
  overflow-x: hidden;
}

/* ════════════════════════════════════════
   TOP BAR
   ════════════════════════════════════════ */
.ap-topbar {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.1rem 2rem;
  background: var(--bg-secondary);
  border-bottom: 1px solid var(--border-color);
  position: sticky;
  top: 0;
  z-index: 60;
  backdrop-filter: blur(16px);
  flex-wrap: wrap;
}

.ap-topbar-title {
  flex: 1;
  min-width: 0;
}
.ap-topbar-title h1 {
  font-family: var(--a-font-heading);
  font-size: 1.3rem;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0 0 2px;
  line-height: 1;
  letter-spacing: -.3px;
}
.ap-topbar-title p {
  font-size: .75rem;
  color: var(--text-muted);
  margin: 0;
  font-family: var(--a-font-mono);
}

.ap-topbar-actions {
  display: flex;
  align-items: center;
  gap: .6rem;
  flex-wrap: wrap;
}

/* Job selector */
.ap-job-select {
  appearance: none;
  padding: .55rem 2.2rem .55rem .9rem;
  border: 1.5px solid var(--border-color);
  border-radius: var(--a-radius-m);
  background: var(--bg-primary) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2364748b' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right .75rem center;
  font-size: .82rem;
  font-weight: 500;
  color: var(--text-primary);
  transition: border-color var(--a-transition), box-shadow var(--a-transition);
  min-width: 180px;
  max-width: 260px;
  cursor: pointer;
}
.ap-job-select:focus {
  outline: none;
  border-color: var(--a-accent);
  box-shadow: 0 0 0 3px var(--a-accent-dim);
}

/* ════════════════════════════════════════
   STATS RIBBON
   ════════════════════════════════════════ */
.ap-stats-ribbon {
  display: flex;
  border-bottom: 1px solid var(--border-color);
  overflow-x: auto;
  scrollbar-width: none;
  background: var(--bg-secondary);
}
.ap-stats-ribbon::-webkit-scrollbar { display: none; }

.ap-stat-tab {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  padding: .9rem 1.4rem;
  border: none;
  background: transparent;
  border-bottom: 3px solid transparent;
  white-space: nowrap;
  transition: background var(--a-transition), border-color var(--a-transition);
  position: relative;
  font-family: var(--a-font-body);
  flex-shrink: 0;
}
.ap-stat-tab:hover { background: var(--bg-tertiary); }
.ap-stat-tab.is-active {
  border-bottom-color: var(--a-accent);
  background: var(--bg-primary);
}

.ap-stat-tab .tab-count {
  font-family: var(--a-font-heading);
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1;
  transition: color var(--a-transition);
}
.ap-stat-tab.is-active .tab-count { color: var(--a-accent); }
.ap-stat-tab .tab-label {
  font-size: .68rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .6px;
  color: var(--text-muted);
}
.ap-stat-tab .tab-indicator {
  position: absolute;
  top: .8rem;
  right: .7rem;
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

/* ════════════════════════════════════════
   CONTROL BAR (search + filters)
   ════════════════════════════════════════ */
.ap-controls {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: .9rem 2rem;
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-primary);
  flex-wrap: wrap;
}

.ap-search {
  flex: 1;
  min-width: 180px;
  position: relative;
}
.ap-search i {
  position: absolute;
  left: 11px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  font-size: .85rem;
  pointer-events: none;
}
.ap-search input {
  width: 100%;
  padding: .58rem 1rem .58rem 2.1rem;
  border: 1.5px solid var(--border-color);
  border-radius: var(--a-radius-m);
  background: var(--bg-secondary);
  font-size: .84rem;
  transition: border-color var(--a-transition), box-shadow var(--a-transition);
}
.ap-search input:focus {
  outline: none;
  border-color: var(--a-accent);
  box-shadow: 0 0 0 3px var(--a-accent-dim);
}

.ap-filter-select {
  appearance: none;
  padding: .58rem 2rem .58rem .85rem;
  border: 1.5px solid var(--border-color);
  border-radius: var(--a-radius-m);
  background: var(--bg-secondary) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='7' viewBox='0 0 10 7'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%2364748b' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right .65rem center;
  font-size: .84rem;
  font-weight: 500;
  transition: border-color var(--a-transition);
  cursor: pointer;
}
.ap-filter-select:focus {
  outline: none;
  border-color: var(--a-accent);
}

.ap-results-count {
  font-size: .78rem;
  color: var(--text-muted);
  font-family: var(--a-font-mono);
  white-space: nowrap;
  margin-left: auto;
}

/* ════════════════════════════════════════
   APPLICATION LIST
   ════════════════════════════════════════ */
.ap-list-wrap {
  flex: 1;
  padding: 1.5rem 2rem 3rem;
  display: flex;
  flex-direction: column;
  gap: .85rem;
}

/* ── Card ── */
.ap-card {
  background: var(--bg-secondary);
  border: 1.5px solid var(--border-color);
  border-radius: var(--a-radius-l);
  overflow: hidden;
  transition: box-shadow var(--a-transition), border-color var(--a-transition), transform var(--a-transition);
  animation: ap-card-in .3s ease both;
  position: relative;
}
.ap-card:hover {
  box-shadow: 0 8px 28px rgba(0,0,0,.09);
  transform: translateY(-1px);
}
@keyframes ap-card-in {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Status stripe */
.ap-card::before {
  content: '';
  position: absolute;
  left: 0; top: 0; bottom: 0;
  width: 4px;
  border-radius: var(--a-radius-l) 0 0 var(--a-radius-l);
}
.ap-card[data-status="pending"]::before    { background: var(--a-amber); }
.ap-card[data-status="reviewed"]::before   { background: var(--a-blue); }
.ap-card[data-status="shortlisted"]::before{ background: var(--a-violet); }
.ap-card[data-status="accepted"]::before   { background: var(--a-green); }
.ap-card[data-status="rejected"]::before   { background: var(--a-red); }

/* ── Card header ── */
.ap-card-head {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.2rem 1.4rem 0.8rem 1.6rem;
}

.ap-avatar {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--a-font-heading);
  font-weight: 800;
  font-size: .95rem;
  color: #fff;
  flex-shrink: 0;
  letter-spacing: .5px;
}

.ap-avatar-a { background: linear-gradient(135deg,#4f46e5,#7c3aed); }
.ap-avatar-b { background: linear-gradient(135deg,#0891b2,#2563eb); }
.ap-avatar-c { background: linear-gradient(135deg,#059669,#0d9488); }
.ap-avatar-d { background: linear-gradient(135deg,#d97706,#dc2626); }
.ap-avatar-e { background: linear-gradient(135deg,#7c3aed,#db2777); }
.ap-avatar-f { background: linear-gradient(135deg,#0284c7,#4f46e5); }

.ap-person {
  flex: 1;
  min-width: 0;
}
.ap-person-name {
  font-family: var(--a-font-heading);
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 2px;
  line-height: 1.2;
}
.ap-person-role {
  font-size: .78rem;
  color: var(--text-muted);
  margin: 0 0 3px;
}
.ap-person-email {
  font-family: var(--a-font-mono);
  font-size: .73rem;
  color: var(--a-accent);
  margin: 0;
}

.ap-card-badges {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: .4rem;
  flex-shrink: 0;
}

/* ── Status badge ── */
.ap-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 11px;
  border-radius: 100px;
  font-size: .7rem;
  font-weight: 700;
  letter-spacing: .4px;
  text-transform: uppercase;
  white-space: nowrap;
}
.ap-status-badge::before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
  flex-shrink: 0;
}
.badge-pending    { background: var(--a-amber-dim);  color: var(--a-amber); }
.badge-reviewed   { background: var(--a-blue-dim);   color: var(--a-blue); }
.badge-shortlisted{ background: var(--a-violet-dim); color: var(--a-violet); }
.badge-accepted   { background: var(--a-green-dim);  color: var(--a-green); }
.badge-rejected   { background: var(--a-red-dim);    color: var(--a-red); }

.ap-time {
  font-size: .7rem;
  color: var(--text-muted);
  font-family: var(--a-font-mono);
}

/* ── Card meta chips ── */
.ap-card-meta {
  display: flex;
  align-items: center;
  gap: .5rem;
  padding: 0 1.4rem .7rem 1.6rem;
  flex-wrap: wrap;
}

.ap-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 9px;
  border-radius: 100px;
  font-size: .72rem;
  font-weight: 500;
  background: var(--bg-tertiary);
  border: 1px solid var(--border-color);
  color: var(--text-muted);
  white-space: nowrap;
}
.ap-chip i { font-size: .65rem; color: var(--a-accent); }

.ap-chip-cv {
  background: var(--a-blue-dim);
  border-color: transparent;
  color: var(--a-blue);
  cursor: pointer;
  transition: background var(--a-transition);
  text-decoration: none;
}
.ap-chip-cv:hover { background: rgba(37,99,235,.22); }

/* ── Cover letter preview ── */
.ap-cover-preview {
  margin: 0 1.4rem .85rem 1.6rem;
  padding: .7rem 1rem;
  background: var(--bg-tertiary);
  border-radius: var(--a-radius-s);
  border-left: 3px solid var(--border-color);
  font-size: .8rem;
  color: var(--text-muted);
  line-height: 1.6;
  font-style: italic;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* ── Employer notes (visible if set) ── */
.ap-employer-note {
  margin: 0 1.4rem .85rem 1.6rem;
  padding: .6rem .9rem;
  background: var(--a-amber-dim);
  border-radius: var(--a-radius-s);
  font-size: .78rem;
  color: var(--a-amber);
  display: flex;
  align-items: flex-start;
  gap: .5rem;
}
.ap-employer-note i { margin-top: 2px; flex-shrink: 0; }

/* ── Card actions ── */
.ap-card-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
  padding: .85rem 1.4rem .85rem 1.6rem;
  border-top: 1px solid var(--border-color);
  flex-wrap: wrap;
}

.ap-action-primary {
  display: flex;
  align-items: center;
  gap: .5rem;
  flex-wrap: wrap;
}

.ap-action-secondary {
  display: flex;
  align-items: center;
  gap: .5rem;
  flex-wrap: wrap;
}

/* ── Action buttons ── */
.ap-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: .48rem .9rem;
  border-radius: var(--a-radius-s);
  font-size: .78rem;
  font-weight: 600;
  border: 1.5px solid transparent;
  transition: all var(--a-transition);
  white-space: nowrap;
  text-decoration: none;
  font-family: var(--a-font-body);
}
.ap-btn i { font-size: .72rem; }

.ap-btn-ghost {
  background: transparent;
  border-color: var(--border-color);
  color: var(--text-secondary);
}
.ap-btn-ghost:hover {
  border-color: var(--a-accent);
  color: var(--a-accent);
  background: var(--a-accent-dim);
}

.ap-btn-review {
  background: var(--a-blue-dim);
  border-color: transparent;
  color: var(--a-blue);
}
.ap-btn-review:hover { background: rgba(37,99,235,.22); }

.ap-btn-shortlist {
  background: var(--a-violet-dim);
  border-color: transparent;
  color: var(--a-violet);
}
.ap-btn-shortlist:hover { background: rgba(124,58,237,.22); }

.ap-btn-accept {
  background: var(--a-green-dim);
  border-color: transparent;
  color: var(--a-green);
}
.ap-btn-accept:hover { background: rgba(5,150,105,.22); }

.ap-btn-reject {
  background: var(--a-red-dim);
  border-color: transparent;
  color: var(--a-red);
}
.ap-btn-reject:hover { background: rgba(220,38,38,.22); }

.ap-btn-interview {
  background: linear-gradient(135deg, var(--a-accent), var(--a-violet));
  border-color: transparent;
  color: #fff;
  box-shadow: 0 2px 8px rgba(79,70,229,.3);
}
.ap-btn-interview:hover {
  box-shadow: 0 4px 14px rgba(79,70,229,.4);
  transform: translateY(-1px);
}

.ap-btn-disabled {
  opacity: .4;
  pointer-events: none;
}

/* ─── Staggered animation delays ─── */
.ap-card:nth-child(1)  { animation-delay: .04s; }
.ap-card:nth-child(2)  { animation-delay: .08s; }
.ap-card:nth-child(3)  { animation-delay: .12s; }
.ap-card:nth-child(4)  { animation-delay: .16s; }
.ap-card:nth-child(5)  { animation-delay: .20s; }
.ap-card:nth-child(6)  { animation-delay: .24s; }
.ap-card:nth-child(n+7){ animation-delay: .28s; }

/* ════════════════════════════════════════
   EMPTY / LOADING STATES
   ════════════════════════════════════════ */
.ap-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 4rem 2rem;
  min-width: 0;
  text-align: center;
  color: var(--text-muted);
}
.ap-state-icon {
  width: 68px;
  height: 68px;
  border-radius: 20px;
  background: var(--bg-tertiary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  color: var(--text-muted);
  border: 2px dashed var(--border-color);
}
.ap-state h3 {
  font-family: var(--a-font-heading);
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  overflow-wrap: anywhere;
}
.ap-state p {
  font-size: .84rem;
  margin: 0;
  width: 100%;
  max-width: 300px;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

/* Skeleton shimmer */
.ap-skeleton {
  border-radius: var(--a-radius-l);
  overflow: hidden;
  background: var(--bg-secondary);
  border: 1.5px solid var(--border-color);
}
.ap-skel-inner {
  padding: 1.2rem 1.6rem;
  display: flex;
  flex-direction: column;
  gap: .7rem;
}
.ap-skel-row {
  display: flex;
  align-items: center;
  gap: .85rem;
}
.ap-skel-bar {
  border-radius: 6px;
  background: linear-gradient(90deg, var(--bg-tertiary) 25%, var(--bg-secondary) 50%, var(--bg-tertiary) 75%);
  background-size: 300% 100%;
  animation: skel-shimmer 1.6s infinite;
}
@keyframes skel-shimmer {
  0%   { background-position: 100% 0; }
  100% { background-position: -100% 0; }
}

/* ════════════════════════════════════════
   MODALS
   ════════════════════════════════════════ */
.ap-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.55);
  backdrop-filter: blur(6px);
  z-index: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  opacity: 0;
  pointer-events: none;
  transition: opacity .25s ease;
}
.ap-modal-overlay.is-open {
  opacity: 1;
  pointer-events: auto;
}

.ap-modal {
  background: var(--bg-primary);
  border: 1.5px solid var(--border-color);
  border-radius: var(--a-radius-xl);
  width: 100%;
  max-width: 560px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  transform: scale(.95) translateY(10px);
  transition: transform .25s cubic-bezier(.34,1.56,.64,1);
  box-shadow: 0 32px 64px rgba(0,0,0,.22);
  overflow: hidden;
}
.ap-modal-overlay.is-open .ap-modal {
  transform: scale(1) translateY(0);
}

.ap-modal-head {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--border-color);
  flex-shrink: 0;
}
.ap-modal-head h2 {
  font-family: var(--a-font-heading);
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0;
  flex: 1;
}
.ap-modal-close {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1.5px solid var(--border-color);
  background: transparent;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .85rem;
  transition: all var(--a-transition);
  flex-shrink: 0;
}
.ap-modal-close:hover {
  border-color: var(--a-red);
  color: var(--a-red);
  background: var(--a-red-dim);
}

.ap-modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
}
.ap-modal-body::-webkit-scrollbar { width: 5px; }
.ap-modal-body::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 10px; }

.ap-modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: .6rem;
  flex-shrink: 0;
  flex-wrap: wrap;
}

/* ── Detail modal profile ── */
.ap-detail-profile {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--bg-secondary);
  border-radius: var(--a-radius-m);
  border: 1px solid var(--border-color);
}
.ap-detail-profile .ap-avatar { width: 56px; height: 56px; font-size: 1.1rem; border-radius: 16px; }
.ap-detail-profile-info h3 {
  font-family: var(--a-font-heading);
  font-size: 1.1rem;
  font-weight: 800;
  margin: 0 0 3px;
  color: var(--text-primary);
}
.ap-detail-profile-info p {
  font-size: .8rem;
  color: var(--text-muted);
  margin: 0;
}

.ap-detail-section-label {
  font-size: .7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .8px;
  color: var(--text-muted);
  margin-bottom: .4rem;
  font-family: var(--a-font-heading);
}
.ap-detail-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .6rem;
}
.ap-detail-info-item {
  padding: .6rem .85rem;
  background: var(--bg-secondary);
  border-radius: var(--a-radius-s);
  border: 1px solid var(--border-color);
}
.ap-detail-info-item .item-label {
  font-size: .68rem;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: var(--text-muted);
  font-weight: 600;
  margin-bottom: 2px;
}
.ap-detail-info-item .item-value {
  font-size: .85rem;
  color: var(--text-primary);
  font-weight: 500;
  word-break: break-word;
}

.ap-cover-full {
  padding: .85rem 1rem;
  background: var(--bg-secondary);
  border-radius: var(--a-radius-s);
  border-left: 3px solid var(--a-accent);
  font-size: .83rem;
  color: var(--text-secondary);
  line-height: 1.7;
  font-style: italic;
  white-space: pre-wrap;
}

/* ── Status change section ── */
.ap-status-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: .55rem;
}
.ap-status-action-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  padding: .75rem .5rem;
  border-radius: var(--a-radius-m);
  border: 1.5px solid transparent;
  font-size: .75rem;
  font-weight: 600;
  transition: all var(--a-transition);
  cursor: pointer;
  font-family: var(--a-font-body);
}
.ap-status-action-btn i { font-size: 1.1rem; }
.ap-status-action-btn span { font-size: .68rem; text-transform: uppercase; letter-spacing: .4px; }

.sab-review    { background: var(--a-blue-dim);   color: var(--a-blue);   border-color: rgba(37,99,235,.25); }
.sab-shortlist { background: var(--a-violet-dim); color: var(--a-violet); border-color: rgba(124,58,237,.25); }
.sab-accept    { background: var(--a-green-dim);  color: var(--a-green);  border-color: rgba(5,150,105,.25); }
.sab-reject    { background: var(--a-red-dim);    color: var(--a-red);    border-color: rgba(220,38,38,.25); }
.sab-interview { background: var(--a-accent-dim); color: var(--a-accent); border-color: rgba(79,70,229,.25); }

.ap-status-action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
.ap-status-action-btn.is-current { opacity: .45; pointer-events: none; }

/* ── Rejection reason ── */
.ap-reject-reason-wrap { display: none; }
.ap-reject-reason-wrap.is-visible { display: block; animation: ap-card-in .2s ease; }
.ap-reject-reason-wrap textarea {
  width: 100%;
  padding: .7rem .9rem;
  border: 1.5px solid var(--a-red-dim);
  border-radius: var(--a-radius-s);
  background: var(--bg-primary);
  font-size: .82rem;
  color: var(--text-primary);
  resize: vertical;
  min-height: 80px;
  transition: border-color var(--a-transition);
  line-height: 1.5;
}
.ap-reject-reason-wrap textarea:focus {
  outline: none;
  border-color: var(--a-red);
}

/* ════════════════════════════════════════
   INTERVIEW MODAL — specific styles
   ════════════════════════════════════════ */
.ap-interview-candidate-card {
  display: flex;
  align-items: center;
  gap: .85rem;
  padding: .85rem 1rem;
  background: var(--a-accent-dim);
  border-radius: var(--a-radius-m);
  border: 1px solid rgba(79,70,229,.2);
}
.ap-interview-candidate-card .ap-avatar { border-radius: 12px; flex-shrink: 0; }
.ap-interview-candidate-info h4 {
  font-family: var(--a-font-heading);
  font-size: .95rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 2px;
}
.ap-interview-candidate-info p {
  font-size: .76rem;
  color: var(--text-muted);
  margin: 0;
}
.ap-interview-candidate-info .job-label {
  font-size: .72rem;
  color: var(--a-accent);
  font-family: var(--a-font-mono);
  margin-top: 2px;
}

.ap-form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .75rem;
}
.ap-form-group { display: flex; flex-direction: column; gap: .35rem; }
.ap-form-group label {
  font-size: .72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: var(--text-muted);
}
.ap-form-group input,
.ap-form-group select,
.ap-form-group textarea {
  padding: .6rem .85rem;
  border: 1.5px solid var(--border-color);
  border-radius: var(--a-radius-s);
  background: var(--bg-secondary);
  font-size: .84rem;
  transition: border-color var(--a-transition), box-shadow var(--a-transition);
}
.ap-form-group input:focus,
.ap-form-group select:focus,
.ap-form-group textarea:focus {
  outline: none;
  border-color: var(--a-accent);
  box-shadow: 0 0 0 3px var(--a-accent-dim);
}
.ap-form-group textarea { resize: vertical; min-height: 80px; line-height: 1.5; }

.ap-type-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: .5rem;
}
.ap-type-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: .65rem .4rem;
  border: 1.5px solid var(--border-color);
  border-radius: var(--a-radius-s);
  background: var(--bg-secondary);
  font-size: .72rem;
  font-weight: 600;
  color: var(--text-muted);
  transition: all var(--a-transition);
  cursor: pointer;
  font-family: var(--a-font-body);
}
.ap-type-btn i { font-size: 1rem; }
.ap-type-btn:hover, .ap-type-btn.is-active {
  border-color: var(--a-accent);
  color: var(--a-accent);
  background: var(--a-accent-dim);
}

/* ── Shortlist celebrate animation ── */
@keyframes ap-pop {
  0%   { transform: scale(1); }
  50%  { transform: scale(1.04); }
  100% { transform: scale(1); }
}
.ap-card.ap-celebrating { animation: ap-pop .4s ease; }

/* ════════════════════════════════════════
   RESPONSIVE
   ════════════════════════════════════════ */
@media (max-width: 768px) {
  .ap-root { min-height: 100dvh; }
  .ap-topbar {
    align-items: stretch;
    gap: .75rem;
    padding: .85rem 1rem;
  }
  .ap-topbar-title h1 { font-size: 1.2rem; line-height: 1.25; }
  .ap-topbar-actions { width: 100%; }
  .ap-job-select { width: 100%; max-width: none; }
  .ap-controls { padding: .75rem 1rem; gap: .55rem; }
  .ap-search { min-width: 0; flex-basis: 100%; }
  .ap-filter-select { width: 100%; min-height: 42px; }
  .ap-list-wrap {
    min-width: 0;
    padding: .85rem 1rem calc(88px + env(safe-area-inset-bottom));
    gap: .7rem;
  }
  .ap-state { gap: .8rem; padding: 2.5rem 1rem; }
  .ap-state-icon { width: 56px; height: 56px; border-radius: 16px; font-size: 1.35rem; }
  .ap-state h3 { font-size: .98rem; }
  .ap-state p { font-size: .8rem; }
  .ap-card-head { gap: .7rem; padding: 1rem 1rem .7rem 1.2rem; }
  .ap-card-meta { gap: .4rem; padding: 0 1rem .7rem 1.2rem; }
  .ap-cover-preview,
  .ap-employer-note { margin-right: 1rem; margin-left: 1.2rem; }
  .ap-card-actions { flex-direction: column; align-items: stretch; padding: .75rem 1rem .75rem 1.2rem; }
  .ap-action-primary,
  .ap-action-secondary { width: 100%; justify-content: flex-start; }
  .ap-btn { min-height: 40px; white-space: normal; justify-content: center; flex: 1 1 135px; }
  .ap-form-row { grid-template-columns: 1fr; }
  .ap-detail-info-grid { grid-template-columns: 1fr; }
  .ap-modal {
    max-width: 100%;
    max-height: calc(100dvh - env(safe-area-inset-top));
    border-radius: var(--a-radius-l) var(--a-radius-l) 0 0;
    margin-top: auto;
  }
  .ap-modal-overlay { align-items: flex-end; padding: 0; }
  .ap-type-grid { grid-template-columns: repeat(3,1fr); }
  .ap-stat-tab { padding: .7rem .85rem; }
  .ap-results-count { display: none; }
  .ap-status-actions-grid { grid-template-columns: repeat(3,1fr); }
  .ap-modal-head { padding: 1rem; }
  .ap-modal-body { padding: 1rem; gap: 1rem; }
  .ap-modal-footer { padding: .8rem 1rem; }
  .ap-form-group input,
  .ap-form-group select,
  .ap-form-group textarea { font-size: 16px; }
}
@media (max-width: 480px) {
  .ap-topbar-title h1 { font-size: 1.1rem; }
  .ap-topbar-title p { font-size: .7rem; }
  .ap-list-wrap { padding-right: .75rem; padding-left: .75rem; }
  .ap-card-head { flex-wrap: wrap; }
  .ap-avatar { width: 40px; height: 40px; border-radius: 12px; }
  .ap-card-badges {
    flex: 1 1 100%;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    margin-left: 47px;
  }
  .ap-card-meta { padding-right: .75rem; padding-left: 1.1rem; }
  .ap-cover-preview,
  .ap-employer-note { margin-right: .75rem; margin-left: 1.1rem; }
  .ap-card-actions { padding-right: .75rem; padding-left: 1.1rem; }
  .ap-status-actions-grid { grid-template-columns: repeat(2,minmax(0,1fr)); }
  .ap-type-btn { min-height: 60px; }
}

/* ── Utility ── */
.ap-hidden { display: none !important; }
.ap-full-btn {
  width: 100%;
  padding: .7rem;
  border-radius: var(--a-radius-m);
  border: none;
  background: linear-gradient(135deg, var(--a-accent), var(--a-violet));
  color: #fff;
  font-family: var(--a-font-heading);
  font-size: .9rem;
  font-weight: 700;
  cursor: pointer;
  transition: all var(--a-transition);
  box-shadow: 0 4px 14px rgba(79,70,229,.35);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: .5rem;
}
.ap-full-btn:hover { box-shadow: 0 6px 20px rgba(79,70,229,.45); transform: translateY(-1px); }
.ap-full-btn:disabled { opacity: .6; pointer-events: none; }
</style>

<div class="ap-root">

  <!-- ═══ TOP BAR ═══ -->
  <div class="ap-topbar">
    <div class="ap-topbar-title">
      <h1>Applications</h1>
      <p id="ap-subtitle">Loading jobs…</p>
    </div>
    <div class="ap-topbar-actions">
      <select class="ap-job-select" id="apJobSelect">
        <option value="">All Positions</option>
      </select>
    </div>
  </div>

  <!-- ═══ STATS RIBBON ═══ -->
  <div class="ap-stats-ribbon" id="apStatsRibbon">
    <!-- Injected by JS -->
    <button class="ap-stat-tab is-active" data-filter="all">
      <span class="tab-count" id="cnt-all">—</span>
      <span class="tab-label">All</span>
    </button>
    <button class="ap-stat-tab" data-filter="pending">
      <span class="tab-indicator" style="background:var(--a-amber)"></span>
      <span class="tab-count" id="cnt-pending">—</span>
      <span class="tab-label">Awaiting Review</span>
    </button>
    <button class="ap-stat-tab" data-filter="reviewed">
      <span class="tab-count" id="cnt-reviewed">—</span>
      <span class="tab-label">Reviewed</span>
    </button>
    <button class="ap-stat-tab" data-filter="shortlisted">
      <span class="tab-indicator" style="background:var(--a-violet)"></span>
      <span class="tab-count" id="cnt-shortlisted">—</span>
      <span class="tab-label">Shortlisted</span>
    </button>
    <button class="ap-stat-tab" data-filter="accepted">
      <span class="tab-indicator" style="background:var(--a-green)"></span>
      <span class="tab-count" id="cnt-accepted">—</span>
      <span class="tab-label">Accepted</span>
    </button>
    <button class="ap-stat-tab" data-filter="rejected">
      <span class="tab-count" id="cnt-rejected">—</span>
      <span class="tab-label">Rejected</span>
    </button>
  </div>

  <!-- ═══ CONTROL BAR ═══ -->
  <div class="ap-controls">
    <div class="ap-search">
      <i class="fas fa-search"></i>
      <input type="text" id="apSearch" placeholder="Search by name, email or position…">
    </div>
    <select class="ap-filter-select" id="apSortSelect">
      <option value="newest">Newest first</option>
      <option value="oldest">Oldest first</option>
      <option value="name">Name A–Z</option>
    </select>
    <span class="ap-results-count" id="apResultsCount">— applications</span>
  </div>

  <!-- ═══ APPLICATION LIST ═══ -->
  <div class="ap-list-wrap" id="apListWrap">
    <!-- Skeletons on initial load -->
    <div class="ap-skeleton" style="animation-delay:.04s">
      <div class="ap-skel-inner">
        <div class="ap-skel-row">
          <div class="ap-skel-bar" style="width:46px;height:46px;border-radius:14px;flex-shrink:0"></div>
          <div style="flex:1;display:flex;flex-direction:column;gap:6px">
            <div class="ap-skel-bar" style="height:14px;width:55%"></div>
            <div class="ap-skel-bar" style="height:11px;width:40%"></div>
          </div>
          <div class="ap-skel-bar" style="width:80px;height:22px;border-radius:100px"></div>
        </div>
        <div class="ap-skel-bar" style="height:10px;width:30%"></div>
        <div class="ap-skel-bar" style="height:32px;width:100%"></div>
      </div>
    </div>
    <div class="ap-skeleton" style="animation-delay:.1s">
      <div class="ap-skel-inner">
        <div class="ap-skel-row">
          <div class="ap-skel-bar" style="width:46px;height:46px;border-radius:14px;flex-shrink:0"></div>
          <div style="flex:1;display:flex;flex-direction:column;gap:6px">
            <div class="ap-skel-bar" style="height:14px;width:45%"></div>
            <div class="ap-skel-bar" style="height:11px;width:35%"></div>
          </div>
          <div class="ap-skel-bar" style="width:70px;height:22px;border-radius:100px"></div>
        </div>
        <div class="ap-skel-bar" style="height:10px;width:25%"></div>
      </div>
    </div>
    <div class="ap-skeleton" style="animation-delay:.16s">
      <div class="ap-skel-inner">
        <div class="ap-skel-row">
          <div class="ap-skel-bar" style="width:46px;height:46px;border-radius:14px;flex-shrink:0"></div>
          <div style="flex:1;display:flex;flex-direction:column;gap:6px">
            <div class="ap-skel-bar" style="height:14px;width:60%"></div>
            <div class="ap-skel-bar" style="height:11px;width:42%"></div>
          </div>
          <div class="ap-skel-bar" style="width:90px;height:22px;border-radius:100px"></div>
        </div>
        <div class="ap-skel-bar" style="height:10px;width:35%"></div>
        <div class="ap-skel-bar" style="height:44px;width:100%"></div>
      </div>
    </div>
  </div>

</div><!-- .ap-root -->

<!-- ═══════════════════════════════════════════
     MODAL: APPLICATION DETAIL
     ═══════════════════════════════════════════ -->
<div class="ap-modal-overlay" id="apDetailModal" role="dialog" aria-modal="true" aria-label="Application detail">
  <div class="ap-modal">
    <div class="ap-modal-head">
      <h2>Application Detail</h2>
      <button class="ap-modal-close" data-close="apDetailModal" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <div class="ap-modal-body" id="apDetailBody">
      <!-- Injected by JS -->
    </div>
    <div class="ap-modal-footer">
      <button class="ap-btn ap-btn-ghost" data-close="apDetailModal">Close</button>
      <button class="ap-btn ap-btn-interview" id="apDetailScheduleBtn"><i class="fas fa-calendar-alt"></i> Schedule Interview</button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════
     MODAL: SCHEDULE INTERVIEW
     ═══════════════════════════════════════════ -->
<div class="ap-modal-overlay" id="apInterviewModal" role="dialog" aria-modal="true" aria-label="Schedule interview">
  <div class="ap-modal" style="max-width:580px">
    <div class="ap-modal-head">
      <h2><i class="fas fa-calendar-check" style="color:var(--a-accent);margin-right:.4rem"></i>Schedule Interview</h2>
      <button class="ap-modal-close" data-close="apInterviewModal" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <div class="ap-modal-body">
      <!-- Candidate card -->
      <div class="ap-interview-candidate-card" id="apInterviewCandidate"></div>

      <!-- Interview type selector -->
      <div class="ap-form-group">
        <label>Interview Format</label>
        <div class="ap-type-grid" id="apInterviewTypeGrid">
          <button type="button" class="ap-type-btn is-active" data-type="video">
            <i class="fas fa-video"></i>Video Call
          </button>
          <button type="button" class="ap-type-btn" data-type="phone">
            <i class="fas fa-phone"></i>Phone
          </button>
          <button type="button" class="ap-type-btn" data-type="in_person">
            <i class="fas fa-building"></i>In Person
          </button>
        </div>
      </div>

      <!-- Date / Duration -->
      <div class="ap-form-row">
        <div class="ap-form-group">
          <label for="apIntDate">Date &amp; Time <span style="color:var(--a-red)">*</span></label>
          <input type="datetime-local" id="apIntDate" required>
        </div>
        <div class="ap-form-group">
          <label for="apIntDuration">Duration (minutes)</label>
          <select id="apIntDuration">
            <option value="30">30 min</option>
            <option value="45">45 min</option>
            <option value="60" selected>60 min</option>
            <option value="90">90 min</option>
            <option value="120">120 min</option>
          </select>
        </div>
      </div>

      <!-- Link / Location (conditional) -->
      <div class="ap-form-group" id="apIntLinkWrap">
        <label for="apIntLink">Meeting Link</label>
        <input type="url" id="apIntLink" placeholder="https://meet.google.com/…">
      </div>
      <div class="ap-form-group ap-hidden" id="apIntLocationWrap">
        <label for="apIntLocation">Office / Address</label>
        <input type="text" id="apIntLocation" placeholder="Floor 4, ABC Building, Nairobi">
      </div>

      <!-- Notes -->
      <div class="ap-form-group">
        <label for="apIntNotes">Notes to candidate <span style="font-weight:400;text-transform:none;letter-spacing:0">(optional)</span></label>
        <textarea id="apIntNotes" placeholder="Add any preparation instructions or agenda items…"></textarea>
      </div>
    </div>
    <div class="ap-modal-footer">
      <button class="ap-btn ap-btn-ghost" data-close="apInterviewModal">Cancel</button>
      <button class="ap-full-btn" id="apIntSubmitBtn" style="width:auto;padding:.6rem 1.5rem">
        <i class="fas fa-paper-plane"></i> Confirm &amp; Send
      </button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════
     MODAL: REJECT WITH REASON
     ═══════════════════════════════════════════ -->
<div class="ap-modal-overlay" id="apRejectModal" role="dialog" aria-modal="true" aria-label="Reject application">
  <div class="ap-modal" style="max-width:460px">
    <div class="ap-modal-head">
      <h2 style="color:var(--a-red)"><i class="fas fa-times-circle" style="margin-right:.4rem"></i>Decline Application</h2>
      <button class="ap-modal-close" data-close="apRejectModal" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <div class="ap-modal-body">
      <p style="font-size:.85rem;color:var(--text-muted);margin:0">You're about to decline <strong id="apRejectName" style="color:var(--text-primary)"></strong>'s application. Optionally leave a reason — it won't be shared unless you choose to.</p>
      <div class="ap-form-group">
        <label for="apRejectReason">Reason (internal note)</label>
        <textarea id="apRejectReason" placeholder="e.g. Insufficient experience for the role's requirements…" style="min-height:100px"></textarea>
      </div>
      <div class="ap-form-group">
        <label for="apRejectNote">Additional note (optional)</label>
        <input type="text" id="apRejectNote" placeholder="Any follow-up action…">
      </div>
    </div>
    <div class="ap-modal-footer">
      <button class="ap-btn ap-btn-ghost" data-close="apRejectModal">Keep reviewing</button>
      <button class="ap-btn ap-btn-reject" id="apRejectConfirmBtn"><i class="fas fa-times"></i> Confirm Decline</button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════
     MODAL: STATUS NOTE (reviewed / accepted / shortlist)
     ═══════════════════════════════════════════ -->
<div class="ap-modal-overlay" id="apStatusNoteModal" role="dialog" aria-modal="true" aria-label="Add status note">
  <div class="ap-modal" style="max-width:440px">
    <div class="ap-modal-head">
      <h2 id="apStatusNoteTitle">Update Status</h2>
      <button class="ap-modal-close" data-close="apStatusNoteModal" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <div class="ap-modal-body">
      <p style="font-size:.85rem;color:var(--text-muted);margin:0" id="apStatusNoteDesc"></p>
      <div class="ap-form-group" style="margin-top:.2rem">
        <label for="apStatusNote">Internal note (optional)</label>
        <textarea id="apStatusNote" placeholder="Add context visible only to your team…" style="min-height:90px"></textarea>
      </div>
    </div>
    <div class="ap-modal-footer">
      <button class="ap-btn ap-btn-ghost" data-close="apStatusNoteModal">Cancel</button>
      <button class="ap-btn ap-btn-review" id="apStatusNoteConfirm"><i class="fas fa-check"></i> Confirm</button>
    </div>
  </div>
</div>
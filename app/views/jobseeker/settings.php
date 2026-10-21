<div class="sp-page">

<style>
/* ════════════════════════════════════════════
   JOBSEEKER SETTINGS PAGE  ·  sp-page scope
   Aesthetic: Refined editorial cards —
   clean whites, sharp ink accents, deliberate
   whitespace, micro-interaction delight.
════════════════════════════════════════════ */

/* ── Base ─────────────────────────────────── */
.sp-page {
  flex: 1;
  min-height: 100vh;
  background: var(--bg-primary);
  padding: 2rem 2rem 6rem;
  font-family: var(--font-primary);
  overflow-x: hidden;
}

/* ── Page header ──────────────────────────── */
.sp-hero {
  margin-bottom: 2.25rem;
  padding-bottom: 1.75rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}
.sp-hero-left { display: flex; align-items: center; gap: 1.25rem; }

.sp-avatar-wrap {
  position: relative;
  width: 72px; height: 72px;
  flex-shrink: 0;
}
.sp-avatar {
  width: 72px; height: 72px; border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--bg-primary);
  box-shadow: 0 0 0 2px var(--primary-color);
  display: block;
}
.sp-avatar-placeholder {
  width: 72px; height: 72px; border-radius: 50%;
  background: var(--gradient-primary);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; font-weight: 700; color: #fff;
  font-family: var(--font-heading);
  box-shadow: 0 0 0 2px var(--primary-color), 0 0 0 4px var(--bg-primary);
}
.sp-avatar-edit-btn {
  position: absolute; bottom: 0; right: 0;
  width: 24px; height: 24px; border-radius: 50%;
  background: var(--gradient-primary); color: #fff;
  border: 2px solid var(--bg-primary);
  display: flex; align-items: center; justify-content: center;
  font-size: .6rem; cursor: pointer;
  transition: transform .2s, box-shadow .2s;
}
.sp-avatar-edit-btn:hover { transform: scale(1.15); box-shadow: 0 2px 8px rgba(67,97,238,.4); }
#sp-avatar-input { display: none; }

.sp-hero-info h1 {
  font-size: 1.5rem; font-weight: 700;
  color: var(--text-primary); font-family: var(--font-heading);
  margin: 0 0 .2rem; line-height: 1.2;
}
.sp-hero-info p { color: var(--text-muted); font-size: .85rem; margin: 0; }

.sp-completion-wrap { display: flex; flex-direction: column; align-items: flex-end; gap: .4rem; }
.sp-completion-label { font-size: .75rem; color: var(--text-muted); font-weight: 500; }
.sp-completion-bar {
  width: 160px; height: 6px;
  background: var(--bg-tertiary); border-radius: 3px; overflow: hidden;
}
.sp-completion-fill {
  height: 100%; border-radius: 3px;
  background: var(--gradient-primary);
  transition: width .6s ease;
}
.sp-completion-pct { font-size: .8rem; font-weight: 700; color: var(--primary-color); }

/* ── Layout: sidebar + content ────────────── */
.sp-layout {
  display: grid;
  grid-template-columns: 220px 1fr;
  gap: 1.5rem;
  align-items: start;
}
@media (max-width: 860px) {
  .sp-layout { grid-template-columns: 1fr; }
  .sp-sidenav { display: flex; flex-wrap: wrap; flex-direction: row; gap: .4rem; border-right: none; padding-right: 0; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: .5rem; }
}

/* ── Side nav ─────────────────────────────── */
.sp-sidenav {
  position: sticky; top: 1rem;
  border-right: 1px solid var(--border-color);
  padding-right: 1.25rem;
}
.sp-nav-item {
  display: flex; align-items: center; gap: .7rem;
  padding: .6rem .85rem; border-radius: 9px;
  font-size: .85rem; font-weight: 500;
  color: var(--text-secondary); cursor: pointer;
  transition: background .15s, color .15s;
  border: none; background: transparent;
  font-family: var(--font-primary); width: 100%;
  text-align: left; margin-bottom: .15rem;
}
.sp-nav-item i { width: 16px; text-align: center; font-size: .85rem; color: var(--text-muted); transition: color .15s; }
.sp-nav-item:hover { background: var(--bg-secondary); color: var(--text-primary); }
.sp-nav-item:hover i { color: var(--primary-color); }
.sp-nav-item.active { background: var(--primary-light, #e0e7ff); color: var(--primary-color); font-weight: 600; }
[data-theme="dark"] .sp-nav-item.active { background: rgba(67,97,238,.18); }
.sp-nav-item.active i { color: var(--primary-color); }
.sp-nav-divider { height: 1px; background: var(--border-color); margin: .6rem 0; }
.sp-nav-item.danger:hover { background: #fee2e2; color: var(--danger-color); }
[data-theme="dark"] .sp-nav-item.danger:hover { background: #7f1d1d; color: #f87171; }
.sp-nav-item.danger:hover i { color: var(--danger-color); }

/* ── Sections ─────────────────────────────── */
.sp-section { display: none; }
.sp-section.active { display: block; animation: sp-fadein .22s ease; }
@keyframes sp-fadein { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }

/* ── Card ─────────────────────────────────── */
.sp-card {
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 14px; padding: 1.5rem;
  margin-bottom: 1.25rem;
}
.sp-card-title {
  font-size: .95rem; font-weight: 700;
  color: var(--text-primary); font-family: var(--font-heading);
  margin: 0 0 1.25rem;
  display: flex; align-items: center; gap: .55rem;
}
.sp-card-title i { color: var(--primary-color); font-size: .9rem; }
.sp-card-title .sp-card-badge {
  margin-left: auto; font-size: .65rem; font-weight: 700;
  padding: .2rem .55rem; border-radius: 50px;
  background: var(--bg-tertiary); color: var(--text-muted);
  font-family: var(--font-primary); text-transform: uppercase; letter-spacing: .05em;
}

/* ── Form elements ────────────────────────── */
.sp-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.sp-form-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
.sp-form-grid.cols-1 { grid-template-columns: 1fr; }
@media (max-width: 640px) { .sp-form-grid, .sp-form-grid.cols-3 { grid-template-columns: 1fr; } }

.sp-field { display: flex; flex-direction: column; gap: .38rem; }
.sp-field.sp-span-2 { grid-column: span 2; }
@media (max-width: 640px) { .sp-field.sp-span-2 { grid-column: span 1; } }

.sp-label {
  font-size: .75rem; font-weight: 600; color: var(--text-secondary);
  letter-spacing: .03em; text-transform: uppercase;
}
.sp-input, .sp-select, .sp-textarea {
  padding: .65rem .9rem;
  border: 1.5px solid var(--border-color); border-radius: 9px;
  background: var(--bg-primary); color: var(--text-primary);
  font-size: .875rem; font-family: var(--font-primary);
  transition: border-color .2s, box-shadow .2s;
  width: 100%;
}
.sp-input:focus, .sp-select:focus, .sp-textarea:focus {
  outline: none; border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(67,97,238,.1);
}
.sp-input.err, .sp-select.err, .sp-textarea.err {
  border-color: var(--danger-color);
  box-shadow: 0 0 0 3px rgba(239,68,68,.1);
}
.sp-textarea { resize: vertical; min-height: 90px; line-height: 1.55; }
.sp-select { appearance: none; cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right .8rem center; padding-right: 2.2rem;
}
.sp-field-hint { font-size: .72rem; color: var(--text-muted); }
.sp-field-err  { font-size: .72rem; color: var(--danger-color); display: none; }
.sp-field-err.show { display: block; }

/* Input with icon */
.sp-input-icon-wrap { position: relative; }
.sp-input-icon-wrap i {
  position: absolute; left: .85rem; top: 50%;
  transform: translateY(-50%); color: var(--text-muted);
  font-size: .8rem; pointer-events: none;
}
.sp-input-icon-wrap .sp-input { padding-left: 2.4rem; }

/* ── Toggle switch ────────────────────────── */
.sp-toggle-row {
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 1rem; padding: .9rem 0;
  border-bottom: 1px solid var(--border-color);
}
.sp-toggle-row:last-child { border-bottom: none; padding-bottom: 0; }
.sp-toggle-info h4 { font-size: .88rem; font-weight: 600; color: var(--text-primary); margin: 0 0 .18rem; }
.sp-toggle-info p  { font-size: .78rem; color: var(--text-muted); margin: 0; }

.sp-toggle {
  position: relative; display: inline-block;
  width: 44px; height: 24px; flex-shrink: 0;
}
.sp-toggle input { opacity: 0; width: 0; height: 0; }
.sp-toggle-slider {
  position: absolute; cursor: pointer;
  inset: 0; background: var(--bg-tertiary);
  border-radius: 24px; transition: background .2s;
  border: 1.5px solid var(--border-color);
}
.sp-toggle-slider::before {
  content: ''; position: absolute;
  width: 16px; height: 16px; border-radius: 50%;
  left: 3px; bottom: 3px; background: var(--text-muted);
  transition: transform .2s, background .2s;
}
.sp-toggle input:checked + .sp-toggle-slider { background: var(--primary-color); border-color: var(--primary-color); }
.sp-toggle input:checked + .sp-toggle-slider::before { transform: translateX(20px); background: #fff; }

/* ── Skills ───────────────────────────────── */
.sp-skills-grid { display: flex; flex-wrap: wrap; gap: .55rem; margin-bottom: 1rem; }
.sp-skill-tag {
  display: inline-flex; align-items: center; gap: .45rem;
  padding: .38rem .8rem; border-radius: 50px;
  background: var(--bg-tertiary); border: 1px solid var(--border-color);
  font-size: .78rem; font-weight: 500; color: var(--text-secondary);
  transition: all .15s;
}
.sp-skill-tag .sp-skill-level {
  font-size: .62rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .04em;
  padding: .1rem .4rem; border-radius: 50px;
}
.sp-skill-tag .sp-skill-level.expert       { background: #d1fae5; color: #065f46; }
.sp-skill-tag .sp-skill-level.advanced     { background: #dbeafe; color: #1e40af; }
.sp-skill-tag .sp-skill-level.intermediate { background: #ede9fe; color: #5b21b6; }
.sp-skill-tag .sp-skill-level.beginner     { background: #fef3c7; color: #92400e; }
[data-theme="dark"] .sp-skill-tag .sp-skill-level.expert       { background: #064e3b; color: #6ee7b7; }
[data-theme="dark"] .sp-skill-tag .sp-skill-level.advanced     { background: #1e3a5f; color: #93c5fd; }
[data-theme="dark"] .sp-skill-tag .sp-skill-level.intermediate { background: #2e1065; color: #c4b5fd; }
[data-theme="dark"] .sp-skill-tag .sp-skill-level.beginner     { background: #451a03; color: #fcd34d; }

.sp-skill-remove {
  width: 16px; height: 16px; border-radius: 50%;
  background: var(--bg-tertiary); border: 1px solid var(--border-color);
  color: var(--text-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: .55rem; padding: 0; line-height: 1;
  transition: background .15s, color .15s;
}
.sp-skill-remove:hover { background: #fee2e2; color: var(--danger-color); border-color: var(--danger-color); }

/* ── Experience / Education item ──────────── */
.sp-entry-item {
  background: var(--bg-primary); border: 1px solid var(--border-color);
  border-radius: 10px; padding: 1rem 1.1rem;
  margin-bottom: .75rem; position: relative;
  transition: box-shadow .15s;
}
.sp-entry-item:hover { box-shadow: var(--shadow-sm); }
.sp-entry-title { font-size: .9rem; font-weight: 600; color: var(--text-primary); margin-bottom: .2rem; }
.sp-entry-sub   { font-size: .8rem; color: var(--text-muted); margin-bottom: .35rem; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.sp-entry-desc  { font-size: .78rem; color: var(--text-secondary); line-height: 1.5; }
.sp-entry-badge {
  display: inline-flex; align-items: center; gap: .25rem;
  font-size: .65rem; font-weight: 700; padding: .15rem .5rem;
  border-radius: 50px; background: #d1fae5; color: #065f46;
  text-transform: uppercase; letter-spacing: .04em;
}
[data-theme="dark"] .sp-entry-badge { background: #064e3b; color: #6ee7b7; }

.sp-entry-actions {
  position: absolute; top: .8rem; right: .9rem;
  display: flex; gap: .3rem;
}
.sp-entry-action-btn {
  width: 28px; height: 28px; border-radius: 7px;
  border: 1px solid var(--border-color); background: var(--bg-secondary);
  color: var(--text-muted); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: .72rem; transition: all .15s;
}
.sp-entry-action-btn:hover { border-color: var(--primary-color); color: var(--primary-color); background: var(--bg-primary); }
.sp-entry-action-btn.del:hover { border-color: var(--danger-color); color: var(--danger-color); background: #fee2e2; }
[data-theme="dark"] .sp-entry-action-btn.del:hover { background: #7f1d1d; }

/* ── Add entry button ─────────────────────── */
.sp-add-btn {
  display: flex; align-items: center; gap: .55rem;
  padding: .65rem 1rem; border-radius: 9px;
  border: 1.5px dashed var(--border-color);
  background: transparent; color: var(--text-muted);
  font-size: .82rem; font-weight: 500; cursor: pointer;
  font-family: var(--font-primary); width: 100%;
  justify-content: center; transition: all .2s;
}
.sp-add-btn:hover { border-color: var(--primary-color); color: var(--primary-color); background: var(--bg-secondary); }

/* ── Inline form (add/edit entry) ─────────── */
.sp-inline-form {
  background: var(--bg-tertiary); border: 1px solid var(--border-color);
  border-radius: 11px; padding: 1.1rem;
  margin-bottom: .75rem;
  animation: sp-fadein .18s ease;
}
.sp-inline-form-title { font-size: .82rem; font-weight: 700; color: var(--text-primary); margin-bottom: .9rem; display: flex; align-items: center; gap: .4rem; }

/* ── Security section ─────────────────────── */
.sp-security-row {
  display: flex; align-items: center; gap: 1rem;
  padding: 1rem 0; border-bottom: 1px solid var(--border-color);
  flex-wrap: wrap;
}
.sp-security-row:last-child { border-bottom: none; }
.sp-security-icon {
  width: 40px; height: 40px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: .95rem; flex-shrink: 0;
}
.sp-security-icon.blue   { background: #dbeafe; color: #2563eb; }
.sp-security-icon.green  { background: #d1fae5; color: #059669; }
.sp-security-icon.amber  { background: #fef3c7; color: #d97706; }
.sp-security-icon.red    { background: #fee2e2; color: #dc2626; }
.sp-security-icon.purple { background: #ede9fe; color: #7c3aed; }
[data-theme="dark"] .sp-security-icon.blue   { background: #1e3a5f; color: #60a5fa; }
[data-theme="dark"] .sp-security-icon.green  { background: #064e3b; color: #34d399; }
[data-theme="dark"] .sp-security-icon.amber  { background: #451a03; color: #fbbf24; }
[data-theme="dark"] .sp-security-icon.red    { background: #7f1d1d; color: #f87171; }
[data-theme="dark"] .sp-security-icon.purple { background: #2e1065; color: #c4b5fd; }

.sp-security-info { flex: 1; min-width: 0; }
.sp-security-info h4 { font-size: .88rem; font-weight: 600; color: var(--text-primary); margin: 0 0 .15rem; }
.sp-security-info p  { font-size: .78rem; color: var(--text-muted); margin: 0; }

/* ── CV upload zone ───────────────────────── */
.sp-cv-dropzone {
  border: 2px dashed var(--border-color);
  border-radius: 12px;
  padding: 1.75rem 1.25rem;
  text-align: center;
  cursor: pointer;
  transition: border-color .2s, background .2s;
  background: var(--bg-primary);
}
.sp-cv-dropzone:hover,
.sp-cv-dropzone.drag-over {
  border-color: var(--primary-color);
  background: rgba(67,97,238,.04);
}
.sp-cv-dropzone i {
  font-size: 2rem;
  color: var(--text-muted);
  display: block;
  margin-bottom: .65rem;
  transition: color .2s;
}
.sp-cv-dropzone:hover i,
.sp-cv-dropzone.drag-over i { color: var(--primary-color); }
.sp-cv-dropzone p {
  font-size: .85rem; color: var(--text-secondary);
  margin: 0 0 .3rem; font-weight: 500;
}
.sp-cv-dropzone span {
  font-size: .75rem; color: var(--text-muted);
}

/* CV uploaded state */
.sp-cv-uploaded {
  display: flex; align-items: center; gap: 1rem;
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: 12px; padding: 1rem 1.25rem;
}
.sp-cv-uploaded-icon {
  width: 44px; height: 44px; border-radius: 10px;
  background: rgba(239,68,68,.1);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem; color: #dc2626; flex-shrink: 0;
}
.sp-cv-uploaded-info { flex: 1; min-width: 0; }
.sp-cv-uploaded-name {
  font-size: .88rem; font-weight: 600; color: var(--text-primary);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  margin: 0 0 .2rem; display: block;
}
.sp-cv-uploaded-meta {
  font-size: .75rem; color: var(--text-muted); display: flex;
  align-items: center; gap: .55rem; flex-wrap: wrap;
}
.sp-cv-uploaded-meta span { display: inline-flex; align-items: center; gap: .25rem; }
.sp-cv-actions { display: flex; gap: .5rem; flex-shrink: 0; flex-wrap: wrap; }

/* CV upload progress bar */
.sp-cv-progress {
  height: 5px; background: var(--bg-tertiary);
  border-radius: 3px; overflow: hidden;
  margin-top: .75rem; display: none;
}
.sp-cv-progress.show { display: block; }
.sp-cv-progress-fill {
  height: 100%; width: 0%;
  background: var(--gradient-primary);
  border-radius: 3px;
  transition: width .25s ease;
}

/* CV hint below upload zone */
.sp-cv-hint {
  font-size: .73rem; color: var(--text-muted);
  margin-top: .6rem; display: flex; align-items: center; gap: .35rem;
}
.sp-cv-hint i { color: var(--primary-color); font-size: .7rem; }

/* ── Danger zone ──────────────────────────── */
.sp-danger-zone {
  border: 1.5px solid var(--danger-color, #ef4444); border-radius: 14px;
  padding: 1.5rem; margin-top: .5rem;
  background: rgba(239,68,68,.03);
}
[data-theme="dark"] .sp-danger-zone { background: rgba(239,68,68,.06); }
.sp-danger-title { font-size: .95rem; font-weight: 700; color: var(--danger-color); font-family: var(--font-heading); margin: 0 0 1rem; display: flex; align-items: center; gap: .55rem; }

/* ── Buttons ──────────────────────────────── */
.sp-btn-row { display: flex; gap: .65rem; align-items: center; flex-wrap: wrap; margin-top: 1.25rem; }
.sp-save-btn {
  padding: .65rem 1.6rem; border-radius: 9px;
  background: var(--gradient-primary); color: #fff;
  border: none; font-size: .875rem; font-weight: 600;
  font-family: var(--font-primary); cursor: pointer;
  transition: opacity .15s, transform .15s, box-shadow .2s;
  display: inline-flex; align-items: center; gap: .45rem;
}
.sp-save-btn:hover  { opacity: .9; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(67,97,238,.3); }
.sp-save-btn:active { transform: translateY(0); }
.sp-save-btn:disabled { opacity: .55; cursor: not-allowed; transform: none; }

.sp-cancel-btn {
  padding: .65rem 1.2rem; border-radius: 9px;
  background: var(--bg-secondary); color: var(--text-secondary);
  border: 1px solid var(--border-color); font-size: .875rem; font-weight: 500;
  font-family: var(--font-primary); cursor: pointer;
  transition: background .15s;
}
.sp-cancel-btn:hover { background: var(--bg-tertiary); }

/* Small icon button (download / delete CV) */
.sp-icon-btn {
  width: 34px; height: 34px; border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: .8rem; cursor: pointer; border: 1px solid var(--border-color);
  background: var(--bg-secondary); color: var(--text-muted);
  transition: all .15s; flex-shrink: 0;
}
.sp-icon-btn:hover { border-color: var(--primary-color); color: var(--primary-color); background: var(--bg-primary); }
.sp-icon-btn.danger:hover { border-color: var(--danger-color); color: var(--danger-color); background: #fee2e2; }
[data-theme="dark"] .sp-icon-btn.danger:hover { background: #7f1d1d; }

/* ── Upload progress ──────────────────────── */
.sp-upload-progress {
  height: 4px; background: var(--bg-tertiary);
  border-radius: 2px; overflow: hidden;
  margin-top: .5rem; display: none;
}
.sp-upload-progress.show { display: block; }
.sp-upload-fill {
  height: 100%; background: var(--gradient-primary);
  border-radius: 2px; width: 0%;
  transition: width .3s;
}

/* ── Unsaved changes indicator ────────────── */
.sp-unsaved-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--warning-color); display: none; flex-shrink: 0;
}
.sp-unsaved-dot.show { display: inline-block; }

/* ── Skeleton ─────────────────────────────── */
.sp-skel {
  background: linear-gradient(110deg, var(--bg-tertiary) 8%, var(--bg-secondary) 18%, var(--bg-tertiary) 33%);
  background-size: 200% 100%;
  animation: sp-shimmer 1.4s linear infinite; border-radius: 7px;
}
@keyframes sp-shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }

/* ── Loading overlay for card saves ──────── */
.sp-card { position: relative; overflow: hidden; }
.sp-card-loading {
  position: absolute; inset: 0; background: rgba(255,255,255,.7);
  display: none; align-items: center; justify-content: center;
  border-radius: 14px; z-index: 10;
}
[data-theme="dark"] .sp-card-loading { background: rgba(15,23,42,.7); }
.sp-card-loading.show { display: flex; }
.sp-card-loading i { font-size: 1.4rem; color: var(--primary-color); animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Error banner ─────────────────────────── */
.sp-page-error {
  background: #fee2e2; border: 1px solid #fca5a5; border-radius: 12px;
  padding: 1rem 1.4rem; display: none; align-items: center;
  gap: .9rem; color: #991b1b; margin-bottom: 1.5rem;
}
[data-theme="dark"] .sp-page-error { background: #7f1d1d; border-color: #b91c1c; color: #fca5a5; }
.sp-page-error.show { display: flex; }

/* ── Responsive ───────────────────────────── */
@media (max-width: 640px) {
  .sp-page { padding: 1rem 1rem 5rem; }
  .sp-hero { flex-direction: column; align-items: flex-start; }
  .sp-completion-wrap { align-items: flex-start; }
  .sp-completion-bar { width: 140px; }
  .sp-cv-uploaded { flex-wrap: wrap; }
  .sp-cv-actions { width: 100%; justify-content: flex-end; }
}
</style>


<!-- PAGE ERROR BANNER -->
<div class="sp-page-error" id="sp-page-error">
  <i class="fas fa-exclamation-circle"></i>
  <span id="sp-page-error-msg">Failed to load your settings.</span>
  <button class="sp-cancel-btn" style="margin-left:auto;padding:.3rem .8rem;font-size:.78rem;" id="sp-retry">
    <i class="fas fa-redo" style="margin-right:.3rem;"></i>Retry
  </button>
</div>

<!-- HERO (populated by JS) -->
<div class="sp-hero" id="sp-hero">
  <div class="sp-hero-left">
    <div class="sp-avatar-wrap">
      <div class="sp-avatar-placeholder sp-skel" style="box-shadow:none;" id="sp-avatar-display"></div>
      <button class="sp-avatar-edit-btn" id="sp-avatar-btn" title="Change photo">
        <i class="fas fa-camera"></i>
      </button>
      <input type="file" id="sp-avatar-input" accept="image/*">
    </div>
    <div class="sp-hero-info">
      <h1 id="sp-hero-name"><span class="sp-skel" style="width:180px;height:22px;display:block;"></span></h1>
      <p id="sp-hero-sub"><span class="sp-skel" style="width:140px;height:13px;display:block;margin-top:4px;"></span></p>
    </div>
  </div>
  <div class="sp-completion-wrap" id="sp-completion-wrap" style="display:none;">
    <span class="sp-completion-label">Profile Completion</span>
    <div class="sp-completion-bar">
      <div class="sp-completion-fill" id="sp-completion-fill" style="width:0%"></div>
    </div>
    <span class="sp-completion-pct" id="sp-completion-pct">0%</span>
  </div>
</div>


<!-- ══════════ LAYOUT ══════════ -->
<div class="sp-layout">

  <!-- SIDE NAV -->
  <nav class="sp-sidenav" id="sp-sidenav">
    <button class="sp-nav-item active" data-section="profile">
      <i class="fas fa-user"></i> Profile
    </button>
    <button class="sp-nav-item" data-section="skills">
      <i class="fas fa-code"></i> Skills
    </button>
    <button class="sp-nav-item" data-section="experience">
      <i class="fas fa-briefcase"></i> Experience
    </button>
    <button class="sp-nav-item" data-section="education">
      <i class="fas fa-graduation-cap"></i> Education
    </button>
    <button class="sp-nav-item" data-section="preferences">
      <i class="fas fa-sliders-h"></i> Preferences
    </button>
    <button class="sp-nav-item" data-section="documents">
      <i class="fas fa-file-alt"></i> CV &amp; Documents
    </button>
    <div class="sp-nav-divider"></div>
    <button class="sp-nav-item" data-section="security">
      <i class="fas fa-shield-alt"></i> Security
    </button>
    <div class="sp-nav-divider"></div>
    <button class="sp-nav-item danger" id="sp-logout-btn">
      <i class="fas fa-sign-out-alt"></i> Sign Out
    </button>
  </nav>


  <!-- SECTIONS -->
  <div id="sp-sections">

    <!-- ════ PROFILE ════ -->
    <div class="sp-section active" id="sec-profile">

      <div class="sp-card" id="card-profile">
        <div class="sp-card-loading"><i class="fas fa-circle-notch"></i></div>
        <h2 class="sp-card-title">
          <i class="fas fa-id-card"></i> Personal Information
          <span class="sp-unsaved-dot" id="dot-profile"></span>
        </h2>

        <div class="sp-form-grid">
          <div class="sp-field">
            <label class="sp-label">First Name</label>
            <input type="text" class="sp-input" id="p-first-name" placeholder="Your first name">
          </div>
          <div class="sp-field">
            <label class="sp-label">Last Name</label>
            <input type="text" class="sp-input" id="p-last-name" placeholder="Your last name">
          </div>
          <div class="sp-field sp-span-2">
            <label class="sp-label">Professional Title</label>
            <input type="text" class="sp-input" id="p-title" placeholder="e.g. Senior Frontend Developer">
            <span class="sp-field-hint">This shows up on your profile and applications.</span>
          </div>
          <div class="sp-field sp-span-2">
            <label class="sp-label">About Me</label>
            <textarea class="sp-textarea" id="p-summary" placeholder="Tell employers a little about yourself…"></textarea>
          </div>
        </div>

        <div class="sp-btn-row">
          <button class="sp-save-btn" id="save-profile">
            <i class="fas fa-check"></i> Save Changes
          </button>
        </div>
      </div>

      <div class="sp-card" id="card-career">
        <div class="sp-card-loading"><i class="fas fa-circle-notch"></i></div>
        <h2 class="sp-card-title">
          <i class="fas fa-chart-line"></i> Career Details
          <span class="sp-unsaved-dot" id="dot-career"></span>
        </h2>

        <div class="sp-form-grid">
          <div class="sp-field">
            <label class="sp-label">Years of Experience</label>
            <input type="number" class="sp-input" id="p-years-exp" min="0" max="50" placeholder="e.g. 3">
          </div>
          <div class="sp-field">
            <label class="sp-label">Expected Salary <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
            <div class="sp-input-icon-wrap">
              <i class="fas fa-dollar-sign"></i>
              <input type="number" class="sp-input" id="p-salary" min="0" placeholder="Annual amount">
            </div>
          </div>
          <div class="sp-field">
            <label class="sp-label">Salary Currency</label>
            <select class="sp-select" id="p-currency">
              <option value="KES">KES – Kenyan Shilling</option>
              <option value="USD">USD – US Dollar</option>
              <option value="EUR">EUR – Euro</option>
              <option value="GBP">GBP – British Pound</option>
              <option value="ZAR">ZAR – South African Rand</option>
            </select>
          </div>
        </div>

        <div class="sp-btn-row">
          <button class="sp-save-btn" id="save-career">
            <i class="fas fa-check"></i> Save Changes
          </button>
        </div>
      </div>

    </div><!-- /sec-profile -->


    <!-- ════ SKILLS ════ -->
    <div class="sp-section" id="sec-skills">
      <div class="sp-card" id="card-skills">
        <div class="sp-card-loading"><i class="fas fa-circle-notch"></i></div>
        <h2 class="sp-card-title">
          <i class="fas fa-code"></i> My Skills
          <span class="sp-card-badge" id="skills-count-badge">0</span>
        </h2>

        <div class="sp-skills-grid" id="skills-list"></div>

        <div id="sp-skill-form" class="sp-inline-form" style="display:none;">
          <div class="sp-inline-form-title"><i class="fas fa-plus-circle" style="color:var(--primary-color);"></i> Add a Skill</div>
          <div class="sp-form-grid cols-3">
            <div class="sp-field">
              <label class="sp-label">Skill Name</label>
              <input type="text" class="sp-input" id="skill-name-input" placeholder="e.g. JavaScript">
            </div>
            <div class="sp-field">
              <label class="sp-label">Proficiency</label>
              <select class="sp-select" id="skill-level-input">
                <option value="beginner">Beginner</option>
                <option value="intermediate" selected>Intermediate</option>
                <option value="advanced">Advanced</option>
                <option value="expert">Expert</option>
              </select>
            </div>
            <div class="sp-field">
              <label class="sp-label">Years Using It</label>
              <input type="number" class="sp-input" id="skill-years-input" min="0" max="30" placeholder="Optional">
            </div>
          </div>
          <div class="sp-btn-row">
            <button class="sp-save-btn" id="skill-save-btn"><i class="fas fa-check"></i> Add Skill</button>
            <button class="sp-cancel-btn" id="skill-cancel-btn">Cancel</button>
          </div>
        </div>

        <button class="sp-add-btn" id="sp-add-skill-btn">
          <i class="fas fa-plus"></i> Add a New Skill
        </button>
      </div>
    </div><!-- /sec-skills -->


    <!-- ════ EXPERIENCE ════ -->
    <div class="sp-section" id="sec-experience">
      <div class="sp-card" id="card-experience">
        <div class="sp-card-loading"><i class="fas fa-circle-notch"></i></div>
        <h2 class="sp-card-title">
          <i class="fas fa-briefcase"></i> Work Experience
        </h2>

        <div id="exp-list"></div>

        <div id="sp-exp-form" class="sp-inline-form" style="display:none;">
          <div class="sp-inline-form-title" id="exp-form-title"><i class="fas fa-plus-circle" style="color:var(--primary-color);"></i> Add Experience</div>
          <input type="hidden" id="exp-edit-id">
          <div class="sp-form-grid">
            <div class="sp-field">
              <label class="sp-label">Company / Organisation</label>
              <input type="text" class="sp-input" id="exp-company" placeholder="Where did you work?">
            </div>
            <div class="sp-field">
              <label class="sp-label">Your Role / Title</label>
              <input type="text" class="sp-input" id="exp-role" placeholder="e.g. Frontend Developer">
            </div>
            <div class="sp-field">
              <label class="sp-label">Start Year</label>
              <input type="number" class="sp-input" id="exp-start" placeholder="e.g. 2020" min="1990" max="2030">
            </div>
            <div class="sp-field">
              <label class="sp-label">End Year <span style="font-weight:400;text-transform:none;letter-spacing:0;">(leave blank if current)</span></label>
              <input type="number" class="sp-input" id="exp-end" placeholder="e.g. 2023" min="1990" max="2030">
            </div>
            <div class="sp-field sp-span-2">
              <label class="sp-label">What You Did</label>
              <textarea class="sp-textarea" id="exp-desc" placeholder="Briefly describe your responsibilities and achievements…"></textarea>
            </div>
          </div>
          <div class="sp-btn-row">
            <button class="sp-save-btn" id="exp-save-btn"><i class="fas fa-check"></i> Save</button>
            <button class="sp-cancel-btn" id="exp-cancel-btn">Cancel</button>
          </div>
        </div>

        <button class="sp-add-btn" id="sp-add-exp-btn">
          <i class="fas fa-plus"></i> Add Work Experience
        </button>
      </div>
    </div><!-- /sec-experience -->


    <!-- ════ EDUCATION ════ -->
    <div class="sp-section" id="sec-education">
      <div class="sp-card" id="card-education">
        <div class="sp-card-loading"><i class="fas fa-circle-notch"></i></div>
        <h2 class="sp-card-title">
          <i class="fas fa-graduation-cap"></i> Education
        </h2>

        <div id="edu-list"></div>

        <div id="sp-edu-form" class="sp-inline-form" style="display:none;">
          <div class="sp-inline-form-title" id="edu-form-title"><i class="fas fa-plus-circle" style="color:var(--primary-color);"></i> Add Education</div>
          <input type="hidden" id="edu-edit-id">
          <div class="sp-form-grid">
            <div class="sp-field sp-span-2">
              <label class="sp-label">School / University</label>
              <input type="text" class="sp-input" id="edu-school-input" placeholder="Search for your school…" autocomplete="off">
              <input type="hidden" id="edu-school-id">
              <div id="edu-school-suggestions" style="position:relative;"></div>
            </div>
            <div class="sp-field">
              <label class="sp-label">Level of Study</label>
              <select class="sp-select" id="edu-level">
                <option value="high_school">High School</option>
                <option value="certificate">Certificate</option>
                <option value="diploma">Diploma</option>
                <option value="bachelor">Bachelor's Degree</option>
                <option value="master">Master's Degree</option>
                <option value="phd">PhD / Doctorate</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="sp-field">
              <label class="sp-label">Field of Study</label>
              <input type="text" class="sp-input" id="edu-field" placeholder="e.g. Computer Science">
            </div>
            <div class="sp-field">
              <label class="sp-label">Start Year</label>
              <input type="number" class="sp-input" id="edu-start" placeholder="e.g. 2020" min="1980" max="2030">
            </div>
            <div class="sp-field">
              <label class="sp-label">End Year <span style="font-weight:400;text-transform:none;letter-spacing:0;">(blank if ongoing)</span></label>
              <input type="number" class="sp-input" id="edu-end" placeholder="e.g. 2024" min="1980" max="2035">
            </div>
            <div class="sp-field">
              <label class="sp-label">Grade / GPA <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
              <input type="text" class="sp-input" id="edu-grade" placeholder="e.g. First Class / 3.8">
            </div>
            <div class="sp-field sp-span-2">
              <label class="sp-label">Highlights / Description</label>
              <textarea class="sp-textarea" id="edu-desc" placeholder="Projects, achievements, societies…"></textarea>
            </div>
          </div>
          <div class="sp-btn-row">
            <button class="sp-save-btn" id="edu-save-btn"><i class="fas fa-check"></i> Save</button>
            <button class="sp-cancel-btn" id="edu-cancel-btn">Cancel</button>
          </div>
        </div>

        <button class="sp-add-btn" id="sp-add-edu-btn">
          <i class="fas fa-plus"></i> Add Education
        </button>
      </div>
    </div><!-- /sec-education -->


    <!-- ════ PREFERENCES ════ -->
    <div class="sp-section" id="sec-preferences">
      <div class="sp-card" id="card-prefs">
        <div class="sp-card-loading"><i class="fas fa-circle-notch"></i></div>
        <h2 class="sp-card-title">
          <i class="fas fa-sliders-h"></i> Job &amp; Work Preferences
        </h2>

        <div class="sp-toggle-row">
          <div class="sp-toggle-info">
            <h4>Open to Work</h4>
            <p>Let employers know you're actively looking for opportunities.</p>
          </div>
          <label class="sp-toggle">
            <input type="checkbox" id="pref-open-to-work">
            <span class="sp-toggle-slider"></span>
          </label>
        </div>

        <div class="sp-toggle-row">
          <div class="sp-toggle-info">
            <h4>Open to Remote Work</h4>
            <p>Show that you're comfortable working from home or remotely.</p>
          </div>
          <label class="sp-toggle">
            <input type="checkbox" id="pref-remote">
            <span class="sp-toggle-slider"></span>
          </label>
        </div>

        <div class="sp-toggle-row">
          <div class="sp-toggle-info">
            <h4>Willing to Relocate</h4>
            <p>Indicate you'd consider moving for the right opportunity.</p>
          </div>
          <label class="sp-toggle">
            <input type="checkbox" id="pref-relocate">
            <span class="sp-toggle-slider"></span>
          </label>
        </div>

        <div class="sp-btn-row">
          <button class="sp-save-btn" id="save-prefs">
            <i class="fas fa-check"></i> Save Preferences
          </button>
        </div>
      </div>

      <!-- Appearance Card -->
      <div class="sp-card" id="card-appearance">
        <h2 class="sp-card-title">
          <i class="fas fa-palette"></i> Appearance
        </h2>

        <div class="sp-toggle-row">
          <div class="sp-toggle-info">
            <h4>Dark Mode</h4>
            <p>Use a dark theme to reduce eye strain in low light environments.</p>
          </div>
          <label class="sp-toggle">
            <input type="checkbox" id="pref-dark-mode">
            <span class="sp-toggle-slider"></span>
          </label>
        </div>

        <div class="sp-btn-row">
          <button class="sp-save-btn" id="save-appearance">
            <i class="fas fa-check"></i> Save Appearance
          </button>
        </div>
      </div>
    </div><!-- /sec-preferences -->


    <!-- ════ CV & DOCUMENTS ════ -->
    <div class="sp-section" id="sec-documents">
      <div class="sp-card" id="card-cv">
        <div class="sp-card-loading"><i class="fas fa-circle-notch"></i></div>
        <h2 class="sp-card-title">
          <i class="fas fa-file-alt"></i> CV / Resume
        </h2>

        <!-- ── No CV uploaded yet ── -->
        <div id="cv-upload-zone">
          <div class="sp-cv-dropzone" id="sp-cv-dropzone" role="button" tabindex="0" aria-label="Upload your CV">
            <i class="fas fa-cloud-upload-alt"></i>
            <p>Drag &amp; drop your CV here, or <strong style="color:var(--primary-color);">click to browse</strong></p>
            <span>PDF, DOC or DOCX &nbsp;·&nbsp; Max 5 MB</span>
          </div>
          <div class="sp-cv-progress" id="sp-cv-progress">
            <div class="sp-cv-progress-fill" id="sp-cv-progress-fill"></div>
          </div>
          <p class="sp-cv-hint">
            <i class="fas fa-info-circle"></i>
            Your CV is sent automatically when you apply for jobs that require one. It is never shared without your consent.
          </p>
          <input type="file" id="sp-cv-input" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" style="display:none;">
        </div>

        <!-- ── CV uploaded ── (hidden until a CV exists) -->
        <div id="cv-uploaded-state" style="display:none;">
          <div class="sp-cv-uploaded">
            <div class="sp-cv-uploaded-icon">
              <i class="fas fa-file-pdf" id="cv-file-icon"></i>
            </div>
            <div class="sp-cv-uploaded-info">
              <span class="sp-cv-uploaded-name" id="cv-file-name">resume.pdf</span>
              <div class="sp-cv-uploaded-meta">
                <span><i class="fas fa-calendar-alt"></i> <span id="cv-upload-date">—</span></span>
                <span><i class="fas fa-shield-check"></i> Ready to use</span>
              </div>
            </div>
            <div class="sp-cv-actions">
              <button class="sp-icon-btn" id="cv-download-btn" title="Download your CV" aria-label="Download CV">
                <i class="fas fa-download"></i>
              </button>
              <button class="sp-icon-btn danger" id="cv-delete-btn" title="Remove your CV" aria-label="Delete CV">
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>
          </div>
          <p class="sp-cv-hint" style="margin-top:.75rem;">
            <i class="fas fa-info-circle"></i>
            Want to update your CV? Delete this one first, then upload the new version.
          </p>
        </div>

      </div>
    </div><!-- /sec-documents -->


    <!-- ════ SECURITY ════ -->
    <div class="sp-section" id="sec-security">

      <div class="sp-card">
        <h2 class="sp-card-title"><i class="fas fa-shield-alt"></i> Account Security</h2>

        <div class="sp-security-row">
          <div class="sp-security-icon blue"><i class="fas fa-envelope"></i></div>
          <div class="sp-security-info">
            <h4>Email Address</h4>
            <p id="sec-email-display">Loading…</p>
          </div>
          <span id="sec-email-verified" style="font-size:.72rem;font-weight:700;padding:.2rem .6rem;border-radius:50px;background:#d1fae5;color:#065f46;"></span>
        </div>

        <div class="sp-security-row">
          <div class="sp-security-icon green"><i class="fas fa-lock"></i></div>
          <div class="sp-security-info">
            <h4>Password</h4>
            <p id="sec-auth-hint">Update your login password.</p>
          </div>
          <button class="sp-save-btn" style="padding:.45rem 1rem;font-size:.78rem;" id="sec-change-pwd-btn">
            <i class="fas fa-key"></i> Change
          </button>
        </div>

        <!-- Change password inline form -->
        <div id="sp-pwd-form" class="sp-inline-form" style="display:none;margin-top:.75rem;">
          <div class="sp-inline-form-title"><i class="fas fa-key" style="color:var(--primary-color);"></i> Change Your Password</div>
          <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:.9rem;">We'll send a password reset link to your email address. Just click it to set a new password.</p>
          <div class="sp-btn-row" style="margin-top:.5rem;">
            <button class="sp-save-btn" id="sec-send-reset">
              <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
            <button class="sp-cancel-btn" id="sec-pwd-cancel">Cancel</button>
          </div>
        </div>

        <div class="sp-security-row">
          <div class="sp-security-icon amber"><i class="fas fa-sign-in-alt"></i></div>
          <div class="sp-security-info">
            <h4>Sign-In Method</h4>
            <p id="sec-provider-display">Loading…</p>
          </div>
        </div>

        <div class="sp-security-row">
          <div class="sp-security-icon blue"><i class="fas fa-calendar-alt"></i></div>
          <div class="sp-security-info">
            <h4>Member Since</h4>
            <p id="sec-member-since">Loading…</p>
          </div>
        </div>
      </div>

      <!-- Danger zone -->
      <div class="sp-danger-zone">
        <div class="sp-danger-title"><i class="fas fa-exclamation-triangle"></i> Danger Zone</div>
        <div class="sp-security-row" style="padding-top:0;">
          <div class="sp-security-icon red"><i class="fas fa-user-slash"></i></div>
          <div class="sp-security-info">
            <h4>Sign Out Everywhere</h4>
            <p>Sign out of all devices and sessions, including this one.</p>
          </div>
          <button class="sp-save-btn" style="background:var(--gradient-danger);padding:.45rem 1rem;font-size:.78rem;" id="sec-logout-all">
            <i class="fas fa-sign-out-alt"></i> Sign Out All
          </button>
        </div>
      </div>

    </div><!-- /sec-security -->

  </div><!-- /sp-sections -->

</div><!-- /sp-layout -->


<!-- ════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════ -->
<script>
(function () {
  'use strict';

  /* ─── API Endpoints ───────────────────────────────────── */
  const API = {
    settings        : '/api/jobseeker/settings',
    saveProfile     : '/api/jobseeker/profile-setup',
    updateProfile   : '/api/jobseeker/update-profile',        // POST — personal & summary
    updatePreferences: '/api/jobseeker/update-preferences',  // POST — preferences
    updateCareer    : '/api/jobseeker/update-career',        // POST — yearsExperience
    uploadPhoto     : '/api/jobseeker/upload-profile-photo',
    uploadCv        : '/api/jobseeker/upload-cv',            // POST multipart — field: "resume"
    deleteCv        : '/api/jobseeker/delete-cv',            // POST — no body needed
    saveTheme       : '/api/jobseeker/save-theme',           // POST — theme: 'light' | 'dark'
    schools         : '/api/schools',
    logout          : '/api/logout',
    resetPwd        : '/api/forgot-password',
  };

  /* ─── State ───────────────────────────────────────────── */
  let settings     = null;
  let schoolsCache = [];

  /* ─── DOM helpers ─────────────────────────────────────── */
  const $   = id => document.getElementById(id);
  const esc = s  => String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  const cap = s  => s ? s.charAt(0).toUpperCase()+s.slice(1) : '';

  function fmt(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', {day:'numeric',month:'long',year:'numeric'});
  }

  /** Return just the filename from a URL or path */
  function fileBaseName(url) {
    if (!url) return 'resume';
    return decodeURIComponent(url.split('/').pop().split('?')[0]) || 'resume';
  }

  /** Pick a file-type icon class based on extension */
  function cvIconClass(url) {
    if (!url) return 'fas fa-file-alt';
    const ext = url.split('.').pop().toLowerCase();
    if (ext === 'pdf') return 'fas fa-file-pdf';
    if (ext === 'doc' || ext === 'docx') return 'fas fa-file-word';
    return 'fas fa-file-alt';
  }

  /* ─── Generic fetch ───────────────────────────────────── */
  async function apiFetch(url, opts = {}) {
    const isFormData = opts.body instanceof FormData;
    const headers = {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(opts.headers || {})
    };

    // The browser must set the multipart boundary for FormData uploads.
    if (!isFormData && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }
    if (isFormData && headers['Content-Type']) {
      delete headers['Content-Type'];
    }
    
    const res  = await fetch(url, { ...opts, headers });
    const json = await res.json();
    if (!json.success) throw new Error(json.message || 'Request failed ('+res.status+')');
    return json;
  }

  /* ─── Toast / Confirm wrappers ───────────────────────── */
  const toast = {
    ok  : msg => window.Toast?.success(msg),
    err : msg => window.Toast?.error(msg),
    info: msg => window.Toast?.info(msg),
    warn: msg => window.Toast?.warning(msg),
  };

  async function confirm(opts) {
    if (window.Confirm?.show) return window.Confirm.show(opts);
    return window.confirm(opts.message || 'Are you sure?');
  }

  /* ─── Helpers ─────────────────────────────────────────── */
  function cardLoading(cardEl, show) {
    cardEl.querySelector('.sp-card-loading')?.style.setProperty('display', show ? 'flex' : 'none');
  }

  function setBtnLoading(btn, loading, label) {
    btn.disabled = loading;
    btn.innerHTML = loading
      ? '<i class="fas fa-circle-notch fa-spin"></i> Saving…'
      : `<i class="fas fa-check"></i> ${label||'Save Changes'}`;
  }

  /* ─── Section navigation ──────────────────────────────── */
  document.querySelectorAll('.sp-nav-item[data-section]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.sp-nav-item').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.sp-section').forEach(s => s.classList.remove('active'));
      btn.classList.add('active');
      $('sec-'+btn.dataset.section)?.classList.add('active');
    });
  });

  /* ─── Load settings ───────────────────────────────────── */
  async function loadSettings() {
    $('sp-page-error').classList.remove('show');
    try {
      const json = await apiFetch(API.settings);
      settings   = json.data;
      populateAll();
    } catch (err) {
      $('sp-page-error-msg').textContent = err.message || 'Could not load settings.';
      $('sp-page-error').classList.add('show');
    }
  }

  /* ─── Populate everything ─────────────────────────────── */
  function populateAll() {
    const { profile, skills, experience, education, location, user } = settings;

    // ── Hero ──
    const fullName = (profile.first_name||'')+' '+(profile.last_name||'');
    $('sp-hero-name').textContent = fullName.trim() || 'Your Name';
    $('sp-hero-sub').textContent  = profile.professional_title || 'No title set yet';

    // Avatar
    const avatarDisplay = $('sp-avatar-display');
    if (profile.avatar || user.avatar) {
      avatarDisplay.outerHTML = `<img src="${esc(profile.avatar||user.avatar)}" class="sp-avatar" id="sp-avatar-display" alt="Profile photo">`;
    } else {
      avatarDisplay.className   = 'sp-avatar-placeholder';
      avatarDisplay.textContent = (profile.first_name||'?').charAt(0).toUpperCase();
    }

    // Completion
    if (profile.profile_completion != null) {
      const pct = Math.min(100, Math.max(0, parseInt(profile.profile_completion)));
      $('sp-completion-fill').style.width = pct+'%';
      $('sp-completion-pct').textContent  = pct+'%';
      $('sp-completion-wrap').style.display = 'flex';
    }

    // ── Profile form ──
    $('p-first-name').value = profile.first_name || '';
    $('p-last-name').value  = profile.last_name  || '';
    $('p-title').value      = profile.professional_title || '';
    $('p-summary').value    = profile.summary || '';

    // ── Career form ──
    $('p-years-exp').value = profile.years_experience ?? '';
    $('p-salary').value    = profile.salary_expectation ?? '';
    $('p-currency').value  = profile.salary_currency || 'KES';

    // ── Preferences ──
    $('pref-open-to-work').checked = !!profile.open_to_work;
    $('pref-remote').checked       = !!profile.remote_preference;
    $('pref-relocate').checked     = !!profile.relocation_willing;

    // ── Theme / Appearance ──
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    $('pref-dark-mode').checked = currentTheme === 'dark';

    // ── Skills ──
    renderSkills(skills || []);

    // ── Experience ──
    renderExperience(experience || []);

    // ── Education ──
    renderEducation(education || []);

    // ── CV ──
    renderCvState(profile.resume_url || null);

    // ── Security ──
    $('sec-email-display').textContent   = user.email || '—';
    $('sec-email-verified').textContent  = user.email_verified_at ? '✓ Verified' : 'Not verified';
    $('sec-email-verified').style.background = user.email_verified_at ? '#d1fae5' : '#fef3c7';
    $('sec-email-verified').style.color      = user.email_verified_at ? '#065f46' : '#92400e';

    const providerMap = { google:'Signed in with Google', email:'Password login', local:'Password login' };
    $('sec-provider-display').textContent = providerMap[user.auth_provider] || cap(user.auth_provider||'') || '—';
    $('sec-member-since').textContent     = fmt(user.created_at);

    if (user.auth_provider === 'google') {
      $('sec-auth-hint').textContent = 'Your account uses Google sign-in. Set a password via the reset link.';
    }
  }

  /* ═══════════════════════════════════════════
     CV / RESUME SECTION
  ═══════════════════════════════════════════ */

  /**
   * Show either the dropzone (no CV) or the uploaded-state panel (CV exists).
   * @param {string|null} resumeUrl  - the stored URL, or null/empty
   */
  function renderCvState(resumeUrl) {
    const uploadZone   = $('cv-upload-zone');
    const uploadedState = $('cv-uploaded-state');

    if (resumeUrl) {
      // ── CV on file ──
      uploadZone.style.display    = 'none';
      uploadedState.style.display = 'block';

      const name = fileBaseName(resumeUrl);
      $('cv-file-name').textContent = name;
      $('cv-file-icon').className   = cvIconClass(resumeUrl);

      // Use utcToLocal from main.js if the profile has an upload date;
      // otherwise just show a friendly label.
      const uploadedAt = settings?.profile?.resume_uploaded_at || null;
      $('cv-upload-date').textContent = uploadedAt
        ? (typeof utcToLocal === 'function'
            ? utcToLocal(uploadedAt, { includeTime: false, dateFormat: 'medium' })
            : fmt(uploadedAt))
        : 'Uploaded';

      // Wire download
      $('cv-download-btn').onclick = () => downloadCv();

    } else {
      // ── No CV ──
      uploadZone.style.display    = 'block';
      uploadedState.style.display = 'none';
    }
  }

  // ── Delete CV Handler (outside renderCvState to avoid duplicate wiring) ──
  $('cv-delete-btn').onclick = () => {
    Confirm.show({
      type       : 'error',
      heading    : 'Remove Your CV?',
      message    : 'Are you sure you want to remove your CV from your profile?',
      details    : 'You will no longer be able to apply for jobs that require a CV until you upload a new one.',
      confirmText: 'Yes, remove it',
      cancelText : 'Keep it',
      onConfirm  : async () => {
        const deleteBtn = $('cv-delete-btn');
        const origHTML  = deleteBtn.innerHTML;
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
          await apiFetch(API.deleteCv, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
          });
          // Refresh
          const fresh = await apiFetch(API.settings);
          settings = fresh.data;
          renderCvState(settings.profile?.resume_url || null);
          toast.ok('Your CV has been removed.');
        } catch (err) {
          toast.err(err.message || 'Could not remove your CV. Please try again.');
          deleteBtn.disabled = false;
          deleteBtn.innerHTML = origHTML;
        }
      },
    });
  };

  // ── Drop zone: click or drag & drop ──

  const dropzone = $('sp-cv-dropzone');
  const cvInput  = $('sp-cv-input');

  dropzone.addEventListener('click',   () => cvInput.click());
  dropzone.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') cvInput.click(); });

  dropzone.addEventListener('dragover', e => {
    e.preventDefault();
    dropzone.classList.add('drag-over');
  });
  dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag-over'));
  dropzone.addEventListener('drop', e => {
    e.preventDefault();
    dropzone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) handleCvFile(file);
  });

  cvInput.addEventListener('change', e => {
    const file = e.target.files[0];
    if (file) handleCvFile(file);
    e.target.value = ''; // reset so the same file can be re-selected later
  });

  /** Validate and upload the chosen CV file */
  async function handleCvFile(file) {
    const ALLOWED = ['application/pdf','application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const ALLOWED_EXT = ['.pdf','.doc','.docx'];
    const ext = '.' + file.name.split('.').pop().toLowerCase();

    if (!ALLOWED.includes(file.type) && !ALLOWED_EXT.includes(ext)) {
      toast.err('Only PDF, DOC or DOCX files are accepted.');
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      toast.err('Your CV must be under 5 MB. Please compress it and try again.');
      return;
    }

    await uploadCv(file);
  }

  /** POST /api/jobseeker/upload-cv  (multipart, field: "resume") */
  async function uploadCv(file) {
    const progress     = $('sp-cv-progress');
    const progressFill = $('sp-cv-progress-fill');

    // Show progress bar
    progress.classList.add('show');
    progressFill.style.width = '10%';

    // Disable dropzone during upload
    dropzone.style.pointerEvents = 'none';
    dropzone.style.opacity       = '.6';

    try {
      // Simulate smooth progress while waiting (real XHR progress would need XMLHttpRequest)
      let pct = 10;
      const ticker = setInterval(() => {
        pct = Math.min(pct + 8, 85);
        progressFill.style.width = pct + '%';
      }, 200);

      const body = new FormData();
      body.append('resume', file);
      const json = await apiFetch(API.uploadCv, { method: 'POST', body });

      clearInterval(ticker);
      progressFill.style.width = '100%';

      await new Promise(r => setTimeout(r, 350)); // brief pause so 100% is visible

      // Refresh settings to get the new resume_url
      const fresh = await apiFetch(API.settings);
      settings = fresh.data;
      renderCvState(settings.profile.resume_url || null);

      toast.ok('Your CV has been uploaded successfully!');

    } catch (err) {
      toast.err(err.message || 'CV upload failed. Please try again.');
    } finally {
      progress.classList.remove('show');
      progressFill.style.width     = '0%';
      dropzone.style.pointerEvents = '';
      dropzone.style.opacity       = '';
    }
  }

  /** Download the CV using the API endpoint */
  function downloadCv() {
    // Create a temporary anchor linking to the download endpoint
    const a = document.createElement('a');
    a.href     = '/api/jobseeker/download-cv';
    a.target   = '_blank';
    a.rel      = 'noopener';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    toast.info('Your CV is being downloaded.');
  }

  /* ═══════════════════════════════════════════
     SKILLS
  ═══════════════════════════════════════════ */
  function renderSkills(skills) {
    const list = $('skills-list');
    $('skills-count-badge').textContent = skills.length;
    if (!skills.length) {
      list.innerHTML = '<p style="font-size:.82rem;color:var(--text-muted);">No skills added yet. Add your first skill below.</p>';
      return;
    }
    list.innerHTML = skills.map(s => `
      <span class="sp-skill-tag">
        <i class="fas ${esc(s.icon||'fa-code')}" style="font-size:.72rem;color:var(--primary-light);"></i>
        ${esc(s.skill_name)}
        <span class="sp-skill-level ${esc(s.proficiency)}">${cap(s.proficiency)}</span>
        ${s.years_experience ? `<span style="font-size:.68rem;color:var(--text-muted);">${s.years_experience}yr</span>` : ''}
        <button class="sp-skill-remove" data-skill-id="${s.id}" title="Remove skill">
          <i class="fas fa-times"></i>
        </button>
      </span>`).join('');

    list.querySelectorAll('.sp-skill-remove').forEach(btn => {
      btn.addEventListener('click', () => removeSkill(parseInt(btn.dataset.skillId)));
    });
  }

  /* ═══════════════════════════════════════════
     EXPERIENCE
  ═══════════════════════════════════════════ */
  function renderExperience(exps) {
    const list = $('exp-list');
    if (!exps.length) {
      list.innerHTML = '<p style="font-size:.82rem;color:var(--text-muted);margin-bottom:.75rem;">No work experience added yet.</p>';
      return;
    }
    list.innerHTML = exps.map(e => `
      <div class="sp-entry-item" data-exp-id="${e.id}">
        <div class="sp-entry-actions">
          <button class="sp-entry-action-btn sp-exp-edit" data-id="${e.id}" title="Edit"><i class="fas fa-pen"></i></button>
        </div>
        <div class="sp-entry-title">${esc(e.role_title)}</div>
        <div class="sp-entry-sub">
          <i class="fas fa-building" style="font-size:.7rem;"></i>
          ${esc(e.company_name)}
          <span>·</span>
          ${e.start_year} – ${e.currently_working ? '<span class="sp-entry-badge"><i class="fas fa-circle" style="font-size:.5rem;"></i> Current</span>' : esc(e.end_year||'?')}
        </div>
        ${e.description ? `<div class="sp-entry-desc">${esc(e.description)}</div>` : ''}
      </div>`).join('');

    list.querySelectorAll('.sp-exp-edit').forEach(btn => {
      btn.addEventListener('click', () => openExpForm(parseInt(btn.dataset.id)));
    });
  }

  /* ═══════════════════════════════════════════
     EDUCATION
  ═══════════════════════════════════════════ */
  const EDU_LEVEL_MAP = {
    high_school:'High School', certificate:'Certificate',
    diploma:'Diploma', bachelor:"Bachelor's Degree",
    master:"Master's Degree", phd:'PhD / Doctorate', other:'Other',
  };

  function renderEducation(edus) {
    const list = $('edu-list');
    if (!edus.length) {
      list.innerHTML = '<p style="font-size:.82rem;color:var(--text-muted);margin-bottom:.75rem;">No education entries added yet.</p>';
      return;
    }
    list.innerHTML = edus.map(e => `
      <div class="sp-entry-item" data-edu-id="${e.id}">
        <div class="sp-entry-actions">
          <button class="sp-entry-action-btn sp-edu-edit" data-id="${e.id}" title="Edit"><i class="fas fa-pen"></i></button>
        </div>
        <div class="sp-entry-title">${esc(e.school_name||'School')}</div>
        <div class="sp-entry-sub">
          <i class="fas fa-graduation-cap" style="font-size:.7rem;"></i>
          ${EDU_LEVEL_MAP[e.education_level]||cap(e.education_level)} in ${esc(e.field_of_study||'—')}
          <span>·</span>
          ${e.start_year} – ${e.currently_studying ? '<span class="sp-entry-badge"><i class="fas fa-circle" style="font-size:.5rem;"></i> Studying</span>' : esc(e.end_year||'?')}
          ${e.grade ? `<span>· ${esc(e.grade)}</span>` : ''}
        </div>
        ${e.description ? `<div class="sp-entry-desc">${esc(e.description)}</div>` : ''}
      </div>`).join('');

    list.querySelectorAll('.sp-edu-edit').forEach(btn => {
      btn.addEventListener('click', () => openEduForm(parseInt(btn.dataset.id)));
    });
  }

  /* ═══════════════════════════════════════════
     SAVE PROFILE (generic)
  ═══════════════════════════════════════════ */
  async function saveProfileSection(btn, fields, label) {
    setBtnLoading(btn, true, label);
    try {
      const body = new FormData();
      Object.entries(fields).forEach(([k,v]) => { if (v !== null && v !== undefined) body.append(k, v); });
      await apiFetch(API.saveProfile, { method:'POST', body });
      toast.ok('Your changes have been saved!');
      const json = await apiFetch(API.settings);
      settings   = json.data;
      populateAll();
    } catch (err) {
      toast.err(err.message || 'Could not save changes.');
    } finally {
      setBtnLoading(btn, false, label || 'Save Changes');
    }
  }

  $('save-profile').addEventListener('click', () => {
    const btn = $('save-profile');
    setBtnLoading(btn, true, 'Save Changes');
    
    const profileData = {
      personal: {
        firstName: $('p-first-name').value.trim(),
        lastName: $('p-last-name').value.trim(),
        professionalTitle: $('p-title').value.trim()
      },
      summary: $('p-summary').value.trim()
    };
    
    apiFetch(API.updateProfile, { method: 'POST', body: JSON.stringify(profileData), headers: { 'Content-Type': 'application/json' } })
      .then(() => {
        toast.ok('Profile saved successfully!');
        return apiFetch(API.settings);
      })
      .then(json => {
        settings = json.data;
        populateAll();
      })
      .catch(err => {
        toast.err(err.message || 'Could not save profile.');
      })
      .finally(() => {
        setBtnLoading(btn, false, 'Save Changes');
      });
  });

  $('save-career').addEventListener('click', () => {
    const btn = $('save-career');
    setBtnLoading(btn, true, 'Save Career Info');
    
    const careerData = {
      yearsExperience: $('p-years-exp').value || null
    };
    
    apiFetch(API.updateCareer, { method: 'POST', body: JSON.stringify(careerData), headers: { 'Content-Type': 'application/json' } })
      .then(() => {
        toast.ok('Career information saved successfully!');
        return apiFetch(API.settings);
      })
      .then(json => {
        settings = json.data;
        populateAll();
      })
      .catch(err => {
        toast.err(err.message || 'Could not save career information.');
      })
      .finally(() => {
        setBtnLoading(btn, false, 'Save Career Info');
      });
  });

  $('save-prefs').addEventListener('click', () => {
    const btn = $('save-prefs');
    setBtnLoading(btn, true, 'Save Preferences');
    
    const preferencesData = {
      preferences: {
        openToWork : $('pref-open-to-work').checked,
        remote     : $('pref-remote').checked,
        relocation : $('pref-relocate').checked,
      }
    };
    
    apiFetch(API.updatePreferences, { method: 'POST', body: JSON.stringify(preferencesData), headers: { 'Content-Type': 'application/json' } })
      .then(() => {
        toast.ok('Preferences saved successfully!');
        return apiFetch(API.settings);
      })
      .then(json => {
        settings = json.data;
        populateAll();
      })
      .catch(err => {
        toast.err(err.message || 'Could not save preferences.');
      })
      .finally(() => {
        setBtnLoading(btn, false, 'Save Preferences');
      });
  });

  /* ─── Theme Appearance Save ─────────────────────── */
  $('save-appearance').addEventListener('click', async () => {
    const btn = $('save-appearance');
    const isDarkMode = $('pref-dark-mode').checked;
    const theme = isDarkMode ? 'dark' : 'light';
    
    setBtnLoading(btn, true, 'Save Appearance');
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
      setBtnLoading(btn, false, 'Save Appearance');
    }
  });

  /* ═══════════════════════════════════════════
     AVATAR UPLOAD
  ═══════════════════════════════════════════ */
  $('sp-avatar-btn').addEventListener('click', () => $('sp-avatar-input').click());

  $('sp-avatar-input').addEventListener('change', async e => {
    const file = e.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) { toast.err('Please choose an image file.'); return; }
    if (file.size > 5 * 1024 * 1024)    { toast.err('Image must be under 5 MB.'); return; }

    const btn = $('sp-avatar-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:.65rem;"></i>';
    btn.disabled  = true;

    try {
      const body = new FormData();
      body.append('photo', file);
      const json = await apiFetch(API.uploadPhoto, { method:'POST', body });
      const d    = $('sp-avatar-display');
      const url  = json.data?.avatar_url || json.data?.avatar || '';
      if (url) {
        if (d.tagName === 'IMG') {
          d.src = url + '?t=' + Date.now();
        } else {
          d.outerHTML = `<img src="${esc(url)}?t=${Date.now()}" class="sp-avatar" id="sp-avatar-display" alt="Profile photo">`;
        }
      }
      toast.ok('Profile photo updated!');
    } catch (err) {
      toast.err(err.message || 'Photo upload failed. Please try again.');
    } finally {
      btn.innerHTML  = '<i class="fas fa-camera"></i>';
      btn.disabled   = false;
      e.target.value = '';
    }
  });

  /* ═══════════════════════════════════════════
     SKILLS ACTIONS
  ═══════════════════════════════════════════ */
  $('sp-add-skill-btn').addEventListener('click', () => {
    $('sp-skill-form').style.display    = 'block';
    $('sp-add-skill-btn').style.display = 'none';
    $('skill-name-input').focus();
  });
  $('skill-cancel-btn').addEventListener('click', () => {
    $('sp-skill-form').style.display    = 'none';
    $('sp-add-skill-btn').style.display = 'flex';
    $('skill-name-input').value         = '';
  });

  $('skill-save-btn').addEventListener('click', async () => {
    const name = $('skill-name-input').value.trim();
    if (!name) { $('skill-name-input').classList.add('err'); return; }
    $('skill-name-input').classList.remove('err');

    const btn = $('skill-save-btn');
    setBtnLoading(btn, true, 'Add Skill');
    try {
      const existing = (settings.skills || []).map(s => ({
        skill_name:s.skill_name, proficiency:s.proficiency, years_experience:s.years_experience,
      }));
      existing.push({ skill_name:name, proficiency:$('skill-level-input').value, years_experience:$('skill-years-input').value || null });

      const body = new FormData();
      body.append('skills', JSON.stringify(existing));
      await apiFetch(API.saveProfile, { method:'POST', body });
      toast.ok(name + ' added to your skills.');

      const json = await apiFetch(API.settings);
      settings = json.data;
      renderSkills(settings.skills || []);
      $('skill-cancel-btn').click();
    } catch (err) {
      toast.err(err.message || 'Could not add skill.');
    } finally {
      setBtnLoading(btn, false, 'Add Skill');
    }
  });

  async function removeSkill(skillId) {
    const skill = settings.skills?.find(s => s.id === skillId);
    if (!skill) return;
    Confirm.show({
      heading:'Remove Skill', message:`Remove "${skill.skill_name}" from your profile?`,
      type:'warning', confirmText:'Remove', cancelText:'Keep',
      onConfirm: async () => {
        try {
          const updated = (settings.skills||[]).filter(s=>s.id!==skillId)
            .map(s=>({skill_name:s.skill_name,proficiency:s.proficiency,years_experience:s.years_experience}));
          const body = new FormData();
          body.append('skills', JSON.stringify(updated));
          await apiFetch(API.saveProfile, { method:'POST', body });
          toast.ok('Skill removed.');
          const json = await apiFetch(API.settings);
          settings = json.data;
          renderSkills(settings.skills || []);
        } catch (err) {
          toast.err(err.message || 'Could not remove skill.');
        }
      },
    });
  }

  /* ═══════════════════════════════════════════
     EXPERIENCE ACTIONS
  ═══════════════════════════════════════════ */
  $('sp-add-exp-btn').addEventListener('click', () => {
    clearExpForm();
    $('exp-form-title').innerHTML = '<i class="fas fa-plus-circle" style="color:var(--primary-color);"></i> Add Work Experience';
    $('sp-exp-form').style.display = 'block';
    $('sp-exp-form').scrollIntoView({ behavior:'smooth', block:'nearest' });
  });
  $('exp-cancel-btn').addEventListener('click', () => { $('sp-exp-form').style.display = 'none'; });

  function clearExpForm() {
    ['exp-edit-id','exp-company','exp-role','exp-start','exp-end','exp-desc'].forEach(id => $$(id).value = '');
  }
  function $$(id) { return document.getElementById(id); }

  function openExpForm(id) {
    const exp = settings.experience?.find(e => e.id === id);
    if (!exp) return;
    $('exp-edit-id').value = exp.id;
    $('exp-company').value = exp.company_name || '';
    $('exp-role').value    = exp.role_title   || '';
    $('exp-start').value   = exp.start_year   || '';
    $('exp-end').value     = exp.currently_working ? '' : (exp.end_year || '');
    $('exp-desc').value    = exp.description  || '';
    $('exp-form-title').innerHTML = '<i class="fas fa-pen" style="color:var(--primary-color);"></i> Edit Experience';
    $('sp-exp-form').style.display = 'block';
    $('sp-exp-form').scrollIntoView({ behavior:'smooth', block:'nearest' });
  }

  $('exp-save-btn').addEventListener('click', async () => {
    const company = $('exp-company').value.trim();
    const role    = $('exp-role').value.trim();
    const start   = $('exp-start').value.trim();
    if (!company || !role || !start) {
      if (!company) $('exp-company').classList.add('err');
      if (!role)    $('exp-role').classList.add('err');
      if (!start)   $('exp-start').classList.add('err');
      toast.err('Please fill in all required fields.');
      return;
    }
    [$('exp-company'),$('exp-role'),$('exp-start')].forEach(el => el.classList.remove('err'));
    const editId  = $('exp-edit-id').value;
    const endYear = $('exp-end').value.trim();
    const btn     = $('exp-save-btn');
    setBtnLoading(btn, true);
    try {
      let exps = (settings.experience||[]).map(e => ({
        id:e.id, company_name:e.company_name, role_title:e.role_title,
        start_year:e.start_year, end_year:e.end_year,
        currently_working:e.currently_working, description:e.description,
      }));
      if (editId) {
        exps = exps.map(e => e.id == editId
          ? {...e,company_name:company,role_title:role,start_year:start,end_year:endYear||null,currently_working:!endYear?1:0,description:$('exp-desc').value.trim()}
          : e);
      } else {
        exps.push({company_name:company,role_title:role,start_year:start,end_year:endYear||null,currently_working:!endYear?1:0,description:$('exp-desc').value.trim()});
      }
      const body = new FormData();
      body.append('experience', JSON.stringify(exps));
      await apiFetch(API.saveProfile, { method:'POST', body });
      toast.ok('Experience saved!');
      const json = await apiFetch(API.settings);
      settings = json.data;
      renderExperience(settings.experience || []);
      $('sp-exp-form').style.display = 'none';
    } catch (err) {
      toast.err(err.message || 'Could not save experience.');
    } finally {
      setBtnLoading(btn, false);
    }
  });

  /* ═══════════════════════════════════════════
     EDUCATION ACTIONS
  ═══════════════════════════════════════════ */
  $('sp-add-edu-btn').addEventListener('click', () => {
    clearEduForm();
    $('edu-form-title').innerHTML = '<i class="fas fa-plus-circle" style="color:var(--primary-color);"></i> Add Education';
    $('sp-edu-form').style.display = 'block';
    $('sp-edu-form').scrollIntoView({ behavior:'smooth', block:'nearest' });
  });
  $('edu-cancel-btn').addEventListener('click', () => { $('sp-edu-form').style.display = 'none'; });

  function clearEduForm() {
    ['edu-edit-id','edu-school-input','edu-school-id','edu-field','edu-start','edu-end','edu-grade','edu-desc']
      .forEach(id => $$(id).value = '');
    $('edu-level').value = 'bachelor';
    $('edu-school-suggestions').innerHTML = '';
  }

  function openEduForm(id) {
    const edu = settings.education?.find(e => e.id === id);
    if (!edu) return;
    clearEduForm();
    $('edu-edit-id').value      = edu.id;
    $('edu-school-input').value = edu.school_name || '';
    $('edu-school-id').value    = edu.school_id   || '';
    $('edu-level').value        = edu.education_level || 'bachelor';
    $('edu-field').value        = edu.field_of_study  || '';
    $('edu-start').value        = edu.start_year  || '';
    $('edu-end').value          = edu.currently_studying ? '' : (edu.end_year || '');
    $('edu-grade').value        = edu.grade || '';
    $('edu-desc').value         = edu.description || '';
    $('edu-form-title').innerHTML = '<i class="fas fa-pen" style="color:var(--primary-color);"></i> Edit Education';
    $('sp-edu-form').style.display = 'block';
    $('sp-edu-form').scrollIntoView({ behavior:'smooth', block:'nearest' });
  }

  let schoolTimer;
  $('edu-school-input').addEventListener('input', () => {
    clearTimeout(schoolTimer);
    const q = $('edu-school-input').value.trim();
    if (q.length < 2) { $('edu-school-suggestions').innerHTML = ''; return; }
    schoolTimer = setTimeout(() => fetchSchools(q), 300);
  });

  async function fetchSchools(q) {
    try {
      if (!schoolsCache.length) {
        const json = await apiFetch(API.schools);
        schoolsCache = Array.isArray(json.data) ? json.data : (json.data?.data || []);
      }
      const matches = schoolsCache.filter(s => (s.name||'').toLowerCase().includes(q.toLowerCase())).slice(0,6);
      renderSchoolSuggestions(matches);
    } catch (_) {}
  }

  function renderSchoolSuggestions(schools) {
    const wrap = $('edu-school-suggestions');
    if (!schools.length) { wrap.innerHTML = ''; return; }
    wrap.innerHTML = `<div style="position:absolute;z-index:200;width:100%;background:var(--bg-primary);border:1.5px solid var(--border-color);border-radius:9px;box-shadow:var(--shadow-md);overflow:hidden;margin-top:3px;">
      ${schools.map(s => `<div class="sp-school-opt" data-id="${s.id}" data-name="${esc(s.name)}"
        style="padding:.6rem .9rem;font-size:.83rem;cursor:pointer;color:var(--text-primary);transition:background .12s;">
        <i class="fas fa-university" style="margin-right:.45rem;color:var(--primary-light);font-size:.75rem;"></i>
        ${esc(s.name)} <span style="font-size:.7rem;color:var(--text-muted);">${esc(s.city||'')}${s.country?', '+esc(s.country):''}</span>
      </div>`).join('')}
    </div>`;
    wrap.querySelectorAll('.sp-school-opt').forEach(opt => {
      opt.addEventListener('mouseenter', () => opt.style.background = 'var(--bg-secondary)');
      opt.addEventListener('mouseleave', () => opt.style.background = '');
      opt.addEventListener('click', () => {
        $('edu-school-input').value = opt.dataset.name;
        $('edu-school-id').value   = opt.dataset.id;
        wrap.innerHTML = '';
      });
    });
  }

  $('edu-save-btn').addEventListener('click', async () => {
    const field = $('edu-field').value.trim();
    const start = $('edu-start').value.trim();
    if (!field || !start) {
      if (!field) $('edu-field').classList.add('err');
      if (!start) $('edu-start').classList.add('err');
      toast.err('Please fill in the required fields.');
      return;
    }
    [$('edu-field'),$('edu-start')].forEach(el => el.classList.remove('err'));
    const editId  = $('edu-edit-id').value;
    const endYear = $('edu-end').value.trim();
    const btn     = $('edu-save-btn');
    setBtnLoading(btn, true);
    try {
      let edus = (settings.education||[]).map(e => ({
        id:e.id,school_id:e.school_id,school_name:e.school_name,education_level:e.education_level,
        field_of_study:e.field_of_study,grade:e.grade,start_year:e.start_year,end_year:e.end_year,
        currently_studying:e.currently_studying,description:e.description,
      }));
      const entry = {
        school_id:$('edu-school-id').value||null, school_name:$('edu-school-input').value.trim(),
        education_level:$('edu-level').value, field_of_study:field,
        grade:$('edu-grade').value.trim()||null, start_year:start, end_year:endYear||null,
        currently_studying:!endYear?1:0, description:$('edu-desc').value.trim()||null,
      };
      if (editId) {
        edus = edus.map(e => e.id == editId ? {...e,...entry} : e);
      } else {
        edus.push(entry);
      }
      const body = new FormData();
      body.append('education', JSON.stringify(edus));
      await apiFetch(API.saveProfile, { method:'POST', body });
      toast.ok('Education saved!');
      const json = await apiFetch(API.settings);
      settings = json.data;
      renderEducation(settings.education || []);
      $('sp-edu-form').style.display = 'none';
    } catch (err) {
      toast.err(err.message || 'Could not save education.');
    } finally {
      setBtnLoading(btn, false);
    }
  });

  /* ═══════════════════════════════════════════
     SECURITY
  ═══════════════════════════════════════════ */
  $('sec-change-pwd-btn').addEventListener('click', () => {
    $('sp-pwd-form').style.display = $('sp-pwd-form').style.display === 'none' ? 'block' : 'none';
  });
  $('sec-pwd-cancel').addEventListener('click', () => { $('sp-pwd-form').style.display = 'none'; });

  $('sec-send-reset').addEventListener('click', async () => {
    const email = settings?.user?.email;
    if (!email) { toast.err('No email address found.'); return; }
    const btn = $('sec-send-reset');
    setBtnLoading(btn, true, 'Sending...');
    try {
      await apiFetch(API.resetPwd, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({email}) });
      
      toast.ok('✓ Reset link sent to your email!');
      $('sp-pwd-form').style.display = 'none';
      
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

  /* ═══════════════════════════════════════════
     LOGOUT
  ═══════════════════════════════════════════ */
  async function doLogout() {
    Confirm.show({
      heading:'Sign Out', message:'Are you sure you want to sign out?',
      type:'warning', confirmText:'Yes, Sign Out', cancelText:'Stay',
      onConfirm: async () => {
        try { await apiFetch(API.logout, { method:'POST', headers:{'Content-Type':'application/json'} }); }
        catch (_) {}
        window.location.href = '/login';
      },
    });
  }

  $('sp-logout-btn').addEventListener('click', doLogout);

  $('sec-logout-all').addEventListener('click', () => {
    Confirm.show({
      heading:'Sign Out Everywhere',
      message:'This will sign you out of all devices and sessions, including this one.',
      type:'warning', confirmText:'Sign Out All Devices', cancelText:'Cancel',
      onConfirm: async () => {
        try { await apiFetch(API.logout, { method:'POST', headers:{'Content-Type':'application/json'} }); }
        catch (_) {}
        window.location.href = '/login';
      },
    });
  });

  /* ═══════════════════════════════════════════
     MISC
  ═══════════════════════════════════════════ */
  $('sp-retry').addEventListener('click', loadSettings);

  document.addEventListener('click', e => {
    if (!e.target.closest('#edu-school-input') && !e.target.closest('#edu-school-suggestions')) {
      $('edu-school-suggestions').innerHTML = '';
    }
  });

  /* ── Boot ─────────────────────────────────── */
  loadSettings();

})();
</script>

</div>
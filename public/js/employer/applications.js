/**
 * Employer — Applications Management
 * /js/employer/applications.js
 *
 * Depends on: Toast (global), Confirm (global)
 */

(function () {
  "use strict";

  /* ─────────────────────────────────────────
     CONFIG
     ───────────────────────────────────────── */
  const API = "/api";

  /* ─────────────────────────────────────────
     STATE
     ───────────────────────────────────────── */
  let jobs = [];
  let applications = []; // master list
  let activeJobId = null; // null = all jobs
  let activeStatus = "all"; // tab filter
  let searchQuery = "";
  let sortMode = "newest";

  // Interview modal state
  let interviewTarget = null; // application object
  let interviewType = "video";

  // Status‑note modal state
  let pendingStatusChange = null; // { app, newStatus }

  // Reject modal state
  let rejectTarget = null; // application object

  // Detail modal currently open for
  let detailTarget = null;

  /* ─────────────────────────────────────────
     DOM REFS  (all resolved after DOMContentLoaded)
     ───────────────────────────────────────── */
  const $ = (id) => document.getElementById(id);
  let elJobSelect, elSearch, elSort, elList, elResultsCount, elSubtitle;

  /* ─────────────────────────────────────────
     HELPERS
     ───────────────────────────────────────── */

  /**
   * Convert a UTC datetime string from the API to a local Date.
   * The API returns strings like "2026-03-23 18:06:37" — no 'Z' suffix,
   * but we treat them as UTC by appending Z.
   */
  function toLocalDate(utcStr) {
    if (!utcStr) return null;
    // Replace space with T and append Z so JS parses as UTC
    const iso = utcStr.replace(" ", "T") + "Z";
    return new Date(iso);
  }

  function formatDate(utcStr) {
    const d = toLocalDate(utcStr);
    if (!d) return "—";
    return d.toLocaleDateString("en-KE", {
      day: "numeric",
      month: "short",
      year: "numeric",
    });
  }

  function formatRelative(utcStr) {
    const d = toLocalDate(utcStr);
    if (!d) return "—";
    const diffMs = Date.now() - d.getTime();
    const diffMin = Math.floor(diffMs / 60000);
    if (diffMin < 1) return "Just now";
    if (diffMin < 60) return `${diffMin}m ago`;
    const diffH = Math.floor(diffMin / 60);
    if (diffH < 24) return `${diffH}h ago`;
    const diffD = Math.floor(diffH / 24);
    if (diffD < 7) return `${diffD}d ago`;
    return formatDate(utcStr);
  }

  function formatDateTimeLocal(utcStr) {
    const d = toLocalDate(utcStr);
    if (!d) return "—";
    return d.toLocaleString("en-KE", {
      day: "numeric",
      month: "short",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  function initials(first, last) {
    return ((first?.[0] ?? "") + (last?.[0] ?? "")).toUpperCase();
  }

  // Rotate through 6 gradient classes for visual variety
  const AVATAR_CLASSES = [
    "ap-avatar-a",
    "ap-avatar-b",
    "ap-avatar-c",
    "ap-avatar-d",
    "ap-avatar-e",
    "ap-avatar-f",
  ];
  function avatarClass(id) {
    return AVATAR_CLASSES[id % AVATAR_CLASSES.length];
  }

  function esc(str) {
    if (!str) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  const STATUS_LABELS = {
    pending: "Awaiting Review",
    reviewed: "Reviewed",
    shortlisted: "Shortlisted",
    accepted: "Accepted",
    rejected: "Declined",
  };

  const STATUS_ICONS = {
    pending: "fas fa-clock",
    reviewed: "fas fa-eye",
    shortlisted: "fas fa-star",
    accepted: "fas fa-check-circle",
    rejected: "fas fa-times-circle",
  };

  function statusLabel(s) {
    return STATUS_LABELS[s] ?? s;
  }

  /* ─────────────────────────────────────────
     API
     ───────────────────────────────────────── */
  async function apiFetch(path, opts = {}) {
    const res = await fetch(API + path, { credentials: "include", ...opts });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(json.message || `HTTP ${res.status}`);
    return json;
  }

  async function loadJobs() {
    const json = await apiFetch("/employer/jobs");
    console.log("Jobs response:", json);
    // Handle paginated or nested structure
    const jobsData = json.data?.data ?? json.data ?? [];
    return Array.isArray(jobsData) ? jobsData : [];
  }

  async function loadApplications() {
    const json = await apiFetch("/employer/applications");
    console.log("Applications response:", json);
    // Handle paginated or nested structure
    const appsData = json.data?.data ?? json.data ?? [];
    return Array.isArray(appsData) ? appsData : [];
  }

  async function patchStatus(appId, status, note = "") {
    return apiFetch(`/applications/${appId}/status`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ status, employer_notes: note }),
    });
  }

  async function postInterview(appId, payload) {
    return apiFetch(`/applications/${appId}/interviews`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
  }

  async function markViewed(appId) {
    return apiFetch(`/applications/${appId}/view`, { method: "POST" }).catch(
      () => {},
    );
  }

  /* ─────────────────────────────────────────
     DERIVED DATA
     ───────────────────────────────────────── */
  function getFiltered() {
    let list = [...applications];

    if (activeJobId) {
      list = list.filter((a) => a.job_id === activeJobId);
    }

    if (activeStatus !== "all") {
      list = list.filter((a) => a.status === activeStatus);
    }

    if (searchQuery) {
      const q = searchQuery.toLowerCase();
      list = list.filter(
        (a) =>
          `${a.first_name} ${a.last_name}`.toLowerCase().includes(q) ||
          (a.email ?? "").toLowerCase().includes(q) ||
          (a.job_title ?? "").toLowerCase().includes(q) ||
          (a.professional_title ?? "").toLowerCase().includes(q),
      );
    }

    if (sortMode === "oldest") {
      list.sort((a, b) => new Date(a.applied_at) - new Date(b.applied_at));
    } else if (sortMode === "name") {
      list.sort((a, b) =>
        `${a.first_name}${a.last_name}`.localeCompare(
          `${b.first_name}${b.last_name}`,
        ),
      );
    } else {
      list.sort((a, b) => new Date(b.applied_at) - new Date(a.applied_at));
    }

    return list;
  }

  function countByStatus(status, scope) {
    const list = scope ?? applications;
    if (status === "all") return list.length;
    return list.filter((a) => a.status === status).length;
  }

  /* ─────────────────────────────────────────
     RENDER — JOB SELECTOR
     ───────────────────────────────────────── */
  function renderJobSelect() {
    if (!elJobSelect) return;
    elJobSelect.innerHTML = '<option value="">All Positions</option>';
    jobs.forEach((job) => {
      const cnt = applications.filter((a) => a.job_id === job.id).length;
      const opt = document.createElement("option");
      opt.value = job.id;
      opt.textContent = `${job.title}${cnt ? ` (${cnt})` : ""}`;
      elJobSelect.appendChild(opt);
    });

    // Subtitle
    if (elSubtitle) {
      elSubtitle.textContent = `${jobs.length} position${jobs.length !== 1 ? "s" : ""} · ${applications.length} total application${applications.length !== 1 ? "s" : ""}`;
    }
  }

  /* ─────────────────────────────────────────
     RENDER — STAT TABS
     ───────────────────────────────────────── */
  function renderStats() {
    // Count within current job scope (before status filter)
    const scopeApps = activeJobId
      ? applications.filter((a) => a.job_id === activeJobId)
      : applications;

    const statuses = [
      "all",
      "pending",
      "reviewed",
      "shortlisted",
      "accepted",
      "rejected",
    ];
    const countIds = {
      all: "cnt-all",
      pending: "cnt-pending",
      reviewed: "cnt-reviewed",
      shortlisted: "cnt-shortlisted",
      accepted: "cnt-accepted",
      rejected: "cnt-rejected",
    };

    statuses.forEach((s) => {
      const el = document.getElementById(countIds[s]);
      if (el) el.textContent = countByStatus(s, scopeApps);
    });

    document.querySelectorAll(".ap-stat-tab").forEach((btn) => {
      btn.classList.toggle("is-active", btn.dataset.filter === activeStatus);
    });
  }

  /* ─────────────────────────────────────────
     RENDER — CARD HTML
     ───────────────────────────────────────── */
  function buildCardHTML(app) {
    const inits = esc(initials(app.first_name, app.last_name));
    const avClass = avatarClass(app.id);
    const sLabel = statusLabel(app.status);
    const sIcon = STATUS_ICONS[app.status] ?? "fas fa-circle";
    const relTime = formatRelative(app.applied_at);
    const hasCV = !!app.resume_path;
    const hasCover = !!app.cover_letter;
    const hasNote = !!app.employer_notes;
    const isUnread = !app.viewed_at;

    /* Quick‑action buttons — context‑aware */
    const actionsBtns = buildQuickActions(app);

    return `
<div class="ap-card" data-status="${esc(app.status)}" data-id="${app.id}">
  <div class="ap-card-head">
    <div class="ap-avatar ${avClass}">${inits}</div>
    <div class="ap-person">
      <p class="ap-person-name">${esc(app.first_name)} ${esc(app.last_name)}${isUnread ? ' <span title="Unread" style="display:inline-block;width:7px;height:7px;background:var(--a-accent);border-radius:50%;margin-left:4px;vertical-align:middle"></span>' : ""}</p>
      ${app.professional_title ? `<p class="ap-person-role">${esc(app.professional_title)}</p>` : ""}
      <p class="ap-person-email">${esc(app.email)}</p>
    </div>
    <div class="ap-card-badges">
      <span class="ap-status-badge badge-${esc(app.status)}">${esc(sLabel)}</span>
      <span class="ap-time">${esc(relTime)}</span>
    </div>
  </div>

  <div class="ap-card-meta">
    <span class="ap-chip"><i class="fas fa-briefcase"></i>${esc(app.job_title ?? "Unknown position")}</span>
    <span class="ap-chip"><i class="far fa-calendar-alt"></i>${formatDate(app.applied_at)}</span>
    ${
      hasCV
        ? `<a class="ap-chip ap-chip-cv" href="/api/applications/${encodeURIComponent(app.id)}/resume"><i class="fas fa-file-pdf"></i>Download CV</a>`
        : `<span class="ap-chip" style="opacity:.5"><i class="fas fa-file-slash"></i>No CV</span>`
    }
  </div>

  ${hasCover ? `<div class="ap-cover-preview">${esc(app.cover_letter)}</div>` : ""}

  ${hasNote ? `<div class="ap-employer-note"><i class="fas fa-sticky-note"></i><span>${esc(app.employer_notes)}</span></div>` : ""}

  <div class="ap-card-actions">
    <div class="ap-action-primary">${actionsBtns}</div>
    <div class="ap-action-secondary">
      <button class="ap-btn ap-btn-ghost js-view-detail" data-id="${app.id}">
        <i class="fas fa-user"></i> View Profile
      </button>
      ${hasCV ? `<a class="ap-btn ap-btn-ghost" href="/api/applications/${encodeURIComponent(app.id)}/resume"><i class="fas fa-download"></i> CV</a>` : ""}
    </div>
  </div>
</div>`;
  }

  function buildQuickActions(app) {
    const s = app.status;
    const id = app.id;
    let btns = "";

    if (s === "pending") {
      btns += `<button class="ap-btn ap-btn-review js-status" data-id="${id}" data-status="reviewed"><i class="fas fa-eye"></i> Mark Reviewed</button>`;
      btns += `<button class="ap-btn ap-btn-shortlist js-status" data-id="${id}" data-status="shortlisted"><i class="fas fa-star"></i> Shortlist</button>`;
      btns += `<button class="ap-btn ap-btn-reject js-reject" data-id="${id}"><i class="fas fa-times"></i> Decline</button>`;
    } else if (s === "reviewed") {
      btns += `<button class="ap-btn ap-btn-shortlist js-status" data-id="${id}" data-status="shortlisted"><i class="fas fa-star"></i> Shortlist</button>`;
      btns += `<button class="ap-btn ap-btn-accept js-status" data-id="${id}" data-status="accepted"><i class="fas fa-check"></i> Accept</button>`;
      btns += `<button class="ap-btn ap-btn-reject js-reject" data-id="${id}"><i class="fas fa-times"></i> Decline</button>`;
    } else if (s === "shortlisted") {
      btns += `<button class="ap-btn ap-btn-interview js-interview" data-id="${id}"><i class="fas fa-calendar-alt"></i> Schedule Interview</button>`;
      btns += `<button class="ap-btn ap-btn-accept js-status" data-id="${id}" data-status="accepted"><i class="fas fa-check"></i> Accept</button>`;
      btns += `<button class="ap-btn ap-btn-reject js-reject" data-id="${id}"><i class="fas fa-times"></i> Decline</button>`;
    } else if (s === "accepted") {
      btns += `<button class="ap-btn ap-btn-interview js-interview" data-id="${id}"><i class="fas fa-calendar-alt"></i> Schedule Interview</button>`;
      btns += `<button class="ap-btn ap-btn-ghost js-status" data-id="${id}" data-status="reviewed" style="font-size:.72rem"><i class="fas fa-undo"></i> Revert</button>`;
    } else if (s === "rejected") {
      btns += `<span style="font-size:.77rem;color:var(--text-muted);font-style:italic">Application declined</span>`;
      btns += `<button class="ap-btn ap-btn-ghost js-status" data-id="${id}" data-status="reviewed" style="font-size:.72rem"><i class="fas fa-undo"></i> Reconsider</button>`;
    }

    return btns;
  }

  /* ─────────────────────────────────────────
     RENDER — LIST
     ───────────────────────────────────────── */
  function renderList() {
    const filtered = getFiltered();

    if (elResultsCount) {
      elResultsCount.textContent = `${filtered.length} application${filtered.length !== 1 ? "s" : ""}`;
    }

    if (!filtered.length) {
      const isEmpty = applications.length === 0;
      elList.innerHTML = `
        <div class="ap-state">
          <div class="ap-state-icon"><i class="fas fa-${isEmpty ? "inbox" : "filter"}"></i></div>
          <h3>${isEmpty ? "No applications yet" : "No matches found"}</h3>
          <p>${
            isEmpty
              ? "When candidates apply for your positions, they'll show up here."
              : "Try adjusting your filters or search term."
          }</p>
        </div>`;
      return;
    }

    elList.innerHTML = filtered.map(buildCardHTML).join("");
    attachCardListeners();
  }

  /* ─────────────────────────────────────────
     EVENT: CARD BUTTONS
     ───────────────────────────────────────── */
  function attachCardListeners() {
    // Quick status
    elList.querySelectorAll(".js-status").forEach((btn) => {
      btn.addEventListener("click", onQuickStatus);
    });
    // Reject (with reason)
    elList.querySelectorAll(".js-reject").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const id = parseInt(e.currentTarget.dataset.id);
        openRejectModal(applications.find((a) => a.id === id));
      });
    });
    // Interview
    elList.querySelectorAll(".js-interview").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const id = parseInt(e.currentTarget.dataset.id);
        openInterviewModal(applications.find((a) => a.id === id));
      });
    });
    // View detail
    elList.querySelectorAll(".js-view-detail").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const id = parseInt(e.currentTarget.dataset.id);
        openDetailModal(applications.find((a) => a.id === id));
      });
    });
  }

  /* ─────────────────────────────────────────
     QUICK STATUS (no confirmation needed for non-reject)
     ───────────────────────────────────────── */
  async function onQuickStatus(e) {
    const btn = e.currentTarget;
    const id = parseInt(btn.dataset.id);
    const newStatus = btn.dataset.status;
    const app = applications.find((a) => a.id === id);
    if (!app) return;

    // Shortlist → open note modal to add context
    if (newStatus === "shortlisted") {
      openStatusNoteModal(
        app,
        "shortlisted",
        `You're marking <strong>${esc(app.first_name)} ${esc(app.last_name)}</strong> as a standout candidate. Add an internal note for your team.`,
        "Shortlist Candidate",
      );
      return;
    }

    // Accepted
    if (newStatus === "accepted") {
      openStatusNoteModal(
        app,
        "accepted",
        `Accepting <strong>${esc(app.first_name)} ${esc(app.last_name)}</strong>. Add any internal context for your records.`,
        "Accept Candidate",
      );
      return;
    }

    // For reviewed / revert — silent update
    await doStatusUpdate(app, newStatus, "");
  }

  async function doStatusUpdate(app, newStatus, note) {
    try {
      await patchStatus(app.id, newStatus, note);
      app.status = newStatus;
      if (note) app.employer_notes = note;
      renderStats();
      renderList();

      const messages = {
        reviewed: `Marked as reviewed`,
        shortlisted: `${app.first_name} shortlisted — great pick!`,
        accepted: `${app.first_name} accepted`,
        rejected: `Application declined`,
        pending: `Status reverted`,
      };
      Toast.success(messages[newStatus] ?? "Status updated");

      // If detail modal is open for same app, refresh it
      if (detailTarget?.id === app.id) openDetailModal(app);

      // Celebrate shortlist
      if (newStatus === "shortlisted") {
        const card = elList.querySelector(`.ap-card[data-id="${app.id}"]`);
        if (card) {
          card.classList.add("ap-celebrating");
          setTimeout(() => card.classList.remove("ap-celebrating"), 450);
        }
      }
    } catch (err) {
      Toast.error(err.message || "Failed to update status");
    }
  }

  /* ─────────────────────────────────────────
     MODAL: STATUS NOTE
     ───────────────────────────────────────── */
  function openStatusNoteModal(app, newStatus, desc, title) {
    pendingStatusChange = { app, newStatus };
    const modal = $("apStatusNoteModal");
    $("apStatusNoteTitle").innerHTML =
      `<i class="${STATUS_ICONS[newStatus]}" style="margin-right:.4rem"></i>${title}`;
    $("apStatusNoteDesc").innerHTML = desc;
    $("apStatusNote").value = "";
    // Colour the confirm button
    const confirmBtn = $("apStatusNoteConfirm");
    confirmBtn.className = `ap-btn ap-btn-${newStatus === "accepted" ? "accept" : "shortlist"}`;
    confirmBtn.innerHTML = `<i class="${STATUS_ICONS[newStatus]}"></i> ${title}`;
    openModal("apStatusNoteModal");
  }

  $("apStatusNoteConfirm")?.addEventListener("click", async () => {
    if (!pendingStatusChange) return;
    const { app, newStatus } = pendingStatusChange;
    const note = $("apStatusNote").value.trim();
    closeModal("apStatusNoteModal");
    pendingStatusChange = null;
    await doStatusUpdate(app, newStatus, note);
  });

  /* ─────────────────────────────────────────
     MODAL: REJECT WITH REASON
     ───────────────────────────────────────── */
  function openRejectModal(app) {
    rejectTarget = app;
    $("apRejectName").textContent = `${app.first_name} ${app.last_name}`;
    $("apRejectReason").value = "";
    $("apRejectNote").value = "";
    openModal("apRejectModal");
  }

  $("apRejectConfirmBtn")?.addEventListener("click", async () => {
    if (!rejectTarget) return;
    const reason = $("apRejectReason").value.trim();
    const note = $("apRejectNote").value.trim();
    const combined = [reason, note].filter(Boolean).join(" | ");
    closeModal("apRejectModal");
    await doStatusUpdate(rejectTarget, "rejected", combined);
    rejectTarget = null;
  });

  /* ─────────────────────────────────────────
     MODAL: APPLICATION DETAIL
     ───────────────────────────────────────── */
  function openDetailModal(app) {
    detailTarget = app;
    markViewed(app.id);

    const body = $("apDetailBody");
    const hasCV = !!app.resume_path;
    const hasCover = !!app.cover_letter;
    const avClass = avatarClass(app.id);

    body.innerHTML = `
      <!-- Profile -->
      <div class="ap-detail-profile">
        <div class="ap-avatar ${avClass}" style="width:56px;height:56px;font-size:1.1rem;border-radius:16px">${esc(initials(app.first_name, app.last_name))}</div>
        <div class="ap-detail-profile-info">
          <h3>${esc(app.first_name)} ${esc(app.last_name)}</h3>
          ${app.professional_title ? `<p>${esc(app.professional_title)}</p>` : ""}
          <p style="color:var(--a-accent);font-family:var(--a-font-mono);font-size:.75rem">${esc(app.email)}</p>
        </div>
        <span class="ap-status-badge badge-${esc(app.status)}" style="margin-left:auto;align-self:flex-start">${esc(statusLabel(app.status))}</span>
      </div>

      <!-- Info grid -->
      <div>
        <div class="ap-detail-section-label">Application Details</div>
        <div class="ap-detail-info-grid">
          <div class="ap-detail-info-item">
            <div class="item-label">Position</div>
            <div class="item-value">${esc(app.job_title ?? "—")}</div>
          </div>
          <div class="ap-detail-info-item">
            <div class="item-label">Applied</div>
            <div class="item-value">${formatDate(app.applied_at)}</div>
          </div>
          <div class="ap-detail-info-item">
            <div class="item-label">Reviewed</div>
            <div class="item-value">${app.reviewed_at ? formatDateTimeLocal(app.reviewed_at) : "Not yet"}</div>
          </div>
          <div class="ap-detail-info-item">
            <div class="item-label">CV Attached</div>
            <div class="item-value">${hasCV ? "✅ Yes" : "❌ No"}</div>
          </div>
        </div>
      </div>

      ${
        hasCover
          ? `
      <div>
        <div class="ap-detail-section-label">Cover Letter</div>
        <div class="ap-cover-full">${esc(app.cover_letter)}</div>
      </div>`
          : ""
      }

      ${
        app.employer_notes
          ? `
      <div>
        <div class="ap-detail-section-label">Internal Notes</div>
        <div class="ap-employer-note" style="border-radius:8px"><i class="fas fa-sticky-note"></i><span>${esc(app.employer_notes)}</span></div>
      </div>`
          : ""
      }

      <!-- Status actions inside modal -->
      <div>
        <div class="ap-detail-section-label">Update Status</div>
        <div class="ap-status-actions-grid">
          <button class="ap-status-action-btn sab-review ${app.status === "reviewed" ? "is-current" : ""} js-modal-status" data-id="${app.id}" data-status="reviewed">
            <i class="fas fa-eye"></i><span>Mark Reviewed</span>
          </button>
          <button class="ap-status-action-btn sab-shortlist ${app.status === "shortlisted" ? "is-current" : ""} js-modal-status" data-id="${app.id}" data-status="shortlisted">
            <i class="fas fa-star"></i><span>Shortlist</span>
          </button>
          <button class="ap-status-action-btn sab-accept ${app.status === "accepted" ? "is-current" : ""} js-modal-status" data-id="${app.id}" data-status="accepted">
            <i class="fas fa-check-circle"></i><span>Accept</span>
          </button>
          <button class="ap-status-action-btn sab-reject ${app.status === "rejected" ? "is-current" : ""}" id="apDetailRejectBtn" data-id="${app.id}">
            <i class="fas fa-times-circle"></i><span>Decline</span>
          </button>
          <button class="ap-status-action-btn sab-interview" id="apDetailInterviewBtn" data-id="${app.id}">
            <i class="fas fa-calendar-alt"></i><span>Interview</span>
          </button>
        </div>
      </div>

      ${
        hasCV
          ? `
      <div>
        <div class="ap-detail-section-label">Documents</div>
        <a class="ap-btn ap-btn-review" href="/api/applications/${encodeURIComponent(app.id)}/resume" style="text-decoration:none;display:inline-flex">
          <i class="fas fa-file-pdf"></i> Download CV / Resume
        </a>
      </div>`
          : ""
      }
    `;

    // Wire modal action buttons
    body.querySelectorAll(".js-modal-status").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        const id = parseInt(e.currentTarget.dataset.id);
        const status = e.currentTarget.dataset.status;
        const a = applications.find((x) => x.id === id);
        if (!a) return;
        closeModal("apDetailModal");
        if (status === "shortlisted") {
          openStatusNoteModal(
            a,
            "shortlisted",
            `Shortlisting <strong>${esc(a.first_name)}</strong>. Add a note for your team.`,
            "Shortlist Candidate",
          );
        } else if (status === "accepted") {
          openStatusNoteModal(
            a,
            "accepted",
            `Accepting <strong>${esc(a.first_name)}</strong>.`,
            "Accept Candidate",
          );
        } else {
          doStatusUpdate(a, status, "");
        }
      });
    });

    body.querySelector("#apDetailRejectBtn")?.addEventListener("click", () => {
      closeModal("apDetailModal");
      openRejectModal(app);
    });
    body
      .querySelector("#apDetailInterviewBtn")
      ?.addEventListener("click", () => {
        closeModal("apDetailModal");
        openInterviewModal(app);
      });

    // Footer schedule button
    $("apDetailScheduleBtn").onclick = () => {
      closeModal("apDetailModal");
      openInterviewModal(app);
    };

    openModal("apDetailModal");
  }

  /* ─────────────────────────────────────────
     MODAL: INTERVIEW SCHEDULING
     ───────────────────────────────────────── */
  function openInterviewModal(app) {
    interviewTarget = app;
    interviewType = "video";

    // Candidate card
    const card = $("apInterviewCandidate");
    const avClass = avatarClass(app.id);
    card.innerHTML = `
      <div class="ap-avatar ${avClass}" style="width:42px;height:42px;font-size:.85rem;border-radius:12px">${esc(initials(app.first_name, app.last_name))}</div>
      <div class="ap-interview-candidate-info">
        <h4>${esc(app.first_name)} ${esc(app.last_name)}</h4>
        <p>${esc(app.email)}</p>
        <p class="job-label"><i class="fas fa-briefcase" style="font-size:.65rem;margin-right:3px"></i>${esc(app.job_title ?? "")}</p>
      </div>`;

    // Reset type buttons
    document
      .querySelectorAll("#apInterviewTypeGrid .ap-type-btn")
      .forEach((btn) => {
        btn.classList.toggle("is-active", btn.dataset.type === "video");
      });

    // Reset fields
    $("apIntDate").value = "";
    $("apIntLink").value = "";
    $("apIntLocation").value = "";
    $("apIntNotes").value = "";
    $("apIntDuration").value = "60";
    showInterviewTypeFields("video");

    // Set min datetime to now
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset()); // adjust to local
    $("apIntDate").min = now.toISOString().slice(0, 16);

    openModal("apInterviewModal");
  }

  function showInterviewTypeFields(type) {
    const linkWrap = $("apIntLinkWrap");
    const locationWrap = $("apIntLocationWrap");
    if (type === "in_person") {
      linkWrap.classList.add("ap-hidden");
      locationWrap.classList.remove("ap-hidden");
    } else {
      linkWrap.classList.remove("ap-hidden");
      locationWrap.classList.add("ap-hidden");
    }
  }

  // Type grid
  document
    .querySelectorAll("#apInterviewTypeGrid .ap-type-btn")
    .forEach((btn) => {
      btn.addEventListener("click", () => {
        document
          .querySelectorAll("#apInterviewTypeGrid .ap-type-btn")
          .forEach((b) => b.classList.remove("is-active"));
        btn.classList.add("is-active");
        interviewType = btn.dataset.type;
        showInterviewTypeFields(interviewType);
      });
    });

  // Submit
  $("apIntSubmitBtn")?.addEventListener("click", async () => {
    if (!interviewTarget) return;
    const dateVal = $("apIntDate").value;
    if (!dateVal) {
      Toast.error("Please pick a date and time");
      return;
    }

    const btn = $("apIntSubmitBtn");
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scheduling…';

    try {
      // Convert local datetime-local value to UTC ISO string
      const localDate = new Date(dateVal);
      const utcStr = localDate.toISOString();

      await postInterview(interviewTarget.id, {
        scheduled_at: utcStr,
        duration_minutes: parseInt($("apIntDuration").value),
        interview_type: interviewType,
        meeting_link: $("apIntLink").value.trim() || null,
        location: $("apIntLocation").value.trim() || null,
        notes: $("apIntNotes").value.trim() || null,
      });

      // Auto shortlist if still pending/reviewed
      const app = interviewTarget;
      if (["pending", "reviewed"].includes(app.status)) {
        await patchStatus(app.id, "shortlisted", "Scheduled for interview");
        app.status = "shortlisted";
        app.employer_notes = "Scheduled for interview";
        renderStats();
        renderList();
      }

      Toast.success(
        `Interview scheduled with ${interviewTarget.first_name} ${interviewTarget.last_name}`,
      );
      closeModal("apInterviewModal");
    } catch (err) {
      Toast.error(err.message || "Could not schedule interview");
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-paper-plane"></i> Confirm &amp; Send';
    }
  });

  /* ─────────────────────────────────────────
     MODAL HELPERS
     ───────────────────────────────────────── */
  function openModal(id) {
    const el = $(id);
    if (!el) return;
    el.classList.add("is-open");
    document.body.style.overflow = "hidden";
  }
  function closeModal(id) {
    const el = $(id);
    if (!el) return;
    el.classList.remove("is-open");
    // Only unlock scroll if no other modals are open
    if (!document.querySelector(".ap-modal-overlay.is-open")) {
      document.body.style.overflow = "";
    }
  }

  // Close button & backdrop click for all modals
  document.querySelectorAll("[data-close]").forEach((btn) => {
    btn.addEventListener("click", () => closeModal(btn.dataset.close));
  });
  document.querySelectorAll(".ap-modal-overlay").forEach((overlay) => {
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) closeModal(overlay.id);
    });
  });

  // Escape key
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      const open = document.querySelector(".ap-modal-overlay.is-open");
      if (open) closeModal(open.id);
    }
  });

  /* ─────────────────────────────────────────
     CONTROL BAR EVENTS
     ───────────────────────────────────────── */
  function attachControlListeners() {
    elJobSelect?.addEventListener("change", (e) => {
      activeJobId = e.target.value ? parseInt(e.target.value) : null;
      activeStatus = "all";
      document
        .querySelectorAll(".ap-stat-tab")
        .forEach((t) =>
          t.classList.toggle("is-active", t.dataset.filter === "all"),
        );
      renderStats();
      renderList();
    });

    document.querySelectorAll(".ap-stat-tab").forEach((btn) => {
      btn.addEventListener("click", () => {
        activeStatus = btn.dataset.filter;
        document
          .querySelectorAll(".ap-stat-tab")
          .forEach((t) => t.classList.toggle("is-active", t === btn));
        renderList();
      });
    });

    elSearch?.addEventListener("input", (e) => {
      searchQuery = e.target.value.trim();
      renderList();
    });

    elSort?.addEventListener("change", (e) => {
      sortMode = e.target.value;
      renderList();
    });
  }

  /* ─────────────────────────────────────────
     INIT
     ───────────────────────────────────────── */
  async function init() {
    elJobSelect = $("apJobSelect");
    elSearch = $("apSearch");
    elSort = $("apSortSelect");
    elList = $("apListWrap");
    elResultsCount = $("apResultsCount");
    elSubtitle = $("ap-subtitle");

    attachControlListeners();

    try {
      [jobs, applications] = await Promise.all([
        loadJobs(),
        loadApplications(),
      ]);
    } catch (err) {
      elList.innerHTML = `
        <div class="ap-state">
          <div class="ap-state-icon"><i class="fas fa-exclamation-triangle"></i></div>
          <h3>Failed to load</h3>
          <p>${esc(err.message)}</p>
          <button class="ap-btn ap-btn-review" onclick="location.reload()"><i class="fas fa-redo"></i> Retry</button>
        </div>`;
      return;
    }

    renderJobSelect();
    renderStats();
    renderList();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();

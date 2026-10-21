// ============================================================
// SAVED JOBS PAGE  —  saved-jobs.js
// Routes used:
//   GET  /api/user/saved-jobs          — fetch all saved jobs
//   POST /api/jobs/{jobId}/toggle-save — unsave a job
//   POST /api/jobs/{jobId}/apply       — submit application
//   GET  /api/jobseeker/info           — current user profile
// ============================================================

var BASE_API_URL = "/api";
var allSavedJobs = []; // full list from the API
var filteredJobs = []; // what is currently displayed
var currentFilter = "all";
var currentSearch = "";
var currentUser = null; // loaded from /api/jobseeker/info

// ── DOM refs ──────────────────────────────────────────────
var grid = document.getElementById("sjGrid");
var emptyState = document.getElementById("sjEmpty");
var noResults = document.getElementById("sjNoResults");
var searchInput = document.getElementById("sjSearchInput");
var clearAllBtn = document.getElementById("sjClearAll");
var resetFilterBtn = document.getElementById("sjResetFilter");
var totalCount = document.getElementById("sjTotalCount");
var cvCount = document.getElementById("sjCvCount");
var openCount = document.getElementById("sjOpenCount");
var expiringCount = document.getElementById("sjExpiringCount");

// ── Utility helpers ───────────────────────────────────────

function escapeHtml(str) {
  if (!str) return "";
  return String(str).replace(/[&<>"']/g, function (m) {
    return {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;",
    }[m];
  });
}

function formatSalary(job) {
  var min = job.salary_min;
  var max = job.salary_max;
  var cur = job.currency || "";
  if (!min && !max) return null;
  if (min && !max) return cur + " " + Number(min).toLocaleString() + "+";
  if (!min && max) return cur + " up to " + Number(max).toLocaleString();
  return (
    cur +
    " " +
    Number(min).toLocaleString() +
    " \u2013 " +
    Number(max).toLocaleString()
  );
}

/** Days until a deadline. Negative = expired. */
function daysUntil(dateStr) {
  if (!dateStr) return null;
  var d = new Date(dateStr.includes("T") ? dateStr : dateStr + "Z");
  if (isNaN(d)) return null;
  return Math.ceil((d - Date.now()) / 86400000);
}

/** Human-friendly deadline label + CSS class */
function deadlineInfo(expiresAt) {
  var days = daysUntil(expiresAt);
  if (days === null) return null;

  if (days < 0) return { label: "Expired", cls: "deadline-urgent" };
  if (days === 0) return { label: "Closes today!", cls: "deadline-urgent" };
  if (days === 1) return { label: "Closes tomorrow", cls: "deadline-urgent" };
  if (days <= 5)
    return { label: "Closes in " + days + " days", cls: "deadline-soon" };
  if (days <= 14) return { label: "Closes in " + days + " days", cls: "" };

  // Use utcToLocal from main.js for a nice date
  return {
    label:
      "Due " +
      utcToLocal(expiresAt, { includeTime: false, dateFormat: "medium" }),
    cls: "",
  };
}

function isExpiringSoon(job) {
  var days = daysUntil(job.expires_at);
  return days !== null && days >= 0 && days <= 5;
}

function jobTypeLabel(raw) {
  if (!raw) return "";
  return raw.replace(/_/g, " ").replace(/\b\w/g, function (c) {
    return c.toUpperCase();
  });
}

// ── Fetch current user ────────────────────────────────────

async function fetchCurrentUser() {
  try {
    var res = await fetch(BASE_API_URL + "/jobseeker/info");
    if (!res.ok) return;
    var data = await res.json();
    if (!data.success) return;
    var profile = data.data.profile || {};
    var user = data.data.user || {};
    currentUser = Object.assign({}, profile, {
      email: user.email,
      role: user.role,
      avatar: profile.avatar || user.avatar || null,
    });
  } catch (_) {}
}

// ── Fetch saved jobs ──────────────────────────────────────

async function fetchSavedJobs() {
  try {
    showSkeletons(3);
    var res = await fetch(BASE_API_URL + "/user/saved-jobs");
    if (!res.ok) throw new Error("Failed");
    var data = await res.json();
    if (data.success) {
      allSavedJobs = data.data.data || [];
      applyFilterAndSearch();
      updateStats();
    } else {
      Toast.error(data.message || "Couldn't load your saved jobs.");
      showEmpty();
    }
  } catch (_) {
    Toast.error("Network error — please refresh the page.");
    showEmpty();
  }
}

// ── Unsave a job ──────────────────────────────────────────

async function unsaveJob(jobId) {
  try {
    var res = await fetch(BASE_API_URL + "/jobs/" + jobId + "/toggle-save", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
    });
    var data = await res.json();
    if (data.success && !data.data.saved) {
      // Remove from local list
      allSavedJobs = allSavedJobs.filter(function (j) {
        return j.id !== jobId;
      });
      applyFilterAndSearch();
      updateStats();
      Toast.success("Job removed from your saved list.");
    } else if (data.success && data.data.saved) {
      // Edge case: toggled back ON — ignore silently
    } else {
      Toast.error(data.message || "Couldn't remove the job. Please try again.");
    }
  } catch (_) {
    Toast.error("Network error. Please try again.");
  }
}

// ── Clear all ─────────────────────────────────────────────

function confirmClearAll() {
  if (allSavedJobs.length === 0) {
    Toast.info("You have no saved jobs to clear.");
    return;
  }

  Confirm.show({
    title: "Clear all saved jobs?",
    message:
      "This will remove all " +
      allSavedJobs.length +
      " saved job" +
      (allSavedJobs.length !== 1 ? "s" : "") +
      " from your list.",
    type: "warning",
    confirmText: "Yes, clear all",
    cancelText: "Keep them",
    onConfirm: async function () {
      // Unsave each one sequentially (no bulk route exists)
      for (var i = 0; i < allSavedJobs.length; i++) {
        try {
          await fetch(
            BASE_API_URL + "/jobs/" + allSavedJobs[i].id + "/toggle-save",
            {
              method: "POST",
              headers: { "Content-Type": "application/json" },
            },
          );
        } catch (_) {}
      }
      allSavedJobs = [];
      applyFilterAndSearch();
      updateStats();
      Toast.success("All saved jobs have been removed.");
    },
  });
}

// ── Apply modal ───────────────────────────────────────────

function openApplyModal(jobId) {
  var job = allSavedJobs.find(function (j) {
    return j.id === jobId;
  });
  if (!job) return;

  var needsCv = job.requires_resume == 1 || job.requires_resume === true;
  var userHasCv = !!(currentUser && currentUser.resume_url);
  var canApply = !needsCv || userHasCv;

  // Avatar
  var avatarHtml = "";
  if (currentUser) {
    var fullName = [currentUser.first_name, currentUser.last_name]
      .filter(Boolean)
      .join(" ");
    avatarHtml = currentUser.avatar
      ? '<img class="profile-preview-avatar" src="' +
        escapeHtml(currentUser.avatar) +
        '" alt="' +
        escapeHtml(fullName) +
        '">'
      : '<div class="profile-preview-avatar-fallback">' +
        escapeHtml(
          (fullName || "U")
            .split(" ")
            .filter(Boolean)
            .slice(0, 2)
            .map(function (p) {
              return p[0].toUpperCase();
            })
            .join(""),
        ) +
        "</div>";
  }

  // CV banner
  var cvBanner = needsCv
    ? userHasCv
      ? '<div class="cv-ok-banner"><i class="fa-solid fa-circle-check"></i> Your CV will be automatically included with this application.</div>'
      : '<div class="cv-warning-banner">' +
        '<i class="fa-solid fa-triangle-exclamation"></i>' +
        "<div><strong>You're not quite ready to apply for this role.</strong>" +
        "This employer requires a CV/resume to review your application. Without one, your submission won't be considered.<br><br>" +
        '<a href="/jobseeker/settings">Upload your CV in Settings</a> — it only takes a minute!</div>' +
        "</div>"
    : '<div class="cv-ok-banner"><i class="fa-solid fa-circle-check"></i> Great news — no CV is required for this role. You can apply right away!</div>';

  // Job logo
  var logoHtml = job.avatar
    ? '<img src="' +
      escapeHtml(job.avatar) +
      '" alt="' +
      escapeHtml(job.company_name) +
      '">'
    : '<i class="fa-solid fa-building"></i>';

  // Profile rows
  var profileRows = "";
  if (currentUser) {
    var fn =
      [currentUser.first_name, currentUser.last_name]
        .filter(Boolean)
        .join(" ") || "Not set";
    profileRows =
      '<div class="profile-preview-row"><i class="fa-solid fa-user"></i><span>Name</span><strong>' +
      escapeHtml(fn) +
      "</strong></div>" +
      '<div class="profile-preview-row"><i class="fa-solid fa-envelope"></i><span>Email</span><strong>' +
      escapeHtml(currentUser.email || "Not set") +
      "</strong></div>" +
      '<div class="profile-preview-row"><i class="fa-solid fa-briefcase"></i><span>Title</span><strong>' +
      escapeHtml(currentUser.professional_title || "Not set") +
      "</strong></div>" +
      '<div class="profile-preview-row"><i class="fa-solid fa-clock-rotate-left"></i><span>Experience</span><strong>' +
      (currentUser.years_experience
        ? currentUser.years_experience + " yrs"
        : "Not set") +
      "</strong></div>" +
      '<div class="profile-preview-row"><i class="fa-solid fa-file-pdf"></i><span>CV / Resume</span><strong>' +
      (userHasCv
        ? '<span style="color:var(--success-color)"><i class="fa-solid fa-check-circle"></i> Uploaded</span>'
        : '<span style="color:var(--warning-color)"><i class="fa-solid fa-triangle-exclamation"></i> Not uploaded</span>') +
      "</strong></div>";
  } else {
    profileRows =
      '<p style="color:var(--text-muted);font-size:.85rem;">Unable to load your profile info.</p>';
  }

  var confirmBtnHtml = canApply
    ? '<i class="fa-solid fa-paper-plane"></i> Submit Application'
    : '<i class="fa-solid fa-lock"></i> Upload CV to Apply';

  var overlay = document.createElement("div");
  overlay.id = "applyModalOverlay";
  overlay.className = "apply-modal-overlay";
  overlay.innerHTML =
    '<div class="apply-modal" role="dialog" aria-modal="true" aria-labelledby="amTitle">' +
    '<div class="apply-modal-header">' +
    '<h3 id="amTitle"><i class="fa-solid fa-paper-plane"></i> Apply for Job</h3>' +
    '<button class="apply-modal-close" id="amClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>' +
    "</div>" +
    '<div class="apply-modal-body">' +
    '<div class="modal-job-summary">' +
    '<div class="modal-job-logo">' +
    logoHtml +
    "</div>" +
    '<div class="modal-job-info">' +
    "<h4>" +
    escapeHtml(job.title) +
    "</h4>" +
    "<p>" +
    escapeHtml(job.company_name) +
    " &bull; " +
    (job.is_remote ? "Remote" : "On-site") +
    " &bull; " +
    escapeHtml(jobTypeLabel(job.job_type)) +
    "</p>" +
    "</div>" +
    "</div>" +
    cvBanner +
    '<div class="modal-section-label"><i class="fa-solid fa-id-card"></i> Your Application Info</div>' +
    '<div class="profile-preview-card">' +
    (currentUser
      ? '<div class="profile-preview-top">' +
        avatarHtml +
        "<div>" +
        '<p class="profile-preview-name">' +
        escapeHtml(
          [currentUser.first_name, currentUser.last_name]
            .filter(Boolean)
            .join(" ") || "—",
        ) +
        "</p>" +
        '<p class="profile-preview-tagline">' +
        escapeHtml(currentUser.professional_title || "Job Seeker") +
        "</p>" +
        "</div>" +
        "</div>"
      : "") +
    profileRows +
    "</div>" +
    '<p class="edit-profile-note"><i class="fa-solid fa-circle-info"></i> Only your application is sent &mdash; not this info. To update your details, <a href="/jobseeker/settings">go to Settings</a>.</p>' +
    "</div>" +
    '<div class="apply-modal-footer">' +
    '<button class="btn-cancel" id="amCancel">Cancel</button>' +
    '<button class="btn-confirm-apply' +
    (needsCv && !userHasCv ? " cv-locked" : "") +
    '"' +
    ' id="amConfirm" data-job-id="' +
    job.id +
    '"' +
    (!canApply ? " disabled" : "") +
    ">" +
    confirmBtnHtml +
    "</button>" +
    "</div>" +
    "</div>";

  document.body.appendChild(overlay);

  var closeModal = function () {
    overlay.remove();
  };

  document.getElementById("amClose").addEventListener("click", closeModal);
  document.getElementById("amCancel").addEventListener("click", closeModal);
  overlay.addEventListener("click", function (e) {
    if (e.target === overlay) closeModal();
  });

  if (canApply) {
    document
      .getElementById("amConfirm")
      .addEventListener("click", async function () {
        var btn = document.getElementById("amConfirm");
        btn.disabled = true;
        btn.innerHTML =
          '<i class="fa-solid fa-spinner fa-spin"></i> Submitting\u2026';
        await submitApplication(job.id);
        closeModal();
      });
  }
}

async function submitApplication(jobId) {
  try {
    var res = await fetch(BASE_API_URL + "/jobs/" + jobId + "/apply", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
    });
    var data = await res.json();
    if (data.success) {
      Toast.success("Your application has been submitted successfully!");
    } else {
      var errors = data.errors || {};
      if (errors.resume) {
        Toast.warning(
          "This role requires a CV. Please upload one in Settings and try again.",
        );
      } else {
        Toast.error(data.message || "Application failed. Please try again.");
      }
    }
  } catch (_) {
    Toast.error("Network error. Please check your connection.");
  }
}

// ── Stats bar ─────────────────────────────────────────────

function updateStats() {
  var total = allSavedJobs.length;
  var needCv = allSavedJobs.filter(function (j) {
    return j.requires_resume == 1;
  }).length;
  var noCv = total - needCv;
  var expiring = allSavedJobs.filter(function (j) {
    return isExpiringSoon(j);
  }).length;

  if (totalCount) totalCount.textContent = total;
  if (cvCount) cvCount.textContent = needCv;
  if (openCount) openCount.textContent = noCv;
  if (expiringCount) expiringCount.textContent = expiring;
}

// ── Filter + search ───────────────────────────────────────

function applyFilterAndSearch() {
  var q = currentSearch.toLowerCase().trim();

  filteredJobs = allSavedJobs.filter(function (job) {
    // Filter tab
    if (currentFilter === "cv_required" && !(job.requires_resume == 1))
      return false;
    if (currentFilter === "no_cv" && job.requires_resume == 1) return false;
    if (currentFilter === "expiring" && !isExpiringSoon(job)) return false;

    // Search
    if (q) {
      var haystack = [
        job.title,
        job.company_name,
        job.description,
        job.requirements,
        job.location,
      ]
        .filter(Boolean)
        .join(" ")
        .toLowerCase();
      if (haystack.indexOf(q) === -1) return false;
    }

    return true;
  });

  renderGrid();
}

// ── Render ────────────────────────────────────────────────

function renderGrid() {
  // Show / hide empty states
  var total = allSavedJobs.length;

  if (total === 0) {
    grid.innerHTML = "";
    emptyState.style.display = "flex";
    noResults.style.display = "none";
    if (clearAllBtn) clearAllBtn.style.display = "none";
    return;
  }

  emptyState.style.display = "none";
  if (clearAllBtn) clearAllBtn.style.display = "";

  if (filteredJobs.length === 0) {
    grid.innerHTML = "";
    noResults.style.display = "flex";
    return;
  }

  noResults.style.display = "none";

  grid.innerHTML = filteredJobs
    .map(function (job) {
      var needsCv = job.requires_resume == 1;
      var expiring = isExpiringSoon(job);
      var dl = deadlineInfo(job.expires_at);
      var salary = formatSalary(job);

      // Logo
      var logoHtml = job.avatar
        ? '<img src="' +
          escapeHtml(job.avatar) +
          '" alt="' +
          escapeHtml(job.company_name) +
          '" onerror="this.parentElement.innerHTML=\'<i class=\\\"fa-solid fa-building\\\"></i>\'">'
        : '<i class="fa-solid fa-building"></i>';

      // CV pill
      var cvPill = needsCv
        ? '<span class="sj-cv-pill sj-cv-pill--required"><i class="fa-solid fa-file-lines"></i> CV Required</span>'
        : '<span class="sj-cv-pill sj-cv-pill--open"><i class="fa-solid fa-circle-check"></i> No CV Needed</span>';

      // Deadline chip
      var dlChip = "";
      if (dl) {
        dlChip =
          '<span class="sj-meta-chip ' +
          dl.cls +
          '"><i class="fa-regular fa-clock"></i> ' +
          escapeHtml(dl.label) +
          "</span>";
      }

      // Salary chip
      var salaryChip = salary
        ? '<span class="sj-meta-chip"><i class="fa-solid fa-coins"></i> ' +
          escapeHtml(salary) +
          "</span>"
        : "";

      // Job type chip
      var typeChip = job.job_type
        ? '<span class="sj-meta-chip"><i class="fa-solid fa-tag"></i> ' +
          escapeHtml(jobTypeLabel(job.job_type)) +
          "</span>"
        : "";

      // Remote chip
      var remoteChip =
        '<span class="sj-meta-chip"><i class="fa-solid fa-' +
        (job.is_remote ? "laptop-house" : "building") +
        '"></i> ' +
        (job.is_remote ? "Remote" : "On-site") +
        "</span>";

      // Saved-on time — uses utcToRelativeTime from main.js
      var savedOn = job.created_at
        ? '<span class="sj-saved-on"><i class="fa-regular fa-bookmark"></i> Saved ' +
          '<span data-utc-timestamp="' +
          escapeHtml(job.created_at) +
          '">' +
          utcToRelativeTime(job.created_at) +
          "</span>" +
          "</span>"
        : "";

      // Apply button label
      var applyLabel = needsCv ? "Apply &mdash; CV Required" : "Apply Now";
      var applyIcon = needsCv ? "fa-file-lines" : "fa-paper-plane";

      return (
        '<div class="sj-card' +
        (expiring ? " is-expiring" : "") +
        '" data-job-id="' +
        job.id +
        '">' +
        // Unsave button
        '<button class="sj-unsave-btn" data-job-id="' +
        job.id +
        '" title="Remove from saved" aria-label="Remove from saved">' +
        '<i class="fa-solid fa-bookmark"></i>' +
        "</button>" +
        // Card top
        '<div class="sj-card-top">' +
        '<div class="sj-logo">' +
        logoHtml +
        "</div>" +
        '<div class="sj-company-info">' +
        '<p class="sj-company-name">' +
        escapeHtml(job.company_name) +
        "</p>" +
        '<p class="sj-company-meta">' +
        escapeHtml(job.experience_level || "") +
        (job.experience_level ? " &bull; " : "") +
        (job.is_remote ? "Remote" : "On-site") +
        "</p>" +
        "</div>" +
        "</div>" +
        // Title + CV pill
        '<div class="sj-title-row">' +
        '<div class="sj-job-title">' +
        escapeHtml(job.title) +
        "</div>" +
        cvPill +
        "</div>" +
        // Meta chips
        '<div class="sj-meta-row">' +
        dlChip +
        salaryChip +
        typeChip +
        remoteChip +
        "</div>" +
        // Description snippet
        '<div class="sj-desc">' +
        escapeHtml((job.description || "").substring(0, 200)) +
        "</div>" +
        // Saved-on
        savedOn +
        // Actions
        '<div class="sj-actions">' +
        '<button class="sj-btn-apply' +
        (needsCv ? " needs-cv" : "") +
        '" data-job-id="' +
        job.id +
        '">' +
        '<i class="fa-solid ' +
        applyIcon +
        '"></i> ' +
        applyLabel +
        "</button>" +
        '<button class="sj-btn-view" data-job-id="' +
        job.id +
        '">' +
        '<i class="fa-solid fa-eye"></i> View' +
        "</button>" +
        "</div>" +
        "</div>"
      );
    })
    .join("");

  attachCardListeners();
  // Kick the auto-updater for any new data-utc-timestamp elements
  updateRelativeTimeElements("[data-utc-timestamp]");
}

// ── Card event listeners ──────────────────────────────────

function attachCardListeners() {
  // Unsave buttons
  document.querySelectorAll(".sj-unsave-btn").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      var jobId = parseInt(btn.dataset.jobId);
      var job = allSavedJobs.find(function (j) {
        return j.id === jobId;
      });
      var label = job ? job.title : "this job";

      Confirm.show({
        title: "Remove saved job?",
        message: 'Remove "' + label + '" from your saved list?',
        type: "warning",
        confirmText: "Remove it",
        cancelText: "Keep it",
        onConfirm: function () {
          unsaveJob(jobId);
        },
      });
    });
  });

  // Apply buttons
  document.querySelectorAll(".sj-btn-apply").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      openApplyModal(parseInt(btn.dataset.jobId));
    });
  });

  // View buttons — scroll to / link to job detail
  document.querySelectorAll(".sj-btn-view").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      var jobId = parseInt(btn.dataset.jobId);
      var job = allSavedJobs.find(function (j) {
        return j.id === jobId;
      });
      if (job && job.slug) {
        window.location.href =
          "/jobseeker/jobs?slug=" + encodeURIComponent(job.slug);
      } else {
        window.location.href = "/jobseeker/jobs?job=" + jobId;
      }
    });
  });
}

// ── Skeleton loader ───────────────────────────────────────

function showSkeletons(count) {
  emptyState.style.display = "none";
  noResults.style.display = "none";
  var html = "";
  for (var i = 0; i < count; i++) {
    html +=
      '<div class="sj-card glossy-placeholder">' +
      '<div class="sj-card-top"><div class="sj-logo"></div><div class="sj-company-info"><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p><p>&nbsp;</p></div></div>' +
      '<div class="sj-title-row"><div class="sj-job-title">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div></div>' +
      '<div class="sj-meta-row">&nbsp;</div>' +
      '<div class="sj-desc">&nbsp;</div>' +
      '<div class="sj-actions">&nbsp;</div>' +
      "</div>";
  }
  grid.innerHTML = html;
}

function showEmpty() {
  grid.innerHTML = "";
  emptyState.style.display = "flex";
  noResults.style.display = "none";
}

// ── Event wiring ──────────────────────────────────────────

// Filter buttons
document.querySelectorAll(".sj-filter-btn").forEach(function (btn) {
  btn.addEventListener("click", function () {
    document.querySelectorAll(".sj-filter-btn").forEach(function (b) {
      b.classList.remove("active");
    });
    btn.classList.add("active");
    currentFilter = btn.dataset.filter;
    applyFilterAndSearch();
  });
});

// Search input (debounced)
var searchTimer = null;
if (searchInput) {
  searchInput.addEventListener("input", function (e) {
    clearTimeout(searchTimer);
    currentSearch = e.target.value;
    searchTimer = setTimeout(applyFilterAndSearch, 220);
  });
}

// Clear all button
if (clearAllBtn) {
  clearAllBtn.addEventListener("click", confirmClearAll);
}

// Reset filter from "no results" state
if (resetFilterBtn) {
  resetFilterBtn.addEventListener("click", function () {
    currentFilter = "all";
    currentSearch = "";
    if (searchInput) searchInput.value = "";
    document.querySelectorAll(".sj-filter-btn").forEach(function (b) {
      b.classList.toggle("active", b.dataset.filter === "all");
    });
    applyFilterAndSearch();
  });
}

// ── Init ──────────────────────────────────────────────────

async function init() {
  await fetchCurrentUser();
  await fetchSavedJobs();
}

init();

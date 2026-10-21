// ============================================================
// EMPLOYER INTERVIEWS PAGE  —  employer-interviews.js
//
// Routes used (from routes.php):
//   GET   /api/interviews?with_details=true
//   PATCH /api/interviews/{id}/cancel
//   PATCH /api/interviews/{id}/complete
//   PUT   /api/interviews/{id}          ← reschedule
// ============================================================

var BASE_API_URL = "/api";
var interviews = []; // full list loaded from API
var currentFilter = "all";
var currentSearch = "";
var currentRescheduleId = null; // interview id currently being rescheduled

// ── DOM refs ──────────────────────────────────────────────────────────────
var grid = document.getElementById("interviewsGrid");
var emptyState = document.getElementById("eiEmpty");
var searchInput = document.getElementById("searchFilter");
var resetFiltersBtn = document.getElementById("eiResetFilters");

// Modal elements
var modal = document.getElementById("rescheduleModal");
var modalClose = document.getElementById("rescheduleModalClose");
var modalCancelBtn = document.getElementById("rescheduleCancelBtn");
var modalSubmitBtn = document.getElementById("rescheduleSubmitBtn");
var candidateInfo = document.getElementById("rescheduleCandidateInfo");
var form = document.getElementById("rescheduleForm");
var fDateTime = document.getElementById("rescheduleDateTime");
var fDuration = document.getElementById("rescheduleDuration");
var fType = document.getElementById("rescheduleType");
var fLink = document.getElementById("rescheduleLink");
var fLocation = document.getElementById("rescheduleLocation");
var fNotes = document.getElementById("rescheduleNotes");
var linkGroup = document.getElementById("rescheduleLinkGroup");
var locationGroup = document.getElementById("rescheduleLocationGroup");

// Stat counters
var statUpcomingCount = document.getElementById("statUpcomingCount");
var statCompletedCount = document.getElementById("statCompletedCount");
var statCancelledCount = document.getElementById("statCancelledCount");

// ── Helpers ───────────────────────────────────────────────────────────────

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

function capitalize(str) {
  if (!str) return "";
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatDuration(minutes) {
  if (!minutes) return "Duration N/A";
  if (minutes < 60) return minutes + " min";
  var h = Math.floor(minutes / 60);
  var m = minutes % 60;
  return m ? h + "h " + m + "m" : h + "h";
}

/** Normalise API status → display token */
function displayStatus(apiStatus) {
  if (apiStatus === "scheduled") return "upcoming";
  return apiStatus || "upcoming";
}

/** Build a human-friendly countdown for an upcoming interview */
function countdownInfo(scheduledAt) {
  if (!scheduledAt) return null;
  var d = new Date(scheduledAt.includes("T") ? scheduledAt : scheduledAt + "Z");
  if (isNaN(d)) return null;
  var diffMs = d - Date.now();
  var diffMins = Math.round(diffMs / 60000);

  if (diffMs < 0) return null; // past, don't show countdown

  var cls, label;
  if (diffMins <= 60) {
    cls = "today";
    label = diffMins <= 1 ? "Starting now!" : "In " + diffMins + " min";
  } else if (diffMins <= 60 * 24) {
    cls = "today";
    label = "Today at " + utcToLocal(scheduledAt, { includeDate: false });
  } else if (diffMins <= 60 * 24 * 3) {
    cls = "soon";
    var days = Math.floor(diffMins / (60 * 24));
    label = "In " + days + " day" + (days > 1 ? "s" : "");
  } else {
    cls = "future";
    label = utcToLocal(scheduledAt, {
      includeTime: false,
      dateFormat: "medium",
    });
  }
  return { cls: cls, label: label };
}

/** Pick one of 5 avatar colour classes based on name hash */
function avatarClass(name) {
  var code = (name || "").split("").reduce(function (s, c) {
    return s + c.charCodeAt(0);
  }, 0);
  return "av" + ((code % 5) + 1);
}

function interviewTypeIcon(type) {
  if (type === "video") return "fa-solid fa-video";
  if (type === "phone") return "fa-solid fa-phone";
  if (type === "in_person") return "fa-solid fa-building";
  return "fa-solid fa-question";
}

function interviewTypeLabel(type) {
  if (type === "video") return "Video Call";
  if (type === "phone") return "Phone Call";
  if (type === "in_person") return "In Person";
  return capitalize(type || "");
}

// ── API calls ─────────────────────────────────────────────────────────────

async function apiFetch(url, opts) {
  var res = await fetch(BASE_API_URL + url, opts || {});
  var data = await res.json();
  if (!res.ok) throw new Error(data.message || "Request failed");
  return data;
}

async function fetchInterviews() {
  var data = await apiFetch("/interviews?with_details=true");
  // Handle both data.data (array) and data.data.data (paginated)
  if (Array.isArray(data.data)) return data.data;
  if (data.data && Array.isArray(data.data.data)) return data.data.data;
  return [];
}

async function cancelInterview(id) {
  return apiFetch("/interviews/" + id + "/cancel", {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
  });
}

async function completeInterview(id) {
  return apiFetch("/interviews/" + id + "/complete", {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
  });
}

async function rescheduleInterview(id, payload) {
  return apiFetch("/interviews/" + id, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

// ── Stats bar ─────────────────────────────────────────────────────────────

function updateStats() {
  var upcoming = interviews.filter(function (i) {
    return displayStatus(i.status) === "upcoming";
  }).length;
  var completed = interviews.filter(function (i) {
    return displayStatus(i.status) === "completed";
  }).length;
  var cancelled = interviews.filter(function (i) {
    return displayStatus(i.status) === "cancelled";
  }).length;
  if (statUpcomingCount) statUpcomingCount.textContent = upcoming;
  if (statCompletedCount) statCompletedCount.textContent = completed;
  if (statCancelledCount) statCancelledCount.textContent = cancelled;
}

// ── Filter + search ───────────────────────────────────────────────────────

function filtered() {
  var q = currentSearch.toLowerCase().trim();
  return interviews.filter(function (i) {
    var ds = displayStatus(i.status);
    if (currentFilter !== "all" && ds !== currentFilter) return false;
    if (q) {
      var hay = [
        i.candidate_first_name,
        i.candidate_last_name,
        i.candidate_email,
        i.job_title,
      ]
        .filter(Boolean)
        .join(" ")
        .toLowerCase();
      if (hay.indexOf(q) === -1) return false;
    }
    return true;
  });
}

// ── Render ────────────────────────────────────────────────────────────────

function render() {
  var list = filtered();

  if (list.length === 0) {
    grid.innerHTML = "";
    emptyState.style.display = "flex";
    return;
  }

  emptyState.style.display = "none";

  grid.innerHTML = list
    .map(function (iv) {
      var ds = displayStatus(iv.status);
      var fullName =
        escapeHtml(iv.candidate_first_name) +
        " " +
        escapeHtml(iv.candidate_last_name);
      var initials =
        (iv.candidate_first_name || "?").charAt(0).toUpperCase() +
        (iv.candidate_last_name || "?").charAt(0).toUpperCase();
      var avCls = avatarClass(
        iv.candidate_first_name + " " + iv.candidate_last_name,
      );

      // Status badge
      var statusIcon =
        ds === "upcoming"
          ? "fa-solid fa-calendar-check"
          : ds === "completed"
            ? "fa-solid fa-circle-check"
            : "fa-solid fa-circle-xmark";

      var statusBadge =
        '<span class="ei-status-badge status-' +
        ds +
        '">' +
        '<i class="' +
        statusIcon +
        '"></i> ' +
        capitalize(ds) +
        "</span>";

      // Countdown chip (only for upcoming)
      var countdownChip = "";
      if (ds === "upcoming") {
        var cd = countdownInfo(iv.scheduled_at);
        if (cd) {
          countdownChip =
            '<span class="ei-countdown ' +
            cd.cls +
            '">' +
            '<i class="fa-solid fa-hourglass-half"></i> ' +
            escapeHtml(cd.label) +
            "</span>";
        }
      }

      // Date/time chip — use utcToLocal from main.js
      var dateLabel = iv.scheduled_at
        ? utcToLocal(iv.scheduled_at, { dateFormat: "medium" })
        : "Date TBD";

      // Detail chips
      var chips =
        '<span class="ei-detail-chip"><i class="fa-regular fa-calendar-alt"></i> ' +
        escapeHtml(dateLabel) +
        "</span>" +
        '<span class="ei-detail-chip"><i class="fa-regular fa-clock"></i> ' +
        escapeHtml(formatDuration(iv.duration_minutes)) +
        "</span>" +
        '<span class="ei-detail-chip"><i class="' +
        interviewTypeIcon(iv.interview_type) +
        '"></i> ' +
        escapeHtml(interviewTypeLabel(iv.interview_type)) +
        "</span>" +
        (iv.meeting_link
          ? '<span class="ei-detail-chip"><i class="fa-solid fa-link"></i> <a href="' +
            escapeHtml(iv.meeting_link) +
            '" target="_blank" rel="noopener">Join Meeting</a></span>'
          : "") +
        (iv.location
          ? '<span class="ei-detail-chip"><i class="fa-solid fa-location-dot"></i> ' +
            escapeHtml(iv.location) +
            "</span>"
          : "") +
        countdownChip;

      // Notes
      var notesBlock = iv.notes
        ? '<div class="ei-notes"><i class="fa-solid fa-note-sticky"></i><span>' +
          escapeHtml(iv.notes) +
          "</span></div>"
        : "";

      // Action buttons
      var actions = "";
      if (ds === "upcoming") {
        actions =
          '<button class="ei-btn ei-btn-reschedule" data-id="' +
          iv.id +
          '">' +
          '<i class="fa-solid fa-calendar-pen"></i> Reschedule' +
          "</button>" +
          (iv.meeting_link
            ? '<a href="' +
              escapeHtml(iv.meeting_link) +
              '" target="_blank" rel="noopener" class="ei-btn ei-btn-open-meeting">' +
              '<i class="fa-solid fa-video"></i> Join' +
              "</a>"
            : "") +
          '<button class="ei-btn ei-btn-cancel" data-id="' +
          iv.id +
          '">' +
          '<i class="fa-solid fa-calendar-xmark"></i> Cancel' +
          "</button>" +
          '<button class="ei-btn ei-btn-complete" data-id="' +
          iv.id +
          '">' +
          '<i class="fa-solid fa-circle-check"></i> Mark Completed' +
          "</button>";
      } else if (ds === "completed") {
        actions =
          '<button class="ei-btn ei-btn-reschedule" disabled>' +
          '<i class="fa-solid fa-circle-check"></i> Completed' +
          "</button>";
      } else if (ds === "cancelled") {
        actions =
          '<button class="ei-btn ei-btn-cancel" disabled>' +
          '<i class="fa-solid fa-circle-xmark"></i> Cancelled' +
          "</button>";
      }

      return (
        '<div class="ei-card" data-id="' +
        iv.id +
        '" data-status="' +
        iv.status +
        '">' +
        // Card header: candidate + status badge
        '<div class="ei-card-header">' +
        '<div class="ei-candidate-row">' +
        '<div class="ei-avatar ' +
        avCls +
        '">' +
        escapeHtml(initials) +
        "</div>" +
        '<div class="ei-candidate-details">' +
        '<p class="ei-candidate-name">' +
        fullName +
        "</p>" +
        (iv.candidate_email
          ? '<p class="ei-candidate-email">' +
            escapeHtml(iv.candidate_email) +
            "</p>"
          : "") +
        (iv.job_title
          ? '<span class="ei-job-label"><i class="fa-solid fa-briefcase"></i>' +
            escapeHtml(iv.job_title) +
            "</span>"
          : "") +
        "</div>" +
        "</div>" +
        statusBadge +
        "</div>" +
        // Details row
        '<div class="ei-detail-row">' +
        chips +
        "</div>" +
        // Notes
        notesBlock +
        // Actions
        '<div class="ei-card-actions">' +
        actions +
        "</div>" +
        "</div>"
      );
    })
    .join("");

  attachCardListeners();
  // Update relative timestamps from main.js
  updateRelativeTimeElements("[data-utc-timestamp]");
}

// ── Card event listeners ──────────────────────────────────────────────────

function attachCardListeners() {
  document
    .querySelectorAll(".ei-btn-reschedule[data-id]")
    .forEach(function (btn) {
      btn.addEventListener("click", function () {
        openRescheduleModal(parseInt(btn.dataset.id));
      });
    });

  document.querySelectorAll(".ei-btn-cancel[data-id]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var id = parseInt(btn.dataset.id);
      var iv = interviews.find(function (i) {
        return i.id === id;
      });
      var name = iv
        ? iv.candidate_first_name + " " + iv.candidate_last_name
        : "this candidate";

      Confirm.show({
        type: "error",
        heading: "Cancel Interview?",
        message:
          "Are you sure you want to cancel the interview with " + name + "?",
        details:
          "The candidate will be notified. This action cannot be undone.",
        confirmText: "Yes, cancel it",
        cancelText: "Keep it",
        onConfirm: async function () {
          try {
            await cancelInterview(id);
            Toast.success("Interview cancelled successfully.");
            await refresh();
          } catch (err) {
            Toast.error(
              err.message || "Failed to cancel interview. Please try again.",
            );
          }
        },
      });
    });
  });

  document
    .querySelectorAll(".ei-btn-complete[data-id]")
    .forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = parseInt(btn.dataset.id);
        var iv = interviews.find(function (i) {
          return i.id === id;
        });
        var name = iv
          ? iv.candidate_first_name + " " + iv.candidate_last_name
          : "this candidate";

        Confirm.show({
          type: "success",
          heading: "Mark as Completed?",
          message: "Mark the interview with " + name + " as completed?",
          details:
            "You can use this to keep your interview records up to date.",
          confirmText: "Yes, mark completed",
          cancelText: "Not yet",
          onConfirm: async function () {
            try {
              await completeInterview(id);
              Toast.success("Interview marked as completed.");
              await refresh();
            } catch (err) {
              Toast.error(
                err.message || "Failed to update interview. Please try again.",
              );
            }
          },
        });
      });
    });
}

// ── Reschedule modal ──────────────────────────────────────────────────────

function openRescheduleModal(id) {
  var iv = interviews.find(function (i) {
    return i.id === id;
  });
  if (!iv) return;
  currentRescheduleId = id;

  // Fill candidate strip
  var initials =
    (iv.candidate_first_name || "?").charAt(0).toUpperCase() +
    (iv.candidate_last_name || "?").charAt(0).toUpperCase();
  var avCls = avatarClass(
    iv.candidate_first_name + " " + iv.candidate_last_name,
  );
  candidateInfo.innerHTML =
    '<div class="ei-avatar ' +
    avCls +
    '">' +
    escapeHtml(initials) +
    "</div>" +
    "<div>" +
    '<p class="ei-modal-candidate-name">' +
    escapeHtml(iv.candidate_first_name) +
    " " +
    escapeHtml(iv.candidate_last_name) +
    "</p>" +
    '<p class="ei-modal-candidate-meta">' +
    escapeHtml(iv.candidate_email || "") +
    (iv.job_title ? " &bull; " + escapeHtml(iv.job_title) : "") +
    "</p>" +
    "</div>";

  // Pre-fill fields
  if (iv.scheduled_at) {
    // Convert to local datetime-local value
    var d = new Date(
      iv.scheduled_at.includes("T") ? iv.scheduled_at : iv.scheduled_at + "Z",
    );
    var localIso = new Date(d.getTime() - d.getTimezoneOffset() * 60000)
      .toISOString()
      .slice(0, 16);
    fDateTime.value = localIso;
  } else {
    fDateTime.value = "";
  }
  fDuration.value = iv.duration_minutes || 30;
  fLink.value = iv.meeting_link || "";
  fLocation.value = iv.location || "";
  fNotes.value = iv.notes || "";

  // Set type toggle
  var type = iv.interview_type || "video";
  fType.value = type;
  document.querySelectorAll(".ei-type-btn").forEach(function (btn) {
    btn.classList.toggle("active", btn.dataset.value === type);
  });
  toggleTypeFields(type);

  modal.classList.add("show");
  fDateTime.focus();
}

function closeRescheduleModal() {
  modal.classList.remove("show");
  currentRescheduleId = null;
}

function toggleTypeFields(type) {
  var showLink = type === "video" || type === "phone";
  linkGroup.style.display = showLink ? "" : "none";
  locationGroup.style.display = showLink ? "none" : "";
}

// Type toggle buttons
document.querySelectorAll(".ei-type-btn").forEach(function (btn) {
  btn.addEventListener("click", function () {
    var val = btn.dataset.value;
    document.querySelectorAll(".ei-type-btn").forEach(function (b) {
      b.classList.remove("active");
    });
    btn.classList.add("active");
    fType.value = val;
    toggleTypeFields(val);
  });
});

// Modal close buttons
if (modalClose) modalClose.addEventListener("click", closeRescheduleModal);
if (modalCancelBtn)
  modalCancelBtn.addEventListener("click", closeRescheduleModal);
modal.addEventListener("click", function (e) {
  if (e.target === modal) closeRescheduleModal();
});

// Submit
if (modalSubmitBtn) {
  modalSubmitBtn.addEventListener("click", async function () {
    if (!currentRescheduleId) return;

    if (!fDateTime.value) {
      Toast.warning("Please choose a new date and time for the interview.");
      fDateTime.focus();
      return;
    }

    var payload = {
      scheduled_at: fDateTime.value,
      duration_minutes: parseInt(fDuration.value) || 30,
      interview_type: fType.value,
      meeting_link: fLink.value || null,
      location: fLocation.value || null,
      notes: fNotes.value || null,
    };

    modalSubmitBtn.disabled = true;
    modalSubmitBtn.innerHTML =
      '<i class="fa-solid fa-spinner fa-spin"></i> Saving\u2026';

    try {
      await rescheduleInterview(currentRescheduleId, payload);
      closeRescheduleModal();
      Toast.success(
        "Interview rescheduled successfully. The candidate will be notified.",
      );
      await refresh();
    } catch (err) {
      Toast.error(
        err.message ||
          "Failed to reschedule. Please check the details and try again.",
      );
    } finally {
      modalSubmitBtn.disabled = false;
      modalSubmitBtn.innerHTML =
        '<i class="fa-solid fa-calendar-check"></i> Update Interview';
    }
  });
}

// Also support form submit (enter key on inputs)
if (form) {
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    if (modalSubmitBtn) modalSubmitBtn.click();
  });
}

// ── Filter tab wiring ─────────────────────────────────────────────────────

document.querySelectorAll(".ei-tab").forEach(function (btn) {
  btn.addEventListener("click", function () {
    document.querySelectorAll(".ei-tab").forEach(function (b) {
      b.classList.remove("active");
    });
    btn.classList.add("active");
    currentFilter = btn.dataset.status;
    render();
  });
});

// Search
var searchTimer = null;
if (searchInput) {
  searchInput.addEventListener("input", function (e) {
    clearTimeout(searchTimer);
    currentSearch = e.target.value;
    searchTimer = setTimeout(render, 200);
  });
}

// Reset filters from empty state
if (resetFiltersBtn) {
  resetFiltersBtn.addEventListener("click", function () {
    currentFilter = "all";
    currentSearch = "";
    if (searchInput) searchInput.value = "";
    document.querySelectorAll(".ei-tab").forEach(function (b) {
      b.classList.toggle("active", b.dataset.status === "all");
    });
    render();
  });
}

// ── Skeletons ─────────────────────────────────────────────────────────────

function showSkeletons() {
  emptyState.style.display = "none";
  var html = "";
  for (var i = 0; i < 3; i++) {
    html +=
      '<div class="ei-card ei-skeleton">' +
      '<div class="ei-card-header">' +
      '<div class="ei-avatar-skeleton"></div>' +
      '<div class="ei-info-skeleton"><span></span><span></span><span></span></div>' +
      "</div>" +
      '<div class="ei-detail-row-skeleton"></div>' +
      '<div class="ei-actions-skeleton"></div>' +
      "</div>";
  }
  grid.innerHTML = html;
}

// ── Refresh ───────────────────────────────────────────────────────────────

async function refresh() {
  try {
    showSkeletons();
    interviews = await fetchInterviews();
    updateStats();
    render();
  } catch (err) {
    console.error(err);
    grid.innerHTML =
      '<div class="ei-empty" style="display:flex">' +
      '<div class="ei-empty-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>' +
      "<h2>Couldn't load interviews</h2>" +
      "<p>" +
      escapeHtml(err.message || "Please refresh the page and try again.") +
      "</p>" +
      "</div>";
    Toast.error(err.message || "Failed to load interviews. Please refresh.");
  }
}

// ── Init ──────────────────────────────────────────────────────────────────

refresh();

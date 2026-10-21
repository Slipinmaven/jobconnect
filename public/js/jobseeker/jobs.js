// ==================== Configuration & State ====================
const BASE_API_URL = "/api";
let currentJobs = [];
let searchTimeout = null;
let isLoading = false;

// Stores the merged profile + user object from /api/jobseeker/info
let currentUser = null;

// Comment state
let currentCommentType = null;
let currentCommentEntityId = null;
let currentCommentSort = "newest";
let currentCommentPage = 1;
let currentComments = [];
let isLoadingComments = false;
let hasMoreComments = true;

// DOM elements
const jobsGrid = document.getElementById("jobsGrid");
const searchInput = document.getElementById("searchInput");
const commentSection = document.querySelector(".comment-section");
const commentsContainer = document.getElementById("comments-container");
const commentHeaderTitle = document.querySelector(".comment-header h2");
const commentSortSelect = document.getElementById("comment-sort");
const addCommentForm = document.getElementById("add-comment-form");
const commentInput = document.getElementById("comment-input");

// ==================== Helper Functions ====================

function formatDate(isoString) {
  if (!isoString) return "Recently";
  const date = new Date(isoString);
  const now = new Date();
  const diff = Math.floor((now - date) / 1000);
  if (diff < 60) return "Just now";
  if (diff < 3600) return Math.floor(diff / 60) + "m ago";
  if (diff < 86400) return Math.floor(diff / 3600) + "h ago";
  if (diff < 604800) return Math.floor(diff / 86400) + "d ago";
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

function formatDeadline(expiresAt) {
  if (!expiresAt) return "No deadline";
  const deadline = new Date(expiresAt);
  const today = new Date();
  const diffDays = Math.ceil((deadline - today) / (1000 * 60 * 60 * 24));
  if (diffDays < 0) return "Expired";
  if (diffDays === 0) return "Today";
  if (diffDays === 1) return "Tomorrow";
  if (diffDays <= 7) return diffDays + " days left";
  return deadline.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
  });
}

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
  const min = job.salary_min;
  const max = job.salary_max;
  const cur = job.currency || "";
  if (!min && !max) return "Salary not specified";
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

/**
 * API returns requirements/responsibilities as a newline-separated string
 * OR as an array. Handle both.
 */
function formatList(value) {
  if (!value) return "<ul><li>Not specified</li></ul>";

  let items = [];

  if (Array.isArray(value)) {
    items = value.map((i) => i.trim()).filter(Boolean);
  } else if (typeof value === "string") {
    items = value
      .split("\n")
      .map((l) => l.trim())
      .filter(Boolean);
  }

  if (items.length === 0) {
    return "<ul><li>Not specified</li></ul>";
  }

  return `<ul>
    ${items.map((i) => `<li>${escapeHtml(i)}</li>`).join("")}
  </ul>`;
}

function getJobOwnerId(job) {
  return job.posted_by || job.employer_id;
}

/** Coloured initials avatar — returns an HTML string */
function avatarFallback(name, small) {
  var initials = (name || "U")
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map(function (p) {
      return p[0].toUpperCase();
    })
    .join("");
  var code = (name || "U").split("").reduce(function (s, c) {
    return s + c.charCodeAt(0);
  }, 0);
  var col = "c" + (code % 5);
  var style = small ? "width:28px;height:28px;font-size:.62rem;" : "";
  return (
    '<div class="avatar-fallback ' +
    col +
    '" style="' +
    style +
    '">' +
    escapeHtml(initials) +
    "</div>"
  );
}

/** Render an <img> with an onerror fallback to the initials avatar */
function renderAvatar(src, name, small) {
  if (src && src.trim()) {
    var fb = avatarFallback(name, small).replace(/'/g, "\\'");
    return (
      '<img class="profile-photo" src="' +
      escapeHtml(src) +
      '" alt="' +
      escapeHtml(name || "User") +
      '"' +
      " onerror=\"this.style.display='none';this.insertAdjacentHTML('afterend','" +
      fb +
      "')\">"
    );
  }
  return avatarFallback(name, small);
}

// ==================== Fetch Current User ====================

async function fetchCurrentUser() {
  try {
    // Uses your existing route: GET /api/jobseeker/info
    var res = await fetch(BASE_API_URL + "/jobseeker/info");
    if (!res.ok) return;
    var data = await res.json();
    if (!data.success) return;

    // Merge profile + user into one flat object so we can read fields simply
    var profile = data.data.profile || {};
    var user = data.data.user || {};
    currentUser = Object.assign({}, profile, {
      email: user.email,
      role: user.role,
      theme: user.theme,
      // prefer profile avatar then user avatar
      avatar: profile.avatar || user.avatar || null,
    });

    // Update the comment-box avatar placeholder
    var addAvatar = document.querySelector(".add-comment-avatar");
    if (addAvatar) {
      var fullName = [currentUser.first_name, currentUser.last_name]
        .filter(Boolean)
        .join(" ");
      if (currentUser.avatar) {
        addAvatar.innerHTML =
          '<img src="' +
          escapeHtml(currentUser.avatar) +
          '" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" alt="You">';
      } else {
        addAvatar.innerHTML = avatarFallback(
          fullName || currentUser.email || "Me",
        );
        addAvatar.style.background = "none";
        addAvatar.style.padding = "0";
      }
    }
  } catch (_) {
    // Not logged in or endpoint not available — silently continue
  }
}

// ==================== API Calls ====================

async function fetchJobs() {
  try {
    isLoading = true;
    showLoadingPlaceholders(3);
    var response = await fetch(BASE_API_URL + "/jobs");
    if (!response.ok) throw new Error("Failed to fetch jobs");
    var data = await response.json();
    if (data.success) {
      currentJobs = (data.data.data || []).map(function (job) {
        return Object.assign({}, job, {
          is_liked: false,
          is_saved: false,
          owner_id: getJobOwnerId(job),
          requirements_text: formatList(job.requirements),
          responsibilities_text: formatList(job.responsibilities),
        });
      });
      await fetchInitialLikeStatuses();
      await fetchInitialSaveStatuses();
      renderJobs(currentJobs);
    } else {
      Toast.error(data.message || "Failed to load jobs");
    }
  } catch (error) {
    console.error(error);
    Toast.error("Network error. Please try again.");
    jobsGrid.innerHTML =
      '<div class="alert alert-danger">Failed to load jobs. Please refresh the page.</div>';
  } finally {
    isLoading = false;
  }
}

async function fetchInitialLikeStatuses() {
  if (currentJobs.length === 0) return;
  var entityIds = currentJobs.map(function (j) {
    return j.id;
  });
  try {
    var params = new URLSearchParams({
      type: "job",
      entity_ids: entityIds.join(","),
    });
    var response = await fetch(
      BASE_API_URL + "/like/statuses?" + params.toString(),
    );
    if (response.ok) {
      var data = await response.json();
      if (data.success && data.data.statuses) {
        currentJobs.forEach(function (job) {
          job.is_liked = data.data.statuses[job.id] || false;
        });
      }
    }
  } catch (error) {
    console.warn("Failed to fetch like statuses", error);
  }
}

async function fetchInitialSaveStatuses() {
  if (currentJobs.length === 0) return;

  try {
    // Batch request to avoid per-job round trips and duplicates
    var response = await fetch(BASE_API_URL + "/user/saved-jobs?per_page=1000");
    if (!response.ok) return;

    var data = await response.json();
    if (!data.success || !Array.isArray(data.data.data)) return;

    var savedIds = new Set(
      data.data.data.map(function (savedJob) {
        return savedJob.id;
      }),
    );

    currentJobs.forEach(function (job) {
      job.is_saved = savedIds.has(job.id);
    });
  } catch (error) {
    console.warn("Failed to fetch save statuses", error);
  }
}

async function toggleSave(jobId) {
  var job = currentJobs.find(function (j) {
    return j.id === jobId;
  });
  if (!job) return;
  try {
    var response = await fetch(
      BASE_API_URL + "/jobs/" + jobId + "/toggle-save",
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
      },
    );
    var data = await response.json();
    if (data.success) {
      job.is_saved = data.data.saved;
      job.saves_count = data.data.saved
        ? (job.saves_count || 0) + 1
        : Math.max((job.saves_count || 0) - 1, 0);
      renderJobs(currentJobs);
      Toast.success(data.data.saved ? "Job saved!" : "Removed from saved");
    } else {
      Toast.error(data.message || "Action failed");
    }
  } catch (_) {
    Toast.error("Network error");
  }
}

async function toggleLike(jobId) {
  var job = currentJobs.find(function (j) {
    return j.id === jobId;
  });
  if (!job) return;
  try {
    var response = await fetch(BASE_API_URL + "/like/toggle", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        type: "job",
        entity_id: jobId,
        content_owner_id: job.owner_id,
      }),
    });
    var data = await response.json();
    if (data.success) {
      job.is_liked = data.data.liked;
      job.likes_count = data.data.likes_count;
      renderJobs(currentJobs);
      Toast.success(data.data.liked ? "Liked!" : "Unliked");
    } else {
      Toast.error(data.message || "Action failed");
    }
  } catch (_) {
    Toast.error("Network error");
  }
}

// ==================== Apply Modal ====================

function openApplyModal(jobId) {
  var job = currentJobs.find(function (j) {
    return j.id === jobId;
  });
  if (!job) return;

  var needsCv = job.requires_resume == 1 || job.requires_resume === true;
  var userHasCv = !!(currentUser && currentUser.resume_url);
  var canApply = !needsCv || userHasCv;

  // --- Avatar in modal ---
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
        avatarFallback(fullName || currentUser.email || "Me") +
        "</div>";
  }

  // --- CV status banner ---
  var cvBanner = "";
  if (needsCv) {
    cvBanner = userHasCv
      ? '<div class="cv-ok-banner"><i class="fa-solid fa-circle-check"></i> Your CV will be attached automatically when you apply.</div>'
      : '<div class="cv-warning-banner">' +
        '<i class="fa-solid fa-triangle-exclamation"></i>' +
        "<div><strong>You're not quite ready to apply for this role.</strong><br>" +
        "This employer requires a CV / resume to review your application. " +
        "Without one, your application cannot be considered.<br><br>" +
        '<a href="/jobseeker/settings">Upload your CV in Settings</a> &mdash; it only takes a minute!' +
        "</div></div>";
  } else {
    cvBanner =
      '<div class="cv-ok-banner"><i class="fa-solid fa-circle-check"></i> Great news &mdash; no CV is required for this role. You can apply right away!</div>';
  }

  // --- Job logo ---
  var jobLogoHtml = job.avatar
    ? '<img src="' +
      escapeHtml(job.avatar) +
      '" alt="' +
      escapeHtml(job.company_name) +
      '">'
    : '<i class="fa-solid fa-building"></i>';

  // --- Profile rows ---
  var profileRows = "";
  if (currentUser) {
    var fullName2 =
      [currentUser.first_name, currentUser.last_name]
        .filter(Boolean)
        .join(" ") || "Not set";
    profileRows =
      '<div class="profile-preview-row"><i class="fa-solid fa-user"></i><span>Name</span><strong>' +
      escapeHtml(fullName2) +
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
      '<p style="color:var(--text-muted);font-size:.85rem;">Could not load your profile info.</p>';
  }

  // --- Submit button text ---
  var confirmBtnHtml = canApply
    ? '<i class="fa-solid fa-paper-plane"></i> Submit Application'
    : '<i class="fa-solid fa-lock"></i> Upload CV to Apply';

  var modal = document.createElement("div");
  modal.id = "applyModalOverlay";
  modal.className = "apply-modal-overlay";
  modal.innerHTML =
    '<div class="apply-modal" role="dialog" aria-modal="true" aria-labelledby="applyModalTitle">' +
    // Header
    '<div class="apply-modal-header">' +
    '<h3 id="applyModalTitle"><i class="fa-solid fa-paper-plane"></i> Apply for Job</h3>' +
    '<button class="apply-modal-close" id="applyModalClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>' +
    "</div>" +
    // Body
    '<div class="apply-modal-body">' +
    //   Job summary chip
    '<div class="modal-job-summary">' +
    '<div class="modal-job-logo">' +
    jobLogoHtml +
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
    escapeHtml(job.job_type || "") +
    "</p>" +
    "</div>" +
    "</div>" +
    //   CV status banner
    cvBanner +
    //   Profile preview (read-only)
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
    '<p class="edit-profile-note"><i class="fa-solid fa-circle-info"></i> This info will be sent to the employer &mdash; To update your details, <a href="/jobseeker/settings">go to Settings</a>.</p>' +
    "</div>" +
    // Footer
    '<div class="apply-modal-footer">' +
    '<button class="btn-cancel" id="applyModalCancel">Cancel</button>' +
    '<button class="btn-confirm-apply' +
    (needsCv && !userHasCv ? " cv-locked" : "") +
    '"' +
    ' id="applyModalConfirm"' +
    ' data-job-id="' +
    job.id +
    '"' +
    (!canApply ? " disabled" : "") +
    ">" +
    confirmBtnHtml +
    "</button>" +
    "</div>" +
    "</div>";

  document.body.appendChild(modal);

  var closeModal = function () {
    modal.remove();
  };

  document
    .getElementById("applyModalClose")
    .addEventListener("click", closeModal);
  document
    .getElementById("applyModalCancel")
    .addEventListener("click", closeModal);
  modal.addEventListener("click", function (e) {
    if (e.target === modal) closeModal();
  });

  if (canApply) {
    document
      .getElementById("applyModalConfirm")
      .addEventListener("click", async function () {
        var btn = document.getElementById("applyModalConfirm");
        btn.disabled = true;
        btn.innerHTML =
          '<i class="fa-solid fa-spinner fa-spin"></i> Submitting\u2026';
        await applyJob(job.id);
        closeModal();
      });
  }
}

async function applyJob(jobId) {
  try {
    var response = await fetch(BASE_API_URL + "/jobs/" + jobId + "/apply", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
    });
    var data = await response.json();
    if (data.success) {
      Toast.success("Your application has been submitted successfully!");
    } else {
      var errors = data.errors || {};
      if (errors.resume) {
        Toast.warning(
          "This role requires a CV. Please upload one in your Settings and try again.",
        );
      } else {
        Toast.error(data.message || "Application failed. Please try again.");
      }
    }
  } catch (_) {
    Toast.error("Network error. Please check your connection.");
  }
}

function reportJob(jobId) {
  var reason = prompt("Please let us know why you're reporting this job:");
  if (!reason) return;
  fetch(BASE_API_URL + "/jobs/" + jobId + "/report", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ reason: reason }),
  })
    .then(function (r) {
      return r.json();
    })
    .then(function (data) {
      if (data.success) {
        Toast.info(
          "Thanks for letting us know. We'll review this job shortly.",
        );
      } else {
        Toast.error(data.message || "Report failed");
      }
    })
    .catch(function () {
      Toast.error("Network error");
    });
}

function shareJob(job) {
  var url =
    window.location.origin + window.location.pathname + "?job=" + job.id;
  if (navigator.share) {
    navigator
      .share({
        title: job.title,
        text: "Check out this job: " + job.title + " at " + job.company_name,
        url: url,
      })
      .catch(function () {});
  } else {
    navigator.clipboard.writeText(url);
    Toast.success("Link copied to clipboard!");
  }
}

function viewCompanyInfo(job) {
  alert(
    "Company: " +
      job.company_name +
      "\nType: " +
      (job.employer_type || "N/A") +
      "\nLocation: " +
      (job.is_remote ? "Remote" : "On-site") +
      "\n\nMore company details coming soon.",
  );
}

function stopSeeingJob(jobId) {
  currentJobs = currentJobs.filter(function (j) {
    return j.id !== jobId;
  });
  renderJobs(currentJobs);
  Toast.info("This job has been hidden from your feed.");
}

// ==================== Comments System ====================

async function loadComments(reset) {
  if (!currentCommentType || !currentCommentEntityId) return;
  if (reset) {
    currentCommentPage = 1;
    currentComments = [];
    hasMoreComments = true;
  }
  if (isLoadingComments || !hasMoreComments) return;
  isLoadingComments = true;
  try {
    var params = new URLSearchParams({
      page: currentCommentPage,
      per_page: 10,
      sort: currentCommentSort,
    });
    var response = await fetch(
      BASE_API_URL +
        "/comments/" +
        currentCommentType +
        "/" +
        currentCommentEntityId +
        "?" +
        params.toString(),
    );
    var data = await response.json();
    if (data.success) {
      var newComments = data.data.data || [];
      currentComments = reset
        ? newComments
        : currentComments.concat(newComments);
      hasMoreComments = newComments.length === 10;
      currentCommentPage++;
      renderComments();
    } else {
      Toast.error(data.message || "Failed to load comments");
    }
  } catch (error) {
    console.error(error);
    Toast.error("Network error loading comments");
  } finally {
    isLoadingComments = false;
  }
}

function renderComments() {
  if (!commentsContainer) return;

  if (currentComments.length === 0) {
    commentsContainer.innerHTML =
      '<div class="comments-empty">' +
      '<i class="fa-regular fa-comments"></i>' +
      "<p>No comments yet</p>" +
      "<span>Be the first to share your thoughts!</span>" +
      "</div>";
    return;
  }

  commentsContainer.innerHTML = currentComments
    .map(function (c) {
      return renderCommentItem(c, 0);
    })
    .join("");

  if (hasMoreComments) {
    var btn = document.createElement("button");
    btn.className = "load-more-comments-btn";
    btn.innerHTML =
      '<i class="fa-solid fa-chevron-down"></i> Load more comments';
    btn.addEventListener("click", function () {
      loadComments(false);
    });
    commentsContainer.appendChild(btn);
  }

  attachCommentEventListeners();
}

function renderCommentItem(comment, level) {
  var isReply = level > 0;
  var likeCount = comment.likes_count || 0;
  var replyCount = comment.replies_count || 0;

  var repliesHtml = "";
  if (comment.replies && comment.replies.length) {
    repliesHtml =
      '<div class="reply-thread">' +
      comment.replies
        .map(function (r) {
          return renderCommentItem(r, level + 1);
        })
        .join("") +
      "</div>";
  }

  var loadMoreBtn = "";
  if (
    comment.has_more_replies &&
    comment.replies &&
    comment.replies.length < comment.replies_count
  ) {
    loadMoreBtn =
      '<button class="load-more-replies-btn" data-comment-id="' +
      comment.id +
      '">' +
      '<i class="fa-solid fa-chevron-down"></i> Load more replies (' +
      (comment.replies_count - comment.replies.length) +
      " more)</button>";
  }

  var replyForm = !isReply
    ? '<div class="reply-form" id="reply-form-' +
      comment.id +
      '" style="display:none;">' +
      '<input type="text" placeholder="Write a reply\u2026" id="reply-input-' +
      comment.id +
      '" maxlength="500">' +
      '<button class="submit-reply" data-parent-id="' +
      comment.id +
      '">Post</button>' +
      "</div>"
    : "";

  return (
    '<div class="comment-item" data-comment-id="' +
    comment.id +
    '">' +
    '<div class="comment-avatar-wrap">' +
    renderAvatar(comment.avatar, comment.display_name, isReply) +
    "</div>" +
    '<div class="comment-content">' +
    '<div class="comment-header-info">' +
    '<span class="comment-username">' +
    escapeHtml(comment.display_name || "Anonymous") +
    "</span>" +
    '<span class="comment-date">' +
    formatDate(comment.created_at) +
    "</span>" +
    "</div>" +
    '<div class="comment-text">' +
    escapeHtml(comment.content) +
    "</div>" +
    '<div class="comment-actions">' +
    '<button class="like-btn" data-comment-id="' +
    comment.id +
    '">' +
    '<i class="fa-' +
    (comment.user_has_liked ? "solid" : "regular") +
    " fa-heart" +
    (comment.user_has_liked ? " active" : "") +
    '"></i>' +
    "<span>" +
    (likeCount > 0 ? likeCount : "") +
    "</span>" +
    "</button>" +
    (!isReply
      ? '<button class="reply-btn" data-comment-id="' +
        comment.id +
        '"><i class="fa-solid fa-reply"></i> Reply</button>'
      : "") +
    (replyCount > 0 && !isReply
      ? '<span class="reply-count"><i class="fa-regular fa-comment"></i> ' +
        replyCount +
        "</span>"
      : "") +
    "</div>" +
    repliesHtml +
    loadMoreBtn +
    replyForm +
    "</div>" +
    "</div>"
  );
}

async function submitComment(content, parentId) {
  if (!currentCommentType || !currentCommentEntityId) return false;
  if (!content.trim()) {
    Toast.error("Comment cannot be empty");
    return false;
  }
  try {
    var response = await fetch(
      BASE_API_URL +
        "/comments/" +
        currentCommentType +
        "/" +
        currentCommentEntityId,
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ content: content, parent_id: parentId || null }),
      },
    );
    var data = await response.json();
    if (data.success) {
      Toast.success(parentId ? "Reply posted!" : "Comment posted!");
      await loadComments(true);
      return true;
    } else {
      Toast.error(data.message || "Failed to post comment");
      return false;
    }
  } catch (_) {
    Toast.error("Network error");
    return false;
  }
}

async function toggleCommentLike(commentId) {
  try {
    var response = await fetch(
      BASE_API_URL + "/comments/" + commentId + "/like",
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
      },
    );
    var data = await response.json();
    if (data.success) {
      (function update(list) {
        for (var i = 0; i < list.length; i++) {
          if (list[i].id === commentId) {
            list[i].user_has_liked = data.data.liked;
            list[i].likes_count = data.data.likes_count;
            return true;
          }
          if (list[i].replies && update(list[i].replies)) return true;
        }
        return false;
      })(currentComments);
      renderComments();
    } else {
      Toast.error(data.message || "Action failed");
    }
  } catch (_) {
    Toast.error("Network error");
  }
}

async function loadMoreReplies(commentId) {
  var comment = currentComments.find(function (c) {
    return c.id === commentId;
  });
  var offset = comment && comment.replies ? comment.replies.length : 0;
  try {
    var response = await fetch(
      BASE_API_URL +
        "/comments/" +
        commentId +
        "/replies?offset=" +
        offset +
        "&limit=5",
    );
    var data = await response.json();
    if (data.success) {
      (function update(list) {
        for (var i = 0; i < list.length; i++) {
          if (list[i].id === commentId) {
            list[i].replies = (list[i].replies || []).concat(data.data);
            list[i].has_more_replies = data.data.length === 5;
            return true;
          }
          if (list[i].replies && update(list[i].replies)) return true;
        }
        return false;
      })(currentComments);
      renderComments();
    } else {
      Toast.error(data.message || "Failed to load replies");
    }
  } catch (_) {
    Toast.error("Network error");
  }
}

function attachCommentEventListeners() {
  // Clone nodes to remove stale listeners before re-attaching
  document
    .querySelectorAll(".comment-actions .like-btn")
    .forEach(function (btn) {
      var fresh = btn.cloneNode(true);
      btn.parentNode.replaceChild(fresh, btn);
    });
  document
    .querySelectorAll(".comment-actions .like-btn")
    .forEach(function (btn) {
      btn.addEventListener("click", function (e) {
        toggleCommentLike(parseInt(e.currentTarget.dataset.commentId));
      });
    });

  document
    .querySelectorAll(".comment-actions .reply-btn")
    .forEach(function (btn) {
      btn.addEventListener("click", function (e) {
        var id = parseInt(e.currentTarget.dataset.commentId);
        var form = document.getElementById("reply-form-" + id);
        if (!form) return;
        var open = form.style.display === "flex";
        form.style.display = open ? "none" : "flex";
        if (!open) {
          var inp = document.getElementById("reply-input-" + id);
          if (inp) inp.focus();
        }
      });
    });

  document.querySelectorAll(".load-more-replies-btn").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      loadMoreReplies(parseInt(e.currentTarget.dataset.commentId));
    });
  });

  document.querySelectorAll(".submit-reply").forEach(function (btn) {
    btn.addEventListener("click", async function (e) {
      var parentId = parseInt(e.currentTarget.dataset.parentId);
      var input = document.getElementById("reply-input-" + parentId);
      var content = input ? input.value.trim() : "";
      if (!content) {
        Toast.error("Reply cannot be empty");
        return;
      }
      var ok = await submitComment(content, parentId);
      if (ok) {
        var form = document.getElementById("reply-form-" + parentId);
        if (form) form.style.display = "none";
        if (input) input.value = "";
      }
    });
  });
}

function openComments(job) {
  currentCommentType = "job";
  currentCommentEntityId = job.id;
  if (commentHeaderTitle) {
    commentHeaderTitle.textContent =
      "Comments (" + (job.comments_count || 0) + ")";
  }
  loadComments(true);
  if (commentSection) {
    commentSection.classList.add("open");
    document.body.classList.add("comments-sidebar-open");
    if (window.innerWidth <= 768) document.body.style.overflow = "hidden";
  }
}

function closeComments() {
  if (commentSection) {
    commentSection.classList.remove("open");
    document.body.classList.remove("comments-sidebar-open");
    document.body.style.overflow = "";
  }
  currentCommentType = null;
  currentCommentEntityId = null;
  currentComments = [];
}

// Tap the dim overlay on mobile to close
document.addEventListener("click", function (e) {
  if (
    document.body.classList.contains("comments-sidebar-open") &&
    window.innerWidth <= 768 &&
    !e.target.closest(".comment-section") &&
    !e.target.closest(".comment-btn")
  ) {
    closeComments();
  }
});

// ==================== Render Jobs ====================

function renderJobs(jobs) {
  if (!jobs || jobs.length === 0) {
    jobsGrid.innerHTML =
      '<div class="alert alert-info">No jobs found. Try adjusting your search.</div>';
    return;
  }

  jobsGrid.innerHTML = jobs
    .map(function (job) {
      var needsCv = job.requires_resume == 1 || job.requires_resume === true;

      // CV pill shown inside the card header area (not absolute-positioned)
      var cvPill = needsCv
        ? '<span class="cv-pill cv-pill--required"><i class="fa-solid fa-file-lines"></i> CV Required</span>'
        : '<span class="cv-pill cv-pill--open"><i class="fa-solid fa-circle-check"></i> No CV Needed</span>';

      var logoHtml = job.avatar
        ? '<img src="' +
          escapeHtml(job.avatar) +
          '" alt="' +
          escapeHtml(job.company_name) +
          '">'
        : '<i class="fas fa-building"></i>';

      var jobTypeFmt = (job.job_type || "").replace(/_/g, " ");

      return (
        '<div class="job-card" data-job-id="' +
        job.id +
        '" style="cursor: pointer;">' +
        // ── 3-dot menu (top-right, absolutely positioned, safe zone)
        '<button class="menu-dots" data-job-id="' +
        job.id +
        '" aria-label="More options"><i class="fas fa-ellipsis-v"></i></button>' +
        '<div class="dropdown-menu" id="dropdown-' +
        job.id +
        '">' +
        '<a href="#" class="stop-seeing" data-job-id="' +
        job.id +
        '"><i class="fa-solid fa-eye-slash"></i> Stop seeing this</a>' +
        '<a href="#" class="report-job" data-job-id="' +
        job.id +
        '" style="color:var(--danger-color)"><i class="fa-solid fa-flag"></i> Report</a>' +
        '<a href="#" class="company-info" data-job-id="' +
        job.id +
        '"><i class="fa-solid fa-circle-info"></i> Company info</a>' +
        "</div>" +
        // ── Company header row
        '<div class="company-header">' +
        '<div class="company-logo">' +
        logoHtml +
        "</div>" +
        '<div class="company-info-block">' +
        "<h3>" +
        escapeHtml(job.company_name) +
        "</h3>" +
        "<p>" +
        escapeHtml(jobTypeFmt) +
        " &bull; " +
        (job.is_remote ? "Remote" : "On-site") +
        " &bull; " +
        escapeHtml(job.experience_level || "N/A") +
        "</p>" +
        "</div>" +
        "</div>" +
        // ── Job title + CV pill on same line
        '<div class="job-title-row">' +
        '<div class="job-title">' +
        escapeHtml(job.title) +
        "</div>" +
        cvPill +
        "</div>" +
        // ── Meta (dates, salary, applicants)
        '<div class="job-meta">' +
        '<span><i class="far fa-calendar-alt"></i> ' +
        formatDate(job.posted_at || job.created_at) +
        "</span>" +
        '<span><i class="far fa-clock"></i> ' +
        formatDeadline(job.expires_at) +
        "</span>" +
        '<span><i class="fas fa-coins"></i> ' +
        escapeHtml(formatSalary(job)) +
        "</span>" +
        '<span><i class="fas fa-users"></i> ' +
        (job.applications_count || 0) +
        " applicants</span>" +
        "</div>" +
        // ── Description
        '<div class="job-description">' +
        escapeHtml(job.description || "No description provided.") +
        "</div>" +
        // ── Requirements
        '<div class="requirements toggle-req"><strong>Requirements:</strong><span>view more</span> ' +
        job.requirements_text +
        "</div>" +
        // ── Responsibilities (only if present)
        (job.responsibilities_text !== "Not specified"
          ? '<div class="requirements toggle-req" style="margin-top:0"><strong>Responsibilities:</strong> <span>view more</span> ' +
            job.responsibilities_text +
            "</div>"
          : "") +
        // ── Apply button
        '<button class="apply-btn' +
        (needsCv ? " apply-btn--cv" : "") +
        '" data-job-id="' +
        job.id +
        '">' +
        '<i class="fas ' +
        (needsCv ? "fa-file-lines" : "fa-paper-plane") +
        '"></i> ' +
        (needsCv ? "Apply &mdash; CV Required" : "Apply Now") +
        "</button>" +
        // ── Action row
        '<div class="action-row">' +
        '<button class="action-icon like-btn" data-job-id="' +
        job.id +
        '" aria-label="Like">' +
        '<i class="fas fa-heart' +
        (job.is_liked ? " active" : "") +
        '"></i><span>' +
        (job.likes_count || 0) +
        "</span>" +
        "</button>" +
        '<button class="action-icon save-btn" data-job-id="' +
        job.id +
        '" aria-label="Save">' +
        '<i class="fas fa-bookmark' +
        (job.is_saved ? " active" : "") +
        '"></i><span>' +
        (job.saves_count || 0) +
        "</span>" +
        "</button>" +
        '<button class="action-icon comment-btn" data-job-id="' +
        job.id +
        '" aria-label="Comments">' +
        '<i class="fas fa-comment"></i><span>' +
        (job.comments_count || 0) +
        "</span>" +
        "</button>" +
        '<button class="action-icon share-btn" data-job-id="' +
        job.id +
        '" aria-label="Share">' +
        '<i class="fas fa-share-alt"></i><span>Share</span>' +
        "</button>" +
        "</div>" +
        "</div>"
      );
    })
    .join("");

  attachJobEventListeners();
}

function attachJobEventListeners() {
  document.querySelectorAll(".like-btn[data-job-id]").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      e.preventDefault();
      toggleLike(parseInt(btn.dataset.jobId));
    });
  });
  document.querySelectorAll(".save-btn").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      e.preventDefault();
      toggleSave(parseInt(btn.dataset.jobId));
    });
  });
  document.querySelectorAll(".apply-btn").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      e.preventDefault();
      openApplyModal(parseInt(btn.dataset.jobId));
    });
  });
  document.querySelectorAll(".comment-btn").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      e.preventDefault();
      var job = currentJobs.find(function (j) {
        return j.id === parseInt(btn.dataset.jobId);
      });
      if (job) openComments(job);
    });
  });
  document.querySelectorAll(".share-btn").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      e.preventDefault();
      e.stopPropagation();
      var job = currentJobs.find(function (j) {
        return j.id === parseInt(btn.dataset.jobId);
      });
      if (job) shareJob(job);
    });
  });

  document.querySelectorAll(".menu-dots").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      e.preventDefault();
      var dd = document.getElementById("dropdown-" + btn.dataset.jobId);
      document.querySelectorAll(".dropdown-menu.show").forEach(function (d) {
        if (d !== dd) d.classList.remove("show");
      });
      dd.classList.toggle("show");
    });
  });
  document.querySelectorAll(".stop-seeing").forEach(function (link) {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var id = parseInt(link.dataset.jobId);
      stopSeeingJob(id);
      var dd = document.getElementById("dropdown-" + id);
      if (dd) dd.classList.remove("show");
    });
  });
  document.querySelectorAll(".report-job").forEach(function (link) {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var id = parseInt(link.dataset.jobId);
      reportJob(id);
      var dd = document.getElementById("dropdown-" + id);
      if (dd) dd.classList.remove("show");
    });
  });
  document.querySelectorAll(".company-info").forEach(function (link) {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var id = parseInt(link.dataset.jobId);
      var job = currentJobs.find(function (j) {
        return j.id === id;
      });
      if (job) viewCompanyInfo(job);
      var dd = document.getElementById("dropdown-" + id);
      if (dd) dd.classList.remove("show");
    });
  });

  document.addEventListener("click", function (e) {
    if (
      !e.target.closest(".menu-dots") &&
      !e.target.closest(".dropdown-menu")
    ) {
      document.querySelectorAll(".dropdown-menu.show").forEach(function (d) {
        d.classList.remove("show");
      });
    }
  });
  document.querySelectorAll(".toggle-req").forEach(function (el) {
    el.addEventListener("click", function () {
      el.classList.toggle("view");
      el.querySelector("span").textContent = el.classList.contains("view")
        ? "view less"
        : "view more";
    });
  });

  // Handle job card navigation - click card to view details
  document.querySelectorAll(".job-card").forEach(function (card) {
    card.addEventListener("click", function (e) {
      // Check if click target is NOT a button or interactive element
      var isButton =
        e.target.closest("button") ||
        e.target.closest(".menu-dots") ||
        e.target.closest(".comment-btn") ||
        e.target.closest(".like-btn") ||
        e.target.closest(".save-btn") ||
        e.target.closest(".share-btn") ||
        e.target.closest(".apply-btn") ||
        e.target.closest(".dropdown-menu") ||
        e.target.closest("a[href='#']");

      // If clicking on interactive element, let those handlers take over
      if (!isButton) {
        // Navigate to job details page
        var jobId = card.getAttribute("data-job-id");
        if (jobId) {
          window.location.href = "/employer/jobs/view/" + jobId;
        }
      }
    });
  });
}

function showLoadingPlaceholders(count) {
  var html = "";
  for (var i = 0; i < count; i++) {
    html +=
      '<div class="job-card glossy-placeholder">' +
      '<div class="company-header"><div class="company-logo"></div>' +
      '<div class="company-info-block"><h3>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</h3><p>&nbsp;</p></div></div>' +
      '<div class="job-title-row"><div class="job-title">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div></div>' +
      '<div class="job-meta">&nbsp;</div>' +
      '<div class="job-description">&nbsp;</div>' +
      '<div class="requirements">&nbsp;</div>' +
      '<div class="apply-btn">&nbsp;</div>' +
      '<div class="action-row">&nbsp;</div>' +
      "</div>";
  }
  jobsGrid.innerHTML = html;
}

// ==================== Search ====================

async function searchJobs(query) {
  if (!query || query.length < 2) {
    fetchJobs();
    return;
  }
  try {
    isLoading = true;
    showLoadingPlaceholders(3);
    var params = new URLSearchParams({ q: query, page: 1, per_page: 20 });
    var response = await fetch(
      BASE_API_URL + "/jobs/search?" + params.toString(),
    );
    if (!response.ok) {
      if (response.status === 422) {
        var err = await response.json();
        Toast.error(
          err.message || "Search query must be at least 2 characters",
        );
        fetchJobs();
        return;
      }
      throw new Error("Search failed");
    }
    var data = await response.json();
    if (data.success) {
      currentJobs = (data.data.data || []).map(function (job) {
        return Object.assign({}, job, {
          is_liked: false,
          is_saved: false,
          owner_id: getJobOwnerId(job),
          requirements_text: formatList(job.requirements),
          responsibilities_text: formatList(job.responsibilities),
        });
      });
      await fetchInitialLikeStatuses();
      await fetchInitialSaveStatuses();
      renderJobs(currentJobs);
      if (currentJobs.length === 0) Toast.info("No jobs found for that search");
    } else {
      Toast.error(data.message || "Search failed");
    }
  } catch (error) {
    console.error(error);
    Toast.error("Search error. Please try again.");
  } finally {
    isLoading = false;
  }
}

function onSearchInput(e) {
  var query = e.target.value.trim();
  clearTimeout(searchTimeout);
  if (query.length >= 2) {
    searchTimeout = setTimeout(function () {
      searchJobs(query);
    }, 300);
  } else if (query.length === 0) {
    searchTimeout = setTimeout(function () {
      fetchJobs();
    }, 300);
  }
}

// ==================== Init ====================

async function init() {
  await fetchCurrentUser();
  fetchJobs();

  if (searchInput) {
    searchInput.addEventListener("input", onSearchInput);
  }

  var closeBtn = document.getElementById("closeCommentBtn");
  if (closeBtn) closeBtn.addEventListener("click", closeComments);

  if (addCommentForm) {
    addCommentForm.addEventListener("submit", async function (e) {
      e.preventDefault();
      var content = commentInput ? commentInput.value.trim() : "";
      if (!content) return;
      var btn = addCommentForm.querySelector("button[type=submit]");
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = "Posting\u2026";
      }
      var ok = await submitComment(content, null);
      if (ok && commentInput) commentInput.value = "";
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Post';
      }
    });
  }

  if (commentSortSelect) {
    commentSortSelect.addEventListener("change", function (e) {
      currentCommentSort = e.target.value;
      loadComments(true);
    });
  }
}

init();

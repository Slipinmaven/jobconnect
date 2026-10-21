// ===== JOBS DASHBOARD JAVASCRIPT =====
(function () {
  const API_BASE_URL = "/api";

  let jobs = [];
  let currentFilter = "all";
  let currentSort = "latest";
  let activeDropdown = null;
  let isLoading = true;

  // ---------- Utility Functions ----------
  function parseUtcFromDb(datetimeString) {
    if (!datetimeString) return null;
    let normalized = datetimeString;

    if (!normalized.includes("T") && !normalized.includes("Z")) {
      normalized = normalized.replace(" ", "T") + "Z";
    }

    const parsed = new Date(normalized);
    if (isNaN(parsed.getTime())) return null;
    return parsed;
  }

  function timeAgo(dateString) {
    if (!dateString) return "Recently";

    const utcDate = parseUtcFromDb(dateString);
    if (!utcDate) return "Recently";

    // Use shared util that handles both past/future and auto update via data attribute
    return utcToRelativeTime(utcDate.toISOString());
  }

  function daysUntilExpiry(expiryDate) {
    if (!expiryDate) return 999;

    const expiry = parseUtcFromDb(expiryDate);
    if (!expiry) return 999;

    const now = new Date();
    return Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
  }

  function getExpiryText(expiryDate) {
    if (!expiryDate) return "No expiry";

    const utcExpiry = parseUtcFromDb(expiryDate);
    if (!utcExpiry) return "Invalid expiry";

    // use main.js utcToRelativeTime for these values
    return utcToRelativeTime(utcExpiry.toISOString());
  }

  function getExpiryClass(expiryDate) {
    if (!expiryDate) return "";
    const days = daysUntilExpiry(expiryDate);
    if (days < 0) return "expiry-expired";
    if (days <= 7) return "expiry-warning";
    return "";
  }

  function formatSalary(min, max, currency) {
    if (!min && !max) return "Competitive";
    if (min && max)
      return `${currency} ${min.toLocaleString()} - ${max.toLocaleString()}`;
    if (min) return `${currency} ${min.toLocaleString()}+`;
    if (max) return `${currency} ${max.toLocaleString()}`;
    return "Competitive";
  }

  function getApplyRate(views, applications) {
    if (views === 0) return "0%";
    return ((applications / views) * 100).toFixed(0) + "%";
  }

  function getJobTypeDisplay(type) {
    const types = {
      full_time: "Full Time",
      part_time: "Part Time",
      contract: "Contract",
      internship: "Internship",
      temporary: "Temporary",
    };
    return types[type] || type;
  }

  // Improved location display – uses is_remote and falls back to "Location TBD"
  function getLocationDisplay(job) {
    if (job.is_remote) return "Remote";
    // If you have a city field, use it; otherwise fallback
    if (job.city) return job.city;
    return "Location TBD";
  }

  // ---------- API Calls ----------
  async function fetchJobs() {
    isLoading = true;
    showLoadingSkeleton(); // optional – we'll implement a simple one
    try {
      const response = await fetch(`${API_BASE_URL}/employer/jobs`);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const result = await response.json();

      if (result.success && result.data && result.data.data) {
        jobs = result.data.data;
      } else {
        jobs = [];
      }
      updateStats();
    } catch (error) {
      console.error("Failed to fetch jobs:", error);
      Toast.error(
        "Could not load your jobs. Please refresh the page.",
        "error",
      );
      jobs = [];
      renderEmptyState();
    } finally {
      isLoading = false;
    }
  }

  // Optional loading skeleton (you can style it as you like)
  function showLoadingSkeleton() {
    const grid = document.getElementById("jobsGrid");
    if (grid && !grid.innerHTML.trim()) {
      grid.innerHTML = `
        <div class="loading-skeleton">
          <div class="skeleton-card"></div>
          <div class="skeleton-card"></div>
          <div class="skeleton-card"></div>
        </div>
      `;
    }
  }

  // Stats update (only after jobs are loaded)
  function updateStats() {
    const total = jobs.length;
    const totalViews = jobs.reduce((sum, j) => sum + (j.views_count || 0), 0);
    const totalApps = jobs.reduce(
      (sum, j) => sum + (j.applications_count || 0),
      0,
    );
    const applyRate =
      totalViews > 0 ? ((totalApps / totalViews) * 100).toFixed(0) : 0;

    const totalJobsEl = document.getElementById("totalJobs");
    const totalViewsEl = document.getElementById("totalViews");
    const totalAppsEl = document.getElementById("totalApps");
    const applyRateEl = document.getElementById("applyRate");

    if (totalJobsEl) totalJobsEl.textContent = total.toLocaleString();
    if (totalViewsEl) totalViewsEl.textContent = totalViews.toLocaleString();
    if (totalAppsEl) totalAppsEl.textContent = totalApps.toLocaleString();
    if (applyRateEl) applyRateEl.textContent = `${applyRate}%`;
  }

  // Actions that modify server data
  async function markAsClosed(jobId) {
    try {
      const response = await fetch(
        `${API_BASE_URL}/employer/jobs/${jobId}/close`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')
              ?.content,
          },
        },
      );
      const data = await response.json();
      if (data.success) {
        await fetchJobs(); // refresh list
        if (activeDropdown) activeDropdown.classList.remove("active");
        const job = jobs.find((j) => j.id === jobId);
        Toast.success(data.message);
        renderJobs();
      } else {
        Toast.error(data.message || "Failed to close job");
      }
    } catch (error) {
      console.error("Error:", error);
      Toast.error("An error occurred");
    }
  }

  async function deleteJob(jobId) {
    Confirm.error({
      heading: "Delete Job",
      message:
        "Are you sure you want to delete this job? This action cannot be undone.",
      confirmText: "Delete",
      cancelText: "Cancel",
      onConfirm: async () => {
        try {
          const response = await fetch(`${API_BASE_URL}/jobs/${jobId}`, {
            method: "DELETE",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')
                ?.content,
            },
          });
          const data = await response.json();
          if (data.success) {
            await fetchJobs().then(() => renderJobs()); // refresh list
            if (activeDropdown) activeDropdown.classList.remove("active");
            Toast.success("Job has been deleted");
          } else {
            Toast.error(data.message || "Failed to delete job");
          }
        } catch (error) {
          console.error("Error:", error);
          Toast.error("An error occurred");
        }
      },
      onCancel: () => {
        // Do nothing – modal closes automatically
      },
    });
  }

  function editJob(jobId) {
    window.location.href = `/employer/jobs/edit/${jobId}`;
  }

  function viewMoreDetails(jobId) {
    window.location.href = `/employer/jobs/view/${jobId}`;
  }

  function createNewJob() {
    window.location.href = "/employer/jobs/create";
  }

  // ---------- Dropdown Handling ----------
  function toggleDropdown(event, jobId) {
    event.stopPropagation();
    if (activeDropdown) activeDropdown.classList.remove("active");
    const dropdown = document.getElementById(`dropdown-${jobId}`);
    if (dropdown) {
      dropdown.classList.toggle("active");
      activeDropdown = dropdown;
    }
  }

  document.addEventListener("click", function (e) {
    if (
      activeDropdown &&
      !activeDropdown.contains(e.target) &&
      !e.target.closest(".menu-dots")
    ) {
      activeDropdown.classList.remove("active");
      activeDropdown = null;
    }
  });

  // ---------- Rendering ----------
  function renderEmptyState() {
    const grid = document.getElementById("jobsGrid");
    if (!grid) return;

    grid.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-icon">
          <i class="fas fa-briefcase"></i>
        </div>
        <h3>No jobs posted yet</h3>
        <p>Your job listings will appear here. Start by posting your first job opportunity.</p>
        <button class="post-job-btn" id="postJobBtn">Post Your First Job</button>
      </div>
    `;
    const postBtn = document.getElementById("postJobBtn");
    if (postBtn) postBtn.addEventListener("click", createNewJob);
  }

  function renderJobs() {
    if (isLoading) return;

    let filtered = jobs.filter(
      (j) => currentFilter === "all" || j.status === currentFilter,
    );

    if (jobs.length === 0) {
      renderEmptyState();
      return;
    }

    if (filtered.length === 0) {
      const grid = document.getElementById("jobsGrid");
      if (grid) {
        grid.innerHTML = `
          <div class="no-results-state">
            <i class="fas fa-filter"></i>
            <h4>No jobs match your filter</h4>
            <p>Try changing your filter selection to see more jobs.</p>
            <button class="reset-filter-btn" onclick="document.querySelector('.filter-chip[data-status=\"all\"]').click()">
              <i class="fas fa-undo"></i> Show All Jobs
            </button>
          </div>
        `;
      }
      return;
    }

    // Sorting
    if (currentSort === "latest") {
      filtered.sort(
        (a, b) =>
          new Date(b.created_at.replace(" ", "T")) -
          new Date(a.created_at.replace(" ", "T")),
      );
    } else if (currentSort === "most-viewed") {
      filtered.sort((a, b) => (b.views_count || 0) - (a.views_count || 0));
    } else if (currentSort === "most-applied") {
      filtered.sort(
        (a, b) => (b.applications_count || 0) - (a.applications_count || 0),
      );
    } else if (currentSort === "expiring-soon") {
      filtered.sort(
        (a, b) => daysUntilExpiry(a.expires_at) - daysUntilExpiry(b.expires_at),
      );
    }

    const grid = document.getElementById("jobsGrid");
    if (!grid) return;

    grid.innerHTML = filtered
      .map((job) => {
        const salaryDisplay = formatSalary(
          job.salary_min,
          job.salary_max,
          job.currency || "KES",
        );
        const applyRate = getApplyRate(
          job.views_count || 0,
          job.applications_count || 0,
        );
        const expiryText = getExpiryText(job.expires_at);
        const expiryClass = getExpiryClass(job.expires_at);
        const location = getLocationDisplay(job);

        return `
          <div class="job-card" data-id="${job.id}">
            <div class="card-header">
              <span class="status-badge status-${job.status}">
                <i class="fas ${job.status === "published" ? "fa-circle" : job.status === "draft" ? "fa-pen" : "fa-ban"}"></i>
                ${job.status.charAt(0).toUpperCase() + job.status.slice(1)}
              </span>
              <button class="menu-dots" onclick="window.toggleDropdown(event, ${job.id})">
                <i class="fas fa-ellipsis-vertical"></i>
              </button>
              <div class="dropdown-menu" id="dropdown-${job.id}">
                <div class="dropdown-item close-job" onclick="window.markAsClosed(${job.id})">
                  <i class="fas ${job.status === "closed" ? "fa-play" : "fa-pause-circle"}"></i>
                  ${job.status === "closed" ? "Reopen" : "Mark as Closed"}
                </div>
                <div class="dropdown-item edit-job" onclick="window.editJob(${job.id})">
                  <i class="fas fa-edit"></i> Edit Job
                </div>
                <div class="dropdown-item delete-job" onclick="window.deleteJob(${job.id})">
                  <i class="fas fa-trash-alt"></i> Delete Job
                </div>
              </div>
            </div>
            <div class="job-title">
              <h3>${escapeHtml(job.title)}</h3>
              <span class="location-tag"><i class="fas fa-map-marker-alt"></i> ${location}</span>
            </div>
            <div class="job-meta">
              <span class="meta-item"><i class="fas fa-briefcase"></i> ${getJobTypeDisplay(job.job_type)}</span>
              <span class="meta-item"><i class="fas fa-chart-line"></i> ${job.experience_level ? job.experience_level.charAt(0).toUpperCase() + job.experience_level.slice(1) : "Entry"}</span>
            </div>
            <div class="salary-section">
              <div class="salary-value">${salaryDisplay}</div>
              <div class="salary-label">Salary Range</div>
            </div>
            <div class="stats-row">
              <div class="stat-bubble">
                <div class="stat-number">${(job.views_count || 0).toLocaleString()}</div>
                <div class="stat-label-small">Views</div>
              </div>
              <div class="stat-bubble">
                <div class="stat-number">${job.applications_count || 0}</div>
                <div class="stat-label-small">Apps</div>
              </div>
              <div class="apply-rate-bubble">
                <div class="apply-rate-value">${applyRate}</div>
                <div class="stat-label-small">Apply Rate</div>
              </div>
            </div>
            <div class="date-section">
              <span><i class="far fa-calendar-alt"></i> <span class="relative-time" data-utc-timestamp="${job.created_at}">${timeAgo(job.created_at)}</span></span>
              <span class="${expiryClass} ${job.expires_at ? "relative-time" : ""}" ${job.expires_at ? `data-utc-timestamp="${job.expires_at}"` : ""}><i class="far fa-hourglass-half"></i> ${expiryText}</span>
            </div>
            <button class="view-more-btn" onclick="window.viewMoreDetails(${job.id})">
              <i class="fas fa-arrow-right"></i> View Full Details
            </button>
          </div>
        `;
      })
      .join("");
  }

  function escapeHtml(str) {
    if (!str) return "";
    return str.replace(/[&<>]/g, function (m) {
      if (m === "&") return "&amp;";
      if (m === "<") return "&lt;";
      if (m === ">") return "&gt;";
      return m;
    });
  }

  // ---------- Event Listeners ----------
  document.querySelectorAll(".filter-chip").forEach((chip) => {
    chip.addEventListener("click", () => {
      document
        .querySelectorAll(".filter-chip")
        .forEach((c) => c.classList.remove("active"));
      chip.classList.add("active");
      currentFilter = chip.dataset.status;
      renderJobs();
    });
  });

  const sortSelect = document.getElementById("sortSelect");
  if (sortSelect) {
    sortSelect.addEventListener("change", (e) => {
      currentSort = e.target.value;
      renderJobs();
    });
  }

  const postJobBtn = document.getElementById("postJobBtn");
  if (postJobBtn) postJobBtn.addEventListener("click", createNewJob);

  const mobileToggle = document.getElementById("mobileMenuToggle");
  if (mobileToggle) {
    mobileToggle.addEventListener("click", () => {
      const sidebar = document.querySelector(".employer-sidebar");
      if (sidebar) sidebar.classList.toggle("active");
    });
  }

  // Make functions global for onclick handlers
  window.toggleDropdown = toggleDropdown;
  window.markAsClosed = markAsClosed;
  window.editJob = editJob;
  window.deleteJob = deleteJob;
  window.viewMoreDetails = viewMoreDetails;
  window.createNewJob = createNewJob;

  document.addEventListener("DOMContentLoaded", () => {
    fetchJobs().then(() => renderJobs());
  });
  // Initial load
})();

/**
 * Notifications Page JS
 * Uses: Toast, Confirm, utcToRelativeTime (from main.js)
 */

(function () {
  "use strict";

  // ─── State ───────────────────────────────────────────────────────────────
  let allNotifications = [];
  let currentFilter = "all";
  let selectedIds = new Set();
  let currentPage = 1;
  let totalPages = 1;
  const PER_PAGE = 20;

  // ─── Type config (icon + label) ─────────────────────────────────────────
  const TYPE_CONFIG = {
    interview: {
      icon: "fa-calendar-check",
      label: "Interview",
    },
    job_application: {
      icon: "fa-file-alt",
      label: "Application",
    },
    job: {
      icon: "fa-briefcase",
      label: "Job",
    },
    comment: {
      icon: "fa-comment",
      label: "Comment",
    },
    like: {
      icon: "fa-heart",
      label: "Like",
    },
    system: {
      icon: "fa-shield-halved",
      label: "System",
    },
  };

  // Title → friendly status keyword extraction
  const STATUS_MAP = {
    "interview scheduled": {
      key: "scheduled",
      label: "Scheduled",
      icon: "fa-calendar-plus",
    },
    "interview rescheduled": {
      key: "rescheduled",
      label: "Rescheduled",
      icon: "fa-calendar-arrow-right",
    },
    "interview cancelled": {
      key: "cancelled",
      label: "Cancelled",
      icon: "fa-calendar-xmark",
    },
    "interview completed": {
      key: "completed",
      label: "Completed",
      icon: "fa-calendar-check",
    },
    "application accepted": {
      key: "accepted",
      label: "Accepted",
      icon: "fa-circle-check",
    },
    "application shortlisted": {
      key: "shortlisted",
      label: "Shortlisted",
      icon: "fa-star",
    },
    "application reviewed": {
      key: "reviewed",
      label: "Under Review",
      icon: "fa-magnifying-glass",
    },
    "application not selected": {
      key: "not_selected",
      label: "Not Selected",
      icon: "fa-circle-xmark",
    },
    "application rejected": {
      key: "rejected",
      label: "Not Selected",
      icon: "fa-circle-xmark",
    },
    "application withdrawn": {
      key: "reviewed",
      label: "Withdrawn",
      icon: "fa-arrow-left",
    },
  };

  // ─── DOM refs ────────────────────────────────────────────────────────────
  const $groups = document.getElementById("notifGroups");
  const $loading = document.getElementById("notifLoading");
  const $empty = document.getElementById("notifEmpty");
  const $unreadBadge = document.getElementById("unreadBadge");
  const $bulkBar = document.getElementById("bulkBar");
  const $selectedCount = document.getElementById("selectedCount");
  const $loadMoreContainer = document.getElementById("loadMoreContainer");
  const $loadMoreBtn = document.getElementById("loadMoreBtn");

  // ─── Init ────────────────────────────────────────────────────────────────
  document.addEventListener("DOMContentLoaded", () => {
    fetchNotifications();
    bindFilterTabs();
    bindHeaderActions();
    bindBulkActions();
    bindLoadMore();
  });

  // ─── API ─────────────────────────────────────────────────────────────────
  async function fetchNotifications(page = 1, append = false) {
    if (page === 1) showLoading(true);

    try {
      const res = await fetch(
        `/api/notifications?page=${page}&per_page=${PER_PAGE}`,
        {
          headers: { "X-Requested-With": "XMLHttpRequest" },
        },
      );
      const json = await res.json();

      if (!json.success) throw new Error(json.message || "Failed to load");

      const data = json.data;
      const newItems = data.data || [];
      totalPages = data.total_pages || 1;
      currentPage = data.page || 1;

      if (append) {
        allNotifications = [...allNotifications, ...newItems];
      } else {
        allNotifications = newItems;
      }

      showLoading(false);
      renderAll();

      // Load more button
      if (currentPage < totalPages) {
        $loadMoreContainer.style.display = "block";
      } else {
        $loadMoreContainer.style.display = "none";
      }
    } catch (err) {
      showLoading(false);
      Toast.error("Could not load notifications. Please try again.");
      console.error(err);
    }
  }

  async function markRead(id) {
    try {
      const res = await fetch(`/api/notifications/${id}/read`, {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      const json = await res.json();
      if (!json.success) throw new Error();

      const n = allNotifications.find((x) => x.id === id);
      if (n) {
        n.is_read = 1;
        n.read_at = new Date().toISOString();
      }
      renderAll();
    } catch {
      Toast.error("Could not mark as read.");
    }
  }

  async function markAllRead() {
    try {
      const res = await fetch(`/api/notifications/read-all`, {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      const json = await res.json();
      if (!json.success) throw new Error();

      allNotifications.forEach((n) => {
        n.is_read = 1;
        n.read_at = new Date().toISOString();
      });
      renderAll();
      Toast.success("All notifications marked as read.");
    } catch {
      Toast.error("Could not mark all as read.");
    }
  }

  async function deleteNotification(id) {
    try {
      const res = await fetch(`/api/notifications/${id}`, {
        method: "DELETE",
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      const json = await res.json();
      if (!json.success) throw new Error();

      allNotifications = allNotifications.filter((x) => x.id !== id);
      selectedIds.delete(id);
      renderAll();
      updateBulkBar();
      Toast.success("Notification deleted.");
    } catch {
      Toast.error("Could not delete notification.");
    }
  }

  async function bulkMarkRead(ids) {
    const promises = ids.map((id) =>
      fetch(`/api/notifications/${id}/read`, {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
      }),
    );
    await Promise.allSettled(promises);
    ids.forEach((id) => {
      const n = allNotifications.find((x) => x.id === id);
      if (n) {
        n.is_read = 1;
        n.read_at = new Date().toISOString();
      }
    });
    selectedIds.clear();
    renderAll();
    updateBulkBar();
    Toast.success(
      `${ids.length} notification${ids.length > 1 ? "s" : ""} marked as read.`,
    );
  }

  async function bulkDelete(ids) {
    Confirm.warning({
      heading: "Delete Notifications",
      message: `Delete ${ids.length} notification${ids.length > 1 ? "s" : ""}?`,
      details: "This cannot be undone.",
      confirmText: "Delete",
      cancelText: "Cancel",
      onConfirm: async () => {
        const promises = ids.map((id) =>
          fetch(`/api/notifications/${id}`, {
            method: "DELETE",
            headers: { "X-Requested-With": "XMLHttpRequest" },
          }),
        );
        await Promise.allSettled(promises);
        allNotifications = allNotifications.filter((x) => !ids.includes(x.id));
        ids.forEach((id) => selectedIds.delete(id));
        renderAll();
        updateBulkBar();
        Toast.success(
          `${ids.length} notification${ids.length > 1 ? "s" : ""} deleted.`,
        );
      },
    });
  }

  // ─── Render ───────────────────────────────────────────────────────────────
  function getFilteredNotifications() {
    if (currentFilter === "all") return allNotifications;
    if (currentFilter === "unread")
      return allNotifications.filter((n) => !n.is_read);
    return allNotifications.filter((n) => n.type === currentFilter);
  }

  function renderAll() {
    updateCounts();
    updateBadge();
    renderNotifications();
  }

  function updateCounts() {
    document.getElementById("countAll").textContent = allNotifications.length;
    document.getElementById("countUnread").textContent =
      allNotifications.filter((n) => !n.is_read).length;
    document.getElementById("countInterview").textContent =
      allNotifications.filter((n) => n.type === "interview").length;
    document.getElementById("countApplication").textContent =
      allNotifications.filter((n) => n.type === "job_application").length;
    document.getElementById("countJob").textContent = allNotifications.filter(
      (n) => n.type === "job",
    ).length;
    document.getElementById("countSystem").textContent =
      allNotifications.filter((n) => n.type === "system").length;
  }

  function updateBadge() {
    const unread = allNotifications.filter((n) => !n.is_read).length;
    $unreadBadge.textContent = unread > 0 ? unread : "";
  }

  function renderNotifications() {
    const filtered = getFilteredNotifications();
    $groups.innerHTML = "";

    if (filtered.length === 0) {
      $empty.style.display = "block";
      return;
    }
    $empty.style.display = "none";

    // Group by time bucket
    const groups = groupByTime(filtered);

    for (const [label, items] of Object.entries(groups)) {
      if (!items.length) continue;

      const groupEl = document.createElement("div");
      groupEl.className = "notif-group";

      const labelEl = document.createElement("div");
      labelEl.className = "notif-group-label";
      labelEl.textContent = label;
      groupEl.appendChild(labelEl);

      items.forEach((n) => {
        groupEl.appendChild(buildNotifItem(n));
      });

      $groups.appendChild(groupEl);
    }
  }

  function groupByTime(items) {
    const now = new Date();
    const groups = {
      Today: [],
      Yesterday: [],
      "This Week": [],
      "This Month": [],
      Earlier: [],
    };

    items.forEach((n) => {
      const date = new Date(
        (n.created_at || "").includes("T") ? n.created_at : n.created_at + "Z",
      );
      const diffDays = Math.floor((now - date) / 86400000);

      if (diffDays === 0) groups["Today"].push(n);
      else if (diffDays === 1) groups["Yesterday"].push(n);
      else if (diffDays < 7) groups["This Week"].push(n);
      else if (diffDays < 30) groups["This Month"].push(n);
      else groups["Earlier"].push(n);
    });

    return groups;
  }

  function buildNotifItem(n) {
    const wrapper = document.createElement("div");
    wrapper.className = `notif-item${n.is_read ? "" : " unread"}${selectedIds.has(n.id) ? " selected" : ""}`;
    wrapper.dataset.id = n.id;

    const typeConfig = TYPE_CONFIG[n.type] || TYPE_CONFIG["system"];
    const status = getStatus(n.title);

    wrapper.innerHTML = `
            <div class="notif-checkbox-wrap">
                <input type="checkbox" class="notif-checkbox" data-id="${n.id}" ${selectedIds.has(n.id) ? "checked" : ""}>
            </div>
            <div class="notif-icon-wrap type-${n.type}">
                <i class="fas ${typeConfig.icon}"></i>
            </div>
            <div class="notif-content">
                <div class="notif-top-row">
                    <span class="notif-title-text">${friendlyTitle(n.title, n)}</span>
                    <span class="notif-time" data-utc-timestamp="${n.created_at}">${utcToRelativeTime(n.created_at)}</span>
                </div>
                <p class="notif-message">${friendlyMessage(n)}</p>
                ${status ? `<span class="notif-status-badge status-${status.key}"><i class="fas ${status.icon}"></i> ${status.label}</span>` : ""}
            </div>
            ${!n.is_read ? '<div class="unread-dot"></div>' : ""}
            <div class="notif-actions">
                ${!n.is_read ? `<button class="notif-action-btn read-btn" title="Mark as read" data-id="${n.id}"><i class="fas fa-check"></i></button>` : ""}
                <button class="notif-action-btn delete" title="Delete" data-id="${n.id}"><i class="fas fa-trash"></i></button>
            </div>
        `;

    // Checkbox
    wrapper.querySelector(".notif-checkbox").addEventListener("change", (e) => {
      e.stopPropagation();
      if (e.target.checked) selectedIds.add(n.id);
      else selectedIds.delete(n.id);
      wrapper.classList.toggle("selected", e.target.checked);
      updateBulkBar();
    });

    // Mark read btn
    const readBtn = wrapper.querySelector(".read-btn");
    if (readBtn) {
      readBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        markRead(n.id);
      });
    }

    // Delete btn
    wrapper
      .querySelector(".notif-action-btn.delete")
      .addEventListener("click", (e) => {
        e.stopPropagation();
        deleteNotification(n.id);
      });

    // Click item → mark read + navigate
    wrapper.addEventListener("click", () => {
      if (!n.is_read) markRead(n.id);
      if (n.action_url) {
        // Prefix with /jobseeker if needed
        const url = n.action_url.startsWith("/applications")
          ? "/jobseeker" + n.action_url
          : n.action_url;
        window.location.href = url;
      }
    });

    return wrapper;
  }

  // ─── Friendly text helpers ───────────────────────────────────────────────
  function friendlyTitle(rawTitle, n) {
    // Just return a clean, user-friendly version of the title
    const map = {
      "Interview Scheduled": "🗓️ Interview Scheduled",
      "Interview Rescheduled": "🔄 Interview Rescheduled",
      "Interview Cancelled": "❌ Interview Cancelled",
      "Interview Completed": "✅ Interview Completed",
      "Application Reviewed": "👀 Application Reviewed",
      "Application Accepted": "🎉 Application Accepted",
      "Application Shortlisted": "⭐ You Were Shortlisted",
      "Application Not Selected": "📋 Application Decision",
      "Application Rejected": "📋 Application Decision",
      "Application Withdrawn": "↩️ Application Withdrawn",
    };
    return map[rawTitle] || rawTitle;
  }

  function friendlyMessage(n) {
    // Clean up "at the employer" fallback, make message presentable
    let msg = n.message || "";

    // Replace generic "the employer" with actual company if known
    if (n.actor_company_name && n.actor_company_name !== "the employer") {
      msg = msg.replace(/\bthe employer\b/gi, n.actor_company_name);
    }

    return msg;
  }

  function getStatus(title) {
    const lower = (title || "").toLowerCase();
    for (const [key, val] of Object.entries(STATUS_MAP)) {
      if (lower.includes(key)) return val;
    }
    return null;
  }

  // ─── Bindings ─────────────────────────────────────────────────────────────
  function bindFilterTabs() {
    document.querySelectorAll(".filter-tab").forEach((tab) => {
      tab.addEventListener("click", () => {
        document
          .querySelectorAll(".filter-tab")
          .forEach((t) => t.classList.remove("active"));
        tab.classList.add("active");
        currentFilter = tab.dataset.filter;
        selectedIds.clear();
        updateBulkBar();
        renderNotifications();
      });
    });
  }

  function bindHeaderActions() {
    document.getElementById("markAllReadBtn").addEventListener("click", () => {
      const unread = allNotifications.filter((n) => !n.is_read);
      if (!unread.length) {
        Toast.info("All notifications are already read.");
        return;
      }
      Confirm.info({
        heading: "Mark All as Read",
        message: `Mark all ${unread.length} unread notification${unread.length > 1 ? "s" : ""} as read?`,
        confirmText: "Mark All Read",
        cancelText: "Cancel",
        onConfirm: markAllRead,
      });
    });
  }

  function bindBulkActions() {
    document.getElementById("bulkMarkReadBtn").addEventListener("click", () => {
      const ids = [...selectedIds];
      const unreadIds = ids.filter((id) => {
        const n = allNotifications.find((x) => x.id === id);
        return n && !n.is_read;
      });
      if (!unreadIds.length) {
        Toast.info("Selected notifications are already read.");
        return;
      }
      bulkMarkRead(unreadIds);
    });

    document.getElementById("bulkDeleteBtn").addEventListener("click", () => {
      bulkDelete([...selectedIds]);
    });

    document.getElementById("cancelSelectBtn").addEventListener("click", () => {
      selectedIds.clear();
      document
        .querySelectorAll(".notif-checkbox")
        .forEach((cb) => (cb.checked = false));
      document
        .querySelectorAll(".notif-item")
        .forEach((el) => el.classList.remove("selected"));
      updateBulkBar();
    });
  }

  function bindLoadMore() {
    $loadMoreBtn.addEventListener("click", () => {
      fetchNotifications(currentPage + 1, true);
    });
  }

  // ─── Bulk bar ─────────────────────────────────────────────────────────────
  function updateBulkBar() {
    const count = selectedIds.size;
    if (count > 0) {
      $bulkBar.classList.add("visible");
      $selectedCount.textContent = `${count} selected`;
    } else {
      $bulkBar.classList.remove("visible");
    }
  }

  // ─── Loading state ────────────────────────────────────────────────────────
  function showLoading(show) {
    $loading.style.display = show ? "flex" : "none";
    $groups.style.display = show ? "none" : "block";
  }
})();

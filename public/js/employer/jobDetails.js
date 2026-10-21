// ===== JOB DETAILS PAGE JAVASCRIPT =====
(function () {
  const API_BASE_URL = "/api";

  // ---------- Job Action Functions ----------

  async function publishJob(jobId) {
    try {
      const response = await fetch(`${API_BASE_URL}/jobs/${jobId}/publish`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')
            ?.content,
        },
      });

      const data = await response.json();

      if (data.success) {
        Toast.success(data.message || "Job published successfully!");
        // Reload the page to show updated status
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        Toast.error(data.message || "Failed to publish job");
      }
    } catch (error) {
      console.error("Error publishing job:", error);
      Toast.error("An error occurred while publishing the job");
    }
  }

  async function closeJob(jobId) {
    Confirm.warning({
      heading: "Close Job",
      message:
        "Are you sure you want to close this job? It will no longer accept new applications.",
      confirmText: "Close Job",
      cancelText: "Cancel",
      onConfirm: async () => {
        try {
          const response = await fetch(
            `${API_BASE_URL}/employer/jobs/${jobId}/close`,
            {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": document.querySelector(
                  'meta[name="csrf-token"]',
                )?.content,
              },
            },
          );

          const data = await response.json();

          if (data.success) {
            Toast.success(data.message || "Job closed successfully!");
            // Reload the page to show updated status
            setTimeout(() => {
              window.location.reload();
            }, 1000);
          } else {
            Toast.error(data.message || "Failed to close job");
          }
        } catch (error) {
          console.error("Error closing job:", error);
          Toast.error("An error occurred while closing the job");
        }
      },
      onCancel: () => {
        // Modal closes automatically
      },
    });
  }

  function duplicateJob(jobId) {
    // For now, redirect to create job page
    // TODO: Implement actual duplicate functionality
    Toast.info(
      "Duplicate functionality coming soon. Redirecting to create job page.",
    );
    setTimeout(() => {
      window.location.href = "/employer/jobs/create";
    }, 1500);
  }

  async function deleteJob(jobId) {
    Confirm.error({
      heading: "Delete Job",
      message:
        "Are you sure you want to delete this job? This action cannot be undone and will remove all associated applications.",
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
            Toast.success("Job deleted successfully!");
            // Redirect back to jobs list
            setTimeout(() => {
              window.location.href = "/employer/jobs";
            }, 1000);
          } else {
            Toast.error(data.message || "Failed to delete job");
          }
        } catch (error) {
          console.error("Error deleting job:", error);
          Toast.error("An error occurred while deleting the job");
        }
      },
      onCancel: () => {
        // Modal closes automatically
      },
    });
  }

  function editJob(jobId) {
    window.location.href = `/employer/jobs/edit/${jobId}`;
  }

  // ---------- Like Functionality ----------
  async function toggleLike(jobId) {
    try {
      const response = await fetch(`${API_BASE_URL}/like/toggle`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')
            ?.content,
        },
        body: JSON.stringify({
          likeable_type: "job",
          likeable_id: jobId,
        }),
      });

      const data = await response.json();

      if (data.success) {
        const likeBtn = document.querySelector(".interaction-btn.like-btn");
        const likeCount = likeBtn.querySelector("span");

        if (data.liked) {
          likeBtn.classList.add("active");
          likeCount.textContent = parseInt(likeCount.textContent) + 1;
        } else {
          likeBtn.classList.remove("active");
          likeCount.textContent = Math.max(
            0,
            parseInt(likeCount.textContent) - 1,
          );
        }
      } else {
        Toast.error(data.message || "Failed to update like");
      }
    } catch (error) {
      console.error("Error toggling like:", error);
      Toast.error("An error occurred");
    }
  }

  // ---------- Save Functionality ----------
  async function toggleSave(jobId) {
    try {
      const response = await fetch(
        `${API_BASE_URL}/jobs/${jobId}/toggle-save`,
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
        const saveBtn = document.querySelector(".interaction-btn.save-btn");
        const saveCount = saveBtn.querySelector("span");

        if (data.saved) {
          saveBtn.classList.add("active");
          saveCount.textContent = parseInt(saveCount.textContent) + 1;
        } else {
          saveBtn.classList.remove("active");
          saveCount.textContent = Math.max(
            0,
            parseInt(saveCount.textContent) - 1,
          );
        }
      } else {
        Toast.error(data.message || "Failed to update save");
      }
    } catch (error) {
      console.error("Error toggling save:", error);
      Toast.error("An error occurred");
    }
  }

  // ---------- Comments Functionality ----------
  let currentJobId = null;
  let currentCommentPage = 1;
  let currentCommentSort = "newest";
  let isLoadingComments = false;
  let hasMoreComments = true;

  function openComments(jobId) {
    currentJobId = jobId;
    const commentSection = document.querySelector(".comment-section");
    const overlay = document.querySelector(".comment-overlay");

    commentSection.classList.add("open");
    overlay.classList.add("visible");

    loadComments(true);
  }

  function closeComments() {
    const commentSection = document.querySelector(".comment-section");
    const overlay = document.querySelector(".comment-overlay");

    commentSection.classList.remove("open");
    overlay.classList.remove("visible");
    currentJobId = null;
  }

  async function loadComments(reset = false) {
    if (isLoadingComments || !currentJobId) return;

    if (reset) {
      currentCommentPage = 1;
      hasMoreComments = true;
    }

    if (!hasMoreComments) return;

    isLoadingComments = true;

    try {
      const response = await fetch(
        `${API_BASE_URL}/comments/job/${currentJobId}?page=${currentCommentPage}&per_page=20&sort=${currentCommentSort}`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')
              ?.content,
          },
        },
      );

      const data = await response.json();

      if (data.success) {
        const commentsContainer = document.querySelector(".comments-container");

        if (reset) {
          commentsContainer.innerHTML = "";
        }

        let comments = [];
        let pagination = {};

        if (data && data.data) {
          if (Array.isArray(data.data)) {
            comments = data.data;
            pagination = data.pagination || {};
          } else if (Array.isArray(data.data.data)) {
            comments = data.data.data;
            pagination = data.data.pagination || {};
          } else if (Array.isArray(data.data.data?.data)) {
            comments = data.data.data.data;
            pagination = data.data.data.pagination || {};
          }
        }

        if (comments.length > 0) {
          commentsContainer.innerHTML += comments
            .map((comment) => renderCommentItem(comment))
            .join("");

          attachCommentEventListeners();

          currentCommentPage++;
          hasMoreComments = !!pagination.has_next;
        } else if (reset) {
          commentsContainer.innerHTML =
            '<div class="comments-empty"><i class="fas fa-comments"></i><p>No comments yet</p><span>Be the first to share your thoughts!</span></div>';
          hasMoreComments = false;
        }
      } else {
        Toast.error(data.message || "Failed to load comments");
      }
    } catch (error) {
      console.error("Error loading comments:", error);
      Toast.error("An error occurred while loading comments");
    } finally {
      isLoadingComments = false;
    }
  }

  function renderCommentItem(comment, level = 0) {
    const authorName =
      comment.display_name || comment.full_name || comment.email || "Anonymous";
    const avatarUrl = comment.avatar || comment.user_avatar || "";
    const initials = authorName
      .split(" ")
      .filter(Boolean)
      .map((n) => n.charAt(0).toUpperCase())
      .slice(0, 2)
      .join("");

    const avatarHtml = avatarUrl
      ? `<img src="${avatarUrl}" alt="${authorName}" class="profile-photo" onerror="this.onerror=null;this.style.display='none';var fallback=this.nextElementSibling;if(fallback)fallback.style.display='flex';"><div class="avatar-fallback c${comment.id % 5}" style="display:none">${initials || "A"}</div>`
      : `<div class="avatar-fallback c${comment.id % 5}">${initials || "A"}</div>`;

    const timeAgo = comment.created_at
      ? new Date(comment.created_at).toLocaleDateString("en-US", {
          year: "numeric",
          month: "short",
          day: "numeric",
          hour: "2-digit",
          minute: "2-digit",
        })
      : "just now";

    const likeClass = comment.user_has_liked ? "active" : "";
    const likeCount = comment.likes_count || comment.like_count || 0;

    const commentHtml = `
      <div class="comment-item" data-comment-id="${comment.id}" style="margin-left:${level * 14}px;">
        <div class="comment-avatar-wrap">
          ${avatarHtml}
        </div>
        <div class="comment-content">
          <div class="comment-header-info">
            <span class="comment-username">${authorName}</span>
            <span class="comment-date">${timeAgo}</span>
          </div>
          <p class="comment-text">${comment.content}</p>
          <div class="comment-actions">
            <button class="like-btn ${likeClass}" data-comment-id="${comment.id}">
              <i class="fas fa-heart"></i>
              <span>${likeCount}</span>
            </button>
            <button class="reply-btn" data-comment-id="${comment.id}">
              <i class="fas fa-reply"></i> Reply
            </button>
            ${comment.replies_count > 0 ? `<span class="reply-count"><i class="fas fa-comment"></i> ${comment.replies_count}</span>` : ""}
          </div>

          <div class="reply-form" id="reply-form-${comment.id}" style="display: none; margin-top: 8px;">
            <textarea class="reply-input" data-parent-id="${comment.id}" rows="2" placeholder="Write a reply..." maxlength="500"></textarea>
            <button type="button" class="submit-reply-btn" data-parent-id="${comment.id}">Post reply</button>
          </div>
        </div>
      </div>
    `;

    const repliesHtml = (comment.replies || []).length
      ? `<div class="reply-thread" style="margin-left: 16px; border-left: 2px solid rgba(0,0,0,.08); padding-left: 12px;">${comment.replies
          .map((reply) => renderCommentItem(reply, level + 1))
          .join("")}</div>`
      : "";

    return commentHtml + repliesHtml;
  }

  async function submitComment() {
    const textarea = document.querySelector(".comment-input");
    const content = textarea.value.trim();

    if (!content) {
      Toast.warning("Please enter a comment");
      return;
    }

    await submitReply(null, content);
  }

  async function submitReply(parentId, content) {
    if (!content.trim()) {
      Toast.warning("Please enter a reply");
      return;
    }

    if (!currentJobId) {
      Toast.error("Job ID not found");
      return;
    }

    try {
      const response = await fetch(
        `${API_BASE_URL}/comments/job/${currentJobId}`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')
              ?.content,
          },
          body: JSON.stringify({
            content: content,
            parent_id: parentId,
          }),
        },
      );

      const data = await response.json();

      if (data.success) {
        if (parentId === null) {
          const textarea = document.querySelector(".comment-input");
          if (textarea) textarea.value = "";
          Toast.success("Comment posted successfully!");
        } else {
          // close and clear reply form
          const replyForm = document.querySelector(`#reply-form-${parentId}`);
          if (replyForm) {
            const input = replyForm.querySelector(".reply-input");
            if (input) input.value = "";
            replyForm.style.display = "none";
          }
          Toast.success("Reply posted successfully!");
        }

        // Update comment count button (top stat)
        const commentBtn = document.querySelector(
          ".interaction-btn.comment-btn",
        );
        if (commentBtn) {
          const countSpan = commentBtn.querySelector("span");
          if (countSpan) {
            countSpan.textContent = parseInt(countSpan.textContent || "0") + 1;
          }
        }

        // Reload comments
        loadComments(true);
      } else {
        Toast.error(data.message || "Failed to post comment");
      }
    } catch (error) {
      console.error("Error submitting comment:", error);
      Toast.error("An error occurred while posting the comment");
    }
  }

  async function toggleCommentLike(commentId) {
    try {
      const response = await fetch(
        `${API_BASE_URL}/comments/${commentId}/like`,
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
        const commentItem = document.querySelector(
          `.comment-item[data-comment-id="${commentId}"]`,
        );
        const likeBtn = commentItem.querySelector(".like-btn");
        const likeCount = likeBtn.querySelector("span");

        if (data.liked) {
          likeBtn.classList.add("active");
          likeCount.textContent = parseInt(likeCount.textContent) + 1;
        } else {
          likeBtn.classList.remove("active");
          likeCount.textContent = Math.max(
            0,
            parseInt(likeCount.textContent) - 1,
          );
        }
      } else {
        Toast.error(data.message || "Failed to update like");
      }
    } catch (error) {
      console.error("Error toggling comment like:", error);
      Toast.error("An error occurred");
    }
  }

  function attachCommentEventListeners() {
    // Close comments on overlay click
    const overlay = document.querySelector(".comment-overlay");
    if (overlay) {
      overlay.onclick = closeComments;
    }

    // Close button
    const closeBtn = document.querySelector(".comment-close-btn");
    if (closeBtn) {
      closeBtn.onclick = closeComments;
    }

    // Comment submit button
    const submitBtn = document.querySelector(".comment-submit-btn");
    if (submitBtn) {
      submitBtn.onclick = submitComment;
    }

    // Comment sort
    const sortSelect = document.querySelector(".comment-sort-select");
    if (sortSelect) {
      sortSelect.onchange = (e) => {
        currentCommentSort = e.target.value;
        loadComments(true);
      };
    }

    // Reply button click (toggle reply form)
    const replyButtons = document.querySelectorAll(".reply-btn");
    replyButtons.forEach((btn) => {
      btn.onclick = (e) => {
        const commentId = e.currentTarget.dataset.commentId;
        const form = document.querySelector(`#reply-form-${commentId}`);
        if (!form) return;
        form.style.display = form.style.display === "none" ? "block" : "none";
      };
    });

    // Reply submit buttons
    const replySubmitButtons = document.querySelectorAll(".submit-reply-btn");
    replySubmitButtons.forEach((btn) => {
      btn.onclick = async (e) => {
        const parentId = e.currentTarget.dataset.parentId;
        const form = document.querySelector(`#reply-form-${parentId}`);
        if (!form) return;
        const input = form.querySelector(".reply-input");
        const text = input ? input.value.trim() : "";
        if (!text) {
          Toast.warning("Please enter a reply");
          return;
        }
        await submitReply(parentId, text);
      };
    });

    // Allow Enter key to submit comment
    const textarea = document.querySelector(".comment-input");
    if (textarea) {
      textarea.onkeydown = (e) => {
        if (e.key === "Enter" && e.ctrlKey) {
          submitComment();
          e.preventDefault();
        }
      };
    }
  }

  // ---------- Initialization ----------
  function init() {
    // Make functions globally available
    window.publishJob = publishJob;
    window.closeJob = closeJob;
    window.duplicateJob = duplicateJob;
    window.deleteJob = deleteJob;
    window.editJob = editJob;
    window.toggleLike = toggleLike;
    window.toggleSave = toggleSave;
    window.openComments = openComments;
    window.closeComments = closeComments;
    window.toggleCommentLike = toggleCommentLike;

    // Extract job ID from URL or window.jobData
    let jobId = null;

    if (window.jobData && window.jobData.id) {
      jobId = window.jobData.id;
    } else {
      // Fallback: extract from URL path /employer/jobs/view/{id}
      const pathParts = window.location.pathname.split("/");
      jobId = pathParts[pathParts.length - 1];
    }

    if (!jobId) {
      console.error("Job ID not found");
      return;
    }

    // Initialize comment event listeners
    attachCommentEventListeners();

    console.log("Job Details page initialized with job ID:", jobId);
  }

  // Initialize when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();

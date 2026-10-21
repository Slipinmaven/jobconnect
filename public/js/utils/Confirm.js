(function () {
  class ConfirmManager {
    constructor() {
      this.container = document.createElement("div");
      this.container.className = "confirm-modal-container";
      document.body.appendChild(this.container);

      this.defaultOptions = {
        type: "info",
        heading: "Confirmation",
        message: "Are you sure?",
        details: null, // additional details
        html: false, // render as HTML
        confirmText: "Confirm",
        cancelText: "Cancel",
        onConfirm: null,
        onCancel: null,
        closeOnConfirm: true,
        closeOnCancel: true,
      };
    }

    show(options, type = "info") {
      if (typeof options === "string") {
        options = { message: options, type };
      }
      return this._showWithOptions({ ...this.defaultOptions, ...options });
    }

    success(options) {
      if (typeof options === "string") options = { message: options };
      return this.show({ ...options, type: "success" });
    }

    error(options) {
      if (typeof options === "string") options = { message: options };
      return this.show({ ...options, type: "error" });
    }

    warning(options) {
      if (typeof options === "string") options = { message: options };
      return this.show({ ...options, type: "warning" });
    }

    info(options) {
      if (typeof options === "string") options = { message: options };
      return this.show({ ...options, type: "info" });
    }

    _showWithOptions(config) {
      // Create overlay
      const overlay = document.createElement("div");
      overlay.className = "confirm-overlay";

      // Create modal
      const modal = document.createElement("div");
      modal.className = `confirm-modal confirm-${config.type}`;

      // --- CONTENT CONTAINER (scrollable) ---
      const contentContainer = document.createElement("div");
      contentContainer.className = "confirm-content";

      // Heading
      if (config.heading) {
        const heading = document.createElement("h3");
        heading.className = "confirm-heading";
        if (config.html) {
          heading.innerHTML = config.heading;
        } else {
          heading.textContent = config.heading;
        }
        contentContainer.appendChild(heading);
      }

      // Message
      if (config.message) {
        const messageEl = document.createElement("div");
        messageEl.className = "confirm-message";

        if (config.html) {
          messageEl.innerHTML = config.message;
        } else {
          messageEl.textContent = config.message;
        }
        contentContainer.appendChild(messageEl);
      }

      // --- DETAILS SECTION ---
      if (config.details) {
        const detailsEl = document.createElement("div");
        detailsEl.className = "confirm-details";

        if (Array.isArray(config.details)) {
          const list = document.createElement("ul");
          config.details.forEach((item) => {
            const li = document.createElement("li");
            if (config.html) {
              li.innerHTML = item;
            } else {
              li.textContent = item;
            }
            list.appendChild(li);
          });
          detailsEl.appendChild(list);
        } else {
          if (config.html) {
            detailsEl.innerHTML = config.details;
          } else {
            detailsEl.textContent = config.details;
          }
        }
        contentContainer.appendChild(detailsEl);
      }

      modal.appendChild(contentContainer);

      // --- BUTTONS CONTAINER ---
      const btnContainer = document.createElement("div");
      btnContainer.className = "confirm-buttons";

      // Cancel button
      if (config.cancelText) {
        const cancelBtn = document.createElement("button");
        cancelBtn.className = "confirm-btn confirm-btn-cancel";
        cancelBtn.textContent = config.cancelText;
        cancelBtn.addEventListener("click", (e) => {
          e.stopPropagation();
          if (config.onCancel && typeof config.onCancel === "function") {
            config.onCancel();
          }
          if (config.closeOnCancel) this._close(overlay);
        });
        btnContainer.appendChild(cancelBtn);
      }

      // Confirm button
      if (config.confirmText) {
        const confirmBtn = document.createElement("button");
        confirmBtn.className = `confirm-btn confirm-btn-confirm confirm-${config.type}`;
        confirmBtn.textContent = config.confirmText;
        confirmBtn.addEventListener("click", (e) => {
          e.stopPropagation();
          if (config.onConfirm && typeof config.onConfirm === "function") {
            config.onConfirm();
          }
          if (config.closeOnConfirm) this._close(overlay);
        });
        btnContainer.appendChild(confirmBtn);
      }

      modal.appendChild(btnContainer);

      // Assemble
      overlay.appendChild(modal);
      this.container.appendChild(overlay);

      // Trigger entrance animation
      setTimeout(() => overlay.classList.add("show"), 10);

      // Click on overlay to cancel (optional)
      overlay.addEventListener("click", (e) => {
        if (e.target === overlay) {
          if (config.onCancel) config.onCancel();
          this._close(overlay);
        }
      });

      return { overlay, modal };
    }

    _close(overlay) {
      if (!overlay || !overlay.parentNode) return;
      overlay.classList.remove("show");
      setTimeout(() => {
        if (overlay.parentNode) overlay.remove();
      }, 300);
    }

    clearAll() {
      while (this.container.firstChild) {
        this.container.firstChild.remove();
      }
    }
  }

  // Attach to global window object
  window.Confirm = new ConfirmManager();
})();

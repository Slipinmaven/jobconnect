/**
 * Toast Notification System - Global Version
 *
 * HOW TO USE:
 * Toast.success('Operation completed!');
 * Toast.error('Something went wrong');
 * Toast.warning('Please check your input');
 * Toast.info('New message received');
 *
 * Or with custom options:
 * Toast.show('Custom message', 'info', 5000);
 * Toast.show({ message: 'Hello', type: 'success', duration: 2000 });
 */

class ToastManager {
  constructor() {
    // Create container if it doesn't exist
    this.container = document.createElement("div");
    this.container.className = "toast-container";
    document.body.appendChild(this.container);

    this.defaultOptions = {
      duration: 3000,
      type: "info",
      dismissible: true,
      showCloseButton: true,
      message: "",
    };

    this.icons = {
      success: '<i class="fas fa-check-circle"></i>',
      error: '<i class="fas fa-exclamation-circle"></i>',
      warning: '<i class="fas fa-exclamation-triangle"></i>',
      info: '<i class="fas fa-info-circle"></i>',
    };

    console.log("✅ Toast system ready");
  }

  /**
   * Show a toast message.
   */
  show(message, type = "info", duration = 3000) {
    if (typeof message === "object" && message !== null) {
      return this._showWithOptions(message);
    }
    const numericDuration = Number(duration) || 3000;
    return this._showWithOptions({ message, type, duration: numericDuration });
  }

  _showWithOptions(options) {
    const config = {
      ...this.defaultOptions,
      ...options,
      duration: Number(options.duration) || this.defaultOptions.duration,
    };

    // Create toast element
    const toast = document.createElement("div");
    toast.className = `toast toast-${config.type}`;
    toast.setAttribute("role", "alert");

    // Content wrapper
    const content = document.createElement("div");
    content.className = "toast-content";

    // Icon - CHANGED: textContent to innerHTML
    const icon = document.createElement("span"); // Also changed from <i> to <span> since we're putting <i> inside
    icon.className = "toast-icon";
    icon.innerHTML = this.icons[config.type] || this.icons.info;
    content.appendChild(icon);

    // Message
    const messageSpan = document.createElement("span");
    messageSpan.className = "toast-message";
    messageSpan.textContent = config.message || "";
    content.appendChild(messageSpan);

    toast.appendChild(content);

    // Close button (if enabled)
    if (config.showCloseButton) {
      const closeBtn = document.createElement("button");
      closeBtn.className = "toast-close";
      closeBtn.innerHTML = "&times;";
      closeBtn.setAttribute("aria-label", "Close");
      closeBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        this._removeToast(toast);
      });
      toast.appendChild(closeBtn);
    }
    // Dismissible on click (if no close button)
    else if (config.dismissible) {
      toast.addEventListener("click", () => {
        this._removeToast(toast);
      });
    }

    // Append to container
    this.container.appendChild(toast);

    // Auto‑remove after duration (if > 0)
    if (config.duration > 0) {
      toast._removalTimeout = setTimeout(() => {
        this._removeToast(toast);
      }, config.duration);
    }

    return toast;
  }

  /**
   * Remove a toast with animation.
   */
  _removeToast(toast) {
    if (!toast || !toast.parentNode) return;

    if (toast._removalTimeout) {
      clearTimeout(toast._removalTimeout);
      toast._removalTimeout = null;
    }

    toast.style.animation = "slideOutRight 0.3s ease-out forwards";

    setTimeout(() => {
      if (toast.parentNode) toast.remove();
    }, 300);
  }

  // Convenience methods
  success(message, duration) {
    return this.show({ message, type: "success", duration });
  }

  error(message, duration) {
    return this.show({ message, type: "error", duration });
  }

  warning(message, duration) {
    return this.show({ message, type: "warning", duration });
  }

  info(message, duration) {
    return this.show({ message, type: "info", duration });
  }

  /**
   * Immediately remove all toasts.
   */
  clearAll() {
    while (this.container.firstChild) {
      this.container.firstChild.remove();
    }
  }
}

// Create a single instance and attach to window (makes it global)
window.Toast = new ToastManager();

// Also make it available as a variable for module usage (optional)
const Toast = window.Toast;

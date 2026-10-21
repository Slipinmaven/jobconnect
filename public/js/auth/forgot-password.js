/**
 * Forgot Password Page JavaScript
 * Handles password reset flow, validation, and UI interactions
 */

class ForgotPasswordHandler {
  constructor() {
    // API Endpoints
    this.API = {
      FORGOT_PASSWORD: "/api/forgot-password",
      VERIFY_RESET_TOKEN: "/api/verify-reset-token",
      RESET_PASSWORD: "/api/reset-password",
      RESEND_EMAIL: "/api/resend-reset-link",
    };

    // Initialize elements
    this.initializeElements();

    // Initialize event listeners
    this.initializeEventListeners();

    // Check URL parameters
    this.checkUrlParams();

    // Initialize theme
    this.initializeTheme();
  }

  initializeElements() {
    // Form steps
    this.step1 = document.getElementById("step1");
    this.step2 = document.getElementById("step2");
    this.step3 = document.getElementById("step3");

    // Forms
    this.forgotPasswordForm = document.getElementById("forgotPasswordForm");
    this.resetPasswordForm = document.getElementById("resetPasswordForm");

    // Input fields
    this.emailInput = document.getElementById("email");
    this.newPassword = document.getElementById("newPassword");
    this.confirmPassword = document.getElementById("confirmPassword");

    // Buttons
    this.resetBtn = document.getElementById("resetPasswordBtn");
    this.sendResetBtn = document.getElementById("sendResetBtn");
    this.resendBtn = document.getElementById("resendBtn");

    // UI Elements
    this.loadingIndicator = document.getElementById("loadingIndicator");
    this.statusMessage = document.getElementById("statusMessage");
    this.statusText = document.getElementById("statusText");
    this.themeToggle = document.getElementById("themeToggle");

    // Password requirement elements
    this.reqLength = document.getElementById("reqLength");
    this.reqUppercase = document.getElementById("reqUppercase");
    this.reqNumber = document.getElementById("reqNumber");
    this.reqSpecial = document.getElementById("reqSpecial");
    this.strengthProgress = document.getElementById("strengthProgress");
    this.strengthText = document.getElementById("strengthText");

    // Hidden fields
    this.tokenInput = document.getElementById("resetToken");
  }

  initializeEventListeners() {
    // Theme toggle
    if (this.themeToggle) {
      this.themeToggle.addEventListener("click", () => this.toggleTheme());
    }

    // Password visibility toggles
    document.querySelectorAll(".toggle-password").forEach((toggle) => {
      toggle.addEventListener("click", (e) => this.togglePasswordVisibility(e));
    });

    // Forgot password form submission
    if (this.forgotPasswordForm) {
      this.forgotPasswordForm.addEventListener("submit", (e) =>
        this.handleForgotPassword(e),
      );
    }

    // Reset password form submission
    if (this.resetPasswordForm) {
      this.resetPasswordForm.addEventListener("submit", (e) =>
        this.handleResetPassword(e),
      );
    }

    // Password validation on input
    if (this.newPassword) {
      this.newPassword.addEventListener("input", () =>
        this.validatePasswordInput(),
      );
    }

    // Confirm password validation
    if (this.confirmPassword) {
      this.confirmPassword.addEventListener("input", () =>
        this.validateConfirmPassword(),
      );
    }

    // Clear errors on input
    if (this.emailInput) {
      this.emailInput.addEventListener("input", () =>
        this.clearFieldError("email"),
      );
    }

    // Resend link button
    if (this.resendBtn) {
      this.resendBtn.addEventListener("click", (e) => this.handleResendLink(e));
    }
  }

  initializeTheme() {
    const html = document.documentElement;
    const savedTheme = localStorage.getItem("theme") || "light";
    html.setAttribute("data-theme", savedTheme);
    this.updateThemeIcons(savedTheme);
  }

  toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute("data-theme");
    const newTheme = currentTheme === "light" ? "dark" : "light";

    html.setAttribute("data-theme", newTheme);
    localStorage.setItem("theme", newTheme);
    this.updateThemeIcons(newTheme);
  }

  updateThemeIcons(theme) {
    if (!this.themeToggle) return;

    const sunIcon = this.themeToggle.querySelector(".fa-sun");
    const moonIcon = this.themeToggle.querySelector(".fa-moon");

    if (theme === "dark") {
      sunIcon.style.display = "none";
      moonIcon.style.display = "inline-block";
    } else {
      sunIcon.style.display = "inline-block";
      moonIcon.style.display = "none";
    }
  }

  togglePasswordVisibility(event) {
    const toggle = event.currentTarget;
    const input = toggle.previousElementSibling;
    const type =
      input.getAttribute("type") === "password" ? "text" : "password";

    input.setAttribute("type", type);
    toggle.classList.toggle("fa-eye");
    toggle.classList.toggle("fa-eye-slash");
  }

  /**
   * Check URL parameters for token and email
   * This is called when user clicks the reset link from email
   */
  async checkUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);

    // Check for token and email in URL (from reset link)
    const token = urlParams.get("token");
    const email = urlParams.get("email");

    if (token && email) {
      // Store token and email
      this.resetToken = token;
      this.resetEmail = decodeURIComponent(email);

      // Pre-fill email field if it exists in step 2
      const emailField = document.getElementById("resetEmail");
      if (emailField) {
        emailField.value = this.resetEmail;
      }

      // Verify token before showing reset form
      await this.verifyResetToken(token, this.resetEmail);
    }

    // Check for expired token
    if (urlParams.get("expired") === "true") {
      this.showStatus(
        "Your reset link has expired. Please request a new one.",
        "error",
      );
    }
  }

  /**
   * Verify reset token is valid
   */
  async verifyResetToken(token, email) {
    this.setLoading(true);

    try {
      const response = await fetch(this.API.VERIFY_RESET_TOKEN, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({ token, email }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || "Invalid or expired token");
      }

      // Token is valid, show reset form
      this.showStep(2);
    } catch (error) {
      console.error("Token verification failed:", error);
      this.showStatus(error.message, "error");

      // Stay on step 1 with error
      this.showStep(1);
    } finally {
      this.setLoading(false);
    }
  }

  showStep(stepNumber) {
    // Hide all steps
    this.step1.style.display = "none";
    this.step2.style.display = "none";
    this.step3.style.display = "none";

    // Show requested step
    switch (stepNumber) {
      case 1:
        this.step1.style.display = "block";
        break;
      case 2:
        this.step2.style.display = "block";
        break;
      case 3:
        this.step3.style.display = "block";
        break;
    }
  }

  // Email validation
  isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }

  // Password validation
  validatePassword(password) {
    const checks = {
      length: password.length >= 6,
      uppercase: /[A-Z]/.test(password),
      number: /[0-9]/.test(password),
      special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password),
    };

    // Update requirement indicators
    this.updateRequirement("length", checks.length);
    this.updateRequirement("uppercase", checks.uppercase);
    this.updateRequirement("number", checks.number);
    this.updateRequirement("special", checks.special);

    // Calculate strength
    const metCount = Object.values(checks).filter(Boolean).length;
    this.updateStrengthMeter(metCount);

    return checks.length && checks.uppercase && checks.number && checks.special;
  }

  updateRequirement(type, isMet) {
    const element = this[`req${type.charAt(0).toUpperCase() + type.slice(1)}`];
    if (!element) return;

    element.classList.toggle("met", isMet);
    const icon = element.querySelector("i");
    icon.className = isMet ? "fas fa-check-circle" : "fas fa-circle";
  }

  updateStrengthMeter(metCount) {
    const strengthPercent = (metCount / 4) * 100;
    this.strengthProgress.style.width = strengthPercent + "%";

    if (metCount === 0) {
      this.strengthProgress.style.background = "#ef4444";
      this.strengthText.textContent = "Password strength: None";
    } else if (metCount <= 2) {
      this.strengthProgress.style.background = "#f59e0b";
      this.strengthText.textContent = "Password strength: Weak";
    } else if (metCount === 3) {
      this.strengthProgress.style.background = "#3b82f6";
      this.strengthText.textContent = "Password strength: Good";
    } else {
      this.strengthProgress.style.background = "#10b981";
      this.strengthText.textContent = "Password strength: Strong";
    }
  }

  passwordsMatch() {
    return this.newPassword.value === this.confirmPassword.value;
  }

  validatePasswordInput() {
    const isValid = this.validatePassword(this.newPassword.value);
    const match = this.passwordsMatch();

    this.resetBtn.disabled = !(isValid && match);

    if (this.newPassword.value) {
      this.newPassword.classList.toggle("is-valid", isValid);
      this.newPassword.classList.toggle("is-invalid", !isValid);
    }
  }

  validateConfirmPassword() {
    const match = this.passwordsMatch();
    const isValid = this.validatePassword(this.newPassword.value);

    this.resetBtn.disabled = !(isValid && match);

    if (this.confirmPassword.value) {
      this.confirmPassword.classList.toggle("is-valid", match);
      this.confirmPassword.classList.toggle("is-invalid", !match);
    }

    this.showConfirmPasswordError();
  }

  showConfirmPasswordError() {
    const confirmError = document.getElementById("confirmPasswordError");
    if (!confirmError) return;

    if (this.confirmPassword.value && !this.passwordsMatch()) {
      confirmError.textContent = "Passwords do not match";
      confirmError.classList.add("show");
    } else {
      confirmError.classList.remove("show");
    }
  }

  // Status message
  showStatus(message, type) {
    if (!this.statusText || !this.statusMessage) return;

    this.statusText.textContent = message;
    this.statusMessage.className = "status-message " + type + " show";

    setTimeout(() => {
      this.statusMessage.classList.remove("show");
    }, 5000);
  }

  // Loading state
  setLoading(isLoading) {
    if (!this.loadingIndicator) return;

    if (isLoading) {
      this.loadingIndicator.classList.add("show");
      if (this.sendResetBtn) this.sendResetBtn.disabled = true;
      if (this.resetBtn) this.resetBtn.disabled = true;
      if (this.resendBtn) this.resendBtn.disabled = true;
    } else {
      this.loadingIndicator.classList.remove("show");
      if (this.sendResetBtn) this.sendResetBtn.disabled = false;
      if (this.resendBtn) this.resendBtn.disabled = false;
      // Reset button state will be updated by validation
      if (this.resetBtn) {
        this.validatePasswordInput();
      }
    }
  }

  // Clear errors
  clearErrors() {
    document.querySelectorAll(".error-message").forEach((el) => {
      el.classList.remove("show");
    });
    document.querySelectorAll(".input-wrapper input").forEach((el) => {
      el.classList.remove("is-invalid", "is-valid");
    });
  }

  clearFieldError(fieldId) {
    const errorEl = document.getElementById(fieldId + "Error");
    const input = document.getElementById(fieldId);

    if (errorEl) {
      errorEl.classList.remove("show");
    }
    if (input) {
      input.classList.remove("is-invalid");
    }
  }

  showFieldError(fieldId, message) {
    const errorEl = document.getElementById(fieldId + "Error");
    const input = document.getElementById(fieldId);

    if (errorEl) {
      errorEl.textContent = message;
      errorEl.classList.add("show");
    }
    if (input) {
      input.classList.add("is-invalid");
    }
  }

  /**
   * Step 1: Send reset link to email
   */
  async handleForgotPassword(e) {
    e.preventDefault();
    this.clearErrors();

    const email = this.emailInput.value.trim();

    if (!email) {
      this.showFieldError("email", "Email is required");
      return;
    }
    if (!this.isValidEmail(email)) {
      this.showFieldError("email", "Please enter a valid email");
      return;
    }

    this.setLoading(true);

    try {
      const data = await this.sendForgotPasswordRequest(email);

      this.setLoading(false);

      if (data.success) {
        this.showStatus(
          data.message || "Reset link sent! Check your email.",
          "success",
        );

        // Store email for potential resend
        this.resetEmail = email;

        // Optionally disable form for 60 seconds (prevent spam)
        this.disableForgotForm(60);
      } else {
        this.showStatus(data.message || "Failed to send reset link", "error");
      }
    } catch (error) {
      this.setLoading(false);
      this.showStatus(error.message || "Failed to send reset link", "error");
    }
  }

  /**
   * API Call: Send forgot password request
   */
  async sendForgotPasswordRequest(email) {
    const response = await fetch(this.API.FORGOT_PASSWORD, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({ email }),
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || "Failed to send reset link");
    }

    return data;
  }

  /**
   * Disable forgot password form for specified seconds
   */
  disableForgotForm(seconds) {
    if (!this.sendResetBtn) return;

    this.sendResetBtn.disabled = true;
    let remaining = seconds;

    const originalText = this.sendResetBtn.innerHTML;
    this.sendResetBtn.innerHTML = `Resend in ${remaining}s`;

    const interval = setInterval(() => {
      remaining--;
      this.sendResetBtn.innerHTML = `Resend in ${remaining}s`;

      if (remaining <= 0) {
        clearInterval(interval);
        this.sendResetBtn.disabled = false;
        this.sendResetBtn.innerHTML = originalText;
      }
    }, 1000);
  }

  /**
   * Step 2: Reset password submission
   */
  async handleResetPassword(e) {
    e.preventDefault();
    this.clearErrors();

    const password = this.newPassword.value;
    const confirm = this.confirmPassword.value;
    const token =
      this.resetToken || (this.tokenInput ? this.tokenInput.value : null);
    const email =
      this.resetEmail ||
      (document.getElementById("resetEmail")
        ? document.getElementById("resetEmail").value
        : null);

    if (!token || !email) {
      this.showStatus(
        "Missing reset information. Please request a new link.",
        "error",
      );
      setTimeout(() => {
        window.location.href = "/forgot-password";
      }, 2000);
      return;
    }

    if (!this.validatePassword(password)) {
      this.showFieldError("newPassword", "Password does not meet requirements");
      return;
    }

    if (!this.passwordsMatch()) {
      this.showFieldError("confirmPassword", "Passwords do not match");
      return;
    }

    this.setLoading(true);

    try {
      const data = await this.sendResetPasswordRequest(token, email, password);

      this.setLoading(false);

      if (data.success) {
        // Show success message
        this.showStep(3);

        // Auto redirect to login after 5 seconds
        setTimeout(() => {
          window.location.href = "/login";
        }, 5000);
      } else {
        this.showStatus(data.message || "Failed to reset password", "error");
      }
    } catch (error) {
      this.setLoading(false);
      this.showStatus(error.message || "Failed to reset password", "error");
    }
  }

  /**
   * API Call: Reset password
   */
  async sendResetPasswordRequest(token, email, password) {
    const response = await fetch(this.API.RESET_PASSWORD, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({
        token,
        email,
        password,
        password_confirmation: this.confirmPassword.value,
      }),
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || "Failed to reset password");
    }

    return data;
  }

  /**
   * Resend reset link
   */
  async handleResendLink(e) {
    e.preventDefault();

    if (!this.resetEmail) {
      this.showStatus("Email not found. Please try again.", "error");
      return;
    }

    this.setLoading(true);

    try {
      const data = await this.resendResetLink(this.resetEmail);

      this.setLoading(false);

      if (data.success) {
        this.showStatus("New reset link sent! Check your email.", "success");
        this.disableForgotForm(60);
      } else {
        this.showStatus(data.message || "Failed to send reset link", "error");
      }
    } catch (error) {
      this.setLoading(false);
      this.showStatus(error.message || "Failed to send reset link", "error");
    }
  }

  /**
   * API Call: Resend reset link
   */
  async resendResetLink(email) {
    const response = await fetch(this.API.RESEND_EMAIL, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({ email }),
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || "Failed to resend reset link");
    }

    return data;
  }

  /**
   * Validate password strength (independent utility method)
   */
  static validatePasswordStrength(password) {
    return {
      length: password.length >= 6,
      uppercase: /[A-Z]/.test(password),
      number: /[0-9]/.test(password),
      special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password),
      score: (password) => {
        let score = 0;
        if (password.length >= 6) score++;
        if (password.length >= 8) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[!@#$%^&*]/.test(password)) score++;
        return score;
      },
    };
  }

  /**
   * Generate random password (independent utility method)
   */
  static generateStrongPassword(length = 12) {
    const uppercase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    const lowercase = "abcdefghijklmnopqrstuvwxyz";
    const numbers = "0123456789";
    const special = "!@#$%^&*()_+";

    const allChars = uppercase + lowercase + numbers + special;
    let password = "";

    // Ensure at least one of each required type
    password += uppercase[Math.floor(Math.random() * uppercase.length)];
    password += numbers[Math.floor(Math.random() * numbers.length)];
    password += special[Math.floor(Math.random() * special.length)];

    // Fill the rest
    for (let i = password.length; i < length; i++) {
      password += allChars[Math.floor(Math.random() * allChars.length)];
    }

    // Shuffle
    return password
      .split("")
      .sort(() => 0.5 - Math.random())
      .join("");
  }

  /**
   * Handle API errors (independent utility method)
   */
  static handleApiError(error, defaultMessage = "An error occurred") {
    console.error("API Error:", error);

    if (error.response) {
      // Server responded with error
      return error.response.data.message || defaultMessage;
    } else if (error.request) {
      // Request made but no response
      return "Network error. Please check your connection.";
    } else {
      // Something else
      return error.message || defaultMessage;
    }
  }
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  new ForgotPasswordHandler();
});

// ==================== EMAIL VALIDATION ====================
function validateEmail() {
  const email = document.getElementById("email").value;
  const emailField = document.getElementById("email");
  const emailHint = document.getElementById("emailHint");

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (email === "") {
    emailField.classList.remove("is-valid", "is-invalid");
    emailHint.className = "email-hint";
    emailHint.innerHTML =
      '<i class="fas fa-info-circle"></i><span>Enter a valid email address</span>';
  } else if (emailRegex.test(email)) {
    emailField.classList.add("is-valid");
    emailField.classList.remove("is-invalid");
    emailHint.className = "email-hint valid";
    emailHint.innerHTML =
      '<i class="fas fa-check-circle"></i><span>Valid email format</span>';
  } else {
    emailField.classList.add("is-invalid");
    emailField.classList.remove("is-valid");
    emailHint.className = "email-hint invalid";
    emailHint.innerHTML =
      '<i class="fas fa-times-circle"></i><span>Invalid email format</span>';
  }

  updateSubmitButton();
}

// Add the missing hideEmailHint function
function hideEmailHint() {
  const emailHint = document.getElementById("emailHint");
  if (emailHint && document.getElementById("email").value === "") {
    emailHint.style.opacity = "0.5";
  }
}

// ==================== PASSWORD VALIDATION ====================
function validatePassword() {
  const password = document.getElementById("password").value;

  const hasLength = password.length >= 8;
  const hasUpper = /[A-Z]/.test(password);
  const hasLower = /[a-z]/.test(password);
  const hasNumber = /[0-9]/.test(password);
  const hasSpecial = /[!@#$%^&*]/.test(password);

  updateChecklistItem("length", hasLength);
  updateChecklistItem("uppercase", hasUpper);
  updateChecklistItem("lowercase", hasLower);
  updateChecklistItem("number", hasNumber);
  updateChecklistItem("special", hasSpecial);

  updatePasswordStrength(password);
  validateConfirmPassword();
  updateSubmitButton();
}

function updateChecklistItem(id, isValid) {
  const item = document.getElementById(id);
  if (!item) return;

  const icon = item.querySelector("i");

  if (isValid) {
    item.classList.add("valid");
    item.classList.remove("invalid");
    icon.className = "fas fa-check-circle";
  } else {
    item.classList.add("invalid");
    item.classList.remove("valid");
    icon.className = "fas fa-circle";
  }
}

function updatePasswordStrength(password) {
  const meter = document.getElementById("strengthMeter");
  const text = document.getElementById("strengthText");

  if (!meter || !text) return;

  if (password.length === 0) {
    meter.style.width = "0";
    meter.className = "strength-meter-fill";
    text.textContent = "Enter password";
    return;
  }

  let strength = 0;

  if (password.length >= 8) strength++;
  if (password.length >= 12) strength++;
  if (/[A-Z]/.test(password)) strength++;
  if (/[a-z]/.test(password)) strength++;
  if (/[0-9]/.test(password)) strength++;
  if (/[!@#$%^&*]/.test(password)) strength++;

  const percentage = (strength / 6) * 100;
  meter.style.width = percentage + "%";

  if (strength <= 2) {
    meter.className = "strength-meter-fill strength-weak";
    text.textContent = "Weak password";
  } else if (strength <= 4) {
    meter.className = "strength-meter-fill strength-medium";
    text.textContent = "Medium password";
  } else if (strength <= 5) {
    meter.className = "strength-meter-fill strength-strong";
    text.textContent = "Very strong password";
  } else {
    meter.className = "strength-meter-fill strength-very-strong";
    text.textContent = "Excellent password";
  }
}

function showPasswordHints() {
  document.getElementById("passwordHints")?.classList.add("show");
}

function hidePasswordHints() {
  document.getElementById("passwordHints")?.classList.remove("show");
}

// ==================== CONFIRM PASSWORD VALIDATION ====================
function validateConfirmPassword() {
  const password = document.getElementById("password").value;
  const confirm = document.getElementById("confirmPassword").value;
  const confirmField = document.getElementById("confirmPassword");
  const confirmHint = document.getElementById("confirmHint");

  if (!confirmField || !confirmHint) return;

  if (confirm === "") {
    confirmField.classList.remove("is-valid", "is-invalid");
    confirmHint.style.display = "none";
  } else if (password === confirm) {
    confirmField.classList.add("is-valid");
    confirmField.classList.remove("is-invalid");
    confirmHint.style.display = "none";
  } else {
    confirmField.classList.add("is-invalid");
    confirmField.classList.remove("is-valid");
    confirmHint.style.display = "flex";
  }

  updateSubmitButton();
}

// ==================== TOGGLE PASSWORD VISIBILITY ====================
function togglePassword(fieldId, icon) {
  const field = document.getElementById(fieldId);
  if (!field) return;

  const type = field.getAttribute("type") === "password" ? "text" : "password";
  field.setAttribute("type", type);
  icon.classList.toggle("fa-eye");
  icon.classList.toggle("fa-eye-slash");
}

// ==================== UPDATE SUBMIT BUTTON ====================
function updateSubmitButton() {
  const email = document.getElementById("email")?.value || "";
  const password = document.getElementById("password")?.value || "";
  const confirm = document.getElementById("confirmPassword")?.value || "";
  const terms = document.getElementById("terms")?.checked || false;

  const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  const passwordValid =
    password.length >= 8 &&
    /[A-Z]/.test(password) &&
    /[a-z]/.test(password) &&
    /[0-9]/.test(password) &&
    /[!@#$%^&*]/.test(password);
  const confirmValid = password === confirm && confirm !== "";

  const submitBtn = document.getElementById("submitBtn");
  if (!submitBtn) return;

  // Don't enable button if form is already submitting
  if (submitBtn.disabled && submitBtn.classList.contains("btn-loading")) {
    return;
  }

  if (emailValid && passwordValid && confirmValid && terms) {
    submitBtn.disabled = false;
    submitBtn.classList.remove("btn-disabled");
  } else {
    submitBtn.disabled = true;
    submitBtn.classList.add("btn-disabled");
  }
}

// ==================== FORM SUBMISSION WITH ASYNC/AWAIT ====================
async function submitRegistration(formData) {
  try {
    setButtonLoading(true);
    const response = await fetch("/api/register", {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "Content-Type": "application/json",
      },
      body: JSON.stringify(formData),
    });

    const data = await response.json();

    if (!response.ok) {
      throw {
        status: response.status,
        data: data,
      };
    }

    return { success: true, data };
  } catch (error) {
    return { success: false, error };
  } finally {
    setButtonLoading(false);
  }
}

function setButtonLoading(isLoading) {
  const submitBtn = document.getElementById("submitBtn");
  if (!submitBtn) return;

  if (isLoading) {
    submitBtn.disabled = true;
    submitBtn.classList.add("btn-disabled", "btn-loading");
    submitBtn.innerHTML =
      '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creating account...';
  } else {
    submitBtn.disabled = false;
    submitBtn.classList.remove("btn-disabled", "btn-loading");
    submitBtn.innerHTML = "Create Account";
    updateSubmitButton(); // Re-check validation state
  }
}

// ==================== HANDLE FORM SUBMISSION ====================
async function handleSubmit(event) {
  event.preventDefault();

  // Double-check validation before submitting
  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;
  const confirm = document.getElementById("confirmPassword").value;
  const terms = document.getElementById("terms").checked;

  const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  const passwordValid =
    password.length >= 8 &&
    /[A-Z]/.test(password) &&
    /[a-z]/.test(password) &&
    /[0-9]/.test(password) &&
    /[!@#$%^&*]/.test(password);
  const confirmValid = password === confirm && confirm !== "";

  if (!emailValid || !passwordValid || !confirmValid || !terms) {
    showError("Please complete all fields correctly");
    return false;
  }

  // Get selected role
  const roleElement = document.querySelector('input[name="role"]:checked');
  if (!roleElement) {
    showError("Please select a role");
    return false;
  }

  const formData = {
    email: email,
    password: password,
    password_confirmation: confirm,
    role: roleElement.value,
    terms: terms,
  };

  // Show loading state
  setButtonLoading(true);

  if (typeof Toast !== "undefined") {
    Toast.info("Creating your account...");
  }

  // Submit the form
  const result = await submitRegistration(formData);

  // Handle the response
  if (result.success) {
    if (result.data.success) {
      if (typeof Toast !== "undefined") {
        Toast.success(result.data.message ?? "Account created successfully");
      } else {
        alert(result.data.message ?? "Account created successfully");
      }
      // Optional: redirect after successful registration
      window.location.href = "/login?email_sent=1";
    } else {
      // Handle validation errors from server
      if (result.data.errors) {
        Object.values(result.data.errors).forEach((fieldErrors) => {
          fieldErrors.forEach((errMsg) => showError(errMsg));
        });
      } else {
        showError(result.data.message || "Registration failed");
      }
      setButtonLoading(false);
    }
  } else {
    // Handle network or server errors
    console.error("Registration error:", result.error);

    if (result.error.status === 422) {
      // Validation errors
      const serverData = result.error.data || {};

      // If server provides structured errors (field => [msgs])
      if (serverData.errors && typeof serverData.errors === "object") {
        Object.values(serverData.errors).forEach((fieldErrors) => {
          if (Array.isArray(fieldErrors)) {
            fieldErrors.forEach((err) => showError(err));
          } else if (typeof fieldErrors === "string") {
            showError(fieldErrors);
          }
        });
      } else if (serverData.message) {
        showError(serverData.message);
      } else {
        showError("Please check your input and try again");
      }
    } else if (result.error.status === 409) {
      showError("Email already exists");
    } else if (result.error.status >= 500) {
      showError("Server error. Please try again later.");
    } else {
      showError(
        result.message ??
          "Network error. Please check your connection and try again.",
      );
    }

    setButtonLoading(false);
  }

  return false;
}

// Add the missing validateForm function
function validateForm() {
  // Trigger all validations
  validateEmail();
  validatePassword();
  validateConfirmPassword();

  // Return the overall form validity
  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;
  const confirm = document.getElementById("confirmPassword").value;
  const terms = document.getElementById("terms").checked;

  const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  const passwordValid =
    password.length >= 8 &&
    /[A-Z]/.test(password) &&
    /[a-z]/.test(password) &&
    /[0-9]/.test(password) &&
    /[!@#$%^&*]/.test(password);
  const confirmValid = password === confirm && confirm !== "";

  return emailValid && passwordValid && confirmValid && terms;
}

// ==================== GOOGLE SIGN IN ====================
function handleGoogleSignIn() {
  console.log("Google sign-in clicked");

  const selectedRoleRadio = document.querySelector(
    'input[name="role"]:checked',
  );

  if (!selectedRoleRadio) {
    console.log("No role selected");
    const errorMsg =
      "Please select a role (Job Seeker or Employer) before continuing.";
    if (typeof Toast !== "undefined" && Toast.error) {
      Toast.error(errorMsg);
    } else {
      alert(errorMsg);
    }
    return;
  }

  const roleValue = selectedRoleRadio.value; // "jobseeker" or "employer"
  console.log("Selected role:", roleValue);

  // Store role in sessionStorage for later retrieval
  sessionStorage.setItem("google_auth_role", roleValue);

  // Redirect to Google OAuth endpoint with role parameter
  const redirectUrl = `/auth/google?role=${encodeURIComponent(roleValue)}`;
  console.log("Redirecting to:", redirectUrl);
  window.location.href = redirectUrl;
}

function showError(message) {
  if (typeof Toast !== "undefined") {
    Toast.error(message);
  } else {
    alert(message);
  }
}

// ==================== INITIALIZATION ====================
document.addEventListener("DOMContentLoaded", function () {
  // Initial check
  updateSubmitButton();

  // Add input event listeners
  const emailInput = document.getElementById("email");
  if (emailInput) {
    emailInput.addEventListener("input", validateEmail);
    // Add blur event for hideEmailHint
    emailInput.addEventListener("blur", hideEmailHint);
  }

  const passwordInput = document.getElementById("password");
  if (passwordInput) {
    passwordInput.addEventListener("input", validatePassword);
    passwordInput.addEventListener("focus", showPasswordHints);
    passwordInput.addEventListener("blur", hidePasswordHints);
  }

  const confirmInput = document.getElementById("confirmPassword");
  if (confirmInput) {
    confirmInput.addEventListener("input", validateConfirmPassword);
  }

  const termsCheckbox = document.getElementById("terms");
  if (termsCheckbox) {
    termsCheckbox.addEventListener("change", validateForm);
    termsCheckbox.addEventListener("change", updateSubmitButton);
  }

  // Add change event for role radio buttons
  const roleRadios = document.querySelectorAll('input[name="role"]');
  roleRadios.forEach((radio) => {
    radio.addEventListener("change", validateForm);
    radio.addEventListener("change", updateSubmitButton);
  });

  // Pre-fill role from session if coming back from Google
  const savedRole = sessionStorage.getItem("google_auth_role");
  if (savedRole) {
    const radio = document.querySelector(`input[value="${savedRole}"]`);
    if (radio) radio.checked = true;
  }
});

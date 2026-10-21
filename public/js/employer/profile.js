// ==================== WIZARD STATE ====================
let currentStep = 1;
const totalSteps = 6;
let selectedType = null;
let websiteOption = null;
let logoUploaded = false;
let logoFile = null;

// ==================== DOM Elements ====================
const industrySearch = document.getElementById("industrySearch");
const industryDropdown = document.getElementById("industryDropdown");
const industryId = document.getElementById("industryId");
const industrySlug = document.getElementById("industrySlug");
const logoUpload = document.getElementById("logoUpload");
const logoInput = document.getElementById("logoInput");
const logoPreview = document.getElementById("logoPreview");
const logoPreviewImg = document.getElementById("logoPreviewImg");
const logoPlaceholder = document.getElementById("logoPlaceholder");

// ==================== INDUSTRY DROPDOWN FUNCTIONALITY ====================

// Filter industries based on search
function filterIndustries() {
  const options = industryDropdown.querySelectorAll(".industry-option");
  let hasVisible = false;

  options.forEach((option) => {
    const name = option.dataset.name || option.textContent;
    if (name.toLowerCase().includes(industrySearch.value.toLowerCase())) {
      option.style.display = "block";
      hasVisible = true;
    } else {
      option.style.display = "none";
    }
  });

  // Show "no results" if nothing found
  let noResults = industryDropdown.querySelector(".no-results");
  if (!hasVisible) {
    if (!noResults) {
      // Remove any existing no-results first
      const existingNoResults = industryDropdown.querySelector(".no-results");
      if (existingNoResults) existingNoResults.remove();

      noResults = document.createElement("div");
      noResults.className = "industry-option no-results";
      noResults.textContent = "No industries found";
      noResults.style.color = "var(--text-muted)";
      noResults.style.cursor = "default";
      industryDropdown.appendChild(noResults);
    }
  } else if (noResults) {
    noResults.remove();
  }
}

// Select industry
function selectIndustry(option) {
  const id = option.dataset.id;
  const slug = option.dataset.slug;
  const name = option.dataset.name || option.textContent;

  industrySearch.value = name;
  industryId.value = id;
  industrySlug.value = slug;

  // Remove selected class from all options
  industryDropdown.querySelectorAll(".industry-option").forEach((opt) => {
    opt.classList.remove("selected");
  });

  // Add selected class to chosen option
  option.classList.add("selected");

  // Close dropdown
  industryDropdown.classList.remove("active");
  industrySearch.classList.remove("is-invalid");

  // Trigger validation
  if (typeof validateStep === "function") {
    validateStep(currentStep);
  }
}

// Industry search input
if (industrySearch) {
  let searchTimeout;

  industrySearch.addEventListener("input", function (e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      filterIndustries();
      if (!industryDropdown.classList.contains("active")) {
        industryDropdown.classList.add("active");
      }
    }, 300);
  });

  industrySearch.addEventListener("focus", () => {
    filterIndustries();
    industryDropdown.classList.add("active");
  });
}

// Click on industry option
if (industryDropdown) {
  industryDropdown.addEventListener("click", (e) => {
    const option = e.target.closest(".industry-option");
    if (option && !option.classList.contains("no-results")) {
      selectIndustry(option);
    }
  });
}

// Close dropdown when clicking outside
document.addEventListener("click", (e) => {
  if (!e.target.closest(".industry-select")) {
    industryDropdown.classList.remove("active");
  }
});

// ==================== STEP NAVIGATION ====================
function nextStep() {
  if (!validateStep(currentStep)) {
    return;
  }

  if (currentStep < totalSteps) {
    // Hide current step
    document
      .getElementById(`step${currentStep}-content`)
      .classList.add("hidden");

    // Update step indicator
    document.getElementById(`step${currentStep}`).classList.remove("active");
    document.getElementById(`step${currentStep}`).classList.add("completed");

    currentStep++;

    // Show next step
    document
      .getElementById(`step${currentStep}-content`)
      .classList.remove("hidden");
    document.getElementById(`step${currentStep}`).classList.add("active");

    // Update step-specific UI
    updateStepUI();

    // Update button text
    const nextBtn = document.getElementById("nextBtn");
    if (currentStep === totalSteps) {
      nextBtn.innerHTML = 'Submit Profile <i class="fas fa-check"></i>';
    } else {
      nextBtn.innerHTML = 'Continue <i class="fas fa-arrow-right"></i>';
    }

    // Scroll to top
    window.scrollTo({ top: 0, behavior: "smooth" });
  } else if (currentStep === totalSteps) {
    submitProfile();
  }
}

function prevStep() {
  if (currentStep > 1) {
    // Hide current step
    document
      .getElementById(`step${currentStep}-content`)
      .classList.add("hidden");
    document.getElementById(`step${currentStep}`).classList.remove("active");

    currentStep--;

    // Show previous step
    document
      .getElementById(`step${currentStep}-content`)
      .classList.remove("hidden");
    document.getElementById(`step${currentStep}`).classList.add("active");
    document.getElementById(`step${currentStep}`).classList.remove("completed");

    // Update button text
    const nextBtn = document.getElementById("nextBtn");
    if (currentStep === totalSteps) {
      nextBtn.innerHTML = 'Submit Profile <i class="fas fa-check"></i>';
    } else {
      nextBtn.innerHTML = 'Continue <i class="fas fa-arrow-right"></i>';
    }

    // Scroll to top
    window.scrollTo({ top: 0, behavior: "smooth" });
  }
}

function goToStep(step) {
  // Hide all steps
  for (let i = 1; i <= totalSteps; i++) {
    document.getElementById(`step${i}-content`).classList.add("hidden");
    document.getElementById(`step${i}`).classList.remove("active");
  }

  // Mark completed steps
  for (let i = 1; i < step; i++) {
    document.getElementById(`step${i}`).classList.add("completed");
  }

  currentStep = step;

  // Show target step
  document
    .getElementById(`step${currentStep}-content`)
    .classList.remove("hidden");
  document.getElementById(`step${currentStep}`).classList.add("active");

  // Update step UI
  updateStepUI();

  // Update button text
  const nextBtn = document.getElementById("nextBtn");
  if (currentStep === totalSteps) {
    nextBtn.innerHTML = 'Submit Profile <i class="fas fa-check"></i>';
  } else {
    nextBtn.innerHTML = 'Continue <i class="fas fa-arrow-right"></i>';
  }
}

function updateStepUI() {
  if (currentStep === 2) {
    // Show appropriate name field based on type
    if (selectedType === "company") {
      document.getElementById("company-fields").style.display = "block";
      document.getElementById("individual-fields").style.display = "none";
    } else {
      document.getElementById("company-fields").style.display = "none";
      document.getElementById("individual-fields").style.display = "block";
    }
  } else if (currentStep === 6) {
    updateReview();
  }
}

// ==================== ACCOUNT TYPE ====================
function selectType(type) {
  selectedType = type;

  // Update UI
  document.getElementById("company-card").classList.remove("selected");
  document.getElementById("individual-card").classList.remove("selected");

  if (type === "company") {
    document.getElementById("company-card").classList.add("selected");
  } else {
    document.getElementById("individual-card").classList.add("selected");
  }

  // Enable next button
  document.getElementById("nextBtn").disabled = false;
}

// ==================== VALIDATION ====================
function validateStep(step) {
  switch (step) {
    case 1:
      if (!selectedType) {
        Toast.error("Please select an account type to continue");
        return false;
      }
      return true;

    case 2:
      // Validate name based on type
      if (selectedType === "company") {
        const companyName = document.getElementById("companyName").value.trim();
        if (!companyName) {
          document.getElementById("companyName").classList.add("is-invalid");
          Toast.error("Company name is required");
          return false;
        }
        document.getElementById("companyName").classList.remove("is-invalid");
      } else {
        const individualName = document
          .getElementById("individualName")
          .value.trim();
        if (!individualName) {
          document.getElementById("individualName").classList.add("is-invalid");
          Toast.error("Your name is required");
          return false;
        }
        document
          .getElementById("individualName")
          .classList.remove("is-invalid");
      }

      // Validate description
      if (!document.getElementById("description").value.trim()) {
        Toast.error("Please tell us about yourself/your company");
        return false;
      }

      // Validate industry
      if (!industryId.value) {
        industrySearch.classList.add("is-invalid");
        Toast.error("Please select an industry");
        return false;
      }
      industrySearch.classList.remove("is-invalid");

      return true;

    case 3:
      // Validate email
      const email = document.getElementById("contactEmail").value.trim();
      if (!email) {
        document.getElementById("contactEmail").classList.add("is-invalid");
        Toast.error("Email is required");
        return false;
      }
      if (!isValidEmail(email)) {
        document.getElementById("contactEmail").classList.add("is-invalid");
        Toast.error("Please enter a valid email address");
        return false;
      }
      document.getElementById("contactEmail").classList.remove("is-invalid");
      return true;

    default:
      return true;
  }
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// ==================== WEBSITE OPTION ====================
function setWebsiteOption(option) {
  websiteOption = option;

  document.getElementById("website-yes").classList.remove("selected");
  document.getElementById("website-no").classList.remove("selected");

  if (option === "yes") {
    document.getElementById("website-yes").classList.add("selected");
    document.getElementById("websiteInput").style.display = "block";
  } else {
    document.getElementById("website-no").classList.add("selected");
    document.getElementById("websiteInput").style.display = "none";
    document.getElementById("website").value = ""; // Clear website field
    // Auto advance to next step
    setTimeout(() => {
      if (currentStep === 5) {
        nextStep();
      }
    }, 300);
  }
}

// ==================== LOGO UPLOAD - REAL IMPLEMENTATION ====================
if (logoUpload) {
  // Create hidden file input if it doesn't exist
  if (!logoInput) {
    const input = document.createElement("input");
    input.type = "file";
    input.id = "logoInput";
    input.name = "avatar";
    input.accept = "image/jpeg,image/png,image/gif,image/webp";
    input.style.display = "none";
    document.body.appendChild(input);
  }

  const fileInput = document.getElementById("logoInput");

  // Click on upload button triggers file selection
  logoUpload.addEventListener("click", function () {
    fileInput.click();
  });

  // Handle file selection
  fileInput.addEventListener("change", function (e) {
    const file = e.target.files[0];

    if (!file) return;

    // Validate file type
    const validTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
    if (!validTypes.includes(file.type)) {
      Toast.error("Please select a valid image file (JPEG, PNG, GIF, or WEBP)");
      fileInput.value = ""; // Clear the input
      return;
    }

    // Validate file size (max 2MB)
    const maxSize = 2 * 1024 * 1024; // 2MB
    if (file.size > maxSize) {
      Toast.error("Logo must be less than 2MB");
      fileInput.value = "";
      return;
    }

    // Read and preview the image
    const reader = new FileReader();

    reader.onload = function (event) {
      // Update preview
      if (logoPreviewImg) {
        logoPreviewImg.src = event.target.result;
        logoPreviewImg.style.display = "block";
      }

      if (logoPlaceholder) {
        logoPlaceholder.style.display = "none";
      }

      // Update preview div styling
      if (logoPreview) {
        logoPreview.innerHTML = "";
        logoPreview.style.background = "transparent";
        logoPreview.style.color = "inherit";

        if (logoPreviewImg && !logoPreview.contains(logoPreviewImg)) {
          logoPreview.appendChild(logoPreviewImg);
        }
      }

      // Store the file for submission
      logoFile = file;
      logoUploaded = true;

      // Show remove button
      const removeBtn = document.getElementById("removeLogoBtn");
      if (removeBtn) {
        removeBtn.style.display = "inline-block";
      }

      Toast.success("Logo uploaded successfully!");

      // Auto advance to next step if on step 4
      setTimeout(() => {
        if (currentStep === 4) {
          nextStep();
        }
      }, 1000);
    };

    reader.readAsDataURL(file);
  });
}

function skipLogo() {
  logoUploaded = false;
  logoFile = null;

  // Reset preview
  if (logoPreviewImg) {
    logoPreviewImg.src = "";
    logoPreviewImg.style.display = "none";
  }

  if (logoPlaceholder) {
    logoPlaceholder.style.display = "flex";
  }

  if (logoPreview) {
    logoPreview.innerHTML = '<i class="fas fa-building"></i>';
    logoPreview.style.background = "var(--bg-light)";
    logoPreview.style.color = "var(--text-muted)";
  }

  // Hide remove button
  const removeBtn = document.getElementById("removeLogoBtn");
  if (removeBtn) {
    removeBtn.style.display = "none";
  }

  Toast.info("You can add your logo later from settings");

  // Auto advance to next step
  nextStep();
}

function removeLogo() {
  logoUploaded = false;
  logoFile = null;

  // Clear file input
  const fileInput = document.getElementById("logoInput");
  if (fileInput) {
    fileInput.value = "";
  }

  // Reset preview
  if (logoPreviewImg) {
    logoPreviewImg.src = "";
    logoPreviewImg.style.display = "none";
  }

  if (logoPlaceholder) {
    logoPlaceholder.style.display = "flex";
  }

  if (logoPreview) {
    logoPreview.innerHTML = '<i class="fas fa-building"></i>';
    logoPreview.style.background = "var(--bg-light)";
    logoPreview.style.color = "var(--text-muted)";
  }

  // Hide remove button
  const removeBtn = document.getElementById("removeLogoBtn");
  if (removeBtn) {
    removeBtn.style.display = "none";
  }

  Toast.info("Logo removed");
}

// ==================== REVIEW ====================
function updateReview() {
  const type =
    selectedType === "company" ? "Company Account" : "Individual Employer";
  const name =
    selectedType === "company"
      ? document.getElementById("companyName").value
      : document.getElementById("individualName").value;
  const description = document.getElementById("description").value;
  const industry = document.getElementById("industrySearch").value;
  const email = document.getElementById("contactEmail").value;
  const phone = document.getElementById("contactPhone").value || "Not provided";
  const contactName =
    document.getElementById("contactName").value || "Not provided";
  const website = document.getElementById("website").value || "Not provided";

  document.getElementById("review-type").textContent = type;
  document.getElementById("review-name").textContent = name;
  document.getElementById("review-description").textContent =
    description.substring(0, 100) + (description.length > 100 ? "..." : "");
  document.getElementById("review-industry").textContent = industry;
  document.getElementById("review-email").textContent = email;
  document.getElementById("review-phone").innerHTML =
    phone === "Not provided" ? "<small>Not provided</small>" : phone;
  document.getElementById("review-contact-name").innerHTML =
    contactName === "Not provided"
      ? "<small>Not provided</small>"
      : contactName;
  document.getElementById("review-logo").textContent = logoUploaded
    ? "✓ Uploaded"
    : "Not uploaded";
  document.getElementById("review-website").innerHTML =
    website === "Not provided" ? "<small>Not provided</small>" : website;

  // Show logo preview in review if available
  const reviewLogoPreview = document.getElementById("review-logo-preview");
  if (reviewLogoPreview && logoPreviewImg && logoPreviewImg.src) {
    reviewLogoPreview.innerHTML = `<img src="${logoPreviewImg.src}" alt="Logo" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">`;
  }
}

// ==================== SUBMIT ====================
async function submitProfile() {
  // Show loading spinner
  const spinner = document.createElement("div");
  spinner.className = "spinner-overlay";
  spinner.innerHTML = `
        <div class="spinner-wrapper">
            <div class="spinner"></div>
            <div class="spinner-message">Creating your profile...</div>
        </div>
    `;
  document.body.appendChild(spinner);

  // Create FormData object for multipart/form-data submission
  const formData = new FormData();

  // Add all profile data
  formData.append("type", selectedType);
  formData.append(
    "name",
    selectedType === "company"
      ? document.getElementById("companyName").value
      : document.getElementById("individualName").value,
  );
  formData.append("description", document.getElementById("description").value);
  formData.append("industry_id", industryId.value);
  formData.append("industry_slug", industrySlug.value);
  formData.append(
    "contact_email",
    document.getElementById("contactEmail").value,
  );
  formData.append(
    "contact_phone",
    document.getElementById("contactPhone").value || "",
  );
  formData.append(
    "contact_name",
    document.getElementById("contactName").value || "",
  );
  formData.append("website", document.getElementById("website").value || "");

  // Append logo file if uploaded
  if (logoFile) {
    formData.append("avatar", logoFile);
  }

  formData.append("has_logo", logoUploaded ? "1" : "0");

  console.log(
    "Submitting profile with logo:",
    logoFile ? logoFile.name : "No logo",
  );

  try {
    // Send AJAX request with file upload
    const response = await fetch("/api/employer/profile-setup", {
      method: "POST",
      body: formData,
      // Don't set Content-Type header - browser will set it with boundary for multipart/form-data
    });

    const result = await response.json();

    // Remove spinner
    document.body.removeChild(spinner);

    if (response.ok && result.success) {
      Toast.success("Profile created successfully! Redirecting...");

      setTimeout(() => {
        window.location.href = "/employer/dashboard";
      }, 1500);
    } else {
      Toast.error(result.message || "Failed to create profile");
    }
  } catch (error) {
    console.error("Error submitting profile:", error);
    document.body.removeChild(spinner);
    Toast.error("Network error. Please try again.");
  }
}

// ==================== INITIALIZATION ====================
document.addEventListener("DOMContentLoaded", function () {
  const existingProfile = window.employerProfileSetup;

  if (existingProfile) {
    if (["company", "individual"].includes(existingProfile.type)) {
      selectType(existingProfile.type);
    }

    const companyName = document.getElementById("companyName");
    const individualName = document.getElementById("individualName");
    if (companyName) companyName.value = existingProfile.name || "";
    if (individualName) individualName.value = existingProfile.name || "";

    document.getElementById("description").value =
      existingProfile.description || "";
    document.getElementById("contactEmail").value =
      existingProfile.contactEmail || "";
    document.getElementById("contactPhone").value =
      existingProfile.contactPhone || "";
    document.getElementById("contactName").value =
      existingProfile.contactName || "";
    document.getElementById("website").value = existingProfile.website || "";

    if (existingProfile.industryId) {
      industrySearch.value = existingProfile.industryName || "";
      industryId.value = existingProfile.industryId;
      industrySlug.value = existingProfile.industrySlug || "";
      const industryOption = Array.from(
        industryDropdown.querySelectorAll(".industry-option"),
      ).find(
        (option) => option.dataset.id === String(existingProfile.industryId),
      );
      if (industryOption) {
        selectIndustry(industryOption);
      }
    }

    if (existingProfile.website) {
      setWebsiteOption("yes");
    }

    if (existingProfile.avatar && logoPreviewImg) {
      logoPreviewImg.src = existingProfile.avatar;
      logoPreviewImg.style.display = "block";
      if (logoPlaceholder) logoPlaceholder.style.display = "none";
      logoUploaded = true;
    }
  }

  // Hide all steps except first
  for (let i = 2; i <= totalSteps; i++) {
    const stepContent = document.getElementById(`step${i}-content`);
    if (stepContent) {
      stepContent.classList.add("hidden");
    }
  }

  // Disable next button initially
  const nextBtn = document.getElementById("nextBtn");
  if (nextBtn) {
    nextBtn.disabled = true;
  }

  // Add enter key support
  document.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      const nextBtn = document.getElementById("nextBtn");
      if (nextBtn && !nextBtn.disabled) {
        nextStep();
      }
    }
  });
});

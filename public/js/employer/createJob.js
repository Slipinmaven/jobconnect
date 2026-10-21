// Job Posting Wizard JavaScript
let currentStep = 0;
const totalSteps = 5;
let isEditing = false;
let jobId = null;

let requirements = [];
let responsibilities = [];
let selectedCategories = [];
let availableCategories = [];

const steps = document.querySelectorAll(".step");
const stepContents = document.querySelectorAll(".step-content");
const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");
const actionButtons = document.getElementById("actionButtons");
const progressLine = document.getElementById("progressLine");

// API Base URL
const API_BASE_URL = "/api";

// ==================== INITIALIZATION ====================

document.addEventListener("DOMContentLoaded", async function () {
  // Check if we're editing an existing job
  const pathParts = window.location.pathname.split("/");
  const editId = pathParts[pathParts.length - 1]; // "123" or "create"

  if (editId && !isNaN(editId)) {
    isEditing = true;
    jobId = parseInt(editId);
  } else {
    isEditing = false;
  }

  // Load categories first
  await fetchCategories();

  // If editing, load job data (which will populate the form)
  if (isEditing) {
    await loadJobData(jobId);
  }

  // Initialize event listeners
  initEventListeners();

  // Render initial lists
  renderRequirements();
  renderResponsibilities();
  updateProgress();

  // Set default job type to Full Time
  setDefaultJobType();

  // Initialize salary sync
  initSalarySync();

  // Set min date for deadline picker to today
  setMinDateForDeadline();
});

// ==================== SET MIN DATE FOR DEADLINE ====================
function setMinDateForDeadline() {
  const deadlineInput = document.getElementById("applicationDeadline");
  if (deadlineInput) {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, "0");
    const dd = String(today.getDate()).padStart(2, "0");
    const todayFormatted = `${yyyy}-${mm}-${dd}`;
    deadlineInput.setAttribute("min", todayFormatted);

    deadlineInput.addEventListener("change", function () {
      const selectedDate = this.value;
      if (selectedDate && selectedDate < todayFormatted) {
        Toast.error(
          "Please select today's date or a future date for the application deadline.",
        );
        this.value = "";
      }
    });
  }
}

// ==================== DEFAULT JOB TYPE ====================
function setDefaultJobType() {
  const defaultCard = document.querySelector(
    '.option-card[data-type="full_time"]',
  );
  if (defaultCard && !document.querySelector(".option-card.selected")) {
    defaultCard.classList.add("selected");
  }
}

// ==================== SALARY SYNC ====================
function initSalarySync() {
  const minInput = document.getElementById("salaryMinInput");
  const maxInput = document.getElementById("salaryMaxInput");
  const minValueSpan = document.getElementById("minValue");
  const maxValueSpan = document.getElementById("maxValue");

  function updateDisplays() {
    let minVal = parseInt(minInput?.value) || 0;
    let maxVal = parseInt(maxInput?.value) || 0;
    if (minValueSpan) minValueSpan.innerText = minVal.toLocaleString();
    if (maxValueSpan) maxValueSpan.innerText = maxVal.toLocaleString();
  }

  if (minInput) minInput.addEventListener("input", updateDisplays);
  if (maxInput) maxInput.addEventListener("input", updateDisplays);
  updateDisplays();
}

// ==================== SALARY VALIDATION ====================
function validateSalaryRange() {
  const minVal =
    parseInt(document.getElementById("salaryMinInput")?.value) || 0;
  const maxVal =
    parseInt(document.getElementById("salaryMaxInput")?.value) || 0;

  if (minVal === 0 && maxVal === 0) {
    return { valid: true, message: null, requiresReview: false };
  }

  if (minVal > 0 && maxVal === 0) {
    return {
      valid: false,
      message: "Please enter a maximum salary amount.",
      requiresReview: false,
    };
  }

  if (minVal === 0 && maxVal > 0) {
    if (maxVal > 50000000) {
      return {
        valid: true,
        message:
          "It appears the salary you entered is unusually high. This job will be reviewed by our team before it is published. Thank you for understanding.",
        requiresReview: true,
      };
    }
    return { valid: true, message: null, requiresReview: false };
  }

  if (minVal > maxVal) {
    return {
      valid: false,
      message: "Minimum salary cannot be greater than maximum salary.",
      requiresReview: false,
    };
  }

  if (minVal > 50000000 || maxVal > 50000000) {
    return {
      valid: true,
      message:
        "The salary you entered is unusually high. No worries! This job will be reviewed by our team before it is published. Thank you for understanding.",
      requiresReview: true,
    };
  }

  return { valid: true, message: null, requiresReview: false };
}

// ==================== HIGH SALARY POPUP ====================
function showHighSalaryPopup(message, onClose) {
  const existing = document.querySelector(".high-salary-popup-overlay");
  if (existing) existing.remove();

  const overlay = document.createElement("div");
  overlay.className = "high-salary-popup-overlay";
  overlay.innerHTML = `
    <div class="high-salary-popup">
      <div class="popup-icon">
        <i class="fas fa-info"></i>
      </div>
      <h3>🤔 Mmhhh!</h3>
      <div class="popup-message">${message}</div>
      <button class="popup-ok-btn">OK, Continue</button>
    </div>
  `;
  document.body.appendChild(overlay);
  setTimeout(() => overlay.classList.add("show"), 10);

  const okBtn = overlay.querySelector(".popup-ok-btn");
  okBtn.addEventListener("click", () => {
    overlay.classList.remove("show");
    setTimeout(() => {
      overlay.remove();
      if (onClose) onClose();
    }, 300);
  });

  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) {
      overlay.classList.remove("show");
      setTimeout(() => {
        overlay.remove();
        if (onClose) onClose();
      }, 300);
    }
  });
}

// ==================== API CALLS ====================

/**
 * Fetch categories from API - GET /api/categories
 */
async function fetchCategories() {
  try {
    const response = await fetch(`${API_BASE_URL}/categories`);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    let categories = [];

    // Your API returns: { success: true, statusCode: 200, message: "Success", data: { categories: [...], total: 210 } }
    if (result.success && result.data && result.data.categories) {
      categories = result.data.categories;
    }
    // Fallback for other possible structures
    else if (result.data && Array.isArray(result.data)) {
      categories = result.data;
    } else if (Array.isArray(result)) {
      categories = result;
    } else if (result.categories && Array.isArray(result.categories)) {
      categories = result.categories;
    } else {
      console.error("Unexpected API response format:", result);
      categories = [];
    }

    if (!Array.isArray(categories)) {
      console.error("Categories is not an array:", categories);
      categories = [];
    }

    // Transform to the format needed for the UI
    const categoryList = categories.map((cat) => ({
      id: cat.id,
      name: cat.name,
      slug: cat.slug || cat.name.toLowerCase().replace(/\s+/g, "-"),
    }));

    console.log(`Loaded ${categoryList.length} categories`);
    availableCategories = categoryList;
    populateCategories(availableCategories);
  } catch (error) {
    console.error("Error fetching categories:", error);
    availableCategories = [];
    populateCategories([]);
  }
}

/**
 * Load job data for editing - GET /api/employer/job/{id}
 */
async function loadJobData(id) {
  try {
    const response = await fetch(`${API_BASE_URL}/employer/job/${id}`);
    const result = await response.json();
    console.log("Load job data API response:", result);

    // The response structure is:
    // { success: true, statusCode: 200, message: "...", data: { ... job object ... } }
    if (result.success && result.data) {
      const job = result.data; // Job object directly
      populateFormWithJobData(job);
    } else {
      console.error("Unexpected response format:", result);
      Toast.error("Failed to load job data");
    }
  } catch (error) {
    console.error("Error loading job data:", error);
    Toast.error("An error occurred while loading job data");
  }
}

/**
 * Create job via API - POST /api/jobs
 */
async function createJob(payload) {
  try {
    const response = await fetch(`${API_BASE_URL}/jobs`, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "Content-Type": "application/json",
      },
      credentials: "include",
      body: JSON.stringify(payload),
    });

    const result = await response.json();
    console.log("Create job API response:", result);

    if (result.success) {
      Toast.success("Job created successfully! Redirecting to jobs page...");
      setTimeout(() => {
        window.location.href = "/employer/jobs";
      }, 1000);
      return true;
    } else {
      Toast.error(result.message || "Failed to create job");
      return false;
    }
  } catch (error) {
    console.error("Error creating job:", error);
    Toast.error("An error occurred while creating the job");
    return false;
  }
}

/**
 * Update job via API - PUT /api/jobs/{id}
 */
async function updateJob(payload) {
  try {
    Spinner.show(isEditing ? "Updating job..." : "Creating job...");
    const response = await fetch(`${API_BASE_URL}/jobs/${jobId}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
      },
      credentials: "include",
      body: JSON.stringify(payload),
    });

    const result = await response.json();

    if (result.success) {
      Toast.success("Job updated successfully! Redirecting to jobs page...");
      setTimeout(() => {
        window.location.href = "/employer/jobs";
      }, 1000);
      return true;
    } else {
      Toast.error(result.message || "Failed to update job");
      return false;
    }
  } catch (error) {
    console.error("Error updating job:", error);
    Toast.error("An error occurred while updating the job");
    return false;
  } finally {
    Spinner.remove();
  }
}

// ==================== CATEGORY FUNCTIONS ====================

function populateCategories(categories) {
  const container = document.getElementById("categoriesContainer");
  if (!container) return;

  container.innerHTML = `
    <div class="category-hint">
      <i class="fas fa-tags"></i>
      <span>Popular categories: Click to select</span>
    </div>
  `;

  // Display first 12 categories
  const categoriesToShow = categories.slice(0, 12);

  categoriesToShow.forEach((category) => {
    const chip = document.createElement("div");
    chip.className = `category-chip ${
      selectedCategories.some((c) => c.id === category.id) ? "selected" : ""
    }`;
    chip.textContent = category.name;
    chip.onclick = () => toggleCategory(category);
    container.appendChild(chip);
  });

  if (categories.length > 12) {
    const viewAllBtn = document.createElement("div");
    viewAllBtn.className = "category-chip";
    viewAllBtn.innerHTML = '<i class="fas fa-ellipsis-h"></i> View All';
    viewAllBtn.onclick = () => showAllCategoriesModal();
    container.appendChild(viewAllBtn);
  }

  renderSelectedCategories();
  setupCategorySearch();
}

function showAllCategoriesModal() {
  const modalHtml = `
    <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;" onclick="closeCategoryModal(event)">
      <div style="background: var(--bg-primary); border-radius: 1rem; max-width: 500px; width: 90%; max-height: 80vh; overflow: auto; padding: 1.5rem;" onclick="event.stopPropagation()">
        <h3 style="margin-bottom: 1rem;">All Categories</h3>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
          ${availableCategories
            .map(
              (cat) => `
            <div class="category-chip ${
              selectedCategories.some((c) => c.id === cat.id) ? "selected" : ""
            }" onclick="toggleCategory({id: ${cat.id}, name: '${cat.name.replace(/'/g, "\\'")}'}); document.querySelector('.category-modal')?.remove();">
              ${cat.name}
            </div>
          `,
            )
            .join("")}
        </div>
        <button onclick="closeCategoryModal()" style="margin-top: 1rem; padding: 0.5rem 1rem; background: var(--primary-color); color: white; border: none; border-radius: 0.5rem; cursor: pointer;">Close</button>
      </div>
    </div>
  `;
  const modalDiv = document.createElement("div");
  modalDiv.className = "category-modal";
  modalDiv.innerHTML = modalHtml;
  document.body.appendChild(modalDiv);
}

function closeCategoryModal(event) {
  const modal = document.querySelector(".category-modal");
  if (modal) modal.remove();
}

function toggleCategory(category) {
  const index = selectedCategories.findIndex((c) => c.id === category.id);
  if (index > -1) {
    selectedCategories.splice(index, 1);
  } else {
    selectedCategories.push(category);
  }
  renderSelectedCategories();
  populateCategories(availableCategories);
}

function renderSelectedCategories() {
  const container = document.getElementById("selectedCategories");
  if (!container) return;

  if (selectedCategories.length === 0) {
    container.innerHTML =
      '<div style="color: var(--text-muted); font-size: 0.875rem; padding: 0.5rem;"><i class="fas fa-info-circle"></i> No categories selected yet. Click on categories above to add them.</div>';
    return;
  }

  container.innerHTML = `
    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.5rem;">Selected Categories (${selectedCategories.length}):</div>
    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
      ${selectedCategories
        .map(
          (cat) => `
        <div class="selected-category-tag">
          ${cat.name || cat}
          <span class="remove-tag" onclick="toggleCategory(${JSON.stringify(
            cat,
          ).replace(/"/g, "&quot;")})">&times;</span>
        </div>
      `,
        )
        .join("")}
    </div>
  `;
}

function setupCategorySearch() {
  const searchInput = document.getElementById("categorySearch");
  const suggestionsDropdown = document.getElementById("suggestionsDropdown");

  if (!searchInput) return;

  searchInput.addEventListener("input", function (e) {
    const searchTerm = e.target.value.toLowerCase().trim();

    if (searchTerm === "") {
      suggestionsDropdown.classList.remove("show");
      return;
    }

    const filtered = availableCategories.filter(
      (cat) =>
        cat.name.toLowerCase().includes(searchTerm) &&
        !selectedCategories.some((c) => c.id === cat.id),
    );

    if (filtered.length > 0) {
      suggestionsDropdown.innerHTML = filtered
        .map(
          (cat) => `
        <div class="suggestion-item" onclick="toggleCategory({id: ${cat.id}, name: '${cat.name.replace(/'/g, "\\'")}'}); document.getElementById('categorySearch').value = ''; suggestionsDropdown.classList.remove('show');">
          <i class="fas fa-tag"></i> ${cat.name}
        </div>
      `,
        )
        .join("");
      suggestionsDropdown.classList.add("show");
    } else {
      suggestionsDropdown.classList.remove("show");
    }
  });

  document.addEventListener("click", function (e) {
    if (
      !searchInput.contains(e.target) &&
      !suggestionsDropdown.contains(e.target)
    ) {
      suggestionsDropdown.classList.remove("show");
    }
  });
}

// ==================== FORM POPULATION ====================

function populateFormWithJobData(job) {
  // Step 1: Job Basics
  document.getElementById("title").value = job.title || "";
  document.getElementById("description").value = job.description || "";
  updateSlugPreview();

  // Requirements & Responsibilities
  if (job.requirements) {
    requirements = Array.isArray(job.requirements)
      ? job.requirements
      : typeof job.requirements === "string"
        ? job.requirements.split("\n").filter((r) => r.trim())
        : [];
  } else {
    requirements = [];
  }

  if (job.responsibilities) {
    responsibilities = Array.isArray(job.responsibilities)
      ? job.responsibilities
      : typeof job.responsibilities === "string"
        ? job.responsibilities.split("\n").filter((r) => r.trim())
        : [];
  } else {
    responsibilities = [];
  }

  renderRequirements();
  renderResponsibilities();

  // Step 3: Employment Details
  const jobTypeMap = {
    full_time: "full_time",
    part_time: "part_time",
    contract: "contract",
    internship: "internship",
  };
  const jobType = jobTypeMap[job.job_type];
  if (jobType) {
    document.querySelectorAll(".option-card").forEach((card) => {
      card.classList.remove("selected");
      if (card.dataset.type === jobType) card.classList.add("selected");
    });
  }

  if (job.experience_level) {
    document.getElementById("experience_level").value = job.experience_level;
  } else {
    document.getElementById("experience_level").value = "";
  }

  document.getElementById("is_remote").checked = job.is_remote == 1;
  document.getElementById("requires_resume").checked = job.requires_resume == 1;

  // Step 4: Compensation
  if (job.salary_min && job.salary_min > 0) {
    document.getElementById("salaryMinInput").value = job.salary_min;
    document.getElementById("minValue").textContent =
      job.salary_min.toLocaleString();
  } else {
    document.getElementById("salaryMinInput").value = 0;
    document.getElementById("minValue").textContent = "0";
  }

  if (job.salary_max && job.salary_max > 0) {
    document.getElementById("salaryMaxInput").value = job.salary_max;
    document.getElementById("maxValue").textContent =
      job.salary_max.toLocaleString();
  } else {
    document.getElementById("salaryMaxInput").value = 0;
    document.getElementById("maxValue").textContent = "0";
  }

  // Application Deadline (expires_at)
  if (job.expires_at) {
    // Format YYYY-MM-DD from expires_at (e.g., "2026-03-25 00:00:00")
    const deadlineDate = job.expires_at.split(" ")[0];
    document.getElementById("applicationDeadline").value = deadlineDate;
  } else {
    document.getElementById("applicationDeadline").value = "";
  }

  // Allow past deadline
  document.getElementById("allowPastDeadline").checked =
    job.allow_past_deadline == 1;

  // Categories: API returns array of objects with category_id
  if (job.categories && job.categories.length > 0) {
    // job.categories = [ { job_id, category_id, is_primary, ... }, ... ]
    const categoryIds = job.categories.map((cat) => cat.category_id);
    // Map to full category objects using availableCategories
    selectedCategories = categoryIds
      .map((catId) => availableCategories.find((c) => c.id === catId))
      .filter((cat) => cat); // Remove any that weren't found

    console.log("Loaded categories:", selectedCategories);
  } else {
    selectedCategories = [];
  }

  // Re-render category chips and selected list
  populateCategories(availableCategories);
  renderSelectedCategories();
}

// ==================== REQUIREMENTS & RESPONSIBILITIES ====================

function renderRequirements() {
  const container = document.getElementById("requirementsList");
  if (!container) return;

  container.innerHTML =
    requirements.length === 0
      ? '<div class="empty-list" style="padding: 2rem; text-align: center; color: var(--text-muted);">No requirements added yet. Add some above.</div>'
      : requirements
          .map(
            (req, idx) => `
            <div class="list-item">
                <div class="list-item-content">
                    <i class="fas fa-check-circle"></i>
                    <span class="list-item-text">${escapeHtml(req)}</span>
                </div>
                <div class="list-item-actions">
                    <button onclick="removeRequirementAtIndex(${idx})">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        `,
          )
          .join("");
}

function renderResponsibilities() {
  const container = document.getElementById("responsibilitiesList");
  if (!container) return;

  container.innerHTML =
    responsibilities.length === 0
      ? '<div class="empty-list" style="padding: 2rem; text-align: center; color: var(--text-muted);">No responsibilities added yet. Add some above.</div>'
      : responsibilities
          .map(
            (resp, idx) => `
            <div class="list-item">
                <div class="list-item-content">
                    <i class="fas fa-chevron-right"></i>
                    <span class="list-item-text">${escapeHtml(resp)}</span>
                </div>
                <div class="list-item-actions">
                    <button onclick="removeResponsibilityAtIndex(${idx})">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        `,
          )
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

window.addRequirement = function () {
  const val = document.getElementById("newRequirement").value.trim();
  if (val) {
    requirements.push(val);
    renderRequirements();
    document.getElementById("newRequirement").value = "";
  }
};

window.removeRequirementAtIndex = function (i) {
  requirements.splice(i, 1);
  renderRequirements();
};

window.addResponsibility = function () {
  const val = document.getElementById("newResponsibility").value.trim();
  if (val) {
    responsibilities.push(val);
    renderResponsibilities();
    document.getElementById("newResponsibility").value = "";
  }
};

window.removeResponsibilityAtIndex = function (i) {
  responsibilities.splice(i, 1);
  renderResponsibilities();
};

// ==================== STEP NAVIGATION ====================

function updateProgress() {
  const percent = (currentStep / (totalSteps - 1)) * 100;
  if (progressLine) progressLine.style.width = `${percent}%`;

  steps.forEach((step, index) => {
    if (index < currentStep) {
      step.classList.add("completed");
      step.classList.remove("active");
    } else if (index === currentStep) {
      step.classList.add("active");
      step.classList.remove("completed");
    } else {
      step.classList.remove("active", "completed");
    }
  });

  if (currentStep === totalSteps - 1) {
    if (nextBtn) nextBtn.style.display = "none";
    if (actionButtons) actionButtons.style.display = "flex";
  } else {
    if (nextBtn) nextBtn.style.display = "block";
    if (actionButtons) actionButtons.style.display = "none";
  }
}

function showStep(step) {
  stepContents.forEach((c, i) => {
    if (i === step) c.classList.add("active");
    else c.classList.remove("active");
  });
  if (prevBtn) prevBtn.disabled = step === 0;
  updateProgress();
  if (step === totalSteps - 1) updateReview();
}

function nextStep() {
  if (currentStep < totalSteps - 1) {
    if (currentStep === 3) {
      const validation = validateSalaryRange();
      if (!validation.valid) {
        Toast.error(validation.message);
        return;
      }
      if (validation.requiresReview) {
        showHighSalaryPopup(validation.message, () => {
          currentStep++;
          showStep(currentStep);
        });
        return;
      }
    } else {
      if (!validateStep(currentStep)) {
        return;
      }
    }
    currentStep++;
    showStep(currentStep);
  }
}

function prevStep() {
  if (currentStep > 0) {
    currentStep--;
    showStep(currentStep);
  }
}

// ==================== STEP VALIDATION ====================

function validateStep(step) {
  switch (step) {
    case 0: // Job Basics
      const title = document.getElementById("title").value.trim();
      const description = document.getElementById("description").value.trim();

      if (!title) {
        Toast.error("Please enter a job title");
        return false;
      }
      if (!description) {
        Toast.error("Please enter a job description");
        return false;
      }
      return true;

    case 1: // Requirements & Duties (optional)
      return true;

    case 2: // Employment Details
      const selectedJobType = document.querySelector(".option-card.selected");
      if (!selectedJobType) {
        Toast.error("Please select a job type");
        return false;
      }
      return true;

    default:
      return true;
  }
}

// ==================== REVIEW SECTION ====================

function collectData(status) {
  const jobTypeMap = {
    full_time: "Full Time",
    part_time: "Part Time",
    contract: "Contract",
    internship: "Internship",
  };

  const selectedJobType =
    document.querySelector(".option-card.selected")?.dataset.type ||
    "full_time";

  let salaryMin =
    parseInt(document.getElementById("salaryMinInput")?.value) || 0;
  let salaryMax =
    parseInt(document.getElementById("salaryMaxInput")?.value) || 0;

  salaryMin = salaryMin > 0 ? salaryMin : null;
  salaryMax = salaryMax > 0 ? salaryMax : null;

  const deadline =
    document.getElementById("applicationDeadline")?.value || null;
  const allowPastDeadline =
    document.getElementById("allowPastDeadline")?.checked || false;

  return {
    id: jobId,
    title: document.getElementById("title").value,
    description: document.getElementById("description").value,
    requirements: requirements,
    responsibilities: responsibilities,
    job_type: selectedJobType,
    job_type_display: jobTypeMap[selectedJobType] || "Full Time",
    experience_level: document.getElementById("experience_level").value || null,
    experience_level_display:
      document.getElementById("experience_level").options[
        document.getElementById("experience_level").selectedIndex
      ]?.text || "Not specified",
    is_remote: document.getElementById("is_remote").checked,
    salary_min: salaryMin,
    salary_max: salaryMax,
    currency: "KES",
    requires_resume: document.getElementById("requires_resume").checked,
    categories: selectedCategories,
    application_deadline: deadline,
    allow_past_deadline: allowPastDeadline,
    status: status,
  };
}

function updateReview() {
  const data = collectData("draft");

  document.getElementById("reviewContent").innerHTML = `
    <div class="review-card">
      <div class="review-item">
        <span class="review-label">Job Title</span>
        <span class="review-value">${escapeHtml(data.title) || "Not specified"}</span>
      </div>
      <div class="review-item">
        <span class="review-label">Description</span>
        <span class="review-value">${
          data.description
            ? escapeHtml(data.description.substring(0, 100)) +
              (data.description.length > 100 ? "..." : "")
            : "Not specified"
        }</span>
      </div>
    </div>
    <div class="review-card">
      <div class="review-item">
        <span class="review-label">Categories</span>
        <span class="review-value">${
          data.categories.map((c) => c.name || c).join(", ") || "None selected"
        }</span>
      </div>
    </div>
    <div class="review-card">
      <div class="review-item">
        <span class="review-label">Requirements</span>
        <span class="review-value">${requirements.length} item(s)</span>
      </div>
      <ul class="review-list">${
        requirements.map((r) => `<li>${escapeHtml(r)}</li>`).join("") ||
        "<li>None added</li>"
      }</ul>
    </div>
    <div class="review-card">
      <div class="review-item">
        <span class="review-label">Responsibilities</span>
        <span class="review-value">${responsibilities.length} item(s)</span>
      </div>
      <ul class="review-list">${
        responsibilities.map((r) => `<li>${escapeHtml(r)}</li>`).join("") ||
        "<li>None added</li>"
      }</ul>
    </div>
    <div class="review-card">
      <div class="review-item">
        <span class="review-label">Job Type</span>
        <span class="review-value">${data.job_type_display}</span>
      </div>
      <div class="review-item">
        <span class="review-label">Experience Level</span>
        <span class="review-value">${data.experience_level_display}</span>
      </div>
      <div class="review-item">
        <span class="review-label">Location</span>
        <span class="review-value">${
          data.is_remote ? "🌍 Remote" : "On-site"
        }</span>
      </div>
    </div>
    <div class="review-card">
      <div class="review-item">
        <span class="review-label">Salary Range (Monthly)</span>
        <span class="review-value">${
          data.salary_min && data.salary_max
            ? `KES ${data.salary_min.toLocaleString()} - ${data.salary_max.toLocaleString()}`
            : data.salary_min
              ? `KES ${data.salary_min.toLocaleString()}+`
              : data.salary_max
                ? `Up to KES ${data.salary_max.toLocaleString()}`
                : "Not specified"
        }</span>
      </div>
      <div class="review-item">
        <span class="review-label">Resume Required</span>
        <span class="review-value">${data.requires_resume ? "Yes" : "No"}</span>
      </div>
    </div>
    <div class="review-card">
      <div class="review-item">
        <span class="review-label">Application Deadline</span>
        <span class="review-value">${
          data.application_deadline
            ? new Date(data.application_deadline).toLocaleDateString()
            : "No deadline set"
        }</span>
      </div>
      <div class="review-item">
        <span class="review-label">Allow Applications Past Deadline</span>
        <span class="review-value">${
          data.allow_past_deadline ? "✅ Yes" : "❌ No"
        }</span>
      </div>
    </div>
  `;
}

// ==================== FORM SUBMISSION ====================

function validateForm() {
  return new Promise((resolve) => {
    const title = document.getElementById("title").value.trim();
    if (!title) {
      Toast.error("Please enter a job title");
      resolve(false);
      return;
    }

    const description = document.getElementById("description").value.trim();
    if (!description) {
      Toast.error("Please enter a job description");
      resolve(false);
      return;
    }

    const selectedJobType = document.querySelector(".option-card.selected");
    if (!selectedJobType) {
      Toast.error("Please select a job type");
      resolve(false);
      return;
    }

    const salaryValidation = validateSalaryRange();
    if (!salaryValidation.valid) {
      Toast.error(salaryValidation.message);
      resolve(false);
      return;
    }

    if (selectedCategories.length === 0) {
      Confirm.warning({
        heading: "No categories selected",
        message: "This job will be harder to find without categories.",
        details: [
          "It's highly recommended to select at least one category to increase visibility.",
          "Are you sure you want to continue without selecting any categories?",
        ],
        confirmText: "Yes, Continue",
        cancelText: "Cancel",
        onConfirm: () => {
          resolve(true);
        },
        onCancel: () => {
          currentStep = 0;
          showStep(currentStep);
          resolve(false);
        },
      });
      return;
    }

    resolve(true);
  });
}

async function submitForm(status) {
  const isValid = await validateForm();
  if (!isValid) return;

  const data = collectData(status);

  // Build payload with category IDs only
  const payload = {
    title: data.title,
    description: data.description,
    requirements: data.requirements,
    responsibilities: data.responsibilities,
    job_type: data.job_type,
    experience_level: data.experience_level || null,
    is_remote: data.is_remote ? 1 : 0,
    salary_min: data.salary_min,
    salary_max: data.salary_max,
    currency: data.currency,
    requires_resume: data.requires_resume ? 1 : 0,
    categories: selectedCategories.map((cat) => cat.id), // Extract IDs
    expires_at: data.application_deadline,
    allow_past_deadline: data.allow_past_deadline ? 1 : 0,
    status: status,
  };

  console.log("Sending payload:", payload);

  let success;
  if (isEditing) {
    success = await updateJob(payload);
  } else {
    success = await createJob(payload);
  }

  if (success) {
    // Already handled inside create/update functions
  }
}

// ==================== EVENT LISTENERS ====================

function initEventListeners() {
  if (prevBtn) prevBtn.addEventListener("click", prevStep);
  if (nextBtn) nextBtn.addEventListener("click", nextStep);

  const publishBtn = document.getElementById("publishBtn");
  const saveDraftBtn = document.getElementById("saveDraftBtn");

  if (publishBtn)
    publishBtn.addEventListener("click", () => submitForm("published"));
  if (saveDraftBtn)
    saveDraftBtn.addEventListener("click", () => submitForm("draft"));

  // Job type selection
  document.querySelectorAll(".option-card").forEach((card) => {
    card.addEventListener("click", () => {
      document
        .querySelectorAll(".option-card")
        .forEach((c) => c.classList.remove("selected"));
      card.classList.add("selected");
    });
  });

  // Slug preview
  const titleInput = document.getElementById("title");
  if (titleInput) {
    titleInput.addEventListener("input", updateSlugPreview);
    updateSlugPreview();
  }
}

function updateSlugPreview() {
  const title = document.getElementById("title").value;
  let slug = title
    .toLowerCase()
    .replace(/[^\w\s-]/g, "")
    .replace(/\s+/g, "-");
  slug = slug.replace(/^-+|-+$/g, "");
  const slugPreview = document.getElementById("slugPreview");
  if (slugPreview) {
    slugPreview.innerHTML = `<i class="fas fa-link"></i> URL: /jobs/${slug || "your-job-title"}`;
  }
}

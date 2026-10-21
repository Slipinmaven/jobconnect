// ==================== WIZARD STATE ====================
let currentStep = 1;
const totalSteps = 8;

// Selected categories and skills
let selectedCategories = [];
let selectedSkills = [];

// ==================== INITIALIZE ====================
document.addEventListener("DOMContentLoaded", function () {
  // Populate skills grid
  populateSkills();

  // Add click handlers to category bubbles
  initializeCategoryBubbles();

  // Hide all steps except first
  for (let i = 2; i <= totalSteps; i++) {
    const stepContent = document.getElementById(`step${i}-content`);
    if (stepContent) {
      stepContent.classList.add("hidden");
    }
  }

  // Setup search listeners
  setupSearchListeners();
});

// ==================== INITIALIZE CATEGORY BUBBLES ====================
function initializeCategoryBubbles() {
  document.querySelectorAll(".category-bubble").forEach((bubble) => {
    bubble.addEventListener("click", function () {
      toggleCategory(this);
    });
  });
}

// ==================== SEARCH FUNCTIONALITY ====================
function setupSearchListeners() {
  // Category search
  const categorySearch = document.getElementById("categorySearch");
  if (categorySearch) {
    categorySearch.addEventListener("input", function () {
      const searchTerm = this.value.toLowerCase();
      const clearBtn = document.getElementById("clearCategorySearch");

      if (searchTerm.length > 0) {
        clearBtn.style.display = "block";
      } else {
        clearBtn.style.display = "none";
      }

      filterCategories(searchTerm);
    });
  }

  // Skill search
  const skillSearch = document.getElementById("skillSearch");
  if (skillSearch) {
    skillSearch.addEventListener("input", function () {
      const searchTerm = this.value.toLowerCase();
      const clearBtn = document.getElementById("clearSkillSearch");

      if (searchTerm.length > 0) {
        clearBtn.style.display = "block";
      } else {
        clearBtn.style.display = "none";
      }

      filterSkills("all", null, searchTerm);
    });
  }
}

function clearCategorySearch() {
  const searchInput = document.getElementById("categorySearch");
  searchInput.value = "";
  document.getElementById("clearCategorySearch").style.display = "none";
  filterCategories("");
}

function clearSkillSearch() {
  const searchInput = document.getElementById("skillSearch");
  searchInput.value = "";
  document.getElementById("clearSkillSearch").style.display = "none";
  filterSkills("all", null, "");
}

function filterCategories(searchTerm) {
  const bubbles = document.querySelectorAll(".category-bubble");
  let visibleCount = 0;

  bubbles.forEach((bubble) => {
    const name = bubble.querySelector(".bubble-name").textContent.toLowerCase();
    if (name.includes(searchTerm)) {
      bubble.style.display = "flex";
      visibleCount++;
    } else {
      bubble.style.display = "none";
    }
  });

  // Show/hide no results message
  const container = document.getElementById("categoriesContainer");
  let noResults = document.getElementById("categoryNoResults");

  if (visibleCount === 0) {
    if (!noResults) {
      noResults = document.createElement("div");
      noResults.id = "categoryNoResults";
      noResults.className = "no-results";
      noResults.innerHTML =
        '<i class="fas fa-search" style="font-size: 40px; margin-bottom: 15px;"></i><br>No categories found. Try a different search term.';
      container.appendChild(noResults);
    }
  } else if (noResults) {
    noResults.remove();
  }
}

// ==================== POPULATE SKILLS ====================
function populateSkills(filterCategory = "all", searchTerm = "") {
  const grid = document.getElementById("skillsGrid");
  if (!grid || !window.skills) return;

  grid.innerHTML = "";

  let filteredSkills = window.skills;

  // Apply category filter
  if (filterCategory !== "all") {
    filteredSkills = filteredSkills.filter(
      (s) => s.category_id == filterCategory,
    );
  } else if (selectedCategories.length > 0 && !searchTerm) {
    // Show skills from selected categories first, then others (only when no search)
    const selectedCategoryIds = selectedCategories.map((c) => c.id);
    const prioritySkills = filteredSkills.filter((s) =>
      selectedCategoryIds.includes(s.category_id),
    );
    const otherSkills = filteredSkills.filter(
      (s) => !selectedCategoryIds.includes(s.category_id),
    );
    filteredSkills = [...prioritySkills, ...otherSkills];
  }

  // Apply search filter
  if (searchTerm) {
    filteredSkills = filteredSkills.filter((s) =>
      s.name.toLowerCase().includes(searchTerm.toLowerCase()),
    );
  }

  if (filteredSkills.length === 0) {
    grid.innerHTML =
      '<div class="no-results" style="grid-column: span 2;"><i class="fas fa-search" style="font-size: 40px; margin-bottom: 15px;"></i><br>No skills found. Try a different search.</div>';
    return;
  }

  filteredSkills.forEach((skill) => {
    const isSelected = selectedSkills.some((s) => s.id === skill.id);
    const category = window.skillCategories?.find(
      (c) => c.id === skill.category_id,
    );

    const card = document.createElement("div");
    card.className = `skill-card ${isSelected ? "selected" : ""}`;
    card.dataset.skillId = skill.id;
    card.dataset.categoryId = skill.category_id;

    // Get selected skill data if exists
    const selectedSkill = selectedSkills.find((s) => s.id === skill.id);

    card.innerHTML = `
      <div class="skill-card-header">
        <span class="skill-name">${skill.name}</span>
        <span class="skill-check">✓</span>
      </div>
      <span class="skill-category-badge">
        ${category?.icon ? `<i class="fas ${category.icon}"></i>` : ""} 
        ${skill.category_name || skill.category || ""}
      </span>
      <div class="skill-proficiency">
        <select class="proficiency-select" data-skill-id="${skill.id}" ${!isSelected ? "disabled" : ""}>
          <option value="beginner" ${selectedSkill?.proficiency === "beginner" ? "selected" : ""}>Beginner</option>
          <option value="intermediate" ${selectedSkill?.proficiency === "intermediate" ? "selected" : ""}>Intermediate</option>
          <option value="advanced" ${selectedSkill?.proficiency === "advanced" ? "selected" : ""}>Advanced</option>
          <option value="expert" ${selectedSkill?.proficiency === "expert" ? "selected" : ""}>Expert</option>
        </select>
        <input type="number" class="years-input" placeholder="Years" 
               value="${selectedSkill?.years || ""}"
               data-skill-id="${skill.id}" ${!isSelected ? "disabled" : ""}>
      </div>
    `;

    // Add click event for the card (but not when clicking on select/input)
    card.addEventListener("click", (e) => {
      if (
        !e.target.closest(".proficiency-select") &&
        !e.target.closest(".years-input")
      ) {
        toggleSkill(skill.id);
      }
    });

    grid.appendChild(card);
  });

  // Add "Add Custom Skill" button
  const addBtn = document.createElement("div");
  addBtn.className = "add-skill-btn";
  addBtn.innerHTML = '<i class="fas fa-plus"></i> Add Custom Skill';
  addBtn.onclick = () => addCustomSkill();
  grid.appendChild(addBtn);

  // Attach event listeners to selects and inputs
  document.querySelectorAll(".proficiency-select").forEach((select) => {
    select.addEventListener("change", function (e) {
      e.stopPropagation();
      const skillId = parseInt(this.dataset.skillId);
      updateSkillProficiency(skillId, this.value);
    });
  });

  document.querySelectorAll(".years-input").forEach((input) => {
    input.addEventListener("change", function (e) {
      e.stopPropagation();
      const skillId = parseInt(this.dataset.skillId);
      updateSkillYears(skillId, this.value);
    });
  });

  updateSkillFilters();
  updateSelectedSkillsSummary();
}

// ==================== UPDATE SKILL FILTERS ====================
function updateSkillFilters() {
  const filtersContainer = document.getElementById("skillFilters");
  if (!filtersContainer) return;

  // Keep the All Skills button
  filtersContainer.innerHTML =
    '<button class="filter-chip active" onclick="filterSkills(\'all\', event)"><i class="fas fa-sparkles"></i> All Skills</button>';

  // Add filters for selected categories
  selectedCategories.forEach((category) => {
    const chip = document.createElement("button");
    chip.className = "filter-chip";
    chip.innerHTML = `${category.icon} ${category.name}`;
    chip.setAttribute("onclick", `filterSkills(${category.id}, event)`);
    filtersContainer.appendChild(chip);
  });
}

// ==================== FILTER SKILLS ====================
function filterSkills(categoryId, event, searchTerm = null) {
  // Update active state
  if (event) {
    document.querySelectorAll(".filter-chip").forEach((chip) => {
      chip.classList.remove("active");
    });
    event.target.classList.add("active");
  }

  const searchInput = document.getElementById("skillSearch");
  const currentSearch = searchTerm !== null ? searchTerm : searchInput.value;

  populateSkills(categoryId, currentSearch);
}

// ==================== TOGGLE CATEGORY ====================
function toggleCategory(element) {
  const categoryId = parseInt(element.dataset.categoryId);
  const categoryName = element.dataset.categoryName;
  const categoryIcon = element.querySelector(".bubble-icon").textContent;

  const index = selectedCategories.findIndex((c) => c.id === categoryId);

  if (index === -1) {
    // Add category
    selectedCategories.push({
      id: categoryId,
      name: categoryName,
      icon: categoryIcon,
    });
    element.classList.add("selected");
    Toast.success(`Added ${categoryName} to your interests`);
  } else {
    // Remove category
    selectedCategories.splice(index, 1);
    element.classList.remove("selected");
    Toast.info(`Removed ${categoryName} from your interests`);

    // Remove any skills from this category that were selected
    selectedSkills = selectedSkills.filter((s) => {
      const skill = window.skills.find((sk) => sk.id === s.id);
      if (skill && skill.category_id === categoryId) {
        Toast.info(`Removed ${skill.name} from your skills`);
        return false;
      }
      return true;
    });
  }

  updateSelectedCategories();

  // Refresh skills to show priority from selected categories
  if (currentStep === 4) {
    const searchInput = document.getElementById("skillSearch");
    populateSkills("all", searchInput.value);
  }
}

// ==================== UPDATE SELECTED CATEGORIES DISPLAY ====================
function updateSelectedCategories() {
  const tagsContainer = document.getElementById("selectedTags");
  const countSpan = document.getElementById("selectedCount");

  if (!tagsContainer || !countSpan) return;

  countSpan.textContent = selectedCategories.length;

  if (selectedCategories.length === 0) {
    tagsContainer.innerHTML =
      '<div class="empty-tags-message">Click on bubbles above to select your interests</div>';
    return;
  }

  tagsContainer.innerHTML = "";
  selectedCategories.forEach((category) => {
    const tag = document.createElement("div");
    tag.className = "category-tag";
    tag.innerHTML = `
            <span>${category.icon} ${category.name}</span>
            <i class="fas fa-times" onclick="removeCategory(${category.id})"></i>
        `;
    tagsContainer.appendChild(tag);
  });
}

// ==================== REMOVE CATEGORY ====================
function removeCategory(categoryId) {
  const bubble = document.querySelector(
    `.category-bubble[data-category-id="${categoryId}"]`,
  );
  if (bubble) {
    bubble.classList.remove("selected");
  }

  selectedCategories = selectedCategories.filter((c) => c.id !== categoryId);
  updateSelectedCategories();

  // Remove any skills from this category that were selected
  selectedSkills = selectedSkills.filter((s) => {
    const skill = window.skills.find((sk) => sk.id === s.id);
    if (skill && skill.category_id === categoryId) {
      return false;
    }
    return true;
  });

  if (currentStep === 4) {
    const searchInput = document.getElementById("skillSearch");
    populateSkills("all", searchInput.value);
    updateSelectedSkillsSummary();
  }
}

// ==================== TOGGLE SKILL ====================
function toggleSkill(skillId) {
  const skill = window.skills.find((s) => s.id === skillId);
  if (!skill) return;

  const index = selectedSkills.findIndex((s) => s.id === skillId);
  const card = document.querySelector(
    `.skill-card[data-skill-id="${skillId}"]`,
  );

  if (index === -1) {
    // Add skill
    selectedSkills.push({
      id: skill.id,
      name: skill.name,
      category: skill.category,
      proficiency: "intermediate",
      years: "",
    });
    if (card) {
      card.classList.add("selected");
      const proficiency = card.querySelector(".proficiency-select");
      const years = card.querySelector(".years-input");
      if (proficiency) proficiency.disabled = false;
      if (years) years.disabled = false;
    }
    Toast.success(`Added ${skill.name} to your skills`);
  } else {
    // Remove skill
    selectedSkills.splice(index, 1);
    if (card) {
      card.classList.remove("selected");
      const proficiency = card.querySelector(".proficiency-select");
      const years = card.querySelector(".years-input");
      if (proficiency) {
        proficiency.disabled = true;
        proficiency.value = "intermediate";
      }
      if (years) {
        years.disabled = true;
        years.value = "";
      }
    }
    Toast.info(`Removed ${skill.name} from your skills`);
  }

  updateSelectedSkillsSummary();
}

// ==================== UPDATE SKILL PROFICIENCY ====================
function updateSkillProficiency(skillId, proficiency) {
  const skill = selectedSkills.find((s) => s.id === skillId);
  if (skill) {
    skill.proficiency = proficiency;
    updateSelectedSkillsSummary();
  }
}

// ==================== UPDATE SKILL YEARS ====================
function updateSkillYears(skillId, years) {
  const skill = selectedSkills.find((s) => s.id === skillId);
  if (skill) {
    skill.years = years;
    updateSelectedSkillsSummary();
  }
}

// ==================== UPDATE SELECTED SKILLS SUMMARY ====================
function updateSelectedSkillsSummary() {
  const container = document.getElementById("selectedSkillsList");
  const countSpan = document.getElementById("selectedSkillsCount");

  if (!container || !countSpan) return;

  countSpan.textContent = selectedSkills.length;

  if (selectedSkills.length === 0) {
    container.innerHTML =
      '<div class="empty-tags-message">Select skills above to add them here</div>';
    return;
  }

  container.innerHTML = "";
  selectedSkills.forEach((skill) => {
    const item = document.createElement("div");
    item.className = "skill-summary-item";
    item.innerHTML = `
            <span>${skill.name}</span>
            <span class="proficiency-badge">${skill.proficiency || "intermediate"}</span>
            ${skill.years ? `<span style="font-size: 12px; color: var(--text-muted);">${skill.years} yrs</span>` : ""}
            <span class="remove-skill" onclick="toggleSkill(${skill.id})">
                <i class="fas fa-times"></i>
            </span>
        `;
    container.appendChild(item);
  });
}

// ==================== ADD CUSTOM SKILL ====================
function addCustomSkill() {
  const skillName = prompt("Enter your custom skill:");
  if (!skillName) return;

  // Create a temporary custom skill
  const customSkill = {
    id: Date.now(),
    name: skillName,
    category_id: 0,
    category: "Custom",
  };

  selectedSkills.push({
    id: customSkill.id,
    name: customSkill.name,
    category: "Custom",
    proficiency: "intermediate",
    years: "",
  });

  Toast.success(`Added ${skillName} to your skills`);
  updateSelectedSkillsSummary();
}

// ==================== EDUCATION FUNCTIONS ====================
function addEducation() {
  const container = document.getElementById("educationsContainer");
  const id = Date.now();

  const eduDiv = document.createElement("div");
  eduDiv.className = "education-item";
  eduDiv.id = `education-${id}`;
  eduDiv.innerHTML = `
        <div class="education-header">
            <h4><i class="fas fa-graduation-cap"></i> Education</h4>
            <button class="remove-education" onclick="removeEducation(${id})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        
        <!-- Institution Type Selection (for filtering schools) -->
        <div class="education-level-selector" id="institution-selector-${id}">
            <div class="level-card" onclick="selectInstitutionType(${id}, 'high_school')">
                <div class="level-icon">🏫</div>
                <div class="level-name">High School</div>
                <div class="level-desc">Secondary education</div>
            </div>
            <div class="level-card" onclick="selectInstitutionType(${id}, 'university')">
                <div class="level-icon">🎓</div>
                <div class="level-name">University</div>
                <div class="level-desc">Bachelor's, Master's, PhD</div>
            </div>
            <div class="level-card" onclick="selectInstitutionType(${id}, 'college')">
                <div class="level-icon">📚</div>
                <div class="level-name">College</div>
                <div class="level-desc">Associate degree, Diploma</div>
            </div>
            <div class="level-card" onclick="selectInstitutionType(${id}, 'bootcamp')">
                <div class="level-icon">⚡</div>
                <div class="level-name">Bootcamp</div>
                <div class="level-desc">Coding, Data, UX bootcamps</div>
            </div>
        </div>
        
        <!-- School Search (hidden until institution selected) -->
        <div id="school-search-${id}" style="display: none;">
            <div class="school-select">
                <input type="text" class="school-search" id="school-search-input-${id}" placeholder="Search for your school..." autocomplete="off">
                <div class="school-dropdown" id="school-dropdown-${id}"></div>
                <input type="hidden" id="school-id-${id}" class="school-id">
            </div>
        </div>
        
        <!-- Education Level (qualification level) - Always visible after school selected -->
        <div id="education-level-container-${id}" style="display: none; margin-top: 20px;">
            <div class="form-group">
                <label class="form-label">Qualification Level <span class="required">*</span></label>
                <select class="form-control" id="education-level-${id}" required>
                    <option value="">Select qualification level</option>
                    <option value="high_school">High School Certificate</option>
                    <option value="certificate">Certificate</option>
                    <option value="diploma">Diploma</option>
                    <option value="bachelor">Bachelor's Degree</option>
                    <option value="master">Master's Degree</option>
                    <option value="doctorate">Doctorate (PhD)</option>
                </select>
            </div>
        </div>
        
        <!-- Education Details (hidden until school selected) -->
        <div id="education-details-${id}" style="display: none; margin-top: 20px;">
            <div class="form-group">
                <label class="form-label">Field of Study</label>
                <input type="text" class="form-control" id="field-of-study-${id}" placeholder="e.g., Computer Science, Business, Engineering">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Start Year</label>
                    <input type="month" class="form-control" id="start-year-${id}" onchange="validateEducationDates(${id})">
                </div>
                <div class="form-group">
                    <label class="form-label">End Year / Expected</label>
                    <input type="month" class="form-control" id="end-year-${id}" onchange="validateEducationDates(${id})">
                </div>
            </div>
            
            <div class="currently-working">
                <label>
                    <input type="checkbox" id="currently-studying-${id}" onchange="toggleCurrentlyStudying(${id})"> I am currently studying here
                </label>
            </div>
            
            <div class="form-group">
                <label class="form-label">Grade/GPA (Optional)</label>
                <input type="text" class="form-control" id="grade-${id}" placeholder="e.g., 3.8 GPA, First Class, A-">
            </div>
            
            <div class="form-group">
                <label class="form-label">Description (Optional)</label>
                <textarea class="form-control" id="education-description-${id}" placeholder="Activities, achievements, notable projects, etc."></textarea>
            </div>
        </div>
    `;

  container.appendChild(eduDiv);

  // Setup school search for this education entry
  setupSchoolSearch(id);
}

function removeEducation(id) {
  const element = document.getElementById(`education-${id}`);
  if (element) {
    element.remove();
    Toast.info("Education removed");
  }
}

function selectInstitutionType(id, type) {
  // Update UI
  const selector = document.getElementById(`institution-selector-${id}`);
  selector.querySelectorAll(".level-card").forEach((card) => {
    card.classList.remove("selected");
  });
  event.currentTarget.classList.add("selected");

  // Show school search
  document.getElementById(`school-search-${id}`).style.display = "block";

  // Store institution type
  document.getElementById(`education-${id}`).dataset.institutionType = type;
}

function setupSchoolSearch(id) {
  const searchInput = document.getElementById(`school-search-input-${id}`);
  const dropdown = document.getElementById(`school-dropdown-${id}`);
  const schoolIdInput = document.getElementById(`school-id-${id}`);

  if (!searchInput) return;

  searchInput.addEventListener("focus", () => {
    filterSchools(id, searchInput.value);
    dropdown.classList.add("active");
  });

  searchInput.addEventListener("input", () => {
    filterSchools(id, searchInput.value);
    dropdown.classList.add("active");
  });

  document.addEventListener("click", (e) => {
    if (!e.target.closest(`#school-search-${id}`)) {
      dropdown.classList.remove("active");
    }
  });
}

function filterSchools(educationId, searchTerm) {
  const dropdown = document.getElementById(`school-dropdown-${educationId}`);
  if (!dropdown || !window.schools) return;

  const institutionType = document.getElementById(`education-${educationId}`)
    .dataset.institutionType;

  let filteredSchools = window.schools;

  // Filter by institution type if selected
  if (institutionType) {
    filteredSchools = filteredSchools.filter((s) => s.type === institutionType);
  }

  // Filter by search term
  if (searchTerm) {
    filteredSchools = filteredSchools.filter(
      (s) =>
        s.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (s.city && s.city.toLowerCase().includes(searchTerm.toLowerCase())) ||
        (s.country &&
          s.country.toLowerCase().includes(searchTerm.toLowerCase())),
    );
  }

  dropdown.innerHTML = "";

  if (filteredSchools.length === 0) {
    const noResult = document.createElement("div");
    noResult.className = "school-option";
    noResult.innerHTML =
      'No schools found. <a href="#" onclick="addNewSchool(' +
      educationId +
      ')">Add new school</a>';
    dropdown.appendChild(noResult);
  } else {
    filteredSchools.forEach((school) => {
      const option = document.createElement("div");
      option.className = "school-option";
      option.innerHTML = `
                <strong>${school.name}</strong>
                <span class="school-type">${school.type.replace("_", " ")}</span>
                <span class="school-location">${school.city ? school.city + ", " : ""}${school.country || ""}</span>
            `;
      option.onclick = () => selectSchool(educationId, school);
      dropdown.appendChild(option);
    });
  }
}

function selectSchool(educationId, school) {
  const searchInput = document.getElementById(
    `school-search-input-${educationId}`,
  );
  const schoolIdInput = document.getElementById(`school-id-${educationId}`);
  const dropdown = document.getElementById(`school-dropdown-${educationId}`);

  searchInput.value = school.name;
  schoolIdInput.value = school.id;
  dropdown.classList.remove("active");

  // Show education level selector and details
  document.getElementById(
    `education-level-container-${educationId}`,
  ).style.display = "block";
  document.getElementById(`education-details-${educationId}`).style.display =
    "block";

  // Store school data
  document.getElementById(`education-${educationId}`).dataset.schoolId =
    school.id;
  document.getElementById(`education-${educationId}`).dataset.schoolName =
    school.name;
}

function addNewSchool(educationId) {
  // In a real app, this would open a modal to add a new school
  Toast.info("This would open a form to add a new school");
}

function toggleCurrentlyStudying(id) {
  const endYearInput = document.getElementById(`end-year-${id}`);
  const checkbox = document.getElementById(`currently-studying-${id}`);

  if (checkbox.checked) {
    endYearInput.disabled = true;
    endYearInput.value = "";
    endYearInput.placeholder = "Expected";
  } else {
    endYearInput.disabled = false;
    endYearInput.placeholder = "YYYY-MM";
  }
  validateEducationDates(id);
}

function validateEducationDates(id) {
  const startInput = document.getElementById(`start-year-${id}`);
  const endInput = document.getElementById(`end-year-${id}`);
  const currentlyStudying =
    document.getElementById(`currently-studying-${id}`)?.checked || false;

  const today = new Date();
  const currentYearMonth = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}`;

  // Validate start date is not in the future
  if (startInput.value && startInput.value > currentYearMonth) {
    startInput.classList.add("is-invalid");
    Toast.error("Start date cannot be in the future");
    return false;
  } else {
    startInput.classList.remove("is-invalid");
  }

  // Validate end date if not currently studying
  if (!currentlyStudying && endInput.value && !endInput.disabled) {
    if (endInput.value < startInput.value) {
      endInput.classList.add("is-invalid");
      Toast.error("End date cannot be before start date");
      return false;
    }
  } else {
    endInput.classList.remove("is-invalid");
  }

  return true;
}

// ==================== EXPERIENCE FUNCTIONS ====================
function addExperience() {
  const container = document.getElementById("experiencesContainer");
  const id = Date.now();

  const expDiv = document.createElement("div");
  expDiv.className = "experience-item";
  expDiv.id = `experience-${id}`;
  expDiv.innerHTML = `
        <div class="experience-header">
            <h4><i class="fas fa-briefcase"></i> Work Experience</h4>
            <button class="remove-education" onclick="removeExperience(${id})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        
        <div class="form-group">
            <label class="form-label">Company Name</label>
            <input type="text" class="form-control" id="exp-company-${id}" placeholder="e.g., Google">
        </div>
        
        <div class="form-group">
            <label class="form-label">Role Title</label>
            <input type="text" class="form-control" id="exp-role-${id}" placeholder="e.g., Software Engineer">
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Start Date</label>
                <input type="month" class="form-control" id="exp-start-${id}" onchange="validateExperienceDates(${id})">
            </div>
            <div class="form-group">
                <label class="form-label">End Date</label>
                <input type="month" class="form-control" id="exp-end-${id}" onchange="validateExperienceDates(${id})">
            </div>
        </div>
        
        <div class="currently-working">
            <label>
                <input type="checkbox" id="exp-current-${id}" onchange="toggleCurrentlyWorking(${id})"> I currently work here
            </label>
        </div>
        
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea class="form-control" id="exp-desc-${id}" placeholder="Describe your responsibilities and achievements..."></textarea>
        </div>
    `;

  container.appendChild(expDiv);
}

function removeExperience(id) {
  const element = document.getElementById(`experience-${id}`);
  if (element) {
    element.remove();
    Toast.info("Experience removed");
  }
}

function toggleCurrentlyWorking(id) {
  const endYearInput = document.getElementById(`exp-end-${id}`);
  const checkbox = document.getElementById(`exp-current-${id}`);

  if (checkbox.checked) {
    endYearInput.disabled = true;
    endYearInput.value = "";
    endYearInput.placeholder = "Present";
  } else {
    endYearInput.disabled = false;
    endYearInput.placeholder = "YYYY-MM";
  }
  validateExperienceDates(id);
}

function validateExperienceDates(id) {
  const startInput = document.getElementById(`exp-start-${id}`);
  const endInput = document.getElementById(`exp-end-${id}`);
  const currentlyWorking =
    document.getElementById(`exp-current-${id}`)?.checked || false;

  const today = new Date();
  const currentYearMonth = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}`;

  // Validate start date is not in the future
  if (startInput.value && startInput.value > currentYearMonth) {
    startInput.classList.add("is-invalid");
    Toast.error("Start date cannot be in the future");
    return false;
  } else {
    startInput.classList.remove("is-invalid");
  }

  // Validate end date if not currently working
  if (!currentlyWorking && endInput.value && !endInput.disabled) {
    if (endInput.value < startInput.value) {
      endInput.classList.add("is-invalid");
      Toast.error("End date cannot be before start date");
      return false;
    }
  } else {
    endInput.classList.remove("is-invalid");
  }

  return true;
}

// ==================== REVIEW FUNCTIONS ====================
function updateReview() {
  const container = document.getElementById("reviewContainer");
  if (!container) return;

  // Personal Info
  const firstName = document.getElementById("firstName").value;
  const lastName = document.getElementById("lastName").value;
  const professionalTitle = document.getElementById("professionalTitle").value;
  const summary = document.getElementById("summary").value;
  const yearsExp = document.getElementById("yearsExperience").value;

  // Build review HTML
  let reviewHTML = `
        <!-- Personal Info Review -->
        <div class="review-section">
            <div class="review-section-header">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
                <button class="review-edit-btn" onclick="goToStep(1)">Edit</button>
            </div>
            <div class="review-grid">
                <div class="review-item">
                    <div class="review-label">Name</div>
                    <div class="review-value">${firstName} ${lastName}</div>
                </div>
                <div class="review-item">
                    <div class="review-label">Professional Title</div>
                    <div class="review-value">${professionalTitle}</div>
                </div>
            </div>
        </div>
        
        <!-- Summary Review -->
        <div class="review-section">
            <div class="review-section-header">
                <h3><i class="fas fa-pen"></i> Professional Summary</h3>
                <button class="review-edit-btn" onclick="goToStep(2)">Edit</button>
            </div>
            <div class="review-item">
                <div class="review-value">${summary || "Not provided"}</div>
                ${yearsExp ? `<div class="review-value" style="margin-top: 10px;"><strong>Years Experience:</strong> ${yearsExp}</div>` : ""}
            </div>
        </div>
    `;

  // Interests Review
  if (selectedCategories.length > 0) {
    reviewHTML += `
            <div class="review-section">
                <div class="review-section-header">
                    <h3><i class="fas fa-heart"></i> Interests</h3>
                    <button class="review-edit-btn" onclick="goToStep(3)">Edit</button>
                </div>
                <div class="review-item">
                    ${selectedCategories.map((c) => `<span class="review-value skill-tag">${c.icon} ${c.name}</span>`).join("")}
                </div>
            </div>
        `;
  }

  // Skills Review
  if (selectedSkills.length > 0) {
    reviewHTML += `
            <div class="review-section">
                <div class="review-section-header">
                    <h3><i class="fas fa-code"></i> Skills</h3>
                    <button class="review-edit-btn" onclick="goToStep(4)">Edit</button>
                </div>
                <div class="review-item">
                    ${selectedSkills
                      .map(
                        (s) => `
                        <span class="review-value skill-tag">
                            ${s.name}
                            <span class="proficiency">${s.proficiency || "intermediate"}</span>
                            ${s.years ? `<span style="font-size: 11px; margin-left: 5px;">${s.years}y</span>` : ""}
                        </span>
                    `,
                      )
                      .join("")}
                </div>
            </div>
        `;
  }

  // Experience Review
  const experienceItems = document.querySelectorAll(".experience-item");
  if (experienceItems.length > 0) {
    let expHTML =
      '<div class="review-section"><div class="review-section-header"><h3><i class="fas fa-briefcase"></i> Experience</h3><button class="review-edit-btn" onclick="goToStep(5)">Edit</button></div>';

    experienceItems.forEach((item, index) => {
      const company =
        item.querySelector('input[id^="exp-company-"]')?.value || "";
      const role = item.querySelector('input[id^="exp-role-"]')?.value || "";
      const start = item.querySelector('input[id^="exp-start-"]')?.value || "";
      const end = item.querySelector('input[id^="exp-end-"]')?.value || "";
      const current = item.querySelector('input[id^="exp-current-"]')?.checked;

      if (company || role) {
        const startDisplay = start ? start.split("-")[0] : "";
        const endDisplay = current ? "Present" : end ? end.split("-")[0] : "";

        expHTML += `
                    <div class="review-experience-item">
                        <strong>${role || "Role"}</strong> at ${company || "Company"}<br>
                        <small>${startDisplay} - ${endDisplay}</small>
                    </div>
                `;
      }
    });

    expHTML += "</div>";
    reviewHTML += expHTML;
  }

  // Education Review
  const educationItems = document.querySelectorAll(".education-item");
  if (educationItems.length > 0) {
    let eduHTML =
      '<div class="review-section"><div class="review-section-header"><h3><i class="fas fa-graduation-cap"></i> Education</h3><button class="review-edit-btn" onclick="goToStep(6)">Edit</button></div>';

    educationItems.forEach((item, index) => {
      const schoolName = item.dataset.schoolName;
      const institutionType = item.dataset.institutionType;
      const educationLevelSelect = item.querySelector(
        `#education-level-${item.id.split("-")[1]}`,
      );
      const educationLevel = educationLevelSelect
        ? educationLevelSelect.value
        : "";

      const field =
        item.querySelector('input[id^="field-of-study-"]')?.value || "";
      const start = item.querySelector('input[id^="start-year-"]')?.value || "";
      const end = item.querySelector('input[id^="end-year-"]')?.value || "";
      const current = item.querySelector(
        'input[id^="currently-studying-"]',
      )?.checked;
      const grade = item.querySelector('input[id^="grade-"]')?.value || "";

      if (schoolName) {
        const startDisplay = start ? start.split("-")[0] : "";
        const endDisplay = current ? "Present" : end ? end.split("-")[0] : "";

        let levelDisplay = "";
        if (educationLevel) {
          const levelLabels = {
            high_school: "High School",
            certificate: "Certificate",
            diploma: "Diploma",
            bachelor: "Bachelor's Degree",
            master: "Master's Degree",
            doctorate: "Doctorate (PhD)",
          };
          levelDisplay = levelLabels[educationLevel] || educationLevel;
        }

        eduHTML += `
                    <div class="review-education-item">
                        <strong>${schoolName}</strong>
                        ${levelDisplay ? `<span style="background: #e2e8f0; padding: 2px 10px; border-radius: 12px; font-size: 11px; margin-left: 8px;">${levelDisplay}</span>` : ""}
                        <br>
                        ${field ? field + "<br>" : ""}
                        <small>${startDisplay} - ${endDisplay}</small>
                        ${grade ? `<br><small>Grade: ${grade}</small>` : ""}
                    </div>
                `;
      }
    });

    eduHTML += "</div>";
    reviewHTML += eduHTML;
  }

  // Preferences Review
  const remote = document.getElementById("remotePreference")?.checked;
  const relocate = document.getElementById("relocationWilling")?.checked;
  const openToWork = document.getElementById("openToWork")?.checked;

  reviewHTML += `
        <div class="review-section">
            <div class="review-section-header">
                <h3><i class="fas fa-map-marker-alt"></i> Work Preferences</h3>
                <button class="review-edit-btn" onclick="goToStep(7)">Edit</button>
            </div>
            <div class="review-grid">
                <div class="review-item">
                    <div class="review-label">Remote Work</div>
                    <div class="review-value">${remote ? "✅ Yes" : "❌ No"}</div>
                </div>
                <div class="review-item">
                    <div class="review-label">Willing to Relocate</div>
                    <div class="review-value">${relocate ? "✅ Yes" : "❌ No"}</div>
                </div>
                <div class="review-item">
                    <div class="review-label">Open to Work</div>
                    <div class="review-value">${openToWork ? "✅ Yes" : "❌ No"}</div>
                </div>
            </div>
        </div>
    `;

  container.innerHTML = reviewHTML;
}

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

    // Special handling for step 4 (skills)
    if (currentStep === 4) {
      const searchInput = document.getElementById("skillSearch");
      populateSkills("all", searchInput.value);
    }

    // Special handling for step 8 (review)
    if (currentStep === 8) {
      updateReview();
    }

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

  // Special handling
  if (currentStep === 4) {
    const searchInput = document.getElementById("skillSearch");
    populateSkills("all", searchInput.value);
  }

  if (currentStep === 8) {
    updateReview();
  }

  // Update button text
  const nextBtn = document.getElementById("nextBtn");
  if (currentStep === totalSteps) {
    nextBtn.innerHTML = 'Submit Profile <i class="fas fa-check"></i>';
  } else {
    nextBtn.innerHTML = 'Continue <i class="fas fa-arrow-right"></i>';
  }
}

// ==================== VALIDATION ====================
function validateStep(step) {
  switch (step) {
    case 1:
      if (!document.getElementById("firstName").value.trim()) {
        Toast.error("First name is required");
        return false;
      }
      if (!document.getElementById("lastName").value.trim()) {
        Toast.error("Last name is required");
        return false;
      }
      if (!document.getElementById("professionalTitle").value.trim()) {
        Toast.error("Professional title is required");
        return false;
      }
      return true;

    case 2:
      if (!document.getElementById("summary").value.trim()) {
        Toast.error("Please tell us about yourself");
        return false;
      }
      return true;

    case 3:
      if (selectedCategories.length === 0) {
        Toast.error("Please select at least one interest area");
        return false;
      }
      return true;

    case 4:
      if (selectedSkills.length === 0) {
        if (
          !confirm(
            "You haven't selected any skills. You can add them later from your profile. Continue anyway?",
          )
        ) {
          return false;
        }
      }
      return true;

    default:
      return true;
  }
}

// ==================== SUBMIT PROFILE ====================
// ==================== SUBMIT PROFILE ====================
async function submitProfile() {
  // Gather all education data with proper mapping to database schema
  const educationData = [];
  document.querySelectorAll(".education-item").forEach((item) => {
    const id = item.id.split("-")[1];
    const schoolId = item.dataset.schoolId;
    const schoolName = item.dataset.schoolName;
    const educationLevelSelect = document.getElementById(
      `education-level-${id}`,
    );
    const educationLevel = educationLevelSelect
      ? educationLevelSelect.value
      : "";

    const fieldOfStudy = document.getElementById(`field-of-study-${id}`)?.value;
    const startYearInput = document.getElementById(`start-year-${id}`)?.value;
    const endYearInput = document.getElementById(`end-year-${id}`)?.value;
    const currentlyStudying =
      document.getElementById(`currently-studying-${id}`)?.checked || false;
    const grade = document.getElementById(`grade-${id}`)?.value;
    const description = document.getElementById(
      `education-description-${id}`,
    )?.value;

    // Only include if we have a school and education level
    if (schoolId && educationLevel) {
      // Extract year from month input (format: YYYY-MM)
      let startYear = null;
      if (startYearInput) {
        startYear = parseInt(startYearInput.split("-")[0]);
      }

      let endYear = null;
      let expectedGraduationYear = null;

      if (currentlyStudying) {
        // If currently studying, use expected graduation year
        if (endYearInput) {
          expectedGraduationYear = parseInt(endYearInput.split("-")[0]);
        }
      } else {
        // If not currently studying, use end year
        if (endYearInput) {
          endYear = parseInt(endYearInput.split("-")[0]);
        }
      }

      educationData.push({
        school_id: parseInt(schoolId),
        school_name: schoolName,
        education_level: educationLevel,
        field_of_study: fieldOfStudy || null,
        grade: grade || null,
        start_year: startYear,
        end_year: endYear,
        currently_studying: currentlyStudying,
        expected_graduation_year: expectedGraduationYear,
        description: description || null,
      });
    }
  });

  // Gather all experience data
  const experienceData = [];
  document.querySelectorAll(".experience-item").forEach((item) => {
    const id = item.id.split("-")[1];
    const company = document.getElementById(`exp-company-${id}`)?.value;
    const role = document.getElementById(`exp-role-${id}`)?.value;
    const startYearInput = document.getElementById(`exp-start-${id}`)?.value;
    const endYearInput = document.getElementById(`exp-end-${id}`)?.value;
    const currentlyWorking =
      document.getElementById(`exp-current-${id}`)?.checked || false;
    const description = document.getElementById(`exp-desc-${id}`)?.value;

    if (company && role) {
      // Extract year from month input
      let startYear = null;
      if (startYearInput) {
        startYear = parseInt(startYearInput.split("-")[0]);
      }

      let endYear = null;
      if (!currentlyWorking && endYearInput) {
        endYear = parseInt(endYearInput.split("-")[0]);
      }

      experienceData.push({
        company_name: company,
        role_title: role,
        start_year: startYear,
        end_year: endYear,
        currently_working: currentlyWorking || false,
        description: description || null,
      });
    }
  });

  const profileData = {
    personal: {
      firstName: document.getElementById("firstName").value,
      lastName: document.getElementById("lastName").value,
      professionalTitle: document.getElementById("professionalTitle").value,
    },
    summary: document.getElementById("summary").value,
    yearsExperience: document.getElementById("yearsExperience").value
      ? parseInt(document.getElementById("yearsExperience").value)
      : null,
    categories: selectedCategories.map(cat => ({
      id: cat.id,
      name: cat.name,
      icon: cat.icon ? cat.icon.trim() : ''
    })),
    skills: selectedSkills.map(skill => ({
      id: skill.id,
      name: skill.name,
      proficiency: skill.proficiency || 'intermediate',
      years: skill.years ? parseInt(skill.years) : null
    })),
    education: educationData,
    experience: experienceData,
    preferences: {
      remote: document.getElementById("remotePreference")?.checked || false,
      relocation:
        document.getElementById("relocationWilling")?.checked || false,
      openToWork: document.getElementById("openToWork")?.checked || true,
    },
  };

  console.log("Submitting profile:", profileData);

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

  try {
    // Make API call to your endpoint
    const response = await fetch('/api/jobseeker/profile-setup', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin', // Include cookies for authentication
      body: JSON.stringify(profileData)
    });

    // Parse response
    const result = await response.json();

    // Remove spinner
    document.body.removeChild(spinner);

    // Handle response based on status
    if (response.ok && result.success) {
      Toast.success(result.message || "Profile created successfully!");
      
      // Redirect after successful submission
      setTimeout(() => {
        if (result.data?.redirect) {
          window.location.href = result.data.redirect;
        } else {
          window.location.href = "/jobseeker/dashboard";
        }
      }, 1500);
      
    } else if (response.status === 422) {
      // Validation errors
      const errors = result.errors || {};
      const errorMessages = [];
      
      // Display all validation errors
      Object.keys(errors).forEach(field => {
        errorMessages.push(`${field}: ${errors[field]}`);
        Toast.error(`${field}: ${errors[field]}`);
      });
      
      // Show first error prominently
      if (errorMessages.length > 0) {
        Toast.error(errorMessages[0]);
      } else {
        Toast.error(result.message || "Validation failed. Please check your inputs.");
      }
      
    } else if (response.status === 401) {
      // Unauthorized
      Toast.error("Please login to create your profile");
      setTimeout(() => {
        window.location.href = "/login";
      }, 2000);
      
    } else if (response.status === 403) {
      // Forbidden
      Toast.error("You don't have permission to create a job seeker profile");
      
    } else if (response.status === 500) {
      // Server error
      Toast.error(result.message || "Server error. Please try again later.");
      console.error("Server error:", result);
      
    } else {
      // Other errors
      Toast.error(result.message || "Failed to create profile. Please try again.");
    }
    
  } catch (error) {
    // Network error or JSON parse error
    console.error("Error submitting profile:", error);
    document.body.removeChild(spinner);
    Toast.error("Network error. Please check your connection and try again.");
  }
}

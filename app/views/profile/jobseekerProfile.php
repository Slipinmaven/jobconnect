<div class="wizard-container">
    <!-- Wizard Header -->
    <div class="wizard-header">
        <h1>Complete Your JobSeeker Profile</h1>
        <p>Let's showcase your talents to employers!</p>
    </div>


    <!-- Progress Steps -->
    <div class="progress-wrapper">
        <div class="progress-steps">
            <div class="step active" id="step1">1<span class="step-label">Personal</span></div>
            <div class="step" id="step2">2<span class="step-label">Summary</span></div>
            <div class="step" id="step3">3<span class="step-label">Interests</span></div>
            <div class="step" id="step4">4<span class="step-label">Skills</span></div>
            <div class="step" id="step5">5<span class="step-label">Experience</span></div>
            <div class="step" id="step6">6<span class="step-label">Education</span></div>
            <div class="step" id="step7">7<span class="step-label">Location</span></div>
            <div class="step" id="step8">8<span class="step-label">Review</span></div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="wizard-content">
        <!-- STEP 1: Personal Info -->
        <div id="step1-content">
            <div class="step-header">
                <h2 class="gradient-text">Nice to meet you! 👋</h2>
                <p class="step-subtitle">Let's start with the basics</p>
            </div>

            <div class="info-grid">
                <div class="info-card">
                    <div class="info-icon">📝</div>
                    <h4>First Impression</h4>
                    <p>Your name helps employers recognize you</p>
                </div>
                <div class="info-card">
                    <div class="info-icon">🎯</div>
                    <h4>Professional Title</h4>
                    <p>What role are you looking for?</p>
                </div>
                <div class="info-card">
                    <div class="info-icon">🚀</div>
                    <h4>Get Discovered</h4>
                    <p>Complete profile = 5x more views</p>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">First Name <span class="required">*</span></label>
                <input type="text" class="form-control" id="firstName" placeholder="e.g., John">
            </div>

            <div class="form-group">
                <label class="form-label">Last Name <span class="required">*</span></label>
                <input type="text" class="form-control" id="lastName" placeholder="e.g., Doe">
            </div>

            <div class="form-group">
                <label class="form-label">Professional Title <span class="required">*</span></label>
                <input type="text" class="form-control" id="professionalTitle"
                    placeholder="e.g., Senior Software Engineer">
            </div>
        </div>

        <!-- STEP 2: Professional Summary -->
        <div id="step2-content" class="hidden">
            <div class="step-header">
                <h2 class="gradient-text">Tell your story 📖</h2>
                <p class="step-subtitle">What makes you unique? What are you passionate about?</p>
            </div>

            <div class="info-grid">
                <div class="info-card">
                    <div class="info-icon">💡</div>
                    <h4>Be Authentic</h4>
                    <p>Share your passion and drive</p>
                </div>
                <div class="info-card">
                    <div class="info-icon">🎯</div>
                    <h4>Highlight Impact</h4>
                    <p>What problems do you solve?</p>
                </div>
                <div class="info-card">
                    <div class="info-icon">⭐</div>
                    <h4>Stand Out</h4>
                    <p>Profiles with summaries get 2x more interviews</p>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">About Me <span class="required">*</span></label>
                <textarea class="form-control" id="summary" placeholder="Tell employers about yourself..."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Years of Experience</label>
                <input type="number" class="form-control" id="yearsExperience" min="0" max="50" placeholder="e.g., 5">
            </div>
        </div>

        <!-- STEP 3: Creative Category Selection with Search -->
        <div id="step3-content" class="hidden">
            <div class="step-header">
                <h2 class="gradient-text">What gets you excited? 🎨</h2>
                <p class="step-subtitle">Select all the areas you're interested in. Search to find specific categories!
                </p>
            </div>

            <!-- Search Bar for Categories -->
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="categorySearch"
                    placeholder="Search categories... (e.g., Programming, Design, Marketing)">
                <i class="fas fa-times clear-search" id="clearCategorySearch" onclick="clearCategorySearch()"></i>
            </div>

            <!-- Creative Category Bubbles -->
            <div class="categories-playground" id="categoriesContainer">
                <?php if (isset($skillCategories) && !empty($skillCategories)): ?>
                    <?php foreach ($skillCategories as $category): ?>
                        <div class="category-bubble" data-category-id="<?= $category['id'] ?>"
                            data-category-name="<?= htmlspecialchars($category['name']) ?>"
                            data-category-icon="<?= htmlspecialchars($category['icon']) ?>">

                            <span class="bubble-icon">
                                <i class="fas <?= htmlspecialchars($category['icon']) ?>"></i>
                            </span>
                            <span class="bubble-name"><?= htmlspecialchars($category['name']) ?></span>
                            <span class="bubble-check">✓</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Selected Categories Display -->
            <div class="selected-categories-container">
                <div class="selected-categories-title">
                    <i class="fas fa-check-circle"></i>
                    <span>Your selected interests (<span id="selectedCount">0</span>)</span>
                </div>
                <div class="selected-tags" id="selectedTags">
                    <div class="empty-tags-message">Click on bubbles above to select your interests</div>
                </div>
            </div>
        </div>

        <!-- STEP 4: Creative Skills Selection with Search -->
        <div id="step4-content" class="hidden">
            <div class="step-header">
                <h2 class="gradient-text">Showcase your superpowers! ⚡</h2>
                <p class="step-subtitle">Select skills you're proficient in. Search to find specific skills!</p>
            </div>

            <!-- Search Bar for Skills -->
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="skillSearch" placeholder="Search skills... (e.g., JavaScript, Python, React)">
                <i class="fas fa-times clear-search" id="clearSkillSearch" onclick="clearSkillSearch()"></i>
            </div>

            <!-- Category Filters -->
            <div class="category-filters" id="skillFilters">
                <button class="filter-chip active" onclick="filterSkills('all', event)">
                    <i class="fas fa-sparkles"></i> All Skills
                </button>
                <!-- Dynamic filters from selected categories will be added via JS -->
            </div>

            <!-- Skills Grid -->
            <div class="skills-grid" id="skillsGrid">
                <!-- Skills will be populated dynamically from PHP data via JS -->
            </div>

            <!-- Selected Skills Summary -->
            <div class="selected-skills-summary" id="selectedSkillsSummary">
                <div class="summary-title">
                    <i class="fas fa-check-circle"></i>
                    <span>Your selected skills (<span id="selectedSkillsCount">0</span>)</span>
                </div>
                <div class="selected-skills-list" id="selectedSkillsList">
                    <div class="empty-tags-message">Select skills above to add them here</div>
                </div>
            </div>
        </div>

        <!-- STEP 5: Experience -->
        <div id="step5-content" class="hidden">
            <div class="step-header">
                <h2 class="gradient-text">Your journey 🚀</h2>
                <p class="step-subtitle">Add your work experience (you can add more later)</p>
            </div>

            <div id="experiencesContainer">
                <!-- Experience entries will be added via JS -->
            </div>

            <button class="add-skill-btn" onclick="addExperience()">
                <i class="fas fa-plus"></i> Add Work Experience
            </button>
        </div>

        <!-- STEP 6: Education with School Search -->
        <div id="step6-content" class="hidden">
            <div class="step-header">
                <h2 class="gradient-text">Your learning path 📚</h2>
                <p class="step-subtitle">Add your educational background</p>
            </div>

            <div id="educationsContainer">
                <!-- Education entries will be added via JS -->
            </div>

            <button class="add-skill-btn" onclick="addEducation()">
                <i class="fas fa-plus"></i> Add Education
            </button>
        </div>

        <!-- STEP 7: Location & Preferences -->
        <div id="step7-content" class="hidden">
            <div class="step-header">
                <h2 class="gradient-text">Where do you want to work? 🌍</h2>
                <p class="step-subtitle">Help us find the right opportunities for you</p>
            </div>

            <div class="info-grid">
                <div class="info-card">
                    <div class="info-icon">📍</div>
                    <h4>Your Location</h4>
                    <p>Based on your registration</p>
                </div>
                <div class="info-card">
                    <div class="info-icon">🏠</div>
                    <h4>Remote Work</h4>
                    <p>Work from anywhere</p>
                </div>
                <div class="info-card">
                    <div class="info-icon">✈️</div>
                    <h4>Relocation</h4>
                    <p>Open to moving</p>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Your Location</label>
                <div class="location-display"
     style="background: var(--bg-tertiary); padding: 15px; border-radius: 16px;">
    <i class="fas fa-map-marker-alt" style="color: var(--primary-color); margin-right: 10px;"></i>
    <span id="userLocation">
        <?php
                        if (isset($userLocation) && !empty($userLocation['city']) && $userLocation['city'] !== 'Unknown'):
                            $locationParts = [];

                            if (!empty($userLocation['city']) && $userLocation['city'] !== 'Unknown') {
                                $locationParts[] = htmlspecialchars($userLocation['city']);
                            }
                            if (!empty($userLocation['region']) && $userLocation['region'] !== 'Unknown') {
                                $locationParts[] = htmlspecialchars($userLocation['region']);
                            }
                            if (!empty($userLocation['country']) && $userLocation['country'] !== 'Unknown') {
                                $locationParts[] = htmlspecialchars($userLocation['country']);
                            }

                            echo implode(', ', $locationParts);
                        else:
                            echo 'Location not available';
                        endif;
                        ?>
                    </span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Work Preferences</label>
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="remotePreference"> Remote work
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="relocationWilling"> Willing to relocate
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="openToWork" checked> Open to work
                    </label>
                </div>
            </div>
        </div>

        <!-- STEP 8: Review -->
        <div id="step8-content" class="hidden">
            <div class="step-header">
                <h2 class="gradient-text">Review your profile ✨</h2>
                <p class="step-subtitle">Everything look good? You can edit any section</p>
            </div>

            <div class="review-container" id="reviewContainer">
                <!-- Review content will be populated dynamically -->
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="wizard-footer">
        <button class="nav-btn btn-prev" onclick="prevStep()" id="prevBtn">
            <i class="fas fa-arrow-left"></i> Back
        </button>
        <button class="nav-btn btn-next" onclick="nextStep()" id="nextBtn">
            Continue <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>

<!-- Pass PHP data to JavaScript -->
<script>
    // Skill Categories from Database
    window.skillCategories = <?php echo json_encode($skillCategories ?? []); ?>;

    // Skills from Database
    window.skills = <?php echo json_encode($skills ?? []); ?>;

    // Schools from Database
    window.schools = <?php echo json_encode($schools ?? []); ?>;
</script>
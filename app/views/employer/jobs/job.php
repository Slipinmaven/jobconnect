<div class="wizard-container">
    <a href="/employer/jobs" class="back">
        <i class="fas fa-arrow-left"></i>
    </a>
    <div class="progress-section">
        <div class="header">
            <h1 style="margin: 0;">Create a New Job Posting</h1>
            <p style="color: var(--text-muted); margin-top: 0.5rem;">Fill in the details to attract the best candidates.</p>
        </div>
        <div class="steps">
            <div class="step active" data-step="0"><div class="step-number"><span>1</span></div><div class="step-label">Job Basics</div></div>
            <div class="step" data-step="1"><div class="step-number"><span>2</span></div><div class="step-label">Requirements & Duties</div></div>
            <div class="step" data-step="2"><div class="step-number"><span>3</span></div><div class="step-label">Employment Details</div></div>
            <div class="step" data-step="3"><div class="step-number"><span>4</span></div><div class="step-label">Compensation</div></div>
            <div class="step" data-step="4"><div class="step-number"><span>5</span></div><div class="step-label">Review</div></div>
            <div class="progress-line" id="progressLine"></div>
        </div>
    </div>

    <div class="content-area">
        <!-- Step 1: Job Basics -->
        <div class="step-content active" data-step="0">
            <h2 style="margin-bottom: 0.5rem; color: var(--primary-color); font-size: 2rem">Create Job</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Start with the basics - what's the job title and description?</p>
            <div class="form-group">
                <label class="form-label">Job Title <span style="color: var(--danger-500);">*</span></label>
                <input type="text" class="form-control" id="title" placeholder="e.g., Senior Frontend Developer">
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;" id="slugPreview"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Job Description <span style="color: var(--danger-500);">*</span></label>
                <textarea class="form-control" id="description" rows="4" placeholder="What does this role involve? What makes it exciting? Describe the team, culture, and impact..."></textarea>
            </div>
            
            <!-- Job Categories Section -->
            <div class="form-group">
                <label class="form-label">Job Categories <span style="color: var(--danger-500);">*</span></label>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                    <i class="fas fa-info-circle"></i> Select categories that best describe this job (can select multiple)
                </div>
                <div class="categories-container" id="categoriesContainer">
                    <!-- Category chips will be added here dynamically -->
                </div>
                <div class="category-search-box">
                    <input type="text" id="categorySearch" class="form-control" placeholder="Search or add new category...">
                    <div class="suggestions-dropdown" id="suggestionsDropdown"></div>
                </div>
                <div class="selected-categories" id="selectedCategories"></div>
            </div>
        </div>

        <!-- Step 2: Requirements & Responsibilities (Combined) -->
        <div class="step-content" data-step="1">
            <h2 style="margin-bottom: 0.5rem;">📋 Requirements & Responsibilities</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Add the must-have skills, qualifications, and what the candidate will be doing.</p>
            <div class="combined-lists">
                <!-- Requirements List -->
                <div class="list-card">
                    <div class="list-header">
                        <h3><i class="fas fa-check-circle" style="color: var(--success-500);"></i> Key Requirements</h3>
                        <p>Must-have skills, qualifications, experience</p>
                    </div>
                    <div class="list-items" id="requirementsList">
                        <div class="list-item"><div class="list-item-content"><i class="fas fa-check-circle"></i><span class="list-item-text">5+ years experience with React</span></div><div class="list-item-actions"><button onclick="removeRequirement(this)"><i class="fas fa-trash-alt"></i></button></div></div>
                        <div class="list-item"><div class="list-item-content"><i class="fas fa-check-circle"></i><span class="list-item-text">TypeScript proficiency</span></div><div class="list-item-actions"><button onclick="removeRequirement(this)"><i class="fas fa-trash-alt"></i></button></div></div>
                    </div>
                    <div class="add-item-form">
                        <input type="text" id="newRequirement" placeholder="e.g., Bachelor's degree in Computer Science">
                        <button class="add-btn" onclick="addRequirement()">+ Add</button>
                    </div>
                </div>

                <!-- Responsibilities List -->
                <div class="list-card">
                    <div class="list-header">
                        <h3><i class="fas fa-tasks" style="color: var(--primary-500);"></i> Key Responsibilities</h3>
                        <p>Day-to-day tasks and expectations</p>
                    </div>
                    <div class="list-items" id="responsibilitiesList">
                        <div class="list-item"><div class="list-item-content"><i class="fas fa-chevron-right"></i><span class="list-item-text">Lead frontend architecture decisions</span></div><div class="list-item-actions"><button onclick="removeResponsibility(this)"><i class="fas fa-trash-alt"></i></button></div></div>
                        <div class="list-item"><div class="list-item-content"><i class="fas fa-chevron-right"></i><span class="list-item-text">Mentor junior developers</span></div><div class="list-item-actions"><button onclick="removeResponsibility(this)"><i class="fas fa-trash-alt"></i></button></div></div>
                    </div>
                    <div class="add-item-form">
                        <input type="text" id="newResponsibility" placeholder="e.g., Write clean, maintainable code">
                        <button class="add-btn" onclick="addResponsibility()">+ Add</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Employment Details -->
        <div class="step-content" data-step="2">
            <h2 style="margin-bottom: 1rem;">🎯 Employment Details</h2>
            <div class="options-grid">
                <div class="option-card" data-type="full_time"><div class="option-icon">💼</div><div class="option-title">Full Time</div><div class="option-desc">40+ hours per week</div></div>
                <div class="option-card" data-type="part_time"><div class="option-icon">⏰</div><div class="option-title">Part Time</div><div class="option-desc">Flexible hours</div></div>
                <div class="option-card" data-type="contract"><div class="option-icon">📄</div><div class="option-title">Contract</div><div class="option-desc">Fixed term agreement</div></div>
                <div class="option-card" data-type="internship"><div class="option-icon">🎓</div><div class="option-title">Internship</div><div class="option-desc">Learn and grow</div></div>
            </div>
            <div class="form-group" style="margin-top: 1.5rem;">
                <label class="form-label">Experience Level</label>
                <select class="form-control" id="experience_level">
                    <option value="" disabled>Select experience level</option>
                    <option value="entry">Entry Level (0-2 years)</option>
                    <option value="junior">Junior (1-3 years)</option>
                    <option value="mid">Mid Level (3-5 years)</option>
                    <option value="senior">Senior (5+ years)</option>
                    <option value="lead">Lead / Manager (7+ years)</option>
                </select>
            </div>
            <div class="checkbox-group" style="margin-top: 1rem;">
                <input type="checkbox" id="is_remote">
                <label for="is_remote">🌍 This is a remote position</label>
            </div>
            <div class="checkbox-group" style="margin-top: 0.75rem;">
                <input type="checkbox" id="requires_resume" checked>
                <label for="requires_resume">📄 Require resume/CV for applications</label>
            </div>
        </div>

        <!-- Step 4: Compensation + Application Deadline & Allow Past Deadline -->
        <div class="step-content" data-step="3">
            <h2 style="margin-bottom: 1rem;">💰 Compensation Package</h2>
            <div class="form-group">
                <label class="form-label">Salary Range (Monthly in KES)</label>
                <div class="salary-range">
                    <div class="range-values" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Min: KES <span id="minValue">0</span></span>
                        <span>Max: KES <span id="maxValue">0</span></span>
                    </div>
                   
                    <!-- Typable fields for monthly salary -->
                    <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                        <div style="flex: 1;">
                            <label style="font-size: 0.75rem; color: var(--text-muted);">Minimum Monthly (KES)</label>
                            <input type="number" id="salaryMinInput" class="form-control" value="0" step="5000" min="0" style="margin-top: 0.25rem;">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 0.75rem; color: var(--text-muted);">Maximum Monthly (KES)</label>
                            <input type="number" id="salaryMaxInput" class="form-control" value="0" step="5000" min="0" style="margin-top: 0.25rem;">
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Currency</label>
                <div style="background: var(--bg-gray-100); padding: 0.875rem 1rem; border-radius: 1rem; color: var(--text-gray);">
                    <i class="fas fa-flag-checkered"></i> Kenyan Shilling (KES) - Only currency supported
                </div>
                <input type="hidden" id="currency" value="KES">
            </div>

            <!-- Application Deadline & Allow Past Deadline Toggle -->
            <div style="margin-top: 2rem; border-top: 1px solid rgba(0,0,0,0.1); padding-top: 1.5rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">📅 Application Settings</h3>
                <div class="form-group">
                    <label class="form-label">Application Deadline</label>
                    <input type="date" id="applicationDeadline" class="form-control">
                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;">Optional — if not set, applications remain open indefinitely</div>
                </div>
                
                <!-- Toggle for allowing applications past deadline -->
                <div class="toggle-group" style="margin-top: 1rem; display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; background: var(--bg-gray-50); border-radius: 0.75rem;">
                    <div>
                        <div style="font-weight: 500;">Allow applications past deadline</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">If enabled, job seekers can still apply after the deadline date</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="allowPastDeadline">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Step 5: Review -->
        <div class="step-content" data-step="4">
            <h2 style="margin-bottom: 1rem;">📋 Review Your Job Posting</h2>
            <div id="reviewContent"></div>
            <div class="review-card" style="background: rgba(16, 185, 129, 0.1); margin-bottom: 0;">
                <p style="font-size: 0.875rem;">✨ Review everything above. Choose "Publish" to make it live immediately, or "Save as Draft" to finish later.</p>
            </div>
        </div>
    </div>

    <div class="nav-buttons">
        <button class="nav-btn btn-prev" id="prevBtn" disabled>← Back</button>
        <div class="action-buttons" id="actionButtons" style="display: none;">
            <button class="nav-btn btn-draft" id="saveDraftBtn"><i class="fas fa-save"></i> Save as Draft</button>
            <button class="nav-btn btn-publish" id="publishBtn"><i class="fas fa-rocket"></i> Publish Job</button>
        </div>
        <button class="nav-btn btn-next" id="nextBtn">Continue →</button>
    </div>
</div>


<?php
$existingProfile = $employerProfile['profile'] ?? [];
$existingIndustry = $employerProfile['industry'] ?? [];
$employerSetupValues = [
    'type' => $existingProfile['employer_type'] ?? '',
    'name' => $existingProfile['company_name'] ?? '',
    'description' => $existingProfile['company_description'] ?? '',
    'industryId' => $existingProfile['industry_id'] ?? '',
    'industrySlug' => $existingIndustry['slug'] ?? '',
    'industryName' => $existingIndustry['name'] ?? '',
    'contactEmail' => $existingProfile['contact_email'] ?? ($_SESSION['user_email'] ?? ''),
    'contactPhone' => $existingProfile['contact_phone'] ?? '',
    'contactName' => $existingProfile['contact_name'] ?? '',
    'website' => $existingProfile['website'] ?? '',
    'avatar' => $existingProfile['avatar'] ?? ($employerProfile['user']['avatar'] ?? '')
];
?>
<div class="wizard-container">
    <!-- Wizard Header -->
    <div class="wizard-header">
        <h1>Complete Your Employer Profile</h1>
        <p>Let's set up your profile in just a few steps</p>
    </div>

    <!-- Progress Steps -->
    <div class="progress-wrapper">
        <div class="progress-steps">
            <div class="step active" id="step1">
                1
                <span class="step-label">Type</span>
            </div>
            <div class="step" id="step2">
                2
                <span class="step-label">Basic Info</span>
            </div>
            <div class="step" id="step3">
                3
                <span class="step-label">Contact</span>
            </div>
            <div class="step" id="step4">
                4
                <span class="step-label">Logo</span>
            </div>
            <div class="step" id="step5">
                5
                <span class="step-label">Website</span>
            </div>
            <div class="step" id="step6">
                6
                <span class="step-label">Review</span>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="wizard-content">
        <!-- STEP 1: Account Type -->
        <div id="step1-content">
            <h2 class="gradient-text text-center">Welcome! First, tell us about yourself</h2>
            <p class="text-center" style="color: var(--text-muted); margin-bottom: 30px;">Choose the option that best describes you</p>
            
            <div class="type-cards">
                <div class="type-card" onclick="selectType('company')" id="company-card">
                    <div class="type-icon">🏢</div>
                    <h3>Company</h3>
                    <p>Register your business, organization, or corporation with multiple team members</p>
                </div>
                
                <div class="type-card" onclick="selectType('individual')" id="individual-card">
                    <div class="type-icon">👤</div>
                    <h3>Individual</h3>
                    <p>Hire as a freelancer, solopreneur, or independent contractor</p>
                </div>
            </div>
        </div>

        <!-- STEP 2: Basic Info -->
        <div id="step2-content" class="hidden">
            <div class="alert alert-info" style="margin-bottom: 25px;">
                <i class="fas fa-info-circle"></i> 
                <strong>Required fields</strong> - These help candidates understand who you are
            </div>

            <div id="company-fields" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Company Name <span class="required">*</span></label>
                    <input type="text" class="form-control" id="companyName" placeholder="e.g., TechCorp Solutions">
                    <div class="invalid-feedback" id="companyName-error">Company name is required</div>
                </div>
            </div>

            <div id="individual-fields" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Your Full Name <span class="required">*</span></label>
                    <input type="text" class="form-control" id="individualName" placeholder="e.g., John Doe">
                    <div class="invalid-feedback" id="individualName-error">Name is required</div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Tell us about yourself/your company <span class="required">*</span></label>
                <textarea class="form-control" id="description" rows="4" placeholder="What do you do? What kind of talent are you looking for? What's your mission?"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Industry <span class="required">*</span></label>
                <div class="industry-select">
                    <input type="text" class="industry-search" id="industrySearch" placeholder="Type to search or select industry..." autocomplete="off">
                    <div class="industry-dropdown" id="industryDropdown">
                        <?php if (isset($industries) && !empty($industries)): ?>
                            <?php foreach ($industries as $industry): ?>
                                <div class="industry-option" 
                                                     data-id="<?= htmlspecialchars($industry['id']) ?>" 
                                                     data-slug="<?= htmlspecialchars($industry['slug']) ?>" 
                                                     data-name="<?= htmlspecialchars($industry['name']) ?>">
                                    <?= htmlspecialchars($industry['name']) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback message if no industries (should not happen) -->
                            <div class="industry-option no-results" style="color: var(--text-muted); cursor: default;">
                                No industries available
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="industryId">
                    <input type="hidden" id="industrySlug">
                </div>
                <div class="invalid-feedback" id="industry-error">Please select an industry</div>
            </div>
        </div>

        <!-- STEP 3: Contact Info -->
        <div id="step3-content" class="hidden">
            <div class="alert alert-info" style="margin-bottom: 25px;">
                <i class="fas fa-envelope"></i> 
                <strong>Why we need your contact info?</strong> Candidates will use these to reach you about job opportunities
            </div>

            <div class="contact-info-grid">
                <div class="contact-info-card">
                    <div class="contact-card-header">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4>Email Address <span class="required">*</span></h4>
                    </div>
                    <input type="email" class="form-control" id="contactEmail" placeholder="hr@yourcompany.com">
                    <ul class="contact-benefits">
                        <li><i class="fas fa-check-circle"></i> Receive job applications</li>
                        <li><i class="fas fa-check-circle"></i> Verify your identity</li>
                    </ul>
                    <div class="invalid-feedback" id="email-error">Valid email is required</div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-card-header">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h4>Phone Number</h4>
                    </div>
                    <input type="tel" class="form-control" id="contactPhone" placeholder="+1 234 567 890">
                    <ul class="contact-benefits">
                        <li><i class="fas fa-check-circle"></i> Quick communication</li>
                        <li><i class="fas fa-check-circle"></i> Optional but recommended</li>
                    </ul>
                </div>
            </div>

            <div class="contact-info-card" style="margin-top: 20px;">
                <div class="contact-card-header">
                    <div class="contact-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h4>Contact Person</h4>
                </div>
                <input type="text" class="form-control" id="contactName" placeholder="Who should candidates contact?">
                <ul class="contact-benefits">
                    <li><i class="fas fa-check-circle"></i> Personal touch for candidates</li>
                    <li><i class="fas fa-check-circle"></i> Helps build trust</li>
                </ul>
            </div>
        </div>

        <!-- STEP 4: Logo Upload -->
        <div id="step4-content" class="hidden">
            <h3 class="gradient-text" style="margin-bottom: 15px;">Add your logo</h3>
            
            <div class="alert alert-warning">
                <i class="fas fa-star"></i> 
                <strong>Did you know?</strong> Profiles with logos get 3x more views!
            </div>
            
            <div class="logo-upload-container">
                <div class="logo-preview" id="logoPreview">
                    <img id="logoPreviewImg" src="" alt="Logo preview" style="display: none; width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                    <div id="logoPlaceholder" style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <i class="fas fa-building" style="font-size: 32px;"></i>
                        <span>Company Logo</span>
                    </div>
                </div>
                
                <div class="logo-actions">
                    <button type="button" class="btn btn-primary" id="logoUpload">
                        <i class="fas fa-upload"></i> Upload Logo
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="skipLogo()">
                        Skip for now
                    </button>
                    <button type="button" class="btn btn-danger" onclick="removeLogo()" id="removeLogoBtn" style="display: none;">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
                <p class="help-text">Accepted formats: JPEG, PNG, GIF, WEBP (Max 2MB)</p>
            </div>
        </div>

        <!-- STEP 5: Website -->
        <div id="step5-content" class="hidden">
            <h3 class="gradient-text" style="margin-bottom: 15px;">Do you have a website?</h3>
            
            <div class="alert alert-info">
                <i class="fas fa-globe"></i> 
                <strong>Why add your website?</strong> Candidates can learn more about your work
            </div>
            
            <div class="option-buttons">
                <button class="option-btn" onclick="setWebsiteOption('yes')" id="website-yes">Yes, I have one</button>
                <button class="option-btn" onclick="setWebsiteOption('no')" id="website-no">No, skip</button>
            </div>

            <div class="website-input" id="websiteInput" style="display: none;">
                <input type="url" class="form-control" id="website" placeholder="https://yourcompany.com">
            </div>
        </div>

        <!-- STEP 6: Review -->
        <div id="step6-content" class="hidden">
            <h3 class="gradient-text" style="margin-bottom: 20px;">Review your information</h3>
            
            <div class="review-card">
                <div class="review-section">
                    <div class="review-header">
                        <span class="review-label">Account Type</span>
                        <button class="review-edit" onclick="goToStep(1)">Edit</button>
                    </div>
                    <div class="review-value" id="review-type"></div>
                </div>

                <div class="review-section">
                    <div class="review-header">
                        <span class="review-label">Name</span>
                        <button class="review-edit" onclick="goToStep(2)">Edit</button>
                    </div>
                    <div class="review-value" id="review-name"></div>
                </div>

                <div class="review-section">
                    <div class="review-header">
                        <span class="review-label">Description</span>
                        <button class="review-edit" onclick="goToStep(2)">Edit</button>
                    </div>
                    <div class="review-value" id="review-description"></div>
                </div>

                <div class="review-section">
                    <div class="review-header">
                        <span class="review-label">Industry</span>
                        <button class="review-edit" onclick="goToStep(2)">Edit</button>
                    </div>
                    <div class="review-value" id="review-industry"></div>
                </div>

                <div class="review-section">
                    <div class="review-header">
                        <span class="review-label">Contact Email</span>
                        <button class="review-edit" onclick="goToStep(3)">Edit</button>
                    </div>
                    <div class="review-value" id="review-email"></div>
                </div>

                <div class="review-section">
                    <div class="review-header">
                        <span class="review-label">Contact Phone</span>
                        <button class="review-edit" onclick="goToStep(3)">Edit</button>
                    </div>
                    <div class="review-value" id="review-phone"></div>
                </div>

                <div class="review-section">
                    <div class="review-header">
                        <span class="review-label">Contact Person</span>
                        <button class="review-edit" onclick="goToStep(3)">Edit</button>
                    </div>
                    <div class="review-value" id="review-contact-name"></div>
                </div>

                <div class="review-section">
                    <div class="review-header">
                        <span class="review-label">Logo</span>
                        <button class="review-edit" onclick="goToStep(4)">Edit</button>
                    </div>
                    <div class="review-value" id="review-logo"></div>
                </div>

                <div class="review-section">
                    <div class="review-header">
                        <span class="review-label">Website</span>
                        <button class="review-edit" onclick="goToStep(5)">Edit</button>
                    </div>
                    <div class="review-value" id="review-website"></div>
                </div>
            </div>

            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                By submitting, you agree to our terms and conditions
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="wizard-footer">
        <button class="nav-btn btn-prev" onclick="prevStep()" id="prevBtn">
            <i class="fas fa-arrow-left"></i> Back
        </button>
        <button class="nav-btn btn-next" onclick="nextStep()" id="nextBtn" disabled>
            Continue <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer"></div>
<script>
window.employerProfileSetup = <?= json_encode(
    $employerSetupValues,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;
</script>

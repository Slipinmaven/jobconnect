<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Forgot Password</h1>
            <p>Enter your email to reset your password</p>
        </div>

        <!-- Status Message -->
        <div id="statusMessage" class="status-message">
            <span id="statusText"></span>
        </div>

        <!-- Step 1: Email Form -->
        <div id="step1">
            <form id="forgotPasswordForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="your.email@example.com" required>
                    </div>
                    <div class="error-message" id="emailError"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="sendResetBtn">
                    Send Reset Link
                </button>
            </form>
            
            <!-- Resend button (appears after first request) -->
            <p class="auth-link" id="resendContainer" style="display: none; margin-top: 1rem;">
                Didn't receive email? 
                <button id="resendBtn" class="btn-link" style="background: none; border: none; color: var(--primary-color); cursor: pointer; text-decoration: underline;">
                    Resend
                </button>
            </p>
        </div>

        <!-- Step 2: Reset Password Form (Initially Hidden) -->
        <div id="step2" style="display: none;">
            <div class="info-text">
                <i class="fas fa-lock"></i> Password must have:
            </div>

            <form id="resetPasswordForm">
                <!-- Hidden fields for token and email from URL -->
                <input type="hidden" id="resetToken" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
                <input type="hidden" id="resetEmail" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">

                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="newPassword" name="newPassword" placeholder="Enter new password" required>
                        <i class="fas fa-eye toggle-password" id="toggleNewPassword"></i>
                    </div>
                    <div class="error-message" id="newPasswordError"></div>
                    
                    <!-- Hint -->
                    <div class="hint-text">
                        <i class="fas fa-info-circle"></i>
                        Password must be at least 6 characters with uppercase, number, and special character
                    </div>
                </div>

                <!-- Password Strength Meter -->
                <div class="password-strength" id="passwordStrength">
                    <div class="strength-bar">
                        <div class="strength-progress" id="strengthProgress"></div>
                    </div>
                    <span class="strength-text" id="strengthText">Password strength: None</span>
                    
                    <ul class="requirements-list">
                        <li class="requirement-item" id="reqLength">
                            <i class="fas fa-circle"></i> 6+ characters
                        </li>
                        <li class="requirement-item" id="reqUppercase">
                            <i class="fas fa-circle"></i> Uppercase letter
                        </li>
                        <li class="requirement-item" id="reqNumber">
                            <i class="fas fa-circle"></i> Number
                        </li>
                        <li class="requirement-item" id="reqSpecial">
                            <i class="fas fa-circle"></i> Special character (!@#$%^&*)
                        </li>
                    </ul>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm new password" required>
                        <i class="fas fa-eye toggle-password" id="toggleConfirmPassword"></i>
                    </div>
                    <div class="error-message" id="confirmPasswordError"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="resetPasswordBtn" disabled>
                    Reset Password
                </button>
            </form>
        </div>

        <!-- Step 3: Success Message (Initially Hidden) -->
        <div id="step3" style="display: none;">
            <div class="success-message">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h2>Password Changed!</h2>
                <p>Your password has been reset successfully</p>
                <p class="auth-link" style="margin-top: 1rem;">
                    Redirecting to login in 5 seconds...
                </p>
                <a href="/login" class="btn btn-primary">Go to Login</a>
            </div>
        </div>

        <div class="auth-links">
            <p class="auth-link">
                <i class="fas fa-arrow-left"></i> Back to <a href="/login">Login</a>
            </p>
        </div>
    </div>
</div>

<!-- Loading Indicator -->
<div id="loadingIndicator" class="loading-overlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <p>Processing your request...</p>
    </div>
</div>

<!-- Theme Toggle (Optional - can be moved to main layout) -->
<div class="theme-toggle" id="themeToggle">
    <i class="fas fa-sun"></i>
    <i class="fas fa-moon"></i>
</div>


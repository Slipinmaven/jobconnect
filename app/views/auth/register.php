<!-- This is ONLY the content that will be inserted into the layout's $content variable -->
<?php 
if (isset($_SESSION['errors'])) {
    echo "<pre>";
    print_r($_SESSION['errors']);
    echo "</pre>";
}
$preSelectedRole = $_GET['role'] ?? null;
?>
<div class="auth-container">
    <div class="auth-header">
        <!-- Your Logo Here -->
        <img src="/images/logo.png" alt="JobConnect" class="auth-logo" style="max-width: 200px;">
        <h1>Create Account</h1>
        <p>Join thousands of professionals</p>
    </div>

    <div class="auth-form">
        <!-- Error Message -->
        <div id="errorMessage" class="alert alert-danger" style="display: none;">
            <i class="fas fa-exclamation-circle"></i>
            <span id="errorText"></span>
        </div>

        <!-- Registration Form -->
        <form id="registerForm" onsubmit="return handleSubmit(event)">
            <!-- Email Field -->
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="you@example.com"
                        autocomplete="off"
                        oninput="validateEmail()"
                        onblur="hideEmailHint()"
                        required>
                </div>
                <div id="emailHint" class="email-hint">
                    <i class="fas fa-info-circle"></i>
                    <span>Enter a valid email address</span>
                </div>
            </div>

            <!-- Password Field -->
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a password"
                        oninput="validatePassword()"
                        onfocus="showPasswordHints()"
                        onblur="hidePasswordHints()"
                        required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
                </div>

                <!-- Password Hints & Checklist -->
                <div id="passwordHints" class="hint-box">
                    <div class="hint-title">Password Requirements</div>
                    <ul class="password-checklist">
                        <li id="length">
                            <i class="fas fa-circle"></i>
                            At least 8 characters
                        </li>
                        <li id="uppercase">
                            <i class="fas fa-circle"></i>
                            One uppercase letter
                        </li>
                        <li id="lowercase">
                            <i class="fas fa-circle"></i>
                            One lowercase letter
                        </li>
                        <li id="number">
                            <i class="fas fa-circle"></i>
                            One number
                        </li>
                        <li id="special">
                            <i class="fas fa-circle"></i>
                            One special character (!@#$%^&*)
                        </li>
                    </ul>

                    <!-- Password Strength Meter -->
                    <div class="strength-meter">
                        <div id="strengthMeter" class="strength-meter-fill"></div>
                    </div>
                    <div id="strengthText" class="strength-text">Enter password</div>
                </div>
            </div>

            <!-- Confirm Password Field -->
            <div class="form-group">
                <label>Confirm Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input
                        type="password"
                        id="confirmPassword"
                        name="confirm_password"
                        placeholder="Re-enter password"
                        oninput="validateConfirmPassword()"
                        required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('confirmPassword', this)"></i>
                </div>
                <div id="confirmHint" class="email-hint" style="display: none;">
                    <i class="fas fa-times-circle"></i>
                    <span>Passwords do not match</span>
                </div>
            </div>

            <!-- Role Selection -->
            <div class="form-group">
                <label>I am a</label>
                <div class="role-selector">
                    <div class="role-option">
                        <input type="radio" name="role" id="jobseeker" value="jobseeker" <?= ($preSelectedRole === 'jobseeker' || !$preSelectedRole) ? 'checked' : '' ?>>
                        <label for="jobseeker">
                            <i class="fas fa-user-tie"></i>
                            <span>Job Seeker</span>
                            <small>Looking for opportunities</small>
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" name="role" id="employer" value="employer" <?= ($preSelectedRole === 'employer') ? 'checked' : '' ?>>
                        <label for="employer">
                            <i class="fas fa-building"></i>
                            <span>Employer</span>
                            <small>Hiring talent</small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Terms Checkbox -->
            <div class="terms-checkbox">
                <input type="checkbox" id="terms" onchange="validateForm()">
                <label for="terms">
                    I agree to the <a href="/terms">Terms of Service</a> and
                    <a href="/privacy">Privacy Policy</a>
                </label>
            </div>

            <!-- Submit Button -->
            <div class="submitArea">
                <button type="submit" id="submitBtn" class="btn btn-primary btn-block" disabled>
                    Create Account
                </button>
            </div>
        </form>

        <!-- Divider -->
        <div class="divider">
            <span>or</span>
        </div>

        <!-- Google Sign In Button -->
        <div class="google" style="width: 100%; display: flex; justify-content: center;">
            <button class="btn-google" onclick="handleGoogleSignIn()">
                <i class="fab fa-google"></i>
                Continue with Google
            </button>
        </div>

        <!-- Login Link -->
        <div class="login-link" style="margin-bottom: 2rem; text-align: center;">
            Already have an account? <a href="/login">Sign In</a>
        </div>
    </div>
</div>
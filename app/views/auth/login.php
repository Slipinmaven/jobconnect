<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Welcome Back</h1>
            <p class="info-text">Sign in to continue to your account</p>
        </div>

        <!-- Status messages for JS feedback -->
        <div id="status" class="status-message" style="display: none;">
            <span id="status-text"></span>
        </div>

        <form action="/api/login" method="POST" id="loginForm" class="auth-form">
            <!-- Email Field -->
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email"
                        placeholder="your.email@example.com"
                        value="<?php echo htmlspecialchars($_GET['email'] ?? $_POST['email'] ?? ''); ?>"
                        required
                        autofocus>
                </div>
                <div class="error-message" id="email-error"></div>
            </div>

            <!-- Password Field -->
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password"
                        placeholder="Enter your password"
                        required>
                    <i class="fas fa-eye toggle-password" id="togglePassword" style="cursor: pointer; right: 15px; left: auto;"></i>
                </div>
                <div class="error-message" id="password-error"></div>
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="form-options">
                <label class="checkbox-container">
                    <input type="checkbox" name="remember" id="remember" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                    <span class="checkbox-label">Remember me</span>
                </label>
                <a href="/forgot-password" class="forgot-link">Forgot Password?</a>
            </div>

            <!-- Submit Button -->
            <button type="submit" id="btn" class="btn-primary btn-block">
                Sign In
            </button>

            <!-- Register Link -->
            <div class="auth-links">
                <p class="auth-link">
                    Don't have an account? <a href="/register">Create one now</a>
                </p>

                <!-- Verify Account Link (for unverified users) -->
                <p class="verify-link">
                    Didn't receive verification email? <a href="/reverify">Verify Account</a>
                </p>
            </div>
        </form>

        <!-- Social Login Divider -->
        <div class="divider">
            <span class="divider-text">Or continue with</span>
        </div>

        <!-- Social Login Buttons - Only Google -->
        <div class="social-login">
            <button onclick="handleGoogleSignIn()" class="btn-social btn-google">
                <i class="fab fa-google"></i>
                <span>Google</span>
            </button>
        </div>
    </div>
</div>

<!-- Loading indicator for JS -->
<div id="loadingIndicator" class="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="spinner"></div>
        <p>Signing you in...</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('loginForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const emailError = document.getElementById('email-error');
        const passwordError = document.getElementById('password-error');
        const statusDiv = document.getElementById('status');
        const statusText = document.getElementById('status-text');
        const submitBtn = document.getElementById('btn');
        const togglePassword = document.getElementById('togglePassword');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const originalButtonText = submitBtn ? submitBtn.textContent : 'Sign In';

        // Check for message in URL (e.g., from verification)
        const urlParams = new URLSearchParams(window.location.search);
        const message = urlParams.get('message');
        const emailSent = urlParams.get('email_sent');

        if (message === 'verified') {
            showSuccess('Email verified successfully! You can now login.');
        } else if (message === 'reset-link-sent') {
            showSuccess('Password reset link sent to your email! Please check your inbox and follow the link to set a new password.');
        } else if (emailSent === '1') {
            showSuccess('Verification email sent! Please check your inbox.');
        } else if (message === 'reverify') {
            showError('Please verify your email before logging in.');
        }

        // Toggle password visibility
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Email validation function
        function validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Show error message
        function showError(message, field = null) {
            if (field === 'email' && emailError) {
                emailError.textContent = message;
                emailError.style.display = 'block';
                emailInput.classList.add('error');
            } else if (field === 'password' && passwordError) {
                passwordError.textContent = message;
                passwordError.style.display = 'block';
                passwordInput.classList.add('error');
            } else if (statusDiv) {
                statusDiv.style.display = 'block';
                statusDiv.className = 'status-message error';
                statusText.textContent = message;
            }
        }

        // Clear error message
        function clearError(field = null) {
            if (field === 'email' && emailError) {
                emailError.textContent = '';
                emailError.style.display = 'none';
                emailInput.classList.remove('error');
            } else if (field === 'password' && passwordError) {
                passwordError.textContent = '';
                passwordError.style.display = 'none';
                passwordInput.classList.remove('error');
            } else {
                if (emailError) {
                    emailError.textContent = '';
                    emailError.style.display = 'none';
                    emailInput.classList.remove('error');
                }
                if (passwordError) {
                    passwordError.textContent = '';
                    passwordError.style.display = 'none';
                    passwordInput.classList.remove('error');
                }
                if (statusDiv) {
                    statusDiv.style.display = 'none';
                }
            }
        }

        // Show success message
        function showSuccess(message) {
            if (statusDiv) {
                statusDiv.style.display = 'block';
                statusDiv.className = 'status-message success';
                statusText.textContent = message;
            }
        }

        // Set loading state
        function setLoading(isLoading) {
            if (isLoading) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Signing in...';
                if (loadingIndicator) {
                    loadingIndicator.style.display = 'flex';
                }
            } else {
                submitBtn.disabled = false;
                submitBtn.textContent = originalButtonText;
                if (loadingIndicator) {
                    loadingIndicator.style.display = 'none';
                }
            }
        }

        // Real-time email validation
        emailInput.addEventListener('input', function() {
            const email = this.value.trim();
            if (email === '') {
                clearError('email');
            } else if (!validateEmail(email)) {
                showError('Please enter a valid email address', 'email');
            } else {
                clearError('email');
            }
        });

        emailInput.addEventListener('blur', function() {
            const email = this.value.trim();
            if (email === '') {
                showError('Email is required', 'email');
            } else if (!validateEmail(email)) {
                showError('Please enter a valid email address', 'email');
            } else {
                clearError('email');
            }
        });

        // Password validation (basic)
        passwordInput.addEventListener('input', function() {
            if (this.value.trim() === '') {
                clearError('password');
            } else if (this.value.length < 6) {
                showError('Password must be at least 6 characters', 'password');
            } else {
                clearError('password');
            }
        });

        passwordInput.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                showError('Password is required', 'password');
            } else if (this.value.length < 6) {
                showError('Password must be at least 6 characters', 'password');
            } else {
                clearError('password');
            }
        });

        // Form submission
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            clearError();

            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();

            // Validate email
            if (!email) {
                showError('Email is required', 'email');
                emailInput.focus();
                return;
            }
            if (!validateEmail(email)) {
                showError('Please enter a valid email address', 'email');
                emailInput.focus();
                return;
            }

            // Validate password
            if (!password) {
                showError('Password is required', 'password');
                passwordInput.focus();
                return;
            }
            if (password.length < 6) {
                showError('Password must be at least 6 characters', 'password');
                passwordInput.focus();
                return;
            }

            setLoading(true);

            // Create JSON payload
            const payload = {
                email: email,
                password: password,
                remember: document.getElementById('remember').checked
            };

            try {
                const response = await fetch('/api/login', {
                    method: 'POST',
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showSuccess(data.message || 'Login successful! Redirecting...');

                    if(data.two_factor_required) {
                        // Redirect to 2FA verification page
                        setTimeout(() => {
                            window.location.href = '/2fa-verify?email=' + encodeURIComponent(email);
                        }, 2000);
                        return;
                    }else if(data.data.requires_verification) {
                        Toast.warning('Please verify your email before logging in. Redirecting to verification page...');
                        setTimeout(() => {
                            window.location.href = '/reverify?from=login&email=' + encodeURIComponent(email);
                        }, 2000);
                    }
                    setTimeout(() => {
                        window.location.href = data.data.redirect;
                        console.log('Redirecting to:', data.data.redirect);
                    }, 1500);
                } else {
                    // Handle specific error cases
                    if (data.message?.includes('verify')) {
                        // Email not verified - redirect to reverify
                        setTimeout(() => {
                            window.location.href = '/reverify?from=login&email=' + encodeURIComponent(email);
                        }, 1000);
                    }
                    showError(data.message || 'Invalid email or password');
                }
            } catch (error) {
                console.error('Login error:', error);
                showError('Network error. Please check your connection.');
            } finally {
                setLoading(false);
            }
        });
    });

    // Google Sign In
    function handleGoogleSignIn() {
        const urlParams = new URLSearchParams(window.location.search);
        const role = urlParams.get('role') || 'jobseeker';
        window.location.href = '/auth/google?role=' + encodeURIComponent(role);
    }
</script>
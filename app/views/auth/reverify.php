<div class="reverify-container">
    <div class="reverify-card">
        <div class="reverify-header">
            <h1><?php echo htmlspecialchars($title ?? 'Resend Verification Email'); ?></h1>
            <p class="info-text"><?php echo htmlspecialchars($message ?? 'Enter your email address to verify your account'); ?></p>
        </div>

        <!-- Status messages for JS feedback -->
        <div id="status" class="status-message" style="display: none;">
            <span id="status-text"></span>
        </div>

        <form action="/api/reverifyEmail" method="POST" id="reverifyForm" class="reverify-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email"
                        placeholder="your.email@example.com"
                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        required>
                </div>
                <div class="error-message" id="email-error"></div>
            </div>

            <!-- Hidden field for mode -->
            <input type="hidden" name="mode" value="<?php echo htmlspecialchars($mode ?? 'direct'); ?>">

            <p class="info-text">Didn't receive verification code? <a href="/reverify" id="resendLink">Resend Code</a> <span id="timerDisplay" style="display: none; color: var(--text-muted);"></span></p>

            <button type="submit" id="btn" class="btn-primary">
                <?php echo htmlspecialchars($button_text ?? 'Send Verification Code'); ?>
            </button>

            <p class="back-link">
                Already Verified? <a href="/login">Back to Login</a>
            </p>
        </form>
    </div>
</div>

<!-- Loading indicator for JS -->
<div id="loadingIndicator" class="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="spinner"></div>
        <p>Sending verification email...</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('reverifyForm');
        const emailInput = document.getElementById('email');
        const emailError = document.getElementById('email-error');
        const statusDiv = document.getElementById('status');
        const statusText = document.getElementById('status-text');
        const submitBtn = document.getElementById('btn');
        const resendLink = document.getElementById('resendLink');
        const timerDisplay = document.getElementById('timerDisplay');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const originalButtonText = submitBtn ? submitBtn.textContent : 'Send Verification Code';

        let resendTimer = null;
        let resendCooldown = 60;

        function validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function showError(message) {
            if (emailError) {
                emailError.textContent = message;
                emailError.style.display = 'block';
                emailInput.classList.add('error');
            }
            if (statusDiv) {
                statusDiv.style.display = 'block';
                statusDiv.className = 'status-message error';
                statusText.textContent = message;
            }
        }

        function clearError() {
            if (emailError) {
                emailError.textContent = '';
                emailError.style.display = 'none';
                emailInput.classList.remove('error');
            }
            if (statusDiv) {
                statusDiv.style.display = 'none';
            }
        }

        function showSuccess(message) {
            if (statusDiv) {
                statusDiv.style.display = 'block';
                statusDiv.className = 'status-message success';
                statusText.textContent = message;
            }
        }

        function setLoading(isLoading) {
            if (isLoading) {
                submitBtn.disabled = true;
                resendLink.style.pointerEvents = 'none';
                resendLink.style.opacity = '0.5';
                submitBtn.textContent = 'Sending...';
                if (loadingIndicator) {
                    loadingIndicator.style.display = 'flex';
                }
            } else {
                submitBtn.disabled = false;
                resendLink.style.pointerEvents = 'auto';
                resendLink.style.opacity = '1';
                submitBtn.textContent = originalButtonText;
                if (loadingIndicator) {
                    loadingIndicator.style.display = 'none';
                }
            }
        }

        function startResendTimer() {
            resendLink.style.display = 'none';
            timerDisplay.style.display = 'inline';

            let timeLeft = resendCooldown;
            timerDisplay.textContent = `(Wait ${timeLeft}s)`;

            resendTimer = setInterval(() => {
                timeLeft--;
                timerDisplay.textContent = `(Wait ${timeLeft}s)`;

                if (timeLeft <= 0) {
                    clearInterval(resendTimer);
                    resendTimer = null;
                    resendLink.style.display = 'inline';
                    timerDisplay.style.display = 'none';
                }
            }, 1000);
        }

        async function resendVerification(email) {
            clearError();
            setLoading(true);

            // Get mode from hidden input
            const modeInput = document.querySelector('input[name="mode"]');
            const mode = modeInput ? modeInput.value : 'direct';

            // Create JSON payload
            const payload = {
                email: email,
                mode: mode
            };

            try {
                const response = await fetch('/api/reverifyEmail', {
                    method: 'POST',
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                // Check if response is OK
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Server error:', errorText);
                    showError('Server error. Please try again.');
                    return false;
                }

                const data = await response.json();

                if (data.success) {
                    showSuccess(data.message || 'Verification email sent successfully!');
                    startResendTimer();
                    return true;
                } else {
                    showError(data.message || 'Failed to send verification email. Please try again.');
                    return false;
                }
            } catch (error) {
                console.error('Fetch error:', error);
                showError('Network error. Please check your connection and try again.');
                return false;
            } finally {
                setLoading(false);
            }
        }

        // Form submission (button)
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = emailInput.value.trim();

            if (!email) {
                showError('Email is required');
                emailInput.focus();
                return;
            }
            if (!validateEmail(email)) {
                showError('Please enter a valid email address');
                emailInput.focus();
                return;
            }

            const success = await resendVerification(email);

            if (success) {
                setTimeout(() => {
                    window.location.href = '/login?email_sent=1';
                }, 3000);
            }
        });

        // Resend link click
        resendLink.addEventListener('click', async function(e) {
            e.preventDefault();

            const email = emailInput.value.trim();

            if (!email) {
                showError('Email is required');
                emailInput.focus();
                return;
            }
            if (!validateEmail(email)) {
                showError('Please enter a valid email address');
                emailInput.focus();
                return;
            }

            if (resendTimer) {
                return;
            }

            await resendVerification(email);
        });

        emailInput.addEventListener('input', function() {
            const email = this.value.trim();
            if (email === '') {
                clearError();
            } else if (!validateEmail(email)) {
                showError('Please enter a valid email address');
            } else {
                clearError();
            }
        });

        emailInput.addEventListener('blur', function() {
            const email = this.value.trim();
            if (email === '') {
                showError('Email is required');
            } else if (!validateEmail(email)) {
                showError('Please enter a valid email address');
            } else {
                clearError();
            }
        });

        const urlParams = new URLSearchParams(window.location.search);
        const emailFromUrl = urlParams.get('email');
        if (emailFromUrl && emailInput) {
            emailInput.value = emailFromUrl;
            if (!validateEmail(emailFromUrl)) {
                showError('Please enter a valid email address');
            }
        }
    });
</script>
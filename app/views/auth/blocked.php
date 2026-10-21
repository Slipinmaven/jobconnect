<!-- Account Blocked Page -->
<div class="auth-container">
    <div class="auth-header">
        <div class="blocked-icon">
            <i class="fas fa-lock"></i>
        </div>
        <h1>Account Temporarily Blocked</h1>
        <p>Your account has been blocked due to security</p>
    </div>

    <div class="auth-form blocked-section">
        <!-- Blocked Message -->
        <div class="alert alert-warning blocked-alert">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Too many failed login attempts</strong>
                <p>We've temporarily blocked your account to protect it from unauthorized access.</p>
            </div>
        </div>

        <!-- Unlock Timer -->
        <div class="unlock-timer">
            <div class="timer-box">
                <h3>Your account will be unlocked in:</h3>
                <div class="countdown" id="countdown">
                    <span class="time-value" id="minutes">15</span><span class="time-label">m</span>
                    <span class="time-value" id="seconds">00</span><span class="time-label">s</span>
                </div>
                <p class="timer-hint">Check back shortly to try again</p>
            </div>
        </div>

        <!-- Details -->
        <div class="blocked-details">
            <div class="detail-item">
                <span class="detail-label"><i class="fas fa-envelope"></i> Email:</span>
                <span class="detail-value" id="blockedEmail"><?= htmlspecialchars($email ?? '') ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label"><i class="fas fa-clock"></i> Unlock Time:</span>
                <span class="detail-value" id="unblockTime"><?= htmlspecialchars($blockedUntil ?? '') ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label"><i class="fas fa-shield-alt"></i> Reason:</span>
                <span class="detail-value">Too many failed login attempts</span>
            </div>
        </div>

        <!-- Info Section -->
        <div class="blocked-info">
            <h4><i class="fas fa-info-circle"></i> What happened?</h4>
            <ul>
                <li>We detected 5 or more failed login attempts from your account</li>
                <li>To protect your account, we've temporarily blocked further login attempts</li>
                <li>This is a security feature to prevent unauthorized access</li>
                <li>Your account will automatically unlock after <strong>15 minutes</strong></li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="blocked-actions">
            <a href="/login" class="btn btn-primary btn-block">
                <i class="fas fa-arrow-left"></i> Go Back to Login
            </a>
            <button type="button" class="btn btn-secondary btn-block" onclick="showSupportOptions()">
                <i class="fas fa-question-circle"></i> Need Help?
            </button>
        </div>

        <!-- Help Section (Hidden by default) -->
        <div id="supportOptions" class="support-options" style="display: none;">
            <div class="support-item">
                <h5><i class="fas fa-envelope"></i> Contact Support</h5>
                <p>If you believe this is a mistake or need immediate assistance:</p>
                <a href="mailto:support@jobconnect.com" class="btn btn-outline btn-small">
                    Email Support
                </a>
            </div>
            <div class="support-item">
                <h5><i class="fas fa-key"></i> Forgot Your Password?</h5>
                <p>If you forgot your password and want to reset it:</p>
                <a href="/forgot-password" class="btn btn-outline btn-small">
                    Reset Password
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.blocked-icon {
    font-size: 4rem;
    color: #ea580c;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.8; }
}

.blocked-alert {
    display: flex;
    gap: 1rem;
    padding: 1.2rem;
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.blocked-alert i {
    font-size: 1.5rem;
    color: #ff6b6b;
    min-width: 24px;
}

.blocked-alert strong {
    display: block;
    margin-bottom: 0.5rem;
    color: #333;
}

.blocked-alert p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.unlock-timer {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    color: white;
    margin-bottom: 2rem;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.unlock-timer h3 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    opacity: 0.9;
}

.countdown {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.time-value {
    font-size: 2.5rem;
    font-weight: 700;
    min-width: 80px;
    font-family: 'Courier New', monospace;
}

.time-label {
    font-size: 0.9rem;
    opacity: 0.8;
    margin-right: 0.5rem;
}

.timer-hint {
    margin: 0;
    font-size: 0.9rem;
    opacity: 0.85;
}

.blocked-details {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.8rem 0;
    border-bottom: 1px solid #e9ecef;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
    font-weight: 600;
    font-size: 0.95rem;
}

.detail-label i {
    color: #667eea;
}

.detail-value {
    color: #333;
    font-weight: 500;
}

.blocked-info {
    background: #e7f3ff;
    border-left: 4px solid #667eea;
    border-radius: 6px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.blocked-info h4 {
    margin: 0 0 1rem 0;
    color: #0c5aa0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.blocked-info i {
    color: #667eea;
}

.blocked-info ul {
    margin: 0;
    padding-left: 1.5rem;
    color: #333;
    font-size: 0.95rem;
    line-height: 1.6;
}

.blocked-info li {
    margin-bottom: 0.6rem;
}

.blocked-info strong {
    color: #0c5aa0;
}

.blocked-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.btn-block {
    width: 100%;
}

.support-options {
    border-top: 1px solid #e9ecef;
    padding-top: 1.5rem;
}

.support-item {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.support-item:last-child {
    margin-bottom: 0;
}

.support-item h5 {
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #333;
}

.support-item p {
    margin: 0 0 0.8rem 0;
    color: #666;
    font-size: 0.9rem;
}

.btn-small {
    display: inline-block;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

@media (max-width: 600px) {
    .blocked-actions {
        grid-template-columns: 1fr;
    }
    
    .blocked-icon {
        font-size: 3rem;
    }
    
    .time-value {
        font-size: 2rem;
        min-width: 60px;
    }
    
    .unlock-timer {
        padding: 1.5rem 1rem;
    }
    
    .countdown {
        margin-bottom: 0.8rem;
    }
}
</style>

<script>
    // Get blocked until time from data attribute or PHP variable
    const blockedUntil = new Date('<?= $blockedUntil ?? date('Y-m-d H:i:s', strtotime('+15 minutes')) ?>');
    
    function updateCountdown() {
        const now = new Date();
        const diff = blockedUntil - now;
        
        if (diff <= 0) {
            // Time's up - redirect or show message
            document.getElementById('countdown').innerHTML = '<p style="color: #28a745; font-size: 1.2rem;"><i class="fas fa-check-circle"></i> Account unlocked! Redirecting...</p>';
            setTimeout(() => {
                window.location.href = '/login';
            }, 2000);
            return;
        }
        
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
        document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
    }
    
    function showSupportOptions() {
        const supportDiv = document.getElementById('supportOptions');
        supportDiv.style.display = supportDiv.style.display === 'none' ? 'block' : 'none';
    }
    
    // Update countdown every second
    updateCountdown();
    setInterval(updateCountdown, 1000);
    
    // Attempt automatic redirect when time is up
    const blockedUntilMs = blockedUntil.getTime();
    const redirectDelay = Math.max(0, blockedUntilMs - new Date().getTime() + 1000);
    if (redirectDelay > 0 && redirectDelay < 20 * 60 * 1000) { // Only set timeout if less than 20 minutes
        setTimeout(() => {
            window.location.href = '/login';
        }, redirectDelay);
    }
</script>

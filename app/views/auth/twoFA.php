<!-- 2FA Page - HTML Structure & JavaScript -->
<div class="twofa-card">
  <div class="icon-header">
    <div class="shield-icon">
      <i class="fas fa-shield-halved"></i>
    </div>
  </div>
  
  <h2 class="text-center gradient-text" style="margin-bottom: 0.5rem;">Two-factor authentication</h2>
  <p class="text-center text-muted" style="max-width: 300px; margin: 0 auto;">Enter the 6-digit code from your authenticator app</p>

  <form id="twofaForm">
    <input type="hidden" name="otp" id="otpHidden">
    
    <div class="code-input-group">
      <input type="number" class="digit-box" id="digit1" min="0" max="9" inputmode="numeric" autofocus>
      <input type="number" class="digit-box" id="digit2" min="0" max="9" inputmode="numeric">
      <input type="number" class="digit-box" id="digit3" min="0" max="9" inputmode="numeric">
      <input type="number" class="digit-box" id="digit4" min="0" max="9" inputmode="numeric">
      <input type="number" class="digit-box" id="digit5" min="0" max="9" inputmode="numeric">
      <input type="number" class="digit-box" id="digit6" min="0" max="9" inputmode="numeric">
    </div>

    <div class="timer-row">
      <i class="far fa-clock" style="color: var(--primary-light);"></i>
      <span id="timerDisplay">02:00</span>
      <span class="recovery-link" id="showRecovery">• use recovery code</span>
    </div>

    <div class="trust-row">
      <label class="checkbox-label">
        <input type="checkbox" id="trustDevice" checked>
        <span>Trust this device for 30 days</span>
      </label>
    </div>

    <button type="submit" class="btn btn-primary btn-full" id="verifyBtn">
      <i class="fas fa-check-circle"></i> Verify & continue
    </button>

    <!-- Recovery field (hidden by default) -->
    <div class="form-group mt-4" id="recoveryField" style="display: none;">
      <label class="form-label"><i class="fas fa-key"></i> Recovery code</label>
      <div class="input-wrapper no-icon">
        <input type="text" class="form-control" id="recoveryCode" placeholder="XXXXX-XXXXX">
      </div>
      <button type="button" class="btn btn-secondary btn-sm mt-2" id="backToCodeBtn">← back to 2FA code</button>
    </div>
  </form>

  <!-- Only essential links: support and privacy -->
  <div class="footer-links">
    <a href="#" id="supportLink"><i class="fas fa-headset"></i> Support</a>
    <a href="#" id="privacyLink"><i class="fas fa-lock"></i> Privacy</a>
  </div>
</div>

<!-- Theme toggle button (minimal) -->
<button class="btn btn-sm btn-secondary" id="themeToggle" style="position: fixed; top: 20px; right: 20px; z-index: 100;">
  <i class="fas fa-moon"></i> <span id="themeText">Dark</span>
</button>

<!-- Toast container for notifications -->
<div class="toast-container" id="toastContainer"></div>

<script>
  (function() {
    // DOM elements
    const digits = [...document.querySelectorAll('.digit-box')];
    const otpHidden = document.getElementById('otpHidden');
    const form = document.getElementById('twofaForm');
    const timerSpan = document.getElementById('timerDisplay');
    const recoveryField = document.getElementById('recoveryField');
    const showRecovery = document.getElementById('showRecovery');
    const backToCodeBtn = document.getElementById('backToCodeBtn');
    const recoveryInput = document.getElementById('recoveryCode');
    const themeToggle = document.getElementById('themeToggle');
    const themeText = document.getElementById('themeText');
    const html = document.documentElement;
    
    // Support & Privacy links
    document.getElementById('supportLink').addEventListener('click', (e) => {
      e.preventDefault();
      showToast('Support contact: support@secureapp.com', 'info');
    });
    
    document.getElementById('privacyLink').addEventListener('click', (e) => {
      e.preventDefault();
      showToast('Privacy policy · Your data is encrypted', 'info');
    });

    // Digit input handling
    digits.forEach((input, idx) => {
      input.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 1);
        if (this.value.length === 1 && idx < digits.length - 1) {
          digits[idx + 1].focus();
        }
        updateHiddenOtp();
      });

      input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value && idx > 0) {
          digits[idx - 1].focus();
          digits[idx - 1].select();
        }
        if (e.key === 'ArrowLeft' && idx > 0) {
          e.preventDefault();
          digits[idx - 1].focus();
        }
        if (e.key === 'ArrowRight' && idx < digits.length - 1) {
          e.preventDefault();
          digits[idx + 1].focus();
        }
      });

      input.addEventListener('paste', function(e) {
        e.preventDefault();
        const pasteData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
        if (pasteData.length === 6) {
          for (let i = 0; i < 6; i++) {
            digits[i].value = pasteData[i];
          }
          digits[5].focus();
          updateHiddenOtp();
        }
      });
    });

    function updateHiddenOtp() {
      otpHidden.value = digits.map(d => d.value || '').join('');
    }

    // Timer countdown
    let timeLeft = 120;
    let timerInterval;
    
    function updateTimerDisplay() {
      const mins = Math.floor(timeLeft / 60);
      const secs = timeLeft % 60;
      timerSpan.textContent = `${mins.toString().padStart(2,'0')}:${secs.toString().padStart(2,'0')}`;
      if (timeLeft <= 0) {
        clearInterval(timerInterval);
        timerSpan.innerHTML = `expired <i class="fas fa-rotate-right"></i>`;
        timerSpan.style.color = 'var(--danger-color)';
      }
    }
    
    timerInterval = setInterval(() => {
      if (timeLeft > 0) {
        timeLeft--;
        updateTimerDisplay();
      } else {
        clearInterval(timerInterval);
      }
    }, 1000);
    updateTimerDisplay();

    // Restart timer on click
    timerSpan.addEventListener('click', function() {
      if (timeLeft <= 0) {
        timeLeft = 120;
        timerSpan.style.color = '';
        clearInterval(timerInterval);
        timerInterval = setInterval(() => {
          if (timeLeft > 0) {
            timeLeft--;
            updateTimerDisplay();
          } else {
            clearInterval(timerInterval);
          }
        }, 1000);
        updateTimerDisplay();
      }
    });

    // Toggle recovery field
    showRecovery.addEventListener('click', (e) => {
      e.preventDefault();
      recoveryField.style.display = 'block';
      recoveryInput.focus();
    });
    
    backToCodeBtn.addEventListener('click', () => {
      recoveryField.style.display = 'none';
    });

    // Toast function
    function showToast(message, type = 'info') {
      const container = document.getElementById('toastContainer');
      const toast = document.createElement('div');
      toast.className = `toast toast-${type}`;
      toast.innerHTML = `
        <div class="toast-content">
          <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
          <span class="toast-message">${message}</span>
        </div>
        <button class="toast-close">&times;</button>
      `;
      container.appendChild(toast);
      setTimeout(() => { if (toast.parentNode) toast.remove(); }, 4000);
      toast.querySelector('.toast-close').addEventListener('click', () => toast.remove());
    }

    // Form submit
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const hiddenOtp = otpHidden.value;
      const isRecoveryVisible = recoveryField.style.display === 'block';
      let codeValid = false;

      if (isRecoveryVisible) {
        const recovery = recoveryInput.value.trim();
        if (recovery.match(/^[A-Z0-9]{5}-[A-Z0-9]{5}$/i)) {
          codeValid = true;
          showToast('Recovery code accepted', 'success');
        } else {
          showToast('Invalid format (XXXXX-XXXXX)', 'error');
          return;
        }
      } else {
        if (hiddenOtp.length === 6 && /^\d+$/.test(hiddenOtp)) {
          showToast('✓ Verification successful', 'success');
          codeValid = true;
        } else {
          showToast('Please enter all 6 digits', 'error');
          return;
        }
      }

      if (codeValid) {
        const overlay = document.createElement('div');
        overlay.className = 'spinner-overlay';
        overlay.innerHTML = `
          <div class="spinner-wrapper">
            <div class="spinner"></div>
            <div class="spinner-message">Verifying...</div>
          </div>
        `;
        document.body.appendChild(overlay);
        setTimeout(() => overlay.remove(), 1500);
      }
    });

    // Theme toggle
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    updateThemeButton(savedTheme);

    themeToggle.addEventListener('click', () => {
      const current = html.getAttribute('data-theme');
      const newTheme = current === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', newTheme);
      localStorage.setItem('theme', newTheme);
      updateThemeButton(newTheme);
    });

    function updateThemeButton(theme) {
      const icon = themeToggle.querySelector('i');
      if (theme === 'dark') {
        icon.className = 'fas fa-sun';
        themeText.textContent = 'Light';
      } else {
        icon.className = 'fas fa-moon';
        themeText.textContent = 'Dark';
      }
    }
  })();
</script>
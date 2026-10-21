<!-- File: views/jobseeker/interviews.php -->
<div class="interviews-dashboard">
    <!-- Welcome Section -->
    <div class="welcome-card">
        <div class="welcome-content">
            <h1><?= htmlspecialchars($prof_title ?? 'Hello') . ' ' . htmlspecialchars($name ?? 'there') ?>!</h1>
            <p>Your interview journey starts here. Stay prepared and confident.</p>
        </div>
        <button class="refresh-btn" id="refreshBtn">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card scheduled">
            <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
            <div class="stat-info">
                <h3 id="scheduledCount">0</h3>
                <p>Upcoming</p>
            </div>
        </div>
        <div class="stat-card completed">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h3 id="completedCount">0</h3>
                <p>Completed</p>
            </div>
        </div>
        <div class="stat-card cancelled">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-info">
                <h3 id="cancelledCount">0</h3>
                <p>Cancelled</p>
            </div>
        </div>
    </div>

    <!-- Interview Tips -->
    <div class="tips-widget">
        <div class="tips-header">
            <i class="fas fa-lightbulb"></i>
            <span>Interview Wisdom</span>
            <button id="nextTipBtn"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="tips-content">
            <p id="tipText">Loading tips...</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="custom-tabs">
        <button class="tab-btn active" data-tab="upcoming">
            <i class="fas fa-calendar-alt"></i> Upcoming
        </button>
        <button class="tab-btn" data-tab="past">
            <i class="fas fa-history"></i> Past
        </button>
    </div>

    <!-- Tab Panels -->
    <div class="tab-panels">
        <div class="tab-panel active" id="upcomingPanel">
            <div id="upcomingList" class="interviews-list"></div>
        </div>
        <div class="tab-panel" id="pastPanel">
            <div id="pastList" class="interviews-list"></div>
        </div>
    </div>
</div>

<!-- Custom Modal (No Bootstrap) -->
<div id="contactModal" class="custom-modal">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h3><i class="fas fa-envelope"></i> Contact Employer</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="contactInfo" class="contact-info"></div>
            <div class="form-group">
                <label>Your Message</label>
                <textarea id="contactMessage" rows="4" class="form-control"></textarea>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Introduce yourself and mention the job position.
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-close-btn">Cancel</button>
            <button class="btn btn-primary" id="sendEmailBtn">Send Email</button>
            <a href="#" id="callPhoneBtn" class="btn btn-success d-none">Call Now</a>
        </div>
    </div>
</div>

<div id="calendarModal" class="custom-modal">
    <div class="modal-overlay"></div>
    <div class="modal-container modal-sm">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-plus"></i> Add to Calendar</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="calendar-options">
                <a href="#" id="googleCalendarLink" target="_blank" class="calendar-option">
                    <i class="fab fa-google"></i> Google Calendar
                </a>
                <a href="#" id="appleCalendarLink" target="_blank" class="calendar-option">
                    <i class="fab fa-apple"></i> Apple Calendar
                </a>
                <button id="copyIcsBtn" class="calendar-option">
                    <i class="fas fa-copy"></i> Copy ICS Link
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Dashboard Container */
    .interviews-dashboard {
        max-width: 1200px;
        width: 100%;
        margin: 0 auto;
        padding: 1.5rem;
    }

    /* Welcome Card */
    .welcome-card {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 1.5rem;
        padding: 1.5rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    [data-theme="dark"] .welcome-card {
        background: linear-gradient(135deg, #1e293b, #0f172a);
    }
    .welcome-content h1 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 600;
    }
    .welcome-content p {
        margin: 0.25rem 0 0;
        color: var(--text-muted);
    }
    .refresh-btn {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 2rem;
        padding: 0.5rem 1rem;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.875rem;
        color: var(--text-primary);
    }
    .refresh-btn:hover {
        background: var(--bg-tertiary);
        transform: translateY(-2px);
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background: var(--bg-secondary);
        border-radius: 1rem;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .stat-card.scheduled .stat-icon { background: rgba(59,130,246,0.1); color: #3b82f6; }
    .stat-card.completed .stat-icon { background: rgba(16,185,129,0.1); color: #10b981; }
    .stat-card.cancelled .stat-icon { background: rgba(239,68,68,0.1); color: #ef4444; }
    .stat-info h3 {
        margin: 0;
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
    }
    .stat-info p {
        margin: 0.25rem 0 0;
        color: var(--text-muted);
        font-size: 0.875rem;
    }

    /* Tips Widget */
    .tips-widget {
        background: var(--bg-secondary);
        border-radius: 1rem;
        overflow: hidden;
        margin-bottom: 2rem;
        border-left: 4px solid #f59e0b;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .tips-header {
        background: rgba(245,158,11,0.1);
        padding: 0.75rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-bottom: 1px solid var(--border-color);
    }
    .tips-header i { color: #f59e0b; }
    .tips-header span { font-weight: 500; flex: 1; }
    .tips-header button {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--text-muted);
        transition: color 0.2s;
    }
    .tips-header button:hover { color: #f59e0b; }
    .tips-content {
        padding: 1.25rem;
    }
    #tipText {
        margin: 0;
        line-height: 1.5;
        color: var(--text-secondary);
    }

    /* Custom Tabs */
    .custom-tabs {
        display: flex;
        gap: 0.5rem;
        border-bottom: 2px solid var(--border-color);
        margin-bottom: 1.5rem;
    }
    .tab-btn {
        background: none;
        border: none;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
        font-size: 1rem;
    }
    .tab-btn i { margin-right: 0.5rem; }
    .tab-btn.active {
        color: var(--primary-color);
    }
    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--primary-color);
    }
    .tab-panels {
        min-height: 300px;
    }
    .tab-panel {
        display: none;
    }
    .tab-panel.active {
        display: block;
    }

    /* Interview Cards */
    .interviews-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .interview-item {
        background: var(--bg-secondary);
        border-radius: 1rem;
        padding: 1.25rem;
        transition: all 0.2s;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .interview-item:hover {
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .interview-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    .interview-title {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 600;
    }
    .interview-company {
        margin: 0.25rem 0 0;
        color: var(--text-muted);
        font-size: 0.875rem;
    }
    .interview-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 500;
    }
    .badge-scheduled { background: #e3f2fd; color: #1976d2; }
    .badge-completed { background: #e8f5e9; color: #2e7d32; }
    .badge-cancelled { background: #ffebee; color: #c62828; }
    [data-theme="dark"] .badge-scheduled { background: #1e3a5f; color: #90caf9; }
    [data-theme="dark"] .badge-completed { background: #1b5e20; color: #a5d6a7; }
    [data-theme="dark"] .badge-cancelled { background: #b71c1c; color: #ffcdd2; }
    .interview-details {
        margin-bottom: 1rem;
    }
    .detail-row {
        display: flex;
        align-items: baseline;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        flex-wrap: wrap;
    }
    .detail-row i {
        width: 1.5rem;
        color: var(--primary-color);
    }
    .relative-time {
        font-weight: 600;
        color: var(--primary-color);
    }
    .exact-time {
        font-size: 0.85rem;
        color: var(--text-muted);
    }
    .notes {
        background: var(--bg-tertiary);
        padding: 0.5rem;
        border-radius: 0.5rem;
        margin-top: 0.5rem;
    }
    .interview-actions {
        margin-top: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .interview-actions .btn {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        text-align: center;
        text-decoration: none;
        font-size: 0.875rem;
        transition: all 0.2s;
        cursor: pointer;
        border: none;
        font-weight: 500;
    }
    .btn-primary {
        background: var(--primary-color);
        color: white;
    }
    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
    }
    .btn-outline-secondary {
        background: transparent;
        border: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    .btn-outline-secondary:hover {
        background: var(--bg-tertiary);
    }

    /* Custom Modal */
    .custom-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .custom-modal.active {
        display: flex;
    }
    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(3px);
    }
    .modal-container {
        position: relative;
        background: var(--bg-primary);
        border-radius: 1rem;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 30px rgba(0,0,0,0.2);
    }
    .modal-container.modal-sm {
        max-width: 400px;
    }
    .modal-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-header h3 {
        margin: 0;
        font-size: 1.2rem;
    }
    .modal-header h3 i {
        margin-right: 0.5rem;
    }
    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--text-muted);
    }
    .modal-body {
        padding: 1.5rem;
    }
    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }
    .contact-info {
        background: var(--bg-tertiary);
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border-color);
        border-radius: 0.5rem;
        background: var(--bg-primary);
        color: var(--text-primary);
        font-family: inherit;
    }
    .alert {
        padding: 0.75rem;
        border-radius: 0.5rem;
        background: rgba(59,130,246,0.1);
        color: #3b82f6;
        font-size: 0.875rem;
    }
    .calendar-options {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .calendar-option {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem;
        background: var(--bg-tertiary);
        border-radius: 0.5rem;
        text-decoration: none;
        color: var(--text-primary);
        transition: background 0.2s;
    }
    .calendar-option:hover {
        background: var(--border-color);
    }
    .btn-secondary {
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    .btn-success {
        background: #10b981;
        color: white;
    }
    .d-none {
        display: none;
    }
    @media (max-width: 768px) {
        .interviews-dashboard {
            padding: 0.75rem;
        }
        .welcome-card {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem 1.1rem;
            margin-bottom: 1rem;
            border-radius: 1rem;
        }
        .welcome-content h1 {
            font-size: 1.35rem;
            line-height: 1.3;
        }
        .welcome-content p {
            font-size: 0.85rem;
        }
        .stats-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .stat-card {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.7rem;
            border-radius: 0.8rem;
        }
        .stat-icon {
            width: 34px;
            height: 34px;
            font-size: 1rem;
        }
        .stat-info h3 {
            font-size: 1.35rem;
        }
        .stat-info p {
            font-size: 0.7rem;
        }
        .tips-widget {
            margin-bottom: 1rem;
        }
        .tips-content {
            padding: 0.85rem;
            font-size: 0.85rem;
        }
        .custom-tabs {
            margin-bottom: 1rem;
        }
        .tab-btn {
            padding: 0.65rem 0.9rem;
            font-size: 0.875rem;
        }
        .interview-item {
            padding: 0.9rem;
        }
        .interview-header {
            flex-direction: column;
            gap: 0.5rem;
        }
        .interview-badge {
            align-self: flex-start;
        }
        .modal-container {
            width: 95%;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
      localStorage.setItem('user_name', <?php echo json_encode($name ?? 'there'); ?>);
        // ---------- TIPS MANAGEMENT ----------
        let tips = [
            "Research the company's mission, values, and recent news before the interview.",
            "Prepare 3-5 thoughtful questions to ask the interviewer – it shows genuine interest.",
            "Dress one level above the company's dress code – it's better to be overdressed than underdressed.",
            "Test your internet connection, camera, and microphone 15 minutes before a video call.",
            "Bring 2-3 printed copies of your resume to in-person interviews (just in case).",
            "Practice the STAR method (Situation, Task, Action, Result) for behavioral questions.",
            "Arrive 10-15 minutes early – punctuality is a sign of respect.",
            "Silence your phone and place it out of sight during the interview.",
            "Use the interviewer's name a couple of times to build rapport (but don't overdo it).",
            "Send a personalized thank-you email within 24 hours after the interview.",
            "Make eye contact and smile – it conveys confidence and warmth.",
            "Be honest about your skills; authenticity builds trust.",
            "Prepare a 30-second elevator pitch summarizing who you are and what you bring.",
            "Take a notebook and pen to jot down key points – it shows you're engaged.",
            "Listen actively and ask follow-up questions based on what the interviewer says.",
            "If you don't know an answer, it's okay to say 'I don't know, but I'd be happy to learn.'",
            "Showcase your achievements with specific numbers and results.",
            "Research the interviewer on LinkedIn to understand their role and background.",
            "Bring a portfolio of your work if applicable (digital or physical).",
            "Get a good night's sleep and eat a light meal before the interview."
        ];
        let currentTipIndex = localStorage.getItem('interviewTipIndex') ? parseInt(localStorage.getItem('interviewTipIndex')) : 0;
        const tipTextEl = document.getElementById('tipText');
        function displayTip(index) {
            if (tipTextEl && tips[index]) {
                tipTextEl.textContent = tips[index];
                localStorage.setItem('interviewTipIndex', index);
            }
        }
        displayTip(currentTipIndex);
        document.getElementById('nextTipBtn')?.addEventListener('click', () => {
            currentTipIndex = (currentTipIndex + 1) % tips.length;
            displayTip(currentTipIndex);
        });

        // ---------- GLOBAL VARIABLES ----------
        let allInterviews = [];
        let currentContactInterview = null;

        // ---------- MODAL HANDLERS (no Bootstrap) ----------
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('active');
        }
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.remove('active');
        }
        // Close modals on overlay click or close button
        document.querySelectorAll('.custom-modal .modal-overlay, .custom-modal .modal-close, .custom-modal .modal-close-btn').forEach(el => {
            el.addEventListener('click', function(e) {
                const modal = this.closest('.custom-modal');
                if (modal) modal.classList.remove('active');
            });
        });

        // ---------- LOAD INTERVIEWS ----------
        async function loadInterviews(showToast = false) {
            document.getElementById('upcomingList').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p>Loading interviews...</p></div>';
            document.getElementById('pastList').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p>Loading interviews...</p></div>';
            try {
                const res = await fetch('/api/jobseeker/interviews', { credentials: 'include', headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('Failed to fetch interviews');
                const result = await res.json();
                const data = result.data || {};
                allInterviews = data.interviews || [];
                const stats = data.stats || { scheduled: 0, completed: 0, cancelled: 0 };
                animateNumber('scheduledCount', stats.scheduled);
                animateNumber('completedCount', stats.completed);
                animateNumber('cancelledCount', stats.cancelled);
                const now = new Date();
                const upcoming = allInterviews.filter(i => i.status === 'scheduled' && new Date(i.scheduled_at) >= now);
                const past = allInterviews.filter(i => i.status !== 'scheduled' || new Date(i.scheduled_at) < now);
                renderInterviews(upcoming, 'upcomingList', true);
                renderInterviews(past, 'pastList', false);
                if (showToast) showToastMessage('Interviews refreshed', 'success');
            } catch (err) {
                console.error(err);
                document.getElementById('upcomingList').innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
                document.getElementById('pastList').innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
            }
        }

        function animateNumber(elementId, target) {
            const el = document.getElementById(elementId);
            if (!el) return;
            const start = parseInt(el.textContent) || 0;
            if (start === target) return;
            const duration = 500;
            const step = (target - start) / (duration / 16);
            let current = start;
            const timer = setInterval(() => {
                current += step;
                if ((step > 0 && current >= target) || (step < 0 && current <= target)) {
                    el.textContent = target;
                    clearInterval(timer);
                } else {
                    el.textContent = Math.round(current);
                }
            }, 16);
        }

        function renderInterviews(interviews, containerId, isUpcoming) {
            const container = document.getElementById(containerId);
            if (!container) return;
            if (interviews.length === 0) {
                container.innerHTML = `<div class="empty-state text-center py-5"><i class="fas fa-calendar-times fa-3x text-muted"></i><p class="mt-2">${isUpcoming ? 'No upcoming interviews.' : 'No past interviews yet.'}</p></div>`;
                return;
            }
            const sorted = [...interviews].sort((a,b) => {
                const da = new Date(a.scheduled_at), db = new Date(b.scheduled_at);
                return isUpcoming ? da - db : db - da;
            });
            container.innerHTML = sorted.map(interview => {
                const interviewDate = new Date(interview.scheduled_at);
                const now = new Date();
                const diffMs = interviewDate - now;
                const diffDays = Math.ceil(diffMs / (1000*60*60*24));
                let relativeTime = '';
                if (isUpcoming) {
                    if (diffDays < 0) relativeTime = 'Overdue';
                    else if (diffDays === 0) relativeTime = 'Today';
                    else if (diffDays === 1) relativeTime = 'Tomorrow';
                    else if (diffDays <= 7) relativeTime = `In ${diffDays} days`;
                    else relativeTime = `In ${Math.ceil(diffDays/7)} week${Math.ceil(diffDays/7) > 1 ? 's' : ''}`;
                } else {
                    const daysAgo = Math.floor((now - interviewDate) / (1000*60*60*24));
                    if (daysAgo === 0) relativeTime = 'Earlier today';
                    else if (daysAgo === 1) relativeTime = 'Yesterday';
                    else relativeTime = `${daysAgo} days ago`;
                }
                const exactTime = interviewDate.toLocaleString(undefined, {
                    hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'short', year: 'numeric'
                });
                const type = interview.interview_type;
                const meetingLink = interview.meeting_link;
                const location = interview.location;
                const contactEmail = interview.employer_contact_email;
                const contactPhone = interview.employer_contact_phone;
                const notes = interview.notes;
                const company = interview.employer_company_name;
                const jobTitle = interview.job_title;
                let actionButtons = '';
                if (isUpcoming && interview.status === 'scheduled') {
                    if (type === 'video' && meetingLink) {
                        actionButtons = `<a href="${meetingLink}" target="_blank" class="btn btn-primary"><i class="fas fa-video"></i> Join Meeting</a>`;
                    } else if (type === 'phone' && contactPhone) {
                        actionButtons = `<a href="tel:${contactPhone}" class="btn btn-primary"><i class="fas fa-phone-alt"></i> Call Now</a>`;
                    } else if (type === 'in_person' && location) {
                        actionButtons = `<a href="https://maps.google.com/?q=${encodeURIComponent(location)}" target="_blank" class="btn btn-primary"><i class="fas fa-directions"></i> Directions</a>`;
                    }
                    if (contactEmail) {
                        actionButtons += `<button class="btn btn-outline-secondary contact-employer" data-interview='${JSON.stringify(interview)}'><i class="fas fa-envelope"></i> Contact Employer</button>`;
                    }
                    actionButtons += `<button class="btn btn-outline-secondary add-calendar" data-interview='${JSON.stringify(interview)}'><i class="fas fa-calendar-plus"></i> Add to Calendar</button>`;
                }
                let badgeClass = '';
                if (interview.status === 'scheduled') badgeClass = 'badge-scheduled';
                else if (interview.status === 'completed') badgeClass = 'badge-completed';
                else badgeClass = 'badge-cancelled';
                return `
                    <div class="interview-item" data-id="${interview.id}">
                        <div class="interview-header">
                            <div>
                                <h4 class="interview-title">${escapeHtml(jobTitle)}</h4>
                                <p class="interview-company"><i class="fas fa-building"></i> ${escapeHtml(company)}</p>
                            </div>
                            <span class="interview-badge ${badgeClass}">${capitalize(interview.status)}</span>
                        </div>
                        <div class="interview-details">
                            <div class="detail-row">
                                <i class="fas fa-clock"></i>
                                <span class="relative-time">${relativeTime}</span>
                                <span class="exact-time">• ${exactTime}</span>
                            </div>
                            <div class="detail-row">
                                <i class="fas fa-hourglass-half"></i>
                                <span>${interview.duration_minutes || 45} minutes</span>
                            </div>
                            <div class="detail-row">
                                <i class="fas ${typeIcon(type)}"></i>
                                <span>${capitalize(type)} interview</span>
                            </div>
                            ${location ? `<div class="detail-row"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(location)}</div>` : ''}
                            ${contactEmail ? `<div class="detail-row"><i class="fas fa-envelope"></i> ${escapeHtml(contactEmail)}</div>` : ''}
                            ${contactPhone ? `<div class="detail-row"><i class="fas fa-phone"></i> ${escapeHtml(contactPhone)}</div>` : ''}
                            ${notes ? `<div class="detail-row notes"><i class="fas fa-sticky-note"></i> ${escapeHtml(notes)}</div>` : ''}
                        </div>
                        ${actionButtons ? `<div class="interview-actions">${actionButtons}</div>` : ''}
                    </div>
                `;
            }).join('');
            // Attach event listeners
            document.querySelectorAll('.contact-employer').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const interview = JSON.parse(btn.getAttribute('data-interview'));
                    openContactModal(interview);
                });
            });
            document.querySelectorAll('.add-calendar').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const interview = JSON.parse(btn.getAttribute('data-interview'));
                    openCalendarModal(interview);
                });
            });
        }

        function openContactModal(interview) {
            currentContactInterview = interview;
            const infoDiv = document.getElementById('contactInfo');
            infoDiv.innerHTML = `
                <strong>${escapeHtml(interview.employer_company_name)}</strong><br>
                ${escapeHtml(interview.job_title)}<br>
                <i class="fas fa-envelope"></i> ${escapeHtml(interview.employer_contact_email)}<br>
                ${interview.employer_contact_phone ? `<i class="fas fa-phone"></i> ${escapeHtml(interview.employer_contact_phone)}` : ''}
            `;
            const msgArea = document.getElementById('contactMessage');
            msgArea.value = `Hello,\n\nMy name is ${localStorage.getItem('user_name') || 'a candidate'}. I have an interview scheduled for the ${interview.job_title} position. I just wanted to touch base regarding the details.\n\nThank you,\n${localStorage.getItem('user_name') || 'Candidate'}`;
            const callBtn = document.getElementById('callPhoneBtn');
            if (interview.employer_contact_phone) {
                callBtn.href = `tel:${interview.employer_contact_phone}`;
                callBtn.classList.remove('d-none');
            } else {
                callBtn.classList.add('d-none');
            }
            openModal('contactModal');
        }

        function openCalendarModal(interview) {
            const start = new Date(interview.scheduled_at);
            const end = new Date(start.getTime() + (interview.duration_minutes || 45) * 60000);
            const formatDate = (date) => date.toISOString().replace(/-|:|\.\d+/g, '');
            const googleUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(interview.job_title)}&dates=${formatDate(start)}/${formatDate(end)}&details=${encodeURIComponent(interview.notes || '')}&location=${encodeURIComponent(interview.location || interview.meeting_link || '')}`;
            const appleUrl = `data:text/calendar;charset=utf8,BEGIN:VCALENDAR%0AVERSION:2.0%0APRODID:-//JobConnect//EN%0ABEGIN:VEVENT%0ASUMMARY:${encodeURIComponent(interview.job_title)}%0ADTSTART:${formatDate(start)}%0ADTEND:${formatDate(end)}%0ADESCRIPTION:${encodeURIComponent(interview.notes || '')}%0ALOCATION:${encodeURIComponent(interview.location || interview.meeting_link || '')}%0AEND:VEVENT%0AEND:VCALENDAR`;
            document.getElementById('googleCalendarLink').href = googleUrl;
            document.getElementById('appleCalendarLink').href = appleUrl;
            openModal('calendarModal');
        }

        document.getElementById('sendEmailBtn')?.addEventListener('click', () => {
            if (currentContactInterview) {
                const subject = encodeURIComponent(`Regarding ${currentContactInterview.job_title} interview`);
                const body = encodeURIComponent(document.getElementById('contactMessage').value);
                window.location.href = `mailto:${currentContactInterview.employer_contact_email}?subject=${subject}&body=${body}`;
            }
            closeModal('contactModal');
        });

        // ---------- TABS ----------
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.getAttribute('data-tab');
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
                document.getElementById(tab + 'Panel').classList.add('active');
            });
        });

        // ---------- REFRESH ----------
        document.getElementById('refreshBtn')?.addEventListener('click', () => loadInterviews(true));

        // ---------- UTILITIES ----------
        function typeIcon(type) {
            switch(type) {
                case 'video': return 'fa-video';
                case 'phone': return 'fa-phone-alt';
                default: return 'fa-building';
            }
        }
        function capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }
        function showToastMessage(msg, type) {
            // Simple fallback if Toast utility not available
            if (typeof Toast !== 'undefined') {
                if (type === 'success') Toast.success(msg);
                else if (type === 'error') Toast.error(msg);
                else Toast.info(msg);
            } else {
                alert(msg);
            }
        }

        // Load initial data
        loadInterviews();
    });
</script>
<aside class="employer-sidebar animate__animated animate__slideInLeft">
    <div class="sidebar-header">
        <div class="company-brand">
            <div class="logo">J</div>
            <div class="brand-text">
                <h2>JobConnect</h2>
                <p>Employer portal</p>
            </div>
        </div>
    </div>
    <div class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <div class="nav-items">
                <a href="/employer/dashboard" class="nav-link active"><i
                        class="fas fa-home"></i><span>Dashboard</span></a>
                <a href="/employer/jobs" class="nav-link"><i class="fas fa-briefcase"></i><span>Job Postings</span></a>
                <a href="/employer/applications" class="nav-link"><i
                        class="fas fa-file-lines"></i><span>Applications</span></a>
            </div>
        </div>
        <div class="nav-section">
            <div class="nav-section-title">Management</div>
            <div class="nav-items">
                <a href="/employer/interviews" class="nav-link"><i class="fas fa-calendar-check"></i><span>Interviews</span><span
                        class=""></span></a>
                <a href="/employer/notifications" class="nav-link"><i
                        class="fas fa-bell"></i><span>Notifications</span><span class="badge" id="notifications-badge"></span></a>
                <a href="/employer/settings" class="nav-link"><i class="fas fa-cog"></i><span>Settings</span></a>
            </div>
        </div>
    </div>
    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar">NC</div>
            <div class="user-details">
                <h4>Nexum Corp</h4>
                <p>hr@nexum.io</p>
                <span class="status-badge"><i class="fas fa-crown"></i> Premium</span>
            </div>
        </div>
        <button class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></button>
    </div>
</aside>

<nav class="mobile-bottom-nav" id="mobileBottomNav">
    <a href="/employer/dashboard" class="mobile-nav-item"><i class="fas fa-home"></i><span>Home</span></a>
    <a href="/employer/jobs" class="mobile-nav-item"><i class="fas fa-briefcase"></i><span>Jobs</span></a>
    <a href="/employer/applications" class="mobile-nav-item"><i class="fas fa-file-lines"></i><span>Apps</span></a>
    <a href="/employer/interviews" class="mobile-nav-item"><i class="fas fa-calendar-check"></i><span>Interview</span></a>
    <a href="/employer/notifications" class="mobile-nav-item"><i class="fas fa-bell"></i><span>Alerts</span><span class="badge" id="mobile-notifications-badge">4</span></a>
    <a href="/employer/settings" class="mobile-nav-item"><i class="fas fa-cog"></i><span>Settings</span></a>
</nav>

<!-- MOBILE OVERLAY MENU (ALL sidebar links) -->
<div class="mobile-overlay" id="mobileOverlay">
    <div class="overlay-drawer">
        <div class="drawer-header">
            <span style="font-weight:600; font-size:1.2rem;">Menu</span>
            <button class="close-drawer" id="closeDrawerBtn"><i class="fas fa-times"></i></button>
        </div>
        <div class="drawer-section">
            <div class="nav-section-title" style="padding-left:24px;">Main</div>
            <a href="/employer/dashboard" class="drawer-link active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="/employer/jobs" class="drawer-link"><i class="fas fa-briefcase"></i> Job Postings</a>
            <a href="/employer/applications" class="drawer-link"><i class="fas fa-file-lines"></i> Applications</a>
        </div>
        <div class="drawer-section">
            <div class="nav-section-title" style="padding-left:24px;">Management</div>
            <a href="/employer/interviews" class="drawer-link"><i class="fas fa-calendar-check"></i> Interviews <span class="badge" style="margin-left:auto;">3</span></a>
            <a href="/employer/notifications" class="drawer-link"><i class="fas fa-bell"></i> Notifications <span class="badge" id="drawer-notifications-badge">4</span></a>
            <a href="/employer/settings" class="drawer-link"><i class="fas fa-cog"></i> Settings</a>
        </div>
    </div>
</div>

<script>
    async function updateNotificationCount() {
        try {
            const response = await fetch('/api/notifications/unread-count');
            if (!response.ok) {
                throw new Error('Failed to fetch notification count');
            }
            const result = await response.json();
            const unreadCount = result.data.unread_count || 0;

            // Update sidebar badge
            const sidebarBadge = document.getElementById('notifications-badge');
            if (sidebarBadge) {
                sidebarBadge.textContent = unreadCount;
                sidebarBadge.style.display = unreadCount > 0 ? 'inline-flex' : 'none';
            }

            // Update mobile badge
            const mobileBadge = document.getElementById('mobile-notifications-badge');
            if (mobileBadge) {
                mobileBadge.textContent = unreadCount;
                mobileBadge.style.display = unreadCount > 0 ? 'inline-flex' : 'none';
            }

            // Update drawer badge
            const drawerBadge = document.getElementById('drawer-notifications-badge');
            if (drawerBadge) {
                drawerBadge.textContent = unreadCount;
                drawerBadge.style.display = unreadCount > 0 ? 'inline-flex' : 'none';
            }

        } catch (error) {
            console.error('Error fetching notification count:', error);
        }
    }

    async function getEmployerInfo() {
        try {
            const response = await fetch('/api/employer/info');
            if (!response.ok) {
                throw new Error('Failed to fetch employer info');
            }
            const result = await response.json();
            const data = result.data;
            console.log('Employer Info:', data);

            // Update user card
            const profile = data.profile || {};
            const user = data.user || {};

            // Update company name
            const companyName = profile.name || profile.contact_name || 'Company Name';
            document.querySelector('.user-details h4').textContent = companyName;

            // Update email
            const email = profile.contact_email || user.email || 'email@example.com';
            document.querySelector('.user-details p').textContent = email;

            // Update status badge
            const statusElement = document.querySelector('.status-badge');
            const status = profile.premium ? 'Premium' : 'Free';
            statusElement.innerHTML = `<i class="fas fa-crown"></i> ${status}`;

            // Update avatar initials (first letter of company name)
            const initials = companyName.charAt(0).toUpperCase();
            document.querySelector('.user-avatar').textContent = initials;

        } catch (error) {
            console.error('Error fetching employer info:', error);
        }
    }

    function setupLogout() {
        const logoutBtn = document.querySelector('.logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async function(e) {
                e.preventDefault();

                try {
                    const response = await fetch('/api/logout', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json'
                        }
                    });

                    // Redirect to login regardless of response
                    window.location.href = '/login';

                } catch (error) {
                    console.error('Logout error:', error);
                    // Still redirect to login
                    window.location.href = '/login';
                }
            });
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateNotificationCount();
        getEmployerInfo();
        setupLogout();
        setActiveMobileNav();

        // Update notification count every 30 seconds
        setInterval(updateNotificationCount, 30000);
    });

    function setActiveMobileNav() {
        const currentPath = window.location.pathname;
        const navItems = document.querySelectorAll('.mobile-nav-item');

        // Remove active class from all items
        navItems.forEach(item => item.classList.remove('active'));

        // Set active based on current path
        if (currentPath === '/employer/dashboard') {
            document.querySelector('.mobile-nav-item[href="/employer/dashboard"]').classList.add('active');
        } else if (currentPath.startsWith('/employer/jobs')) {
            document.querySelector('.mobile-nav-item[href="/employer/jobs"]').classList.add('active');
        } else if (currentPath.startsWith('/employer/applications')) {
            document.querySelector('.mobile-nav-item[href="/employer/applications"]').classList.add('active');
        } else if (currentPath.startsWith('/employer/interviews')) {
            document.querySelector('.mobile-nav-item[href="/employer/interviews"]').classList.add('active');
        } else if (currentPath.startsWith('/employer/notifications')) {
            document.querySelector('.mobile-nav-item[href="/employer/notifications"]').classList.add('active');
        } else if (currentPath.startsWith('/employer/settings')) {
            document.querySelector('.mobile-nav-item[href="/employer/settings"]').classList.add('active');
        }
    }
</script>
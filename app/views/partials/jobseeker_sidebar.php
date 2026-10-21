<aside class="employer-sidebar animate__animated animate__slideInLeft">
    <div class="sidebar-header">
        <div class="company-brand">
            <div class="logo" id="sidebar-profile-logo">J</div>
            <div class="brand-text">
                <h2>JobConnect</h2>
                <p>Job Seeker Portal</p>
            </div>
        </div>
    </div>
    <div class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <div class="nav-items">
                <a href="/jobseeker/dashboard" class="nav-link active"><i
                        class="fas fa-home"></i><span>Dashboard</span></a>
                <a href="/jobseeker/jobs" class="nav-link"><i class="fas fa-briefcase"></i><span>Find Jobs</span></a>
                <a href="/jobseeker/applications" class="nav-link"><i
                        class="fas fa-file-lines"></i><span>My Applications</span></a>
            </div>
        </div>
        <div class="nav-section">
            <div class="nav-section-title">Management</div>
            <div class="nav-items">
                <a href="/jobseeker/interviews" class="nav-link"><i class="fas fa-calendar-check"></i><span>Interviews</span><span
                        class="badge" id="interviews-badge">2</span></a>
                <a href="/jobseeker/notifications" class="nav-link"><i
                        class="fas fa-bell"></i><span>Notifications</span><span class="badge" id="notifications-badge">5</span></a>
                <a href="/jobseeker/saved" class="nav-link"><i class="fas fa-bookmark"></i><span>Saved Jobs</span></a>
            </div>
        </div>
        <div class="nav-section">
            <div class="nav-section-title">Settings</div>
            <div class="nav-items">
                <a href="/jobseeker/settings" class="nav-link"><i class="fas fa-cog"></i><span>Settings</span></a>
            </div>
        </div>
    </div>
    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar" id="user-avatar">JD</div>
            <div class="user-details">
                <h4 id="user-name">Loading...</h4>
                <p id="user-email">Loading...</p>
                <span class="status-badge" id="user-status"><i class="fas fa-crown"></i> Premium</span>
            </div>
        </div>
        <a href="/logout" class="logout-btn" style="text-decoration: none;"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</aside>

<nav class="mobile-bottom-nav" id="mobileBottomNav">
    <a href="/jobseeker/dashboard" class="mobile-nav-item"><i class="fas fa-home"></i><span>Home</span></a>
    <a href="/jobseeker/jobs" class="mobile-nav-item"><i class="fas fa-briefcase"></i><span>Jobs</span></a>
    <a href="/jobseeker/applications" class="mobile-nav-item"><i class="fas fa-file-lines"></i><span>Apps</span></a>
    <a href="/jobseeker/interviews" class="mobile-nav-item"><i class="fas fa-calendar-check"></i><span>Interviews</span></a>
    <a href="/jobseeker/notifications" class="mobile-nav-item"><i class="fas fa-bell"></i><span>Alerts</span><span class="badge" id="mobile-notifications-badge">5</span></a>
    <a href="/jobseeker/settings" class="mobile-nav-item"><i class="fas fa-cog"></i><span>Settings</span></a>
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
            <a href="/jobseeker/dashboard" class="drawer-link active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="/jobseeker/jobs" class="drawer-link"><i class="fas fa-briefcase"></i> Find Jobs</a>
            <a href="/jobseeker/applications" class="drawer-link"><i class="fas fa-file-lines"></i> My Applications</a>
        </div>
        <div class="drawer-section">
            <div class="nav-section-title" style="padding-left:24px;">Management</div>
            <a href="/jobseeker/interviews" class="drawer-link"><i class="fas fa-calendar-check"></i> Interviews <span class="badge" id="drawer-interviews-badge" style="margin-left:auto;">2</span></a>
            <a href="/jobseeker/notifications" class="drawer-link"><i class="fas fa-bell"></i> Notifications <span class="badge" id="drawer-notifications-badge">5</span></a>
            <a href="/jobseeker/saved" class="drawer-link"><i class="fas fa-bookmark"></i> Saved Jobs</a>
        </div>
        <div class="drawer-section">
            <div class="nav-section-title" style="padding-left:24px;">Settings</div>
            <a href="/jobseeker/settings" class="drawer-link"><i class="fas fa-cog"></i> Settings</a>
        </div>
    </div>
</div>
<script>
    async function getUserInfo() {
        try {
            const response = await fetch('/api/jobseeker/info');
            if (!response.ok) {
                throw new Error('Failed to fetch user info');
            }
            const result = await response.json();
            const data = result.data; // Assuming the API returns { success: true, data: {...} }
            console.log('User Info:', data);

            // Update user card
            const profile = data.profile || {};
            const user = data.user || {};
            const name = `${profile.first_name || ''} ${profile.last_name || ''}`.trim() || 'User';
            const email = user.email || 'email@example.com';
            const status = profile.premium ? 'Premium' : 'Free'; // Assuming profile has premium field

            document.getElementById('user-name').textContent = name;
            document.getElementById('user-email').textContent = email;
            document.getElementById('user-status').innerHTML = `<i class="fas fa-crown"></i> ${status}`;

            // Update avatar initials
            const initials = name.split(' ').map(n => n[0]).join('').toUpperCase() || 'U';
            const avatarElement = document.getElementById('user-avatar');
            const avatarPath = String(profile.avatar || user.avatar || '').trim();
            const avatarUrl = avatarPath && !/^(?:https?:)?\/\//i.test(avatarPath)
                ? `/${avatarPath.replace(/^\/+/, '')}`
                : avatarPath;
            const renderAvatar = (element, fallbackText) => {
                element.textContent = fallbackText;
                if (!avatarUrl) return;

                const image = document.createElement('img');
                image.src = avatarUrl;
                image.alt = `${name} profile photo`;
                image.onerror = () => {
                    element.textContent = fallbackText;
                };
                element.replaceChildren(image);
            };

            renderAvatar(avatarElement, initials);
            renderAvatar(document.getElementById('sidebar-profile-logo'), 'J');

            // Update badges
            const notifications = data.unread_notifications || 0;
            document.getElementById('notifications-badge').textContent = notifications;
            document.getElementById('mobile-notifications-badge').textContent = notifications;
            document.getElementById('drawer-notifications-badge').textContent = notifications;

            // For interviews, if not in data, set to 0 or fetch separately
            // Assuming not in data, set to 0 for now
            document.getElementById('interviews-badge').textContent = '0';
            document.getElementById('drawer-interviews-badge').textContent = '0';

        } catch (error) {
            console.error('Error fetching user info:', error);
        }
    }

    function setActiveMobileNav() {
        const currentPath = window.location.pathname;
        const navItems = document.querySelectorAll('.mobile-nav-item');

        // Remove active class from all items
        navItems.forEach(item => item.classList.remove('active'));

        // Set active based on current path
        if (currentPath === '/jobseeker/dashboard') {
            document.querySelector('.mobile-nav-item[href="/jobseeker/dashboard"]').classList.add('active');
        } else if (currentPath.startsWith('/jobseeker/jobs')) {
            document.querySelector('.mobile-nav-item[href="/jobseeker/jobs"]').classList.add('active');
        } else if (currentPath.startsWith('/jobseeker/applications')) {
            document.querySelector('.mobile-nav-item[href="/jobseeker/applications"]').classList.add('active');
        } else if (currentPath.startsWith('/jobseeker/interviews')) {
            document.querySelector('.mobile-nav-item[href="/jobseeker/interviews"]').classList.add('active');
        } else if (currentPath.startsWith('/jobseeker/notifications')) {
            document.querySelector('.mobile-nav-item[href="/jobseeker/notifications"]').classList.add('active');
        } else if (currentPath.startsWith('/jobseeker/settings')) {
            document.querySelector('.mobile-nav-item[href="/jobseeker/settings"]').classList.add('active');
        }
    }

    // Call on page load
    document.addEventListener('DOMContentLoaded', function() {
        getUserInfo();
        setActiveMobileNav();
    });
</script>
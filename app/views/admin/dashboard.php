<!-- Admin Dashboard -->
<div class="admin-page">
<style>
/* Admin Dashboard Styles */
.admin-page {
  display: flex;
  height: 100vh;
  background: var(--bg-primary);
  font-family: var(--font-primary);
}

.admin-sidebar {
  width: 260px;
  background: var(--bg-secondary);
  border-right: 1px solid var(--border);
  padding: 2rem 0;
  overflow-y: auto;
  position: sticky;
  top: 0;
}

.admin-sidebar-logo {
  padding: 0 1.5rem 2rem;
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--primary-color);
  font-family: var(--font-heading);
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.admin-nav-item {
  display: flex;
  align-items: center;
  gap: 0.8rem;
  padding: 0.9rem 1.5rem;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.15s;
  border-left: 3px solid transparent;
  font-size: 0.95rem;
  margin: 0.3rem 0;
}

.admin-nav-item:hover {
  background: var(--bg-tertiary);
  color: var(--primary-color);
  border-left-color: var(--primary-color);
}

.admin-nav-item.active {
  background: rgba(67, 97, 238, 0.1);
  color: var(--primary-color);
  border-left-color: var(--primary-color);
  font-weight: 600;
}

.admin-nav-item i {
  width: 18px;
  text-align: center;
}

.admin-content {
  flex: 1;
  overflow-y: auto;
  padding: 2rem;
}

.admin-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--border);
}

.admin-title {
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--text-primary);
  font-family: var(--font-heading);
}

.admin-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.admin-stat-card {
  background: var(--bg-secondary);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
  transition: all 0.2s;
}

.admin-stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-color: var(--primary-color);
}

.admin-stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.stat-icon-users { background: #dbeafe; color: #2563eb; }
.stat-icon-blocked { background: #fee2e2; color: #dc2626; }
.stat-icon-skills { background: #ede9fe; color: #7c3aed; }
.stat-icon-schools { background: #dbeafe; color: #0891b2; }

[data-theme="dark"] .stat-icon-users { background: #1e3a5f; color: #60a5fa; }
[data-theme="dark"] .stat-icon-blocked { background: #7f1d1d; color: #f87171; }
[data-theme="dark"] .stat-icon-skills { background: #2e1065; color: #a78bfa; }
[data-theme="dark"] .stat-icon-schools { background: #083344; color: #67e8f9; }

.admin-stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
  font-family: var(--font-heading);
}

.admin-stat-label {
  font-size: 0.85rem;
  color: var(--text-muted);
  font-weight: 500;
}

.admin-quick-actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 1rem;
  margin-bottom: 3rem;
}

.admin-action-btn {
  background: var(--primary-color);
  color: white;
  border: none;
  border-radius: 10px;
  padding: 1rem;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.6rem;
  transition: all 0.2s;
  text-decoration: none;
  text-align: center;
}

.admin-action-btn:hover {
  opacity: 0.9;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
}

.admin-action-btn i {
  font-size: 1.3rem;
}

@media (max-width: 1024px) {
  .admin-sidebar {
    width: 220px;
    padding: 1.5rem 0;
  }
  
  .admin-content {
    padding: 1.5rem;
  }
}

@media (max-width: 768px) {
  .admin-page {
    flex-direction: column;
  }
  
  .admin-sidebar {
    width: 100%;
    max-height: 200px;
    overflow-x: auto;
    overflow-y: hidden;
    display: flex;
    border-bottom: 1px solid var(--border);
    border-right: none;
    padding: 1rem;
  }
  
  .admin-sidebar-logo {
    display: none;
  }
  
  .admin-nav-item {
    margin: 0 0.5rem;
    white-space: nowrap;
    border-left: none;
    border-bottom: 3px solid transparent;
  }
  
  .admin-nav-item:hover,
  .admin-nav-item.active {
    border-bottom-color: var(--primary-color);
    border-left: none;
  }
  
  .admin-stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>

  <!-- Sidebar -->
  <div class="admin-sidebar">
    <div class="admin-sidebar-logo">
      <i class="fas fa-shield-alt"></i> Admin
    </div>

    <a href="/admin/dashboard" class="admin-nav-item active" data-section="dashboard">
      <i class="fas fa-chart-line"></i> Dashboard
    </a>

    <a href="/admin/users" class="admin-nav-item" data-section="users">
      <i class="fas fa-users"></i> Users
    </a>

    <a href="/admin/skills" class="admin-nav-item" data-section="skills">
      <i class="fas fa-star"></i> Skills
    </a>

    <a href="/admin/schools" class="admin-nav-item" data-section="schools">
      <i class="fas fa-graduation-cap"></i> Schools
    </a>

    <a href="/admin/categories" class="admin-nav-item" data-section="categories">
      <i class="fas fa-list"></i> Categories
    </a>

    <a href="/admin/industries" class="admin-nav-item" data-section="industries">
      <i class="fas fa-industry"></i> Industries
    </a>

    <div style="margin-top: 2rem; padding: 1rem 0 0; border-top: 1px solid var(--border);">
      <a href="/jobseeker/settings" class="admin-nav-item">
        <i class="fas fa-user-circle"></i> My Settings
      </a>

      <a href="#logout" class="admin-nav-item" id="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="admin-content">
    <div class="admin-header">
      <div>
        <h1 class="admin-title">Admin Dashboard</h1>
        <p style="color: var(--text-muted); margin-top: 0.4rem;">Welcome back! Here's what's happening.</p>
      </div>
      <div style="text-align: right;">
        <p id="current-time" style="font-size: 0.9rem; color: var(--text-muted);"></p>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="admin-stats-grid">
      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-users">
          <i class="fas fa-users"></i>
        </div>
        <div class="admin-stat-value" id="stat-total-users">0</div>
        <div class="admin-stat-label">Total Users</div>
      </div>

      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-blocked">
          <i class="fas fa-ban"></i>
        </div>
        <div class="admin-stat-value" id="stat-blocked-users">0</div>
        <div class="admin-stat-label">Blocked Users</div>
      </div>

      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-skills">
          <i class="fas fa-star"></i>
        </div>
        <div class="admin-stat-value" id="stat-total-skills">0</div>
        <div class="admin-stat-label">Total Skills</div>
      </div>

      <div class="admin-stat-card">
        <div class="admin-stat-icon stat-icon-schools">
          <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="admin-stat-value" id="stat-total-schools">0</div>
        <div class="admin-stat-label">Total Schools</div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div style="margin-bottom: 2rem;">
      <h2 style="font-size: 1.1rem; color: var(--text-primary); margin-bottom: 1rem; font-weight: 600;">Quick Actions</h2>
      <div class="admin-quick-actions">
        <a href="/admin/users" class="admin-action-btn">
          <i class="fas fa-user-shield"></i> Manage Users
        </a>
        <a href="/admin/skills/create" class="admin-action-btn">
          <i class="fas fa-plus"></i> Add Skill
        </a>
        <a href="/admin/schools/create" class="admin-action-btn">
          <i class="fas fa-plus"></i> Add School
        </a>
        <a href="/admin/categories" class="admin-action-btn">
          <i class="fas fa-list"></i> Categories
        </a>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  'use strict';

  // Update current time
  function updateTime() {
    const now = new Date();
    document.getElementById('current-time').textContent = now.toLocaleDateString('en-GB', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  // Load dashboard statistics
  async function loadStats() {
    try {
      const res = await fetch('/api/admin/dashboard/stats');
      const json = await res.json();

      if (json.success && json.data) {
        const { users, blocked_users, total_skills, total_schools } = json.data;
        document.getElementById('stat-total-users').textContent = users?.total || 0;
        document.getElementById('stat-blocked-users').textContent = blocked_users || 0;
        document.getElementById('stat-total-skills').textContent = total_skills || 0;
        document.getElementById('stat-total-schools').textContent = total_schools || 0;
      }
    } catch (err) {
      console.error('Error loading stats:', err);
    }
  }

  // Logout handler
  document.getElementById('logout-btn')?.addEventListener('click', async (e) => {
    e.preventDefault();
    try {
      await fetch('/api/logout', { method: 'POST' });
      window.location.href = '/login';
    } catch (err) {
      console.error('Logout error:', err);
    }
  });

  updateTime();
  loadStats();
  setInterval(updateTime, 60000);
})();
</script>

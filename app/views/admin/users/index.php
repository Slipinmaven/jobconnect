<!-- Admin: Users Management -->
<div class="admin-page">
<style>
.admin-page { display: flex; height: 100vh; background: var(--bg-primary); font-family: var(--font-primary); }
.admin-sidebar { width: 260px; background: var(--bg-secondary); border-right: 1px solid var(--border); padding: 2rem 0; overflow-y: auto; }
.admin-sidebar-logo { padding: 0 1.5rem 2rem; font-size: 1.3rem; font-weight: 700; color: var(--primary-color); font-family: var(--font-heading); display: flex; align-items: center; gap: 0.6rem; }
.admin-nav-item { display: flex; align-items: center; gap: 0.8rem; padding: 0.9rem 1.5rem; color: var(--text-secondary); cursor: pointer; transition: all 0.15s; border-left: 3px solid transparent; font-size: 0.95rem; margin: 0.3rem 0; text-decoration: none; }
.admin-nav-item:hover { background: var(--bg-tertiary); color: var(--primary-color); border-left-color: var(--primary-color); }
.admin-nav-item.active { background: rgba(67, 97, 238, 0.1); color: var(--primary-color); border-left-color: var(--primary-color); font-weight: 600; }
.admin-nav-item i { width: 18px; text-align: center; }
.admin-content { flex: 1; overflow-y: auto; padding: 2rem; }
.admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border); }
.admin-title { font-size: 1.8rem; font-weight: 700; color: var(--text-primary); font-family: var(--font-heading); }

.admin-filters { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; }
.admin-filter-group { display: flex; flex-direction: column; gap: 0.4rem; }
.admin-filter-label { font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
.admin-filter-input, .admin-filter-select { padding: 0.6rem 0.9rem; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-primary); color: var(--text-primary); font-size: 0.875rem; font-family: var(--font-primary); }
.admin-filter-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.8rem center; padding-right: 2.2rem; cursor: pointer; }
.admin-filter-btn { padding: 0.6rem 1.2rem; background: var(--primary-color); color: white; border: none; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; align-self: flex-end; }

.admin-table { width: 100%; border-collapse: collapse; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.admin-table thead { background: var(--bg-tertiary); }
.admin-table th { padding: 0.9rem 1.2rem; text-align: left; font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--border); }
.admin-table td { padding: 1rem 1.2rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; color: var(--text-primary); }
.admin-table tr:hover { background: var(--bg-tertiary); }

.admin-status-badge { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
.status-active { background: #d1fae5; color: #065f46; }
.status-blocked { background: #fee2e2; color: #991b1b; }
[data-theme="dark"] .status-active { background: #064e3b; color: #6ee7b7; }
[data-theme="dark"] .status-blocked { background: #7f1d1d; color: #fca5a5; }

.admin-action-cell { display: flex; gap: 0.5rem; align-items: center; }
.admin-btn-small { padding: 0.4rem 0.8rem; border: none; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.15s; }
.btn-block { background: #fee2e2; color: #991b1b; }
.btn-block:hover { background: #fca5a5; }
.btn-unblock { background: #d1fae5; color: #065f46; }
.btn-unblock:hover { background: #a7f3d0; }

.admin-pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem; }
.admin-page-btn { padding: 0.6rem 1rem; border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-primary); border-radius: 6px; cursor: pointer; font-weight: 500; }
.admin-page-btn.active { background: var(--primary-color); color: white; border-color: var(--primary-color); }
</style>

  <!-- Sidebar -->
  <div class="admin-sidebar">
    <div class="admin-sidebar-logo">
      <i class="fas fa-shield-alt"></i> Admin
    </div>
    <a href="/admin/dashboard" class="admin-nav-item">
      <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="/admin/users" class="admin-nav-item active">
      <i class="fas fa-users"></i> Users
    </a>
    <a href="/admin/skills" class="admin-nav-item">
      <i class="fas fa-star"></i> Skills
    </a>
    <a href="/admin/schools" class="admin-nav-item">
      <i class="fas fa-graduation-cap"></i> Schools
    </a>
    <a href="/admin/categories" class="admin-nav-item">
      <i class="fas fa-list"></i> Categories
    </a>
    <a href="/admin/industries" class="admin-nav-item">
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
      <h1 class="admin-title">Manage Users</h1>
    </div>

    <!-- Filters -->
    <div class="admin-filters">
      <div class="admin-filter-group">
        <label class="admin-filter-label">Search</label>
        <input type="text" id="filter-search" class="admin-filter-input" placeholder="Email, name...">
      </div>
      <div class="admin-filter-group">
        <label class="admin-filter-label">Role</label>
        <select id="filter-role" class="admin-filter-select">
          <option value="">All Roles</option>
          <option value="jobseeker">Job Seeker</option>
          <option value="employer">Employer</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="admin-filter-group">
        <label class="admin-filter-label">Status</label>
        <select id="filter-status" class="admin-filter-select">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="blocked">Blocked</option>
        </select>
      </div>
      <div class="admin-filter-group">
        <label class="admin-filter-label">Verified</label>
        <select id="filter-verified" class="admin-filter-select">
          <option value="">All</option>
          <option value="yes">Verified</option>
          <option value="no">Unverified</option>
        </select>
      </div>
      <button class="admin-filter-btn" id="apply-filters">Apply Filters</button>
    </div>

    <!-- Users Table -->
    <table class="admin-table">
      <thead>
        <tr>
          <th>Email</th>
          <th>Name</th>
          <th>Role</th>
          <th>Status</th>
          <th>Verified</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="users-tbody">
        <!-- Populated by JS -->
      </tbody>
    </table>

    <!-- Pagination -->
    <div class="admin-pagination" id="pagination"></div>
  </div>
</div>

<script>
(function() {
  'use strict';

  const API = {
    users: '/api/admin/users',
    blockUntil: (id) => `/api/admin/users/${id}/block-until`,
    unblock: (id) => `/api/admin/users/${id}/unblock`
  };

  let currentPage = 1;

  async function apiFetch(url, opts = {}) {
    const res = await fetch(url, opts);
    const json = await res.json();
    if (!json.success) throw new Error(json.message || 'Request failed');
    return json;
  }

  function renderUsers(result) {
    const tbody = document.getElementById('users-tbody');
    tbody.innerHTML = '';

    if (!result.users || result.users.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;">No users found</td></tr>';
      return;
    }

    result.users.forEach(user => {
      const row = document.createElement('tr');
      const isBlocked = user.is_blocked;
      const verified = user.email_verified_at ? 'Yes' : 'No';
      const joined = new Date(user.created_at).toLocaleDateString();

      row.innerHTML = `
        <td>${user.email}</td>
        <td>${user.first_name} ${user.last_name}</td>
        <td>${user.role}</td>
        <td>
          <span class="admin-status-badge ${isBlocked ? 'status-blocked' : 'status-active'}">
            ${isBlocked ? '🚫 Blocked' : '✓ Active'}
          </span>
        </td>
        <td>${verified}</td>
        <td>${joined}</td>
        <td>
          <div class="admin-action-cell">
            ${!isBlocked 
              ? `<button class="admin-btn-small btn-block" onclick="blockUser(${user.id})">Block</button>` 
              : `<button class="admin-btn-small btn-unblock" onclick="unblockUser(${user.id})">Unblock</button>`
            }
          </div>
        </td>
      `;
      tbody.appendChild(row);
    });

    renderPagination(result);
  }

  function renderPagination(result) {
    const container = document.getElementById('pagination');
    container.innerHTML = '';

    for (let i = 1; i <= result.pagination.pages; i++) {
      const btn = document.createElement('button');
      btn.className = `admin-page-btn ${i === currentPage ? 'active' : ''}`;
      btn.textContent = i;
      btn.onclick = () => {
        currentPage = i;
        loadUsers();
      };
      container.appendChild(btn);
    }
  }

  async function loadUsers() {
    try {
      const search = document.getElementById('filter-search').value;
      const role = document.getElementById('filter-role').value;
      const status = document.getElementById('filter-status').value;
      const verified = document.getElementById('filter-verified').value;

      const params = new URLSearchParams({
        page: currentPage,
        ...(search && { search }),
        ...(role && { role }),
        ...(status && { status }),
        ...(verified && { verified })
      });

      const result = await apiFetch(`${API.users}?${params}`);
      renderUsers(result.data);
    } catch (err) {
      console.error('Error loading users:', err);
      alert('Failed to load users');
    }
  }

  window.blockUser = async (userId) => {
    const blockedUntil = prompt('Block until (format: YYYY-MM-DD HH:MM:SS):', new Date().toLocaleString());
    if (!blockedUntil) return;

    try {
      await apiFetch(API.blockUntil(userId), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ blocked_until: blockedUntil, reason: 'Admin action' })
      });
      loadUsers();
      alert('User blocked successfully');
    } catch (err) {
      console.error('Error blocking user:', err);
      alert('Failed to block user');
    }
  };

  window.unblockUser = async (userId) => {
    if (!confirm('Unblock this user?')) return;

    try {
      await apiFetch(API.unblock(userId), { method: 'POST' });
      loadUsers();
      alert('User unblocked successfully');
    } catch (err) {
      console.error('Error unblocking user:', err);
      alert('Failed to unblock user');
    }
  };

  document.getElementById('apply-filters').addEventListener('click', () => {
    currentPage = 1;
    loadUsers();
  });

  document.getElementById('logout-btn')?.addEventListener('click', async (e) => {
    e.preventDefault();
    try {
      await fetch('/api/logout', { method: 'POST' });
      window.location.href = '/login';
    } catch (err) {
      console.error('Logout error:', err);
    }
  });

  loadUsers();
})();
</script>

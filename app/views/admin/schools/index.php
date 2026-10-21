<!-- Admin: Schools Management -->
<div class="admin-page">
<style>
.admin-page { display: flex; min-height: 100vh; background: var(--bg-primary); font-family: var(--font-primary); }
.admin-sidebar { width: 260px; background: var(--bg-secondary); border-right: 1px solid var(--border); padding: 2rem 0; }
.admin-sidebar-logo { padding: 0 1.5rem 2rem; font-size: 1.3rem; font-weight: 700; color: var(--primary-color); font-family: var(--font-heading); display: flex; align-items: center; gap: 0.6rem; }
.admin-nav-item { display: flex; align-items: center; gap: 0.8rem; padding: 0.9rem 1.5rem; color: var(--text-secondary); cursor: pointer; transition: all 0.15s; border-left: 3px solid transparent; font-size: 0.95rem; margin: 0.3rem 0; text-decoration: none; }
.admin-nav-item:hover { background: var(--bg-tertiary); color: var(--primary-color); border-left-color: var(--primary-color); }
.admin-nav-item.active { background: rgba(67, 97, 238, 0.1); color: var(--primary-color); border-left-color: var(--primary-color); font-weight: 600; }
.admin-nav-item i { width: 18px; text-align: center; }
.admin-content { flex: 1; padding: 2rem; }
.admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border); }
.admin-title { font-size: 1.8rem; font-weight: 700; color: var(--text-primary); font-family: var(--font-heading); }
.admin-header-actions { display: flex; gap: 1rem; }
.admin-btn { padding: 0.7rem 1.5rem; background: var(--primary-color); color: white; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
.admin-btn:hover { opacity: 0.9; }

.admin-filters { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; }
.admin-filter-group { display: flex; flex-direction: column; gap: 0.4rem; }
.admin-filter-label { font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
.admin-filter-input, .admin-filter-select { padding: 0.6rem 0.9rem; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-primary); color: var(--text-primary); font-size: 0.875rem; font-family: var(--font-primary); }
.admin-filter-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.8rem center; padding-right: 2.2rem; cursor: pointer; }
.admin-filter-btn { padding: 0.6rem 1.2rem; background: var(--primary-color); color: white; border: none; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; align-self: flex-end; }

.admin-table { width: 100%; border-collapse: collapse; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.admin-table th { padding: 0.9rem 1.2rem; text-align: left; font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; background: var(--bg-tertiary); border-bottom: 1px solid var(--border); }
.admin-table td { padding: 1rem 1.2rem; border-bottom: 1px solid var(--border); color: var(--text-primary); }
.admin-table tr:hover { background: var(--bg-tertiary); }

.admin-btn-edit { padding: 0.4rem 0.8rem; background: #dbeafe; color: #1e40af; border: none; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: pointer; }
.admin-btn-delete { padding: 0.4rem 0.8rem; background: #fee2e2; color: #991b1b; border: none; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: pointer; margin-left: 0.4rem; }

.school-type-badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; }
.type-university { background: #dbeafe; color: #1e40af; }
.type-college { background: #ede9fe; color: #5b21b6; }
.type-highschool { background: #fef3c7; color: #92400e; }
.type-bootcamp { background: #d1fae5; color: #065f46; }
</style>

  <!-- Sidebar -->
  <div class="admin-sidebar">
    <div class="admin-sidebar-logo">
      <i class="fas fa-shield-alt"></i> Admin
    </div>
    <a href="/admin/dashboard" class="admin-nav-item">
      <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="/admin/users" class="admin-nav-item">
      <i class="fas fa-users"></i> Users
    </a>
    <a href="/admin/skills" class="admin-nav-item">
      <i class="fas fa-star"></i> Skills
    </a>
    <a href="/admin/schools" class="admin-nav-item active">
      <i class="fas fa-graduation-cap"></i> Schools
    </a>
    <div style="margin-top: 2rem; padding: 1rem 0 0; border-top: 1px solid var(--border);">
      <a href="#logout" class="admin-nav-item" id="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="admin-content">
    <div class="admin-header">
      <h1 class="admin-title">Manage Schools</h1>
      <div class="admin-header-actions">
        <a href="/admin/schools/create" class="admin-btn">
          <i class="fas fa-plus"></i> Add School
        </a>
      </div>
    </div>

    <!-- Filters -->
    <div class="admin-filters">
      <div class="admin-filter-group">
        <label class="admin-filter-label">Search</label>
        <input type="text" id="filter-search" class="admin-filter-input" placeholder="School name...">
      </div>
      <div class="admin-filter-group">
        <label class="admin-filter-label">Type</label>
        <select id="filter-type" class="admin-filter-select">
          <option value="">All Types</option>
          <option value="university">University</option>
          <option value="college">College</option>
          <option value="high_school">High School</option>
          <option value="bootcamp">Bootcamp</option>
        </select>
      </div>
      <div class="admin-filter-group">
        <label class="admin-filter-label">Country</label>
        <select id="filter-country" class="admin-filter-select">
          <option value="">All Countries</option>
          <option value="Kenya">Kenya</option>
          <option value="Uganda">Uganda</option>
          <option value="Tanzania">Tanzania</option>
          <option value="Rwanda">Rwanda</option>
        </select>
      </div>
      <button class="admin-filter-btn" id="apply-filters">Apply Filters</button>
    </div>

    <!-- Schools Table -->
    <table class="admin-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Type</th>
          <th>Country</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="schools-tbody">
        <!-- Populated by JS -->
      </tbody>
    </table>
  </div>
</div>

<script>
(function() {
  'use strict';

  const API = {
    schools: '/api/admin/schools',
    delete: (id) => `/api/admin/schools/${id}`
  };

  let currentPage = 1;

  async function apiFetch(url, opts = {}) {
    const res = await fetch(url, opts);
    const json = await res.json();
    if (!json.success) throw new Error(json.message || 'Request failed');
    return json;
  }

  function getTypeBadgeClass(type) {
    const map = {
      'university': 'type-university',
      'college': 'type-college',
      'high_school': 'type-highschool',
      'bootcamp': 'type-bootcamp'
    };
    return map[type] || 'type-university';
  }

  function renderSchools(result) {
    const tbody = document.getElementById('schools-tbody');
    tbody.innerHTML = '';

    if (!result.schools || result.schools.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:2rem;">No schools found</td></tr>';
      return;
    }

    result.schools.forEach(school => {
      const row = document.createElement('tr');
      const created = new Date(school.created_at).toLocaleDateString();
      const typeLabel = school.type.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
      
      row.innerHTML = `
        <td>${school.name}</td>
        <td>
          <span class="school-type-badge ${getTypeBadgeClass(school.type)}">
            ${typeLabel}
          </span>
        </td>
        <td>${school.country || 'N/A'}</td>
        <td>${created}</td>
        <td>
          <a href="/admin/schools/${school.id}/edit" class="admin-btn-edit">Edit</a>
          <button class="admin-btn-delete" onclick="deleteSchool(${school.id})">Delete</button>
        </td>
      `;
      tbody.appendChild(row);
    });
  }

  async function loadSchools() {
    try {
      const search = document.getElementById('filter-search').value;
      const type = document.getElementById('filter-type').value;
      const country = document.getElementById('filter-country').value;

      const params = new URLSearchParams({
        page: currentPage,
        ...(search && { search }),
        ...(type && { type }),
        ...(country && { country })
      });

      const result = await apiFetch(`${API.schools}?${params}`);
      renderSchools(result.data);
    } catch (err) {
      console.error('Error loading schools:', err);
      alert('Failed to load schools');
    }
  }

  window.deleteSchool = async (id) => {
    if (!confirm('Delete this school?')) return;

    try {
      const res = await fetch(API.delete(id), { method: 'DELETE' });
      const json = await res.json();
      if (json.success) {
        loadSchools();
        alert('School deleted successfully');
      }
    } catch (err) {
      console.error('Delete error:', err);
      alert('Failed to delete school');
    }
  };

  document.getElementById('apply-filters').addEventListener('click', () => {
    currentPage = 1;
    loadSchools();
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

  loadSchools();
})();
</script>

<!-- Admin: Skills Management -->
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

.admin-table { width: 100%; border-collapse: collapse; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.admin-table th { padding: 0.9rem 1.2rem; text-align: left; font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; background: var(--bg-tertiary); border-bottom: 1px solid var(--border); }
.admin-table td { padding: 1rem 1.2rem; border-bottom: 1px solid var(--border); color: var(--text-primary); }
.admin-table tr:hover { background: var(--bg-tertiary); }

.admin-btn-edit { padding: 0.4rem 0.8rem; background: #dbeafe; color: #1e40af; border: none; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: pointer; }
.admin-btn-delete { padding: 0.4rem 0.8rem; background: #fee2e2; color: #991b1b; border: none; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: pointer; margin-left: 0.4rem; }
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
    <a href="/admin/skills" class="admin-nav-item active">
      <i class="fas fa-star"></i> Skills
    </a>
    <a href="/admin/schools" class="admin-nav-item">
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
      <h1 class="admin-title">Manage Skills</h1>
      <div class="admin-header-actions">
        <a href="/admin/skills/create" class="admin-btn">
          <i class="fas fa-plus"></i> Add Skill
        </a>
      </div>
    </div>

    <!-- Skills Table -->
    <table class="admin-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Category</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="skills-tbody">
        <!-- Populated by JS -->
      </tbody>
    </table>
  </div>
</div>

<script>
(function() {
  'use strict';

  const API = {
    skills: '/api/admin/skills',
    delete: (id) => `/api/admin/skills/${id}`
  };

  async function loadSkills() {
    try {
      const res = await fetch(API.skills);
      const json = await res.json();

      if (!json.success) throw new Error('Failed to load skills');

      const tbody = document.getElementById('skills-tbody');
      tbody.innerHTML = '';

      if (!json.data.skills || json.data.skills.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:2rem;">No skills found</td></tr>';
        return;
      }

      json.data.skills.forEach(skill => {
        const row = document.createElement('tr');
        const created = new Date(skill.created_at).toLocaleDateString();
        
        row.innerHTML = `
          <td>${skill.name}</td>
          <td>${skill.category_name || 'Uncategorized'}</td>
          <td>${created}</td>
          <td>
            <a href="/admin/skills/${skill.id}/edit" class="admin-btn-edit">Edit</a>
            <button class="admin-btn-delete" onclick="deleteSkill(${skill.id})">Delete</button>
          </td>
        `;
        tbody.appendChild(row);
      });
    } catch (err) {
      console.error('Error loading skills:', err);
    }
  }

  window.deleteSkill = async (id) => {
    if (!confirm('Delete this skill?')) return;

    try {
      const res = await fetch(API.delete(id), { method: 'DELETE' });
      const json = await res.json();
      if (json.success) {
        loadSkills();
      }
    } catch (err) {
      console.error('Delete error:', err);
    }
  };

  loadSkills();
})();
</script>


<main class="dash-main" id="jobseeker-dashboard">

  <!-- ═══════════════ HERO ═══════════════ -->
  <div class="dash-hero">
    <div class="hero-inner">

      <!-- Avatar + greeting -->
      <div class="hero-left">
        <div class="hero-avatar" id="hero-avatar">
          <!-- filled by JS -->
        </div>
        <div class="hero-text">
          <h1 id="hero-name">Loading…</h1>
          <p class="hero-role" id="hero-title"></p>
        </div>
      </div>

      <!-- Open to work badge -->
      <div class="hero-right">
        <span class="hero-badge" id="hero-otw" style="display:none">
          <span class="dot"></span>
          Open to Work
        </span>
      </div>

    </div><!-- /hero-inner -->

    <!-- Quick stats -->
    <div class="hero-stats">
      <div class="hstat">
        <div class="hstat-num" id="stat-applications">–</div>
        <div class="hstat-label">Applications</div>
      </div>
      <div class="hstat">
        <div class="hstat-num" id="stat-interviews">–</div>
        <div class="hstat-label">Interviews</div>
      </div>
      <div class="hstat">
        <div class="hstat-num" id="stat-saved">–</div>
        <div class="hstat-label">Saved Jobs</div>
      </div>
      <div class="hstat">
        <div class="hstat-num" id="stat-notifications">–</div>
        <div class="hstat-label">Notifications</div>
      </div>
    </div>

  </div><!-- /dash-hero -->


  <!-- ═══════════════ BODY GRID ═══════════════ -->
  <div class="dash-body">

    <!-- ── LEFT COLUMN ── -->
    <div class="dash-left">

      <!-- Profile Completion -->
      <div class="d-card mb-3" id="completion-card">
        <div class="sec-head">
          <h2><i class="fas fa-chart-pie" style="color:var(--primary-color);margin-right:.5rem"></i>Profile Completion</h2>
          <a href="/jobseeker/profile-setup">Complete Profile</a>
        </div>
        <div class="completion-bar-wrap">
          <div class="completion-bar" id="completion-bar" style="width:0%"></div>
        </div>
        <div class="completion-meta">
          <span>Keep going — employers notice complete profiles!</span>
          <span class="completion-pct" id="completion-pct">0%</span>
        </div>
      </div>

      <!-- Upcoming Interviews -->
      <div class="d-card mb-3">
        <div class="sec-head">
          <h2><i class="fas fa-calendar-check" style="color:var(--primary-color);margin-right:.5rem"></i>Upcoming Interviews</h2>
          <a href="/jobseeker/interviews">View all</a>
        </div>
        <div class="interview-list" id="interview-list">
          <!-- skeleton -->
          <div class="skel" style="height:80px;border-radius:1rem"></div>
          <div class="skel" style="height:80px;border-radius:1rem"></div>
        </div>
      </div>

      <!-- My Applications -->
      <div class="d-card mb-3">
        <div class="sec-head">
          <h2><i class="fas fa-paper-plane" style="color:var(--primary-color);margin-right:.5rem"></i>My Applications</h2>
          <a href="/jobseeker/applications">View all</a>
        </div>
        <div class="app-list" id="app-list">
          <div class="skel" style="height:64px;border-radius:.9rem"></div>
          <div class="skel" style="height:64px;border-radius:.9rem"></div>
        </div>
      </div>

      <!-- Saved Jobs -->
      <div class="d-card">
        <div class="sec-head">
          <h2><i class="fas fa-bookmark" style="color:var(--primary-color);margin-right:.5rem"></i>Saved Jobs</h2>
          <a href="/jobseeker/jobs">Browse Jobs</a>
        </div>
        <div class="saved-list" id="saved-list">
          <div class="skel" style="height:64px;border-radius:.9rem"></div>
        </div>
      </div>

    </div><!-- /dash-left -->


    <!-- ── RIGHT COLUMN ── -->
    <div class="dash-right">

      <!-- Skills -->
      <div class="d-card">
        <div class="sec-head">
          <h2><i class="fas fa-code" style="color:var(--primary-color);margin-right:.5rem"></i>Your Skills</h2>
        </div>
        <div class="skill-chips" id="skill-chips">
          <div class="skel" style="height:30px;width:80px;border-radius:99px"></div>
          <div class="skel" style="height:30px;width:100px;border-radius:99px"></div>
          <div class="skel" style="height:30px;width:60px;border-radius:99px"></div>
        </div>
      </div>

      <!-- Experience -->
      <div class="d-card">
        <div class="sec-head">
          <h2><i class="fas fa-briefcase" style="color:var(--primary-color);margin-right:.5rem"></i>Experience</h2>
        </div>
        <div class="timeline" id="exp-timeline">
          <div class="skel" style="height:60px;border-radius:.75rem;margin-bottom:.75rem"></div>
        </div>
      </div>

      <!-- Education -->
      <div class="d-card">
        <div class="sec-head">
          <h2><i class="fas fa-graduation-cap" style="color:var(--primary-color);margin-right:.5rem"></i>Education</h2>
        </div>
        <div class="timeline" id="edu-timeline">
          <div class="skel" style="height:60px;border-radius:.75rem;margin-bottom:.75rem"></div>
        </div>
      </div>

      <!-- Notifications -->
      <div class="d-card">
        <div class="sec-head">
          <h2><i class="fas fa-bell" style="color:var(--primary-color);margin-right:.5rem"></i>Notifications</h2>
        </div>
        <div id="notif-container">
          <div class="skel" style="height:40px;border-radius:.75rem"></div>
        </div>
      </div>

    </div><!-- /dash-right -->

  </div><!-- /dash-body -->

</main>


<!-- ─────────────────────────────── SCRIPTS ─────────────────────────────── -->
<script>
// ═══════════════════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Convert a UTC datetime string ("2026-04-23 14:39:00") to the user's
 * local time, returning a structured object.
 */
function parseUtcDate(raw) {
  if (!raw) return null;
  // Replace space with T so Date() parses it as UTC universally
  const d = new Date(raw.includes('T') ? raw : raw.replace(' ', 'T') + 'Z');
  return isNaN(d.getTime()) ? null : d;
}

function fmtDate(d) {
  if (!d) return '';
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function fmtTime(d) {
  if (!d) return '';
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function relativeTime(raw) {
  const d = parseUtcDate(raw);
  if (!d) return '';
  const diff = Date.now() - d.getTime();
  const abs  = Math.abs(diff);
  const mins = Math.floor(abs / 60000);
  const hrs  = Math.floor(abs / 3600000);
  const days = Math.floor(abs / 86400000);
  const future = diff < 0;

  if (mins < 1)   return future ? 'in moments'        : 'just now';
  if (mins < 60)  return future ? `in ${mins}m`        : `${mins}m ago`;
  if (hrs  < 24)  return future ? `in ${hrs}h`         : `${hrs}h ago`;
  if (days < 7)   return future ? `in ${days}d`        : `${days}d ago`;
  return fmtDate(d);
}

/** Countdown until a future date (e.g. "in 2d 4h") */
function countdown(d) {
  if (!d) return null;
  const ms = d.getTime() - Date.now();
  if (ms <= 0) return null;
  const days = Math.floor(ms / 86400000);
  const hrs  = Math.floor((ms % 86400000) / 3600000);
  const mins = Math.floor((ms % 3600000)  / 60000);
  if (days > 0) return `${days}d ${hrs}h`;
  if (hrs  > 0) return `${hrs}h ${mins}m`;
  return `${mins}m`;
}

function fmtSalary(min, max, currency = 'KES') {
  const fmt = n => Number(n).toLocaleString();
  if (min && max) return `${currency} ${fmt(min)} – ${fmt(max)}`;
  if (min) return `From ${currency} ${fmt(min)}`;
  if (max) return `Up to ${currency} ${fmt(max)}`;
  return '';
}

function initials(name) {
  if (!name) return '?';
  return name.split(' ').map(p => p[0]).join('').slice(0,2).toUpperCase();
}

function empty(icon, msg) {
  return `<div class="empty-state"><i class="fas ${icon}"></i><p>${msg}</p></div>`;
}

// ═══════════════════════════════════════════════════════════════════════════
// API FETCH HELPERS
// ═══════════════════════════════════════════════════════════════════════════

async function apiFetch(url) {
  const res  = await fetch(url);
  const json = await res.json();
  if (!res.ok) throw new Error(json.message || `HTTP ${res.status}`);
  return json.data;
}

// ═══════════════════════════════════════════════════════════════════════════
// RENDER FUNCTIONS
// ═══════════════════════════════════════════════════════════════════════════

function renderProfile(data) {
  const p   = data.profile;
  const u   = data.user;
  const avatarSrc = u.avatar ? `/${u.avatar.replace(/^\//, '')}` : null;

  // Hero avatar
  const avatarEl = document.getElementById('hero-avatar');
  if (avatarSrc) {
    avatarEl.innerHTML = `<img src="${avatarSrc}" alt="${p.first_name}" onerror="this.parentElement.textContent='${initials(p.first_name + ' ' + p.last_name)}'">`;
  } else {
    avatarEl.textContent = initials(p.first_name + ' ' + p.last_name);
  }

  document.getElementById('hero-name').textContent  = `Hi, ${p.first_name} 👋`;
  document.getElementById('hero-title').textContent = p.professional_title || 'Jobseeker';

  if (p.open_to_work) document.getElementById('hero-otw').style.display = 'flex';

  // Profile completion bar
  const pct = p.profile_completion || 0;
  document.getElementById('completion-bar').style.width = pct + '%';
  document.getElementById('completion-pct').textContent = pct + '%';

  // Skills
  const chipsEl = document.getElementById('skill-chips');
  if (data.skills && data.skills.length) {
    chipsEl.innerHTML = data.skills.map(s => `
      <span class="skill-chip" title="${s.proficiency} · ${s.years_experience ? s.years_experience + ' yr(s)' : 'N/A'}">
        <span class="level-dot ${s.proficiency}"></span>
        ${s.skill_name}
      </span>`).join('');
  } else {
    chipsEl.innerHTML = '<span style="color:var(--text-muted);font-size:.82rem">No skills added yet.</span>';
  }

  // Experience timeline
  const expEl = document.getElementById('exp-timeline');
  if (data.experience && data.experience.length) {
    expEl.innerHTML = data.experience.map(e => {
      const end = e.currently_working ? 'Present' : (e.end_year || '?');
      return `<div class="tl-item">
        <div class="tl-title">${e.role_title}</div>
        <div class="tl-sub">${e.company_name}</div>
        <span class="tl-dates"><i class="fas fa-calendar-alt" style="font-size:.65rem"></i>${e.start_year} – ${end}</span>
      </div>`;
    }).join('');
  } else {
    expEl.innerHTML = empty('fa-briefcase', 'No experience added yet.');
  }

  // Education timeline
  const eduEl = document.getElementById('edu-timeline');
  if (data.education && data.education.length) {
    eduEl.innerHTML = data.education.map(e => {
      const end = e.currently_studying
        ? (e.expected_graduation_year ? `Expected ${e.expected_graduation_year}` : 'Present')
        : (e.end_year || '?');
      return `<div class="tl-item">
        <div class="tl-title">${e.field_of_study} <span style="font-weight:400;font-size:.75rem;color:var(--text-muted)">(${e.education_level})</span></div>
        <div class="tl-sub">${e.school_name}${e.city ? ' · ' + e.city : ''}</div>
        <span class="tl-dates"><i class="fas fa-calendar-alt" style="font-size:.65rem"></i>${e.start_year} – ${end}</span>
      </div>`;
    }).join('');
  } else {
    eduEl.innerHTML = empty('fa-graduation-cap', 'No education added yet.');
  }
}


function renderInterviews(data) {
  const listEl = document.getElementById('interview-list');
  const interviews = data.interviews || [];

  // Sort upcoming first
  const sorted = [...interviews].sort((a, b) =>
    new Date(a.scheduled_at.replace(' ','T')+'Z') - new Date(b.scheduled_at.replace(' ','T')+'Z')
  );

  // Only show scheduled ones on dashboard (top 3)
  const upcoming = sorted.filter(i => i.status === 'scheduled').slice(0, 3);

  document.getElementById('stat-interviews').textContent = data.stats.scheduled ?? interviews.length;

  if (!upcoming.length) {
    listEl.innerHTML = empty('fa-calendar-times', 'No upcoming interviews scheduled.');
    return;
  }

  listEl.innerHTML = upcoming.map(iv => {
    const d    = parseUtcDate(iv.scheduled_at);
    const day  = d ? d.toLocaleDateString('en-GB', { day: 'numeric' }) : '?';
    const mon  = d ? d.toLocaleDateString('en-GB', { month: 'short' }).toUpperCase() : '';
    const time = d ? fmtTime(d) : '';
    const cd   = countdown(d);
    const type = iv.interview_type || 'video';
    const typeLabel = { video: 'Video Call', phone: 'Phone Call', onsite: 'On-Site' }[type] || type;
    const typeIcon  = { video: 'fa-video',   phone: 'fa-phone',   onsite: 'fa-building' }[type] || 'fa-calendar';
    const hasLink   = !!iv.meeting_link;
    const logo = iv.employer_avatar
      ? `<img src="${iv.employer_avatar.replace(/^\//, '/')}" alt="${iv.employer_company_name}">`
      : initials(iv.employer_company_name);

    return `
    <div class="interview-card ${type}">

      <div class="interview-date-block">
        <div class="iday">${day}</div>
        <div class="imon">${mon}</div>
      </div>

      <div class="interview-info">
        <div class="ijob">${iv.job_title}</div>
        <div class="icompany">
          <i class="fas fa-building" style="font-size:.7rem;margin-right:.3rem"></i>${iv.employer_company_name}
        </div>
        <div class="interview-meta">
          <span class="i-pill ${type}">
            <i class="fas ${typeIcon}" style="font-size:.65rem"></i>${typeLabel}
          </span>
          <span class="i-pill">
            <i class="fas fa-clock" style="font-size:.65rem"></i>${time}
          </span>
          <span class="i-pill">
            <i class="fas fa-stopwatch" style="font-size:.65rem"></i>${iv.duration_minutes}min
          </span>
          ${cd ? `<span class="countdown-chip">⏳ ${cd}</span>` : ''}
        </div>
      </div>

      <div class="interview-action">
        ${hasLink && type === 'video'
          ? `<a href="${iv.meeting_link}" target="_blank" rel="noopener" class="btn-join">
               <i class="fas fa-video" style="font-size:.75rem"></i> Join
             </a>`
          : type === 'phone'
          ? `<a href="tel:${iv.employer_contact_phone}" class="btn-join phone-type">
               <i class="fas fa-phone" style="font-size:.75rem"></i> Call
             </a>`
          : `<span class="i-pill" style="padding:.45rem .8rem">${iv.status}</span>`
        }
      </div>

    </div>`;
  }).join('');
}


function renderApplications(data) {
  const listEl = document.getElementById('app-list');
  const apps   = (data.data || []).slice(0, 5);

  document.getElementById('stat-applications').textContent = data.total ?? apps.length;

  if (!apps.length) {
    listEl.innerHTML = empty('fa-paper-plane', 'No applications yet. Start applying!');
    return;
  }

  listEl.innerHTML = apps.map(a => {
    const applied = relativeTime(a.applied_at);
    const company = a.company_name || 'Company';
    const logoLetter = company[0].toUpperCase();

    return `
    <div class="app-row">
      <div class="app-logo">${logoLetter}</div>
      <div class="app-info">
        <div class="atitle">${a.title}</div>
        <div class="acompany">${company}</div>
        <div class="ameta">
          <i class="fas fa-clock" style="font-size:.65rem;margin-right:.3rem"></i>Applied ${applied}
          &nbsp;·&nbsp;${a.job_type?.replace('_',' ') || ''}
        </div>
      </div>
      <span class="status-badge ${a.status}">${a.status}</span>
    </div>`;
  }).join('');
}


function renderSavedJobs(data) {
  const listEl = document.getElementById('saved-list');
  const jobs   = (data.data || []).slice(0, 4);

  document.getElementById('stat-saved').textContent = data.total ?? jobs.length;

  if (!jobs.length) {
    listEl.innerHTML = empty('fa-bookmark', 'No saved jobs yet.');
    return;
  }

  listEl.innerHTML = jobs.map(j => {
    const salary  = fmtSalary(j.salary_min, j.salary_max, j.currency);
    const typeTag = j.job_type?.replace('_',' ') || '';
    const remote  = j.is_remote ? '<span class="stag">Remote</span>' : '';
    const exp     = j.experience_level ? `<span class="stag">${j.experience_level}</span>` : '';

    return `
    <a href="/jobseeker/jobs" class="saved-job">
      <div class="app-logo">${j.company_name?.[0]?.toUpperCase() || '?'}</div>
      <div class="sjob-info">
        <div class="sjob-title">${j.title}</div>
        <div class="sjob-meta">
          <span>${j.company_name}</span>
          ${typeTag ? `<span class="stag">${typeTag}</span>` : ''}
          ${remote}${exp}
        </div>
      </div>
      ${salary ? `<div class="salary-range">${salary}</div>` : ''}
    </a>`;
  }).join('');
}


function renderNotifications(data) {
  const el     = document.getElementById('notif-container');
  const notifs = data.data || [];
  const unread = data.unread_count || 0;

  document.getElementById('stat-notifications').textContent = unread;

  if (!notifs.length) {
    el.innerHTML = `<div class="notif-empty">
      <i class="fas fa-bell-slash"></i>
      You're all caught up!
    </div>`;
    return;
  }

  el.innerHTML = notifs.slice(0,5).map(n => `
    <div style="padding:.7rem 0;border-bottom:1px solid var(--border-color);display:flex;gap:.75rem;align-items:flex-start">
      <i class="fas fa-circle" style="font-size:.45rem;color:var(--primary-color);margin-top:.45rem;flex-shrink:0${n.read_at ? ';opacity:.3' : ''}"></i>
      <div>
        <div style="font-size:.85rem;color:var(--text-primary);font-weight:${n.read_at ? '400' : '600'}">${n.message || n.title || 'Notification'}</div>
        <div style="font-size:.72rem;color:var(--text-muted);margin-top:.2rem">${relativeTime(n.created_at)}</div>
      </div>
    </div>`).join('');
}


// ═══════════════════════════════════════════════════════════════════════════
// BOOT – parallel fetch + render
// ═══════════════════════════════════════════════════════════════════════════

(async function boot() {
  try {
    const [profile, interviewData, savedJobs, notifications, applications] = await Promise.allSettled([
      apiFetch('/api/jobseeker/profile'),
      apiFetch('/api/jobseeker/interviews'),
      apiFetch('/api/user/saved-jobs'),
      apiFetch('/api/notifications'),
      apiFetch('/api/applications/user'),
    ]);

    if (profile.status === 'fulfilled')      renderProfile(profile.value);
    if (interviewData.status === 'fulfilled') renderInterviews(interviewData.value);
    if (applications.status === 'fulfilled') renderApplications(applications.value);
    if (savedJobs.status === 'fulfilled')    renderSavedJobs(savedJobs.value);
    if (notifications.status === 'fulfilled') renderNotifications(notifications.value);

    // Log any rejected promises for debugging
    [profile, interviewData, savedJobs, notifications, applications].forEach((r, i) => {
      if (r.status === 'rejected') {
        console.warn(`[Dashboard] Fetch #${i} failed:`, r.reason?.message);
      }
    });

  } catch (err) {
    console.error('[Dashboard] Unexpected error:', err);
  }
})();
</script>
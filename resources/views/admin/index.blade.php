<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel — AI Gateway</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg: #0f1117;
  --surface: #1a1d27;
  --surface2: #222536;
  --border: #2d3048;
  --text: #e2e8f0;
  --muted: #8892a4;
  --accent: #6366f1;
  --accent-hover: #818cf8;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #f87171;
  --sidebar-w: 220px;
}

body { background: var(--bg); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; height: 100vh; overflow: hidden; }

/* Auth overlay */
#authOverlay {
  position: fixed; inset: 0; background: var(--bg);
  display: flex; align-items: center; justify-content: center;
  z-index: 100; padding: 20px;
}
.auth-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 16px; padding: 40px; width: 100%; max-width: 400px;
}
.auth-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; }
.auth-logo-icon {
  width: 36px; height: 36px;
  background: linear-gradient(135deg,#6366f1,#8b5cf6);
  border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;
}
.auth-logo-text { font-size: 18px; font-weight: 700; }
.auth-card h2 { font-size: 20px; margin-bottom: 6px; }
.auth-card p { color: var(--muted); font-size: 13px; margin-bottom: 24px; }
.auth-input {
  width: 100%; background: var(--bg); border: 1px solid var(--border);
  border-radius: 8px; color: var(--text); font-size: 14px;
  padding: 10px 14px; outline: none; transition: border-color .2s; margin-bottom: 12px; font-family: monospace;
}
.auth-input:focus { border-color: var(--accent); }
.auth-error { color: var(--danger); font-size: 12px; margin-bottom: 10px; display: none; }
.btn-primary {
  width: 100%; background: var(--accent); color: white; border: none;
  border-radius: 8px; padding: 11px; font-size: 14px; font-weight: 600;
  cursor: pointer; transition: background .2s;
}
.btn-primary:hover { background: var(--accent-hover); }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; }

/* App layout */
#app { display: none; height: 100vh; }

.sidebar {
  position: fixed; left: 0; top: 0; bottom: 0; width: var(--sidebar-w);
  background: var(--surface); border-right: 1px solid var(--border);
  display: flex; flex-direction: column; overflow: hidden;
}
.sidebar-header {
  padding: 20px 16px 16px;
  border-bottom: 1px solid var(--border);
}
.sidebar-logo { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
.sidebar-logo-icon {
  width: 28px; height: 28px;
  background: linear-gradient(135deg,#6366f1,#8b5cf6);
  border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 14px;
}
.sidebar-title { font-weight: 700; font-size: 14px; }
.sidebar-sub { font-size: 11px; color: var(--muted); margin-top: 2px; }

.nav { padding: 12px 8px; flex: 1; }
.nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 10px; border-radius: 8px; cursor: pointer;
  font-size: 13px; font-weight: 500; color: var(--muted);
  transition: background .15s, color .15s; margin-bottom: 2px;
  position: relative;
}
.nav-item:hover { background: var(--surface2); color: var(--text); }
.nav-item.active { background: rgba(99,102,241,.15); color: var(--accent); }
.nav-item .nav-icon { font-size: 16px; width: 20px; text-align: center; }
.nav-badge {
  position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
  background: var(--danger); color: white; border-radius: 10px;
  font-size: 10px; font-weight: 700; padding: 1px 6px; min-width: 18px; text-align: center;
  display: none;
}
.nav-badge.show { display: block; }

.sidebar-footer {
  padding: 12px 16px;
  border-top: 1px solid var(--border);
}
.sidebar-user { font-size: 11px; color: var(--muted); }
.sidebar-key { font-size: 10px; color: var(--muted); font-family: monospace; margin-top: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.logout-btn {
  margin-top: 8px; width: 100%; background: none; border: 1px solid var(--border);
  color: var(--muted); border-radius: 6px; padding: 6px; font-size: 12px;
  cursor: pointer; transition: all .2s;
}
.logout-btn:hover { border-color: var(--danger); color: var(--danger); }

.main {
  margin-left: var(--sidebar-w);
  height: 100vh; overflow-y: auto;
  padding: 28px 32px;
}

.page { display: none; }
.page.active { display: block; }

.page-header { margin-bottom: 24px; }
.page-header h1 { font-size: 22px; font-weight: 700; }
.page-header p { color: var(--muted); font-size: 13px; margin-top: 4px; }

/* Stat cards */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px; }
.stat-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 12px; padding: 20px;
}
.stat-label { font-size: 12px; color: var(--muted); font-weight: 500; text-transform: uppercase; letter-spacing: .05em; }
.stat-value { font-size: 28px; font-weight: 700; margin-top: 6px; }
.stat-sub { font-size: 11px; color: var(--muted); margin-top: 4px; }

/* Table */
.table-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 12px; overflow: hidden; margin-bottom: 24px;
}
.table-header {
  padding: 16px 20px; border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
}
.table-header h3 { font-size: 14px; font-weight: 600; }
table { width: 100%; border-collapse: collapse; }
th {
  text-align: left; padding: 10px 20px; font-size: 11px; font-weight: 600;
  color: var(--muted); text-transform: uppercase; letter-spacing: .05em;
  border-bottom: 1px solid var(--border); background: var(--surface2);
}
td { padding: 12px 20px; font-size: 13px; border-bottom: 1px solid var(--border); vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: rgba(255,255,255,.02); }

.badge {
  display: inline-block; padding: 2px 8px; border-radius: 6px;
  font-size: 11px; font-weight: 600; text-transform: uppercase;
}
.badge-active { background: rgba(16,185,129,.15); color: var(--success); }
.badge-inactive { background: rgba(248,113,113,.1); color: var(--danger); }
.badge-pending { background: rgba(245,158,11,.1); color: var(--warning); }
.badge-approved { background: rgba(16,185,129,.15); color: var(--success); }
.badge-rejected { background: rgba(248,113,113,.1); color: var(--danger); }
.badge-admin { background: rgba(99,102,241,.15); color: var(--accent); }

.btn {
  padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 500;
  border: none; cursor: pointer; transition: opacity .2s;
}
.btn:hover { opacity: .85; }
.btn:disabled { opacity: .5; cursor: not-allowed; }
.btn-sm { padding: 4px 10px; font-size: 11px; }
.btn-success { background: rgba(16,185,129,.2); color: var(--success); border: 1px solid rgba(16,185,129,.3); }
.btn-danger { background: rgba(248,113,113,.1); color: var(--danger); border: 1px solid rgba(248,113,113,.3); }
.btn-accent { background: var(--accent); color: white; }
.btn-ghost { background: none; color: var(--muted); border: 1px solid var(--border); }

.empty-state { text-align: center; padding: 48px; color: var(--muted); font-size: 14px; }

/* Create user form */
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.field { margin-bottom: 14px; }
.field label { display: block; font-size: 12px; font-weight: 500; color: var(--muted); margin-bottom: 5px; }
.field input, .field select, .field textarea {
  width: 100%; background: var(--bg); border: 1px solid var(--border);
  border-radius: 6px; color: var(--text); font-size: 13px;
  padding: 8px 12px; outline: none; transition: border-color .2s; font-family: inherit;
}
.field input:focus, .field select:focus { border-color: var(--accent); }

/* Modal */
.modal-backdrop {
  position: fixed; inset: 0; background: rgba(0,0,0,.6);
  display: flex; align-items: center; justify-content: center;
  z-index: 200; padding: 20px; display: none;
}
.modal-backdrop.open { display: flex; }
.modal {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 14px; padding: 28px; width: 100%; max-width: 480px;
}
.modal h3 { font-size: 17px; font-weight: 700; margin-bottom: 6px; }
.modal p { color: var(--muted); font-size: 13px; margin-bottom: 20px; }
.modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }

/* API key reveal */
.api-key-reveal {
  background: var(--bg); border: 1px solid var(--border);
  border-radius: 8px; padding: 12px 14px; font-family: monospace;
  font-size: 12px; word-break: break-all; color: var(--success);
  margin: 12px 0; line-height: 1.6;
}

/* Tabs (for registrations filter) */
.tabs { display: flex; gap: 4px; margin-bottom: 20px; }
.tab-btn {
  padding: 7px 16px; border-radius: 8px; font-size: 13px; font-weight: 500;
  border: 1px solid var(--border); background: none; color: var(--muted);
  cursor: pointer; transition: all .15s;
}
.tab-btn:hover { color: var(--text); border-color: var(--accent); }
.tab-btn.active { background: rgba(99,102,241,.15); color: var(--accent); border-color: var(--accent); }

/* Loading */
.loading { text-align: center; padding: 48px; color: var(--muted); font-size: 13px; }

/* Chart placeholder */
.chart-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 12px; padding: 20px; margin-bottom: 24px;
}
.chart-card h3 { font-size: 14px; font-weight: 600; margin-bottom: 16px; }
.bar-chart { display: flex; align-items: flex-end; gap: 6px; height: 100px; }
.bar-wrap { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; }
.bar { width: 100%; border-radius: 4px 4px 0 0; background: var(--accent); opacity: .7; min-height: 2px; transition: opacity .2s; }
.bar:hover { opacity: 1; }
.bar-label { font-size: 9px; color: var(--muted); }

/* Responsive */
@media (max-width: 700px) {
  .sidebar { width: 60px; }
  .nav-item span, .sidebar-title, .sidebar-sub, .sidebar-footer .sidebar-user, .sidebar-key { display: none; }
  .main { margin-left: 60px; padding: 20px 16px; }
  .stats-grid { grid-template-columns: 1fr 1fr; }
  .form-row { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- Auth overlay -->
<div id="authOverlay">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="auth-logo-icon">⚡</div>
      <span class="auth-logo-text">AI Gateway</span>
    </div>
    <h2>Admin Panel</h2>
    <p>Enter your admin API key to access the dashboard.</p>
    <input type="password" class="auth-input" id="authKey" placeholder="ak_…" autocomplete="off">
    <div class="auth-error" id="authError"></div>
    <button class="btn-primary" id="authBtn">Sign In</button>
  </div>
</div>

<!-- Main app -->
<div id="app">
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <div class="sidebar-logo-icon">⚡</div>
        <span class="sidebar-title">AI Gateway</span>
      </div>
      <div class="sidebar-sub">Admin Panel</div>
    </div>
    <nav class="nav">
      <div class="nav-item active" data-page="overview">
        <span class="nav-icon">📊</span>
        <span>Overview</span>
      </div>
      <div class="nav-item" data-page="users">
        <span class="nav-icon">👥</span>
        <span>Users</span>
      </div>
      <div class="nav-item" data-page="registrations">
        <span class="nav-icon">📋</span>
        <span>Registrations</span>
        <span class="nav-badge" id="pendingBadge">0</span>
      </div>
      <div class="nav-item" data-page="models">
        <span class="nav-icon">🤖</span>
        <span>Models</span>
      </div>
    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-user">Logged in as Admin</div>
      <div class="sidebar-key" id="sidebarKeyDisplay"></div>
      <button class="logout-btn" onclick="logout()">Sign Out</button>
    </div>
  </div>

  <!-- Main content -->
  <main class="main">

    <!-- Overview -->
    <div class="page active" id="page-overview">
      <div class="page-header">
        <h1>Overview</h1>
        <p>Platform usage at a glance</p>
      </div>
      <div class="stats-grid" id="statsGrid">
        <div class="loading">Loading…</div>
      </div>
      <div class="chart-card">
        <h3>Messages (last 7 days)</h3>
        <div class="bar-chart" id="barChart"><div class="loading" style="padding:20px">Loading…</div></div>
      </div>
      <div class="table-card">
        <div class="table-header"><h3>Top Users</h3></div>
        <div id="topUsersTable"><div class="loading">Loading…</div></div>
      </div>
    </div>

    <!-- Users -->
    <div class="page" id="page-users">
      <div class="page-header">
        <h1>Users</h1>
        <p>Manage API access and accounts</p>
      </div>

      <!-- Create user form -->
      <div class="table-card" style="margin-bottom:24px">
        <div class="table-header"><h3>Create New User</h3></div>
        <div style="padding:20px">
          <div class="form-row">
            <div class="field">
              <label>Full Name</label>
              <input type="text" id="newName" placeholder="Jane Smith">
            </div>
            <div class="field">
              <label>Email</label>
              <input type="email" id="newEmail" placeholder="jane@example.com">
            </div>
          </div>
          <div class="field">
            <label>
              <input type="checkbox" id="newIsAdmin" style="width:auto;margin-right:6px">
              Grant admin access
            </label>
          </div>
          <button class="btn btn-accent" id="createUserBtn" onclick="createUser()">Create User & Generate Key</button>
        </div>
      </div>

      <div class="table-card" id="usersCard">
        <div class="table-header">
          <h3>All Users</h3>
          <button class="btn btn-ghost btn-sm" onclick="loadUsers()">↻ Refresh</button>
        </div>
        <div id="usersTableWrap"><div class="loading">Loading…</div></div>
      </div>
    </div>

    <!-- Registrations -->
    <div class="page" id="page-registrations">
      <div class="page-header">
        <h1>Registration Requests</h1>
        <p>Review and approve access requests</p>
      </div>
      <div class="tabs">
        <button class="tab-btn active" data-status="pending" onclick="switchRegTab(this,'pending')">Pending</button>
        <button class="tab-btn" data-status="approved" onclick="switchRegTab(this,'approved')">Approved</button>
        <button class="tab-btn" data-status="rejected" onclick="switchRegTab(this,'rejected')">Rejected</button>
      </div>
      <div class="table-card" id="regCard">
        <div class="table-header">
          <h3 id="regTableTitle">Pending Requests</h3>
          <button class="btn btn-ghost btn-sm" onclick="loadRegistrations(currentRegStatus)">↻ Refresh</button>
        </div>
        <div id="regTableWrap"><div class="loading">Loading…</div></div>
      </div>
    </div>

    <!-- Models -->
    <div class="page" id="page-models">
      <div class="page-header">
        <h1>AI Models</h1>
        <p>Enable or disable available models</p>
      </div>
      <div class="table-card">
        <div class="table-header">
          <h3>Models</h3>
          <button class="btn btn-ghost btn-sm" onclick="loadModels()">↻ Refresh</button>
        </div>
        <div id="modelsTableWrap"><div class="loading">Loading…</div></div>
      </div>
    </div>

  </main>
</div>

<!-- Modal: show API key after create/approve -->
<div class="modal-backdrop" id="keyModal">
  <div class="modal">
    <h3 id="keyModalTitle">User Created</h3>
    <p id="keyModalDesc">Share this API key with the user. It will not be shown again.</p>
    <div class="api-key-reveal" id="keyModalKey"></div>
    <div id="keyModalUser" style="font-size:13px;color:var(--muted);margin-bottom:4px"></div>
    <div class="modal-actions">
      <button class="btn btn-ghost" onclick="copyKey()">Copy Key</button>
      <button class="btn btn-accent" onclick="closeKeyModal()">Done</button>
    </div>
  </div>
</div>

<script>
let apiKey = '';
let currentRegStatus = 'pending';

// ─── Auth ──────────────────────────────────────────────────────────────────

async function tryAuth(key) {
  const res = await fetch('/api/v1/auth/me', {
    headers: { 'X-API-KEY': key, 'Accept': 'application/json' }
  });
  if (!res.ok) return null;
  const d = await res.json();
  return d.data;
}

document.getElementById('authBtn').addEventListener('click', doLogin);
document.getElementById('authKey').addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });

async function doLogin() {
  const key = document.getElementById('authKey').value.trim();
  if (!key) return;
  const btn = document.getElementById('authBtn');
  btn.disabled = true; btn.textContent = 'Checking…';
  document.getElementById('authError').style.display = 'none';

  const user = await tryAuth(key).catch(() => null);

  if (!user || !user.is_admin) {
    document.getElementById('authError').textContent = user ? 'This key does not have admin access.' : 'Invalid API key.';
    document.getElementById('authError').style.display = 'block';
    btn.disabled = false; btn.textContent = 'Sign In';
    return;
  }

  apiKey = key;
  sessionStorage.setItem('admin_key', key);
  showApp(key);
}

function showApp(key) {
  document.getElementById('authOverlay').style.display = 'none';
  document.getElementById('app').style.display = 'block';
  document.getElementById('sidebarKeyDisplay').textContent = key.slice(0, 12) + '…';
  loadOverview();
  loadPendingCount();
}

function logout() {
  apiKey = '';
  sessionStorage.removeItem('admin_key');
  document.getElementById('authOverlay').style.display = 'flex';
  document.getElementById('app').style.display = 'none';
  document.getElementById('authKey').value = '';
}

// Auto-login from session
(async () => {
  const saved = sessionStorage.getItem('admin_key');
  if (saved) {
    const user = await tryAuth(saved).catch(() => null);
    if (user && user.is_admin) { apiKey = saved; showApp(saved); }
  }
})();

// ─── Navigation ────────────────────────────────────────────────────────────

document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', () => {
    const page = item.dataset.page;
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    item.classList.add('active');
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.getElementById('page-' + page).classList.add('active');
    if (page === 'overview') loadOverview();
    else if (page === 'users') loadUsers();
    else if (page === 'registrations') { loadRegistrations('pending'); loadPendingCount(); }
    else if (page === 'models') loadModels();
  });
});

// ─── API helpers ────────────────────────────────────────────────────────────

async function api(method, path, body) {
  const opts = { method, headers: { 'X-API-KEY': apiKey, 'Accept': 'application/json' } };
  if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
  const res = await fetch('/api/v1' + path, opts);
  return { ok: res.ok, status: res.status, data: await res.json() };
}

// ─── Overview ──────────────────────────────────────────────────────────────

async function loadOverview() {
  const [metrics, usage] = await Promise.all([
    api('GET', '/admin/metrics'),
    api('GET', '/admin/usage?days=7'),
  ]);

  if (metrics.ok) renderStats(metrics.data.data);
  if (usage.ok) {
    renderBarChart(usage.data.data.by_day);
    renderTopUsers(usage.data.data.top_users);
  }
}

function renderStats(d) {
  const grid = document.getElementById('statsGrid');
  const cards = [
    { label: 'Total Users',    value: d.total_users,    sub: d.active_users + ' active' },
    { label: 'Total Messages', value: d.total_messages, sub: 'all time' },
    { label: 'Conversations',  value: d.total_conversations, sub: 'all time' },
    { label: 'Messages Today', value: d.messages_today,  sub: 'last 24h' },
  ];
  grid.innerHTML = cards.map(c => `
    <div class="stat-card">
      <div class="stat-label">${c.label}</div>
      <div class="stat-value">${(c.value ?? 0).toLocaleString()}</div>
      <div class="stat-sub">${c.sub}</div>
    </div>
  `).join('');
}

function renderBarChart(days) {
  const chart = document.getElementById('barChart');
  if (!days || !days.length) { chart.innerHTML = '<div class="empty-state">No data</div>'; return; }
  const max = Math.max(...days.map(d => d.count), 1);
  chart.innerHTML = days.map(d => {
    const pct = Math.max((d.count / max) * 100, 2);
    const label = new Date(d.date).toLocaleDateString('en', { month:'short', day:'numeric' });
    return `<div class="bar-wrap"><div class="bar" style="height:${pct}%" title="${d.count} messages"></div><div class="bar-label">${label}</div></div>`;
  }).join('');
}

function renderTopUsers(users) {
  const el = document.getElementById('topUsersTable');
  if (!users || !users.length) { el.innerHTML = '<div class="empty-state">No data yet</div>'; return; }
  el.innerHTML = `<table>
    <thead><tr><th>User</th><th>Messages</th><th>Tokens</th></tr></thead>
    <tbody>${users.map(u => `
      <tr>
        <td><div style="font-weight:500">${esc(u.name || 'Unknown')}</div><div style="color:var(--muted);font-size:11px">${esc(u.email || '')}</div></td>
        <td>${(u.total_requests ?? 0).toLocaleString()}</td>
        <td>${(u.total_tokens ?? 0).toLocaleString()}</td>
      </tr>`).join('')}
    </tbody>
  </table>`;
}

// ─── Users ─────────────────────────────────────────────────────────────────

async function loadUsers() {
  document.getElementById('usersTableWrap').innerHTML = '<div class="loading">Loading…</div>';
  const r = await api('GET', '/admin/users');
  if (!r.ok) { document.getElementById('usersTableWrap').innerHTML = '<div class="empty-state">Failed to load</div>'; return; }
  const users = r.data.data;
  if (!users.length) { document.getElementById('usersTableWrap').innerHTML = '<div class="empty-state">No users yet</div>'; return; }
  document.getElementById('usersTableWrap').innerHTML = `<table>
    <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Role</th><th>Last Used</th><th>Action</th></tr></thead>
    <tbody>${users.map(u => `
      <tr id="user-row-${u.id}">
        <td style="font-weight:500">${esc(u.name)}</td>
        <td style="color:var(--muted)">${esc(u.email)}</td>
        <td><span class="badge badge-${u.is_active ? 'active':'inactive'}">${u.is_active ? 'Active':'Disabled'}</span></td>
        <td>${u.is_admin ? '<span class="badge badge-admin">Admin</span>' : '<span style="color:var(--muted);font-size:11px">User</span>'}</td>
        <td style="color:var(--muted);font-size:12px">${u.last_used_at ? new Date(u.last_used_at).toLocaleDateString() : 'Never'}</td>
        <td><button class="btn btn-sm ${u.is_active ? 'btn-danger':'btn-success'}" onclick="toggleUser(${u.id},${u.is_active})">${u.is_active ? 'Disable':'Enable'}</button></td>
      </tr>`).join('')}
    </tbody>
  </table>`;
}

async function createUser() {
  const name = document.getElementById('newName').value.trim();
  const email = document.getElementById('newEmail').value.trim();
  const isAdmin = document.getElementById('newIsAdmin').checked;
  if (!name || !email) { alert('Name and email are required.'); return; }

  const btn = document.getElementById('createUserBtn');
  btn.disabled = true; btn.textContent = 'Creating…';

  const r = await api('POST', '/admin/users', { name, email, is_admin: isAdmin });
  btn.disabled = false; btn.textContent = 'Create User & Generate Key';

  if (r.ok) {
    document.getElementById('newName').value = '';
    document.getElementById('newEmail').value = '';
    document.getElementById('newIsAdmin').checked = false;
    showKeyModal('User Created', `Share this API key with ${esc(r.data.data.name)}. It will not be shown again.`, r.data.data.api_key, r.data.data.name, r.data.data.email);
    loadUsers();
  } else {
    const msg = r.data.errors?.email?.[0] || r.data.errors?.name?.[0] || r.data.message || 'Failed to create user.';
    alert(msg);
  }
}

async function toggleUser(id, currentlyActive) {
  const r = await api('PUT', `/admin/users/${id}/toggle`);
  if (r.ok) loadUsers();
}

// ─── Registrations ─────────────────────────────────────────────────────────

async function loadPendingCount() {
  const r = await api('GET', '/admin/registration-requests?status=pending');
  if (r.ok) {
    const count = r.data.meta?.pending_count ?? 0;
    const badge = document.getElementById('pendingBadge');
    badge.textContent = count;
    badge.classList.toggle('show', count > 0);
  }
}

async function loadRegistrations(status) {
  currentRegStatus = status;
  document.getElementById('regTableWrap').innerHTML = '<div class="loading">Loading…</div>';
  const titles = { pending: 'Pending Requests', approved: 'Approved Requests', rejected: 'Rejected Requests' };
  document.getElementById('regTableTitle').textContent = titles[status];

  const r = await api('GET', `/admin/registration-requests?status=${status}`);
  if (!r.ok) { document.getElementById('regTableWrap').innerHTML = '<div class="empty-state">Failed to load</div>'; return; }

  const reqs = r.data.data;
  if (!reqs.length) { document.getElementById('regTableWrap').innerHTML = `<div class="empty-state">No ${status} requests</div>`; return; }

  const isPending = status === 'pending';
  document.getElementById('regTableWrap').innerHTML = `<table>
    <thead><tr><th>Name</th><th>Email</th><th>Message</th><th>Submitted</th><th>Status</th>${isPending ? '<th>Actions</th>' : ''}</tr></thead>
    <tbody>${reqs.map(req => `
      <tr id="req-row-${req.id}">
        <td style="font-weight:500">${esc(req.name)}</td>
        <td style="color:var(--muted)">${esc(req.email)}</td>
        <td style="color:var(--muted);font-size:12px;max-width:200px">${req.message ? esc(req.message.slice(0,80)) + (req.message.length>80?'…':'') : '<em>—</em>'}</td>
        <td style="color:var(--muted);font-size:12px">${new Date(req.created_at).toLocaleDateString()}</td>
        <td><span class="badge badge-${req.status}">${req.status}</span></td>
        ${isPending ? `<td style="display:flex;gap:6px">
          <button class="btn btn-sm btn-success" onclick="approveReq(${req.id},'${esc(req.name)}')">Approve</button>
          <button class="btn btn-sm btn-danger" onclick="rejectReq(${req.id})">Reject</button>
        </td>` : ''}
      </tr>`).join('')}
    </tbody>
  </table>`;
}

function switchRegTab(el, status) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  loadRegistrations(status);
}

async function approveReq(id, name) {
  const r = await api('PUT', `/admin/registration-requests/${id}/approve`);
  if (r.ok) {
    showKeyModal('Request Approved', `Share this API key with ${esc(name)}. It will not be shown again.`, r.data.data.api_key, r.data.data.name, r.data.data.email);
    loadRegistrations(currentRegStatus);
    loadPendingCount();
  } else {
    alert(r.data.error?.message || 'Failed to approve.');
  }
}

async function rejectReq(id) {
  if (!confirm('Reject this request?')) return;
  const r = await api('PUT', `/admin/registration-requests/${id}/reject`);
  if (r.ok) { loadRegistrations(currentRegStatus); loadPendingCount(); }
  else alert(r.data.error?.message || 'Failed to reject.');
}

// ─── Models ────────────────────────────────────────────────────────────────

async function loadModels() {
  document.getElementById('modelsTableWrap').innerHTML = '<div class="loading">Loading…</div>';
  const r = await api('GET', '/admin/models');
  if (!r.ok) { document.getElementById('modelsTableWrap').innerHTML = '<div class="empty-state">Failed to load</div>'; return; }

  const models = r.data.data;
  if (!models.length) { document.getElementById('modelsTableWrap').innerHTML = '<div class="empty-state">No models configured</div>'; return; }

  document.getElementById('modelsTableWrap').innerHTML = `<table>
    <thead><tr><th>Display Name</th><th>Ollama Name</th><th>Default</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>${models.map(m => `
      <tr>
        <td style="font-weight:500">${esc(m.display_name || m.ollama_name)}</td>
        <td style="font-family:monospace;font-size:12px;color:var(--muted)">${esc(m.ollama_name)}</td>
        <td>${m.is_default ? '<span class="badge badge-active">Default</span>' : '—'}</td>
        <td><span class="badge badge-${m.enabled ? 'active':'inactive'}">${m.enabled ? 'Enabled':'Disabled'}</span></td>
        <td><button class="btn btn-sm ${m.enabled ? 'btn-danger':'btn-success'}" onclick="toggleModel(${m.id},${m.enabled})">${m.enabled ? 'Disable':'Enable'}</button></td>
      </tr>`).join('')}
    </tbody>
  </table>`;
}

async function toggleModel(id, currentlyEnabled) {
  const r = await api('POST', `/admin/models/${id}/toggle`);
  if (r.ok) loadModels();
}

// ─── Key modal ─────────────────────────────────────────────────────────────

function showKeyModal(title, desc, key, name, email) {
  document.getElementById('keyModalTitle').textContent = title;
  document.getElementById('keyModalDesc').textContent = desc;
  document.getElementById('keyModalKey').textContent = key;
  document.getElementById('keyModalUser').textContent = name + ' · ' + email;
  document.getElementById('keyModal').classList.add('open');
}

function closeKeyModal() {
  document.getElementById('keyModal').classList.remove('open');
}

async function copyKey() {
  const key = document.getElementById('keyModalKey').textContent;
  await navigator.clipboard.writeText(key).catch(() => {});
  const btn = event.target;
  btn.textContent = 'Copied!';
  setTimeout(() => btn.textContent = 'Copy Key', 1500);
}

// ─── Utils ─────────────────────────────────────────────────────────────────

function esc(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>

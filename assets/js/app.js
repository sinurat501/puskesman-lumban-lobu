// =============================================
// SIAP LOKET — Sistem Antrian Puskesmas
// app.js — Utility functions & Core Logic
// =============================================

// ---- TOAST NOTIFICATIONS ----
function showToast(msg, type = 'info', duration = 3500) {
  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
  const container = document.getElementById('toastContainer');
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<span class="toast-icon">${icons[type] || 'ℹ️'}</span><span class="toast-msg">${msg}</span>`;
  toast.addEventListener('click', () => removeToast(toast));
  container.appendChild(toast);
  setTimeout(() => removeToast(toast), duration);
}

function removeToast(toast) {
  if (!toast.parentNode) return;
  toast.classList.add('hiding');
  setTimeout(() => toast.remove(), 300);
}

// ---- CONFIRM DIALOG ----
let _confirmCallback = null;
function showConfirm(title, msg, callback, icon = '⚠️', btnText = 'Ya, Lanjutkan', btnClass = 'btn-danger') {
  document.getElementById('confirmIcon').textContent = icon;
  document.getElementById('confirmTitle').textContent = title;
  document.getElementById('confirmMsg').textContent = msg;
  const btn = document.getElementById('confirmBtn');
  btn.textContent = btnText;
  btn.className = `btn ${btnClass}`;
  _confirmCallback = callback;
  document.getElementById('confirmOverlay').classList.add('active');
}
function closeConfirm() { document.getElementById('confirmOverlay').classList.remove('active'); _confirmCallback = null; }
function doConfirm() { if (_confirmCallback) _confirmCallback(); closeConfirm(); }
document.getElementById('confirmOverlay')?.addEventListener('click', function(e) {
  if (e.target === this) closeConfirm();
});

// ---- TOPBAR DATE/TIME ----
function updateTopbarDate() {
  const el = document.getElementById('topbarDate');
  if (!el) return;
  const now = new Date();
  const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
  const d = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
  const t = now.toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'});
  el.textContent = `${d} — ${t}`;
}
updateTopbarDate();
setInterval(updateTopbarDate, 1000);

// ---- SIDEBAR TOGGLE ----
function toggleSidebar() {
  document.getElementById('sidebar')?.classList.toggle('open');
}

// ---- LOGOUT ----
async function handleLogout() {
  showConfirm('Keluar', 'Apakah kamu yakin ingin keluar dari sistem?', async () => {
    const fd = new FormData();
    fd.append('action', 'logout');
    await fetch('api/handler.php', { method: 'POST', body: fd });
    window.location.href = 'login.php';
  }, '🚪', 'Ya, Keluar', 'btn-danger');
}

// ---- API HELPER ----
async function apiPost(action, data = {}) {
  const fd = new FormData();
  fd.append('action', action);
  for (const [k, v] of Object.entries(data)) fd.append(k, v);
  const res = await fetch('api/handler.php', { method: 'POST', body: fd });
  return res.json();
}

async function apiGet(action, params = {}) {
  const qs = new URLSearchParams({ action, ...params }).toString();
  const res = await fetch(`api/handler.php?${qs}`);
  return res.json();
}

// ---- FORMAT UTILS ----
function formatDate(dateStr) {
  if (!dateStr) return '-';
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' });
}

function formatTime(tsStr) {
  if (!tsStr) return '-';
  const d = new Date(tsStr);
  return d.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });
}

function capitalize(str) {
  return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}

// ---- STATUS BADGE ----
function statusBadge(status) {
  const labels = {
    menunggu: '🟡 Menunggu',
    dipanggil: '🔵 Dipanggil',
    selesai: '🟢 Selesai',
    lewat: '🔴 Lewat'
  };
  return `<span class="badge badge-${status}">`
    + `<span class="status-dot dot-${status}"></span> ${capitalize(status)}</span>`;
}

// ---- SIDEBAR BADGE (menunggu count) ----
async function updateSidebarBadge() {
  try {
    const data = await apiGet('get_statistik', { tanggal: new Date().toISOString().split('T')[0] });
    if (data.success) {
      const badge = document.getElementById('sidebarBadge');
      if (badge) {
        const count = parseInt(data.stats.menunggu) || 0;
        if (count > 0) { badge.style.display = ''; badge.textContent = count; }
        else { badge.style.display = 'none'; }
      }
    }
  } catch(e) {}
}
updateSidebarBadge();
setInterval(updateSidebarBadge, 15000);

// ---- MODAL HELPERS ----
function openModal(id) { document.getElementById(id)?.classList.add('active'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('active'); }
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('active'); });
});
document.querySelectorAll('.modal-close').forEach(btn => {
  btn.addEventListener('click', () => btn.closest('.modal-overlay')?.classList.remove('active'));
});

// ---- KEYBOARD SHORTCUT ----
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    closeConfirm();
  }
});

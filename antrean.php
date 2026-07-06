<?php
require_once 'config/auth_check.php';
require_once 'config/database.php';

$page_title = 'Kelola Antrean';
$page_desc = 'Manajemen status antrian pasien';

$conn = getConnection();
$poli_result = $conn->query("SELECT * FROM poli ORDER BY nama_poli");
$poli_list = $poli_result->fetch_all(MYSQLI_ASSOC);
$conn->close();

include 'includes/header.php';
?>

<!-- FILTER BAR -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-body" style="padding:16px 22px;">
    <div class="filter-bar">
      <div style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#64748b;">
        ðŸ” Filter:
      </div>
      <input type="date" id="filterTanggal" class="form-control"
             value="<?= date('Y-m-d') ?>" style="max-width:160px;">
      <select id="filterPoli" class="form-control" style="max-width:200px;">
        <option value="">Semua Poli</option>
        <?php foreach ($poli_list as $p): ?>
        <option value="<?= $p['id_poli'] ?>"><?= htmlspecialchars($p['nama_poli']) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="filterStatus" class="form-control" style="max-width:160px;">
        <option value="">Semua Status</option>
        <option value="menunggu">â³ Menunggu</option>
        <option value="dipanggil">ðŸ”” Dipanggil</option>
        <option value="selesai">âœ… Selesai</option>
        <option value="lewat">âŒ Lewat</option>
      </select>
      <button class="btn btn-primary btn-sm" onclick="loadAntrean()">ðŸ”„ Tampilkan</button>
      <div style="margin-left:auto;display:flex;gap:8px;">
        <a href="daftar.php" class="btn btn-success btn-sm">âž• Daftar Pasien Baru</a>
        <button class="btn btn-outline btn-sm" onclick="autoRefreshToggle()" id="autoRefreshBtn">
          â–¶ Auto-Refresh: OFF
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ANTREAN TABLE -->
<div class="card">
  <div class="card-header">
    <div class="card-title" id="tableTitle">
      ðŸ“‹ Daftar Antrean
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
      <span id="countBadge" style="font-size:12px;background:#f0f9ff;color:#0284c7;padding:4px 12px;border-radius:20px;font-weight:600;"></span>
      <button class="btn btn-outline btn-sm" onclick="loadAntrean()" title="Refresh">ðŸ”„</button>
    </div>
  </div>
  <div id="tableContainer">
    <div style="text-align:center;padding:60px;">
      <div class="loading-spinner"></div>
      <p style="color:#94a3b8;margin-top:12px;font-size:13px;">Memuat data antrean...</p>
    </div>
  </div>
</div>

<!-- TIKET MODAL -->
<div class="modal-overlay" id="tiketModal">
  <div class="modal" style="max-width:340px;">
    <div class="modal-header">
      <div class="modal-title">ðŸŽ« Tiket Antrean</div>
      <button class="modal-close">âœ•</button>
    </div>
    <div class="modal-body" id="tiketContent"></div>
    <div class="modal-footer">
      <button class="btn btn-outline modal-close">Tutup</button>
      <button class="btn btn-primary" onclick="window.print()">ðŸ–¨ï¸ Cetak Tiket</button>
    </div>
  </div>
</div>

<script>
let autoRefreshInterval = null;
let autoRefreshOn = false;
let currentData = [];

// Load on start with URL param support
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('poli')) document.getElementById('filterPoli').value = urlParams.get('poli');

loadAntrean();

async function loadAntrean() {
  const tanggal = document.getElementById('filterTanggal').value;
  const poli = document.getElementById('filterPoli').value;
  const status = document.getElementById('filterStatus').value;
  const container = document.getElementById('tableContainer');

  container.innerHTML = `<div style="text-align:center;padding:40px;"><div class="loading-spinner"></div></div>`;

  const data = await apiGet('get_antrean', { tanggal, id_poli: poli, status });
  currentData = data.data || [];

  const badge = document.getElementById('countBadge');
  badge.textContent = `${currentData.length} data`;

  if (!currentData.length) {
    container.innerHTML = `
      <div class="empty-state">
        <div class="icon">ðŸ“­</div>
        <h3>Tidak Ada Data</h3>
        <p>Tidak ada antrean untuk filter yang dipilih</p>
      </div>`;
    return;
  }

  container.innerHTML = `
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>No. Antrean</th>
            <th>Nama Pasien</th>
            <th>NIK</th>
            <th>Poli</th>
            <th>Status</th>
            <th>Waktu Daftar</th>
            <th style="text-align:right;">Aksi</th>
          </tr>
        </thead>
        <tbody id="antreanBody"></tbody>
      </table>
    </div>`;

  renderRows(currentData);
}

function renderRows(data) {
  const body = document.getElementById('antreanBody');
  if (!body) return;
  body.innerHTML = data.map((r, i) => `
    <tr id="row-${r.id_antrean}">
      <td style="color:#94a3b8;font-size:13px;">${i + 1}</td>
      <td>
        <strong style="font-size:20px;color:#0ea5e9;font-family:'Poppins',sans-serif;">${r.nomor_antrean}</strong>
      </td>
      <td>
        <div style="font-weight:600;">${escHtml(r.nama_pasien)}</div>
      </td>
      <td style="font-size:12.5px;color:#64748b;font-family:monospace;">${r.nik}</td>
      <td style="font-size:13px;">${escHtml(r.nama_poli)}</td>
      <td>
        <select class="form-control" style="max-width:150px;padding:6px 10px;font-size:12.5px;"
          onchange="updateStatus(${r.id_antrean}, this.value, this)" data-current="${r.status}">
          <option value="menunggu" ${r.status==='menunggu'?'selected':''}>â³ Menunggu</option>
          <option value="dipanggil" ${r.status==='dipanggil'?'selected':''}>ðŸ”” Dipanggil</option>
          <option value="selesai" ${r.status==='selesai'?'selected':''}>âœ… Selesai</option>
          <option value="lewat" ${r.status==='lewat'?'selected':''}>âŒ Lewat</option>
        </select>
      </td>
      <td style="font-size:12px;color:#94a3b8;">${formatTime(r.created_at)}</td>
      <td style="text-align:right;">
        <div style="display:flex;gap:6px;justify-content:flex-end;">
          <button class="btn btn-outline btn-sm" onclick="showTiket(${JSON.stringify(r).replace(/"/g,'&quot;')})"
            title="Lihat Tiket">ðŸŽ«</button>
          <button class="btn btn-danger btn-sm" onclick="hapusAntrean(${r.id_antrean}, '${r.nomor_antrean}')"
            title="Hapus">ðŸ—‘ï¸</button>
        </div>
      </td>
    </tr>
  `).join('');
}

async function updateStatus(id, newStatus, selectEl) {
  const prev = selectEl.dataset.current;
  selectEl.disabled = true;
  const result = await apiPost('update_status', { id_antrean: id, status: newStatus });
  selectEl.disabled = false;
  if (result.success) {
    selectEl.dataset.current = newStatus;
    showToast(`Status diperbarui â†’ ${newStatus}`, 'success');
    updateSidebarBadge();
  } else {
    selectEl.value = prev;
    showToast(result.message || 'Gagal memperbarui status', 'error');
  }
}

function hapusAntrean(id, nomor) {
  showConfirm(
    'Hapus Antrean', 
    `Hapus antrean ${nomor}? Tindakan ini tidak bisa dibatalkan.`,
    async () => {
      const result = await apiPost('hapus_antrean', { id_antrean: id });
      if (result.success) {
        showToast('Antrean berhasil dihapus', 'success');
        loadAntrean();
      } else {
        showToast(result.message || 'Gagal menghapus', 'error');
      }
    }, 'ðŸ—‘ï¸', 'Ya, Hapus', 'btn-danger'
  );
}

function showTiket(r) {
  const today = new Date().toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'});
  document.getElementById('tiketContent').innerHTML = `
    <div class="tiket-container" style="max-width:100%;margin:0;">
      <div class="tiket-header">
        <h2>ðŸ¥ POLI ${r.nama_poli.toUpperCase()}</h2>
        <p>Puskesmas Lumban Lobu</p>
      </div>
      <div class="tiket-body">
        <div class="tiket-label">Nomor Antrean Anda</div>
        <div class="tiket-number">${r.nomor_antrean}</div>
        <div class="tiket-poli">${r.nama_poli}</div>
        <hr class="tiket-divider">
        <div class="tiket-info" style="display:flex;flex-direction:column;gap:6px;">
          <div>Nama: <span>${r.nama_pasien}</span></div>
          <div>NIK: <span>${r.nik}</span></div>
          <div>Tanggal: <span>${today}</span></div>
          <div>Status: <span>${r.status.toUpperCase()}</span></div>
        </div>
      </div>
      <div class="tiket-footer">Harap hadir saat nomor dipanggil. Terima kasih.</div>
    </div>
  `;
  openModal('tiketModal');
}

function autoRefreshToggle() {
  const btn = document.getElementById('autoRefreshBtn');
  if (autoRefreshOn) {
    clearInterval(autoRefreshInterval);
    autoRefreshOn = false;
    btn.textContent = 'â–¶ Auto-Refresh: OFF';
    btn.className = 'btn btn-outline btn-sm';
    showToast('Auto-refresh dinonaktifkan', 'info');
  } else {
    autoRefreshInterval = setInterval(loadAntrean, 10000);
    autoRefreshOn = true;
    btn.textContent = 'â¸ Auto-Refresh: ON';
    btn.className = 'btn btn-success btn-sm';
    showToast('Auto-refresh aktif (setiap 10 detik)', 'success');
  }
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Filter change auto-load
['filterTanggal', 'filterPoli', 'filterStatus'].forEach(id => {
  document.getElementById(id)?.addEventListener('change', loadAntrean);
});
</script>

<?php include 'includes/footer.php'; ?>


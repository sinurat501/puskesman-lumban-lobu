<?php
require_once 'config/auth_check.php';
require_once 'config/database.php';

$page_title = 'Daftar Pasien';
$page_desc = 'Tambah pasien baru ke antrian';

$conn = getConnection();
$poli_result = $conn->query("SELECT * FROM poli ORDER BY nama_poli");
$poli_list = $poli_result->fetch_all(MYSQLI_ASSOC);
$conn->close();

include 'includes/header.php';
?>

<div class="grid grid-2" style="align-items:start;gap:24px;">

  <!-- FORM PENDAFTARAN -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">ðŸ“ Form Pendaftaran Pasien</div>
    </div>
    <div class="card-body">
      <div id="daftarAlert"></div>

      <form id="daftarForm" autocomplete="off">
        <div class="form-group">
          <label class="form-label" for="nama_pasien">
            Nama Lengkap Pasien <span class="required">*</span>
          </label>
          <input type="text" id="nama_pasien" name="nama_pasien" class="form-control"
                 placeholder="Contoh: Budi Santoso" required
                 oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g,'')"
                 style="text-transform:capitalize;">
          <p class="form-hint">Nama lengkap sesuai identitas (hanya huruf)</p>
        </div>

        <div class="form-group">
          <label class="form-label" for="nik">
            NIK (Nomor Induk Kependudukan) <span class="required">*</span>
          </label>
          <input type="text" id="nik" name="nik" class="form-control"
                 placeholder="16 digit angka" maxlength="16" required
                 oninput="this.value = this.value.replace(/\D/g,''); validateNIK(this);"
                 style="font-family:monospace;letter-spacing:2px;font-size:16px;">
          <div style="margin-top:6px;">
            <div style="height:4px;background:#e2e8f0;border-radius:10px;overflow:hidden;">
              <div id="nikBar" style="height:100%;background:#0ea5e9;border-radius:10px;width:0%;transition:width 0.3s;"></div>
            </div>
            <div id="nikHint" class="form-hint" style="margin-top:4px;">Masukkan 16 digit NIK</div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="id_poli">
            Pilih Poli <span class="required">*</span>
          </label>
          <select id="id_poli" name="id_poli" class="form-control" required onchange="cekKuotaPoli(this.value)">
            <option value="">â€” Pilih Poli â€”</option>
            <?php foreach ($poli_list as $p): ?>
            <option value="<?= $p['id_poli'] ?>"
                    data-kuota="<?= $p['kuota_maksimal'] ?>"
                    data-nama="<?= htmlspecialchars($p['nama_poli']) ?>">
              <?= htmlspecialchars($p['nama_poli']) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div id="kuotaInfo" class="form-hint" style="margin-top:6px;"></div>
        </div>

        <div class="form-group">
          <label class="form-label" for="tanggal_kunjungan">Tanggal Kunjungan</label>
          <input type="date" id="tanggal_kunjungan" name="tanggal_kunjungan" class="form-control"
                 value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
          <p class="form-hint">Default: hari ini</p>
        </div>

        <div style="display:flex;gap:12px;margin-top:24px;">
          <button type="button" class="btn btn-outline" onclick="resetForm()">ðŸ”„ Reset</button>
          <button type="submit" class="btn btn-primary btn-lg" id="btnDaftar"
                  style="flex:1;justify-content:center;">
            <span id="btnDaftarText">âœ… Daftarkan Pasien</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- TIKET PREVIEW + ANTREAN MENUNGGU -->
  <div style="display:flex;flex-direction:column;gap:20px;">

    <!-- TIKET HASIL PENDAFTARAN -->
    <div id="tiketPreviewBox" style="display:none;">
      <div class="card" style="border:2px solid #0ea5e9;overflow:visible;position:relative;">
        <div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:#0ea5e9;color:#fff;padding:4px 16px;border-radius:20px;font-size:12px;font-weight:700;">
          ðŸŽ‰ PENDAFTARAN BERHASIL
        </div>
        <div class="card-body" style="padding:0;">
          <div class="tiket-container" style="max-width:100%;margin:0;border:none;box-shadow:none;">
            <div class="tiket-header" id="tiketHeaderPreview"></div>
            <div class="tiket-body" id="tiketBodyPreview"></div>
            <div class="tiket-footer">Harap hadir saat nomor dipanggil. Terima kasih.</div>
          </div>
        </div>
        <div style="padding:16px;display:flex;gap:10px;border-top:1px solid #e2e8f0;">
          <button class="btn btn-outline btn-sm" style="flex:1;justify-content:center;"
            onclick="document.getElementById('tiketPreviewBox').style.display='none'">Tutup</button>
          <button class="btn btn-primary btn-sm" style="flex:1;justify-content:center;"
            onclick="window.print()">ðŸ–¨ï¸ Cetak Tiket</button>
        </div>
      </div>
    </div>

    <!-- ANTREAN MENUNGGU HARI INI -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">â³ Menunggu Dipanggil</div>
        <button class="btn btn-outline btn-sm" onclick="loadMenunggu()">ðŸ”„</button>
      </div>
      <div id="menungguContainer">
        <div style="padding:30px;text-align:center;">
          <div class="loading-spinner"></div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
loadMenunggu();

// NIK Validation
function validateNIK(input) {
  const len = input.value.length;
  const bar = document.getElementById('nikBar');
  const hint = document.getElementById('nikHint');
  bar.style.width = (len / 16 * 100) + '%';
  if (len === 16) {
    bar.style.background = '#22c55e';
    hint.textContent = 'âœ… NIK valid (16 digit)';
    hint.style.color = '#15803d';
    input.classList.remove('is-invalid');
  } else if (len > 0) {
    bar.style.background = '#f59e0b';
    hint.textContent = `âš ï¸ Baru ${len} digit, perlu ${16 - len} lagi`;
    hint.style.color = '#92400e';
  } else {
    bar.style.background = '#0ea5e9';
    hint.textContent = 'Masukkan 16 digit NIK';
    hint.style.color = '#64748b';
  }
}

// Cek kuota poli
async function cekKuotaPoli(poliId) {
  const info = document.getElementById('kuotaInfo');
  if (!poliId) { info.innerHTML = ''; return; }
  const tanggal = document.getElementById('tanggal_kunjungan').value;
  const data = await apiGet('get_statistik', { tanggal });
  if (data.success) {
    const poli = data.per_poli.find(p => String(p.id_poli || '') === String(poliId));
    if (poli) {
      const sisa = poli.kuota_maksimal - poli.jumlah;
      const pct = Math.round(poli.jumlah / poli.kuota_maksimal * 100);
      if (sisa <= 0) {
        info.innerHTML = `<span style="color:#ef4444;font-weight:600;">âŒ Kuota penuh! Tidak bisa mendaftar.</span>`;
      } else if (sisa <= 5) {
        info.innerHTML = `<span style="color:#f59e0b;font-weight:600;">âš ï¸ Sisa ${sisa} kuota (${pct}% terisi)</span>`;
      } else {
        info.innerHTML = `<span style="color:#15803d;font-weight:600;">âœ… Sisa kuota: ${sisa} dari ${poli.kuota_maksimal}</span>`;
      }
    }
  }
}

document.getElementById('tanggal_kunjungan').addEventListener('change', () => {
  const poliId = document.getElementById('id_poli').value;
  if (poliId) cekKuotaPoli(poliId);
});

// Form submit
document.getElementById('daftarForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const nik = document.getElementById('nik').value;
  if (nik.length !== 16) {
    showToast('NIK harus tepat 16 digit', 'error');
    document.getElementById('nik').focus();
    return;
  }

  const btn = document.getElementById('btnDaftar');
  const btnText = document.getElementById('btnDaftarText');
  btn.disabled = true;
  btnText.innerHTML = 'â³ Mendaftarkan...';

  const data = await apiPost('tambah_antrean', {
    nama_pasien: document.getElementById('nama_pasien').value,
    nik: nik,
    id_poli: document.getElementById('id_poli').value,
    tanggal_kunjungan: document.getElementById('tanggal_kunjungan').value
  });

  if (data.success) {
    showToast(`ðŸŽ‰ ${data.nama_poli}: ${data.nomor_antrean}`, 'success', 5000);
    showTiketPreview(data, document.getElementById('nama_pasien').value, nik);
    resetForm();
    loadMenunggu();
    updateSidebarBadge();
  } else {
    showToast(data.message, 'error');
  }
  btn.disabled = false;
  btnText.innerHTML = 'âœ… Daftarkan Pasien';
});

function showTiketPreview(data, nama, nik) {
  const today = new Date().toLocaleDateString('id-ID', {weekday:'long', day:'2-digit', month:'long', year:'numeric'});
  document.getElementById('tiketHeaderPreview').innerHTML = `
    <h2>ðŸ¥ POLI ${data.nama_poli.toUpperCase()}</h2>
    <p>Puskesmas Lumban Lobu</p>`;
  document.getElementById('tiketBodyPreview').innerHTML = `
    <div class="tiket-label">Nomor Antrean Anda</div>
    <div class="tiket-number">${data.nomor_antrean}</div>
    <div class="tiket-poli">${data.nama_poli}</div>
    <hr class="tiket-divider">
    <div class="tiket-info" style="display:flex;flex-direction:column;gap:6px;">
      <div>Nama: <span>${nama}</span></div>
      <div>NIK: <span>${nik}</span></div>
      <div>Tanggal: <span>${today}</span></div>
      <div>Status: <span>MENUNGGU</span></div>
    </div>`;
  document.getElementById('tiketPreviewBox').style.display = 'block';
  document.getElementById('tiketPreviewBox').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function resetForm() {
  document.getElementById('daftarForm').reset();
  document.getElementById('tanggal_kunjungan').value = new Date().toISOString().split('T')[0];
  document.getElementById('daftarAlert').innerHTML = '';
  document.getElementById('kuotaInfo').innerHTML = '';
  document.getElementById('nikBar').style.width = '0%';
  document.getElementById('nikHint').textContent = 'Masukkan 16 digit NIK';
  document.getElementById('nikHint').style.color = '#64748b';
}

async function loadMenunggu() {
  const container = document.getElementById('menungguContainer');
  const today = new Date().toISOString().split('T')[0];
  const data = await apiGet('get_antrean', { tanggal: today, status: 'menunggu' });

  if (!data.data || !data.data.length) {
    container.innerHTML = `<div class="empty-state" style="padding:30px;">
      <div class="icon" style="font-size:36px;">âœ…</div>
      <h3 style="font-size:14px;">Tidak ada pasien menunggu</h3>
    </div>`;
    return;
  }

  container.innerHTML = `<div style="padding:14px;display:flex;flex-direction:column;gap:8px;">
    ${data.data.map(r => `
      <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
        <strong style="font-size:20px;color:#0ea5e9;font-family:'Poppins',sans-serif;min-width:52px;">${r.nomor_antrean}</strong>
        <div style="flex:1;">
          <div style="font-weight:600;font-size:13.5px;">${escHtml(r.nama_pasien)}</div>
          <div style="font-size:11.5px;color:#94a3b8;">${r.nama_poli}</div>
        </div>
        <span class="badge badge-menunggu">â³ Menunggu</span>
      </div>
    `).join('')}
  </div>`;
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

setInterval(loadMenunggu, 15000);
</script>

<?php include 'includes/footer.php'; ?>


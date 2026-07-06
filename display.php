<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Layar Antrean â€” Puskesmas</title>
<meta name="description" content="Display layar panggilan nomor antrian puskesmas">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  /* Override untuk display mode */
  body { overflow: hidden; }
  .display-page { height: 100vh; }

  .ticker {
    background: rgba(255,255,255,0.05);
    border-top: 1px solid rgba(255,255,255,0.08);
    padding: 10px 40px;
    display: flex;
    gap: 40px;
    overflow: hidden;
    white-space: nowrap;
  }
  .ticker-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: rgba(255,255,255,0.6);
  }
  .ticker-num { font-weight: 700; color: #0ea5e9; font-family: 'Poppins', sans-serif; }

  @keyframes slideInNum {
    from { opacity: 0; transform: scale(0.6) translateY(40px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
  }
  .number-animate { animation: slideInNum 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); }

  .calling-ring {
    width: 280px; height: 280px;
    border-radius: 50%;
    border: 3px solid rgba(14,165,233,0.3);
    display: flex; align-items: center; justify-content: center;
    position: relative;
    margin: 0 auto;
  }
  .calling-ring::before {
    content: '';
    position: absolute;
    inset: -12px;
    border-radius: 50%;
    border: 2px solid rgba(14,165,233,0.15);
    animation: ringPulse 2s ease-in-out infinite;
  }
  .calling-ring::after {
    content: '';
    position: absolute;
    inset: -24px;
    border-radius: 50%;
    border: 1px solid rgba(14,165,233,0.08);
    animation: ringPulse 2s ease-in-out infinite 0.4s;
  }
  @keyframes ringPulse {
    0%, 100% { transform: scale(1); opacity: 0.6; }
    50% { transform: scale(1.05); opacity: 1; }
  }
</style>
</head>
<body>
<div class="display-page">

  <!-- HEADER -->
  <div class="display-header">
    <div>
      <div style="font-size:12px;color:rgba(255,255,255,0.5);letter-spacing:1px;text-transform:uppercase;margin-bottom:4px;">
        ðŸ¥ Sistem Antrian
      </div>
      <h1>🏥 PUSKESMAS LUMBAN LOBU</h1>
    </div>
    <div style="text-align:right;">
      <div class="clock" id="displayClock">00:00:00</div>
      <div style="font-size:12px;color:rgba(255,255,255,0.5);margin-top:4px;" id="displayDate"></div>
    </div>
  </div>

  <!-- MAIN DISPLAY -->
  <div class="display-main">

    <!-- NOMOR YANG SEDANG DIPANGGIL -->
    <div class="display-current">
      <div class="label">NOMOR YANG DIPANGGIL</div>
      <div class="calling-ring" id="callingRing">
        <div id="displayNumber" class="display-number" style="font-size:100px;">-</div>
      </div>
      <div class="display-poli" id="displayPoli" style="margin-top:20px;">Belum Ada Panggilan</div>
      <div id="displayNama" style="font-size:14px;color:rgba(255,255,255,0.5);margin-top:6px;font-family:'Inter',sans-serif;"></div>

      <div style="margin-top:30px;display:flex;gap:12px;flex-wrap:wrap;justify-content:center;" id="displayStats">
        <!-- Stats loaded dynamically -->
      </div>
    </div>

    <!-- ANTREAN BERIKUTNYA -->
    <div class="display-queue-list">
      <h2>ANTREAN BERIKUTNYA</h2>
      <div id="nextQueueList">
        <div style="text-align:center;padding:40px;color:rgba(255,255,255,0.3);">
          <div style="font-size:40px;margin-bottom:12px;">â³</div>
          <div>Memuat antrean...</div>
        </div>
      </div>
    </div>

  </div>

  <!-- TICKER -->
  <div class="ticker" id="ticker">
    <span class="ticker-item">ðŸ“‹ Memuat data...</span>
  </div>

</div>

<script>
let prevCallingNumber = null;

// ---- CLOCK ----
function updateClock() {
  const now = new Date();
  document.getElementById('displayClock').textContent = now.toLocaleTimeString('id-ID');
  const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  document.getElementById('displayDate').textContent =
    `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}
updateClock();
setInterval(updateClock, 1000);

// ---- LOAD DATA ----
async function loadDisplayData() {
  const today = new Date().toISOString().split('T')[0];
  try {
    const [antreanRes, statsRes] = await Promise.all([
      fetch(`api/handler.php?action=get_antrean&tanggal=${today}`).then(r=>r.json()),
      fetch(`api/handler.php?action=get_statistik&tanggal=${today}`).then(r=>r.json())
    ]);

    const all = antreanRes.data || [];
    const stats = statsRes.stats || {};

    // Find currently being called
    const dipanggil = all.filter(a => a.status === 'dipanggil');
    const menunggu = all.filter(a => a.status === 'menunggu');

    // Update current number
    const currentEl = document.getElementById('displayNumber');
    const poliEl = document.getElementById('displayPoli');
    const namaEl = document.getElementById('displayNama');

    if (dipanggil.length > 0) {
      const latest = dipanggil[dipanggil.length - 1];
      if (prevCallingNumber !== latest.nomor_antrean) {
        currentEl.classList.remove('number-animate');
        void currentEl.offsetWidth; // reflow
        currentEl.classList.add('number-animate');
        prevCallingNumber = latest.nomor_antrean;
      }
      currentEl.textContent = latest.nomor_antrean;
      poliEl.textContent = latest.nama_poli;
      namaEl.textContent = latest.nama_pasien;
    } else {
      currentEl.textContent = '-';
      poliEl.textContent = 'Belum Ada Panggilan';
      namaEl.textContent = '';
      prevCallingNumber = null;
    }

    // Stats bar
    const statsEl = document.getElementById('displayStats');
    statsEl.innerHTML = [
      { label: 'Menunggu', val: stats.menunggu || 0, color: '#f59e0b' },
      { label: 'Dipanggil', val: stats.dipanggil || 0, color: '#3b82f6' },
      { label: 'Selesai', val: stats.selesai || 0, color: '#22c55e' },
    ].map(s => `
      <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:12px 20px;text-align:center;min-width:90px;">
        <div style="font-size:28px;font-weight:800;color:${s.color};font-family:'Poppins',sans-serif;">${s.val}</div>
        <div style="font-size:11px;color:rgba(255,255,255,0.45);margin-top:3px;text-transform:uppercase;letter-spacing:0.5px;">${s.label}</div>
      </div>
    `).join('');

    // Next queue list (show menunggu, max 8)
    const nextEl = document.getElementById('nextQueueList');
    if (!menunggu.length) {
      nextEl.innerHTML = `<div style="text-align:center;padding:40px;color:rgba(255,255,255,0.3);">
        <div style="font-size:40px;margin-bottom:12px;">âœ…</div>
        <div>Semua pasien telah dilayani</div>
      </div>`;
    } else {
      nextEl.innerHTML = menunggu.slice(0, 8).map((r, i) => `
        <div class="display-queue-item" style="${i === 0 ? 'background:rgba(14,165,233,0.15);border-color:rgba(14,165,233,0.3);' : ''}">
          <div>
            <div class="dqi-number">${r.nomor_antrean}</div>
            <div class="dqi-name">${r.nama_pasien}</div>
          </div>
          <span class="dqi-poli">${r.nama_poli}</span>
        </div>
      `).join('');
    }

    // Ticker â€” all today's data
    const tickerEl = document.getElementById('ticker');
    if (all.length) {
      tickerEl.innerHTML = all.slice(0, 20).map(r => `
        <span class="ticker-item">
          <span class="ticker-num">${r.nomor_antrean}</span>
          ${r.nama_pasien} â€” ${r.nama_poli}
          <span style="font-size:10px;margin-left:4px;opacity:0.6;">(${r.status})</span>
        </span>
        <span style="color:rgba(255,255,255,0.2);">â€¢</span>
      `).join('');
    } else {
      tickerEl.innerHTML = `<span class="ticker-item">ðŸ“‹ Belum ada pasien terdaftar hari ini</span>`;
    }

  } catch(e) {
    console.error('Error loading display data:', e);
  }
}

loadDisplayData();
setInterval(loadDisplayData, 5000); // refresh setiap 5 detik
</script>
</body>
</html>


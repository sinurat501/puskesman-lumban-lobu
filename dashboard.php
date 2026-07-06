<?php
require_once 'config/auth_check.php';
require_once 'config/database.php';

$page_title = 'Dashboard';
$page_desc = 'Ringkasan sistem antrian hari ini';

// Get today's stats
$conn = getConnection();
$today = date('Y-m-d');

$stmt = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='menunggu' THEN 1 ELSE 0 END) as menunggu,
    SUM(CASE WHEN status='dipanggil' THEN 1 ELSE 0 END) as dipanggil,
    SUM(CASE WHEN status='selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN status='lewat' THEN 1 ELSE 0 END) as lewat
    FROM antrean WHERE tanggal_kunjungan = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$poli_result = $conn->query("SELECT p.*, 
    COUNT(a.id_antrean) as jumlah,
    SUM(CASE WHEN a.status='menunggu' THEN 1 ELSE 0 END) as menunggu,
    SUM(CASE WHEN a.status='dipanggil' THEN 1 ELSE 0 END) as dipanggil,
    SUM(CASE WHEN a.status='selesai' THEN 1 ELSE 0 END) as selesai
    FROM poli p LEFT JOIN antrean a ON p.id_poli = a.id_poli AND a.tanggal_kunjungan = '$today'
    GROUP BY p.id_poli ORDER BY p.id_poli");
$poli_list = $poli_result->fetch_all(MYSQLI_ASSOC);

// Recent antrean
$stmt2 = $conn->prepare("SELECT a.*, p.nama_poli FROM antrean a 
    JOIN poli p ON a.id_poli = p.id_poli 
    WHERE a.tanggal_kunjungan = ? ORDER BY a.created_at DESC LIMIT 8");
$stmt2->bind_param("s", $today);
$stmt2->execute();
$recent = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();

$poli_colors = ['blue', 'green', 'orange', 'red'];
$poli_icons = ['🩺', '🦷', '👶', '💊'];

// Fungsi Helper PHP untuk menampilkan Badge Status agar tidak Error
function phpStatusBadge($status) {
    $map = [
        'menunggu' => 'badge-menunggu',
        'dipanggil' => 'badge-dipanggil',
        'selesai' => 'badge-selesai',
        'lewat' => 'badge-lewat'
    ];
    $labels = [
        'menunggu' => 'Menunggu',
        'dipanggil' => 'Dipanggil',
        'selesai' => 'Selesai',
        'lewat' => 'Lewat'
    ];
    $class = $map[$status] ?? '';
    $label = $labels[$status] ?? htmlspecialchars($status);
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}

include 'includes/header.php';
?>

<!-- STATS GRID -->
<div class="stats-grid">
  <div class="stat-card blue">
    <div class="stat-icon">📋</div>
    <div class="stat-value"><?= $stats['total'] ?? 0 ?></div>
    <div class="stat-label">Total Antrean Hari Ini</div>
    <div class="stat-change neutral">📅 <?= date('d M Y') ?></div>
  </div>
  <div class="stat-card orange">
    <div class="stat-icon">⏳</div>
    <div class="stat-value"><?= $stats['menunggu'] ?? 0 ?></div>
    <div class="stat-label">Sedang Menunggu</div>
    <div class="stat-change neutral">Belum dipanggil</div>
  </div>
  <div class="stat-card blue" style="--primary:#6366f1;">
    <div class="stat-icon" style="background:#ede9fe;">🔔</div>
    <div class="stat-value"><?= $stats['dipanggil'] ?? 0 ?></div>
    <div class="stat-label">Sedang Dipanggil</div>
    <div class="stat-change neutral">Dalam pelayanan</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon">✅</div>
    <div class="stat-value"><?= $stats['selesai'] ?? 0 ?></div>
    <div class="stat-label">Selesai Dilayani</div>
    <div class="stat-change up">↑ Telah terlayani</div>
  </div>
</div>

<!-- POLI CARDS + RECENT -->
<div class="grid grid-2" style="gap:24px;">

  <!-- POLI STATUS -->
  <div>
    <div class="card">
      <div class="card-header">
        <div class="card-title">🏥 Status Per Poli</div>
        <a href="daftar.php" class="btn btn-primary btn-sm">➕ Daftar Pasien</a>
      </div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:14px;">
        <?php foreach ($poli_list as $i => $poli): 
          $pct = $poli['kuota_maksimal'] > 0 ? min(100, round($poli['jumlah'] / $poli['kuota_maksimal'] * 100)) : 0;
          $fill_colors = ['#0ea5e9', '#22c55e', '#f59e0b', '#ef4444'];
          $fill = $fill_colors[$i % count($fill_colors)];
        ?>
        <div class="poli-card" style="cursor:pointer;" onclick="window.location.href='antrean.php?poli=<?= $poli['id_poli'] ?>'">
          <div class="poli-card-header">
            <div style="display:flex;align-items:center;gap:10px;">
              <span style="font-size:24px;"><?= $poli_icons[$i % count($poli_icons)] ?></span>
              <div>
                <div class="poli-name"><?= htmlspecialchars($poli['nama_poli']) ?></div>
                <div style="font-size:12px;color:#64748b;">
                  <?= $poli['jumlah'] ?> / <?= $poli['kuota_maksimal'] ?> pasien
                </div>
              </div>
            </div>
            <span class="poli-badge"><?= $pct ?>% penuh</span>
          </div>
          <div class="poli-progress-bar">
            <div class="poli-progress-fill" style="width:<?= $pct ?>%;background:<?= $fill ?>;"></div>
          </div>
          <div style="display:flex;gap:16px;font-size:12px;color:#64748b;margin-top:6px;">
            <span>⏳ <?= $poli['menunggu'] ?? 0 ?> menunggu</span>
            <span>🔔 <?= $poli['dipanggil'] ?? 0 ?> dipanggil</span>
            <span>✅ <?= $poli['selesai'] ?? 0 ?> selesai</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- RECENT ANTREAN -->
  <div>
    <div class="card">
      <div class="card-header">
        <div class="card-title">🕐 Antrean Terbaru</div>
        <a href="antrean.php" class="btn btn-outline btn-sm">Lihat Semua</a>
      </div>
      <?php if (empty($recent)): ?>
        <div class="empty-state">
          <div class="icon">📭</div>
          <h3>Belum Ada Antrean</h3>
          <p>Antrean hari ini masih kosong</p>
        </div>
      <?php else: ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>No.</th>
              <th>Nama Pasien</th>
              <th>Poli</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $row): ?>
            <tr>
              <td><strong style="font-size:16px;color:#0ea5e9;"><?= htmlspecialchars($row['nomor_antrean']) ?></strong></td>
              <td>
                <div style="font-weight:600;"><?= htmlspecialchars($row['nama_pasien']) ?></div>
                <div style="font-size:11px;color:#94a3b8;"><?= htmlspecialchars($row['nik']) ?></div>
              </td>
              <td style="font-size:12.5px;"><?= htmlspecialchars($row['nama_poli']) ?></td>
              <td><?= phpStatusBadge($row['status']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div><!-- /.grid -->

<script>
// Auto-refresh dashboard setiap 30 detik
setInterval(() => { window.location.reload(); }, 30000);
</script>

<?php include 'includes/footer.php'; ?>
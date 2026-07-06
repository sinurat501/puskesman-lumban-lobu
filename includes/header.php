<?php
// Komponen Sidebar - include di semua halaman utama
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?? 'SIAP - Puskesmas Lumban Lobu' ?></title>
<meta name="description" content="<?= $page_desc ?? 'Sistem Informasi Antrian Pasien Puskesmas Lumban Lobu' ?>">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-wrapper">

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <a href="dashboard.php" class="sidebar-logo">
        <div class="logo-icon">ðŸ¥</div>
        <div class="logo-text">
          <strong>Lumban Lobu</strong>
          <span>Sistem Antrian Pasien</span>
        </div>
      </a>
    </div>

    <nav class="sidebar-nav">
      <p class="nav-label">Menu Utama</p>

      <a href="dashboard.php" class="nav-item <?= $current_page === 'dashboard' ? 'active' : '' ?>">
        <span class="nav-icon">ðŸ“Š</span> Dashboard
      </a>

      <a href="antrean.php" class="nav-item <?= $current_page === 'antrean' ? 'active' : '' ?>">
        <span class="nav-icon">ðŸ“‹</span> Kelola Antrean
        <span class="nav-badge" id="sidebarBadge" style="display:none">0</span>
      </a>

      <a href="daftar.php" class="nav-item <?= $current_page === 'daftar' ? 'active' : '' ?>">
        <span class="nav-icon">âž•</span> Daftar Pasien
      </a>

      <p class="nav-label">Tampilan</p>

      <a href="display.php" target="_blank" class="nav-item">
        <span class="nav-icon">ðŸ“º</span> Layar Antrean
        <span style="font-size:10px;color:#475569;margin-left:auto;">â†—</span>
      </a>

    </nav>

    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar">ðŸ‘¤</div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($_SESSION['nama_petugas'] ?? 'Petugas') ?></div>
          <div class="user-role">Petugas Loket</div>
        </div>
      </div>
      <button onclick="handleLogout()" class="nav-item" style="margin-top:8px;color:#ef4444;">
        <span class="nav-icon">ðŸšª</span> Keluar
      </button>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="btn-icon" onclick="toggleSidebar()" id="menuBtn" title="Toggle Menu">â˜°</button>
        <div>
          <div class="page-title"><?= $page_title ?? 'Dashboard' ?></div>
          <div class="page-subtitle"><?= $page_desc ?? '' ?></div>
        </div>
      </div>
      <div class="topbar-right">
        <div class="topbar-date" id="topbarDate"></div>
        <a href="display.php" target="_blank" class="btn btn-outline btn-sm" title="Buka Layar Panggilan">
          ðŸ“º Layar
        </a>
      </div>
    </header>

    <main class="page-body">


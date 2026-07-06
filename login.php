<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
$nama_puskesmas = 'Puskesmas Lumban Lobu';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — SIAP Puskesmas Lumban Lobu</title>
<meta name="description" content="Login petugas loket sistem antrian Puskesmas Lumban Lobu">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="icon">ðŸ ¥</div>
      <h1>Puskesmas Lumban Lobu</h1>
      <p>Sistem Informasi Antrian Pasien — Panel Petugas</p>
    </div>

    <div id="loginAlert"></div>

    <form id="loginForm" autocomplete="off">
      <div class="form-group">
        <label class="form-label" for="username">Username <span class="required">*</span></label>
        <input type="text" id="username" name="username" class="form-control"
               placeholder="Masukkan username" autocomplete="username" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password <span class="required">*</span></label>
        <div style="position:relative;">
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="Masukkan password" autocomplete="current-password" required
                 style="padding-right: 44px;">
          <button type="button" id="togglePass"
            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:18px;color:#94a3b8;">
            ðŸ‘ï¸
          </button>
        </div>
        <p class="form-hint" style="margin-top:6px;">Default: <strong>admin_loket</strong> / <strong>password</strong></p>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" id="btnLogin"
              style="width:100%;justify-content:center;margin-top:8px;">
        <span id="btnText">ðŸ” Masuk</span>
      </button>
    </form>

    <div style="text-align:center;margin-top:24px;padding-top:20px;border-top:1px solid #e2e8f0;">
      <p style="font-size:12px;color:#94a3b8;">
        ðŸ”— <a href="display.php" target="_blank"
              style="color:#0ea5e9;text-decoration:none;font-weight:600;">
          Buka Layar Panggilan Antrian
        </a>
      </p>
    </div>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script src="assets/js/app.js"></script>
<script>
document.getElementById('togglePass').addEventListener('click', function() {
  const pw = document.getElementById('password');
  const isPass = pw.type === 'password';
  pw.type = isPass ? 'text' : 'password';
  this.textContent = isPass ? 'ðŸ™ˆ' : 'ðŸ‘ï¸';
});

document.getElementById('loginForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('btnLogin');
  const btnText = document.getElementById('btnText');
  const alertEl = document.getElementById('loginAlert');

  btn.disabled = true;
  btnText.innerHTML = 'â³ Memverifikasi...';
  alertEl.innerHTML = '';

  const formData = new FormData(this);
  formData.append('action', 'login');

  try {
    const res = await fetch('api/handler.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      btnText.innerHTML = 'âœ… Berhasil! Mengalihkan...';
      showToast('Selamat datang, ' + data.nama_petugas + '!', 'success');
      setTimeout(() => { window.location.href = 'dashboard.php'; }, 800);
    } else {
      alertEl.innerHTML = `<div class="alert alert-danger"><span class="alert-icon">âŒ</span>${data.message}</div>`;
      btn.disabled = false;
      btnText.innerHTML = 'ðŸ” Masuk';
      document.getElementById('password').focus();
    }
  } catch(err) {
    alertEl.innerHTML = `<div class="alert alert-danger"><span class="alert-icon">âŒ</span>Koneksi server gagal. Pastikan XAMPP aktif.</div>`;
    btn.disabled = false;
    btnText.innerHTML = 'ðŸ” Masuk';
  }
});
</script>
</body>
</html>


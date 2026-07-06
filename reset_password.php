<?php
/**
 * Script Reset Password Admin
 * Akses sekali saja: http://localhost/puskesmas/reset_password.php
 * HAPUS file ini setelah digunakan!
 */

// Uncomment dan ganti password sesuai kebutuhan:
$new_password = 'password';  // ← ganti ini

require_once 'config/database.php';
$conn = getConnection();

$hash = password_hash($new_password, PASSWORD_BCRYPT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin_loket'");
$stmt->bind_param("s", $hash);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "<h2 style='color:green;font-family:sans-serif;'>✅ Password berhasil direset!</h2>";
    echo "<p>Username: <strong>admin_loket</strong></p>";
    echo "<p>Password baru: <strong>$new_password</strong></p>";
    echo "<p style='color:red;'><strong>⚠️ Segera hapus file ini setelah digunakan!</strong></p>";
} else {
    echo "<h2 style='color:red;font-family:sans-serif;'>❌ Gagal reset password</h2>";
    echo "<p>User 'admin_loket' tidak ditemukan.</p>";
}
$conn->close();
?>

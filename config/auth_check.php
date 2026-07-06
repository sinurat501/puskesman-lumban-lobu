<?php
// auth_check.php - Include di setiap halaman yang perlu login
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

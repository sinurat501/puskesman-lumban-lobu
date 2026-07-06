<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'get_antrean':
        requireAuth();
        getAntrean();
        break;
    case 'tambah_antrean':
        requireAuth();
        tambahAntrean();
        break;
    case 'update_status':
        requireAuth();
        updateStatus();
        break;
    case 'hapus_antrean':
        requireAuth();
        hapusAntrean();
        break;
    case 'get_poli':
        getPoli();
        break;
    case 'get_statistik':
        requireAuth();
        getStatistik();
        break;
    case 'check_auth':
        echo json_encode(['logged_in' => isset($_SESSION['user_id']), 'user' => $_SESSION['nama_petugas'] ?? null]);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu', 'redirect' => 'login']);
        exit;
    }
}

function handleLogin() {
    $conn = getConnection();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username dan password harus diisi']);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama_petugas'] = $user['nama_petugas'];
        echo json_encode(['success' => true, 'message' => 'Login berhasil', 'nama_petugas' => $user['nama_petugas']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Username atau password salah']);
    }

    $conn->close();
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logout berhasil']);
}

function getPoli() {
    $conn = getConnection();
    $result = $conn->query("SELECT * FROM poli ORDER BY nama_poli");
    $poli = [];
    while ($row = $result->fetch_assoc()) {
        $poli[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $poli]);
    $conn->close();
}

function getAntrean() {
    $conn = getConnection();
    $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
    $id_poli = $_GET['id_poli'] ?? '';
    $status = $_GET['status'] ?? '';

    $sql = "SELECT a.*, p.nama_poli FROM antrean a 
            JOIN poli p ON a.id_poli = p.id_poli 
            WHERE a.tanggal_kunjungan = ?";
    $params = [$tanggal];
    $types = "s";

    if (!empty($id_poli)) {
        $sql .= " AND a.id_poli = ?";
        $params[] = $id_poli;
        $types .= "i";
    }

    if (!empty($status)) {
        $sql .= " AND a.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    $sql .= " ORDER BY a.nomor_antrean ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $antrean = [];
    while ($row = $result->fetch_assoc()) {
        $antrean[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $antrean]);
    $conn->close();
}

function tambahAntrean() {
    $conn = getConnection();
    $nama_pasien = trim($_POST['nama_pasien'] ?? '');
    $nik = trim($_POST['nik'] ?? '');
    $id_poli = intval($_POST['id_poli'] ?? 0);
    $tanggal = $_POST['tanggal_kunjungan'] ?? date('Y-m-d');

    if (empty($nama_pasien) || empty($nik) || empty($id_poli)) {
        echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
        return;
    }

    if (strlen($nik) !== 16 || !ctype_digit($nik)) {
        echo json_encode(['success' => false, 'message' => 'NIK harus 16 digit angka']);
        return;
    }

    // Cek kuota poli
    $stmt = $conn->prepare("SELECT p.kuota_maksimal, COUNT(a.id_antrean) as jumlah 
                             FROM poli p LEFT JOIN antrean a ON p.id_poli = a.id_poli 
                             AND a.tanggal_kunjungan = ? AND a.status != 'lewat'
                             WHERE p.id_poli = ? GROUP BY p.id_poli");
    $stmt->bind_param("si", $tanggal, $id_poli);
    $stmt->execute();
    $kuota_data = $stmt->get_result()->fetch_assoc();

    if (!$kuota_data) {
        echo json_encode(['success' => false, 'message' => 'Poli tidak ditemukan']);
        return;
    }

    if ($kuota_data['jumlah'] >= $kuota_data['kuota_maksimal']) {
        echo json_encode(['success' => false, 'message' => 'Kuota poli sudah penuh untuk hari ini']);
        return;
    }

    // Cek duplikat NIK di poli yang sama hari ini
    $stmt = $conn->prepare("SELECT id_antrean FROM antrean WHERE nik = ? AND id_poli = ? AND tanggal_kunjungan = ?");
    $stmt->bind_param("sis", $nik, $id_poli, $tanggal);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Pasien dengan NIK ini sudah terdaftar di poli ini hari ini']);
        return;
    }

    // Generate nomor antrean
    $stmt = $conn->prepare("SELECT p.nama_poli, COUNT(a.id_antrean) as total FROM poli p 
                             LEFT JOIN antrean a ON p.id_poli = a.id_poli AND a.tanggal_kunjungan = ?
                             WHERE p.id_poli = ? GROUP BY p.id_poli");
    $stmt->bind_param("si", $tanggal, $id_poli);
    $stmt->execute();
    $info = $stmt->get_result()->fetch_assoc();

    $prefix = strtoupper(substr($info['nama_poli'], 0, 1));
    $nomor = $info['total'] + 1;
    $nomor_antrean = $prefix . '-' . str_pad($nomor, 2, '0', STR_PAD_LEFT);

    // Insert antrean
    $stmt = $conn->prepare("INSERT INTO antrean (nama_pasien, nik, id_poli, nomor_antrean, tanggal_kunjungan, status) VALUES (?, ?, ?, ?, ?, 'menunggu')");
    $stmt->bind_param("ssiss", $nama_pasien, $nik, $id_poli, $nomor_antrean, $tanggal);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Antrean berhasil ditambahkan',
            'nomor_antrean' => $nomor_antrean,
            'nama_poli' => $info['nama_poli']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan antrean']);
    }

    $conn->close();
}

function updateStatus() {
    $conn = getConnection();
    $id_antrean = intval($_POST['id_antrean'] ?? 0);
    $status = $_POST['status'] ?? '';

    $valid_status = ['menunggu', 'dipanggil', 'selesai', 'lewat'];
    if (!in_array($status, $valid_status)) {
        echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
        return;
    }

    $stmt = $conn->prepare("UPDATE antrean SET status = ? WHERE id_antrean = ?");
    $stmt->bind_param("si", $status, $id_antrean);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Status berhasil diperbarui']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status']);
    }
    $conn->close();
}

function hapusAntrean() {
    $conn = getConnection();
    $id_antrean = intval($_POST['id_antrean'] ?? 0);

    $stmt = $conn->prepare("DELETE FROM antrean WHERE id_antrean = ?");
    $stmt->bind_param("i", $id_antrean);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Data antrean berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus data']);
    }
    $conn->close();
}

function getStatistik() {
    $conn = getConnection();
    $tanggal = $_GET['tanggal'] ?? date('Y-m-d');

    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
                SUM(CASE WHEN status = 'dipanggil' THEN 1 ELSE 0 END) as dipanggil,
                SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN status = 'lewat' THEN 1 ELSE 0 END) as lewat
            FROM antrean WHERE tanggal_kunjungan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $tanggal);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    // Per poli
    $sql2 = "SELECT p.nama_poli, COUNT(a.id_antrean) as jumlah, p.kuota_maksimal
             FROM poli p LEFT JOIN antrean a ON p.id_poli = a.id_poli AND a.tanggal_kunjungan = ?
             GROUP BY p.id_poli, p.nama_poli, p.kuota_maksimal";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("s", $tanggal);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $per_poli = [];
    while ($row = $result2->fetch_assoc()) {
        $per_poli[] = $row;
    }

    echo json_encode(['success' => true, 'stats' => $stats, 'per_poli' => $per_poli]);
    $conn->close();
}
?>

<?php
/**
 * IntegrationTest.php
 * Integration Tests — Menguji interaksi API ↔ Database
 *
 * Pastikan XAMPP MySQL aktif dan database uas_puskesmas tersedia sebelum menjalankan.
 * Jalankan: vendor/bin/phpunit tests/integration/IntegrationTest.php --colors=always
 */

use PHPUnit\Framework\TestCase;

// Load config
require_once __DIR__ . '/../../config/database.php';

class IntegrationTest extends TestCase
{
    private $conn;
    private $testPoliId = 1; // Poli Umum
    private $createdIds = []; // Track IDs to cleanup

    protected function setUp(): void
    {
        $this->conn = getConnection();
        $this->assertNotNull($this->conn, 'Koneksi database harus berhasil');
    }

    protected function tearDown(): void
    {
        // Cleanup test data
        if (!empty($this->createdIds)) {
            $ids = implode(',', array_map('intval', $this->createdIds));
            $this->conn->query("DELETE FROM antrean WHERE id_antrean IN ($ids)");
        }
        $this->conn->close();
    }

    // ============================================================
    //  Koneksi Database
    // ============================================================

    /** @test */
    public function testDatabaseConnection_berhasil()
    {
        $result = $this->conn->query("SELECT 1 AS ping");
        $row = $result->fetch_assoc();
        $this->assertEquals(1, $row['ping']);
    }

    /** @test */
    public function testTabelPoli_exist_dan_berisiData()
    {
        $result = $this->conn->query("SELECT COUNT(*) AS total FROM poli");
        $row = $result->fetch_assoc();
        $this->assertGreaterThan(0, $row['total'], 'Tabel poli harus berisi minimal 1 data');
    }

    /** @test */
    public function testTabelUsers_exist_dan_berisiData()
    {
        $result = $this->conn->query("SELECT COUNT(*) AS total FROM users");
        $row = $result->fetch_assoc();
        $this->assertGreaterThan(0, $row['total'], 'Tabel users harus berisi minimal 1 akun');
    }

    // ============================================================
    //  Insert Antrean
    // ============================================================

    /** @test */
    public function testInsertAntrean_berhasil()
    {
        $tanggal = '2099-12-31'; // tanggal test agar tidak konflik data real
        $stmt = $this->conn->prepare(
            "INSERT INTO antrean (nama_pasien, nik, id_poli, nomor_antrean, tanggal_kunjungan, status)
             VALUES (?, ?, ?, ?, ?, 'menunggu')"
        );
        $nama = 'Test Pasien Integration';
        $nik = '9999999999999999';
        $nomor = 'T-99';
        $stmt->bind_param("ssiss", $nama, $nik, $this->testPoliId, $nomor, $tanggal);
        $stmt->execute();

        $this->assertEquals(1, $stmt->affected_rows, 'Insert antrean harus berhasil');
        $this->createdIds[] = $this->conn->insert_id;
    }

    /** @test */
    public function testInsertAntrean_duplikatNIKPoliBlokirDiLevel_App()
    {
        // Test bahwa query duplikat dapat dideteksi di level aplikasi
        $tanggal = '2099-12-30';
        $nik = '8888888888888888';

        // Insert pertama
        $stmt = $this->conn->prepare(
            "INSERT INTO antrean (nama_pasien, nik, id_poli, nomor_antrean, tanggal_kunjungan, status)
             VALUES (?, ?, ?, ?, ?, 'menunggu')"
        );
        $nama = 'Pasien Duplikat A';
        $nomor = 'D-01';
        $stmt->bind_param("ssiss", $nama, $nik, $this->testPoliId, $nomor, $tanggal);
        $stmt->execute();
        $this->createdIds[] = $this->conn->insert_id;

        // Cek apakah sudah ada (simulasi logika aplikasi)
        $stmt2 = $this->conn->prepare(
            "SELECT id_antrean FROM antrean WHERE nik = ? AND id_poli = ? AND tanggal_kunjungan = ?"
        );
        $stmt2->bind_param("sis", $nik, $this->testPoliId, $tanggal);
        $stmt2->execute();
        $count = $stmt2->get_result()->num_rows;

        $this->assertEquals(1, $count, 'NIK yang sama di poli yang sama seharusnya terdeteksi sebagai duplikat');
    }

    // ============================================================
    //  Update Status
    // ============================================================

    /** @test */
    public function testUpdateStatus_dariMenungguKeDipanggil()
    {
        // Insert dulu
        $tanggal = '2099-12-29';
        $stmt = $this->conn->prepare(
            "INSERT INTO antrean (nama_pasien, nik, id_poli, nomor_antrean, tanggal_kunjungan, status)
             VALUES ('Test Update', '7777777777777777', ?, 'U-01', ?, 'menunggu')"
        );
        $stmt->bind_param("is", $this->testPoliId, $tanggal);
        $stmt->execute();
        $id = $this->conn->insert_id;
        $this->createdIds[] = $id;

        // Update status
        $newStatus = 'dipanggil';
        $stmt2 = $this->conn->prepare("UPDATE antrean SET status = ? WHERE id_antrean = ?");
        $stmt2->bind_param("si", $newStatus, $id);
        $stmt2->execute();

        // Verifikasi
        $res = $this->conn->query("SELECT status FROM antrean WHERE id_antrean = $id");
        $row = $res->fetch_assoc();
        $this->assertEquals('dipanggil', $row['status'], 'Status harus berubah menjadi dipanggil');
    }

    // ============================================================
    //  Password Hash (Users)
    // ============================================================

    /** @test */
    public function testUserPassword_menggunakanBcryptHash()
    {
        $result = $this->conn->query("SELECT password FROM users LIMIT 1");
        $row = $result->fetch_assoc();
        $this->assertNotNull($row, 'Harus ada minimal 1 user');
        // Bcrypt hash dimulai dengan $2y$
        $this->assertStringStartsWith('$2y$', $row['password'],
            'Password harus tersimpan sebagai bcrypt hash, bukan plaintext');
    }

    // ============================================================
    //  Kuota Poli
    // ============================================================

    /** @test */
    public function testKuotaPoli_terisiTidakMelebihiMaksimal()
    {
        $today = date('Y-m-d');
        $result = $this->conn->query("
            SELECT p.nama_poli, p.kuota_maksimal, COUNT(a.id_antrean) as terisi
            FROM poli p
            LEFT JOIN antrean a ON p.id_poli = a.id_poli AND a.tanggal_kunjungan = '$today' AND a.status != 'lewat'
            GROUP BY p.id_poli
        ");
        while ($row = $result->fetch_assoc()) {
            $this->assertLessThanOrEqual(
                $row['kuota_maksimal'],
                $row['terisi'],
                "Poli {$row['nama_poli']}: jumlah terisi ({$row['terisi']}) tidak boleh melebihi kuota maksimal ({$row['kuota_maksimal']})"
            );
        }
    }
}

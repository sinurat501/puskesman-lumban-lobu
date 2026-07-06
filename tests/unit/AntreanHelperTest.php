<?php
/**
 * AntreanHelperTest.php
 * Unit Tests untuk AntreanHelper
 *
 * Menguji setiap fungsi secara terisolasi tanpa perlu database.
 * Jalankan: vendor/bin/phpunit tests/unit/AntreanHelperTest.php --colors=always
 */

use PHPUnit\Framework\TestCase;
use App\AntreanHelper;

// Autoload manual jika composer belum diinstal
if (!class_exists('App\AntreanHelper')) {
    require_once __DIR__ . '/../../src/AntreanHelper.php';
}

class AntreanHelperTest extends TestCase
{
    // ============================================================
    //  validateNIK
    // ============================================================

    /** @test */
    public function testValidateNIK_valid()
    {
        $this->assertTrue(AntreanHelper::validateNIK('1234567890123456'));
    }

    /** @test */
    public function testValidateNIK_terlalu_pendek()
    {
        $this->assertFalse(AntreanHelper::validateNIK('12345'));
    }

    /** @test */
    public function testValidateNIK_terlalu_panjang()
    {
        $this->assertFalse(AntreanHelper::validateNIK('12345678901234567'));
    }

    /** @test */
    public function testValidateNIK_mengandung_huruf()
    {
        $this->assertFalse(AntreanHelper::validateNIK('12345678ABCD5678'));
    }

    /** @test */
    public function testValidateNIK_kosong()
    {
        $this->assertFalse(AntreanHelper::validateNIK(''));
    }

    // ============================================================
    //  generateNomorAntrean
    // ============================================================

    /** @test */
    public function testGenerateNomorAntrean_poliUmum_urutan1()
    {
        $nomor = AntreanHelper::generateNomorAntrean('Poli Umum', 1);
        $this->assertEquals('P-01', $nomor);
    }

    /** @test */
    public function testGenerateNomorAntrean_poliGigi_urutan3()
    {
        $nomor = AntreanHelper::generateNomorAntrean('Poli Gigi', 3);
        $this->assertEquals('P-03', $nomor);
    }

    /** @test */
    public function testGenerateNomorAntrean_KIA_urutan15()
    {
        $nomor = AntreanHelper::generateNomorAntrean('Kesehatan Ibu dan Anak (KIA)', 15);
        $this->assertEquals('K-15', $nomor);
    }

    /** @test */
    public function testGenerateNomorAntrean_urutan0_throwException()
    {
        $this->expectException(\InvalidArgumentException::class);
        AntreanHelper::generateNomorAntrean('Poli Umum', 0);
    }

    /** @test */
    public function testGenerateNomorAntrean_urutanNegatif_throwException()
    {
        $this->expectException(\InvalidArgumentException::class);
        AntreanHelper::generateNomorAntrean('Poli Umum', -1);
    }

    // ============================================================
    //  validateNamaPasien
    // ============================================================

    /** @test */
    public function testValidateNamaPasien_valid()
    {
        $this->assertTrue(AntreanHelper::validateNamaPasien('Budi Santoso'));
    }

    /** @test */
    public function testValidateNamaPasien_satu_kata()
    {
        $this->assertTrue(AntreanHelper::validateNamaPasien('Ahmad'));
    }

    /** @test */
    public function testValidateNamaPasien_terlalu_pendek()
    {
        $this->assertFalse(AntreanHelper::validateNamaPasien('Bu'));
    }

    /** @test */
    public function testValidateNamaPasien_mengandung_angka()
    {
        $this->assertFalse(AntreanHelper::validateNamaPasien('Budi123'));
    }

    /** @test */
    public function testValidateNamaPasien_kosong()
    {
        $this->assertFalse(AntreanHelper::validateNamaPasien(''));
    }

    // ============================================================
    //  isKuotaAvailable
    // ============================================================

    /** @test */
    public function testIsKuotaAvailable_masihAda()
    {
        $this->assertTrue(AntreanHelper::isKuotaAvailable(10, 30));
    }

    /** @test */
    public function testIsKuotaAvailable_penuh()
    {
        $this->assertFalse(AntreanHelper::isKuotaAvailable(30, 30));
    }

    /** @test */
    public function testIsKuotaAvailable_melebihi()
    {
        $this->assertFalse(AntreanHelper::isKuotaAvailable(35, 30));
    }

    /** @test */
    public function testIsKuotaAvailable_kosong()
    {
        $this->assertTrue(AntreanHelper::isKuotaAvailable(0, 50));
    }

    // ============================================================
    //  getKuotaPercentage
    // ============================================================

    /** @test */
    public function testGetKuotaPercentage_50persen()
    {
        $this->assertEquals(50.0, AntreanHelper::getKuotaPercentage(15, 30));
    }

    /** @test */
    public function testGetKuotaPercentage_penuh()
    {
        $this->assertEquals(100.0, AntreanHelper::getKuotaPercentage(30, 30));
    }

    /** @test */
    public function testGetKuotaPercentage_kosong()
    {
        $this->assertEquals(0.0, AntreanHelper::getKuotaPercentage(0, 30));
    }

    /** @test */
    public function testGetKuotaPercentage_maksimalNol()
    {
        $this->assertEquals(0.0, AntreanHelper::getKuotaPercentage(10, 0));
    }

    // ============================================================
    //  validateTanggal
    // ============================================================

    /** @test */
    public function testValidateTanggal_valid()
    {
        $this->assertTrue(AntreanHelper::validateTanggal('2026-07-06'));
    }

    /** @test */
    public function testValidateTanggal_formatSalah()
    {
        $this->assertFalse(AntreanHelper::validateTanggal('06-07-2026'));
    }

    /** @test */
    public function testValidateTanggal_bulanTidakValid()
    {
        $this->assertFalse(AntreanHelper::validateTanggal('2026-13-01'));
    }

    /** @test */
    public function testValidateTanggal_kosong()
    {
        $this->assertFalse(AntreanHelper::validateTanggal(''));
    }

    // ============================================================
    //  validateStatus
    // ============================================================

    /** @test */
    public function testValidateStatus_menunggu()
    {
        $this->assertTrue(AntreanHelper::validateStatus('menunggu'));
    }

    /** @test */
    public function testValidateStatus_dipanggil()
    {
        $this->assertTrue(AntreanHelper::validateStatus('dipanggil'));
    }

    /** @test */
    public function testValidateStatus_selesai()
    {
        $this->assertTrue(AntreanHelper::validateStatus('selesai'));
    }

    /** @test */
    public function testValidateStatus_lewat()
    {
        $this->assertTrue(AntreanHelper::validateStatus('lewat'));
    }

    /** @test */
    public function testValidateStatus_tidakValid()
    {
        $this->assertFalse(AntreanHelper::validateStatus('batal'));
    }

    /** @test */
    public function testValidateStatus_hurufBesar()
    {
        $this->assertFalse(AntreanHelper::validateStatus('Menunggu'));
    }
}

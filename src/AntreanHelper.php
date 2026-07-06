<?php
/**
 * AntreanHelper.php
 * Kelas pembantu untuk logika bisnis sistem antrian
 * Dapat diuji secara terisolasi (unit test)
 */

namespace App;

class AntreanHelper
{
    /**
     * Validasi NIK (Nomor Induk Kependudukan)
     * @param string $nik
     * @return bool
     */
    public static function validateNIK(string $nik): bool
    {
        // NIK harus tepat 16 karakter dan semua digit
        return strlen($nik) === 16 && ctype_digit($nik);
    }

    /**
     * Generate nomor antrean berdasarkan nama poli dan urutan
     * Format: [huruf pertama nama poli]-[nomor 2 digit]
     * Contoh: P-01 (Poli Umum), G-03 (Gigi)
     *
     * @param string $nama_poli
     * @param int    $urutan     Nomor urut (mulai dari 1)
     * @return string
     */
    public static function generateNomorAntrean(string $nama_poli, int $urutan): string
    {
        if ($urutan < 1) {
            throw new \InvalidArgumentException('Urutan harus minimal 1');
        }
        $prefix = strtoupper(substr(trim($nama_poli), 0, 1));
        return $prefix . '-' . str_pad($urutan, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Validasi nama pasien
     * @param string $nama
     * @return bool
     */
    public static function validateNamaPasien(string $nama): bool
    {
        $nama = trim($nama);
        // Minimal 3 karakter, hanya huruf dan spasi
        return strlen($nama) >= 3 && preg_match('/^[a-zA-Z\s]+$/', $nama);
    }

    /**
     * Cek apakah masih ada kuota tersisa
     * @param int $terisi
     * @param int $maksimal
     * @return bool
     */
    public static function isKuotaAvailable(int $terisi, int $maksimal): bool
    {
        return $terisi < $maksimal;
    }

    /**
     * Hitung persentase kuota terisi
     * @param int $terisi
     * @param int $maksimal
     * @return float (0-100)
     */
    public static function getKuotaPercentage(int $terisi, int $maksimal): float
    {
        if ($maksimal <= 0) return 0.0;
        return min(100.0, round(($terisi / $maksimal) * 100, 2));
    }

    /**
     * Validasi format tanggal kunjungan (YYYY-MM-DD)
     * @param string $tanggal
     * @return bool
     */
    public static function validateTanggal(string $tanggal): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $tanggal);
        return $d && $d->format('Y-m-d') === $tanggal;
    }

    /**
     * Validasi status antrean
     * @param string $status
     * @return bool
     */
    public static function validateStatus(string $status): bool
    {
        return in_array($status, ['menunggu', 'dipanggil', 'selesai', 'lewat'], true);
    }
}

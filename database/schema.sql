-- ============================================================
-- Schema SQL — Sistem Antrian Puskesmas (uas_puskesmas)
-- Untuk GitHub Actions CI/CD & dokumentasi
-- ============================================================

CREATE DATABASE IF NOT EXISTS `uas_puskesmas`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `uas_puskesmas`;

-- Tabel poli
CREATE TABLE IF NOT EXISTS `poli` (
  `id_poli` int(11) NOT NULL AUTO_INCREMENT,
  `nama_poli` varchar(50) NOT NULL,
  `kuota_maksimal` int(11) NOT NULL DEFAULT 30,
  PRIMARY KEY (`id_poli`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel users (petugas loket)
CREATE TABLE IF NOT EXISTS `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'bcrypt hash',
  `nama_petugas` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel antrean
CREATE TABLE IF NOT EXISTS `antrean` (
  `id_antrean` int(11) NOT NULL AUTO_INCREMENT,
  `nama_pasien` varchar(100) NOT NULL,
  `nik` varchar(16) NOT NULL,
  `id_poli` int(11) NOT NULL,
  `nomor_antrean` varchar(10) NOT NULL,
  `tanggal_kunjungan` date NOT NULL,
  `status` enum('menunggu','dipanggil','selesai','lewat') DEFAULT 'menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_antrean`),
  KEY `id_poli` (`id_poli`),
  CONSTRAINT `fk_antrean_poli` FOREIGN KEY (`id_poli`) REFERENCES `poli` (`id_poli`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

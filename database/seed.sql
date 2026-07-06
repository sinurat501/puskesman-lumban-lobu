-- ============================================================
-- Seed Data — uas_puskesmas
-- Data awal untuk pengujian CI/CD dan demo
-- ============================================================

USE `uas_puskesmas`;

-- Data poli
INSERT INTO `poli` (`id_poli`, `nama_poli`, `kuota_maksimal`) VALUES
(1, 'Poli Umum', 50),
(2, 'Poli Gigi', 20),
(3, 'Kesehatan Ibu dan Anak (KIA)', 30)
ON DUPLICATE KEY UPDATE `nama_poli` = VALUES(`nama_poli`);

-- Data user admin (password: password)
-- Hash: $2y$10$wK1V.U.tXgLpP2tVvBohE.z14V96.yBwH4p.U5yH5wK7hYwW6L3y.
INSERT INTO `users` (`username`, `password`, `nama_petugas`) VALUES
('admin_loket', '$2y$10$wK1V.U.tXgLpP2tVvBohE.z14V96.yBwH4p.U5yH5wK7hYwW6L3y.', 'Petugas Loket UNPRI')
ON DUPLICATE KEY UPDATE `nama_petugas` = VALUES(`nama_petugas`);

-- Data antrean sample (hari yang sudah lewat, tidak mengganggu data real)
INSERT INTO `antrean` (`nama_pasien`, `nik`, `id_poli`, `nomor_antrean`, `tanggal_kunjungan`, `status`) VALUES
('Budi Santoso', '1234567890123456', 1, 'P-01', '2026-07-06', 'selesai'),
('Siti Aminah', '6543210987654321', 1, 'P-02', '2026-07-06', 'selesai'),
('Rian Wijaya', '1122334455667788', 2, 'P-01', '2026-07-06', 'menunggu')
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

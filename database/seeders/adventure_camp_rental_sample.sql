-- =========================================================
-- Adventure Camp Rental - Sample Database Dump
-- Sistem Informasi Penyewaan Alat Camping & Outdoor
-- Target Database: adventure_camp_rental
-- Generated: 2026-11-01 16:17:15
-- Kredensial Default: admin/admin123, pemilik/pemilik123, pelanggan/pelanggan123
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+07:00';

-- ---------------------------------------------------------
-- Struktur Tabel `users`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `id_pelanggan` int DEFAULT NULL,
  `nama_user` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Pemilik','Pelanggan','Pengguna') NOT NULL DEFAULT 'Pengguna',
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping Data untuk `users` (7 baris)
INSERT INTO `users` (`id_user`, `id_pelanggan`, `nama_user`, `username`, `password`, `role`, `status_aktif`, `remember_token`, `created_at`) VALUES ('1', NULL, 'Administrator', 'admin', '$2y$12$SMa9j0futPZwAQBQAvKTGeUiLfDpq7XawpeFZlRw1n/J/SOtXEejm', 'Admin', '1', NULL, '2026-10-04 18:52:45');
INSERT INTO `users` (`id_user`, `id_pelanggan`, `nama_user`, `username`, `password`, `role`, `status_aktif`, `remember_token`, `created_at`) VALUES ('2', NULL, 'Pemilik Adventure Camp', 'pemilik', '$2y$12$kqD2BlGRyyBMVpA4Sw9DPeL2rboJfEGF/C4M2j0o.o/1bvojy4RiC', 'Pemilik', '1', NULL, '2026-10-04 18:52:45');
INSERT INTO `users` (`id_user`, `id_pelanggan`, `nama_user`, `username`, `password`, `role`, `status_aktif`, `remember_token`, `created_at`) VALUES ('3', '1', 'Pelanggan Demo', 'pelanggan', '$2y$12$Uqgn0ZsPwo92OHGFTIP1zOaEp1UQYZB/y95p/r0oprXh7FXBnomU.', 'Pelanggan', '1', NULL, '2026-10-12 08:09:36');
INSERT INTO `users` (`id_user`, `id_pelanggan`, `nama_user`, `username`, `password`, `role`, `status_aktif`, `remember_token`, `created_at`) VALUES ('4', '7', 'Ahmad Fauzi', 'ahmadfauzi', '$2y$12$JRamRjO502abh5keQi1oCOwFsUgo23UtdAVmTk.X6r2Ux.EW0I/fO', 'Pengguna', '1', NULL, '2026-10-15 11:56:28');
INSERT INTO `users` (`id_user`, `id_pelanggan`, `nama_user`, `username`, `password`, `role`, `status_aktif`, `remember_token`, `created_at`) VALUES ('5', '8', 'Christoval Erock Dylan', 'erick', '$2y$12$w9WAKwGbvEu9BbbulGT95OvXJlvNMCw6pny/o0HiyPHCdg2WvAsYS', 'Pengguna', '1', NULL, '2026-10-17 10:28:12');
INSERT INTO `users` (`id_user`, `id_pelanggan`, `nama_user`, `username`, `password`, `role`, `status_aktif`, `remember_token`, `created_at`) VALUES ('6', '12', 'Alda Fuadiyah', 'alda', '$2y$12$hs9rlxXleIYeyHy5oq9.VuIlL2rRWkEp88bezHbzHn9aemcxxpeM.', 'Pengguna', '1', NULL, '2026-10-19 08:22:06');
INSERT INTO `users` (`id_user`, `id_pelanggan`, `nama_user`, `username`, `password`, `role`, `status_aktif`, `remember_token`, `created_at`) VALUES ('7', '14', 'Haritsa Gilba Lahya', 'gilba', '$2y$12$/F0ZOmRAdFeHvjX.PvlT4emf7Z1AGXr4NK5yh308RGtDo0h5/pz8O', 'Pengguna', '1', NULL, '2026-10-25 16:51:26');

-- ---------------------------------------------------------
-- Struktur Tabel `pelanggan`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `pelanggan`;
CREATE TABLE `pelanggan` (
  `id_pelanggan` int NOT NULL AUTO_INCREMENT,
  `nama_pelanggan` varchar(100) NOT NULL,
  `alamat` varchar(255) NOT NULL,
  `nomor_telepon` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pelanggan`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping Data untuk `pelanggan` (15 baris)
INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`) VALUES ('1', 'Andi Pratama', 'Jl. Soekarno Hatta No. 10, Malang', '081234567890', '2026-10-04 18:52:45');
INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`) VALUES ('2', 'Siti Aulia', 'Jl. Diponegoro No. 15, Batu', '083456789012', '2026-10-04 18:52:45');
INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`) VALUES ('3', 'Budi Santoso', 'Jl. Ijen No. 22, Malang', '085712345678', '2026-10-04 18:52:45');
INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`) VALUES ('4', 'Maharani', 'Batu', '098765432', '2026-10-05 15:22:22');
INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`) VALUES ('5', 'Maha', 'Batu', '0987654321', '2026-10-05 15:22:44');
INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`) VALUES ('6', 'Alda', 'Malang', '08657268', '2026-10-05 15:32:38');
INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`) VALUES ('7', 'Ahmad Fauzi', 'Jl. Danau Ranau No. 8, Sawojajar, Malang', '081399887766', '2026-10-15 11:56:27');
INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`) VALUES ('8', 'Christoval Erock Dylan', 'Alamat belum disetel', '085892214000', '2026-10-17 10:28:12');
INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`) VALUES ('9', 'Kuprit', 'JL. Anggrek No.14 Oro-oro ombo', '082937489299', '2026-10-17 21:04:14');
INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`) VALUES ('10', 'Rambo', 'Jl. Teratai No.1', '079476473865', '2026-10-18 17:27:33');
INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`) VALUES ('11', 'Yovi', 'Dampit', '087652345678', '2026-10-19 07:58:16');
INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`) VALUES ('12', 'Alda Fuadiyah', 'Sawojajar 2', '09876567892', '2026-10-19 08:22:06');
INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`) VALUES ('13', 'Cimi', 'Sawojajar', '09876567865', '2026-10-19 08:36:13');
INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`) VALUES ('14', 'Haritsa Gilba Lahya', 'JL. Mawar No.19', '0897528937', '2026-10-25 16:51:25');
INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`) VALUES ('15', 'Cimol', 'Songgokerto', '08765872938', '2026-10-25 17:21:36');

-- ---------------------------------------------------------
-- Struktur Tabel `alat`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `alat`;
CREATE TABLE `alat` (
  `kode_alat` varchar(10) NOT NULL,
  `nama_alat` varchar(100) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `stok` int NOT NULL DEFAULT '0',
  `kondisi` enum('Baik','Perlu Perbaikan','Rusak') NOT NULL DEFAULT 'Baik',
  `tarif_sewa` decimal(12,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`kode_alat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping Data untuk `alat` (5 baris)
INSERT INTO `alat` (`kode_alat`, `nama_alat`, `kategori`, `stok`, `kondisi`, `tarif_sewa`, `created_at`) VALUES ('ALT001', 'Tenda Dome 4 Orang', 'Tenda', '3', 'Baik', '50000.00', '2026-10-04 18:52:46');
INSERT INTO `alat` (`kode_alat`, `nama_alat`, `kategori`, `stok`, `kondisi`, `tarif_sewa`, `created_at`) VALUES ('ALT002', 'Sleeping Bag', 'Perlengkapan Tidur', '7', 'Baik', '25000.00', '2026-10-04 18:52:46');
INSERT INTO `alat` (`kode_alat`, `nama_alat`, `kategori`, `stok`, `kondisi`, `tarif_sewa`, `created_at`) VALUES ('ALT003', 'Carrier 60L', 'Tas', '3', 'Baik', '40000.00', '2026-10-04 18:52:46');
INSERT INTO `alat` (`kode_alat`, `nama_alat`, `kategori`, `stok`, `kondisi`, `tarif_sewa`, `created_at`) VALUES ('ALT004', 'Kompor Portable', 'Peralatan Masak', '1', 'Baik', '30000.00', '2026-10-04 18:52:46');
INSERT INTO `alat` (`kode_alat`, `nama_alat`, `kategori`, `stok`, `kondisi`, `tarif_sewa`, `created_at`) VALUES ('ALT005', 'Matras Camping', 'Perlengkapan Tidur', '3', 'Baik', '15000.00', '2026-10-04 18:52:46');

-- ---------------------------------------------------------
-- Struktur Tabel `supplier`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `supplier`;
CREATE TABLE `supplier` (
  `id_supplier` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_supplier` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kontak_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telepon` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_supplier`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping Data untuk `supplier` (1 baris)
INSERT INTO `supplier` (`id_supplier`, `nama_supplier`, `kontak_person`, `telepon`, `alamat`, `created_at`) VALUES ('1', 'Toko Cahaya Baru', NULL, '08927373646', 'Kota Surabaya', '2026-10-18 17:21:31');

-- ---------------------------------------------------------
-- Struktur Tabel `penyewaan`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `penyewaan`;
CREATE TABLE `penyewaan` (
  `no_transaksi` varchar(20) NOT NULL,
  `id_pelanggan` int NOT NULL,
  `id_user` int DEFAULT NULL,
  `tanggal_sewa` date NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `status_penyewaan` varchar(50) NOT NULL DEFAULT 'Menunggu Konfirmasi',
  `sumber_pesanan` varchar(20) NOT NULL DEFAULT 'online',
  PRIMARY KEY (`no_transaksi`),
  KEY `id_pelanggan` (`id_pelanggan`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `penyewaan_ibfk_1` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`),
  CONSTRAINT `penyewaan_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping Data untuk `penyewaan` (16 baris)
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261005-001', '5', '1', '2026-10-05', '2026-10-07', 'Selesai', 'online');
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261006-001', '6', '1', '2026-10-06', '2026-10-07', 'Selesai', 'online');
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261011-001', '5', '1', '2026-10-12', '2026-10-14', 'Selesai', 'online');
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261012-001', '3', '1', '2026-10-01', '2026-10-13', 'Selesai', 'online');
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261012-002', '2', '1', '2026-10-13', '2026-10-16', 'Selesai', 'online');
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261012-003', '5', '1', '2026-10-20', '2026-10-29', 'Selesai', 'online');
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261016-001', '1', '3', '2026-10-16', '2026-10-17', 'Selesai', 'online');
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261017-001', '8', '5', '2026-10-17', '2026-10-18', 'Selesai', 'online');
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261017-002', '9', NULL, '2026-10-17', '2026-10-18', 'Selesai', 'on-site');
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261018-001', '10', NULL, '2026-10-18', '2026-10-19', 'Selesai', 'on-site');
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261019-001', '8', '5', '2026-10-19', '2026-10-21', 'Selesai', 'online');
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261019-002', '11', NULL, '2026-10-19', '2026-10-20', 'Selesai', 'on-site');
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261019-003', '12', '6', '2026-10-19', '2026-10-22', 'Selesai', 'online');
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261019-004', '13', NULL, '2026-10-19', '2026-10-22', 'Sedang Disewa', 'on-site');
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261025-001', '14', '7', '2026-10-25', '2026-10-28', 'Sedang Disewa', 'online');
INSERT INTO `penyewaan` (`no_transaksi`, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`, `sumber_pesanan`) VALUES ('TRX-20261025-002', '15', NULL, '2026-10-25', '2026-10-30', 'Sedang Disewa', 'on-site');

-- ---------------------------------------------------------
-- Struktur Tabel `detail_penyewaan`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `detail_penyewaan`;
CREATE TABLE `detail_penyewaan` (
  `id_detail` int NOT NULL AUTO_INCREMENT,
  `no_transaksi` varchar(20) NOT NULL,
  `kode_alat` varchar(10) NOT NULL,
  `jumlah` int NOT NULL,
  `tarif_sewa` decimal(12,2) NOT NULL,
  `jumlah_diserahkan` int DEFAULT '0',
  `jumlah_kembali_baik` int DEFAULT '0',
  `jumlah_perlu_perbaikan` int DEFAULT '0',
  `jumlah_diperbaiki` int DEFAULT '0',
  `jumlah_rusak` int DEFAULT '0',
  PRIMARY KEY (`id_detail`),
  KEY `no_transaksi` (`no_transaksi`),
  KEY `kode_alat` (`kode_alat`),
  CONSTRAINT `detail_penyewaan_ibfk_1` FOREIGN KEY (`no_transaksi`) REFERENCES `penyewaan` (`no_transaksi`) ON DELETE CASCADE,
  CONSTRAINT `detail_penyewaan_ibfk_2` FOREIGN KEY (`kode_alat`) REFERENCES `alat` (`kode_alat`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping Data untuk `detail_penyewaan` (44 baris)
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('1', 'TRX-20261005-001', 'ALT001', '1', '50000.00', '0', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('2', 'TRX-20261005-001', 'ALT002', '2', '25000.00', '0', '2', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('3', 'TRX-20261005-001', 'ALT003', '4', '40000.00', '0', '4', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('4', 'TRX-20261005-001', 'ALT004', '1', '30000.00', '0', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('5', 'TRX-20261005-001', 'ALT005', '1', '15000.00', '0', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('6', 'TRX-20261006-001', 'ALT001', '4', '50000.00', '0', '0', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('7', 'TRX-20261006-001', 'ALT002', '4', '25000.00', '0', '0', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('8', 'TRX-20261011-001', 'ALT001', '1', '50000.00', '0', '0', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('9', 'TRX-20261012-001', 'ALT002', '3', '25000.00', '0', '3', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('10', 'TRX-20261012-001', 'ALT002', '1', '25000.00', '0', '0', '0', '0', '1');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('11', 'TRX-20261012-002', 'ALT001', '1', '50000.00', '0', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('12', 'TRX-20261012-002', 'ALT001', '1', '50000.00', '0', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('13', 'TRX-20261012-003', 'ALT001', '1', '50000.00', '0', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('14', 'TRX-20261016-001', 'ALT004', '2', '30000.00', '2', '2', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('15', 'TRX-20261017-001', 'ALT005', '2', '15000.00', '2', '2', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('16', 'TRX-20261017-002', 'ALT001', '1', '50000.00', '1', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('17', 'TRX-20261017-002', 'ALT002', '4', '25000.00', '4', '4', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('18', 'TRX-20261017-002', 'ALT004', '1', '30000.00', '1', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('19', 'TRX-20261017-002', 'ALT005', '2', '15000.00', '2', '2', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('20', 'TRX-20261018-001', 'ALT001', '1', '50000.00', '1', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('21', 'TRX-20261018-001', 'ALT002', '3', '25000.00', '3', '3', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('22', 'TRX-20261018-001', 'ALT003', '3', '40000.00', '3', '3', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('23', 'TRX-20261018-001', 'ALT004', '1', '30000.00', '1', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('24', 'TRX-20261018-001', 'ALT005', '3', '15000.00', '3', '3', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('25', 'TRX-20261019-001', 'ALT001', '1', '50000.00', '1', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('26', 'TRX-20261019-001', 'ALT003', '1', '40000.00', '1', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('27', 'TRX-20261019-001', 'ALT004', '1', '30000.00', '1', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('28', 'TRX-20261019-001', 'ALT005', '1', '15000.00', '1', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('29', 'TRX-20261019-002', 'ALT002', '1', '25000.00', '1', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('30', 'TRX-20261019-002', 'ALT004', '1', '30000.00', '1', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('31', 'TRX-20261019-002', 'ALT005', '1', '15000.00', '1', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('32', 'TRX-20261019-003', 'ALT002', '1', '25000.00', '1', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('33', 'TRX-20261019-003', 'ALT003', '1', '40000.00', '1', '1', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('34', 'TRX-20261019-004', 'ALT004', '2', '30000.00', '2', '0', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('35', 'TRX-20261019-004', 'ALT005', '1', '15000.00', '1', '0', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('36', 'TRX-20261025-001', 'ALT001', '1', '50000.00', '1', '0', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('37', 'TRX-20261025-001', 'ALT002', '2', '25000.00', '2', '0', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('38', 'TRX-20261025-001', 'ALT003', '2', '40000.00', '2', '0', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('39', 'TRX-20261025-001', 'ALT004', '1', '30000.00', '1', '0', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('40', 'TRX-20261025-001', 'ALT005', '1', '15000.00', '1', '0', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('41', 'TRX-20261025-002', 'ALT001', '1', '50000.00', '1', '0', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('42', 'TRX-20261025-002', 'ALT003', '4', '40000.00', '4', '0', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('43', 'TRX-20261025-002', 'ALT004', '1', '30000.00', '1', '0', '0', '0', '0');
INSERT INTO `detail_penyewaan` (`id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`, `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`) VALUES ('44', 'TRX-20261025-002', 'ALT005', '4', '15000.00', '4', '0', '0', '0', '0');

-- ---------------------------------------------------------
-- Struktur Tabel `pembayaran`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `pembayaran`;
CREATE TABLE `pembayaran` (
  `no_pembayaran` varchar(20) NOT NULL,
  `no_transaksi` varchar(20) NOT NULL,
  `total_biaya` decimal(12,2) NOT NULL,
  `jumlah_pembayaran` decimal(12,2) NOT NULL,
  `metode_pembayaran` varchar(50) NOT NULL DEFAULT 'Cash',
  `tanggal_pembayaran` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status_pembayaran` varchar(50) NOT NULL DEFAULT 'Belum Lunas',
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `waktu_unggah_bukti` timestamp NULL DEFAULT NULL,
  `id_admin` bigint unsigned DEFAULT NULL,
  `waktu_konfirmasi` timestamp NULL DEFAULT NULL,
  `alasan_penolakan` text,
  PRIMARY KEY (`no_pembayaran`),
  UNIQUE KEY `no_transaksi` (`no_transaksi`),
  CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`no_transaksi`) REFERENCES `penyewaan` (`no_transaksi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping Data untuk `pembayaran` (16 baris)
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261011-001', 'TRX-20261005-001', '610000.00', '610000.00', 'Cash', '2026-10-11 22:45:19', 'Lunas', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261016-001', 'TRX-20261016-001', '60000.00', '60000.00', 'QRIS', '2026-10-16 22:26:11', 'Lunas', NULL, NULL, '1', '2026-10-16 22:29:46', NULL);
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261016-002', 'TRX-20261012-002', '300000.00', '300000.00', 'Transfer Bank', '2026-10-16 22:29:58', 'Lunas', NULL, NULL, '1', '2026-10-16 22:29:58', NULL);
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261016-003', 'TRX-20261011-001', '100000.00', '100000.00', 'Transfer Bank', '2026-10-16 22:30:07', 'Lunas', NULL, NULL, '1', '2026-10-16 22:30:07', NULL);
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261016-004', 'TRX-20261006-001', '300000.00', '300000.00', 'Transfer Bank', '2026-10-16 22:30:13', 'Lunas', NULL, NULL, '1', '2026-10-16 22:30:13', NULL);
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261017-001', 'TRX-20261017-001', '30000.00', '30000.00', 'QRIS', '2026-10-17 14:49:35', 'Lunas', NULL, NULL, '1', '2026-10-17 20:56:55', NULL);
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261017-002', 'TRX-20261017-002', '210000.00', '210000.00', 'Cash', '2026-10-17 21:04:14', 'Lunas', NULL, NULL, '1', '2026-10-17 21:05:03', NULL);
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261018-001', 'TRX-20261012-001', '1200000.00', '1200000.00', 'Transfer Bank', '2026-10-18 16:53:25', 'Lunas', NULL, NULL, '1', '2026-10-18 16:53:25', NULL);
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261018-002', 'TRX-20261018-001', '320000.00', '320000.00', 'Transfer Bank', '2026-10-18 17:27:33', 'Lunas', NULL, NULL, '1', '2026-10-18 17:33:12', NULL);
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261018-003', 'TRX-20261012-003', '450000.00', '450000.00', 'Transfer Bank', '2026-10-18 17:55:27', 'Lunas', NULL, NULL, '1', '2026-10-18 17:55:27', NULL);
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261019-001', 'TRX-20261019-001', '270000.00', '270000.00', 'QRIS', '2026-10-19 07:53:58', 'Lunas', NULL, NULL, '1', '2026-10-19 07:55:21', NULL);
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261019-002', 'TRX-20261019-002', '70000.00', '70000.00', 'Cash', '2026-10-19 07:58:17', 'Lunas', NULL, NULL, '1', '2026-10-19 07:58:57', NULL);
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261019-003', 'TRX-20261019-003', '195000.00', '195000.00', 'Cash', '2026-10-19 08:23:51', 'Lunas', NULL, NULL, '1', '2026-10-19 08:24:34', NULL);
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261019-004', 'TRX-20261019-004', '225000.00', '225000.00', 'QRIS', '2026-10-19 08:36:13', 'Lunas', NULL, NULL, '1', '2026-10-19 08:36:33', NULL);
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261025-001', 'TRX-20261025-001', '675000.00', '675000.00', 'Transfer Bank', '2026-10-25 16:55:36', 'Lunas', NULL, NULL, '1', '2026-10-25 17:00:03', NULL);
INSERT INTO `pembayaran` (`no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`, `bukti_pembayaran`, `waktu_unggah_bukti`, `id_admin`, `waktu_konfirmasi`, `alasan_penolakan`) VALUES ('PAY-20261025-002', 'TRX-20261025-002', '1500000.00', '1500000.00', 'Cash', '2026-10-25 17:21:37', 'Lunas', NULL, NULL, '1', '2026-10-25 17:24:09', NULL);

-- ---------------------------------------------------------
-- Struktur Tabel `pengambilan`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `pengambilan`;
CREATE TABLE `pengambilan` (
  `id_pengambilan` int unsigned NOT NULL AUTO_INCREMENT,
  `no_transaksi` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_pengambilan` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_admin` bigint unsigned DEFAULT NULL,
  `nama_penerima` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_pengambilan`),
  UNIQUE KEY `pengambilan_no_transaksi_unique` (`no_transaksi`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping Data untuk `pengambilan` (10 baris)
INSERT INTO `pengambilan` (`id_pengambilan`, `no_transaksi`, `tanggal_pengambilan`, `id_admin`, `nama_penerima`, `catatan`) VALUES ('1', 'TRX-20261016-001', '2026-10-17 21:13:00', '1', 'Andi Pratama', NULL);
INSERT INTO `pengambilan` (`id_pengambilan`, `no_transaksi`, `tanggal_pengambilan`, `id_admin`, `nama_penerima`, `catatan`) VALUES ('2', 'TRX-20261017-001', '2026-10-19 08:17:00', '1', 'Christoval Erock Dylan', NULL);
INSERT INTO `pengambilan` (`id_pengambilan`, `no_transaksi`, `tanggal_pengambilan`, `id_admin`, `nama_penerima`, `catatan`) VALUES ('3', 'TRX-20261017-002', '2026-10-19 08:17:00', '1', 'Kuprit', NULL);
INSERT INTO `pengambilan` (`id_pengambilan`, `no_transaksi`, `tanggal_pengambilan`, `id_admin`, `nama_penerima`, `catatan`) VALUES ('4', 'TRX-20261018-001', '2026-10-19 08:17:00', '1', 'Rambo', NULL);
INSERT INTO `pengambilan` (`id_pengambilan`, `no_transaksi`, `tanggal_pengambilan`, `id_admin`, `nama_penerima`, `catatan`) VALUES ('5', 'TRX-20261019-001', '2026-10-19 08:17:00', '1', 'Christoval Erock Dylan', NULL);
INSERT INTO `pengambilan` (`id_pengambilan`, `no_transaksi`, `tanggal_pengambilan`, `id_admin`, `nama_penerima`, `catatan`) VALUES ('6', 'TRX-20261019-002', '2026-10-19 08:17:00', '1', 'Yovi', NULL);
INSERT INTO `pengambilan` (`id_pengambilan`, `no_transaksi`, `tanggal_pengambilan`, `id_admin`, `nama_penerima`, `catatan`) VALUES ('7', 'TRX-20261019-003', '2026-10-19 08:24:00', '1', 'Alda Fuadiyah', NULL);
INSERT INTO `pengambilan` (`id_pengambilan`, `no_transaksi`, `tanggal_pengambilan`, `id_admin`, `nama_penerima`, `catatan`) VALUES ('8', 'TRX-20261019-004', '2026-10-19 08:36:00', '1', 'Cimi', NULL);
INSERT INTO `pengambilan` (`id_pengambilan`, `no_transaksi`, `tanggal_pengambilan`, `id_admin`, `nama_penerima`, `catatan`) VALUES ('9', 'TRX-20261025-001', '2026-10-25 17:03:00', '1', 'Haritsa Gilba Lahya', NULL);
INSERT INTO `pengambilan` (`id_pengambilan`, `no_transaksi`, `tanggal_pengambilan`, `id_admin`, `nama_penerima`, `catatan`) VALUES ('10', 'TRX-20261025-002', '2026-10-25 17:25:00', '1', 'Cimol', NULL);

-- ---------------------------------------------------------
-- Struktur Tabel `pengembalian`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `pengembalian`;
CREATE TABLE `pengembalian` (
  `id_pengembalian` int NOT NULL AUTO_INCREMENT,
  `no_transaksi` varchar(20) NOT NULL,
  `tanggal_pengembalian_aktual` date NOT NULL,
  `kondisi_alat` varchar(100) NOT NULL,
  `status_pengembalian` enum('Selesai','Bermasalah') NOT NULL,
  `id_admin` bigint unsigned DEFAULT NULL,
  `catatan` text,
  PRIMARY KEY (`id_pengembalian`),
  UNIQUE KEY `no_transaksi` (`no_transaksi`),
  CONSTRAINT `pengembalian_ibfk_1` FOREIGN KEY (`no_transaksi`) REFERENCES `penyewaan` (`no_transaksi`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping Data untuk `pengembalian` (13 baris)
INSERT INTO `pengembalian` (`id_pengembalian`, `no_transaksi`, `tanggal_pengembalian_aktual`, `kondisi_alat`, `status_pengembalian`, `id_admin`, `catatan`) VALUES ('1', 'TRX-20261006-001', '2026-10-11', 'Kondisi baik dan lengkap', 'Selesai', NULL, NULL);
INSERT INTO `pengembalian` (`id_pengembalian`, `no_transaksi`, `tanggal_pengembalian_aktual`, `kondisi_alat`, `status_pengembalian`, `id_admin`, `catatan`) VALUES ('2', 'TRX-20261011-001', '2026-10-12', 'Baik', 'Selesai', NULL, NULL);
INSERT INTO `pengembalian` (`id_pengembalian`, `no_transaksi`, `tanggal_pengembalian_aktual`, `kondisi_alat`, `status_pengembalian`, `id_admin`, `catatan`) VALUES ('3', 'TRX-20261005-001', '2026-10-16', 'ALT001: 1 Baik; ALT002: 2 Baik; ALT003: 4 Baik; ALT004: 1 Baik; ALT005: 1 Baik', 'Bermasalah', '1', 'Baik Semua');
INSERT INTO `pengembalian` (`id_pengembalian`, `no_transaksi`, `tanggal_pengembalian_aktual`, `kondisi_alat`, `status_pengembalian`, `id_admin`, `catatan`) VALUES ('4', 'TRX-20261012-002', '2026-10-16', 'ALT001: 1 Baik; ALT001: 1 Baik', 'Selesai', '1', NULL);
INSERT INTO `pengembalian` (`id_pengembalian`, `no_transaksi`, `tanggal_pengembalian_aktual`, `kondisi_alat`, `status_pengembalian`, `id_admin`, `catatan`) VALUES ('5', 'TRX-20261016-001', '2026-10-17', 'ALT004: 2 Baik', 'Selesai', '1', NULL);
INSERT INTO `pengembalian` (`id_pengembalian`, `no_transaksi`, `tanggal_pengembalian_aktual`, `kondisi_alat`, `status_pengembalian`, `id_admin`, `catatan`) VALUES ('6', 'TRX-20261012-001', '2026-10-18', 'ALT002: 3 Baik; ALT002: 1 Rusak', 'Bermasalah', '1', NULL);
INSERT INTO `pengembalian` (`id_pengembalian`, `no_transaksi`, `tanggal_pengembalian_aktual`, `kondisi_alat`, `status_pengembalian`, `id_admin`, `catatan`) VALUES ('7', 'TRX-20261012-003', '2026-10-29', 'ALT001: 1 Baik', 'Selesai', '1', NULL);
INSERT INTO `pengembalian` (`id_pengembalian`, `no_transaksi`, `tanggal_pengembalian_aktual`, `kondisi_alat`, `status_pengembalian`, `id_admin`, `catatan`) VALUES ('8', 'TRX-20261017-001', '2026-10-19', 'ALT005: 2 Baik', 'Bermasalah', '1', NULL);
INSERT INTO `pengembalian` (`id_pengembalian`, `no_transaksi`, `tanggal_pengembalian_aktual`, `kondisi_alat`, `status_pengembalian`, `id_admin`, `catatan`) VALUES ('9', 'TRX-20261017-002', '2026-10-19', 'ALT001: 1 Baik; ALT002: 4 Baik; ALT004: 1 Baik; ALT005: 2 Baik', 'Bermasalah', '1', NULL);
INSERT INTO `pengembalian` (`id_pengembalian`, `no_transaksi`, `tanggal_pengembalian_aktual`, `kondisi_alat`, `status_pengembalian`, `id_admin`, `catatan`) VALUES ('10', 'TRX-20261018-001', '2026-10-19', 'ALT001: 1 Baik; ALT002: 3 Baik; ALT003: 3 Baik; ALT004: 1 Baik; ALT005: 3 Baik', 'Selesai', '1', NULL);
INSERT INTO `pengembalian` (`id_pengembalian`, `no_transaksi`, `tanggal_pengembalian_aktual`, `kondisi_alat`, `status_pengembalian`, `id_admin`, `catatan`) VALUES ('11', 'TRX-20261019-001', '2026-10-25', 'ALT001: 1 Baik; ALT003: 1 Baik; ALT004: 1 Baik; ALT005: 1 Baik', 'Bermasalah', '1', NULL);
INSERT INTO `pengembalian` (`id_pengembalian`, `no_transaksi`, `tanggal_pengembalian_aktual`, `kondisi_alat`, `status_pengembalian`, `id_admin`, `catatan`) VALUES ('12', 'TRX-20261019-002', '2026-10-25', 'ALT002: 1 Baik; ALT004: 1 Baik; ALT005: 1 Baik', 'Bermasalah', '1', NULL);
INSERT INTO `pengembalian` (`id_pengembalian`, `no_transaksi`, `tanggal_pengembalian_aktual`, `kondisi_alat`, `status_pengembalian`, `id_admin`, `catatan`) VALUES ('13', 'TRX-20261019-003', '2026-10-25', 'ALT002: 1 Baik; ALT003: 1 Baik', 'Bermasalah', '1', NULL);

-- ---------------------------------------------------------
-- Struktur Tabel `denda`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `denda`;
CREATE TABLE `denda` (
  `id_denda` int NOT NULL AUTO_INCREMENT,
  `id_pengembalian` int NOT NULL,
  `tarif_denda` decimal(12,2) NOT NULL DEFAULT '0.00',
  `nominal_denda` decimal(12,2) NOT NULL DEFAULT '0.00',
  `alasan_denda` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_denda`),
  UNIQUE KEY `id_pengembalian` (`id_pengembalian`),
  CONSTRAINT `denda_ibfk_1` FOREIGN KEY (`id_pengembalian`) REFERENCES `pengembalian` (`id_pengembalian`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping Data untuk `denda` (8 baris)
INSERT INTO `denda` (`id_denda`, `id_pengembalian`, `tarif_denda`, `nominal_denda`, `alasan_denda`) VALUES ('1', '2', '25.00', '25.00', '');
INSERT INTO `denda` (`id_denda`, `id_pengembalian`, `tarif_denda`, `nominal_denda`, `alasan_denda`) VALUES ('2', '3', '2745000.00', '2745000.00', 'Keterlambatan 9 hari dari batas jadwal kembali.');
INSERT INTO `denda` (`id_denda`, `id_pengembalian`, `tarif_denda`, `nominal_denda`, `alasan_denda`) VALUES ('3', '6', '500000.00', '500000.00', 'Keterlambatan 5 hari dari batas jadwal kembali.');
INSERT INTO `denda` (`id_denda`, `id_pengembalian`, `tarif_denda`, `nominal_denda`, `alasan_denda`) VALUES ('4', '8', '30000.00', '30000.00', 'Keterlambatan 1 hari dari batas jadwal kembali.');
INSERT INTO `denda` (`id_denda`, `id_pengembalian`, `tarif_denda`, `nominal_denda`, `alasan_denda`) VALUES ('5', '9', '210000.00', '210000.00', 'Keterlambatan 1 hari dari batas jadwal kembali.');
INSERT INTO `denda` (`id_denda`, `id_pengembalian`, `tarif_denda`, `nominal_denda`, `alasan_denda`) VALUES ('6', '11', '540000.00', '540000.00', 'Keterlambatan 4 hari dari batas jadwal kembali.');
INSERT INTO `denda` (`id_denda`, `id_pengembalian`, `tarif_denda`, `nominal_denda`, `alasan_denda`) VALUES ('7', '12', '350000.00', '350000.00', 'Keterlambatan 5 hari dari batas jadwal kembali.');
INSERT INTO `denda` (`id_denda`, `id_pengembalian`, `tarif_denda`, `nominal_denda`, `alasan_denda`) VALUES ('8', '13', '195000.00', '195000.00', 'Keterlambatan 3 hari dari batas jadwal kembali.');

-- ---------------------------------------------------------
-- Struktur Tabel `riwayat_stok`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `riwayat_stok`;
CREATE TABLE `riwayat_stok` (
  `id_log` int unsigned NOT NULL AUTO_INCREMENT,
  `kode_alat` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_perubahan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stok_sebelum` int NOT NULL,
  `jumlah_perubahan` int NOT NULL,
  `stok_sesudah` int NOT NULL,
  `alasan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_user` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `riwayat_stok_kode_alat_index` (`kode_alat`),
  KEY `riwayat_stok_jenis_perubahan_index` (`jenis_perubahan`),
  KEY `riwayat_stok_id_user_index` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping Data untuk `riwayat_stok` (61 baris)
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('1', 'ALT001', 'Pengembalian Sewa', '1', '1', '2', 'Pengembalian pesanan TRX-20261005-001 (1 Baik)', '1', '2026-10-16 22:24:42');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('2', 'ALT002', 'Pengembalian Sewa', '4', '2', '6', 'Pengembalian pesanan TRX-20261005-001 (2 Baik)', '1', '2026-10-16 22:24:42');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('3', 'ALT003', 'Pengembalian Sewa', '5', '4', '9', 'Pengembalian pesanan TRX-20261005-001 (4 Baik)', '1', '2026-10-16 22:24:42');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('4', 'ALT004', 'Pengembalian Sewa', '2', '1', '3', 'Pengembalian pesanan TRX-20261005-001 (1 Baik)', '1', '2026-10-16 22:24:42');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('5', 'ALT005', 'Pengembalian Sewa', '7', '1', '8', 'Pengembalian pesanan TRX-20261005-001 (1 Baik)', '1', '2026-10-16 22:24:42');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('6', 'ALT001', 'Pengembalian Sewa', '2', '1', '3', 'Pengembalian pesanan TRX-20261012-002 (1 Baik)', '1', '2026-10-16 22:24:53');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('7', 'ALT001', 'Pengembalian Sewa', '3', '1', '4', 'Pengembalian pesanan TRX-20261012-002 (1 Baik)', '1', '2026-10-16 22:24:53');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('8', 'ALT004', 'Serah Terima Sewa', '3', '-2', '1', 'Penyerahan sewa pesanan TRX-20261016-001 kepada Andi Pratama', '1', '2026-10-17 21:14:11');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('9', 'ALT004', 'Pengembalian Sewa', '1', '2', '3', 'Pengembalian pesanan TRX-20261016-001 (2 Baik)', '1', '2026-10-17 21:14:27');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('10', 'ALT002', 'Pengembalian Sewa', '6', '3', '9', 'Pengembalian pesanan TRX-20261012-001 (3 Baik)', '1', '2026-10-18 16:53:04');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('11', 'ALT004', 'Pembelian Supplier', '3', '2', '5', 'Pengadaan stok 2 unit via PO-20261018-001 dari Toko Cahaya Baru', '1', '2026-10-18 17:21:31');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('12', 'ALT001', 'Pengembalian Sewa', '4', '1', '5', 'Pengembalian pesanan TRX-20261012-003 (1 Baik)', '1', '2026-10-18 17:33:57');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('13', 'ALT005', 'Serah Terima Sewa', '8', '-2', '6', 'Penyerahan sewa pesanan TRX-20261017-001 kepada Christoval Erock Dylan', '1', '2026-10-19 08:17:31');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('14', 'ALT001', 'Serah Terima Sewa', '5', '-1', '4', 'Penyerahan sewa pesanan TRX-20261017-002 kepada Kuprit', '1', '2026-10-19 08:17:35');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('15', 'ALT002', 'Serah Terima Sewa', '9', '-4', '5', 'Penyerahan sewa pesanan TRX-20261017-002 kepada Kuprit', '1', '2026-10-19 08:17:35');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('16', 'ALT004', 'Serah Terima Sewa', '5', '-1', '4', 'Penyerahan sewa pesanan TRX-20261017-002 kepada Kuprit', '1', '2026-10-19 08:17:35');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('17', 'ALT005', 'Serah Terima Sewa', '6', '-2', '4', 'Penyerahan sewa pesanan TRX-20261017-002 kepada Kuprit', '1', '2026-10-19 08:17:35');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('18', 'ALT001', 'Serah Terima Sewa', '4', '-1', '3', 'Penyerahan sewa pesanan TRX-20261018-001 kepada Rambo', '1', '2026-10-19 08:17:43');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('19', 'ALT002', 'Serah Terima Sewa', '5', '-3', '2', 'Penyerahan sewa pesanan TRX-20261018-001 kepada Rambo', '1', '2026-10-19 08:17:43');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('20', 'ALT003', 'Serah Terima Sewa', '9', '-3', '6', 'Penyerahan sewa pesanan TRX-20261018-001 kepada Rambo', '1', '2026-10-19 08:17:43');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('21', 'ALT004', 'Serah Terima Sewa', '4', '-1', '3', 'Penyerahan sewa pesanan TRX-20261018-001 kepada Rambo', '1', '2026-10-19 08:17:43');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('22', 'ALT005', 'Serah Terima Sewa', '4', '-3', '1', 'Penyerahan sewa pesanan TRX-20261018-001 kepada Rambo', '1', '2026-10-19 08:17:43');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('23', 'ALT001', 'Serah Terima Sewa', '3', '-1', '2', 'Penyerahan sewa pesanan TRX-20261019-001 kepada Christoval Erock Dylan', '1', '2026-10-19 08:17:47');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('24', 'ALT003', 'Serah Terima Sewa', '6', '-1', '5', 'Penyerahan sewa pesanan TRX-20261019-001 kepada Christoval Erock Dylan', '1', '2026-10-19 08:17:47');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('25', 'ALT004', 'Serah Terima Sewa', '3', '-1', '2', 'Penyerahan sewa pesanan TRX-20261019-001 kepada Christoval Erock Dylan', '1', '2026-10-19 08:17:47');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('26', 'ALT005', 'Serah Terima Sewa', '1', '-1', '0', 'Penyerahan sewa pesanan TRX-20261019-001 kepada Christoval Erock Dylan', '1', '2026-10-19 08:17:47');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('27', 'ALT002', 'Serah Terima Sewa', '2', '-1', '1', 'Penyerahan sewa pesanan TRX-20261019-002 kepada Yovi', '1', '2026-10-19 08:17:49');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('28', 'ALT004', 'Serah Terima Sewa', '2', '-1', '1', 'Penyerahan sewa pesanan TRX-20261019-002 kepada Yovi', '1', '2026-10-19 08:17:49');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('29', 'ALT005', 'Serah Terima Sewa', '0', '-1', '0', 'Penyerahan sewa pesanan TRX-20261019-002 kepada Yovi', '1', '2026-10-19 08:17:49');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('30', 'ALT005', 'Pengembalian Sewa', '0', '2', '2', 'Pengembalian pesanan TRX-20261017-001 (2 Baik)', '1', '2026-10-19 08:17:58');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('31', 'ALT001', 'Pengembalian Sewa', '2', '1', '3', 'Pengembalian pesanan TRX-20261017-002 (1 Baik)', '1', '2026-10-19 08:18:02');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('32', 'ALT002', 'Pengembalian Sewa', '1', '4', '5', 'Pengembalian pesanan TRX-20261017-002 (4 Baik)', '1', '2026-10-19 08:18:02');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('33', 'ALT004', 'Pengembalian Sewa', '1', '1', '2', 'Pengembalian pesanan TRX-20261017-002 (1 Baik)', '1', '2026-10-19 08:18:02');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('34', 'ALT005', 'Pengembalian Sewa', '2', '2', '4', 'Pengembalian pesanan TRX-20261017-002 (2 Baik)', '1', '2026-10-19 08:18:02');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('35', 'ALT001', 'Pengembalian Sewa', '3', '1', '4', 'Pengembalian pesanan TRX-20261018-001 (1 Baik)', '1', '2026-10-19 08:18:06');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('36', 'ALT002', 'Pengembalian Sewa', '5', '3', '8', 'Pengembalian pesanan TRX-20261018-001 (3 Baik)', '1', '2026-10-19 08:18:06');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('37', 'ALT003', 'Pengembalian Sewa', '5', '3', '8', 'Pengembalian pesanan TRX-20261018-001 (3 Baik)', '1', '2026-10-19 08:18:06');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('38', 'ALT004', 'Pengembalian Sewa', '2', '1', '3', 'Pengembalian pesanan TRX-20261018-001 (1 Baik)', '1', '2026-10-19 08:18:06');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('39', 'ALT005', 'Pengembalian Sewa', '4', '3', '7', 'Pengembalian pesanan TRX-20261018-001 (3 Baik)', '1', '2026-10-19 08:18:06');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('40', 'ALT002', 'Serah Terima Sewa', '8', '-1', '7', 'Penyerahan sewa pesanan TRX-20261019-003 kepada Alda Fuadiyah', '1', '2026-10-19 08:24:54');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('41', 'ALT003', 'Serah Terima Sewa', '8', '-1', '7', 'Penyerahan sewa pesanan TRX-20261019-003 kepada Alda Fuadiyah', '1', '2026-10-19 08:24:54');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('42', 'ALT004', 'Serah Terima Sewa', '3', '-2', '1', 'Penyerahan sewa pesanan TRX-20261019-004 kepada Cimi', '1', '2026-10-19 08:36:38');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('43', 'ALT005', 'Serah Terima Sewa', '7', '-1', '6', 'Penyerahan sewa pesanan TRX-20261019-004 kepada Cimi', '1', '2026-10-19 08:36:38');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('44', 'ALT001', 'Pengembalian Sewa', '4', '1', '5', 'Pengembalian pesanan TRX-20261019-001 (1 Baik)', '1', '2026-10-25 16:31:56');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('45', 'ALT003', 'Pengembalian Sewa', '7', '1', '8', 'Pengembalian pesanan TRX-20261019-001 (1 Baik)', '1', '2026-10-25 16:31:56');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('46', 'ALT004', 'Pengembalian Sewa', '1', '1', '2', 'Pengembalian pesanan TRX-20261019-001 (1 Baik)', '1', '2026-10-25 16:31:57');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('47', 'ALT005', 'Pengembalian Sewa', '6', '1', '7', 'Pengembalian pesanan TRX-20261019-001 (1 Baik)', '1', '2026-10-25 16:31:57');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('48', 'ALT002', 'Pengembalian Sewa', '7', '1', '8', 'Pengembalian pesanan TRX-20261019-002 (1 Baik)', '1', '2026-10-25 16:32:22');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('49', 'ALT004', 'Pengembalian Sewa', '2', '1', '3', 'Pengembalian pesanan TRX-20261019-002 (1 Baik)', '1', '2026-10-25 16:32:22');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('50', 'ALT005', 'Pengembalian Sewa', '7', '1', '8', 'Pengembalian pesanan TRX-20261019-002 (1 Baik)', '1', '2026-10-25 16:32:22');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('51', 'ALT002', 'Pengembalian Sewa', '8', '1', '9', 'Pengembalian pesanan TRX-20261019-003 (1 Baik)', '1', '2026-10-25 16:32:27');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('52', 'ALT003', 'Pengembalian Sewa', '8', '1', '9', 'Pengembalian pesanan TRX-20261019-003 (1 Baik)', '1', '2026-10-25 16:32:27');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('53', 'ALT001', 'Serah Terima Sewa', '5', '-1', '4', 'Penyerahan sewa pesanan TRX-20261025-001 kepada Haritsa Gilba Lahya', '1', '2026-10-25 17:04:33');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('54', 'ALT002', 'Serah Terima Sewa', '9', '-2', '7', 'Penyerahan sewa pesanan TRX-20261025-001 kepada Haritsa Gilba Lahya', '1', '2026-10-25 17:04:33');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('55', 'ALT003', 'Serah Terima Sewa', '9', '-2', '7', 'Penyerahan sewa pesanan TRX-20261025-001 kepada Haritsa Gilba Lahya', '1', '2026-10-25 17:04:33');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('56', 'ALT004', 'Serah Terima Sewa', '3', '-1', '2', 'Penyerahan sewa pesanan TRX-20261025-001 kepada Haritsa Gilba Lahya', '1', '2026-10-25 17:04:33');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('57', 'ALT005', 'Serah Terima Sewa', '8', '-1', '7', 'Penyerahan sewa pesanan TRX-20261025-001 kepada Haritsa Gilba Lahya', '1', '2026-10-25 17:04:33');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('58', 'ALT001', 'Serah Terima Sewa', '4', '-1', '3', 'Penyerahan sewa pesanan TRX-20261025-002 kepada Cimol', '1', '2026-10-25 17:26:27');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('59', 'ALT003', 'Serah Terima Sewa', '7', '-4', '3', 'Penyerahan sewa pesanan TRX-20261025-002 kepada Cimol', '1', '2026-10-25 17:26:27');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('60', 'ALT004', 'Serah Terima Sewa', '2', '-1', '1', 'Penyerahan sewa pesanan TRX-20261025-002 kepada Cimol', '1', '2026-10-25 17:26:27');
INSERT INTO `riwayat_stok` (`id_log`, `kode_alat`, `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user`, `created_at`) VALUES ('61', 'ALT005', 'Serah Terima Sewa', '7', '-4', '3', 'Penyerahan sewa pesanan TRX-20261025-002 kepada Cimol', '1', '2026-10-25 17:26:27');

-- ---------------------------------------------------------
-- Struktur Tabel `pembelian_inventaris`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `pembelian_inventaris`;
CREATE TABLE `pembelian_inventaris` (
  `no_pembelian` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_pembelian` date NOT NULL,
  `id_supplier` int unsigned NOT NULL,
  `total_pembelian` decimal(14,2) NOT NULL DEFAULT '0.00',
  `id_admin` bigint unsigned NOT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`no_pembelian`),
  KEY `pembelian_inventaris_id_supplier_index` (`id_supplier`),
  KEY `pembelian_inventaris_id_admin_index` (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping Data untuk `pembelian_inventaris` (1 baris)
INSERT INTO `pembelian_inventaris` (`no_pembelian`, `tanggal_pembelian`, `id_supplier`, `total_pembelian`, `id_admin`, `catatan`, `created_at`) VALUES ('PO-20261018-001', '2026-10-18', '1', '100000.00', '1', NULL, '2026-10-18 17:21:31');

-- ---------------------------------------------------------
-- Struktur Tabel `detail_pembelian_inventaris`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `detail_pembelian_inventaris`;
CREATE TABLE `detail_pembelian_inventaris` (
  `id_detail` int unsigned NOT NULL AUTO_INCREMENT,
  `no_pembelian` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode_alat` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kuantitas` int NOT NULL,
  `harga_beli_satuan` decimal(12,2) NOT NULL,
  `subtotal` decimal(14,2) NOT NULL,
  PRIMARY KEY (`id_detail`),
  KEY `detail_pembelian_inventaris_no_pembelian_index` (`no_pembelian`),
  KEY `detail_pembelian_inventaris_kode_alat_index` (`kode_alat`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping Data untuk `detail_pembelian_inventaris` (1 baris)
INSERT INTO `detail_pembelian_inventaris` (`id_detail`, `no_pembelian`, `kode_alat`, `kuantitas`, `harga_beli_satuan`, `subtotal`) VALUES ('1', 'PO-20261018-001', 'ALT004', '2', '50000.00', '100000.00');

-- ---------------------------------------------------------
-- Struktur Tabel `penanganan_kerusakan`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `penanganan_kerusakan`;
CREATE TABLE `penanganan_kerusakan` (
  `id_penanganan` int unsigned NOT NULL AUTO_INCREMENT,
  `kode_alat` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_detail` int unsigned DEFAULT NULL,
  `jenis_tindakan` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah_unit` int NOT NULL DEFAULT '1',
  `biaya_perbaikan` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tanggal_tindakan` date NOT NULL,
  `pelaksana_teknisi` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `id_admin` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_penanganan`),
  KEY `penanganan_kerusakan_kode_alat_index` (`kode_alat`),
  KEY `penanganan_kerusakan_id_detail_index` (`id_detail`),
  KEY `penanganan_kerusakan_jenis_tindakan_index` (`jenis_tindakan`),
  KEY `penanganan_kerusakan_id_admin_index` (`id_admin`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping Data untuk `penanganan_kerusakan` (1 baris)
INSERT INTO `penanganan_kerusakan` (`id_penanganan`, `kode_alat`, `id_detail`, `jenis_tindakan`, `jumlah_unit`, `biaya_perbaikan`, `tanggal_tindakan`, `pelaksana_teknisi`, `keterangan`, `id_admin`, `created_at`) VALUES ('1', 'ALT002', NULL, 'Afkir/Pensiun', '1', '0.00', '2026-10-18', NULL, NULL, '1', '2026-10-18 17:58:07');

-- ---------------------------------------------------------
-- Struktur Tabel `activity_logs`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id_log` int NOT NULL,
  `id_user` int NOT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `modul` varchar(100) NOT NULL,
  `data_diproses` text,
  `waktu` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping Data untuk `activity_logs` (16 baris)
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('1', '1', 'Membuat transaksi penyewaan', 'Penyewaan', 'TRX-20261011-001', '2026-10-11 14:50:53');
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('2', '1', 'Membuat transaksi penyewaan', 'Penyewaan', 'TRX-20261011-002', '2026-10-11 15:07:15');
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('3', '1', 'Membuat transaksi penyewaan', 'Penyewaan', 'TRX-20261011-003', '2026-10-11 15:07:40');
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('4', '1', 'Membuat transaksi penyewaan', 'Penyewaan', 'TRX-20261011-004', '2026-10-11 15:27:34');
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('5', '1', 'Membuat transaksi penyewaan', 'Penyewaan', 'TRX-20261011-005', '2026-10-11 15:27:40');
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('6', '1', 'Membuat transaksi penyewaan', 'Penyewaan', 'TRX-20261011-006', '2026-10-11 15:27:47');
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('7', '1', 'Membuat transaksi penyewaan', 'Penyewaan', 'TRX-20261011-007', '2026-10-11 15:28:38');
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('8', '1', 'Membuat transaksi penyewaan', 'Penyewaan', 'TRX-20261011-008', '2026-10-11 15:29:06');
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('9', '1', 'Membuat transaksi penyewaan', 'Penyewaan', 'TRX-20261011-009', '2026-10-11 15:29:11');
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('10', '1', 'Membuat transaksi penyewaan', 'Penyewaan', 'TRX-20261011-010', '2026-10-11 15:33:32');
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('11', '1', 'Membuat transaksi penyewaan', 'Penyewaan', 'TRX-20261011-011', '2026-10-11 15:34:12');
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('12', '1', 'Membuat transaksi penyewaan', 'Penyewaan', 'TRX-20261011-012', '2026-10-11 15:43:59');
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('13', '1', 'Membuat transaksi penyewaan', 'Penyewaan', 'TRX-20261011-013', '2026-10-11 15:45:47');
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('14', '1', 'Menyetujui penyewaan', 'Penyewaan', 'TRX-20261014-C7B9F840', '2026-10-14 14:14:45');
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('15', '3', 'Mencatat pembayaran', 'Pembayaran', 'TRX-20261014-C7B9F840', '2026-10-15 03:11:10');
INSERT INTO `activity_logs` (`id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`) VALUES ('16', '3', 'Mencatat pembayaran', 'Pembayaran', 'TRX-20261014-C7B9F840', '2026-10-15 04:08:47');

SET FOREIGN_KEY_CHECKS = 1;
-- =========================================================
-- Selesai. Seluruh tabel dan data sampel berhasil di-dump.
-- =========================================================

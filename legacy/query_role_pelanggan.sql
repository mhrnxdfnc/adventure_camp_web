-- BACKUP DATABASE SEBELUM MENJALANKAN QUERY INI. Jalankan satu kali.
-- Tambahkan role Pelanggan sambil mempertahankan role yang sudah ada.
ALTER TABLE `users` MODIFY COLUMN `role` ENUM('Admin','Pemilik','Pelanggan') NOT NULL;

-- Hubungkan akun login dengan satu baris pada tabel pelanggan.
ALTER TABLE `users` ADD COLUMN `id_pelanggan` INT NULL AFTER `id_user`;

-- Setelah kolom ditambahkan, tautkan akun Pelanggan yang sudah ada ke data pelanggan yang benar.
-- Ganti 2 dan 1 dengan ID ASLI dari tabel users dan pelanggan kamu.
-- UPDATE users SET id_pelanggan = 1 WHERE id_user = 2 AND role = 'Pelanggan';

-- Rekening pada halaman pelanggan adalah DATA DEMO, bukan rekening sungguhan.

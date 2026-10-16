# Adventure Camp Rental Management System

Sistem Informasi Manajemen Penyewaan Alat Camping dan Perlengkapan Outdoor berbasis web menggunakan Laravel 12 dan PHP 8.3+. Dirancang untuk mengelola seluruh siklus operasional rental mulai dari reservasi pelanggan, persetujuan admin, verifikasi pembayaran, serah terima alat (pickup), pengembalian & kalkulasi denda, pemantauan inventaris fisik, hingga dashboard pengawasan bisnis bagi pemilik usaha.

---

## 1. Persyaratan Sistem

Pastikan lingkungan server lokal Anda memenuhi spesifikasi berikut:
- **PHP**: Versi 8.2 atau lebih baru (direkomendasikan PHP 8.3 / PHP 8.4)
- **Ekstensi PHP Wajib**: `pdo_mysql`, `mbstring`, `openssl`, `xml`, `ctype`, `json`, `tokenizer`, `curl`, `fileinfo`
- **Database**: MySQL 5.7+ / MariaDB 10.3+ (Tersedia langsung di Laragon)
- **Composer**: Versi 2.x
- **Web Server**: Apache / Nginx (via Laragon) atau PHP Built-in Server

---

## 2. Panduan Instalasi & Konfigurasi

### Langkah 1: Clone atau Buka Proyek
Letakkan direktori proyek di dalam folder root web Laragon:
```text
C:\laragon\www\adventure_camp_web
```
atau pada drive lain yang terhubung dengan Laragon:
```text
D:\APP\laragon\www\adventure_camp_web
```

### Langkah 2: Instal Dependensi Composer
Buka terminal (Laragon Terminal atau PowerShell) di direktori proyek, lalu jalankan:
```bash
composer install
```

### Langkah 3: Konfigurasi Berkas Lingkungan (.env)
Salin berkas template lingkungan `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Buka berkas `.env` dan sesuaikan parameter koneksi database MySQL:
```dotenv
APP_NAME="Adventure Camp Rental"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Asia/Jakarta
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=adventure_camp_rental
DB_USERNAME=root
DB_PASSWORD=root
```
> **Catatan Laragon**: Password default MySQL pada instalasi Laragon standar umumnya kosong (`DB_PASSWORD=`) atau disesuaikan dengan konfigurasi root MySQL Anda.

### Langkah 4: Generate Application Key
```bash
php artisan key:generate
```

### Langkah 5: Migrasi Database & Seeder
Buat database bernama `adventure_camp_rental` di phpMyAdmin / HeidiSQL Laragon, lalu jalankan migrasi dan seeder awal:
```bash
php artisan migrate --seed
```

---

## 3. Cara Menjalankan Aplikasi Melalui Laragon

### Opsi A: Menggunakan Laragon Auto Virtual Host
1. Pastikan servis **Apache** dan **MySQL** pada Laragon berstatus **Running** (Klik tombol *Start All* pada Laragon).
2. Jika diletakkan di `C:\laragon\www\adventure_camp_web`, Laragon akan otomatis membuat virtual host:
   ```text
   http://adventure_camp_web.test
   ```
3. Buka URL tersebut di peramban (browser) Anda.

### Opsi B: Menggunakan PHP Artisan Serve
Jalankan dev server bawaan Laravel:
```bash
php artisan serve
```
Akses aplikasi melalui peramban pada alamat:
```text
http://127.0.0.1:8000
```

---

## 4. Cara Menjalankan Pengujian Otomatis (Testing)

Proyek ini dilengkapi dengan rangkaian pengujian fitur dan integrasi end-to-end menyeluruh (Feature & Unit Tests):

1. **Menjalankan Seluruh Test Suite**:
   ```bash
   php artisan test
   ```
   *(Menjalankan 164 pengujian dengan SQLite in-memory yang terisolasi dari database produksi/lokal)*.

2. **Menjalankan Pengujian Integrasi End-to-End Spesifik**:
   ```bash
   php artisan test --filter=AdventureCampIntegrationTest
   ```

3. **Menjalankan Pengujian Berdasarkan Modul**:
   ```bash
   php artisan test --filter=OperasionalTest
   php artisan test --filter=PembayaranTest
   php artisan test --filter=InventarisTest
   php artisan test --filter=PemilikDashboardTest
   php artisan test --filter=AdminLogViewTest
   ```

---

## 5. Dokumentasi Hak Akses & Tiga Role Pengguna

Aplikasi menerapkan pembatasan hak akses berbasis peran (*Role-Based Access Control*) yang ketat:

| Role | Username Bawaan | Password Bawaan | Deskripsi Hak Akses |
|---|---|---|---|
| **Admin** | `admin` | `password` / `admin123` | **Operasional Penuh**: Menyetujui/menolak pesanan sewa, verifikasi pembayaran manual, penyerahan barang, pengembalian & perhitungan denda, kelola master data alat, penyesuaian stok inventaris fisik, dan memeriksa audit trail log. |
| **Pengguna** | `pelanggan` | `password` / `pelanggan123` | **Layanan Mandiri Pelanggan**: Eksplorasi katalog alat outdoor siap sewa, pengajuan sewa mandiri, melihat instruksi bayar (QRIS/Transfer/Cash), unggah bukti transfer, memantau status pesanan, dan melihat riwayat denda/pengembalian sendiri. |
| **Pemilik** | `pemilik` | `password` / `pemilik123` | **Strictly Read-Only (Pengawasan Bisnis)**: Memantau ringkasan omzet penjualan hari ini dan bulan berjalan, grafik tren harian/bulanan, rincian riwayat transaksi pelanggan, serta audit alat rusak atau butuh perbaikan. Tidak memiliki tombol operasional atau aksi mutasi data. |

---

## 6. Alur Bisnis & Transaksi Penyewaan

```text
[Pelanggan]
  Katalog Alat -> Pilih Alat, Jumlah & Periode -> Kirim Pesanan
       |
       v (Status: Menunggu Konfirmasi)
[Admin]
  Periksa Ketersediaan Stok Real-Time -> Setujui / Tolak Pesanan
       | (Jika Disetujui -> Status: Menunggu Pembayaran)
       v
[Pelanggan]
  Pilih Metode Bayar:
  * Transfer Bank -> Unggah Berkas Bukti Transfer (JPG/PNG/PDF maks 2MB)
  * QRIS Kasir -> Minta Kode QRIS Kasir saat berkunjung
  * Cash / Tunai -> Pembayaran Langsung di Kasir
       |
       v (Status Pembayaran: Menunggu Verifikasi)
[Admin]
  Verifikasi Bukti / Terima Dana -> Konfirmasi Pembayaran (Status: Lunas)
       |
       v (Status Pesanan: Siap Diambil)
[Admin - Penyerahan Alat]
  Catat Tanggal Ambil & Nama Penerima -> Serah Terima Alat
       | (Status Pesanan: Sedang Disewa, Stok Fisik Berkurang)
       v
[Admin - Pengembalian Alat]
  Catat Tanggal Kembali Aktual -> Audit Kondisi Fisik Alat (Layak Sewa vs Rusak)
  -> Kalkulasi Denda Terlambat/Kerusakan -> Selesaikan Transaksi
       | (Status Pesanan: Selesai, Stok Layak Sewa Kembali)
       v
[Audit Log & Rekap Laporan Pemilik]
```

---

## 7. Pengelolaan Inventaris & Stok Fisik

1. **Pemisahan Kondisi Alat**:
   - **Layak Sewa (`Baik`)**: Alat dalam kondisi prima yang diperhitungkan ke dalam stok siap sewa.
   - **Perlu Perbaikan**: Alat membutuhkan pemeliharaan ringan sebelum dapat disewakan kembali.
   - **Rusak**: Alat rusak yang dikeluarkan dari perhitungan stok siap sewa untuk mencegah kegagalan pemenuhan pesanan pelanggan.
2. **Riwayat Perubahan Stok (*Stock Movement History*)**:
   - Setiap mutasi stok (penambahan stok baru, pengurangan stok serah terima, pengembalian, penyesuaian audit opname) tercatat secara otomatis di tabel `riwayat_stok` lengkap dengan identitas admin dan alasan perubahan.
3. **Pencegahan Stok Negatif & Transaksi Ganda**:
   - Sistem menggunakan transaksi basis data dengan mekanisme *pessimistic locking* (`lockForUpdate`) untuk mencegah kondisi balapan (*race condition*) ketika dua pesanan bersaing atas stok unit yang terbatas.

---

## 8. Batasan Sistem & Keterbatasan Implementasi Saat Ini

Sesuai kesepakatan dan batasan modul saat ini:
1. **Verifikasi Pembayaran Manual**:
   - Integrasi payment gateway otomatis (misalnya Midtrans/Xendit/Doku) belum diimplementasikan. Pembayaran QRIS kasir dan transfer bank diverifikasi secara manual oleh Admin/Kasir setelah mengecek mutasi rekening atau struk pembayaran kasir.
2. **Pembayaran Penuh (Full Payment)**:
   - Sistem saat ini mensyaratkan pembayaran lunas penuh sebelum barang diserahterimakan. Pembayaran sebagian (*partial payment*) atau skema uang muka (*down payment / DP*) belum didukung.
3. **Data Historis Legacy**:
   - Transaksi legasi masa lalu yang tidak memiliki rincian item alat di tabel `detail_penyewaan` tetap ditampilkan secara utuh dan aman pada riwayat pesanan dengan label khusus data historis tanpa merusak integritas pelaporan keuangan.

---

## 9. Lisensi & Hak Cipta
Hak Cipta &copy; 2026 Adventure Camp Rental. Dikembangkan untuk manajemen penyewaan alat outdoor yang andal, aman, dan efisien.

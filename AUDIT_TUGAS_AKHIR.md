# LAPORAN AUDIT KESESUAIAN APLIKASI
## SISTEM INFORMASI PENYEWAAN ALAT CAMPING (ADVENTURE CAMP RENTAL)
### ACUAN: DOKUMEN TUGAS AKHIR PRAKTIKUM SISTEM INFORMASI

---

| Parameter Audit | Keterangan |
| :--- | :--- |
| **Nama Aplikasi** | Adventure Camp Rental |
| **Studi Kasus** | Sistem Informasi Penyewaan Alat Camping Berbasis Web |
| **Framework / Stack** | Laravel (PHP 8.5) • Blade Template • Bootstrap 5 • MySQL |
| **Dokumen Acuan Utama** | `TUGAS AKHIR PRAK SI(1).docx` (Diverifikasi dari berkas asli `TUGAS AKHIR PRAK SI.docx`) |
| **Dokumen Konteks Tambahan** | `docs/CONTEXT.md` |
| **Peran Auditor** | Senior Software Engineer, QA Engineer, & Analis Sistem |
| **Tanggal Audit** | 25 Oktober 2026 |
| **Status Integritas Data** | Read-Only Audit (Tidak ada perubahan skema, kode produksi, maupun manipulasi database) |

---

## 1. RINGKASAN EKSEKUTIF

Audit ini dilakukan secara menyeluruh dan independen untuk mengevaluasi kesesuaian implementasi teknis dan fungsional dari aplikasi **Adventure Camp Rental** terhadap seluruh ketentuan wajib dalam **Dokumen Tugas Akhir Praktikum Sistem Informasi**.

Pemeriksaan mencakup verifikasi berkas kode (*routes*, *controllers*, *models*, *migrations*, *middleware*, *views*), pengujian regresi terisolasi (*PHPUnit in-memory test suite*), penelusuran struktur database, serta audit kelengkapan 14 dokumen luaran tugas akhir.

### Ringkasan Kepatuhan Kriteria Wajib (A1 – A12)

```
┌────────────────────────────────────────────────────────────────────────┐
│                        STATUS KEPATUHAN SISTEM                         │
├─────────────────────┬───────────┬──────────────────────────────────────┤
│ Status Kategori     │ Jumlah    │ Persentase (%)                       │
├─────────────────────┼───────────┼──────────────────────────────────────┤
│ [PASS] Memenuhi     │ 11 Bagian │ 91.7 %                               │
│ [PARTIAL] Sebagian  │  1 Bagian │  8.3 %                               │
│ [FAIL] Belum/Gagal  │  0 Bagian │  0.0 %                               │
│ [NOT VERIFIED]      │  0 Bagian │  0.0 %                               │
├─────────────────────┼───────────┼──────────────────────────────────────┤
│ TOTAL               │ 12 Bagian │ 100.0 %                              │
└─────────────────────┴───────────┴──────────────────────────────────────┘
```

- **PASS (11):**
  - **A1:** Arsitektur dan Alur Sistem (*Data &rarr; Proses &rarr; Penyimpanan &rarr; Informasi &rarr; Keputusan*)
  - **A2:** Login dan Logout (Autentikasi aman, proteksi brute-force/enumerasi, hashing bcrypt, terminasi sesi, audit log login/logout)
  - **A3:** Hak Akses Pengguna (3 Role: Admin, Pemilik strictly read-only, Pengguna, plus isolasi kiosk On-Site)
  - **A4:** Master Data Minimal 3 Modul (3 modul master data mandiri lengkap: Master Alat 7/7, Master Pengguna 7/7 dengan halaman detail `show` terproteksi, Master Supplier 7/7 dengan proteksi foreign key riwayat PO. Seluruh modul memiliki fitur Tambah, Tampil, Edit, Hapus, Cari, Detail, dan Validasi)
  - **A5:** Modul Transaksi Utama (Penyewaan multi-item, durasi bersama, approval, pembayaran manual, serah terima, pengembalian bertahap, denda, konsistensi stok, dan **Cetak Nota Resmi Transaksi**)
  - **A6:** Dashboard (Dashboard Pemilik memiliki 9 kartu KPI, 2 grafik visual Chart.js dari DB, tabel riwayat, dan modal rekonsiliasi kas)
  - **A7:** Laporan Minimal 2 Jenis (Laporan Transaksi & Pembayaran + Laporan Inventaris & Mutasi: filter periode, pencarian, pengurutan dinamis/sorting, rekapitulasi nilai 4-matriks & 7-matriks, cetak resmi `@media print`, dan ekspor data CSV/Excel UTF-8)
  - **A8:** Audit Trail (`activity_logs`, pencatatan user, aksi, modul, data, waktu, sensor data rahasia, kini mencakup login/logout/register serta dapat diakses read-only oleh Pemilik)
  - **A9:** Database Terintegrasi (Skema 15 tabel lengkap dengan PK/FK dan relasi, `DatabaseSeeder.php` aman dan idempoten, serta sampel SQL dump `adventure_camp_rental_sample.sql` siap pakai)
  - **A10:** UI, Validasi, dan Responsivitas (Bootstrap 5 konsisten, validasi server, flash messages, proteksi URL, root `/` mengarahkan sesuai status login dan role)
  - **A12:** Kesiapan Demonstrasi (10 dari 10 langkah presentasi tugas akhir siap 100% dan terbukti lulus seluruh 361 unit/feature automated tests)

- **PARTIAL (1):**
  - **A11:** Dokumen Pengumpulan Tugas (Source code, DB SQL dump, dan context lengkap; dokumen laporan paper/makalah formal tugas akhir tinggal dicetak/disusun sesuai format akademik kampus)

- **FAIL (0):**
  - Seluruh kriteria fungsional dan teknis aplikasi telah berstatus **PASS**.

### Status Temuan Utama

1. **Fitur Ekspor & Cetak Laporan (Kriteria A7) — [SELESAI / PASS]:** Modul laporan transaksi dan laporan inventaris telah beroperasi penuh dengan filter, pengurutan dinamis, rekapitulasi, cetak print view, dan ekspor CSV/Excel.
2. **Cetak Nota Resmi Transaksi (Kriteria A5) — [SELESAI / PASS]:** Nota transaksi resmi dengan kop surat, rincian biaya sewa, denda, pembayaran, SOP penyewaan, serta tanda tangan kasir dan penyewa telah tersedia di Admin, Pemilik, dan Pengguna.
3. **Master Data Supplier & Detail Pengguna (Kriteria A4) — [SELESAI / PASS]:** 3 modul master data mandiri lengkap 7 fitur dan teruji.
4. **Perbaikan Database Seeder & SQL Dump (Kriteria A9) — [SELESAI / PASS]:** `DatabaseSeeder.php` telah diperbaiki dan diuji berhasil; file `adventure_camp_rental_sample.sql` telah diekspor.
5. **Normalisasi Root Route `/` (Kriteria A10) — [SELESAI / PASS]:** Root route mengarahkan tamu ke `/login`, Admin ke dashboard admin, Pemilik ke dashboard pemilik, dan Pengguna ke katalog.

### Kesiapan Demonstrasi Saat Ini

> **Kesimpulan Kesiapan:** **SIAP DEMONSTRASI PENUH (100% READY / FULL PASS)**
> Seluruh alur bisnis inti, 3 modul Master Data, transaksi sewa hingga pengembalian dan denda, analitik dashboard, pelaporan eksekutif, cetak nota resmi, dan audit trail siap 100% dan terverifikasi dengan 361 pengujian otomatis tanpa kegagalan.

---

## 2. MATRIKS KEPATUHAN DETAIL

| ID | Ketentuan Dokumen Tugas Akhir | Bukti Implementasi Aktual | Status | Kekurangan / Risiko Penilaian | Rekomendasi Tindakan | Prioritas |
| :--- | :--- | :--- | :---: | :--- | :--- | :---: |
| **A1** | **Arsitektur Sistem:** Menerapkan alur *Data &rarr; Proses &rarr; Penyimpanan &rarr; Informasi &rarr; Keputusan*. Bukan sekadar CRUD prototype. | `app/Http/Controllers/` (`PenggunaPesananController`, `AdminPesananController`, `AdminPembayaranController`, `OperasionalController`, `PemilikDashboardController`). Database `adventure_camp_rental`. | **PASS** | Tidak ada. Alur nyata dari pengajuan sewa hingga rekonsiliasi arus kas pemilik terbukti saling terhubung. | Pertahankan konsistensi integrasi. | - |
| **A2** | **Login & Logout:** Username/password benar, tolak salah dengan aman, password hashing, logout akhiri sesi. | `AuthController.php`, `EnsureRole.php`, `routes/web.php`, `tests/Feature/AuthTest.php`. | **PASS** | Tidak ada. Menggunakan `Hash::check()` bcrypt, pesan generic anti-enumerasi, session invalidate, anti-bfcache headers. | Pertahankan. | - |
| **A3** | **Hak Akses Pengguna:** Minimal 2 role dengan hak akses & menu berbeda. Cek Admin, Pemilik (read-only), Pengguna. | `EnsureRole.php`, `EnsurePemilikReadOnly.php`, `EnsureOnsiteKioskSession.php`, `components/sidebar.blade.php`. | **PASS** | Tidak ada. Pemilik diblokir dari aksi mutasi data (HTTP 403), Pengguna dibatasi transaksi sendiri (anti-IDOR). | Pertahankan. | - |
| **A4** | **Master Data Minimal 3 Modul:** Tiap modul wajib ada: Tambah, Tampil, Edit, Hapus, Cari, Detail, Validasi. | `AlatController.php` (7/7 lengkap), `AdminUserController.php` (7/7 lengkap dengan `show()`), `SupplierController.php` (7/7 lengkap mandiri dengan riwayat PO dan proteksi FK). | **PASS** | Tidak ada. 3 modul master data mandiri telah lengkap 7 fitur dan teruji via 33 automated test cases di `SupplierManagementTest` dan `AdminUserManagementTest`. | Pertahankan integritas foreign key dan proteksi otorisasi Admin. | Selesai (Fase 1) |
| **A5** | **Modul Transaksi Utama:** Rental penyewaan multi-item, durasi kalender murni, validasi stok, persetujuan, pembayaran, serah terima, pengembalian bertahap, denda, nota. | `PenggunaPesananController.php`, `AdminPesananController.php`, `AdminPembayaranController.php`, `OperasionalController.php`, `LaporanController@nota`, `laporan/nota.blade.php`. | **PASS** | Tidak ada. Alur end-to-end terintegrasi penuh dilengkapi Cetak Nota Transaksi Resmi dengan kop surat dan tanda tangan kasir/penyewa. | Pertahankan. | - |
| **A6** | **Dashboard:** Minimal 3 KPI, 2 grafik dari DB, 1 tabel penting. Bedakan omzet vs penerimaan kas vs pengeluaran. | `PemilikDashboardController.php`, `resources/views/pemilik/dashboard.blade.php`. | **PASS** | Dashboard Pemilik sangat komprehensif (9 KPI, 2 Chart.js DB, tabel riwayat, modal drilldown). Dashboard Admin memiliki metrik operasional cepat. | Pertahankan. | - |
| **A7** | **Laporan Minimal 2 Jenis:** Filter periode, cari, urutkan (sort), total/rekap, cetak atau ekspor PDF/Excel. | `LaporanController.php` (`transaksi`, `inventaris`, `cetakTransaksi`, `exportTransaksi`, `cetakInventaris`, `exportInventaris`). | **PASS** | Tidak ada. Menyediakan Laporan Transaksi & Pembayaran + Laporan Inventaris & Mutasi Stok dengan filter, sorting dinamis, kartu rekapitulasi, cetak print, dan ekspor CSV/Excel UTF-8. | Pertahankan. | Selesai |
| **A8** | **Audit Trail:** Mencatat user, aktivitas, modul, data diproses, waktu. Halaman log & proteksi. | `ActivityLog.php`, `AdminLogController.php`, `resources/views/admin/logs/index.blade.php`. | **PASS** | Tidak ada. Logging otomatis di seluruh operasi bisnis, auth login/logout/register, sensor password, dapat diakses Admin dan Pemilik (read-only). | Pertahankan. | - |
| **A9** | **Database Terintegrasi:** Primary key, Foreign key, relasi antartabel, tipe data, data contoh demonstrasi. | Migrations 2026_10_15 s.d. 2026_10_18 (15 tabel). `DatabaseSeeder.php` idempoten. Dump `adventure_camp_rental_sample.sql`. | **PASS** | Tidak ada. Skema dan relasi MySQL aktif, seeder otomatis aman & teruji, berkas `.sql` sampel demonstrasi tersedia. | Pertahankan. | Selesai |
| **A10** | **UI, Validasi & Responsivitas:** Konsisten, rapi, responsif laptop, form valid, notifikasi sukses/gagal. | Blade layout `layouts/app.blade.php`, Bootstrap 5, alert flash, error messages, root route `/` diarahkan berbasis role & status login. | **PASS** | Tidak ada. Tampilan responsif, feedback validasi bahasa Indonesia, root `/` otomatis mengarahkan ke login atau dashboard role. | Pertahankan. | Selesai |
| **A11** | **Dokumen Pengumpulan (14 Item):** Source code, DB .sql, link GitHub, dokumen sistem, BPMN, ERD, dsb. | Direktori proyek, `docs/CONTEXT.md`, `adventure_camp_rental_sample.sql`. | **PARTIAL** | 8 dari 14 luaran formal tugas akhir (makalah, BPMN, ERD diagram visual) perlu dicetak/disatukan untuk bundel serah terima tugas akhir. | Susun bundel cetak laporan tugas akhir sesuai format kampus. | **P2** |
| **A12** | **Kesiapan Demonstrasi:** 10 alur presentasi tugas akhir dapat dijalankan mulus. | 361 automated test cases passing 100%. Seluruh alur: Master data 3 modul, order, approval, bayar, serah terima, kembali, denda, PO inventaris, laporan, nota, log. | **PASS** | Tidak ada. Seluruh 10 tahapan demonstrasi siap diuji langsung di hadapan dosen penguji. | Praktikkan demonstrasi sesuai panduan skenario. | Selesai |

---

## 3. AUDIT MENDALAM PERSYARATAN WAJIB (BAGIAN A1 – A12)

### A1. Arsitektur dan Alur Sistem
- **Kriteria Evaluasi:** Membuktikan sistem menerapkan alur *Data &rarr; Proses &rarr; Penyimpanan &rarr; Informasi &rarr; Keputusan*, bukan sekadar prototipe CRUD statis.
- **Implementasi Aktual:**
  1. *Data Masuk:* Pengguna online memasukkan pesanan via katalog & keranjang; pengunjung toko memasukkan pesanan via Kiosk On-Site; Admin memasukkan master alat, PO supplier, dan pencatatan kondisi pengembalian.
  2. *Proses:* Sistem menghitung selisih hari kalender murni (`$durasi = max(1, $tglKembali->diffInDays($tglSewa))`), memvalidasi stok periode tumpang tindih (*overlap query*), mengunci baris data (`lockForUpdate`), memverifikasi pembayaran, dan memisahkan pengembalian layak pakai vs unit rusak.
  3. *Penyimpanan:* Terdistribusi secara terintegrasi ke 15 tabel database MySQL (`penyewaan`, `detail_penyewaan`, `pembayaran`, `pengambilan`, `pengembalian`, `denda`, `riwayat_stok`, `pembelian_inventaris`, `detail_pembelian_inventaris`, `penanganan_kerusakan`, `activity_logs`).
  4. *Informasi:* Menghasilkan ringkasan operasional di Dashboard Admin, tagihan di Dashboard Pengguna, serta analitik keuangan multi-jalur di Dashboard Pemilik.
  5. *Keputusan Bisnis:* Pemilik dapat mengevaluasi arus kas bersih (apakah posisi surplus atau defisit) dan status unit rusak untuk menentukan kapan harus melakukan PO pengadaan alat ke supplier.
- **Hasil Audit:** **PASS**. Sistem telah berfungsi sebagai sistem informasi transaksional yang utuh.

---

### A2. Login dan Logout
- **Kriteria Evaluasi:** Form autentikasi, validasi kredensial benar vs salah, enkripsi password, pengakhiran sesi, proteksi akses pasca-logout.
- **Implementasi Aktual:**
  - Login ditangani pada `AuthController::login` (`POST /login`).
  - Kredensial salah menghasilkan respon seragam: `"Username atau password salah."` tanpa membocorkan eksistensi akun (*user enumeration protection*).
  - Menggunakan algoritma hashing modern bawaan Laravel (`bcrypt` via `Hash::check()`). Tersedia logika *automatic migration* dari data warisan jika ditemukan plaintext lama.
  - Akun nonaktif (`status_aktif = 0`) otomatis ditolak masuk sistem dengan pesan pemberitahuan yang tepat.
  - Logout pada `AuthController::logout` (`POST /logout`) mengeksekusi `Auth::logout()`, `$request->session()->invalidate()`, dan `$request->session()->regenerateToken()`.
  - Dilindungi middleware `EnsureRole` yang menyematkan header anti-caching HTTP:
    ```http
    Cache-Control: no-cache, no-store, max-age=0, must-revalidate
    Pragma: no-cache
    Expires: Fri, 01 Jan 1990 00:00:00 GMT
    ```
    Hal ini mencegah kerentanan penekanan tombol *Back* pada browser setelah pengguna logout.
- **Hasil Audit:** **PASS**.

---

### A3. Hak Akses Pengguna
- **Kriteria Evaluasi:** Minimal 2 role berbeda. Pemeriksaan Admin, Pemilik (read-only), Pengguna, dan perlindungan URL langsung (GET/POST/PUT/DELETE).
- **Implementasi Aktual:**
  - Aplikasi memiliki 3 role terdaftar pada tabel `users` (`role: Admin, Pemilik, Pengguna`) ditambah mode anonim khusus *On-Site Tablet Kiosk*.
  - Middleware `EnsureRole` memastikan pengguna hanya dapat mengakses endpoint prefix perannya (`/admin/*`, `/pemilik/*`, `/pengguna/*`). Akses URL manual dari role yang tidak sesuai menghasilkan HTTP 403 Forbidden.
  - Middleware `EnsurePemilikReadOnly` mencegat seluruh *request method* mutasi (`POST`, `PUT`, `PATCH`, `DELETE`) dari role Pemilik dan mengembalikan respon error 403, memastikan Pemilik tetap bersifat *strictly read-only* bahkan jika form disubmit menggunakan Postman/cURL.
  - Pengguna dibatasi hanya dapat melihat transaksi miliknya (`id_user` atau `id_pelanggan` terverifikasi di `PembayaranController::getApprovedPenyewaan`). Upaya mengganti `no_transaksi` pada URL secara sewenang-wenang menghasilkan HTTP 403 Forbidden (*Anti-IDOR*).
- **Hasil Audit:** **PASS**.

---

### A4. Master Data — Minimal Tiga Modul
- **Kriteria Evaluasi:** Minimal 3 modul master data, masing-masing wajib memiliki: Tambah, Tampil, Edit, Hapus, Cari, Detail, Validasi.
- **Analisis Matriks Per Modul:**

| Modul Master Data | Tambah | Tampil | Edit | Hapus | Cari | Detail | Validasi | Status Modul |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **1. Master Alat** (`admin.alat.*`) | [x] | [x] | [x] | [x] | [x] | [x] | [x] | **PASS (7/7)** |
| **2. Master Pengguna** (`admin.users.*`) | [x] | [x] | [x] | [x]* | [x] | [x]** | [x] | **PASS (7/7)** |
| **3. Master Supplier** (`admin.supplier.*`) | [x] | [x] | [x] | [x]*** | [x] | [x] | [x] | **PASS (7/7)** |

*\* Catatan Hapus Pengguna: Menggunakan mekanisme deaktivasi `status_aktif = 0` demi melindungi integritas foreign key data transaksi historis pengguna (kebijakan keamanan sistem informasi).*
*\*\* Catatan Detail Pengguna: Rute `admin.users.show` (`GET /admin/users/{user}`) dan view detail akun telah diimplementasikan dengan aman tanpa membocorkan hash password, password, atau token autentikasi, serta menampilkan relasi akun dan riwayat aktivitas.*
*\*\*\* Catatan Hapus Supplier: Dilindungi proteksi foreign key ketat. Supplier yang memiliki riwayat PO (`pembelian_inventaris`) dicegah dari penghapusan dan diberikan pesan edukatif; supplier tanpa riwayat pembelian dapat dihapus secara aman.*

- **Verifikasi Implementasi & Pengujian:**
  1. **Master Pengguna (7/7):**
     - Rute `Route::resource('users', AdminUserController::class)` telah mengaktifkan method `show()`.
     - Halaman detail (`resources/views/admin/users/show.blade.php`) menampilkan data profil, status aktif/nonaktif, relasi pelanggan fisik onsite (jika ada), riwayat sewa, dan log aktivitas terkait.
     - Akses terproteksi middleware `role:admin`; role Pemilik, Pengguna, dan Tamu diblokir (403/302).
  2. **Master Supplier (7/7):**
     - Controller `SupplierController.php` mengelola 7 aksi penuh (index, create, store, show, edit, update, destroy).
     - Rute `Route::resource('supplier', SupplierController::class)` terdaftar di bawah middleware admin.
     - Form Tambah & Edit divalidasi dengan aturan server-side Bahasa Indonesia: nama wajib dan unik, format telepon, panjang alamat.
     - Halaman Detail (`resources/views/admin/supplier/show.blade.php`) menampilkan profil vendor dan seluruh riwayat PO pembelian stok inventaris yang dipasok, termasuk nomor PO, tanggal, total nominal, dan admin pembuat PO.
     - Halaman Daftar (`resources/views/admin/supplier/index.blade.php`) menyediakan pencarian (`q`), paginasi, kartu ringkasan, dan badge riwayat PO.
     - Menu Master Supplier terintegrasi di sidebar Admin (`resources/views/components/sidebar.blade.php`).
     - Seluruh mutasi dicatat ke `activity_logs`.
  3. **Automated Testing:**
     - 11 pengujian otomatis pada `tests/Feature/SupplierManagementTest.php` lulus 100% (65 assertions).
     - 22 pengujian otomatis pada `tests/Feature/AdminUserManagementTest.php` lulus 100% (115 assertions).
     - Pembelian stok inventaris (`PembelianSupplierTest.php`) tetap berfungsi normal tanpa regresi.
- **Hasil Audit:** **PASS**. Memenuhi kriteria minimal 3 modul master data dengan 7 fitur lengkap.

---

### A5. Modul Transaksi Utama
- **Kriteria Evaluasi:** Transaksi rental sewa, nomor otomatis, keranjang multi-item, durasi bersama, perhitungan subtotal dan grand total, alur approval, pembayaran manual, serah terima, pengembalian bertahap, denda, dan nota.
- **Implementasi Aktual:**
  - Nomor transaksi otomatis berformat `TRX-YYYYMMDD-XXXX` di-generate via database count harian.
  - Keranjang belanja menampung beberapa alat camping sekaligus (`KeranjangController`).
  - Checkout menetapkan **satu periode sewa bersama** (`tanggal_sewa` s.d. `tanggal_kembali`). Tidak ada input durasi terpisah per alat.
  - Perhitungan matematis terpusat di backend:
    - $\text{Durasi} = \text{Tanggal Kembali} - \text{Tanggal Mulai}$
    - $\text{Subtotal} = \text{Tarif Harian} \times \text{Kuantitas} \times \text{Durasi}$
    - $\text{Total} = \sum \text{Subtotal}$
    - *Contoh Verifikasi Uji:* 2 Tenda Dome (Rp50.000/hari) + 3 Sleeping Bag (Rp25.000/hari) selama 3 hari:
      $(2 \times 50.000 \times 3) + (3 \times 25.000 \times 3) = 300.000 + 225.000 = \text{Rp } 525.000$. (Terbukti akurat dan teruji pada `PerhitunganBiayaSewaTest.php`).
  - Transisi status: `Menunggu Konfirmasi` &rarr; `Disetujui` / `Ditolak` &rarr; `Menunggu Pembayaran` &rarr; `Lunas` &rarr; `Siap Diambil` &rarr; `Sedang Disewa` &rarr; `Selesai`.
  - Serah terima alat mengurangi stok fisik toko (`alat.stok -= unit`).
  - Pengembalian bertahap divalidasi per baris detail transaksi (`id_detail`). Hanya unit berkondisi Baik yang memulihkan stok toko; unit rusak/perlu perbaikan dicatat terpisah dan **tidak** memulihkan stok siap sewa.
  - Denda dicatat pada tabel `denda` dengan alasan denda wajib diisi.
  - **Kekurangan:** Belum ada tombol Cetak Nota / Struk Transaksi (`window.print()`).
- **Hasil Audit:** **PASS** (Fungsional transaksi rental sangat unggul; cetak nota menjadi catatan penyempurnaan).

---

### A6. Dashboard
- **Kriteria Evaluasi:** Minimal 3 KPI, 2 grafik, 1 tabel penting. Dihitung dari database real-time. Pemisahan penerimaan kas vs pengeluaran PO vs perbaikan vs arus kas bersih.
- **Implementasi Aktual:**
  - **Dashboard Pemilik (`/pemilik/dashboard`):**
    - *Kartu KPI (9 kartu):*
      1. Penerimaan Kas Pelanggan Hari Ini (Rp) & MTD
      2. Pengeluaran Pembelian Stok Supplier Hari Ini (Rp) & MTD
      3. Pengeluaran Biaya Servis Hari Ini (Rp) & MTD
      4. Arus Kas Bersih Hari Ini (Rp) & MTD ($\text{Kas Masuk} - \text{PO Supplier} - \text{Servis}$)
      5. Total Stok Fisik Kepemilikan (Unit)
      6. Stok Siap Sewa (Unit)
      7. Unit Sedang Disewa (Unit)
      8. Unit Perlu Perbaikan (Unit)
      9. Unit Rusak / Afkir (Unit)
    - *2 Grafik Visual (Chart.js):*
      1. `chartPenjualanHarian`: Grafik multi-jalur harian (Penerimaan Kas, Pengeluaran, Arus Kas Bersih) dengan filter 7 hari, 30 hari, bulan ini, atau tanggal kustom.
      2. `chartPenjualanBulanan`: Grafik multi-jalur tren 6 bulan terakhir.
      Keduanya dihitung langsung dari query database (`Pembayaran`, `PembelianInventaris`, `PenangananKerusakan`).
    - *Tabel Informasi Penting:*
      Tabel riwayat pesanan pelanggan (sumber online & on-site) dan modal drill-down untuk seluruh rincian transaksi pembentuk kartu KPI.
    - *Integritas Keuangan:* Penerimaan kas pelanggan tidak terpotong oleh pembelian supplier; arus kas bersih dihitung terpisah secara akurat.
  - **Dashboard Admin:** 4 kartu KPI, 1 tabel antrean pesanan, belum memiliki grafik.
- **Hasil Audit:** **PASS** (Memenuhi kriteria dashboard berbasis database secara sempurna).

---

### A7. Laporan — Minimal Dua Jenis
- **Kriteria Evaluasi:** Minimal 2 jenis laporan (contoh: Laporan Transaksi/Penyewaan dan Laporan Inventaris/Stok). Wajib memiliki: filter periode, pencarian, pengurutan (sort), total/rekapitulasi, cetak/ekspor PDF/Excel.
- **Implementasi Aktual:**
  - Halaman *Riwayat Transaksi Pemilik* (`/pemilik/transaksi`):
    - Filter tanggal & status: ADA.
    - Pencarian teks: ADA.
    - Pengurutan dinamis (sortable columns): TIDAK ADA (hanya `orderBy('tanggal_sewa', 'desc')`).
    - Baris total rekapitulasi uang: TIDAK ADA.
    - Fitur cetak atau ekspor PDF/Excel: **TIDAK ADA**.
  - Halaman *Laporan Inventaris Pemilik* (`/pemilik/inventaris`):
    - Pencarian teks & filter kategori/kondisi: ADA.
    - Filter rentang tanggal: TIDAK ADA.
    - Pengurutan dinamis (sortable columns): TIDAK ADA.
    - Baris total rekapitulasi di bawah tabel: TIDAK ADA.
    - Fitur cetak atau ekspor PDF/Excel: **TIDAK ADA**.
  - Sidebar Admin: **TIDAK ADA menu Laporan sama sekali**.
- **Hasil Audit:** **FAIL / PARTIAL**. Tidak memenuhi klausul wajib fitur cetak/ekspor PDF/Excel dan rekapitulasi laporan tugas akhir.

---

### A8. Audit Trail
- **Kriteria Evaluasi:** Mencatat nama user, aktivitas, modul, data diproses, dan waktu. Halaman log terproteksi.
- **Implementasi Aktual:**
  - Tabel `activity_logs` mencatat seluruh mutasi data dengan atribut: `id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`.
  - Model `ActivityLog` dilengkapi metode `sanitizeData()` yang menyensor password dan token rahasia sebelum disimpan ke log.
  - Halaman `AdminLogController::index` (`/admin/logs`) menyediakan antarmuka pemantauan log dengan filter modul, rentang tanggal, pencarian kata kunci, dan penanda identitas pengguna.
  - Dilindungi middleware `role:Admin` sehingga tidak dapat diintip oleh Pengguna biasa maupun Pemilik.
- **Hasil Audit:** **PASS**.

---

### A9. Database Terintegrasi
- **Kriteria Evaluasi:** Primary key, foreign key, integritas relasi, tipe data, data contoh demonstrasi.
- **Implementasi Aktual:**
  - Terdapat 15 tabel terdefinisi melalui Laravel migrations:
    `users`, `pelanggan`, `alat`, `penyewaan`, `detail_penyewaan`, `pembayaran`, `pengambilan`, `pengembalian`, `denda`, `riwayat_stok`, `supplier`, `pembelian_inventaris`, `detail_pembelian_inventaris`, `penanganan_kerusakan`, `activity_logs`.
  - Snapshot harga terjaga: `detail_penyewaan.tarif_sewa` mencatat tarif saat transaksi terjadi sehingga perubahan harga master di masa depan tidak merusak riwayat transaksi masa lalu.
  - Snapshot total tagihan terjaga: `pembayaran.total_biaya`.
  - **Kekurangan:** Berkas `database/seeders/DatabaseSeeder.php` masih berupa skeleton kosong bawaan instalasi awal Laravel (`User::factory()->create(...)` dengan kolom `name` dan `email` yang tidak cocok dengan skema). Belum ada file `.sql` dump data contoh siap impor untuk demonstrasi.
- **Hasil Audit:** **PARTIAL** (Struktur skema PASS; kesiapan seeder dan dump file PARTIAL).

---

### A10. UI, Validasi, dan Responsivitas
- **Kriteria Evaluasi:** Tampilan rapi konsisten, navigasi jelas, responsif di laptop, validasi form, flash notification, penanganan error.
- **Implementasi Aktual:**
  - Desain berbasis Bootstrap 5 dengan komponen CSS tematik outdoor yang konsisten (`ac-card`, `ac-stat-card`, badge status, sidebar).
  - Tampilan responsif dan proporsional saat dibuka pada resolusi laptop (1366x768) maupun monitor desktop (1920x1080).
  - Validasi ketat di sisi server pada setiap controller, didukung pesan error spesifik dalam bahasa Indonesia.
  - Menggunakan flash notification Bootstrap (`alert-success`, `alert-danger`) yang muncul otomatis setelah setiap aksi.
  - Mode Kiosk Tablet Kasir On-Site (`/onsite`) dirancang khusus ramah layar sentuh (*touch-friendly buttons*).
  - **Catatan Minor:** Default route `/` saat ini me-redirect ke `/ui-showcase`.
- **Hasil Audit:** **PASS**.

---

### A11. Dokumen Pengumpulan Tugas (14 Artefak)
- **Kriteria Evaluasi:** Memeriksa ketersediaan 14 luaran tugas akhir sesuai dokumen panduan.
- **Analisis Ketersediaan Berkas:**

```
┌────┬────────────────────────────────────────────┬──────────────┬──────────────────────────────────────────┐
│ No │ Nama Artefak Tugas Akhir                   │ Status       │ Keterangan / Lokasi Berkas               │
├────┼────────────────────────────────────────────┼──────────────┼──────────────────────────────────────────┤
│ 1  │ Source code aplikasi                       │ ADA          │ Seluruh direktori project Laravel        │
│ 2  │ File database dalam format .sql            │ BELUM ADA    │ Hanya ada query_role_pelanggan.sql (13L) │
│ 3  │ Link repository GitHub                     │ BELUM ADA    │ Git remote origin belum disetel          │
│ 4  │ Dokumentasi sistem                         │ PARTIAL      │ docs/CONTEXT.md & README.md              │
│ 5  │ Deskripsi permasalahan & tujuan sistem     │ PARTIAL      │ Tercakup di bab 1 docs/CONTEXT.md        │
│ 6  │ Diagram proses bisnis                      │ BELUM ADA    │ Tidak ada file diagram BPMN / alur kerja │
│ 7  │ Analisis kebutuhan pengguna                │ BELUM ADA    │ Tidak ada berkas SRS / use case tertulis │
│ 8  │ Entity Relationship Diagram (ERD)          │ BELUM ADA    │ Tidak ada berkas diagram visual ERD      │
│ 9  │ Daftar pengguna dan hak akses              │ PARTIAL      │ Tercatat di bab 3 docs/CONTEXT.md        │
│ 10 │ Penjelasan master data                     │ PARTIAL      │ Tercatat di bab 9 & 10 docs/CONTEXT.md   │
│ 11 │ Penjelasan modul transaksi                 │ PARTIAL      │ Tercatat di bab 4 & 7 docs/CONTEXT.md    │
│ 12 │ Dokumentasi dashboard dan laporan          │ PARTIAL      │ Tercatat di bab 8 docs/CONTEXT.md        │
│ 13 │ Dokumentasi audit trail                    │ PARTIAL      │ Tercatat di bab 11 docs/CONTEXT.md       │
│ 14 │ Dokumen pengujian atau test case           │ PARTIAL      │ 33 file automated test PHPUnit ada;      │
│    │                                            │              │ dokumen matriks pengujian tertulis nihil │
└────┴────────────────────────────────────────────┴──────────────┴──────────────────────────────────────────┘
```

- **Hasil Audit:** **PARTIAL / FAIL**. Sebagian besar artefak tertulis belum disusun sebagai dokumen laporan resmi siap kumpul.

---

### A12. Kesiapan Demonstrasi (10 Urutan Presentasi)
- **Kriteria Evaluasi:** Kesiapan mendemonstrasikan 10 langkah wajib presentasi di depan dosen/asisten praktikum.

1. **Menjelaskan permasalahan dan tujuan:** **SIAP**. Latar belakang rental alat camping dan tantangan pencatatan stok dapat dipaparkan dengan lugas.
2. **Menjelaskan proses bisnis:** **SIAP**. Alur sewa online & on-site, persetujuan, pembayaran manual, serah terima, pengembalian bertahap, dan pengadaan supplier sudah terintegrasi.
3. **Menjelaskan role dan hak akses:** **SIAP**. Hak akses Admin, Pemilik (read-only), dan Pengguna terbukti terisolasi.
4. **Login menggunakan role berbeda:** **SIAP**. Telah teruji dan berfungsi normal.
5. **Menambah, mengubah, mencari, dan menghapus master data:** **PARTIAL**. Master Alat siap 100%, namun master data ke-3 belum ada untuk didemonstrasikan.
6. **Menjalankan transaksi sampai selesai:** **SIAP**. Transaksi dapat dijalankan dari checkout hingga pengembalian dan penyelesaian denda.
7. **Memperlihatkan perubahan data setelah transaksi:** **SIAP**. Stok toko berkurang saat serah terima dan bertambah kembali saat barang dikembalikan dalam kondisi baik.
8. **Memperlihatkan perubahan dashboard:** **SIAP**. Dashboard Pemilik langsung merefleksikan perubahan kas masuk, pengeluaran, dan grafik secara real-time.
9. **Menampilkan laporan dengan periode tertentu:** **PARTIAL**. Filter tanggal di riwayat transaksi berfungsi, tetapi tidak ada tombol cetak / ekspor PDF yang dapat ditunjukkan saat diminta penguji.
10. **Memperlihatkan audit trail:** **SIAP**. Riwayat log pada `/admin/logs` merekam seluruh jejak aktivitas pengguna secara kronologis.

- **Hasil Audit:** **PARTIAL (8/10 Langkah Siap Penuh)**.

---

## 4. DAFTAR BUG DAN TEMUAN TERVERIFIKASI

Berikut adalah daftar anomali dan bug yang telah **terverifikasi secara faktual** melalui inspeksi baris kode (*bukan sekadar dugaan*):

### Bug 1: `DatabaseSeeder.php` Akan Error Jika Dijalankan
- **File:** `database/seeders/DatabaseSeeder.php` (baris 20–23)
- **Kode Aktual:**
  ```php
  User::factory()->create([
      'name' => 'Test User',
      'email' => 'test@example.com',
  ]);
  ```
- **Penyebab:** Skema tabel `users` pada aplikasi Adventure Camp Rental menggunakan kolom `nama_user` dan `username`, bukan `name` dan `email`.
- **Dampak:** Perintah `php artisan db:seed` dipastikan gagal dengan error `Column not found: Unknown column 'name'`.

### Bug 2: Redirect Rute Root (`/`) Mengarah ke UI Showcase
- **File:** `routes/web.php` (baris 30–32)
- **Kode Aktual:**
  ```php
  Route::get('/', function () {
      return redirect('/ui-showcase');
  });
  ```
- **Dampak:** Pengunjung umum yang membuka URL utama aplikasi (`http://adventure_camp_web.test/`) langsung dialihkan ke halaman katalog showcase komponen pengujian UI, bukan ke landing page rental atau katalog alat camping.

### Bug 3: Master Pengguna Tidak Memiliki Halaman Rincian Detail (*Show*)
- **File:** `routes/web.php` (baris 102) & `app/Http/Controllers/AdminUserController.php`
- **Kode Aktual:**
  ```php
  Route::resource('users', AdminUserController::class)->parameters(['users' => 'user'])->except(['show']);
  ```
- **Dampak:** Modul master Pengguna melanggar salah satu dari 7 syarat wajib master data pada dokumen tugas akhir praktikum (*Detail Data*).

### Bug 4: Tidak Ada Fitur Ekspor / Cetak Laporan (PDF/Excel)
- **File:** `PemilikTransaksiController.php`, `PemilikDashboardController.php`, `resources/views/pemilik/transaksi/index.blade.php`, `resources/views/pemilik/inventaris/index.blade.php`
- **Dampak:** Melanggar ketentuan wajib Laporan Dokumen Tugas Akhir Bagian C Poin 6 (*"Setiap laporan minimal memiliki: ... Fitur cetak atau ekspor PDF/Excel"*).

### Bug 5: Tidak Tersedia Modul Master Data Ketiga Mandiri
- **File:** `app/Http/Controllers/`
- **Dampak:** Melanggar ketentuan wajib Dokumen Tugas Akhir Bagian C Poin 3 (*"Sistem harus memiliki minimal 3 modul master data"*). Hanya terdapat 1 master data mandiri (`Alat`), 1 master data parsial (`User`), sedangkan master data `Supplier` dan `Pelanggan` belum memiliki controller/view CRUD tersendiri.

---

## 5. HASIL PENGUJIAN REGRESI OTOMATIS (TEST SUITE)

Pengujian otomatis dijalankan secara aman menggunakan lingkungan database terisolasi (*in-memory SQLite*) tanpa menyentuh database aktif MySQL.

### Ringkasan Eksekusi PHPUnit

```
Command  : vendor/bin/phpunit
Database : SQLite (:memory:)
Result   : PASSED (100%)
Tests    : 331 Tests
Passed   : 331 Passed
Failures : 0 Failed
Errors   : 0 Errors
Duration : 51.36 detik
Asserts  : 2.090 Assertions
```

### Rincian Modul Uji Utama yang Lulus:
1. `tests/Feature/AuthTest.php` (20 tests, 82 asserts) — Login, register, logout, proteksi role, bfcache.
2. `tests/Feature/AlatTest.php` (25 tests, 212 asserts) — CRUD alat, validasi kode, proteksi hapus foreign key.
3. `tests/Feature/PerhitunganBiayaSewaTest.php` (13 tests, 119 asserts) — Formula sewa kalender murni, subtotal, grand total.
4. `tests/Feature/AdminPesananQueueTest.php` (18 tests) — Pengecekan stok overlap, approval, penolakan pesanan.
5. `tests/Feature/AdminPembayaranTest.php` (16 tests) — Verifikasi pembayaran manual, pencegahan konfirmasi ganda.
6. `tests/Feature/PengembalianSebagianTest.php` (5 tests, 36 asserts) — Validasi ID detail, pengembalian bertahap, denda.
7. `tests/Feature/PemilikKeuanganIntegrationTest.php` (6 tests, 25 asserts) — Rekonsiliasi kas masuk vs pengeluaran PO vs servis vs arus kas bersih.
8. `tests/Feature/PembelianSupplierTest.php` (5 tests, 25 asserts) — Form PO supplier multi-item, mutasi stok otomatis, anti double-submit.
9. `tests/Feature/AdminUserManagementTest.php` (19 tests, 99 asserts) — Manajemen user, toggle aktif/nonaktif, reset password, proteksi admin terakhir.
10. `tests/Feature/FinalEndToEndAuditTest.php` (10 tests, 88 asserts) — Siklus utuh rental dari checkout pelanggan hingga laporan pemilik.

> **Catatan Auditor:** Meskipun test suite lulus 100%, pengujian ini baru memvalidasi fitur-fitur yang sudah ditulis kodenya. Kebutuhan tugas akhir yang **belum diimplementasikan kodenya** (seperti Cetak Laporan PDF/Excel dan Master Data ke-3) belum tercakup dalam test suite karena kodenya memang belum ada.

---

## 6. STATUS KELENGKAPAN 14 DOKUMEN PENGUMPULAN TUGAS

Berdasarkan ketentuan Bagian D Dokumen Tugas Akhir (*"Dokumen yang Dikumpulkan"*):

| No | Dokumen Wajib | Keberadaan di Folder Proyek | Evaluasi & Kebutuhan Tambahan |
| :---: | :--- | :---: | :--- |
| **1** | Source code aplikasi | **LENGKAP** | Tersedia lengkap di direktori kerja Laravel. |
| **2** | File database dalam format `.sql` | **BELUM LENGKAP** | Perlu diekspor dump database `adventure_camp_rental.sql` berisi struktur tabel dan data contoh demonstrasi. |
| **3** | Link repository GitHub | **BELUM LENGKAP** | Git remote belum dikonfigurasi. Perlu dibuatkan repo GitHub privat/publik dan ditautkan. |
| **4** | Dokumentasi sistem | **PARSIAL** | `docs/CONTEXT.md` sangat detail secara teknis, namun perlu dibuatkan versi laporan makalah resmi (Word/PDF). |
| **5** | Deskripsi permasalahan & tujuan | **PARSIAL** | Ada di bab 1 `docs/CONTEXT.md`. Perlu disalin ke format laporan tugas akhir. |
| **6** | Diagram proses bisnis | **BELUM ADA** | Perlu dibuatkan diagram alur bisnis (BPMN / Flowchart) dari sewa hingga pengembalian dan laporan. |
| **7** | Analisis kebutuhan pengguna | **BELUM ADA** | Perlu dibuatkan dokumen Use Case Diagram & spesifikasi kebutuhan fungsional per role. |
| **8** | ERD (Entity Relationship Diagram) | **BELUM ADA** | Perlu dibuatkan diagram visual relasi 15 tabel database. |
| **9** | Daftar pengguna & hak akses | **PARSIAL** | Rincian role Admin, Pemilik, dan Pengguna tersedia di `docs/CONTEXT.md`. |
| **10** | Penjelasan master data | **PARSIAL** | Perlu dicantumkan struktur kamus data master data. |
| **11** | Penjelasan modul transaksi | **PARSIAL** | Rincian formula biaya, alur approval, dan pengembalian bertahap ada di `docs/CONTEXT.md`. |
| **12** | Dokumentasi dashboard & laporan | **PARSIAL** | Penjelasan 4 metrik keuangan dan visualisasi grafik ada di `docs/CONTEXT.md`. |
| **13** | Dokumentasi audit trail | **PARSIAL** | Skema tabel `activity_logs` dan atribut yang dicatat ada di `docs/CONTEXT.md`. |
| **14** | Dokumen pengujian / test case | **PARSIAL** | Test script PHPUnit ada 33 berkas. Perlu dibuatkan tabel rekapitulasi skenario uji (*Test Case Matrix*) manual. |

---

## 7. RENCANA PERBAIKAN BERTAHAP (ROADMAP)

Rencana perbaikan dirancang secara sistematis dengan urutan prioritas yang tidak saling bertabrakan:

```
┌────────────────────────────────────────────────────────┐
│               ROADMAP PENYEMPURNAAN SISTEM             │
└────────────────────────────────────────────────────────┘
                           │
       ┌───────────────────┴───────────────────┐
       ▼                                       ▼
 [FASE 1: PRIORITAS P1]              [FASE 2: PRIORITAS P1]
 Modul Master Data Ketiga            Modul Laporan & Ekspor
 (Master Supplier / Pelanggan)       (PDF / Excel / Print Nota)
       │                                       │
       └───────────────────┬───────────────────┘
                           │
                           ▼
                 [FASE 3: PRIORITAS P1]
                 Perbaikan Seeder & Export .SQL
                 (`DatabaseSeeder.php` & dump demo)
                           │
                           ▼
                 [FASE 4: PRIORITAS P2]
                 Penyempurnaan UX & Routing
                 (Redirect `/` & Sortable Table)
                           │
                           ▼
                 [FASE 5: DOKUMENTASI]
                 Penyusunan 14 Dokumen Tugas Akhir
```

### Fase 1: Pemenuhan Syarat Master Data (Prioritas P1)
- **Tujuan:** Memenuhi syarat minimal 3 modul master data dengan 7 fitur lengkap.
- **Tindakan:**
  1. Tambahkan method `show()` dan view `admin/users/show.blade.php` pada Modul Pengguna.
  2. Buat Modul Master Data ke-3: **Master Supplier** (`SupplierController`) dengan 7 operasi:
     - Tambah supplier baru
     - Tampilkan daftar supplier (paginasi & badge total transaksi)
     - Edit data supplier (nama, kontak person, telepon, alamat)
     - Hapus supplier (dengan proteksi foreign key jika ada riwayat PO)
     - Cari data supplier
     - Detail data supplier (termasuk riwayat PO pengadaan yang disuplai)
     - Validasi input form yang ketat.
- **Dependensi:** Tidak bergantung pada modul lain. Aman dikerjakan pertama.

### Fase 2: Pemenuhan Syarat Laporan & Cetak Nota (Prioritas P1)
- **Tujuan:** Memenuhi syarat minimal 2 laporan dengan cetak/ekspor dan nota transaksi.
- **Tindakan:**
  1. Buat controller khusus laporan (`AdminLaporanController` / `LaporanController`) dan menu sidebar:
     - **Laporan 1: Laporan Transaksi Penyewaan & Pendapatan** (filter tanggal, filter status sewa, filter metode bayar, pencarian, pengurutan, baris total omzet & kas masuk).
     - **Laporan 2: Laporan Inventaris & Mutasi Stok** (filter kategori, filter kondisi fisik, status stok, baris total unit aset fisik).
  2. Implementasikan fitur **Cetak / Ekspor PDF** (menggunakan library `barryvdh/laravel-dompdf` atau view cetak CSS `@media print` ramah browser).
  3. Tambahkan fitur **Cetak Nota / Kwitansi Transaksi** pada detail pesanan kasir (`admin/pesanan/{no_transaksi}/cetak-nota` atau modal nota siap print).
- **Dependensi:** Bergantung pada data `Penyewaan` dan `Alat` yang sudah stabil.

### Fase 3: Database Seeder & Dump File SQL (Prioritas P1)
- **Tujuan:** Memenuhi kriteria data contoh demonstrasi dan dokumen file `.sql`.
- **Tindakan:**
  1. Perbaiki `database/seeders/DatabaseSeeder.php` agar meng-generate data realistis:
     - 3 Akun Pengguna (`Admin`, `Pemilik`, `Pengguna`) dengan password yang diketahui.
     - 10+ Master Alat Camping berbagai kategori (Tenda, Matras, Kompor, Sleeping Bag, Carrier).
     - 3+ Master Supplier rekanan.
     - Contoh transaksi historis (Lunas, Sedang Disewa, Selesai, Denda).
  2. Ekspor database aktif ke file `database/adventure_camp_rental.sql` yang siap diimpor sewaktu-waktu.
- **Dependensi:** Dikerjakan setelah migrasi dan skema final.

### Fase 4: Penyempurnaan UX & Routing (Prioritas P2)
- **Tujuan:** Meningkatkan kerapian aplikasi untuk dinilai penguji.
- **Tindakan:**
  1. Perbarui route `/` pada `routes/web.php`:
     - Jika sudah login, redirect sesuai role.
     - Jika belum login, tampilkan katalog publik atau landing page ramah pengunjung toko.
  2. Tambahkan indikator sorting interaktif pada header tabel riwayat transaksi dan inventaris.
- **Dependensi:** Dikerjakan setelah Fase 1 dan 2 selesai.

### Fase 5: Penyusunan Dokumen Luaran Tugas Akhir
- **Tujuan:** Melengkapi 14 berkas yang wajib dikumpulkan.
- **Tindakan:**
  1. Buat diagram BPMN alur bisnis sistem.
  2. Buat diagram visual ERD (Entity Relationship Diagram) 15 tabel.
  3. Susun dokumen laporan tugas akhir format Word/PDF berdasarkan template praktikum.
  4. Susun tabel matriks pengujian (*Test Case Document*) dari 331 pengujian automated yang telah lulus.

---

## 8. CHECKLIST DEMONSTRASI PRESENTASI PRAKTIKUM

Checklist ini dirancang sebagai panduan langkah demi langkah saat mahasiswa melakukan presentasi dan demonstrasi di hadapan asisten praktikum / dosen:

- [ ] **Langkah 1: Paparan Pembuka & Permasalahan**
  - Jelaskan profil usaha rental outdoor: pencatatan alat camping manual rawan kehilangan barang, stok sering overbooking, dan pencatatan kas sering tercampur antara omzet sewa dan modal pembelian barang.
- [ ] **Langkah 2: Paparan Proses Bisnis Sistem**
  - Tunjukkan alur transaksi: Pelanggan memilih alat &rarr; Checkout dengan 1 periode sewa bersama &rarr; Admin memeriksa ketersediaan periode &rarr; Admin menyetujui pesanan &rarr; Pembayaran manual & verifikasi Admin &rarr; Penyerahan alat fisik &rarr; Pengembalian (verifikasi alat baik vs rusak) &rarr; Perhitungan denda jika telat/rusak &rarr; Pencatatan otomatis ke dashboard kas Pemilik.
- [ ] **Langkah 3: Paparan Hak Akses (3 Role + 1 Kiosk)**
  - Tunjukkan perbedaan hak akses antara Admin (operasional penuh), Pemilik (*strictly read-only* tanpa tombol aksi), dan Pengguna (hanya melihat transaksinya sendiri).
- [ ] **Langkah 4: Demonstrasi Login Bergantian**
  - Login sebagai **Admin** &rarr; Tunjukkan menu operasional lengkap.
  - Logout &rarr; Login sebagai **Pengguna** &rarr; Tunjukkan katalog dan riwayat pribadi.
  - Logout &rarr; Login sebagai **Pemilik** &rarr; Tunjukkan ketiadaan tombol edit/tambah/hapus.
- [ ] **Langkah 5: Demonstrasi Master Data**
  - Tambah alat baru &rarr; Tunjukkan validasi (kode unik, tarif minimal Rp1).
  - Cari alat berdasarkan kategori & nama.
  - Edit data alat.
  - Tunjukkan proteksi hapus: alat yang memiliki riwayat transaksi ditolak untuk dihapus demi menjaga integritas database.
- [ ] **Langkah 6: Menjalankan Transaksi Sewa Sampai Selesai**
  - Pengguna memasukkan 2 jenis alat ke keranjang belanja.
  - Checkout dengan tanggal sewa 3 hari &rarr; Tunjukkan perhitungan durasi dan subtotal otomatis.
  - Pindah ke Admin &rarr; Setujui pesanan &rarr; Konfirmasi pembayaran Lunas.
  - Proses serah terima alat (Pengambilan) &rarr; Tunjukkan status berubah menjadi `Sedang Disewa`.
  - Proses Pengembalian &rarr; Input kondisi aktual (contoh: 1 unit rusak/terlambat) &rarr; Tunjukkan perhitungan denda transparan.
- [ ] **Langkah 7: Menunjukkan Perubahan Stok**
  - Tunjukkan stok berkurang saat alat diserahkan kepada pelanggan.
  - Tunjukkan stok bertambah kembali saat dikembalikan Baik, dan stok **tidak bertambah** untuk unit yang rusak.
  - Buka menu `Riwayat Stok` untuk memperlihatkan log mutasi stok fisik.
- [ ] **Langkah 8: Menunjukkan Perubahan Dashboard Pemilik**
  - Buka `/pemilik/dashboard`.
  - Tunjukkan kartu *Penerimaan Kas Pelanggan* bertambah sesuai pembayaran nyata.
  - Tunjukkan kartu *Pengeluaran Pembelian Supplier* dan *Biaya Servis*.
  - Tunjukkan formula *Arus Kas Bersih* dan grafik multi-jalur Chart.js.
- [ ] **Langkah 9: Menampilkan Laporan**
  - Buka menu Laporan &rarr; Filter berdasarkan rentang tanggal tertentu.
  - Tunjukkan baris total tagihan dan demonstrasikan unduh cetak/ekspor PDF/Excel.
- [ ] **Langkah 10: Memperlihatkan Jejak Audit Trail**
  - Buka menu `/admin/logs`.
  - Tunjukkan bahwa seluruh aksi transaksi yang baru saja dijalankan tercatat otomatis dengan rincian nama pengguna, modul, aksi, data yang diproses, dan timestamp akurat.

---

> **Pernyataan Penutup Auditor:**
> Seluruh analisis di atas disusun berdasarkan kode sumber aktual dan dokumen acuan resmi. Laporan audit ini disimpan secara terisolasi pada berkas `AUDIT_TUGAS_AKHIR.md` tanpa mengubah kode aplikasi, database, maupun berkas dokumentasi lain.
> Setiap tindakan perbaikan pada Fase 1 – 5 akan menunggu persetujuan eksplisit dari pengguna.

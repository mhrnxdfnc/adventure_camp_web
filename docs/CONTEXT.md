# CONTEXT.md — Adventure Camp Rental

## 1. Identitas dan Tujuan Proyek

**Nama proyek:** Sistem Informasi Penyewaan Alat Camping pada Adventure Camp Rental.

**Tujuan:** Membangun aplikasi web untuk mengelola katalog alat camping, pemesanan dan penyewaan, pembayaran, pengembalian, denda, inventaris, serta pemantauan bisnis.

Aplikasi memiliki tiga role utama:
1. Admin
2. Pengguna
3. Pemilik

Sistem harus memiliki alur bisnis yang jelas, menjaga konsistensi stok dan transaksi, serta membatasi akses sesuai hak setiap role.

### Teknologi

- Backend: Laravel dan PHP.
- Frontend: Blade Template, HTML, CSS, Bootstrap 5, dan JavaScript seperlunya.
- Database: MySQL.
- Lingkungan pengembangan: Laragon.
- Version control: Git.

Gunakan arsitektur Laravel monolith. Hindari penggunaan Flutter, Dart, emulator Android, frontend SPA terpisah, atau API PHP terpisah untuk aplikasi utama.

---

## 2. Instruksi Wajib untuk AI Coding Agent

1. Baca file `CONTEXT.md` ini sebelum melakukan pekerjaan.
2. Audit proyek, source code, database, dan fitur yang sudah tersedia sebelum mengubah kode.
3. Jangan langsung menulis ulang seluruh proyek atau mengganti kode yang besar dengan implementasi sederhana yang menghilangkan fitur lama.
4. Pertahankan fitur lama yang masih relevan dan sudah berjalan.
5. Jangan mengarang struktur database. Verifikasi tabel, kolom, primary key, foreign key, enum, indeks, dan data yang tersedia.
6. Pertahankan database `adventure_camp_rental` beserta data lamanya.
7. Dilarang menjalankan `php artisan migrate:fresh`, `db:wipe`, atau perintah destruktif lain yang dapat menghapus database atau data.
8. Gunakan migration yang aman dan kompatibel dengan data lama.
9. Jangan menyimpan password dalam bentuk plaintext. Gunakan hashing Laravel.
10. Jangan menyimpan `.env`, password, secret key, atau data sensitif ke Git.
11. Setiap fungsi yang selesai harus diuji dan dibuatkan commit Git tersendiri.
12. Jangan menggabungkan beberapa fungsi yang tidak berkaitan dalam satu commit.
13. Sebelum commit, periksa `git status`, `git diff`, dan hasil pengujian.
14. Jangan menghapus perubahan pengguna atau mengembalikan file ke versi lama tanpa izin.
15. Jangan melakukan push ke remote repository tanpa instruksi eksplisit.
16. Jangan membuat tombol atau halaman yang terlihat berfungsi tetapi belum memiliki implementasi nyata.
17. Jika ada keputusan bisnis yang belum jelas, jelaskan pilihan dan dampaknya sebelum mengimplementasikan logika yang memengaruhi uang, stok, atau transaksi.
18. Jangan menganggap pesanan yang diajukan sudah disetujui atau pembayaran sudah lunas.
19. Jangan membuat QR code acak yang seolah-olah merupakan QRIS pembayaran valid.
20. Mulai dengan audit saja. Sampaikan laporan audit dan rencana implementasi sebelum melakukan perubahan kode.

---

## 3. Role dan Hak Akses

Semua role harus memiliki dashboard, menu, dan hak akses berbeda. Terapkan autentikasi dan otorisasi di backend menggunakan middleware, policy, atau mekanisme Laravel yang sesuai.

Menyembunyikan menu di frontend saja tidak cukup.

### 3.1 Admin

Admin bertanggung jawab atas operasional penyewaan.

Hak akses Admin:

- Melihat dashboard operasional.
- Melihat pesanan baru dari Pengguna.
- Melihat detail pelanggan, daftar alat, jumlah, tanggal sewa, tanggal kembali, dan metode pembayaran yang dipilih.
- Memeriksa ketersediaan alat.
- Menyetujui atau menolak pesanan.
- Memberikan alasan jika pesanan ditolak.
- Mengelola data alat camping (`/admin/alat`).
- Mengelola data rekanan Master Supplier (`/admin/supplier`) dengan 7 operasi penuh (tambah, tampil, edit, hapus dengan proteksi foreign key, cari, detail riwayat pengadaan PO, dan validasi server-side).
- Mengelola akun pengguna (`/admin/users`) dengan 7 operasi penuh termasuk rincian detail akun (`/admin/users/{user}`), reset password terenkripsi, toggle status aktif/nonaktif, serta perlindungan akun administrator aktif terakhir.
- Mengonfirmasi pembayaran setelah pembayaran benar-benar diterima atau diverifikasi.
- Memeriksa bukti transfer jika metode transfer menggunakan verifikasi manual.
- Melihat proses pengambilan alat.
- Mengelola proses pengembalian alat, termasuk mendukung pengembalian bertahap/sebagian per baris detail transaksi (`id_detail`), memverifikasi kondisi aktual per unit, dan menghitung denda jika terlambat atau rusak.
- Mengakses menu Inventaris & Stok untuk memantau stok fisik, stok siap sewa, unit sedang disewa, unit dialokasikan, unit perlu perbaikan, dan unit rusak/afkir.
- Mencatat penyesuaian stok manual (tambah/kurang) dengan alasan wajib yang tercatat di `riwayat_stok` dan `activity_logs`.
- Mengelola pengadaan pembelian stok dari supplier (`/admin/inventaris/pembelian`), mencatat transaksi PO supplier, menambah stok fisik toko secara otomatis, dengan proteksi pencegahan submit ganda (*double-submit*).
- Mengelola penanganan unit rusak / menunggu perbaikan (`/admin/inventaris/{kode_alat}`): mencatat antrean servis, mencatat pengeluaran biaya perbaikan teknisi, mengonfirmasi unit selesai diperbaiki kembali ke stok siap sewa, atau memensiunkan/afkir unit yang rusak permanen pada tabel `penanganan_kerusakan`.
- Melihat laporan dan riwayat aktivitas sesuai kebutuhan operasional *(Catatan Status Audit A7: Riwayat aktivitas tersedia di `/admin/logs`, namun menu Laporan untuk Admin saat ini belum tersedia dan dijadwalkan pada Roadmap Fase 2)*.

Admin tidak boleh menyetujui pesanan jika stok tidak mencukupi. Admin tidak boleh mengonfirmasi pembayaran hanya karena Pengguna memilih metode pembayaran atau mengunggah bukti yang belum diverifikasi.

### 3.2 Pengguna

Pengguna adalah pelanggan yang menggunakan aplikasi untuk memesan alat camping secara online.

Hak akses Pengguna:

- Registrasi dan login.
- Melihat katalog alat camping.
- Melihat nama, kategori, tarif harian per unit, kondisi, dan stok yang dapat disewa.
- Memilih satu atau beberapa jenis alat pada katalog dan menentukan jumlah unit masing-masing dimulai dari kuantitas 0 (durasi sewa tidak dipilih di katalog atau halaman rincian detail).
- Mengelola keranjang belanja yang menampung beberapa jenis alat beserta jumlah unitnya masing-masing. Sebelum tanggal sewa dipilih, keranjang hanya menampilkan estimasi subtotal sementara per hari, bukan total final.
- Menentukan satu periode sewa bersama (satu tanggal mulai sewa dan satu tanggal kembali) untuk seluruh isi keranjang pada saat checkout.
- Melihat ringkasan pesanan dengan durasi sewa bersama (selisih hari kalender murni) dan total biaya final yang dihitung ulang oleh server.
- Memilih metode pembayaran: QRIS (kasir), transfer bank, atau cash.
- Mengirim pesanan kepada Admin untuk diperiksa.
- Melihat status dan alasan penolakan pesanan.
- Melihat instruksi pembayaran setelah pesanan disetujui Admin.
- Mengunggah bukti transfer jika diperlukan.
- Melihat status pembayaran dan tagihan.
- Melihat riwayat pesanan miliknya sendiri.
- Melihat informasi pengambilan dan pengembalian.
- Melihat denda jika ada.

Pengguna hanya boleh melihat dan mengakses transaksi miliknya sendiri.

Pengguna tidak boleh:
- Mengakses dashboard Admin atau Pemilik.
- Mengonfirmasi pesanan sendiri.
- Mengubah status pembayaran secara langsung.
- Mengubah tarif atau stok.
- Melihat pesanan atau data pribadi pengguna lain.
- Menandai pembayaran sendiri sebagai lunas.

### 3.3 Pemilik

Pemilik bertindak sebagai pengawas bisnis dan memiliki hak akses **strictly read-only**. Seluruh interaksi ditujukan untuk pemantauan performa bisnis tanpa kemampuan memanipulasi data operasional.

Pemilik boleh:

- Melihat ringkasan dan rincian **Penerimaan Kas Pelanggan** (hanya pembayaran lunas nyata terkonfirmasi, bukan total nilai pesanan yang belum dibayar).
- Melihat ringkasan dan rincian **Pengeluaran Pembelian Stok Supplier** (transaksi PO pengadaan inventaris yang sah).
- Melihat ringkasan dan rincian **Pengeluaran Biaya Perbaikan Alat** (catatan servis dan perbaikan unit alat rusak).
- Melihat ringkasan dan rekonsiliasi **Arus Kas Bersih** harian dan bulanan (`Penerimaan Kas - Pembelian Stok - Biaya Perbaikan`).
- Melihat grafik tren penjualan dan arus kas harian serta bulanan dengan visualisasi multi-jalur (Penerimaan Kas, Pengeluaran, dan Arus Kas Bersih).
- Membuka modal rincian untuk setiap kartu metrik pada dashboard.
- Membuka rincian kartu ringkasan stok (`Total Stok Fisik`, `Sedang Disewa`, `Stok Siap Sewa`, `Perlu Perbaikan`, `Rusak`) untuk melihat daftar unit alat di balik angka tersebut.
- Melihat riwayat transaksi pesanan pelanggan (baik pesanan online maupun on-site).
- Melihat riwayat pengadaan stok supplier dan riwayat servis penanganan kerusakan.

Pemilik tidak boleh:
- Menambah, mengubah, atau menghapus data operasional apa pun.
- Menyetujui atau menolak pesanan.
- Mengonfirmasi pembayaran.
- Mengubah stok atau tarif master alat.
- Mengubah status pengembalian atau memproses serah terima alat.
- Mengubah atau menghapus denda.
- Mencatat pembelian supplier atau penanganan kerusakan (seluruh tombol aksi ditiadakan pada role Pemilik).

Seluruh halaman Pemilik harus bersifat read-only, termasuk ketika URL diakses langsung atau request dikirim secara manual.

### 3.4 Mode Khusus On-Site (Tablet Kiosk di Kasir)

Aplikasi menyediakan antarmuka khusus pemesanan on-site menggunakan tablet fisik di depan kasir toko (`/tablet` diarahkan ke `/onsite`):

- **Akses Tanpa Akun Pengguna:** Pengunjung toko memesan mandiri tanpa login akun `users` (`id_user = null`, `sumber_pesanan = 'on-site'`).
- **Isolasi Sesi Kiosk:** Dilindungi middleware `EnsureOnsiteKioskSession` agar sesi keranjang belanja terisolasi dan dapat di-reset kapan saja (tombol batal/reset) atau otomatis dibersihkan saat pesanan selesai. Data pemesan sebelumnya tidak boleh tertinggal pada pelanggan berikutnya.
- **Alur Pemesanan:** Mengikuti aturan katalog, keranjang belanja, dan checkout durasi bersama yang identik dengan pelanggan online.
- **Pencatatan Identitas:** Pelanggan mengisi nama lengkap, nomor telepon, dan alamat saat checkout. Sistem memvalidasi atau membuat data pelanggan pada tabel `pelanggan` berdasarkan nomor telepon.
- **Verifikasi Operasional Kasir:** Pesanan masuk ke antrean Admin dengan status `Menunggu Konfirmasi` untuk diperiksa ketersediaannya dan diverifikasi pembayarannya oleh kasir/Admin.

---

## 4. Alur Pemesanan

Alur pemesanan dirancang secara terstruktur mulai dari eksplorasi katalog hingga penyelesaian transaksi. Sistem menerapkan pemisahan yang tegas antara pemilihan unit barang di katalog, pengelolaan keranjang belanja, penentuan periode sewa bersama saat checkout, persetujuan Admin, dan verifikasi pembayaran.

### Tahap 1 — Pemilihan Alat di Katalog dan Rincian Detail

1. Pengguna membuka katalog atau halaman rincian detail alat camping.
2. Sistem menampilkan data alat: nama, kode, kategori, foto/ilustrasi nyata, deskripsi/spesifikasi, kondisi, stok siap sewa, dan **tarif sewa per hari** (format: `Rp X / hari`).
3. **Aturan Jumlah Unit:** Kontrol kuantitas unit pada setiap kartu katalog dimulai dari angka `0`. Tombol minus tidak boleh menghasilkan nilai negatif. Tombol plus dan input manual tidak boleh melebihi stok tersedia. Tombol tambah ke keranjang dinonaktifkan jika jumlah unit 0, input tidak valid, stok habis, atau kondisi alat tidak layak sewa.
4. **Tanpa Pemilihan Durasi di Awal:** Katalog dan rincian detail **tidak menyediakan pemilihan tanggal maupun durasi sewa per barang**. Durasi tidak dipilih per item.
5. **Kebersihan Informasi Rincian Detail:** Halaman rincian detail berfokus penuh pada informasi dan spesifikasi barang. Halaman ini **tidak menampilkan penjelasan teknis, variabel kode internal, rumus developer, atau pesan perhitungan biaya internal** yang tidak dibutuhkan pelanggan.
6. Pengguna menambahkan satu atau beberapa jenis alat beserta kuantitas unitnya ke dalam keranjang belanja.

### Tahap 2 — Keranjang Belanja

1. Keranjang belanja menampung beberapa jenis alat camping, masing-masing dengan jumlah unit yang dipilih.
2. Pengguna dapat menambah, mengurangi unit (dalam batas stok tersedia), atau menghapus item dari keranjang.
3. Keranjang menampilkan ringkasan item: nama, kode, kategori, stok relevan, tarif harian, jumlah unit, serta **subtotal sementara per hari** (`tarif harian × jumlah unit`).
4. Keranjang menampilkan total jumlah jenis alat, total unit fisik, dan **total sementara per hari** (`sum(subtotal per hari)`).
5. **Penegasan Estimasi Sementara:** Sebelum tanggal sewa ditentukan, keranjang secara eksplisit menampilkan bahwa angka tersebut adalah **subtotal dan total sementara per hari**, bukan tagihan final transaksi.
6. Pengguna menekan tombol **Lanjut ke Checkout**.

### Tahap 3 — Checkout dan Penentuan Periode Sewa Bersama

1. Halaman checkout menampilkan ringkasan seluruh barang dari keranjang belanja.
2. **Satu Periode Sewa Bersama:** Sistem menyediakan **SATU input "Tanggal Mulai Sewa"** dan **SATU input "Tanggal Kembali"** untuk seluruh keranjang. Tidak ada input tanggal atau durasi terpisah per barang.
3. **Perhitungan Durasi Hari:** Durasi sewa dihitung otomatis dari selisih tanggal kalender murni:
   $$\text{Durasi hari} = \text{Tanggal Kembali} - \text{Tanggal Mulai}$$
   *(Contoh: sewa tanggal 10 Oktober dan kembali tanggal 13 Oktober berarti durasi 3 hari).*
4. **Rincian Biaya Final:** Sistem menampilkan subtotal final per jenis alat (`tarif harian × jumlah unit × durasi bersama`) dan total transaksi final (`sum(subtotal)`).
5. **Pilihan Metode Pembayaran:** Pengguna memilih metode pembayaran yang didukung: QRIS (kasir toko), Transfer Bank (dengan verifikasi manual), atau Cash (tunai di kasir).
6. Pengguna menekan tombol untuk mengirimkan pesanan.

### Tahap 4 — Validasi Server dan Penyimpanan Pesanan

1. **Validasi Mutlak Sisi Server:** Seluruh data (ketersediaan stok, keabsahan rentang tanggal, perhitungan durasi, subtotal per item, dan total transaksi final) divalidasi dan dihitung ulang di backend. Sistem tidak pernah mempercayai angka kalkulasi dari browser/klien.
2. **Penyimpanan Transaksi:** Sistem menyimpan satu transaksi pada tabel `penyewaan` dengan periode bersama (`tanggal_sewa`, `tanggal_kembali`), detail setiap alat pada tabel `detail_penyewaan` dengan snapshot `tarif_sewa` harian, serta tagihan pada tabel `pembayaran` dengan snapshot `total_biaya`.
3. Pesanan disimpan dengan status awal `Menunggu Konfirmasi` Admin.
4. Pada tahap ini pesanan belum disetujui, belum dialokasikan secara permanen, dan belum dibayar.

### Tahap 5 — Admin Memeriksa Pesanan

1. Pesanan muncul di menu pesanan masuk Admin (baik dari sumber `online` maupun `on-site`).
2. Admin membuka detail pesanan.
3. Sistem menampilkan rincian alat, jumlah unit, periode sewa bersama, durasi hari, total biaya final, dan metode pembayaran.
4. Sistem memeriksa ketersediaan stok aktual untuk periode tersebut.
5. Admin meninjau hasil pemeriksaan:
   - Jika stok mencukupi, Admin dapat menyetujui pesanan (`Disetujui`).
   - Jika stok tidak mencukupi, Admin menolak pesanan (`Ditolak`) dan wajib mengisikan alasan penolakan.
6. Pengguna dapat melihat keputusan Admin pada dashboard / riwayat transaksinya.

Pemeriksaan stok harus dilakukan ulang di backend saat persetujuan. Jangan hanya mengandalkan stok tampilan katalog.

### Tahap 6 — Sistem Menampilkan Instruksi Pembayaran

Setelah pesanan disetujui Admin:

1. Sistem memastikan ulang total biaya final sesuai snapshot transaksi.
2. Sistem menampilkan halaman instruksi pembayaran berdasarkan metode yang dipilih (QRIS kasir, nomor rekening resmi transfer bank, atau pembayaran cash di toko).
3. Status pembayaran berada pada status `Belum Dibayar` atau `Menunggu Verifikasi`.
4. Pilihan metode pembayaran tidak otomatis mengubah status menjadi lunas.

### Tahap 7 — Admin Mengonfirmasi Pembayaran

1. Pengguna melakukan pembayaran sesuai instruksi.
2. Jika menggunakan transfer bank, Pengguna mengunggah foto bukti transfer melalui sistem.
3. Jika menggunakan QRIS, pembayaran dilakukan menggunakan QRIS resmi yang disodorkan oleh kasir toko.
4. Jika menggunakan cash, pembayaran diserahkan langsung kepada kasir/Admin.
5. Admin memeriksa keabsahan pembayaran secara manual. Bukti transfer yang diunggah tidak otomatis dianggap valid.
6. Setelah dana dipastikan masuk atau diterima, Admin mengonfirmasi pembayaran di sistem.
7. Sistem mencatat waktu konfirmasi, Admin yang memverifikasi, metode pembayaran, dan nominal pembayaran.
8. Status pembayaran berubah menjadi `Lunas`.

### Tahap 8 — Pengambilan Alat

Setelah pesanan disetujui dan status pembayaran lunas (atau sesuai kesepakatan operasional):

1. Admin memproses serah terima alat fisik kepada pelanggan.
2. Sistem mencatat waktu pengambilan dan memverifikasi jumlah unit alat yang diserahkan.
3. Status penyewaan diperbarui menjadi `Sedang Disewa`.
4. Sistem mempertahankan relasi antara pesanan, detail unit alat, dan pembayaran.

### Tahap 9 — Pengembalian Alat, Pengembalian Bertahap, dan Pencatatan Kondisi Aktual

1. Admin menerima pengembalian alat dan mencatat tanggal pengembalian aktual.
2. **Identifikasi Detail Berdasarkan Primary Key (`id_detail`):** Satu transaksi dapat memuat barang berkode sama dalam beberapa baris `detail_penyewaan` dengan kuantitas berbeda. Sistem mengidentifikasi dan memvalidasi pengembalian menggunakan primary key `id_detail` per baris (bukan sekadar `kode_alat`), sehingga baris detail tidak saling bertabrakan.
3. **Dukungan Pengembalian Bertahap / Sebagian:** Sistem mendukung pengembalian bertahap apabila pelanggan mengembalikan sebagian unit terlebih dahulu (`pengembalian_sebagian = true`). Sisa unit yang wajib dikembalikan dihitung per baris detail:
   $$\text{Sisa Belum Kembali} = \text{Jumlah Diserahkan / Awal} - (\text{Kembali Baik} + \text{Perlu Perbaikan} + \text{Rusak})$$
4. **Validasi Kuantitas Pengembalian:**
   - Kuantitas yang dikembalikan pada pemeriksaan tidak boleh melebihi sisa unit yang belum kembali.
   - Jika bukan pengembalian sebagian, seluruh sisa unit wajib diselesaikan sekaligus.
   - Pengembalian berulang tidak boleh menambah stok toko dua kali.
5. **Pemeriksaan Kondisi Fisik per Unit:** Admin memeriksa kondisi setiap unit alat: Baik, Perlu Perbaikan, atau Rusak/Hilang. Kerusakan pada satu unit tidak boleh menyebabkan seluruh unit dalam transaksi dianggap rusak.
6. **Pemisahan Pemulihan Stok Siap Sewa:**
   - Hanya unit berkondisi **Baik** yang otomatis ditambahkan kembali ke stok siap sewa toko (`alat.stok += jumlah_kembali_baik`) dan dicatat pada `riwayat_stok`.
   - Unit yang dinyatakan **Perlu Perbaikan** atau **Rusak** dicatat pada kolom pelacakan detail (`jumlah_perlu_perbaikan`, `jumlah_rusak`) dan **TIDAK** ditambahkan ke stok siap sewa toko.
7. **Perhitungan Denda Transparan:** Jika terdapat keterlambatan hari kalender terhadap `tanggal_kembali` atau kerusakan fisik, denda dihitung transparan. Apabila terdapat nominal denda, alasan denda wajib diisi. Denda dicatat pada tabel `denda`.
8. **Penyelesaian Transaksi:**
   - Status transaksi penyewaan baru berubah menjadi `Selesai` ketika **seluruh unit pada seluruh baris detail** telah lunas dikembalikan.
   - Apabila masih ada sisa unit yang belum dikembalikan, status pesanan tetap `Sedang Disewa` dan status pengembalian ditandai `Sebagian`.

Unit alat yang rusak atau perlu perbaikan tidak boleh dihitung sebagai stok siap sewa.

---

## 5. Instruksi Pembayaran

### 5.1 QRIS

Pada versi awal, sistem **tidak menggunakan integrasi payment gateway dan tidak membuat QRIS acak**.

Setelah pesanan disetujui Admin, halaman pembayaran QRIS menampilkan:

- Nomor transaksi.
- Rincian alat.
- Jumlah setiap alat.
- Durasi sewa.
- Total tagihan.
- Status pembayaran.
- Pemberitahuan: "Silakan minta kode QRIS kepada kasir untuk melanjutkan pembayaran."

Pengguna melakukan pembayaran menggunakan QRIS yang diberikan oleh kasir.

Admin kemudian mengonfirmasi pembayaran setelah pembayaran benar-benar diterima atau diverifikasi melalui prosedur operasional.

QRIS tidak boleh otomatis dianggap lunas hanya karena Pengguna memilih metode tersebut atau membuka halaman pembayaran.

Integrasi QRIS otomatis dapat dikembangkan kemudian jika tersedia penyedia pembayaran resmi dan kredensial yang sah.

### 5.2 Transfer bank

Setelah pesanan disetujui, halaman transfer menampilkan:

- Nomor transaksi.
- Rincian pesanan.
- Total tagihan.
- Nama bank.
- Nomor rekening.
- Nama pemilik rekening.
- Petunjuk transfer.
- Status pembayaran.
- Form unggah bukti transfer jika menggunakan verifikasi manual.

Informasi rekening harus berasal dari konfigurasi yang valid. Jangan mengarang nomor rekening atau nama pemilik rekening.

Jika informasi rekening belum diatur, tampilkan pemberitahuan bahwa Pengguna perlu meminta informasi rekening kepada kasir atau Admin.

Bukti transfer hanya menjadi bahan pemeriksaan. Status pembayaran baru menjadi lunas setelah Admin melakukan verifikasi yang sah.

### 5.3 Cash

Setelah pesanan disetujui, halaman cash menampilkan:

- Nomor transaksi.
- Rincian pesanan.
- Total tagihan.
- Status pembayaran.
- Pemberitahuan bahwa pembayaran dilakukan secara tunai kepada kasir atau Admin.

Status pembayaran berubah menjadi lunas setelah Admin menerima uang dan mengonfirmasi transaksi.

### 5.4 Pemisahan status

Status pesanan dan status pembayaran harus dipisahkan.

Contoh status pesanan:
- Menunggu Konfirmasi Admin.
- Disetujui.
- Ditolak.
- Menunggu Pembayaran.
- Siap Diambil.
- Sedang Disewa.
- Selesai.
- Dibatalkan.

Contoh status pembayaran:
- Belum Dibayar.
- Menunggu Verifikasi.
- Lunas.
- Ditolak.
- Dikembalikan jika fitur pengembalian dana memang diperlukan.

Status di atas merupakan rancangan awal. Audit status lama dan struktur database sebelum melakukan perubahan.

Status pesanan Disetujui tidak berarti pembayaran Lunas.

Transisi status harus divalidasi di backend. Pesanan yang ditolak atau dibatalkan tidak boleh diubah menjadi lunas secara sembarangan.

---

## 6. Aturan Stok, Ketersediaan, dan Konsistensi Inventaris

### 6.1 Definisi Metrik Stok Inventaris

Sistem membedakan secara tegas klasifikasi metrik stok agar perhitungan di Katalog Pengguna, Dashboard Admin, dan Dashboard Pemilik konsisten:

1. **Stok Fisik di Toko (`stok_toko` / `alat.stok`):** Kuantitas fisik alat yang saat ini berada di rak/gudang toko.
2. **Stok Siap Sewa (`stok_siap_sewa`):** Kuantitas alat di toko yang berkondisi **Baik** dan siap langsung disewakan (`kondisi === 'Baik' ? stok_toko : 0`).
3. **Stok Sedang Disewa (`stok_sedang_disewa`):** Kuantitas unit yang sedang berada di tangan pelanggan pada transaksi penyewaan aktif (`Sedang Disewa`, `Berjalan`). Dihitung dari sisa unit yang belum dikembalikan pada transaksi aktif tersebut.
4. **Stok Dialokasikan (`stok_dialokasikan`):** Kuantitas unit yang telah terikat oleh pesanan aktif di toko sebelum serah terima dilakukan (`Menunggu Konfirmasi`, `Menunggu Pembayaran`, `Disetujui`, `Siap Diambil`).
5. **Stok Perlu Perbaikan (`stok_perlu_perbaikan`):** Kuantitas alat yang membutuhkan servis/perawatan, berasal dari master alat berkondisi `Perlu Perbaikan` ditambah akumulasi unit hasil pengembalian yang masih menunggu selesai perbaikan (`jumlah_perlu_perbaikan - jumlah_diperbaiki`).
6. **Stok Rusak / Afkir (`stok_rusak`):** Kuantitas alat yang rusak berat/tidak layak, berasal dari master alat berkondisi `Rusak` ditambah akumulasi unit hasil pengembalian yang rusak (`jumlah_rusak`).
7. **Total Stok Fisik Kepemilikan (`total_stok_fisik`):** Akumulasi total kepemilikan aset alat oleh rental:
   $$\text{Total Stok Fisik} = \text{Stok Siap Sewa} + \text{Sedang Disewa} + \text{Perlu Perbaikan} + \text{Rusak}$$

### 6.2 Aturan Ketersediaan dan Reservasi Periode

1. **Konsistensi Lintas Modul:** Katalog Pengguna, pemesanan on-site, dashboard Admin, dan dashboard Pemilik menggunakan logika perhitungan ketersediaan yang sama dan bersumber dari model data aktual (`Alat` & `Penyewaan`).
2. **Pengecekan Ketersediaan Berdasarkan Periode:** Ketersediaan unit memperhitungkan rentang tanggal sewa bersama (`tanggal_sewa` s.d. `tanggal_kembali`). Sistem mendeteksi potensi tumpang tindih (*overlap*) tanggal dengan pesanan lain untuk mencegah kelebihan pemesanan (*overbooking*).
3. **Pencegahan Pengurangan Ganda:**
   - Stok tidak boleh dikurangi dua kali saat pesanan berpindah dari `Menunggu Konfirmasi` ke `Disetujui`, atau saat pembayaran dikonfirmasi `Lunas`.
   - Pengembalian berulang/bertahap tidak boleh menambah stok dua kali.
4. **Pemulihan Ketersediaan:** Pesanan yang ditolak oleh Admin atau dibatalkan otomatis melepaskan alokasi unit kembali ke ketersediaan periode.
5. **Isolasi Unit Bermasalah:** Unit yang sedang dalam kondisi `Perlu Perbaikan` atau `Rusak` dilarang keras dihitung sebagai stok siap sewa toko.
6. **Integritas Historis:** Dilarang mengubah data transaksi historis atau mengarang angka stok fiktif demi memaksakan kecocokan angka.
7. **Pencegahan Stok Negatif:** Setiap mutasi penyesuaian stok divalidasi ketat di sisi database dengan mekanisme *row-level locking* (`lockForUpdate`) di dalam *database transaction*.

---

## 7. Perhitungan Tagihan dan Keputusan Bisnis Terpusat

> [!NOTE]
> **Status Keputusan:** Catatan audit lama mengenai basis perhitungan (per hari vs per malam, inklusif vs eksklusif) kini telah **DITETAPKAN SECARA RESMI DAN MENGGANTIKAN** seluruh keraguan/catatan audit sebelumnya. Seluruh modul (katalog, rincian detail alat, keranjang, checkout, pesanan, invoice, pembayaran, admin, dan pemilik) wajib mengacu pada 12 aturan bisnis terpusat berikut.

### 7.1 Dua Belas Aturan Bisnis Terpusat

1. **Periode Sewa Bersama:** Satu transaksi penyewaan memiliki satu periode sewa bersama (`tanggal_sewa` dan `tanggal_kembali`) untuk seluruh barang yang dipesan.
2. **Pemilihan Jumlah Unit di Katalog:** Pelanggan memilih kuantitas unit masing-masing jenis barang langsung pada katalog atau halaman detail. Kontrol unit dimulai dari 0 dan tidak boleh melebihi stok siap sewa.
3. **Tanpa Durasi pada Katalog dan Detail:** Katalog dan halaman rincian detail **tidak menyediakan pemilihan tanggal maupun durasi sewa per barang**. Tampilan harga murni berupa tarif sewa per unit per hari (`Rp X / hari`).
4. **Multi-Item dalam Keranjang:** Keranjang belanja menampung beberapa jenis barang sekaligus, masing-masing dengan jumlah unit yang dapat disesuaikan.
5. **Penentuan Tanggal Tunggal pada Checkout:** Halaman checkout menyediakan **satu input tanggal mulai sewa** dan **satu input tanggal kembali** untuk seluruh isi keranjang. Tidak ada durasi atau tanggal yang diatur terpisah per item.
6. **Rumus Durasi Sewa (Selisih Hari Kalender):** Durasi dihitung secara eksklusif berdasarkan selisih murni hari kalender:
   $$\text{Durasi hari} = \text{Tanggal Kembali} - \text{Tanggal Mulai}$$
   Contoh: Tanggal mulai **10 Oktober** dan tanggal kembali **13 Oktober** dihitung **3 hari** ($13 - 10 = 3$). Hari pertama dan hari terakhir tidak dihitung ganda/inklusif (tidak ditambah 1).
7. **Rumus Subtotal per Barang:**
   $$\text{Subtotal per Jenis Alat} = \text{Tarif Harian Historis} \times \text{Jumlah Unit} \times \text{Durasi Hari Bersama}$$
8. **Rumus Total Transaksi:**
   $$\text{Total Transaksi} = \sum \text{Subtotal Seluruh Jenis Alat}$$
9. **Estimasi Sementara pada Keranjang:** Sebelum tanggal sewa dipilih pada halaman checkout, keranjang belanja **hanya menampilkan subtotal sementara per hari** (`tarif harian × unit`) dan total sementara per hari (`sum(subtotal per hari)`). Sistem tidak boleh mengklaim atau menampilkan angka keranjang sebagai tagihan final transaksi.
10. **Validasi dan Perhitungan Ulang di Sisi Server (Backend Recalculation):** Harga barang, durasi hari, ketersediaan stok, subtotal per item, dan total transaksi final wajib divalidasi dan dihitung ulang di backend. Sistem tidak pernah mempercayai nominal atau hasil perhitungan yang dikirim dari browser/frontend.
11. **Konsistensi dan Snapshot Data Historis:** Tarif sewa per unit dicatat sebagai snapshot pada tabel `detail_penyewaan.tarif_sewa` dan total transaksi dicatat pada `pembayaran.total_biaya`. Perubahan tarif master di tabel `alat` di kemudian hari tidak boleh mengubah nilai transaksi masa lalu yang sudah tersimpan.
12. **Kebersihan Tampilan Halaman Rincian Detail:** Halaman rincian detail dikhususkan untuk menampilkan informasi produk (nama alat, kode, kategori, foto/gambar nyata, spesifikasi/deskripsi faktual, kondisi alat, stok tersedia, dan tarif sewa per unit per hari). Halaman ini **dilarang menampilkan pesan teknis, rumus perhitungan internal developer, nama variabel, atau penjelasan biaya** yang tidak dibutuhkan pelanggan.

### 7.2 Contoh Kasus Perhitungan Biaya

Contoh transaksi sewa:
- **Tenda Dome 4 Orang:** Tarif sewa Rp50.000/hari, jumlah 2 unit.
- **Sleeping Bag:** Tarif sewa Rp25.000/hari, jumlah 3 unit.
- **Tanggal Mulai:** 10 Oktober.
- **Tanggal Kembali:** 13 Oktober.

Perhitungan:
- **Durasi sewa:** $13 - 10 = 3\text{ hari}$.
- **Subtotal Tenda Dome:** $\text{Rp}50.000 \times 2 \times 3 = \text{Rp}300.000$.
- **Subtotal Sleeping Bag:** $\text{Rp}25.000 \times 3 \times 3 = \text{Rp}225.000$.
- **Total Biaya Transaksi:** $\text{Rp}300.000 + \text{Rp}225.000 = \text{Rp}525.000$.

### 7.3 Rincian Informasi Tagihan

Setiap tagihan, invoice, dan ringkasan pembayaran harus menyajikan rincian transparan:
- Nomor transaksi.
- Nama pelanggan dan kontak (sumber online maupun on-site).
- Periode sewa bersama (`tanggal_sewa` s.d. `tanggal_kembali`).
- Durasi hari bersama.
- Daftar rincian setiap alat: nama alat, tarif sewa harian (snapshot), jumlah unit, dan subtotal final.
- Total tagihan transaksi final.
- Metode pembayaran yang dipilih (QRIS kasir, transfer bank, atau cash).
- Status pembayaran (`Belum Dibayar`, `Menunggu Verifikasi`, `Lunas`, `Ditolak`).
- Denda (keterlambatan/kerusakan) jika timbul saat pengembalian barang.

> [!NOTE]
> **Status Cetak Nota Transaksi (Catatan Audit A5):** Rincian transaksi saat ini dapat dilihat pada layar sistem, namun tombol/fitur cetak fisik nota transaksi kasir (`window.print()`) belum diimplementasikan dan dijadwalkan pada Roadmap Fase 2.

---

## 8. Dashboard dan Menu

> [!NOTE]
> **Status Fitur Laporan (Catatan Audit A7):** Halaman pelaporan saat ini baru tersedia pada role Pemilik (`/pemilik/transaksi` dan `/pemilik/inventaris`), namun belum dilengkapi pengurutan kolom dinamis (sorting), baris total/rekapitulasi nilai, serta fitur cetak atau ekspor PDF/Excel. Role Admin belum memiliki menu Laporan. Penyempurnaan menyeluruh dijadwalkan pada Roadmap Fase 2.

### 8.1 Dashboard Admin

Menampilkan ringkasan operasional, seperti:

- Pesanan menunggu konfirmasi.
- Pesanan yang telah disetujui dan menunggu pembayaran.
- Pembayaran menunggu verifikasi.
- Pesanan siap diambil.
- Pesanan sedang disewa.
- Pengembalian yang perlu diproses.
- Stok alat.
- Alat rusak atau perlu perbaikan.

Angka yang dapat ditindaklanjuti sebaiknya mengarah ke daftar data terkait.

### 8.2 Dashboard Pengguna

Menampilkan:

- Katalog alat camping.
- Informasi stok dan tarif.
- Ringkasan pesanan aktif.
- Status pesanan.
- Status pembayaran.
- Tagihan yang menunggu pembayaran.
- Riwayat pesanan milik Pengguna.

### 8.3 Dashboard Pemilik (Strictly Read-Only)

Dashboard Pemilik menyajikan pemantauan performa bisnis dan kesehatan kas operasional secara transparan. Sistem secara tegas memisahkan 4 metrik keuangan utama:

1. **Penerimaan Kas Pelanggan:**
   - Menghitung uang riil yang benar-benar telah diterima dari transaksi pembayaran pelanggan yang berstatus `Lunas` (`Pembayaran::where('status_pembayaran', 'Lunas')`).
   - Dihitung terpisah untuk hari ini dan bulan berjalan (MTD) menggunakan zona waktu `Asia/Jakarta`.
   - Menampilkan pembanding "Total Nilai Pesanan" agar Pemilik dapat membedakan nilai omzet pesanan vs uang tunai/kas yang benar-benar sudah masuk rekening.
   - Pesanan yang belum lunas, menunggu verifikasi, ditolak, atau dibatalkan **TIDAK** dimasukkan ke dalam penerimaan kas.

2. **Pengeluaran Pembelian Stok Supplier:**
   - Menghitung seluruh pengeluaran kas yang sah untuk pengadaan inventaris alat dari supplier (`PembelianInventaris`).
   - Dihitung harian dan bulanan (MTD).
   - Memiliki modal rincian yang menampilkan nomor PO, nama supplier, item alat, kuantitas, dan Admin pembuat PO.

3. **Pengeluaran Biaya Perbaikan Alat:**
   - Menghitung seluruh pengeluaran biaya perbaikan/servis alat camping rusak yang ditangani teknisi (`PenangananKerusakan::where('biaya_perbaikan', '>', 0)`).
   - Dihitung harian dan bulanan (MTD).
   - Memiliki modal rincian yang mencatat alat, jumlah unit, pelaksana teknisi, tanggal tindakan, dan biaya servis aktual.

4. **Arus Kas Bersih (Net Cash Flow):**
   - Mengkalkulasi posisi surplus atau defisit kas nyata:
     $$\text{Arus Kas Bersih} = \text{Penerimaan Kas Pelanggan} - \text{Pembelian Stok Supplier} - \text{Biaya Perbaikan Alat}$$
   - Status otomatis ditandai **Surplus Operasional (+)** jika $\ge 0$ atau **Defisit Operasional (-)** jika $< 0$.
   - Dilengkapi modal rekonsiliasi arus kas yang merinci komponen penambah dan pengurang kas secara transparan.

> [!IMPORTANT]
> **Aturan Integritas Keuangan:**
> - Pembelian stok supplier dan biaya perbaikan **TIDAK BOLEH** mengurangi angka penerimaan pelanggan. Angka penerimaan pelanggan harus tetap utuh mencerminkan pembayaran pelanggan yang terkonfirmasi.
> - Pembelian stok supplier dan biaya perbaikan diakui sebagai pengeluaran operasional tersendiri pada perhitungan Arus Kas Bersih.
> - Contoh uji logika resmi:
>   - Penerimaan kas pelanggan terkonfirmasi: **Rp 1.200.000**
>   - Pembelian stok supplier sah: **Rp 100.000**
>   - Biaya perbaikan alat: **Rp 0**
>   - Hasil: Penerimaan kas pelanggan **tetap Rp 1.200.000**, dan Arus Kas Bersih adalah **Rp 1.100.000** (Surplus).
> - Tidak boleh ada pengeluaran yang dihitung ganda.

5. **Ringkasan Inventaris dan Modal Rincian Unit:**
   - Kartu `Total Stok Fisik`, `Sedang Disewa`, `Stok Siap Sewa`, `Perlu Perbaikan`, dan `Rusak` dapat diklik/dibuka melalui modal rincian untuk melihat daftar alat, kategori, dan unit yang membentuk angka ringkasan tersebut.

6. **Grafik Tren Penjualan & Arus Kas:**
   - Menyajikan grafik harian (pilihan filter 7 hari, 30 hari, bulan ini, atau rentang kustom) dan grafik tren bulanan (6 bulan terakhir).
   - Menampilkan visualisasi multi-jalur: Penerimaan Kas (hijau), Pengeluaran Stok & Servis (merah), dan Arus Kas Bersih (biru).

Seluruh halaman dan modal Pemilik bersifat *strictly read-only*.

### 8.4 Riwayat Pembelian milik Pemilik

Dalam proyek ini, istilah **Riwayat Pembelian milik Pemilik berarti riwayat pesanan pelanggan**, bukan pembelian stok dari supplier.

Tampilkan:
- Nomor transaksi.
- Tanggal pemesanan.
- Sumber pesanan (Online atau On-site).
- Nama pelanggan dan kontak.
- Rincian alat dan jumlah unit.
- Durasi sewa.
- Total tagihan.
- Metode pembayaran.
- Status pesanan.
- Status pembayaran.

Pemilik dapat mencari, memfilter tanggal, membuka detail, dan melihat riwayat tersebut tanpa mengubah data.

---

## 9. Menu Inventaris, Pembelian Stok Supplier, dan Penanganan Kerusakan

Menu Inventaris (`/admin/inventaris`) berfungsi untuk memantau persediaan alat camping, mengelola mutasi stok, mencatat pengadaan dari supplier, serta menangani unit alat yang rusak.

### 9.1 Pemantauan Stok dan Kondisi Alat
- Melihat daftar seluruh alat camping dengan filter kategori, kondisi, dan status stok.
- Menampilkan metrik lengkap per alat: Stok Toko, Stok Siap Sewa, Unit Sedang Disewa, Unit Dialokasikan, Unit Perlu Perbaikan, Unit Rusak, dan Total Stok Fisik Kepemilikan.
- Mencatat penyesuaian stok manual (tambah/kurang) dengan alasan wajib. Validasi ketat mencegah stok menjadi negatif.
- Seluruh mutasi stok otomatis tercatat di tabel `riwayat_stok` dan `activity_logs`.

### 9.2 Pengadaan Pembelian Stok dari Supplier (Fitur Lengkap & Aktif)
Fitur pembelian supplier dapat diakses melalui menu **Inventaris & Stok &rarr; Pembelian Supplier** (`/admin/inventaris/pembelian`):
- Tombol **"Catat Pembelian Supplier"** (`/admin/inventaris/pembelian/tambah`) berfungsi penuh membuka formulir input.
- Mendukung pemilihan supplier rekanan yang sudah ada atau pencatatan supplier baru secara langsung.
- Nomor transaksi pembelian/PO dihasilkan otomatis dengan format `PO-YYYYMMDD-XXX` (dapat disesuaikan jika ada nomor faktur supplier).
- Mendukung pembelian multi-item alat camping sekaligus dalam satu transaksi PO.
- Input data per baris: kode alat, kuantitas unit, harga beli satuan, dan subtotal per item.
- Harga beli satuan dibedakan secara tegas dari tarif sewa harian.
- Stok fisik alat di toko otomatis bertambah (`alat.stok += kuantitas`) saat transaksi PO berhasil disimpan.
- **Proteksi Double-Submit:** Tombol simpan otomatis dinonaktifkan saat diklik untuk mencegah duplikasi transaksi dan penambahan stok ganda.
- **Input Retention:** Form mempertahankan data baris item yang telah diinput jika validasi server menemukan kesalahan.
- Riwayat pembelian tersimpan lengkap pada tabel `pembelian_inventaris` dan `detail_pembelian_inventaris`.

### 9.3 Master Data Supplier & Vendor Rekanan (Modul Mandiri CRUD Lengkap)
Modul Master Supplier dapat diakses melalui menu sidebar Admin **Master Supplier** (`/admin/supplier`):
- **Controller:** `SupplierController` (`app/Http/Controllers/SupplierController.php`).
- **Rute:** `Route::resource('supplier', SupplierController::class)` (lengkap 7 aksi: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`).
- **Tampil & Cari (Index):** Menampilkan daftar supplier dengan paginasi, pencarian (`q`) multi-kolom (nama supplier, nomor telepon, kontak person), kartu ringkasan total supplier & supplier aktif memasok, serta badge riwayat pembelian.
- **Tambah (Create & Store):** Form input nama supplier, kontak person, nomor telepon, dan alamat dengan validasi server-side bahasa Indonesia (nama wajib dan unik, format nomor telepon, batas panjang karakter).
- **Detail (Show):** Menampilkan profil lengkap supplier beserta tabel riwayat seluruh PO pembelian inventaris yang dipasok oleh supplier tersebut (nomor PO, tanggal pembelian, daftar item & unit, total nominal, dan admin pembuat PO).
- **Edit & Update:** Memperbarui data supplier dengan mempertahankan integritas data dan mengabaikan nilai unik untuk ID yang sedang diedit.
- **Hapus (Destroy):** Menerapkan proteksi integritas foreign key secara ketat: jika supplier memiliki riwayat PO di tabel `pembelian_inventaris`, sistem membatalkan penghapusan dan menampilkan pesan edukatif. Supplier tanpa riwayat pembelian dapat dihapus secara aman.
- **Audit Trail:** Seluruh aksi create, update, dan delete otomatis dicatat ke tabel `activity_logs`.

### 9.4 Penanganan Barang Rusak & Biaya Perbaikan (Fitur Lengkap & Aktif)
Pada halaman rincian detail inventaris alat (`/admin/inventaris/{kode_alat}`), Admin dapat mengelola unit alat yang mengalami kendala teknis atau rusak melalui aksi penanganan:
1. **Menunggu Perbaikan:** Mencatat unit yang masuk dalam antrean servis dan membutuhkan perawatan teknisi.
2. **Catat Biaya Perbaikan:** Mencatat pengeluaran biaya perbaikan aktual, nama teknisi/bengkel pelaksana, tanggal tindakan, dan keterangan kerusakan. Biaya servis ini otomatis diakui sebagai biaya operasional pada dashboard Pemilik.
3. **Selesai Perbaikan:** Mengonfirmasi bahwa unit telah selesai diperbaiki dan kondisinya telah layak kembali, sehingga unit otomatis dikembalikan ke stok siap sewa toko (`alat.stok += unit_diperbaiki`).
4. **Afkir / Pensiunkan Unit:** Memensiunkan unit yang rusak permanen atau tidak layak pakai lagi, sehingga kuantitasnya dikurangi secara permanen dari stok fisik kepemilikan.
- Seluruh tindakan penanganan dan riwayat biaya servis tercatat pada tabel `penanganan_kerusakan`.

### 9.5 Aturan Inventaris Terpadu
- Perubahan stok harus selalu tercatat dan dapat ditelusuri riwayatnya (*audit trail*).
- Stok yang sedang disewa harus dibedakan dari stok siap sewa.
- Stok rusak atau perlu perbaikan dilarang dihitung sebagai stok siap sewa toko.
- Penambahan stok dari supplier hanya menambah stok fisik toko, tidak mengubah tarif sewa maupun transaksi penyewaan historis.
- Setiap penyesuaian harus mencatat alasan, waktu, dan pengguna yang melakukan perubahan.
- Jangan mengubah tagihan atau riwayat transaksi lama ketika stok berubah.

---

## 10. Struktur Database dan Migrasi

Database yang digunakan dan wajib dipertahankan adalah `adventure_camp_rental`.

### Tabel Database Aktual yang Telah Diverifikasi

1. **`users`:** `id_user` (PK), `nama_user`, `username`, `password`, `role` ('admin', 'pengguna', 'pemilik'), `status_aktif`, `id_pelanggan` (FK nullable), `remember_token`, `created_at`.
2. **`pelanggan`:** `id_pelanggan` (PK), `id_user` (FK nullable), `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`.
3. **`alat`:** `kode_alat` (PK string), `nama_alat`, `kategori`, `stok` (stok fisik toko), `kondisi` ('Baik', 'Perlu Perbaikan', 'Rusak'), `tarif_sewa` (tarif harian master), `gambar`, `deskripsi`, `created_at`.
4. **`penyewaan`:** `no_transaksi` (PK string), `id_pelanggan` (FK), `id_user` (FK nullable, null jika pesanan on-site), `tanggal_sewa` (tanggal mulai bersama), `tanggal_kembali` (tanggal kembali bersama), `sumber_pesanan` ('online', 'on-site'), `status_penyewaan` ('Menunggu Konfirmasi', 'Disetujui', 'Ditolak', 'Sedang Disewa', 'Selesai', 'Dibatalkan'), `alasan_penolakan`, `created_at`.
5. **`detail_penyewaan`:** `id_detail` (PK), `no_transaksi` (FK), `kode_alat` (FK), `jumlah` (kuantitas unit awal), `tarif_sewa` (snapshot tarif harian per unit), `jumlah_diserahkan`, `jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_diperbaiki`, `jumlah_rusak`.
6. **`pembayaran`:** `no_pembayaran` (PK string), `no_transaksi` (FK), `total_biaya` (snapshot total transaksi final), `jumlah_pembayaran`, `metode_pembayaran` ('transfer', 'cash', 'qris'), `tanggal_pembayaran`, `status_pembayaran` ('Belum Dibayar', 'Menunggu Verifikasi', 'Lunas', 'Ditolak'), `bukti_pembayaran`, `konfirmasi_oleh` (FK nullable), `waktu_konfirmasi`, `id_admin`, `catatan_admin`, `created_at`.
7. **`pengambilan`:** `id_pengambilan` (PK), `no_transaksi` (FK unique), `tanggal_pengambilan`, `id_admin` (FK), `nama_penerima`, `catatan`.
8. **`pengembalian`:** `id_pengembalian` (PK), `no_transaksi` (FK unique), `tanggal_pengembalian_aktual`, `kondisi_alat`, `status_pengembalian` ('Selesai', 'Sebagian', 'Bermasalah'), `id_admin` (FK), `catatan`.
9. **`denda`:** `id_denda` (PK), `id_pengembalian` (FK unique), `tarif_denda`, `nominal_denda`, `alasan_denda`.
10. **`riwayat_stok`:** `id_riwayat` (PK), `kode_alat` (FK), `jenis_perubahan`, `stok_sebelum`, `jumlah_perubahan`, `stok_sesudah`, `alasan`, `id_user` (FK), `created_at`.
11. **`supplier`:** `id_supplier` (PK), `nama_supplier`, `kontak_person`, `telepon`, `alamat`, `created_at`.
12. **`pembelian_inventaris`:** `no_pembelian` (PK string), `tanggal_pembelian`, `id_supplier` (FK), `total_pembelian`, `id_admin` (FK), `catatan`, `created_at`.
13. **`detail_pembelian_inventaris`:** `id_detail_pembelian` (PK), `no_pembelian` (FK), `kode_alat` (FK), `kuantitas`, `harga_beli_satuan`, `subtotal`, `created_at`.
14. **`penanganan_kerusakan`:** `id_penanganan` (PK), `kode_alat` (FK), `id_detail` (FK nullable), `jenis_tindakan` ('Menunggu Perbaikan', 'Catat Biaya Perbaikan', 'Selesai Perbaikan', 'Afkir/Pensiun'), `jumlah_unit`, `biaya_perbaikan`, `tanggal_tindakan`, `pelaksana_teknisi`, `keterangan`, `id_admin` (FK), `created_at`.
15. **`activity_logs`:** `id_log` (PK), `id_user` (FK), `aktivitas`, `modul`, `data_diproses`, `waktu`.

### Catatan Penting dan Status Migrasi

1. **Dukungan Skema untuk Keputusan Bisnis Baru:**
   - Tabel `penyewaan` menggunakan `tanggal_sewa` dan `tanggal_kembali` sebagai **satu periode sewa bersama** di tingkat transaksi.
   - Tabel `detail_penyewaan` memiliki snapshot `tarif_sewa` harian dan kolom pemantauan pengembalian bertahap (`jumlah_kembali_baik`, `jumlah_perlu_perbaikan`, `jumlah_rusak`, dll).
   - Tabel `pembayaran` memiliki snapshot `total_biaya` final dan kolom verifikasi (`waktu_konfirmasi`, `id_admin`).
2. **Kelengkapan Skema Aktual (Schema Fully Synchronized):**
   Seluruh tabel pendukung (supplier, pembelian inventaris, riwayat mutasi stok, dan penanganan kerusakan) telah dibuat melalui migrasi additif yang aman tanpa merusak struktur atau data lama. Skema database saat ini telah lengkap dan mencakup seluruh alur operasional.
3. **Penyimpanan Transaksi On-Site:**
   Pesanan dari mode kiosk tablet on-site menggunakan `id_user = null` dan `sumber_pesanan = 'on-site'`, dengan relasi pelanggan pada tabel `pelanggan` yang dicocokkan berdasarkan nomor telepon.
4. **Keamanan Data Historis:**
   Dilarang menjalankan perubahan yang menghapus atau menimpa data lama pada database `adventure_camp_rental`. Perubahan harga master pada tabel `alat` tidak boleh memperbarui nilai `tarif_sewa` pada `detail_penyewaan` transaksi yang sudah terjadi.
5. **Dilarang Perintah Destruktif:**
   Dilarang keras menjalankan `php artisan migrate:fresh`, `migrate:reset`, `db:wipe`, atau truncate tabel apa pun.
6. **Kesiapan Seeder dan Berkas SQL Dump (Catatan Audit A9):**
   Berkas `database/seeders/DatabaseSeeder.php` saat ini masih berupa skeleton default Laravel (`User::factory()->create(['name' => ..., 'email' => ...])`) yang tidak sesuai dengan kolom skema tabel `users` (`nama_user`, `username`), sehingga akan gagal jika dijalankan. Selain itu, berkas dump database SQL siap impor (`adventure_camp_rental.sql`) yang memuat data contoh demonstrasi belum dipaketkan di repository. Penataan seeder dan pembuatan file dump SQL dijadwalkan pada Roadmap Fase 3.

---

## 11. Autentikasi dan Keamanan

- Gunakan hashing Laravel untuk password.
- Validasi semua input di backend.
- Terapkan middleware atau policy untuk membatasi akses setiap role.
- Batasi Pengguna agar hanya dapat mengakses pesanan miliknya.
- Batasi Pemilik ke akses read-only.
- Batasi tindakan persetujuan dan verifikasi pembayaran kepada Admin.
- Hitung total tagihan di backend.
- Validasi jumlah pesanan, tanggal, tarif, dan stok.
- Validasi jenis dan ukuran file bukti transfer.
- Simpan file sensitif secara aman.
- Jangan menyimpan kredensial di repository.
- Gunakan proteksi CSRF.
- Cegah SQL injection dengan mekanisme query Laravel yang aman.
- Cegah IDOR atau akses transaksi milik orang lain melalui perubahan ID pada URL.
- Gunakan transaksi database ketika beberapa perubahan saling bergantung.
- Catat tindakan penting ke audit trail.
- Jangan mengizinkan Pengguna mengubah status pembayaran melalui request manual.

### Audit trail

Catat tindakan penting, misalnya:

- Persetujuan dan penolakan pesanan.
- Konfirmasi pembayaran.
- Perubahan status pesanan.
- Pengambilan dan pengembalian.
- Perubahan kondisi alat.
- Penambahan atau pengurangan stok.
- Pencatatan denda.
- Perubahan data operasional penting.

Log harus menyimpan pelaku, waktu, aktivitas, dan informasi yang relevan. Hindari menyimpan password, token, atau data sensitif dalam log.

---

## 12. UI/UX

Buat antarmuka yang sederhana, konsisten, responsif, dan mudah digunakan.

Gunakan:
- Navbar atau sidebar sesuai role.
- Dashboard berbeda untuk setiap role.
- Tabel dengan pencarian, filter, dan pagination.
- Badge status yang jelas.
- Form dengan validasi dan pesan kesalahan.
- Konfirmasi sebelum tindakan penting.
- Halaman detail pesanan yang mudah dibaca.
- Halaman tagihan yang menampilkan rincian biaya.
- Tampilan responsif untuk desktop dan perangkat mobile.

Jangan menampilkan menu Admin pada Pengguna atau Pemilik. Jangan menampilkan tombol operasional pada Pemilik. Jangan membuat tombol yang tidak memiliki fungsi nyata.

> [!NOTE]
> **Status Routing Root UI (Catatan Audit A10):** Route root `/` (`routes/web.php`) saat ini masih mengalihkan request ke `/ui-showcase` (halaman dev showcase komponen UI), bukan ke katalog penyewaan publik atau beranda toko. Penyesuaian diarahkan pada Roadmap Fase 4.

---

## 13. Status Pekerjaan, Audit Kepatuhan, dan Roadmap Penyempurnaan

Dokumentasi ini disinkronkan secara berkala dengan hasil pengujian nyata dan laporan audit independen (`AUDIT_TUGAS_AKHIR.md`, per 25 Oktober 2026). Status sistem dibedakan secara tegas antara fitur yang telah teruji, temuan kepatuhan tugas akhir (A1–A12), daftar kekurangan terbuka (*open gaps*), dan urutan roadmap perbaikan.

### 13.1 Fitur yang Telah Diterapkan dan Teruji (Completed & Tested)

- [x] **Autentikasi Tiga Role & Keamanan:** Login, registrasi Pengguna, otorisasi Admin, pembatasan ketat read-only untuk Pemilik, dan proteksi CSRF.
- [x] **Mode Kiosk Tablet On-Site (`/tablet`, `/onsite`):** Pemesanan mandiri pengunjung toko fisik tanpa akun login (`id_user = null`, `sumber_pesanan = 'on-site'`), middleware isolasi sesi (`EnsureOnsiteKioskSession`), reset sesi pemesan, dan pembuatan/pencocokan data pelanggan otomatis via nomor telepon.
- [x] **Alur Operasional Admin:** Penerimaan pesanan online & on-site, pemeriksaan ketersediaan stok periode, persetujuan pesanan, dan penolakan pesanan dengan alasan wajib.
- [x] **Alur Pembayaran Manual:** Halaman instruksi QRIS kasir (tanpa generate QR acak), instruksi Transfer Bank dengan unggah bukti transfer, instruksi Tunai/Cash, serta verifikasi pembayaran manual oleh Admin dengan pencatatan nama Admin pemverifikasi.
- [x] **Penyelarasan Logika Perhitungan Terpusat:** Penyeragaman rumus durasi hari kalender murni (`kembali - mulai`), subtotal item (`tarif × unit × durasi`), dan total pesanan di seluruh layer backend (Controller, Model, Invoice, dan Admin panel).
- [x] **Keranjang Belanja Multi-Item:** Menampung multi-item tanpa pemilihan tanggal di katalog, menampilkan estimasi subtotal dan total sementara per hari (`tarif × unit`).
- [x] **Checkout Periode Bersama:** Satu input tanggal mulai sewa dan satu input tanggal kembali bersama untuk seluruh isi keranjang.
- [x] **Pengembalian Bertahap & Multi-Baris Detail:** Identifikasi detail unik via primary key `id_detail` (aman untuk transaksi dengan alat berkode sama di beberapa baris detail), validasi kuantitas tidak melebihi sisa unit yang belum kembali, pengembalian berulang tidak menambah stok dua kali, dan pesanan baru berstatus `Selesai` setelah seluruh unit kembali lengkap.
- [x] **Inventaris & Mutasi Stok (Non-Negative Clamped & Clean Separation):** Pemantauan stok fisik toko, stok siap sewa, stok dialokasikan di toko, stok sedang disewa di lapangan, stok perlu perbaikan, dan stok rusak. Logika perhitungan `stok_sedang_disewa` pada `Alat` dan `Penyewaan` menerapkan proteksi *lower-bound clamp* (CASE WHEN ... > 0 ... ELSE 0 END dan `max(0, ...)`) sehingga mencegah angka negatif pada anomali data pengembalian. Definisi bisnis dipisahkan secara tegas: pesanan `Siap Diambil` masuk kategori *dialokasikan di toko*, sedangkan *sedang disewa* murni unit yang telah diserahkan dan belum kembali (`Sedang Disewa`/`Berjalan`).
- [x] **Penanganan Barang Rusak & Biaya Perbaikan:** Tindakan penanganan unit bermasalah di detail inventaris (`/admin/inventaris/{kode_alat}`) mencakup: Menunggu Perbaikan, Catat Biaya Perbaikan teknisi, Selesai Perbaikan (kembali ke stok siap sewa toko), dan Afkir/Pensiunkan unit rusak permanen. Riwayat tersimpan pada tabel `penanganan_kerusakan`.
- [x] **Pengadaan Pembelian Stok Supplier (Menu Navigasi & Integrasi PO):** Menu navigasi "Pembelian Inventaris" aktif di sidebar Admin (`/admin/inventaris/pembelian`), tombol pintas "Beli Stok via PO Supplier" tersedia di halaman detail inventaris (`/admin/inventaris/{kode_alat}`) dengan pra-pilih alat otomatis via query parameter `kode_alat`. Form PO pengadaan multi-item, penambahan stok fisik toko otomatis dengan DB transaction dan locking (`lockForUpdate`), pencatatan mutasi di `riwayat_stok`, biaya pembelian tercatat sebagai pengeluaran terpisah dari sewa pelanggan, serta pencegahan submit ganda (*double-submit*). Akses dibatasi ketat untuk Admin (Pemilik strictly read-only).
- [x] **Pemisahan Metrik Keuangan Dashboard Pemilik (Strictly Read-Only):** Pemisahan 4 metrik keuangan (Penerimaan Kas Pelanggan terkonfirmasi, Pengeluaran Pembelian Stok Supplier, Pengeluaran Biaya Perbaikan Alat, dan Arus Kas Bersih harian & MTD), modal rincian per kartu metrik, modal rincian unit alat pada kartu stok, serta visualisasi grafik 3 jalur.
- [x] **Master Pengguna Lengkap dengan Halaman Detail:** Rute `admin.users.show` (`/admin/users/{user}`) dan view detail akun pengguna terintegrasi Admin, menampilkan data profil, relasi pelanggan fisik onsite (jika ada), riwayat transaksi sewa, dan ringkasan audit log tanpa mengekspos hash password/token.
- [x] **Master Supplier Mandiri 7 Fitur:** Modul Master Data ke-3 mandiri (`SupplierController`, `/admin/supplier`) dengan 7 operasi penuh: Tambah, Tampil, Edit, Hapus (proteksi foreign key riwayat PO), Cari, Detail (riwayat PO vendor), dan Validasi server-side Bahasa Indonesia.
- [x] **Pengujian Regresi Otomatis Menyeluruh:** Berdasarkan pengujian regresi independen pada laporan audit dan pengujian Fase 1, seluruh test suite PHPUnit (SQLite in-memory) lulus 100% tanpa error (*0 failed, 0 errors*). Seluruh fitur yang kodenya telah tertulis terbukti valid dan aman dari regresi.

---

### 13.2 Status Kepatuhan Kriteria Wajib Tugas Akhir (A1 – A12)

Berdasarkan audit menyeluruh terhadap **Dokumen Tugas Akhir Praktikum Sistem Informasi** (`TUGAS AKHIR PRAK SI(1).docx`) yang dilaporkan pada `AUDIT_TUGAS_AKHIR.md`:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        STATUS KEPATUHAN SISTEM                         │
├─────────────────────┬───────────┬──────────────────────────────────────┤
│ Status Kategori     │ Jumlah    │ Persentase (%)                       │
├─────────────────────┼───────────┼──────────────────────────────────────┤
│ [PASS] Memenuhi     │ 8 Bagian  │ 66.7 %                               │
│ [PARTIAL] Sebagian  │ 3 Bagian  │ 25.0 %                               │
│ [FAIL] Belum/Gagal  │ 1 Bagian  │  8.3 %                               │
├─────────────────────┼───────────┼──────────────────────────────────────┤
│ TOTAL               │ 12 Bagian │ 100.0 %                              │
└─────────────────────┴───────────┴──────────────────────────────────────┘
```

#### Matriks Kepatuhan Detail:

| ID | Kriteria Dokumen Tugas Akhir | Implementasi Aktual | Status | Evaluasi Temuan & Risiko Penilaian |
| :---: | :--- | :--- | :---: | :--- |
| **A1** | **Arsitektur dan Alur Sistem:** Menerapkan alur *Data &rarr; Proses &rarr; Penyimpanan &rarr; Informasi &rarr; Keputusan*. | Alur terintegrasi nyata dari input pesanan online/on-site, kalkulasi hari kalender, validasi stok, penyimpanan ke 15 tabel, hingga ringkasan arus kas Pemilik. | **PASS** | Memenuhi syarat sistem informasi transaksional utuh, bukan prototipe CRUD statis. |
| **A2** | **Login dan Logout:** Autentikasi kredensial, proteksi enumerasi, hashing bcrypt, terminasi sesi, proteksi back-button. | `AuthController.php`, `EnsureRole.php`, `Hash::check()`, invalidate session, anti-bfcache HTTP headers. | **PASS** | Memenuhi seluruh syarat keamanan autentikasi. |
| **A3** | **Hak Akses Pengguna:** Minimal 2 role berbeda. Pemeriksaan Admin, Pemilik (read-only), Pengguna, isolasi Kiosk. | 3 role pada `users` + mode Kiosk on-site. Middleware `EnsureRole` (HTTP 403), `EnsurePemilikReadOnly` (blokir mutasi HTTP 403), anti-IDOR Pengguna. | **PASS** | Hak akses terisolasi secara ketat di sisi backend. |
| **A4** | **Master Data Minimal 3 Modul:** Tiap modul wajib ada 7 fitur: Tambah, Tampil, Edit, Hapus, Cari, Detail, Validasi. | Master Alat: 7/7 lengkap (`AlatController`).<br>Master Pengguna: 7/7 lengkap dengan detail `show` (`AdminUserController@show`).<br>Master Supplier: 7/7 lengkap mandiri (`SupplierController`) dengan proteksi FK riwayat PO. | **PASS** | Memenuhi seluruh ketentuan minimal 3 modul master data dengan 7 fitur lengkap. Teruji via 33 automated tests di `SupplierManagementTest` dan `AdminUserManagementTest`. |
| **A5** | **Modul Transaksi Utama:** Rental penyewaan multi-item, durasi bersama, validasi stok overlap, approval, pembayaran manual, serah terima, pengembalian bertahap, denda, nota. | `PenggunaPesananController`, `AdminPesananController`, `AdminPembayaranController`, `OperasionalController`. Dukung pengembalian bertahap & multi-baris `id_detail`. | **PASS** | Fungsional rental sangat lengkap. Catatan: belum ada tombol cetak nota / struk sewa fisik kasir (`window.print()`). |
| **A6** | **Dashboard:** Minimal 3 KPI, 2 grafik dari DB, 1 tabel penting. Bedakan omzet vs penerimaan kas vs pengeluaran. | `PemilikDashboardController`: 9 kartu KPI, 2 grafik Chart.js dari DB (harian & tren bulanan), tabel riwayat pesanan, modal rekonsiliasi arus kas. | **PASS** | Memenuhi kriteria dashboard analitik real-time secara sempurna. |
| **A7** | **Laporan Minimal 2 Jenis:** Filter periode, pencarian, pengurutan (sort), total/rekap, cetak atau ekspor PDF/Excel. | Riwayat Transaksi (`/pemilik/transaksi`) & Inventaris (`/pemilik/inventaris`). | **FAIL** | **Temuan A7 Kritis:**<br>1. **Tidak ada fitur cetak atau ekspor PDF/Excel** sama sekali.<br>2. Tabel belum mendukung sorting dinamis per kolom.<br>3. Belum ada baris total rekapitulasi nilai.<br>4. Role Admin belum memiliki menu Laporan. |
| **A8** | **Audit Trail:** Mencatat user, aktivitas, modul, data diproses, waktu. Halaman log terproteksi. | Tabel `activity_logs`, model `ActivityLog` dengan sensor data sensitif/password, view `/admin/logs` terproteksi role Admin. | **PASS** | Audit trail aktif merekam jejak operasional. |
| **A9** | **Database Terintegrasi:** Primary key, Foreign key, relasi antartabel, snapshot data historis, data demonstrasi. | Migrasi 15 tabel lengkap dengan PK/FK, snapshot `detail_penyewaan.tarif_sewa`, `pembayaran.total_biaya`. | **PARTIAL** | **Temuan A9:** Skema database PASS. Namun berkas `DatabaseSeeder.php` masih skeleton default (`name`/`email`) yang error jika dijalankan, dan berkas dump SQL `.sql` belum dipaketkan. |
| **A10** | **UI, Validasi & Responsivitas:** Konsisten, rapi, responsif laptop/desktop, form valid, flash alert notification. | Bootstrap 5 rapi tematik outdoor, responsif laptop & desktop, validasi server ketat, flash alert. | **PASS** | Sangat baik. Catatan minor: route `/` masih mengarah ke `/ui-showcase`. |
| **A11** | **Dokumen Pengumpulan Tugas (14 Artefak):** Source code, SQL dump, repo GitHub, dokumen sistem, BPMN, ERD, dsb. | Source code, `docs/CONTEXT.md`, dan `README.md` tersedia di proyek. | **PARTIAL** | **Temuan A11:** 8 dari 14 luaran formal (BPMN, visual ERD, dokumen SRS/analisis, matriks test case, dan file `.sql` dump) belum disusun sebagai berkas siap kumpul. |
| **A12** | **Kesiapan Demonstrasi (10 Urutan Presentasi):** Alur presentasi pengujian praktikum dapat dijalankan mulus. | 9 dari 10 langkah presentasi siap 100% setelah penambahan Master Supplier; 1 langkah tersisa terkendala fitur cetak/ekspor laporan. | **PARTIAL** | **Temuan A12:** Demonstrasi pembuktian cetak/ekspor laporan belum dapat ditunjukkan kepada penguji. |

---

### 13.3 Daftar Kekurangan Terbuka dan Isu yang Belum Selesai (Open Gaps)

Berikut adalah daftar 5 kekurangan dan isu yang masih terbuka berdasarkan audit dan inspeksi kode aktual:

1. **Master Data ke-3 dan Halaman Detail Pengguna — [SELESAI PADA FASE 1]:**
   - Telah diselesaikan penuh pada implementasi Fase 1. Modul Master Pengguna telah dilengkapi rute/method/view `show` (`/admin/users/{user}`) yang aman dan informatif.
   - Modul Master Supplier mandiri (`/admin/supplier`) telah dibangun dengan 7 fitur lengkap (Tambah, Tampil, Edit, Hapus, Cari, Detail, Validasi) serta proteksi foreign key ketat terhadap riwayat PO pembelian inventaris.
2. **Laporan Belum Lengkap (Sorting, Rekapitulasi, dan Cetak/Ekspor):**
   - Halaman pelaporan yang ada saat ini (`/pemilik/transaksi` dan `/pemilik/inventaris`) belum memiliki fitur pengurutan data dinamis per kolom (*sortable headers*).
   - Belum memiliki baris total/rekapitulasi nilai keuangan (omzet, kas masuk) maupun total unit stok di baris akhir tabel.
   - Fitur cetak atau ekspor ke format PDF maupun Excel sama sekali belum diimplementasikan di controller maupun view mana pun.
   - Role Admin belum memiliki menu Laporan tersendiri.
3. **Cetak Nota Transaksi Belum Tersedia:**
   - Belum tersedia tombol, rute, atau template tampilan cetak nota / struk fisik transaksi kasir (`window.print()`) pada rincian pesanan yang disetujui atau lunas.
4. **`DatabaseSeeder.php` Perlu Diperiksa dan Diperbaiki:**
   - Berkas `database/seeders/DatabaseSeeder.php` (baris 20–23) masih memanggil `User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com'])`. Skema tabel `users` proyek menggunakan `nama_user` dan `username`, bukan `name` dan `email`. Perintah `php artisan db:seed` dipastikan gagal dengan error `Column not found`.
5. **Dump Database SQL dan Dokumen Formal Tugas Akhir Belum Lengkap:**
   - Berkas dump database MySQL siap impor (`adventure_camp_rental.sql`) yang memuat skema dan data contoh demonstrasi belum dipaketkan.
   - 8 dari 14 dokumen luaran formal tugas akhir (diagram alur proses bisnis BPMN, diagram relasi ERD visual 15 tabel, dokumen laporan sistem format Word/PDF, spesifikasi kebutuhan pengguna SRS, dan dokumen rekapitulasi matriks test case) belum dibuat.
6. **Route `/` Masih Menuju `/ui-showcase`:**
   - Berkas `routes/web.php` (baris 30–32) saat ini mendefinisikan redirect rute root `/` langsung ke `/ui-showcase` (halaman showcase komponen antarmuka pengujian UI), bukan ke beranda toko atau katalog alat camping publik.

---

### 13.4 Pembedaan Temuan Auditor vs Status Verifikasi Mandiri

Untuk menjaga kejujuran teknis dan integritas dokumentasi proyek:
- **Temuan yang Dilaporkan Auditor:** Seluruh status A1–A12, 5 bug utama, dan kelengkapan 14 artefak berasal dari berkas `AUDIT_TUGAS_AKHIR.md` (25 Oktober 2026) yang disusun berdasarkan analisis perbandingan terhadap dokumen tugas akhir dan pengetesan automated PHPUnit (331 tests passed).
- **Status Verifikasi Mandiri pada Kode Sumber:** Keberadaan ke-6 kekurangan di atas telah diverifikasi secara langsung melalui inspeksi kode sumber aktual (`routes/web.php` baris 30–32 & 102, `database/seeders/DatabaseSeeder.php` baris 20–23, `AdminUserController.php`, dan views terkait). Bug dan celah tersebut terbukti faktual ada di dalam kode.
- **Penegasan Status Implementasi:** Sesi pembaruan ini **hanya melakukan sinkronisasi dokumentasi (`docs/CONTEXT.md`)**. Tidak ada perubahan pada kode aplikasi, database aktif `adventure_camp_rental` tidak disentuh atau dimanipulasi, file `.env` tidak diubah, dan tidak ada pengujian ulang yang dijalankan. Fitur yang tercatat belum ada **TIDAK** dinyatakan telah selesai sebelum implementasi dan pengujian nyata dilakukan.

---

### 13.5 Roadmap Perbaikan Bertahap (Urutan Penyempurnaan Sistem)

Roadmap penyempurnaan dirancang secara terstruktur dengan urutan prioritas yang tidak saling bertabrakan:

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

#### Fase 1: Pemenuhan Syarat Master Data (Prioritas P1) — [SELESAI & TERVERIFIKASI]
- **Tujuan:** Memenuhi syarat minimal 3 modul master data dengan 7 fitur lengkap (Kriteria A4).
- **Tindakan yang Telah Diselesaikan & Teruji:**
  1. Menambahkan method `show()` pada `AdminUserController` dan view `resources/views/admin/users/show.blade.php` pada Modul Pengguna. Detail akun aman tanpa kebocoran hash/token, menampilkan profil, relasi onsite, riwayat sewa, dan log aktivitas.
  2. Membangun Modul Master Data ke-3: **Master Supplier** (`SupplierController`) dengan 7 operasi lengkap: Tambah data supplier, Tampilkan tabel supplier (dengan paginasi & badge PO), Edit data supplier, Hapus supplier (dengan proteksi foreign key jika terdapat riwayat pembelian inventaris), Cari data supplier, Detail data supplier (termasuk riwayat PO yang pernah dipasok), dan Validasi input form server-side bahasa Indonesia.
  3. Mengintegrasikan tautan Master Supplier pada sidebar Admin (`components/sidebar.blade.php`).
  4. Menambahkan 33 automated test cases (`SupplierManagementTest` dan `AdminUserManagementTest`) yang lulus 100%.

#### Fase 2: Pemenuhan Syarat Laporan dan Cetak Nota (Prioritas P1)
- **Tujuan:** Memenuhi syarat minimal 2 laporan dengan cetak/ekspor dan struk transaksi kasir.
- **Tindakan:**
  1. Buat controller khusus laporan (`AdminLaporanController` / `LaporanController`) dan menu sidebar:
     - **Laporan 1: Laporan Transaksi Penyewaan & Pendapatan** (filter rentang tanggal, filter status sewa, filter metode pembayaran, pencarian, pengurutan kolom, dan baris total omzet & kas masuk).
     - **Laporan 2: Laporan Inventaris & Mutasi Stok** (filter kategori alat, filter kondisi fisik, status ketersediaan stok, dan baris total akumulasi unit aset fisik).
  2. Implementasikan fitur **Cetak / Ekspor PDF** (menggunakan library PDF atau template CSS `@media print` ramah cetak browser) dan **Ekspor Excel/CSV**.
  3. Tambahkan fitur **Cetak Nota / Kwitansi Transaksi** pada detail pesanan kasir (`/admin/pesanan/{no_transaksi}/cetak-nota` atau modal nota kasir siap cetak `window.print()`).

#### Fase 3: Database Seeder dan Dump SQL (Prioritas P1)
- **Tujuan:** Memenuhi kriteria data contoh demonstrasi dan kelengkapan file `.sql`.
- **Tindakan:**
  1. Perbaiki `database/seeders/DatabaseSeeder.php` agar menghasilkan data contoh realistis yang sesuai skema:
     - 3 Akun Pengguna (`Admin`, `Pemilik`, `Pengguna`) dengan password yang diketahui.
     - 10+ Master Alat Camping berbagai kategori (Tenda, Matras, Kompor, Sleeping Bag, Carrier).
     - 3+ Master Supplier rekanan.
     - Contoh transaksi historis (Lunas, Sedang Disewa, Selesai, Denda).
  2. Ekspor database aktif ke berkas `database/adventure_camp_rental.sql` yang siap diimpor sewaktu-waktu.

#### Fase 4: Penyempurnaan UX & Routing (Prioritas P2)
- **Tujuan:** Meningkatkan kerapian aplikasi untuk penilaian penguji.
- **Tindakan:**
  1. Perbarui route `/` pada `routes/web.php`:
     - Jika sudah login, redirect sesuai dashboard perannya (Admin, Pemilik, atau Pengguna).
     - Jika belum login (tamu), tampilkan katalog publik atau landing page ramah pengunjung toko.
  2. Tambahkan indikator sorting interaktif pada header tabel riwayat transaksi dan inventaris.

#### Fase 5: Penyusunan Dokumen Formal Tugas Akhir
- **Tujuan:** Melengkapi 14 berkas yang wajib dikumpulkan sesuai panduan tugas akhir.
- **Tindakan:**
  1. Buat diagram alur proses bisnis BPMN (Business Process Model and Notation).
  2. Buat diagram visual ERD (Entity Relationship Diagram) 15 tabel database.
  3. Susun dokumen laporan tugas akhir resmi (Word/PDF) berdasarkan template praktikum.
  4. Susun tabel matriks pengujian (*Test Case Document*) dari 345 pengujian automated yang telah lulus (2.171 assertions).

---

### 13.6 Fitur Pengembangan Lanjutan (Planned for Future Iteration)

- [ ] **Pemberitahuan Otomatis (Notifikasi Pengingat):** Pengiriman pesan pengingat jatuh tempo sewa dan konfirmasi pembayaran via WhatsApp / Email.
- [ ] **Integrasi Payment Gateway Otomatis:** Integrasi gateway pembayaran berlisensi (Midtrans/Xendit) jika kredensial resmi telah tersedia.

---

## 14. Aturan Commit Git per Fungsi

Setiap fungsi yang selesai harus memiliki commit tersendiri setelah pengujian.

Contoh pembagian commit:

### Autentikasi dan role
- `feat: add user registration`
- `feat: add role-based authentication`
- `feat: restrict routes by role`

### Katalog dan pesanan
- `feat: add equipment catalog`
- `feat: add equipment availability check`
- `feat: add rental order summary`
- `feat: add rental order submission`
- `feat: add admin order approval`
- `feat: add admin order rejection`

### Pembayaran
- `feat: add QRIS payment instructions`
- `feat: add bank transfer instructions`
- `feat: add cash payment instructions`
- `feat: add payment proof submission`
- `feat: add admin payment verification`

### Pengembalian dan inventaris
- `feat: add equipment pickup processing`
- `feat: add partial equipment return processing`
- `feat: add damage handling and repair expense tracking`
- `feat: add supplier stock purchase flow`
- `feat: add inventory stock history`

### Dashboard Pemilik
- `feat: add owner daily and monthly cash receipt summary`
- `feat: add supplier purchase and repair expense integration`
- `feat: add net cash flow tracking and multi-series charts`
- `feat: add stock summary drill-down modals`

Pesan commit harus disesuaikan dengan fungsi yang benar-benar selesai.

### Prosedur setiap commit

1. Periksa `git status`.
2. Implementasikan satu fungsi atau operasi yang jelas.
3. Jalankan test yang relevan.
4. Perbaiki error sebelum commit.
5. Periksa `git diff`.
6. Pastikan `.env`, secret, data sensitif, dan perubahan yang tidak berkaitan tidak ikut ter-commit.
7. Commit perubahan yang hanya berkaitan dengan fungsi tersebut.
8. Verifikasi menggunakan `git log -1`.
9. Laporkan fungsi, file yang berubah, pengujian, hasil pengujian, pesan commit, dan hash commit.

Jangan mengklaim fitur selesai jika belum berfungsi atau test masih gagal. Jangan menggabungkan beberapa fitur besar ke dalam satu commit.

---

## 15. Pengujian dan Kriteria Penerimaan

### Role dan Keamanan
- Ketiga role (Admin, Pengguna, Pemilik) dapat login dan diarahkan ke antarmuka masing-masing.
- Pengguna hanya dapat mengakses transaksi miliknya sendiri (mencegah IDOR).
- Pemilik memiliki hak akses strictly read-only dan diblokir dari aksi perubahan data operasional.
- Admin memiliki otoritas penuh untuk approval, konfirmasi pembayaran, serah terima, pengembalian, pengadaan supplier, dan penanganan kerusakan.
- Sesi kiosk tablet on-site terisolasi dari login Pengguna biasa dan tidak membocorkan data antar pengunjung.

### Katalog dan Rincian Detail
- Katalog hanya menampilkan tarif sewa per unit per hari (`Rp X / hari`).
- Tidak ada opsi pemilihan durasi atau tanggal sewa di kartu katalog maupun halaman rincian detail.
- Kontrol kuantitas unit dimulai dari angka 0, tidak bisa negatif, dan tidak bisa melebihi stok yang tersedia.
- Tombol tambah ke keranjang disabled jika kuantitas 0 atau alat tidak layak sewa.
- Halaman rincian detail murni menyajikan data produk tanpa pesan teknis internal developer.

### Keranjang Belanja
- Mampu menampung beberapa jenis alat camping dengan unit masing-masing.
- Menampilkan subtotal sementara per hari per item: $\text{tarif harian} \times \text{jumlah unit}$.
- Menampilkan total sementara per hari: $\sum \text{subtotal per hari}$.
- Menegaskan bahwa nilai tersebut adalah estimasi sementara per hari, bukan tagihan final transaksi.

### Checkout dan Perhitungan Biaya Terpusat
- Hanya menyediakan SATU input Tanggal Mulai dan SATU input Tanggal Kembali untuk seluruh keranjang.
- Durasi hari kalender dihitung otomatis: $\text{Tanggal Kembali} - \text{Tanggal Mulai}$ (contoh 10–13 Oktober = 3 hari).
- Subtotal final per item dihitung: $\text{tarif} \times \text{unit} \times \text{durasi bersama}$.
- Total transaksi final dihitung: $\sum \text{subtotal final item}$.
- Seluruh harga, durasi, ketersediaan stok, dan total biaya divalidasi dan dihitung ulang di backend.
- Snapshot tarif harian disimpan di `detail_penyewaan.tarif_sewa` dan snapshot total biaya disimpan di `pembayaran.total_biaya`.

### Alur Pembayaran
- QRIS menampilkan instruksi resmi untuk meminta kode QRIS kepada kasir toko.
- Transfer Bank menampilkan rekening valid dan form unggah bukti transfer yang divalidasi format/ukurannya.
- Cash menampilkan petunjuk penyerahan uang tunai ke kasir.
- Status pembayaran tidak otomatis lunas sebelum Admin melakukan konfirmasi verifikasi manual.

### Pengembalian Bertahap dan Kondisi Alat
- Baris detail alat yang sama diidentifikasi terpisah berdasarkan primary key `id_detail`.
- Sisa unit belum kembali dihitung akurat: `jumlah_acuan - (kembali_baik + perlu_perbaikan + rusak)`.
- Pengembalian berulang tidak menambah stok dua kali.
- Hanya unit berkondisi Baik yang menambah stok fisik siap sewa toko.
- Status pesanan tetap `Sedang Disewa` sampai seluruh unit di semua baris detail tuntas dikembalikan.

### Pembelian Stok Supplier
- Form PO menyimpan data supplier, tanggal, nomor PO, multi-item alat, kuantitas, harga beli satuan, dan total.
- Stok fisik alat di toko bertambah otomatis sesuai kuantitas yang dibeli.
- Tombol simpan terlindungi dari submit ganda (*double-submit*).
- Riwayat pembelian tersimpan lengkap dan dapat ditelusuri.

### Penanganan Kerusakan & Biaya Servis
- Tindakan tercatat di `penanganan_kerusakan` dengan rincian biaya servis, teknisi, tanggal, dan keterangan.
- Aksi "Selesai Perbaikan" memulihkan unit yang diservis kembali ke stok siap sewa toko.
- Aksi "Afkir/Pensiun" mengurangi unit rusak permanen dari stok fisik kepemilikan.

### Laporan & Rekapitulasi Eksekutif
- Menyediakan dua jenis laporan utama: Laporan Transaksi Penyewaan & Arus Kas Masuk dan Laporan Inventaris & Ketersediaan Stok.
- Dilengkapi filter periode, pencarian kata kunci, status sewa/bayar, pengurutan dinamis (sort_by & order), rekapitulasi nilai, cetak resmi (@media print), dan ekspor data CSV/Excel UTF-8.
- Terintegrasi untuk Admin dan Pemilik (strictly read-only), serta diblokir bagi Pengguna biasa (403).

### Cetak Nota Transaksi Resmi
- Menyediakan invoice/struk sewa resmi dengan kop Adventure Camp Rental, identitas penyewa, tabel rincian sewa, tarif harian, durasi hari, subtotal, denda keterlambatan/kerusakan, total tagihan, pembayaran terverifikasi, sisa piutang, SOP, dan tanda tangan kasir/penyewa.
- Tombol cetak terintegrasi pada Admin (pesanan show), Pemilik (transaksi show), Pengguna (dashboard), dan Kiosk On-Site.

### Master Data Minimal 3 Modul
- Master Alat: CRUD lengkap, cari, filter, detail, dan validasi.
- Master Pengguna: CRUD lengkap, detail profil terproteksi, toggle status aktif/nonaktif, reset password, dan validasi.
- Master Supplier: CRUD lengkap mandiri, pencarian, detail profil & riwayat PO, dan proteksi foreign key transaksi pembelian.

### Database Seeder & Sampel Data
- DatabaseSeeder.php telah diperbaiki dan bersifat idempoten untuk seeding akun Admin, Pemilik, Pelanggan, dan data awal.
- Berkas sampel database dump tersedia di database/seeders/adventure_camp_rental_sample.sql.

### Keuangan Dashboard Pemilik
- Penerimaan kas hanya menghitung pembayaran pelanggan yang berstatus `Lunas`.
- Pembelian supplier dan biaya perbaikan dicatat sebagai pengeluaran operasional terpisah.
- Arus kas bersih dikalkulasi secara transparan: $\text{Penerimaan} - \text{Pembelian} - \text{Biaya Servis}$.
- Seluruh kartu metrik dan rincian unit stok dapat dibuka melalui modal rincian tanpa fitur pengeditan data (*strictly read-only*).

### Integritas Data dan Skema
- Database `adventure_camp_rental` dan data historis tetap utuh dan aman.
- Tidak ada tindakan destruktif terhadap database (`migrate:fresh`, `db:wipe`, dll).
- Riwayat transaksi masa lalu tetap konsisten dengan snapshot biaya saat transaksi terjadi.

---

## 16. Prinsip Akhir

Bangun aplikasi berdasarkan alur bisnis yang benar, bukan sekadar tampilan yang terlihat berfungsi.

Pisahkan:
- Hak akses Admin, Pengguna, dan Pemilik.
- Status pesanan dan status pembayaran.
- Persetujuan pesanan dan konfirmasi pembayaran.
- Stok fisik, stok dialokasikan, dan stok siap sewa.
- Riwayat penyewaan pelanggan dan pembelian inventaris dari supplier.

Pertahankan database dan fitur lama yang relevan. Implementasikan perubahan secara bertahap, uji setiap fungsi, dan buat commit Git tersendiri untuk setiap fungsi yang selesai.

Jika keputusan bisnis belum jelas, laporkan dan minta persetujuan sebelum menerapkan logika yang memengaruhi uang, stok, atau transaksi.

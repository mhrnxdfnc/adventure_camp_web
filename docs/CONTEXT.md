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
- Mengelola data alat camping.
- Mengelola data pelanggan atau akun Pengguna sesuai kebutuhan.
- Mengonfirmasi pembayaran setelah pembayaran benar-benar diterima atau diverifikasi.
- Memeriksa bukti transfer jika metode transfer menggunakan verifikasi manual.
- Mengelola proses pengambilan alat.
- Mengelola proses pengembalian dan kondisi alat.
- Mencatat keterlambatan, kerusakan, dan denda.
- Mengakses menu Inventaris.
- Mencatat penambahan atau pengurangan stok dengan alasan yang jelas.
- Mencatat pembelian stok dari supplier jika fitur pembelian inventaris diimplementasikan.
- Melihat laporan dan riwayat aktivitas sesuai kebutuhan operasional.

Admin tidak boleh menyetujui pesanan jika stok tidak mencukupi. Admin tidak boleh mengonfirmasi pembayaran hanya karena Pengguna memilih metode pembayaran atau mengunggah bukti yang belum diverifikasi.

### 3.2 Pengguna

Pengguna adalah pelanggan yang menggunakan aplikasi untuk memesan alat camping.

Hak akses Pengguna:

- Registrasi dan login.
- Melihat katalog alat camping.
- Melihat nama, kategori, tarif, kondisi, dan stok yang dapat disewa.
- Memilih satu atau beberapa alat.
- Menentukan jumlah setiap alat.
- Memilih tanggal mulai sewa dan tanggal kembali.
- Melihat ringkasan pesanan.
- Memilih metode pembayaran: QRIS, transfer bank, atau cash.
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

Pemilik bertindak sebagai pengawas bisnis dan memiliki hak akses **read-only**.

Pemilik boleh:

- Melihat total penjualan hari ini.
- Melihat total penjualan bulan berjalan.
- Melihat grafik penjualan harian dan bulanan.
- Membuka rincian angka penjualan pada dashboard.
- Melihat riwayat penjualan atau pesanan pelanggan.
- Melihat rincian transaksi.
- Melihat alat yang rusak.
- Melihat alat yang perlu perbaikan.
- Melihat riwayat pembelian pelanggan.

Pemilik tidak boleh:
- Menambah, mengubah, atau menghapus data operasional.
- Menyetujui atau menolak pesanan.
- Mengonfirmasi pembayaran.
- Mengubah stok atau tarif.
- Mengubah status pengembalian.
- Mengubah atau menghapus denda.
- Mencatat pembelian inventaris.

Seluruh halaman Pemilik harus bersifat read-only, termasuk ketika URL diakses langsung atau request dikirim secara manual.

---

## 4. Alur Pemesanan

### Tahap 1 — Pengguna memilih alat

1. Pengguna login.
2. Pengguna membuka katalog alat camping.
3. Sistem menampilkan informasi alat dan stok yang dapat disewa.
4. Pengguna memilih alat dan jumlah.
5. Pengguna menentukan tanggal mulai sewa dan tanggal kembali.
6. Pengguna memilih metode pembayaran.
7. Sistem menghitung estimasi biaya.
8. Sistem menampilkan ringkasan pesanan.
9. Pengguna menekan tombol **Lanjutkan Pesanan**.
10. Sistem menyimpan pesanan dengan status menunggu konfirmasi Admin.

Pada tahap ini pesanan belum disetujui, belum tentu mendapatkan alokasi stok permanen, dan belum dibayar.

### Tahap 2 — Admin memeriksa pesanan

1. Pesanan muncul di menu pesanan masuk Admin.
2. Admin membuka detail pesanan.
3. Sistem menampilkan daftar alat, jumlah, tanggal sewa, tanggal kembali, total estimasi, dan metode pembayaran.
4. Sistem memeriksa ketersediaan stok untuk periode tersebut.
5. Admin meninjau hasil pemeriksaan.
6. Jika stok mencukupi, Admin dapat menyetujui pesanan.
7. Jika stok tidak mencukupi, Admin dapat menolak pesanan dan wajib mengisi alasan.
8. Pengguna dapat melihat keputusan Admin.

Pemeriksaan stok harus dilakukan ulang di backend saat persetujuan. Jangan hanya mengandalkan stok yang ditampilkan saat Pengguna membuka katalog.

### Tahap 3 — Sistem menampilkan instruksi pembayaran

Setelah Admin menyetujui pesanan:

1. Sistem memastikan ulang total biaya.
2. Sistem menampilkan halaman pembayaran berdasarkan metode yang dipilih.
3. Status pembayaran menjadi belum dibayar atau menunggu pembayaran.
4. Pengguna dapat membuka kembali tagihan dan instruksi pembayaran.

Pilihan metode pembayaran tidak otomatis mengubah status menjadi lunas.

### Tahap 4 — Admin mengonfirmasi pembayaran

1. Pengguna melakukan pembayaran sesuai instruksi.
2. Jika menggunakan transfer, Pengguna dapat mengunggah bukti transfer.
3. Jika menggunakan QRIS, pembayaran dilakukan menggunakan QRIS yang disediakan kasir.
4. Jika menggunakan cash, pembayaran diserahkan kepada kasir atau Admin.
5. Admin memeriksa pembayaran.
6. Jika pembayaran valid, Admin mengonfirmasi pembayaran di sistem.
7. Sistem menyimpan waktu konfirmasi, Admin yang melakukan konfirmasi, metode pembayaran, dan jumlah pembayaran.
8. Status pembayaran berubah menjadi lunas apabila seluruh tagihan telah dibayar.
9. Pengguna dapat melihat status terbaru.

Bukti transfer yang diunggah tidak otomatis dianggap valid.

### Tahap 5 — Pengambilan alat

Setelah persyaratan pembayaran dan pengambilan terpenuhi:

1. Admin memproses penyerahan alat.
2. Sistem mencatat tanggal pengambilan dan jumlah alat yang diserahkan.
3. Status pesanan diperbarui sesuai tahapan operasional.
4. Sistem mempertahankan keterkaitan antara pesanan, detail alat, dan pembayaran.

### Tahap 6 — Pengembalian alat

1. Admin mencatat tanggal pengembalian aktual.
2. Admin memeriksa kondisi alat.
3. Jika alat rusak atau perlu perbaikan, kondisi dicatat.
4. Jika terdapat keterlambatan atau kerusakan, sistem menghitung denda sesuai kebijakan yang telah disepakati.
5. Stok diperbarui sesuai kondisi alat dan aturan inventaris.
6. Pesanan ditandai selesai setelah seluruh persyaratan penyelesaian terpenuhi.

Alat yang rusak tidak otomatis dihitung sebagai stok siap sewa.

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

## 6. Aturan Stok dan Ketersediaan

1. Sistem harus mencegah pesanan dengan jumlah nol atau negatif.
2. Sistem memeriksa ketersediaan saat Pengguna mengajukan pesanan.
3. Sistem memeriksa ulang ketersediaan ketika Admin menyetujui pesanan.
4. Pemeriksaan stok harus aman terhadap pesanan yang diproses bersamaan.
5. Pesanan yang ditolak tidak boleh menyebabkan stok berkurang permanen.
6. Pembatalan harus melepaskan alokasi stok jika stok sebelumnya telah dialokasikan.
7. Alat rusak atau perlu perbaikan tidak boleh dihitung sebagai stok siap sewa.
8. Ketersediaan harus mempertimbangkan tanggal sewa dan tanggal kembali.
9. Alat yang sedang disewa atau dialokasikan tidak boleh dipesan melebihi kapasitas inventaris pada periode yang sama.
10. Setiap perubahan stok harus divalidasi di backend.
11. Hindari stok negatif.
12. Pengembalian alat tidak otomatis berarti alat layak disewakan kembali.

Jangan langsung mengurangi stok permanen ketika Pengguna menekan Lanjutkan Pesanan.

Agent harus mengaudit mekanisme stok lama dan mengusulkan cara menangani reservasi sementara, persetujuan Admin, pembatalan, dan transaksi bersamaan. Jelaskan rancangan final sebelum mengimplementasikannya.

---

## 7. Perhitungan Tagihan

Setiap tagihan harus memiliki rincian:

- Nomor transaksi.
- Nama pelanggan.
- Daftar alat.
- Jumlah setiap alat.
- Tarif sewa yang digunakan saat transaksi.
- Tanggal mulai sewa.
- Tanggal kembali.
- Durasi sewa.
- Subtotal setiap alat.
- Biaya tambahan atau denda jika berlaku.
- Total tagihan.
- Total pembayaran yang sudah dikonfirmasi.
- Sisa tagihan jika pembayaran parsial diizinkan.

Simpan tarif yang digunakan pada detail transaksi agar perubahan tarif saat ini tidak mengubah tagihan transaksi lama.

Sebelum menetapkan perhitungan final, audit dan tentukan:

- Tarif dihitung per hari, per malam, atau per transaksi.
- Cara menghitung durasi sewa.
- Apakah tanggal sewa dan tanggal kembali dihitung inklusif atau eksklusif.
- Apakah pembayaran harus lunas sebelum pengambilan.
- Cara menghitung denda keterlambatan dan kerusakan.
- Kebijakan pembatalan dan pengembalian dana.

Jangan membuat keputusan tersebut secara diam-diam.

Total tagihan harus dihitung ulang di backend. Jangan mempercayai nominal yang dikirim frontend.

---

## 8. Dashboard dan Menu

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

### 8.3 Dashboard Pemilik

Menampilkan:

- Total penjualan hari ini.
- Total penjualan bulan berjalan.
- Grafik penjualan harian.
- Grafik tren penjualan bulanan.
- Daftar alat rusak.
- Daftar alat yang perlu perbaikan.
- Ringkasan riwayat penjualan atau pesanan pelanggan.
- Akses ke rincian transaksi.
- Akses ke riwayat pembelian pelanggan.

Angka penjualan harus didasarkan pada pembayaran yang benar-benar telah dikonfirmasi, bukan semua pesanan yang baru dibuat.

Jika ada pembayaran parsial, bedakan uang yang telah diterima dari total nilai pesanan.

Gunakan zona waktu aplikasi yang konsisten, misalnya `Asia/Jakarta`, untuk menghitung penjualan hari ini dan bulan berjalan.

Setiap angka grafik harus bisa ditelusuri ke data transaksi yang mendasarinya.

### 8.4 Riwayat Pembelian milik Pemilik

Dalam proyek ini, istilah **Riwayat Pembelian milik Pemilik berarti riwayat pesanan pelanggan**, bukan pembelian stok dari supplier.

Tampilkan:
- Nomor transaksi.
- Tanggal pemesanan.
- Nama pelanggan.
- Rincian alat.
- Jumlah alat.
- Durasi sewa.
- Total tagihan.
- Metode pembayaran.
- Status pesanan.
- Status pembayaran.

Pemilik dapat mencari, memfilter tanggal, membuka detail, dan melihat riwayat tersebut tanpa mengubah data.

Gunakan tabel transaksi yang sudah ada setelah relasinya diverifikasi.

---

## 9. Menu Inventaris untuk Admin

Menu Inventaris berfungsi untuk memantau persediaan alat camping dan perubahan stok.

Fitur yang dibutuhkan:

- Melihat daftar alat.
- Melihat stok fisik.
- Melihat stok yang tersedia untuk disewa.
- Melihat stok yang sedang dialokasikan atau disewa.
- Melihat kondisi alat.
- Melihat alat rusak dan perlu perbaikan.
- Mencatat penambahan stok.
- Mencatat pengurangan stok dengan alasan.
- Melihat riwayat perubahan stok.
- Mencatat pembelian stok dari supplier jika fitur tersebut diimplementasikan.

### Pembelian stok dari supplier

Pembelian stok dari supplier merupakan fitur inventaris terpisah dari pesanan pelanggan.

Jika fitur ini ditambahkan, data yang dapat dicatat mencakup:
- Nomor transaksi pembelian.
- Tanggal pembelian.
- Nama supplier.
- Daftar alat yang dibeli.
- Kuantitas.
- Harga beli satuan.
- Total biaya pembelian.
- Admin yang mencatat.
- Catatan transaksi.

Audit database sebelum membuat tabel supplier atau pembelian. Jangan menganggap struktur tersebut sudah tersedia.

Jika fitur pembelian supplier belum diperlukan untuk versi awal, bangun pemantauan stok dan riwayat perubahan stok terlebih dahulu.

### Aturan inventaris

- Perubahan stok harus dicatat dan dapat ditelusuri.
- Stok yang sedang disewa harus dibedakan dari stok siap sewa.
- Stok rusak tidak boleh dihitung sebagai stok siap sewa.
- Penambahan stok dari supplier harus dicatat sebagai transaksi inventaris jika fitur pembelian tersedia.
- Setiap penyesuaian harus mencatat alasan, waktu, dan pengguna yang melakukan perubahan.
- Jangan mengubah tagihan atau riwayat transaksi lama ketika stok berubah.

---

## 10. Struktur Database dan Migrasi

Database yang harus dipertahankan adalah `adventure_camp_rental`.

Tabel lama yang diketahui atau harus diverifikasi:

- `users`
- `pelanggan`
- `alat`
- `penyewaan`
- `detail_penyewaan`
- `pembayaran`
- `pengembalian`
- `denda`
- `activity_logs`

### Struktur yang diketahui

- `users`: `id_user`, `nama_user`, `username`, `password`, `role`, `status_aktif`, `created_at`.
- `pelanggan`: `id_pelanggan`, `nama_pelanggan`, `alamat`, `nomor_telepon`, `created_at`.
- `alat`: `kode_alat` sebagai primary key, `nama_alat`, `kategori`, `stok`, `kondisi`, `tarif_sewa`, `created_at`.
- `penyewaan`: `no_transaksi` sebagai primary key, `id_pelanggan`, `id_user`, `tanggal_sewa`, `tanggal_kembali`, `status_penyewaan`.
- `detail_penyewaan`: `id_detail`, `no_transaksi`, `kode_alat`, `jumlah`, `tarif_sewa`.
- `pembayaran`: `no_pembayaran`, `no_transaksi`, `total_biaya`, `jumlah_pembayaran`, `metode_pembayaran`, `tanggal_pembayaran`, `status_pembayaran`.
- `pengembalian`: `id_pengembalian`, `no_transaksi`, `tanggal_pengembalian_aktual`, `kondisi_alat`, `status_pengembalian`.
- `denda`: `id_denda`, `id_pengembalian`, `tarif_denda`, `nominal_denda`, `alasan_denda`.
- `activity_logs`: `id_log`, `id_user`, `aktivitas`, `modul`, `data_diproses`, `waktu`.

Daftar ini merupakan informasi awal, bukan pengganti pemeriksaan skema aktual.

### Catatan penting

1. Role lama diketahui mencakup Admin dan Pemilik. Tambahkan dukungan Pengguna tanpa merusak akun lama.
2. Tentukan relasi akun login Pengguna dengan tabel `pelanggan`. Jangan mengasumsikan relasi tersebut sudah tersedia.
3. `alat` menggunakan `kode_alat`, bukan `id_alat`.
4. `penyewaan` menggunakan `no_transaksi`, bukan `id_penyewaan`.
5. Tabel pembayaran perlu diaudit untuk mendukung konfirmasi manual, bukti pembayaran, dan riwayat pembayaran.
6. Status pesanan dan status pembayaran harus dipisahkan.
7. Periksa apakah diperlukan mekanisme alokasi stok berdasarkan periode sewa.
8. Konfigurasi QRIS cukup berupa instruksi untuk meminta QRIS kepada kasir pada versi awal.
9. Informasi rekening bank hanya boleh ditampilkan apabila sudah dikonfigurasi dengan benar.
10. Jika fitur pembelian supplier dibuat, desain tabel baru setelah audit.
11. Semua migration harus mempertahankan data lama dan diuji pada salinan database sebelum diterapkan.

Agent harus menjelaskan kebutuhan perubahan skema sebelum menerapkan migration yang berisiko.

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

---

## 13. Tahapan Implementasi

Kerjakan secara bertahap.

1. Audit source code, database, fitur lama, dan Git.
2. Laporkan kondisi proyek dan rencana migrasi.
3. Siapkan Laravel dan koneksi database dengan aman.
4. Bangun layout dan desain dasar.
5. Implementasikan autentikasi dan tiga role.
6. Implementasikan katalog alat dan tampilan stok.
7. Implementasikan pemilihan alat dan ringkasan pesanan.
8. Implementasikan pengajuan pesanan Pengguna.
9. Implementasikan pemeriksaan stok dan persetujuan atau penolakan Admin.
10. Implementasikan instruksi pembayaran QRIS, transfer, dan cash.
11. Implementasikan verifikasi pembayaran Admin.
12. Implementasikan pengambilan alat.
13. Implementasikan pengembalian dan denda.
14. Implementasikan menu Inventaris dan riwayat perubahan stok.
15. Implementasikan dashboard Pemilik.
16. Implementasikan grafik penjualan dan rincian transaksi.
17. Implementasikan riwayat pembelian pelanggan.
18. Implementasikan audit trail.
19. Lakukan pengujian integrasi seluruh role.
20. Selesaikan perbaikan, dokumentasi, dan pengujian akhir.

Urutan dapat disesuaikan berdasarkan hasil audit, tetapi pengujian dan keamanan tidak boleh dilewati.

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
- `feat: add equipment return processing`
- `feat: add late return fine calculation`
- `feat: add inventory stock history`
- `feat: add damaged equipment report`
- `feat: add supplier stock purchase records` jika fitur supplier disetujui dan dibuat.

### Dashboard Pemilik
- `feat: add owner daily sales summary`
- `feat: add owner monthly sales chart`
- `feat: add owner sales history`
- `feat: add owner customer purchase history`

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

Jika sebuah fitur memerlukan perubahan database dan kode, seluruh perubahan yang memang diperlukan untuk satu fungsi boleh berada dalam commit yang sama setelah diuji.

---

## 15. Pengujian dan Kriteria Penerimaan

### Role dan keamanan
- Ketiga role dapat login sesuai akun masing-masing.
- Pengguna tidak dapat mengakses data milik Pengguna lain.
- Pemilik hanya dapat melihat data.
- Admin dapat melakukan tindakan operasional sesuai hak akses.

### Pemesanan
- Pengguna dapat memilih alat, jumlah, dan periode sewa.
- Sistem menampilkan ringkasan dan total estimasi.
- Pesanan masuk ke Admin dengan status menunggu konfirmasi.
- Admin dapat menyetujui atau menolak pesanan.
- Penolakan harus menyimpan alasan.
- Stok diperiksa ulang ketika pesanan disetujui.

### Pembayaran
- QRIS menampilkan instruksi meminta QRIS kepada kasir.
- QRIS tidak menggunakan kode QR acak yang diklaim sebagai pembayaran valid.
- Transfer menampilkan rekening yang valid atau pemberitahuan jika informasi belum tersedia.
- Cash menampilkan total tagihan dan instruksi pembayaran tunai.
- Pembayaran tidak otomatis lunas hanya karena metode dipilih.
- Admin dapat mengonfirmasi pembayaran yang benar-benar diterima atau diverifikasi.

### Inventaris dan pengembalian
- Stok tidak menjadi negatif.
- Sistem mencegah pemesanan berlebih.
- Stok alat rusak tidak dihitung sebagai stok siap sewa.
- Perubahan stok tercatat.
- Pengembalian dan denda mengikuti aturan bisnis yang telah disetujui.

### Pemilik
- Dashboard menampilkan total penjualan hari ini dan bulan berjalan.
- Grafik dapat ditelusuri ke rincian transaksi.
- Riwayat pembelian menampilkan pesanan pelanggan.
- Pemilik dapat melihat alat rusak dan perlu perbaikan.
- Pemilik tidak dapat mengubah data.

### Integritas data dan Git
- Database lama tetap aman.
- Tidak ada migration destruktif.
- Transaksi lama tetap dapat ditampilkan dengan benar.
- Setiap fungsi yang selesai telah diuji dan memiliki commit tersendiri.
- Tidak ada secret yang ter-commit.
- Dokumentasi menjelaskan cara menjalankan aplikasi dan pengujian.

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

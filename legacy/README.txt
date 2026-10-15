ADVENTURE CAMP RENTAL — VERSI WEB (HTML + CSS + JS + PHP)

Versi ini menggantikan antarmuka Flutter dengan web responsif. HTML/CSS/JavaScript menjadi tampilan, sedangkan PHP yang sudah ada tetap menjadi API dan MySQL tetap menyimpan data.

CARA PASANG DI XAMPP
1. Backup database adventure_camp_rental.
2. Salin folder adventure_camp_web ke C:\\xampp\\htdocs\\ (boleh ubah nama folder, misalnya menjadi adventure_camp).
3. Buka koneksi.php dan sesuaikan username/password MySQL. File bawaan paket memakai user root dan password root; pada instalasi XAMPP yang password root-nya kosong, ubah menjadi $password = "".
4. Pastikan database adventure_camp_rental dan tabel-tabelnya sudah ada.
5. Jalankan Apache dan MySQL dari XAMPP.
6. Buka http://localhost/adventure_camp_web/ (atau nama folder yang kamu pakai).

FITUR ANTARMUKA
- Login Admin, Pemilik, dan Pelanggan (akun harus tersedia di tabel users dan role-nya harus cocok).
- Dashboard ringkasan.
- Tabel data alat, pelanggan, penyewaan, pembayaran, pengembalian, dan pengguna.
- Form tambah alat, pelanggan, transaksi penyewaan, pembayaran, serta pengembalian/denda.
- Filter pencarian tabel dan laporan penyewaan/pendapatan.
- Layout responsif untuk desktop dan ponsel.

CATATAN PENTING
- Backend berasal dari file PHP proyek yang diberikan dan belum bisa diuji terhadap database lokalmu dari sini. Struktur tabel di screenshot tidak menggantikan pemeriksaan kolom aktual; jika ada pesan SQL error, kirim pesan error dan struktur tabel terkait.
- API login diperbarui agar role yang dipilih harus cocok dan login pelanggan tidak bisa dilakukan hanya dengan nama/nomor telepon tanpa password. Akun pelanggan harus dibuat di tabel users.
- Halaman Pelanggan saat ini sengaja hanya menampilkan placeholder. Agar pelanggan bisa melihat transaksinya sendiri, database/API perlu menghubungkan akun users ke id_pelanggan dan memfilter endpoint berdasarkan akun login. Jangan tampilkan endpoint data umum kepada pelanggan sebelum pembatasan ini diterapkan.
- Pembatasan role di antarmuka bukan pengganti otorisasi backend. Sebelum dipakai di internet, semua endpoint tulis/baca perlu memeriksa sesi/token dan role di sisi server, dan password plaintext perlu dimigrasikan ke password_hash/password_verify.
- Buat backup sebelum mengganti file PHP yang lama.


UPDATE DASHBOARD PEMILIK & PELANGGAN
- Dashboard Pemilik sekarang menampilkan penjualan hari ini/bulan ini, pendapatan hari ini/bulan ini, serta empat grafik.
- Dashboard Pelanggan menampilkan transaksi miliknya, total harga, item pesanan, status pembayaran, dan instruksi transfer DEMO.
- Sebelum fitur pelanggan dipakai, backup database lalu jalankan query_role_pelanggan.sql satu kali dan isi users.id_pelanggan dengan ID pelanggan yang sesuai.
- API pelanggan menggunakan PHP session; logout menghapus session. Login ulang setelah memasang versi ini.
- Rekening 1234567890 dan kode AC-* adalah data contoh, bukan rekening sungguhan. Ganti dengan informasi pembayaran resmi sebelum digunakan.
- Pendapatan dihitung dari pembayaran dengan status 'Lunas' berdasarkan tanggal_pembayaran. Penjualan dihitung dari tanggal_sewa.

PERBAIKAN LOGIN
- api_login.php telah diperbarui agar login Admin/Pemilik tidak gagal jika users.id_pelanggan belum ditambahkan.
- Pastikan kredensial MySQL di koneksi.php sesuai. XAMPP sering memakai user root dengan password kosong; sesuaikan jika instalasimu berbeda.
- Untuk akun Pelanggan, jalankan query_role_pelanggan.sql satu kali setelah backup database dan tautkan id_pelanggan dengan ID pelanggan yang benar.
- Salin folder hasil ZIP ini ke htdocs untuk mengganti file lama, lalu muat ulang halaman dan login kembali.

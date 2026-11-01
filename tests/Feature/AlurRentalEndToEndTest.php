<?php

namespace Tests\Feature;

use App\Http\Controllers\KeranjangController;
use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Penyewaan;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class AlurRentalEndToEndTest extends TestCase
{
    /**
     * Skenario 1 s.d. 10: Pengujian End-to-End Alur Lengkap Pelanggan
     * 1. Pelanggan membuka katalog dan melihat tarif harian.
     * 2. Jumlah awal setiap barang adalah 0.
     * 3. Pelanggan memilih 2 tenda dan 3 sleeping bag.
     * 4. Pelanggan membuka keranjang dan memverifikasi subtotal sementara per hari.
     * 5. Pelanggan melanjutkan ke checkout.
     * 6. Pelanggan memilih tanggal mulai 10 Oktober dan tanggal kembali 13 Oktober pada tahun pengujian yang valid.
     * 7. Sistem menghitung durasi 3 hari.
     * 8. Sistem menghitung total Rp525.000 berdasarkan tarif contoh yang sesuai.
     * 9. Sistem menyimpan satu transaksi dengan satu periode sewa bersama dan beberapa detail barang.
     * 10. Tagihan dan instruksi pembayaran menampilkan nilai yang sama dengan hasil perhitungan server.
     */
    public function test_skenario_1_sampai_10_alur_lengkap_pelanggan_dari_katalog_ke_pembayaran(): void
    {
        $user = User::where('username', 'pelanggan')->firstOrFail();
        $admin = User::where('role', 'Admin')->firstOrFail();

        // Siapkan master data alat contoh
        $tenda = Alat::where('kode_alat', 'ALT001')->firstOrFail();
        $tenda->stok = 10;
        $tenda->kondisi = 'Baik';
        $tenda->tarif_sewa = 50000.00;
        $tenda->save();

        $sleepingBag = Alat::where('kode_alat', 'ALT002')->firstOrFail();
        $sleepingBag->stok = 10;
        $sleepingBag->kondisi = 'Baik';
        $sleepingBag->tarif_sewa = 25000.00;
        $sleepingBag->save();

        // Bersihkan keranjang sesi awal
        KeranjangController::clear($user->id_user);

        // --- SKENARIO 1: Pelanggan membuka katalog dan melihat tarif harian ---
        $resKatalog = $this->actingAs($user)->get(route('pengguna.katalog.index'));
        $resKatalog->assertStatus(200);
        $resKatalog->assertSee('Tenda Dome 4 Orang');
        $resKatalog->assertSee('Sleeping Bag');
        $resKatalog->assertSee('Rp 50.000');
        $resKatalog->assertSee('Rp 25.000');
        $resKatalog->assertSee('/ hari');

        // --- SKENARIO 2: Jumlah awal setiap barang adalah 0 ---
        $resKatalog->assertSee('value="0"', false);

        // --- SKENARIO 3: Pelanggan memilih 2 tenda dan 3 sleeping bag ---
        $addTenda = $this->actingAs($user)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => 'ALT001',
            'jumlah' => 2,
        ]);
        $addTenda->assertRedirect(route('pengguna.katalog.index'));
        $addTenda->assertSessionHas('success');

        $addSb = $this->actingAs($user)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => 'ALT002',
            'jumlah' => 3,
        ]);
        $addSb->assertRedirect(route('pengguna.katalog.index'));
        $addSb->assertSessionHas('success');

        // --- SKENARIO 4: Pelanggan membuka keranjang dan memverifikasi subtotal sementara per hari ---
        $resKeranjang = $this->actingAs($user)->get(route('pengguna.keranjang.index'));
        $resKeranjang->assertStatus(200);

        // Verifikasi rincian barang di keranjang
        // Tenda: 2 unit x 50.000 = 100.000
        // Sleeping bag: 3 unit x 25.000 = 75.000
        // Total sementara per hari = 175.000
        $resKeranjang->assertSee('2 unit');
        $resKeranjang->assertSee('3 unit');
        $resKeranjang->assertSee('Rp 100.000');
        $resKeranjang->assertSee('Rp 75.000');
        $resKeranjang->assertSee('Rp 175.000');
        $resKeranjang->assertSee('Total Sementara / Hari');
        $resKeranjang->assertSee('5 Unit');

        // --- SKENARIO 5: Pelanggan melanjutkan ke checkout ---
        $resCheckout = $this->actingAs($user)->get(route('pengguna.checkout.index'));
        $resCheckout->assertStatus(200);
        $resCheckout->assertSee('Checkout Penyewaan');
        $resCheckout->assertSee('Tanggal Mulai Sewa');
        $resCheckout->assertSee('Tanggal Kembali');
        $resCheckout->assertSee('Ringkasan Pesanan Alat');
        $resCheckout->assertSee('Konfirmasi & Lanjutkan Pesanan');

        // --- SKENARIO 6: Pelanggan memilih tanggal mulai 10 Oktober dan kembali 13 Oktober pada tahun pengujian yang valid ---
        // Tentukan tahun pengujian di masa depan agar selalu valid terhadap aturan after_or_equal:today
        $tahunPengujian = Carbon::now()->month > 10 ? Carbon::now()->year + 1 : Carbon::now()->year;
        // Jika 10 Oktober tahun ini sudah lewat hari ini, gunakan tahun depan
        $targetMulai = Carbon::create($tahunPengujian, 10, 10)->startOfDay();
        if ($targetMulai->isPast()) {
            $tahunPengujian++;
        }
        $tglSewa = sprintf('%04d-10-10', $tahunPengujian);
        $tglKembali = sprintf('%04d-10-13', $tahunPengujian);

        // Kirim request checkout dengan metode pembayaran Transfer Bank
        $submitCheckout = $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'metode_pembayaran' => 'Transfer Bank',
        ]);

        $submitCheckout->assertRedirect(route('pengguna.dashboard'));
        $submitCheckout->assertSessionHas('success');

        // Ambil data transaksi yang baru saja tersimpan di database
        $trx = Penyewaan::with(['detailPenyewaan.alat', 'pembayaran'])
            ->where('id_user', $user->id_user)
            ->latest('no_transaksi')
            ->firstOrFail();

        // --- SKENARIO 7: Sistem menghitung durasi 3 hari ---
        // 10 Oktober s/d 13 Oktober = tepat 3 hari (13 - 10 = 3 hari kalender)
        $this->assertEquals(3, $trx->durasi_hari);
        $this->assertEquals($tglSewa, Carbon::parse($trx->tanggal_sewa)->format('Y-m-d'));
        $this->assertEquals($tglKembali, Carbon::parse($trx->tanggal_kembali)->format('Y-m-d'));

        // --- SKENARIO 8: Sistem menghitung total Rp525.000 berdasarkan tarif contoh yang sesuai ---
        // Tenda: Rp50.000 x 2 unit x 3 hari = Rp300.000
        // Sleeping bag: Rp25.000 x 3 unit x 3 hari = Rp225.000
        // Total = Rp525.000
        $this->assertEquals(525000.00, (float) $trx->total_biaya);
        $this->assertEquals(525000.00, (float) $trx->pembayaran->total_biaya);

        // --- SKENARIO 9: Sistem menyimpan satu transaksi dengan satu periode sewa bersama dan beberapa detail barang ---
        $this->assertEquals('Menunggu Konfirmasi', $trx->status_penyewaan);
        $this->assertEquals('online', $trx->sumber_pesanan);
        $this->assertCount(2, $trx->detailPenyewaan);

        // Periksa detail tenda
        $detailTenda = $trx->detailPenyewaan->where('kode_alat', 'ALT001')->firstOrFail();
        $this->assertEquals(2, $detailTenda->jumlah);
        $this->assertEquals(50000.00, (float) $detailTenda->tarif_sewa);
        $this->assertEquals(300000.00, $detailTenda->subtotal_biaya);

        // Periksa detail sleeping bag
        $detailSb = $trx->detailPenyewaan->where('kode_alat', 'ALT002')->firstOrFail();
        $this->assertEquals(3, $detailSb->jumlah);
        $this->assertEquals(25000.00, (float) $detailSb->tarif_sewa);
        $this->assertEquals(225000.00, $detailSb->subtotal_biaya);

        // Pastikan keranjang belanja pelanggan telah otomatis dibersihkan
        $this->assertEquals(0, KeranjangController::count($user->id_user));

        // --- SKENARIO 10: Tagihan dan instruksi pembayaran menampilkan nilai yang sama dengan hasil perhitungan server ---
        // Admin menyetujui pesanan agar berstatus Menunggu Pembayaran dan siap dilihat instruksinya
        $this->actingAs($admin)->post(route('admin.pesanan.setujui', $trx->no_transaksi))->assertSessionHas('success');

        // A. Halaman Instruksi Transfer Bank
        $resTransfer = $this->actingAs($user)->get(route('pengguna.pembayaran.transfer', $trx->no_transaksi));
        $resTransfer->assertStatus(200);
        $resTransfer->assertSee('Rp 525.000');
        $resTransfer->assertSee('Rp 300.000');
        $resTransfer->assertSee('Rp 225.000');

        // B. Halaman Instruksi QRIS
        $resQris = $this->actingAs($user)->get(route('pengguna.pembayaran.qris', $trx->no_transaksi));
        $resQris->assertStatus(200);
        $resQris->assertSee('Rp 525.000');
        $resQris->assertSee('Rp 300.000');
        $resQris->assertSee('Rp 225.000');

        // C. Halaman Instruksi Cash
        $resCash = $this->actingAs($user)->get(route('pengguna.pembayaran.cash', $trx->no_transaksi));
        $resCash->assertStatus(200);
        $resCash->assertSee('Rp 525.000');
        $resCash->assertSee('Rp 300.000');
        $resCash->assertSee('Rp 225.000');
    }

    /**
     * Skenario 11: Admin dapat melihat pesanan sesuai alur otorisasi yang sudah ada.
     */
    public function test_skenario_11_admin_dapat_melihat_pesanan_sesuai_alur_otorisasi(): void
    {
        $admin = User::where('role', 'Admin')->firstOrFail();
        $user = User::where('username', 'pelanggan')->firstOrFail();

        // Pastikan stok alat mencukupi untuk pemesanan contoh 2 unit tenda dan 3 unit sleeping bag
        $tenda = Alat::where('kode_alat', 'ALT001')->firstOrFail();
        $tenda->stok = 10;
        $tenda->save();

        $sb = Alat::where('kode_alat', 'ALT002')->firstOrFail();
        $sb->stok = 10;
        $sb->save();

        // Buat pesanan baru untuk verifikasi Admin
        $tglSewa = Carbon::tomorrow()->addDays(5)->format('Y-m-d');
        $tglKembali = Carbon::tomorrow()->addDays(8)->format('Y-m-d'); // 3 hari

        KeranjangController::clear($user->id_user);
        KeranjangController::add($user->id_user, 'ALT001', 2);
        KeranjangController::add($user->id_user, 'ALT002', 3);

        $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'metode_pembayaran' => 'Cash',
        ])->assertSessionHasNoErrors();

        $trx = Penyewaan::where('id_user', $user->id_user)->latest('no_transaksi')->firstOrFail();

        // 1. Admin melihat antrean pesanan di Dashboard Admin
        $resAdminDash = $this->actingAs($admin)->get(route('admin.dashboard'));
        $resAdminDash->assertStatus(200);
        $resAdminDash->assertSee($trx->no_transaksi);

        // 2. Admin membuka detail rincian pesanan
        $resAdminPesanan = $this->actingAs($admin)->get(route('admin.pesanan.show', $trx->no_transaksi));
        $resAdminPesanan->assertStatus(200);
        $resAdminPesanan->assertSee($trx->no_transaksi);
        $resAdminPesanan->assertSee('Rp 525.000');
        $resAdminPesanan->assertSee('3 Hari');

        // 3. Admin membuka halaman verifikasi pembayaran
        $resAdminBayar = $this->actingAs($admin)->get(route('admin.pembayaran.show', $trx->no_transaksi));
        $resAdminBayar->assertStatus(200);
        $resAdminBayar->assertSee($trx->no_transaksi);
        $resAdminBayar->assertSee('Rp 525.000');
    }

    /**
     * Skenario 12: Pelanggan tidak dapat melihat transaksi pelanggan lain (IDOR Protection).
     */
    public function test_skenario_12_pelanggan_tidak_dapat_melihat_transaksi_pelanggan_lain(): void
    {
        $pelanggan1 = User::where('username', 'pelanggan')->firstOrFail();
        $pelanggan2 = User::where('username', 'budi')->firstOrFail();

        // Siapkan pesanan milik Pelanggan 1
        $trx1 = Penyewaan::where('id_user', $pelanggan1->id_user)
            ->whereIn('status_penyewaan', ['Menunggu Pembayaran', 'Disetujui'])
            ->first();

        if (! $trx1) {
            $trx1 = Penyewaan::create([
                'no_transaksi' => 'TRX-IDOR-TEST-001',
                'id_pelanggan' => $pelanggan1->id_pelanggan,
                'id_user' => $pelanggan1->id_user,
                'tanggal_sewa' => Carbon::tomorrow()->format('Y-m-d'),
                'tanggal_kembali' => Carbon::tomorrow()->addDays(2)->format('Y-m-d'),
                'status_penyewaan' => 'Menunggu Pembayaran',
                'sumber_pesanan' => 'online',
            ]);
            DetailPenyewaan::create([
                'no_transaksi' => $trx1->no_transaksi,
                'kode_alat' => 'ALT001',
                'jumlah' => 1,
                'tarif_sewa' => 50000.00,
            ]);
            Pembayaran::create([
                'no_pembayaran' => 'PAY-IDOR-TEST-001',
                'no_transaksi' => $trx1->no_transaksi,
                'total_biaya' => 100000.00,
                'jumlah_pembayaran' => 0,
                'metode_pembayaran' => 'Transfer Bank',
                'status_pembayaran' => 'Belum Dibayar',
            ]);
        }

        // Pelanggan 2 mencoba mengakses invoice & transaksi milik Pelanggan 1
        $resTransfer = $this->actingAs($pelanggan2)->get(route('pengguna.pembayaran.transfer', $trx1->no_transaksi));
        $resTransfer->assertStatus(403);

        $resQris = $this->actingAs($pelanggan2)->get(route('pengguna.pembayaran.qris', $trx1->no_transaksi));
        $resQris->assertStatus(403);

        $resCash = $this->actingAs($pelanggan2)->get(route('pengguna.pembayaran.cash', $trx1->no_transaksi));
        $resCash->assertStatus(403);

        $resBukti = $this->actingAs($pelanggan2)->get(route('pembayaran.bukti', $trx1->no_transaksi));
        $resBukti->assertStatus(403);
    }

    /**
     * Skenario 13: Stok tidak boleh berubah secara tidak konsisten akibat penambahan atau pengubahan keranjang.
     */
    public function test_skenario_13_stok_tidak_berubah_akibat_penambahan_atau_perubahan_keranjang(): void
    {
        $user = User::where('username', 'pelanggan')->firstOrFail();

        $tenda = Alat::where('kode_alat', 'ALT001')->firstOrFail();
        $tenda->stok = 10;
        $tenda->save();

        $sb = Alat::where('kode_alat', 'ALT002')->firstOrFail();
        $sb->stok = 15;
        $sb->save();

        KeranjangController::clear($user->id_user);

        // 1. Tambah ke keranjang
        $this->actingAs($user)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => 'ALT001',
            'jumlah' => 2,
        ]);
        $this->assertEquals(10, $tenda->fresh()->stok, 'Stok fisik tidak boleh berkurang saat barang ditambah ke keranjang');

        $this->actingAs($user)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => 'ALT002',
            'jumlah' => 4,
        ]);
        $this->assertEquals(15, $sb->fresh()->stok, 'Stok fisik tidak boleh berkurang saat barang kedua ditambah ke keranjang');

        // 2. Ubah kuantitas di keranjang
        $this->actingAs($user)->post(route('pengguna.keranjang.update'), [
            'kode_alat' => 'ALT001',
            'jumlah' => 5,
        ]);
        $this->assertEquals(10, $tenda->fresh()->stok, 'Stok fisik tidak boleh berubah saat kuantitas keranjang diubah');

        // 3. Hapus item dari keranjang
        $this->actingAs($user)->post(route('pengguna.keranjang.hapus'), [
            'kode_alat' => 'ALT002',
        ]);
        $this->assertEquals(15, $sb->fresh()->stok, 'Stok fisik tidak boleh bertambah/berubah saat item keranjang dihapus');

        // 4. Kosongkan keranjang
        $this->actingAs($user)->post(route('pengguna.keranjang.kosongkan'));
        $this->assertEquals(10, $tenda->fresh()->stok, 'Stok fisik harus tetap utuh saat keranjang dikosongkan');
        $this->assertEquals(15, $sb->fresh()->stok, 'Stok fisik harus tetap utuh saat keranjang dikosongkan');
    }

    /**
     * Skenario 14: Transaksi lama tetap dapat dibaca dengan nilai historisnya meskipun master tarif diubah.
     */
    public function test_skenario_14_transaksi_lama_tetap_dibaca_dengan_nilai_historisnya(): void
    {
        $user = User::where('username', 'pelanggan')->firstOrFail();
        $admin = User::where('role', 'Admin')->firstOrFail();
        $pemilik = User::where('role', 'Pemilik')->firstOrFail();

        // 1. Buat transaksi baru dengan harga normal
        $tenda = Alat::where('kode_alat', 'ALT001')->firstOrFail();
        $tenda->tarif_sewa = 50000.00;
        $tenda->stok = 10;
        $tenda->save();

        KeranjangController::clear($user->id_user);
        KeranjangController::add($user->id_user, 'ALT001', 2);

        $tglSewa = Carbon::tomorrow()->addDays(10)->format('Y-m-d');
        $tglKembali = Carbon::tomorrow()->addDays(13)->format('Y-m-d'); // 3 hari

        $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'metode_pembayaran' => 'Cash',
        ])->assertSessionHasNoErrors();

        $trx = Penyewaan::where('id_user', $user->id_user)->latest('no_transaksi')->firstOrFail();
        $this->assertEquals(300000.00, $trx->total_biaya);

        // 2. Sekarang ubah master tarif ALT001 menjadi 4x lipat (Rp 200.000)
        $tenda->tarif_sewa = 200000.00;
        $tenda->save();

        // 3. Pastikan transaksi lama tetap terbaca Rp 300.000
        $freshTrx = Penyewaan::where('no_transaksi', $trx->no_transaksi)->firstOrFail();
        $this->assertEquals(300000.00, $freshTrx->total_biaya, 'Total biaya transaksi lama harus tetap menggunakan snapshot historis');
        $this->assertEquals(50000.00, (float) $freshTrx->detailPenyewaan->first()->tarif_sewa);
        $this->assertEquals(300000.00, $freshTrx->detailPenyewaan->first()->subtotal_biaya);

        // 4. Periksa pada halaman Pemilik Transaksi Show
        $resPemilik = $this->actingAs($pemilik)->get(route('pemilik.transaksi.show', $trx->no_transaksi));
        $resPemilik->assertStatus(200);
        $resPemilik->assertSee('Rp 300.000');
        $resPemilik->assertDontSee('Rp 1.200.000');

        // 5. Periksa pada halaman Admin Pembayaran Show
        $resAdmin = $this->actingAs($admin)->get(route('admin.pembayaran.show', $trx->no_transaksi));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Rp 300.000');
        $resAdmin->assertDontSee('Rp 1.200.000');
    }

    /**
     * Pengujian Regresi: Aturan Durasi Bersama dan Validasi Tanggal
     * - Tanggal kosong ditolak
     * - Tanggal sama (kembali == mulai) ditolak
     * - Tanggal terbalik (kembali < mulai) ditolak
     * - Tanggal di masa lampau ditolak
     * - Durasi dihitung kalender murni: kembali - mulai
     */
    public function test_regresi_aturan_durasi_bersama_dan_validasi_tanggal(): void
    {
        $user = User::where('username', 'pelanggan')->firstOrFail();

        KeranjangController::clear($user->id_user);
        KeranjangController::add($user->id_user, 'ALT001', 1);

        $tglBesok = Carbon::tomorrow()->format('Y-m-d');
        $tglKemarin = Carbon::yesterday()->format('Y-m-d');

        // 1. Tanggal kosong
        $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => '',
            'tanggal_kembali' => '',
            'metode_pembayaran' => 'Cash',
        ])->assertSessionHasErrors(['tanggal_sewa', 'tanggal_kembali']);

        // 2. Tanggal sama (kembali = mulai)
        $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglBesok,
            'tanggal_kembali' => $tglBesok,
            'metode_pembayaran' => 'Cash',
        ])->assertSessionHasErrors(['tanggal_kembali']);

        // 3. Tanggal terbalik (kembali < mulai)
        $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglBesok,
            'tanggal_kembali' => $tglKemarin,
            'metode_pembayaran' => 'Cash',
        ])->assertSessionHasErrors(['tanggal_kembali']);

        // 4. Tanggal sewa di masa lampau
        $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglKemarin,
            'tanggal_kembali' => $tglBesok,
            'metode_pembayaran' => 'Cash',
        ])->assertSessionHasErrors(['tanggal_sewa']);

        // 5. Perhitungan durasi kalender murni di model
        $this->assertEquals(3, Penyewaan::hitungDurasiHari('2026-10-10', '2026-10-13'));
        $this->assertEquals(1, Penyewaan::hitungDurasiHari('2026-10-10', '2026-10-11'));
        $this->assertEquals(7, Penyewaan::hitungDurasiHari('2026-10-10', '2026-10-17'));
    }
}

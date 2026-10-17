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

class PerhitunganBiayaSewaTest extends TestCase
{
    /**
     * Uji logika model hitung durasi hari (selisih tanggal dalam hari)
     */
    public function test_hitung_durasi_hari_model_logic(): void
    {
        // 1 hari: 10 ke 11
        $this->assertEquals(1, Penyewaan::hitungDurasiHari('2026-11-10', '2026-11-11'));

        // 3 hari: 10 ke 13 (sesuai contoh instruksi)
        $this->assertEquals(3, Penyewaan::hitungDurasiHari('2026-11-10', '2026-11-13'));

        // Beberapa hari: 7 hari
        $this->assertEquals(7, Penyewaan::hitungDurasiHari('2026-11-01', '2026-11-08'));

        // Tanggal sama minimal 1 hari
        $this->assertEquals(1, Penyewaan::hitungDurasiHari('2026-11-10', '2026-11-10'));
    }

    /**
     * Uji rumus total biaya setiap alat = tarif × jumlah × durasi
     */
    public function test_hitung_total_biaya_model_formula(): void
    {
        // Tarif 50.000, 2 unit, 1 hari = 100.000
        $this->assertEquals(100000.0, Penyewaan::hitungTotalBiaya(50000, 2, 1));

        // Tarif 25.000, 3 unit, 3 hari = 225.000
        $this->assertEquals(225000.0, Penyewaan::hitungTotalBiaya(25000, 3, 3));

        // Jumlah 0 menghasilkan 0
        $this->assertEquals(0.0, Penyewaan::hitungTotalBiaya(50000, 0, 3));
    }

    /**
     * Uji pemesanan sewa durasi 1 hari oleh Pengguna
     */
    public function test_pengguna_booking_durasi_satu_hari(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        $tglSewa = Carbon::tomorrow()->format('Y-m-d');
        $tglKembali = Carbon::tomorrow()->addDay()->format('Y-m-d'); // 1 hari

        $alat = Alat::where('kode_alat', 'ALT001')->first(); // Tarif: 50.000

        $response = $this->actingAs($user)->post(route('pengguna.pesanan.store'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'metode_pembayaran' => 'Transfer Bank',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('pengguna.dashboard'));

        $trx = Penyewaan::where('id_user', $user->id_user)
            ->latest('no_transaksi')
            ->first();

        $this->assertNotNull($trx);
        $this->assertEquals(1, $trx->durasi_hari);

        // Tarif 50.000 × 1 unit × 1 hari = 50.000
        $this->assertEquals(50000.0, $trx->total_biaya);

        // Data pembayaran awal juga tersimpan 50.000
        $this->assertEquals(50000.0, (float) $trx->pembayaran->total_biaya);
    }

    /**
     * Uji pemesanan sewa beberapa hari (misal 3 hari: tgl 10 s/d 13)
     */
    public function test_pengguna_booking_durasi_beberapa_hari(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        $tglSewa = Carbon::tomorrow()->format('Y-m-d');
        $tglKembali = Carbon::tomorrow()->addDays(3)->format('Y-m-d'); // 3 hari

        $alat = Alat::where('kode_alat', 'ALT002')->first(); // Tarif: 25.000, Stok: 4

        $response = $this->actingAs($user)->post(route('pengguna.pesanan.store'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 2,
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'metode_pembayaran' => 'QRIS',
        ]);

        $response->assertSessionHasNoErrors();

        $trx = Penyewaan::where('id_user', $user->id_user)
            ->latest('no_transaksi')
            ->first();

        $this->assertNotNull($trx);
        $this->assertEquals(3, $trx->durasi_hari);

        // Tarif 25.000 × 2 unit × 3 hari = 150.000
        $this->assertEquals(150000.0, $trx->total_biaya);
        $this->assertEquals(150000.0, (float) $trx->pembayaran->total_biaya);
    }

    /**
     * Uji transaksi dengan beberapa alat (multi-item)
     */
    public function test_transaksi_dengan_beberapa_alat(): void
    {
        $pelanggan = Pelanggan::first();
        $user = User::where('role', 'Pelanggan')->first();

        $noTrx = 'TRX-TEST-MULTI-001';
        $durasiHari = 4; // 4 hari
        $tglSewa = Carbon::tomorrow()->format('Y-m-d');
        $tglKembali = Carbon::tomorrow()->addDays($durasiHari)->format('Y-m-d');

        $trx = Penyewaan::create([
            'no_transaksi' => $noTrx,
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => $user->id_user,
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'status_penyewaan' => 'Menunggu Pembayaran',
        ]);

        // Item 1: ALT001 @ 50.000 x 2 unit x 4 hari = 400.000
        DetailPenyewaan::create([
            'no_transaksi' => $noTrx,
            'kode_alat' => 'ALT001',
            'jumlah' => 2,
            'tarif_sewa' => 50000.00,
        ]);

        // Item 2: ALT002 @ 25.000 x 3 unit x 4 hari = 300.000
        DetailPenyewaan::create([
            'no_transaksi' => $noTrx,
            'kode_alat' => 'ALT002',
            'jumlah' => 3,
            'tarif_sewa' => 25000.00,
        ]);

        Pembayaran::create([
            'no_pembayaran' => 'BYR-TEST-MULTI-001',
            'no_transaksi' => $noTrx,
            'total_biaya' => 700000.00,
            'jumlah_pembayaran' => 0,
            'metode_pembayaran' => 'Transfer Bank',
            'status_pembayaran' => 'Belum Dibayar',
        ]);

        $trx->load('detailPenyewaan');

        // Verifikasi durasi
        $this->assertEquals(4, $trx->durasi_hari);

        // Verifikasi subtotal setiap item
        $items = $trx->detailPenyewaan;
        $this->assertEquals(400000.0, $items[0]->subtotal_biaya);
        $this->assertEquals(300000.0, $items[1]->subtotal_biaya);

        // Verifikasi total tagihan transaksi = jumlah seluruh biaya alat (400.000 + 300.000 = 700.000)
        $this->assertEquals(700000.0, $trx->total_biaya);
    }

    /**
     * Uji validasi tanggal: tanggal kembali harus sesudah tanggal sewa
     */
    public function test_validasi_tanggal_kembali_harus_setelah_tanggal_sewa(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        $tglSewa = Carbon::tomorrow()->format('Y-m-d');

        // Kasus 1: Tanggal kembali sama dengan tanggal sewa (harus ditolak)
        $responseSama = $this->actingAs($user)->post(route('pengguna.pesanan.store'), [
            'kode_alat' => 'ALT001',
            'jumlah' => 1,
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglSewa,
            'metode_pembayaran' => 'Cash',
        ]);

        $responseSama->assertSessionHasErrors('tanggal_kembali');

        // Kasus 2: Tanggal kembali sebelum tanggal sewa (harus ditolak)
        $tglKembaliSebelumnya = Carbon::today()->format('Y-m-d');
        $responseSebelum = $this->actingAs($user)->post(route('pengguna.pesanan.store'), [
            'kode_alat' => 'ALT001',
            'jumlah' => 1,
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembaliSebelumnya,
            'metode_pembayaran' => 'Cash',
        ]);

        $responseSebelum->assertSessionHasErrors('tanggal_kembali');

        // Kasus 3: Tanggal sewa di masa lampau (harus ditolak)
        $tglKemarin = Carbon::yesterday()->format('Y-m-d');
        $responseKemarin = $this->actingAs($user)->post(route('pengguna.pesanan.store'), [
            'kode_alat' => 'ALT001',
            'jumlah' => 1,
            'tanggal_sewa' => $tglKemarin,
            'tanggal_kembali' => $tglSewa,
            'metode_pembayaran' => 'Cash',
        ]);

        $responseKemarin->assertSessionHasErrors('tanggal_sewa');
    }

    /**
     * Uji konsistensi nilai total di seluruh halaman terkait:
     * - Instruksi Pembayaran Pengguna (Transfer, QRIS, Cash)
     * - Detail Pembayaran Admin
     * - Riwayat Transaksi Pemilik
     */
    public function test_konsistensi_perhitungan_di_seluruh_halaman(): void
    {
        // Gunakan transaksi seeded TRX-20261015-001 (Durasi 2 hari, total 200.000)
        $trx = Penyewaan::where('no_transaksi', 'TRX-20261015-001')->firstOrFail();
        $pengguna = User::where('id_user', $trx->id_user)->firstOrFail();
        $admin = User::where('role', 'Admin')->firstOrFail();
        $pemilik = User::where('role', 'Pemilik')->firstOrFail();

        $formattedTotal = '200.000';

        // 1. Instruksi Transfer Bank Pengguna
        $resTransfer = $this->actingAs($pengguna)->get(route('pengguna.pembayaran.transfer', $trx->no_transaksi));
        $resTransfer->assertStatus(200);
        $resTransfer->assertSee("Rp {$formattedTotal}");

        // 2. Instruksi QRIS Pengguna
        $resQris = $this->actingAs($pengguna)->get(route('pengguna.pembayaran.qris', $trx->no_transaksi));
        $resQris->assertStatus(200);
        $resQris->assertSee("Rp {$formattedTotal}");

        // 3. Instruksi Cash Pengguna
        $resCash = $this->actingAs($pengguna)->get(route('pengguna.pembayaran.cash', $trx->no_transaksi));
        $resCash->assertStatus(200);
        $resCash->assertSee("Rp {$formattedTotal}");

        // 4. Detail Pembayaran / Pesanan Admin
        $resAdmin = $this->actingAs($admin)->get(route('admin.pembayaran.show', $trx->no_transaksi));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee("Rp {$formattedTotal}");

        // 5. Riwayat Transaksi Pemilik (Show)
        $resPemilikShow = $this->actingAs($pemilik)->get(route('pemilik.transaksi.show', $trx->no_transaksi));
        $resPemilikShow->assertStatus(200);
        $resPemilikShow->assertSee("Rp {$formattedTotal}");

        // 6. Riwayat Transaksi Pemilik (Index)
        $resPemilikIndex = $this->actingAs($pemilik)->get(route('pemilik.transaksi.index'));
        $resPemilikIndex->assertStatus(200);
        $resPemilikIndex->assertSee("Rp {$formattedTotal}");
    }

    /**
     * Uji pelestarian data historis:
     * Mengubah tarif master alat tidak mengubah total tagihan transaksi yang sudah tersimpan.
     */
    public function test_perubahan_tarif_master_tidak_mengubah_tagihan_transaksi_lama(): void
    {
        $trx = Penyewaan::where('no_transaksi', 'TRX-20261015-001')->firstOrFail();
        $initialTotal = $trx->total_biaya; // 200.000

        // Ubah tarif alat master ALT001 menjadi 2x lipat
        $alat = Alat::where('kode_alat', 'ALT001')->firstOrFail();
        $alat->tarif_sewa = 100000.00;
        $alat->save();

        // Refresh model transaksi
        $trxFresh = Penyewaan::where('no_transaksi', 'TRX-20261015-001')->firstOrFail();

        // Total biaya transaksi historis harus tetap 200.000 karena detail_penyewaan menyimpan snapshot tarif_sewa
        $this->assertEquals($initialTotal, $trxFresh->total_biaya);
    }

    /**
     * Uji pelestarian data transaksi legacy tanpa detail_penyewaan
     */
    public function test_transaksi_legacy_tanpa_detail_tetap_menampilkan_total_biaya_dari_pembayaran(): void
    {
        $pelanggan = Pelanggan::first();
        $user = User::where('role', 'Pelanggan')->first();

        $noTrx = 'TRX-LEGACY-999';
        $trx = Penyewaan::create([
            'no_transaksi' => $noTrx,
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => $user->id_user,
            'tanggal_sewa' => '2025-01-01',
            'tanggal_kembali' => '2025-01-04', // 3 hari
            'status_penyewaan' => 'Selesai',
        ]);

        // Tidak ada data di tabel detail_penyewaan untuk transaksi ini
        Pembayaran::create([
            'no_pembayaran' => 'BYR-LEGACY-999',
            'no_transaksi' => $noTrx,
            'total_biaya' => 350000.00,
            'jumlah_pembayaran' => 350000.00,
            'metode_pembayaran' => 'Cash',
            'status_pembayaran' => 'Lunas',
        ]);

        $this->assertEquals(3, $trx->durasi_hari);
        // Tetap menghasilkan 350.000 dari data pembayaran historis
        $this->assertEquals(350000.0, $trx->total_biaya);
    }

    /**
     * Uji Rule 7: 2 tenda @ Rp50.000/hari dan 3 sleeping bag @ Rp25.000/hari selama 3 hari menghasilkan total Rp525.000
     */
    public function test_checkout_formula_example_rule_7_tenda_dan_sleeping_bag(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($user->id_user);

        // Pastikan stok mencukupi untuk pengujian
        $tenda = Alat::where('kode_alat', 'ALT001')->first();
        $tenda->stok = 5;
        $tenda->tarif_sewa = 50000.00;
        $tenda->save();

        $sleepingBag = Alat::where('kode_alat', 'ALT002')->first();
        $sleepingBag->stok = 6;
        $sleepingBag->tarif_sewa = 25000.00;
        $sleepingBag->save();

        // 1. Uji rumus unit model murni
        $subtotalTenda = Penyewaan::hitungTotalBiaya(50000.00, 2, 3);
        $subtotalSleepingBag = Penyewaan::hitungTotalBiaya(25000.00, 3, 3);
        $this->assertEquals(300000.00, $subtotalTenda);
        $this->assertEquals(225000.00, $subtotalSleepingBag);
        $this->assertEquals(525000.00, $subtotalTenda + $subtotalSleepingBag);

        // 2. Uji alur checkout HTTP sebenarnya
        KeranjangController::add($user->id_user, 'ALT001', 2);
        KeranjangController::add($user->id_user, 'ALT002', 3);

        $tglSewa = Carbon::now()->addDays(5)->format('Y-m-d');
        $tglKembali = Carbon::now()->addDays(8)->format('Y-m-d'); // 3 hari durasi

        $response = $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'metode_pembayaran' => 'QRIS',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('pengguna.dashboard'));

        $trx = Penyewaan::with(['detailPenyewaan', 'pembayaran'])
            ->where('id_user', $user->id_user)
            ->latest('no_transaksi')
            ->first();

        $this->assertNotNull($trx);
        $this->assertEquals(3, $trx->durasi_hari);
        $this->assertCount(2, $trx->detailPenyewaan);

        // Detail 1: Tenda (2 unit @ 50.000 x 3 hari = 300.000)
        $detailTenda = $trx->detailPenyewaan->where('kode_alat', 'ALT001')->first();
        $this->assertEquals(2, $detailTenda->jumlah);
        $this->assertEquals(50000.00, (float) $detailTenda->tarif_sewa);
        $this->assertEquals(300000.00, $detailTenda->subtotal_biaya);

        // Detail 2: Sleeping Bag (3 unit @ 25.000 x 3 hari = 225.000)
        $detailSb = $trx->detailPenyewaan->where('kode_alat', 'ALT002')->first();
        $this->assertEquals(3, $detailSb->jumlah);
        $this->assertEquals(25000.00, (float) $detailSb->tarif_sewa);
        $this->assertEquals(225000.00, $detailSb->subtotal_biaya);

        // Total transaksi = 525.000
        $this->assertEquals(525000.00, $trx->total_biaya);
        $this->assertEquals(525000.00, (float) $trx->pembayaran->total_biaya);
    }

    /**
     * Uji Rule 8: Hitung ulang harga di backend dan abaikan data manipulasi dari browser
     */
    public function test_backend_recalculates_price_ignoring_browser_input(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($user->id_user);

        $alat = Alat::where('kode_alat', 'ALT001')->first();
        $alat->stok = 5;
        $alat->tarif_sewa = 50000.00;
        $alat->save();

        KeranjangController::add($user->id_user, 'ALT001', 1);

        $tglSewa = Carbon::now()->addDays(10)->format('Y-m-d');
        $tglKembali = Carbon::now()->addDays(12)->format('Y-m-d'); // 2 hari

        // Coba kirim data palsu total_biaya / tarif_sewa / subtotal dari browser
        $response = $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'metode_pembayaran' => 'Cash',
            'total_biaya' => 1000,
            'tarif_sewa' => 100,
            'subtotal' => 200,
        ]);

        $response->assertSessionHasNoErrors();

        $trx = Penyewaan::where('id_user', $user->id_user)->latest('no_transaksi')->first();
        $this->assertEquals(2, $trx->durasi_hari);
        // Backend harus tetap menghitung: 50.000 x 1 x 2 = 100.000
        $this->assertEquals(100000.00, $trx->total_biaya);
        $this->assertEquals(100000.00, (float) $trx->pembayaran->total_biaya);
    }

    /**
     * Uji Rule 11: Validasi ketersediaan alat berdasarkan periode sewa dan pencegahan overbooking pesanan bersamaan
     */
    public function test_validasi_ketersediaan_alat_berdasarkan_periode_sewa_dan_pesanan_bersamaan(): void
    {
        $user1 = User::where('username', 'pelanggan')->first();
        $user2 = User::where('username', 'budi')->first();
        $admin = User::where('role', 'Admin')->first();

        // Siapkan alat dengan stok terbatas (2 unit)
        $alat = Alat::where('kode_alat', 'ALT004')->first();
        $alat->stok = 2;
        $alat->save();

        $tglSewa = Carbon::now()->addDays(15)->format('Y-m-d');
        $tglKembali = Carbon::now()->addDays(18)->format('Y-m-d');

        // Pesanan 1: Memesan 2 unit ALT004 untuk periode tsb
        $res1 = $this->actingAs($user1)->post(route('pengguna.pesanan.store'), [
            'kode_alat' => 'ALT004',
            'jumlah' => 2,
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'metode_pembayaran' => 'Cash',
        ]);
        $res1->assertSessionHasNoErrors();

        $trx1 = Penyewaan::where('id_user', $user1->id_user)->latest('no_transaksi')->first();
        // Admin menyetujui pesanan 1 sehingga stok teralokasi (status: Menunggu Pembayaran)
        $this->actingAs($admin)->post(route('admin.pesanan.setujui', $trx1->no_transaksi));
        $this->assertEquals('Menunggu Pembayaran', $trx1->fresh()->status_penyewaan);

        // Pesanan 2: User lain mencoba memesan 1 unit pada periode yang beririsan (misal tgl 16 s/d 17)
        $tglSewaIrisan = Carbon::now()->addDays(16)->format('Y-m-d');
        $tglKembaliIrisan = Carbon::now()->addDays(17)->format('Y-m-d');

        $res2 = $this->actingAs($user2)->post(route('pengguna.pesanan.store'), [
            'kode_alat' => 'ALT004',
            'jumlah' => 1,
            'tanggal_sewa' => $tglSewaIrisan,
            'tanggal_kembali' => $tglKembaliIrisan,
            'metode_pembayaran' => 'Cash',
        ]);

        // Pesanan 2 harus ditolak karena kapasitas stok pada periode tersebut sudah penuh (tersedia 0 unit)
        $res2->assertSessionHasErrors('jumlah');

        // Pesanan 3: User mencoba memesan 2 unit pada periode yang TIDAK beririsan (misal tgl 19 s/d 21)
        $tglSewaBeda = Carbon::now()->addDays(19)->format('Y-m-d');
        $tglKembaliBeda = Carbon::now()->addDays(21)->format('Y-m-d');

        $res3 = $this->actingAs($user2)->post(route('pengguna.pesanan.store'), [
            'kode_alat' => 'ALT004',
            'jumlah' => 2,
            'tanggal_sewa' => $tglSewaBeda,
            'tanggal_kembali' => $tglKembaliBeda,
            'metode_pembayaran' => 'Cash',
        ]);

        // Pesanan 3 harus berhasil karena tidak beririsan
        $res3->assertSessionHasNoErrors();
        $trx3 = Penyewaan::where('id_user', $user2->id_user)->latest('no_transaksi')->first();
        $this->assertNotNull($trx3);
        $this->assertEquals(2, $trx3->detailPenyewaan->first()->jumlah);
    }
}

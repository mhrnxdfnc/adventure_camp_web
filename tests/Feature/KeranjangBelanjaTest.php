<?php

namespace Tests\Feature;

use App\Http\Controllers\KeranjangController;
use App\Models\Alat;
use App\Models\Pembayaran;
use App\Models\Penyewaan;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class KeranjangBelanjaTest extends TestCase
{
    /**
     * Pastikan halaman keranjang belanja kosong dapat diakses oleh Pengguna
     */
    public function test_pengguna_can_view_empty_cart_page(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($user->id_user);

        $response = $this->actingAs($user)->get(route('pengguna.keranjang.index'));

        $response->assertStatus(200);
        $response->assertSee('Keranjang Belanja Masih Kosong');
        $response->assertSee('Buka Katalog Alat Camping');
    }

    /**
     * Pastikan pengguna dapat menambahkan beberapa jenis alat berbeda sekaligus ke keranjang
     */
    public function test_pengguna_can_add_multiple_items_to_cart(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($user->id_user);

        $alat1 = Alat::where('kode_alat', 'ALT001')->first(); // Tenda Dome
        $alat1->stok = 5;
        $alat1->save();

        $alat2 = Alat::where('kode_alat', 'ALT002')->first(); // Sleeping Bag (stok: 4)

        // Tambahkan alat 1 (2 unit)
        $response1 = $this->actingAs($user)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => $alat1->kode_alat,
            'jumlah' => 2,
        ]);
        $response1->assertRedirect();
        $response1->assertSessionHas('success');

        // Tambahkan alat 2 (3 unit)
        $response2 = $this->actingAs($user)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => $alat2->kode_alat,
            'jumlah' => 3,
        ]);
        $response2->assertRedirect();
        $response2->assertSessionHas('success');

        // Verifikasi keranjang memiliki 2 jenis alat dengan total 5 unit
        $this->assertEquals(2, KeranjangController::countDistinct($user->id_user));
        $this->assertEquals(5, KeranjangController::count($user->id_user));

        // Verifikasi halaman keranjang menampilkan kedua alat
        $cartView = $this->actingAs($user)->get(route('pengguna.keranjang.index'));
        $cartView->assertStatus(200);
        $cartView->assertSee($alat1->nama_alat);
        $cartView->assertSee($alat2->nama_alat);
        $cartView->assertSee('5 Unit');
    }

    /**
     * Pastikan penambahan melebihi stok yang tersedia dicegah
     */
    public function test_pengguna_cannot_add_item_exceeding_available_stock(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($user->id_user);

        $alat = Alat::where('kode_alat', 'ALT002')->first(); // Stok: 4 unit

        // Coba tambahkan 10 unit (melebihi stok 4 unit)
        $response = $this->actingAs($user)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 10,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, KeranjangController::count($user->id_user));
    }

    /**
     * Pastikan alat yang tidak berkondisi Baik atau stok 0 tidak dapat dimasukkan ke keranjang
     */
    public function test_pengguna_cannot_add_damaged_or_out_of_stock_item(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($user->id_user);

        // Buat alat rusak untuk pengujian
        $alatRusak = Alat::create([
            'kode_alat' => 'ALTRSK',
            'nama_alat' => 'Tenda Robek',
            'kategori' => 'Tenda',
            'stok' => 2,
            'kondisi' => 'Rusak',
            'tarif_sewa' => 40000,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => $alatRusak->kode_alat,
            'jumlah' => 1,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, KeranjangController::count($user->id_user));
    }

    /**
     * Pastikan kuantitas alat di keranjang dapat diubah (tambah/kurangi)
     */
    public function test_pengguna_can_update_item_quantity_in_cart(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($user->id_user);

        $alat = Alat::where('kode_alat', 'ALT001')->first();
        $alat->stok = 5;
        $alat->save();

        KeranjangController::add($user->id_user, $alat->kode_alat, 1);

        // Ubah jumlah menjadi 3
        $response = $this->actingAs($user)->post(route('pengguna.keranjang.update'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 3,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(3, KeranjangController::count($user->id_user));

        // Ubah jumlah menjadi 0 harus menghapus item dari keranjang
        $responseRemove = $this->actingAs($user)->post(route('pengguna.keranjang.update'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 0,
        ]);

        $responseRemove->assertSessionHas('success');
        $this->assertEquals(0, KeranjangController::count($user->id_user));
    }

    /**
     * Pastikan pengguna dapat menghapus satu item spesifik dari keranjang
     */
    public function test_pengguna_can_remove_item_from_cart(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($user->id_user);

        KeranjangController::add($user->id_user, 'ALT001', 1);
        KeranjangController::add($user->id_user, 'ALT002', 2);

        $this->assertEquals(2, KeranjangController::countDistinct($user->id_user));

        // Hapus ALT001
        $response = $this->actingAs($user)->post(route('pengguna.keranjang.hapus'), [
            'kode_alat' => 'ALT001',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(1, KeranjangController::countDistinct($user->id_user));
        $this->assertEquals(2, KeranjangController::count($user->id_user));
    }

    /**
     * Pastikan pengguna dapat mengosongkan seluruh keranjang belanja
     */
    public function test_pengguna_can_clear_entire_cart(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($user->id_user);

        KeranjangController::add($user->id_user, 'ALT001', 1);
        KeranjangController::add($user->id_user, 'ALT002', 2);

        $response = $this->actingAs($user)->post(route('pengguna.keranjang.kosongkan'));
        $response->assertRedirect(route('pengguna.keranjang.index'));
        $response->assertSessionHas('info');

        $this->assertEquals(0, KeranjangController::count($user->id_user));
    }

    /**
     * Pastikan isi keranjang terpisah per pengguna dan tidak tercampur antar-pengguna
     */
    public function test_cart_is_isolated_between_different_users(): void
    {
        $userA = User::where('username', 'pelanggan')->first();
        $userB = User::where('username', 'admin')->first(); // atau user lain

        $alat1 = Alat::where('kode_alat', 'ALT001')->first();
        $alat1->stok = 5;
        $alat1->save();

        KeranjangController::clear($userA->id_user);
        KeranjangController::clear($userB->id_user);

        // User A memasukkan 2 unit ALT001
        KeranjangController::add($userA->id_user, 'ALT001', 2);

        // User B memasukkan 1 unit ALT002
        KeranjangController::add($userB->id_user, 'ALT002', 1);

        // Verifikasi keranjang User A hanya berisi ALT001 (2 unit)
        $this->assertEquals(2, KeranjangController::count($userA->id_user));
        $cartA = KeranjangController::getCart($userA->id_user);
        $this->assertArrayHasKey('ALT001', $cartA);
        $this->assertArrayNotHasKey('ALT002', $cartA);

        // Verifikasi keranjang User B hanya berisi ALT002 (1 unit)
        $this->assertEquals(1, KeranjangController::count($userB->id_user));
        $cartB = KeranjangController::getCart($userB->id_user);
        $this->assertArrayHasKey('ALT002', $cartB);
        $this->assertArrayNotHasKey('ALT001', $cartB);
    }

    /**
     * Pastikan checkout menghasilkan 1 transaksi penyewaan dengan beberapa detail alat dan perhitungan total sesuai rumus:
     * Tenda Dome: 2 × Rp50.000 × 3 hari = Rp300.000
     * Sleeping Bag: 3 × Rp25.000 × 3 hari = Rp225.000
     * Total tagihan = Rp525.000
     */
    public function test_checkout_multi_item_from_cart_creates_single_transaction_with_multiple_details(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($user->id_user);

        $alat1 = Alat::where('kode_alat', 'ALT001')->first();
        $alat1->stok = 5;
        $alat1->save();

        // Masukkan alat ke keranjang
        // ALT001: Tarif 50.000, jumlah 2
        // ALT002: Tarif 25.000, jumlah 3
        KeranjangController::add($user->id_user, 'ALT001', 2);
        KeranjangController::add($user->id_user, 'ALT002', 3);

        $tglSewa = Carbon::tomorrow()->format('Y-m-d');
        $tglKembali = Carbon::tomorrow()->addDays(3)->format('Y-m-d'); // 3 hari durasi

        // Kirim checkout sekaligus
        $response = $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'metode_pembayaran' => 'QRIS',
        ]);

        $response->assertRedirect(route('pengguna.dashboard'));
        $response->assertSessionHas('success');

        // 1. Verifikasi hanya 1 transaksi penyewaan dibuat
        $trx = Penyewaan::with(['detailPenyewaan.alat', 'pembayaran'])
            ->where('id_user', $user->id_user)
            ->latest('no_transaksi')
            ->first();

        $this->assertNotNull($trx);
        $this->assertEquals(3, $trx->durasi_hari);
        $this->assertEquals('Menunggu Konfirmasi', $trx->status_penyewaan);

        // 2. Verifikasi transaksi memiliki 2 baris detail penyewaan
        $this->assertCount(2, $trx->detailPenyewaan);

        $detail1 = $trx->detailPenyewaan->where('kode_alat', 'ALT001')->first();
        $this->assertNotNull($detail1);
        $this->assertEquals(2, $detail1->jumlah);
        $this->assertEquals(50000.0, (float) $detail1->tarif_sewa);
        // Subtotal = 50.000 x 2 x 3 hari = 300.000
        $this->assertEquals(300000.0, $detail1->subtotal_biaya);

        $detail2 = $trx->detailPenyewaan->where('kode_alat', 'ALT002')->first();
        $this->assertNotNull($detail2);
        $this->assertEquals(3, $detail2->jumlah);
        $this->assertEquals(25000.0, (float) $detail2->tarif_sewa);
        // Subtotal = 25.000 x 3 x 3 hari = 225.000
        $this->assertEquals(225000.0, $detail2->subtotal_biaya);

        // 3. Verifikasi total tagihan transaksi = 300.000 + 225.000 = 525.000
        $this->assertEquals(525000.0, $trx->total_biaya);

        // 4. Verifikasi pembayaran awal tersimpan dengan nilai 525.000 dan status Belum Dibayar
        $this->assertNotNull($trx->pembayaran);
        $this->assertEquals(525000.0, (float) $trx->pembayaran->total_biaya);
        $this->assertEquals('QRIS', $trx->pembayaran->metode_pembayaran);
        $this->assertEquals('Belum Dibayar', $trx->pembayaran->status_pembayaran);

        // 5. Verifikasi keranjang belanja pengguna telah dikosongkan secara otomatis setelah checkout
        $this->assertEquals(0, KeranjangController::count($user->id_user));
    }

    /**
     * Pastikan backend memvalidasi ketersediaan stok aktual saat checkout diajukan
     */
    public function test_checkout_backend_validates_insufficient_stock_at_submission_time(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($user->id_user);

        // Masukkan barang ke keranjang
        KeranjangController::add($user->id_user, 'ALT002', 3);

        // Simulasikan stok fisik di database berkurang menjadi hanya 1 unit sebelum checkout disubmit
        $alat = Alat::where('kode_alat', 'ALT002')->first();
        $stokAwal = $alat->stok;
        $alat->stok = 1;
        $alat->save();

        $tglSewa = Carbon::tomorrow()->format('Y-m-d');
        $tglKembali = Carbon::tomorrow()->addDay()->format('Y-m-d');

        $response = $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'metode_pembayaran' => 'Cash',
        ]);

        // Harus gagal dan menampilkan error stok tidak mencukupi
        $response->assertSessionHasErrors(['jumlah']);

        // Kembalikan stok fisik
        $alat->stok = $stokAwal;
        $alat->save();
    }

    /**
     * Pastikan validasi tanggal kembali harus setelah tanggal sewa
     */
    public function test_checkout_backend_validates_invalid_dates(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($user->id_user);

        KeranjangController::add($user->id_user, 'ALT001', 1);

        $tglSewa = Carbon::tomorrow()->format('Y-m-d');
        $tglKembaliSama = $tglSewa; // Tanggal kembali sama dengan tanggal sewa

        $response = $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembaliSama,
            'metode_pembayaran' => 'QRIS',
        ]);

        $response->assertSessionHasErrors(['tanggal_kembali']);
    }

    /**
     * Pastikan alur Admin (melihat pesanan multi-item, menyetujui, verifikasi bayar, serah terima) tetap terintegrasi sempurna
     */
    public function test_admin_can_view_and_process_multi_item_order(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        $admin = User::where('username', 'admin')->first();
        KeranjangController::clear($user->id_user);

        // Buat pesanan 2 alat (ALT001: 1 unit, ALT002: 1 unit) durasi 2 hari
        KeranjangController::add($user->id_user, 'ALT001', 1);
        KeranjangController::add($user->id_user, 'ALT002', 1);

        $tglSewa = Carbon::tomorrow()->format('Y-m-d');
        $tglKembali = Carbon::tomorrow()->addDays(2)->format('Y-m-d');

        $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'metode_pembayaran' => 'Transfer Bank',
        ]);

        $trx = Penyewaan::where('id_user', $user->id_user)->latest('no_transaksi')->first();

        // 1. Admin melihat pesanan di dashboard admin
        $adminDashboard = $this->actingAs($admin)->get(route('admin.dashboard'));
        $adminDashboard->assertStatus(200);
        $adminDashboard->assertSee($trx->no_transaksi);

        // 2. Admin menyetujui pesanan
        $responseSetujui = $this->actingAs($admin)->post(route('admin.pesanan.setujui', $trx->no_transaksi));
        $responseSetujui->assertSessionHas('success');

        $trx->refresh();
        $this->assertEquals('Menunggu Pembayaran', $trx->status_penyewaan);

        // 3. Admin verifikasi pembayaran lunas
        $totalTagihan = $trx->total_biaya; // (50.000x1x2) + (25.000x1x2) = 150.000
        $responseBayar = $this->actingAs($admin)->post(route('admin.pembayaran.konfirmasi', $trx->no_transaksi), [
            'jumlah_diterima' => $totalTagihan,
            'metode_pembayaran' => 'Transfer Bank',
        ]);
        $responseBayar->assertSessionHas('success');

        $trx->refresh();
        $this->assertEquals('Siap Diambil', $trx->status_penyewaan);
        $this->assertTrue($trx->pembayaran->isLunas());

        // 4. Admin memproses penyerahan alat (pengambilan)
        $stokAwalAlt1 = (int) Alat::where('kode_alat', 'ALT001')->value('stok');
        $stokAwalAlt2 = (int) Alat::where('kode_alat', 'ALT002')->value('stok');

        $responseAmbil = $this->actingAs($admin)->post(route('admin.operasional.proses-pengambilan', $trx->no_transaksi), [
            'tanggal_pengambilan' => Carbon::now()->format('Y-m-d'),
            'nama_penerima' => 'Pelanggan Camping',
            'items' => [
                'ALT001' => ['jumlah_diserahkan' => 1],
                'ALT002' => ['jumlah_diserahkan' => 1],
            ],
        ]);
        $responseAmbil->assertSessionHas('success');

        $trx->refresh();
        $this->assertEquals('Sedang Disewa', $trx->status_penyewaan);

        // Verifikasi stok fisik berkurang saat serah terima
        $this->assertEquals($stokAwalAlt1 - 1, (int) Alat::where('kode_alat', 'ALT001')->value('stok'));
        $this->assertEquals($stokAwalAlt2 - 1, (int) Alat::where('kode_alat', 'ALT002')->value('stok'));
    }
}

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

    /**
     * Uji lengkap halaman keranjang belanja:
     * - Menampilkan rincian alat (nama, kode, kategori, stok, tarif harian, jumlah unit, subtotal per hari, tombol hapus)
     * - Menampilkan total jenis alat, total unit, dan total sementara per hari
     * - Penambahan dan pengurangan unit memperbarui subtotal dan total sementara
     * - Perubahan stok di database memicu peringatan stok dan menolak/mencegah checkout sampai disesuaikan
     * - Menghapus barang memperbarui ringkasan
     */
    public function test_keranjang_belanja_unified_period_rules_and_stock_change_behavior(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($user->id_user);

        $alat1 = Alat::where('kode_alat', 'ALT001')->first();
        $alat1->stok = 10;
        $alat1->save();

        $alat2 = Alat::where('kode_alat', 'ALT002')->first();
        $alat2->stok = 10;
        $alat2->save();

        // 1. Tambah dua jenis barang: ALT001 (2 unit @ 50.000) dan ALT002 (3 unit @ 25.000)
        KeranjangController::add($user->id_user, 'ALT001', 2);
        KeranjangController::add($user->id_user, 'ALT002', 3);

        // 2. Buka halaman keranjang belanja
        $response = $this->actingAs($user)->get(route('pengguna.keranjang.index'));
        $response->assertStatus(200);

        // Verifikasi elemen tampilan
        $response->assertSee($alat1->nama_alat);
        $response->assertSee($alat1->kode_alat);
        $response->assertSee($alat1->kategori);
        $response->assertSee('Stok: 10 unit');
        $response->assertSee($alat2->nama_alat);
        $response->assertSee($alat2->kode_alat);

        // Verifikasi jumlah jenis alat dan total unit
        $response->assertSee('2 Jenis Alat');
        $response->assertSee('5 Unit');

        // Verifikasi subtotal per hari
        // ALT001: 2 x 50.000 = 100.000
        // ALT002: 3 x 25.000 = 75.000
        // Total sementara / hari = 175.000
        $response->assertSee('Rp 100.000');
        $response->assertSee('Rp 75.000');
        $response->assertSee('Total Sementara / Hari:');
        $response->assertSee('Rp 175.000');
        $response->assertSee('Lanjut ke Checkout');

        // Memastikan tidak ada teks teknis rumus membingungkan
        $response->assertDontSee('belum dikalikan durasi hari sewa');

        // 3. Tambah unit ALT001 (menjadi 4 unit)
        $this->actingAs($user)->post(route('pengguna.keranjang.update'), [
            'kode_alat' => 'ALT001',
            'jumlah' => 4,
        ]);
        $responseUpdate = $this->actingAs($user)->get(route('pengguna.keranjang.index'));
        // Total unit: 4 + 3 = 7
        // Total sementara: (4 x 50.000) + (3 x 25.000) = 200.000 + 75.000 = 275.000
        $responseUpdate->assertSee('7 Unit');
        $responseUpdate->assertSee('Rp 275.000');

        // 4. Kurangi unit ALT002 (menjadi 1 unit)
        $this->actingAs($user)->post(route('pengguna.keranjang.update'), [
            'kode_alat' => 'ALT002',
            'jumlah' => 1,
        ]);
        $responseKurang = $this->actingAs($user)->get(route('pengguna.keranjang.index'));
        // Total unit: 4 + 1 = 5
        // Total sementara: 200.000 + 25.000 = 225.000
        $responseKurang->assertSee('5 Unit');
        $responseKurang->assertSee('Rp 225.000');

        // 5. Uji skenario stok berkurang di database di bawah kuantitas keranjang
        // ALT001 di keranjang ada 4 unit, kita ubah stok fisik di DB menjadi 2 unit
        $alat1->stok = 2;
        $alat1->save();

        $responseStokKurang = $this->actingAs($user)->get(route('pengguna.keranjang.index'));
        $responseStokKurang->assertSee('Perhatian Ketersediaan Stok');
        $responseStokKurang->assertSee('Melebihi Stok! Tersedia: 2 unit');

        // Mencoba langsung checkout saat stok kurang harus ditolak dan dialihkan kembali
        $checkoutTry = $this->actingAs($user)->get(route('pengguna.checkout.index'));
        $checkoutTry->assertRedirect(route('pengguna.keranjang.index'));
        $checkoutTry->assertSessionHas('error');

        // 6. Sesuaikan jumlah ALT001 ke batas stok yang valid (2 unit)
        $this->actingAs($user)->post(route('pengguna.keranjang.update'), [
            'kode_alat' => 'ALT001',
            'jumlah' => 2,
        ]);
        $responseStokPas = $this->actingAs($user)->get(route('pengguna.keranjang.index'));
        $responseStokPas->assertDontSee('Perhatian Ketersediaan Stok');
        $responseStokPas->assertSee('id="btnLanjutCheckout"', false);

        // 7. Hapus salah satu barang (ALT002)
        $this->actingAs($user)->post(route('pengguna.keranjang.hapus'), [
            'kode_alat' => 'ALT002',
        ]);
        $responseHapus = $this->actingAs($user)->get(route('pengguna.keranjang.index'));
        $responseHapus->assertSee('1 Jenis Alat');
        $responseHapus->assertSee('2 Unit');
        $responseHapus->assertSee('Rp 100.000');
        $responseHapus->assertDontSee($alat2->nama_alat);

        // 8. Kosongkan keranjang
        $this->actingAs($user)->post(route('pengguna.keranjang.kosongkan'));
        $responseKosong = $this->actingAs($user)->get(route('pengguna.keranjang.index'));
        $responseKosong->assertSee('Keranjang Belanja Masih Kosong');
        $responseKosong->assertSee('Buka Katalog Alat Camping');

        // Kembalikan stok alat1
        $alat1->stok = 10;
        $alat1->save();
    }

    /**
     * Uji lengkap skenario halaman dan proses checkout:
     * - Tampilan checkout (nama, kode, tarif harian, jumlah unit, subtotal per hari, durasi, biaya final, total, metode pembayaran, tombol konfirmasi)
     * - Validasi tanggal kosong (tanggal_sewa / tanggal_kembali kosong)
     * - Validasi tanggal sama (tanggal_kembali == tanggal_sewa)
     * - Validasi tanggal terbalik (tanggal_kembali < tanggal_sewa)
     * - Validasi keranjang kosong
     * - Validasi stok berubah saat submission
     * - Periode valid dan perhitungan harga di backend
     * - Metode pembayaran (QRIS, Transfer Bank, Cash) dan penyimpanan pesanan
     */
    public function test_checkout_complete_validation_and_submission_scenarios(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($user->id_user);

        $alat1 = Alat::where('kode_alat', 'ALT001')->first();
        $alat1->stok = 10;
        $alat1->tarif_sewa = 50000;
        $alat1->save();

        $alat2 = Alat::where('kode_alat', 'ALT002')->first();
        $alat2->stok = 10;
        $alat2->tarif_sewa = 25000;
        $alat2->save();

        // 1. Uji keranjang kosong dilarang checkout
        $emptyCartCheckout = $this->actingAs($user)->get(route('pengguna.checkout.index'));
        $emptyCartCheckout->assertRedirect(route('pengguna.katalog.index'));
        $emptyCartCheckout->assertSessionHas('error');

        $emptyCartSubmit = $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => Carbon::tomorrow()->format('Y-m-d'),
            'tanggal_kembali' => Carbon::tomorrow()->addDays(2)->format('Y-m-d'),
            'metode_pembayaran' => 'Cash',
        ]);
        $emptyCartSubmit->assertSessionHasErrors(['items']);

        // 2. Masukkan 2 barang ke keranjang: ALT001 (2 unit) & ALT002 (3 unit)
        KeranjangController::add($user->id_user, 'ALT001', 2);
        KeranjangController::add($user->id_user, 'ALT002', 3);

        // 3. Tampilan Checkout: verifikasi seluruh elemen UI
        $checkoutView = $this->actingAs($user)->get(route('pengguna.checkout.index'));
        $checkoutView->assertStatus(200);

        // Ringkasan barang: nama, kode, tarif harian, jumlah unit, subtotal per hari
        $checkoutView->assertSee($alat1->nama_alat);
        $checkoutView->assertSee($alat1->kode_alat);
        $checkoutView->assertSee('2 unit');
        $checkoutView->assertSee('Rp 50.000');
        $checkoutView->assertSee('Subtotal / Hari: Rp 100.000');

        $checkoutView->assertSee($alat2->nama_alat);
        $checkoutView->assertSee($alat2->kode_alat);
        $checkoutView->assertSee('3 unit');
        $checkoutView->assertSee('Rp 25.000');
        $checkoutView->assertSee('Subtotal / Hari: Rp 75.000');

        // Form tanggal & durasi
        $checkoutView->assertSee('Tanggal Mulai Sewa');
        $checkoutView->assertSee('name="tanggal_sewa"', false);
        $checkoutView->assertSee('Tanggal Kembali');
        $checkoutView->assertSee('name="tanggal_kembali"', false);
        $checkoutView->assertSee('id="labelDurasiSewa"', false);
        $checkoutView->assertSee('id="labelGrandTotal"', false);

        // Pilihan metode pembayaran
        $checkoutView->assertSee('QRIS');
        $checkoutView->assertSee('Transfer Bank');
        $checkoutView->assertSee('Cash');

        // Tombol konfirmasi
        $checkoutView->assertSee('Konfirmasi & Lanjutkan Pesanan');

        // 4. Uji validasi tanggal kosong
        $resTglKosong = $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => '',
            'tanggal_kembali' => '',
            'metode_pembayaran' => 'QRIS',
        ]);
        $resTglKosong->assertSessionHasErrors(['tanggal_sewa', 'tanggal_kembali']);

        // 5. Uji validasi tanggal sama (tanggal_kembali == tanggal_sewa)
        $tglHariIni = Carbon::tomorrow()->format('Y-m-d');
        $resTglSama = $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglHariIni,
            'tanggal_kembali' => $tglHariIni,
            'metode_pembayaran' => 'QRIS',
        ]);
        $resTglSama->assertSessionHasErrors(['tanggal_kembali']);

        // 6. Uji validasi tanggal terbalik (tanggal_kembali < tanggal_sewa)
        $resTglTerbalik = $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => Carbon::tomorrow()->addDays(3)->format('Y-m-d'),
            'tanggal_kembali' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'metode_pembayaran' => 'QRIS',
        ]);
        $resTglTerbalik->assertSessionHasErrors(['tanggal_kembali']);

        // 7. Uji metode pembayaran tidak valid
        $resMetodeInvalid = $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => Carbon::tomorrow()->format('Y-m-d'),
            'tanggal_kembali' => Carbon::tomorrow()->addDays(2)->format('Y-m-d'),
            'metode_pembayaran' => 'Bitcoin',
        ]);
        $resMetodeInvalid->assertSessionHasErrors(['metode_pembayaran']);

        // 8. Uji stok berkurang di database di bawah kuantitas keranjang saat checkout
        $alat1->stok = 1; // keranjang meminta 2 unit
        $alat1->save();

        $resStokBerkurang = $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => Carbon::tomorrow()->format('Y-m-d'),
            'tanggal_kembali' => Carbon::tomorrow()->addDays(3)->format('Y-m-d'),
            'metode_pembayaran' => 'QRIS',
        ]);
        $resStokBerkurang->assertSessionHasErrors(['jumlah']);

        // Kembalikan stok alat1
        $alat1->stok = 10;
        $alat1->save();

        // 9. Uji periode valid dan penyimpanan pesanan
        // Periode: 3 hari (misal tgl mulai s/d 3 hari ke depan)
        // Perhitungan:
        // ALT001: 2 unit x Rp 50.000 x 3 hari = Rp 300.000
        // ALT002: 3 unit x Rp 25.000 x 3 hari = Rp 225.000
        // Grand Total: Rp 525.000
        $tglMulai = Carbon::now()->addDays(20)->format('Y-m-d');
        $tglSelesai = Carbon::now()->addDays(23)->format('Y-m-d'); // 3 hari durasi

        $resValid = $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglMulai,
            'tanggal_kembali' => $tglSelesai,
            'metode_pembayaran' => 'Transfer Bank',
        ]);
        $resValid->assertSessionHasNoErrors();
        $resValid->assertRedirect(route('pengguna.dashboard'));
        $resValid->assertSessionHas('success');

        // Verifikasi transaksi di database
        $trx = Penyewaan::where('id_user', $user->id_user)->latest('no_transaksi')->first();
        $this->assertNotNull($trx);
        $this->assertEquals(3, $trx->durasi_hari);
        $this->assertEquals($tglMulai, Carbon::parse($trx->tanggal_sewa)->format('Y-m-d'));
        $this->assertEquals($tglSelesai, Carbon::parse($trx->tanggal_kembali)->format('Y-m-d'));
        $this->assertEquals('Menunggu Konfirmasi', $trx->status_penyewaan);
        $this->assertEquals(525000.00, $trx->total_biaya);

        // Verifikasi detail penyewaan
        $this->assertCount(2, $trx->detailPenyewaan);
        $detail1 = $trx->detailPenyewaan->where('kode_alat', 'ALT001')->first();
        $this->assertEquals(2, $detail1->jumlah);
        $this->assertEquals(300000.00, $detail1->subtotal_biaya);

        $detail2 = $trx->detailPenyewaan->where('kode_alat', 'ALT002')->first();
        $this->assertEquals(3, $detail2->jumlah);
        $this->assertEquals(225000.00, $detail2->subtotal_biaya);

        // Verifikasi pembayaran
        $this->assertNotNull($trx->pembayaran);
        $this->assertEquals(525000.00, (float) $trx->pembayaran->total_biaya);
        $this->assertEquals('Transfer Bank', $trx->pembayaran->metode_pembayaran);
        $this->assertEquals('Belum Dibayar', $trx->pembayaran->status_pembayaran);

        // Verifikasi keranjang otomatis dikosongkan setelah pesanan berhasil disimpan
        $this->assertEquals(0, KeranjangController::count($user->id_user));
    }
}

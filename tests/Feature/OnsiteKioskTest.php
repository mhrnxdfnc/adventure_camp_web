<?php

namespace Tests\Feature;

use App\Http\Controllers\OnsiteKioskController;
use App\Models\Alat;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Penyewaan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class OnsiteKioskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        OnsiteKioskController::clearCart();
    }

    /**
     * 1. Halaman awal landing mode tablet dapat diakses tamu tanpa autentikasi
     */
    public function test_landing_page_can_be_accessed_without_authentication(): void
    {
        $response = $this->get(route('onsite.landing'));

        $response->assertStatus(200);
        $response->assertSee('Selamat Datang di Adventure Camp Rental');
        $response->assertSee('Mulai Pesanan');
        $response->assertSee('Mode Pemesanan Langsung di Toko');
    }

    /**
     * Alias /tablet mengarahkan ke halaman awal kiosk
     */
    public function test_tablet_alias_redirects_to_onsite_landing(): void
    {
        $response = $this->get('/tablet');

        $response->assertRedirect(route('onsite.landing'));
    }

    /**
     * 2. Tombol mulai pesanan mengosongkan keranjang sebelumnya dan mengarahkan ke katalog
     */
    public function test_mulai_pesanan_clears_cart_and_redirects_to_catalog(): void
    {
        // Beri data awal di sesi keranjang
        Session::put('onsite_kiosk_cart', [
            'ALT001' => ['kode_alat' => 'ALT001', 'jumlah' => 1],
        ]);

        $this->assertEquals(1, OnsiteKioskController::countCart());

        $response = $this->post(route('onsite.mulai'));

        $response->assertRedirect(route('onsite.katalog'));
        $this->assertEquals(0, OnsiteKioskController::countCart());
    }

    /**
     * 3. Katalog on-site menampilkan peralatan siap sewa, stok, tarif harian, dan stepper awal 0
     */
    public function test_katalog_displays_equipment_with_stock_and_zero_initial_stepper(): void
    {
        $alatSiap = Alat::where('kode_alat', 'ALT001')->first();
        $alatRusak = Alat::where('kode_alat', 'ALT006')->first();

        $response = $this->get(route('onsite.katalog'));

        $response->assertStatus(200);
        $response->assertSee($alatSiap->nama_alat);
        $response->assertSee('Tersedia: '.$alatSiap->stok.' unit');
        $response->assertSee('value="0"', false); // stepper kuantitas mulai dari 0
        $response->assertSee('+ Keranjang');

        // Pastikan alat rusak tidak ditampilkan
        $response->assertDontSee($alatRusak->nama_alat);
    }

    /**
     * 4. Pelanggan tablet dapat memasukkan beberapa jenis alat berbeda ke keranjang on-site
     */
    public function test_tablet_user_can_add_multiple_items_to_onsite_cart(): void
    {
        $alat1 = Alat::where('kode_alat', 'ALT002')->first(); // Sleeping bag (stok: 4)
        $alat2 = Alat::where('kode_alat', 'ALT003')->first(); // Carrier (stok: 5)

        // Tambah alat 1 (2 unit)
        $res1 = $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat1->kode_alat,
            'jumlah' => 2,
        ]);
        $res1->assertSessionHas('success');

        // Tambah alat 2 (3 unit)
        $res2 = $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat2->kode_alat,
            'jumlah' => 3,
        ]);
        $res2->assertSessionHas('success');

        $this->assertEquals(5, OnsiteKioskController::countCart());
        $cart = OnsiteKioskController::getCart();
        $this->assertArrayHasKey('ALT002', $cart);
        $this->assertArrayHasKey('ALT003', $cart);
        $this->assertEquals(2, $cart['ALT002']['jumlah']);
        $this->assertEquals(3, $cart['ALT003']['jumlah']);
    }

    /**
     * 5. Menambah ke keranjang menolak kuantitas 0 atau negatif
     */
    public function test_add_to_cart_rejects_zero_or_negative_quantity(): void
    {
        $response = $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => 'ALT001',
            'jumlah' => 0,
        ]);

        $response->assertSessionHasErrors(['jumlah']);
        $this->assertEquals(0, OnsiteKioskController::countCart());
    }

    /**
     * 6. Menambah ke keranjang menolak kuantitas melebihi stok yang ada
     */
    public function test_add_to_cart_rejects_quantity_exceeding_stock(): void
    {
        $alat = Alat::where('kode_alat', 'ALT001')->first(); // Stok: 1
        $response = $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 5,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, OnsiteKioskController::countCart());
    }

    /**
     * 7. Barang di keranjang TIDAK mengurangi stok fisik inventaris secara prematur
     */
    public function test_cart_items_do_not_permanently_decrement_inventory_stock(): void
    {
        $alat = Alat::where('kode_alat', 'ALT002')->first();
        $stokAwal = (int) $alat->stok;

        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 2,
        ]);

        $this->assertEquals(2, OnsiteKioskController::countCart());
        $this->assertEquals($stokAwal, (int) $alat->fresh()->stok, 'Stok di database tidak boleh berkurang hanya karena barang masuk keranjang');
    }

    /**
     * 8. Pelanggan dapat memperbarui dan menghapus item dari keranjang on-site
     */
    public function test_customer_can_update_and_remove_items_in_onsite_cart(): void
    {
        $alat = Alat::where('kode_alat', 'ALT002')->first();

        // Tambah 2 unit
        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 2,
        ]);

        // Update jadi 3 unit
        $this->post(route('onsite.keranjang.update'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 3,
        ]);
        $this->assertEquals(3, OnsiteKioskController::countCart());

        // Hapus item
        $this->post(route('onsite.keranjang.hapus'), [
            'kode_alat' => $alat->kode_alat,
        ]);
        $this->assertEquals(0, OnsiteKioskController::countCart());
    }

    /**
     * 9. Form checkout wajib memvalidasi identitas pelanggan: nama, telepon, dan alamat
     */
    public function test_checkout_validates_mandatory_guest_identity_fields(): void
    {
        $alat = Alat::where('kode_alat', 'ALT002')->first();
        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
        ]);

        // Kirim tanpa nama, telepon, dan alamat
        $response = $this->post(route('onsite.checkout.proses'), [
            'nama_lengkap' => '',
            'nomor_telepon' => '',
            'alamat' => '',
            'tanggal_sewa' => Carbon::now()->addDay()->toDateString(),
            'tanggal_kembali' => Carbon::now()->addDays(3)->toDateString(),
            'metode_pembayaran' => 'Cash',
        ]);

        $response->assertSessionHasErrors(['nama_lengkap', 'nomor_telepon', 'alamat']);
    }

    /**
     * 10. Form checkout memvalidasi tanggal kembali harus setelah tanggal sewa
     */
    public function test_checkout_validates_rental_dates(): void
    {
        $alat = Alat::where('kode_alat', 'ALT002')->first();
        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
        ]);

        $sewa = Carbon::now()->addDay()->toDateString();

        $response = $this->post(route('onsite.checkout.proses'), [
            'nama_lengkap' => 'Pelanggan Tamu',
            'nomor_telepon' => '081234567899',
            'alamat' => 'Jl. Ijen No. 1, Malang',
            'tanggal_sewa' => $sewa,
            'tanggal_kembali' => $sewa, // tanggal sama tidak diperbolehkan
            'metode_pembayaran' => 'Cash',
        ]);

        $response->assertSessionHasErrors(['tanggal_kembali']);
    }

    /**
     * 11. Checkout on-site berhasil membuat satu transaksi dengan detail barang, sumber on-site, dan id_user null
     */
    public function test_successful_onsite_checkout_creates_transaction_with_onsite_source_and_null_user(): void
    {
        $alat1 = Alat::where('kode_alat', 'ALT002')->first(); // 25.000 / hari
        $alat2 = Alat::where('kode_alat', 'ALT003')->first(); // 40.000 / hari

        // Masukkan 2 unit ALT002 dan 1 unit ALT003
        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat1->kode_alat,
            'jumlah' => 2,
        ]);
        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat2->kode_alat,
            'jumlah' => 1,
        ]);

        $tanggalSewa = Carbon::now()->addDays(2)->toDateString();
        $tanggalKembali = Carbon::now()->addDays(5)->toDateString(); // 3 hari durasi

        // Perhitungan: (25.000 * 2 * 3) + (40.000 * 1 * 3) = 150.000 + 120.000 = 270.000
        $response = $this->post(route('onsite.checkout.proses'), [
            'nama_lengkap' => 'Dian Permatasari',
            'nomor_telepon' => '081299887766',
            'alamat' => 'Jl. Danau Toba No. 9, Malang',
            'tanggal_sewa' => $tanggalSewa,
            'tanggal_kembali' => $tanggalKembali,
            'metode_pembayaran' => 'Cash',
        ]);

        $trx = Penyewaan::where('sumber_pesanan', 'on-site')->latest('created_at')->first();
        $this->assertNotNull($trx, 'Transaksi on-site harus tersimpan di database');

        $response->assertRedirect(route('onsite.sukses', $trx->no_transaksi));

        // Verifikasi struktur transaksi
        $this->assertNull($trx->id_user, 'Transaksi on-site tidak boleh dikaitkan dengan id_user');
        $this->assertEquals('on-site', $trx->sumber_pesanan);
        $this->assertTrue($trx->isOnsite());
        $this->assertFalse($trx->isOnline());
        $this->assertEquals('Menunggu Konfirmasi', $trx->status_penyewaan);
        $this->assertEquals(3, $trx->durasi_hari);

        // Verifikasi pelanggan toko
        $pelanggan = Pelanggan::find($trx->id_pelanggan);
        $this->assertNotNull($pelanggan);
        $this->assertEquals('Dian Permatasari', $pelanggan->nama_pelanggan);
        $this->assertEquals('081299887766', $pelanggan->nomor_telepon);

        // Verifikasi detail penyewaan (multi-item)
        $this->assertCount(2, $trx->detailPenyewaan);
        $detail1 = $trx->detailPenyewaan->where('kode_alat', 'ALT002')->first();
        $detail2 = $trx->detailPenyewaan->where('kode_alat', 'ALT003')->first();
        $this->assertEquals(2, $detail1->jumlah);
        $this->assertEquals(1, $detail2->jumlah);

        // Verifikasi total biaya dihitung ulang dengan benar di backend
        $this->assertEquals(270000.0, (float) $trx->total_biaya);

        // Verifikasi pembayaran dibuat
        $pembayaran = Pembayaran::where('no_transaksi', $trx->no_transaksi)->first();
        $this->assertNotNull($pembayaran);
        $this->assertEquals('Cash', $pembayaran->metode_pembayaran);
        $this->assertEquals(270000.0, (float) $pembayaran->total_biaya);
        $this->assertEquals('Belum Dibayar', $pembayaran->status_pembayaran);

        // Verifikasi keranjang on-site telah dikosongkan setelah checkout
        $this->assertEquals(0, OnsiteKioskController::countCart());

        // Verifikasi halaman sukses menampilkan nomor transaksi
        $resSukses = $this->get(route('onsite.sukses', $trx->no_transaksi));
        $resSukses->assertStatus(200);
        $resSukses->assertSee($trx->no_transaksi);
        $resSukses->assertSee('Menunggu Konfirmasi', false);
        $resSukses->assertSee('Pemeriksaan Admin Kasir', false);
        $resSukses->assertSee('Dian Permatasari');
        $resSukses->assertSee('Rp 270.000');
    }

    /**
     * 12. Pesanan on-site masuk ke antrean Admin yang sama dengan indikator On-Site Kasir
     */
    public function test_onsite_order_integrates_into_admin_queue_with_onsite_badge(): void
    {
        $pelanggan = Pelanggan::create([
            'nama_pelanggan' => 'Eko Prasetyo',
            'nomor_telepon' => '085566778899',
            'alamat' => 'Jl. Semeru No. 20, Malang',
            'created_at' => now(),
        ]);

        $trxOnsite = Penyewaan::create([
            'no_transaksi' => 'TRX-ONSITE-TEST01',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => null,
            'tanggal_sewa' => Carbon::now()->addDays(2)->toDateString(),
            'tanggal_kembali' => Carbon::now()->addDays(4)->toDateString(),
            'status_penyewaan' => 'Menunggu Konfirmasi',
            'sumber_pesanan' => 'on-site',
        ]);

        $admin = User::where('role', 'Admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('TRX-ONSITE-TEST01');
        $response->assertSee('On-Site Kasir');
        $response->assertSee('Eko Prasetyo');
    }

    /**
     * 13. Mode tablet tidak dapat membuka dashboard Admin atau Pemilik
     */
    public function test_tablet_mode_cannot_access_admin_or_pemilik_dashboards(): void
    {
        // Sebagai pengunjung tablet (tamu tanpa login)
        $resAdmin = $this->get(route('admin.dashboard'));
        $resAdmin->assertRedirect(route('login'));

        $resPemilik = $this->get(route('pemilik.dashboard'));
        $resPemilik->assertRedirect(route('login'));

        // Layout tablet tidak menampilkan tautan ke dashboard Admin atau Pemilik
        $resLanding = $this->get(route('onsite.landing'));
        $resLanding->assertDontSee('/admin/dashboard');
        $resLanding->assertDontSee('/pemilik/dashboard');
    }

    /**
     * 14. Memastikan tidak ada role baru yang dibuat; mempertahankan Admin, Pemilik, dan Pengguna/Pelanggan
     */
    public function test_no_new_roles_created_for_onsite_customers(): void
    {
        $allowedRoles = ['Admin', 'Pemilik', 'Pelanggan', 'Pengguna'];
        $distinctRoles = User::distinct()->pluck('role')->toArray();

        foreach ($distinctRoles as $role) {
            $this->assertContains($role, $allowedRoles, "Role '{$role}' tidak diperbolehkan. Hanya role utama yang boleh ada.");
        }
    }
}

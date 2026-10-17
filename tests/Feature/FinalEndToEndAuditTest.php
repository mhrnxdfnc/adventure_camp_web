<?php

namespace Tests\Feature;

use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\OnsiteKioskController;
use App\Models\Alat;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Pengambilan;
use App\Models\Pengembalian;
use App\Models\Penyewaan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinalEndToEndAuditTest extends TestCase
{
    /**
     * Skenario 1: Pelanggan online login, memilih beberapa jenis alat, mengatur jumlah unit,
     * memilih periode sewa bersama, checkout, dan melihat pesanan miliknya.
     */
    public function test_scenario_1_online_customer_multi_item_order_flow(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($user->id_user);

        // Ambil alat dan pastikan stok siap sewa
        $alat1 = Alat::where('kode_alat', 'ALT001')->first(); // Tenda Dome (50.000)
        $alat1->update(['stok' => 5, 'kondisi' => 'Baik', 'tarif_sewa' => 50000]);
        $alat2 = Alat::where('kode_alat', 'ALT002')->first(); // Sleeping Bag (25.000)
        $alat2->update(['stok' => 5, 'kondisi' => 'Baik', 'tarif_sewa' => 25000]);

        // Tambah ke keranjang
        $this->actingAs($user)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => $alat1->kode_alat,
            'jumlah' => 2,
        ])->assertSessionHas('success');

        $this->actingAs($user)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => $alat2->kode_alat,
            'jumlah' => 3,
        ])->assertSessionHas('success');

        $tglSewa = Carbon::tomorrow()->toDateString();
        $tglKembali = Carbon::tomorrow()->addDays(3)->toDateString(); // 3 hari durasi

        // Checkout multi-barang dengan 1 periode bersama
        $checkoutRes = $this->actingAs($user)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'metode_pembayaran' => 'Transfer Bank',
        ]);

        $checkoutRes->assertRedirect(route('pengguna.dashboard'));
        $checkoutRes->assertSessionHas('success');

        // Verifikasi transaksi di database
        $trx = Penyewaan::where('id_user', $user->id_user)
            ->where('sumber_pesanan', 'online')
            ->latest('no_transaksi')
            ->first();

        $this->assertNotNull($trx);
        $this->assertEquals(3, $trx->durasi_hari);
        // Perhitungan: (2 * 50.000 * 3) + (3 * 25.000 * 3) = 300.000 + 225.000 = 525.000
        $this->assertEquals(525000.00, (float) $trx->total_biaya);
        $this->assertEquals('Menunggu Konfirmasi', $trx->status_penyewaan);
        $this->assertCount(2, $trx->detailPenyewaan);

        // Verifikasi pelanggan melihat pesanannya di dashboard
        $dashRes = $this->actingAs($user)->get(route('pengguna.dashboard'));
        $dashRes->assertStatus(200);
        $dashRes->assertSee($trx->no_transaksi);
        $dashRes->assertSee('Rp 525.000');
    }

    /**
     * Skenario 2: Pelanggan on-site membuka halaman awal tablet, memulai pesanan,
     * memilih beberapa alat, mengisi identitas, dan mengirim pesanan tanpa mewarisi sesi pelanggan sebelumnya.
     */
    public function test_scenario_2_onsite_tablet_kiosk_isolated_order_flow(): void
    {
        OnsiteKioskController::clearCart();

        // 1. Pelanggan membuka halaman awal kios tablet
        $welcomeRes = $this->get(route('onsite.landing'));
        $welcomeRes->assertStatus(200);
        $welcomeRes->assertSee('Selamat Datang di Adventure Camp Rental');
        $welcomeRes->assertSee('Mulai Pesanan');

        // 2. Masuk katalog kios
        $catalogRes = $this->get(route('onsite.katalog'));
        $catalogRes->assertStatus(200);
        $catalogRes->assertSee('Katalog Perlengkapan Outdoor');

        // 3. Tambahkan beberapa alat ke keranjang kios
        $alat1 = Alat::where('kode_alat', 'ALT001')->first();
        $alat1->update(['stok' => 5, 'kondisi' => 'Baik', 'tarif_sewa' => 50000]);
        $alat2 = Alat::where('kode_alat', 'ALT002')->first();
        $alat2->update(['stok' => 5, 'kondisi' => 'Baik', 'tarif_sewa' => 25000]);

        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat1->kode_alat,
            'jumlah' => 1,
        ])->assertSessionHas('success');

        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat2->kode_alat,
            'jumlah' => 2,
        ])->assertSessionHas('success');

        // 4. Masuk ke checkout kios
        $checkoutView = $this->get(route('onsite.checkout'));
        $checkoutView->assertStatus(200);
        $checkoutView->assertSee('Checkout Pemesanan On-Site');

        // 5. Submit pesanan dengan identitas pelanggan baru
        $tglSewa = Carbon::now()->addDay()->toDateString();
        $tglKembali = Carbon::now()->addDays(3)->toDateString(); // 2 hari durasi

        $submitRes = $this->post(route('onsite.checkout.proses'), [
            'nama_lengkap' => 'Bambang Supriyanto',
            'nomor_telepon' => '087811223344',
            'alamat' => 'Jl. Ijen No. 12, Malang',
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'metode_pembayaran' => 'Cash',
        ]);

        $submitRes->assertRedirect();
        $this->assertGuest(); // Sesi tidak login akun apapun

        $trxOnsite = Penyewaan::where('sumber_pesanan', 'on-site')->latest('no_transaksi')->first();
        $this->assertNotNull($trxOnsite);
        $this->assertNull($trxOnsite->id_user);
        $this->assertEquals('Menunggu Konfirmasi', $trxOnsite->status_penyewaan);
        // Durasi 2 hari: (1 * 50.000 * 2) + (2 * 25.000 * 2) = 100.000 + 100.000 = 200.000
        $this->assertEquals(200000.00, (float) $trxOnsite->total_biaya);

        // Keranjang kios bersih setelah checkout
        $this->assertEquals(0, OnsiteKioskController::countCart());
    }

    /**
     * Skenario 3: Admin menerima pesanan dari kedua sumber (Online dan On-site),
     * memeriksa ketersediaan, lalu menyetujui atau menolak pesanan.
     */
    public function test_scenario_3_admin_receives_and_processes_both_order_sources(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Buat pesanan Online
        $trxOnline = Penyewaan::create([
            'no_transaksi' => 'TRX-AUDIT-ONL',
            'id_pelanggan' => 1,
            'id_user' => 3,
            'tanggal_sewa' => Carbon::now()->addDays(5)->toDateString(),
            'tanggal_kembali' => Carbon::now()->addDays(7)->toDateString(),
            'status_penyewaan' => 'Menunggu Konfirmasi',
            'sumber_pesanan' => 'online',
        ]);
        $trxOnline->detailPenyewaan()->create([
            'kode_alat' => 'ALT001',
            'jumlah' => 1,
            'tarif_sewa' => 50000,
        ]);
        Pembayaran::create([
            'no_pembayaran' => 'PAY-AUDIT-ONL',
            'no_transaksi' => $trxOnline->no_transaksi,
            'total_biaya' => 100000,
            'jumlah_pembayaran' => 0,
            'metode_pembayaran' => 'Transfer Bank',
            'tanggal_pembayaran' => now(),
            'status_pembayaran' => 'Belum Dibayar',
        ]);

        // 2. Buat pesanan On-site
        $trxOnsite = Penyewaan::create([
            'no_transaksi' => 'TRX-AUDIT-ONS',
            'id_pelanggan' => 2,
            'id_user' => null,
            'tanggal_sewa' => Carbon::now()->addDays(5)->toDateString(),
            'tanggal_kembali' => Carbon::now()->addDays(7)->toDateString(),
            'status_penyewaan' => 'Menunggu Konfirmasi',
            'sumber_pesanan' => 'on-site',
        ]);
        $trxOnsite->detailPenyewaan()->create([
            'kode_alat' => 'ALT002',
            'jumlah' => 1,
            'tarif_sewa' => 25000,
        ]);
        Pembayaran::create([
            'no_pembayaran' => 'PAY-AUDIT-ONS',
            'no_transaksi' => $trxOnsite->no_transaksi,
            'total_biaya' => 50000,
            'jumlah_pembayaran' => 0,
            'metode_pembayaran' => 'Cash',
            'tanggal_pembayaran' => now(),
            'status_pembayaran' => 'Belum Dibayar',
        ]);

        // Dashboard/Queue Admin menampilkan kedua pesanan dengan label sumber yang sesuai
        $queueRes = $this->actingAs($admin)->get(route('admin.dashboard'));
        $queueRes->assertStatus(200);
        $queueRes->assertSee('TRX-AUDIT-ONL');
        $queueRes->assertSee('TRX-AUDIT-ONS');
        $queueRes->assertSee('Online');
        $queueRes->assertSee('On-site');

        // Admin menyetujui pesanan Online
        $approveOnline = $this->actingAs($admin)->post(route('admin.pesanan.setujui', $trxOnline->no_transaksi));
        $approveOnline->assertSessionHas('success');
        $this->assertEquals('Menunggu Pembayaran', $trxOnline->fresh()->status_penyewaan);

        // Admin menolak pesanan On-site dengan alasan
        $rejectOnsite = $this->actingAs($admin)->post(route('admin.pesanan.tolak', $trxOnsite->no_transaksi), [
            'alasan_penolakan' => 'Alat sedang dalam masa kalibrasi berkala.',
        ]);
        $rejectOnsite->assertSessionHas('success');
        $this->assertEquals('Ditolak', $trxOnsite->fresh()->status_penyewaan);
    }

    /**
     * Skenario 4: Pelanggan menerima instruksi pembayaran yang sesuai dan
     * Admin mengonfirmasi pembayaran setelah benar-benar diverifikasi.
     */
    public function test_scenario_4_payment_instruction_and_admin_verification(): void
    {
        $admin = User::where('username', 'admin')->first();
        $user = User::where('username', 'pelanggan')->first();

        // Pesanan yang disetujui menunggu pembayaran
        $trx = Penyewaan::create([
            'no_transaksi' => 'TRX-PAY-VERIFY',
            'id_pelanggan' => 1,
            'id_user' => $user->id_user,
            'tanggal_sewa' => Carbon::now()->addDays(2)->toDateString(),
            'tanggal_kembali' => Carbon::now()->addDays(4)->toDateString(),
            'status_penyewaan' => 'Menunggu Pembayaran',
            'sumber_pesanan' => 'online',
        ]);
        $trx->detailPenyewaan()->create([
            'kode_alat' => 'ALT001',
            'jumlah' => 1,
            'tarif_sewa' => 50000,
        ]);
        $bayar = Pembayaran::create([
            'no_pembayaran' => 'PAY-PAY-VERIFY',
            'no_transaksi' => $trx->no_transaksi,
            'total_biaya' => 100000,
            'jumlah_pembayaran' => 0,
            'metode_pembayaran' => 'Transfer Bank',
            'tanggal_pembayaran' => now(),
            'status_pembayaran' => 'Belum Dibayar',
        ]);

        // Pelanggan melihat instruksi transfer
        $instruksiRes = $this->actingAs($user)->get(route('pengguna.pembayaran.transfer', $trx->no_transaksi));
        $instruksiRes->assertStatus(200);
        $instruksiRes->assertSee('Instruksi Pembayaran Transfer Bank');
        $instruksiRes->assertSee('100.000');

        // Pelanggan mengunggah bukti pembayaran
        Storage::fake('private');
        $bukti = UploadedFile::fake()->create('bukti_bayar.jpg', 300, 'image/jpeg');

        $uploadRes = $this->actingAs($user)->post(route('pengguna.pembayaran.upload-bukti', $trx->no_transaksi), [
            'bukti_transfer' => $bukti,
        ]);
        $uploadRes->assertSessionHas('success');
        $this->assertEquals('Menunggu Verifikasi', $bayar->fresh()->status_pembayaran);

        // Admin memverifikasi pembayaran menjadi Lunas
        $confirmRes = $this->actingAs($admin)->post(route('admin.pembayaran.konfirmasi', $trx->no_transaksi), [
            'jumlah_diterima' => 100000,
            'metode_pembayaran' => 'Transfer Bank',
        ]);
        $confirmRes->assertSessionHas('success');

        $this->assertEquals('Lunas', $bayar->fresh()->status_pembayaran);
        $this->assertEquals('Siap Diambil', $trx->fresh()->status_penyewaan);
    }

    /**
     * Skenario 5: Admin memproses pengambilan dan pengembalian alat serta mencatat kondisi aktual per unit.
     */
    public function test_scenario_5_handover_return_and_unit_condition_recording(): void
    {
        $admin = User::where('username', 'admin')->first();

        $trx = Penyewaan::create([
            'no_transaksi' => 'TRX-OPS-AUDIT',
            'id_pelanggan' => 1,
            'id_user' => 3,
            'tanggal_sewa' => Carbon::now()->subDays(3)->toDateString(),
            'tanggal_kembali' => Carbon::now()->subDays(1)->toDateString(),
            'status_penyewaan' => 'Siap Diambil',
            'sumber_pesanan' => 'online',
        ]);
        $trx->detailPenyewaan()->create([
            'kode_alat' => 'ALT001',
            'jumlah' => 2,
            'tarif_sewa' => 50000,
        ]);
        Pembayaran::create([
            'no_pembayaran' => 'PAY-OPS-AUDIT',
            'no_transaksi' => $trx->no_transaksi,
            'total_biaya' => 100000,
            'jumlah_pembayaran' => 100000,
            'metode_pembayaran' => 'Cash',
            'tanggal_pembayaran' => now(),
            'status_pembayaran' => 'Lunas',
            'waktu_konfirmasi' => now(),
            'id_admin' => $admin->id_user,
        ]);

        // 1. Proses Pengambilan (Handover)
        $handoverRes = $this->actingAs($admin)->post(route('admin.operasional.proses-pengambilan', $trx->no_transaksi), [
            'tanggal_pengambilan' => Carbon::now()->subDays(3)->toDateString(),
            'nama_penerima' => 'Andi Pratama',
            'items' => [
                'ALT001' => ['jumlah_diserahkan' => 2],
            ],
        ]);
        $handoverRes->assertRedirect(route('admin.operasional.pengambilan'));
        $this->assertEquals('Sedang Disewa', $trx->fresh()->status_penyewaan);

        // 2. Proses Pengembalian dengan kondisi: 1 Baik, 1 Perlu Perbaikan/Rusak dan denda keterlambatan
        $returnRes = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $trx->no_transaksi), [
            'tanggal_pengembalian_aktual' => Carbon::now()->toDateString(),
            'items' => [
                'ALT001' => ['baik' => 1, 'rusak' => 1],
            ],
            'nominal_denda' => 50000,
            'alasan_denda' => 'Terlambat 1 hari dan pasak tenda bengkok',
        ]);
        $returnRes->assertRedirect(route('admin.operasional.pengembalian'));
        $this->assertEquals('Selesai', $trx->fresh()->status_penyewaan);

        // Verifikasi data pengembalian tercatat
        $kembali = Pengembalian::where('no_transaksi', $trx->no_transaksi)->first();
        $this->assertNotNull($kembali);
        $this->assertEquals(50000.00, (float) $kembali->denda->nominal_denda);
    }

    /**
     * Skenario 6: Inventaris dan dashboard diperbarui secara konsisten tanpa stok ganda
     * atau jumlah alat rusak yang keliru.
     */
    public function test_scenario_6_inventory_and_dashboard_condition_metrics_consistency(): void
    {
        $ringkasan = Alat::getRingkasanInventaris();

        $this->assertArrayHasKey('total_stok_fisik', $ringkasan);
        $this->assertArrayHasKey('stok_siap_sewa', $ringkasan);
        $this->assertArrayHasKey('stok_sedang_disewa', $ringkasan);
        $this->assertArrayHasKey('stok_perlu_perbaikan', $ringkasan);
        $this->assertArrayHasKey('stok_rusak', $ringkasan);

        // Seluruh komponen stok harus terjumlahkan dengan tepat ke total stok fisik
        $this->assertEquals(
            $ringkasan['total_stok_fisik'],
            $ringkasan['stok_siap_sewa'] + $ringkasan['stok_sedang_disewa'] + $ringkasan['stok_perlu_perbaikan'] + $ringkasan['stok_rusak']
        );
    }

    /**
     * Skenario 7: Pemilik dapat melihat laporan penjualan, kondisi alat, dan riwayat pesanan tanpa mengubah data.
     */
    public function test_scenario_7_pemilik_read_only_dashboard_and_reporting(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        // 1. Akses dashboard
        $response = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Strictly Read-Only');
        $response->assertSee('Penjualan Hari Ini');
        $response->assertSee('Pendapatan Hari Ini');
        $response->assertSee('Riwayat Pesanan Pelanggan');

        // 2. Akses riwayat pesanan
        $trxRes = $this->actingAs($pemilik)->get(route('pemilik.transaksi.index'));
        $trxRes->assertStatus(200);

        // 3. Pemilik dilarang melakukan aksi perubahan data (403)
        $this->actingAs($pemilik)->post('/admin/alat', ['nama_alat' => 'Tenda Hacker'])->assertStatus(403);
        $this->actingAs($pemilik)->post('/admin/pembayaran/PAY-001/konfirmasi', [])->assertStatus(403);
        $this->actingAs($pemilik)->post(route('pemilik.test-action'))->assertStatus(403);
    }

    /**
     * Skenario 8: Manajemen Pengguna hanya dapat diakses Admin.
     */
    public function test_scenario_8_user_management_strictly_admin_only(): void
    {
        $admin = User::where('username', 'admin')->first();
        $pemilik = User::where('username', 'pemilik')->first();
        $pengguna = User::where('username', 'pelanggan')->first();

        // Admin dapat membuka manajemen pengguna
        $this->actingAs($admin)->get(route('admin.users.index'))->assertStatus(200);

        // Pemilik diblokir (403)
        $this->actingAs($pemilik)->get(route('admin.users.index'))->assertStatus(403);

        // Pengguna diblokir (403)
        $this->actingAs($pengguna)->get(route('admin.users.index'))->assertStatus(403);

        // Tamu dialihkan ke login
        auth()->logout();
        $this->get(route('admin.users.index'))->assertRedirect('/login');
    }

    /**
     * Skenario 9: Logout berfungsi untuk seluruh role.
     */
    public function test_scenario_9_logout_functions_for_all_roles(): void
    {
        // 1. Logout Admin
        $admin = User::where('username', 'admin')->first();
        $this->actingAs($admin)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();

        // 2. Logout Pemilik
        $pemilik = User::where('username', 'pemilik')->first();
        $this->actingAs($pemilik)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();

        // 3. Logout Pengguna
        $pengguna = User::where('username', 'pelanggan')->first();
        $this->actingAs($pengguna)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Skenario 10: Kegagalan koneksi database ditangani dengan aman dan tidak menampilkan kredensial.
     */
    public function test_scenario_10_database_failure_handled_gracefully_without_credentials_leak(): void
    {
        Route::get('/audit-test-db-failure', function () {
            throw new QueryException(
                'mysql',
                'SELECT * FROM pembayaran',
                [],
                new \Exception('SQLSTATE[HY000] [2002] Connection refused: db_pass=super_secret_production_password_xyz', 2002)
            );
        });

        $response = $this->get('/audit-test-db-failure');

        $response->assertStatus(503);
        $response->assertSee('ERR_DB_CONN');
        $response->assertSee('Layanan Database Tidak Tersedia');
        $response->assertDontSee('super_secret_production_password_xyz');
        $response->assertDontSee('SQLSTATE');
    }
}

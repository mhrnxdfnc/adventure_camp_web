<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Denda;
use App\Models\DetailPenyewaan;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Pengambilan;
use App\Models\Pengembalian;
use App\Models\Penyewaan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdventureCampIntegrationTest extends TestCase
{
    /*
    |--------------------------------------------------------------------------
    | ALUR PENGGUNA (End-to-End Customer Flow)
    |--------------------------------------------------------------------------
    */

    /**
     * 1. Registrasi atau login Pengguna
     */
    public function test_user_flow_registration_and_login(): void
    {
        // Registrasi pengguna baru
        $response = $this->post('/register', [
            'nama_pelanggan' => 'Citra Lestari',
            'alamat' => 'Jl. Danau Toba No. 15, Malang',
            'nomor_telepon' => '081299887766',
            'username' => 'citra_outdoor',
            'password' => 'Outdoor2026!',
            'password_confirmation' => 'Outdoor2026!',
        ]);

        $response->assertRedirect(route('pengguna.dashboard'));
        $this->assertAuthenticated();

        $user = User::where('username', 'citra_outdoor')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Pengguna', $user->role);
        $this->assertNotNull($user->id_pelanggan);

        // Logout
        $this->post(route('logout'));
        $this->assertGuest();

        // Login kembali
        $loginRes = $this->post('/login', [
            'username' => 'citra_outdoor',
            'password' => 'Outdoor2026!',
            'role' => 'Pengguna',
        ]);

        $loginRes->assertRedirect(route('pengguna.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * 2. Melihat katalog dan stok alat siap sewa
     */
    public function test_user_flow_view_catalog_and_stock(): void
    {
        $user = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($user)->get(route('pengguna.katalog.index'));

        $response->assertStatus(200);
        $response->assertSee('Katalog Alat Camping');
        $response->assertSee('ALT001');
        $response->assertSee('Tenda Dome 4 Orang');
        $response->assertSee('Stok:');

        // Buka detail alat
        $detailRes = $this->actingAs($user)->get(route('pengguna.katalog.show', 'ALT001'));
        $detailRes->assertStatus(200);
        $detailRes->assertSee('Tenda Dome 4 Orang');
        $detailRes->assertSee('Lanjutkan Pesanan');
    }

    /**
     * 3, 4, 5, 6, 7. Memilih alat, jumlah, periode sewa, ringkasan biaya, metode pembayaran, mengirim pesanan, dan status Menunggu Konfirmasi
     */
    public function test_user_flow_submit_order_and_view_pending_confirmation(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        $alat = Alat::where('kode_alat', 'ALT001')->first();

        $sewaDate = Carbon::tomorrow()->format('Y-m-d');
        $kembaliDate = Carbon::tomorrow()->addDays(2)->format('Y-m-d'); // 2 hari

        // Mengirim pesanan (Checkout)
        $response = $this->actingAs($user)->post(route('pengguna.pesanan.store'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
            'tanggal_sewa' => $sewaDate,
            'tanggal_kembali' => $kembaliDate,
            'metode_pembayaran' => 'Transfer Bank',
        ]);

        $response->assertRedirect(route('pengguna.dashboard'));
        $response->assertSessionHas('success');

        // Verifikasi database transaksi penyewaan
        $penyewaan = Penyewaan::where('id_user', $user->id_user)
            ->where('status_penyewaan', 'Menunggu Konfirmasi')
            ->latest('no_transaksi')
            ->first();

        $this->assertNotNull($penyewaan);
        $this->assertEquals(2, $penyewaan->durasi_hari);
        $this->assertEquals(100000.00, $penyewaan->total_biaya); // 50.000 x 1 x 2 hari

        // Detail penyewaan tersimpan
        $detail = DetailPenyewaan::where('no_transaksi', $penyewaan->no_transaksi)->first();
        $this->assertNotNull($detail);
        $this->assertEquals('ALT001', $detail->kode_alat);
        $this->assertEquals(1, $detail->jumlah);
        $this->assertEquals(50000.00, (float) $detail->tarif_sewa);

        // Data pembayaran awal dibuat dengan status Belum Dibayar
        $pembayaran = Pembayaran::where('no_transaksi', $penyewaan->no_transaksi)->first();
        $this->assertNotNull($pembayaran);
        $this->assertEquals('Transfer Bank', $pembayaran->metode_pembayaran);
        $this->assertEquals('Belum Dibayar', $pembayaran->status_pembayaran);

        // Dashboard pengguna menampilkan status Menunggu Konfirmasi
        $dashRes = $this->actingAs($user)->get(route('pengguna.dashboard'));
        $dashRes->assertStatus(200);
        $dashRes->assertSee($penyewaan->no_transaksi);
        $dashRes->assertSee('Menunggu Konfirmasi');
    }

    /**
     * 8, 9, 10. Melihat persetujuan Admin, instruksi pembayaran (QRIS/Transfer/Cash), dan status pembayaran terbaru
     */
    public function test_user_flow_view_approval_and_payment_instructions(): void
    {
        $admin = User::where('username', 'admin')->first();
        $user = User::where('username', 'pelanggan')->first();

        // 1. Pesanan TRX-20261015-002 masih 'Menunggu Konfirmasi'
        $penyewaan = Penyewaan::where('no_transaksi', 'TRX-20261015-002')->first();
        $this->assertEquals('Menunggu Konfirmasi', $penyewaan->status_penyewaan);

        // Sebelum disetujui, instruksi pembayaran terproteksi (403 Forbidden)
        $forbiddenRes = $this->actingAs($user)->get(route('pengguna.pembayaran.transfer', $penyewaan->no_transaksi));
        $forbiddenRes->assertStatus(403);

        // 2. Admin menyetujui pesanan
        $this->actingAs($admin)->post(route('admin.pesanan.setujui', $penyewaan->no_transaksi));
        $penyewaan->refresh();
        $this->assertEquals('Menunggu Pembayaran', $penyewaan->status_penyewaan);

        // 3. Setelah disetujui, Pengguna dapat membuka instruksi pembayaran
        $transferRes = $this->actingAs($user)->get(route('pengguna.pembayaran.transfer', $penyewaan->no_transaksi));
        $transferRes->assertStatus(200);
        $transferRes->assertSee('Instruksi Pembayaran Transfer Bank');
        $transferRes->assertSee($penyewaan->no_transaksi);

        // 4. Pengguna mengunggah bukti transfer
        Storage::fake('private');
        $file = UploadedFile::fake()->create('bukti_transfer.jpg', 500, 'image/jpeg');

        $uploadRes = $this->actingAs($user)->post(route('pengguna.pembayaran.upload-bukti', $penyewaan->no_transaksi), [
            'bukti_transfer' => $file,
        ]);
        $uploadRes->assertSessionHas('success');

        // Status pembayaran berubah menjadi Menunggu Verifikasi
        $pembayaran = Pembayaran::where('no_transaksi', $penyewaan->no_transaksi)->first();
        $this->assertEquals('Menunggu Verifikasi', $pembayaran->status_pembayaran);

        // 5. Dashboard Pengguna menampilkan status terbaru
        $dashRes = $this->actingAs($user)->get(route('pengguna.dashboard'));
        $dashRes->assertStatus(200);
        $dashRes->assertSee('Menunggu Verifikasi');
    }

    /**
     * 11, 12. Melihat informasi pengambilan, pengembalian, denda, dan riwayat pesanan sendiri
     */
    public function test_user_flow_view_handover_return_fines_and_own_history(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        $admin = User::where('username', 'admin')->first();

        // Siapkan transaksi yang telah dikembalikan beserta denda
        $trx = Penyewaan::where('no_transaksi', 'TRX-20261015-001')->first();
        $trx->status_penyewaan = 'Sedang Disewa';
        $trx->save();

        Pengambilan::create([
            'no_transaksi' => $trx->no_transaksi,
            'tanggal_pengambilan' => '2026-10-16',
            'id_admin' => $admin->id_user,
            'nama_penerima' => 'Andi Pratama',
            'catatan' => 'Semua pasak lengkap',
        ]);

        $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $trx->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-20',
            'items' => [
                'ALT001' => ['baik' => 1, 'rusak' => 0],
                'ALT002' => ['baik' => 2, 'rusak' => 0],
            ],
            'nominal_denda' => 75000,
            'alasan_denda' => 'Terlambat 2 hari dari jadwal pengembalian',
            'catatan' => 'Denda dibayar tunai',
        ]);

        // Dashboard pengguna menampilkan detail lengkap
        $response = $this->actingAs($user)->get(route('pengguna.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('TRX-20261015-001');
        $response->assertSee('Andi Pratama');
        $response->assertSee('75.000');
        $response->assertSee('Terlambat 2 hari');
        $response->assertSee('Selesai');
    }

    /*
    |--------------------------------------------------------------------------
    | ALUR ADMIN (Admin Operational Flow)
    |--------------------------------------------------------------------------
    */

    /**
     * Alur Admin: Melihat pesanan masuk, periksa stok, setujui/tolak, verifikasi bayar, serah terima, kembali, denda, inventaris, log
     */
    public function test_admin_flow_complete_operational_cycle(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Melihat pesanan masuk pada dashboard admin
        $dashRes = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dashRes->assertStatus(200);
        $dashRes->assertSee('Antrean Pesanan Menunggu Konfirmasi');

        // 2. Admin menolak pesanan tidak layak dengan alasan wajib
        $trxTolak = Penyewaan::create([
            'no_transaksi' => 'TRX-ADMIN-TOLAK',
            'id_pelanggan' => 1,
            'id_user' => 3,
            'tanggal_sewa' => '2026-10-25',
            'tanggal_kembali' => '2026-10-27',
            'status_penyewaan' => 'Menunggu Konfirmasi',
        ]);

        $tolakRes = $this->actingAs($admin)->post(route('admin.pesanan.tolak', $trxTolak->no_transaksi), [
            'alasan_penolakan' => 'Jadwal peminjaman bertabrakan dengan maintenance gudang.',
        ]);
        $tolakRes->assertSessionHas('success');
        $this->assertEquals('Ditolak', $trxTolak->fresh()->status_penyewaan);

        // 3. Admin memverifikasi pembayaran menjadi Lunas
        $trxBayar = Penyewaan::where('no_transaksi', 'TRX-20261015-001')->first();
        $this->actingAs($admin)->post(route('admin.pembayaran.konfirmasi', $trxBayar->no_transaksi), [
            'jumlah_diterima' => 200000,
            'metode_pembayaran' => 'Cash',
        ]);
        $this->assertEquals('Siap Diambil', $trxBayar->fresh()->status_penyewaan);

        // 4. Admin memproses penyerahan alat (Pengambilan)
        $penyerahanRes = $this->actingAs($admin)->post(route('admin.operasional.proses-pengambilan', $trxBayar->no_transaksi), [
            'tanggal_pengambilan' => '2026-10-16',
            'nama_penerima' => 'Andi Pratama',
            'items' => [
                'ALT001' => ['jumlah_diserahkan' => 1],
                'ALT002' => ['jumlah_diserahkan' => 2],
            ],
        ]);
        $penyerahanRes->assertRedirect(route('admin.operasional.pengambilan'));
        $this->assertEquals('Sedang Disewa', $trxBayar->fresh()->status_penyewaan);

        // 5. Admin mencatat pengembalian alat dan denda
        $pengembalianRes = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $trxBayar->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-19',
            'items' => [
                'ALT001' => ['baik' => 1, 'rusak' => 0],
                'ALT002' => ['baik' => 1, 'rusak' => 1], // 1 unit rusak
            ],
            'nominal_denda' => 50000,
            'alasan_denda' => '1 unit sleeping bag terbakar puntung rokok',
        ]);
        $pengembalianRes->assertRedirect(route('admin.operasional.pengembalian'));
        $this->assertEquals('Selesai', $trxBayar->fresh()->status_penyewaan);

        // 6. Admin melihat inventaris & stok yang terisolasi dari alat rusak
        $invRes = $this->actingAs($admin)->get(route('admin.inventaris.index'));
        $invRes->assertStatus(200);
        $invRes->assertSee('Pemantauan Inventaris');

        // 7. Admin melihat audit trail log aktivitas
        $logRes = $this->actingAs($admin)->get(route('admin.logs.index'));
        $logRes->assertStatus(200);
        $logRes->assertSee('Audit Trail');
        $logRes->assertSee('Pencatatan Denda');
    }

    /*
    |--------------------------------------------------------------------------
    | ALUR PEMILIK (Owner Read-Only Flow)
    |--------------------------------------------------------------------------
    */

    /**
     * Alur Pemilik: Melihat penjualan hari ini & bulan berjalan, grafik, riwayat pelanggan, alat rusak, dan strictly read-only
     */
    public function test_owner_flow_comprehensive(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        // 1. Dashboard Pemilik menampilkan metrik finansial riil
        $response = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Dashboard Pemilik');
        $response->assertSee('Strictly Read-Only');
        $response->assertSee('Penjualan Hari Ini');
        $response->assertSee('Penjualan Bulan Ini');
        $response->assertSee('Laporan Alat Rusak');

        // 2. Pemilik melihat riwayat pesanan pelanggan
        $trxRes = $this->actingAs($pemilik)->get(route('pemilik.transaksi.index'));
        $trxRes->assertStatus(200);
        $trxRes->assertSee('Riwayat Pesanan Pelanggan');
        $trxRes->assertSee('TRX-20261015-001');

        // 3. Pemilik membuka detail rincian transaksi
        $detailRes = $this->actingAs($pemilik)->get(route('pemilik.transaksi.show', 'TRX-20261015-001'));
        $detailRes->assertStatus(200);
        $detailRes->assertSee('Detail Transaksi Pelanggan');
        $detailRes->assertSee('TRX-20261015-001');

        // 4. Pemilik dicegah mengubah data (Middleware pemilik.readonly memblokir aksi POST/PUT/DELETE)
        $mutateRes = $this->actingAs($pemilik)->post(route('pemilik.test-action'));
        $mutateRes->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | PENGUJIAN KEAMANAN & INTEGRITAS (Security & Integrity Verification)
    |--------------------------------------------------------------------------
    */

    /**
     * Tamu dialihkan ke login saat mengakses rute terproteksi
     */
    public function test_security_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect('/login');
        $this->get(route('pemilik.dashboard'))->assertRedirect('/login');
        $this->get(route('pengguna.dashboard'))->assertRedirect('/login');
    }

    /**
     * Akses URL tanpa izin dibatasi sesuai role
     */
    public function test_security_unauthorized_url_access_blocked(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        $pemilik = User::where('username', 'pemilik')->first();

        // Pengguna tidak dapat mengakses rute Admin
        $this->actingAs($pengguna)->get(route('admin.dashboard'))->assertStatus(403);
        $this->actingAs($pengguna)->get(route('admin.pembayaran.index'))->assertStatus(403);
        $this->actingAs($pengguna)->get(route('admin.logs.index'))->assertStatus(403);

        // Pengguna tidak dapat mengakses rute Pemilik
        $this->actingAs($pengguna)->get(route('pemilik.dashboard'))->assertStatus(403);

        // Pemilik tidak dapat mengakses rute Admin
        $this->actingAs($pemilik)->get(route('admin.pembayaran.index'))->assertStatus(403);
        $this->actingAs($pemilik)->get(route('admin.logs.index'))->assertStatus(403);
    }

    /**
     * Pencegahan IDOR: Pengguna tidak dapat melihat transaksi atau bukti pembayaran milik orang lain
     */
    public function test_security_idor_prevented_between_users(): void
    {
        // User 3 (pelanggan Andi) memiliki TRX-20261015-001
        // User 4 (budi) adalah pelanggan lain
        $userBudi = User::where('username', 'budi')->first();

        // Budi mencoba membuka instruksi QRIS transaksi milik Andi
        $response = $this->actingAs($userBudi)->get(route('pengguna.pembayaran.qris', 'TRX-20261015-001'));
        $response->assertStatus(403);

        // Budi mencoba mengunggah bukti ke transaksi milik Andi
        Storage::fake('private');
        $file = UploadedFile::fake()->create('bukti.jpg', 500, 'image/jpeg');
        $uploadRes = $this->actingAs($userBudi)->post(route('pengguna.pembayaran.upload-bukti', 'TRX-20261015-001'), [
            'bukti_transfer' => $file,
        ]);
        $uploadRes->assertStatus(403);
    }

    /**
     * Manipulasi harga melalui frontend dicegah: Tarif dihitung mutlak dari database
     */
    public function test_security_prevent_frontend_price_tampering(): void
    {
        $user = User::where('username', 'pelanggan')->first();

        // Pengguna mencoba menyisipkan tarif palsu (Rp 1 per hari) via request
        $response = $this->actingAs($user)->post(route('pengguna.pesanan.store'), [
            'kode_alat' => 'ALT001',
            'jumlah' => 1,
            'tanggal_sewa' => Carbon::tomorrow()->format('Y-m-d'),
            'tanggal_kembali' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'metode_pembayaran' => 'Cash',
            'tarif_sewa' => 1.00, // Harga palsu!
            'total_biaya' => 1.00, // Total palsu!
        ]);

        $response->assertRedirect(route('pengguna.dashboard'));

        $trx = Penyewaan::where('id_user', $user->id_user)->latest('no_transaksi')->first();
        $this->assertNotNull($trx);

        // Sistem mengabaikan input harga frontend dan menggunakan tarif resmi database (Rp 50.000)
        $detail = DetailPenyewaan::where('no_transaksi', $trx->no_transaksi)->first();
        $this->assertEquals(50000.00, (float) $detail->tarif_sewa);
        $this->assertEquals(50000.00, (float) $trx->total_biaya);
    }

    /**
     * Jumlah pesanan yang tidak valid (0 atau negatif) ditolak
     */
    public function test_security_invalid_order_quantities_rejected(): void
    {
        $user = User::where('username', 'pelanggan')->first();

        $res1 = $this->actingAs($user)->post(route('pengguna.pesanan.store'), [
            'kode_alat' => 'ALT001',
            'jumlah' => 0,
            'tanggal_sewa' => Carbon::tomorrow()->format('Y-m-d'),
            'tanggal_kembali' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'metode_pembayaran' => 'Cash',
        ]);
        $res1->assertSessionHasErrors('jumlah');

        $res2 = $this->actingAs($user)->post(route('pengguna.pesanan.store'), [
            'kode_alat' => 'ALT001',
            'jumlah' => -5,
            'tanggal_sewa' => Carbon::tomorrow()->format('Y-m-d'),
            'tanggal_kembali' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'metode_pembayaran' => 'Cash',
        ]);
        $res2->assertSessionHasErrors('jumlah');
    }

    /**
     * Dua pesanan bersaing atas stok yang sama: Persetujuan dicegah jika stok tidak mencukupi
     */
    public function test_security_competing_orders_for_same_stock(): void
    {
        $admin = User::where('username', 'admin')->first();
        $user = User::where('username', 'pelanggan')->first();

        $alat = Alat::where('kode_alat', 'ALT001')->first();
        $alat->stok = 1;
        $alat->save();

        // Pesanan 1 meminta 1 unit ALT001
        $trx1 = Penyewaan::create([
            'no_transaksi' => 'TRX-COMPETE-001',
            'id_pelanggan' => 1,
            'id_user' => $user->id_user,
            'tanggal_sewa' => Carbon::tomorrow()->format('Y-m-d'),
            'tanggal_kembali' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'status_penyewaan' => 'Menunggu Konfirmasi',
        ]);
        DetailPenyewaan::create([
            'no_transaksi' => $trx1->no_transaksi,
            'kode_alat' => 'ALT001',
            'jumlah' => 1,
            'tarif_sewa' => 50000,
        ]);

        // Pesanan 2 juga meminta 2 unit ALT001 (melebihi stok 1)
        $trx2 = Penyewaan::create([
            'no_transaksi' => 'TRX-COMPETE-002',
            'id_pelanggan' => 2,
            'id_user' => 4,
            'tanggal_sewa' => Carbon::tomorrow()->format('Y-m-d'),
            'tanggal_kembali' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'status_penyewaan' => 'Menunggu Konfirmasi',
        ]);
        DetailPenyewaan::create([
            'no_transaksi' => $trx2->no_transaksi,
            'kode_alat' => 'ALT001',
            'jumlah' => 2, // Melebihi stok!
            'tarif_sewa' => 50000,
        ]);

        // Pesanan 1 disetujui
        $res1 = $this->actingAs($admin)->post(route('admin.pesanan.setujui', $trx1->no_transaksi));
        $res1->assertSessionHas('success');
        $this->assertEquals('Menunggu Pembayaran', $trx1->fresh()->status_penyewaan);

        // Pesanan 2 ditolak persetujuannya karena stok tidak mencukupi
        $res2 = $this->actingAs($admin)->post(route('admin.pesanan.setujui', $trx2->no_transaksi));
        $res2->assertSessionHas('error');
        $this->assertEquals('Menunggu Konfirmasi', $trx2->fresh()->status_penyewaan);
    }

    /**
     * Konfirmasi pembayaran dua kali dicegah
     */
    public function test_security_prevent_duplicate_payment_confirmation(): void
    {
        $admin = User::where('username', 'admin')->first();
        $trx = Penyewaan::where('no_transaksi', 'TRX-20261015-001')->first();

        // Konfirmasi pertama: Berhasil
        $res1 = $this->actingAs($admin)->post(route('admin.pembayaran.konfirmasi', $trx->no_transaksi), [
            'jumlah_diterima' => 200000,
            'metode_pembayaran' => 'Cash',
        ]);
        $res1->assertRedirect(route('admin.pembayaran.index'));

        // Konfirmasi kedua: Ditolak karena sudah Lunas
        $res2 = $this->actingAs($admin)->post(route('admin.pembayaran.konfirmasi', $trx->no_transaksi), [
            'jumlah_diterima' => 200000,
            'metode_pembayaran' => 'Cash',
        ]);
        $res2->assertSessionHas('error');
    }

    /**
     * Pesanan ditolak tidak dapat dibayar atau diserahkan
     */
    public function test_security_rejected_order_cannot_be_paid_or_handed_over(): void
    {
        $admin = User::where('username', 'admin')->first();
        $user = User::where('username', 'pelanggan')->first();

        $trx = Penyewaan::create([
            'no_transaksi' => 'TRX-REJECT-CHECK',
            'id_pelanggan' => 1,
            'id_user' => $user->id_user,
            'tanggal_sewa' => '2026-10-25',
            'tanggal_kembali' => '2026-10-27',
            'status_penyewaan' => 'Ditolak',
        ]);

        // Pengguna tidak dapat membuka instruksi pembayaran pesanan yang ditolak
        $this->actingAs($user)->get(route('pengguna.pembayaran.transfer', $trx->no_transaksi))->assertStatus(403);

        // Admin tidak dapat memproses penyerahan alat
        $resHandover = $this->actingAs($admin)->post(route('admin.operasional.proses-pengambilan', $trx->no_transaksi), [
            'tanggal_pengambilan' => '2026-10-25',
            'nama_penerima' => 'Andi',
            'items' => [],
        ]);
        $resHandover->assertSessionHas('error');
    }

    /**
     * Alat rusak tidak boleh disewakan atau disetujui
     */
    public function test_security_damaged_stock_handling(): void
    {
        $admin = User::where('username', 'admin')->first();
        $user = User::where('username', 'pelanggan')->first();

        // Buat alat berkondisi Rusak
        $alatRusak = Alat::create([
            'kode_alat' => 'RUSAK01',
            'nama_alat' => 'Lentera Pecah',
            'kategori' => 'Penerangan',
            'stok' => 5,
            'kondisi' => 'Rusak',
            'tarif_sewa' => 15000,
        ]);

        // Pengguna tidak dapat memesan alat rusak
        $res = $this->actingAs($user)->post(route('pengguna.pesanan.store'), [
            'kode_alat' => 'RUSAK01',
            'jumlah' => 1,
            'tanggal_sewa' => Carbon::tomorrow()->format('Y-m-d'),
            'tanggal_kembali' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'metode_pembayaran' => 'Cash',
        ]);
        $res->assertSessionHasErrors('jumlah');

        // Admin tidak dapat menyetujui pesanan berisi alat rusak jika ada
        $trx = Penyewaan::create([
            'no_transaksi' => 'TRX-RUSAK-APPROVE',
            'id_pelanggan' => 1,
            'id_user' => $user->id_user,
            'tanggal_sewa' => Carbon::tomorrow()->format('Y-m-d'),
            'tanggal_kembali' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'status_penyewaan' => 'Menunggu Konfirmasi',
        ]);
        DetailPenyewaan::create([
            'no_transaksi' => $trx->no_transaksi,
            'kode_alat' => 'RUSAK01',
            'jumlah' => 1,
            'tarif_sewa' => 15000,
        ]);

        $approveRes = $this->actingAs($admin)->post(route('admin.pesanan.setujui', $trx->no_transaksi));
        $approveRes->assertSessionHas('error');
        $this->assertEquals('Menunggu Konfirmasi', $trx->fresh()->status_penyewaan);
    }

    /**
     * Bukti transfer tidak valid (format berkas tidak diizinkan atau ukuran melebihi batas 2MB)
     */
    public function test_security_invalid_transfer_proof_rejected(): void
    {
        $user = User::where('username', 'pelanggan')->first();
        $admin = User::where('username', 'admin')->first();

        $trx = Penyewaan::where('no_transaksi', 'TRX-20261015-002')->first();
        $this->actingAs($admin)->post(route('admin.pesanan.setujui', $trx->no_transaksi));

        Storage::fake('private');

        // 1. Format berkas tidak valid (.txt / text/plain)
        $invalidFile = UploadedFile::fake()->create('malicious.txt', 100, 'text/plain');
        $resInvalidType = $this->actingAs($user)->post(route('pengguna.pembayaran.upload-bukti', $trx->no_transaksi), [
            'bukti_transfer' => $invalidFile,
        ]);
        $resInvalidType->assertSessionHasErrors('bukti_transfer');

        // 2. Ukuran berkas melebihi batas 2MB (3000KB)
        $oversizedFile = UploadedFile::fake()->create('huge_receipt.jpg', 3000, 'image/jpeg');
        $resOversized = $this->actingAs($user)->post(route('pengguna.pembayaran.upload-bukti', $trx->no_transaksi), [
            'bukti_transfer' => $oversizedFile,
        ]);
        $resOversized->assertSessionHasErrors('bukti_transfer');

        // 3. Payload kosong / file tidak disertakan
        $resEmpty = $this->actingAs($user)->post(route('pengguna.pembayaran.upload-bukti', $trx->no_transaksi), []);
        $resEmpty->assertSessionHasErrors('bukti_transfer');
    }

    /**
     * Perubahan status yang tidak sah dicegah
     */
    public function test_security_unauthorized_status_transitions_blocked(): void
    {
        $admin = User::where('username', 'admin')->first();
        $user = User::where('username', 'pelanggan')->first();

        // 1. Pesanan yang masih 'Menunggu Konfirmasi' atau 'Menunggu Pembayaran' tidak dapat diserahkan
        $trxUnpaid = Penyewaan::where('no_transaksi', 'TRX-20261015-002')->first();
        $resPrematurePickup = $this->actingAs($admin)->post(route('admin.operasional.proses-pengambilan', $trxUnpaid->no_transaksi), [
            'tanggal_pengambilan' => '2026-10-16',
            'nama_penerima' => 'Pelanggan',
            'items' => [],
        ]);
        $resPrematurePickup->assertSessionHas('error');

        // 2. Pesanan yang belum diserahkan tidak dapat diproses pengembaliannya
        $resPrematureReturn = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $trxUnpaid->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-20',
            'items' => [],
        ]);
        $resPrematureReturn->assertSessionHas('error');

        // 3. Pesanan yang sudah selesai atau ditolak tidak dapat disetujui ulang
        $trxRejected = Penyewaan::create([
            'no_transaksi' => 'TRX-REJECTED-LOCK',
            'id_pelanggan' => 1,
            'id_user' => $user->id_user,
            'tanggal_sewa' => '2026-10-25',
            'tanggal_kembali' => '2026-10-27',
            'status_penyewaan' => 'Ditolak',
        ]);
        $resReapprove = $this->actingAs($admin)->post(route('admin.pesanan.setujui', $trxRejected->no_transaksi));
        $resReapprove->assertSessionHas('error');
    }

    /**
     * Migrasi database dan integritas data lama (Legacy Data Preservation)
     */
    public function test_security_legacy_data_and_schema_integrity(): void
    {
        // Pastikan transaksi legacy dari seeder/database lama tetap ada, valid, dan dapat diakses
        $legacyTrx = Penyewaan::with(['pelanggan', 'user', 'detailPenyewaan.alat', 'pembayaran'])
            ->whereIn('no_transaksi', ['TRX-20261015-001', 'TRX-20261015-999'])
            ->get();

        $this->assertCount(2, $legacyTrx);

        foreach ($legacyTrx as $trx) {
            $this->assertNotEmpty($trx->no_transaksi);
            $this->assertNotNull($trx->pelanggan);
            $this->assertGreaterThan(0, $trx->total_biaya);
            $this->assertGreaterThan(0, $trx->detailPenyewaan->count());

            // Integritas perhitungan biaya total sama dengan durasi * tarif detail
            $calculatedTotal = $trx->detailPenyewaan->sum(function ($detail) use ($trx) {
                return (float) $detail->jumlah * (float) $detail->tarif_sewa * max(1, $trx->durasi_hari);
            });
            $this->assertEquals((float) $trx->total_biaya, (float) $calculatedTotal);
        }
    }
}

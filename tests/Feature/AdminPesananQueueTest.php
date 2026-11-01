<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Penyewaan;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class AdminPesananQueueTest extends TestCase
{
    protected User $adminUser;

    protected User $pemilikUser;

    protected User $penggunaUser;

    protected Pelanggan $pelanggan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::where('username', 'admin')->first();
        $this->pemilikUser = User::where('username', 'pemilik')->first();
        $this->penggunaUser = User::where('username', 'pelanggan')->first();
        $this->pelanggan = Pelanggan::first();
    }

    /**
     * Helper untuk membuat transaksi pesanan pengujian
     */
    protected function createPesananTest(
        string $noTrx,
        string $sumber = 'online',
        string $metode = 'Cash',
        string $statusPesanan = 'Menunggu Konfirmasi',
        string $statusBayar = 'Belum Dibayar',
        int $durasiHari = 3,
        array $items = [['kode_alat' => 'ALT001', 'jumlah' => 1, 'tarif' => 50000]],
        int $offsetHari = 5
    ): Penyewaan {
        $tglSewa = Carbon::now()->addDays($offsetHari)->format('Y-m-d');
        $tglKembali = Carbon::now()->addDays($offsetHari + $durasiHari)->format('Y-m-d');

        // Bersihkan data lama jika ada
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();

        $penyewaan = Penyewaan::create([
            'no_transaksi' => $noTrx,
            'id_pelanggan' => $this->pelanggan->id_pelanggan,
            'id_user' => $this->penggunaUser->id_user,
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'status_penyewaan' => $statusPesanan,
            'sumber_pesanan' => $sumber,
        ]);

        $grandTotal = 0;
        foreach ($items as $item) {
            $subtotal = $item['tarif'] * $item['jumlah'] * $durasiHari;
            $grandTotal += $subtotal;

            DetailPenyewaan::create([
                'no_transaksi' => $noTrx,
                'kode_alat' => $item['kode_alat'],
                'jumlah' => $item['jumlah'],
                'tarif_sewa' => $item['tarif'],
            ]);
        }

        Pembayaran::create([
            'no_pembayaran' => 'PAY-TEST-'.substr(md5($noTrx), 0, 8),
            'no_transaksi' => $noTrx,
            'total_biaya' => $grandTotal,
            'jumlah_pembayaran' => 0,
            'metode_pembayaran' => $metode,
            'status_pembayaran' => $statusBayar,
        ]);

        return $penyewaan->fresh(['pelanggan', 'pembayaran', 'detailPenyewaan.alat']);
    }

    /**
     * 1 & 2. Antrean pesanan Admin menampilkan seluruh kolom yang diwajibkan serta label Online dan On-site
     */
    public function test_admin_order_queue_displays_all_required_columns_and_accurate_source_badges(): void
    {
        $onlineTrxNo = 'TRX-TEST-ONL-001';
        $onsiteTrxNo = 'TRX-TEST-ONS-002';

        $this->createPesananTest($onlineTrxNo, 'online', 'Transfer Bank', 'Menunggu Konfirmasi', 'Belum Dibayar', 2);
        $this->createPesananTest($onsiteTrxNo, 'on-site', 'Cash', 'Menunggu Konfirmasi', 'Belum Dibayar', 4);

        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));

        $response->assertStatus(200);

        // Nomor transaksi & identitas pelanggan
        $response->assertSee($onlineTrxNo);
        $response->assertSee($onsiteTrxNo);
        $response->assertSee($this->pelanggan->nama_pelanggan);

        // Label sumber pesanan yang sebenarnya (Requirement 2)
        $response->assertSee('Online');
        $response->assertSee('On-site');

        // Status pesanan dan status pembayaran (Requirement 1 & 9)
        $response->assertSee('Menunggu Konfirmasi');
        $response->assertSee('Belum Dibayar');

        // Durasi sewa
        $response->assertSee('2 Hari');
        $response->assertSee('4 Hari');

        // Bersihkan
        DetailPenyewaan::whereIn('no_transaksi', [$onlineTrxNo, $onsiteTrxNo])->delete();
        Pembayaran::whereIn('no_transaksi', [$onlineTrxNo, $onsiteTrxNo])->delete();
        Penyewaan::whereIn('no_transaksi', [$onlineTrxNo, $onsiteTrxNo])->delete();
    }

    /**
     * 3 & 4. Detail pesanan menampilkan semua jenis alat, kode barang, jumlah, tarif, subtotal, dan ketersediaan periode
     */
    public function test_admin_can_view_order_details_and_item_stock_availability_for_requested_period(): void
    {
        $noTrx = 'TRX-TEST-DET-003';
        $trx = $this->createPesananTest($noTrx, 'online', 'QRIS', 'Menunggu Konfirmasi', 'Belum Dibayar', 3, [
            ['kode_alat' => 'ALT001', 'jumlah' => 2, 'tarif' => 50000],
            ['kode_alat' => 'ALT002', 'jumlah' => 1, 'tarif' => 25000],
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.pesanan.show', $noTrx));

        $response->assertStatus(200);
        $response->assertSee($noTrx);
        $response->assertSee('ALT001');
        $response->assertSee('ALT002');
        $response->assertSee('2 Unit');
        $response->assertSee('1 Unit');
        $response->assertSee('QRIS');
        $response->assertSee('Ketersediaan Periode');

        // Subtotal dan total tagihan
        $response->assertSee(number_format($trx->total_biaya, 0, ',', '.'));

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * 5, 8 & 9. Admin menyetujui pesanan yang stoknya cukup dan mendapatkan instruksi pembayaran sesuai metode
     */
    public function test_admin_can_approve_order_when_stock_is_sufficient_and_receives_matching_payment_instructions(): void
    {
        // Uji untuk metode Cash
        $noTrxCash = 'TRX-TEST-APP-CASH';
        $this->createPesananTest($noTrxCash, 'on-site', 'Cash', 'Menunggu Konfirmasi', 'Belum Dibayar', 3, [['kode_alat' => 'ALT001', 'jumlah' => 1, 'tarif' => 50000]], 10);

        $resCash = $this->actingAs($this->adminUser)->post(route('admin.pesanan.setujui', $noTrxCash));

        $resCash->assertSessionHas('success');
        $resCash->assertSessionHas('instruksi_pembayaran');
        $resCash->assertSessionHas('metode_pembayaran', 'Cash');

        $trxCashUpdated = Penyewaan::find($noTrxCash);
        $this->assertEquals('Menunggu Pembayaran', $trxCashUpdated->status_penyewaan);
        $this->assertEquals('Belum Dibayar', $trxCashUpdated->status_pembayaran);

        // Uji untuk metode Transfer Bank
        $noTrxTransfer = 'TRX-TEST-APP-TRF';
        $this->createPesananTest($noTrxTransfer, 'online', 'Transfer Bank', 'Menunggu Konfirmasi', 'Belum Dibayar', 3, [['kode_alat' => 'ALT001', 'jumlah' => 1, 'tarif' => 50000]], 20);

        $resTransfer = $this->actingAs($this->adminUser)->post(route('admin.pesanan.setujui', $noTrxTransfer));

        $resTransfer->assertSessionHas('success');
        $this->assertStringContainsString('Transfer Bank', session('instruksi_pembayaran'));

        // Uji untuk metode QRIS
        $noTrxQris = 'TRX-TEST-APP-QRIS';
        $this->createPesananTest($noTrxQris, 'online', 'QRIS', 'Menunggu Konfirmasi', 'Belum Dibayar', 3, [['kode_alat' => 'ALT001', 'jumlah' => 1, 'tarif' => 50000]], 30);

        $resQris = $this->actingAs($this->adminUser)->post(route('admin.pesanan.setujui', $noTrxQris));

        $resQris->assertSessionHas('success');
        $this->assertStringContainsString('QRIS', session('instruksi_pembayaran'));

        // Pastikan activity log mencatat persetujuan
        $this->assertDatabaseHas('activity_logs', [
            'aktivitas' => 'Persetujuan Pesanan',
            'modul' => 'Penyewaan',
        ]);

        // Bersihkan
        DetailPenyewaan::whereIn('no_transaksi', [$noTrxCash, $noTrxTransfer, $noTrxQris])->delete();
        Pembayaran::whereIn('no_transaksi', [$noTrxCash, $noTrxTransfer, $noTrxQris])->delete();
        Penyewaan::whereIn('no_transaksi', [$noTrxCash, $noTrxTransfer, $noTrxQris])->delete();
    }

    /**
     * 5. Admin dapat menolak pesanan dengan alasan yang jelas dan tervalidasi
     */
    public function test_admin_can_reject_order_with_mandatory_clear_reason(): void
    {
        $noTrx = 'TRX-TEST-REJ-001';
        $this->createPesananTest($noTrx, 'online', 'Cash');

        // Percobaan 1: Tanpa alasan penolakan -> gagal validasi
        $resFail = $this->actingAs($this->adminUser)->post(route('admin.pesanan.tolak', $noTrx), [
            'alasan_penolakan' => '',
        ]);
        $resFail->assertSessionHasErrors('alasan_penolakan');

        // Percobaan 2: Alasan terlalu pendek (< 5 karakter) -> gagal validasi
        $resTooShort = $this->actingAs($this->adminUser)->post(route('admin.pesanan.tolak', $noTrx), [
            'alasan_penolakan' => 'abc',
        ]);
        $resTooShort->assertSessionHasErrors('alasan_penolakan');

        // Percobaan 3: Alasan valid -> berhasil ditolak
        $alasanValid = 'Stok tenda dome pada tanggal sewa tersebut telah penuh dialokasikan untuk kegiatan lapangan.';
        $resSuccess = $this->actingAs($this->adminUser)->post(route('admin.pesanan.tolak', $noTrx), [
            'alasan_penolakan' => $alasanValid,
        ]);

        $resSuccess->assertSessionHas('success');

        $trxUpdated = Penyewaan::with('pembayaran')->find($noTrx);
        $this->assertEquals('Ditolak', $trxUpdated->status_penyewaan);
        $this->assertEquals($alasanValid, $trxUpdated->pembayaran?->alasan_penolakan);

        // Pastikan activity log penolakan tercatat
        $this->assertDatabaseHas('activity_logs', [
            'aktivitas' => 'Penolakan Pesanan',
            'modul' => 'Penyewaan',
        ]);

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * 6. Validasi stok kembali saat persetujuan agar tidak terjadi pemesanan melebihi ketersediaan
     */
    public function test_admin_cannot_approve_order_when_stock_is_insufficient_for_the_period(): void
    {
        $alat = Alat::where('kode_alat', 'ALT004')->first();
        $stokMaks = (int) $alat->stok;

        // Buat pesanan dengan jumlah melebihi stok yang ada
        $noTrx = 'TRX-TEST-OVER-001';
        $this->createPesananTest($noTrx, 'online', 'Cash', 'Menunggu Konfirmasi', 'Belum Dibayar', 2, [
            ['kode_alat' => 'ALT004', 'jumlah' => $stokMaks + 10, 'tarif' => 20000],
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.pesanan.setujui', $noTrx));

        $response->assertSessionHas('error');
        $this->assertStringContainsString('tidak mencukupi', session('error'));

        // Status harus tetap Menunggu Konfirmasi (tidak boleh berubah menjadi Menunggu Pembayaran)
        $trx = Penyewaan::find($noTrx);
        $this->assertEquals('Menunggu Konfirmasi', $trx->status_penyewaan);

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * 6b. Persetujuan ditolak jika kondisi alat tidak 'Baik'
     */
    public function test_admin_cannot_approve_order_when_equipment_condition_is_not_baik(): void
    {
        $alat = Alat::where('kode_alat', 'ALT003')->first();
        $kondisiAwal = $alat->kondisi;

        $alat->kondisi = 'Perlu Perbaikan';
        $alat->save();

        $noTrx = 'TRX-TEST-COND-001';
        $this->createPesananTest($noTrx, 'online', 'Cash', 'Menunggu Konfirmasi', 'Belum Dibayar', 2, [
            ['kode_alat' => 'ALT003', 'jumlah' => 1, 'tarif' => 35000],
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.pesanan.setujui', $noTrx));

        $response->assertSessionHas('error');
        $this->assertStringContainsString('tidak layak disewakan', session('error'));

        $this->assertEquals('Menunggu Konfirmasi', Penyewaan::find($noTrx)->status_penyewaan);

        // Kembalikan kondisi alat
        $alat->kondisi = $kondisiAwal;
        $alat->save();

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * 7. Tangani dua pesanan yang bersaing untuk stok periode yang sama secara aman (first approved, second rejected)
     */
    public function test_competing_orders_for_same_period_stock_handled_safely(): void
    {
        $alat = Alat::where('kode_alat', 'ALT004')->first();
        $stokAwal = (int) $alat->stok;

        // Set stok alat ALT004 menjadi 2 unit untuk pengujian persaingan stok
        $alat->stok = 2;
        $alat->kondisi = 'Baik';
        $alat->save();

        $tglSewa = Carbon::now()->addDays(20)->format('Y-m-d');
        $tglKembali = Carbon::now()->addDays(23)->format('Y-m-d');

        // Pesanan A: meminta 2 unit pada rentang tglSewa s/d tglKembali
        $noTrxA = 'TRX-TEST-COMPETE-A';
        $this->createPesananTest($noTrxA, 'online', 'Cash', 'Menunggu Konfirmasi', 'Belum Dibayar', 3, [
            ['kode_alat' => 'ALT004', 'jumlah' => 2, 'tarif' => 20000],
        ]);
        Penyewaan::where('no_transaksi', $noTrxA)->update([
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
        ]);

        // Pesanan B: meminta 1 unit pada rentang tanggal yang beririsan
        $noTrxB = 'TRX-TEST-COMPETE-B';
        $this->createPesananTest($noTrxB, 'on-site', 'Cash', 'Menunggu Konfirmasi', 'Belum Dibayar', 3, [
            ['kode_alat' => 'ALT004', 'jumlah' => 1, 'tarif' => 20000],
        ]);
        Penyewaan::where('no_transaksi', $noTrxB)->update([
            'tanggal_sewa' => Carbon::now()->addDays(21)->format('Y-m-d'),
            'tanggal_kembali' => Carbon::now()->addDays(24)->format('Y-m-d'),
        ]);

        // Admin menyetujui Pesanan A terlebih dahulu -> Berhasil
        $resA = $this->actingAs($this->adminUser)->post(route('admin.pesanan.setujui', $noTrxA));
        $resA->assertSessionHas('success');
        $this->assertEquals('Menunggu Pembayaran', Penyewaan::find($noTrxA)->status_penyewaan);

        // Admin kemudian mencoba menyetujui Pesanan B -> Harus ditolak karena stok periode habis teralokasi ke Pesanan A
        $resB = $this->actingAs($this->adminUser)->post(route('admin.pesanan.setujui', $noTrxB));
        $resB->assertSessionHas('error');
        $this->assertStringContainsString('tidak mencukupi', session('error'));
        $this->assertEquals('Menunggu Konfirmasi', Penyewaan::find($noTrxB)->status_penyewaan);

        // Kembalikan stok alat
        $alat->stok = $stokAwal;
        $alat->save();

        // Bersihkan
        DetailPenyewaan::whereIn('no_transaksi', [$noTrxA, $noTrxB])->delete();
        Pembayaran::whereIn('no_transaksi', [$noTrxA, $noTrxB])->delete();
        Penyewaan::whereIn('no_transaksi', [$noTrxA, $noTrxB])->delete();
    }

    /**
     * 10. Cegah pemrosesan ganda akibat klik berulang atau pengulangan request (Idempotency)
     */
    public function test_prevent_duplicate_order_submission_on_rapid_repeated_requests(): void
    {
        $noTrx = 'TRX-TEST-DOUBLE-001';
        $this->createPesananTest($noTrx, 'online', 'Cash');

        // Request 1: Persetujuan pertama berhasil
        $res1 = $this->actingAs($this->adminUser)->post(route('admin.pesanan.setujui', $noTrx));
        $res1->assertSessionHas('success');
        $this->assertEquals('Menunggu Pembayaran', Penyewaan::find($noTrx)->status_penyewaan);

        // Request 2 (pengulangan klik): Ditolak karena status sudah bukan Menunggu Konfirmasi
        $res2 = $this->actingAs($this->adminUser)->post(route('admin.pesanan.setujui', $noTrx));
        $res2->assertSessionHas('error');
        $this->assertStringContainsString('sudah diproses sebelumnya', session('error'));

        // Request 3 (mencoba menolak pesanan yang sudah disetujui): Ditolak
        $res3 = $this->actingAs($this->adminUser)->post(route('admin.pesanan.tolak', $noTrx), [
            'alasan_penolakan' => 'Membatalkan yang sudah disetujui',
        ]);
        $res3->assertSessionHas('error');
        $this->assertStringContainsString('sudah diproses sebelumnya', session('error'));

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * 11. Pertahankan hak akses: hanya Admin yang dapat menyetujui atau menolak. Pemilik hanya melihat laporan.
     */
    public function test_role_access_control_only_admin_can_approve_or_reject_pemilik_and_pengguna_forbidden(): void
    {
        $noTrx = 'TRX-TEST-AUTH-001';
        $this->createPesananTest($noTrx, 'online', 'Cash');

        // 1. Role Pemilik mencoba menyetujui -> 403 Forbidden
        $resPemilikSetujui = $this->actingAs($this->pemilikUser)->post(route('admin.pesanan.setujui', $noTrx));
        $resPemilikSetujui->assertStatus(403);

        // 2. Role Pemilik mencoba menolak -> 403 Forbidden
        $resPemilikTolak = $this->actingAs($this->pemilikUser)->post(route('admin.pesanan.tolak', $noTrx), [
            'alasan_penolakan' => 'Alasan penolakan oleh pemilik',
        ]);
        $resPemilikTolak->assertStatus(403);

        // 3. Role Pengguna / Pelanggan mencoba menyetujui -> 403 Forbidden
        $resPenggunaSetujui = $this->actingAs($this->penggunaUser)->post(route('admin.pesanan.setujui', $noTrx));
        $resPenggunaSetujui->assertStatus(403);

        // 4. Tamu (Guest / Tanpa login) mencoba menyetujui -> dialihkan ke login
        $this->app['auth']->logout();
        $resGuest = $this->post(route('admin.pesanan.setujui', $noTrx));
        $resGuest->assertRedirect(route('login'));

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * 9 & 12. Status pesanan tetap terpisah dari status pembayaran, relasi historis terjaga
     */
    public function test_order_status_remains_distinct_from_payment_status(): void
    {
        $noTrx = 'TRX-TEST-STAT-001';
        $penyewaan = $this->createPesananTest($noTrx, 'online', 'Transfer Bank', 'Menunggu Konfirmasi', 'Belum Dibayar');

        // Sebelum persetujuan
        $this->assertEquals('Menunggu Konfirmasi', $penyewaan->status_penyewaan);
        $this->assertEquals('Belum Dibayar', $penyewaan->status_pembayaran);

        // Setelah disetujui Admin
        $this->actingAs($this->adminUser)->post(route('admin.pesanan.setujui', $noTrx));
        $penyewaan->refresh();

        // Status pesanan berubah ke 'Menunggu Pembayaran', status pembayaran tetap 'Belum Dibayar'
        $this->assertEquals('Menunggu Pembayaran', $penyewaan->status_penyewaan);
        $this->assertEquals('Belum Dibayar', $penyewaan->status_pembayaran);

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * Pesanan yang pembayarannya sudah Lunas langsung menjadi Siap Diambil ketika stok disetujui
     */
    public function test_order_approved_with_lunas_payment_becomes_siap_diambil_immediately(): void
    {
        $noTrx = 'TRX-TEST-PAID-001';
        $penyewaan = $this->createPesananTest($noTrx, 'on-site', 'Cash', 'Menunggu Konfirmasi', 'Lunas');

        // Sebelum persetujuan
        $this->assertEquals('Menunggu Konfirmasi', $penyewaan->status_penyewaan);
        $this->assertEquals('Lunas', $penyewaan->status_pembayaran);

        // Setelah disetujui Admin (konfirmasi stok)
        $res = $this->actingAs($this->adminUser)->post(route('admin.pesanan.setujui', $noTrx));
        $res->assertSessionHas('success');

        $penyewaan->refresh();

        // Status pesanan harus langsung Siap Diambil karena sudah lunas
        $this->assertEquals('Siap Diambil', $penyewaan->status_penyewaan);
        $this->assertTrue($penyewaan->isSiapDiambil());

        // Verifikasi muncul di antrean penyerahan alat
        $pickupRes = $this->actingAs($this->adminUser)->get(route('admin.operasional.pengambilan'));
        $pickupRes->assertStatus(200);
        $pickupRes->assertSee($noTrx);

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }
}

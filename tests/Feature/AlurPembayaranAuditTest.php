<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Penyewaan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlurPembayaranAuditTest extends TestCase
{
    protected User $adminUser;

    protected User $pemilikUser;

    protected User $penggunaUser1;

    protected User $penggunaUser2;

    protected Pelanggan $pelanggan1;

    protected Pelanggan $pelanggan2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::where('username', 'admin')->first();
        $this->pemilikUser = User::where('username', 'pemilik')->first();
        $this->penggunaUser1 = User::where('username', 'pelanggan')->first();
        $this->penggunaUser2 = User::where('username', 'budi')->first();
        $this->pelanggan1 = Pelanggan::find($this->penggunaUser1->id_pelanggan) ?? Pelanggan::first();
        $this->pelanggan2 = Pelanggan::find($this->penggunaUser2->id_pelanggan) ?? Pelanggan::skip(1)->first();
    }

    /**
     * Helper untuk membuat transaksi pengujian yang terisolasi
     */
    protected function createIsolatedTransaction(
        string $noTrx,
        User $user,
        Pelanggan $pelanggan,
        string $metode = 'Transfer Bank',
        string $statusPesanan = 'Menunggu Pembayaran',
        string $statusBayar = 'Belum Dibayar',
        int $durasiHari = 3,
        array $items = [['kode_alat' => 'ALT001', 'jumlah' => 2, 'tarif' => 50000]],
        int $offsetHari = 50
    ): Penyewaan {
        $tglSewa = Carbon::now()->addDays($offsetHari)->format('Y-m-d');
        $tglKembali = Carbon::now()->addDays($offsetHari + $durasiHari)->format('Y-m-d');

        // Bersihkan data lama
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();

        $penyewaan = Penyewaan::create([
            'no_transaksi' => $noTrx,
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => $user->id_user,
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'status_penyewaan' => $statusPesanan,
            'sumber_pesanan' => 'online',
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
            'jumlah_pembayaran' => ($statusBayar === 'Lunas') ? $grandTotal : 0,
            'metode_pembayaran' => $metode,
            'status_pembayaran' => $statusBayar,
        ]);

        return $penyewaan->fresh(['pelanggan', 'pembayaran', 'detailPenyewaan.alat']);
    }

    /**
     * 1. Pertahankan metode pembayaran QRIS, transfer, dan tunai
     */
    public function test_metode_pembayaran_qris_transfer_dan_tunai_tetap_tersedia(): void
    {
        $trxQris = $this->createIsolatedTransaction('TRX-AUDIT-MET-QRIS', $this->penggunaUser1, $this->pelanggan1, 'QRIS');
        $trxTrf = $this->createIsolatedTransaction('TRX-AUDIT-MET-TRF', $this->penggunaUser1, $this->pelanggan1, 'Transfer Bank');
        $trxCash = $this->createIsolatedTransaction('TRX-AUDIT-MET-CASH', $this->penggunaUser1, $this->pelanggan1, 'Cash');

        $this->actingAs($this->penggunaUser1)->get(route('pengguna.pembayaran.qris', $trxQris->no_transaksi))->assertStatus(200);
        $this->actingAs($this->penggunaUser1)->get(route('pengguna.pembayaran.transfer', $trxTrf->no_transaksi))->assertStatus(200);
        $this->actingAs($this->penggunaUser1)->get(route('pengguna.pembayaran.cash', $trxCash->no_transaksi))->assertStatus(200);

        // Bersihkan
        DetailPenyewaan::whereIn('no_transaksi', [$trxQris->no_transaksi, $trxTrf->no_transaksi, $trxCash->no_transaksi])->delete();
        Pembayaran::whereIn('no_transaksi', [$trxQris->no_transaksi, $trxTrf->no_transaksi, $trxCash->no_transaksi])->delete();
        Penyewaan::whereIn('no_transaksi', [$trxQris->no_transaksi, $trxTrf->no_transaksi, $trxCash->no_transaksi])->delete();
    }

    /**
     * 2. Semua metode pembayaran menggunakan total tagihan yang dihitung di backend dari seluruh barang dan durasi sewa
     */
    public function test_semua_metode_menggunakan_total_tagihan_yang_dihitung_di_backend_dari_seluruh_barang_dan_durasi(): void
    {
        // 2 tenda @ 50.000 + 3 sleeping bag @ 25.000 selama 3 hari = (100.000 + 75.000) * 3 = 525.000
        $noTrx = 'TRX-AUDIT-TOTAL-001';
        $penyewaan = $this->createIsolatedTransaction($noTrx, $this->penggunaUser1, $this->pelanggan1, 'Transfer Bank', 'Menunggu Pembayaran', 'Belum Dibayar', 3, [
            ['kode_alat' => 'ALT001', 'jumlah' => 2, 'tarif' => 50000],
            ['kode_alat' => 'ALT002', 'jumlah' => 3, 'tarif' => 25000],
        ]);

        $this->assertEquals(525000.0, $penyewaan->total_biaya);
        $this->assertEquals(525000.0, (float) $penyewaan->pembayaran->total_biaya);

        // Akses view pembayaran dan pastikan total 525.000 tampil konsisten
        $res = $this->actingAs($this->penggunaUser1)->get(route('pengguna.pembayaran.transfer', $noTrx));
        $res->assertSee('525.000');

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * 3. QRIS: tampilkan instruksi “Silakan minta kode QRIS kepada kasir untuk melanjutkan pembayaran.” Jangan membuat kode QRIS palsu/acak
     */
    public function test_qris_displays_mandatory_cashier_instruction_and_no_fake_qr_code(): void
    {
        $noTrx = 'TRX-AUDIT-QRIS-001';
        $penyewaan = $this->createIsolatedTransaction($noTrx, $this->penggunaUser1, $this->pelanggan1, 'QRIS');

        $response = $this->actingAs($this->penggunaUser1)->get(route('pengguna.pembayaran.qris', $noTrx));

        $response->assertStatus(200);

        // Tampilkan instruksi wajib
        $response->assertSee('Silakan minta kode QRIS kepada kasir untuk melanjutkan pembayaran.');

        // Jangan membuat kode QRIS palsu, acak, atau seolah-olah dapat menerima pembayaran
        $content = $response->getContent();
        $this->assertStringNotContainsString('chart.googleapis.com', $content);
        $this->assertStringNotContainsString('api.qrserver.com', $content);
        $this->assertStringNotContainsString('qrcode.js', $content);
        $this->assertStringNotContainsString('<canvas id="qrcode"', $content);

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * 4. Transfer: tampilkan nama bank, nomor rekening, nama pemilik rekening, total tagihan, dan instruksi unggah bukti
     */
    public function test_transfer_displays_bank_name_account_number_holder_and_upload_instruction(): void
    {
        Config::set('payment.bank', [
            'name' => 'Bank BCA',
            'account_number' => '8830592811',
            'account_holder' => 'Adventure Camp Rental',
        ]);

        $noTrx = 'TRX-AUDIT-TRF-001';
        $penyewaan = $this->createIsolatedTransaction($noTrx, $this->penggunaUser1, $this->pelanggan1, 'Transfer Bank', 'Menunggu Pembayaran', 'Belum Dibayar', 2, [
            ['kode_alat' => 'ALT001', 'jumlah' => 1, 'tarif' => 50000],
        ]);

        $response = $this->actingAs($this->penggunaUser1)->get(route('pengguna.pembayaran.transfer', $noTrx));

        $response->assertStatus(200);
        $response->assertSee('Bank BCA');
        $response->assertSee('8830592811');
        $response->assertSee('Adventure Camp Rental');
        $response->assertSee('100.000');
        $response->assertSee('Unggah Bukti Transfer');
        $response->assertSee('JPG, JPEG, PNG, PDF');

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * 4b. Transfer: jika rekening belum dikonfigurasi, minta pelanggan menghubungi kasir/Admin (jangan mengarang rekening)
     */
    public function test_transfer_prompts_contacting_cashier_or_admin_when_bank_is_not_configured(): void
    {
        Config::set('payment.bank', [
            'name' => null,
            'account_number' => null,
            'account_holder' => null,
        ]);

        $noTrx = 'TRX-AUDIT-TRF-002';
        $penyewaan = $this->createIsolatedTransaction($noTrx, $this->penggunaUser1, $this->pelanggan1, 'Transfer Bank');

        $response = $this->actingAs($this->penggunaUser1)->get(route('pengguna.pembayaran.transfer', $noTrx));

        $response->assertStatus(200);
        $response->assertSee('Informasi rekening bank resmi belum dikonfigurasi');
        $response->assertSee('Silakan minta nomor rekening resmi toko langsung kepada kasir atau Admin.');

        // Jangan mengarang nomor rekening
        $response->assertDontSee('8830592811');

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * 5. Tunai: tampilkan rincian tagihan dan instruksi membayar kepada kasir/Admin
     */
    public function test_tunai_displays_itemized_billing_breakdown_and_instructions_to_pay_cashier(): void
    {
        $noTrx = 'TRX-AUDIT-CASH-001';
        $penyewaan = $this->createIsolatedTransaction($noTrx, $this->penggunaUser1, $this->pelanggan1, 'Cash', 'Menunggu Pembayaran', 'Belum Dibayar', 2, [
            ['kode_alat' => 'ALT001', 'jumlah' => 2, 'tarif' => 50000],
        ]);

        $response = $this->actingAs($this->penggunaUser1)->get(route('pengguna.pembayaran.cash', $noTrx));

        $response->assertStatus(200);

        // Rincian tagihan
        $response->assertSee('ALT001');
        $response->assertSee('2 unit');
        $response->assertSee('Rp 200.000');

        // Instruksi membayar kepada kasir/Admin
        $response->assertSee('Pembayaran tunai dilakukan secara langsung kepada kasir atau Admin');
        $response->assertSee('Siapkan uang tunai pas sebesar');

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * 6. Admin harus memverifikasi pembayaran sebelum status menjadi Lunas
     */
    public function test_admin_must_verify_payment_before_status_becomes_lunas_and_customer_cannot_set_lunas(): void
    {
        Storage::fake('local');
        $noTrx = 'TRX-AUDIT-VERIF-001';
        $penyewaan = $this->createIsolatedTransaction($noTrx, $this->penggunaUser1, $this->pelanggan1, 'Transfer Bank', 'Menunggu Pembayaran', 'Belum Dibayar', 2, [
            ['kode_alat' => 'ALT001', 'jumlah' => 1, 'tarif' => 50000],
        ]);

        // Pelanggan mengunggah bukti transfer
        $file = UploadedFile::fake()->create('bukti_transfer.jpg', 300, 'image/jpeg');
        $this->actingAs($this->penggunaUser1)->post(route('pengguna.pembayaran.upload-bukti', $noTrx), [
            'bukti_transfer' => $file,
        ]);

        // Status setelah unggah bukti adalah 'Menunggu Verifikasi', BUKAN 'Lunas'
        $penyewaan->refresh();
        $this->assertEquals('Menunggu Verifikasi', $penyewaan->status_pembayaran);
        $this->assertEquals('Menunggu Pembayaran', $penyewaan->status_penyewaan);
        $this->assertEquals(0, (float) $penyewaan->pembayaran->jumlah_pembayaran);

        // Pelanggan mencoba rute konfirmasi sendiri -> 403 atau 404
        $this->actingAs($this->penggunaUser1)->post(route('admin.pembayaran.konfirmasi', $noTrx), [
            'jumlah_diterima' => 100000,
        ])->assertStatus(403);

        // Status tetap Menunggu Verifikasi
        $penyewaan->refresh();
        $this->assertEquals('Menunggu Verifikasi', $penyewaan->status_pembayaran);

        // Hanya Admin yang dapat memverifikasi dan mengubah status menjadi Lunas
        $resAdmin = $this->actingAs($this->adminUser)->post(route('admin.pembayaran.konfirmasi', $noTrx), [
            'jumlah_diterima' => 100000,
            'metode_pembayaran' => 'Transfer Bank',
        ]);
        $resAdmin->assertSessionHas('success');

        $penyewaan->refresh();
        $this->assertEquals('Lunas', $penyewaan->status_pembayaran);
        $this->assertEquals('Siap Diambil', $penyewaan->status_penyewaan);
        $this->assertEquals(100000.0, (float) $penyewaan->pembayaran->jumlah_pembayaran);
        $this->assertEquals($this->adminUser->id_user, $penyewaan->pembayaran->id_admin);

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * 7. Bedakan status pesanan dengan status pembayaran
     */
    public function test_order_status_is_strictly_distinguished_from_payment_status(): void
    {
        $noTrx = 'TRX-AUDIT-DISTINCT-001';
        $penyewaan = $this->createIsolatedTransaction($noTrx, $this->penggunaUser1, $this->pelanggan1, 'Cash', 'Menunggu Konfirmasi', 'Belum Dibayar', 1, [
            ['kode_alat' => 'ALT001', 'jumlah' => 1, 'tarif' => 50000],
        ]);

        // Fase 1: Baru Dibuat
        $this->assertEquals('Menunggu Konfirmasi', $penyewaan->status_penyewaan);
        $this->assertEquals('Belum Dibayar', $penyewaan->status_pembayaran);

        // Fase 2: Disetujui Admin
        $this->actingAs($this->adminUser)->post(route('admin.pesanan.setujui', $noTrx));
        $penyewaan->refresh();
        $this->assertEquals('Menunggu Pembayaran', $penyewaan->status_penyewaan);
        $this->assertEquals('Belum Dibayar', $penyewaan->status_pembayaran);

        // Fase 3: Dikonfirmasi Pembayaran Lunas
        $this->actingAs($this->adminUser)->post(route('admin.pembayaran.konfirmasi', $noTrx), [
            'jumlah_diterima' => 50000,
            'metode_pembayaran' => 'Cash',
        ]);
        $penyewaan->refresh();
        $this->assertEquals('Siap Diambil', $penyewaan->status_penyewaan);
        $this->assertEquals('Lunas', $penyewaan->status_pembayaran);

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * 8. Jangan menghitung pesanan yang belum dibayar sebagai pendapatan yang sudah diterima
     */
    public function test_unpaid_orders_are_not_counted_as_received_revenue(): void
    {
        $todayDate = Carbon::today('Asia/Jakarta')->toDateString();

        // Ambil pendapatan awal hari ini dari pembayaran yang berstatus Lunas
        $initialRevenue = (float) Pembayaran::where('status_pembayaran', 'Lunas')
            ->whereDate('waktu_konfirmasi', $todayDate)
            ->sum('jumlah_pembayaran');

        // Buat pesanan baru bernilai besar (Rp 5.000.000) yang BELUM DIBAYAR
        $noTrx = 'TRX-AUDIT-UNPAID-REV';
        $penyewaan = $this->createIsolatedTransaction($noTrx, $this->penggunaUser1, $this->pelanggan1, 'Transfer Bank', 'Menunggu Pembayaran', 'Belum Dibayar', 10, [
            ['kode_alat' => 'ALT001', 'jumlah' => 10, 'tarif' => 50000],
        ]);

        // Pastikan total biaya adalah 5.000.000
        $this->assertEquals(5000000.0, $penyewaan->total_biaya);

        // Periksa pendapatan hari ini: TIDAK BOLEH bertambah 5.000.000 karena status belum Lunas
        $revenueAfterUnpaid = (float) Pembayaran::where('status_pembayaran', 'Lunas')
            ->whereDate('waktu_konfirmasi', $todayDate)
            ->sum('jumlah_pembayaran');

        $this->assertEquals($initialRevenue, $revenueAfterUnpaid);

        // Admin mengonfirmasi pembayaran Lunas
        $this->actingAs($this->adminUser)->post(route('admin.pembayaran.konfirmasi', $noTrx), [
            'jumlah_diterima' => 5000000,
            'metode_pembayaran' => 'Transfer Bank',
        ]);

        // Setelah berstatus Lunas, pendapatan hari ini bertambah 5.000.000
        $revenueAfterPaid = (float) Pembayaran::where('status_pembayaran', 'Lunas')
            ->whereDate('waktu_konfirmasi', $todayDate)
            ->sum('jumlah_pembayaran');

        $this->assertEquals($initialRevenue + 5000000.0, $revenueAfterPaid);

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * 9. Pastikan tagihan historis tetap menggunakan tarif dan total yang berlaku ketika transaksi dibuat
     */
    public function test_historical_transactions_preserve_original_rates_and_totals(): void
    {
        $alat = Alat::where('kode_alat', 'ALT001')->first();
        $originalTarif = (float) $alat->tarif_sewa;

        // Buat transaksi dengan tarif 50.000, durasi 3 hari = 150.000
        $noTrx = 'TRX-AUDIT-HISTORIS-001';
        $penyewaan = $this->createIsolatedTransaction($noTrx, $this->penggunaUser1, $this->pelanggan1, 'Cash', 'Menunggu Pembayaran', 'Belum Dibayar', 3, [
            ['kode_alat' => 'ALT001', 'jumlah' => 1, 'tarif' => $originalTarif],
        ]);

        $this->assertEquals($originalTarif * 3, $penyewaan->total_biaya);

        // Ubah tarif alat di tabel master 'alat' menjadi 250.000
        $alat->tarif_sewa = 250000;
        $alat->save();

        // Muat ulang transaksi dari database dan pastikan total tagihan transaksi lama TETAP 150.000 (bukan 750.000)
        $penyewaanReloaded = Penyewaan::with('detailPenyewaan')->find($noTrx);
        $this->assertEquals($originalTarif * 3, $penyewaanReloaded->total_biaya);
        $this->assertEquals($originalTarif, (float) $penyewaanReloaded->detailPenyewaan->first()->tarif_sewa);

        // Kembalikan tarif asli alat master
        $alat->tarif_sewa = $originalTarif;
        $alat->save();

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }

    /**
     * 10. Pastikan pelanggan hanya dapat melihat tagihan dan bukti pembayaran miliknya (IDOR Protection)
     */
    public function test_customer_can_only_view_their_own_bills_and_payment_proofs(): void
    {
        Storage::fake('local');
        $storedPath = 'bukti_pembayaran/test_privat.jpg';
        Storage::disk('local')->put($storedPath, 'konten-gambar-rahasia');

        // Transaksi milik Pengguna 1
        $noTrx1 = 'TRX-AUDIT-OWNER-001';
        $trx1 = $this->createIsolatedTransaction($noTrx1, $this->penggunaUser1, $this->pelanggan1, 'Transfer Bank', 'Menunggu Pembayaran', 'Menunggu Verifikasi');
        $trx1->pembayaran->bukti_pembayaran = $storedPath;
        $trx1->pembayaran->save();

        // Pengguna 1 (pemilik) DAPAT mengakses halamannya sendiri
        $this->actingAs($this->penggunaUser1)->get(route('pengguna.pembayaran.transfer', $noTrx1))->assertStatus(200);
        $this->actingAs($this->penggunaUser1)->get(route('pembayaran.bukti', $noTrx1))->assertStatus(200);

        // Pengguna 2 (bukan pemilik) DITOLAK 403 Forbidden untuk melihat tagihan dan bukti Pengguna 1
        $this->actingAs($this->penggunaUser2)->get(route('pengguna.pembayaran.transfer', $noTrx1))->assertStatus(403);
        $this->actingAs($this->penggunaUser2)->get(route('pengguna.pembayaran.qris', $noTrx1))->assertStatus(403);
        $this->actingAs($this->penggunaUser2)->get(route('pengguna.pembayaran.cash', $noTrx1))->assertStatus(403);
        $this->actingAs($this->penggunaUser2)->get(route('pembayaran.bukti', $noTrx1))->assertStatus(403);

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx1)->delete();
        Pembayaran::where('no_transaksi', $noTrx1)->delete();
        Penyewaan::where('no_transaksi', $noTrx1)->delete();
    }

    /**
     * 11. Pastikan pembayaran tidak dapat dikonfirmasi dua kali secara tidak sengaja (Idempotency & Concurrency)
     */
    public function test_payment_cannot_be_confirmed_twice_accidentally(): void
    {
        $noTrx = 'TRX-AUDIT-DOUBLE-CONFIRM';
        $penyewaan = $this->createIsolatedTransaction($noTrx, $this->penggunaUser1, $this->pelanggan1, 'Cash', 'Menunggu Pembayaran', 'Belum Dibayar', 2, [
            ['kode_alat' => 'ALT001', 'jumlah' => 1, 'tarif' => 50000],
        ]);

        // Konfirmasi Pertama oleh Admin -> Berhasil
        $res1 = $this->actingAs($this->adminUser)->post(route('admin.pembayaran.konfirmasi', $noTrx), [
            'jumlah_diterima' => 100000,
            'metode_pembayaran' => 'Cash',
        ]);
        $res1->assertSessionHas('success');

        $pembayaran1 = Pembayaran::where('no_transaksi', $noTrx)->first();
        $this->assertEquals('Lunas', $pembayaran1->status_pembayaran);
        $this->assertEquals(100000.0, (float) $pembayaran1->jumlah_pembayaran);

        // Konfirmasi Kedua (misal klik berulang / repeat request) -> Ditolak dengan pesan error aman
        $res2 = $this->actingAs($this->adminUser)->post(route('admin.pembayaran.konfirmasi', $noTrx), [
            'jumlah_diterima' => 100000,
            'metode_pembayaran' => 'Cash',
        ]);
        $res2->assertSessionHas('error');
        $this->assertStringContainsString('sudah berstatus Lunas sebelumnya', session('error'));

        // Nominal TIDAK berlipat ganda
        $pembayaran2 = Pembayaran::where('no_transaksi', $noTrx)->first();
        $this->assertEquals(100000.0, (float) $pembayaran2->jumlah_pembayaran);
        $this->assertEquals(1, Pembayaran::where('no_transaksi', $noTrx)->count());

        // Bersihkan
        DetailPenyewaan::where('no_transaksi', $noTrx)->delete();
        Pembayaran::where('no_transaksi', $noTrx)->delete();
        Penyewaan::where('no_transaksi', $noTrx)->delete();
    }
}

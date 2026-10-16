<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Penyewaan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PembayaranTest extends TestCase
{
    /**
     * Pengguna dapat melihat instruksi pembayaran QRIS untuk pesanan yang telah disetujui Admin
     */
    public function test_authenticated_pengguna_can_view_qris_instructions_for_approved_order(): void
    {
        $user = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($user)->get(route('pengguna.pembayaran.qris', 'TRX-20261015-001'));

        $response->assertStatus(200);

        // 1. Nomor transaksi
        $response->assertSee('TRX-20261015-001');

        // 2. Rincian pesanan (nama alat, durasi)
        $response->assertSee('Tenda Dome 4 Orang');
        $response->assertSee('Sleeping Bag');
        $response->assertSee('2 Hari');

        // 3. Total tagihan sesuai transaksi (200.000)
        $response->assertSee('Rp 200.000');

        // 4. Status pesanan dan status pembayaran terpisah
        $response->assertSee('Menunggu Pembayaran');
        $response->assertSee('Belum Dibayar');

        // 5. Pesan wajib sesuai SOP
        $response->assertSee('Silakan minta kode QRIS kepada kasir untuk melanjutkan pembayaran.');
    }

    /**
     * QRIS tidak menggunakan gambar QR acak atau generator QR palsu
     */
    public function test_qris_page_does_not_render_random_or_fake_qr_code(): void
    {
        $user = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($user)->get(route('pengguna.pembayaran.qris', 'TRX-20261015-001'));

        $content = $response->getContent();

        // Tidak boleh ada api generator QR eksternal atau dummy canvas QR
        $this->assertStringNotContainsString('chart.googleapis.com', $content);
        $this->assertStringNotContainsString('api.qrserver.com', $content);
        $this->assertStringNotContainsString('qrcode.js', $content);
    }

    /**
     * Pengguna dapat melihat instruksi pembayaran Transfer Bank beserta rekening resmi
     */
    public function test_authenticated_pengguna_can_view_transfer_instructions_with_valid_bank_info(): void
    {
        $user = User::where('username', 'pelanggan')->first();

        Config::set('payment.bank', [
            'name' => 'Bank BCA',
            'account_number' => '8830592811',
            'account_holder' => 'Adventure Camp Rental',
        ]);

        $response = $this->actingAs($user)->get(route('pengguna.pembayaran.transfer', 'TRX-20261015-001'));

        $response->assertStatus(200);
        $response->assertSee('TRX-20261015-001');
        $response->assertSee('Bank BCA');
        $response->assertSee('8830592811');
        $response->assertSee('Adventure Camp Rental');
        $response->assertSee('Rp 200.000');
        $response->assertSee('Unggah Bukti Transfer');
    }

    /**
     * Halaman transfer menampilkan peringatan jika rekening bank belum diatur
     */
    public function test_transfer_page_displays_warning_if_bank_info_is_not_configured(): void
    {
        $user = User::where('username', 'pelanggan')->first();

        Config::set('payment.bank', [
            'name' => null,
            'account_number' => null,
            'account_holder' => null,
        ]);

        $response = $this->actingAs($user)->get(route('pengguna.pembayaran.transfer', 'TRX-20261015-001'));

        $response->assertStatus(200);
        $response->assertSee('Informasi rekening bank resmi belum dikonfigurasi');
        $response->assertSee('Silakan minta nomor rekening resmi toko langsung kepada kasir atau Admin.');
    }

    /**
     * Pengguna dapat melihat instruksi pembayaran Tunai (Cash) di kasir
     */
    public function test_authenticated_pengguna_can_view_cash_instructions(): void
    {
        $user = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($user)->get(route('pengguna.pembayaran.cash', 'TRX-20261015-001'));

        $response->assertStatus(200);
        $response->assertSee('TRX-20261015-001');
        $response->assertSee('Pembayaran tunai dilakukan secara langsung kepada kasir atau Admin');
        $response->assertSee('Rp 200.000');
    }

    /**
     * Bukti transfer dapat diunggah dengan validasi dan disimpan secara aman
     */
    public function test_payment_proof_can_be_uploaded_with_validation_and_saved_securely(): void
    {
        Storage::fake('local');
        $user = User::where('username', 'pelanggan')->first();

        $file = UploadedFile::fake()->create('bukti_transfer.jpg', 500, 'image/jpeg');

        $response = $this->actingAs($user)->post(route('pengguna.pembayaran.upload-bukti', 'TRX-20261015-001'), [
            'bukti_transfer' => $file,
        ]);

        $response->assertRedirect(route('pengguna.pembayaran.transfer', 'TRX-20261015-001'));
        $response->assertSessionHas('success');

        $pembayaran = Pembayaran::where('no_transaksi', 'TRX-20261015-001')->first();
        $this->assertNotNull($pembayaran);
        $this->assertEquals('Menunggu Verifikasi', $pembayaran->status_pembayaran);
        $this->assertEquals('Transfer Bank', $pembayaran->metode_pembayaran);
        $this->assertNotNull($pembayaran->bukti_pembayaran);
        $this->assertNotNull($pembayaran->waktu_unggah_bukti);

        // Pastikan berkas tersimpan di disk storage
        Storage::disk('local')->assertExists($pembayaran->bukti_pembayaran);
    }

    /**
     * Validasi tipe file menolak format yang tidak diizinkan
     */
    public function test_payment_proof_validation_rejects_unallowed_mime_types(): void
    {
        Storage::fake('local');
        $user = User::where('username', 'pelanggan')->first();

        $file = UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($user)->post(route('pengguna.pembayaran.upload-bukti', 'TRX-20261015-001'), [
            'bukti_transfer' => $file,
        ]);

        $response->assertSessionHasErrors(['bukti_transfer']);
        $this->assertDatabaseMissing('pembayaran', [
            'no_transaksi' => 'TRX-20261015-001',
        ]);
    }

    /**
     * Validasi ukuran berkas menolak ukuran file melebihi batas 2MB
     */
    public function test_payment_proof_validation_rejects_file_exceeding_max_size(): void
    {
        Storage::fake('local');
        $user = User::where('username', 'pelanggan')->first();

        // 3000 KB > 2048 KB
        $file = UploadedFile::fake()->create('foto_raksasa.jpg', 3000, 'image/jpeg');

        $response = $this->actingAs($user)->post(route('pengguna.pembayaran.upload-bukti', 'TRX-20261015-001'), [
            'bukti_transfer' => $file,
        ]);

        $response->assertSessionHasErrors(['bukti_transfer']);
    }

    /**
     * Pembayaran tidak otomatis lunas setelah unggah bukti
     */
    public function test_payment_is_not_automatically_lunas_after_uploading_proof(): void
    {
        Storage::fake('local');
        $user = User::where('username', 'pelanggan')->first();

        $file = UploadedFile::fake()->create('transfer_bca.png', 400, 'image/png');

        $this->actingAs($user)->post(route('pengguna.pembayaran.upload-bukti', 'TRX-20261015-001'), [
            'bukti_transfer' => $file,
        ]);

        $pembayaran = Pembayaran::where('no_transaksi', 'TRX-20261015-001')->first();
        $this->assertNotNull($pembayaran);
        $this->assertNotEquals('Lunas', $pembayaran->status_pembayaran);
        $this->assertEquals('Menunggu Verifikasi', $pembayaran->status_pembayaran);
        $this->assertEquals(0, (float) $pembayaran->jumlah_pembayaran);

        $penyewaan = Penyewaan::find('TRX-20261015-001');
        $this->assertNotEquals('Lunas', $penyewaan->status_pembayaran);
    }

    /**
     * Pengguna tidak dapat mengonfirmasi pembayaran sendiri
     */
    public function test_pengguna_cannot_confirm_payment_themselves(): void
    {
        $user = User::where('username', 'pelanggan')->first();

        // Pengguna tidak memiliki rute atau hak akses untuk mengonfirmasi pembayaran sendiri
        $response = $this->actingAs($user)->post('/pengguna/pembayaran/TRX-20261015-001/konfirmasi');
        $this->assertTrue(in_array($response->status(), [403, 404, 405], true));

        $penyewaan = Penyewaan::find('TRX-20261015-001');
        $this->assertNotEquals('Lunas', $penyewaan->status_pembayaran);
    }

    /**
     * Pengguna tidak dapat mengunggah bukti untuk transaksi milik orang lain (IDOR)
     */
    public function test_pengguna_cannot_upload_proof_for_other_users_transaction_idor(): void
    {
        Storage::fake('local');
        $user = User::where('username', 'pelanggan')->first(); // Pelanggan 1

        $file = UploadedFile::fake()->create('bukti.jpg', 300, 'image/jpeg');

        // TRX-20261015-999 adalah milik Pelanggan 2
        $response = $this->actingAs($user)->post(route('pengguna.pembayaran.upload-bukti', 'TRX-20261015-999'), [
            'bukti_transfer' => $file,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Pengguna tidak dapat mengunggah bukti untuk pesanan yang belum disetujui Admin
     */
    public function test_cannot_upload_proof_for_unapproved_order(): void
    {
        Storage::fake('local');
        $user = User::where('username', 'pelanggan')->first();

        $file = UploadedFile::fake()->create('bukti.jpg', 300, 'image/jpeg');

        // TRX-20261015-002 masih berstatus 'Menunggu Konfirmasi'
        $response = $this->actingAs($user)->post(route('pengguna.pembayaran.upload-bukti', 'TRX-20261015-002'), [
            'bukti_transfer' => $file,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Berkas bukti transfer dapat diakses oleh pemilik transaksi dan Admin
     */
    public function test_owner_and_admin_can_access_uploaded_proof_file(): void
    {
        Storage::fake('local');
        $storedPath = 'bukti_pembayaran/test_bukti.jpg';
        Storage::disk('local')->put($storedPath, 'dummy-image-content');

        Pembayaran::create([
            'no_pembayaran' => 'PAY-20261016-001',
            'no_transaksi' => 'TRX-20261015-001',
            'total_biaya' => 200000,
            'jumlah_pembayaran' => 0,
            'metode_pembayaran' => 'Transfer Bank',
            'status_pembayaran' => 'Menunggu Verifikasi',
            'bukti_pembayaran' => $storedPath,
            'waktu_unggah_bukti' => now(),
        ]);

        $owner = User::where('username', 'pelanggan')->first();
        $admin = User::where('username', 'admin')->first();

        // Pemilik dapat melihat berkas
        $ownerResponse = $this->actingAs($owner)->get(route('pembayaran.bukti', 'TRX-20261015-001'));
        $ownerResponse->assertStatus(200);

        // Admin dapat melihat berkas
        $adminResponse = $this->actingAs($admin)->get(route('pembayaran.bukti', 'TRX-20261015-001'));
        $adminResponse->assertStatus(200);
    }

    /**
     * Pengguna lain tidak dapat mengakses berkas bukti transfer milik pengguna lain
     */
    public function test_other_user_cannot_access_payment_proof_file(): void
    {
        Storage::fake('local');
        $storedPath = 'bukti_pembayaran/test_bukti.jpg';
        Storage::disk('local')->put($storedPath, 'dummy-image-content');

        Pembayaran::create([
            'no_pembayaran' => 'PAY-20261016-001',
            'no_transaksi' => 'TRX-20261015-001',
            'total_biaya' => 200000,
            'jumlah_pembayaran' => 0,
            'metode_pembayaran' => 'Transfer Bank',
            'status_pembayaran' => 'Menunggu Verifikasi',
            'bukti_pembayaran' => $storedPath,
            'waktu_unggah_bukti' => now(),
        ]);

        $otherUser = User::where('username', 'budi')->first(); // Pelanggan 2

        $response = $this->actingAs($otherUser)->get(route('pembayaran.bukti', 'TRX-20261015-001'));
        $response->assertStatus(403);
    }

    /**
     * Membuka halaman instruksi tidak mengubah status pembayaran menjadi lunas
     */
    public function test_viewing_payment_page_does_not_change_payment_status(): void
    {
        $user = User::where('username', 'pelanggan')->first();

        $penyewaanBefore = Penyewaan::find('TRX-20261015-001');
        $this->assertEquals('Belum Dibayar', $penyewaanBefore->status_pembayaran);

        $response = $this->actingAs($user)->get(route('pengguna.pembayaran.qris', 'TRX-20261015-001'));
        $response->assertStatus(200);

        $penyewaanAfter = Penyewaan::find('TRX-20261015-001');
        $this->assertEquals('Belum Dibayar', $penyewaanAfter->status_pembayaran);
    }

    /**
     * Pengguna tidak dapat melihat transaksi milik pengguna lain (IDOR Protection)
     */
    public function test_pengguna_cannot_view_other_users_transaction_idor(): void
    {
        $user = User::where('username', 'pelanggan')->first(); // Pelanggan 1 (Andi)

        // TRX-20261015-999 adalah milik Pelanggan 2 (Budi)
        $response = $this->actingAs($user)->get(route('pengguna.pembayaran.qris', 'TRX-20261015-999'));

        $response->assertStatus(403);
    }

    /**
     * Instruksi pembayaran hanya dapat ditampilkan untuk pesanan yang telah disetujui
     */
    public function test_cannot_view_payment_instructions_for_unapproved_order(): void
    {
        $user = User::where('username', 'pelanggan')->first();

        // TRX-20261015-002 masih berstatus 'Menunggu Konfirmasi' (belum disetujui Admin)
        $response = $this->actingAs($user)->get(route('pengguna.pembayaran.qris', 'TRX-20261015-002'));

        $response->assertStatus(403);
    }

    /**
     * Tamu yang belum login diarahkan ke halaman login
     */
    public function test_unauthenticated_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('pengguna.pembayaran.qris', 'TRX-20261015-001'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Role Admin atau Pemilik tidak dapat mengakses rute instruksi pembayaran pengguna
     */
    public function test_admin_cannot_access_pengguna_payment_page(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('pengguna.pembayaran.qris', 'TRX-20261015-001'));

        $response->assertStatus(403);
    }
}

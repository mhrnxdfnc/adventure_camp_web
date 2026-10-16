<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pembayaran;
use App\Models\Penyewaan;
use App\Models\User;
use Tests\TestCase;

class ActivityLogRecordingTest extends TestCase
{
    /**
     * Persetujuan pesanan oleh Admin tercatat di audit trail dengan identitas pelaku dan modul Penyewaan
     */
    public function test_order_approval_is_recorded_in_audit_trail(): void
    {
        $admin = User::where('username', 'admin')->first();

        // TRX-20261015-002 berstatus 'Menunggu Konfirmasi'
        $response = $this->actingAs($admin)->post(route('admin.pesanan.setujui', 'TRX-20261015-002'));

        $response->assertSessionHas('success');

        $trx = Penyewaan::where('no_transaksi', 'TRX-20261015-002')->first();
        $this->assertEquals('Menunggu Pembayaran', $trx->status_penyewaan);

        $log = ActivityLog::where('aktivitas', 'Persetujuan Pesanan')
            ->where('modul', 'Penyewaan')
            ->latest('id_log')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($admin->id_user, $log->id_user);
        $this->assertStringContainsString('TRX-20261015-002', $log->data_diproses);
        $this->assertNotNull($log->waktu);
    }

    /**
     * Penolakan pesanan oleh Admin tercatat di audit trail dengan alasan penolakan
     */
    public function test_order_rejection_is_recorded_in_audit_trail(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Buat pesanan baru berstatus Menunggu Konfirmasi
        $trx = Penyewaan::create([
            'no_transaksi' => 'TRX-TEST-TOLAK',
            'id_pelanggan' => 1,
            'id_user' => 3,
            'tanggal_sewa' => '2026-10-25',
            'tanggal_kembali' => '2026-10-27',
            'status_penyewaan' => 'Menunggu Konfirmasi',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pesanan.tolak', $trx->no_transaksi), [
            'alasan_penolakan' => 'Stok alat camping tidak tersedia pada tanggal tersebut.',
        ]);

        $response->assertSessionHas('success');

        $trx->refresh();
        $this->assertEquals('Ditolak', $trx->status_penyewaan);

        $log = ActivityLog::where('aktivitas', 'Penolakan Pesanan')
            ->where('modul', 'Penyewaan')
            ->latest('id_log')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($admin->id_user, $log->id_user);
        $this->assertStringContainsString('TRX-TEST-TOLAK', $log->data_diproses);
        $this->assertStringContainsString('Stok alat camping tidak tersedia', $log->data_diproses);
    }

    /**
     * Pengguna dan Pemilik tidak dapat menyetujui atau menolak pesanan
     */
    public function test_non_admin_cannot_approve_or_reject_orders(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        $pemilik = User::where('username', 'pemilik')->first();

        $res1 = $this->actingAs($pengguna)->post(route('admin.pesanan.setujui', 'TRX-20261015-002'));
        $res1->assertStatus(403);

        $res2 = $this->actingAs($pemilik)->post(route('admin.pesanan.tolak', 'TRX-20261015-002'), [
            'alasan_penolakan' => 'Mencoba menolak secara ilegal',
        ]);
        $res2->assertStatus(403);
    }

    /**
     * Konfirmasi pembayaran lunas tercatat di audit trail
     */
    public function test_payment_confirmation_is_recorded_in_audit_trail(): void
    {
        $admin = User::where('username', 'admin')->first();

        $trx = Penyewaan::where('no_transaksi', 'TRX-20261015-001')->first();

        $response = $this->actingAs($admin)->post(route('admin.pembayaran.konfirmasi', $trx->no_transaksi), [
            'jumlah_diterima' => 200000,
            'metode_pembayaran' => 'Cash',
        ]);

        $response->assertRedirect(route('admin.pembayaran.index'));

        $log = ActivityLog::where('aktivitas', 'Konfirmasi Pembayaran Lunas')
            ->where('modul', 'Pembayaran')
            ->latest('id_log')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($admin->id_user, $log->id_user);
        $this->assertStringContainsString('TRX-20261015-001', $log->data_diproses);
        $this->assertStringContainsString('Lunas', $log->data_diproses);
    }

    /**
     * Penolakan bukti pembayaran tercatat di audit trail
     */
    public function test_payment_rejection_is_recorded_in_audit_trail(): void
    {
        $admin = User::where('username', 'admin')->first();

        $trx = Penyewaan::create([
            'no_transaksi' => 'TRX-TEST-BAYAR-TOLAK',
            'id_pelanggan' => 1,
            'id_user' => 3,
            'tanggal_sewa' => '2026-10-25',
            'tanggal_kembali' => '2026-10-27',
            'status_penyewaan' => 'Menunggu Pembayaran',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pembayaran.tolak', $trx->no_transaksi), [
            'alasan_penolakan' => 'Bukti struk tidak jelas dan nomor rekening tujuan berbeda.',
        ]);

        $response->assertRedirect(route('admin.pembayaran.index'));

        $log = ActivityLog::where('aktivitas', 'Penolakan Bukti Pembayaran')
            ->where('modul', 'Pembayaran')
            ->latest('id_log')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($admin->id_user, $log->id_user);
        $this->assertStringContainsString('TRX-TEST-BAYAR-TOLAK', $log->data_diproses);
        $this->assertStringContainsString('Bukti struk tidak jelas', $log->data_diproses);
    }

    /**
     * Serah terima alat (pengambilan) tercatat di audit trail
     */
    public function test_equipment_handover_is_recorded_in_audit_trail(): void
    {
        $admin = User::where('username', 'admin')->first();

        $trx = Penyewaan::create([
            'no_transaksi' => 'TRX-TEST-SERAH',
            'id_pelanggan' => 1,
            'id_user' => 3,
            'tanggal_sewa' => '2026-10-16',
            'tanggal_kembali' => '2026-10-18',
            'status_penyewaan' => 'Disetujui',
        ]);

        DetailPenyewaan::create([
            'no_transaksi' => $trx->no_transaksi,
            'kode_alat' => 'ALT001',
            'jumlah' => 1,
            'tarif_sewa' => 50000,
        ]);

        Pembayaran::create([
            'no_pembayaran' => 'PAY-TEST-SERAH',
            'no_transaksi' => $trx->no_transaksi,
            'total_biaya' => 100000,
            'jumlah_pembayaran' => 100000,
            'metode_pembayaran' => 'Cash',
            'status_pembayaran' => 'Lunas',
            'waktu_konfirmasi' => now(),
            'id_admin' => $admin->id_user,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengambilan', $trx->no_transaksi), [
            'tanggal_pengambilan' => '2026-10-16',
            'nama_penerima' => 'Andi Pratama Sendiri',
            'catatan' => 'Semua pasak tenda lengkap',
            'items' => [
                'ALT001' => ['jumlah_diserahkan' => 1],
            ],
        ]);

        $response->assertRedirect(route('admin.operasional.pengambilan'));

        $log = ActivityLog::where('aktivitas', 'Menyerahkan Alat Camping (Pengambilan)')
            ->where('modul', 'Pengambilan')
            ->latest('id_log')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($admin->id_user, $log->id_user);
        $this->assertStringContainsString('TRX-TEST-SERAH', $log->data_diproses);
        $this->assertStringContainsString('Andi Pratama Sendiri', $log->data_diproses);
    }

    /**
     * Pengembalian, pencatatan denda, dan perubahan kondisi alat tercatat di audit trail
     */
    public function test_return_fine_and_condition_change_are_recorded(): void
    {
        $admin = User::where('username', 'admin')->first();

        $trx = Penyewaan::create([
            'no_transaksi' => 'TRX-TEST-KEMBALI-DENDA',
            'id_pelanggan' => 1,
            'id_user' => 3,
            'tanggal_sewa' => '2026-10-10',
            'tanggal_kembali' => '2026-10-12',
            'status_penyewaan' => 'Sedang Disewa',
        ]);

        DetailPenyewaan::create([
            'no_transaksi' => $trx->no_transaksi,
            'kode_alat' => 'ALT001',
            'jumlah' => 2,
            'jumlah_diserahkan' => 2,
            'tarif_sewa' => 50000,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $trx->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-15',
            'items' => [
                'ALT001' => [
                    'baik' => 1,
                    'rusak' => 1, // 1 unit rusak
                ],
            ],
            'nominal_denda' => 225000,
            'alasan_denda' => 'Terlambat 3 hari dan 1 unit frame tenda patah.',
            'catatan' => 'Denda dibayar tunai di kasir.',
        ]);

        $response->assertRedirect(route('admin.operasional.pengembalian'));

        // 1. Audit log Denda
        $logDenda = ActivityLog::where('aktivitas', 'Pencatatan Denda')
            ->where('modul', 'Denda')
            ->latest('id_log')
            ->first();
        $this->assertNotNull($logDenda);
        $this->assertEquals($admin->id_user, $logDenda->id_user);
        $this->assertStringContainsString('225.000', $logDenda->data_diproses);
        $this->assertStringContainsString('Terlambat 3 hari', $logDenda->data_diproses);

        // 2. Audit log Perubahan Kondisi Alat
        $logKondisi = ActivityLog::where('aktivitas', 'Perubahan Kondisi Alat')
            ->where('modul', 'Inventaris')
            ->latest('id_log')
            ->first();
        $this->assertNotNull($logKondisi);
        $this->assertStringContainsString('1 Rusak', $logKondisi->data_diproses);

        // 3. Audit log Pengembalian
        $logKembali = ActivityLog::where('aktivitas', 'Memproses Pengembalian Alat')
            ->where('modul', 'Pengembalian')
            ->latest('id_log')
            ->first();
        $this->assertNotNull($logKembali);
        $this->assertStringContainsString('TRX-TEST-KEMBALI-DENDA', $logKembali->data_diproses);
    }

    /**
     * Perubahan stok manual tercatat di audit trail
     */
    public function test_stock_adjustment_is_recorded_in_audit_trail(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.inventaris.adjust-stock', 'ALT001'), [
            'tipe_penyesuaian' => 'tambah',
            'jumlah' => 3,
            'alasan' => 'Restock unit baru dari cabang lain',
        ]);

        $response->assertRedirect(route('admin.inventaris.show', 'ALT001'));

        $log = ActivityLog::where('aktivitas', 'Penambahan Manual')
            ->where('modul', 'Inventaris')
            ->latest('id_log')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($admin->id_user, $log->id_user);
        $this->assertStringContainsString('+3', $log->data_diproses);
        $this->assertStringContainsString('Restock unit baru', $log->data_diproses);
    }

    /**
     * Perubahan kondisi alat pada master data alat tercatat di audit trail
     */
    public function test_alat_condition_change_in_master_is_recorded(): void
    {
        $admin = User::where('username', 'admin')->first();

        $alat = Alat::where('kode_alat', 'ALT001')->first();
        $this->assertEquals('Baik', $alat->kondisi);

        $response = $this->actingAs($admin)->put(route('admin.alat.update', 'ALT001'), [
            'nama_alat' => $alat->nama_alat,
            'kategori' => $alat->kategori,
            'stok' => $alat->stok,
            'kondisi' => 'Perlu Perbaikan',
            'tarif_sewa' => $alat->tarif_sewa,
        ]);

        $response->assertRedirect(route('admin.alat.index'));

        $log = ActivityLog::where('aktivitas', 'Perubahan Kondisi Alat')
            ->where('modul', 'Alat')
            ->latest('id_log')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Baik -> Perlu Perbaikan', $log->data_diproses);
    }

    /**
     * Sensor otomatis data sensitif: password, token, kredensial, dsb. tidak pernah disimpan mentah
     */
    public function test_sensitive_data_is_never_stored_in_logs(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Array with sensitive keys
        $log1 = ActivityLog::record(
            $admin->id_user,
            'Uji Keamanan Array',
            'Keamanan',
            [
                'username' => 'andi',
                'password' => 'SuperSecret123!',
                'password_confirmation' => 'SuperSecret123!',
                'token' => 'jwt-token-abcdef123456',
                'api_key' => 'live_secret_key_888',
                'remember_token' => 'rem_abc123',
                'action' => 'user_auth',
            ]
        );

        $this->assertNotNull($log1);
        $this->assertStringNotContainsString('SuperSecret123!', $log1->data_diproses);
        $this->assertStringNotContainsString('jwt-token-abcdef123456', $log1->data_diproses);
        $this->assertStringNotContainsString('live_secret_key_888', $log1->data_diproses);
        $this->assertStringContainsString('***REDACTED***', $log1->data_diproses);

        // 2. String with sensitive patterns
        $log2 = ActivityLog::record(
            $admin->id_user,
            'Uji Keamanan String',
            'Keamanan',
            'Login attempt with password: SecretPass! and token: my-secret-token'
        );

        $this->assertNotNull($log2);
        $this->assertStringNotContainsString('SecretPass!', $log2->data_diproses);
        $this->assertStringNotContainsString('my-secret-token', $log2->data_diproses);
        $this->assertStringContainsString('***REDACTED***', $log2->data_diproses);
    }

    /**
     * Log tidak dapat diubah (update) atau dihapus (delete) melalui model (Append-Only)
     */
    public function test_logs_are_immutable_and_cannot_be_updated_or_deleted(): void
    {
        $log = ActivityLog::record(1, 'Log Uji Immutability', 'Uji', 'Data awal yang sah');
        $this->assertNotNull($log);

        // Percobaan Update harus gagal / dicegah
        $updateResult = $log->update(['aktivitas' => 'Data Telah Dipalsukan']);
        $this->assertFalse($updateResult);

        $log->refresh();
        $this->assertEquals('Log Uji Immutability', $log->aktivitas);

        // Percobaan Delete harus gagal / dicegah
        $deleteResult = $log->delete();
        $this->assertFalse($deleteResult);

        $this->assertNotNull(ActivityLog::find($log->id_log));
    }

    /**
     * Kegagalan log tidak menyebabkan transaksi bisnis utama menjadi gagal/inkonsisten
     */
    public function test_logging_failure_does_not_break_business_flow(): void
    {
        // ActivityLog::record menangani error dengan aman
        $result = ActivityLog::record(-999, '', '', null);
        // Method mengembalikan null atau instance, tidak melempar uncaught exception
        $this->assertTrue($result === null || $result instanceof ActivityLog);
    }
}

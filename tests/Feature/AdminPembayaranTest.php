<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Penyewaan;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPembayaranTest extends TestCase
{
    /**
     * Admin dapat melihat daftar pembayaran yang menunggu verifikasi
     */
    public function test_admin_can_view_payment_verification_list(): void
    {
        $admin = User::where('username', 'admin')->first();

        Pembayaran::create([
            'no_pembayaran' => 'PAY-20261016-001',
            'no_transaksi' => 'TRX-20261015-001',
            'total_biaya' => 200000,
            'jumlah_pembayaran' => 0,
            'metode_pembayaran' => 'Transfer Bank',
            'status_pembayaran' => 'Menunggu Verifikasi',
            'bukti_pembayaran' => 'bukti_pembayaran/dummy.jpg',
            'waktu_unggah_bukti' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pembayaran.index'));

        $response->assertStatus(200);
        $response->assertSee('Verifikasi Pembayaran');
        $response->assertSee('TRX-20261015-001');
        $response->assertSee('Andi Pratama');
        $response->assertSee('Transfer Bank');
        $response->assertSee('Rp 200.000');
        $response->assertSee('Menunggu Verifikasi');
    }

    /**
     * Admin dapat memfilter daftar pembayaran berdasarkan status dan metode
     */
    public function test_admin_can_filter_payments_by_status_and_method(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Buat satu pembayaran Lunas
        Pembayaran::create([
            'no_pembayaran' => 'PAY-20261016-001',
            'no_transaksi' => 'TRX-20261015-001',
            'total_biaya' => 200000,
            'jumlah_pembayaran' => 200000,
            'metode_pembayaran' => 'Transfer Bank',
            'status_pembayaran' => 'Lunas',
            'id_admin' => $admin->id_user,
            'waktu_konfirmasi' => now(),
        ]);

        // Filter tab Lunas
        $responseLunas = $this->actingAs($admin)->get(route('admin.pembayaran.index', ['status' => 'Lunas']));
        $responseLunas->assertStatus(200);
        $responseLunas->assertSee('TRX-20261015-001');

        // Filter tab Menunggu Verifikasi (TRX-001 tidak boleh muncul di tab ini)
        $responseMenunggu = $this->actingAs($admin)->get(route('admin.pembayaran.index', ['status' => 'Menunggu Verifikasi']));
        $responseMenunggu->assertStatus(200);
        $responseMenunggu->assertDontSee('PAY-20261016-001');
    }

    /**
     * Admin dapat melihat halaman detail rincian transaksi pemeriksaan pembayaran
     */
    public function test_admin_can_view_payment_detail_show_page(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.pembayaran.show', 'TRX-20261015-001'));

        $response->assertStatus(200);
        $response->assertSee('TRX-20261015-001');
        $response->assertSee('Tenda Dome 4 Orang');
        $response->assertSee('Sleeping Bag');
        $response->assertSee('Rp 200.000');
    }

    /**
     * Admin dapat mengonfirmasi pembayaran dan status berubah menjadi Lunas
     */
    public function test_admin_can_confirm_payment_successfully(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.pembayaran.konfirmasi', 'TRX-20261015-001'), [
            'jumlah_diterima' => 200000,
            'metode_pembayaran' => 'Transfer Bank',
        ]);

        $response->assertRedirect(route('admin.pembayaran.index'));
        $response->assertSessionHas('success');

        $pembayaran = Pembayaran::where('no_transaksi', 'TRX-20261015-001')->first();
        $this->assertNotNull($pembayaran);
        $this->assertEquals('Lunas', $pembayaran->status_pembayaran);
        $this->assertEquals(200000, (float) $pembayaran->jumlah_pembayaran);
        $this->assertEquals($admin->id_user, $pembayaran->id_admin);
        $this->assertNotNull($pembayaran->waktu_konfirmasi);
        $this->assertEquals('Transfer Bank', $pembayaran->metode_pembayaran);

        // Status penyewaan diperbarui
        $penyewaan = Penyewaan::find('TRX-20261015-001');
        $this->assertEquals('Siap Diambil', $penyewaan->status_penyewaan);

        // Periksa audit trail tercatat
        if (Schema::hasTable('activity_logs')) {
            $this->assertDatabaseHas('activity_logs', [
                'id_user' => $admin->id_user,
                'aktivitas' => 'Konfirmasi Pembayaran Lunas',
                'modul' => 'Pembayaran',
            ]);
        }
    }

    /**
     * Sistem mencegah pembayaran sebagian (parsial) dianggap sebagai Lunas
     */
    public function test_partial_payment_cannot_be_marked_as_lunas(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Total tagihan adalah 200.000, dikirim hanya 100.000
        $response = $this->actingAs($admin)->post(route('admin.pembayaran.konfirmasi', 'TRX-20261015-001'), [
            'jumlah_diterima' => 100000,
            'metode_pembayaran' => 'Transfer Bank',
        ]);

        $response->assertSessionHasErrors(['jumlah_diterima']);

        // Pastikan status tidak berubah menjadi Lunas
        $penyewaan = Penyewaan::find('TRX-20261015-001');
        $this->assertNotEquals('Lunas', $penyewaan->status_pembayaran);
    }

    /**
     * Konfirmasi pembayaran QRIS kasir dan Tunai (Cash) ditangani dengan benar
     */
    public function test_admin_can_confirm_qris_and_cash_payments(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Konfirmasi QRIS
        $responseQris = $this->actingAs($admin)->post(route('admin.pembayaran.konfirmasi', 'TRX-20261015-001'), [
            'jumlah_diterima' => 200000,
            'metode_pembayaran' => 'QRIS',
        ]);
        $responseQris->assertRedirect(route('admin.pembayaran.index'));

        $pembayaranQris = Pembayaran::where('no_transaksi', 'TRX-20261015-001')->first();
        $this->assertEquals('QRIS', $pembayaranQris->metode_pembayaran);
        $this->assertEquals('Lunas', $pembayaranQris->status_pembayaran);

        // 2. Konfirmasi Cash untuk TRX-20261015-999 (Customer 2)
        // Total tagihan: 1 x ALT003 (40.000) * 2 hari = 80.000
        $penyewaan999 = Penyewaan::find('TRX-20261015-999');
        $total999 = $penyewaan999->total_biaya;

        $responseCash = $this->actingAs($admin)->post(route('admin.pembayaran.konfirmasi', 'TRX-20261015-999'), [
            'jumlah_diterima' => $total999,
            'metode_pembayaran' => 'Cash',
        ]);
        $responseCash->assertRedirect(route('admin.pembayaran.index'));

        $pembayaranCash = Pembayaran::where('no_transaksi', 'TRX-20261015-999')->first();
        $this->assertEquals('Cash', $pembayaranCash->metode_pembayaran);
        $this->assertEquals('Lunas', $pembayaranCash->status_pembayaran);
        $this->assertEquals($total999, (float) $pembayaranCash->jumlah_pembayaran);
    }

    /**
     * Konfirmasi ganda dicegah sehingga nominal pembayaran tidak tercatat dua kali
     */
    public function test_double_confirmation_is_prevented_safely(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Konfirmasi pertama
        $this->actingAs($admin)->post(route('admin.pembayaran.konfirmasi', 'TRX-20261015-001'), [
            'jumlah_diterima' => 200000,
            'metode_pembayaran' => 'Transfer Bank',
        ]);

        $pembayaranFirst = Pembayaran::where('no_transaksi', 'TRX-20261015-001')->first();
        $this->assertEquals('Lunas', $pembayaranFirst->status_pembayaran);
        $this->assertEquals(200000, (float) $pembayaranFirst->jumlah_pembayaran);

        // Percobaan konfirmasi kedua (misal double-click / repeat request)
        $responseSecond = $this->actingAs($admin)->post(route('admin.pembayaran.konfirmasi', 'TRX-20261015-001'), [
            'jumlah_diterima' => 200000,
            'metode_pembayaran' => 'Transfer Bank',
        ]);

        $responseSecond->assertSessionHas('error');

        // Nominal tetap 200.000, tidak berlipat ganda menjadi 400.000
        $pembayaranSecond = Pembayaran::where('no_transaksi', 'TRX-20261015-001')->first();
        $this->assertEquals(200000, (float) $pembayaranSecond->jumlah_pembayaran);
        $this->assertEquals(1, Pembayaran::where('no_transaksi', 'TRX-20261015-001')->count());
    }

    /**
     * Admin dapat menolak pembayaran dengan alasan wajib
     */
    public function test_admin_can_reject_payment_with_required_reason(): void
    {
        $admin = User::where('username', 'admin')->first();

        Pembayaran::create([
            'no_pembayaran' => 'PAY-20261016-001',
            'no_transaksi' => 'TRX-20261015-001',
            'total_biaya' => 200000,
            'jumlah_pembayaran' => 0,
            'metode_pembayaran' => 'Transfer Bank',
            'status_pembayaran' => 'Menunggu Verifikasi',
            'bukti_pembayaran' => 'bukti_pembayaran/dummy.jpg',
            'waktu_unggah_bukti' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pembayaran.tolak', 'TRX-20261015-001'), [
            'alasan_penolakan' => 'Bukti transfer tidak jelas dan nominal tidak sesuai rekening koran.',
        ]);

        $response->assertRedirect(route('admin.pembayaran.index'));
        $response->assertSessionHas('success');

        $pembayaran = Pembayaran::where('no_transaksi', 'TRX-20261015-001')->first();
        $this->assertEquals('Ditolak', $pembayaran->status_pembayaran);
        $this->assertEquals('Bukti transfer tidak jelas dan nominal tidak sesuai rekening koran.', $pembayaran->alasan_penolakan);
        $this->assertEquals($admin->id_user, $pembayaran->id_admin);
        $this->assertNotNull($pembayaran->waktu_konfirmasi);

        // Periksa audit trail
        if (Schema::hasTable('activity_logs')) {
            $this->assertDatabaseHas('activity_logs', [
                'id_user' => $admin->id_user,
                'aktivitas' => 'Penolakan Bukti Pembayaran',
                'modul' => 'Pembayaran',
            ]);
        }
    }

    /**
     * Penolakan pembayaran wajib menyertakan alasan penolakan
     */
    public function test_rejection_requires_valid_reason(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.pembayaran.tolak', 'TRX-20261015-001'), [
            'alasan_penolakan' => '', // Kosong
        ]);

        $response->assertSessionHasErrors(['alasan_penolakan']);
    }

    /**
     * Pengguna dan pemilik tidak dapat mengakses halaman dan aksi verifikasi pembayaran admin
     */
    public function test_non_admin_cannot_access_payment_verification_actions(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        $pemilik = User::where('username', 'pemilik')->first();

        // 1. Pengguna
        $this->actingAs($pengguna)->get(route('admin.pembayaran.index'))->assertStatus(403);
        $this->actingAs($pengguna)->post(route('admin.pembayaran.konfirmasi', 'TRX-20261015-001'), [
            'jumlah_diterima' => 200000,
        ])->assertStatus(403);

        // 2. Pemilik (Read-only)
        $this->actingAs($pemilik)->get(route('admin.pembayaran.index'))->assertStatus(403);
        $this->actingAs($pemilik)->post(route('admin.pembayaran.konfirmasi', 'TRX-20261015-001'), [
            'jumlah_diterima' => 200000,
        ])->assertStatus(403);
    }

    /**
     * Tamu diarahkan ke halaman login
     */
    public function test_unauthenticated_guest_is_redirected_to_login(): void
    {
        auth()->logout();
        $this->get(route('admin.pembayaran.index'))->assertRedirect(route('login'));
    }
}

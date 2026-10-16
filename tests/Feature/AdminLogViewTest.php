<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Tests\TestCase;

class AdminLogViewTest extends TestCase
{
    /**
     * Admin dapat mengakses halaman riwayat audit trail log aktivitas
     */
    public function test_admin_can_access_audit_trail_page(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Buat log aktivitas uji coba
        ActivityLog::record(
            $admin->id_user,
            'Pengujian Akses Audit Trail',
            'Penyewaan',
            'No Transaksi: TRX-TEST-LOG-001'
        );

        $response = $this->actingAs($admin)->get(route('admin.logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Audit Trail');
        $response->assertSee('Total Log Aktivitas');
        $response->assertSee('Pengujian Akses Audit Trail');
        $response->assertSee('TRX-TEST-LOG-001');
    }

    /**
     * Filter modul pada halaman audit trail berfungsi menyaring aktivitas
     */
    public function test_admin_can_filter_audit_logs_by_module(): void
    {
        $admin = User::where('username', 'admin')->first();

        ActivityLog::record($admin->id_user, 'Aksi Modul Pembayaran', 'Pembayaran', 'Data Pembayaran Test');
        ActivityLog::record($admin->id_user, 'Aksi Modul Inventaris', 'Inventaris', 'Data Inventaris Test');

        // Filter modul Pembayaran
        $response = $this->actingAs($admin)->get(route('admin.logs.index', ['modul' => 'Pembayaran']));

        $response->assertStatus(200);
        $response->assertSee('Aksi Modul Pembayaran');
        $response->assertDontSee('Aksi Modul Inventaris');
    }

    /**
     * Pencarian kata kunci menyaring aktivitas berdasarkan query
     */
    public function test_admin_can_search_audit_logs(): void
    {
        $admin = User::where('username', 'admin')->first();

        ActivityLog::record($admin->id_user, 'Operasi Khusus Alpha', 'Pengambilan', 'KunciUnikXYZ123');
        ActivityLog::record($admin->id_user, 'Operasi Biasa Beta', 'Pengambilan', 'KunciBiasa456');

        $response = $this->actingAs($admin)->get(route('admin.logs.index', ['search' => 'KunciUnikXYZ123']));

        $response->assertStatus(200);
        $response->assertSee('Operasi Khusus Alpha');
        $response->assertDontSee('Operasi Biasa Beta');
    }

    /**
     * Pengguna (Pelanggan) tidak memiliki hak akses ke audit trail log (403 Forbidden)
     */
    public function test_pengguna_cannot_access_audit_logs(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($pengguna)->get(route('admin.logs.index'));

        $response->assertStatus(403);
    }

    /**
     * Pemilik tidak memiliki hak akses ke halaman audit trail Admin (403 Forbidden)
     */
    public function test_pemilik_cannot_access_admin_audit_logs(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        $response = $this->actingAs($pemilik)->get(route('admin.logs.index'));

        $response->assertStatus(403);
    }

    /**
     * Pengunjung anonim (guest) dialihkan ke halaman login
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.logs.index'));

        $response->assertRedirect('/login');
    }

    /**
     * Tidak ada endpoint untuk memodifikasi atau menghapus catatan audit trail (Strictly Read-Only)
     */
    public function test_audit_logs_have_no_mutation_routes(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Coba kirim POST ke endpoint logs
        $postRes = $this->actingAs($admin)->post('/admin/logs', [
            'aktivitas' => 'Palsu',
        ]);
        $this->assertContains($postRes->getStatusCode(), [404, 405]);

        // Coba kirim DELETE ke endpoint logs
        $delRes = $this->actingAs($admin)->delete('/admin/logs/1');
        $this->assertContains($delRes->getStatusCode(), [404, 405]);

        // Coba kirim PUT ke endpoint logs
        $putRes = $this->actingAs($admin)->put('/admin/logs/1', [
            'aktivitas' => 'Diubah',
        ]);
        $this->assertContains($putRes->getStatusCode(), [404, 405]);
    }
}

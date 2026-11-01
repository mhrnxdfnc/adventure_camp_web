<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Penyewaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanDanNotaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pemilik;

    protected User $pengguna1;

    protected User $pengguna2;

    protected Pelanggan $pelanggan1;

    protected Pelanggan $pelanggan2;

    protected Penyewaan $sewa1;

    protected Penyewaan $sewa2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['username' => 'admin_test'],
            [
                'nama_user' => 'Admin Penguji',
                'password' => 'admin123',
                'role' => 'Admin',
                'status_aktif' => 1,
            ]
        );

        $this->pemilik = User::firstOrCreate(
            ['username' => 'pemilik_test'],
            [
                'nama_user' => 'Pemilik Penguji',
                'password' => 'pemilik123',
                'role' => 'Pemilik',
                'status_aktif' => 1,
            ]
        );

        $this->pelanggan1 = Pelanggan::create([
            'nama_pelanggan' => 'Pelanggan Satu',
            'nomor_telepon' => '081111111111',
            'alamat' => 'Alamat Pelanggan Satu',
            'created_at' => now(),
        ]);

        $this->pengguna1 = User::create([
            'id_pelanggan' => $this->pelanggan1->id_pelanggan,
            'nama_user' => 'Pelanggan Satu',
            'username' => 'user_satu',
            'password' => 'user12345',
            'role' => 'Pengguna',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);

        $this->pelanggan2 = Pelanggan::create([
            'nama_pelanggan' => 'Pelanggan Dua',
            'nomor_telepon' => '082222222222',
            'alamat' => 'Alamat Pelanggan Dua',
            'created_at' => now(),
        ]);

        $this->pengguna2 = User::create([
            'id_pelanggan' => $this->pelanggan2->id_pelanggan,
            'nama_user' => 'Pelanggan Dua',
            'username' => 'user_dua',
            'password' => 'user12345',
            'role' => 'Pengguna',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);

        $alat = Alat::firstOrCreate(
            ['kode_alat' => 'ALT-TEST-01'],
            [
                'nama_alat' => 'Tenda Dome Uji Coba',
                'kategori' => 'Tenda',
                'tarif_sewa' => 50000,
                'stok' => 5,
                'kondisi' => 'Baik',
            ]
        );

        $this->sewa1 = Penyewaan::create([
            'no_transaksi' => 'TRX-LAP-001',
            'id_pelanggan' => $this->pelanggan1->id_pelanggan,
            'id_user' => $this->pengguna1->id_user,
            'tanggal_sewa' => '2026-11-01',
            'tanggal_kembali' => '2026-11-03',
            'total_biaya' => 100000,
            'status_penyewaan' => 'Disetujui',
            'sumber_pesanan' => 'Online',
        ]);

        DetailPenyewaan::create([
            'no_transaksi' => 'TRX-LAP-001',
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
            'tarif_sewa' => 50000,
            'subtotal' => 100000,
        ]);

        Pembayaran::create([
            'no_pembayaran' => 'PAY-LAP-001',
            'no_transaksi' => 'TRX-LAP-001',
            'total_biaya' => 100000,
            'jumlah_pembayaran' => 100000,
            'metode_pembayaran' => 'Transfer Bank',
            'status_pembayaran' => 'Lunas',
            'tanggal_pembayaran' => now(),
            'id_admin' => $this->admin->id_user,
            'waktu_konfirmasi' => now(),
        ]);

        $this->sewa2 = Penyewaan::create([
            'no_transaksi' => 'TRX-LAP-002',
            'id_pelanggan' => $this->pelanggan2->id_pelanggan,
            'id_user' => $this->pengguna2->id_user,
            'tanggal_sewa' => '2026-11-02',
            'tanggal_kembali' => '2026-11-04',
            'total_biaya' => 200000,
            'status_penyewaan' => 'Menunggu Pembayaran',
            'sumber_pesanan' => 'Online',
        ]);

        DetailPenyewaan::create([
            'no_transaksi' => 'TRX-LAP-002',
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 2,
            'tarif_sewa' => 50000,
            'subtotal' => 200000,
        ]);
    }

    /**
     * Uji rute beranda / mengarahkan pengguna sesuai status login dan peran
     */
    public function test_root_route_redirects_by_authentication_status(): void
    {
        // 1. Tamu dialihkan ke login
        $this->get('/')->assertRedirect(route('login'));

        // 2. Admin dialihkan ke dashboard admin
        $this->actingAs($this->admin)->get('/')->assertRedirect(route('admin.dashboard'));

        // 3. Pemilik dialihkan ke dashboard pemilik
        $this->actingAs($this->pemilik)->get('/')->assertRedirect(route('pemilik.dashboard'));

        // 4. Pengguna dialihkan ke katalog
        $this->actingAs($this->pengguna1)->get('/')->assertRedirect(route('pengguna.katalog.index'));
    }

    /**
     * Uji Admin dapat melihat Laporan Transaksi dan Rekapitulasi Kas
     */
    public function test_admin_can_view_transaction_report_with_recap(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.laporan.transaksi'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Transaksi');
        $response->assertSee('Total Nilai Tagihan');
        $response->assertSee('Penerimaan Kas Riil');
        $response->assertSee('TRX-LAP-001');
        $response->assertSee('TRX-LAP-002');
    }

    /**
     * Uji pengurutan dinamis (sort_by dan order) pada Laporan Transaksi
     */
    public function test_transaction_report_sorting(): void
    {
        $responseAsc = $this->actingAs($this->admin)
            ->get(route('admin.laporan.transaksi', ['sort_by' => 'total_biaya', 'order' => 'asc']));
        $responseAsc->assertStatus(200);

        $responseDesc = $this->actingAs($this->admin)
            ->get(route('admin.laporan.transaksi', ['sort_by' => 'total_biaya', 'order' => 'desc']));
        $responseDesc->assertStatus(200);
    }

    /**
     * Uji cetak Laporan Transaksi menghasilkan tampilan cetak resmi lengkap dengan kop
     */
    public function test_transaction_report_print_view(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.laporan.transaksi.cetak'));

        $response->assertStatus(200);
        $response->assertSee('ADVENTURE CAMP RENTAL');
        $response->assertSee('Laporan Transaksi Penyewaan', false);
        $response->assertSee('TRX-LAP-001');
        $response->assertSee('REKAPITULASI LAPORAN');
    }

    /**
     * Uji ekspor CSV Laporan Transaksi menghasilkan stream file CSV UTF-8 yang valid
     */
    public function test_transaction_report_csv_export(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.laporan.transaksi.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));
    }

    /**
     * Uji Admin dapat melihat Laporan Inventaris & Ketersediaan Stok
     */
    public function test_admin_can_view_inventory_report(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.laporan.inventaris'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Inventaris');
        $response->assertSee('Ketersediaan Stok');
        $response->assertSee('ALT-TEST-01');
        $response->assertSee('Tenda Dome Uji Coba');
    }

    /**
     * Uji cetak dan ekspor Laporan Inventaris
     */
    public function test_inventory_report_print_and_export(): void
    {
        $printRes = $this->actingAs($this->admin)->get(route('admin.laporan.inventaris.cetak'));
        $printRes->assertStatus(200);
        $printRes->assertSee('ADVENTURE CAMP RENTAL');
        $printRes->assertSee('Laporan Ketersediaan Stok', false);

        $exportRes = $this->actingAs($this->admin)->get(route('admin.laporan.inventaris.export'));
        $exportRes->assertStatus(200);
        $exportRes->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * Uji Pemilik dapat mengakses Laporan Transaksi dan Inventaris serta Cetak/Ekspor (Read-Only)
     */
    public function test_pemilik_can_access_reports_print_and_export(): void
    {
        $this->actingAs($this->pemilik)
            ->get(route('pemilik.transaksi.index'))
            ->assertStatus(200);

        $this->actingAs($this->pemilik)
            ->get(route('pemilik.transaksi.cetak'))
            ->assertStatus(200);

        $this->actingAs($this->pemilik)
            ->get(route('pemilik.transaksi.export'))
            ->assertStatus(200);

        $this->actingAs($this->pemilik)
            ->get(route('pemilik.inventaris.index'))
            ->assertStatus(200);

        $this->actingAs($this->pemilik)
            ->get(route('pemilik.inventaris.cetak'))
            ->assertStatus(200);

        $this->actingAs($this->pemilik)
            ->get(route('pemilik.inventaris.export'))
            ->assertStatus(200);
    }

    /**
     * Uji Pemilik dapat mengakses Audit Trail Log secara Read-Only
     */
    public function test_pemilik_can_view_audit_trail_logs(): void
    {
        ActivityLog::record(
            $this->admin->id_user,
            'Pencatatan Uji Audit',
            'Pengujian',
            'Informasi Audit'
        );

        $response = $this->actingAs($this->pemilik)->get(route('pemilik.logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Audit Trail Log Aktivitas');
        $response->assertSee('Pencatatan Uji Audit');
    }

    /**
     * Uji Pengguna biasa tidak dapat mengakses rute Laporan Admin maupun Pemilik (403)
     */
    public function test_pengguna_cannot_access_reports_and_logs(): void
    {
        $this->actingAs($this->pengguna1)->get(route('admin.laporan.transaksi'))->assertStatus(403);
        $this->actingAs($this->pengguna1)->get(route('admin.laporan.inventaris'))->assertStatus(403);
        $this->actingAs($this->pengguna1)->get(route('pemilik.transaksi.index'))->assertStatus(403);
        $this->actingAs($this->pengguna1)->get(route('pemilik.inventaris.index'))->assertStatus(403);
        $this->actingAs($this->pengguna1)->get(route('pemilik.logs.index'))->assertStatus(403);
    }

    /**
     * Uji Cetak Nota Resmi Transaksi oleh Admin, Pemilik, dan Pelanggan Pemilik
     */
    public function test_receipt_nota_generation_and_access_control(): void
    {
        // 1. Admin dapat mencetak nota transaksi manapun
        $adminRes = $this->actingAs($this->admin)->get(route('transaksi.nota', 'TRX-LAP-001'));
        $adminRes->assertStatus(200);
        $adminRes->assertSee('NOTA TRANSAKSI SEWA');
        $adminRes->assertSee('TRX-LAP-001');
        $adminRes->assertSee('Pelanggan Satu');

        // 2. Pemilik dapat mencetak nota transaksi untuk audit
        $pemilikRes = $this->actingAs($this->pemilik)->get(route('pemilik.transaksi.nota', 'TRX-LAP-001'));
        $pemilikRes->assertStatus(200);
        $pemilikRes->assertSee('TRX-LAP-001');

        // 3. Pengguna Satu dapat mencetak notanya sendiri
        $ownerRes = $this->actingAs($this->pengguna1)->get(route('pengguna.pesanan.nota', 'TRX-LAP-001'));
        $ownerRes->assertStatus(200);
        $ownerRes->assertSee('TRX-LAP-001');

        // 4. Pengguna Dua DITOLAK (403) ketika mencoba mencetak nota milik Pengguna Satu
        $otherRes = $this->actingAs($this->pengguna2)->get(route('transaksi.nota', 'TRX-LAP-001'));
        $otherRes->assertStatus(403);
    }

    /**
     * Uji aktivitas login dan logout tercatat di tabel audit trail
     */
    public function test_auth_actions_are_logged_to_audit_trail(): void
    {
        // Login
        $this->post('/login', [
            'username' => 'admin_test',
            'password' => 'admin123',
            'role' => 'Admin',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('activity_logs', [
            'id_user' => $this->admin->id_user,
            'aktivitas' => 'Login Sistem',
            'modul' => 'Autentikasi',
        ]);

        // Logout
        $this->actingAs($this->admin)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertDatabaseHas('activity_logs', [
            'id_user' => $this->admin->id_user,
            'aktivitas' => 'Logout Sistem',
            'modul' => 'Autentikasi',
        ]);
    }
}

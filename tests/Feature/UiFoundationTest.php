<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class UiFoundationTest extends TestCase
{
    /**
     * Uji redirect root ke halaman login bagi pengunjung umum
     */
    public function test_root_redirects_to_ui_showcase(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    /**
     * Uji halaman login ter-render dengan komponen lengkap
     */
    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Adventure Camp');
        $response->assertSee('Login Sebagai');
        $response->assertSee('Admin (Operasional');
        $response->assertSee('Pengguna (Penyewa Alat Camping)');
        $response->assertSee('Pemilik (Pengawasan Bisnis Read-Only)');
        $response->assertSee('Username');
        $response->assertSee('Password');
        $response->assertSee('Masuk ke Dashboard');
    }

    /**
     * Uji halaman showcase antarmuka UI dan seluruh komponen
     */
    public function test_ui_showcase_renders_all_components(): void
    {
        $response = $this->get('/ui-showcase');

        $response->assertStatus(200);
        $response->assertSee('Showcase Komponen UI');
        // Status badges
        $response->assertSee('Menunggu Konfirmasi');
        $response->assertSee('Disetujui');
        $response->assertSee('Sedang Disewa');
        $response->assertSee('Selesai');
        $response->assertSee('Belum Dibayar');
        $response->assertSee('Lunas');
        $response->assertSee('Baik');
        $response->assertSee('Perlu Perbaikan');
        $response->assertSee('Rusak');
        // Alerts & States
        $response->assertSee('Validasi Gagal');
        $response->assertSee('Data Kosong');
        $response->assertSee('Loading Skeleton');
        $response->assertSee('Error Handler');
        // Table
        $response->assertSee('Komponen Data Table');
    }

    /**
     * Uji layout dashboard Admin
     */
    public function test_admin_dashboard_renders_correctly(): void
    {
        $admin = User::where('username', 'admin')->first();
        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Admin');
        $response->assertSee('Pesanan Masuk');
        $response->assertSee('Verifikasi Pembayaran');
        $response->assertSee('Sedang Disewa');
        $response->assertSee('Stok Fisik Alat');
        $response->assertSee('Pedoman Operasional Admin');
    }

    /**
     * Uji layout dashboard Pengguna
     */
    public function test_pengguna_dashboard_renders_correctly(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        $response = $this->actingAs($pengguna)->get('/pengguna/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Pengguna');
        $response->assertSee('Halo,');
        $response->assertSee('Pesanan Aktif Saya');
        $response->assertSee('Tagihan Menunggu Pembayaran');
        $response->assertSee('TRX-20261015-001');
    }

    /**
     * Uji layout dashboard Pemilik (Strictly Read-Only)
     */
    public function test_pemilik_dashboard_renders_strictly_read_only(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();
        $response = $this->actingAs($pemilik)->get('/pemilik/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Pemilik');
        $response->assertSee('Strictly Read-Only');
        $response->assertSee('Penjualan Hari Ini');
        $response->assertSee('Pendapatan Hari Ini');
        $response->assertSee('Penjualan Bulan Ini');
        $response->assertSee('Pendapatan Bulan Ini');
        $response->assertSee('Riwayat Pesanan Pelanggan (Pengawasan Pemilik)');
        $response->assertSee('Laporan Alat Rusak');
    }

    /**
     * Uji antarmuka bersih dari teks teknis internal dan status badge dapat digunakan visual-only
     */
    public function test_ui_clean_from_internal_technical_documentation_and_status_badge_visual_only(): void
    {
        $response = $this->get('/ui-showcase');
        $content = $response->getContent();

        // Tidak boleh menampilkan teks dokumentasi internal atau metadata tugas
        $this->assertStringNotContainsString('CONTEXT.md', $content);
        $this->assertStringNotContainsString('Arsitektur Laravel Monolith', $content);
        $this->assertStringNotContainsString('Laragon Environment', $content);
        $this->assertStringNotContainsString('Tahap 2', $content);
        $this->assertStringNotContainsString('(422 Unprocessable Entity)', $content);

        // Uji rendering komponen visual-only status-badge
        $renderedVisual = (string) $this->blade('<x-status-badge status="Baik" :visual-only="true" />');
        $this->assertStringContainsString('ac-badge-visual-only', $renderedVisual);
        $this->assertStringContainsString('title="Baik"', $renderedVisual);
        $this->assertStringNotContainsString('<span>Baik</span>', $renderedVisual);
    }
}

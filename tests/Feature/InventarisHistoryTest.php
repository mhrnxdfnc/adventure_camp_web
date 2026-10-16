<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\RiwayatStok;
use App\Models\User;
use Tests\TestCase;

class InventarisHistoryTest extends TestCase
{
    /**
     * Penyesuaian stok mencatat riwayat perubahan stok secara akurat
     */
    public function test_stock_adjustment_creates_riwayat_stok_entry(): void
    {
        $admin = User::where('username', 'admin')->first();
        $alat = Alat::find('ALT001');
        $stokAwal = (int) $alat->stok;

        $this->actingAs($admin)->post(route('admin.inventaris.adjust-stock', 'ALT001'), [
            'tipe_penyesuaian' => 'tambah',
            'jumlah' => 4,
            'alasan' => 'Restok tambahan 4 unit tenda dari gudang.',
        ]);

        $this->assertDatabaseHas('riwayat_stok', [
            'kode_alat' => 'ALT001',
            'jenis_perubahan' => 'Penambahan Manual',
            'stok_sebelum' => $stokAwal,
            'jumlah_perubahan' => 4,
            'stok_sesudah' => $stokAwal + 4,
            'alasan' => 'Restok tambahan 4 unit tenda dari gudang.',
            'id_user' => $admin->id_user,
        ]);
    }

    /**
     * Halaman detail alat menampilkan tabel riwayat mutasi stok alat tersebut
     */
    public function test_inventory_detail_displays_tool_stock_history(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Buat mutasi stok
        RiwayatStok::recordChange(
            'ALT001',
            'Penambahan Manual',
            5,
            3,
            8,
            'Pencatatan unit baru display toko',
            $admin->id_user
        );

        $response = $this->actingAs($admin)->get(route('admin.inventaris.show', 'ALT001'));

        $response->assertStatus(200);
        $response->assertSee('Riwayat Perubahan Stok Alat Ini');
        $response->assertSee('Pencatatan unit baru display toko');
        $response->assertSee('+3');
        $response->assertSee('8 unit');
    }

    /**
     * Admin dapat melihat halaman riwayat perubahan stok global
     */
    public function test_admin_can_view_global_stock_history_page(): void
    {
        $admin = User::where('username', 'admin')->first();

        RiwayatStok::recordChange(
            'ALT002',
            'Pengurangan Manual',
            10,
            -2,
            8,
            'Pembersihan unit rusak berat',
            $admin->id_user
        );

        $response = $this->actingAs($admin)->get(route('admin.inventaris.riwayat'));

        $response->assertStatus(200);
        $response->assertSee('Log Riwayat Perubahan Stok Inventaris');
        $response->assertSee('ALT002');
        $response->assertSee('Pengurangan Manual');
        $response->assertSee('Pembersihan unit rusak berat');
    }

    /**
     * Pengguna dan Pemilik dilarang mengakses halaman riwayat mutasi stok
     */
    public function test_non_admin_cannot_access_stock_history(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        $pemilik = User::where('username', 'pemilik')->first();

        $this->actingAs($pengguna)->get(route('admin.inventaris.riwayat'))->assertStatus(403);
        $this->actingAs($pemilik)->get(route('admin.inventaris.riwayat'))->assertStatus(403);
    }
}

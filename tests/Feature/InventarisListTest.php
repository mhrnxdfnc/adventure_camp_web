<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\User;
use Tests\TestCase;

class InventarisListTest extends TestCase
{
    /**
     * Admin dapat melihat halaman daftar inventaris dengan metrik lengkap
     */
    public function test_admin_can_view_inventory_list(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.inventaris.index'));

        $response->assertStatus(200);
        $response->assertSee('Pemantauan Inventaris');
        $response->assertSee('Total Stok Fisik');
        $response->assertSee('Stok Siap Sewa');
        $response->assertSee('Sedang Disewa');
        $response->assertSee('Rusak / Perlu Servis');
        $response->assertSee('ALT001');
        $response->assertSee('Tenda Dome 4 Orang');
    }

    /**
     * Alat rusak TIDAK dihitung sebagai stok siap sewa
     */
    public function test_damaged_equipment_is_not_counted_as_ready_for_rent_stock(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Buat atau ubah alat menjadi kondisi Rusak tetapi memiliki stok 5 di toko
        $alatRusak = Alat::updateOrCreate(
            ['kode_alat' => 'ALT-TEST-RSK'],
            [
                'nama_alat' => 'Kompor Portable Rusak',
                'kategori' => 'Masak',
                'stok' => 5,
                'kondisi' => 'Rusak',
                'tarif_sewa' => 15000,
            ]
        );

        $response = $this->actingAs($admin)->get(route('admin.inventaris.index'));

        $response->assertStatus(200);
        $response->assertSee('ALT-TEST-RSK');
        $response->assertSee('Kompor Portable Rusak');
        // Stok siap sewa untuk alat rusak harus 0 unit
        $response->assertSee('0 unit');

        // Buka halaman detail inventaris alat rusak
        $detailResponse = $this->actingAs($admin)->get(route('admin.inventaris.show', 'ALT-TEST-RSK'));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('0 (Kondisi Rusak)');
    }

    /**
     * Admin dapat melihat halaman detail inventaris beserta alokasi transaksi aktif
     */
    public function test_admin_can_view_inventory_detail_page(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.inventaris.show', 'ALT001'));

        $response->assertStatus(200);
        $response->assertSee('Tenda Dome 4 Orang (ALT001)');
        $response->assertSee('Kondisi Fisik');
        $response->assertSee('Unit Sedang Dialokasikan / Disewa');
        $response->assertSee('Penyesuaian Stok Fisik Manual');
    }

    /**
     * Pengguna dan Pemilik tidak diizinkan mengakses halaman inventaris Admin
     */
    public function test_non_admin_cannot_access_inventory(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        $pemilik = User::where('username', 'pemilik')->first();

        $this->actingAs($pengguna)->get(route('admin.inventaris.index'))->assertStatus(403);
        $this->actingAs($pengguna)->get(route('admin.inventaris.show', 'ALT001'))->assertStatus(403);

        $this->actingAs($pemilik)->get(route('admin.inventaris.index'))->assertStatus(403);
        $this->actingAs($pemilik)->get(route('admin.inventaris.show', 'ALT001'))->assertStatus(403);
    }
}

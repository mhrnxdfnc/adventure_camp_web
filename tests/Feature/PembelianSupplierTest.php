<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Penyewaan;
use App\Models\Supplier;
use App\Models\User;
use Tests\TestCase;

class PembelianSupplierTest extends TestCase
{
    /**
     * Admin dapat melihat halaman daftar pembelian stok supplier
     */
    public function test_admin_can_view_supplier_purchase_list(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.inventaris.pembelian.index'));

        $response->assertStatus(200);
        $response->assertSee('Riwayat Pembelian Stok dari Supplier');
        $response->assertSee('Catat Pembelian Supplier');
    }

    /**
     * Admin dapat membuka formulir pencatatan pembelian stok supplier
     */
    public function test_admin_can_open_create_purchase_form(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.inventaris.pembelian.create'));

        $response->assertStatus(200);
        $response->assertSee('Pencatatan Pembelian Stok dari Supplier');
        $response->assertSee('Nomor Pembelian / PO');
        $response->assertSee('Data Supplier / Vendor');
        $response->assertSee('Simpan Pembelian Supplier');
    }

    /**
     * Admin dapat mencatat pembelian supplier dengan rincian lengkap dan stok fisik bertambah
     */
    public function test_admin_can_record_supplier_purchase_successfully(): void
    {
        $admin = User::where('username', 'admin')->first();
        $alat1 = Alat::find('ALT001');
        $alat2 = Alat::find('ALT002');
        $stokAwal1 = (int) $alat1->stok;
        $stokAwal2 = (int) $alat2->stok;

        $response = $this->actingAs($admin)->post(route('admin.inventaris.pembelian.store'), [
            'no_pembelian' => 'PO-TEST-20261016-01',
            'tanggal_pembelian' => '2026-10-16',
            'nama_supplier_baru' => 'PT Consina Outdoor Indonesia',
            'telepon_supplier' => '021-88997766',
            'alamat_supplier' => 'Jakarta Selatan',
            'catatan' => 'Faktur pengadaan batch 2026 resmi bergaransi 1 tahun.',
            'items' => [
                [
                    'kode_alat' => 'ALT001',
                    'kuantitas' => 5,
                    'harga_beli_satuan' => 450000,
                ],
                [
                    'kode_alat' => 'ALT002',
                    'kuantitas' => 10,
                    'harga_beli_satuan' => 120000,
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.inventaris.pembelian.index'));
        $response->assertSessionHas('success');

        // Pastikan record pembelian tersimpan di tabel pembelian_inventaris
        $this->assertDatabaseHas('pembelian_inventaris', [
            'no_pembelian' => 'PO-TEST-20261016-01',
            'total_pembelian' => (5 * 450000) + (10 * 120000), // 2.250.000 + 1.200.000 = 3.450.000
            'id_admin' => $admin->id_user,
        ]);

        // Pastikan detail pembelian tersimpan
        $this->assertDatabaseHas('detail_pembelian_inventaris', [
            'no_pembelian' => 'PO-TEST-20261016-01',
            'kode_alat' => 'ALT001',
            'kuantitas' => 5,
            'harga_beli_satuan' => 450000,
        ]);

        // Pastikan supplier baru tersimpan
        $this->assertDatabaseHas('supplier', [
            'nama_supplier' => 'PT Consina Outdoor Indonesia',
        ]);

        // Pastikan stok fisik kedua alat bertambah otomatis
        $alat1->refresh();
        $alat2->refresh();
        $this->assertEquals($stokAwal1 + 5, $alat1->stok);
        $this->assertEquals($stokAwal2 + 10, $alat2->stok);

        // Pastikan tercatat di riwayat mutasi stok
        $this->assertDatabaseHas('riwayat_stok', [
            'kode_alat' => 'ALT001',
            'jenis_perubahan' => 'Pembelian Supplier',
            'jumlah_perubahan' => 5,
            'stok_sesudah' => $stokAwal1 + 5,
        ]);

        // Pastikan tercatat di audit trail
        $this->assertDatabaseHas('activity_logs', [
            'id_user' => $admin->id_user,
            'aktivitas' => 'Mencatat Pembelian Inventaris Supplier',
            'modul' => 'Inventaris',
        ]);
    }

    /**
     * Pembelian supplier tidak mencampur atau mengubah riwayat pesanan sewa pelanggan
     */
    public function test_supplier_purchase_does_not_affect_customer_rentals(): void
    {
        $admin = User::where('username', 'admin')->first();
        $penyewaanAwalCount = Penyewaan::count();

        // Buat supplier
        $supplier = Supplier::create([
            'nama_supplier' => 'CV Mitra Outdoor',
            'telepon' => '0811223344',
        ]);

        $this->actingAs($admin)->post(route('admin.inventaris.pembelian.store'), [
            'no_pembelian' => 'PO-SEPARATE-001',
            'tanggal_pembelian' => '2026-10-16',
            'id_supplier' => $supplier->id_supplier,
            'items' => [
                [
                    'kode_alat' => 'ALT001',
                    'kuantitas' => 2,
                    'harga_beli_satuan' => 400000,
                ],
            ],
        ]);

        // Jumlah transaksi pesanan pelanggan harus tetap sama
        $this->assertEquals($penyewaanAwalCount, Penyewaan::count());
    }

    /**
     * Pengguna dan Pemilik tidak dapat mengakses alur pembelian inventaris supplier
     */
    public function test_non_admin_cannot_access_supplier_purchasing(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        $pemilik = User::where('username', 'pemilik')->first();

        $this->actingAs($pengguna)->get(route('admin.inventaris.pembelian.index'))->assertStatus(403);
        $this->actingAs($pengguna)->get(route('admin.inventaris.pembelian.create'))->assertStatus(403);
        $this->actingAs($pengguna)->post(route('admin.inventaris.pembelian.store'), [])->assertStatus(403);

        $this->actingAs($pemilik)->get(route('admin.inventaris.pembelian.index'))->assertStatus(403);
        $this->actingAs($pemilik)->get(route('admin.inventaris.pembelian.create'))->assertStatus(403);
        $this->actingAs($pemilik)->post(route('admin.inventaris.pembelian.store'), [])->assertStatus(403);
    }
}

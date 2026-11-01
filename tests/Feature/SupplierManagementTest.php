<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\PembelianInventaris;
use App\Models\Supplier;
use App\Models\User;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    /**
     * Admin dapat melihat halaman daftar supplier (index)
     */
    public function test_admin_can_view_supplier_index(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Buat supplier contoh
        Supplier::create([
            'nama_supplier' => 'CV Tenda Semeru Jaya',
            'kontak_person' => 'Joko Widodo',
            'telepon' => '08123456789',
            'alamat' => 'Malang, Jawa Timur',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.supplier.index'));

        $response->assertStatus(200);
        $response->assertSee('Master Data Supplier');
        $response->assertSee('CV Tenda Semeru Jaya');
        $response->assertSee('Joko Widodo');
        $response->assertSee('08123456789');
    }

    /**
     * Admin dapat melihat halaman rincian detail supplier (show)
     */
    public function test_admin_can_view_supplier_detail(): void
    {
        $admin = User::where('username', 'admin')->first();

        $supplier = Supplier::create([
            'nama_supplier' => 'PT Eiger Outdoorindo',
            'kontak_person' => 'Rina Sugiarto',
            'telepon' => '022-88776655',
            'alamat' => 'Bandung, Jawa Barat',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.supplier.show', $supplier->id_supplier));

        $response->assertStatus(200);
        $response->assertSee('Rincian Mitra: PT Eiger Outdoorindo');
        $response->assertSee('Rina Sugiarto');
        $response->assertSee('022-88776655');
        $response->assertSee('Bandung, Jawa Barat');
        $response->assertSee('Riwayat Transaksi Pengadaan Pembelian (PO)');
    }

    /**
     * Admin dapat membuka formulir tambah dan edit supplier
     */
    public function test_admin_can_open_create_and_edit_forms(): void
    {
        $admin = User::where('username', 'admin')->first();

        $supplier = Supplier::create([
            'nama_supplier' => 'Toko Alat Gunung Malang',
            'kontak_person' => 'Agus',
            'telepon' => '0899999999',
            'alamat' => 'Jl. Ijen',
            'created_at' => now(),
        ]);

        // Form create
        $responseCreate = $this->actingAs($admin)->get(route('admin.supplier.create'));
        $responseCreate->assertStatus(200);
        $responseCreate->assertSee('Tambah Master Data Supplier');
        $responseCreate->assertSee('Formulir Pendaftaran Rekanan Supplier');

        // Form edit
        $responseEdit = $this->actingAs($admin)->get(route('admin.supplier.edit', $supplier->id_supplier));
        $responseEdit->assertStatus(200);
        $responseEdit->assertSee('Edit Data Supplier');
        $responseEdit->assertSee('Toko Alat Gunung Malang');
    }

    /**
     * Admin dapat menambah supplier baru dengan data valid
     */
    public function test_admin_can_create_supplier_with_valid_data(): void
    {
        $admin = User::where('username', 'admin')->first();

        $payload = [
            'nama_supplier' => 'PT Consina Segara Alam',
            'kontak_person' => 'Dani Saputra',
            'telepon' => '021-77889900',
            'alamat' => 'Jakarta Timur',
        ];

        $response = $this->actingAs($admin)->post(route('admin.supplier.store'), $payload);

        $response->assertRedirect(route('admin.supplier.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('supplier', [
            'nama_supplier' => 'PT Consina Segara Alam',
            'kontak_person' => 'Dani Saputra',
            'telepon' => '021-77889900',
            'alamat' => 'Jakarta Timur',
        ]);

        // Pastikan tercatat di activity_logs
        $this->assertDatabaseHas('activity_logs', [
            'modul' => 'Supplier',
            'aktivitas' => 'Tambah Supplier',
        ]);
    }

    /**
     * Validasi menolak data nama supplier kosong atau data melampaui batas karakter
     */
    public function test_supplier_validation_rejects_empty_name_or_oversized_fields(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Nama kosong
        $response = $this->actingAs($admin)->post(route('admin.supplier.store'), [
            'nama_supplier' => '',
            'telepon' => '12345',
        ]);

        $response->assertSessionHasErrors(['nama_supplier']);

        // 2. Karakter terlalu panjang
        $responseLength = $this->actingAs($admin)->post(route('admin.supplier.store'), [
            'nama_supplier' => str_repeat('A', 105),
            'kontak_person' => str_repeat('B', 105),
            'telepon' => str_repeat('1', 30),
            'alamat' => str_repeat('C', 550),
        ]);

        $responseLength->assertSessionHasErrors(['nama_supplier', 'kontak_person', 'telepon', 'alamat']);
    }

    /**
     * Admin dapat memperbarui data supplier (edit & update)
     */
    public function test_admin_can_update_supplier(): void
    {
        $admin = User::where('username', 'admin')->first();

        $supplier = Supplier::create([
            'nama_supplier' => 'CV Lama Outdoor',
            'kontak_person' => 'Lama',
            'telepon' => '0811111111',
            'alamat' => 'Alamat Lama',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->put(route('admin.supplier.update', $supplier->id_supplier), [
            'nama_supplier' => 'CV Baru Adventure',
            'kontak_person' => 'Kontak Baru',
            'telepon' => '0822222222',
            'alamat' => 'Alamat Baru No. 100',
        ]);

        $response->assertRedirect(route('admin.supplier.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('supplier', [
            'id_supplier' => $supplier->id_supplier,
            'nama_supplier' => 'CV Baru Adventure',
            'kontak_person' => 'Kontak Baru',
            'telepon' => '0822222222',
            'alamat' => 'Alamat Baru No. 100',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'modul' => 'Supplier',
            'aktivitas' => 'Perbarui Supplier',
        ]);
    }

    /**
     * Pencarian supplier berjalan benar berdasarkan nama, kontak, maupun telepon
     */
    public function test_supplier_search_filters_results_correctly(): void
    {
        $admin = User::where('username', 'admin')->first();

        Supplier::create([
            'nama_supplier' => 'Deuter Official Indonesia',
            'kontak_person' => 'Hans Gruber',
            'telepon' => '0813998877',
            'alamat' => 'Tangerang',
            'created_at' => now(),
        ]);

        Supplier::create([
            'nama_supplier' => 'Osprey Packs Distribution',
            'kontak_person' => 'Sarah Connor',
            'telepon' => '0812776655',
            'alamat' => 'Bekasi',
            'created_at' => now(),
        ]);

        // Cari Deuter
        $responseDeuter = $this->actingAs($admin)->get(route('admin.supplier.index', ['q' => 'Deuter']));
        $responseDeuter->assertStatus(200);
        $responseDeuter->assertSee('Deuter Official Indonesia');
        $responseDeuter->assertDontSee('Osprey Packs Distribution');

        // Cari berdasarkan kontak Sarah
        $responseSarah = $this->actingAs($admin)->get(route('admin.supplier.index', ['q' => 'Sarah']));
        $responseSarah->assertStatus(200);
        $responseSarah->assertSee('Osprey Packs Distribution');
        $responseSarah->assertDontSee('Deuter Official Indonesia');
    }

    /**
     * Supplier yang memiliki riwayat transaksi PO pembelian TIDAK DAPAT dihapus (Integritas Relasi Foreign Key)
     */
    public function test_supplier_with_purchase_history_cannot_be_deleted(): void
    {
        $admin = User::where('username', 'admin')->first();

        $supplier = Supplier::create([
            'nama_supplier' => 'PT Mitra Pengadaan Tenda',
            'kontak_person' => 'Hadi',
            'telepon' => '0811223344',
            'alamat' => 'Surabaya',
            'created_at' => now(),
        ]);

        // Buat data riwayat pembelian inventaris terkait
        PembelianInventaris::create([
            'no_pembelian' => 'PO-TEST-HISTORIS-01',
            'tanggal_pembelian' => '2026-10-15',
            'id_supplier' => $supplier->id_supplier,
            'total_pembelian' => 500000.00,
            'id_admin' => $admin->id_user,
            'catatan' => 'Pengadaan tenda batch 1',
            'created_at' => now(),
        ]);

        // Upaya hapus harus ditolak
        $response = $this->actingAs($admin)->delete(route('admin.supplier.destroy', $supplier->id_supplier));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('supplier', [
            'id_supplier' => $supplier->id_supplier,
            'nama_supplier' => 'PT Mitra Pengadaan Tenda',
        ]);
    }

    /**
     * Supplier tanpa riwayat pembelian dapat dihapus secara aman
     */
    public function test_supplier_without_purchase_history_can_be_safely_deleted(): void
    {
        $admin = User::where('username', 'admin')->first();

        $supplier = Supplier::create([
            'nama_supplier' => 'Supplier Salah Input',
            'kontak_person' => 'Testing',
            'telepon' => '08000000',
            'alamat' => 'Tidak Valid',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.supplier.destroy', $supplier->id_supplier));

        $response->assertRedirect(route('admin.supplier.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('supplier', [
            'id_supplier' => $supplier->id_supplier,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'modul' => 'Supplier',
            'aktivitas' => 'Hapus Supplier',
        ]);
    }

    /**
     * Role Pemilik dan Pengguna dilarang mengakses atau memodifikasi modul Supplier (403)
     */
    public function test_non_admin_cannot_access_or_modify_supplier_module(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();
        $pengguna = User::where('username', 'pelanggan')->first();

        $supplier = Supplier::create([
            'nama_supplier' => 'Vendor Rahasia',
            'kontak_person' => 'PIC',
            'telepon' => '081234',
            'alamat' => 'Alamat',
            'created_at' => now(),
        ]);

        // 1. Pemilik (Strictly Read-Only)
        $this->actingAs($pemilik)->get(route('admin.supplier.index'))->assertStatus(403);
        $this->actingAs($pemilik)->get(route('admin.supplier.create'))->assertStatus(403);
        $this->actingAs($pemilik)->post(route('admin.supplier.store'), ['nama_supplier' => 'Hack'])->assertStatus(403);
        $this->actingAs($pemilik)->get(route('admin.supplier.show', $supplier->id_supplier))->assertStatus(403);
        $this->actingAs($pemilik)->get(route('admin.supplier.edit', $supplier->id_supplier))->assertStatus(403);
        $this->actingAs($pemilik)->put(route('admin.supplier.update', $supplier->id_supplier), ['nama_supplier' => 'Hack'])->assertStatus(403);
        $this->actingAs($pemilik)->delete(route('admin.supplier.destroy', $supplier->id_supplier))->assertStatus(403);

        // 2. Pengguna biasa
        $this->actingAs($pengguna)->get(route('admin.supplier.index'))->assertStatus(403);
        $this->actingAs($pengguna)->get(route('admin.supplier.show', $supplier->id_supplier))->assertStatus(403);
        $this->actingAs($pengguna)->post(route('admin.supplier.store'), ['nama_supplier' => 'Hack'])->assertStatus(403);

        // 3. Tamu (Guest)
        auth()->logout();
        $this->get(route('admin.supplier.index'))->assertRedirect(route('login'));
    }

    /**
     * Memastikan integrasi alur pengadaan inventaris tetap berjalan mulus
     */
    public function test_inventory_purchase_flow_still_works_with_supplier(): void
    {
        $admin = User::where('username', 'admin')->first();
        $supplier = Supplier::create([
            'nama_supplier' => 'Supplier Khusus Pengadaan',
            'kontak_person' => 'Budi',
            'telepon' => '0877665544',
            'alamat' => 'Malang',
            'created_at' => now(),
        ]);

        $alat = Alat::find('ALT001');
        $stokAwal = (int) $alat->stok;

        $response = $this->actingAs($admin)->post(route('admin.inventaris.pembelian.store'), [
            'no_pembelian' => 'PO-SUPPLIER-INTEGRATION-01',
            'tanggal_pembelian' => '2026-10-25',
            'id_supplier' => $supplier->id_supplier,
            'items' => [
                [
                    'kode_alat' => 'ALT001',
                    'kuantitas' => 3,
                    'harga_beli_satuan' => 200000,
                ],
            ],
            'catatan' => 'Uji integrasi relasi supplier',
        ]);

        $response->assertRedirect(route('admin.inventaris.pembelian.index'));
        $response->assertSessionHas('success');

        // Pastikan stok bertambah
        $alat->refresh();
        $this->assertEquals($stokAwal + 3, (int) $alat->stok);

        // Pastikan relasi supplier pada detail PO valid
        $supplier->refresh();
        $this->assertEquals(1, $supplier->pembelian()->count());
        $this->assertEquals(600000.00, (float) $supplier->pembelian()->first()->total_pembelian);
    }
}

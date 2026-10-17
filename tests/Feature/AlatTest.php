<?php

namespace Tests\Feature;

use App\Http\Controllers\KeranjangController;
use App\Models\Alat;
use App\Models\User;
use Tests\TestCase;

class AlatTest extends TestCase
{
    /**
     * Pengguna dapat melihat daftar katalog alat
     */
    public function test_pengguna_can_view_katalog(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($pengguna)->get('/pengguna/katalog');

        $response->assertStatus(200);
        $response->assertSee('Peralatan Outdoor Siap Sewa');
        $response->assertSee('Tenda Dome 4 Orang');
        $response->assertSee('Sleeping Bag');
        $response->assertSee('Carrier 60L');
    }

    /**
     * Aturan bisnis: Katalog HANYA menampilkan alat berstatus Baik dan stok > 0.
     * Alat rusak (ALT006) tidak boleh ditampilkan sebagai alat siap sewa.
     */
    public function test_katalog_excludes_damaged_and_empty_stock_equipment(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($pengguna)->get('/pengguna/katalog');

        $response->assertStatus(200);
        $response->assertDontSee('Lentera Rusak'); // ALT006 kondisi Rusak
    }

    /**
     * Pengguna dapat mencari alat berdasarkan nama atau kode alat
     */
    public function test_pengguna_can_search_katalog(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($pengguna)->get('/pengguna/katalog?q=Dome');

        $response->assertStatus(200);
        $response->assertSee('Tenda Dome 4 Orang');
        $response->assertDontSee('Sleeping Bag');
    }

    /**
     * Pengguna dapat memfilter katalog berdasarkan kategori
     */
    public function test_pengguna_can_filter_katalog_by_category(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($pengguna)->get('/pengguna/katalog?kategori=Tas');

        $response->assertStatus(200);
        $response->assertSee('Carrier 60L');
        $response->assertDontSee('Kompor Portable');
    }

    /**
     * Pengguna dapat melihat halaman rincian detail alat
     */
    public function test_pengguna_can_view_equipment_detail(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($pengguna)->get('/pengguna/katalog/ALT001');

        $response->assertStatus(200);
        $response->assertSee('ALT001');
        $response->assertSee('Tenda Dome 4 Orang');
        $response->assertSee('Rp 50.000');
        $response->assertSee('Ketentuan Penyewaan');
    }

    /**
     * Admin dapat melihat daftar seluruh alat di inventaris
     */
    public function test_admin_can_view_equipment_index(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/alat');

        $response->assertStatus(200);
        $response->assertSee('Inventaris Master Alat');
        $response->assertSee('ALT001');
        $response->assertSee('ALT006'); // Termasuk alat rusak untuk pemantauan admin
        $response->assertSee('Tambah Alat Baru');
    }

    /**
     * Admin dapat melihat detail alat beserta status integritas transaksinya
     */
    public function test_admin_can_view_equipment_detail(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/alat/ALT001');

        $response->assertStatus(200);
        $response->assertSee('ALT001');
        $response->assertSee('Integritas Relasi Transaksi');
        $response->assertSee('1 Transaksi');
    }

    /**
     * Pengguna DILARANG mengakses daftar inventaris Admin (403 Forbidden)
     */
    public function test_pengguna_cannot_access_admin_equipment_index(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($pengguna)->get('/admin/alat');

        $response->assertForbidden();
    }

    /**
     * Admin dapat membuka formulir pembuatan alat baru
     */
    public function test_admin_can_view_create_form(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/alat/create');

        $response->assertStatus(200);
        $response->assertSee('Formulir Perekaman Alat Baru');
        $response->assertSee('Kode Alat');
        $response->assertSee('Simpan Alat Baru');
    }

    /**
     * Admin dapat menyimpan alat baru dengan data valid
     */
    public function test_admin_can_create_new_equipment(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post('/admin/alat', [
            'kode_alat' => 'ALT007',
            'nama_alat' => 'Flysheet 3x4 Meter',
            'kategori' => 'Tenda',
            'stok' => 3,
            'kondisi' => 'Baik',
            'tarif_sewa' => 20000,
        ]);

        $response->assertRedirect('/admin/alat');
        $response->assertSessionHas('success');

        $alat = Alat::where('kode_alat', 'ALT007')->first();
        $this->assertNotNull($alat);
        $this->assertEquals('Flysheet 3x4 Meter', $alat->nama_alat);
        $this->assertEquals(3, $alat->stok);
        $this->assertEquals('20000.00', $alat->tarif_sewa);
    }

    /**
     * Validasi kode alat duplikat ditolak
     */
    public function test_equipment_creation_rejects_duplicate_kode_alat(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post('/admin/alat', [
            'kode_alat' => 'ALT001', // Sudah ada
            'nama_alat' => 'Tenda Duplikat',
            'kategori' => 'Tenda',
            'stok' => 2,
            'kondisi' => 'Baik',
            'tarif_sewa' => 50000,
        ]);

        $response->assertSessionHasErrors(['kode_alat']);
    }

    /**
     * Validasi nilai stok dan tarif tidak boleh bernilai negatif atau 0
     */
    public function test_equipment_creation_rejects_invalid_rate_and_stock(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Stok negatif
        $response = $this->actingAs($admin)->post('/admin/alat', [
            'kode_alat' => 'ALT008',
            'nama_alat' => 'Nesting Camping',
            'kategori' => 'Peralatan Masak',
            'stok' => -5,
            'kondisi' => 'Baik',
            'tarif_sewa' => 15000,
        ]);
        $response->assertSessionHasErrors(['stok']);

        // 2. Tarif 0 atau negatif
        $response = $this->actingAs($admin)->post('/admin/alat', [
            'kode_alat' => 'ALT008',
            'nama_alat' => 'Nesting Camping',
            'kategori' => 'Peralatan Masak',
            'stok' => 2,
            'kondisi' => 'Baik',
            'tarif_sewa' => 0,
        ]);
        $response->assertSessionHasErrors(['tarif_sewa']);
    }

    /**
     * Pengguna biasa dilarang melakukan penambahan alat (403 Forbidden)
     */
    public function test_pengguna_cannot_create_equipment(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($pengguna)->post('/admin/alat', [
            'kode_alat' => 'ALT999',
            'nama_alat' => 'Hacker Tent',
            'kategori' => 'Tenda',
            'stok' => 1,
            'kondisi' => 'Baik',
            'tarif_sewa' => 10000,
        ]);

        $response->assertForbidden();
    }

    /**
     * Admin dapat membuka formulir edit alat
     */
    public function test_admin_can_view_edit_form(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/alat/ALT001/edit');

        $response->assertStatus(200);
        $response->assertSee('Perbarui Data Alat');
        $response->assertSee('ALT001');
        $response->assertSee('Tenda Dome 4 Orang');
    }

    /**
     * Admin dapat memperbarui data alat
     */
    public function test_admin_can_update_equipment(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->put('/admin/alat/ALT001', [
            'nama_alat' => 'Tenda Dome 4P Updated',
            'kategori' => 'Tenda',
            'stok' => 2,
            'kondisi' => 'Baik',
            'tarif_sewa' => 55000,
        ]);

        $response->assertRedirect('/admin/alat');
        $response->assertSessionHas('success');

        $alat = Alat::where('kode_alat', 'ALT001')->first();
        $this->assertEquals('Tenda Dome 4P Updated', $alat->nama_alat);
        $this->assertEquals(2, $alat->stok);
        $this->assertEquals('55000.00', $alat->tarif_sewa);
    }

    /**
     * Pengguna dilarang melakukan pembaruan data alat (403 Forbidden)
     */
    public function test_pengguna_cannot_update_equipment(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($pengguna)->put('/admin/alat/ALT001', [
            'nama_alat' => 'Tenda Modifikasi User',
            'kategori' => 'Tenda',
            'stok' => 100,
            'kondisi' => 'Baik',
            'tarif_sewa' => 1000,
        ]);

        $response->assertForbidden();
    }

    /**
     * Aturan Integritas Relasi: Alat yang sudah ada transaksi penyewaan DILARANG dihapus
     */
    public function test_admin_cannot_delete_equipment_with_rental_history(): void
    {
        $admin = User::where('username', 'admin')->first();

        // ALT001 memiliki detail_penyewaan di seed
        $this->assertTrue(Alat::find('ALT001')->hasRentals());

        $response = $this->actingAs($admin)->delete('/admin/alat/ALT001');

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('alat', ['kode_alat' => 'ALT001']);
    }

    /**
     * Alat yang belum pernah ada transaksi penyewaan dapat dihapus dengan aman
     */
    public function test_admin_can_delete_unused_equipment(): void
    {
        $admin = User::where('username', 'admin')->first();

        // ALT005 tidak memiliki detail_penyewaan di seed
        $this->assertFalse(Alat::find('ALT005')->hasRentals());

        $response = $this->actingAs($admin)->delete('/admin/alat/ALT005');

        $response->assertRedirect('/admin/alat');
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('alat', ['kode_alat' => 'ALT005']);
    }

    /**
     * Pengguna dilarang menghapus data alat (403 Forbidden)
     */
    public function test_pengguna_cannot_delete_equipment(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($pengguna)->delete('/admin/alat/ALT002');

        $response->assertForbidden();
        $this->assertDatabaseHas('alat', ['kode_alat' => 'ALT002']);
    }

    /**
     * Pastikan kelima barang di katalog memiliki nilai awal 0,
     * penambahan jumlah 0 atau negatif ditolak, dan penambahan positif tidak di-reset oleh katalog.
     */
    public function test_katalog_initial_quantity_controls_are_zero_and_cannot_be_negative(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($pengguna->id_user);

        // 1. Buka katalog dan verifikasi seluruh 5 alat siap sewa memiliki kontrol awal value="0" dan min="0"
        $katalogRes = $this->actingAs($pengguna)->get('/pengguna/katalog');
        $katalogRes->assertStatus(200);

        $daftarAlat = Alat::where('kondisi', 'Baik')->where('stok', '>', 0)->get();
        $this->assertCount(5, $daftarAlat);

        foreach ($daftarAlat as $item) {
            // Verifikasi input jumlah memiliki nilai awal 0 dan batas bawah min="0"
            $katalogRes->assertSee('id="qty_'.$item->kode_alat.'"', false);
            $katalogRes->assertSee('value="0"', false);
            $katalogRes->assertSee('min="0"', false);
        }

        // 2. Tombol + Keranjang menolak jika jumlah = 0
        $responseNol = $this->actingAs($pengguna)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => 'ALT001',
            'jumlah' => 0,
        ]);
        $responseNol->assertSessionHasErrors('jumlah');
        $this->assertEquals(0, KeranjangController::count($pengguna->id_user));

        // 3. Tombol + Keranjang menolak jika jumlah negatif
        $responseNegatif = $this->actingAs($pengguna)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => 'ALT001',
            'jumlah' => -2,
        ]);
        $responseNegatif->assertSessionHasErrors('jumlah');
        $this->assertEquals(0, KeranjangController::count($pengguna->id_user));

        // 4. Tambahkan barang jika jumlah > 0 (misal 2 unit ALT002 dengan stok 4)
        $responsePositif = $this->actingAs($pengguna)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => 'ALT002',
            'jumlah' => 2,
        ]);
        $responsePositif->assertSessionHas('success');
        $this->assertEquals(2, KeranjangController::count($pengguna->id_user));

        // 5. Muat ulang katalog: Barang di keranjang TIDAK boleh ter-reset dan input katalog tetap 0
        $reloadRes = $this->actingAs($pengguna)->get('/pengguna/katalog');
        $reloadRes->assertStatus(200);
        $this->assertEquals(2, KeranjangController::count($pengguna->id_user));
    }
}

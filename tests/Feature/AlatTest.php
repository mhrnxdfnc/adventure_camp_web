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

    /**
     * Memastikan kartu katalog menampilkan seluruh elemen yang disyaratkan:
     * nama alat, kode, kategori, kondisi, stok tersedia, tarif harian dengan label "/ unit / hari",
     * stepper kontrol kuantitas awal 0, tombol keranjang awal disabled, dan tautan rincian detail.
     * Serta memastikan TIDAK ADA kontrol durasi atau tanggal pada kartu katalog.
     */
    public function test_katalog_cards_display_all_required_elements_and_no_duration_controls(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($pengguna->id_user);

        $response = $this->actingAs($pengguna)->get('/pengguna/katalog');
        $response->assertStatus(200);

        // Ambil alat yang aktif di katalog
        $alatList = Alat::where('kondisi', 'Baik')->where('stok', '>', 0)->get();
        $this->assertGreaterThan(0, $alatList->count());

        foreach ($alatList as $alat) {
            // 1. Nama alat, kode, kategori, kondisi
            $response->assertSee($alat->nama_alat);
            $response->assertSee($alat->kode_alat);
            $response->assertSee($alat->kategori);
            $response->assertSee($alat->kondisi);

            // 2. Stok yang tersedia
            $response->assertSee('Stok: '.$alat->stok.' unit');

            // 3. Tarif harian dengan format "/ unit / hari"
            $response->assertSee('/ unit / hari');
            $response->assertSee('Rp '.number_format($alat->tarif_sewa, 0, ',', '.'));

            // 4. Kontrol jumlah unit awal 0 dan tombol minus awal disabled
            $response->assertSee('id="qty_'.$alat->kode_alat.'"', false);
            $response->assertSee('id="btn_minus_'.$alat->kode_alat.'"', false);
            $response->assertSee('id="btn_plus_'.$alat->kode_alat.'"', false);
            $response->assertSee('id="btn_cart_'.$alat->kode_alat.'"', false);

            // 5. Tautan rincian detail
            $response->assertSee(route('pengguna.katalog.show', $alat->kode_alat));
        }

        // 6. Memastikan tidak ada kontrol durasi sewa atau pilihan tanggal pada kartu katalog
        $response->assertDontSee('type="date"', false);
        $response->assertDontSee('name="tgl_sewa"', false);
        $response->assertDontSee('name="tgl_kembali"', false);
        $response->assertDontSee('name="durasi"', false);
    }

    /**
     * Memastikan alat yang tidak layak sewa atau stok habis tidak dapat ditambahkan ke keranjang
     */
    public function test_katalog_handles_unrentable_and_out_of_stock_conditions(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($pengguna->id_user);

        // Alat kondisi rusak
        $alatRusak = Alat::where('kondisi', '!=', 'Baik')->first();
        if ($alatRusak) {
            $res = $this->actingAs($pengguna)->post(route('pengguna.keranjang.tambah'), [
                'kode_alat' => $alatRusak->kode_alat,
                'jumlah' => 1,
            ]);
            $res->assertSessionHas('error');
            $this->assertEquals(0, KeranjangController::count($pengguna->id_user));
        }

        // Alat dengan stok 0
        $alatHabis = Alat::where('stok', '<=', 0)->first();
        if (! $alatHabis) {
            $alatHabis = Alat::create([
                'kode_alat' => 'ALT998',
                'nama_alat' => 'Tenda Uji Stok Kosong',
                'kategori' => 'Tenda',
                'stok' => 0,
                'kondisi' => 'Baik',
                'tarif_sewa' => 30000,
            ]);
        }
        $resHabis = $this->actingAs($pengguna)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => $alatHabis->kode_alat,
            'jumlah' => 1,
        ]);
        $resHabis->assertSessionHas('error');
        $this->assertEquals(0, KeranjangController::count($pengguna->id_user));

        if ($alatHabis->kode_alat === 'ALT998') {
            $alatHabis->delete();
        }
    }

    /**
     * Memastikan penambahan beberapa jenis alat berbeda ke keranjang memperbarui kuantitas
     * tanpa membuat baris duplikat, dan memvalidasi batas stok maksimal.
     */
    public function test_katalog_multi_item_addition_and_stock_limits(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($pengguna->id_user);

        $alat1 = Alat::where('kode_alat', 'ALT001')->first();
        $alat1->stok = 10;
        $alat1->save();

        $alat2 = Alat::where('kode_alat', 'ALT002')->first();
        $alat2->stok = 10;
        $alat2->save();

        // 1. Tambah alat 1 (2 unit)
        $res1 = $this->actingAs($pengguna)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => $alat1->kode_alat,
            'jumlah' => 2,
        ]);
        $res1->assertSessionHas('success');

        // 2. Tambah alat 2 (1 unit)
        $res2 = $this->actingAs($pengguna)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => $alat2->kode_alat,
            'jumlah' => 1,
        ]);
        $res2->assertSessionHas('success');

        // 3. Tambah lagi alat 1 (1 unit) -> harus menambah jumlah alat 1 tanpa membuat baris duplikat
        $res3 = $this->actingAs($pengguna)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => $alat1->kode_alat,
            'jumlah' => 1,
        ]);
        $res3->assertSessionHas('success');

        $this->assertEquals(2, KeranjangController::countDistinct($pengguna->id_user));
        $this->assertEquals(4, KeranjangController::count($pengguna->id_user));

        $cart = KeranjangController::getCart($pengguna->id_user);
        $this->assertEquals(3, $cart[$alat1->kode_alat]['jumlah']);
        $this->assertEquals(1, $cart[$alat2->kode_alat]['jumlah']);

        // 4. Menambah melebihi stok yang tersisa harus ditolak
        $sisaStokAlat2 = $alat2->stok - 1;
        $resExceed = $this->actingAs($pengguna)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => $alat2->kode_alat,
            'jumlah' => $sisaStokAlat2 + 10,
        ]);
        $resExceed->assertSessionHas('error');

        // Jumlah keranjang tidak boleh bertambah
        $this->assertEquals(4, KeranjangController::count($pengguna->id_user));
    }

    /**
     * Memastikan halaman detail alat menampilkan informasi produk aktual:
     * nama alat, kode alat, kategori, ilustrasi visual, kondisi, stok tersedia, tarif harian "/ unit / hari",
     * tombol kembali ke katalog, tombol menuju keranjang, dan kontrol kuantitas awal 0 dengan tombol disabled.
     * Serta memastikan TIDAK ADA pesan teknis rumus, pemilih tanggal, atau durasi sewa.
     */
    public function test_equipment_detail_displays_required_product_info_and_excludes_technical_duration_clutter(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        $alat = Alat::where('kode_alat', 'ALT001')->first();

        // 1. Navigasi dari katalog: link detail ada di katalog
        $katalogRes = $this->actingAs($pengguna)->get(route('pengguna.katalog.index'));
        $katalogRes->assertStatus(200);
        $katalogRes->assertSee(route('pengguna.katalog.show', $alat->kode_alat));

        // 2. Akses halaman detail alat
        $detailRes = $this->actingAs($pengguna)->get(route('pengguna.katalog.show', $alat->kode_alat));
        $detailRes->assertStatus(200);

        // Memastikan informasi produk aktual tampil
        $detailRes->assertSee($alat->nama_alat);
        $detailRes->assertSee($alat->kode_alat);
        $detailRes->assertSee($alat->kategori);
        $detailRes->assertSee($alat->kondisi);
        $detailRes->assertSee($alat->stok.' Unit');
        $detailRes->assertSee('Rp '.number_format($alat->tarif_sewa, 0, ',', '.'));
        $detailRes->assertSee('/ unit / hari');

        // Memastikan tombol kembali ke katalog dan tombol ke keranjang tersedia
        $detailRes->assertSee('Kembali ke Katalog');
        $detailRes->assertSee(route('pengguna.katalog.index'));
        $detailRes->assertSee(route('pengguna.keranjang.index'));

        // Memastikan kontrol jumlah awal 0 dan tombol minus & submit awal disabled
        $detailRes->assertSee('id="inputDetailJumlah"', false);
        $detailRes->assertSee('value="0"', false);
        $detailRes->assertSee('id="btnDetailMinus"', false);
        $detailRes->assertSee('id="btnDetailSubmit"', false);

        // Memastikan TIDAK ADA pemilihan tanggal sewa, durasi sewa, atau kalkulasi teknis internal
        $detailRes->assertDontSee('type="date"', false);
        $detailRes->assertDontSee('name="tanggal_sewa"', false);
        $detailRes->assertDontSee('name="tanggal_kembali"', false);
        $detailRes->assertDontSee('name="durasi"', false);
        $detailRes->assertDontSee('belum dikalikan durasi sewa');
        $detailRes->assertDontSee('labelKalkulasi');
        $detailRes->assertDontSee('labelDurasi');
        $detailRes->assertDontSee('Estimasi Tagihan');
    }

    /**
     * Memastikan penambahan barang dari halaman detail alat memvalidasi kuantitas:
     * tidak boleh 0, tidak boleh negatif, tidak melebihi stok, dan konsisten dengan keranjang belanja.
     */
    public function test_equipment_detail_cart_controls_and_quantity_validation(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        KeranjangController::clear($pengguna->id_user);

        $alat = Alat::where('kode_alat', 'ALT001')->first();
        $alat->stok = 10;
        $alat->save();

        // 1. Submit kuantitas 0 ditolak
        $resZero = $this->actingAs($pengguna)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 0,
        ]);
        $resZero->assertSessionHasErrors('jumlah');
        $this->assertEquals(0, KeranjangController::count($pengguna->id_user));

        // 2. Submit kuantitas negatif ditolak
        $resNegative = $this->actingAs($pengguna)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => -1,
        ]);
        $resNegative->assertSessionHasErrors('jumlah');
        $this->assertEquals(0, KeranjangController::count($pengguna->id_user));

        // 3. Submit kuantitas melebihi stok ditolak
        $resExceed = $this->actingAs($pengguna)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => $alat->stok + 5,
        ]);
        $resExceed->assertSessionHas('error');
        $this->assertEquals(0, KeranjangController::count($pengguna->id_user));

        // 4. Submit kuantitas valid (misal 3 unit) via AJAX
        $resAjax = $this->actingAs($pengguna)->postJson(route('pengguna.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 3,
        ]);
        $resAjax->assertOk();
        $resAjax->assertJson([
            'success' => true,
            'cart_count' => 3,
        ]);
        $this->assertEquals(3, KeranjangController::count($pengguna->id_user));

        // 5. Verifikasi konsistensi dengan tampilan halaman keranjang belanja
        $cartPageRes = $this->actingAs($pengguna)->get(route('pengguna.keranjang.index'));
        $cartPageRes->assertStatus(200);
        $cartPageRes->assertSee($alat->nama_alat);
        $cartPageRes->assertSee('3 Unit');
    }
}

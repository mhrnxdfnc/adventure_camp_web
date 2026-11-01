<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Pengambilan;
use App\Models\Pengembalian;
use App\Models\Penyewaan;
use App\Models\User;
use Tests\TestCase;

class PengembalianSebagianTest extends TestCase
{
    /**
     * Siapkan transaksi milik Budi Santoso dengan 2 detail berkode alat sama (ALT002: 3 unit dan 1 unit)
     */
    private function prepareBudiSantosoDuplicateItems(string $noTransaksi = 'TRX-BUDI-DUPLICATE'): Penyewaan
    {
        $admin = User::where('username', 'admin')->first();
        $budi = Pelanggan::where('nama_pelanggan', 'like', '%Budi%')->first();

        $penyewaan = Penyewaan::create([
            'no_transaksi' => $noTransaksi,
            'id_pelanggan' => $budi->id_pelanggan,
            'id_user' => 4,
            'tanggal_sewa' => '2026-10-12',
            'tanggal_kembali' => '2026-10-15',
            'status_penyewaan' => 'Sedang Disewa',
            'sumber_pesanan' => 'online',
        ]);

        Pembayaran::create([
            'no_transaksi' => $noTransaksi,
            'no_pembayaran' => 'PAY-BUDI-001',
            'total_biaya' => 200000,
            'jumlah_pembayaran' => 200000,
            'metode_pembayaran' => 'Transfer Bank',
            'status_pembayaran' => 'Lunas',
            'id_admin' => $admin->id_user,
            'waktu_konfirmasi' => now(),
        ]);

        Pengambilan::create([
            'no_transaksi' => $noTransaksi,
            'tanggal_pengambilan' => '2026-10-12',
            'id_admin' => $admin->id_user,
            'nama_penerima' => 'Budi Santoso',
            'catatan' => 'Semua alat diserahkan lengkap',
        ]);

        // Detail 1: ALT002 Sleeping Bag - 3 unit
        DetailPenyewaan::create([
            'no_transaksi' => $noTransaksi,
            'kode_alat' => 'ALT002',
            'jumlah' => 3,
            'tarif_sewa' => 25000,
            'jumlah_diserahkan' => 3,
            'jumlah_kembali_baik' => 0,
            'jumlah_perlu_perbaikan' => 0,
            'jumlah_rusak' => 0,
        ]);

        // Detail 2: ALT002 Sleeping Bag - 1 unit
        DetailPenyewaan::create([
            'no_transaksi' => $noTransaksi,
            'kode_alat' => 'ALT002',
            'jumlah' => 1,
            'tarif_sewa' => 25000,
            'jumlah_diserahkan' => 1,
            'jumlah_kembali_baik' => 0,
            'jumlah_perlu_perbaikan' => 0,
            'jumlah_rusak' => 0,
        ]);

        return $penyewaan;
    }

    /**
     * Form pengembalian merender baris terpisah dengan id_detail unik untuk barang berkode sama
     */
    public function test_form_pengembalian_renders_unique_inputs_for_same_kode_alat(): void
    {
        $penyewaan = $this->prepareBudiSantosoDuplicateItems();
        $admin = User::where('username', 'admin')->first();
        $details = $penyewaan->detailPenyewaan()->get();
        $d1 = $details[0];
        $d2 = $details[1];

        $response = $this->actingAs($admin)->get(route('admin.operasional.form-pengembalian', $penyewaan->no_transaksi));

        $response->assertStatus(200);
        $response->assertSee("items[{$d1->id_detail}][baik]", false);
        $response->assertSee("items[{$d2->id_detail}][baik]", false);
        $response->assertSee('max="3"', false);
        $response->assertSee('max="1"', false);
    }

    /**
     * Berhasil mengembalikan seluruh barang milik Budi Santoso meski memiliki kode alat yang sama
     */
    public function test_can_return_all_items_when_same_equipment_code_exists_in_multiple_details(): void
    {
        $penyewaan = $this->prepareBudiSantosoDuplicateItems();
        $admin = User::where('username', 'admin')->first();
        $details = $penyewaan->detailPenyewaan()->get();
        $d1 = $details[0];
        $d2 = $details[1];
        $stokAwalAlt2 = (int) Alat::find('ALT002')->stok;

        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-18',
            'items' => [
                (string) $d1->id_detail => ['baik' => 3, 'perlu_perbaikan' => 0, 'rusak' => 0],
                (string) $d2->id_detail => ['baik' => 1, 'perlu_perbaikan' => 0, 'rusak' => 0],
            ],
            'nominal_denda' => 0,
            'catatan' => 'Semua sleeping bag kembali utuh dan bersih.',
        ]);

        $response->assertRedirect(route('admin.operasional.pengembalian'));
        $response->assertSessionHas('success');

        // Pastikan status transaksi kini Selesai
        $penyewaan->refresh();
        $this->assertEquals('Selesai', $penyewaan->status_penyewaan);

        // Pastikan stok ALT002 bertambah 4 unit (3 + 1)
        $this->assertEquals($stokAwalAlt2 + 4, Alat::find('ALT002')->stok);

        // Pastikan masing-masing detail tercatat kembali dengan benar
        $d1->refresh();
        $d2->refresh();
        $this->assertEquals(3, $d1->jumlah_kembali_baik);
        $this->assertEquals(1, $d2->jumlah_kembali_baik);
    }

    /**
     * Pengembalian bertahap: sebagian unit dikembalikan lebih dulu, status tetap Sedang Disewa
     */
    public function test_partial_staged_return_leaves_transaction_in_sedang_disewa_until_complete(): void
    {
        $penyewaan = $this->prepareBudiSantosoDuplicateItems();
        $admin = User::where('username', 'admin')->first();
        $details = $penyewaan->detailPenyewaan()->get();
        $d1 = $details[0];
        $d2 = $details[1];
        $stokAwalAlt2 = (int) Alat::find('ALT002')->stok;

        // Tahap 1: Kembalikan 1 unit dari Detail 1 (masih tersisa 2 unit di Detail 1 dan 1 unit di Detail 2)
        $responseTahap1 = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-16',
            'pengembalian_sebagian' => 1,
            'items' => [
                (string) $d1->id_detail => ['baik' => 1, 'perlu_perbaikan' => 0, 'rusak' => 0],
                (string) $d2->id_detail => ['baik' => 0, 'perlu_perbaikan' => 0, 'rusak' => 0],
            ],
            'nominal_denda' => 0,
        ]);

        $responseTahap1->assertRedirect(route('admin.operasional.pengembalian'));
        $responseTahap1->assertSessionHas('success');

        $penyewaan->refresh();
        $this->assertEquals('Sedang Disewa', $penyewaan->status_penyewaan);
        $this->assertEquals($stokAwalAlt2 + 1, Alat::find('ALT002')->stok);

        // Periksa sisa kembali pada detail
        $d1->refresh();
        $this->assertEquals(1, $d1->jumlah_kembali_baik);
        $this->assertEquals(2, $d1->jumlah_sisa_kembali);

        // Tahap 2: Buka form kembali, form menampilkan sisa 2 unit untuk Detail 1 dan 1 unit untuk Detail 2
        $formResponse = $this->actingAs($admin)->get(route('admin.operasional.form-pengembalian', $penyewaan->no_transaksi));
        $formResponse->assertStatus(200);
        $formResponse->assertSee('Sisa: 2 unit');

        // Tahap 3: Kembalikan sisa 2 unit pada Detail 1 dan 1 unit pada Detail 2
        $responseTahap2 = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-18',
            'pengembalian_sebagian' => 0,
            'items' => [
                (string) $d1->id_detail => ['baik' => 2, 'perlu_perbaikan' => 0, 'rusak' => 0],
                (string) $d2->id_detail => ['baik' => 1, 'perlu_perbaikan' => 0, 'rusak' => 0],
            ],
            'nominal_denda' => 0,
        ]);

        $responseTahap2->assertRedirect(route('admin.operasional.pengembalian'));
        $penyewaan->refresh();
        $this->assertEquals('Selesai', $penyewaan->status_penyewaan);

        // Total stok bertambah 4 unit dari awal
        $this->assertEquals($stokAwalAlt2 + 4, Alat::find('ALT002')->stok);

        // Record pengembalian tidak duplikat (tetap 1 baris di tabel pengembalian)
        $this->assertEquals(1, Pengembalian::where('no_transaksi', $penyewaan->no_transaksi)->count());
    }

    /**
     * Mengembalikan jumlah unit melebihi sisa unit yang disewa akan ditolak
     */
    public function test_returning_more_than_remaining_rented_units_is_rejected(): void
    {
        $penyewaan = $this->prepareBudiSantosoDuplicateItems();
        $admin = User::where('username', 'admin')->first();
        $details = $penyewaan->detailPenyewaan()->get();
        $d1 = $details[0];
        $d2 = $details[1];

        // Detail 2 hanya 1 unit, tapi diisi 2 unit
        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-18',
            'items' => [
                (string) $d1->id_detail => ['baik' => 3, 'perlu_perbaikan' => 0, 'rusak' => 0],
                (string) $d2->id_detail => ['baik' => 2, 'perlu_perbaikan' => 0, 'rusak' => 0],
            ],
            'nominal_denda' => 0,
        ]);

        $response->assertRedirect(route('admin.operasional.pengembalian'));
        $response->assertSessionHas('error');

        $penyewaan->refresh();
        $this->assertEquals('Sedang Disewa', $penyewaan->status_penyewaan);
    }

    /**
     * Unit rusak pada pengembalian multi-detail tidak ditambahkan ke stok toko
     */
    public function test_damaged_units_in_multi_detail_are_not_added_to_available_stock(): void
    {
        $penyewaan = $this->prepareBudiSantosoDuplicateItems();
        $admin = User::where('username', 'admin')->first();
        $details = $penyewaan->detailPenyewaan()->get();
        $d1 = $details[0];
        $d2 = $details[1];
        $stokAwalAlt2 = (int) Alat::find('ALT002')->stok;

        // Detail 1: 2 Baik, 1 Rusak
        // Detail 2: 1 Baik
        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-18',
            'items' => [
                (string) $d1->id_detail => ['baik' => 2, 'perlu_perbaikan' => 0, 'rusak' => 1],
                (string) $d2->id_detail => ['baik' => 1, 'perlu_perbaikan' => 0, 'rusak' => 0],
            ],
            'nominal_denda' => 30000,
            'alasan_denda' => '1 unit sleeping bag sobek parah di bagian bawah.',
        ]);

        $response->assertRedirect(route('admin.operasional.pengembalian'));
        $response->assertSessionHas('success');

        // Stok toko hanya bertambah 3 unit (yang baik)
        $this->assertEquals($stokAwalAlt2 + 3, Alat::find('ALT002')->stok);

        $d1->refresh();
        $this->assertEquals(2, $d1->jumlah_kembali_baik);
        $this->assertEquals(1, $d1->jumlah_rusak);
    }
}

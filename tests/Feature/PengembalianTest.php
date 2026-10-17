<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pembayaran;
use App\Models\Pengambilan;
use App\Models\Pengembalian;
use App\Models\Penyewaan;
use App\Models\User;
use Tests\TestCase;

class PengembalianTest extends TestCase
{
    /**
     * Siapkan transaksi dalam status Sedang Disewa
     */
    private function prepareSedangDisewa(string $noTransaksi = 'TRX-20261015-001'): Penyewaan
    {
        $admin = User::where('username', 'admin')->first();

        $penyewaan = Penyewaan::find($noTransaksi);
        $penyewaan->status_penyewaan = 'Sedang Disewa';
        $penyewaan->save();

        Pembayaran::updateOrCreate(
            ['no_transaksi' => $noTransaksi],
            [
                'no_pembayaran' => 'PAY-RET-001',
                'total_biaya' => $penyewaan->total_biaya,
                'jumlah_pembayaran' => $penyewaan->total_biaya,
                'metode_pembayaran' => 'Transfer Bank',
                'status_pembayaran' => 'Lunas',
                'id_admin' => $admin->id_user,
                'waktu_konfirmasi' => now(),
            ]
        );

        Pengambilan::updateOrCreate(
            ['no_transaksi' => $noTransaksi],
            [
                'tanggal_pengambilan' => '2026-10-15',
                'id_admin' => $admin->id_user,
                'nama_penerima' => 'Andi Pratama',
                'catatan' => 'Diserahkan dalam kondisi lengkap',
            ]
        );

        foreach ($penyewaan->detailPenyewaan as $detail) {
            $detail->jumlah_diserahkan = $detail->jumlah;
            $detail->save();
        }

        return $penyewaan;
    }

    /**
     * Admin dapat melihat daftar pesanan yang sedang disewa dan perlu dikembalikan
     */
    public function test_admin_can_view_active_rentals_for_return(): void
    {
        $this->prepareSedangDisewa();
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.operasional.pengembalian'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Pesanan Sedang Disewa');
        $response->assertSee('TRX-20261015-001');
        $response->assertSee('Andi Pratama');
        $response->assertSee('Kembalikan');
    }

    /**
     * Admin dapat membuka formulir pemeriksaan pengembalian alat
     */
    public function test_admin_can_open_return_inspection_form(): void
    {
        $this->prepareSedangDisewa();
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.operasional.form-pengembalian', 'TRX-20261015-001'));

        $response->assertStatus(200);
        $response->assertSee('Pemeriksaan Fisik');
        $response->assertSee('Pemeriksaan Kondisi Alat Camping');
        $response->assertSee('TRX-20261015-001');
    }

    /**
     * Admin dapat mencatat pengembalian alat berkondisi baik, status menjadi Selesai, dan stok kembali bertambah
     */
    public function test_admin_can_record_return_with_all_items_in_good_condition(): void
    {
        $penyewaan = $this->prepareSedangDisewa();
        $admin = User::where('username', 'admin')->first();

        $stokAwalAlt1 = Alat::find('ALT001')->stok;
        $stokAwalAlt2 = Alat::find('ALT002')->stok;

        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-18',
            'items' => [
                'ALT001' => ['baik' => 1, 'rusak' => 0],
                'ALT002' => ['baik' => 2, 'rusak' => 0],
            ],
            'nominal_denda' => 0,
            'catatan' => 'Semua alat kembali dalam keadaan prima dan bersih.',
        ]);

        $response->assertRedirect(route('admin.operasional.pengembalian'));
        $response->assertSessionHas('success');

        // Pastikan record pengembalian tersimpan
        $this->assertDatabaseHas('pengembalian', [
            'no_transaksi' => 'TRX-20261015-001',
            'status_pengembalian' => 'Selesai',
            'id_admin' => $admin->id_user,
        ]);

        $pengembalian = Pengembalian::where('no_transaksi', 'TRX-20261015-001')->first();
        $this->assertNotNull($pengembalian);
        $this->assertEquals('2026-10-18', $pengembalian->tanggal_pengembalian_aktual->format('Y-m-d'));

        // Pastikan status transaksi berubah jadi Selesai
        $penyewaan->refresh();
        $this->assertEquals('Selesai', $penyewaan->status_penyewaan);

        // Pastikan stok bertambah karena kondisi Baik
        $this->assertEquals($stokAwalAlt1 + 1, Alat::find('ALT001')->stok);
        $this->assertEquals($stokAwalAlt2 + 2, Alat::find('ALT002')->stok);

        // Pastikan audit trail tercatat
        $this->assertDatabaseHas('activity_logs', [
            'id_user' => $admin->id_user,
            'modul' => 'Pengembalian',
        ]);
    }

    /**
     * Alat yang rusak TIDAK dikembalikan ke stok siap sewa
     */
    public function test_damaged_equipment_is_not_returned_to_available_stock(): void
    {
        $penyewaan = $this->prepareSedangDisewa();
        $admin = User::where('username', 'admin')->first();

        $stokAwalAlt1 = Alat::find('ALT001')->stok;
        $stokAwalAlt2 = Alat::find('ALT002')->stok;

        // ALT001: 1 baik (masuk stok)
        // ALT002: 1 baik (masuk stok), 1 rusak (TIDAK masuk stok)
        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-18',
            'items' => [
                'ALT001' => ['baik' => 1, 'rusak' => 0],
                'ALT002' => ['baik' => 1, 'rusak' => 1],
            ],
            'nominal_denda' => 50000,
            'alasan_denda' => '1 unit ALT002 sobek pada frame penyangga.',
            'catatan' => 'Unit rusak dipindahkan ke ruang servis.',
        ]);

        $response->assertRedirect(route('admin.operasional.pengembalian'));

        // ALT001 bertambah 1
        $this->assertEquals($stokAwalAlt1 + 1, Alat::find('ALT001')->stok);
        // ALT002 hanya bertambah 1 (yang rusak 1 tidak masuk stok)
        $this->assertEquals($stokAwalAlt2 + 1, Alat::find('ALT002')->stok);

        // Detail penyewaan mencatat rincian fisik
        $detailAlt2 = DetailPenyewaan::where('no_transaksi', $penyewaan->no_transaksi)
            ->where('kode_alat', 'ALT002')
            ->first();
        $this->assertEquals(1, $detailAlt2->jumlah_kembali_baik);
        $this->assertEquals(1, $detailAlt2->jumlah_rusak);

        // Status pengembalian menjadi Bermasalah karena ada kerusakan
        $this->assertDatabaseHas('pengembalian', [
            'no_transaksi' => 'TRX-20261015-001',
            'status_pengembalian' => 'Bermasalah',
        ]);
    }

    /**
     * Jumlah alat kembali (baik + rusak) harus sama dengan jumlah yang disewa
     */
    public function test_return_fails_if_total_returned_items_mismatches_rented_quantity(): void
    {
        $penyewaan = $this->prepareSedangDisewa();
        $admin = User::where('username', 'admin')->first();

        // ALT002 disewa 2 unit, tapi hanya dikembalikan 1 unit
        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-18',
            'items' => [
                'ALT001' => ['baik' => 1, 'rusak' => 0],
                'ALT002' => ['baik' => 1, 'rusak' => 0],
            ],
            'nominal_denda' => 0,
        ]);

        $response->assertRedirect(route('admin.operasional.pengembalian'));
        $response->assertSessionHas('error');

        // Pastikan belum diselesaikan
        $penyewaan->refresh();
        $this->assertEquals('Sedang Disewa', $penyewaan->status_penyewaan);
    }

    /**
     * Pengguna biasa tidak dapat memproses pengembalian alat
     */
    public function test_non_admin_cannot_process_equipment_return(): void
    {
        $penyewaan = $this->prepareSedangDisewa();
        $pengguna = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($pengguna)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-18',
            'items' => [
                'ALT001' => ['baik' => 1, 'rusak' => 0],
                'ALT002' => ['baik' => 2, 'rusak' => 0],
            ],
        ]);

        $response->assertStatus(403);
    }

    /**
     * Pengembalian 1 unit rusak dari transaksi multi-unit merefleksikan perubahan stok rusak pada dashboard
     */
    public function test_return_one_damaged_unit_from_multi_unit_transaction_updates_dashboard_rusak_stock(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Ambil kondisi dashboard sebelum pengembalian
        $dashBefore = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dashBefore->assertStatus(200);
        $stokRusakAwal = $dashBefore->viewData('stokRusak');
        $stokBaikAwal = $dashBefore->viewData('stokKondisiBaik');

        $penyewaan = $this->prepareSedangDisewa();
        $stokAwalAlt2 = Alat::find('ALT002')->stok;

        // 2. Kembalikan ALT001 (1 unit baik) dan ALT002 (2 unit: 1 baik, 1 rusak)
        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-18',
            'items' => [
                'ALT001' => ['baik' => 1, 'perlu_perbaikan' => 0, 'rusak' => 0],
                'ALT002' => ['baik' => 1, 'perlu_perbaikan' => 0, 'rusak' => 1],
            ],
            'nominal_denda' => 25000,
            'alasan_denda' => '1 unit ALT002 resleting robek permanen.',
        ]);

        $response->assertRedirect(route('admin.operasional.pengembalian'));

        // 3. Pastikan stok toko ALT002 hanya bertambah 1 (unit baik)
        $this->assertEquals($stokAwalAlt2 + 1, Alat::find('ALT002')->stok);

        // 4. Periksa dashboard setelah pengembalian: Stok Rusak harus bertambah 1 unit
        $dashAfter = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dashAfter->assertStatus(200);

        $this->assertEquals($stokRusakAwal + 1, $dashAfter->viewData('stokRusak'));
        $this->assertEquals($stokBaikAwal + 2, $dashAfter->viewData('stokKondisiBaik')); // 1 ALT001 + 1 ALT002
        $dashAfter->assertSee(($stokRusakAwal + 1).' Unit');
    }

    /**
     * Pengembalian barang berkondisi baik dan barang yang perlu perbaikan
     */
    public function test_return_good_and_needs_repair_items_updates_dashboard_accurately(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Ambil kondisi dashboard sebelum pengembalian
        $dashBefore = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dashBefore->assertStatus(200);
        $stokPerbaikanAwal = $dashBefore->viewData('stokPerluPerbaikan');
        $stokBaikAwal = $dashBefore->viewData('stokKondisiBaik');

        $penyewaan = $this->prepareSedangDisewa();
        $stokAwalAlt2 = Alat::find('ALT002')->stok;

        // 2. Kembalikan ALT001 (1 unit baik) dan ALT002 (1 baik, 1 perlu perbaikan, 0 rusak)
        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-18',
            'items' => [
                'ALT001' => ['baik' => 1, 'perlu_perbaikan' => 0, 'rusak' => 0],
                'ALT002' => ['baik' => 1, 'perlu_perbaikan' => 1, 'rusak' => 0],
            ],
            'nominal_denda' => 10000,
            'alasan_denda' => 'Biaya laundry dan pembersihan noda lumpur.',
        ]);

        $response->assertRedirect(route('admin.operasional.pengembalian'));

        // 3. Pastikan database menyimpan kolom jumlah_perlu_perbaikan
        $detailAlt2 = DetailPenyewaan::where('no_transaksi', $penyewaan->no_transaksi)
            ->where('kode_alat', 'ALT002')
            ->first();
        $this->assertEquals(1, $detailAlt2->jumlah_kembali_baik);
        $this->assertEquals(1, $detailAlt2->jumlah_perlu_perbaikan);
        $this->assertEquals(0, $detailAlt2->jumlah_rusak);

        // 4. Pastikan stok toko ALT002 hanya bertambah 1 (unit baik, unit perbaikan tidak masuk stok siap sewa)
        $this->assertEquals($stokAwalAlt2 + 1, Alat::find('ALT002')->stok);

        // 5. Periksa dashboard setelah pengembalian: Stok Perlu Perbaikan bertambah 1
        $dashAfter = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dashAfter->assertStatus(200);
        $this->assertEquals($stokPerbaikanAwal + 1, $dashAfter->viewData('stokPerluPerbaikan'));
        $this->assertEquals($stokBaikAwal + 2, $dashAfter->viewData('stokKondisiBaik'));
        $dashAfter->assertSee(($stokPerbaikanAwal + 1).' Unit');
    }

    /**
     * Konsistensi stok setelah halaman dimuat ulang (page reload)
     */
    public function test_stock_consistency_persists_after_page_reload(): void
    {
        $admin = User::where('username', 'admin')->first();
        $penyewaan = $this->prepareSedangDisewa();

        // Proses pengembalian kombinasi (1 baik, 1 rusak)
        $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-18',
            'items' => [
                'ALT001' => ['baik' => 1, 'perlu_perbaikan' => 0, 'rusak' => 0],
                'ALT002' => ['baik' => 1, 'perlu_perbaikan' => 0, 'rusak' => 1],
            ],
            'nominal_denda' => 0,
        ]);

        // Load 1: Dashboard pertama kali
        $dash1 = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dash1->assertStatus(200);
        $stokBaik1 = $dash1->viewData('stokKondisiBaik');
        $stokPerbaikan1 = $dash1->viewData('stokPerluPerbaikan');
        $stokRusak1 = $dash1->viewData('stokRusak');
        $totalFisik1 = $dash1->viewData('totalStokFisik');

        // Load 2: Reload dashboard halaman yang sama
        $dash2 = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dash2->assertStatus(200);
        $stokBaik2 = $dash2->viewData('stokKondisiBaik');
        $stokPerbaikan2 = $dash2->viewData('stokPerluPerbaikan');
        $stokRusak2 = $dash2->viewData('stokRusak');
        $totalFisik2 = $dash2->viewData('totalStokFisik');

        // Nilai harus persis sama dan konsisten
        $this->assertSame($stokBaik1, $stokBaik2);
        $this->assertSame($stokPerbaikan1, $stokPerbaikan2);
        $this->assertSame($stokRusak1, $stokRusak2);
        $this->assertSame($totalFisik1, $totalFisik2);

        // Nilai stok rusak tidak boleh 0
        $this->assertGreaterThan(0, $stokRusak2);
    }
}

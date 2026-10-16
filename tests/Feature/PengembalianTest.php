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
}

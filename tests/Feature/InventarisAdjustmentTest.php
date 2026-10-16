<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Penyewaan;
use App\Models\User;
use Tests\TestCase;

class InventarisAdjustmentTest extends TestCase
{
    /**
     * Admin dapat mencatat penambahan stok dengan alasan yang wajib diisi
     */
    public function test_admin_can_add_stock_with_mandatory_reason(): void
    {
        $admin = User::where('username', 'admin')->first();
        $alat = Alat::find('ALT001');
        $stokAwal = (int) $alat->stok;

        $response = $this->actingAs($admin)->post(route('admin.inventaris.adjust-stock', 'ALT001'), [
            'tipe_penyesuaian' => 'tambah',
            'jumlah' => 5,
            'alasan' => 'Pengadaan stok tambahan dari gudang cadangan pusat.',
        ]);

        $response->assertRedirect(route('admin.inventaris.show', 'ALT001'));
        $response->assertSessionHas('success');

        // Pastikan stok bertambah
        $alat->refresh();
        $this->assertEquals($stokAwal + 5, $alat->stok);

        // Pastikan waktu dan admin tercatat di audit trail ActivityLog
        $this->assertDatabaseHas('activity_logs', [
            'id_user' => $admin->id_user,
            'aktivitas' => 'Penambahan Stok Manual',
            'modul' => 'Inventaris',
        ]);
    }

    /**
     * Admin dapat mencatat pengurangan stok dengan alasan yang wajib diisi
     */
    public function test_admin_can_reduce_stock_with_mandatory_reason(): void
    {
        $admin = User::where('username', 'admin')->first();
        $alat = Alat::find('ALT002');
        $alat->stok = 10;
        $alat->save();

        $response = $this->actingAs($admin)->post(route('admin.inventaris.adjust-stock', 'ALT002'), [
            'tipe_penyesuaian' => 'kurang',
            'jumlah' => 3,
            'alasan' => 'Afkir 3 unit rusak berat karena sobek permanen saat uji kelayakan.',
        ]);

        $response->assertRedirect(route('admin.inventaris.show', 'ALT002'));
        $response->assertSessionHas('success');

        $alat->refresh();
        $this->assertEquals(7, $alat->stok);

        $this->assertDatabaseHas('activity_logs', [
            'id_user' => $admin->id_user,
            'aktivitas' => 'Pengurangan Stok Manual',
            'modul' => 'Inventaris',
        ]);
    }

    /**
     * Sistem mencegah stok negatif jika pengurangan melebihi stok yang ada
     */
    public function test_system_prevents_negative_stock(): void
    {
        $admin = User::where('username', 'admin')->first();
        $alat = Alat::find('ALT001');
        $alat->stok = 2;
        $alat->save();

        // Coba kurangi 5 unit ketika stok hanya 2 unit
        $response = $this->actingAs($admin)->post(route('admin.inventaris.adjust-stock', 'ALT001'), [
            'tipe_penyesuaian' => 'kurang',
            'jumlah' => 5,
            'alasan' => 'Pengurangan melebihi stok.',
        ]);

        $response->assertSessionHasErrors('jumlah');

        // Pastikan stok tidak berubah dan tidak menjadi negatif
        $alat->refresh();
        $this->assertEquals(2, $alat->stok);
    }

    /**
     * Penyesuaian stok wajib menyertakan alasan yang jelas
     */
    public function test_stock_adjustment_requires_mandatory_reason(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.inventaris.adjust-stock', 'ALT001'), [
            'tipe_penyesuaian' => 'tambah',
            'jumlah' => 2,
            'alasan' => '', // Alasan kosong
        ]);

        $response->assertSessionHasErrors('alasan');
    }

    /**
     * Pengguna dan Pemilik dilarang mengubah inventaris
     */
    public function test_non_admin_cannot_adjust_stock(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        $pemilik = User::where('username', 'pemilik')->first();

        $this->actingAs($pengguna)->post(route('admin.inventaris.adjust-stock', 'ALT001'), [
            'tipe_penyesuaian' => 'tambah',
            'jumlah' => 1,
            'alasan' => 'Upaya ilegal Pengguna',
        ])->assertStatus(403);

        $this->actingAs($pemilik)->post(route('admin.inventaris.adjust-stock', 'ALT001'), [
            'tipe_penyesuaian' => 'tambah',
            'jumlah' => 1,
            'alasan' => 'Upaya ilegal Pemilik',
        ])->assertStatus(403);
    }

    /**
     * Perubahan inventaris tidak mengubah tagihan atau transaksi sewa lama
     */
    public function test_stock_adjustment_does_not_alter_existing_transactions_or_billing(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Cek total biaya dan detail transaksi sewa yang ada
        $penyewaan = Penyewaan::find('TRX-20261015-001');
        $biayaAwal = (float) $penyewaan->total_biaya;
        $detailAwal = DetailPenyewaan::where('no_transaksi', 'TRX-20261015-001')->get();

        // Lakukan penyesuaian stok alat ALT001 dan ALT002
        $this->actingAs($admin)->post(route('admin.inventaris.adjust-stock', 'ALT001'), [
            'tipe_penyesuaian' => 'tambah',
            'jumlah' => 10,
            'alasan' => 'Penambahan unit cadangan.',
        ]);

        // Verifikasi transaksi sewa lama tidak terpengaruh
        $penyewaan->refresh();
        $this->assertEquals($biayaAwal, (float) $penyewaan->total_biaya);

        $detailSesudah = DetailPenyewaan::where('no_transaksi', 'TRX-20261015-001')->get();
        foreach ($detailAwal as $index => $itemAwal) {
            $this->assertEquals($itemAwal->jumlah, $detailSesudah[$index]->jumlah);
            $this->assertEquals($itemAwal->tarif_sewa, $detailSesudah[$index]->tarif_sewa);
        }
    }
}

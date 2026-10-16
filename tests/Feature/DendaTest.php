<?php

namespace Tests\Feature;

use App\Models\Denda;
use App\Models\Pembayaran;
use App\Models\Pengambilan;
use App\Models\Pengembalian;
use App\Models\Penyewaan;
use App\Models\User;
use Tests\TestCase;

class DendaTest extends TestCase
{
    /**
     * Siapkan transaksi aktif Sedang Disewa
     */
    private function prepareSedangDisewa(string $noTransaksi = 'TRX-20261015-001'): Penyewaan
    {
        $admin = User::where('username', 'admin')->first();

        $penyewaan = Penyewaan::find($noTransaksi);
        $penyewaan->status_penyewaan = 'Sedang Disewa';
        $penyewaan->tanggal_sewa = '2026-10-10';
        $penyewaan->tanggal_kembali = '2026-10-12'; // 2 hari sewa
        $penyewaan->save();

        Pembayaran::updateOrCreate(
            ['no_transaksi' => $noTransaksi],
            [
                'no_pembayaran' => 'PAY-DENDA-001',
                'total_biaya' => $penyewaan->total_biaya,
                'jumlah_pembayaran' => $penyewaan->total_biaya,
                'metode_pembayaran' => 'Cash',
                'status_pembayaran' => 'Lunas',
                'id_admin' => $admin->id_user,
                'waktu_konfirmasi' => now(),
            ]
        );

        Pengambilan::updateOrCreate(
            ['no_transaksi' => $noTransaksi],
            [
                'tanggal_pengambilan' => '2026-10-10',
                'id_admin' => $admin->id_user,
                'nama_penerima' => 'Andi Pratama',
                'catatan' => 'Diserahkan lengkap',
            ]
        );

        foreach ($penyewaan->detailPenyewaan as $detail) {
            $detail->jumlah_diserahkan = $detail->jumlah;
            $detail->save();
        }

        return $penyewaan;
    }

    /**
     * Denda keterlambatan dihitung transparan sesuai aturan bisnis (hari terlambat x total tarif sewa harian)
     */
    public function test_late_rental_penalty_is_calculated_and_stored_with_details(): void
    {
        $penyewaan = $this->prepareSedangDisewa();
        $admin = User::where('username', 'admin')->first();

        // Tanggal sewa: 10-12 Okt. Dikembalikan tanggal 14 Okt (terlambat 2 hari).
        // Total tarif harian ALT001 (1 x 50.000) + ALT002 (2 x 25.000) = 100.000/hari
        // Denda keterlambatan 2 hari x 100.000 = 200.000
        $totalTarifHarian = (float) $penyewaan->detailPenyewaan->sum(function ($item) {
            return (float) $item->jumlah * (float) $item->tarif_sewa;
        });
        $hariTerlambat = 2;
        $nominalDenda = $hariTerlambat * $totalTarifHarian;

        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-14',
            'items' => [
                'ALT001' => ['baik' => 1, 'rusak' => 0],
                'ALT002' => ['baik' => 2, 'rusak' => 0],
            ],
            'nominal_denda' => $nominalDenda,
            'alasan_denda' => "Terlambat {$hariTerlambat} hari dari batas kembali 12 Okt 2026.",
        ]);

        $response->assertRedirect(route('admin.operasional.pengembalian'));

        // Pastikan denda tersimpan dengan rincian dan alasan jelas
        $pengembalian = Pengembalian::where('no_transaksi', $penyewaan->no_transaksi)->first();
        $this->assertNotNull($pengembalian);

        $this->assertDatabaseHas('denda', [
            'id_pengembalian' => $pengembalian->id_pengembalian,
            'nominal_denda' => $nominalDenda,
            'alasan_denda' => "Terlambat {$hariTerlambat} hari dari batas kembali 12 Okt 2026.",
        ]);
    }

    /**
     * Denda wajib menyertakan alasan ketika nominal denda > 0
     */
    public function test_fine_requires_transparent_reason_when_penalty_is_assessed(): void
    {
        $penyewaan = $this->prepareSedangDisewa();
        $admin = User::where('username', 'admin')->first();

        // Denda 100.000 tanpa mengisi alasan denda
        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-13',
            'items' => [
                'ALT001' => ['baik' => 1, 'rusak' => 0],
                'ALT002' => ['baik' => 2, 'rusak' => 0],
            ],
            'nominal_denda' => 100000,
            'alasan_denda' => '', // Alasan kosong
        ]);

        $response->assertSessionHasErrors('alasan_denda');

        // Pastikan tidak ada pengembalian & denda yang tersimpan
        $this->assertDatabaseMissing('pengembalian', [
            'no_transaksi' => $penyewaan->no_transaksi,
        ]);
        $this->assertDatabaseMissing('denda', [
            'nominal_denda' => 100000,
        ]);
    }

    /**
     * Mencegah denda dan pengembalian dicatat dua kali untuk pengembalian yang sama
     */
    public function test_prevent_duplicate_return_and_duplicate_penalty(): void
    {
        $penyewaan = $this->prepareSedangDisewa();
        $admin = User::where('username', 'admin')->first();

        // Pengembalian pertama sukses
        $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-14',
            'items' => [
                'ALT001' => ['baik' => 1, 'rusak' => 0],
                'ALT002' => ['baik' => 2, 'rusak' => 0],
            ],
            'nominal_denda' => 50000,
            'alasan_denda' => 'Keterlambatan pengembalian 1 hari.',
        ]);

        $this->assertEquals(1, Denda::count());

        // Pengembalian kedua untuk transaksi yang sama harus ditolak
        $secondResponse = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-14',
            'items' => [
                'ALT001' => ['baik' => 1, 'rusak' => 0],
                'ALT002' => ['baik' => 2, 'rusak' => 0],
            ],
            'nominal_denda' => 50000,
            'alasan_denda' => 'Keterlambatan pengembalian 1 hari.',
        ]);

        $secondResponse->assertSessionHas('error');

        // Jumlah denda tetap 1 (tidak terduplikasi)
        $this->assertEquals(1, Denda::count());
    }

    /**
     * Pengguna dapat melihat status pengambilan, pengembalian, serta rincian dan alasan denda pada dashboard
     */
    public function test_user_can_view_pickup_return_and_fine_breakdown_on_dashboard(): void
    {
        $penyewaan = $this->prepareSedangDisewa();
        $admin = User::where('username', 'admin')->first();
        $pengguna = User::where('username', 'pelanggan')->first();

        // Admin selesaikan pengembalian dengan denda
        $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', $penyewaan->no_transaksi), [
            'tanggal_pengembalian_aktual' => '2026-10-14',
            'items' => [
                'ALT001' => ['baik' => 1, 'rusak' => 0],
                'ALT002' => ['baik' => 1, 'rusak' => 1],
            ],
            'nominal_denda' => 75000,
            'alasan_denda' => 'Terlambat 2 hari dan 1 unit sleeping bag terbakar percikan api unggun.',
        ]);

        // Login sebagai Pengguna dan buka dashboard
        $response = $this->actingAs($pengguna)->get(route('pengguna.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('TRX-20261015-001');
        $response->assertSee('Status Pengambilan:');
        $response->assertSee('Status Pengembalian:');
        $response->assertSee('Denda Keterlambatan / Kerusakan');
        $response->assertSee('Rp 75.000');
        $response->assertSee('Terlambat 2 hari dan 1 unit sleeping bag terbakar percikan api unggun.');
    }
}

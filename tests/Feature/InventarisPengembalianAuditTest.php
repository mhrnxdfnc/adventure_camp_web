<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pembayaran;
use App\Models\Pengembalian;
use App\Models\Penyewaan;
use App\Models\RiwayatStok;
use App\Models\User;
use Tests\TestCase;

class InventarisPengembalianAuditTest extends TestCase
{
    private function prepareActiveRental(string $noTransaksi, string $kodeAlat, int $jumlah = 3): Penyewaan
    {
        $admin = User::where('username', 'admin')->first();

        // Siapkan master alat dalam kondisi Baik jika belum ada
        $alat = Alat::firstOrCreate(
            ['kode_alat' => $kodeAlat],
            [
                'nama_alat' => 'Tenda Dome Audit 4P',
                'kategori' => 'Tenda',
                'stok' => 10,
                'kondisi' => 'Baik',
                'tarif_sewa' => 50000,
            ]
        );

        $penyewaan = Penyewaan::updateOrCreate(
            ['no_transaksi' => $noTransaksi],
            [
                'id_pelanggan' => 1,
                'id_user' => 3, // pelanggan
                'tanggal_sewa' => '2026-10-10',
                'tanggal_kembali' => '2026-10-12', // 2 hari
                'status_penyewaan' => 'Siap Diambil',
                'sumber_pesanan' => 'online',
            ]
        );

        DetailPenyewaan::updateOrCreate(
            [
                'no_transaksi' => $noTransaksi,
                'kode_alat' => $kodeAlat,
            ],
            [
                'jumlah' => $jumlah,
                'tarif_sewa' => 50000,
                'jumlah_diserahkan' => 0,
                'jumlah_kembali_baik' => 0,
                'jumlah_perlu_perbaikan' => 0,
                'jumlah_diperbaiki' => 0,
                'jumlah_rusak' => 0,
            ]
        );

        Pembayaran::updateOrCreate(
            ['no_transaksi' => $noTransaksi],
            [
                'no_pembayaran' => 'PAY-AUDIT-'.substr(md5($noTransaksi), 0, 8),
                'total_biaya' => $jumlah * 50000 * 2,
                'jumlah_pembayaran' => $jumlah * 50000 * 2,
                'metode_pembayaran' => 'Transfer Bank',
                'status_pembayaran' => 'Lunas',
                'id_admin' => $admin->id_user,
                'waktu_konfirmasi' => now(),
            ]
        );

        return $penyewaan;
    }

    /**
     * Requirement 1 & 4: Membedakan stok fisik total, stok siap sewa, stok disewa, perlu perbaikan, dan rusak.
     */
    public function test_distinguish_physical_stock_available_rented_repair_and_damaged(): void
    {
        $alat = Alat::updateOrCreate(
            ['kode_alat' => 'ALT-ADT01'],
            [
                'nama_alat' => 'Kompor Gas Camping Audit',
                'kategori' => 'Masak',
                'stok' => 8,
                'kondisi' => 'Baik',
                'tarif_sewa' => 20000,
            ]
        );

        // Buat 1 rental yang sedang disewa (2 unit)
        $penyewaan = $this->prepareActiveRental('TRX-ADT-DIST-01', 'ALT-ADT01', 2);
        $admin = User::where('username', 'admin')->first();

        // Admin serahkan alat
        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengambilan', 'TRX-ADT-DIST-01'), [
            'tanggal_pengambilan' => '2026-10-10 10:00:00',
            'nama_penerima' => 'Budi Santoso',
            'items' => [
                'ALT-ADT01' => ['jumlah_diserahkan' => 2],
            ],
        ]);

        $response->assertRedirect(route('admin.operasional.pengambilan'));

        // Cek stok setelah serah terima: stok di toko berkurang 2 (dari 8 jadi 6)
        $alat->refresh();
        $this->assertEquals(6, $alat->stok);

        // Buat 1 rental historis yang telah dikembalikan dengan 1 perlu perbaikan dan 1 rusak
        $penyewaanSelesai = $this->prepareActiveRental('TRX-ADT-DIST-02', 'ALT-ADT01', 2);
        $penyewaanSelesai->status_penyewaan = 'Sedang Disewa';
        $penyewaanSelesai->save();

        $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', 'TRX-ADT-DIST-02'), [
            'tanggal_pengembalian_aktual' => '2026-10-12',
            'items' => [
                'ALT-ADT01' => ['baik' => 0, 'perlu_perbaikan' => 1, 'rusak' => 1],
            ],
            'nominal_denda' => 25000,
            'alasan_denda' => '1 unit rusak fisik dan 1 perlu servis pemantik.',
        ]);

        // Periksa perhitungan metrik model Alat
        $alat->refresh();
        $this->assertEquals(6, $alat->stok_siap_sewa); // Hanya 6 unit baik di toko
        $this->assertEquals(2, $alat->stok_sedang_disewa); // 2 unit di tangan TRX-AUDIT-DIST-01
        $this->assertEquals(1, $alat->stok_perlu_perbaikan); // 1 unit perlu perbaikan dari TRX-AUDIT-DIST-02
        $this->assertEquals(1, $alat->stok_rusak); // 1 unit rusak dari TRX-AUDIT-DIST-02
        $this->assertEquals(10, $alat->stok_fisik_total); // 6 + 2 + 1 + 1 = 10 unit fisik total

        // Ringkasan global inventaris juga mencerminkan hal yang sama
        $summary = Alat::getRingkasanInventaris();
        $this->assertGreaterThanOrEqual(10, $summary['total_stok_fisik']);
        $this->assertGreaterThanOrEqual(6, $summary['stok_siap_sewa']);
        $this->assertGreaterThanOrEqual(2, $summary['stok_sedang_disewa']);
        $this->assertGreaterThanOrEqual(1, $summary['stok_perlu_perbaikan']);
        $this->assertGreaterThanOrEqual(1, $summary['stok_rusak']);
    }

    /**
     * Requirement 2 & 3: Pengembalian sebagian unit (1 dari 3 unit rusak, 2 unit baik).
     * Tidak boleh menganggap seluruh unit rusak.
     */
    public function test_return_partial_condition_units_one_of_three_damaged(): void
    {
        $penyewaan = $this->prepareActiveRental('TRX-PART-001', 'ALT-PART1', 3);
        $admin = User::where('username', 'admin')->first();

        // 1. Serahkan 3 unit
        $this->actingAs($admin)->post(route('admin.operasional.proses-pengambilan', 'TRX-PART-001'), [
            'tanggal_pengambilan' => '2026-10-10 09:00:00',
            'nama_penerima' => 'Ahmad Pelanggan',
            'items' => [
                'ALT-PART1' => ['jumlah_diserahkan' => 3],
            ],
        ]);

        $stokSetelahAmbil = Alat::find('ALT-PART1')->stok; // 10 - 3 = 7

        // 2. Kembalikan 3 unit: 2 Baik, 0 Perlu Perbaikan, 1 Rusak
        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', 'TRX-PART-001'), [
            'tanggal_pengembalian_aktual' => '2026-10-12',
            'items' => [
                'ALT-PART1' => ['baik' => 2, 'perlu_perbaikan' => 0, 'rusak' => 1],
            ],
            'nominal_denda' => 50000,
            'alasan_denda' => '1 dari 3 unit robek pada bagian kain penutup.',
            'catatan' => '2 unit langsung kembali ke rak siap sewa, 1 unit diafkir ke ruang rusak.',
        ]);

        $response->assertRedirect(route('admin.operasional.pengembalian'));
        $response->assertSessionHas('success');

        // 3. Stok toko hanya bertambah 2 (unit baik), BUKAN 3
        $alat = Alat::find('ALT-PART1');
        $this->assertEquals($stokSetelahAmbil + 2, $alat->stok);

        // 4. Detail transaksi mencatat rincian per kondisi secara presisi
        $detail = DetailPenyewaan::where('no_transaksi', 'TRX-PART-001')
            ->where('kode_alat', 'ALT-PART1')
            ->first();

        $this->assertEquals(2, $detail->jumlah_kembali_baik);
        $this->assertEquals(0, $detail->jumlah_perlu_perbaikan);
        $this->assertEquals(1, $detail->jumlah_rusak);
        $this->assertEquals(0, $detail->jumlah_diperbaiki);

        // 5. Tabel pengembalian mencatat ringkasan kondisi dan status Bermasalah
        $pengembalian = Pengembalian::where('no_transaksi', 'TRX-PART-001')->first();
        $this->assertNotNull($pengembalian);
        $this->assertEquals('Bermasalah', $pengembalian->status_pengembalian);
        $this->assertStringContainsString('2 Baik', $pengembalian->kondisi_alat);
        $this->assertStringContainsString('1 Rusak', $pengembalian->kondisi_alat);
    }

    /**
     * Requirement 4: Alat berstatus Perlu Perbaikan atau Rusak tidak dihitung sebagai stok siap sewa.
     */
    public function test_repair_and_damaged_units_are_excluded_from_ready_to_rent_stock(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Buat alat dengan kondisi Perlu Perbaikan
        $alatServis = Alat::updateOrCreate(
            ['kode_alat' => 'ALT-SVS01'],
            [
                'nama_alat' => 'Tenda Butuh Servis',
                'kategori' => 'Tenda',
                'stok' => 5,
                'kondisi' => 'Perlu Perbaikan',
                'tarif_sewa' => 45000,
            ]
        );

        $this->assertFalse($alatServis->isSiapSewa());
        $this->assertEquals(0, $alatServis->stok_siap_sewa);
        $this->assertEquals(0, Penyewaan::hitungStokTersediaPeriode('ALT-SVS01', '2026-10-15', '2026-10-18'));

        // Buat alat dengan kondisi Rusak
        $alatRusak = Alat::updateOrCreate(
            ['kode_alat' => 'ALT-RSK02'],
            [
                'nama_alat' => 'Matras Rusak Total',
                'kategori' => 'Matras',
                'stok' => 4,
                'kondisi' => 'Rusak',
                'tarif_sewa' => 10000,
            ]
        );

        $this->assertFalse($alatRusak->isSiapSewa());
        $this->assertEquals(0, $alatRusak->stok_siap_sewa);
        $this->assertEquals(0, Penyewaan::hitungStokTersediaPeriode('ALT-RSK02', '2026-10-15', '2026-10-18'));
    }

    /**
     * Requirement 5: Alat yang selesai diperbaiki hanya boleh kembali tersedia setelah kondisinya
     * dikonfirmasi oleh petugas yang berwenang (Admin).
     */
    public function test_authorized_staff_can_confirm_repaired_units_to_become_available_stock(): void
    {
        $penyewaan = $this->prepareActiveRental('TRX-REP-001', 'ALT-REP01', 3);
        $admin = User::where('username', 'admin')->first();

        // Serahkan 3 unit
        $this->actingAs($admin)->post(route('admin.operasional.proses-pengambilan', 'TRX-REP-001'), [
            'tanggal_pengambilan' => '2026-10-10 10:00:00',
            'nama_penerima' => 'Doni',
            'items' => [
                'ALT-REP01' => ['jumlah_diserahkan' => 3],
            ],
        ]);

        // Kembalikan: 1 Baik, 2 Perlu Perbaikan
        $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', 'TRX-REP-001'), [
            'tanggal_pengembalian_aktual' => '2026-10-12',
            'items' => [
                'ALT-REP01' => ['baik' => 1, 'perlu_perbaikan' => 2, 'rusak' => 0],
            ],
            'nominal_denda' => 20000,
            'alasan_denda' => '2 unit perlu penggantian tali penarik dan resleting.',
        ]);

        $alat = Alat::find('ALT-REP01');
        $stokSebelumKonfirmasi = $alat->stok; // 10 - 3 + 1 = 8

        $detail = DetailPenyewaan::where('no_transaksi', 'TRX-REP-001')
            ->where('kode_alat', 'ALT-REP01')
            ->first();

        $this->assertEquals(2, $detail->jumlah_menunggu_perbaikan);

        // Petugas yang berwenang (Admin) mengonfirmasi 1 unit telah selesai diperbaiki
        $response = $this->actingAs($admin)->post(route('admin.inventaris.konfirmasi-perbaikan', 'ALT-REP01'), [
            'id_detail' => $detail->id_detail,
            'jumlah' => 1,
            'catatan' => 'Resleting unit 1 sudah diganti baru dan lulus tes tarik.',
        ]);

        $response->assertRedirect(route('admin.inventaris.show', 'ALT-REP01'));
        $response->assertSessionHas('success');

        // Pastikan stok siap sewa bertambah 1 unit
        $alat->refresh();
        $this->assertEquals($stokSebelumKonfirmasi + 1, $alat->stok);

        // Pastikan tracking detail diperbarui
        $detail->refresh();
        $this->assertEquals(1, $detail->jumlah_diperbaiki);
        $this->assertEquals(1, $detail->jumlah_menunggu_perbaikan);

        // Pastikan tercatat di RiwayatStok & ActivityLog
        $this->assertDatabaseHas('riwayat_stok', [
            'kode_alat' => 'ALT-REP01',
            'jenis_perubahan' => 'Selesai Perbaikan',
            'jumlah_perubahan' => 1,
            'stok_sesudah' => $alat->stok,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'id_user' => $admin->id_user,
            'aktivitas' => 'Konfirmasi Perbaikan Alat',
            'modul' => 'Inventaris',
        ]);
    }

    /**
     * Petugas tidak boleh mengonfirmasi unit melebihi yang sedang menunggu perbaikan.
     */
    public function test_repair_confirmation_prevents_confirming_more_units_than_pending(): void
    {
        $penyewaan = $this->prepareActiveRental('TRX-REP-002', 'ALT-REP02', 2);
        $admin = User::where('username', 'admin')->first();

        $this->actingAs($admin)->post(route('admin.operasional.proses-pengambilan', 'TRX-REP-002'), [
            'tanggal_pengambilan' => '2026-10-10 10:00:00',
            'nama_penerima' => 'Doni',
            'items' => [
                'ALT-REP02' => ['jumlah_diserahkan' => 2],
            ],
        ]);

        $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', 'TRX-REP-002'), [
            'tanggal_pengembalian_aktual' => '2026-10-12',
            'items' => [
                'ALT-REP02' => ['baik' => 1, 'perlu_perbaikan' => 1, 'rusak' => 0],
            ],
            'nominal_denda' => 0,
        ]);

        $detail = DetailPenyewaan::where('no_transaksi', 'TRX-REP-002')
            ->where('kode_alat', 'ALT-REP02')
            ->first();

        // Hanya 1 unit pending, coba konfirmasi 5 unit
        $response = $this->actingAs($admin)->post(route('admin.inventaris.konfirmasi-perbaikan', 'ALT-REP02'), [
            'id_detail' => $detail->id_detail,
            'jumlah' => 5,
        ]);

        $response->assertSessionHas('error');

        // Pastikan tidak ada stok bertambah
        $detail->refresh();
        $this->assertEquals(0, $detail->jumlah_diperbaiki);
    }

    /**
     * Pengguna dan Pemilik dilarang mengonfirmasi perbaikan alat.
     */
    public function test_non_admin_cannot_confirm_repaired_units(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        $pemilik = User::where('username', 'pemilik')->first();

        $this->actingAs($pengguna)->post(route('admin.inventaris.konfirmasi-perbaikan', 'ALT001'), [
            'jumlah' => 1,
        ])->assertStatus(403);

        $this->actingAs($pemilik)->post(route('admin.inventaris.konfirmasi-perbaikan', 'ALT001'), [
            'jumlah' => 1,
        ])->assertStatus(403);
    }

    /**
     * Requirement 6: Pastikan jumlah stok tidak bertambah dua kali ketika pengembalian diproses ulang.
     */
    public function test_prevent_duplicate_return_processing_and_double_stock_addition(): void
    {
        $penyewaan = $this->prepareActiveRental('TRX-DUP-001', 'ALT-DUP01', 2);
        $admin = User::where('username', 'admin')->first();

        // Serahkan 2 unit
        $this->actingAs($admin)->post(route('admin.operasional.proses-pengambilan', 'TRX-DUP-001'), [
            'tanggal_pengambilan' => '2026-10-10 10:00:00',
            'nama_penerima' => 'Eko',
            'items' => [
                'ALT-DUP01' => ['jumlah_diserahkan' => 2],
            ],
        ]);

        $stokSetelahAmbil = Alat::find('ALT-DUP01')->stok; // 10 - 2 = 8

        // Pengembalian pertama (2 Baik)
        $firstResponse = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', 'TRX-DUP-001'), [
            'tanggal_pengembalian_aktual' => '2026-10-12',
            'items' => [
                'ALT-DUP01' => ['baik' => 2, 'perlu_perbaikan' => 0, 'rusak' => 0],
            ],
            'nominal_denda' => 0,
        ]);

        $firstResponse->assertRedirect(route('admin.operasional.pengembalian'));

        $stokSetelahKembali = Alat::find('ALT-DUP01')->stok;
        $this->assertEquals($stokSetelahAmbil + 2, $stokSetelahKembali); // 10

        // Pengembalian kedua untuk transaksi yang sama harus ditolak
        $secondResponse = $this->actingAs($admin)->post(route('admin.operasional.proses-pengembalian', 'TRX-DUP-001'), [
            'tanggal_pengembalian_aktual' => '2026-10-12',
            'items' => [
                'ALT-DUP01' => ['baik' => 2, 'perlu_perbaikan' => 0, 'rusak' => 0],
            ],
            'nominal_denda' => 0,
        ]);

        $secondResponse->assertSessionHas('error');

        // Pastikan stok TIDAK bertambah dua kali!
        $this->assertEquals($stokSetelahKembali, Alat::find('ALT-DUP01')->stok);
    }

    /**
     * Requirement 8: Pastikan dashboard Admin dan Pemilik mengambil jumlah kondisi alat dari sumber data yang konsisten.
     */
    public function test_admin_and_pemilik_dashboards_use_consistent_inventory_source_data(): void
    {
        $admin = User::where('username', 'admin')->first();
        $pemilik = User::where('username', 'pemilik')->first();

        // Ambil data dashboard admin
        $adminDash = $this->actingAs($admin)->get(route('admin.dashboard'));
        $adminDash->assertStatus(200);

        $adminBaik = $adminDash->viewData('stokKondisiBaik');
        $adminPerluPerbaikan = $adminDash->viewData('stokPerluPerbaikan');
        $adminRusak = $adminDash->viewData('stokRusak');
        $adminTotalFisik = $adminDash->viewData('totalStokFisik');

        // Ambil data dashboard pemilik
        $pemilikDash = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));
        $pemilikDash->assertStatus(200);

        $pemilikRingkasan = $pemilikDash->viewData('ringkasanInventaris');
        $this->assertNotNull($pemilikRingkasan);

        // Verifikasi bahwa angka kondisi alat pada kedua dashboard persis sama dan konsisten
        $this->assertEquals($adminBaik, $pemilikRingkasan['stok_siap_sewa']);
        $this->assertEquals($adminPerluPerbaikan, $pemilikRingkasan['stok_perlu_perbaikan']);
        $this->assertEquals($adminRusak, $pemilikRingkasan['stok_rusak']);
        $this->assertEquals($adminTotalFisik, $pemilikRingkasan['total_stok_fisik']);
    }

    /**
     * Mengonfirmasi perbaikan alat berstatus master Perlu Perbaikan mengubah kondisinya menjadi Baik.
     */
    public function test_confirm_master_tool_repair_restores_condition_to_baik(): void
    {
        $admin = User::where('username', 'admin')->first();

        $alat = Alat::updateOrCreate(
            ['kode_alat' => 'ALT-MREP1'],
            [
                'nama_alat' => 'Lentera Camping Servis',
                'kategori' => 'Penerangan',
                'stok' => 4,
                'kondisi' => 'Perlu Perbaikan',
                'tarif_sewa' => 15000,
            ]
        );

        $this->assertFalse($alat->isSiapSewa());

        $response = $this->actingAs($admin)->post(route('admin.inventaris.konfirmasi-perbaikan', 'ALT-MREP1'), [
            'jumlah' => 1,
            'catatan' => 'Pemeriksaan kelistrikan baterai selesai dan berfungsi normal.',
        ]);

        $response->assertRedirect(route('admin.inventaris.show', 'ALT-MREP1'));
        $response->assertSessionHas('success');

        $alat->refresh();
        $this->assertEquals('Baik', $alat->kondisi);
        $this->assertTrue($alat->isSiapSewa());
        $this->assertEquals(4, $alat->stok_siap_sewa);
    }
}

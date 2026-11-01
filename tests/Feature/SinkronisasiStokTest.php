<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pelanggan;
use App\Models\Penyewaan;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class SinkronisasiStokTest extends TestCase
{
    /**
     * Hitung stok tersedia periode tidak melakukan pengurangan ganda pada unit yang sedang disewa
     */
    public function test_hitung_stok_tersedia_periode_does_not_double_deduct_active_rentals(): void
    {
        $alat = Alat::create([
            'kode_alat' => 'ALT-SYNC-01',
            'nama_alat' => 'Tenda Dome Sync Test',
            'kategori' => 'Tenda',
            'stok' => 5, // Di rak toko ada 5 unit
            'kondisi' => 'Baik',
            'tarif_sewa' => 50000,
        ]);

        $pelanggan = Pelanggan::first();

        // Buat transaksi yang sedang aktif di lapangan (Sedang Disewa) untuk 2 unit
        // Tanggal sewa: hari ini s/d lusa
        $penyewaanAktif = Penyewaan::create([
            'no_transaksi' => 'TRX-SYNC-01',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'tanggal_sewa' => Carbon::today()->toDateString(),
            'tanggal_kembali' => Carbon::today()->addDays(2)->toDateString(),
            'total_biaya' => 200000,
            'status_penyewaan' => 'Sedang Disewa',
            'sumber_pesanan' => 'online',
        ]);

        DetailPenyewaan::create([
            'no_transaksi' => $penyewaanAktif->no_transaksi,
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 2,
            'jumlah_diserahkan' => 2,
            'tarif_sewa' => 50000,
            'subtotal' => 200000,
        ]);

        // Kapasitas total alat layak adalah 5 (di toko) + 2 (sedang disewa) = 7 unit
        $this->assertEquals(7, $alat->total_kapasitas_layak);

        // Kasus 1: Untuk periode yang bertabrakan dengan sewa aktif (hari ini s/d besok)
        // Stok tersedia harus 5 unit (7 total - 2 sedang disewa = 5 unit, BUKAN 5 - 2 = 3 unit)
        $stokTersediaPeriodeTabrakan = Penyewaan::hitungStokTersediaPeriode(
            $alat->kode_alat,
            Carbon::today()->toDateString(),
            Carbon::today()->addDays(1)->toDateString()
        );
        $this->assertEquals(5, $stokTersediaPeriodeTabrakan);

        // Kasus 2: Untuk periode masa depan setelah sewa aktif selesai (misal minggu depan)
        // Stok tersedia harus penuh 7 unit karena penyewaan aktif sudah selesai sebelum periode tersebut
        $stokTersediaPeriodeMasaDepan = Penyewaan::hitungStokTersediaPeriode(
            $alat->kode_alat,
            Carbon::today()->addDays(10)->toDateString(),
            Carbon::today()->addDays(12)->toDateString()
        );
        $this->assertEquals(7, $stokTersediaPeriodeMasaDepan);
    }

    /**
     * Pesanan 'Menunggu Konfirmasi' dan 'Siap Diambil' mengalokasikan stok dan mencegah overselling
     */
    public function test_pending_confirmation_rentals_reserve_stock_and_prevent_overselling(): void
    {
        $alat = Alat::create([
            'kode_alat' => 'ALT-SYNC-02',
            'nama_alat' => 'Kompor Gas Camping',
            'kategori' => 'Masak',
            'stok' => 3, // Total kapasitas fisik 3 unit
            'kondisi' => 'Baik',
            'tarif_sewa' => 25000,
        ]);

        $pelanggan = Pelanggan::first();
        $tglSewa = Carbon::today()->addDays(3)->toDateString();
        $tglKembali = Carbon::today()->addDays(5)->toDateString();

        // Sebelum ada pesanan, ketersediaan adalah 3 unit
        $this->assertEquals(3, Penyewaan::hitungStokTersediaPeriode($alat->kode_alat, $tglSewa, $tglKembali));

        // Buat pesanan baru berstatus 'Menunggu Pembayaran' sebanyak 2 unit
        $pesananPending = Penyewaan::create([
            'no_transaksi' => 'TRX-SYNC-02',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'total_biaya' => 100000,
            'status_penyewaan' => 'Menunggu Pembayaran',
            'sumber_pesanan' => 'online',
        ]);

        DetailPenyewaan::create([
            'no_transaksi' => $pesananPending->no_transaksi,
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 2,
            'tarif_sewa' => 25000,
            'subtotal' => 100000,
        ]);

        // Ketersediaan pada periode tersebut sekarang harus tersisa 1 unit
        $this->assertEquals(1, Penyewaan::hitungStokTersediaPeriode($alat->kode_alat, $tglSewa, $tglKembali));

        // Jika pesanan dibatalkan, ketersediaan harus kembali penuh menjadi 3 unit
        $pesananPending->update(['status_penyewaan' => 'Dibatalkan']);
        $this->assertEquals(3, Penyewaan::hitungStokTersediaPeriode($alat->kode_alat, $tglSewa, $tglKembali));
    }

    /**
     * Dashboard dan inventaris Pemilik menampilkan rincian barang dialokasikan dan ringkasan yang akurat
     */
    public function test_pemilik_can_view_allocated_and_rented_stock_drilldown(): void
    {
        $pemilik = User::where('role', 'Pemilik')->first();
        if ($pemilik === null) {
            $pemilik = User::create([
                'username' => 'pemilik_test',
                'password' => bcrypt('password'),
                'nama_lengkap' => 'Bapak Pemilik',
                'role' => 'Pemilik',
                'is_active' => true,
            ]);
        }

        $alat = Alat::create([
            'kode_alat' => 'ALT-SYNC-03',
            'nama_alat' => 'Lampu Tenda LED',
            'kategori' => 'Penerangan',
            'stok' => 10,
            'kondisi' => 'Baik',
            'tarif_sewa' => 15000,
        ]);

        $pelanggan = Pelanggan::first();

        // Buat pesanan status 'Siap Diambil' untuk 3 unit
        $pesananSiapDiambil = Penyewaan::create([
            'no_transaksi' => 'TRX-SYNC-03A',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'tanggal_sewa' => Carbon::today()->toDateString(),
            'tanggal_kembali' => Carbon::today()->addDays(2)->toDateString(),
            'total_biaya' => 90000,
            'status_penyewaan' => 'Siap Diambil',
            'sumber_pesanan' => 'online',
        ]);

        DetailPenyewaan::create([
            'no_transaksi' => $pesananSiapDiambil->no_transaksi,
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 3,
            'tarif_sewa' => 15000,
            'subtotal' => 90000,
        ]);

        // Cek getDaftarDialokasikan
        $daftarDialokasikan = Alat::getDaftarDialokasikan('ALT-SYNC-03');
        $this->assertTrue($daftarDialokasikan->contains('no_transaksi', 'TRX-SYNC-03A'));

        // Akses dashboard Pemilik
        $responseDashboard = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));
        $responseDashboard->assertStatus(200);
        $responseDashboard->assertSee('Rincian Unit Terpesan / Menunggu Diambil di Toko');
        $responseDashboard->assertSee('TRX-SYNC-03A');

        // Akses laporan inventaris Pemilik dengan filter dialokasikan
        $responseInventaris = $this->actingAs($pemilik)->get(route('pemilik.inventaris.index', ['status_stok' => 'dialokasikan']));
        $responseInventaris->assertStatus(200);
        $responseInventaris->assertSee('TRX-SYNC-03A');
        $responseInventaris->assertSee('Lampu Tenda LED');
    }

    /**
     * Total pada kartu sesuai dengan jumlah rincian daftar, dan kuantitas unit sewa multi-item dihitung per unit bukan per transaksi
     */
    public function test_card_totals_match_drilldown_lists_and_counts_units_not_transactions(): void
    {
        $pemilik = User::where('role', 'Pemilik')->first();
        if ($pemilik === null) {
            $pemilik = User::create([
                'username' => 'pemilik_test2',
                'password' => bcrypt('password'),
                'nama_lengkap' => 'Bapak Pemilik 2',
                'role' => 'Pemilik',
                'is_active' => true,
            ]);
        }

        $pelanggan = Pelanggan::first();

        $alatMulti = Alat::create([
            'kode_alat' => 'ALT-MULTI-01',
            'nama_alat' => 'Matras Camping Spon',
            'kategori' => 'Alas Tidur',
            'stok' => 4,
            'kondisi' => 'Baik',
            'tarif_sewa' => 10000,
        ]);

        // Buat 1 transaksi yang menyewa 3 unit matras sekaligus
        $sewaAktifMulti = Penyewaan::create([
            'no_transaksi' => 'TRX-MULTI-01',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'tanggal_sewa' => Carbon::today()->toDateString(),
            'tanggal_kembali' => Carbon::today()->addDays(2)->toDateString(),
            'total_biaya' => 60000,
            'status_penyewaan' => 'Sedang Disewa',
            'sumber_pesanan' => 'online',
        ]);

        DetailPenyewaan::create([
            'no_transaksi' => $sewaAktifMulti->no_transaksi,
            'kode_alat' => $alatMulti->kode_alat,
            'jumlah' => 3,
            'jumlah_diserahkan' => 3,
            'tarif_sewa' => 10000,
            'subtotal' => 60000,
        ]);

        // Dapatkan data ringkasan inventaris
        $ringkasan = Alat::getRingkasanInventaris();
        $daftarSedangDisewa = Alat::getDaftarSedangDisewa();

        // Cari item TRX-MULTI-01 pada daftar rincian
        $detailItem = $daftarSedangDisewa->firstWhere('no_transaksi', 'TRX-MULTI-01');
        $this->assertNotNull($detailItem);
        // Pastikan kuantitas unit yang disewa adalah 3 unit (bukan 1 transaksi)
        $this->assertEquals(3, $detailItem->jumlah_unit);
        $this->assertEquals(3, $detailItem->unit_disewa);

        // Pastikan total sedang disewa pada ringkasan mencakup 3 unit tersebut
        $this->assertGreaterThanOrEqual(3, $ringkasan['stok_sedang_disewa']);
    }

    /**
     * Pemilik memiliki hak akses strictly read-only dan tidak dapat mengubah data
     */
    public function test_pemilik_is_strictly_read_only_and_cannot_modify_data(): void
    {
        $pemilik = User::where('role', 'Pemilik')->first();
        if ($pemilik === null) {
            $pemilik = User::create([
                'username' => 'pemilik_ro',
                'password' => bcrypt('password'),
                'nama_lengkap' => 'Pemilik Read Only',
                'role' => 'Pemilik',
                'is_active' => true,
            ]);
        }

        // Pemilik mencoba mengakses aksi admin POST untuk menyetujui pesanan -> 403 Forbidden
        $response = $this->actingAs($pemilik)->post(route('admin.pesanan.setujui', 'TRX-NON-EXISTENT'));
        $this->assertEquals(403, $response->getStatusCode());

        // Pemilik mencoba menambah alat baru -> 403 Forbidden
        $responseAddAlat = $this->actingAs($pemilik)->post(route('admin.alat.store'), [
            'kode_alat' => 'ALT-ILLEGAL',
            'nama_alat' => 'Illegal Item',
            'kategori' => 'Tenda',
            'stok' => 10,
            'tarif_sewa' => 50000,
            'kondisi' => 'Baik',
        ]);
        $this->assertEquals(403, $responseAddAlat->getStatusCode());
    }

    /**
     * Pengguna tanpa role Pemilik tidak dapat membuka dashboard atau inventaris Pemilik
     */
    public function test_non_pemilik_cannot_access_pemilik_routes(): void
    {
        // 1. Unauthenticated user dialihkan ke login
        $guestResponse = $this->get(route('pemilik.dashboard'));
        $guestResponse->assertRedirect(route('login'));

        // 2. User dengan role Pelanggan/Pengguna ditolak (403 Forbidden)
        $pengguna = User::where('username', 'pelanggan')->first();

        $forbiddenDashboard = $this->actingAs($pengguna)->get(route('pemilik.dashboard'));
        $this->assertEquals(403, $forbiddenDashboard->getStatusCode());

        $forbiddenInventaris = $this->actingAs($pengguna)->get(route('pemilik.inventaris.index'));
        $this->assertEquals(403, $forbiddenInventaris->getStatusCode());
    }
}

<?php

namespace Tests\Feature;

use App\Http\Controllers\KeranjangController;
use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Pengambilan;
use App\Models\Penyewaan;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class RegresiKonsistensiStokTest extends TestCase
{
    protected User $userPengguna;

    protected User $userAdmin;

    protected User $userPemilik;

    protected Pelanggan $pelanggan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userPengguna = User::where('username', 'pelanggan')->firstOrFail();
        $this->userAdmin = User::where('role', 'Admin')->firstOrFail();

        $pemilik = User::where('role', 'Pemilik')->first();
        if ($pemilik === null) {
            $pemilik = User::create([
                'id_user' => 99,
                'nama_user' => 'Pemilik Camp Test',
                'username' => 'pemilik',
                'password' => bcrypt('password'),
                'role' => 'Pemilik',
                'status_aktif' => 1,
            ]);
        }
        $this->userPemilik = $pemilik;
        $this->pelanggan = Pelanggan::firstOrFail();
    }

    /**
     * Skenario 1 s/d 7: Siklus Penuh Transaksi, Kuantitas Riil Multi-Item, Ketiadaan Double-Deduction, dan Kesesuaian Rincian Pemilik
     */
    public function test_skenario_1_sampai_7_siklus_pesanan_multi_unit_dan_sinkronisasi_pemilik(): void
    {
        // ---------------------------------------------------------------------------------
        // SKENARIO 1: Catat stok awal alat dan kondisi alat
        // ---------------------------------------------------------------------------------
        $alat = Alat::where('kode_alat', 'ALT001')->firstOrFail();
        $alat->stok = 10;
        $alat->kondisi = 'Baik';
        $alat->tarif_sewa = 50000;
        $alat->save();

        $stokAwal = (int) $alat->stok;
        $kondisiAwal = $alat->kondisi;
        $ringkasanAwal = Alat::getRingkasanInventaris();

        $this->assertEquals(10, $stokAwal);
        $this->assertEquals('Baik', $kondisiAwal);

        // ---------------------------------------------------------------------------------
        // SKENARIO 2: Buat pesanan beberapa unit melalui role Pengguna (3 unit ALT001)
        // ---------------------------------------------------------------------------------
        KeranjangController::clear($this->userPengguna->id_user);

        $addRes = $this->actingAs($this->userPengguna)->post(route('pengguna.keranjang.tambah'), [
            'kode_alat' => 'ALT001',
            'jumlah' => 3,
        ]);
        $addRes->assertSessionHas('success');

        $tglSewa = Carbon::today()->addDays(5)->format('Y-m-d');
        $tglKembali = Carbon::today()->addDays(8)->format('Y-m-d');

        $checkoutRes = $this->actingAs($this->userPengguna)->post(route('pengguna.checkout.store'), [
            'tanggal_sewa' => $tglSewa,
            'tanggal_kembali' => $tglKembali,
            'metode_pembayaran' => 'Cash',
        ]);
        $checkoutRes->assertRedirect(route('pengguna.dashboard'));

        $trx = Penyewaan::where('id_user', $this->userPengguna->id_user)
            ->latest('no_transaksi')
            ->firstOrFail();

        $this->assertEquals('Menunggu Konfirmasi', $trx->status_penyewaan);
        $detailTrx = $trx->detailPenyewaan->where('kode_alat', 'ALT001')->firstOrFail();
        $this->assertEquals(3, $detailTrx->jumlah);

        // ---------------------------------------------------------------------------------
        // SKENARIO 3: Periksa ketersediaan pada katalog dan aturan status pesanan
        // ---------------------------------------------------------------------------------
        // Ketersediaan dihitung dari basis kapasitas fisik toko
        $katalogRes = $this->actingAs($this->userPengguna)->get(route('pengguna.katalog.index'));
        $katalogRes->assertStatus(200);
        $katalogRes->assertSee('Tenda Dome 4 Orang');
        $katalogRes->assertSee('Stok: 10 unit');

        // ---------------------------------------------------------------------------------
        // SKENARIO 4: Proses konfirmasi Admin (Setujui -> Lunas -> Ambil) dan pastikan tidak berkurang dua kali
        // ---------------------------------------------------------------------------------
        // 4a. Admin Menyetujui Pesanan
        $setujuiRes = $this->actingAs($this->userAdmin)->post(route('admin.pesanan.setujui', $trx->no_transaksi));
        $setujuiRes->assertSessionHas('success');
        $trx->refresh();
        $this->assertEquals('Menunggu Pembayaran', $trx->status_penyewaan);

        // Setelah disetujui, kuantitas teralokasi (10 kapasitas - 3 unit dipesan = 7 unit tersedia untuk periode tsb)
        $stokTersediaPeriode = Penyewaan::hitungStokTersediaPeriode('ALT001', $tglSewa, $tglKembali);
        $this->assertEquals(7, $stokTersediaPeriode);

        // 4b. Admin Konfirmasi Pembayaran
        $konfirmasiBayarRes = $this->actingAs($this->userAdmin)->post(route('admin.pembayaran.konfirmasi', $trx->no_transaksi), [
            'jumlah_diterima' => $trx->total_biaya,
            'catatan' => 'Pembayaran lunas via kasir',
        ]);
        $konfirmasiBayarRes->assertSessionHas('success');
        $trx->refresh();
        $this->assertEquals('Siap Diambil', $trx->status_penyewaan);

        // Pada status Siap Diambil, unit fisik masih di toko (stok = 10, belum diserahkan fisik)
        $this->assertEquals(10, Alat::find('ALT001')->stok);

        // 4c. Operasional Serah Terima Fisik (Pengambilan)
        $ambilRes = $this->actingAs($this->userAdmin)->post(route('admin.operasional.proses-pengambilan', $trx->no_transaksi), [
            'tanggal_pengambilan' => Carbon::today()->format('Y-m-d'),
            'nama_penerima' => 'Pelanggan Uji',
            'catatan' => '3 unit tenda diserahkan lengkap',
            'items' => [
                'ALT001' => ['jumlah_diserahkan' => 3],
            ],
        ]);
        $ambilRes->assertSessionHas('success');
        $trx->refresh();
        $this->assertEquals('Sedang Disewa', $trx->status_penyewaan);

        // Stok di rak toko sekarang berkurang 3 menjadi 7 unit
        $alat->refresh();
        $this->assertEquals(7, $alat->stok);

        // PASTIKAN TIDAK TERJADI PENGURANGAN GANDA:
        // Kapasitas total layak = 7 (di toko) + 3 (di lapangan) = 10 unit
        // Stok tersedia periode beririsan = 10 - 3 = 7 unit (BUKAN 7 - 3 = 4 unit!)
        $stokTersediaSetelahAmbil = Penyewaan::hitungStokTersediaPeriode('ALT001', $tglSewa, $tglKembali);
        $this->assertEquals(7, $stokTersediaSetelahAmbil);

        // Untuk periode masa depan setelah barang kembali (misal 2 minggu ke depan), stok harus penuh 10 unit
        $tglMasaDepanSewa = Carbon::today()->addDays(20)->format('Y-m-d');
        $tglMasaDepanKembali = Carbon::today()->addDays(22)->format('Y-m-d');
        $stokPeriodeMasaDepan = Penyewaan::hitungStokTersediaPeriode('ALT001', $tglMasaDepanSewa, $tglMasaDepanKembali);
        $this->assertEquals(10, $stokPeriodeMasaDepan);

        // ---------------------------------------------------------------------------------
        // SKENARIO 5: Periksa angka total stok fisik, sedang disewa, dan tersedia pada dashboard Pemilik
        // ---------------------------------------------------------------------------------
        $dashboardRes = $this->actingAs($this->userPemilik)->get(route('pemilik.dashboard'));
        $dashboardRes->assertStatus(200);

        $ringkasan = Alat::getRingkasanInventaris();
        $daftarFisik = Alat::getDaftarInventarisLengkap();
        $daftarSewa = Alat::getDaftarSedangDisewa();

        // Konsistensi Total Aset Toko: Total Fisik = Siap Sewa + Sedang Sewa + Perlu Perbaikan + Rusak
        $this->assertEquals(
            $ringkasan['stok_siap_sewa'] + $ringkasan['stok_sedang_disewa'] + $ringkasan['stok_perlu_perbaikan'] + $ringkasan['stok_rusak'],
            $ringkasan['total_stok_fisik']
        );

        // Dashboard menampilkan metrik ringkasan
        $dashboardRes->assertSee($ringkasan['total_stok_fisik'].' Unit');
        $dashboardRes->assertSee($ringkasan['stok_siap_sewa'].' Unit');
        $dashboardRes->assertSee($ringkasan['stok_sedang_disewa'].' Unit');

        // ---------------------------------------------------------------------------------
        // SKENARIO 6: Buka rincian setiap kartu dan cocokkan daftar unit dengan angka ringkasan
        // ---------------------------------------------------------------------------------
        // Jumlah sum seluruh baris daftar rincian HARUS sama persis dengan angka kartu ringkasan
        $this->assertEquals($ringkasan['stok_siap_sewa'], $daftarFisik->sum('stok_siap_sewa'));
        $this->assertEquals($ringkasan['stok_sedang_disewa'], $daftarSewa->sum('unit_disewa'));
        $this->assertEquals($ringkasan['total_stok_fisik'], $daftarFisik->sum('total_stok_fisik'));

        // Rincian fisik khusus untuk ALT001 (10 total: 7 di toko, 3 sedang disewa)
        $itemFisik = $daftarFisik->firstWhere('kode_alat', 'ALT001');
        $this->assertNotNull($itemFisik);
        $this->assertEquals(10, $itemFisik->total_stok_fisik);
        $this->assertEquals(7, $itemFisik->stok_siap_sewa);
        $this->assertEquals(3, $itemFisik->stok_sedang_disewa);

        // ---------------------------------------------------------------------------------
        // SKENARIO 7: Uji beberapa unit pada satu transaksi agar perhitungan berbasis kuantitas, bukan jumlah transaksi
        // ---------------------------------------------------------------------------------
        // 1 transaksi di atas menyewa 3 unit
        $sewaItem = $daftarSewa->firstWhere('no_transaksi', $trx->no_transaksi);
        $this->assertNotNull($sewaItem);
        // Kuantitas unit adalah 3, BUKAN 1 (jumlah transaksi)
        $this->assertEquals(3, $sewaItem->jumlah_unit);
        $this->assertEquals(3, $sewaItem->unit_disewa);
        $this->assertEquals('Sedang Disewa', $sewaItem->status_penyewaan);
    }

    /**
     * Skenario 8 & 9: Persaingan Stok Periode Bertumpang Tindih dan Pembatalan/Penolakan Mengembalikan Ketersediaan
     */
    public function test_skenario_8_dan_9_persaingan_stok_bertumpang_tindih_dan_pemulihan_pembatalan(): void
    {
        $alat = Alat::create([
            'kode_alat' => 'ALT-TEST-COMP',
            'nama_alat' => 'Tenda Kompak 2P',
            'kategori' => 'Tenda',
            'stok' => 2, // Total stok hanya 2 unit
            'kondisi' => 'Baik',
            'tarif_sewa' => 40000,
        ]);

        $tglMulai = Carbon::today()->addDays(10)->format('Y-m-d');
        $tglSelesai = Carbon::today()->addDays(12)->format('Y-m-d');

        // Buat Pesanan A meminta 2 unit
        $trxA = Penyewaan::create([
            'no_transaksi' => 'TRX-COMP-A',
            'id_pelanggan' => $this->pelanggan->id_pelanggan,
            'id_user' => $this->userPengguna->id_user,
            'tanggal_sewa' => $tglMulai,
            'tanggal_kembali' => $tglSelesai,
            'status_penyewaan' => 'Menunggu Konfirmasi',
            'sumber_pesanan' => 'online',
        ]);
        DetailPenyewaan::create([
            'no_transaksi' => $trxA->no_transaksi,
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 2,
            'tarif_sewa' => 40000,
        ]);

        // Buat Pesanan B meminta 1 unit pada periode beririsan
        $trxB = Penyewaan::create([
            'no_transaksi' => 'TRX-COMP-B',
            'id_pelanggan' => $this->pelanggan->id_pelanggan,
            'id_user' => $this->userPengguna->id_user,
            'tanggal_sewa' => Carbon::today()->addDays(11)->format('Y-m-d'),
            'tanggal_kembali' => Carbon::today()->addDays(13)->format('Y-m-d'),
            'status_penyewaan' => 'Menunggu Konfirmasi',
            'sumber_pesanan' => 'online',
        ]);
        DetailPenyewaan::create([
            'no_transaksi' => $trxB->no_transaksi,
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
            'tarif_sewa' => 40000,
        ]);

        // Admin menyetujui Pesanan A -> Berhasil, stok teralokasi
        $resSetujuiA = $this->actingAs($this->userAdmin)->post(route('admin.pesanan.setujui', $trxA->no_transaksi));
        $resSetujuiA->assertSessionHas('success');

        // Sisa stok untuk periode Pesanan B sekarang adalah 2 - 2 = 0 unit
        $stokTersediaB = Penyewaan::hitungStokTersediaPeriode($alat->kode_alat, $trxB->tanggal_sewa, $trxB->tanggal_kembali);
        $this->assertEquals(0, $stokTersediaB);

        // SKENARIO 8: Admin mencoba menyetujui Pesanan B -> Ditolak karena stok habis
        $resSetujuiB = $this->actingAs($this->userAdmin)->post(route('admin.pesanan.setujui', $trxB->no_transaksi));
        $resSetujuiB->assertSessionHas('error');
        $this->assertStringContainsString('tidak mencukupi', session('error'));
        $this->assertEquals('Menunggu Konfirmasi', $trxB->fresh()->status_penyewaan);

        // SKENARIO 9: Pembatalan / Penolakan Pesanan A memulihkan ketersediaan stok
        // Admin menolak Pesanan A (atau pesanan dibatalkan)
        $trxA->update(['status_penyewaan' => 'Ditolak']);

        // Stok tersedia untuk periode Pesanan B kembali pulih menjadi 2 unit
        $stokPulih = Penyewaan::hitungStokTersediaPeriode($alat->kode_alat, $trxB->tanggal_sewa, $trxB->tanggal_kembali);
        $this->assertEquals(2, $stokPulih);

        // Sekarang Admin dapat menyetujui Pesanan B dengan sukses
        $resSetujuiBLagi = $this->actingAs($this->userAdmin)->post(route('admin.pesanan.setujui', $trxB->no_transaksi));
        $resSetujuiBLagi->assertSessionHas('success');
        $this->assertEquals('Menunggu Pembayaran', $trxB->fresh()->status_penyewaan);
    }

    /**
     * Skenario 10, 11, dan 12: Pengembalian Alat (Baik, Rusak, Perbaikan), Ketiadaan Stok Negatif, dan Read-Only Pemilik
     */
    public function test_skenario_10_sampai_12_pengembalian_alat_stok_positif_dan_otorisasi_pemilik(): void
    {
        // ---------------------------------------------------------------------------------
        // SKENARIO 10: Uji pengembalian alat baik, rusak, dan perlu perbaikan
        // ---------------------------------------------------------------------------------
        $alat = Alat::create([
            'kode_alat' => 'ALT-TEST-RET',
            'nama_alat' => 'Kompor Multi Kondisi',
            'kategori' => 'Masak',
            'stok' => 0, // 0 di toko karena semua 3 unit sedang disewa
            'kondisi' => 'Baik',
            'tarif_sewa' => 20000,
        ]);

        $trxRet = Penyewaan::create([
            'no_transaksi' => 'TRX-RET-MULTI',
            'id_pelanggan' => $this->pelanggan->id_pelanggan,
            'id_user' => $this->userPengguna->id_user,
            'tanggal_sewa' => Carbon::today()->subDays(3)->format('Y-m-d'),
            'tanggal_kembali' => Carbon::today()->format('Y-m-d'),
            'status_penyewaan' => 'Sedang Disewa',
            'sumber_pesanan' => 'online',
        ]);

        DetailPenyewaan::create([
            'no_transaksi' => $trxRet->no_transaksi,
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 3,
            'jumlah_diserahkan' => 3,
            'tarif_sewa' => 20000,
        ]);

        Pembayaran::create([
            'no_pembayaran' => 'PAY-RET-MULTI',
            'no_transaksi' => $trxRet->no_transaksi,
            'total_biaya' => 60000,
            'jumlah_pembayaran' => 60000,
            'metode_pembayaran' => 'Cash',
            'status_pembayaran' => 'Lunas',
            'id_admin' => $this->userAdmin->id_user,
            'waktu_konfirmasi' => now(),
        ]);

        Pengambilan::create([
            'no_transaksi' => $trxRet->no_transaksi,
            'tanggal_pengambilan' => Carbon::today()->subDays(3)->format('Y-m-d'),
            'id_admin' => $this->userAdmin->id_user,
            'nama_penerima' => 'Pelanggan Uji',
        ]);

        // Proses pengembalian: 1 Baik, 1 Perlu Perbaikan, 1 Rusak
        $resProsesRet = $this->actingAs($this->userAdmin)->post(route('admin.operasional.proses-pengembalian', $trxRet->no_transaksi), [
            'tanggal_pengembalian_aktual' => Carbon::today()->format('Y-m-d'),
            'items' => [
                $alat->kode_alat => [
                    'baik' => 1,
                    'perlu_perbaikan' => 1,
                    'rusak' => 1,
                ],
            ],
            'nominal_denda' => 25000,
            'alasan_denda' => '1 unit rusak dan 1 unit perlu servis perbaikan frame.',
            'catatan' => 'Unit rusak disendirikan.',
        ]);
        $resProsesRet->assertRedirect(route('admin.operasional.pengembalian'));

        // Verifikasi kondisi alat:
        $alat->refresh();
        // Hanya 1 unit baik yang masuk kembali ke stok toko
        $this->assertEquals(1, $alat->stok);
        // Unit perlu perbaikan tercatat 1
        $this->assertEquals(1, $alat->stok_perlu_perbaikan);
        // Unit rusak tercatat 1
        $this->assertEquals(1, $alat->stok_rusak);
        // Total fisik tetap terjaga 3 unit aset (1 toko + 1 perbaikan + 1 rusak)
        $this->assertEquals(3, $alat->stok + $alat->stok_perlu_perbaikan + $alat->stok_rusak);

        // ---------------------------------------------------------------------------------
        // SKENARIO 11: Pastikan stok tidak negatif dan histori transaksi tercatat sah
        // ---------------------------------------------------------------------------------
        $this->assertGreaterThanOrEqual(0, $alat->stok);
        $this->assertDatabaseHas('riwayat_stok', [
            'kode_alat' => $alat->kode_alat,
            'jenis_perubahan' => 'Pengembalian Sewa',
        ]);

        // ---------------------------------------------------------------------------------
        // SKENARIO 12: Pastikan Pemilik tetap hanya-baca (Strictly Read-Only)
        // ---------------------------------------------------------------------------------
        // A. Pemilik mencoba akses POST pengembalian -> 403 Forbidden
        $resForbidden = $this->actingAs($this->userPemilik)->post(route('admin.operasional.proses-pengembalian', $trxRet->no_transaksi), [
            'tanggal_pengembalian_aktual' => Carbon::today()->format('Y-m-d'),
            'items' => [$alat->kode_alat => ['baik' => 1, 'perlu_perbaikan' => 0, 'rusak' => 0]],
        ]);
        $this->assertEquals(403, $resForbidden->getStatusCode());

        // B. Pemilik mencoba akses POST persetujuan pesanan -> 403 Forbidden
        $resForbiddenSetujui = $this->actingAs($this->userPemilik)->post(route('admin.pesanan.setujui', $trxRet->no_transaksi));
        $this->assertEquals(403, $resForbiddenSetujui->getStatusCode());
    }
}

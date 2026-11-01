<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\PembelianInventaris;
use App\Models\PenangananKerusakan;
use App\Models\Penyewaan;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PemilikKeuanganIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $pemilik;

    protected User $admin;

    protected Pelanggan $pelanggan;

    protected Alat $alat;

    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pemilik = User::firstOrCreate(
            ['username' => 'pemilik'],
            [
                'nama' => 'Bapak Pemilik',
                'password' => 'pemilik123',
                'role' => 'Pemilik',
            ]
        );

        $this->admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'nama' => 'Administrator',
                'password' => 'admin123',
                'role' => 'Admin',
            ]
        );

        $this->pelanggan = Pelanggan::firstOrCreate(
            ['nama_pelanggan' => 'Budi Santoso'],
            [
                'nomor_telepon' => '081234567890',
                'alamat' => 'Jl. Pengujian No. 123',
            ]
        );

        $this->alat = Alat::firstOrCreate(
            ['kode_alat' => 'ALT001'],
            [
                'nama_alat' => 'Tenda Dome Kapasitas 4 Orang',
                'kategori' => 'Tenda',
                'stok' => 10,
                'tarif_sewa' => 50000,
                'kondisi' => 'Baik',
            ]
        );

        $this->supplier = Supplier::firstOrCreate(
            ['nama_supplier' => 'Toko Cahaya Baru'],
            [
                'telepon' => '081234567899',
                'alamat' => 'Yogyakarta',
            ]
        );
    }

    /**
     * Skenario 1: Penerimaan Rp1.200.000 dan Pembelian Supplier Rp100.000
     * menghasilkan Penerimaan Rp1.200.000 dan Arus Kas Bersih Rp1.100.000.
     */
    public function test_penerimaan_pelanggan_dan_pembelian_supplier_menghasilkan_arus_kas_bersih_yang_benar(): void
    {
        $todayJakarta = Carbon::today('Asia/Jakarta')->toDateString();

        // 1. Catat Penerimaan Pelanggan Rp1.200.000 (Lunas hari ini)
        $sewa = Penyewaan::create([
            'no_transaksi' => 'TRX-20261018-001',
            'id_pelanggan' => $this->pelanggan->id_pelanggan,
            'id_user' => $this->pelanggan->id_user ?? 1,
            'tanggal_sewa' => $todayJakarta,
            'tanggal_kembali' => $todayJakarta,
            'total_biaya' => 1200000,
            'status_penyewaan' => 'Disetujui',
            'sumber_pesanan' => 'Online',
        ]);

        Pembayaran::create([
            'no_pembayaran' => 'PAY-20261018-001',
            'no_transaksi' => $sewa->no_transaksi,
            'metode_pembayaran' => 'Transfer',
            'jumlah_pembayaran' => 1200000,
            'total_biaya' => 1200000,
            'status_pembayaran' => 'Lunas',
            'tanggal_pembayaran' => $todayJakarta.' 10:00:00',
            'waktu_konfirmasi' => $todayJakarta.' 10:05:00',
            'id_admin' => $this->admin->id_user,
        ]);

        // 2. Catat Pembelian Supplier Rp100.000 hari ini
        $pembelian = PembelianInventaris::create([
            'no_pembelian' => 'PO-20261018-001',
            'tanggal_pembelian' => $todayJakarta,
            'id_supplier' => $this->supplier->id_supplier,
            'total_pembelian' => 100000,
            'id_admin' => $this->admin->id_user,
            'catatan' => 'Pengadaan unit kompor portable',
        ]);

        $pembelian->detailPembelian()->create([
            'kode_alat' => $this->alat->kode_alat,
            'kuantitas' => 2,
            'harga_beli_satuan' => 50000,
            'subtotal' => 100000,
        ]);

        // 3. Akses dashboard Pemilik
        $response = $this->actingAs($this->pemilik)->get(route('pemilik.dashboard'));

        $response->assertStatus(200);

        // Verifikasi variabel view
        $response->assertViewHas('pendapatanHariIni', 1200000.0);
        $response->assertViewHas('totalPembelianHariIni', 100000.0);
        $response->assertViewHas('totalBiayaPerbaikanHariIni', 0.0);
        $response->assertViewHas('arusKasBersihHariIni', 1100000.0);

        // Verifikasi teks angka terformat di tampilan
        $response->assertSee('1.200.000');
        $response->assertSee('100.000');
        $response->assertSee('1.100.000');
        $response->assertSee('PO-20261018-001');
        $response->assertSee('Toko Cahaya Baru');
    }

    /**
     * Skenario 2: Pembelian supplier tidak mengubah nominal penerimaan pelanggan yang tercatat.
     */
    public function test_pembelian_supplier_tidak_mengubah_nominal_pembayaran_pelanggan(): void
    {
        $todayJakarta = Carbon::today('Asia/Jakarta')->toDateString();

        // Penerimaan Pelanggan Rp500.000
        $sewa = Penyewaan::create([
            'no_transaksi' => 'TRX-TEST-002',
            'id_pelanggan' => $this->pelanggan->id_pelanggan,
            'tanggal_sewa' => $todayJakarta,
            'tanggal_kembali' => $todayJakarta,
            'total_biaya' => 500000,
            'status_penyewaan' => 'Disetujui',
        ]);

        Pembayaran::create([
            'no_pembayaran' => 'PAY-TEST-002',
            'no_transaksi' => $sewa->no_transaksi,
            'metode_pembayaran' => 'QRIS',
            'jumlah_pembayaran' => 500000,
            'total_biaya' => 500000,
            'status_pembayaran' => 'Lunas',
            'waktu_konfirmasi' => $todayJakarta.' 11:00:00',
        ]);

        // Cek dashboard sebelum ada pembelian
        $resBefore = $this->actingAs($this->pemilik)->get(route('pemilik.dashboard'));
        $resBefore->assertViewHas('pendapatanHariIni', 500000.0);
        $resBefore->assertViewHas('arusKasBersihHariIni', 500000.0);

        // Tambah pembelian supplier Rp200.000
        PembelianInventaris::create([
            'no_pembelian' => 'PO-TEST-002',
            'tanggal_pembelian' => $todayJakarta,
            'id_supplier' => $this->supplier->id_supplier,
            'total_pembelian' => 200000,
            'id_admin' => $this->admin->id_user,
        ]);

        // Cek dashboard setelah ada pembelian
        $resAfter = $this->actingAs($this->pemilik)->get(route('pemilik.dashboard'));
        $resAfter->assertViewHas('pendapatanHariIni', 500000.0); // Tetap utuh 500.000
        $resAfter->assertViewHas('totalPembelianHariIni', 200000.0);
        $resAfter->assertViewHas('arusKasBersihHariIni', 300000.0); // 500.000 - 200.000 = 300.000
    }

    /**
     * Skenario 3: Transaksi pembelian dengan multiple item detail tidak dihitung dua kali (no multiplication).
     */
    public function test_transaksi_pembelian_multi_item_tidak_dihitung_dua_kali(): void
    {
        $todayJakarta = Carbon::today('Asia/Jakarta')->toDateString();

        $alat2 = Alat::firstOrCreate(
            ['kode_alat' => 'ALT002'],
            [
                'nama_alat' => 'Sleeping Bag Polar Bulu',
                'kategori' => 'Sleeping Bag',
                'stok' => 5,
                'tarif_sewa' => 25000,
                'kondisi' => 'Baik',
            ]
        );

        $pembelian = PembelianInventaris::create([
            'no_pembelian' => 'PO-MULTI-001',
            'tanggal_pembelian' => $todayJakarta,
            'id_supplier' => $this->supplier->id_supplier,
            'total_pembelian' => 150000,
            'id_admin' => $this->admin->id_user,
        ]);

        // Dua baris item: 100.000 + 50.000 = 150.000
        $pembelian->detailPembelian()->create([
            'kode_alat' => $this->alat->kode_alat,
            'kuantitas' => 2,
            'harga_beli_satuan' => 50000,
            'subtotal' => 100000,
        ]);

        $pembelian->detailPembelian()->create([
            'kode_alat' => $alat2->kode_alat,
            'kuantitas' => 1,
            'harga_beli_satuan' => 50000,
            'subtotal' => 50000,
        ]);

        $res = $this->actingAs($this->pemilik)->get(route('pemilik.dashboard'));
        $res->assertViewHas('totalPembelianHariIni', 150000.0);
        $res->assertViewHas('pembelianHariIniCount', 1); // Tepat 1 PO
    }

    /**
     * Skenario 4: Pembayaran pelanggan belum lunas (Pending/Menunggu Konfirmasi) tidak dihitung sebagai penerimaan kas.
     */
    public function test_pembayaran_belum_lunas_tidak_dihitung_sebagai_penerimaan_kas(): void
    {
        $todayJakarta = Carbon::today('Asia/Jakarta')->toDateString();

        $sewa = Penyewaan::create([
            'no_transaksi' => 'TRX-PENDING-001',
            'id_pelanggan' => $this->pelanggan->id_pelanggan,
            'tanggal_sewa' => $todayJakarta,
            'tanggal_kembali' => $todayJakarta,
            'total_biaya' => 800000,
            'status_penyewaan' => 'Menunggu Pembayaran',
        ]);

        Pembayaran::create([
            'no_pembayaran' => 'PAY-PENDING-001',
            'no_transaksi' => $sewa->no_transaksi,
            'metode_pembayaran' => 'Transfer',
            'jumlah_pembayaran' => 800000,
            'total_biaya' => 800000,
            'status_pembayaran' => 'Menunggu Konfirmasi',
            'tanggal_pembayaran' => $todayJakarta.' 12:00:00',
            'waktu_konfirmasi' => null,
        ]);

        $res = $this->actingAs($this->pemilik)->get(route('pemilik.dashboard'));
        $res->assertViewHas('pendapatanHariIni', 0.0);
        $res->assertViewHas('penjualanHariIniCount', 0);
    }

    /**
     * Skenario 5: Biaya perbaikan alat tercatat dan mengurangi arus kas bersih secara akurat.
     */
    public function test_biaya_perbaikan_alat_mengurangi_arus_kas_bersih(): void
    {
        $todayJakarta = Carbon::today('Asia/Jakarta')->toDateString();

        // 1. Kas masuk: Rp 1.000.000
        $sewa = Penyewaan::create([
            'no_transaksi' => 'TRX-REPAIR-001',
            'id_pelanggan' => $this->pelanggan->id_pelanggan,
            'tanggal_sewa' => $todayJakarta,
            'tanggal_kembali' => $todayJakarta,
            'total_biaya' => 1000000,
            'status_penyewaan' => 'Disetujui',
        ]);

        Pembayaran::create([
            'no_pembayaran' => 'PAY-REPAIR-001',
            'no_transaksi' => $sewa->no_transaksi,
            'metode_pembayaran' => 'Cash',
            'jumlah_pembayaran' => 1000000,
            'total_biaya' => 1000000,
            'status_pembayaran' => 'Lunas',
            'waktu_konfirmasi' => $todayJakarta.' 09:00:00',
        ]);

        // 2. Pembelian supplier: Rp 200.000
        PembelianInventaris::create([
            'no_pembelian' => 'PO-REPAIR-001',
            'tanggal_pembelian' => $todayJakarta,
            'id_supplier' => $this->supplier->id_supplier,
            'total_pembelian' => 200000,
            'id_admin' => $this->admin->id_user,
        ]);

        // 3. Biaya servis/perbaikan: Rp 50.000
        PenangananKerusakan::create([
            'kode_alat' => $this->alat->kode_alat,
            'jenis_tindakan' => 'Catat Biaya Perbaikan',
            'jumlah_unit' => 1,
            'biaya_perbaikan' => 50000,
            'tanggal_tindakan' => $todayJakarta,
            'pelaksana_teknisi' => 'Bengkel Outdoor Jaya',
            'keterangan' => 'Ganti resleting tenda',
            'id_admin' => $this->admin->id_user,
            'created_at' => now(),
        ]);

        $res = $this->actingAs($this->pemilik)->get(route('pemilik.dashboard'));
        $res->assertViewHas('pendapatanHariIni', 1000000.0);
        $res->assertViewHas('totalPembelianHariIni', 200000.0);
        $res->assertViewHas('totalBiayaPerbaikanHariIni', 50000.0);
        // Arus Kas Bersih = 1.000.000 - 200.000 - 50.000 = 750.000
        $res->assertViewHas('arusKasBersihHariIni', 750000.0);
    }

    /**
     * Skenario 6: Pemilik strictly read-only dan tidak dapat mengubah atau menghapus transaksi.
     */
    public function test_pemilik_strictly_read_only(): void
    {
        $response = $this->actingAs($this->pemilik)->post(route('pemilik.test-action'));
        $response->assertStatus(403);

        $responsePurchase = $this->actingAs($this->pemilik)->post(route('admin.inventaris.pembelian.store'), [
            'no_pembelian' => 'PO-HACK-001',
            'tanggal_pembelian' => date('Y-m-d'),
        ]);
        $responsePurchase->assertStatus(403);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Penyewaan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PemilikDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan akun Pemilik
        User::firstOrCreate(
            ['username' => 'pemilik'],
            [
                'nama_user' => 'Bapak Pemilik',
                'password' => 'pemilik123',
                'role' => 'Pemilik',
            ]
        );
    }

    /**
     * Uji ringkasan penjualan harian dihitung secara akurat dari database dan hanya pembayaran Lunas hari ini.
     */
    public function test_pemilik_can_view_daily_sales_summary_with_real_database_data(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();
        $admin = User::where('username', 'admin')->first();
        $pelanggan = Pelanggan::find(1);
        $alat = Alat::find('ALT001');

        $nowJakarta = Carbon::now('Asia/Jakarta');
        $todayStr = $nowJakarta->toDateString();
        $yesterdayJakarta = Carbon::yesterday('Asia/Jakarta');

        // 1. Transaksi Lunas Hari Ini (Uang diterima: Rp 200.000, Total Nilai Pesanan: Rp 200.000)
        $sewaToday = Penyewaan::create([
            'no_transaksi' => 'TRX-TODAY-001',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => 3,
            'tanggal_sewa' => $todayStr,
            'tanggal_kembali' => $todayStr,
            'status_penyewaan' => 'Disetujui',
        ]);
        $sewaToday->detailPenyewaan()->create([
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 2,
            'tarif_sewa' => 100000,
        ]);
        Pembayaran::create([
            'no_pembayaran' => 'PAY-TODAY-001',
            'no_transaksi' => 'TRX-TODAY-001',
            'total_biaya' => 200000,
            'jumlah_pembayaran' => 200000,
            'metode_pembayaran' => 'Cash',
            'tanggal_pembayaran' => $nowJakarta,
            'status_pembayaran' => 'Lunas',
            'id_admin' => $admin->id_user,
            'waktu_konfirmasi' => $nowJakarta,
        ]);

        // 2. Transaksi Lunas Kemarin (TIDAK boleh masuk penjualan hari ini)
        $sewaYesterday = Penyewaan::create([
            'no_transaksi' => 'TRX-YEST-002',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => 3,
            'tanggal_sewa' => $yesterdayJakarta->toDateString(),
            'tanggal_kembali' => $yesterdayJakarta->toDateString(),
            'status_penyewaan' => 'Selesai',
        ]);
        $sewaYesterday->detailPenyewaan()->create([
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
            'tarif_sewa' => 100000,
        ]);
        Pembayaran::create([
            'no_pembayaran' => 'PAY-YEST-002',
            'no_transaksi' => 'TRX-YEST-002',
            'total_biaya' => 100000,
            'jumlah_pembayaran' => 100000,
            'metode_pembayaran' => 'Transfer Bank',
            'tanggal_pembayaran' => $yesterdayJakarta,
            'status_pembayaran' => 'Lunas',
            'id_admin' => $admin->id_user,
            'waktu_konfirmasi' => $yesterdayJakarta,
        ]);

        // 3. Transaksi Hari Ini tapi status Menunggu Verifikasi (TIDAK boleh masuk penjualan lunas)
        $sewaPending = Penyewaan::create([
            'no_transaksi' => 'TRX-PEND-003',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => 3,
            'tanggal_sewa' => $todayStr,
            'tanggal_kembali' => $todayStr,
            'status_penyewaan' => 'Menunggu Verifikasi',
        ]);
        $sewaPending->detailPenyewaan()->create([
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
            'tarif_sewa' => 100000,
        ]);
        Pembayaran::create([
            'no_pembayaran' => 'PAY-PEND-003',
            'no_transaksi' => 'TRX-PEND-003',
            'total_biaya' => 100000,
            'jumlah_pembayaran' => 100000,
            'metode_pembayaran' => 'Transfer Bank',
            'tanggal_pembayaran' => $nowJakarta,
            'status_pembayaran' => 'Menunggu Verifikasi',
        ]);

        $response = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));

        $response->assertStatus(200);
        // Memastikan angka penjualan hari ini berasal dari database (hanya 1 transaksi lunas hari ini bernilai 200.000)
        $response->assertSee('id="valPenjualanHariIni">1<', false);
        $response->assertSee('Rp 200.000');
        // Rincian modal harus menampilkan detail transaksi hari ini
        $response->assertSee('TRX-TODAY-001');
        $response->assertSee('PAY-TODAY-001');
        // Transaksi kemarin tidak boleh muncul di modal rincian hari ini
        $modalHariIniContent = (string) str($response->getContent())->between('id="modalRincianHariIni"', 'id="modalRincianBulanIni"');
        $this->assertStringNotContainsString('PAY-YEST-002', $modalHariIniContent);
    }

    /**
     * Uji kondisi kosong ketika belum ada penjualan terkonfirmasi hari ini.
     */
    public function test_pemilik_dashboard_empty_state_when_no_sales_today(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        $response = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('id="valPenjualanHariIni">0<', false);
        $response->assertSee('id="valPendapatanHariIni" style="font-size:1.45rem;">Rp 0<', false);
        $response->assertSee('Belum ada transaksi pembayaran yang dikonfirmasi pada hari ini');
    }

    /**
     * Uji ringkasan penjualan bulanan (MTD) dihitung secara akurat dari database dan hanya pembayaran Lunas bulan berjalan.
     */
    public function test_pemilik_can_view_monthly_sales_summary_with_real_database_data(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();
        $admin = User::where('username', 'admin')->first();
        $pelanggan = Pelanggan::find(1);
        $alat = Alat::find('ALT001');

        $nowJakarta = Carbon::now('Asia/Jakarta');
        $startOfMonth = Carbon::now('Asia/Jakarta')->startOfMonth()->addDays(2);
        $lastMonthJakarta = Carbon::now('Asia/Jakarta')->subMonth();

        // 1. Transaksi Lunas Bulan Ini #1 (Uang diterima: Rp 150.000)
        $sewaMonth1 = Penyewaan::create([
            'no_transaksi' => 'TRX-MTD-001',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => 3,
            'tanggal_sewa' => $nowJakarta->toDateString(),
            'tanggal_kembali' => $nowJakarta->toDateString(),
            'status_penyewaan' => 'Disetujui',
        ]);
        $sewaMonth1->detailPenyewaan()->create([
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
            'tarif_sewa' => 150000,
        ]);
        Pembayaran::create([
            'no_pembayaran' => 'PAY-MTD-001',
            'no_transaksi' => 'TRX-MTD-001',
            'total_biaya' => 150000,
            'jumlah_pembayaran' => 150000,
            'metode_pembayaran' => 'Cash',
            'tanggal_pembayaran' => $nowJakarta,
            'status_pembayaran' => 'Lunas',
            'id_admin' => $admin->id_user,
            'waktu_konfirmasi' => $nowJakarta,
        ]);

        // 2. Transaksi Lunas Bulan Ini #2 beberapa hari lalu (Uang diterima: Rp 250.000)
        $sewaMonth2 = Penyewaan::create([
            'no_transaksi' => 'TRX-MTD-002',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => 3,
            'tanggal_sewa' => $startOfMonth->toDateString(),
            'tanggal_kembali' => $startOfMonth->toDateString(),
            'status_penyewaan' => 'Selesai',
        ]);
        $sewaMonth2->detailPenyewaan()->create([
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
            'tarif_sewa' => 250000,
        ]);
        Pembayaran::create([
            'no_pembayaran' => 'PAY-MTD-002',
            'no_transaksi' => 'TRX-MTD-002',
            'total_biaya' => 250000,
            'jumlah_pembayaran' => 250000,
            'metode_pembayaran' => 'Transfer Bank',
            'tanggal_pembayaran' => $startOfMonth,
            'status_pembayaran' => 'Lunas',
            'id_admin' => $admin->id_user,
            'waktu_konfirmasi' => $startOfMonth,
        ]);

        // 3. Transaksi Lunas Bulan Lalu (TIDAK boleh masuk penjualan bulan berjalan)
        $sewaLastMonth = Penyewaan::create([
            'no_transaksi' => 'TRX-PAST-003',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => 3,
            'tanggal_sewa' => $lastMonthJakarta->toDateString(),
            'tanggal_kembali' => $lastMonthJakarta->toDateString(),
            'status_penyewaan' => 'Selesai',
        ]);
        $sewaLastMonth->detailPenyewaan()->create([
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
            'tarif_sewa' => 500000,
        ]);
        Pembayaran::create([
            'no_pembayaran' => 'PAY-PAST-003',
            'no_transaksi' => 'TRX-PAST-003',
            'total_biaya' => 500000,
            'jumlah_pembayaran' => 500000,
            'metode_pembayaran' => 'Transfer Bank',
            'tanggal_pembayaran' => $lastMonthJakarta,
            'status_pembayaran' => 'Lunas',
            'id_admin' => $admin->id_user,
            'waktu_konfirmasi' => $lastMonthJakarta,
        ]);

        $response = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));

        $response->assertStatus(200);
        // Total penjualan bulan ini harus 2 transaksi lunas (Rp 150.000 + Rp 250.000 = Rp 400.000)
        $response->assertSee('id="valPenjualanBulanIni">2<', false);
        $response->assertSee('id="valPendapatanBulanIni" style="font-size:1.45rem;">Rp 400.000<', false);
        // Modal rincian bulan ini harus berisi kedua transaksi bulan ini
        $response->assertSee('TRX-MTD-001');
        $response->assertSee('TRX-MTD-002');
        $response->assertSee('PAY-MTD-001');
        $response->assertSee('PAY-MTD-002');
        // Transaksi bulan lalu tidak boleh muncul
        $response->assertDontSee('PAY-PAST-003');
    }

    /**
     * Uji grafik penjualan harian dan bulanan menampilkan data sesuai perhitungan backend.
     */
    public function test_pemilik_can_view_charts_with_backend_calculated_data(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();
        $admin = User::where('username', 'admin')->first();
        $pelanggan = Pelanggan::find(1);
        $alat = Alat::find('ALT001');

        $nowJakarta = Carbon::now('Asia/Jakarta');

        $sewa = Penyewaan::create([
            'no_transaksi' => 'TRX-CHART-001',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => 3,
            'tanggal_sewa' => $nowJakarta->toDateString(),
            'tanggal_kembali' => $nowJakarta->toDateString(),
            'status_penyewaan' => 'Disetujui',
        ]);
        $sewa->detailPenyewaan()->create([
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
            'tarif_sewa' => 350000,
        ]);
        Pembayaran::create([
            'no_pembayaran' => 'PAY-CHART-001',
            'no_transaksi' => 'TRX-CHART-001',
            'total_biaya' => 350000,
            'jumlah_pembayaran' => 350000,
            'metode_pembayaran' => 'QRIS',
            'tanggal_pembayaran' => $nowJakarta,
            'status_pembayaran' => 'Lunas',
            'id_admin' => $admin->id_user,
            'waktu_konfirmasi' => $nowJakarta,
        ]);

        $response = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Grafik Penjualan Harian (7 Hari Terakhir)');
        $response->assertSee('Grafik Tren Penjualan Bulanan (6 Bulan Terakhir)');
        $response->assertSee('id="chartPenjualanHarian"', false);
        $response->assertSee('id="chartPenjualanBulanan"', false);

        // Pastikan variabel dataset grafik tersedia dan sesuai nilai kalkulasi
        $response->assertViewHas('grafikHarian', function ($harian) {
            $todayEntry = end($harian);

            return $todayEntry['total_transaksi'] === 1 && (float) $todayEntry['uang_diterima'] === 350000.0;
        });

        $response->assertViewHas('grafikBulanan', function ($bulanan) {
            $currentMonthEntry = end($bulanan);

            return $currentMonthEntry['total_transaksi'] === 1 && (float) $currentMonthEntry['uang_diterima'] === 350000.0;
        });
    }

    /**
     * Uji kondisi kosong pada grafik jika belum ada data penjualan.
     */
    public function test_pemilik_dashboard_empty_state_when_no_chart_data(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        $response = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('id="emptyDailyChart"', false);
        $response->assertSee('id="emptyMonthlyChart"', false);
        $response->assertSee('Belum Ada Transaksi Penjualan 7 Hari Terakhir');
        $response->assertSee('Belum Ada Riwayat Penjualan 6 Bulan Terakhir');
    }

    /**
     * Uji daftar alat rusak dan alat perlu perbaikan ditampilkan sesuai database secara akurat.
     */
    public function test_pemilik_can_view_damaged_and_repair_equipment_from_database(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        // Bersihkan atau buat alat dengan kondisi berbeda
        Alat::create([
            'kode_alat' => 'ALT-DAMAGED-99',
            'nama_alat' => 'Tenda Robek Total',
            'kategori' => 'Tenda',
            'stok' => 2,
            'kondisi' => 'Rusak',
            'tarif_sewa' => 50000,
        ]);

        Alat::create([
            'kode_alat' => 'ALT-REPAIR-88',
            'nama_alat' => 'Kompor Pematik Lepas',
            'kategori' => 'Peralatan Masak',
            'stok' => 3,
            'kondisi' => 'Perlu Perbaikan',
            'tarif_sewa' => 30000,
        ]);

        $response = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Alat Rusak');
        $response->assertSee('ALT-DAMAGED-99');
        $response->assertSee('Tenda Robek Total');
        $response->assertSee('2 unit');

        $response->assertSee('Alat Perlu Perbaikan');
        $response->assertSee('ALT-REPAIR-88');
        $response->assertSee('Kompor Pematik Lepas');
        $response->assertSee('3 unit');
    }

    /**
     * Uji ringkasan transaksi pelanggan tampil dari database tanpa tombol operasional / persetujuan.
     */
    public function test_pemilik_can_view_customer_transactions_summary(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();
        $pelanggan = Pelanggan::find(1);
        $alat = Alat::find('ALT001');

        $sewa = Penyewaan::create([
            'no_transaksi' => 'TRX-SUMMARY-001',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => 3,
            'tanggal_sewa' => '2026-10-16',
            'tanggal_kembali' => '2026-10-18',
            'status_penyewaan' => 'Sedang Disewa',
        ]);
        $sewa->detailPenyewaan()->create([
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
            'tarif_sewa' => 75000,
        ]);

        $response = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('TRX-SUMMARY-001');
        $response->assertSee('Sedang Disewa');
    }

    /**
     * Uji dashboard Pemilik strictly read-only: tidak ada tombol persetujuan/operasional dan mutasi dilarang.
     */
    public function test_pemilik_dashboard_has_strictly_no_operational_or_approval_buttons(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        $response = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));

        $response->assertStatus(200);
        // Dashboard Pemilik tidak boleh memiliki tombol operasional atau persetujuan
        $response->assertDontSee('Setujui Pesanan');
        $response->assertDontSee('Tolak Pesanan');
        $response->assertDontSee('Konfirmasi Pembayaran');
        $response->assertDontSee('Proses Pengambilan');
        $response->assertDontSee('Proses Pengembalian');
        $response->assertDontSee('Sesuaikan Stok');
        $response->assertDontSee('Tambah Alat');
        $response->assertDontSee('Edit Alat');
        $response->assertDontSee('Hapus Alat');

        // Pemilik tidak dapat melakukan aksi POST / mutasi data
        $this->actingAs($pemilik)
            ->post(route('pemilik.test-action'))
            ->assertStatus(403);
    }

    /**
     * Uji filter periode grafik 30 hari dan rentang tanggal kustom
     */
    public function test_pemilik_can_filter_chart_period_30_days_and_custom_range(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();
        $admin = User::where('username', 'admin')->first();
        $pelanggan = Pelanggan::find(1);
        $alat = Alat::find('ALT001');

        $nowJakarta = Carbon::now('Asia/Jakarta');
        $tenDaysAgo = Carbon::now('Asia/Jakarta')->subDays(10);

        // Buat pembayaran lunas 10 hari yang lalu
        $sewaOld = Penyewaan::create([
            'no_transaksi' => 'TRX-10D-001',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => 3,
            'tanggal_sewa' => $tenDaysAgo->toDateString(),
            'tanggal_kembali' => $tenDaysAgo->toDateString(),
            'status_penyewaan' => 'Selesai',
        ]);
        $sewaOld->detailPenyewaan()->create([
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
            'tarif_sewa' => 180000,
        ]);
        Pembayaran::create([
            'no_pembayaran' => 'PAY-10D-001',
            'no_transaksi' => 'TRX-10D-001',
            'total_biaya' => 180000,
            'jumlah_pembayaran' => 180000,
            'metode_pembayaran' => 'Transfer Bank',
            'tanggal_pembayaran' => $tenDaysAgo,
            'status_pembayaran' => 'Lunas',
            'id_admin' => $admin->id_user,
            'waktu_konfirmasi' => $tenDaysAgo,
        ]);

        // 1. Filter periode 30 hari
        $res30 = $this->actingAs($pemilik)->get(route('pemilik.dashboard', ['periode' => '30_hari']));
        $res30->assertStatus(200);
        $res30->assertSee('Grafik Penjualan Harian (30 Hari Terakhir)');
        $res30->assertViewHas('grafikHarian', function ($harian) {
            return count($harian) === 30;
        });

        // 2. Filter periode kustom
        $startStr = $tenDaysAgo->copy()->subDay()->toDateString();
        $endStr = $tenDaysAgo->copy()->addDay()->toDateString();

        $resCustom = $this->actingAs($pemilik)->get(route('pemilik.dashboard', [
            'periode' => 'kustom',
            'tanggal_mulai' => $startStr,
            'tanggal_selesai' => $endStr,
        ]));
        $resCustom->assertStatus(200);
        $resCustom->assertViewHas('grafikHarian', function ($harian) {
            // Rentang 3 hari (subDay, tenDaysAgo, addDay)
            return count($harian) === 3;
        });
    }

    /**
     * Uji pemisahan nilai pesanan vs uang riil yang diterima dan penggunaan tarif historis
     */
    public function test_order_value_differs_from_actual_cash_received_and_uses_historical_rates(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();
        $admin = User::where('username', 'admin')->first();
        $pelanggan = Pelanggan::find(1);
        $alat = Alat::find('ALT001');

        $nowJakarta = Carbon::now('Asia/Jakarta');
        $todayStr = $nowJakarta->toDateString();

        // Ubah tarif master saat ini untuk menguji bahwa perhitungan menggunakan tarif historis
        $originalPrice = $alat->tarif_sewa_per_hari;
        $alat->update(['tarif_sewa' => 999999]);

        // 1. Pesanan lunas dengan tarif historis Rp 120.000 (disimpan pada transaksi)
        $sewaLunas = Penyewaan::create([
            'no_transaksi' => 'TRX-HIST-001',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => 3,
            'tanggal_sewa' => $todayStr,
            'tanggal_kembali' => $todayStr,
            'status_penyewaan' => 'Disetujui',
        ]);
        $sewaLunas->detailPenyewaan()->create([
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
            'tarif_sewa' => 120000,
        ]);
        Pembayaran::create([
            'no_pembayaran' => 'PAY-HIST-001',
            'no_transaksi' => 'TRX-HIST-001',
            'total_biaya' => 120000,
            'jumlah_pembayaran' => 120000,
            'metode_pembayaran' => 'Cash',
            'tanggal_pembayaran' => $nowJakarta,
            'status_pembayaran' => 'Lunas',
            'id_admin' => $admin->id_user,
            'waktu_konfirmasi' => $nowJakarta,
        ]);

        // 2. Pesanan hari ini yang BELUM bayar (total nilai pesanan Rp 300.000)
        // Nilai ini adalah nilai pesanan, namun TIDAK BOLEH masuk ke kas diterima (Rp 120.000)
        $sewaUnpaid = Penyewaan::create([
            'no_transaksi' => 'TRX-UNPAID-002',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => 3,
            'tanggal_sewa' => $todayStr,
            'tanggal_kembali' => $todayStr,
            'status_penyewaan' => 'Menunggu Pembayaran',
        ]);
        $sewaUnpaid->detailPenyewaan()->create([
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
            'tarif_sewa' => 300000,
        ]);
        Pembayaran::create([
            'no_pembayaran' => 'PAY-UNPAID-002',
            'no_transaksi' => 'TRX-UNPAID-002',
            'total_biaya' => 300000,
            'jumlah_pembayaran' => 0,
            'metode_pembayaran' => 'Transfer Bank',
            'tanggal_pembayaran' => $nowJakarta,
            'status_pembayaran' => 'Belum Lunas',
        ]);

        $response = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));

        $response->assertStatus(200);

        // Uang diterima hanya Rp 120.000 (bukan 999.999 dan bukan ditambah 300.000)
        $response->assertSee('id="valPendapatanHariIni" style="font-size:1.45rem;">Rp 120.000<', false);
        // Pesanan belum bayar TIDAK masuk ke pendapatan lunas
        $response->assertDontSee('Rp 420.000');
        $response->assertDontSee('Rp 999.999');
    }

    /**
     * Uji tabel riwayat pesanan menampilkan label sumber Online dan On-site beserta tombol rincian
     */
    public function test_customer_orders_history_table_displays_online_and_onsite_sources_with_detail_link(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();
        $pelanggan = Pelanggan::find(1);
        $alat = Alat::find('ALT001');

        // 1. Pesanan Online
        $sewaOnline = Penyewaan::create([
            'no_transaksi' => 'TRX-SRC-ONL-01',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => 3,
            'tanggal_sewa' => '2026-10-17',
            'tanggal_kembali' => '2026-10-18',
            'status_penyewaan' => 'Disetujui',
            'sumber_pesanan' => 'online',
        ]);
        $sewaOnline->detailPenyewaan()->create([
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
            'tarif_sewa' => 50000,
        ]);

        // 2. Pesanan On-site (Tablet Kios Kasir)
        $sewaOnsite = Penyewaan::create([
            'no_transaksi' => 'TRX-SRC-ONS-02',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => null,
            'tanggal_sewa' => '2026-10-17',
            'tanggal_kembali' => '2026-10-18',
            'status_penyewaan' => 'Disetujui',
            'sumber_pesanan' => 'on-site',
        ]);
        $sewaOnsite->detailPenyewaan()->create([
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 2,
            'tarif_sewa' => 50000,
        ]);

        $response = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('TRX-SRC-ONL-01');
        $response->assertSee('TRX-SRC-ONS-02');
        $response->assertSee('Online');
        $response->assertSee('On-site');
        // Tautan rincian transaksi menuju ke show Pemilik
        $response->assertSee(route('pemilik.transaksi.show', 'TRX-SRC-ONL-01'));
        $response->assertSee(route('pemilik.transaksi.show', 'TRX-SRC-ONS-02'));
    }

    /**
     * Uji pembatasan akses: Pemilik dilarang mengakses rute pengelolaan operasional Admin (403)
     */
    public function test_pemilik_cannot_access_admin_operational_routes(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        // 1. Rute kelola alat
        $this->actingAs($pemilik)->get('/admin/alat')->assertStatus(403);
        $this->actingAs($pemilik)->get('/admin/alat/tambah')->assertStatus(403);

        // 2. Rute kelola pesanan dan pembayaran
        $this->actingAs($pemilik)->get('/admin/pembayaran')->assertStatus(403);
        $this->actingAs($pemilik)->post('/admin/pembayaran/PAY-001/konfirmasi', [])->assertStatus(403);

        // 3. Rute manajemen pengguna
        $this->actingAs($pemilik)->get('/admin/users')->assertStatus(403);
        $this->actingAs($pemilik)->post('/admin/users', [])->assertStatus(403);

        // 4. Rute penyesuaian stok inventaris
        $this->actingAs($pemilik)->post('/admin/inventaris/ALT001/adjust', ['stok_baru' => 10])->assertStatus(403);
    }

    /**
     * Uji penanganan ramah saat terjadi kegagalan koneksi database pada dashboard Pemilik
     */
    public function test_pemilik_dashboard_database_connection_failure_renders_friendly_error_page(): void
    {
        Route::get('/pemilik/test-db-connection-failure', function () {
            throw new QueryException(
                'mysql',
                'SELECT * FROM pembayaran',
                [],
                new \Exception('SQLSTATE[HY000] [2002] Connection refused to 127.0.0.1:3306', 2002)
            );
        });

        $pemilik = User::where('username', 'pemilik')->first();

        $response = $this->actingAs($pemilik)->get('/pemilik/test-db-connection-failure');

        $response->assertStatus(503);
        $response->assertSee('ERR_DB_CONN');
        $response->assertSee('Layanan Database Tidak Tersedia');
    }
}

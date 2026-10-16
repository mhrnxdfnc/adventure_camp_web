<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Penyewaan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

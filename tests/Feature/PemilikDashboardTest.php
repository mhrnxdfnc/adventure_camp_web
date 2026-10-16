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
        $response->assertSee('Andi Pratama');
        // Transaksi kemarin tidak boleh muncul di modal rincian hari ini
        $response->assertDontSee('PAY-YEST-002');
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
}

<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Penyewaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PemilikTransaksiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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
     * Uji Pemilik dapat melihat daftar riwayat transaksi pelanggan dari database.
     */
    public function test_pemilik_can_view_customer_transactions_list(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();
        $admin = User::where('username', 'admin')->first();
        $pelanggan = Pelanggan::find(1);
        $alat = Alat::find('ALT001');

        $sewa = Penyewaan::create([
            'no_transaksi' => 'TRX-TEST-LIST-01',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => 3,
            'tanggal_sewa' => '2026-10-15',
            'tanggal_kembali' => '2026-10-17',
            'status_penyewaan' => 'Disetujui',
        ]);
        $sewa->detailPenyewaan()->create([
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 2,
            'tarif_sewa' => 50000,
        ]);
        Pembayaran::create([
            'no_pembayaran' => 'PAY-TEST-LIST-01',
            'no_transaksi' => 'TRX-TEST-LIST-01',
            'total_biaya' => 200000,
            'jumlah_pembayaran' => 200000,
            'metode_pembayaran' => 'Transfer Bank',
            'tanggal_pembayaran' => now(),
            'status_pembayaran' => 'Lunas',
            'id_admin' => $admin->id_user,
            'waktu_konfirmasi' => now(),
        ]);

        $response = $this->actingAs($pemilik)->get(route('pemilik.transaksi.index'));

        $response->assertStatus(200);
        $response->assertSee('Riwayat Transaksi Pelanggan');
        $response->assertSee('TRX-TEST-LIST-01');
        $response->assertSee($pelanggan->nama_pelanggan);
        $response->assertSee('Rp 200.000');
        $response->assertSee('Transfer Bank');
        $response->assertSee('Lunas');
        $response->assertSee('Disetujui');
    }

    /**
     * Uji Pengunjung dan Pengguna tidak dapat mengakses riwayat transaksi Pemilik.
     */
    public function test_guest_and_pengguna_cannot_access_owner_transactions_list(): void
    {
        $this->get(route('pemilik.transaksi.index'))->assertRedirect('/login');

        $pengguna = User::where('username', 'pelanggan')->first();
        $this->actingAs($pengguna)
            ->get(route('pemilik.transaksi.index'))
            ->assertStatus(403);
    }

    /**
     * Uji kondisi kosong ketika belum ada transaksi pelanggan.
     */
    public function test_owner_transactions_list_empty_state(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        // Hapus data transaksi dummy yang di-seed test case
        DetailPenyewaan::truncate();
        Pembayaran::truncate();
        Penyewaan::truncate();

        $response = $this->actingAs($pemilik)->get(route('pemilik.transaksi.index'));

        $response->assertStatus(200);
        $response->assertSee('Belum Ada Riwayat Transaksi Pelanggan');
    }
}

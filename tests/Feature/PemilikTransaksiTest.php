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

    /**
     * Uji Pemilik dapat melihat halaman rincian transaksi lengkap: item alat, tarif, durasi, tagihan, pembayaran, denda.
     */
    public function test_pemilik_can_view_customer_transaction_detail_with_items_and_payment_breakdown(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();
        $admin = User::where('username', 'admin')->first();
        $pelanggan = Pelanggan::find(1);
        $alat1 = Alat::find('ALT001');
        $alat2 = Alat::find('ALT002');

        $sewa = Penyewaan::create([
            'no_transaksi' => 'TRX-TEST-DETAIL-99',
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'id_user' => 3,
            'tanggal_sewa' => '2026-10-10',
            'tanggal_kembali' => '2026-10-12',
            'status_penyewaan' => 'Selesai',
        ]);

        // 2 hari sewa
        // Alat 1: 1 x 50.000 x 2 hari = 100.000
        $sewa->detailPenyewaan()->create([
            'kode_alat' => $alat1->kode_alat,
            'jumlah' => 1,
            'tarif_sewa' => 50000,
        ]);
        // Alat 2: 2 x 25.000 x 2 hari = 100.000
        $sewa->detailPenyewaan()->create([
            'kode_alat' => $alat2->kode_alat,
            'jumlah' => 2,
            'tarif_sewa' => 25000,
        ]);

        // Pembayaran terverifikasi lunas
        Pembayaran::create([
            'no_pembayaran' => 'PAY-TEST-DETAIL-99',
            'no_transaksi' => 'TRX-TEST-DETAIL-99',
            'total_biaya' => 200000,
            'jumlah_pembayaran' => 200000,
            'metode_pembayaran' => 'Cash',
            'tanggal_pembayaran' => now(),
            'status_pembayaran' => 'Lunas',
            'id_admin' => $admin->id_user,
            'waktu_konfirmasi' => now(),
        ]);

        // Data pengembalian & denda
        $pengembalian = $sewa->pengembalian()->create([
            'id_admin' => $admin->id_user,
            'tanggal_pengembalian_aktual' => '2026-10-13',
            'kondisi_alat' => 'Terlambat 1 hari',
        ]);
        $pengembalian->denda()->create([
            'tarif_denda' => 50000,
            'nominal_denda' => 50000,
            'alasan_denda' => 'Terlambat 1 hari pengembalian tenda',
        ]);

        $response = $this->actingAs($pemilik)->get(route('pemilik.transaksi.show', 'TRX-TEST-DETAIL-99'));

        $response->assertStatus(200);
        $response->assertSee('TRX-TEST-DETAIL-99');
        $response->assertSee($pelanggan->nama_pelanggan);
        $response->assertSee('2 Hari');
        // Item alat outdoor
        $response->assertSee('Tenda Dome 4 Orang');
        $response->assertSee('Sleeping Bag');
        $response->assertSee('Rp 200.000');
        // Pembayaran
        $response->assertSee('PAY-TEST-DETAIL-99');
        $response->assertSee('Cash');
        $response->assertSee('Lunas');
        // Denda
        $response->assertSee('Terlambat 1 hari pengembalian tenda');
        $response->assertSee('Rp 50.000');
    }

    /**
     * Uji halaman detail Pemilik bersifat strictly read-only tanpa tombol aksi operasional.
     */
    public function test_pemilik_cannot_mutate_or_see_operational_buttons_in_detail(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        $response = $this->actingAs($pemilik)->get(route('pemilik.transaksi.show', 'TRX-20261015-001'));

        $response->assertStatus(200);
        // Pastikan tidak ada tombol aksi operasional di halaman detail Pemilik
        $response->assertDontSee('Setujui Pesanan');
        $response->assertDontSee('Tolak Pesanan');
        $response->assertDontSee('Konfirmasi Pembayaran');
        $response->assertDontSee('Proses Pengambilan');
        $response->assertDontSee('Proses Pengembalian');
        $response->assertDontSee('Bayar Sekarang');
    }

    /**
     * Uji halaman detail menangani data historis legacy tanpa error.
     */
    public function test_pemilik_transaction_detail_handles_legacy_data_gracefully(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        // TRX-20261015-002 tidak memiliki detail penyewaan di database seeder
        $response = $this->actingAs($pemilik)->get(route('pemilik.transaksi.show', 'TRX-20261015-002'));

        $response->assertStatus(200);
        $response->assertSee('TRX-20261015-002');
        $response->assertSee('Catatan Data Historis Legacy');
    }
}

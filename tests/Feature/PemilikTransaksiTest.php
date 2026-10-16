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

    /**
     * Uji pencarian transaksi berdasarkan nomor transaksi dan nama pelanggan.
     */
    public function test_pemilik_can_search_transactions_by_number_and_customer_name(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        // Pelanggan 1: Andi Pratama
        // Pelanggan 2: Budi Santoso
        $sewa1 = Penyewaan::create([
            'no_transaksi' => 'TRX-SEARCH-ANDI',
            'id_pelanggan' => 1,
            'id_user' => 3,
            'tanggal_sewa' => '2026-10-15',
            'tanggal_kembali' => '2026-10-17',
            'status_penyewaan' => 'Disetujui',
        ]);

        $sewa2 = Penyewaan::create([
            'no_transaksi' => 'TRX-SEARCH-BUDI',
            'id_pelanggan' => 2,
            'id_user' => 4,
            'tanggal_sewa' => '2026-10-15',
            'tanggal_kembali' => '2026-10-17',
            'status_penyewaan' => 'Disetujui',
        ]);

        // Cari berdasarkan nama "Andi"
        $responseAndi = $this->actingAs($pemilik)->get(route('pemilik.transaksi.index', ['search' => 'Andi']));
        $responseAndi->assertStatus(200);
        $responseAndi->assertSee('TRX-SEARCH-ANDI');
        $responseAndi->assertDontSee('TRX-SEARCH-BUDI');

        // Cari berdasarkan nomor transaksi "SEARCH-BUDI"
        $responseBudi = $this->actingAs($pemilik)->get(route('pemilik.transaksi.index', ['search' => 'SEARCH-BUDI']));
        $responseBudi->assertStatus(200);
        $responseBudi->assertSee('TRX-SEARCH-BUDI');
        $responseBudi->assertDontSee('TRX-SEARCH-ANDI');
    }

    /**
     * Uji filter transaksi berdasarkan rentang tanggal pemesanan.
     */
    public function test_pemilik_can_filter_transactions_by_date_range(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        $sewaAwalBulan = Penyewaan::create([
            'no_transaksi' => 'TRX-DATE-001',
            'id_pelanggan' => 1,
            'id_user' => 3,
            'tanggal_sewa' => '2026-10-01',
            'tanggal_kembali' => '2026-10-03',
            'status_penyewaan' => 'Selesai',
        ]);

        $sewaAkhirBulan = Penyewaan::create([
            'no_transaksi' => 'TRX-DATE-002',
            'id_pelanggan' => 1,
            'id_user' => 3,
            'tanggal_sewa' => '2026-10-25',
            'tanggal_kembali' => '2026-10-27',
            'status_penyewaan' => 'Menunggu Pembayaran',
        ]);

        $response = $this->actingAs($pemilik)->get(route('pemilik.transaksi.index', [
            'tanggal_mulai' => '2026-10-20',
            'tanggal_selesai' => '2026-10-31',
        ]));

        $response->assertStatus(200);
        $response->assertSee('TRX-DATE-002');
        $response->assertDontSee('TRX-DATE-001');
    }

    /**
     * Uji filter transaksi berdasarkan status pesanan dan status pembayaran.
     */
    public function test_pemilik_can_filter_transactions_by_status(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        $sewaLunas = Penyewaan::create([
            'no_transaksi' => 'TRX-STAT-LUNAS',
            'id_pelanggan' => 1,
            'id_user' => 3,
            'tanggal_sewa' => '2026-10-15',
            'tanggal_kembali' => '2026-10-17',
            'status_penyewaan' => 'Disetujui',
        ]);
        Pembayaran::create([
            'no_pembayaran' => 'PAY-STAT-LUNAS',
            'no_transaksi' => 'TRX-STAT-LUNAS',
            'total_biaya' => 100000,
            'jumlah_pembayaran' => 100000,
            'metode_pembayaran' => 'Cash',
            'tanggal_pembayaran' => now(),
            'status_pembayaran' => 'Lunas',
        ]);

        $sewaPending = Penyewaan::create([
            'no_transaksi' => 'TRX-STAT-PENDING',
            'id_pelanggan' => 1,
            'id_user' => 3,
            'tanggal_sewa' => '2026-10-15',
            'tanggal_kembali' => '2026-10-17',
            'status_penyewaan' => 'Menunggu Pembayaran',
        ]);

        // Filter status pembayaran Lunas
        $response = $this->actingAs($pemilik)->get(route('pemilik.transaksi.index', [
            'status_pembayaran' => 'Lunas',
        ]));

        $response->assertStatus(200);
        $response->assertSee('TRX-STAT-LUNAS');
        $response->assertDontSee('TRX-STAT-PENDING');
    }

    /**
     * Uji kondisi kosong ketika hasil filter tidak menemukan transaksi yang cocok.
     */
    public function test_pemilik_filter_renders_informative_empty_state_when_no_match(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        $response = $this->actingAs($pemilik)->get(route('pemilik.transaksi.index', [
            'search' => 'NOMOR_TIDAK_MUNGKIN_ADA_9999999',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Tidak Ada Transaksi Sesuai Filter');
        $response->assertSee('Reset Filter');
    }
}

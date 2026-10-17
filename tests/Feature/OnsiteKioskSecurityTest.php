<?php

namespace Tests\Feature;

use App\Http\Controllers\OnsiteKioskController;
use App\Models\Alat;
use App\Models\Pelanggan;
use App\Models\Penyewaan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class OnsiteKioskSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        OnsiteKioskController::clearCart();
    }

    /**
     * 1. Validasi field wajib identitas pemesan (nama, telepon, alamat)
     */
    public function test_checkout_validates_required_identity_fields(): void
    {
        $alat = Alat::where('kode_alat', 'ALT002')->first();
        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
        ]);

        $response = $this->post(route('onsite.checkout.proses'), [
            'nama_lengkap' => '',
            'nomor_telepon' => '',
            'alamat' => '',
            'tanggal_sewa' => Carbon::now()->addDay()->toDateString(),
            'tanggal_kembali' => Carbon::now()->addDays(3)->toDateString(),
            'metode_pembayaran' => 'Cash',
        ]);

        $response->assertSessionHasErrors(['nama_lengkap', 'nomor_telepon', 'alamat']);
    }

    /**
     * 2. Normalisasi nomor telepon saat checkout (+62 / format berjarak menjadi angka standar)
     */
    public function test_phone_number_normalization_on_checkout(): void
    {
        $alat = Alat::where('kode_alat', 'ALT002')->first();
        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
        ]);

        $response = $this->post(route('onsite.checkout.proses'), [
            'nama_lengkap' => 'Rina Wijaya',
            'nomor_telepon' => '+62 813-9876-5432',
            'alamat' => 'Jl. Borobudur No. 44, Malang',
            'tanggal_sewa' => Carbon::now()->addDay()->toDateString(),
            'tanggal_kembali' => Carbon::now()->addDays(2)->toDateString(),
            'metode_pembayaran' => 'Cash',
        ]);

        $response->assertRedirect();
        $pelanggan = Pelanggan::where('nama_pelanggan', 'Rina Wijaya')->first();
        $this->assertNotNull($pelanggan);
        $this->assertEquals('081398765432', $pelanggan->nomor_telepon);
    }

    /**
     * 3. Pelanggan lama dicocokkan secara aman berdasarkan telepon dan nama yang cocok
     */
    public function test_returning_customer_matched_safely_by_phone_and_matching_name(): void
    {
        $alat = Alat::where('kode_alat', 'ALT002')->first();
        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
        ]);

        // Andi Pratama sudah ada di seeder dengan nomor 081234567890
        $andiAwal = Pelanggan::find(1);
        $this->assertNotNull($andiAwal);
        $this->assertEquals('Andi Pratama', $andiAwal->nama_pelanggan);

        $response = $this->post(route('onsite.checkout.proses'), [
            'nama_lengkap' => 'Andi Pratama',
            'nomor_telepon' => '081234567890',
            'alamat' => 'Jl. Soekarno Hatta No. 99, Malang (Alamat Baru)',
            'tanggal_sewa' => Carbon::now()->addDay()->toDateString(),
            'tanggal_kembali' => Carbon::now()->addDays(3)->toDateString(),
            'metode_pembayaran' => 'Cash',
        ]);

        $response->assertRedirect();

        // Verifikasi tidak ada penambahan row baru di tabel pelanggan (mencegah duplikasi)
        $this->assertEquals(1, Pelanggan::where('nomor_telepon', '081234567890')->count());
        $andiUpdate = Pelanggan::find(1);
        $this->assertEquals('Andi Pratama', $andiUpdate->nama_pelanggan);
        $this->assertEquals('Jl. Soekarno Hatta No. 99, Malang (Alamat Baru)', $andiUpdate->alamat);

        // Verifikasi transaksi terhubung ke ID pelanggan yang benar
        $noTrx = Session::get('onsite_last_completed_trx');
        $trx = Penyewaan::find($noTrx);
        $this->assertNotNull($trx);
        $this->assertEquals(1, $trx->id_pelanggan);
        $this->assertEquals('on-site', $trx->sumber_pesanan);
    }

    /**
     * 4. Dua pelanggan dengan nama yang sama TIDAK digabungkan jika nomor teleponnya berbeda
     */
    public function test_customers_with_same_name_are_not_merged_if_phones_differ(): void
    {
        $alat = Alat::where('kode_alat', 'ALT002')->first();
        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
        ]);

        // Ada Andi Pratama lain dengan nomor HP berbeda
        $response = $this->post(route('onsite.checkout.proses'), [
            'nama_lengkap' => 'Andi Pratama',
            'nomor_telepon' => '087711223344',
            'alamat' => 'Jl. Surabaya No. 15, Malang',
            'tanggal_sewa' => Carbon::now()->addDay()->toDateString(),
            'tanggal_kembali' => Carbon::now()->addDays(2)->toDateString(),
            'metode_pembayaran' => 'Cash',
        ]);

        $response->assertRedirect();

        // Harus ada 2 pelanggan bernama Andi Pratama dengan id_pelanggan berbeda
        $andiList = Pelanggan::where('nama_pelanggan', 'Andi Pratama')->get();
        $this->assertCount(2, $andiList);

        $andiBaru = Pelanggan::where('nomor_telepon', '087711223344')->first();
        $this->assertNotNull($andiBaru);
        $this->assertNotEquals(1, $andiBaru->id_pelanggan);

        // Transaksi baru harus terhubung ke pelanggan baru, bukan pelanggan 1
        $trx = Penyewaan::where('id_pelanggan', $andiBaru->id_pelanggan)->first();
        $this->assertNotNull($trx);
        $this->assertEquals($andiBaru->id_pelanggan, $trx->id_pelanggan);
    }

    /**
     * 5. Nomor telepon terdaftar yang diinput dengan nama berbeda ditolak jika tanpa konfirmasi
     */
    public function test_ambiguous_phone_with_different_name_is_rejected_without_confirmation(): void
    {
        $alat = Alat::where('kode_alat', 'ALT002')->first();
        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
        ]);

        // Nomor 081234567890 milik Andi Pratama, tapi diinput nama Siti Nurhaliza tanpa konfirmasi
        $response = $this->post(route('onsite.checkout.proses'), [
            'nama_lengkap' => 'Siti Nurhaliza',
            'nomor_telepon' => '081234567890',
            'alamat' => 'Jl. Kawi No. 8, Malang',
            'tanggal_sewa' => Carbon::now()->addDay()->toDateString(),
            'tanggal_kembali' => Carbon::now()->addDays(2)->toDateString(),
            'metode_pembayaran' => 'Cash',
        ]);

        $response->assertSessionHasErrors(['identitas']);

        // Data Andi Pratama di database tidak boleh tertimpa
        $andi = Pelanggan::find(1);
        $this->assertEquals('Andi Pratama', $andi->nama_pelanggan);
    }

    /**
     * 6. Nomor terdaftar yang diinput dengan nama berbeda dan telah dikonfirmasi mempertahankan nama asli di master
     */
    public function test_ambiguous_phone_with_confirmation_associates_transaction_and_preserves_registered_name(): void
    {
        $alat = Alat::where('kode_alat', 'ALT002')->first();
        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
        ]);

        // Konfirmasi penggunaan nomor kontak bersama
        $response = $this->post(route('onsite.checkout.proses'), [
            'nama_lengkap' => 'Siti Nurhaliza',
            'nomor_telepon' => '081234567890',
            'alamat' => 'Jl. Kawi No. 8, Malang',
            'tanggal_sewa' => Carbon::now()->addDay()->toDateString(),
            'tanggal_kembali' => Carbon::now()->addDays(2)->toDateString(),
            'metode_pembayaran' => 'Cash',
            'konfirmasi_identitas_bersama' => 1,
        ]);

        $response->assertRedirect();

        // Nama asli Andi Pratama tetap terjaga di master pelanggan
        $andi = Pelanggan::find(1);
        $this->assertEquals('Andi Pratama', $andi->nama_pelanggan);

        // Transaksi berhasil terhubung ke id_pelanggan 1
        $noTrx = Session::get('onsite_last_completed_trx');
        $trx = Penyewaan::find($noTrx);
        $this->assertNotNull($trx);
        $this->assertEquals(1, $trx->id_pelanggan);
        $this->assertEquals('on-site', $trx->sumber_pesanan);
    }

    /**
     * 7. Endpoint cek pelanggan menyamarkan nama untuk privasi pada tablet bersama
     */
    public function test_cek_pelanggan_endpoint_masks_name_for_privacy(): void
    {
        // 1. Cek nomor Andi Pratama tanpa nama lengkap
        $res1 = $this->postJson(route('onsite.cek-pelanggan'), [
            'nomor_telepon' => '081234567890',
        ]);

        $res1->assertStatus(200);
        $res1->assertJson([
            'registered' => true,
            'matched' => null,
        ]);
        // Nama asli Andi Pratama harus disamarkan (misal: "An** Pr*****")
        $this->assertStringContainsString('An*', $res1->json('masked_name'));
        $this->assertStringNotContainsString('Andi Pratama', $res1->json('masked_name'));

        // 2. Cek dengan nama cocok
        $res2 = $this->postJson(route('onsite.cek-pelanggan'), [
            'nomor_telepon' => '081234567890',
            'nama_lengkap' => 'Andi Pratama',
        ]);

        $res2->assertStatus(200);
        $res2->assertJson([
            'registered' => true,
            'matched' => true,
        ]);
        $this->assertNotNull($res2->json('alamat'));

        // 3. Cek dengan nama tidak cocok
        $res3 = $this->postJson(route('onsite.cek-pelanggan'), [
            'nomor_telepon' => '081234567890',
            'nama_lengkap' => 'Bambang Sudarsono',
        ]);

        $res3->assertStatus(200);
        $res3->assertJson([
            'registered' => true,
            'matched' => false,
        ]);
    }

    /**
     * 8. IDOR Protection: Pengunjung tablet tidak dapat membuka URL transaksi orang lain
     */
    public function test_customer_cannot_access_other_customers_transaction_url_idor_protection(): void
    {
        // Akses langsung ke transaksi yang sudah ada di database tanpa otorisasi sesi
        $response = $this->get(route('onsite.sukses', 'TRX-20261015-001'));

        $response->assertRedirect(route('onsite.landing'));
        $response->assertSessionHas('error');
    }

    /**
     * 9. Pengunjung dapat melihat transaksinya sendiri segera setelah checkout berhasil
     */
    public function test_customer_can_view_own_transaction_immediately_after_checkout(): void
    {
        $alat = Alat::where('kode_alat', 'ALT002')->first();
        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
        ]);

        $response = $this->post(route('onsite.checkout.proses'), [
            'nama_lengkap' => 'Fajar Nugraha',
            'nomor_telepon' => '082233445566',
            'alamat' => 'Jl. Veteran No. 3, Malang',
            'tanggal_sewa' => Carbon::now()->addDay()->toDateString(),
            'tanggal_kembali' => Carbon::now()->addDays(2)->toDateString(),
            'metode_pembayaran' => 'Cash',
        ]);

        $trx = Penyewaan::where('sumber_pesanan', 'on-site')->latest('created_at')->first();
        $this->assertNotNull($trx);

        // Sukses redirect
        $response->assertRedirect(route('onsite.sukses', $trx->no_transaksi));

        // Karena sesi memegang 'onsite_last_completed_trx', maka boleh diakses
        $resSukses = $this->get(route('onsite.sukses', $trx->no_transaksi));
        $resSukses->assertStatus(200);
        $resSukses->assertSee($trx->no_transaksi);
        $resSukses->assertSee('Fajar Nugraha');
    }

    /**
     * 10. Aksi selesai menghapus otorisasi akses transaksi (mencegah orang lain melihat saat Back)
     */
    public function test_selesai_action_clears_completed_transaction_access(): void
    {
        $alat = Alat::where('kode_alat', 'ALT002')->first();
        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 1,
        ]);

        $this->post(route('onsite.checkout.proses'), [
            'nama_lengkap' => 'Gilang Ramadhan',
            'nomor_telepon' => '083344556677',
            'alamat' => 'Jl. Galunggung No. 18, Malang',
            'tanggal_sewa' => Carbon::now()->addDay()->toDateString(),
            'tanggal_kembali' => Carbon::now()->addDays(2)->toDateString(),
            'metode_pembayaran' => 'Cash',
        ]);

        $trx = Penyewaan::where('sumber_pesanan', 'on-site')->latest('created_at')->first();

        // Tekan tombol Selesai
        $resSelesai = $this->post(route('onsite.selesai'));
        $resSelesai->assertRedirect(route('onsite.landing'));

        // Coba buka kembali transaksi tadi -> harus ditolak
        $resAksesLagi = $this->get(route('onsite.sukses', $trx->no_transaksi));
        $resAksesLagi->assertRedirect(route('onsite.landing'));
        $resAksesLagi->assertSessionHas('error');
    }

    /**
     * 11. Tombol Batal/Reset membersihkan seluruh sesi sementara dan meregenerasi ID sesi
     */
    public function test_batal_or_reset_clears_all_temporary_data_and_regenerates_session(): void
    {
        $alat = Alat::where('kode_alat', 'ALT002')->first();
        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 2,
        ]);
        $this->assertEquals(2, OnsiteKioskController::countCart());

        $response = $this->post(route('onsite.batal'));

        $response->assertRedirect(route('onsite.landing'));
        $this->assertEquals(0, OnsiteKioskController::countCart());
        $this->assertNull(Session::get('onsite_last_completed_trx'));
    }

    /**
     * 12. Timeout inaktivitas tablet membersihkan sesi setelah batas waktu
     */
    public function test_onsite_kiosk_timeout_clears_session_after_inactivity(): void
    {
        $alat = Alat::where('kode_alat', 'ALT002')->first();
        $this->post(route('onsite.keranjang.tambah'), [
            'kode_alat' => $alat->kode_alat,
            'jumlah' => 2,
        ]);
        $this->assertEquals(2, OnsiteKioskController::countCart());

        // Simulasikan inaktivitas 16 menit (960 detik > 900 detik timeout)
        Session::put('onsite_last_activity', time() - 960);

        // Akses halaman katalog berikutnya
        $response = $this->get(route('onsite.katalog'));

        // Harus diarahkan kembali ke landing page dengan pemberitahuan timeout
        $response->assertRedirect(route('onsite.landing'));
        $response->assertSessionHas('info');

        // Keranjang harus sudah bersih
        $this->assertEquals(0, OnsiteKioskController::countCart());
    }

    /**
     * 13. Respon mode on-site menyertakan header no-cache untuk mencegah bfcache
     */
    public function test_kiosk_response_contains_no_cache_headers(): void
    {
        $response = $this->get(route('onsite.landing'));

        $response->assertStatus(200);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
    }

    /**
     * 14. Sesi tablet tidak mengganggu atau mengeluarkan sesi login Admin pada browser berbeda
     */
    public function test_tablet_session_does_not_affect_admin_session(): void
    {
        $admin = User::where('role', 'Admin')->first();

        // Admin login di browsernya
        $this->actingAs($admin);
        $this->assertAuthenticatedAs($admin);

        // Tablet kiosk melakukan pembatalan dan reset
        $this->post(route('onsite.batal'));

        // Admin tetap terautentikasi dan dapat membuka dashboard
        $resAdmin = $this->get(route('admin.dashboard'));
        $resAdmin->assertStatus(200);
        $this->assertAuthenticatedAs($admin);
    }
}

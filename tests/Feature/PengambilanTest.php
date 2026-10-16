<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pembayaran;
use App\Models\Pengambilan;
use App\Models\Penyewaan;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PengambilanTest extends TestCase
{
    /**
     * Admin dapat melihat daftar pesanan yang siap diambil
     */
    public function test_admin_can_view_orders_ready_for_pickup(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Siapkan pesanan siap diambil dengan pembayaran lunas
        $penyewaan = Penyewaan::find('TRX-20261015-001');
        $penyewaan->status_penyewaan = 'Siap Diambil';
        $penyewaan->save();

        Pembayaran::create([
            'no_pembayaran' => 'PAY-20261016-001',
            'no_transaksi' => 'TRX-20261015-001',
            'total_biaya' => 200000,
            'jumlah_pembayaran' => 200000,
            'metode_pembayaran' => 'Transfer Bank',
            'status_pembayaran' => 'Lunas',
            'id_admin' => $admin->id_user,
            'waktu_konfirmasi' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.operasional.pengambilan'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Pesanan Siap Diambil');
        $response->assertSee('TRX-20261015-001');
        $response->assertSee('Andi Pratama');
        $response->assertSee('Tenda Dome 4 Orang');
        $response->assertSee('Serahkan Alat');
    }

    /**
     * Admin dapat mencatat tanggal, penerima, dan jumlah alat yang diserahkan
     */
    public function test_admin_can_process_equipment_handover_successfully(): void
    {
        $admin = User::where('username', 'admin')->first();

        $penyewaan = Penyewaan::find('TRX-20261015-001');
        $penyewaan->status_penyewaan = 'Siap Diambil';
        $penyewaan->save();

        Pembayaran::create([
            'no_pembayaran' => 'PAY-20261016-001',
            'no_transaksi' => 'TRX-20261015-001',
            'total_biaya' => 200000,
            'jumlah_pembayaran' => 200000,
            'metode_pembayaran' => 'Transfer Bank',
            'status_pembayaran' => 'Lunas',
            'id_admin' => $admin->id_user,
            'waktu_konfirmasi' => now(),
        ]);

        // Cek stok awal ALT001
        $stokAwal = Alat::find('ALT001')->stok;

        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengambilan', 'TRX-20261015-001'), [
            'tanggal_pengambilan' => '2026-10-16 10:00:00',
            'nama_penerima' => 'Andi Pratama Sendiri',
            'catatan' => 'Semua unit alat dicek lengkap dan berfungsi.',
            'items' => [
                'ALT001' => ['jumlah_diserahkan' => 1],
                'ALT002' => ['jumlah_diserahkan' => 2],
            ],
        ]);

        $response->assertRedirect(route('admin.operasional.pengambilan'));
        $response->assertSessionHas('success');

        // 1. Data pengambilan tercatat
        $pengambilan = Pengambilan::where('no_transaksi', 'TRX-20261015-001')->first();
        $this->assertNotNull($pengambilan);
        $this->assertEquals('Andi Pratama Sendiri', $pengambilan->nama_penerima);
        $this->assertEquals($admin->id_user, $pengambilan->id_admin);

        // 2. Transisi status sah menjadi Sedang Disewa
        $penyewaanAfter = Penyewaan::find('TRX-20261015-001');
        $this->assertEquals('Sedang Disewa', $penyewaanAfter->status_penyewaan);

        // 3. Jumlah diserahkan pada detail tercatat
        $detail1 = DetailPenyewaan::where('no_transaksi', 'TRX-20261015-001')->where('kode_alat', 'ALT001')->first();
        $this->assertEquals(1, $detail1->jumlah_diserahkan);

        // 4. Stok berkurang sesuai alat yang diserahkan
        $stokAkhir = Alat::find('ALT001')->stok;
        $this->assertEquals($stokAwal - 1, $stokAkhir);

        // 5. Audit trail tercatat
        if (Schema::hasTable('activity_logs')) {
            $this->assertDatabaseHas('activity_logs', [
                'id_user' => $admin->id_user,
                'aktivitas' => 'Menyerahkan Alat Camping (Pengambilan)',
                'modul' => 'Pengambilan',
            ]);
        }
    }

    /**
     * Tidak dapat menyerahkan alat jika pembayaran belum lunas
     */
    public function test_cannot_handover_equipment_if_payment_is_not_lunas(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Pembayaran belum lunas (belum ada record pembayaran atau belum Lunas)
        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengambilan', 'TRX-20261015-001'), [
            'tanggal_pengambilan' => '2026-10-16 10:00:00',
            'nama_penerima' => 'Andi Pratama',
            'items' => [
                'ALT001' => ['jumlah_diserahkan' => 1],
            ],
        ]);

        $response->assertSessionHas('error');

        $penyewaan = Penyewaan::find('TRX-20261015-001');
        $this->assertNotEquals('Sedang Disewa', $penyewaan->status_penyewaan);
        $this->assertNull(Pengambilan::where('no_transaksi', 'TRX-20261015-001')->first());
    }

    /**
     * Tidak dapat menyerahkan alat dua kali untuk transaksi yang sama
     */
    public function test_cannot_handover_equipment_twice(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Transaksi sudah Sedang Disewa
        $penyewaan = Penyewaan::find('TRX-20261015-001');
        $penyewaan->status_penyewaan = 'Sedang Disewa';
        $penyewaan->save();

        Pembayaran::create([
            'no_pembayaran' => 'PAY-20261016-001',
            'no_transaksi' => 'TRX-20261015-001',
            'total_biaya' => 200000,
            'jumlah_pembayaran' => 200000,
            'metode_pembayaran' => 'Transfer Bank',
            'status_pembayaran' => 'Lunas',
            'id_admin' => $admin->id_user,
            'waktu_konfirmasi' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.operasional.proses-pengambilan', 'TRX-20261015-001'), [
            'tanggal_pengambilan' => '2026-10-16 10:00:00',
            'nama_penerima' => 'Andi Pratama',
            'items' => [
                'ALT001' => ['jumlah_diserahkan' => 1],
            ],
        ]);

        $response->assertSessionHas('error');
    }

    /**
     * Non-admin tidak dapat mengakses penyerahan alat
     */
    public function test_non_admin_cannot_access_handover_actions(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();
        $pemilik = User::where('username', 'pemilik')->first();

        $this->actingAs($pengguna)->get(route('admin.operasional.pengambilan'))->assertStatus(403);
        $this->actingAs($pengguna)->post(route('admin.operasional.proses-pengambilan', 'TRX-20261015-001'))->assertStatus(403);

        $this->actingAs($pemilik)->get(route('admin.operasional.pengambilan'))->assertStatus(403);
        $this->actingAs($pemilik)->post(route('admin.operasional.proses-pengambilan', 'TRX-20261015-001'))->assertStatus(403);
    }
}

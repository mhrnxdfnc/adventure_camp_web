<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Penyewaan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    /**
     * Admin dapat melihat daftar akun pada menu Manajemen Pengguna
     */
    public function test_admin_can_view_user_management_index(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Akun Pengguna');
        $response->assertSee('Administrator');
        $response->assertSee('admin');
        $response->assertSee('pemilik');
        $response->assertSee('pelanggan');
        $response->assertSee('Tambah Akun Baru');
    }

    /**
     * Pemilik (Owner) TIDAK DAPAT mengakses manajemen pengguna (Strictly Read-Only 403)
     */
    public function test_pemilik_cannot_access_user_management(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        $this->actingAs($pemilik)->get(route('admin.users.index'))->assertStatus(403);
        $this->actingAs($pemilik)->get(route('admin.users.create'))->assertStatus(403);
        $this->actingAs($pemilik)->post(route('admin.users.store'), [])->assertStatus(403);
        $this->actingAs($pemilik)->get(route('admin.users.edit', 1))->assertStatus(403);
    }

    /**
     * Pengguna (Customer) TIDAK DAPAT mengakses manajemen pengguna (403)
     */
    public function test_pengguna_cannot_access_user_management(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();

        $this->actingAs($pengguna)->get(route('admin.users.index'))->assertStatus(403);
        $this->actingAs($pengguna)->get(route('admin.users.create'))->assertStatus(403);
        $this->actingAs($pengguna)->post(route('admin.users.store'), [])->assertStatus(403);
    }

    /**
     * Pengunjung tamu (Guest) diarahkan ke form login
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->get(route('admin.users.create'))->assertRedirect(route('login'));
    }

    /**
     * Admin dapat membuat akun baru dengan hashing Laravel dan tidak membocorkan password
     */
    public function test_admin_can_create_user_with_hashed_password(): void
    {
        $admin = User::where('username', 'admin')->first();

        $rawPassword = 'PasswordBaru123!';

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'nama_user' => 'Staff Baru Adventure',
            'username' => 'staff_baru',
            'password' => $rawPassword,
            'password_confirmation' => $rawPassword,
            'role' => 'Admin',
            'status_aktif' => 1,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        // Pastikan akun tersimpan di database
        $this->assertDatabaseHas('users', [
            'nama_user' => 'Staff Baru Adventure',
            'username' => 'staff_baru',
            'role' => 'Admin',
            'status_aktif' => 1,
        ]);

        // Verifikasi keamanan: Password plaintext TIDAK PERNAH tersimpan di database
        $this->assertDatabaseMissing('users', [
            'username' => 'staff_baru',
            'password' => $rawPassword,
        ]);

        $newUser = User::where('username', 'staff_baru')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue(Hash::check($rawPassword, $newUser->password));

        // Logout admin terlebih dahulu sebelum menguji login pengguna baru
        auth()->logout();

        // Verifikasi akun baru dapat digunakan login
        $loginRes = $this->post('/login', [
            'username' => 'staff_baru',
            'password' => $rawPassword,
        ]);
        $loginRes->assertRedirect(route('admin.dashboard'));
    }

    /**
     * Pembuatan akun memvalidasi keunikan username
     */
    public function test_create_user_validates_unique_username(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'nama_user' => 'Duplikat Admin',
            'username' => 'admin', // Sudah dipakai
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'Admin',
            'status_aktif' => 1,
        ]);

        $response->assertSessionHasErrors('username');
    }

    /**
     * Admin dapat mengedit nama, username, dan mereset password pengguna
     */
    public function test_admin_can_edit_user_and_reset_password(): void
    {
        $admin = User::where('username', 'admin')->first();
        $targetUser = User::where('username', 'budi')->first();

        $newPassword = 'BudiBaruPassword88';

        $response = $this->actingAs($admin)->put(route('admin.users.update', $targetUser->id_user), [
            'nama_user' => 'Budi Santoso Updated',
            'username' => 'budi_updated',
            'role' => 'Pengguna',
            'status_aktif' => 1,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $targetUser->refresh();
        $this->assertEquals('Budi Santoso Updated', $targetUser->nama_user);
        $this->assertEquals('budi_updated', $targetUser->username);
        $this->assertTrue(Hash::check($newPassword, $targetUser->password));

        // Logout admin sebelum menguji login target user dengan password baru
        auth()->logout();

        // Pastikan login dengan password baru berhasil
        $loginRes = $this->post('/login', [
            'username' => 'budi_updated',
            'password' => $newPassword,
        ]);
        $loginRes->assertRedirect(route('pengguna.dashboard'));
    }

    /**
     * Edit akun tanpa mengisi password tidak mengubah password lama
     */
    public function test_editing_user_without_password_preserves_existing_password(): void
    {
        $admin = User::where('username', 'admin')->first();
        $targetUser = User::where('username', 'budi')->first();
        $oldPasswordHash = $targetUser->password;

        $response = $this->actingAs($admin)->put(route('admin.users.update', $targetUser->id_user), [
            'nama_user' => 'Budi Nama Baru',
            'username' => 'budi', // username sama
            'role' => 'Pengguna',
            'status_aktif' => 1,
            'password' => '', // kosong
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $targetUser->refresh();
        $this->assertEquals('Budi Nama Baru', $targetUser->nama_user);
        $this->assertEquals($oldPasswordHash, $targetUser->password);
    }

    /**
     * Admin dicegah menonaktifkan akunnya sendiri
     */
    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Coba lewat update
        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin->id_user), [
            'nama_user' => 'Administrator',
            'username' => 'admin',
            'role' => 'Admin',
            'status_aktif' => 0, // Nonaktifkan sendiri
        ]);

        $response->assertSessionHasErrors('status_aktif');
        $admin->refresh();
        $this->assertEquals(1, $admin->status_aktif);

        // 2. Coba lewat toggle-status
        $toggleRes = $this->actingAs($admin)->patch(route('admin.users.toggle-status', $admin->id_user));
        $toggleRes->assertSessionHas('error');
        $admin->refresh();
        $this->assertEquals(1, $admin->status_aktif);

        // 3. Coba lewat destroy
        $delRes = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin->id_user));
        $delRes->assertSessionHas('error');
        $admin->refresh();
        $this->assertEquals(1, $admin->status_aktif);
    }

    /**
     * Admin dicegah mengubah role akunnya sendiri
     */
    public function test_admin_cannot_change_own_role(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin->id_user), [
            'nama_user' => 'Administrator',
            'username' => 'admin',
            'role' => 'Pemilik', // Turunkan role sendiri
            'status_aktif' => 1,
        ]);

        $response->assertSessionHasErrors('role');
        $admin->refresh();
        $this->assertEquals('Admin', $admin->role);
    }

    /**
     * Menonaktifkan akun mencegah login tanpa menghapus riwayat transaksi
     */
    public function test_deactivating_user_prevents_login_without_deleting_rental_history(): void
    {
        $admin = User::where('username', 'admin')->first();
        $pelangganUser = User::where('username', 'pelanggan')->first();

        // Pastikan pelanggan memiliki riwayat transaksi penyewaan
        $transaksiCount = Penyewaan::where('id_user', $pelangganUser->id_user)->count();
        $this->assertGreaterThan(0, $transaksiCount);

        // Admin menonaktifkan akun pelanggan
        $response = $this->actingAs($admin)->patch(route('admin.users.toggle-status', $pelangganUser->id_user));
        $response->assertSessionHas('success');

        $pelangganUser->refresh();
        $this->assertEquals(0, $pelangganUser->status_aktif);

        // Riwayat transaksi tetap utuh di database (TIDAK terhapus)
        $this->assertEquals($transaksiCount, Penyewaan::where('id_user', $pelangganUser->id_user)->count());

        // Logout sesi admin sebelum menguji login akun yang dinonaktifkan
        auth()->logout();

        // Akun nonaktif tidak dapat login
        $loginRes = $this->post('/login', [
            'username' => 'pelanggan',
            'password' => 'pelanggan123',
        ]);
        $loginRes->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Endpoint langsung reset password berhasil memperbarui hash password
     */
    public function test_direct_reset_password_endpoint_updates_hash_successfully(): void
    {
        $admin = User::where('username', 'admin')->first();
        $targetUser = User::where('username', 'budi')->first();

        $newPassword = 'PasswordResetDirect999!';

        $response = $this->actingAs($admin)->post(route('admin.users.reset-password', $targetUser->id_user), [
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $targetUser->refresh();
        $this->assertTrue(Hash::check($newPassword, $targetUser->password));

        // Logout admin sebelum menguji login pengguna dengan password baru
        auth()->logout();

        $loginRes = $this->post('/login', [
            'username' => 'budi',
            'password' => $newPassword,
        ]);
        $loginRes->assertRedirect(route('pengguna.dashboard'));
    }

    /**
     * Reset password langsung memvalidasi panjang minimal dan konfirmasi password
     */
    public function test_direct_reset_password_validates_min_length_and_confirmation(): void
    {
        $admin = User::where('username', 'admin')->first();
        $targetUser = User::where('username', 'budi')->first();

        // 1. Password terlalu pendek (< 8 karakter)
        $resShort = $this->actingAs($admin)->post(route('admin.users.reset-password', $targetUser->id_user), [
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ]);
        $resShort->assertSessionHasErrors('password');

        // 2. Konfirmasi password tidak cocok
        $resMismatch = $this->actingAs($admin)->post(route('admin.users.reset-password', $targetUser->id_user), [
            'password' => 'passwordPanjang123',
            'password_confirmation' => 'passwordBerbeda456',
        ]);
        $resMismatch->assertSessionHasErrors('password');
    }

    /**
     * Admin aktif terakhir tidak dapat dinonaktifkan
     */
    public function test_cannot_deactivate_last_active_admin_account(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Pastikan hanya tersisa 1 admin aktif
        User::where('role', 'Admin')
            ->where('id_user', '!=', $admin->id_user)
            ->update(['status_aktif' => 0]);

        // Coba nonaktifkan admin terakhir lewat toggle-status
        $resToggle = $this->actingAs($admin)->patch(route('admin.users.toggle-status', $admin->id_user));
        $resToggle->assertSessionHas('error');
        $admin->refresh();
        $this->assertEquals(1, $admin->status_aktif);

        // Coba nonaktifkan admin terakhir lewat destroy
        $resDestroy = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin->id_user));
        $resDestroy->assertSessionHas('error');
        $admin->refresh();
        $this->assertEquals(1, $admin->status_aktif);

        // Coba nonaktifkan admin terakhir lewat update
        $resUpdate = $this->actingAs($admin)->put(route('admin.users.update', $admin->id_user), [
            'nama_user' => 'Admin Satu-Satunya',
            'username' => 'admin',
            'role' => 'Admin',
            'status_aktif' => 0,
        ]);
        $resUpdate->assertSessionHasErrors('status_aktif');
        $admin->refresh();
        $this->assertEquals(1, $admin->status_aktif);
    }

    /**
     * Role admin aktif terakhir tidak dapat diubah menjadi peran non-admin
     */
    public function test_cannot_change_role_of_last_active_admin_account(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Pastikan hanya tersisa 1 admin aktif
        User::where('role', 'Admin')
            ->where('id_user', '!=', $admin->id_user)
            ->update(['status_aktif' => 0]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin->id_user), [
            'nama_user' => 'Admin Satu-Satunya',
            'username' => 'admin',
            'role' => 'Pengguna', // Coba ubah jadi Pengguna
            'status_aktif' => 1,
        ]);

        $response->assertSessionHasErrors('role');
        $admin->refresh();
        $this->assertEquals('Admin', $admin->role);
    }

    /**
     * Admin dapat membuat akun pengguna dan menghubungkannya dengan pelanggan on-site yang sudah ada
     */
    public function test_admin_can_create_user_and_link_existing_onsite_customer(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Buat data pelanggan on-site walk-in yang belum memiliki akun user
        $pelangganOnsite = Pelanggan::create([
            'nama_pelanggan' => 'Pelanggan Toko Fisik',
            'alamat' => 'Jl. Toko Kios No. 10',
            'nomor_telepon' => '087799887766',
            'created_at' => now(),
        ]);

        $initialPelangganCount = Pelanggan::count();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'nama_user' => 'Pelanggan Toko Fisik',
            'username' => 'user_onsite_01',
            'password' => 'PasswordOnsite123!',
            'password_confirmation' => 'PasswordOnsite123!',
            'role' => 'Pengguna',
            'status_aktif' => 1,
            'id_pelanggan_onsite' => $pelangganOnsite->id_pelanggan,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        // Akun user tertaut ke pelanggan on-site yang sudah ada, tanpa membuat pelanggan ganda
        $newUser = User::where('username', 'user_onsite_01')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals($pelangganOnsite->id_pelanggan, $newUser->id_pelanggan);
        $this->assertEquals($initialPelangganCount, Pelanggan::count());
    }

    /**
     * Admin dapat membuat akun pengguna baru dengan rincian telepon dan alamat
     */
    public function test_admin_can_create_user_with_custom_phone_and_address(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'nama_user' => 'Kurnia Ramadhan',
            'username' => 'kurnia_camp',
            'password' => 'KurniaPass123!',
            'password_confirmation' => 'KurniaPass123!',
            'role' => 'Pengguna',
            'status_aktif' => 1,
            'nomor_telepon' => '081399882211',
            'alamat' => 'Komplek Gn. Puntang No. 7',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $newUser = User::where('username', 'kurnia_camp')->with('pelanggan')->first();
        $this->assertNotNull($newUser);
        $this->assertNotNull($newUser->pelanggan);
        $this->assertEquals('081399882211', $newUser->pelanggan->nomor_telepon);
        $this->assertEquals('Komplek Gn. Puntang No. 7', $newUser->pelanggan->alamat);
    }

    /**
     * Pemilik dan Pengguna dilarang memanggil endpoint reset-password dan toggle-status (403)
     */
    public function test_non_admin_cannot_call_reset_password_or_toggle_status(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();
        $pengguna = User::where('username', 'pelanggan')->first();
        $targetUser = User::where('username', 'budi')->first();

        // 1. Pemilik (Strictly Read-Only)
        $this->actingAs($pemilik)->post(route('admin.users.reset-password', $targetUser->id_user), [
            'password' => 'PercobaanReset123',
            'password_confirmation' => 'PercobaanReset123',
        ])->assertStatus(403);

        $this->actingAs($pemilik)->patch(route('admin.users.toggle-status', $targetUser->id_user))
            ->assertStatus(403);

        // 2. Pengguna biasa
        $this->actingAs($pengguna)->post(route('admin.users.reset-password', $targetUser->id_user), [
            'password' => 'PercobaanReset123',
            'password_confirmation' => 'PercobaanReset123',
        ])->assertStatus(403);

        $this->actingAs($pengguna)->patch(route('admin.users.toggle-status', $targetUser->id_user))
            ->assertStatus(403);
    }

    /**
     * Dashboard Admin menampilkan widget dan link akses Manajemen Pengguna
     */
    public function test_admin_dashboard_shows_user_management_widget_and_links(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Pengguna');
        $response->assertSee('Total Akun Terdaftar');
        $response->assertSee(route('admin.users.index'));
        $response->assertSee(route('admin.users.create'));
    }

    /**
     * Admin dapat melihat halaman rincian detail akun pengguna (show)
     */
    public function test_admin_can_view_user_detail_page(): void
    {
        $admin = User::where('username', 'admin')->first();
        $targetUser = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($admin)->get(route('admin.users.show', $targetUser->id_user));

        $response->assertStatus(200);
        $response->assertSee('Rincian Akun: '.$targetUser->nama_user);
        $response->assertSee($targetUser->username);
        $response->assertSee('Pengguna / Pelanggan');
        $response->assertSee('Aktif');
        $response->assertSee('Terdaftar Sejak:');
        $response->assertSee($targetUser->pelanggan->nomor_telepon);
    }

    /**
     * Halaman detail akun pengguna TIDAK membocorkan password, hash bcrypt, maupun remember token
     */
    public function test_user_detail_page_does_not_leak_passwords_hashes_or_tokens(): void
    {
        $admin = User::where('username', 'admin')->first();
        $targetUser = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($admin)->get(route('admin.users.show', $targetUser->id_user));

        $response->assertStatus(200);
        $response->assertDontSee($targetUser->password);
        $response->assertDontSee('$2y$');
        $response->assertDontSee('remember_token');
        $response->assertSee('Password dan kredensial aman terenkripsi (bcrypt).');
    }

    /**
     * Pemilik dan Pengguna dilarang mengakses halaman detail pengguna (403)
     */
    public function test_non_admin_cannot_access_user_detail_page(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();
        $pengguna = User::where('username', 'pelanggan')->first();
        $targetUser = User::where('username', 'admin')->first();

        // Pemilik -> 403
        $this->actingAs($pemilik)->get(route('admin.users.show', $targetUser->id_user))->assertStatus(403);

        // Pengguna -> 403
        $this->actingAs($pengguna)->get(route('admin.users.show', $targetUser->id_user))->assertStatus(403);

        // Tamu -> 302 login
        auth()->logout();
        $this->get(route('admin.users.show', $targetUser->id_user))->assertRedirect(route('login'));
    }
}

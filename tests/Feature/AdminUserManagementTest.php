<?php

namespace Tests\Feature;

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
}

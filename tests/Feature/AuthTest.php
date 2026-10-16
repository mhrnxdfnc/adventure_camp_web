<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    /**
     * Halaman login dapat dirender
     */
    public function test_login_page_renders_with_form_and_roles(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Adventure Camp');
        $response->assertSee('Login Sebagai');
        $response->assertSee('Admin (Operasional');
        $response->assertSee('Pengguna (Penyewa Alat Camping)');
        $response->assertSee('Pemilik (Pengawasan Bisnis Read-Only)');
        $response->assertSee('Daftar Sekarang');
    }

    /**
     * Akun Admin legacy dapat login dengan password lama dan diarahkan ke dashboard Admin
     */
    public function test_admin_can_login_with_legacy_password(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
            'role' => 'Admin',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(Auth::user()->isAdmin());
    }

    /**
     * Akun Pemilik legacy dapat login dengan password lama dan diarahkan ke dashboard Pemilik
     */
    public function test_pemilik_can_login_with_legacy_password(): void
    {
        $response = $this->post('/login', [
            'username' => 'pemilik',
            'password' => 'pemilik123',
            'role' => 'Pemilik',
        ]);

        $response->assertRedirect('/pemilik/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(Auth::user()->isPemilik());
    }

    /**
     * Akun Pelanggan legacy dapat login dan diarahkan ke dashboard Pengguna
     */
    public function test_pengguna_can_login_with_legacy_password(): void
    {
        $response = $this->post('/login', [
            'username' => 'pelanggan',
            'password' => 'pelanggan123',
            'role' => 'Pengguna',
        ]);

        $response->assertRedirect('/pengguna/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(Auth::user()->isPengguna());
    }

    /**
     * Login pertama kali otomatis meng-upgrade password plaintext menjadi bcrypt hash
     */
    public function test_login_automatically_upgrades_plaintext_password_to_hash(): void
    {
        $user = User::where('username', 'admin')->first();
        $this->assertEquals('admin123', $user->password); // Masih plaintext

        $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
            'role' => 'Admin',
        ]);

        $user->refresh();
        $this->assertNotEquals('admin123', $user->password);
        $this->assertTrue(Hash::check('admin123', $user->password));
    }

    /**
     * Akun non-aktif ditolak saat mencoba login
     */
    public function test_inactive_account_cannot_login(): void
    {
        User::create([
            'nama_user' => 'Akun Nonaktif',
            'username' => 'nonaktif',
            'password' => Hash::make('password123'),
            'role' => 'Pengguna',
            'status_aktif' => 0,
        ]);

        $response = $this->post('/login', [
            'username' => 'nonaktif',
            'password' => 'password123',
            'role' => 'Pengguna',
        ]);

        $response->assertSessionHasErrors(['username']);
        $this->assertGuest();
    }

    /**
     * Password yang salah ditolak
     */
    public function test_invalid_password_is_rejected(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'password_salah',
            'role' => 'Admin',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }

    /**
     * Ketidakcocokan role pilihan dengan role di akun ditolak
     */
    public function test_role_mismatch_is_rejected(): void
    {
        // Mencoba login akun pelanggan tetapi memilih role Admin
        $response = $this->post('/login', [
            'username' => 'pelanggan',
            'password' => 'pelanggan123',
            'role' => 'Admin',
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->assertGuest();
    }

    /**
     * Logout berhasil menghapus sesi dan mengarahkan kembali ke login untuk Admin
     */
    public function test_admin_can_logout(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    /**
     * Logout berhasil untuk role Pemilik (tidak terblokir EnsurePemilikReadOnly)
     */
    public function test_pemilik_can_logout(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        $response = $this->actingAs($pemilik)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/pemilik/dashboard')->assertRedirect('/login');
    }

    /**
     * Logout berhasil untuk role Pengguna / Pelanggan
     */
    public function test_pengguna_can_logout(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($pengguna)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/pengguna/dashboard')->assertRedirect('/login');
    }

    /**
     * Halaman dashboard memiliki header anti-cache agar tidak dapat diakses via Back browser setelah logout
     */
    public function test_authenticated_dashboard_has_no_cache_headers(): void
    {
        $admin = User::where('username', 'admin')->first();
        $resAdmin = $this->actingAs($admin)->get('/admin/dashboard');
        $this->assertStringContainsString('no-store', $resAdmin->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', $resAdmin->headers->get('Cache-Control'));
        $resAdmin->assertHeader('Pragma', 'no-cache');

        $pemilik = User::where('username', 'pemilik')->first();
        $resPemilik = $this->actingAs($pemilik)->get('/pemilik/dashboard');
        $this->assertStringContainsString('no-store', $resPemilik->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', $resPemilik->headers->get('Cache-Control'));

        $pengguna = User::where('username', 'pelanggan')->first();
        $resPengguna = $this->actingAs($pengguna)->get('/pengguna/dashboard');
        $this->assertStringContainsString('no-store', $resPengguna->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', $resPengguna->headers->get('Cache-Control'));
    }

    /**
     * Akses tamu ke dashboard dilindungi dan diarahkan ke login
     */
    public function test_unauthenticated_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/pengguna/dashboard')->assertRedirect('/login');
        $this->get('/pemilik/dashboard')->assertRedirect('/login');
    }

    /**
     * Pengguna tidak dapat membuka dashboard Admin (403 Forbidden)
     */
    public function test_pengguna_cannot_access_admin_dashboard(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($pengguna)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    /**
     * Pengguna tidak dapat membuka dashboard Pemilik (403 Forbidden)
     */
    public function test_pengguna_cannot_access_pemilik_dashboard(): void
    {
        $pengguna = User::where('username', 'pelanggan')->first();

        $response = $this->actingAs($pengguna)->get('/pemilik/dashboard');

        $response->assertForbidden();
    }

    /**
     * Admin tidak dapat membuka dashboard Pengguna (403 Forbidden)
     */
    public function test_admin_cannot_access_pengguna_dashboard(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/pengguna/dashboard');

        $response->assertForbidden();
    }

    /**
     * Pemilik berstatus strictly read-only dan dilarang melakukan aksi POST / mutasi (403 Forbidden)
     */
    public function test_pemilik_cannot_perform_mutations_strictly_read_only(): void
    {
        $pemilik = User::where('username', 'pemilik')->first();

        $response = $this->actingAs($pemilik)->post('/pemilik/test-action');

        $response->assertForbidden();
    }

    /**
     * Pendaftaran Pengguna baru berhasil membuat entri pelanggan dan user yang saling terhubung
     */
    public function test_customer_registration_creates_linked_records(): void
    {
        $response = $this->post('/register', [
            'nama_pelanggan' => 'Fajar Pratama',
            'alamat' => 'Jl. Danau Toba No. 12, Malang',
            'nomor_telepon' => '089912345678',
            'username' => 'fajarpratama',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect('/pengguna/dashboard');
        $this->assertAuthenticated();

        $user = User::where('username', 'fajarpratama')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Pengguna', $user->role);
        $this->assertNotNull($user->id_pelanggan);

        $pelanggan = Pelanggan::find($user->id_pelanggan);
        $this->assertNotNull($pelanggan);
        $this->assertEquals('Fajar Pratama', $pelanggan->nama_pelanggan);
        $this->assertEquals('089912345678', $pelanggan->nomor_telepon);
    }

    /**
     * Validasi registrasi: nomor telepon dan username unik, password minimal 8 karakter
     */
    public function test_registration_validation_rules(): void
    {
        // 1. Password kurang dari 8 karakter
        $response = $this->post('/register', [
            'nama_pelanggan' => 'Budi',
            'alamat' => 'Malang',
            'nomor_telepon' => '0811111111',
            'username' => 'budi1',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);
        $response->assertSessionHasErrors(['password']);

        // 2. Nomor telepon duplikat
        $response = $this->post('/register', [
            'nama_pelanggan' => 'Andi Tiruan',
            'alamat' => 'Malang',
            'nomor_telepon' => '081234567890', // Sudah terdaftar pada Andi Pratama
            'username' => 'anditiruan',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertSessionHasErrors(['nomor_telepon']);

        // 3. Username duplikat
        $response = $this->post('/register', [
            'nama_pelanggan' => 'Admin Palsu',
            'alamat' => 'Malang',
            'nomor_telepon' => '0877777777',
            'username' => 'admin', // Sudah ada
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertSessionHasErrors(['username']);
    }

    /**
     * Upaya eskalasi peran pada registrasi dicegah (role tetap Pengguna)
     */
    public function test_registration_cannot_elevate_role(): void
    {
        $this->post('/register', [
            'nama_pelanggan' => 'Hacker Coba Admin',
            'alamat' => 'Unknown',
            'nomor_telepon' => '082223334455',
            'username' => 'hackertry',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'Admin', // Injeksi role berniat jahat
        ]);

        $user = User::where('username', 'hackertry')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Pengguna', $user->role);
        $this->assertFalse($user->isAdmin());
    }
}

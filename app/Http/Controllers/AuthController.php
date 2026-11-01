<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Tampilkan formulir login
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi login
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'role' => ['nullable', 'string', 'in:Admin,Pemilik,Pengguna,Pelanggan'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'role.in' => 'Pilihan peran tidak valid.',
        ]);

        $user = User::where('username', $credentials['username'])->first();

        // 1. Verifikasi keberadaan akun
        if (! $user) {
            return back()->withInput($request->only('username', 'role'))->withErrors([
                'username' => 'Username atau password salah.',
            ]);
        }

        // 2. Verifikasi kecocokan password (mendukung upgrade hash dari password legacy plaintext)
        $storedPassword = (string) $user->password;
        $isHashed = password_get_info($storedPassword)['algo'] !== null;
        $passwordValid = false;

        if ($isHashed) {
            $passwordValid = Hash::check($credentials['password'], $storedPassword);
        } else {
            if (hash_equals($storedPassword, (string) $credentials['password'])) {
                // Password lama plaintext terverifikasi; upgrade langsung ke bcrypt hash Laravel
                $user->password = Hash::make($credentials['password']);
                $user->save();
                $passwordValid = true;
            }
        }

        if (! $passwordValid) {
            return back()->withInput($request->only('username', 'role'))->withErrors([
                'password' => 'Username atau password salah.',
            ]);
        }

        // 3. Verifikasi status akun aktif
        if (! $user->isActive()) {
            return back()->withInput($request->only('username', 'role'))->withErrors([
                'username' => 'Akun Anda telah dinonaktifkan. Silakan hubungi Administrator.',
            ]);
        }

        // 4. Verifikasi kecocokan role pilihan dengan role di database
        if (! empty($credentials['role'])) {
            $selectedRole = $credentials['role'];
            $allowedRoles = in_array($selectedRole, ['Pengguna', 'Pelanggan'], true)
                ? ['Pengguna', 'Pelanggan']
                : [$selectedRole];

            if (! in_array($user->role, $allowedRoles, true)) {
                return back()->withInput($request->only('username', 'role'))->withErrors([
                    'role' => "Peran akun Anda adalah {$user->normalized_role}, tidak sesuai dengan pilihan peran ({$selectedRole}).",
                ]);
            }
        }

        // 5. Login sesi dan regenerasi session id
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Catat aktivitas login di audit trail log
        ActivityLog::record(
            $user->id_user,
            'Login Sistem',
            'Autentikasi',
            "Pengguna {$user->username} ({$user->normalized_role}) berhasil masuk ke sistem"
        );

        return $this->redirectByRole($user, "Selamat datang kembali, {$user->nama_user}!");
    }

    /**
     * Tampilkan formulir registrasi mandiri Pengguna
     */
    public function showRegisterForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        return view('auth.register');
    }

    /**
     * Proses registrasi Pengguna baru dan tautkan ke data Pelanggan
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string', 'max:255'],
            'nomor_telepon' => ['required', 'string', 'max:20', 'unique:pelanggan,nomor_telepon'],
            'username' => ['required', 'string', 'min:4', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nama_pelanggan.required' => 'Nama lengkap wajib diisi.',
            'alamat.required' => 'Alamat domisili wajib diisi.',
            'nomor_telepon.required' => 'Nomor telepon wajib diisi.',
            'nomor_telepon.unique' => 'Nomor telepon ini sudah terdaftar.',
            'username.required' => 'Username wajib diisi.',
            'username.min' => 'Username minimal terdiri dari 4 karakter.',
            'username.unique' => 'Username ini sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user = DB::transaction(function () use ($validated) {
            // 1. Simpan profil pelanggan
            $pelanggan = Pelanggan::create([
                'nama_pelanggan' => $validated['nama_pelanggan'],
                'alamat' => $validated['alamat'],
                'nomor_telepon' => $validated['nomor_telepon'],
                'created_at' => now(),
            ]);

            // 2. Buat akun user dengan role Pengguna (strictly hardcoded, cegah privilege escalation)
            return User::create([
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'nama_user' => $validated['nama_pelanggan'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'role' => 'Pengguna',
                'status_aktif' => 1,
                'created_at' => now(),
            ]);
        });

        // 3. Login otomatis setelah registrasi berhasil
        Auth::login($user);
        $request->session()->regenerate();

        // Catat aktivitas registrasi di audit trail log
        ActivityLog::record(
            $user->id_user,
            'Registrasi Pengguna Baru',
            'Autentikasi',
            "Pengguna baru terdaftar dengan username {$user->username} atas nama {$user->nama_user}"
        );

        return redirect()->route('pengguna.dashboard')->with(
            'success',
            'Pendaftaran berhasil! Selamat datang di Adventure Camp Rental, '.$user->nama_user.'.'
        );
    }

    /**
     * Proses logout sistem
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            ActivityLog::record(
                $user->id_user,
                'Logout Sistem',
                'Autentikasi',
                "Pengguna {$user->username} ({$user->normalized_role}) telah keluar dari sistem"
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }

    /**
     * Helper pengarah dashboard berdasarkan role
     */
    protected function redirectByRole(User $user, ?string $message = null): RedirectResponse
    {
        $redirect = match (true) {
            $user->isAdmin() => redirect()->intended(route('admin.dashboard')),
            $user->isPemilik() => redirect()->intended(route('pemilik.dashboard')),
            default => redirect()->intended(route('pengguna.dashboard')),
        };

        if ($message) {
            $redirect->with('success', $message);
        }

        return $redirect;
    }
}

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
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    /**
     * Periksa apakah user merupakan administrator aktif terakhir di sistem
     */
    public function isLastActiveAdmin(User $user): bool
    {
        if (! $user->isAdmin() || ! $user->isActive()) {
            return false;
        }

        $otherActiveAdmins = User::where('role', 'Admin')
            ->where('status_aktif', 1)
            ->where('id_user', '!=', $user->id_user)
            ->count();

        return $otherActiveAdmins === 0;
    }

    /**
     * Tampilkan daftar akun pengguna sistem
     */
    public function index(Request $request): View
    {
        $query = User::query()->with(['pelanggan.penyewaan']);

        // Filter pencarian berdasarkan nama user atau username
        if ($request->filled('q')) {
            $search = trim((string) $request->q);
            $query->where(function ($sub) use ($search) {
                $sub->where('nama_user', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Filter role pengguna
        if ($request->filled('role')) {
            $roleFilter = trim((string) $request->role);
            if ($roleFilter === 'Pengguna') {
                $query->whereIn('role', ['Pengguna', 'Pelanggan']);
            } else {
                $query->where('role', $roleFilter);
            }
        }

        // Filter status akun aktif / nonaktif
        if ($request->filled('status')) {
            $statusVal = (int) $request->status;
            $query->where('status_aktif', $statusVal);
        }

        $query->orderBy('id_user', 'asc');
        $users = $query->paginate(10)->withQueryString();

        // Ringkasan metrik statistik akun
        $totalUsers = User::count();
        $totalAdmin = User::where('role', 'Admin')->count();
        $totalPemilik = User::where('role', 'Pemilik')->count();
        $totalPengguna = User::whereIn('role', ['Pengguna', 'Pelanggan'])->count();
        $totalAktif = User::where('status_aktif', 1)->count();
        $totalNonaktif = User::where('status_aktif', 0)->count();
        $activeAdminCount = User::where('role', 'Admin')->where('status_aktif', 1)->count();

        // Pelanggan on-site yang belum memiliki akun login
        $totalPelangganTanpaAkun = Pelanggan::whereDoesntHave('user')->count();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $request->query('q', ''),
            'selectedRole' => $request->query('role', ''),
            'selectedStatus' => $request->query('status', ''),
            'totalUsers' => $totalUsers,
            'totalAdmin' => $totalAdmin,
            'totalPemilik' => $totalPemilik,
            'totalPengguna' => $totalPengguna,
            'totalAktif' => $totalAktif,
            'totalNonaktif' => $totalNonaktif,
            'activeAdminCount' => $activeAdminCount,
            'totalPelangganTanpaAkun' => $totalPelangganTanpaAkun,
        ]);
    }

    /**
     * Tampilkan formulir penambahan akun baru
     */
    public function create(): View
    {
        // Ambil daftar pelanggan on-site (toko) yang belum memiliki akun user
        $pelangganOnsite = Pelanggan::whereDoesntHave('user')
            ->orderBy('nama_pelanggan', 'asc')
            ->get();

        return view('admin.users.create', [
            'pelangganOnsite' => $pelangganOnsite,
        ]);
    }

    /**
     * Simpan akun pengguna baru ke database
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_user' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'min:4',
                'max:50',
                'regex:/^[a-zA-Z0-9._-]+$/',
                'unique:users,username',
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:Admin,Pemilik,Pengguna'],
            'status_aktif' => ['required', 'in:0,1'],
            'id_pelanggan_onsite' => ['nullable', 'integer', 'exists:pelanggan,id_pelanggan'],
            'nomor_telepon' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:255'],
        ], [
            'nama_user.required' => 'Nama lengkap pengguna wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.min' => 'Username minimal terdiri dari 4 karakter.',
            'username.regex' => 'Username hanya boleh memuat huruf, angka, titik, minus, dan garis bawah.',
            'username.unique' => 'Username ini sudah digunakan akun lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'role.required' => 'Peran akun wajib dipilih.',
            'role.in' => 'Pilihan peran akun tidak valid.',
            'status_aktif.required' => 'Status keaktifan akun wajib dipilih.',
            'id_pelanggan_onsite.exists' => 'Data pelanggan on-site yang dipilih tidak ditemukan.',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $idPelanggan = null;

            // Jika peran Pengguna, hubungkan dengan profil pelanggan
            if ($validated['role'] === 'Pengguna') {
                if (! empty($validated['id_pelanggan_onsite'])) {
                    $idPelanggan = (int) $validated['id_pelanggan_onsite'];
                } else {
                    $phone = ! empty($validated['nomor_telepon'])
                        ? trim((string) $validated['nomor_telepon'])
                        : '08'.str_pad((string) random_int(10000000, 99999999), 10, '0', STR_PAD_RIGHT);

                    $alamat = ! empty($validated['alamat'])
                        ? trim((string) $validated['alamat'])
                        : 'Alamat belum disetel';

                    $existingPelanggan = Pelanggan::findByPhone($phone);
                    if ($existingPelanggan && ! $existingPelanggan->user()->exists()) {
                        $idPelanggan = $existingPelanggan->id_pelanggan;
                    } else {
                        $pelanggan = Pelanggan::create([
                            'nama_pelanggan' => $validated['nama_user'],
                            'alamat' => $alamat,
                            'nomor_telepon' => $phone,
                            'created_at' => now(),
                        ]);
                        $idPelanggan = $pelanggan->id_pelanggan;
                    }
                }
            }

            return User::create([
                'id_pelanggan' => $idPelanggan,
                'nama_user' => $validated['nama_user'],
                'username' => strtolower($validated['username']),
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'status_aktif' => (int) $validated['status_aktif'],
                'created_at' => now(),
            ]);
        });

        ActivityLog::record(
            Auth::id() ?? 1,
            'Tambah Akun Pengguna',
            'User',
            "Menambahkan akun baru: {$user->username} (Peran: {$user->role}, Status: ".($user->status_aktif ? 'Aktif' : 'Nonaktif').')'
        );

        return redirect()->route('admin.users.index')->with(
            'success',
            "Akun pengguna [{$user->username}] ({$user->nama_user}) berhasil ditambahkan."
        );
    }

    /**
     * Tampilkan formulir edit akun pengguna
     */
    public function edit(User $user): View
    {
        $user->load('pelanggan');
        $isLastAdmin = $this->isLastActiveAdmin($user);

        return view('admin.users.edit', [
            'user' => $user,
            'isLastAdmin' => $isLastAdmin,
        ]);
    }

    /**
     * Perbarui data akun pengguna
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'nama_user' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'min:4',
                'max:50',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($user->id_user, 'id_user'),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:Admin,Pemilik,Pengguna'],
            'status_aktif' => ['required', 'in:0,1'],
            'nomor_telepon' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:255'],
        ], [
            'nama_user.required' => 'Nama lengkap pengguna wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.min' => 'Username minimal terdiri dari 4 karakter.',
            'username.regex' => 'Username hanya boleh memuat huruf, angka, titik, minus, dan garis bawah.',
            'username.unique' => 'Username ini sudah digunakan akun lain.',
            'password.min' => 'Password baru minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'role.required' => 'Peran akun wajib dipilih.',
            'role.in' => 'Pilihan peran akun tidak valid.',
            'status_aktif.required' => 'Status keaktifan akun wajib dipilih.',
        ]);

        // Keamanan: Cegah Admin mengubah role atau menonaktifkan akun sendiri
        if ((int) $user->id_user === (int) Auth::id()) {
            if ($validated['role'] !== 'Admin') {
                return back()->withInput()->withErrors([
                    'role' => 'Anda tidak dapat mengubah peran akun administrator Anda sendiri demi menjaga akses sistem.',
                ]);
            }

            if ((int) $validated['status_aktif'] !== 1) {
                return back()->withInput()->withErrors([
                    'status_aktif' => 'Anda tidak dapat menonaktifkan akun administrator Anda sendiri.',
                ]);
            }
        }

        // Keamanan: Cegah mengubah role atau menonaktifkan Admin aktif terakhir
        if ($this->isLastActiveAdmin($user)) {
            if ($validated['role'] !== 'Admin') {
                return back()->withInput()->withErrors([
                    'role' => 'Akun ini adalah administrator aktif terakhir di sistem. Perannya tidak dapat diubah agar sistem tetap dapat dikelola.',
                ]);
            }

            if ((int) $validated['status_aktif'] !== 1) {
                return back()->withInput()->withErrors([
                    'status_aktif' => 'Akun ini adalah administrator aktif terakhir di sistem. Akun tidak dapat dinonaktifkan agar sistem tetap dapat dikelola.',
                ]);
            }
        }

        $oldRole = $user->role;
        $oldStatus = $user->status_aktif;
        $passwordChanged = false;

        DB::transaction(function () use ($user, $validated, &$passwordChanged) {
            $user->nama_user = $validated['nama_user'];
            $user->username = strtolower($validated['username']);
            $user->role = $validated['role'];
            $user->status_aktif = (int) $validated['status_aktif'];

            // Jika peran adalah Pengguna
            if ($validated['role'] === 'Pengguna') {
                if (empty($user->id_pelanggan)) {
                    $phone = ! empty($validated['nomor_telepon'])
                        ? trim((string) $validated['nomor_telepon'])
                        : '08'.str_pad((string) random_int(10000000, 99999999), 10, '0', STR_PAD_RIGHT);

                    $alamat = ! empty($validated['alamat'])
                        ? trim((string) $validated['alamat'])
                        : 'Alamat belum disetel';

                    $pelanggan = Pelanggan::create([
                        'nama_pelanggan' => $validated['nama_user'],
                        'alamat' => $alamat,
                        'nomor_telepon' => $phone,
                        'created_at' => now(),
                    ]);
                    $user->id_pelanggan = $pelanggan->id_pelanggan;
                } else {
                    // Perbarui profil pelanggan jika terhubung
                    $pelanggan = $user->pelanggan;
                    if ($pelanggan) {
                        $pelanggan->nama_pelanggan = $validated['nama_user'];
                        if (! empty($validated['nomor_telepon'])) {
                            $pelanggan->nomor_telepon = trim((string) $validated['nomor_telepon']);
                        }
                        if (! empty($validated['alamat'])) {
                            $pelanggan->alamat = trim((string) $validated['alamat']);
                        }
                        $pelanggan->save();
                    }
                }
            }

            // Atur ulang password jika diisi
            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
                $passwordChanged = true;
            }

            $user->save();
        });

        $changes = [];
        if ($oldRole !== $user->role) {
            $changes[] = "Peran: {$oldRole} -> {$user->role}";
        }
        if ((int) $oldStatus !== (int) $user->status_aktif) {
            $changes[] = 'Status: '.($oldStatus ? 'Aktif' : 'Nonaktif').' -> '.($user->status_aktif ? 'Aktif' : 'Nonaktif');
        }
        if ($passwordChanged) {
            $changes[] = 'Password direset';
        }

        $logMsg = "Memperbarui akun {$user->username}".(empty($changes) ? '' : ' ('.implode(', ', $changes).')');

        ActivityLog::record(
            Auth::id() ?? 1,
            'Perbarui Akun Pengguna',
            'User',
            $logMsg
        );

        return redirect()->route('admin.users.index')->with(
            'success',
            "Perubahan data akun [{$user->username}] berhasil disimpan."
        );
    }

    /**
     * Beralih status aktif / nonaktif akun
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        // Keamanan: Cegah Admin menonaktifkan akun sendiri
        if ((int) $user->id_user === (int) Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun administrator Anda sendiri.');
        }

        // Keamanan: Cegah menonaktifkan admin aktif terakhir
        if ($user->isActive() && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'Tidak dapat menonaktifkan administrator terakhir. Sistem harus memiliki setidaknya satu administrator aktif untuk mengelola aplikasi.');
        }

        $newStatus = $user->status_aktif ? 0 : 1;
        $user->status_aktif = $newStatus;
        $user->save();

        $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLog::record(
            Auth::id() ?? 1,
            $newStatus ? 'Aktivasi Akun' : 'Deaktivasi Akun',
            'User',
            "Akun [{$user->username}] {$statusText} oleh Administrator"
        );

        return back()->with('success', "Akun [{$user->username}] berhasil {$statusText}. Riwayat transaksi tetap aman.");
    }

    /**
     * Atur ulang (reset) password akun pengguna secara langsung menggunakan hashing Laravel
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        ActivityLog::record(
            Auth::id() ?? 1,
            'Reset Password Pengguna',
            'User',
            "Mereset password akun [{$user->username}] ({$user->nama_user})"
        );

        return back()->with('success', "Password akun [{$user->username}] berhasil direset.");
    }

    /**
     * Nonaktifkan akun tanpa menghapus data historis transaksi
     */
    public function destroy(User $user): RedirectResponse
    {
        // Keamanan: Cegah Admin menonaktifkan akun sendiri
        if ((int) $user->id_user === (int) Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun administrator Anda sendiri.');
        }

        // Keamanan: Cegah menonaktifkan admin aktif terakhir
        if ($user->isActive() && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'Tidak dapat menonaktifkan administrator terakhir. Sistem harus memiliki setidaknya satu administrator aktif untuk mengelola aplikasi.');
        }

        // Kebijakan Integritas: Akun tidak dihapus permanen melainkan dinonaktifkan
        $user->status_aktif = 0;
        $user->save();

        ActivityLog::record(
            Auth::id() ?? 1,
            'Deaktivasi Akun',
            'User',
            "Akun [{$user->username}] dinonaktifkan (tanpa hapus riwayat transaksi)"
        );

        return redirect()->route('admin.users.index')->with(
            'success',
            "Akun [{$user->username}] telah dinonaktifkan. Data riwayat transaksi tetap terjaga utuh."
        );
    }
}

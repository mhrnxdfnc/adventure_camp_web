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
     * Tampilkan daftar akun pengguna sistem
     */
    public function index(Request $request): View
    {
        $query = User::query()->with('pelanggan');

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
        ]);
    }

    /**
     * Tampilkan formulir penambahan akun baru
     */
    public function create(): View
    {
        return view('admin.users.create');
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
        ]);

        $user = DB::transaction(function () use ($validated) {
            $idPelanggan = null;

            // Jika peran Pengguna, pastikan terhubung dengan profil pelanggan
            if ($validated['role'] === 'Pengguna') {
                $pelanggan = Pelanggan::create([
                    'nama_pelanggan' => $validated['nama_user'],
                    'alamat' => 'Alamat belum disetel',
                    'nomor_telepon' => '08'.str_pad((string) random_int(10000000, 99999999), 10, '0', STR_PAD_RIGHT),
                    'created_at' => now(),
                ]);
                $idPelanggan = $pelanggan->id_pelanggan;
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
        return view('admin.users.edit', compact('user'));
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

        $oldRole = $user->role;
        $oldStatus = $user->status_aktif;
        $passwordChanged = false;

        DB::transaction(function () use ($user, $validated, &$passwordChanged) {
            $user->nama_user = $validated['nama_user'];
            $user->username = strtolower($validated['username']);
            $user->role = $validated['role'];
            $user->status_aktif = (int) $validated['status_aktif'];

            // Jika peran diubah menjadi Pengguna dan belum memiliki tautan pelanggan
            if ($validated['role'] === 'Pengguna' && empty($user->id_pelanggan)) {
                $pelanggan = Pelanggan::create([
                    'nama_pelanggan' => $validated['nama_user'],
                    'alamat' => 'Alamat belum disetel',
                    'nomor_telepon' => '08'.str_pad((string) random_int(10000000, 99999999), 10, '0', STR_PAD_RIGHT),
                    'created_at' => now(),
                ]);
                $user->id_pelanggan = $pelanggan->id_pelanggan;
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
     * Nonaktifkan akun tanpa menghapus data historis transaksi
     */
    public function destroy(User $user): RedirectResponse
    {
        // Keamanan: Cegah Admin menonaktifkan akun sendiri
        if ((int) $user->id_user === (int) Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun administrator Anda sendiri.');
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

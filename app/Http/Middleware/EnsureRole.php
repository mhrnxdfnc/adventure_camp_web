<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. Pastikan pengguna sudah terautentikasi
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('login'))->with('error', 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
        }

        $user = Auth::user();

        // 2. Pastikan akun aktif
        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akun dinonaktifkan.'], 403);
            }

            return redirect()->route('login')->withErrors([
                'username' => 'Akun Anda dinonaktifkan. Silakan hubungi administrator.',
            ]);
        }

        // 3. Normalisasi daftar peran yang diizinkan
        $expandedRoles = [];
        foreach ($roles as $role) {
            $expandedRoles[] = $role;
            if ($role === 'Pengguna') {
                $expandedRoles[] = 'Pelanggan';
            } elseif ($role === 'Pelanggan') {
                $expandedRoles[] = 'Pengguna';
            }
        }

        // 4. Verifikasi kecocokan peran
        if (! in_array($user->role, $expandedRoles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }

            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        $response = $next($request);

        // Cegah caching halaman terproteksi di browser (bfcache) agar setelah logout
        // pengguna tidak dapat membuka dashboard melalui tombol Back browser tanpa login kembali
        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');

        return $response;
    }
}

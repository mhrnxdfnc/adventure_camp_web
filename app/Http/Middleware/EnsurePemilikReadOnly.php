<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePemilikReadOnly
{
    /**
     * Pastikan role Pemilik tidak dapat melakukan mutasi / perubahan data.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->isPemilik() && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
                // Izinkan proses logout untuk role Pemilik agar sesi dapat diakhiri secara normal
                if ($request->routeIs('logout') || $request->is('logout')) {
                    return $next($request);
                }

                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Role Pemilik bersifat strictly read-only dan dilarang mengubah data.',
                    ], 403);
                }

                abort(403, 'Akses ditolak: Role Pemilik bersifat strictly read-only dan dilarang mengubah data operasional.');
            }
        }

        return $next($request);
    }
}

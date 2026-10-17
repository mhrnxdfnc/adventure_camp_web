<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnsiteKioskSession
{
    /**
     * Batas waktu inaktivitas sesi tablet bersama (15 menit = 900 detik)
     */
    protected const INACTIVITY_TIMEOUT_SECONDS = 900;

    /**
     * Tangani permintaan masuk untuk mode tablet kasir on-site.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Periksa batas waktu inaktivitas sesi tablet (Inactivity Timeout)
        if ($request->hasSession()) {
            $lastActivity = $request->session()->get('onsite_last_activity');
            $currentTime = time();

            if ($lastActivity && ($currentTime - (int) $lastActivity > self::INACTIVITY_TIMEOUT_SECONDS)) {
                // Bersihkan seluruh data sesi sementara mode on-site
                $request->session()->forget([
                    'onsite_kiosk_cart',
                    'onsite_customer_draft',
                    'onsite_last_completed_trx',
                ]);
                $request->session()->put('onsite_last_activity', $currentTime);

                // Jika pengguna sedang berada di luar halaman landing, arahkan kembali dengan pemberitahuan
                if (! $request->routeIs('onsite.landing') && ! $request->routeIs('tablet')) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Sesi tablet telah kedaluwarsa karena tidak ada aktivitas. Silakan mulai pesanan baru.',
                            'timeout' => true,
                        ], 419);
                    }

                    return redirect()->route('onsite.landing')->with('info', 'Sesi tablet telah di-reset otomatis karena tidak ada aktivitas selama 15 menit. Data sementara telah dibersihkan demi melindungi privasi Anda.');
                }
            } else {
                $request->session()->put('onsite_last_activity', $currentTime);
            }
        }

        // 2. Lanjutkan pemrosesan request
        $response = $next($request);

        // 3. Tambahkan header proteksi cache agar riwayat halaman tablet tidak tersimpan di browser
        // Ini memastikan tombol 'Back' browser tidak menampilkan data pribadi pelanggan sebelumnya
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}

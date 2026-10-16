<?php

namespace App\Http\Controllers;

use App\Models\Penyewaan;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UiController extends Controller
{
    /**
     * Halaman showcase komponen antarmuka
     */
    public function showcase(): View
    {
        return view('ui-showcase');
    }

    /**
     * Tampilan login mandiri
     */
    public function login(): View
    {
        return view('auth.login');
    }

    /**
     * Layout dashboard Admin
     */
    public function adminDashboard(): View
    {
        return view('admin.dashboard');
    }

    /**
     * Layout dashboard Pengguna / Pelanggan
     */
    public function penggunaDashboard(): View
    {
        $user = Auth::user();
        $pelangganId = $user->id_pelanggan;

        $pesananList = Penyewaan::with(['pelanggan', 'pembayaran', 'detailPenyewaan.alat', 'pengambilan', 'pengembalian.denda'])
            ->where(function ($query) use ($user, $pelangganId) {
                if ($pelangganId) {
                    $query->where('id_pelanggan', $pelangganId);
                }
                $query->orWhere('id_user', $user->id_user);
            })
            ->orderBy('tanggal_sewa', 'desc')
            ->get();

        $pesananAktifCount = $pesananList->filter(function ($trx) {
            return in_array($trx->status_penyewaan, ['Menunggu Pembayaran', 'Disetujui', 'Siap Diambil', 'Sedang Disewa', 'Berjalan'], true);
        })->count();

        $tagihanMenunggu = (float) $pesananList->filter(function ($trx) {
            return ! $trx->pembayaran || ! $trx->pembayaran->isLunas();
        })->sum(function ($trx) {
            return $trx->total_biaya;
        });

        return view('pengguna.dashboard', [
            'pesananList' => $pesananList,
            'pesananAktifCount' => $pesananAktifCount,
            'tagihanMenunggu' => $tagihanMenunggu,
        ]);
    }

    /**
     * Layout dashboard Pemilik (Strictly Read-Only)
     */
    public function pemilikDashboard(): View
    {
        return view('pemilik.dashboard');
    }
}

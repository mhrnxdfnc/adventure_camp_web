<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pembayaran;
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
        $pesananMenunggu = Penyewaan::with(['pelanggan', 'pembayaran', 'detailPenyewaan.alat'])
            ->where('status_penyewaan', 'Menunggu Konfirmasi')
            ->orderBy('tanggal_sewa', 'asc')
            ->get();

        $countMenungguKonfirmasi = $pesananMenunggu->count();
        $countMenungguPembayaran = Pembayaran::where('status_pembayaran', 'Menunggu Verifikasi')->count();
        $countSedangDisewa = Penyewaan::whereIn('status_penyewaan', ['Sedang Disewa', 'Berjalan'])->count();

        // 1. Stok siap sewa (Kondisi Baik di toko yang dapat disewa)
        $stokKondisiBaik = (int) Alat::where('kondisi', 'Baik')->sum('stok');

        // 2. Stok perlu perbaikan (Master alat kondisi Perlu Perbaikan + unit pengembalian butuh perbaikan)
        $stokPerluPerbaikanMaster = (int) Alat::where('kondisi', 'Perlu Perbaikan')->sum('stok');
        $stokPerluPerbaikanReturn = (int) DetailPenyewaan::sum('jumlah_perlu_perbaikan');
        $stokPerluPerbaikan = $stokPerluPerbaikanMaster + $stokPerluPerbaikanReturn;

        // 3. Stok rusak / afkir (Master alat kondisi Rusak + unit pengembalian rusak)
        $stokRusakMaster = (int) Alat::where('kondisi', 'Rusak')->sum('stok');
        $stokRusakReturn = (int) DetailPenyewaan::sum('jumlah_rusak');
        $stokRusak = $stokRusakMaster + $stokRusakReturn;

        // 4. Stok unit yang sedang aktif dialokasikan / disewa di lapangan
        $stokSedangDisewaUnit = (int) DetailPenyewaan::whereHas('penyewaan', function ($q) {
            $q->whereIn('status_penyewaan', ['Siap Diambil', 'Sedang Disewa', 'Berjalan']);
        })->selectRaw('SUM(CASE WHEN jumlah_diserahkan > 0 THEN jumlah_diserahkan ELSE jumlah END) as total')->value('total');

        // 5. Total seluruh stok fisik inventaris (stok toko + sedang disewa + perlu perbaikan return + rusak return)
        $totalStokFisik = (int) Alat::sum('stok') + $stokSedangDisewaUnit + $stokPerluPerbaikanReturn + $stokRusakReturn;

        return view('admin.dashboard', [
            'pesananMenunggu' => $pesananMenunggu,
            'countMenungguKonfirmasi' => $countMenungguKonfirmasi,
            'countMenungguPembayaran' => $countMenungguPembayaran,
            'countSedangDisewa' => $countSedangDisewa,
            'totalStokFisik' => $totalStokFisik,
            'stokKondisiBaik' => $stokKondisiBaik,
            'stokPerluPerbaikan' => $stokPerluPerbaikan,
            'stokRusak' => $stokRusak,
        ]);
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

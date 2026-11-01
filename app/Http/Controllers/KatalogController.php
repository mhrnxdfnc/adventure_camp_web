<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Penyewaan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KatalogController extends Controller
{
    /**
     * Tampilkan katalog alat camping siap sewa untuk Pengguna
     */
    public function index(Request $request): View
    {
        $query = Alat::query();

        // Aturan bisnis utama: Hanya tampilkan alat berstatus Baik dan stok > 0
        $query->where('kondisi', 'Baik')
            ->where('stok', '>', 0);

        // Filter pencarian berdasarkan nama alat atau kode alat
        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($sub) use ($search) {
                $sub->where('nama_alat', 'like', "%{$search}%")
                    ->orWhere('kode_alat', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan kategori
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Urutkan berdasarkan kategori dan nama alat
        $query->orderBy('kategori', 'asc')->orderBy('nama_alat', 'asc');

        $alatList = $query->paginate(9)->withQueryString();

        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');
        $kodeList = $alatList->pluck('kode_alat');

        // Reservasi aktif yang beririsan dengan hari ini
        $reservedTodayMap = DetailPenyewaan::whereIn('kode_alat', $kodeList)
            ->whereHas('penyewaan', function ($q) use ($today, $tomorrow) {
                $q->whereIn('status_penyewaan', [
                    'Menunggu Konfirmasi',
                    'Menunggu Pembayaran',
                    'Disetujui',
                    'Siap Diambil',
                    'Sedang Disewa',
                    'Berjalan',
                ])
                    ->whereDate('tanggal_sewa', '<', $tomorrow)
                    ->whereDate('tanggal_kembali', '>', $today);
            })
            ->groupBy('kode_alat')
            ->selectRaw('kode_alat, SUM(jumlah) as total_reserved')
            ->pluck('total_reserved', 'kode_alat');

        // Unit yang saat ini sedang disewa di luar toko
        $sedangDisewaMap = DetailPenyewaan::whereIn('kode_alat', $kodeList)
            ->whereHas('penyewaan', fn ($q) => $q->whereIn('status_penyewaan', ['Sedang Disewa', 'Berjalan']))
            ->groupBy('kode_alat')
            ->selectRaw('kode_alat, SUM(CASE WHEN jumlah_diserahkan > 0 THEN jumlah_diserahkan ELSE jumlah END) as total_out')
            ->pluck('total_out', 'kode_alat');

        foreach ($alatList as $alat) {
            $kapasitas = (int) $alat->stok + (int) ($sedangDisewaMap[$alat->kode_alat] ?? 0);
            $reserved = (int) ($reservedTodayMap[$alat->kode_alat] ?? 0);
            $alat->stok_tersedia_hari_ini = max(0, $kapasitas - $reserved);
            $alat->kapasitas_total = $kapasitas;
        }

        // Daftar kategori unik yang memiliki stok siap sewa
        $kategoriList = Alat::where('kondisi', 'Baik')
            ->where('stok', '>', 0)
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori');

        return view('pengguna.katalog.index', compact('alatList', 'kategoriList'));
    }

    /**
     * Tampilkan rincian detail alat camping untuk Pengguna
     */
    public function show(string $kode_alat): View
    {
        $alat = Alat::where('kode_alat', $kode_alat)->firstOrFail();

        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');
        $alat->stok_tersedia_hari_ini = Penyewaan::hitungStokTersediaPeriode($alat->kode_alat, $today, $tomorrow);
        $alat->kapasitas_total = $alat->total_kapasitas_layak;

        return view('pengguna.katalog.show', compact('alat'));
    }
}

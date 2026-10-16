<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\DetailPenyewaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventarisController extends Controller
{
    /**
     * Tampilkan halaman daftar inventaris untuk memantau stok fisik,
     * stok siap sewa, stok dialokasikan/disewa, dan kondisi alat.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $kategoriFilter = trim((string) $request->query('kategori', ''));
        $kondisiFilter = trim((string) $request->query('kondisi', ''));

        // Query dasar alat
        $query = Alat::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_alat', 'like', "%{$search}%")
                    ->orWhere('kode_alat', 'like', "%{$search}%");
            });
        }

        if ($kategoriFilter !== '') {
            $query->where('kategori', $kategoriFilter);
        }

        if ($kondisiFilter !== '') {
            $query->where('kondisi', $kondisiFilter);
        }

        $query->orderBy('kategori', 'asc')->orderBy('kode_alat', 'asc');
        $alatList = $query->get();

        // Agregasi stok yang sedang dialokasikan / disewa per kode_alat (status Siap Diambil, Sedang Disewa, Berjalan)
        $dialokasikanMap = DetailPenyewaan::whereHas('penyewaan', function ($q) {
            $q->whereIn('status_penyewaan', ['Siap Diambil', 'Sedang Disewa', 'Berjalan']);
        })
            ->selectRaw('kode_alat, SUM(CASE WHEN jumlah_diserahkan > 0 THEN jumlah_diserahkan ELSE jumlah END) as total_alokasi')
            ->groupBy('kode_alat')
            ->pluck('total_alokasi', 'kode_alat');

        // Kalkulasi metrik inventaris per alat
        $inventarisData = $alatList->map(function ($alat) use ($dialokasikanMap) {
            $dialokasikan = (int) ($dialokasikanMap[$alat->kode_alat] ?? 0);
            $stokToko = (int) $alat->stok;
            $stokFisikTotal = $stokToko + $dialokasikan;

            // Aturan Bisnis: Alat rusak / perlu perbaikan TIDAK dihitung sebagai stok siap sewa
            $stokSiapSewa = ($alat->kondisi === 'Baik') ? $stokToko : 0;
            $stokRusak = ($alat->kondisi !== 'Baik') ? $stokToko : 0;

            return (object) [
                'alat' => $alat,
                'kode_alat' => $alat->kode_alat,
                'nama_alat' => $alat->nama_alat,
                'kategori' => $alat->kategori,
                'kondisi' => $alat->kondisi,
                'tarif_sewa' => $alat->tarif_sewa,
                'stok_toko' => $stokToko,
                'stok_dialokasikan' => $dialokasikan,
                'stok_fisik_total' => $stokFisikTotal,
                'stok_siap_sewa' => $stokSiapSewa,
                'stok_rusak' => $stokRusak,
            ];
        });

        // Filter opsi status stok (misal: hanya stok menipis / kritis)
        $statusStok = $request->query('status_stok');
        if ($statusStok === 'siap_sewa') {
            $inventarisData = $inventarisData->filter(fn ($item) => $item->stok_siap_sewa > 0);
        } elseif ($statusStok === 'habis') {
            $inventarisData = $inventarisData->filter(fn ($item) => $item->stok_siap_sewa <= 0);
        } elseif ($statusStok === 'dialokasikan') {
            $inventarisData = $inventarisData->filter(fn ($item) => $item->stok_dialokasikan > 0);
        } elseif ($statusStok === 'bermasalah') {
            $inventarisData = $inventarisData->filter(fn ($item) => $item->kondisi !== 'Baik' || $item->stok_rusak > 0);
        }

        // Ringkasan metrik inventaris menyeluruh untuk widget kartu
        $totalFisikSemua = $inventarisData->sum('stok_fisik_total');
        $totalSiapSewaSemua = $inventarisData->sum('stok_siap_sewa');
        $totalDialokasikanSemua = $inventarisData->sum('stok_dialokasikan');
        $totalBermasalahSemua = $inventarisData->sum('stok_rusak');

        $kategoriList = Alat::distinct()->orderBy('kategori')->pluck('kategori');

        return view('admin.inventaris.index', [
            'inventarisData' => $inventarisData,
            'kategoriList' => $kategoriList,
            'search' => $search,
            'kategoriFilter' => $kategoriFilter,
            'kondisiFilter' => $kondisiFilter,
            'statusStok' => $statusStok,
            'totalFisikSemua' => $totalFisikSemua,
            'totalSiapSewaSemua' => $totalSiapSewaSemua,
            'totalDialokasikanSemua' => $totalDialokasikanSemua,
            'totalBermasalahSemua' => $totalBermasalahSemua,
        ]);
    }

    /**
     * Halaman detail inventaris spesifik alat:
     * Menampilkan metrik stok, daftar transaksi yang sedang menggunakan unit alat,
     * serta formulir penyesuaian stok manual.
     */
    public function show(string $kode_alat): View
    {
        $alat = Alat::where('kode_alat', $kode_alat)->firstOrFail();

        // Cari pesanan aktif yang sedang mengalokasikan alat ini
        $alokasiAktif = DetailPenyewaan::with(['penyewaan.pelanggan'])
            ->where('kode_alat', $kode_alat)
            ->whereHas('penyewaan', function ($q) {
                $q->whereIn('status_penyewaan', ['Siap Diambil', 'Sedang Disewa', 'Berjalan']);
            })
            ->get();

        $jumlahDialokasikan = (int) $alokasiAktif->sum(function ($detail) {
            return $detail->jumlah_diserahkan > 0 ? $detail->jumlah_diserahkan : $detail->jumlah;
        });

        $stokToko = (int) $alat->stok;
        $stokFisikTotal = $stokToko + $jumlahDialokasikan;
        $stokSiapSewa = ($alat->kondisi === 'Baik') ? $stokToko : 0;
        $stokRusak = ($alat->kondisi !== 'Baik') ? $stokToko : 0;

        return view('admin.inventaris.show', [
            'alat' => $alat,
            'stokToko' => $stokToko,
            'stokDialokasikan' => $jumlahDialokasikan,
            'stokFisikTotal' => $stokFisikTotal,
            'stokSiapSewa' => $stokSiapSewa,
            'stokRusak' => $stokRusak,
            'alokasiAktif' => $alokasiAktif,
        ]);
    }

    /**
     * Catat penyesuaian stok manual (tambah / kurang) dengan alasan wajib
     */
    public function adjustStock(Request $request, string $kode_alat): RedirectResponse
    {
        return redirect()->route('admin.inventaris.show', $kode_alat)
            ->with('info', 'Fungsi penyesuaian stok disiapkan.');
    }
}

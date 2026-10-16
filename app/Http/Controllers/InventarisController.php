<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\DetailPenyewaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        $request->validate([
            'tipe_penyesuaian' => ['required', 'in:tambah,kurang'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'alasan' => ['required', 'string', 'max:255'],
        ], [
            'tipe_penyesuaian.required' => 'Pilih jenis penyesuaian stok (Tambah atau Kurang).',
            'jumlah.required' => 'Jumlah unit penyesuaian wajib diisi.',
            'jumlah.min' => 'Jumlah unit penyesuaian minimal 1.',
            'alasan.required' => 'Alasan penyesuaian stok wajib diisi demi integritas audit stok.',
        ]);

        $tipe = $request->input('tipe_penyesuaian');
        $jumlah = (int) $request->input('jumlah');
        $alasan = trim((string) $request->input('alasan'));

        return DB::transaction(function () use ($kode_alat, $tipe, $jumlah, $alasan) {
            $alat = Alat::where('kode_alat', $kode_alat)->lockForUpdate()->firstOrFail();

            $stokSebelum = (int) $alat->stok;
            $delta = ($tipe === 'tambah') ? $jumlah : -$jumlah;
            $stokSesudah = $stokSebelum + $delta;

            // Cegah stok negatif
            if ($stokSesudah < 0) {
                return back()
                    ->withErrors(['jumlah' => "Penyesuaian stok ditolak karena akan menyebabkan stok fisik di toko menjadi negatif ({$stokSesudah})."])
                    ->withInput();
            }

            // Perbarui stok fisik alat
            $alat->stok = $stokSesudah;
            $alat->save();

            // Catat ke Audit Trail ActivityLog (Waktu dan pengguna tercatat)
            $actionName = ($tipe === 'tambah') ? 'Penambahan Stok Manual' : 'Pengurangan Stok Manual';
            ActivityLog::record(
                Auth::id(),
                $actionName,
                'Inventaris',
                "Kode Alat: {$alat->kode_alat}, Perubahan: ".($delta > 0 ? "+{$delta}" : (string) $delta)." unit, Stok: {$stokSebelum} -> {$stokSesudah}, Alasan: {$alasan}"
            );

            // Jika tabel riwayat_stok sudah ada, catat juga ke log inventaris
            if (Schema::hasTable('riwayat_stok')) {
                DB::table('riwayat_stok')->insert([
                    'kode_alat' => $alat->kode_alat,
                    'jenis_perubahan' => ($tipe === 'tambah') ? 'Penambahan Manual' : 'Pengurangan Manual',
                    'stok_sebelum' => $stokSebelum,
                    'jumlah_perubahan' => $delta,
                    'stok_sesudah' => $stokSesudah,
                    'alasan' => $alasan,
                    'id_user' => Auth::id(),
                    'created_at' => now(),
                ]);
            }

            return redirect()->route('admin.inventaris.show', $alat->kode_alat)
                ->with('success', "Stok alat {$alat->nama_alat} ({$alat->kode_alat}) berhasil diperbarui: {$stokSebelum} unit &rarr; {$stokSesudah} unit.");
        });
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\RiwayatStok;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // Agregasi unit rusak & perbaikan dari pengembalian sewa per kode_alat (memperhitungkan unit yang sudah diperbaiki)
        $kondisiReturnMap = DetailPenyewaan::selectRaw('kode_alat, SUM(jumlah_rusak) as total_rusak, SUM(jumlah_perlu_perbaikan - COALESCE(jumlah_diperbaiki, 0)) as total_perbaikan')
            ->groupBy('kode_alat')
            ->get()
            ->keyBy('kode_alat');

        // Kalkulasi metrik inventaris per alat
        $inventarisData = $alatList->map(function ($alat) use ($dialokasikanMap, $kondisiReturnMap) {
            $dialokasikan = (int) ($dialokasikanMap[$alat->kode_alat] ?? 0);
            $rusakReturn = (int) ($kondisiReturnMap[$alat->kode_alat]->total_rusak ?? 0);
            $perbaikanReturn = (int) ($kondisiReturnMap[$alat->kode_alat]->total_perbaikan ?? 0);
            $stokToko = (int) $alat->stok;

            // Aturan Bisnis: Alat rusak / perlu perbaikan TIDAK dihitung sebagai stok siap sewa
            $stokSiapSewa = ($alat->kondisi === 'Baik') ? $stokToko : 0;
            $stokPerluPerbaikan = (($alat->kondisi === 'Perlu Perbaikan') ? $stokToko : 0) + $perbaikanReturn;
            $stokRusak = (($alat->kondisi === 'Rusak') ? $stokToko : 0) + $rusakReturn;
            $stokFisikTotal = $stokSiapSewa + $dialokasikan + $stokPerluPerbaikan + $stokRusak;

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
                'stok_perlu_perbaikan' => $stokPerluPerbaikan,
                'stok_rusak' => $stokRusak,
                'stok_bermasalah' => $stokPerluPerbaikan + $stokRusak,
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
            $inventarisData = $inventarisData->filter(fn ($item) => $item->kondisi !== 'Baik' || $item->stok_bermasalah > 0);
        }

        // Ringkasan metrik inventaris menyeluruh untuk widget kartu (Konsisten dengan model Alat)
        $ringkasan = Alat::getRingkasanInventaris();
        $totalFisikSemua = $ringkasan['total_stok_fisik'];
        $totalSiapSewaSemua = $ringkasan['stok_siap_sewa'];
        $totalDialokasikanSemua = $ringkasan['stok_sedang_disewa'];
        $totalPerluPerbaikanSemua = $ringkasan['stok_perlu_perbaikan'];
        $totalRusakSemua = $ringkasan['stok_rusak'];
        $totalBermasalahSemua = $totalPerluPerbaikanSemua + $totalRusakSemua;

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
            'totalPerluPerbaikanSemua' => $totalPerluPerbaikanSemua,
            'totalRusakSemua' => $totalRusakSemua,
            'totalBermasalahSemua' => $totalBermasalahSemua,
        ]);
    }

    /**
     * Halaman detail inventaris spesifik alat:
     * Menampilkan metrik stok, daftar transaksi yang sedang menggunakan unit alat,
     * unit yang menunggu perbaikan, serta formulir penyesuaian stok manual.
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

        $rusakReturn = (int) DetailPenyewaan::where('kode_alat', $kode_alat)->sum('jumlah_rusak');
        $perbaikanReturn = (int) DetailPenyewaan::where('kode_alat', $kode_alat)
            ->selectRaw('COALESCE(SUM(jumlah_perlu_perbaikan - COALESCE(jumlah_diperbaiki, 0)), 0) as total')
            ->value('total');

        $stokToko = (int) $alat->stok;
        $stokSiapSewa = ($alat->kondisi === 'Baik') ? $stokToko : 0;
        $stokPerluPerbaikan = (($alat->kondisi === 'Perlu Perbaikan') ? $stokToko : 0) + $perbaikanReturn;
        $stokRusak = (($alat->kondisi === 'Rusak') ? $stokToko : 0) + $rusakReturn;
        $stokFisikTotal = $stokSiapSewa + $jumlahDialokasikan + $stokPerluPerbaikan + $stokRusak;

        // Ambil transaksi yang memiliki unit menunggu perbaikan untuk alat ini
        $unitPerbaikanList = DetailPenyewaan::with(['penyewaan.pelanggan', 'penyewaan.pengembalian'])
            ->where('kode_alat', $kode_alat)
            ->whereRaw('jumlah_perlu_perbaikan > COALESCE(jumlah_diperbaiki, 0)')
            ->get();

        // Ambil riwayat perubahan stok khusus alat ini
        $riwayatStok = $alat->riwayatStok()->with('user')->take(30)->get();

        return view('admin.inventaris.show', [
            'alat' => $alat,
            'stokToko' => $stokToko,
            'stokDialokasikan' => $jumlahDialokasikan,
            'stokFisikTotal' => $stokFisikTotal,
            'stokSiapSewa' => $stokSiapSewa,
            'stokPerluPerbaikan' => $stokPerluPerbaikan,
            'stokRusak' => $stokRusak,
            'stokBermasalah' => $stokPerluPerbaikan + $stokRusak,
            'unitPerbaikanList' => $unitPerbaikanList,
            'alokasiAktif' => $alokasiAktif,
            'riwayatStok' => $riwayatStok,
        ]);
    }

    /**
     * Konfirmasi perbaikan alat oleh petugas yang berwenang:
     * Mengembalikan unit yang selesai diperbaiki ke stok siap sewa (Kondisi Baik).
     */
    public function konfirmasiPerbaikan(Request $request, string $kode_alat): RedirectResponse
    {
        $request->validate([
            'id_detail' => ['nullable', 'integer', 'exists:detail_penyewaan,id_detail'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'jumlah.required' => 'Jumlah unit yang selesai diperbaiki wajib diisi.',
            'jumlah.min' => 'Jumlah unit selesai perbaikan minimal 1.',
        ]);

        $idDetail = $request->input('id_detail');
        $jumlah = (int) $request->input('jumlah');
        $catatan = trim((string) $request->input('catatan'));

        return DB::transaction(function () use ($kode_alat, $idDetail, $jumlah, $catatan) {
            $alat = Alat::where('kode_alat', $kode_alat)->lockForUpdate()->firstOrFail();

            if ($idDetail) {
                $detail = DetailPenyewaan::where('id_detail', $idDetail)
                    ->where('kode_alat', $kode_alat)
                    ->lockForUpdate()
                    ->firstOrFail();

                $pending = max(0, (int) $detail->jumlah_perlu_perbaikan - (int) ($detail->jumlah_diperbaiki ?? 0));
                if ($pending < $jumlah) {
                    return back()->with('error', "Jumlah unit yang dikonfirmasi ({$jumlah}) melebihi unit yang membutuhkan perbaikan ({$pending}).");
                }

                $detail->jumlah_diperbaiki = (int) ($detail->jumlah_diperbaiki ?? 0) + $jumlah;
                $detail->save();

                $stokSebelum = (int) $alat->stok;
                $alat->stok = $stokSebelum + $jumlah;
                $alat->save();

                $keterangan = "Perbaikan {$jumlah} unit dari transaksi {$detail->no_transaksi} selesai dikonfirmasi oleh ".Auth::user()->nama;
                if ($catatan !== '') {
                    $keterangan .= ". Catatan: {$catatan}";
                }

                RiwayatStok::recordChange(
                    $alat->kode_alat,
                    'Selesai Perbaikan',
                    $stokSebelum,
                    +$jumlah,
                    $alat->stok,
                    $keterangan,
                    Auth::id()
                );

                ActivityLog::record(
                    Auth::id(),
                    'Konfirmasi Perbaikan Alat',
                    'Inventaris',
                    "Alat {$alat->kode_alat} sebanyak {$jumlah} unit selesai diperbaiki dari transaksi {$detail->no_transaksi}"
                );

                return redirect()->route('admin.inventaris.show', $alat->kode_alat)
                    ->with('success', "Konfirmasi berhasil! {$jumlah} unit {$alat->nama_alat} telah selesai diperbaiki dan kembali tersedia di stok siap sewa.");
            } else {
                if ($alat->kondisi !== 'Perlu Perbaikan') {
                    return back()->with('error', "Alat {$alat->kode_alat} tidak dalam status Perlu Perbaikan.");
                }

                $alat->kondisi = 'Baik';
                $alat->save();

                $keterangan = "Konfirmasi kondisi master alat {$alat->kode_alat} selesai diperbaiki menjadi Baik oleh ".Auth::user()->nama;
                if ($catatan !== '') {
                    $keterangan .= ". Catatan: {$catatan}";
                }

                ActivityLog::record(
                    Auth::id(),
                    'Konfirmasi Perbaikan Alat',
                    'Alat',
                    $keterangan
                );

                return redirect()->route('admin.inventaris.show', $alat->kode_alat)
                    ->with('success', "Kondisi alat {$alat->nama_alat} ({$alat->kode_alat}) berhasil dikonfirmasi kembali menjadi Baik dan siap disewa.");
            }
        });
    }

    /**
     * Halaman riwayat perubahan stok global untuk seluruh alat
     */
    public function riwayatIndex(Request $request): View
    {
        $query = RiwayatStok::with(['alat', 'user']);

        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($sub) use ($search) {
                $sub->where('kode_alat', 'like', "%{$search}%")
                    ->orWhere('alasan', 'like', "%{$search}%")
                    ->orWhereHas('alat', function ($a) use ($search) {
                        $a->where('nama_alat', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('kode_alat')) {
            $query->where('kode_alat', $request->kode_alat);
        }

        if ($request->filled('jenis')) {
            $query->where('jenis_perubahan', $request->jenis);
        }

        $riwayatList = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $alatList = Alat::orderBy('nama_alat')->get();

        return view('admin.inventaris.riwayat', [
            'riwayatList' => $riwayatList,
            'alatList' => $alatList,
            'search' => $request->query('q', ''),
            'selectedAlat' => $request->query('kode_alat', ''),
            'selectedJenis' => $request->query('jenis', ''),
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

            $jenisPerubahan = ($tipe === 'tambah') ? 'Penambahan Manual' : 'Pengurangan Manual';

            // Catat ke Audit Trail ActivityLog (Waktu dan pengguna tercatat)
            ActivityLog::record(
                Auth::id(),
                $jenisPerubahan,
                'Inventaris',
                "Kode Alat: {$alat->kode_alat}, Perubahan: ".($delta > 0 ? "+{$delta}" : (string) $delta)." unit, Stok: {$stokSebelum} -> {$stokSesudah}, Alasan: {$alasan}"
            );

            // Catat ke Riwayat Perubahan Stok Inventaris
            RiwayatStok::recordChange(
                $alat->kode_alat,
                $jenisPerubahan,
                $stokSebelum,
                $delta,
                $stokSesudah,
                $alasan,
                Auth::id()
            );

            return redirect()->route('admin.inventaris.show', $alat->kode_alat)
                ->with('success', "Stok alat {$alat->nama_alat} ({$alat->kode_alat}) berhasil diperbarui: {$stokSebelum} unit &rarr; {$stokSesudah} unit.");
        });
    }
}

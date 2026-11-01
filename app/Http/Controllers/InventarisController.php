<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\PenangananKerusakan;
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

        // Dapatkan data inventaris lengkap menggunakan metode terpadu dari Model Alat
        $inventarisData = Alat::getDaftarInventarisLengkap(
            $search !== '' ? $search : null,
            $kategoriFilter !== '' ? $kategoriFilter : null,
            $kondisiFilter !== '' ? $kondisiFilter : null
        );

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
        $totalSedangDisewaSemua = $ringkasan['stok_sedang_disewa'];
        $totalDialokasikanSemua = $ringkasan['stok_dialokasikan'];
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
            'totalSedangDisewaSemua' => $totalSedangDisewaSemua,
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

        // 1. Pesanan aktif yang unitnya sedang berada di tangan pelanggan (Sedang Disewa / Berjalan)
        $sedangDisewaAktif = DetailPenyewaan::with(['penyewaan.pelanggan'])
            ->where('kode_alat', $kode_alat)
            ->whereHas('penyewaan', function ($q) {
                $q->whereIn('status_penyewaan', ['Sedang Disewa', 'Berjalan']);
            })
            ->get();

        $stokSedangDisewa = (int) $sedangDisewaAktif->sum(function ($detail) {
            return $detail->jumlah_sisa_kembali;
        });

        // 2. Pesanan aktif yang dialokasikan di toko namun belum diambil pelanggan
        $dialokasikanAktif = DetailPenyewaan::with(['penyewaan.pelanggan'])
            ->where('kode_alat', $kode_alat)
            ->whereHas('penyewaan', function ($q) {
                $q->whereIn('status_penyewaan', ['Menunggu Konfirmasi', 'Menunggu Pembayaran', 'Disetujui', 'Siap Diambil']);
            })
            ->get();

        $stokDialokasikan = (int) $dialokasikanAktif->sum('jumlah');

        $rusakReturn = (int) DetailPenyewaan::where('kode_alat', $kode_alat)->sum('jumlah_rusak');
        $perbaikanReturn = (int) DetailPenyewaan::where('kode_alat', $kode_alat)
            ->selectRaw('COALESCE(SUM(CASE WHEN (jumlah_perlu_perbaikan - COALESCE(jumlah_diperbaiki, 0)) > 0 THEN (jumlah_perlu_perbaikan - COALESCE(jumlah_diperbaiki, 0)) ELSE 0 END), 0) as total')
            ->value('total');

        $stokToko = (int) $alat->stok;
        $stokSiapSewa = ($alat->kondisi === 'Baik') ? $stokToko : 0;
        $stokPerluPerbaikan = (($alat->kondisi === 'Perlu Perbaikan') ? $stokToko : 0) + $perbaikanReturn;
        $stokRusak = (($alat->kondisi === 'Rusak') ? $stokToko : 0) + $rusakReturn;
        $stokFisikTotal = $stokSiapSewa + $stokSedangDisewa + $stokPerluPerbaikan + $stokRusak;

        // Ambil transaksi yang memiliki unit menunggu perbaikan untuk alat ini
        $unitPerbaikanList = DetailPenyewaan::with(['penyewaan.pelanggan', 'penyewaan.pengembalian'])
            ->where('kode_alat', $kode_alat)
            ->whereRaw('jumlah_perlu_perbaikan > COALESCE(jumlah_diperbaiki, 0)')
            ->get();

        // Ambil riwayat perubahan stok khusus alat ini
        $riwayatStok = $alat->riwayatStok()->with('user')->take(30)->get();

        // Ambil riwayat penanganan kerusakan & biaya perbaikan alat ini
        $riwayatPenanganan = PenangananKerusakan::with(['admin', 'detailPenyewaan.penyewaan.pelanggan'])
            ->where('kode_alat', $kode_alat)
            ->orderByDesc('tanggal_tindakan')
            ->orderByDesc('id_penanganan')
            ->get();

        return view('admin.inventaris.show', [
            'alat' => $alat,
            'stokToko' => $stokToko,
            'stokSedangDisewa' => $stokSedangDisewa,
            'stokDialokasikan' => $stokDialokasikan,
            'stokFisikTotal' => $stokFisikTotal,
            'stokSiapSewa' => $stokSiapSewa,
            'stokPerluPerbaikan' => $stokPerluPerbaikan,
            'stokRusak' => $stokRusak,
            'stokBermasalah' => $stokPerluPerbaikan + $stokRusak,
            'unitPerbaikanList' => $unitPerbaikanList,
            'sedangDisewaAktif' => $sedangDisewaAktif,
            'dialokasikanAktif' => $dialokasikanAktif,
            'alokasiAktif' => $sedangDisewaAktif,
            'riwayatStok' => $riwayatStok,
            'riwayatPenanganan' => $riwayatPenanganan,
        ]);
    }

    /**
     * Tindakan penanganan unit bermasalah / rusak:
     * - Menunggu perbaikan
     * - Catat biaya perbaikan
     * - Selesai perbaikan & kembalikan ke stok siap sewa
     * - Afkir / pensiunkan unit rusak
     */
    public function tindakKerusakan(Request $request, string $kode_alat): RedirectResponse
    {
        $request->validate([
            'jenis_tindakan' => ['required', 'in:Menunggu Perbaikan,Catat Biaya Perbaikan,Selesai Perbaikan,Afkir/Pensiun'],
            'id_detail' => ['nullable', 'integer', 'exists:detail_penyewaan,id_detail'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'biaya_perbaikan' => ['nullable', 'numeric', 'min:0'],
            'tanggal_tindakan' => ['required', 'date'],
            'pelaksana_teknisi' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ], [
            'jenis_tindakan.required' => 'Pilih jenis tindakan penanganan.',
            'jenis_tindakan.in' => 'Jenis tindakan penanganan tidak valid.',
            'jumlah.required' => 'Jumlah unit wajib diisi.',
            'jumlah.min' => 'Jumlah unit minimal 1.',
            'tanggal_tindakan.required' => 'Tanggal tindakan penanganan wajib diisi.',
        ]);

        $jenisTindakan = $request->input('jenis_tindakan');
        $idDetail = $request->input('id_detail');
        $jumlah = (int) $request->input('jumlah');
        $biayaPerbaikan = (float) ($request->input('biaya_perbaikan') ?? 0);
        $tanggalTindakan = $request->input('tanggal_tindakan');
        $teknisi = trim((string) $request->input('pelaksana_teknisi', ''));
        $keterangan = trim((string) $request->input('keterangan', ''));

        return DB::transaction(function () use ($kode_alat, $jenisTindakan, $idDetail, $jumlah, $biayaPerbaikan, $tanggalTindakan, $teknisi, $keterangan) {
            $alat = Alat::where('kode_alat', $kode_alat)->lockForUpdate()->firstOrFail();
            $admin = Auth::user();

            $detail = null;
            if ($idDetail) {
                $detail = DetailPenyewaan::where('id_detail', $idDetail)
                    ->where('kode_alat', $kode_alat)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            if ($jenisTindakan === 'Selesai Perbaikan') {
                if ($detail) {
                    $pending = max(0, (int) $detail->jumlah_perlu_perbaikan - (int) ($detail->jumlah_diperbaiki ?? 0));
                    if ($pending < $jumlah) {
                        return back()->with('error', "Jumlah unit selesai perbaikan ({$jumlah}) melebihi unit yang butuh servis ({$pending}).")->withInput();
                    }
                    $detail->jumlah_diperbaiki = (int) ($detail->jumlah_diperbaiki ?? 0) + $jumlah;
                    $detail->save();

                    $stokSebelum = (int) $alat->stok;
                    $alat->stok = $stokSebelum + $jumlah;
                    $alat->save();

                    RiwayatStok::recordChange(
                        $alat->kode_alat,
                        'Selesai Perbaikan',
                        $stokSebelum,
                        +$jumlah,
                        $alat->stok,
                        "Selesai servis {$jumlah} unit dari sewa {$detail->no_transaksi}. Teknisi: ".($teknisi ?: 'Internal').($keterangan ? ". Ket: {$keterangan}" : ''),
                        Auth::id()
                    );
                } else {
                    if ($alat->kondisi === 'Perlu Perbaikan') {
                        $alat->kondisi = 'Baik';
                        $alat->save();
                    }
                }
            } elseif ($jenisTindakan === 'Afkir/Pensiun') {
                if ($alat->kondisi === 'Rusak' && $alat->stok >= $jumlah) {
                    $stokSebelum = (int) $alat->stok;
                    $alat->stok = max(0, $stokSebelum - $jumlah);
                    $alat->save();

                    RiwayatStok::recordChange(
                        $alat->kode_alat,
                        'Afkir Unit Rusak',
                        $stokSebelum,
                        -$jumlah,
                        $alat->stok,
                        "Pensiun/afkir {$jumlah} unit rusak permanen. ".($keterangan ?: 'Tidak dapat digunakan lagi'),
                        Auth::id()
                    );
                }
            }

            // Catat ke tabel penanganan_kerusakan
            PenangananKerusakan::create([
                'kode_alat' => $alat->kode_alat,
                'id_detail' => $idDetail ?: null,
                'jenis_tindakan' => $jenisTindakan,
                'jumlah_unit' => $jumlah,
                'biaya_perbaikan' => $biayaPerbaikan,
                'tanggal_tindakan' => $tanggalTindakan,
                'pelaksana_teknisi' => $teknisi ?: null,
                'keterangan' => $keterangan ?: null,
                'id_admin' => Auth::id() ?? 1,
                'created_at' => now(),
            ]);

            // Audit trail activity log
            ActivityLog::record(
                Auth::id(),
                'Penanganan Kerusakan Alat',
                'Inventaris',
                "Tindakan {$jenisTindakan} untuk {$alat->kode_alat} ({$jumlah} unit). Biaya: Rp ".number_format($biayaPerbaikan, 0, ',', '.').' oleh '.$admin->nama
            );

            return redirect()->route('admin.inventaris.show', $alat->kode_alat)
                ->with('success', "Tindakan '{$jenisTindakan}' untuk {$alat->nama_alat} berhasil disimpan.");
        });
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
            'biaya_perbaikan' => ['nullable', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'jumlah.required' => 'Jumlah unit yang selesai diperbaiki wajib diisi.',
            'jumlah.min' => 'Jumlah unit selesai perbaikan minimal 1.',
        ]);

        $idDetail = $request->input('id_detail');
        $jumlah = (int) $request->input('jumlah');
        $biayaPerbaikan = (float) ($request->input('biaya_perbaikan') ?? 0);
        $catatan = trim((string) $request->input('catatan'));

        return DB::transaction(function () use ($kode_alat, $idDetail, $jumlah, $biayaPerbaikan, $catatan) {
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

                PenangananKerusakan::create([
                    'kode_alat' => $alat->kode_alat,
                    'id_detail' => $detail->id_detail,
                    'jenis_tindakan' => 'Selesai Perbaikan',
                    'jumlah_unit' => $jumlah,
                    'biaya_perbaikan' => $biayaPerbaikan,
                    'tanggal_tindakan' => now()->toDateString(),
                    'pelaksana_teknisi' => Auth::user()->nama,
                    'keterangan' => $keterangan,
                    'id_admin' => Auth::id() ?? 1,
                    'created_at' => now(),
                ]);

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

                PenangananKerusakan::create([
                    'kode_alat' => $alat->kode_alat,
                    'id_detail' => null,
                    'jenis_tindakan' => 'Selesai Perbaikan',
                    'jumlah_unit' => $jumlah,
                    'biaya_perbaikan' => $biayaPerbaikan,
                    'tanggal_tindakan' => now()->toDateString(),
                    'pelaksana_teknisi' => Auth::user()->nama,
                    'keterangan' => $keterangan,
                    'id_admin' => Auth::id() ?? 1,
                    'created_at' => now(),
                ]);

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

<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\PembelianInventaris;
use App\Models\Penyewaan;
use App\Models\RiwayatStok;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    /**
     * Tampilkan Laporan Transaksi Penyewaan & Pembayaran (Admin & Pemilik)
     */
    public function transaksi(Request $request): View
    {
        $query = Penyewaan::query()
            ->with([
                'pelanggan',
                'pembayaran.admin',
                'pengambilan.admin',
                'pengembalian.admin',
                'pengembalian.denda',
                'detailPenyewaan.alat',
            ]);

        // 1. Filter Pencarian Kata Kunci
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('penyewaan.no_transaksi', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($sub) use ($search) {
                        $sub->where('nama_pelanggan', 'like', "%{$search}%")
                            ->orWhere('nomor_telepon', 'like', "%{$search}%");
                    });
            });
        }

        // 2. Filter Rentang Tanggal Sewa
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_sewa', '>=', $request->input('tanggal_mulai'));
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_sewa', '<=', $request->input('tanggal_selesai'));
        }

        // 3. Filter Status Penyewaan
        if ($request->filled('status_penyewaan')) {
            $statusSewa = $request->input('status_penyewaan');
            if ($statusSewa === 'Berjalan') {
                $query->whereIn('status_penyewaan', ['Berjalan', 'Sedang Disewa']);
            } else {
                $query->where('status_penyewaan', $statusSewa);
            }
        }

        // 4. Filter Status Pembayaran
        if ($request->filled('status_pembayaran')) {
            $statusBayar = $request->input('status_pembayaran');
            if ($statusBayar === 'Belum Dibayar') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('pembayaran')
                        ->orWhereHas('pembayaran', function ($sub) {
                            $sub->where('status_pembayaran', 'Belum Dibayar')
                                ->orWhere('status_pembayaran', 'Belum Lunas')
                                ->orWhereNull('status_pembayaran');
                        });
                });
            } else {
                $query->whereHas('pembayaran', function ($q) use ($statusBayar) {
                    $q->where('status_pembayaran', $statusBayar);
                });
            }
        }

        // 5. Filter Sumber Pesanan
        if ($request->filled('sumber_pesanan')) {
            $query->where('sumber_pesanan', $request->input('sumber_pesanan'));
        }

        // 6. Pengurutan Dinamis (Sortable Columns)
        $sortBy = $request->query('sort_by', 'tanggal_sewa');
        $order = strtolower($request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['tanggal_sewa', 'no_transaksi', 'total_biaya', 'status_penyewaan', 'status_pembayaran', 'tanggal_kembali'];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'tanggal_sewa';
        }

        if ($sortBy === 'status_pembayaran') {
            $query->leftJoin('pembayaran', 'penyewaan.no_transaksi', '=', 'pembayaran.no_transaksi')
                ->select('penyewaan.*')
                ->orderBy('pembayaran.status_pembayaran', $order);
        } else {
            $query->orderBy($sortBy, $order);
        }

        // 7. Rekapitulasi Menyeluruh Seluruh Data yang Sesuai Filter
        $allFiltered = (clone $query)->get();
        $rekapJumlahTransaksi = $allFiltered->count();
        $rekapTotalTagihan = (float) $allFiltered->sum(function ($t) {
            $tagihan = (float) $t->total_biaya;
            if ($t->pengembalian?->denda) {
                $tagihan += (float) $t->pengembalian->denda->nominal_denda;
            }

            return $tagihan;
        });

        $rekapTotalKasDiterima = (float) $allFiltered->sum(function ($t) {
            if ($t->pembayaran && $t->pembayaran->isLunas()) {
                return (float) ($t->pembayaran->jumlah_pembayaran > 0 ? $t->pembayaran->jumlah_pembayaran : $t->total_biaya);
            }

            return 0.0;
        });

        $rekapTotalSisaTagihan = max(0.0, $rekapTotalTagihan - $rekapTotalKasDiterima);

        // 8. Paginasi Data
        $transaksiList = $query->paginate(15)->withQueryString();

        $filters = [
            'search' => $request->input('search'),
            'tanggal_mulai' => $request->input('tanggal_mulai'),
            'tanggal_selesai' => $request->input('tanggal_selesai'),
            'status_penyewaan' => $request->input('status_penyewaan'),
            'status_pembayaran' => $request->input('status_pembayaran'),
            'sumber_pesanan' => $request->input('sumber_pesanan'),
            'sort_by' => $sortBy,
            'order' => $order,
        ];

        return view('laporan.transaksi', [
            'transaksiList' => $transaksiList,
            'filters' => $filters,
            'hasActiveFilter' => $request->anyFilled(['search', 'tanggal_mulai', 'tanggal_selesai', 'status_penyewaan', 'status_pembayaran', 'sumber_pesanan']),
            'rekapJumlahTransaksi' => $rekapJumlahTransaksi,
            'rekapTotalTagihan' => $rekapTotalTagihan,
            'rekapTotalKasDiterima' => $rekapTotalKasDiterima,
            'rekapTotalSisaTagihan' => $rekapTotalSisaTagihan,
            'isPemilik' => Auth::user()->isPemilik(),
        ]);
    }

    /**
     * Cetak Lembar Laporan Transaksi Penyewaan & Pembayaran (Print View)
     */
    public function cetakTransaksi(Request $request): View
    {
        $query = Penyewaan::query()
            ->with([
                'pelanggan',
                'pembayaran.admin',
                'pengambilan.admin',
                'pengembalian.admin',
                'pengembalian.denda',
                'detailPenyewaan.alat',
            ]);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('penyewaan.no_transaksi', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($sub) use ($search) {
                        $sub->where('nama_pelanggan', 'like', "%{$search}%")
                            ->orWhere('nomor_telepon', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_sewa', '>=', $request->input('tanggal_mulai'));
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_sewa', '<=', $request->input('tanggal_selesai'));
        }

        if ($request->filled('status_penyewaan')) {
            $statusSewa = $request->input('status_penyewaan');
            if ($statusSewa === 'Berjalan') {
                $query->whereIn('status_penyewaan', ['Berjalan', 'Sedang Disewa']);
            } else {
                $query->where('status_penyewaan', $statusSewa);
            }
        }

        if ($request->filled('status_pembayaran')) {
            $statusBayar = $request->input('status_pembayaran');
            if ($statusBayar === 'Belum Dibayar') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('pembayaran')
                        ->orWhereHas('pembayaran', function ($sub) {
                            $sub->where('status_pembayaran', 'Belum Dibayar')
                                ->orWhere('status_pembayaran', 'Belum Lunas')
                                ->orWhereNull('status_pembayaran');
                        });
                });
            } else {
                $query->whereHas('pembayaran', function ($q) use ($statusBayar) {
                    $q->where('status_pembayaran', $statusBayar);
                });
            }
        }

        if ($request->filled('sumber_pesanan')) {
            $query->where('sumber_pesanan', $request->input('sumber_pesanan'));
        }

        $sortBy = $request->query('sort_by', 'tanggal_sewa');
        $order = strtolower($request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['tanggal_sewa', 'no_transaksi', 'total_biaya', 'status_penyewaan', 'status_pembayaran', 'tanggal_kembali'];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'tanggal_sewa';
        }

        if ($sortBy === 'status_pembayaran') {
            $query->leftJoin('pembayaran', 'penyewaan.no_transaksi', '=', 'pembayaran.no_transaksi')
                ->select('penyewaan.*')
                ->orderBy('pembayaran.status_pembayaran', $order);
        } else {
            $query->orderBy($sortBy, $order);
        }

        $transaksiList = $query->get();

        $rekapJumlahTransaksi = $transaksiList->count();
        $rekapTotalTagihan = (float) $transaksiList->sum(function ($t) {
            $tagihan = (float) $t->total_biaya;
            if ($t->pengembalian?->denda) {
                $tagihan += (float) $t->pengembalian->denda->nominal_denda;
            }

            return $tagihan;
        });

        $rekapTotalKasDiterima = (float) $transaksiList->sum(function ($t) {
            if ($t->pembayaran && $t->pembayaran->isLunas()) {
                return (float) ($t->pembayaran->jumlah_pembayaran > 0 ? $t->pembayaran->jumlah_pembayaran : $t->total_biaya);
            }

            return 0.0;
        });

        $rekapTotalSisaTagihan = max(0.0, $rekapTotalTagihan - $rekapTotalKasDiterima);

        return view('laporan.cetak_transaksi', [
            'transaksiList' => $transaksiList,
            'filters' => $request->all(),
            'rekapJumlahTransaksi' => $rekapJumlahTransaksi,
            'rekapTotalTagihan' => $rekapTotalTagihan,
            'rekapTotalKasDiterima' => $rekapTotalKasDiterima,
            'rekapTotalSisaTagihan' => $rekapTotalSisaTagihan,
            'printedBy' => Auth::user(),
            'printedAt' => Carbon::now('Asia/Jakarta'),
        ]);
    }

    /**
     * Ekspor Data Laporan Transaksi Penyewaan & Pembayaran ke File CSV (Excel Compatible)
     */
    public function exportTransaksi(Request $request): StreamedResponse
    {
        $query = Penyewaan::query()
            ->with([
                'pelanggan',
                'pembayaran.admin',
                'pengambilan.admin',
                'pengembalian.admin',
                'pengembalian.denda',
                'detailPenyewaan.alat',
            ]);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('penyewaan.no_transaksi', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($sub) use ($search) {
                        $sub->where('nama_pelanggan', 'like', "%{$search}%")
                            ->orWhere('nomor_telepon', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_sewa', '>=', $request->input('tanggal_mulai'));
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_sewa', '<=', $request->input('tanggal_selesai'));
        }

        if ($request->filled('status_penyewaan')) {
            $statusSewa = $request->input('status_penyewaan');
            if ($statusSewa === 'Berjalan') {
                $query->whereIn('status_penyewaan', ['Berjalan', 'Sedang Disewa']);
            } else {
                $query->where('status_penyewaan', $statusSewa);
            }
        }

        if ($request->filled('status_pembayaran')) {
            $statusBayar = $request->input('status_pembayaran');
            if ($statusBayar === 'Belum Dibayar') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('pembayaran')
                        ->orWhereHas('pembayaran', function ($sub) {
                            $sub->where('status_pembayaran', 'Belum Dibayar')
                                ->orWhere('status_pembayaran', 'Belum Lunas')
                                ->orWhereNull('status_pembayaran');
                        });
                });
            } else {
                $query->whereHas('pembayaran', function ($q) use ($statusBayar) {
                    $q->where('status_pembayaran', $statusBayar);
                });
            }
        }

        if ($request->filled('sumber_pesanan')) {
            $query->where('sumber_pesanan', $request->input('sumber_pesanan'));
        }

        $sortBy = $request->query('sort_by', 'tanggal_sewa');
        $order = strtolower($request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['tanggal_sewa', 'no_transaksi', 'total_biaya', 'status_penyewaan', 'status_pembayaran', 'tanggal_kembali'];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'tanggal_sewa';
        }

        if ($sortBy === 'status_pembayaran') {
            $query->leftJoin('pembayaran', 'penyewaan.no_transaksi', '=', 'pembayaran.no_transaksi')
                ->select('penyewaan.*')
                ->orderBy('pembayaran.status_pembayaran', $order);
        } else {
            $query->orderBy($sortBy, $order);
        }

        $transaksiList = $query->get();

        $filename = 'laporan_transaksi_adventure_camp_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($transaksiList, $request) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM untuk kompatibilitas otomatis Microsoft Excel
            fwrite($file, "\xEF\xBB\xBF");

            // Header Dokumen
            fputcsv($file, ['ADVENTURE CAMP RENTAL — SISTEM INFORMASI PENYEWAAN ALAT CAMPING']);
            fputcsv($file, ['LAPORAN TRANSAKSI PENYEWAAN DAN PEMBAYARAN']);
            fputcsv($file, [
                'Periode Filter:',
                ($request->input('tanggal_mulai') ?? 'Awal').' s.d. '.($request->input('tanggal_selesai') ?? 'Sekarang'),
                'Tanggal Cetak:',
                Carbon::now('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB',
                'Dicetak Oleh:',
                Auth::user()->nama_user.' ('.Auth::user()->normalized_role.')',
            ]);
            fputcsv($file, []);

            // Baris Judul Kolom
            fputcsv($file, [
                'No',
                'No. Transaksi',
                'Sumber Pesanan',
                'Tanggal Sewa',
                'Tanggal Kembali',
                'Durasi (Hari)',
                'Nama Pelanggan',
                'No. Telepon',
                'Petugas Pemroses',
                'Total Tagihan (Rp)',
                'Jumlah Dibayar (Rp)',
                'Sisa Tagihan (Rp)',
                'Metode Pembayaran',
                'Status Pembayaran',
                'Status Penyewaan',
            ]);

            $no = 1;
            $totalTagihanSum = 0;
            $totalKasSum = 0;

            foreach ($transaksiList as $t) {
                $tagihan = (float) $t->total_biaya;
                if ($t->pengembalian?->denda) {
                    $tagihan += (float) $t->pengembalian->denda->nominal_denda;
                }

                $isLunas = $t->pembayaran && $t->pembayaran->isLunas();
                $dibayar = $isLunas ? (float) ($t->pembayaran->jumlah_pembayaran > 0 ? $t->pembayaran->jumlah_pembayaran : $tagihan) : 0.0;
                $sisa = max(0.0, $tagihan - $dibayar);

                $petugas = $t->petugas_pemroses;

                $totalTagihanSum += $tagihan;
                $totalKasSum += $dibayar;

                fputcsv($file, [
                    $no++,
                    $t->no_transaksi,
                    strtoupper($t->sumber_pesanan ?? 'online'),
                    $t->tanggal_sewa ? Carbon::parse($t->tanggal_sewa)->format('d/m/Y') : '-',
                    $t->tanggal_kembali ? Carbon::parse($t->tanggal_kembali)->format('d/m/Y') : '-',
                    $t->durasi_hari ?? 1,
                    $t->pelanggan?->nama_pelanggan ?? 'Umum',
                    $t->pelanggan?->nomor_telepon ?? '-',
                    $petugas,
                    number_format($tagihan, 0, ',', '.'),
                    number_format($dibayar, 0, ',', '.'),
                    number_format($sisa, 0, ',', '.'),
                    $t->pembayaran?->metode_pembayaran ?? '-',
                    $t->status_pembayaran,
                    $t->status_penyewaan,
                ]);
            }

            $totalSisaSum = max(0.0, $totalTagihanSum - $totalKasSum);

            // Baris Rekapitulasi
            fputcsv($file, []);
            fputcsv($file, ['REKAPITULASI LAPORAN']);
            fputcsv($file, ['Total Transaksi', count($transaksiList).' Transaksi']);
            fputcsv($file, ['Total Tagihan Seluruhnya', 'Rp '.number_format($totalTagihanSum, 0, ',', '.')]);
            fputcsv($file, ['Total Penerimaan Kas Riil', 'Rp '.number_format($totalKasSum, 0, ',', '.')]);
            fputcsv($file, ['Total Sisa Tagihan (Piutang)', 'Rp '.number_format($totalSisaSum, 0, ',', '.')]);

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Tampilkan Laporan Inventaris & Ketersediaan Stok (Admin & Pemilik)
     */
    public function inventaris(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $kategoriFilter = trim((string) $request->query('kategori', ''));
        $statusStok = trim((string) $request->query('status_stok', 'semua'));
        $tab = trim((string) $request->query('tab', 'stok'));

        $ringkasanInventaris = Alat::getRingkasanInventaris();
        $daftarInventaris = Alat::getDaftarInventarisLengkap($search, $kategoriFilter);

        // Filter status stok pada daftar alat
        if ($statusStok === 'siap_sewa') {
            $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_siap_sewa > 0)->values();
        } elseif ($statusStok === 'sedang_disewa') {
            $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_sedang_disewa > 0)->values();
        } elseif ($statusStok === 'dialokasikan') {
            $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_dialokasikan > 0)->values();
        } elseif ($statusStok === 'perlu_perbaikan') {
            $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_perlu_perbaikan > 0)->values();
        } elseif ($statusStok === 'rusak') {
            $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_rusak > 0)->values();
        } elseif ($statusStok === 'bermasalah') {
            $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_bermasalah > 0)->values();
        }

        // Pengurutan Dinamis Kolom Inventaris
        $sortBy = $request->query('sort_by', 'nama_alat');
        $order = strtolower($request->query('order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['kode_alat', 'nama_alat', 'kategori', 'kondisi', 'tarif_sewa', 'stok_fisik_total', 'stok_siap_sewa', 'stok_sedang_disewa', 'stok_dialokasikan', 'stok_bermasalah'];

        if (in_array($sortBy, $allowedSorts, true)) {
            if ($order === 'desc') {
                $daftarInventaris = $daftarInventaris->sortByDesc($sortBy)->values();
            } else {
                $daftarInventaris = $daftarInventaris->sortBy($sortBy)->values();
            }
        }

        // Rekapitulasi Total Inventaris
        $rekapTotalAlat = $daftarInventaris->count();
        $rekapStokFisik = (int) $daftarInventaris->sum('stok_fisik_total');
        $rekapSiapSewa = (int) $daftarInventaris->sum('stok_siap_sewa');
        $rekapSedangDisewa = (int) $daftarInventaris->sum('stok_sedang_disewa');
        $rekapDialokasikan = (int) $daftarInventaris->sum('stok_dialokasikan');
        $rekapBermasalah = (int) $daftarInventaris->sum('stok_bermasalah');
        $totalBiayaPembelian = (float) PembelianInventaris::sum('total_pembelian');

        // Data Riwayat Mutasi Stok (Tab Mutasi / Pergerakan Stok)
        $mutasiQuery = RiwayatStok::with(['alat', 'user'])->orderBy('created_at', 'desc');

        if ($request->filled('mutasi_tgl_mulai')) {
            $mutasiQuery->whereDate('created_at', '>=', $request->input('mutasi_tgl_mulai'));
        }
        if ($request->filled('mutasi_tgl_selesai')) {
            $mutasiQuery->whereDate('created_at', '<=', $request->input('mutasi_tgl_selesai'));
        }
        if ($request->filled('mutasi_alat')) {
            $mutasiQuery->where('kode_alat', $request->input('mutasi_alat'));
        }
        if ($request->filled('mutasi_jenis')) {
            $mutasiQuery->where('jenis_perubahan', $request->input('mutasi_jenis'));
        }

        $riwayatMutasi = $mutasiQuery->paginate(15, ['*'], 'page_mutasi')->withQueryString();

        $kategoriList = Alat::distinct()->orderBy('kategori')->pluck('kategori');
        $semuaAlatList = Alat::orderBy('nama_alat')->get(['kode_alat', 'nama_alat']);

        return view('laporan.inventaris', [
            'ringkasanInventaris' => $ringkasanInventaris,
            'inventarisList' => $daftarInventaris,
            'riwayatMutasi' => $riwayatMutasi,
            'kategoriList' => $kategoriList,
            'semuaAlatList' => $semuaAlatList,
            'search' => $search,
            'kategoriFilter' => $kategoriFilter,
            'statusStok' => $statusStok,
            'sortBy' => $sortBy,
            'order' => $order,
            'tab' => $tab,
            'rekapTotalAlat' => $rekapTotalAlat,
            'rekapStokFisik' => $rekapStokFisik,
            'rekapSiapSewa' => $rekapSiapSewa,
            'rekapSedangDisewa' => $rekapSedangDisewa,
            'rekapDialokasikan' => $rekapDialokasikan,
            'rekapBermasalah' => $rekapBermasalah,
            'totalBiayaPembelian' => $totalBiayaPembelian,
            'isPemilik' => Auth::user()->isPemilik(),
        ]);
    }

    /**
     * Cetak Lembar Laporan Inventaris & Stok (Print View)
     */
    public function cetakInventaris(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $kategoriFilter = trim((string) $request->query('kategori', ''));
        $statusStok = trim((string) $request->query('status_stok', 'semua'));
        $tab = trim((string) $request->query('tab', 'stok'));

        $ringkasanInventaris = Alat::getRingkasanInventaris();
        $daftarInventaris = Alat::getDaftarInventarisLengkap($search, $kategoriFilter);

        if ($statusStok === 'siap_sewa') {
            $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_siap_sewa > 0)->values();
        } elseif ($statusStok === 'sedang_disewa') {
            $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_sedang_disewa > 0)->values();
        } elseif ($statusStok === 'dialokasikan') {
            $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_dialokasikan > 0)->values();
        } elseif ($statusStok === 'perlu_perbaikan') {
            $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_perlu_perbaikan > 0)->values();
        } elseif ($statusStok === 'rusak') {
            $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_rusak > 0)->values();
        } elseif ($statusStok === 'bermasalah') {
            $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_bermasalah > 0)->values();
        }

        $sortBy = $request->query('sort_by', 'nama_alat');
        $order = strtolower($request->query('order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['kode_alat', 'nama_alat', 'kategori', 'kondisi', 'tarif_sewa', 'stok_fisik_total', 'stok_siap_sewa', 'stok_sedang_disewa', 'stok_dialokasikan', 'stok_bermasalah'];

        if (in_array($sortBy, $allowedSorts, true)) {
            if ($order === 'desc') {
                $daftarInventaris = $daftarInventaris->sortByDesc($sortBy)->values();
            } else {
                $daftarInventaris = $daftarInventaris->sortBy($sortBy)->values();
            }
        }

        // Data mutasi lengkap jika tab mutasi dipilih
        $riwayatMutasi = null;
        if ($tab === 'mutasi') {
            $mutasiQuery = RiwayatStok::with(['alat', 'user'])->orderBy('created_at', 'desc');
            if ($request->filled('mutasi_tgl_mulai')) {
                $mutasiQuery->whereDate('created_at', '>=', $request->input('mutasi_tgl_mulai'));
            }
            if ($request->filled('mutasi_tgl_selesai')) {
                $mutasiQuery->whereDate('created_at', '<=', $request->input('mutasi_tgl_selesai'));
            }
            if ($request->filled('mutasi_alat')) {
                $mutasiQuery->where('kode_alat', $request->input('mutasi_alat'));
            }
            if ($request->filled('mutasi_jenis')) {
                $mutasiQuery->where('jenis_perubahan', $request->input('mutasi_jenis'));
            }
            $riwayatMutasi = $mutasiQuery->get();
        }

        return view('laporan.cetak_inventaris', [
            'ringkasanInventaris' => $ringkasanInventaris,
            'inventarisList' => $daftarInventaris,
            'riwayatMutasi' => $riwayatMutasi,
            'filters' => $request->all(),
            'tab' => $tab,
            'totalBiayaPembelian' => (float) PembelianInventaris::sum('total_pembelian'),
            'printedBy' => Auth::user(),
            'printedAt' => Carbon::now('Asia/Jakarta'),
        ]);
    }

    /**
     * Ekspor Data Laporan Inventaris ke File CSV (Excel Compatible)
     */
    public function exportInventaris(Request $request): StreamedResponse
    {
        $search = trim((string) $request->query('q', ''));
        $kategoriFilter = trim((string) $request->query('kategori', ''));
        $statusStok = trim((string) $request->query('status_stok', 'semua'));
        $tab = trim((string) $request->query('tab', 'stok'));

        $filename = 'laporan_inventaris_adventure_camp_'.date('Ymd_His').'.csv';

        if ($tab === 'mutasi') {
            $filename = 'laporan_mutasi_stok_adventure_camp_'.date('Ymd_His').'.csv';
        }

        return response()->streamDownload(function () use ($search, $kategoriFilter, $statusStok, $tab, $request) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");

            if ($tab === 'mutasi') {
                // Ekspor Mutasi Stok
                fputcsv($file, ['ADVENTURE CAMP RENTAL — SISTEM INFORMASI PENYEWAAN ALAT CAMPING']);
                fputcsv($file, ['LAPORAN PERGERAKAN & MUTASI STOK INVENTARIS']);
                fputcsv($file, ['Waktu Cetak:', Carbon::now('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB', 'Oleh:', Auth::user()->nama_user]);
                fputcsv($file, []);

                fputcsv($file, [
                    'No',
                    'Waktu Kejadian',
                    'Kode Alat',
                    'Nama Alat',
                    'Jenis Perubahan',
                    'Perubahan (+/-)',
                    'Stok Sebelum',
                    'Stok Sesudah',
                    'Keterangan / Alasan',
                    'Petugas / Admin',
                ]);

                $mutasiQuery = RiwayatStok::with(['alat', 'user'])->orderBy('created_at', 'desc');
                if ($request->filled('mutasi_tgl_mulai')) {
                    $mutasiQuery->whereDate('created_at', '>=', $request->input('mutasi_tgl_mulai'));
                }
                if ($request->filled('mutasi_tgl_selesai')) {
                    $mutasiQuery->whereDate('created_at', '<=', $request->input('mutasi_tgl_selesai'));
                }
                if ($request->filled('mutasi_alat')) {
                    $mutasiQuery->where('kode_alat', $request->input('mutasi_alat'));
                }
                if ($request->filled('mutasi_jenis')) {
                    $mutasiQuery->where('jenis_perubahan', $request->input('mutasi_jenis'));
                }

                $mutasiList = $mutasiQuery->get();
                $no = 1;
                foreach ($mutasiList as $m) {
                    $sign = $m->jumlah_perubahan > 0 ? '+'.$m->jumlah_perubahan : $m->jumlah_perubahan;
                    fputcsv($file, [
                        $no++,
                        $m->created_at ? Carbon::parse($m->created_at)->format('d/m/Y H:i') : '-',
                        $m->kode_alat,
                        $m->alat?->nama_alat ?? '-',
                        $m->jenis_perubahan,
                        $sign,
                        $m->stok_sebelum,
                        $m->stok_sesudah,
                        $m->alasan,
                        $m->user?->nama_user ?? 'Sistem',
                    ]);
                }
            } else {
                // Ekspor Status Stok Aset
                $daftarInventaris = Alat::getDaftarInventarisLengkap($search, $kategoriFilter);

                if ($statusStok === 'siap_sewa') {
                    $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_siap_sewa > 0)->values();
                } elseif ($statusStok === 'sedang_disewa') {
                    $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_sedang_disewa > 0)->values();
                } elseif ($statusStok === 'dialokasikan') {
                    $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_dialokasikan > 0)->values();
                } elseif ($statusStok === 'perlu_perbaikan') {
                    $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_perlu_perbaikan > 0)->values();
                } elseif ($statusStok === 'rusak') {
                    $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_rusak > 0)->values();
                } elseif ($statusStok === 'bermasalah') {
                    $daftarInventaris = $daftarInventaris->filter(fn ($item) => $item->stok_bermasalah > 0)->values();
                }

                fputcsv($file, ['ADVENTURE CAMP RENTAL — SISTEM INFORMASI PENYEWAAN ALAT CAMPING']);
                fputcsv($file, ['LAPORAN KETERSEDIAAN STOK & KONDISI INVENTARIS']);
                fputcsv($file, ['Filter Status:', strtoupper($statusStok), 'Waktu Cetak:', Carbon::now('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB', 'Oleh:', Auth::user()->nama_user]);
                fputcsv($file, []);

                fputcsv($file, [
                    'No',
                    'Kode Alat',
                    'Nama Alat Camping',
                    'Kategori',
                    'Kondisi Master',
                    'Tarif Sewa/Hari (Rp)',
                    'Total Stok Fisik (Unit)',
                    'Stok Siap Sewa (Unit)',
                    'Sedang Disewa (Unit)',
                    'Dialokasikan di Toko (Unit)',
                    'Perlu Perbaikan (Unit)',
                    'Rusak / Afkir (Unit)',
                ]);

                $no = 1;
                $sumFisik = 0;
                $sumSiap = 0;
                $sumSewa = 0;
                $sumAlok = 0;
                $sumPerbaikan = 0;
                $sumRusak = 0;

                foreach ($daftarInventaris as $item) {
                    $sumFisik += $item->stok_fisik_total;
                    $sumSiap += $item->stok_siap_sewa;
                    $sumSewa += $item->stok_sedang_disewa;
                    $sumAlok += $item->stok_dialokasikan;
                    $sumPerbaikan += $item->stok_perlu_perbaikan;
                    $sumRusak += $item->stok_rusak;

                    fputcsv($file, [
                        $no++,
                        $item->kode_alat,
                        $item->nama_alat,
                        $item->kategori,
                        $item->kondisi,
                        number_format((float) $item->tarif_sewa, 0, ',', '.'),
                        $item->stok_fisik_total,
                        $item->stok_siap_sewa,
                        $item->stok_sedang_disewa,
                        $item->stok_dialokasikan,
                        $item->stok_perlu_perbaikan,
                        $item->stok_rusak,
                    ]);
                }

                fputcsv($file, []);
                fputcsv($file, ['REKAPITULASI INVENTARIS']);
                fputcsv($file, ['Total Jenis Alat (SKU)', count($daftarInventaris).' Jenis']);
                fputcsv($file, ['Total Stok Fisik', $sumFisik.' Unit']);
                fputcsv($file, ['Total Stok Siap Sewa', $sumSiap.' Unit']);
                fputcsv($file, ['Total Sedang Disewa', $sumSewa.' Unit']);
                fputcsv($file, ['Total Dialokasikan di Toko', $sumAlok.' Unit']);
                fputcsv($file, ['Total Unit Perlu Perbaikan', $sumPerbaikan.' Unit']);
                fputcsv($file, ['Total Unit Rusak/Afkir', $sumRusak.' Unit']);
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Cetak Nota Transaksi Penyewaan (Official Receipt / Struk Sewa)
     */
    public function nota(Request $request, string $no_transaksi): View
    {
        $penyewaan = Penyewaan::with([
            'pelanggan',
            'pembayaran.admin',
            'pengambilan.admin',
            'pengembalian.admin',
            'pengembalian.denda',
            'detailPenyewaan.alat',
        ])->where('no_transaksi', $no_transaksi)->firstOrFail();

        $user = Auth::user();

        // Otorisasi: Pengguna hanya boleh mencetak notanya sendiri
        if ($user->isPengguna()) {
            $isOwner = ($penyewaan->id_user === $user->id_user) ||
                ($user->id_pelanggan && $penyewaan->id_pelanggan === $user->id_pelanggan);

            if (! $isOwner) {
                abort(403, 'Anda tidak memiliki hak akses untuk mencetak nota transaksi ini.');
            }
        }

        // Catat di log audit jika Admin mencetak nota
        if ($user->isAdmin()) {
            ActivityLog::record(
                $user->id_user,
                'Cetak Nota Transaksi',
                'Transaksi',
                "Mencetak nota struk resmi untuk transaksi {$penyewaan->no_transaksi}"
            );
        }

        return view('laporan.nota', [
            'penyewaan' => $penyewaan,
            'printedBy' => $user,
            'printedAt' => Carbon::now('Asia/Jakarta'),
        ]);
    }
}

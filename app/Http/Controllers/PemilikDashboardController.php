<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Pembayaran;
use App\Models\PembelianInventaris;
use App\Models\PenangananKerusakan;
use App\Models\Penyewaan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PemilikDashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard utama Pemilik (Strictly Read-Only).
     */
    public function index(Request $request): View
    {
        $timezone = 'Asia/Jakarta';
        $today = Carbon::today($timezone);
        $todayDate = $today->toDateString();

        // 1. Ringkasan Penerimaan Kas Pelanggan Hari Ini (Lunas di Asia/Jakarta)
        $pembayaranHariIni = Pembayaran::with(['penyewaan.pelanggan', 'penyewaan.detailPenyewaan.alat'])
            ->where('status_pembayaran', 'Lunas')
            ->where(function ($q) use ($todayDate) {
                $q->whereDate('waktu_konfirmasi', $todayDate)
                    ->orWhere(function ($sub) use ($todayDate) {
                        $sub->whereNull('waktu_konfirmasi')
                            ->whereDate('tanggal_pembayaran', $todayDate);
                    });
            })
            ->orderByDesc('waktu_konfirmasi')
            ->get();

        $penjualanHariIniCount = $pembayaranHariIni->count();
        $pendapatanHariIni = (float) $pembayaranHariIni->sum('jumlah_pembayaran');
        $nilaiPesananHariIni = (float) $pembayaranHariIni->sum(function ($p) {
            return (float) ($p->total_biaya > 0 ? $p->total_biaya : ($p->penyewaan?->total_biaya ?? 0));
        });

        // 2. Ringkasan Pengeluaran Pembelian Stok Supplier Hari Ini
        $pembelianHariIni = PembelianInventaris::with(['supplier', 'admin', 'detailPembelian.alat'])
            ->whereDate('tanggal_pembelian', $todayDate)
            ->orderByDesc('created_at')
            ->get();

        $pembelianHariIniCount = $pembelianHariIni->count();
        $totalPembelianHariIni = (float) $pembelianHariIni->sum('total_pembelian');

        // 3. Ringkasan Pengeluaran Biaya Perbaikan Hari Ini
        $perbaikanHariIni = PenangananKerusakan::with(['alat', 'admin', 'detailPenyewaan.penyewaan.pelanggan'])
            ->whereDate('tanggal_tindakan', $todayDate)
            ->where('biaya_perbaikan', '>', 0)
            ->orderByDesc('id_penanganan')
            ->get();

        $perbaikanHariIniCount = $perbaikanHariIni->count();
        $totalBiayaPerbaikanHariIni = (float) $perbaikanHariIni->sum('biaya_perbaikan');

        // 4. Arus Kas Bersih Hari Ini (Penerimaan Pelanggan - Pembelian Stok - Biaya Perbaikan)
        $arusKasBersihHariIni = $pendapatanHariIni - $totalPembelianHariIni - $totalBiayaPerbaikanHariIni;

        // 5. Ringkasan Penerimaan Kas Pelanggan Bulan Berjalan (MTD di Asia/Jakarta)
        $startOfMonth = Carbon::now($timezone)->startOfMonth();
        $endOfMonth = Carbon::now($timezone)->endOfMonth();
        $startOfMonthDate = $startOfMonth->toDateString();
        $endOfMonthDate = $endOfMonth->toDateString();

        $pembayaranBulanIni = Pembayaran::with(['penyewaan.pelanggan', 'penyewaan.detailPenyewaan.alat'])
            ->where('status_pembayaran', 'Lunas')
            ->where(function ($q) use ($startOfMonthDate, $endOfMonthDate) {
                $q->whereBetween('waktu_konfirmasi', [$startOfMonthDate.' 00:00:00', $endOfMonthDate.' 23:59:59'])
                    ->orWhere(function ($sub) use ($startOfMonthDate, $endOfMonthDate) {
                        $sub->whereNull('waktu_konfirmasi')
                            ->whereBetween('tanggal_pembayaran', [$startOfMonthDate.' 00:00:00', $endOfMonthDate.' 23:59:59']);
                    });
            })
            ->orderByDesc('waktu_konfirmasi')
            ->get();

        $penjualanBulanIniCount = $pembayaranBulanIni->count();
        $pendapatanBulanIni = (float) $pembayaranBulanIni->sum('jumlah_pembayaran');
        $nilaiPesananBulanIni = (float) $pembayaranBulanIni->sum(function ($p) {
            return (float) ($p->total_biaya > 0 ? $p->total_biaya : ($p->penyewaan?->total_biaya ?? 0));
        });

        // 6. Ringkasan Pengeluaran Pembelian Stok Supplier Bulan Berjalan
        $pembelianBulanIni = PembelianInventaris::with(['supplier', 'admin', 'detailPembelian.alat'])
            ->whereBetween('tanggal_pembelian', [$startOfMonthDate, $endOfMonthDate])
            ->orderByDesc('tanggal_pembelian')
            ->orderByDesc('created_at')
            ->get();

        $pembelianBulanIniCount = $pembelianBulanIni->count();
        $totalPembelianBulanIni = (float) $pembelianBulanIni->sum('total_pembelian');

        // 7. Ringkasan Pengeluaran Biaya Perbaikan Bulan Berjalan
        $perbaikanBulanIni = PenangananKerusakan::with(['alat', 'admin', 'detailPenyewaan.penyewaan.pelanggan'])
            ->whereBetween('tanggal_tindakan', [$startOfMonthDate, $endOfMonthDate])
            ->where('biaya_perbaikan', '>', 0)
            ->orderByDesc('tanggal_tindakan')
            ->orderByDesc('id_penanganan')
            ->get();

        $perbaikanBulanIniCount = $perbaikanBulanIni->count();
        $totalBiayaPerbaikanBulanIni = (float) $perbaikanBulanIni->sum('biaya_perbaikan');

        // 8. Arus Kas Bersih Bulan Berjalan
        $arusKasBersihBulanIni = $pendapatanBulanIni - $totalPembelianBulanIni - $totalBiayaPerbaikanBulanIni;

        // 9. Konfigurasi Periode Filter Grafik Keuangan
        $selectedPeriode = $request->query('periode', '7_hari');
        $tanggalMulaiInput = $request->query('tanggal_mulai');
        $tanggalSelesaiInput = $request->query('tanggal_selesai');

        $chartTimeline = [];
        $chartTitle = '📊 Grafik Penjualan Harian (7 Hari Terakhir)';
        $chartSubtitle = 'Performa harian kas masuk, pengeluaran stok & perbaikan, serta arus kas bersih (WIB)';

        if ($selectedPeriode === '30_hari') {
            $chartTitle = '📊 Grafik Penjualan Harian (30 Hari Terakhir)';
            $chartSubtitle = 'Performa 30 hari kas masuk sah, pengeluaran & arus kas bersih (WIB)';
            for ($i = 29; $i >= 0; $i--) {
                $chartTimeline[] = Carbon::now($timezone)->subDays($i);
            }
        } elseif ($selectedPeriode === 'bulan_ini') {
            $chartTitle = '📊 Grafik Penjualan Harian Bulan Ini ('.Carbon::now($timezone)->translatedFormat('F Y').')';
            $chartSubtitle = 'Akumulasi harian kas masuk & pengeluaran bulan berjalan (WIB)';
            $daysInMonthSoFar = Carbon::now($timezone)->day;
            for ($i = $daysInMonthSoFar - 1; $i >= 0; $i--) {
                $chartTimeline[] = Carbon::now($timezone)->subDays($i);
            }
        } elseif ($selectedPeriode === 'kustom' && $request->filled(['tanggal_mulai', 'tanggal_selesai'])) {
            $tglMulai = Carbon::parse($tanggalMulaiInput, $timezone)->startOfDay();
            $tglSelesai = Carbon::parse($tanggalSelesaiInput, $timezone)->startOfDay();
            if ($tglMulai->gt($tglSelesai)) {
                $temp = $tglMulai;
                $tglMulai = $tglSelesai;
                $tglSelesai = $temp;
            }
            $diffDays = min(60, (int) $tglMulai->diffInDays($tglSelesai));
            for ($i = 0; $i <= $diffDays; $i++) {
                $chartTimeline[] = $tglMulai->copy()->addDays($i);
            }
            $chartTitle = '📊 Grafik Penjualan Harian ('.$tglMulai->translatedFormat('d M Y').' — '.$tglSelesai->translatedFormat('d M Y').')';
            $chartSubtitle = 'Rentang kustom terpilih penerimaan vs pengeluaran (WIB)';
        } else {
            // Default: 7 Hari Terakhir
            $selectedPeriode = '7_hari';
            for ($i = 6; $i >= 0; $i--) {
                $chartTimeline[] = Carbon::now($timezone)->subDays($i);
            }
        }

        // Ambil data pembayaran lunas untuk periode timeline dengan 1 query optimal
        $earliestDate = $chartTimeline[0]->toDateString();
        $latestDate = end($chartTimeline)->toDateString();

        $pembayaranPeriod = Pembayaran::query()
            ->where('status_pembayaran', 'Lunas')
            ->where(function ($q) use ($earliestDate, $latestDate) {
                $q->whereBetween('waktu_konfirmasi', [$earliestDate.' 00:00:00', $latestDate.' 23:59:59'])
                    ->orWhere(function ($sub) use ($earliestDate, $latestDate) {
                        $sub->whereNull('waktu_konfirmasi')
                            ->whereBetween('tanggal_pembayaran', [$earliestDate.' 00:00:00', $latestDate.' 23:59:59']);
                    });
            })
            ->get();

        $pembayaranByDay = $pembayaranPeriod->groupBy(function ($bayar) {
            $time = $bayar->waktu_konfirmasi ?? $bayar->tanggal_pembayaran;

            return Carbon::parse($time)->timezone('Asia/Jakarta')->toDateString();
        });

        // Ambil data pembelian supplier pada rentang timeline
        $pembelianPeriod = PembelianInventaris::whereBetween('tanggal_pembelian', [$earliestDate, $latestDate])->get();
        $pembelianByDay = $pembelianPeriod->groupBy(fn ($p) => Carbon::parse($p->tanggal_pembelian)->toDateString());

        // Ambil data biaya perbaikan pada rentang timeline
        $perbaikanPeriod = PenangananKerusakan::whereBetween('tanggal_tindakan', [$earliestDate, $latestDate])
            ->where('biaya_perbaikan', '>', 0)
            ->get();
        $perbaikanByDay = $perbaikanPeriod->groupBy(fn ($p) => Carbon::parse($p->tanggal_tindakan)->toDateString());

        $grafikHarian = [];
        foreach ($chartTimeline as $day) {
            $dayDate = $day->toDateString();
            $dayLabel = $day->translatedFormat('d M');
            $pembayaranDay = $pembayaranByDay->get($dayDate, collect());
            $pembelianDay = $pembelianByDay->get($dayDate, collect());
            $perbaikanDay = $perbaikanByDay->get($dayDate, collect());

            $uangMasuk = (float) $pembayaranDay->sum('jumlah_pembayaran');
            $beliTotal = (float) $pembelianDay->sum('total_pembelian');
            $perbaikanTotal = (float) $perbaikanDay->sum('biaya_perbaikan');
            $pengeluaranTotal = $beliTotal + $perbaikanTotal;
            $arusKasBersih = $uangMasuk - $pengeluaranTotal;

            $grafikHarian[] = [
                'tanggal' => $dayDate,
                'label' => $dayLabel,
                'total_transaksi' => $pembayaranDay->count(),
                'uang_diterima' => $uangMasuk,
                'pembelian_stok' => $beliTotal,
                'biaya_perbaikan' => $perbaikanTotal,
                'total_pengeluaran' => $pengeluaranTotal,
                'arus_kas_bersih' => $arusKasBersih,
                'nilai_pesanan' => (float) $pembayaranDay->sum(function ($p) {
                    return (float) ($p->total_biaya > 0 ? $p->total_biaya : ($p->penyewaan?->total_biaya ?? 0));
                }),
            ];
        }

        $hasDailyChartData = collect($grafikHarian)->sum('total_transaksi') > 0
            || collect($grafikHarian)->sum('uang_diterima') > 0
            || collect($grafikHarian)->sum('total_pengeluaran') > 0;

        // 10. Grafik Tren Keuangan Bulanan (6 Bulan Terakhir di Asia/Jakarta)
        $mStartQuery = Carbon::now($timezone)->subMonths(5)->startOfMonth()->toDateString();
        $mEndQuery = Carbon::now($timezone)->endOfMonth()->toDateString();

        $pembayaran6Bulan = Pembayaran::query()
            ->where('status_pembayaran', 'Lunas')
            ->where(function ($q) use ($mStartQuery, $mEndQuery) {
                $q->whereBetween('waktu_konfirmasi', [$mStartQuery.' 00:00:00', $mEndQuery.' 23:59:59'])
                    ->orWhere(function ($sub) use ($mStartQuery, $mEndQuery) {
                        $sub->whereNull('waktu_konfirmasi')
                            ->whereBetween('tanggal_pembayaran', [$mStartQuery.' 00:00:00', $mEndQuery.' 23:59:59']);
                    });
            })
            ->get();

        $pembayaranByMonth = $pembayaran6Bulan->groupBy(function ($bayar) {
            $time = $bayar->waktu_konfirmasi ?? $bayar->tanggal_pembayaran;

            return Carbon::parse($time)->timezone('Asia/Jakarta')->format('Y-m');
        });

        $pembelian6Bulan = PembelianInventaris::whereBetween('tanggal_pembelian', [$mStartQuery, $mEndQuery])->get();
        $pembelianByMonth = $pembelian6Bulan->groupBy(fn ($p) => Carbon::parse($p->tanggal_pembelian)->format('Y-m'));

        $perbaikan6Bulan = PenangananKerusakan::whereBetween('tanggal_tindakan', [$mStartQuery, $mEndQuery])
            ->where('biaya_perbaikan', '>', 0)
            ->get();
        $perbaikanByMonth = $perbaikan6Bulan->groupBy(fn ($p) => Carbon::parse($p->tanggal_tindakan)->format('Y-m'));

        $grafikBulanan = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now($timezone)->subMonths($i);
            $monthKey = $month->format('Y-m');
            $mLabel = $month->translatedFormat('M Y');
            $pembayaranMonth = $pembayaranByMonth->get($monthKey, collect());
            $pembelianMonth = $pembelianByMonth->get($monthKey, collect());
            $perbaikanMonth = $perbaikanByMonth->get($monthKey, collect());

            $uangMasukM = (float) $pembayaranMonth->sum('jumlah_pembayaran');
            $beliM = (float) $pembelianMonth->sum('total_pembelian');
            $perbaikanM = (float) $perbaikanMonth->sum('biaya_perbaikan');
            $pengeluaranM = $beliM + $perbaikanM;
            $arusKasM = $uangMasukM - $pengeluaranM;

            $grafikBulanan[] = [
                'bulan' => $monthKey,
                'label' => $mLabel,
                'total_transaksi' => $pembayaranMonth->count(),
                'uang_diterima' => $uangMasukM,
                'pembelian_stok' => $beliM,
                'biaya_perbaikan' => $perbaikanM,
                'total_pengeluaran' => $pengeluaranM,
                'arus_kas_bersih' => $arusKasM,
                'nilai_pesanan' => (float) $pembayaranMonth->sum(function ($p) {
                    return (float) ($p->total_biaya > 0 ? $p->total_biaya : ($p->penyewaan?->total_biaya ?? 0));
                }),
            ];
        }

        $hasMonthlyChartData = collect($grafikBulanan)->sum('total_transaksi') > 0
            || collect($grafikBulanan)->sum('uang_diterima') > 0
            || collect($grafikBulanan)->sum('total_pengeluaran') > 0;

        // 11. Ringkasan Kondisi Alat & Daftar Rincian Lengkap (Real Database, Konsisten dengan Admin)
        $ringkasanInventaris = Alat::getRingkasanInventaris();
        $daftarInventarisLengkap = Alat::getDaftarInventarisLengkap();
        $daftarSedangDisewa = Alat::getDaftarSedangDisewa();
        $daftarDialokasikan = Alat::getDaftarDialokasikan();
        $stokSiapSewaList = $daftarInventarisLengkap->filter(fn ($item) => $item->stok_siap_sewa > 0)->values();

        $alatRusakList = Alat::with(['detailPenyewaan'])
            ->where(function ($q) {
                $q->where('kondisi', 'Rusak')
                    ->orWhereHas('detailPenyewaan', function ($sub) {
                        $sub->where('jumlah_rusak', '>', 0);
                    });
            })
            ->orderBy('kode_alat')
            ->get()
            ->map(function ($alat) {
                $rusakReturn = (int) $alat->detailPenyewaan->sum('jumlah_rusak');
                $rusakMaster = ($alat->kondisi === 'Rusak') ? (int) $alat->stok : 0;
                $alat->total_unit_rusak = $rusakMaster + $rusakReturn;

                return $alat;
            });

        $alatPerluPerbaikanList = Alat::with(['detailPenyewaan'])
            ->where(function ($q) {
                $q->where('kondisi', 'Perlu Perbaikan')
                    ->orWhereHas('detailPenyewaan', function ($sub) {
                        $sub->whereRaw('jumlah_perlu_perbaikan > COALESCE(jumlah_diperbaiki, 0)');
                    });
            })
            ->orderBy('kode_alat')
            ->get()
            ->map(function ($alat) {
                $perbaikanReturn = (int) $alat->detailPenyewaan->sum(function ($d) {
                    return max(0, (int) $d->jumlah_perlu_perbaikan - (int) ($d->jumlah_diperbaiki ?? 0));
                });
                $perbaikanMaster = ($alat->kondisi === 'Perlu Perbaikan') ? (int) $alat->stok : 0;
                $alat->total_unit_perbaikan = $perbaikanMaster + $perbaikanReturn;

                return $alat;
            });

        // 12. Riwayat Pesanan Pelanggan (Real Database, Termasuk Sumber Online & On-site, Read-Only)
        $pesananList = Penyewaan::with(['pelanggan', 'pembayaran', 'detailPenyewaan.alat'])
            ->orderBy('tanggal_sewa', 'desc')
            ->get();

        return view('pemilik.dashboard', [
            'todayDateFormatted' => $today->translatedFormat('d F Y'),
            'currentMonthFormatted' => Carbon::now($timezone)->translatedFormat('F Y'),

            // Penerimaan Pelanggan (Gross Collections)
            'penjualanHariIniCount' => $penjualanHariIniCount,
            'pendapatanHariIni' => $pendapatanHariIni,
            'nilaiPesananHariIni' => $nilaiPesananHariIni,
            'rincianHariIni' => $pembayaranHariIni,
            'penjualanBulanIniCount' => $penjualanBulanIniCount,
            'pendapatanBulanIni' => $pendapatanBulanIni,
            'nilaiPesananBulanIni' => $nilaiPesananBulanIni,
            'rincianBulanIni' => $pembayaranBulanIni,

            // Pengeluaran Pembelian Stok Supplier
            'pembelianHariIniCount' => $pembelianHariIniCount,
            'totalPembelianHariIni' => $totalPembelianHariIni,
            'rincianPembelianHariIni' => $pembelianHariIni,
            'pembelianBulanIniCount' => $pembelianBulanIniCount,
            'totalPembelianBulanIni' => $totalPembelianBulanIni,
            'rincianPembelianBulanIni' => $pembelianBulanIni,

            // Pengeluaran Biaya Perbaikan
            'perbaikanHariIniCount' => $perbaikanHariIniCount,
            'totalBiayaPerbaikanHariIni' => $totalBiayaPerbaikanHariIni,
            'rincianPerbaikanHariIni' => $perbaikanHariIni,
            'perbaikanBulanIniCount' => $perbaikanBulanIniCount,
            'totalBiayaPerbaikanBulanIni' => $totalBiayaPerbaikanBulanIni,
            'rincianPerbaikanBulanIni' => $perbaikanBulanIni,

            // Arus Kas Bersih (Net Cash Flow)
            'arusKasBersihHariIni' => $arusKasBersihHariIni,
            'arusKasBersihBulanIni' => $arusKasBersihBulanIni,

            // Grafik & Filter
            'selectedPeriode' => $selectedPeriode,
            'tanggalMulaiInput' => $tanggalMulaiInput,
            'tanggalSelesaiInput' => $tanggalSelesaiInput,
            'chartTitle' => $chartTitle,
            'chartSubtitle' => $chartSubtitle,
            'grafikHarian' => $grafikHarian,
            'hasDailyChartData' => $hasDailyChartData,
            'grafikBulanan' => $grafikBulanan,
            'hasMonthlyChartData' => $hasMonthlyChartData,

            // Inventaris & Pesanan
            'ringkasanInventaris' => $ringkasanInventaris,
            'daftarInventarisLengkap' => $daftarInventarisLengkap,
            'daftarSedangDisewa' => $daftarSedangDisewa,
            'daftarDialokasikan' => $daftarDialokasikan,
            'stokSiapSewaList' => $stokSiapSewaList,
            'alatRusakList' => $alatRusakList,
            'alatPerluPerbaikanList' => $alatPerluPerbaikanList,
            'pesananList' => $pesananList,
        ]);
    }

    /**
     * Tampilkan halaman rincian inventaris lengkap untuk Pemilik (Strictly Read-Only).
     */
    public function inventaris(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $kategoriFilter = trim((string) $request->query('kategori', ''));
        $statusStok = trim((string) $request->query('status_stok', 'semua'));

        $ringkasanInventaris = Alat::getRingkasanInventaris();
        $daftarInventarisLengkap = Alat::getDaftarInventarisLengkap($search, $kategoriFilter);
        $daftarSedangDisewa = Alat::getDaftarSedangDisewa($search);
        $daftarDialokasikan = Alat::getDaftarDialokasikan($search);

        // Filter berdasarkan status stok jika dipilih
        if ($statusStok === 'siap_sewa') {
            $daftarInventarisLengkap = $daftarInventarisLengkap->filter(fn ($item) => $item->stok_siap_sewa > 0)->values();
        } elseif ($statusStok === 'sedang_disewa') {
            $daftarInventarisLengkap = $daftarInventarisLengkap->filter(fn ($item) => $item->stok_sedang_disewa > 0)->values();
        } elseif ($statusStok === 'dialokasikan') {
            $daftarInventarisLengkap = $daftarInventarisLengkap->filter(fn ($item) => $item->stok_dialokasikan > 0)->values();
        } elseif ($statusStok === 'perlu_perbaikan') {
            $daftarInventarisLengkap = $daftarInventarisLengkap->filter(fn ($item) => $item->stok_perlu_perbaikan > 0)->values();
        } elseif ($statusStok === 'rusak') {
            $daftarInventarisLengkap = $daftarInventarisLengkap->filter(fn ($item) => $item->stok_rusak > 0)->values();
        }

        $kategoriList = Alat::distinct()->orderBy('kategori')->pluck('kategori');

        return view('pemilik.inventaris.index', [
            'ringkasanInventaris' => $ringkasanInventaris,
            'inventarisList' => $daftarInventarisLengkap,
            'daftarSedangDisewa' => $daftarSedangDisewa,
            'daftarDialokasikan' => $daftarDialokasikan,
            'kategoriList' => $kategoriList,
            'search' => $search,
            'kategoriFilter' => $kategoriFilter,
            'statusStok' => $statusStok,
        ]);
    }
}

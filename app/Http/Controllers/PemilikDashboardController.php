<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Pembayaran;
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

        // 1. Ringkasan Penjualan Hari Ini (berdasarkan pembayaran yang benar-benar terkonfirmasi Lunas di Asia/Jakarta)
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

        // 2. Ringkasan Penjualan Bulan Berjalan (MTD berdasarkan pembayaran Lunas di Asia/Jakarta)
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

        // 3. Konfigurasi Periode Filter Grafik Penjualan
        $selectedPeriode = $request->query('periode', '7_hari');
        $tanggalMulaiInput = $request->query('tanggal_mulai');
        $tanggalSelesaiInput = $request->query('tanggal_selesai');

        $chartTimeline = [];
        $chartTitle = '📊 Grafik Penjualan Harian (7 Hari Terakhir)';
        $chartSubtitle = 'Performa harian kas masuk & nilai pesanan (WIB)';

        if ($selectedPeriode === '30_hari') {
            $chartTitle = '📊 Grafik Penjualan Harian (30 Hari Terakhir)';
            $chartSubtitle = 'Performa 30 hari kas masuk sah & volume pesanan (WIB)';
            for ($i = 29; $i >= 0; $i--) {
                $chartTimeline[] = Carbon::now($timezone)->subDays($i);
            }
        } elseif ($selectedPeriode === 'bulan_ini') {
            $chartTitle = '📊 Grafik Penjualan Bulan Ini ('.Carbon::now($timezone)->translatedFormat('F Y').')';
            $chartSubtitle = 'Akumulasi harian bulan berjalan (WIB)';
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
            $chartTitle = '📊 Grafik Penjualan ('.$tglMulai->translatedFormat('d M Y').' — '.$tglSelesai->translatedFormat('d M Y').')';
            $chartSubtitle = 'Rentang kustom terpilih (WIB)';
        } else {
            // Default: 7 Hari Terakhir
            $selectedPeriode = '7_hari';
            for ($i = 6; $i >= 0; $i--) {
                $chartTimeline[] = Carbon::now($timezone)->subDays($i);
            }
        }

        // Ambil data pembayaran lunas untuk periode timeline dengan 1 query optimal (mencegah relasi join multiplication)
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

        $grafikHarian = [];
        foreach ($chartTimeline as $day) {
            $dayDate = $day->toDateString();
            $dayLabel = $day->translatedFormat('d M');
            $pembayaranDay = $pembayaranByDay->get($dayDate, collect());

            $grafikHarian[] = [
                'tanggal' => $dayDate,
                'label' => $dayLabel,
                'total_transaksi' => $pembayaranDay->count(),
                'uang_diterima' => (float) $pembayaranDay->sum('jumlah_pembayaran'),
                'nilai_pesanan' => (float) $pembayaranDay->sum(function ($p) {
                    return (float) ($p->total_biaya > 0 ? $p->total_biaya : ($p->penyewaan?->total_biaya ?? 0));
                }),
            ];
        }

        $hasDailyChartData = collect($grafikHarian)->sum('total_transaksi') > 0 || collect($grafikHarian)->sum('uang_diterima') > 0;

        // 4. Grafik Tren Penjualan Bulanan (6 Bulan Terakhir di Asia/Jakarta)
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

        $grafikBulanan = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now($timezone)->subMonths($i);
            $monthKey = $month->format('Y-m');
            $mLabel = $month->translatedFormat('M Y');
            $pembayaranMonth = $pembayaranByMonth->get($monthKey, collect());

            $grafikBulanan[] = [
                'bulan' => $monthKey,
                'label' => $mLabel,
                'total_transaksi' => $pembayaranMonth->count(),
                'uang_diterima' => (float) $pembayaranMonth->sum('jumlah_pembayaran'),
                'nilai_pesanan' => (float) $pembayaranMonth->sum(function ($p) {
                    return (float) ($p->total_biaya > 0 ? $p->total_biaya : ($p->penyewaan?->total_biaya ?? 0));
                }),
            ];
        }

        $hasMonthlyChartData = collect($grafikBulanan)->sum('total_transaksi') > 0 || collect($grafikBulanan)->sum('uang_diterima') > 0;

        // 5 & 6. Ringkasan Kondisi Alat & Daftar Alat Rusak & Perlu Perbaikan (Real Database, Konsisten dengan Admin)
        $ringkasanInventaris = Alat::getRingkasanInventaris();

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

        // 7. Riwayat Pesanan Pelanggan (Real Database, Termasuk Sumber Online & On-site, Read-Only)
        $pesananList = Penyewaan::with(['pelanggan', 'pembayaran', 'detailPenyewaan.alat'])
            ->orderBy('tanggal_sewa', 'desc')
            ->get();

        return view('pemilik.dashboard', [
            'todayDateFormatted' => $today->translatedFormat('d F Y'),
            'currentMonthFormatted' => Carbon::now($timezone)->translatedFormat('F Y'),
            'penjualanHariIniCount' => $penjualanHariIniCount,
            'pendapatanHariIni' => $pendapatanHariIni,
            'nilaiPesananHariIni' => $nilaiPesananHariIni,
            'rincianHariIni' => $pembayaranHariIni,
            'penjualanBulanIniCount' => $penjualanBulanIniCount,
            'pendapatanBulanIni' => $pendapatanBulanIni,
            'nilaiPesananBulanIni' => $nilaiPesananBulanIni,
            'rincianBulanIni' => $pembayaranBulanIni,
            'selectedPeriode' => $selectedPeriode,
            'tanggalMulaiInput' => $tanggalMulaiInput,
            'tanggalSelesaiInput' => $tanggalSelesaiInput,
            'chartTitle' => $chartTitle,
            'chartSubtitle' => $chartSubtitle,
            'grafikHarian' => $grafikHarian,
            'hasDailyChartData' => $hasDailyChartData,
            'grafikBulanan' => $grafikBulanan,
            'hasMonthlyChartData' => $hasMonthlyChartData,
            'ringkasanInventaris' => $ringkasanInventaris,
            'alatRusakList' => $alatRusakList,
            'alatPerluPerbaikanList' => $alatPerluPerbaikanList,
            'pesananList' => $pesananList,
        ]);
    }
}

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

        // 1. Ringkasan Penjualan Hari Ini (berdasarkan pembayaran yang benar-benar terkonfirmasi Lunas)
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

        // 2. Ringkasan Penjualan Bulan Berjalan (MTD berdasarkan pembayaran Lunas)
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

        // 3. Grafik Penjualan Harian (7 Hari Terakhir di Asia/Jakarta)
        $grafikHarian = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now($timezone)->subDays($i);
            $dayDate = $day->toDateString();
            $dayLabel = $day->translatedFormat('d M');

            $pembayaranDay = Pembayaran::query()
                ->where('status_pembayaran', 'Lunas')
                ->where(function ($q) use ($dayDate) {
                    $q->whereDate('waktu_konfirmasi', $dayDate)
                        ->orWhere(function ($sub) use ($dayDate) {
                            $sub->whereNull('waktu_konfirmasi')
                                ->whereDate('tanggal_pembayaran', $dayDate);
                        });
                })
                ->get();

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
        $grafikBulanan = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now($timezone)->subMonths($i);
            $mStart = $month->copy()->startOfMonth()->toDateString();
            $mEnd = $month->copy()->endOfMonth()->toDateString();
            $mLabel = $month->translatedFormat('M Y');

            $pembayaranMonth = Pembayaran::query()
                ->where('status_pembayaran', 'Lunas')
                ->where(function ($q) use ($mStart, $mEnd) {
                    $q->whereBetween('waktu_konfirmasi', [$mStart.' 00:00:00', $mEnd.' 23:59:59'])
                        ->orWhere(function ($sub) use ($mStart, $mEnd) {
                            $sub->whereNull('waktu_konfirmasi')
                                ->whereBetween('tanggal_pembayaran', [$mStart.' 00:00:00', $mEnd.' 23:59:59']);
                        });
                })
                ->get();

            $grafikBulanan[] = [
                'bulan' => $month->format('Y-m'),
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

        // 7. Ringkasan Transaksi Pelanggan (Real Database, Read-Only)
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

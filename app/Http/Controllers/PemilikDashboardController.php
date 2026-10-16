<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
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
        ]);
    }
}

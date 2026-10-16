<?php

namespace App\Http\Controllers;

use App\Models\Penyewaan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PemilikTransaksiController extends Controller
{
    /**
     * Tampilkan daftar riwayat transaksi pelanggan untuk Pemilik (Read-Only).
     */
    public function index(Request $request): View
    {
        $query = Penyewaan::query()
            ->with(['pelanggan', 'pembayaran', 'detailPenyewaan.alat'])
            ->orderBy('tanggal_sewa', 'desc');

        // Filter kata kunci pencarian (No. Transaksi atau Nama Pelanggan)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('penyewaan.no_transaksi', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($sub) use ($search) {
                        $sub->where('nama_pelanggan', 'like', "%{$search}%");
                    });
            });
        }

        // Filter rentang tanggal pemesanan / sewa
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_sewa', '>=', $request->input('tanggal_mulai'));
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_sewa', '<=', $request->input('tanggal_selesai'));
        }

        // Filter status pesanan
        if ($request->filled('status_penyewaan')) {
            $statusSewa = $request->input('status_penyewaan');
            if ($statusSewa === 'Berjalan') {
                $query->whereIn('status_penyewaan', ['Berjalan', 'Sedang Disewa']);
            } else {
                $query->where('status_penyewaan', $statusSewa);
            }
        }

        // Filter status pembayaran
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

        $transaksiList = $query->paginate(10)->withQueryString();

        return view('pemilik.transaksi.index', [
            'transaksiList' => $transaksiList,
            'filters' => [
                'search' => $request->input('search'),
                'tanggal_mulai' => $request->input('tanggal_mulai'),
                'tanggal_selesai' => $request->input('tanggal_selesai'),
                'status_penyewaan' => $request->input('status_penyewaan'),
                'status_pembayaran' => $request->input('status_pembayaran'),
            ],
            'hasActiveFilter' => $request->anyFilled(['search', 'tanggal_mulai', 'tanggal_selesai', 'status_penyewaan', 'status_pembayaran']),
        ]);
    }

    /**
     * Tampilkan rincian lengkap transaksi penyewaan pelanggan untuk Pemilik (Strictly Read-Only).
     */
    public function show(string $no_transaksi): View
    {
        $penyewaan = Penyewaan::with([
            'pelanggan',
            'pembayaran.admin',
            'detailPenyewaan.alat',
            'pengambilan.admin',
            'pengembalian.admin',
            'pengembalian.denda',
        ])->where('no_transaksi', $no_transaksi)->firstOrFail();

        return view('pemilik.transaksi.show', [
            'penyewaan' => $penyewaan,
        ]);
    }
}

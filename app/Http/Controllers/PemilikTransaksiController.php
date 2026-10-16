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
        $transaksiList = Penyewaan::with(['pelanggan', 'pembayaran', 'detailPenyewaan.alat'])
            ->orderBy('tanggal_sewa', 'desc')
            ->paginate(10);

        return view('pemilik.transaksi.index', [
            'transaksiList' => $transaksiList,
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

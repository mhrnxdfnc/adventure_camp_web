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
}

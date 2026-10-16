<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\Penyewaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminPesananController extends Controller
{
    /**
     * Setujui pengajuan pesanan penyewaan dari pelanggan
     */
    public function setujui(Request $request, string $no_transaksi): RedirectResponse
    {
        $penyewaan = Penyewaan::with(['pelanggan', 'detailPenyewaan.alat'])
            ->where('no_transaksi', $no_transaksi)
            ->firstOrFail();

        if ($penyewaan->status_penyewaan !== 'Menunggu Konfirmasi') {
            return back()->with('error', "Pesanan {$no_transaksi} tidak dalam status Menunggu Konfirmasi (status saat ini: {$penyewaan->status_penyewaan}).");
        }

        try {
            DB::transaction(function () use ($penyewaan) {
                $trx = Penyewaan::with('detailPenyewaan')
                    ->where('no_transaksi', $penyewaan->no_transaksi)
                    ->lockForUpdate()
                    ->first();

                // Pemeriksaan ketersediaan stok fisik & kelayakan kondisi alat saat persetujuan
                foreach ($trx->detailPenyewaan as $detail) {
                    $alat = Alat::where('kode_alat', $detail->kode_alat)->lockForUpdate()->first();

                    if (! $alat) {
                        throw new \DomainException("Persetujuan ditolak: Alat {$detail->kode_alat} tidak ditemukan dalam database.");
                    }

                    if ($alat->kondisi !== 'Baik') {
                        throw new \DomainException("Persetujuan ditolak: Alat [{$alat->kode_alat}] {$alat->nama_alat} dalam kondisi '{$alat->kondisi}' dan tidak layak disewakan.");
                    }

                    if ($alat->stok < $detail->jumlah) {
                        throw new \DomainException("Persetujuan ditolak: Stok alat [{$alat->kode_alat}] {$alat->nama_alat} tidak mencukupi (Tersedia: {$alat->stok} unit, Diminta: {$detail->jumlah} unit).");
                    }
                }

                $trx->status_penyewaan = 'Menunggu Pembayaran';
                $trx->save();

                $namaPelanggan = $trx->pelanggan?->nama_pelanggan ?? 'ID Pelanggan: '.$trx->id_pelanggan;

                // Catat persetujuan pesanan ke dalam audit trail
                ActivityLog::record(
                    Auth::id(),
                    'Persetujuan Pesanan',
                    'Penyewaan',
                    "No Transaksi: {$trx->no_transaksi}, Pelanggan: {$namaPelanggan}, Status: Menunggu Pembayaran"
                );
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Pesanan {$no_transaksi} berhasil disetujui. Pelanggan kini dapat melanjutkan ke pembayaran.");
    }

    /**
     * Tolak pengajuan pesanan penyewaan dari pelanggan dengan alasan penolakan wajib
     */
    public function tolak(Request $request, string $no_transaksi): RedirectResponse
    {
        $penyewaan = Penyewaan::with('pelanggan')
            ->where('no_transaksi', $no_transaksi)
            ->firstOrFail();

        if ($penyewaan->status_penyewaan !== 'Menunggu Konfirmasi') {
            return back()->with('error', "Pesanan {$no_transaksi} tidak dalam status Menunggu Konfirmasi (status saat ini: {$penyewaan->status_penyewaan}).");
        }

        $request->validate([
            'alasan_penolakan' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'alasan_penolakan.required' => 'Alasan penolakan pesanan wajib diisi.',
            'alasan_penolakan.min' => 'Alasan penolakan minimal 5 karakter.',
            'alasan_penolakan.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $alasan = trim((string) $request->input('alasan_penolakan'));

        DB::transaction(function () use ($penyewaan, $alasan) {
            $trx = Penyewaan::where('no_transaksi', $penyewaan->no_transaksi)
                ->lockForUpdate()
                ->first();

            $trx->status_penyewaan = 'Ditolak';
            $trx->save();

            // Catat penolakan pesanan ke dalam audit trail
            ActivityLog::record(
                Auth::id(),
                'Penolakan Pesanan',
                'Penyewaan',
                "No Transaksi: {$trx->no_transaksi}, Alasan: {$alasan}"
            );
        });

        return back()->with('success', "Pesanan {$no_transaksi} berhasil ditolak.");
    }
}

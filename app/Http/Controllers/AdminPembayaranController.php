<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Pembayaran;
use App\Models\Penyewaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminPembayaranController extends Controller
{
    /**
     * Tampilkan daftar pembayaran yang menunggu verifikasi beserta seluruh status transaksi
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status', 'Menunggu Verifikasi');
        $metodeFilter = $request->query('metode');
        $search = trim((string) $request->query('search', ''));

        // Query transaksi penyewaan yang sudah disetujui (siap bayar) atau memiliki record pembayaran
        $query = Penyewaan::with(['pelanggan', 'pembayaran.admin', 'detailPenyewaan.alat'])
            ->where(function ($q) {
                $q->whereIn('status_penyewaan', [
                    'Menunggu Pembayaran',
                    'Disetujui',
                    'Siap Diambil',
                    'Sedang Disewa',
                    'Selesai',
                ])->orWhereHas('pembayaran');
            });

        // Filter status pembayaran
        if ($statusFilter === 'Menunggu Verifikasi') {
            $query->whereHas('pembayaran', function ($q) {
                $q->where('status_pembayaran', 'Menunggu Verifikasi');
            });
        } elseif ($statusFilter === 'Belum Dibayar') {
            $query->where(function ($q) {
                $q->whereDoesntHave('pembayaran')
                    ->orWhereHas('pembayaran', function ($sub) {
                        $sub->where('status_pembayaran', 'Belum Dibayar');
                    });
            });
        } elseif ($statusFilter === 'Lunas') {
            $query->whereHas('pembayaran', function ($q) {
                $q->where('status_pembayaran', 'Lunas');
            });
        } elseif ($statusFilter === 'Ditolak') {
            $query->whereHas('pembayaran', function ($q) {
                $q->where('status_pembayaran', 'Ditolak');
            });
        }

        // Filter metode pembayaran
        if (! empty($metodeFilter) && in_array($metodeFilter, ['Transfer Bank', 'QRIS', 'Cash'], true)) {
            $query->whereHas('pembayaran', function ($q) use ($metodeFilter) {
                $q->where('metode_pembayaran', $metodeFilter);
            });
        }

        // Pencarian nomor transaksi atau nama pelanggan
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('no_transaksi', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($sub) use ($search) {
                        $sub->where('nama_pelanggan', 'like', "%{$search}%");
                    })
                    ->orWhereHas('pembayaran', function ($sub) use ($search) {
                        $sub->where('no_pembayaran', 'like', "%{$search}%");
                    });
            });
        }

        $transaksiList = $query->orderBy('tanggal_sewa', 'desc')->get();

        // Hitung statistik untuk badge tab filter
        $countMenunggu = Pembayaran::where('status_pembayaran', 'Menunggu Verifikasi')->count();
        $countLunas = Pembayaran::where('status_pembayaran', 'Lunas')->count();
        $countDitolak = Pembayaran::where('status_pembayaran', 'Ditolak')->count();

        return view('admin.pembayaran.index', [
            'transaksiList' => $transaksiList,
            'statusFilter' => $statusFilter,
            'metodeFilter' => $metodeFilter,
            'search' => $search,
            'countMenunggu' => $countMenunggu,
            'countLunas' => $countLunas,
            'countDitolak' => $countDitolak,
        ]);
    }

    /**
     * Tampilkan detail rincian transaksi dan pembayaran untuk pemeriksaan Admin
     */
    public function show(string $no_transaksi): View
    {
        $penyewaan = Penyewaan::with(['pelanggan', 'pembayaran.admin', 'detailPenyewaan.alat'])
            ->where('no_transaksi', $no_transaksi)
            ->firstOrFail();

        return view('admin.pembayaran.show', [
            'penyewaan' => $penyewaan,
        ]);
    }

    /**
     * Konfirmasi pembayaran oleh Admin (Mencegah konfirmasi ganda & memvalidasi pelunasan)
     */
    public function konfirmasi(Request $request, string $no_transaksi): RedirectResponse
    {
        $penyewaan = Penyewaan::with(['detailPenyewaan', 'pembayaran'])
            ->where('no_transaksi', $no_transaksi)
            ->firstOrFail();

        $request->validate([
            'jumlah_diterima' => ['required', 'numeric', 'min:0'],
            'metode_pembayaran' => ['nullable', 'string', 'in:Transfer Bank,QRIS,Cash'],
        ], [
            'jumlah_diterima.required' => 'Nominal pembayaran yang diterima wajib diisi.',
            'jumlah_diterima.numeric' => 'Nominal pembayaran harus berupa angka.',
            'jumlah_diterima.min' => 'Nominal pembayaran tidak boleh bernilai negatif.',
        ]);

        $totalBiaya = (float) $penyewaan->total_biaya;
        $jumlahDiterima = (float) $request->input('jumlah_diterima');

        // Requirement 11 & 12: Jika pembayaran parsial belum didukung, cegah pembayaran sebagian sebagai Lunas
        if ($jumlahDiterima < $totalBiaya) {
            return back()->withErrors([
                'jumlah_diterima' => 'Nominal yang diterima (Rp '.number_format($jumlahDiterima, 0, ',', '.').') kurang dari total tagihan (Rp '.number_format($totalBiaya, 0, ',', '.').'). Pembayaran parsial belum didukung, status belum dapat diubah menjadi Lunas.',
            ])->withInput();
        }

        try {
            DB::transaction(function () use ($penyewaan, $jumlahDiterima, $totalBiaya, $request) {
                $pembayaran = Pembayaran::where('no_transaksi', $penyewaan->no_transaksi)
                    ->lockForUpdate()
                    ->first();

                // Requirement 13: Cegah konfirmasi ganda yang menyebabkan nominal pembayaran tercatat dua kali
                if ($pembayaran && $pembayaran->status_pembayaran === 'Lunas') {
                    throw new \DomainException("Transaksi {$penyewaan->no_transaksi} sudah berstatus Lunas sebelumnya. Konfirmasi ganda dicegah.");
                }

                $metode = $request->input('metode_pembayaran')
                    ?? $pembayaran?->metode_pembayaran
                    ?? 'Transfer Bank';

                if (! $pembayaran) {
                    $pembayaran = new Pembayaran;
                    $pembayaran->no_pembayaran = Pembayaran::generateNoPembayaran();
                    $pembayaran->no_transaksi = $penyewaan->no_transaksi;
                }

                // Requirement 10: Catat Admin yang konfirmasi, waktu, metode, dan nominal yang diterima
                $pembayaran->total_biaya = $totalBiaya;
                $pembayaran->jumlah_pembayaran = $jumlahDiterima;
                $pembayaran->metode_pembayaran = $metode;
                $pembayaran->id_admin = Auth::id();
                $pembayaran->waktu_konfirmasi = now();
                $pembayaran->status_pembayaran = 'Lunas'; // Requirement 11
                $pembayaran->alasan_penolakan = null;
                $pembayaran->save();

                // Perbarui status penyewaan menjadi 'Siap Diambil' setelah pembayaran lunas
                if (in_array($penyewaan->status_penyewaan, ['Menunggu Pembayaran', 'Disetujui'], true)) {
                    $penyewaan->status_penyewaan = 'Siap Diambil';
                    $penyewaan->save();
                }

                // Requirement 14: Catat perubahan status dalam audit trail
                ActivityLog::record(
                    Auth::id(),
                    'Konfirmasi Pembayaran Lunas',
                    'Pembayaran',
                    "No Transaksi: {$penyewaan->no_transaksi}, No Pembayaran: {$pembayaran->no_pembayaran}, Metode: {$metode}, Nominal: Rp ".number_format($jumlahDiterima, 0, ',', '.').', Status: Lunas'
                );
            });
        } catch (\DomainException $e) {
            return redirect()->route('admin.pembayaran.index')->with(
                'error',
                $e->getMessage()
            );
        }

        return redirect()->route('admin.pembayaran.index')->with(
            'success',
            "Pembayaran untuk transaksi {$no_transaksi} berhasil diverifikasi dan dikonfirmasi Lunas."
        );
    }

    /**
     * Tolak bukti pembayaran yang tidak valid dengan alasan penolakan wajib
     */
    public function tolak(Request $request, string $no_transaksi): RedirectResponse
    {
        $penyewaan = Penyewaan::with('pembayaran')
            ->where('no_transaksi', $no_transaksi)
            ->firstOrFail();

        // Requirement 7: Wajibkan alasan ketika pembayaran ditolak
        $request->validate([
            'alasan_penolakan' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'alasan_penolakan.required' => 'Alasan penolakan bukti pembayaran wajib diisi.',
            'alasan_penolakan.min' => 'Alasan penolakan minimal terdiri dari 5 karakter.',
            'alasan_penolakan.max' => 'Alasan penolakan tidak boleh melebihi 500 karakter.',
        ]);

        $alasan = trim((string) $request->input('alasan_penolakan'));

        try {
            DB::transaction(function () use ($penyewaan, $alasan) {
                $pembayaran = Pembayaran::where('no_transaksi', $penyewaan->no_transaksi)
                    ->lockForUpdate()
                    ->first();

                if ($pembayaran && $pembayaran->status_pembayaran === 'Lunas') {
                    throw new \DomainException('Pembayaran yang sudah berstatus Lunas tidak dapat ditolak.');
                }

                if (! $pembayaran) {
                    $pembayaran = new Pembayaran;
                    $pembayaran->no_pembayaran = Pembayaran::generateNoPembayaran();
                    $pembayaran->no_transaksi = $penyewaan->no_transaksi;
                    $pembayaran->total_biaya = $penyewaan->total_biaya;
                    $pembayaran->jumlah_pembayaran = 0;
                    $pembayaran->metode_pembayaran = 'Transfer Bank';
                }

                $pembayaran->status_pembayaran = 'Ditolak';
                $pembayaran->alasan_penolakan = $alasan;
                $pembayaran->id_admin = Auth::id();
                $pembayaran->waktu_konfirmasi = now();
                $pembayaran->save();

                // Requirement 14: Catat perubahan status dalam audit trail
                ActivityLog::record(
                    Auth::id(),
                    'Penolakan Bukti Pembayaran',
                    'Pembayaran',
                    "No Transaksi: {$penyewaan->no_transaksi}, Alasan: {$alasan}, Admin ID: ".Auth::id()
                );
            });
        } catch (\DomainException $e) {
            return redirect()->route('admin.pembayaran.index')->with(
                'error',
                $e->getMessage()
            );
        }

        return redirect()->route('admin.pembayaran.index')->with(
            'success',
            "Bukti pembayaran untuk transaksi {$no_transaksi} telah ditolak dengan alasan: \"{$alasan}\"."
        );
    }
}

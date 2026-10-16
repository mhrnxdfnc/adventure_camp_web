<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Pembayaran;
use App\Models\Penyewaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PembayaranController extends Controller
{
    /**
     * Ambil dan validasi kepemilikan serta kelayakan status pesanan
     */
    protected function getApprovedPenyewaan(string $no_transaksi): Penyewaan
    {
        $penyewaan = Penyewaan::with(['detailPenyewaan.alat', 'pelanggan', 'pembayaran'])
            ->where('no_transaksi', $no_transaksi)
            ->firstOrFail();

        $user = Auth::user();

        // 1. Pencegahan IDOR: Pengguna hanya boleh melihat transaksi miliknya sendiri
        if (! $user->id_pelanggan || (int) $penyewaan->id_pelanggan !== (int) $user->id_pelanggan) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melihat data transaksi ini.');
        }

        // 2. Validasi status pesanan: hanya pesanan yang telah disetujui Admin yang dapat melihat instruksi pembayaran
        if (! $penyewaan->isSiapDibayar()) {
            abort(Response::HTTP_FORBIDDEN, 'Instruksi pembayaran hanya dapat diakses untuk pesanan yang telah disetujui oleh Admin.');
        }

        return $penyewaan;
    }

    /**
     * Halaman instruksi pembayaran QRIS
     */
    public function showQris(string $no_transaksi): View
    {
        $penyewaan = $this->getApprovedPenyewaan($no_transaksi);

        return view('pengguna.pembayaran.qris', [
            'penyewaan' => $penyewaan,
            'metodeAktif' => 'qris',
        ]);
    }

    /**
     * Halaman instruksi pembayaran Transfer Bank dan unggah bukti transfer
     */
    public function showTransfer(string $no_transaksi): View
    {
        $penyewaan = $this->getApprovedPenyewaan($no_transaksi);
        $bankConfig = config('payment.bank', []);

        return view('pengguna.pembayaran.transfer', [
            'penyewaan' => $penyewaan,
            'bankConfig' => $bankConfig,
            'metodeAktif' => 'transfer',
        ]);
    }

    /**
     * Halaman instruksi pembayaran Tunai / Cash di lokasi
     */
    public function showCash(string $no_transaksi): View
    {
        $penyewaan = $this->getApprovedPenyewaan($no_transaksi);

        return view('pengguna.pembayaran.cash', [
            'penyewaan' => $penyewaan,
            'metodeAktif' => 'cash',
        ]);
    }

    /**
     * Proses unggah bukti transfer bank secara aman dengan validasi tipe, ukuran, dan status
     */
    public function uploadBukti(Request $request, string $no_transaksi): RedirectResponse
    {
        $penyewaan = $this->getApprovedPenyewaan($no_transaksi);

        // Jika pembayaran sudah berstatus Lunas, tidak boleh unggah bukti lagi
        if ($penyewaan->pembayaran && $penyewaan->pembayaran->isLunas()) {
            return redirect()->route('pengguna.pembayaran.transfer', $no_transaksi)
                ->with('error', 'Pembayaran untuk transaksi ini telah diverifikasi Lunas.');
        }

        $request->validate([
            'bukti_transfer' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048', // 2MB
            ],
        ], [
            'bukti_transfer.required' => 'File bukti transfer wajib dipilih.',
            'bukti_transfer.file' => 'Bukti transfer harus berupa berkas file yang valid.',
            'bukti_transfer.mimes' => 'Format berkas bukti transfer harus berupa JPG, JPEG, PNG, atau PDF.',
            'bukti_transfer.max' => 'Ukuran berkas bukti transfer tidak boleh melebihi 2MB.',
        ]);

        // Simpan file secara aman di private disk storage (local)
        $uploadedFile = $request->file('bukti_transfer');
        $storedPath = $uploadedFile->store('bukti_pembayaran', 'local');

        DB::transaction(function () use ($penyewaan, $storedPath) {
            $pembayaran = Pembayaran::where('no_transaksi', $penyewaan->no_transaksi)
                ->lockForUpdate()
                ->first();

            // Hapus file fisik bukti transfer sebelumnya jika ada
            if ($pembayaran && $pembayaran->bukti_pembayaran && Storage::disk('local')->exists($pembayaran->bukti_pembayaran)) {
                Storage::disk('local')->delete($pembayaran->bukti_pembayaran);
            }

            if (! $pembayaran) {
                $pembayaran = new Pembayaran;
                $pembayaran->no_pembayaran = Pembayaran::generateNoPembayaran();
                $pembayaran->no_transaksi = $penyewaan->no_transaksi;
                $pembayaran->total_biaya = $penyewaan->total_biaya;
                $pembayaran->jumlah_pembayaran = 0; // Tidak otomatis lunas!
            }

            $pembayaran->metode_pembayaran = 'Transfer Bank';
            $pembayaran->bukti_pembayaran = $storedPath;
            $pembayaran->waktu_unggah_bukti = now();
            $pembayaran->status_pembayaran = 'Menunggu Verifikasi'; // Menunggu verifikasi admin
            $pembayaran->alasan_penolakan = null; // Reset alasan penolakan jika mengunggah ulang
            $pembayaran->save();

            ActivityLog::record(
                Auth::id() ?? $penyewaan->id_user,
                'Mengunggah Bukti Transfer',
                'Pembayaran',
                "No Transaksi: {$penyewaan->no_transaksi}, No Pembayaran: {$pembayaran->no_pembayaran}, Berkas: {$storedPath}"
            );
        });

        return redirect()->route('pengguna.pembayaran.transfer', $no_transaksi)
            ->with('success', 'Bukti transfer berhasil diunggah. Pembayaran Anda kini berstatus Menunggu Verifikasi oleh Admin.');
    }

    /**
     * Tampilkan berkas bukti transfer dengan proteksi hak akses ketat (Hanya Admin & Pemilik Transaksi)
     */
    public function lihatBukti(string $no_transaksi): Response
    {
        $penyewaan = Penyewaan::with('pembayaran')
            ->where('no_transaksi', $no_transaksi)
            ->firstOrFail();

        $user = Auth::user();

        // 1. Otorisasi akses berkas: Admin, Pemilik (pengawasan bisnis read-only), atau Pengguna pemilik pesanan
        $isCustomerOwner = $user->isPengguna() && (int) $user->id_pelanggan === (int) $penyewaan->id_pelanggan;
        $isStaffOrOwner = $user->isAdmin() || $user->isPemilik();

        if (! $isStaffOrOwner && ! $isCustomerOwner) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk melihat berkas bukti pembayaran ini.');
        }

        $pembayaran = $penyewaan->pembayaran;
        if (! $pembayaran || ! $pembayaran->bukti_pembayaran || ! Storage::disk('local')->exists($pembayaran->bukti_pembayaran)) {
            abort(Response::HTTP_NOT_FOUND, 'Berkas bukti pembayaran tidak ditemukan.');
        }

        return Storage::disk('local')->response($pembayaran->bukti_pembayaran);
    }
}

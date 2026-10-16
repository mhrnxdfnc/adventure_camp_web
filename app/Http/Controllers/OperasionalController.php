<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\Pengambilan;
use App\Models\Penyewaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OperasionalController extends Controller
{
    /**
     * Tampilkan daftar pesanan yang siap diambil (Pembayaran Lunas & Disetujui)
     */
    public function pengambilanIndex(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $query = Penyewaan::with(['pelanggan', 'pembayaran', 'detailPenyewaan.alat', 'pengambilan'])
            ->where(function ($q) {
                $q->where('status_penyewaan', 'Siap Diambil')
                    ->orWhere(function ($sub) {
                        $sub->where('status_penyewaan', 'Disetujui')
                            ->whereHas('pembayaran', function ($p) {
                                $p->where('status_pembayaran', 'Lunas');
                            });
                    });
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('no_transaksi', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($sub) use ($search) {
                        $sub->where('nama_pelanggan', 'like', "%{$search}%");
                    });
            });
        }

        $pesananList = $query->orderBy('tanggal_sewa', 'asc')->get();

        return view('admin.operasional.pengambilan', [
            'pesananList' => $pesananList,
            'search' => $search,
        ]);
    }

    /**
     * Proses serah terima / pengambilan alat camping kepada pelanggan
     */
    public function prosesPengambilan(Request $request, string $no_transaksi): RedirectResponse
    {
        $penyewaan = Penyewaan::with(['detailPenyewaan.alat', 'pembayaran', 'pengambilan'])
            ->where('no_transaksi', $no_transaksi)
            ->firstOrFail();

        // 1. Validasi pembayaran: Hanya pesanan dengan pembayaran Lunas yang boleh diserahkan
        if (! $penyewaan->pembayaran || ! $penyewaan->pembayaran->isLunas()) {
            return back()->with('error', 'Pembayaran belum Lunas. Alat hanya dapat diserahkan setelah pembayaran diverifikasi Lunas.');
        }

        // 2. Validasi status pesanan: Cegah penyerahan ganda
        if ($penyewaan->isSedangDisewa() || $penyewaan->isSelesai()) {
            return back()->with('error', "Pesanan {$no_transaksi} sudah dalam status {$penyewaan->status_penyewaan}, tidak dapat diserahkan ulang.");
        }

        $request->validate([
            'tanggal_pengambilan' => ['required', 'date'],
            'nama_penerima' => ['required', 'string', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array'],
            'items.*.jumlah_diserahkan' => ['required', 'integer', 'min:1'],
        ], [
            'tanggal_pengambilan.required' => 'Tanggal pengambilan alat wajib diisi.',
            'nama_penerima.required' => 'Nama penerima alat wajib diisi.',
            'items.required' => 'Rincian jumlah alat yang diserahkan wajib ada.',
        ]);

        try {
            DB::transaction(function () use ($penyewaan, $request) {
                $trx = Penyewaan::where('no_transaksi', $penyewaan->no_transaksi)
                    ->lockForUpdate()
                    ->first();

                if ($trx->isSedangDisewa() || $trx->isSelesai()) {
                    throw new \DomainException("Transaksi {$trx->no_transaksi} sudah diserahkan sebelumnya.");
                }

                // Catat tanggal serah terima & identitas penerima
                $pengambilan = Pengambilan::firstOrNew(['no_transaksi' => $trx->no_transaksi]);
                $pengambilan->tanggal_pengambilan = $request->input('tanggal_pengambilan');
                $pengambilan->id_admin = Auth::id();
                $pengambilan->nama_penerima = $request->input('nama_penerima');
                $pengambilan->catatan = $request->input('catatan');
                $pengambilan->save();

                // Catat jumlah alat yang benar-benar diserahkan per item
                $itemsInput = $request->input('items', []);
                foreach ($trx->detailPenyewaan as $detail) {
                    $submittedQty = isset($itemsInput[$detail->kode_alat]['jumlah_diserahkan'])
                        ? (int) $itemsInput[$detail->kode_alat]['jumlah_diserahkan']
                        : (int) $detail->jumlah;

                    $detail->jumlah_diserahkan = min($submittedQty, (int) $detail->jumlah);
                    $detail->save();

                    // Kurangi stok fisik alat saat serah terima
                    $alat = Alat::where('kode_alat', $detail->kode_alat)->lockForUpdate()->first();
                    if ($alat) {
                        $alat->stok = max(0, $alat->stok - $detail->jumlah_diserahkan);
                        $alat->save();
                    }
                }

                // Transisi status sah: Siap Diambil -> Sedang Disewa
                $trx->status_penyewaan = 'Sedang Disewa';
                $trx->save();

                // Catat ke audit trail
                ActivityLog::record(
                    Auth::id(),
                    'Menyerahkan Alat Camping (Pengambilan)',
                    'Pengambilan',
                    "No Transaksi: {$trx->no_transaksi}, Penerima: {$pengambilan->nama_penerima}, Tanggal: {$pengambilan->tanggal_pengambilan}"
                );
            });
        } catch (\DomainException $e) {
            return redirect()->route('admin.operasional.pengambilan')->with('error', $e->getMessage());
        }

        return redirect()->route('admin.operasional.pengambilan')->with(
            'success',
            "Alat untuk pesanan {$no_transaksi} berhasil diserahkan kepada {$request->input('nama_penerima')}. Status transaksi kini Sedang Disewa."
        );
    }
}

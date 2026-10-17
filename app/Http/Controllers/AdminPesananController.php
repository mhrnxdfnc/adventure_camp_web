<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\Pembayaran;
use App\Models\Penyewaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminPesananController extends Controller
{
    /**
     * Dapatkan teks instruksi pembayaran yang jelas dan informatif berdasarkan metode pembayaran.
     */
    public static function getInstruksiPembayaran(string $metode): string
    {
        return match ($metode) {
            'Cash' => 'Pelanggan memilih metode pembayaran Tunai (Cash). Arahkan pelanggan untuk melakukan pembayaran langsung kepada kasir atau Admin saat pengambilan alat.',
            'QRIS' => 'Pelanggan memilih metode QRIS. Silakan minta kode QRIS kepada kasir untuk melanjutkan pembayaran. Admin akan memverifikasi mutasi setelah pembayaran berhasil.',
            'Transfer Bank' => 'Pelanggan memilih Transfer Bank. Pelanggan dapat mentransfer ke rekening resmi toko (BCA / Mandiri) dan mengunggah bukti transfer untuk diverifikasi Admin di menu Verifikasi Pembayaran.',
            default => "Pelanggan dapat menyelesaikan pembayaran menggunakan metode {$metode}."
        };
    }

    /**
     * Tampilkan detail pesanan beserta analisis ketersediaan stok periode seluruh alat.
     */
    public function show(string $no_transaksi): View
    {
        $penyewaan = Penyewaan::with(['pelanggan', 'pembayaran', 'detailPenyewaan.alat'])
            ->where('no_transaksi', $no_transaksi)
            ->firstOrFail();

        $itemAvailability = [];
        $allAvailable = true;

        foreach ($penyewaan->detailPenyewaan as $detail) {
            $stokTersedia = Penyewaan::hitungStokTersediaPeriode(
                $detail->kode_alat,
                $penyewaan->tanggal_sewa,
                $penyewaan->tanggal_kembali,
                $penyewaan->no_transaksi
            );

            $isConditionGood = ($detail->alat?->kondisi === 'Baik');
            $isSufficient = ($stokTersedia >= $detail->jumlah) && $isConditionGood;

            if (! $isSufficient) {
                $allAvailable = false;
            }

            $itemAvailability[$detail->kode_alat] = [
                'stok_fisik' => (int) ($detail->alat?->stok ?? 0),
                'kondisi' => $detail->alat?->kondisi ?? '-',
                'stok_tersedia_periode' => $stokTersedia,
                'jumlah_diminta' => (int) $detail->jumlah,
                'is_available' => $isSufficient,
            ];
        }

        $metode = $penyewaan->pembayaran?->metode_pembayaran ?? 'Cash';
        $instruksiPembayaran = self::getInstruksiPembayaran($metode);

        return view('admin.pesanan.show', [
            'penyewaan' => $penyewaan,
            'itemAvailability' => $itemAvailability,
            'allAvailable' => $allAvailable,
            'instruksiPembayaran' => $instruksiPembayaran,
            'metodePembayaran' => $metode,
        ]);
    }

    /**
     * Setujui pengajuan pesanan penyewaan dari pelanggan dengan validasi stok periode & kunci transaksi aman.
     */
    public function setujui(Request $request, string $no_transaksi): RedirectResponse
    {
        try {
            $instruksi = '';
            $metodePembayaran = 'Cash';

            DB::transaction(function () use ($no_transaksi, &$instruksi, &$metodePembayaran) {
                // 1. Kunci baris transaksi dengan lockForUpdate untuk mencegah race condition & double submission
                $trx = Penyewaan::with(['pelanggan', 'pembayaran', 'detailPenyewaan'])
                    ->where('no_transaksi', $no_transaksi)
                    ->lockForUpdate()
                    ->firstOrFail();

                // 2. Validasi status pesanan di bawah kunci baris untuk idempotensi
                if ($trx->status_penyewaan !== 'Menunggu Konfirmasi') {
                    throw new \DomainException("Pesanan {$no_transaksi} tidak dapat disetujui karena sudah diproses sebelumnya (status saat ini: {$trx->status_penyewaan}).");
                }

                // 3. Kunci seluruh master alat yang dipesan secara terurut (alphabetical) untuk mencegah kebuntuan (deadlock)
                $kodeList = $trx->detailPenyewaan->pluck('kode_alat')->sort()->values();
                $alatModels = Alat::whereIn('kode_alat', $kodeList)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('kode_alat');

                // 4. Pemeriksaan ketersediaan stok fisik & kelayakan kondisi alat saat persetujuan
                foreach ($trx->detailPenyewaan as $detail) {
                    $alat = $alatModels->get($detail->kode_alat);

                    if (! $alat) {
                        throw new \DomainException("Persetujuan ditolak: Alat {$detail->kode_alat} tidak ditemukan dalam database.");
                    }

                    if ($alat->kondisi !== 'Baik') {
                        throw new \DomainException("Persetujuan ditolak: Alat [{$alat->kode_alat}] {$alat->nama_alat} dalam kondisi '{$alat->kondisi}' dan tidak layak disewakan.");
                    }

                    // Periksa ketersediaan stok periode sewa (mengecualikan transaksi saat ini)
                    $stokTersedia = Penyewaan::hitungStokTersediaPeriode(
                        $detail->kode_alat,
                        $trx->tanggal_sewa,
                        $trx->tanggal_kembali,
                        $trx->no_transaksi
                    );

                    if ($stokTersedia < $detail->jumlah) {
                        throw new \DomainException("Persetujuan ditolak: Stok alat [{$alat->kode_alat}] {$alat->nama_alat} tidak mencukupi untuk periode sewa yang diajukan (Tersedia: {$stokTersedia} unit, Diminta: {$detail->jumlah} unit).");
                    }
                }

                // 5. Ubah status pesanan menjadi Menunggu Pembayaran
                $trx->status_penyewaan = 'Menunggu Pembayaran';
                $trx->save();

                // 6. Siapkan instruksi pembayaran berdasarkan metode pilihan pelanggan
                $metodePembayaran = $trx->pembayaran?->metode_pembayaran ?? 'Cash';
                $instruksi = self::getInstruksiPembayaran($metodePembayaran);
                $namaPelanggan = $trx->pelanggan?->nama_pelanggan ?? 'ID Pelanggan: '.$trx->id_pelanggan;
                $sumber = $trx->sumber_pesanan ?? 'online';

                // 7. Catat persetujuan pesanan ke dalam audit trail
                ActivityLog::record(
                    Auth::id(),
                    'Persetujuan Pesanan',
                    'Penyewaan',
                    "No Transaksi: {$trx->no_transaksi}, Pelanggan: {$namaPelanggan}, Sumber: {$sumber}, Metode: {$metodePembayaran}, Status: Menunggu Pembayaran"
                );
            });

            // Flash instruksi pembayaran ke session untuk ditampilkan di antarmuka
            session()->flash('instruksi_pembayaran', $instruksi);
            session()->flash('metode_pembayaran', $metodePembayaran);
            session()->flash('transaksi_disetujui', $no_transaksi);

            return back()->with('success', "Pesanan {$no_transaksi} berhasil disetujui. {$instruksi}");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Tolak pengajuan pesanan penyewaan dari pelanggan dengan alasan penolakan wajib & kunci transaksi aman.
     */
    public function tolak(Request $request, string $no_transaksi): RedirectResponse
    {
        $request->validate([
            'alasan_penolakan' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'alasan_penolakan.required' => 'Alasan penolakan pesanan wajib diisi.',
            'alasan_penolakan.min' => 'Alasan penolakan minimal 5 karakter.',
            'alasan_penolakan.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $alasan = trim((string) $request->input('alasan_penolakan'));

        try {
            DB::transaction(function () use ($no_transaksi, $alasan) {
                // 1. Kunci baris transaksi dengan lockForUpdate untuk mencegah race condition & double rejection
                $trx = Penyewaan::with(['pelanggan', 'pembayaran'])
                    ->where('no_transaksi', $no_transaksi)
                    ->lockForUpdate()
                    ->firstOrFail();

                // 2. Validasi status pesanan di bawah kunci baris
                if ($trx->status_penyewaan !== 'Menunggu Konfirmasi') {
                    throw new \DomainException("Pesanan {$no_transaksi} tidak dapat ditolak karena sudah diproses sebelumnya (status saat ini: {$trx->status_penyewaan}).");
                }

                // 3. Ubah status pesanan menjadi Ditolak
                $trx->status_penyewaan = 'Ditolak';
                $trx->save();

                // 4. Simpan alasan penolakan ke record pembayaran terkait
                if ($trx->pembayaran) {
                    $trx->pembayaran->alasan_penolakan = $alasan;
                    $trx->pembayaran->id_admin = Auth::id();
                    $trx->pembayaran->waktu_konfirmasi = now();
                    $trx->pembayaran->save();
                } else {
                    Pembayaran::create([
                        'no_pembayaran' => Pembayaran::generateNoPembayaran(),
                        'no_transaksi' => $trx->no_transaksi,
                        'total_biaya' => $trx->total_biaya,
                        'jumlah_pembayaran' => 0,
                        'metode_pembayaran' => 'Cash',
                        'status_pembayaran' => 'Belum Dibayar',
                        'alasan_penolakan' => $alasan,
                        'id_admin' => Auth::id(),
                        'waktu_konfirmasi' => now(),
                    ]);
                }

                $sumber = $trx->sumber_pesanan ?? 'online';

                // 5. Catat penolakan pesanan ke dalam audit trail
                ActivityLog::record(
                    Auth::id(),
                    'Penolakan Pesanan',
                    'Penyewaan',
                    "No Transaksi: {$trx->no_transaksi}, Sumber: {$sumber}, Alasan: {$alasan}"
                );
            });

            return back()->with('success', "Pesanan {$no_transaksi} berhasil ditolak.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

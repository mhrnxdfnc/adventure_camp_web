<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\Denda;
use App\Models\Pengambilan;
use App\Models\Pengembalian;
use App\Models\Penyewaan;
use App\Models\RiwayatStok;
use Carbon\Carbon;
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

                if ($trx->isSedangDisewa() || $trx->isSelesai() || $trx->pengambilan()->exists()) {
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
                        $stokSebelum = (int) $alat->stok;
                        $stokSesudah = max(0, $alat->stok - $detail->jumlah_diserahkan);
                        $alat->stok = $stokSesudah;
                        $alat->save();

                        RiwayatStok::recordChange(
                            $detail->kode_alat,
                            'Serah Terima Sewa',
                            $stokSebelum,
                            -$detail->jumlah_diserahkan,
                            $stokSesudah,
                            "Penyerahan sewa pesanan {$trx->no_transaksi} kepada {$pengambilan->nama_penerima}",
                            Auth::id()
                        );
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

    /**
     * Tampilkan daftar pesanan yang sedang disewa dan perlu dikembalikan
     */
    public function pengembalianIndex(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $query = Penyewaan::with(['pelanggan', 'pembayaran', 'detailPenyewaan.alat', 'pengambilan', 'pengembalian.denda'])
            ->whereIn('status_penyewaan', ['Sedang Disewa', 'Berjalan']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('no_transaksi', 'like', "%{$search}%")
                    ->orWhereHas('pelanggan', function ($sub) use ($search) {
                        $sub->where('nama_pelanggan', 'like', "%{$search}%");
                    });
            });
        }

        $sewaAktifList = $query->orderBy('tanggal_kembali', 'asc')->get();

        return view('admin.operasional.pengembalian', [
            'sewaAktifList' => $sewaAktifList,
            'search' => $search,
        ]);
    }

    /**
     * Halaman formulir pemeriksaan fisik dan proses pengembalian alat
     */
    public function formPengembalian(string $no_transaksi): View
    {
        $penyewaan = Penyewaan::with(['pelanggan', 'pembayaran', 'detailPenyewaan.alat', 'pengambilan', 'pengembalian.denda'])
            ->where('no_transaksi', $no_transaksi)
            ->firstOrFail();

        if (! $penyewaan->isSedangDisewa()) {
            abort(400, "Transaksi {$no_transaksi} tidak dalam status Sedang Disewa (Status saat ini: {$penyewaan->status_penyewaan}).");
        }

        $tanggalRencanaKembali = Carbon::parse($penyewaan->tanggal_kembali)->startOfDay();
        $hariIni = Carbon::today();
        $hariTerlambat = $hariIni->gt($tanggalRencanaKembali) ? $tanggalRencanaKembali->diffInDays($hariIni) : 0;

        // Hitung total tarif sewa harian transaksi untuk dasar perhitungan denda keterlambatan
        $durasi = max(1, $penyewaan->durasi_hari);
        $totalTarifHarian = (float) $penyewaan->detailPenyewaan->sum(function ($item) {
            return (float) $item->jumlah * (float) $item->tarif_sewa;
        });

        $estimasiDendaTerlambat = $hariTerlambat * $totalTarifHarian;

        return view('admin.operasional.form_pengembalian', [
            'penyewaan' => $penyewaan,
            'hariTerlambat' => $hariTerlambat,
            'totalTarifHarian' => $totalTarifHarian,
            'estimasiDendaTerlambat' => $estimasiDendaTerlambat,
        ]);
    }

    /**
     * Proses pengembalian alat camping: verifikasi kondisi, pemisahan stok layak sewa vs rusak, dan update status
     */
    public function prosesPengembalian(Request $request, string $no_transaksi): RedirectResponse
    {
        $penyewaan = Penyewaan::with(['detailPenyewaan.alat', 'pengembalian.denda'])
            ->where('no_transaksi', $no_transaksi)
            ->firstOrFail();

        // 1. Validasi status pesanan: Harus Sedang Disewa / Berjalan
        if (! $penyewaan->isSedangDisewa()) {
            return back()->with('error', "Transaksi {$no_transaksi} tidak dapat diproses pengembaliannya karena berstatus {$penyewaan->status_penyewaan}.");
        }

        $request->validate([
            'tanggal_pengembalian_aktual' => ['required', 'date', 'after_or_equal:'.$penyewaan->tanggal_sewa->format('Y-m-d')],
            'items' => ['required', 'array'],
            'items.*.baik' => ['required', 'integer', 'min:0'],
            'items.*.perlu_perbaikan' => ['nullable', 'integer', 'min:0'],
            'items.*.rusak' => ['required', 'integer', 'min:0'],
            'nominal_denda' => ['nullable', 'numeric', 'min:0'],
            'alasan_denda' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'tanggal_pengembalian_aktual.required' => 'Tanggal pengembalian aktual wajib diisi.',
            'tanggal_pengembalian_aktual.after_or_equal' => 'Tanggal pengembalian tidak boleh sebelum tanggal sewa.',
        ]);

        $nominalDenda = (float) ($request->input('nominal_denda') ?? 0);
        $alasanDenda = trim((string) ($request->input('alasan_denda') ?? ''));

        // Requirement 8 & 9: Jika terdapat denda, alasan denda wajib diisi
        if ($nominalDenda > 0 && empty($alasanDenda)) {
            return back()->withErrors([
                'alasan_denda' => 'Alasan denda wajib diisi apabila terdapat nominal denda keterlambatan atau kerusakan.',
            ])->withInput();
        }

        try {
            DB::transaction(function () use ($penyewaan, $request, $nominalDenda, $alasanDenda) {
                $trx = Penyewaan::where('no_transaksi', $penyewaan->no_transaksi)
                    ->lockForUpdate()
                    ->first();

                // Cegah pengembalian dan denda ganda
                if (! $trx->isSedangDisewa() || $trx->isSelesai() || $trx->pengembalian()->exists()) {
                    throw new \DomainException("Transaksi {$trx->no_transaksi} sudah selesai dan telah dikembalikan sebelumnya.");
                }

                $itemsInput = $request->input('items', []);
                $ringkasanKondisi = [];
                $adaKerusakan = false;

                // Proses setiap item alat dan pembaruan stok sesuai kondisi aktual
                foreach ($trx->detailPenyewaan as $detail) {
                    $itemData = $itemsInput[$detail->kode_alat] ?? [];
                    $jumlahBaik = max(0, (int) ($itemData['baik'] ?? 0));
                    $jumlahPerbaikan = max(0, (int) ($itemData['perlu_perbaikan'] ?? 0));
                    $jumlahRusak = max(0, (int) ($itemData['rusak'] ?? 0));
                    $totalKembali = $jumlahBaik + $jumlahPerbaikan + $jumlahRusak;

                    $jumlahAcuan = $detail->jumlah_diserahkan > 0 ? $detail->jumlah_diserahkan : $detail->jumlah;

                    if ($totalKembali !== $jumlahAcuan) {
                        throw new \DomainException("Jumlah alat kembali untuk [{$detail->kode_alat}] ({$totalKembali} unit) harus sama dengan jumlah yang disewa ({$jumlahAcuan} unit).");
                    }

                    // Simpan kuantitas kondisi aktual ke detail_penyewaan
                    $detail->jumlah_kembali_baik = $jumlahBaik;
                    $detail->jumlah_perlu_perbaikan = $jumlahPerbaikan;
                    $detail->jumlah_rusak = $jumlahRusak;
                    $detail->save();

                    // Requirement 7 & 11: Hanya alat berkondisi BAIK yang dikembalikan ke stok siap sewa
                    $alat = Alat::where('kode_alat', $detail->kode_alat)->lockForUpdate()->first();
                    if ($alat) {
                        $stokSebelum = (int) $alat->stok;
                        if ($jumlahBaik > 0) {
                            $alat->stok = $alat->stok + $jumlahBaik;
                            $alat->save();

                            $rincianPerubahan = ["{$jumlahBaik} Baik"];
                            if ($jumlahPerbaikan > 0) {
                                $rincianPerubahan[] = "{$jumlahPerbaikan} Perlu Perbaikan";
                            }
                            if ($jumlahRusak > 0) {
                                $rincianPerubahan[] = "{$jumlahRusak} Rusak";
                            }

                            RiwayatStok::recordChange(
                                $detail->kode_alat,
                                'Pengembalian Sewa',
                                $stokSebelum,
                                +$jumlahBaik,
                                $alat->stok,
                                "Pengembalian pesanan {$trx->no_transaksi} (".implode(', ', $rincianPerubahan).')',
                                Auth::id()
                            );
                        }

                        // Unit rusak atau perlu perbaikan TIDAK ditambahkan ke stok siap sewa
                        if ($jumlahRusak > 0 || $jumlahPerbaikan > 0) {
                            $adaKerusakan = true;
                        }
                    }

                    $partRingkasan = [];
                    if ($jumlahBaik > 0) {
                        $partRingkasan[] = "{$jumlahBaik} Baik";
                    }
                    if ($jumlahPerbaikan > 0) {
                        $partRingkasan[] = "{$jumlahPerbaikan} Perlu Perbaikan";
                    }
                    if ($jumlahRusak > 0) {
                        $partRingkasan[] = "{$jumlahRusak} Rusak";
                    }
                    $ringkasanKondisi[] = "{$detail->kode_alat}: ".implode(', ', $partRingkasan);
                }

                $kondisiTeks = empty($ringkasanKondisi) ? 'Baik' : implode('; ', $ringkasanKondisi);
                $statusPengembalian = ($adaKerusakan || $nominalDenda > 0) ? 'Bermasalah' : 'Selesai';

                // Catat ke tabel pengembalian
                $pengembalian = new Pengembalian;
                $pengembalian->no_transaksi = $trx->no_transaksi;
                $pengembalian->tanggal_pengembalian_aktual = $request->input('tanggal_pengembalian_aktual');
                $pengembalian->kondisi_alat = $kondisiTeks;
                $pengembalian->status_pengembalian = $statusPengembalian;
                $pengembalian->id_admin = Auth::id();
                $pengembalian->catatan = $request->input('catatan');
                $pengembalian->save();

                // Requirement 8, 9, 10: Catat denda jika berlaku dan pastikan tidak ganda
                if ($nominalDenda > 0) {
                    $denda = new Denda;
                    $denda->id_pengembalian = $pengembalian->id_pengembalian;
                    $denda->tarif_denda = $nominalDenda;
                    $denda->nominal_denda = $nominalDenda;
                    $denda->alasan_denda = $alasanDenda;
                    $denda->save();

                    ActivityLog::record(
                        Auth::id(),
                        'Pencatatan Denda',
                        'Denda',
                        "No Transaksi: {$trx->no_transaksi}, Nominal: Rp ".number_format($nominalDenda, 0, ',', '.').", Alasan: {$alasanDenda}"
                    );
                }

                if ($adaKerusakan) {
                    ActivityLog::record(
                        Auth::id(),
                        'Perubahan Kondisi Alat',
                        'Inventaris',
                        "Perubahan kondisi alat pada pengembalian {$trx->no_transaksi}: {$kondisiTeks}"
                    );
                }

                // Ubah status pesanan menjadi Selesai
                $trx->status_penyewaan = 'Selesai';
                $trx->save();

                // Audit Trail
                ActivityLog::record(
                    Auth::id(),
                    'Memproses Pengembalian Alat',
                    'Pengembalian',
                    "No Transaksi: {$trx->no_transaksi}, Tanggal Kembali: {$pengembalian->tanggal_pengembalian_aktual}, Kondisi: {$kondisiTeks}, Denda: Rp ".number_format($nominalDenda, 0, ',', '.')
                );
            });
        } catch (\DomainException $e) {
            return redirect()->route('admin.operasional.pengembalian')->with('error', $e->getMessage());
        }

        return redirect()->route('admin.operasional.pengembalian')->with(
            'success',
            "Pengembalian alat untuk pesanan {$no_transaksi} berhasil diproses. Status transaksi kini Selesai."
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Penyewaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PenggunaPesananController extends Controller
{
    /**
     * Simpan pengajuan pesanan penyewaan alat camping dari Pengguna
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_alat' => ['required', 'string', 'exists:alat,kode_alat'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'tanggal_sewa' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_kembali' => ['required', 'date', 'after:tanggal_sewa'],
            'metode_pembayaran' => ['required', 'in:QRIS,Transfer Bank,Cash'],
        ], [
            'kode_alat.required' => 'Pilihan alat wajib ada.',
            'kode_alat.exists' => 'Alat camping yang dipilih tidak ditemukan.',
            'jumlah.required' => 'Jumlah sewa wajib diisi.',
            'jumlah.integer' => 'Jumlah sewa harus berupa bilangan bulat.',
            'jumlah.min' => 'Jumlah alat sewa minimal 1 unit.',
            'tanggal_sewa.required' => 'Tanggal mulai sewa wajib diisi.',
            'tanggal_sewa.after_or_equal' => 'Tanggal mulai sewa harus hari ini atau setelahnya.',
            'tanggal_kembali.required' => 'Tanggal pengembalian wajib diisi.',
            'tanggal_kembali.after' => 'Tanggal pengembalian harus sesudah tanggal sewa.',
            'metode_pembayaran.required' => 'Metode pembayaran wajib dipilih.',
            'metode_pembayaran.in' => 'Metode pembayaran harus berupa QRIS, Transfer Bank, atau Cash.',
        ]);

        $user = Auth::user();

        // Pastikan akun memiliki relasi pelanggan valid
        $idPelanggan = $user->id_pelanggan;
        if (! $idPelanggan) {
            $pelanggan = Pelanggan::create([
                'nama_pelanggan' => $user->nama_user,
                'alamat' => 'Malang',
                'nomor_telepon' => '081'.rand(10000000, 99999999),
                'created_at' => now(),
            ]);
            $user->id_pelanggan = $pelanggan->id_pelanggan;
            $user->save();
            $idPelanggan = $pelanggan->id_pelanggan;
        }

        try {
            $noTransaksi = DB::transaction(function () use ($validated, $user, $idPelanggan) {
                $alat = Alat::where('kode_alat', $validated['kode_alat'])
                    ->lockForUpdate()
                    ->firstOrFail();

                // Validasi kelayakan kondisi alat (Alat rusak tidak boleh disewa)
                if ($alat->kondisi !== 'Baik') {
                    throw new \DomainException("Alat {$alat->nama_alat} saat ini sedang tidak siap disewa (kondisi: {$alat->kondisi}).");
                }

                // Validasi ketersediaan stok fisik
                $jumlahDiminta = (int) $validated['jumlah'];
                if ($jumlahDiminta > (int) $alat->stok) {
                    throw new \DomainException("Stok alat {$alat->nama_alat} tidak mencukupi (tersedia: {$alat->stok} unit, diminta: {$jumlahDiminta} unit).");
                }

                // Kalkulasi durasi sewa di backend menggunakan satu sumber logika model
                $durasi = Penyewaan::hitungDurasiHari($validated['tanggal_sewa'], $validated['tanggal_kembali']);

                // Kalkulasi total biaya resmi dari tarif database: tarif × jumlah × durasi
                $tarifResmi = (float) $alat->tarif_sewa;
                $totalBiaya = Penyewaan::hitungTotalBiaya($tarifResmi, $jumlahDiminta, $durasi);

                // Buat nomor transaksi unik
                $noTrx = Penyewaan::generateNoTransaksi();

                // Simpan transaksi penyewaan (status awal: Menunggu Konfirmasi)
                Penyewaan::create([
                    'no_transaksi' => $noTrx,
                    'id_pelanggan' => $idPelanggan,
                    'id_user' => $user->id_user,
                    'tanggal_sewa' => $validated['tanggal_sewa'],
                    'tanggal_kembali' => $validated['tanggal_kembali'],
                    'status_penyewaan' => 'Menunggu Konfirmasi',
                ]);

                // Simpan detail alat yang disewa dengan tarif resmi database
                DetailPenyewaan::create([
                    'no_transaksi' => $noTrx,
                    'kode_alat' => $alat->kode_alat,
                    'jumlah' => $jumlahDiminta,
                    'tarif_sewa' => $tarifResmi,
                ]);

                // Buat data pembayaran awal (status awal: Belum Dibayar)
                $noPembayaran = Pembayaran::generateNoPembayaran();
                Pembayaran::create([
                    'no_pembayaran' => $noPembayaran,
                    'no_transaksi' => $noTrx,
                    'total_biaya' => $totalBiaya,
                    'jumlah_pembayaran' => 0,
                    'metode_pembayaran' => $validated['metode_pembayaran'],
                    'status_pembayaran' => 'Belum Dibayar',
                ]);

                // Catat ke audit trail
                ActivityLog::record(
                    $user->id_user,
                    'Mengajukan Pesanan Penyewaan',
                    'Penyewaan',
                    "No Transaksi: {$noTrx}, Alat: {$alat->nama_alat} ({$jumlahDiminta} unit), Durasi: {$durasi} hari, Estimasi: Rp ".number_format($totalBiaya, 0, ',', '.').", Metode: {$validated['metode_pembayaran']}"
                );

                return $noTrx;
            });
        } catch (\DomainException $e) {
            return back()->withErrors(['jumlah' => $e->getMessage()])->withInput();
        }

        return redirect()->route('pengguna.dashboard')->with(
            'success',
            "Pengajuan pesanan {$noTransaksi} berhasil dikirim! Menunggu konfirmasi dan pengecekan ketersediaan oleh Admin."
        );
    }
}

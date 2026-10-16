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
     * Simpan pengajuan pesanan penyewaan alat camping dari Pengguna (Mendukung multi-item keranjang belanja sekaligus)
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validasi header pesanan (tanggal sewa, tanggal kembali, metode pembayaran)
        $request->validate([
            'tanggal_sewa' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_kembali' => ['required', 'date', 'after:tanggal_sewa'],
            'metode_pembayaran' => ['required', 'in:QRIS,Transfer Bank,Cash'],
            'kode_alat' => ['nullable', 'string', 'exists:alat,kode_alat'],
            'jumlah' => ['nullable', 'integer', 'min:1'],
            'items' => ['nullable', 'array'],
            'items.*.kode_alat' => ['required_with:items', 'string', 'exists:alat,kode_alat'],
            'items.*.jumlah' => ['required_with:items', 'integer', 'min:1'],
        ], [
            'tanggal_sewa.required' => 'Tanggal mulai sewa wajib diisi.',
            'tanggal_sewa.after_or_equal' => 'Tanggal mulai sewa harus hari ini atau setelahnya.',
            'tanggal_kembali.required' => 'Tanggal pengembalian wajib diisi.',
            'tanggal_kembali.after' => 'Tanggal pengembalian harus sesudah tanggal sewa.',
            'metode_pembayaran.required' => 'Metode pembayaran wajib dipilih.',
            'metode_pembayaran.in' => 'Metode pembayaran harus berupa QRIS, Transfer Bank, atau Cash.',
            'kode_alat.exists' => 'Alat camping yang dipilih tidak ditemukan.',
            'jumlah.min' => 'Jumlah alat sewa minimal 1 unit.',
        ]);

        $user = Auth::user();

        // 2. Tentukan daftar item yang akan dipesan (Prioritas: single item langsung, items array, atau isi keranjang session)
        $orderItems = [];

        if ($request->filled('kode_alat')) {
            // Pemesanan langsung satuan
            $orderItems[] = [
                'kode_alat' => (string) $request->input('kode_alat'),
                'jumlah' => max(1, (int) $request->input('jumlah', 1)),
            ];
        } elseif ($request->filled('items') && is_array($request->input('items'))) {
            // Pemesanan daftar item eksplisit
            foreach ($request->input('items') as $rawItem) {
                if (! empty($rawItem['kode_alat']) && ! empty($rawItem['jumlah'])) {
                    $orderItems[] = [
                        'kode_alat' => (string) $rawItem['kode_alat'],
                        'jumlah' => max(1, (int) $rawItem['jumlah']),
                    ];
                }
            }
        } else {
            // Pemesanan dari Keranjang Belanja Pengguna
            $cart = KeranjangController::getCart($user->id_user);
            foreach ($cart as $cItem) {
                $orderItems[] = [
                    'kode_alat' => $cItem['kode_alat'],
                    'jumlah' => (int) $cItem['jumlah'],
                ];
            }
        }

        if (empty($orderItems)) {
            return back()->withErrors([
                'items' => 'Tidak ada alat camping yang dipilih dalam pesanan. Silakan tambahkan alat ke keranjang belanja.',
            ])->withInput();
        }

        // 3. Pastikan akun memiliki profil pelanggan
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

        $tanggalSewa = $request->input('tanggal_sewa');
        $tanggalKembali = $request->input('tanggal_kembali');
        $metodePembayaran = $request->input('metode_pembayaran');

        try {
            $noTransaksi = DB::transaction(function () use ($orderItems, $tanggalSewa, $tanggalKembali, $metodePembayaran, $user, $idPelanggan) {
                // Hitung durasi hari dari kedua tanggal
                $durasi = Penyewaan::hitungDurasiHari($tanggalSewa, $tanggalKembali);

                $validatedItems = [];
                $grandTotal = 0.0;
                $itemLogDescriptions = [];

                // Periksa setiap alat dalam transaksi: kondisi fisik, ketersediaan stok, dan tarif resmi database
                foreach ($orderItems as $item) {
                    $alat = Alat::where('kode_alat', $item['kode_alat'])
                        ->lockForUpdate()
                        ->first();

                    if (! $alat) {
                        throw new \DomainException("Alat camping dengan kode [{$item['kode_alat']}] tidak ditemukan.");
                    }

                    // Alat rusak tidak boleh disewa
                    if ($alat->kondisi !== 'Baik') {
                        throw new \DomainException("Alat {$alat->nama_alat} saat ini sedang tidak siap disewa (kondisi fisik: {$alat->kondisi}).");
                    }

                    $jumlah = (int) $item['jumlah'];
                    if ($jumlah > (int) $alat->stok) {
                        throw new \DomainException("Stok alat {$alat->nama_alat} tidak mencukupi (tersedia: {$alat->stok} unit, diminta: {$jumlah} unit).");
                    }

                    $tarifResmi = (float) $alat->tarif_sewa;
                    $subtotal = Penyewaan::hitungTotalBiaya($tarifResmi, $jumlah, $durasi);
                    $grandTotal += $subtotal;

                    $validatedItems[] = [
                        'alat' => $alat,
                        'kode_alat' => $alat->kode_alat,
                        'jumlah' => $jumlah,
                        'tarif_sewa' => $tarifResmi,
                        'subtotal' => $subtotal,
                    ];

                    $itemLogDescriptions[] = "{$alat->nama_alat} ({$jumlah} unit)";
                }

                // Buat 1 nomor transaksi penyewaan
                $noTrx = Penyewaan::generateNoTransaksi();

                // Simpan data transaksi penyewaan utama (header transaksi)
                Penyewaan::create([
                    'no_transaksi' => $noTrx,
                    'id_pelanggan' => $idPelanggan,
                    'id_user' => $user->id_user,
                    'tanggal_sewa' => $tanggalSewa,
                    'tanggal_kembali' => $tanggalKembali,
                    'status_penyewaan' => 'Menunggu Konfirmasi',
                ]);

                // Simpan seluruh rincian alat ke dalam detail_penyewaan
                foreach ($validatedItems as $vItem) {
                    DetailPenyewaan::create([
                        'no_transaksi' => $noTrx,
                        'kode_alat' => $vItem['kode_alat'],
                        'jumlah' => $vItem['jumlah'],
                        'tarif_sewa' => $vItem['tarif_sewa'],
                    ]);
                }

                // Buat 1 data pembayaran dengan total penjumlahan seluruh alat
                $noPembayaran = Pembayaran::generateNoPembayaran();
                Pembayaran::create([
                    'no_pembayaran' => $noPembayaran,
                    'no_transaksi' => $noTrx,
                    'total_biaya' => $grandTotal,
                    'jumlah_pembayaran' => 0,
                    'metode_pembayaran' => $metodePembayaran,
                    'status_pembayaran' => 'Belum Dibayar',
                ]);

                // Kosongkan keranjang belanja pengguna setelah checkout sukses
                KeranjangController::clear($user->id_user);

                // Catat ke audit trail
                $summaryItems = implode(', ', $itemLogDescriptions);
                ActivityLog::record(
                    $user->id_user,
                    'Mengajukan Pesanan Penyewaan',
                    'Penyewaan',
                    "No Transaksi: {$noTrx}, Rincian: {$summaryItems}, Durasi: {$durasi} hari, Total Tagihan: Rp ".number_format($grandTotal, 0, ',', '.').", Metode: {$metodePembayaran}"
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

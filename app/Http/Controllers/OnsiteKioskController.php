<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Penyewaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class OnsiteKioskController extends Controller
{
    protected const SESSION_CART_KEY = 'onsite_kiosk_cart';

    /**
     * Dapatkan data keranjang on-site dari sesi
     *
     * @return array<string, array{kode_alat: string, jumlah: int}>
     */
    public static function getCart(): array
    {
        return Session::get(self::SESSION_CART_KEY, []);
    }

    /**
     * Hitung total unit dalam keranjang on-site
     */
    public static function countCart(): int
    {
        $cart = self::getCart();
        $total = 0;
        foreach ($cart as $item) {
            $total += (int) ($item['jumlah'] ?? 0);
        }

        return $total;
    }

    /**
     * Kosongkan keranjang on-site
     */
    public static function clearCart(): void
    {
        Session::forget(self::SESSION_CART_KEY);
    }

    /**
     * Dapatkan rincian item keranjang on-site disinkronkan dengan data database terbaru
     *
     * @return array{items: array, total_units: int, total_subtotal_per_hari: float}
     */
    public static function getItemsWithDetails(): array
    {
        $cart = self::getCart();
        if (empty($cart)) {
            return [
                'items' => [],
                'total_units' => 0,
                'total_subtotal_per_hari' => 0.0,
            ];
        }

        $kodeList = array_keys($cart);
        $alatList = Alat::whereIn('kode_alat', $kodeList)->get()->keyBy('kode_alat');

        $items = [];
        $totalUnits = 0;
        $totalSubtotalPerHari = 0.0;
        $needsSync = false;

        foreach ($cart as $kode => $item) {
            $alat = $alatList->get($kode);

            // Validasi kelayakan alat
            if (! $alat || $alat->kondisi !== 'Baik' || $alat->stok <= 0) {
                unset($cart[$kode]);
                $needsSync = true;

                continue;
            }

            $jumlah = (int) $item['jumlah'];
            $stokTersedia = (int) $alat->stok;

            // Batasi agar tidak melebihi stok fisik
            if ($jumlah > $stokTersedia) {
                $jumlah = $stokTersedia;
                $cart[$kode]['jumlah'] = $jumlah;
                $needsSync = true;
            }

            if ($jumlah <= 0) {
                unset($cart[$kode]);
                $needsSync = true;

                continue;
            }

            $tarif = (float) $alat->tarif_sewa;
            $subtotalPerHari = $tarif * $jumlah;

            $items[] = [
                'kode_alat' => $alat->kode_alat,
                'nama_alat' => $alat->nama_alat,
                'kategori' => $alat->kategori,
                'stok' => $stokTersedia,
                'tarif_sewa' => $tarif,
                'jumlah' => $jumlah,
                'subtotal_per_hari' => $subtotalPerHari,
            ];

            $totalUnits += $jumlah;
            $totalSubtotalPerHari += $subtotalPerHari;
        }

        if ($needsSync) {
            Session::put(self::SESSION_CART_KEY, $cart);
        }

        return [
            'items' => $items,
            'total_units' => $totalUnits,
            'total_subtotal_per_hari' => $totalSubtotalPerHari,
        ];
    }

    /**
     * 1. Halaman Awal Sambutan Mode Tablet / Kiosk Kasir
     */
    public function landing(): View
    {
        $cartCount = self::countCart();

        return view('onsite.landing', compact('cartCount'));
    }

    /**
     * Memulai alur pesanan on-site baru
     */
    public function mulai(): RedirectResponse
    {
        // Kosongkan keranjang sebelumnya untuk sesi pelanggan baru
        self::clearCart();

        return redirect()->route('onsite.katalog');
    }

    /**
     * 2. Katalog Alat Camping Khusus Tablet On-Site
     */
    public function katalog(Request $request): View
    {
        $query = Alat::query();

        // Hanya tampilkan alat berstatus Baik dan stok > 0
        $query->where('kondisi', 'Baik')
            ->where('stok', '>', 0);

        // Filter pencarian
        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($sub) use ($search) {
                $sub->where('nama_alat', 'like', "%{$search}%")
                    ->orWhere('kode_alat', 'like', "%{$search}%");
            });
        }

        // Filter kategori
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $query->orderBy('kategori', 'asc')->orderBy('nama_alat', 'asc');
        $alatList = $query->paginate(9)->withQueryString();

        $kategoriList = Alat::where('kondisi', 'Baik')
            ->where('stok', '>', 0)
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori');

        $cartCount = self::countCart();

        return view('onsite.katalog', compact('alatList', 'kategoriList', 'cartCount'));
    }

    /**
     * Rincian detail alat camping pada mode tablet
     */
    public function detailAlat(string $kode_alat): View
    {
        $alat = Alat::where('kode_alat', $kode_alat)->firstOrFail();
        $cartCount = self::countCart();

        return view('onsite.show', compact('alat', 'cartCount'));
    }

    /**
     * 3. Tambahkan alat ke keranjang belanja on-site
     */
    public function tambahKeranjang(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'kode_alat' => ['required', 'string', 'exists:alat,kode_alat'],
            'jumlah' => ['required', 'integer', 'min:1'],
        ], [
            'jumlah.required' => 'Silakan tentukan jumlah alat yang ingin disewa.',
            'jumlah.min' => 'Jumlah alat sewa minimal 1 unit.',
        ]);

        $kodeAlat = (string) $request->input('kode_alat');
        $qty = (int) $request->input('jumlah');

        $alat = Alat::where('kode_alat', $kodeAlat)->first();
        if (! $alat || $alat->kondisi !== 'Baik' || $alat->stok <= 0) {
            $msg = 'Alat camping sedang tidak tersedia untuk disewa.';
            if ($request->expectsJson() || $request->isXmlHttpRequest()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $cart = self::getCart();
        $currentQty = isset($cart[$kodeAlat]) ? (int) $cart[$kodeAlat]['jumlah'] : 0;
        $newQty = $currentQty + $qty;

        if ($newQty > (int) $alat->stok) {
            $msg = "Stok alat {$alat->nama_alat} tidak mencukupi (tersedia: {$alat->stok} unit, sudah di keranjang: {$currentQty} unit).";
            if ($request->expectsJson() || $request->isXmlHttpRequest()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $cart[$kodeAlat] = [
            'kode_alat' => $kodeAlat,
            'jumlah' => $newQty,
        ];
        Session::put(self::SESSION_CART_KEY, $cart);

        $totalCartCount = self::countCart();
        $msg = "Alat {$alat->nama_alat} ({$qty} unit) berhasil ditambahkan ke keranjang!";

        if ($request->expectsJson() || $request->isXmlHttpRequest()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'cart_count' => $totalCartCount,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Perbarui jumlah item di keranjang on-site
     */
    public function updateKeranjang(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'kode_alat' => ['required', 'string'],
            'jumlah' => ['required', 'integer', 'min:0'],
        ]);

        $kodeAlat = (string) $request->input('kode_alat');
        $qty = (int) $request->input('jumlah');
        $cart = self::getCart();

        if ($qty <= 0) {
            unset($cart[$kodeAlat]);
            Session::put(self::SESSION_CART_KEY, $cart);
            $msg = 'Item berhasil dihapus dari keranjang.';
        } else {
            $alat = Alat::where('kode_alat', $kodeAlat)->first();
            if ($alat && $qty > (int) $alat->stok) {
                $qty = (int) $alat->stok;
            }
            $cart[$kodeAlat] = [
                'kode_alat' => $kodeAlat,
                'jumlah' => $qty,
            ];
            Session::put(self::SESSION_CART_KEY, $cart);
            $msg = 'Kuantitas berhasil diperbarui.';
        }

        if ($request->expectsJson() || $request->isXmlHttpRequest()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'cart_count' => self::countCart(),
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Hapus satu item dari keranjang on-site
     */
    public function hapusKeranjang(Request $request): RedirectResponse
    {
        $request->validate([
            'kode_alat' => ['required', 'string'],
        ]);

        $kodeAlat = (string) $request->input('kode_alat');
        $cart = self::getCart();
        unset($cart[$kodeAlat]);
        Session::put(self::SESSION_CART_KEY, $cart);

        return back()->with('success', 'Item berhasil dihapus dari keranjang.');
    }

    /**
     * Kosongkan seluruh keranjang on-site
     */
    public function kosongkanKeranjang(): RedirectResponse
    {
        self::clearCart();

        return redirect()->route('onsite.katalog')->with('info', 'Keranjang belanja on-site telah dikosongkan.');
    }

    /**
     * Halaman ringkasan keranjang on-site
     */
    public function keranjang(): View|RedirectResponse
    {
        $cartDetails = self::getItemsWithDetails();
        if (empty($cartDetails['items'])) {
            return redirect()->route('onsite.katalog')->with('info', 'Keranjang belanja Anda masih kosong. Silakan pilih alat terlebih dahulu.');
        }

        $cartCount = $cartDetails['total_units'];

        return view('onsite.keranjang', [
            'cartItems' => $cartDetails['items'],
            'totalUnits' => $cartDetails['total_units'],
            'totalSubtotalPerHari' => $cartDetails['total_subtotal_per_hari'],
            'cartCount' => $cartCount,
        ]);
    }

    /**
     * 4 & 5. Halaman Checkout On-Site & Form Identitas Tamu
     */
    public function checkout(): View|RedirectResponse
    {
        $cartDetails = self::getItemsWithDetails();
        if (empty($cartDetails['items'])) {
            return redirect()->route('onsite.katalog')->with('error', 'Keranjang belanja on-site masih kosong. Silakan pilih alat camping terlebih dahulu.');
        }

        $tanggalSewaDefault = date('Y-m-d');
        $tanggalKembaliDefault = date('Y-m-d', strtotime('+1 day'));
        $durasiDefault = Penyewaan::hitungDurasiHari($tanggalSewaDefault, $tanggalKembaliDefault);
        $cartCount = $cartDetails['total_units'];

        return view('onsite.checkout', [
            'cartItems' => $cartDetails['items'],
            'totalUnits' => $cartDetails['total_units'],
            'totalSubtotalPerHari' => $cartDetails['total_subtotal_per_hari'],
            'tanggalSewaDefault' => $tanggalSewaDefault,
            'tanggalKembaliDefault' => $tanggalKembaliDefault,
            'durasiDefault' => $durasiDefault,
            'cartCount' => $cartCount,
        ]);
    }

    /**
     * 6. Proses Penyimpanan Pesanan On-Site
     */
    public function prosesPesanan(Request $request): RedirectResponse
    {
        // Validasi identitas pelanggan on-site, periode sewa, dan metode pembayaran
        $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'nomor_telepon' => ['required', 'string', 'min:8', 'max:20'],
            'alamat' => ['required', 'string', 'max:255'],
            'tanggal_sewa' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_kembali' => ['required', 'date', 'after:tanggal_sewa'],
            'metode_pembayaran' => ['required', 'in:Cash,QRIS,Transfer Bank'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap pemesan wajib diisi.',
            'nomor_telepon.required' => 'Nomor telepon / WhatsApp wajib diisi.',
            'nomor_telepon.min' => 'Nomor telepon minimal 8 digit.',
            'alamat.required' => 'Alamat tempat tinggal / asal wajib diisi.',
            'tanggal_sewa.required' => 'Tanggal mulai sewa wajib diisi.',
            'tanggal_sewa.after_or_equal' => 'Tanggal mulai sewa harus hari ini atau setelahnya.',
            'tanggal_kembali.required' => 'Tanggal pengembalian wajib diisi.',
            'tanggal_kembali.after' => 'Tanggal pengembalian harus sesudah tanggal mulai sewa.',
            'metode_pembayaran.required' => 'Pilihan metode pembayaran wajib ditentukan.',
        ]);

        $cartDetails = self::getItemsWithDetails();
        if (empty($cartDetails['items'])) {
            return redirect()->route('onsite.katalog')->withErrors([
                'items' => 'Keranjang belanja on-site masih kosong. Silakan pilih alat terlebih dahulu.',
            ]);
        }

        $tanggalSewa = $request->input('tanggal_sewa');
        $tanggalKembali = $request->input('tanggal_kembali');
        $metodePembayaran = $request->input('metode_pembayaran');
        $namaLengkap = trim($request->input('nama_lengkap'));
        $nomorTelepon = trim($request->input('nomor_telepon'));
        $alamat = trim($request->input('alamat'));

        try {
            $noTransaksi = DB::transaction(function () use ($cartDetails, $tanggalSewa, $tanggalKembali, $metodePembayaran, $namaLengkap, $nomorTelepon, $alamat) {
                // 1. Registrasi / Sinkronisasi data Pelanggan toko
                $pelanggan = Pelanggan::where('nomor_telepon', $nomorTelepon)->first();
                if (! $pelanggan) {
                    $pelanggan = Pelanggan::create([
                        'nama_pelanggan' => $namaLengkap,
                        'nomor_telepon' => $nomorTelepon,
                        'alamat' => $alamat,
                        'created_at' => now(),
                    ]);
                } else {
                    $pelanggan->nama_pelanggan = $namaLengkap;
                    $pelanggan->alamat = $alamat;
                    $pelanggan->save();
                }

                $items = $cartDetails['items'];
                // Urutkan item berdasarkan kode_alat untuk mencegah deadlock database
                usort($items, fn ($a, $b) => strcmp($a['kode_alat'], $b['kode_alat']));

                $durasi = Penyewaan::hitungDurasiHari($tanggalSewa, $tanggalKembali);
                $validatedItems = [];
                $grandTotal = 0.0;
                $itemLogDescriptions = [];

                foreach ($items as $item) {
                    $alat = Alat::where('kode_alat', $item['kode_alat'])->lockForUpdate()->first();

                    if (! $alat) {
                        throw new \DomainException("Alat camping [{$item['kode_alat']}] tidak ditemukan.");
                    }

                    if ($alat->kondisi !== 'Baik') {
                        throw new \DomainException("Alat {$alat->nama_alat} saat ini sedang tidak siap sewa.");
                    }

                    $jumlah = (int) $item['jumlah'];
                    if ($jumlah <= 0) {
                        throw new \DomainException("Jumlah unit untuk alat {$alat->nama_alat} minimal 1 unit.");
                    }

                    // Validasi ketersediaan stok periode sewa
                    $stokTersedia = Penyewaan::hitungStokTersediaPeriode($alat->kode_alat, $tanggalSewa, $tanggalKembali);
                    if ($jumlah > $stokTersedia) {
                        throw new \DomainException("Stok alat {$alat->nama_alat} tidak mencukupi untuk periode tanggal yang dipilih (tersedia: {$stokTersedia} unit, diminta: {$jumlah} unit).");
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

                // 2. Buat satu nomor transaksi utama
                $noTrx = Penyewaan::generateNoTransaksi();

                // 3. Simpan transaksi utama dengan penanda sumber on-site dan id_user null
                Penyewaan::create([
                    'no_transaksi' => $noTrx,
                    'id_pelanggan' => $pelanggan->id_pelanggan,
                    'id_user' => null,
                    'tanggal_sewa' => $tanggalSewa,
                    'tanggal_kembali' => $tanggalKembali,
                    'status_penyewaan' => 'Menunggu Konfirmasi',
                    'sumber_pesanan' => 'on-site',
                ]);

                // 4. Simpan seluruh detail alat
                foreach ($validatedItems as $vItem) {
                    DetailPenyewaan::create([
                        'no_transaksi' => $noTrx,
                        'kode_alat' => $vItem['kode_alat'],
                        'jumlah' => $vItem['jumlah'],
                        'tarif_sewa' => $vItem['tarif_sewa'],
                    ]);
                }

                // 5. Buat data pembayaran awal
                $noPembayaran = Pembayaran::generateNoPembayaran();
                Pembayaran::create([
                    'no_pembayaran' => $noPembayaran,
                    'no_transaksi' => $noTrx,
                    'total_biaya' => $grandTotal,
                    'jumlah_pembayaran' => 0,
                    'metode_pembayaran' => $metodePembayaran,
                    'status_pembayaran' => 'Belum Dibayar',
                ]);

                // 6. Kosongkan keranjang on-site
                self::clearCart();

                // 7. Catat audit trail
                $summaryItems = implode(', ', $itemLogDescriptions);
                ActivityLog::record(
                    null,
                    'Pemesanan On-Site Tablet Kasir',
                    'Penyewaan',
                    "No Transaksi: {$noTrx}, Pelanggan On-Site: {$namaLengkap} ({$nomorTelepon}), Rincian: {$summaryItems}, Durasi: {$durasi} hari, Total: Rp ".number_format($grandTotal, 0, ',', '.').", Metode: {$metodePembayaran}"
                );

                return $noTrx;
            });
        } catch (\DomainException $e) {
            return back()->withErrors(['stok' => $e->getMessage()])->withInput();
        }

        return redirect()->route('onsite.sukses', $noTransaksi);
    }

    /**
     * 7. Halaman Sukses Pemesanan Tablet On-Site
     */
    public function sukses(string $no_transaksi): View
    {
        $penyewaan = Penyewaan::with(['pelanggan', 'detailPenyewaan.alat', 'pembayaran'])
            ->where('no_transaksi', $no_transaksi)
            ->firstOrFail();

        return view('onsite.sukses', compact('penyewaan'));
    }
}

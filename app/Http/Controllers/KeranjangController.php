<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Penyewaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class KeranjangController extends Controller
{
    /**
     * Dapatkan key session keranjang khusus untuk user yang sedang aktif
     */
    protected static function getSessionKey(?int $userId = null): string
    {
        $uid = $userId ?? Auth::id() ?? 0;

        return "cart_user_{$uid}";
    }

    /**
     * Ambil raw data keranjang belanja user dari session
     *
     * @return array<string, array{kode_alat: string, jumlah: int}>
     */
    public static function getCart(?int $userId = null): array
    {
        $key = self::getSessionKey($userId);

        return Session::get($key, []);
    }

    /**
     * Hitung total kuantitas unit seluruh barang di keranjang belanja user
     */
    public static function count(?int $userId = null): int
    {
        $cart = self::getCart($userId);
        $total = 0;
        foreach ($cart as $item) {
            $total += (int) ($item['jumlah'] ?? 0);
        }

        return $total;
    }

    /**
     * Hitung jumlah variasi jenis alat dalam keranjang
     */
    public static function countDistinct(?int $userId = null): int
    {
        return count(self::getCart($userId));
    }

    /**
     * Tambahkan alat ke keranjang belanja user
     *
     * @return array{success: bool, message: string, cart_count: int, item?: array}
     */
    public static function add(int $userId, string $kodeAlat, int $qty = 1): array
    {
        if ($qty <= 0) {
            return [
                'success' => false,
                'message' => 'Silakan tentukan jumlah sewa alat terlebih dahulu (minimal 1 unit).',
                'cart_count' => self::count($userId),
            ];
        }

        $alat = Alat::where('kode_alat', $kodeAlat)->first();

        if (! $alat) {
            return [
                'success' => false,
                'message' => 'Alat camping tidak ditemukan.',
                'cart_count' => self::count($userId),
            ];
        }

        if ($alat->kondisi !== 'Baik' || $alat->stok <= 0) {
            return [
                'success' => false,
                'message' => "Alat {$alat->nama_alat} saat ini sedang tidak siap sewa.",
                'cart_count' => self::count($userId),
            ];
        }

        $cart = self::getCart($userId);
        $currentQty = isset($cart[$kodeAlat]) ? (int) $cart[$kodeAlat]['jumlah'] : 0;
        $newQty = $currentQty + $qty;

        if ($newQty > (int) $alat->stok) {
            $sisaBisaDitambah = max(0, (int) $alat->stok - $currentQty);

            return [
                'success' => false,
                'message' => "Stok alat {$alat->nama_alat} tidak mencukupi. Tersedia: {$alat->stok} unit (sudah di keranjang: {$currentQty} unit).",
                'cart_count' => self::count($userId),
            ];
        }

        $cart[$kodeAlat] = [
            'kode_alat' => $kodeAlat,
            'jumlah' => $newQty,
        ];

        Session::put(self::getSessionKey($userId), $cart);

        return [
            'success' => true,
            'message' => "Alat {$alat->nama_alat} ({$qty} unit) berhasil ditambahkan ke keranjang!",
            'cart_count' => self::count($userId),
            'item' => [
                'kode_alat' => $kodeAlat,
                'nama_alat' => $alat->nama_alat,
                'jumlah' => $newQty,
            ],
        ];
    }

    /**
     * Ubah kuantitas alat tertentu di keranjang belanja
     */
    public static function updateItem(int $userId, string $kodeAlat, int $qty): array
    {
        $cart = self::getCart($userId);

        if (! isset($cart[$kodeAlat])) {
            return [
                'success' => false,
                'message' => 'Alat tidak ada di keranjang.',
                'cart_count' => self::count($userId),
            ];
        }

        if ($qty <= 0) {
            self::removeItem($userId, $kodeAlat);

            return [
                'success' => true,
                'message' => 'Barang berhasil dihapus dari keranjang.',
                'cart_count' => self::count($userId),
            ];
        }

        $alat = Alat::where('kode_alat', $kodeAlat)->first();
        if (! $alat || $alat->kondisi !== 'Baik') {
            self::removeItem($userId, $kodeAlat);

            return [
                'success' => false,
                'message' => 'Alat sedang tidak tersedia dan telah dihapus dari keranjang.',
                'cart_count' => self::count($userId),
            ];
        }

        if ($qty > (int) $alat->stok) {
            $cart[$kodeAlat]['jumlah'] = (int) $alat->stok;
            Session::put(self::getSessionKey($userId), $cart);

            return [
                'success' => false,
                'message' => "Jumlah disesuaikan ke stok maksimal yang tersedia ({$alat->stok} unit).",
                'cart_count' => self::count($userId),
            ];
        }

        $cart[$kodeAlat]['jumlah'] = $qty;
        Session::put(self::getSessionKey($userId), $cart);

        return [
            'success' => true,
            'message' => 'Jumlah alat berhasil diperbarui.',
            'cart_count' => self::count($userId),
        ];
    }

    /**
     * Hapus alat dari keranjang
     */
    public static function removeItem(int $userId, string $kodeAlat): void
    {
        $cart = self::getCart($userId);
        unset($cart[$kodeAlat]);
        Session::put(self::getSessionKey($userId), $cart);
    }

    /**
     * Kosongkan seluruh keranjang belanja user
     */
    public static function clear(?int $userId = null): void
    {
        Session::forget(self::getSessionKey($userId));
    }

    /**
     * Dapatkan rincian lengkap barang di keranjang disinkronkan dengan data database terbaru
     *
     * @return array{items: array, total_units: int, total_subtotal_per_hari: float}
     */
    public static function getItemsWithDetails(?int $userId = null): array
    {
        $cart = self::getCart($userId);

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

            // Jika alat sudah tidak ada atau kondisinya rusak, jangan masukkan
            if (! $alat || $alat->kondisi !== 'Baik' || $alat->stok <= 0) {
                unset($cart[$kode]);
                $needsSync = true;

                continue;
            }

            $jumlah = (int) $item['jumlah'];
            $stokTersedia = (int) $alat->stok;
            $tarifSewa = (float) $alat->tarif_sewa;
            $subtotalPerHari = $tarifSewa * $jumlah;

            $items[] = [
                'kode_alat' => $kode,
                'alat' => $alat,
                'nama_alat' => $alat->nama_alat,
                'kategori' => $alat->kategori,
                'stok' => $stokTersedia,
                'tarif_sewa' => $tarifSewa,
                'jumlah' => $jumlah,
                'stok_cukup' => ($jumlah <= $stokTersedia),
                'subtotal_per_hari' => $subtotalPerHari,
            ];

            $totalUnits += $jumlah;
            $totalSubtotalPerHari += $subtotalPerHari;
        }

        if ($needsSync) {
            Session::put(self::getSessionKey($userId), $cart);
        }

        return [
            'items' => $items,
            'total_units' => $totalUnits,
            'total_subtotal_per_hari' => $totalSubtotalPerHari,
        ];
    }

    /**
     * Tampilkan halaman keranjang belanja Pengguna
     */
    public function index(): View
    {
        $userId = (int) Auth::id();
        $cartDetails = self::getItemsWithDetails($userId);

        return view('pengguna.keranjang.index', [
            'cartItems' => $cartDetails['items'],
            'totalUnits' => $cartDetails['total_units'],
            'totalSubtotalPerHari' => $cartDetails['total_subtotal_per_hari'],
        ]);
    }

    /**
     * Handler untuk menambahkan alat ke keranjang
     */
    public function tambah(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'kode_alat' => ['required', 'string', 'exists:alat,kode_alat'],
            'jumlah' => ['required', 'integer', 'min:1'],
        ], [
            'jumlah.required' => 'Silakan tentukan jumlah sewa alat terlebih dahulu.',
            'jumlah.min' => 'Silakan tentukan jumlah sewa alat terlebih dahulu (minimal 1 unit).',
        ]);

        $userId = (int) Auth::id();
        $kodeAlat = (string) $request->input('kode_alat');
        $jumlah = (int) $request->input('jumlah');

        $result = self::add($userId, $kodeAlat, $jumlah);

        if ($request->expectsJson() || $request->isXmlHttpRequest()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Handler untuk memperbarui kuantitas alat di keranjang
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'kode_alat' => ['required', 'string'],
            'jumlah' => ['required', 'integer', 'min:0'],
        ]);

        $userId = (int) Auth::id();
        $kodeAlat = (string) $request->input('kode_alat');
        $jumlah = (int) $request->input('jumlah');

        $result = self::updateItem($userId, $kodeAlat, $jumlah);

        if ($request->expectsJson() || $request->isXmlHttpRequest()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Handler untuk menghapus satu item alat dari keranjang
     */
    public function hapus(Request $request): RedirectResponse
    {
        $request->validate([
            'kode_alat' => ['required', 'string'],
        ]);

        $userId = (int) Auth::id();
        $kodeAlat = (string) $request->input('kode_alat');

        self::removeItem($userId, $kodeAlat);

        return back()->with('success', 'Alat berhasil dihapus dari keranjang belanja.');
    }

    /**
     * Handler untuk mengosongkan seluruh isi keranjang
     */
    public function kosongkan(): RedirectResponse
    {
        $userId = (int) Auth::id();
        self::clear($userId);

        return redirect()->route('pengguna.keranjang.index')->with('info', 'Keranjang belanja telah dikosongkan.');
    }

    /**
     * Halaman Checkout Sekaligus untuk seluruh alat di keranjang
     */
    public function checkout(): View|RedirectResponse
    {
        $userId = (int) Auth::id();
        $cartDetails = self::getItemsWithDetails($userId);

        if (empty($cartDetails['items'])) {
            return redirect()->route('pengguna.katalog.index')->with(
                'error',
                'Keranjang belanja Anda masih kosong. Silakan pilih alat camping dari katalog terlebih dahulu.'
            );
        }

        $tanggalSewaDefault = date('Y-m-d');
        $tanggalKembaliDefault = date('Y-m-d', strtotime('+1 day'));
        $durasiDefault = Penyewaan::hitungDurasiHari($tanggalSewaDefault, $tanggalKembaliDefault);

        return view('pengguna.checkout.index', [
            'cartItems' => $cartDetails['items'],
            'totalUnits' => $cartDetails['total_units'],
            'totalSubtotalPerHari' => $cartDetails['total_subtotal_per_hari'],
            'tanggalSewaDefault' => $tanggalSewaDefault,
            'tanggalKembaliDefault' => $tanggalKembaliDefault,
            'durasiDefault' => $durasiDefault,
        ]);
    }
}

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

        $kapasitas = (int) $alat->total_kapasitas_layak;

        if ($alat->kondisi !== 'Baik' || $kapasitas <= 0) {
            return [
                'success' => false,
                'message' => "Alat {$alat->nama_alat} saat ini sedang tidak siap sewa.",
                'cart_count' => self::count($userId),
            ];
        }

        $cart = self::getCart($userId);
        $currentQty = isset($cart[$kodeAlat]) ? (int) $cart[$kodeAlat]['jumlah'] : 0;
        $newQty = $currentQty + $qty;

        if ($newQty > $kapasitas) {
            return [
                'success' => false,
                'message' => "Stok alat {$alat->nama_alat} tidak mencukupi. Maksimal unit yang tersedia: {$kapasitas} unit (sudah di keranjang: {$currentQty} unit).",
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

        $kapasitas = (int) $alat->total_kapasitas_layak;
        if ($qty > $kapasitas) {
            $cart[$kodeAlat]['jumlah'] = $kapasitas;
            Session::put(self::getSessionKey($userId), $cart);

            return [
                'success' => false,
                'message' => "Jumlah disesuaikan ke stok maksimal yang tersedia ({$kapasitas} unit).",
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
     * @return array{items: array, total_units: int, total_subtotal_per_hari: float, has_stok_issue: bool, removed_items: array}
     */
    public static function getItemsWithDetails(?int $userId = null): array
    {
        $cart = self::getCart($userId);

        if (empty($cart)) {
            return [
                'items' => [],
                'total_units' => 0,
                'total_subtotal_per_hari' => 0.0,
                'has_stok_issue' => false,
                'removed_items' => [],
            ];
        }

        $kodeList = array_keys($cart);
        $alatList = Alat::whereIn('kode_alat', $kodeList)->get()->keyBy('kode_alat');

        $items = [];
        $totalUnits = 0;
        $totalSubtotalPerHari = 0.0;
        $needsSync = false;
        $removedItems = [];
        $hasStokIssue = false;

        foreach ($cart as $kode => $item) {
            $alat = $alatList->get($kode);

            // Jika alat sudah tidak ada atau kondisinya rusak atau kapasitas 0, jangan masukkan
            $kapasitas = (int) $alat?->total_kapasitas_layak;
            if (! $alat || $alat->kondisi !== 'Baik' || $kapasitas <= 0) {
                unset($cart[$kode]);
                $needsSync = true;
                $removedItems[] = $alat ? $alat->nama_alat : $kode;

                continue;
            }

            $jumlah = (int) $item['jumlah'];
            $stokToko = (int) $alat->stok;
            $tarifSewa = (float) $alat->tarif_sewa;
            $subtotalPerHari = $tarifSewa * $jumlah;
            $stokCukup = ($jumlah <= $kapasitas);

            if (! $stokCukup) {
                $hasStokIssue = true;
            }

            $items[] = [
                'kode_alat' => $kode,
                'alat' => $alat,
                'nama_alat' => $alat->nama_alat,
                'kategori' => $alat->kategori,
                'stok' => $stokToko,
                'kapasitas' => $kapasitas,
                'tarif_sewa' => $tarifSewa,
                'jumlah' => $jumlah,
                'stok_cukup' => $stokCukup,
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
            'has_stok_issue' => $hasStokIssue,
            'removed_items' => $removedItems,
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
            'hasStokIssue' => $cartDetails['has_stok_issue'],
            'removedItems' => $cartDetails['removed_items'],
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

        // Cegah checkout jika ada alat yang melebihi stok yang tersedia
        foreach ($cartDetails['items'] as $item) {
            if (! $item['stok_cukup']) {
                return redirect()->route('pengguna.keranjang.index')->with(
                    'error',
                    "Jumlah unit alat {$item['nama_alat']} ({$item['jumlah']} unit) melebihi stok yang tersedia ({$item['stok']} unit). Harap sesuaikan jumlah unit di keranjang sebelum checkout."
                );
            }
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

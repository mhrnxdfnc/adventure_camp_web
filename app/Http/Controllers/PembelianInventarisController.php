<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alat;
use App\Models\DetailPembelianInventaris;
use App\Models\PembelianInventaris;
use App\Models\RiwayatStok;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PembelianInventarisController extends Controller
{
    /**
     * Tampilkan daftar transaksi pembelian stok inventaris dari supplier
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $supplierFilter = $request->query('id_supplier');

        $query = PembelianInventaris::with(['supplier', 'admin', 'detailPembelian.alat']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('no_pembelian', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sub) use ($search) {
                        $sub->where('nama_supplier', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($supplierFilter)) {
            $query->where('id_supplier', $supplierFilter);
        }

        $pembelianList = $query->orderBy('tanggal_pembelian', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $supplierList = Supplier::orderBy('nama_supplier')->get();

        return view('admin.inventaris.pembelian.index', [
            'pembelianList' => $pembelianList,
            'supplierList' => $supplierList,
            'search' => $search,
            'supplierFilter' => $supplierFilter,
        ]);
    }

    /**
     * Formulir pencatatan pengadaan stok baru dari supplier
     */
    public function create(Request $request): View
    {
        $todayStr = date('Ymd');
        $countToday = PembelianInventaris::whereDate('created_at', today())->count() + 1;
        $suggestedNo = 'PO-'.$todayStr.'-'.str_pad((string) $countToday, 3, '0', STR_PAD_LEFT);

        $supplierList = Supplier::orderBy('nama_supplier')->get();
        $alatList = Alat::orderBy('kategori')->orderBy('nama_alat')->get();
        $selectedKodeAlat = trim((string) $request->query('kode_alat', ''));

        return view('admin.inventaris.pembelian.create', [
            'suggestedNo' => $suggestedNo,
            'supplierList' => $supplierList,
            'alatList' => $alatList,
            'selectedKodeAlat' => $selectedKodeAlat,
        ]);
    }

    /**
     * Simpan transaksi pembelian supplier, perbarui stok fisik alat, dan catat riwayat stok
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'no_pembelian' => ['required', 'string', 'max:30', 'unique:pembelian_inventaris,no_pembelian'],
            'tanggal_pembelian' => ['required', 'date'],
            'id_supplier' => ['nullable', 'integer'],
            'nama_supplier_baru' => ['nullable', 'string', 'max:100'],
            'telepon_supplier' => ['nullable', 'string', 'max:25'],
            'alamat_supplier' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.kode_alat' => ['required', 'string', 'exists:alat,kode_alat'],
            'items.*.kuantitas' => ['required', 'integer', 'min:1'],
            'items.*.harga_beli_satuan' => ['required', 'numeric', 'min:0'],
        ], [
            'no_pembelian.required' => 'Nomor pembelian wajib diisi.',
            'no_pembelian.unique' => 'Nomor pembelian sudah pernah dicatat sebelumnya.',
            'tanggal_pembelian.required' => 'Tanggal pembelian wajib diisi.',
            'items.required' => 'Minimal satu item alat harus ditambahkan.',
            'items.min' => 'Minimal satu item alat harus ditambahkan.',
        ]);

        // Validasi supplier: harus pilih yang ada atau isi nama supplier baru
        $idSupplier = $request->input('id_supplier');
        $namaSupplierBaru = trim((string) $request->input('nama_supplier_baru', ''));

        if (empty($idSupplier) && empty($namaSupplierBaru)) {
            return back()->withErrors(['id_supplier' => 'Pilih supplier yang ada atau masukkan nama supplier baru.'])->withInput();
        }

        try {
            DB::transaction(function () use ($request, $idSupplier, $namaSupplierBaru) {
                // Buat supplier baru jika belum ada
                if (empty($idSupplier) && ! empty($namaSupplierBaru)) {
                    $supplier = Supplier::create([
                        'nama_supplier' => $namaSupplierBaru,
                        'telepon' => $request->input('telepon_supplier'),
                        'alamat' => $request->input('alamat_supplier'),
                        'created_at' => now(),
                    ]);
                    $finalSupplierId = $supplier->id_supplier;
                    $finalSupplierName = $supplier->nama_supplier;
                } else {
                    $supplier = Supplier::findOrFail($idSupplier);
                    $finalSupplierId = $supplier->id_supplier;
                    $finalSupplierName = $supplier->nama_supplier;
                }

                $noPembelian = trim((string) $request->input('no_pembelian'));
                $items = $request->input('items', []);
                $totalPembelian = 0;

                // Simpan header pembelian
                $pembelian = new PembelianInventaris;
                $pembelian->no_pembelian = $noPembelian;
                $pembelian->tanggal_pembelian = $request->input('tanggal_pembelian');
                $pembelian->id_supplier = $finalSupplierId;
                $pembelian->total_pembelian = 0;
                $pembelian->id_admin = Auth::id();
                $pembelian->catatan = $request->input('catatan');
                $pembelian->save();

                // Proses setiap detail item alat
                foreach ($items as $item) {
                    $kodeAlat = $item['kode_alat'];
                    $qty = (int) $item['kuantitas'];
                    $hargaSatuan = (float) $item['harga_beli_satuan'];
                    $subtotal = $qty * $hargaSatuan;
                    $totalPembelian += $subtotal;

                    // Detail pembelian
                    $detail = new DetailPembelianInventaris;
                    $detail->no_pembelian = $noPembelian;
                    $detail->kode_alat = $kodeAlat;
                    $detail->kuantitas = $qty;
                    $detail->harga_beli_satuan = $hargaSatuan;
                    $detail->subtotal = $subtotal;
                    $detail->save();

                    // Tambahkan stok fisik alat di toko
                    $alat = Alat::where('kode_alat', $kodeAlat)->lockForUpdate()->firstOrFail();
                    $stokSebelum = (int) $alat->stok;
                    $stokSesudah = $stokSebelum + $qty;
                    $alat->stok = $stokSesudah;
                    $alat->save();

                    // Catat riwayat mutasi stok
                    RiwayatStok::recordChange(
                        $alat->kode_alat,
                        'Pembelian Supplier',
                        $stokSebelum,
                        +$qty,
                        $stokSesudah,
                        "Pengadaan stok {$qty} unit via {$noPembelian} dari {$finalSupplierName}",
                        Auth::id()
                    );
                }

                // Update total pembelian pada header
                $pembelian->total_pembelian = $totalPembelian;
                $pembelian->save();

                // Audit trail
                ActivityLog::record(
                    Auth::id(),
                    'Mencatat Pembelian Inventaris Supplier',
                    'Inventaris',
                    "No PO: {$noPembelian}, Supplier: {$finalSupplierName}, Total: Rp ".number_format($totalPembelian, 0, ',', '.')
                );
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mencatat pembelian: '.$e->getMessage())->withInput();
        }

        return redirect()->route('admin.inventaris.pembelian.index')->with(
            'success',
            "Transaksi pembelian stok supplier {$request->input('no_pembelian')} berhasil dicatat dan stok fisik telah ditambahkan."
        );
    }

    /**
     * Tampilkan detail rincian transaksi pembelian stok dari supplier
     */
    public function show(string $no_pembelian): View
    {
        $pembelian = PembelianInventaris::with(['supplier', 'admin', 'detailPembelian.alat'])
            ->where('no_pembelian', $no_pembelian)
            ->firstOrFail();

        return view('admin.inventaris.pembelian.show', [
            'pembelian' => $pembelian,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupplierController extends Controller
{
    /**
     * Tampilkan daftar supplier dengan paginasi dan pencarian
     */
    public function index(Request $request): View
    {
        $query = Supplier::query()->withCount('pembelian');

        // Pencarian berdasarkan nama supplier, kontak person, telepon, atau alamat
        if ($request->filled('q')) {
            $search = trim((string) $request->q);
            $query->where(function ($sub) use ($search) {
                $sub->where('nama_supplier', 'like', "%{$search}%")
                    ->orWhere('kontak_person', 'like', "%{$search}%")
                    ->orWhere('telepon', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        $query->orderBy('nama_supplier', 'asc');
        $suppliers = $query->paginate(10)->withQueryString();

        // Metrik statistik supplier
        $totalSupplier = Supplier::count();
        $supplierWithPurchasesCount = Supplier::has('pembelian')->count();

        return view('admin.supplier.index', [
            'suppliers' => $suppliers,
            'search' => $request->query('q', ''),
            'totalSupplier' => $totalSupplier,
            'supplierWithPurchasesCount' => $supplierWithPurchasesCount,
        ]);
    }

    /**
     * Tampilkan formulir penambahan supplier baru
     */
    public function create(): View
    {
        return view('admin.supplier.create');
    }

    /**
     * Simpan data supplier baru ke database
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_supplier' => ['required', 'string', 'max:100'],
            'kontak_person' => ['nullable', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:25'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ], [
            'nama_supplier.required' => 'Nama supplier / vendor wajib diisi.',
            'nama_supplier.max' => 'Nama supplier tidak boleh lebih dari 100 karakter.',
            'kontak_person.max' => 'Nama kontak person tidak boleh lebih dari 100 karakter.',
            'telepon.max' => 'Nomor telepon tidak boleh lebih dari 25 karakter.',
            'alamat.max' => 'Alamat tidak boleh lebih dari 500 karakter.',
        ]);

        $supplier = Supplier::create([
            'nama_supplier' => trim($validated['nama_supplier']),
            'kontak_person' => ! empty($validated['kontak_person']) ? trim($validated['kontak_person']) : null,
            'telepon' => ! empty($validated['telepon']) ? trim($validated['telepon']) : null,
            'alamat' => ! empty($validated['alamat']) ? trim($validated['alamat']) : null,
            'created_at' => now(),
        ]);

        ActivityLog::record(
            Auth::id() ?? 1,
            'Tambah Supplier',
            'Supplier',
            "Menambahkan master supplier baru: [ID: {$supplier->id_supplier}] {$supplier->nama_supplier}"
        );

        return redirect()->route('admin.supplier.index')->with(
            'success',
            "Supplier [{$supplier->nama_supplier}] berhasil ditambahkan."
        );
    }

    /**
     * Tampilkan detail supplier beserta riwayat pengadaan inventaris
     */
    public function show(Supplier $supplier): View
    {
        $supplier->load(['pembelian' => function ($query) {
            $query->with(['admin', 'detailPembelian.alat'])->orderBy('tanggal_pembelian', 'desc')->orderBy('created_at', 'desc');
        }]);

        $totalPembelianCount = $supplier->pembelian->count();
        $totalPengeluaran = (float) $supplier->pembelian->sum('total_pembelian');

        return view('admin.supplier.show', [
            'supplier' => $supplier,
            'totalPembelianCount' => $totalPembelianCount,
            'totalPengeluaran' => $totalPengeluaran,
        ]);
    }

    /**
     * Tampilkan formulir edit data supplier
     */
    public function edit(Supplier $supplier): View
    {
        return view('admin.supplier.edit', [
            'supplier' => $supplier,
        ]);
    }

    /**
     * Perbarui data supplier
     */
    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'nama_supplier' => ['required', 'string', 'max:100'],
            'kontak_person' => ['nullable', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:25'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ], [
            'nama_supplier.required' => 'Nama supplier / vendor wajib diisi.',
            'nama_supplier.max' => 'Nama supplier tidak boleh lebih dari 100 karakter.',
            'kontak_person.max' => 'Nama kontak person tidak boleh lebih dari 100 karakter.',
            'telepon.max' => 'Nomor telepon tidak boleh lebih dari 25 karakter.',
            'alamat.max' => 'Alamat tidak boleh lebih dari 500 karakter.',
        ]);

        $supplier->update([
            'nama_supplier' => trim($validated['nama_supplier']),
            'kontak_person' => ! empty($validated['kontak_person']) ? trim($validated['kontak_person']) : null,
            'telepon' => ! empty($validated['telepon']) ? trim($validated['telepon']) : null,
            'alamat' => ! empty($validated['alamat']) ? trim($validated['alamat']) : null,
        ]);

        ActivityLog::record(
            Auth::id() ?? 1,
            'Perbarui Supplier',
            'Supplier',
            "Memperbarui data supplier: [ID: {$supplier->id_supplier}] {$supplier->nama_supplier}"
        );

        return redirect()->route('admin.supplier.index')->with(
            'success',
            "Data supplier [{$supplier->nama_supplier}] berhasil diperbarui."
        );
    }

    /**
     * Hapus data supplier dengan proteksi foreign key riwayat transaksi
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        // Proteksi integritas data historis: Dilarang menghapus supplier yang memiliki riwayat PO pembelian
        $purchaseCount = $supplier->pembelian()->count();
        if ($purchaseCount > 0) {
            return back()->with(
                'error',
                "Supplier [{$supplier->nama_supplier}] TIDAK DAPAT DIHAPUS karena memiliki {$purchaseCount} riwayat transaksi pengadaan pembelian stok inventaris. Data historis dan relasi keuangan dilindungi."
            );
        }

        $namaSupplier = $supplier->nama_supplier;
        $idSupplier = $supplier->id_supplier;
        $supplier->delete();

        ActivityLog::record(
            Auth::id() ?? 1,
            'Hapus Supplier',
            'Supplier',
            "Menghapus master supplier: [ID: {$idSupplier}] {$namaSupplier}"
        );

        return redirect()->route('admin.supplier.index')->with(
            'success',
            "Supplier [{$namaSupplier}] berhasil dihapus dari sistem."
        );
    }
}

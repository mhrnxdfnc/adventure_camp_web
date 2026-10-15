<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KatalogController extends Controller
{
    /**
     * Tampilkan katalog alat camping siap sewa untuk Pengguna
     */
    public function index(Request $request): View
    {
        $query = Alat::query();

        // Aturan bisnis utama: Hanya tampilkan alat berstatus Baik dan stok > 0
        $query->where('kondisi', 'Baik')
              ->where('stok', '>', 0);

        // Filter pencarian berdasarkan nama alat atau kode alat
        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($sub) use ($search) {
                $sub->where('nama_alat', 'like', "%{$search}%")
                    ->orWhere('kode_alat', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan kategori
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Urutkan berdasarkan kategori dan nama alat
        $query->orderBy('kategori', 'asc')->orderBy('nama_alat', 'asc');

        $alatList = $query->paginate(9)->withQueryString();

        // Daftar kategori unik yang memiliki stok siap sewa
        $kategoriList = Alat::where('kondisi', 'Baik')
            ->where('stok', '>', 0)
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori');

        return view('pengguna.katalog.index', compact('alatList', 'kategoriList'));
    }

    /**
     * Tampilkan rincian detail alat camping untuk Pengguna
     */
    public function show(string $kode_alat): View
    {
        $alat = Alat::where('kode_alat', $kode_alat)->firstOrFail();

        return view('pengguna.katalog.show', compact('alat'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Alat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AlatController extends Controller
{
    /**
     * Tampilkan daftar inventaris master alat untuk Admin
     */
    public function index(Request $request): View
    {
        $query = Alat::query();

        // Pencarian berdasarkan kode atau nama alat
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

        // Filter kondisi fisik
        if ($request->filled('kondisi')) {
            $query->where('kondisi', $request->kondisi);
        }

        $query->orderBy('kategori', 'asc')->orderBy('kode_alat', 'asc');

        $alatList = $query->paginate(10)->withQueryString();

        $kategoriList = Alat::distinct()->orderBy('kategori')->pluck('kategori');

        return view('admin.alat.index', compact('alatList', 'kategoriList'));
    }

    /**
     * Formulir penambahan alat baru
     */
    public function create(): View
    {
        return view('admin.alat.create');
    }

    /**
     * Simpan data alat baru ke database
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_alat' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Za-z0-9_-]+$/',
                'unique:alat,kode_alat',
            ],
            'nama_alat' => ['required', 'string', 'max:100'],
            'kategori' => ['required', 'string', 'max:50'],
            'stok' => ['required', 'integer', 'min:0'],
            'kondisi' => ['required', 'in:Baik,Perlu Perbaikan,Rusak'],
            'tarif_sewa' => ['required', 'numeric', 'min:1'],
        ], [
            'kode_alat.required' => 'Kode alat wajib diisi.',
            'kode_alat.regex' => 'Kode alat hanya boleh memuat huruf, angka, tanda minus, dan garis bawah.',
            'kode_alat.unique' => 'Kode alat ini sudah digunakan.',
            'nama_alat.required' => 'Nama alat wajib diisi.',
            'kategori.required' => 'Kategori wajib diisi.',
            'stok.required' => 'Stok wajib diisi.',
            'stok.integer' => 'Stok harus berupa bilangan bulat.',
            'stok.min' => 'Stok tidak boleh bernilai negatif.',
            'kondisi.required' => 'Kondisi alat wajib dipilih.',
            'kondisi.in' => 'Pilihan kondisi alat tidak valid.',
            'tarif_sewa.required' => 'Tarif sewa wajib diisi.',
            'tarif_sewa.numeric' => 'Tarif sewa harus berupa nominal angka valid.',
            'tarif_sewa.min' => 'Tarif sewa harus lebih besar dari 0 Rupiah.',
        ]);

        $validated['kode_alat'] = strtoupper($validated['kode_alat']);

        $alat = Alat::create([
            'kode_alat' => $validated['kode_alat'],
            'nama_alat' => $validated['nama_alat'],
            'kategori' => $validated['kategori'],
            'stok' => (int) $validated['stok'],
            'kondisi' => $validated['kondisi'],
            'tarif_sewa' => (float) $validated['tarif_sewa'],
            'created_at' => now(),
        ]);

        $this->logActivity("Tambah alat: {$alat->kode_alat}", $alat->nama_alat);

        return redirect()->route('admin.alat.index')->with(
            'success',
            "Alat baru [{$alat->kode_alat}] {$alat->nama_alat} berhasil ditambahkan ke inventaris."
        );
    }

    /**
     * Tampilkan detail alat untuk Admin
     */
    public function show(string $kode_alat): View
    {
        $alat = Alat::with('detailPenyewaan')->where('kode_alat', $kode_alat)->firstOrFail();
        $totalPenyewaan = $alat->detailPenyewaan()->count();

        return view('admin.alat.show', compact('alat', 'totalPenyewaan'));
    }

    /**
     * Formulir edit data alat
     */
    public function edit(string $kode_alat): View
    {
        $alat = Alat::where('kode_alat', $kode_alat)->firstOrFail();

        return view('admin.alat.edit', compact('alat'));
    }

    /**
     * Perbarui data alat
     */
    public function update(Request $request, string $kode_alat): RedirectResponse
    {
        $alat = Alat::where('kode_alat', $kode_alat)->firstOrFail();

        $validated = $request->validate([
            'nama_alat' => ['required', 'string', 'max:100'],
            'kategori' => ['required', 'string', 'max:50'],
            'stok' => ['required', 'integer', 'min:0'],
            'kondisi' => ['required', 'in:Baik,Perlu Perbaikan,Rusak'],
            'tarif_sewa' => ['required', 'numeric', 'min:1'],
        ], [
            'nama_alat.required' => 'Nama alat wajib diisi.',
            'kategori.required' => 'Kategori wajib diisi.',
            'stok.required' => 'Stok wajib diisi.',
            'stok.integer' => 'Stok harus berupa bilangan bulat.',
            'stok.min' => 'Stok tidak boleh bernilai negatif.',
            'kondisi.required' => 'Kondisi alat wajib dipilih.',
            'kondisi.in' => 'Pilihan kondisi alat tidak valid.',
            'tarif_sewa.required' => 'Tarif sewa wajib diisi.',
            'tarif_sewa.numeric' => 'Tarif sewa harus berupa angka valid.',
            'tarif_sewa.min' => 'Tarif sewa harus lebih besar dari 0 Rupiah.',
        ]);

        $oldKondisi = $alat->kondisi;

        $alat->update([
            'nama_alat' => $validated['nama_alat'],
            'kategori' => $validated['kategori'],
            'stok' => (int) $validated['stok'],
            'kondisi' => $validated['kondisi'],
            'tarif_sewa' => (float) $validated['tarif_sewa'],
        ]);

        if ($oldKondisi !== $validated['kondisi']) {
            ActivityLog::record(
                Auth::id() ?? 1,
                'Perubahan Kondisi Alat',
                'Alat',
                "Kode Alat: {$alat->kode_alat}, Nama: {$alat->nama_alat}, Kondisi diubah: {$oldKondisi} -> {$validated['kondisi']}"
            );
        }

        $this->logActivity("Perbarui alat: {$alat->kode_alat}", $alat->nama_alat);

        return redirect()->route('admin.alat.index')->with(
            'success',
            "Perubahan data alat [{$alat->kode_alat}] {$alat->nama_alat} berhasil disimpan."
        );
    }

    /**
     * Hapus alat dengan perlindungan integritas relasi foreign key
     */
    public function destroy(string $kode_alat): RedirectResponse
    {
        $alat = Alat::where('kode_alat', $kode_alat)->firstOrFail();

        // Aturan integritas relasi database: Dilarang menghapus alat yang sudah ada riwayat transaksi
        if ($alat->hasRentals()) {
            return back()->with(
                'error',
                "Alat [{$alat->kode_alat}] {$alat->nama_alat} TIDAK DAPAT DIHAPUS karena memiliki riwayat transaksi penyewaan aktif/tersimpan. Untuk menonaktifkannya, ubah kondisi menjadi 'Rusak' atau atur stok menjadi 0."
            );
        }

        $namaAlat = $alat->nama_alat;
        $alat->delete();

        $this->logActivity("Hapus alat: {$kode_alat}", $namaAlat);

        return redirect()->route('admin.alat.index')->with(
            'success',
            "Alat [{$kode_alat}] {$namaAlat} berhasil dihapus dari inventaris."
        );
    }

    /**
     * Catat riwayat ke tabel activity_logs jika tabel tersedia
     */
    protected function logActivity(string $aktivitas, string $data): void
    {
        ActivityLog::record(Auth::id() ?? 1, $aktivitas, 'Alat', $data);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Alat extends Model
{
    use HasFactory;

    protected $table = 'alat';

    protected $primaryKey = 'kode_alat';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * Atribut yang dapat diisi massal
     */
    protected $fillable = [
        'kode_alat',
        'nama_alat',
        'kategori',
        'stok',
        'kondisi',
        'tarif_sewa',
        'created_at',
    ];

    /**
     * Type casting atribut
     */
    protected function casts(): array
    {
        return [
            'stok' => 'integer',
            'tarif_sewa' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke baris transaksi pada detail penyewaan
     */
    public function detailPenyewaan(): HasMany
    {
        return $this->hasMany(DetailPenyewaan::class, 'kode_alat', 'kode_alat');
    }

    /**
     * Relasi ke catatan riwayat perubahan stok
     */
    public function riwayatStok(): HasMany
    {
        return $this->hasMany(RiwayatStok::class, 'kode_alat', 'kode_alat')->orderBy('created_at', 'desc');
    }

    /**
     * Periksa apakah alat sudah memiliki riwayat transaksi penyewaan
     */
    public function hasRentals(): bool
    {
        return $this->detailPenyewaan()->exists();
    }

    /**
     * Periksa apakah alat memenuhi syarat untuk siap disewa oleh Pengguna:
     * Kondisi wajib 'Baik' dan stok fisik > 0.
     */
    public function isSiapSewa(): bool
    {
        return $this->kondisi === 'Baik' && $this->stok > 0;
    }

    /**
     * Format tarif sewa ke format mata uang Rupiah
     */
    public function getFormattedTarifAttribute(): string
    {
        return 'Rp '.number_format((float) $this->tarif_sewa, 0, ',', '.');
    }

    /**
     * Ringkasan global inventaris seluruh alat untuk dashboard Admin & Pemilik
     */
    public static function getRingkasanInventaris(): array
    {
        // 1. Stok siap sewa (Kondisi Baik di toko yang dapat disewa)
        $stokSiapSewa = (int) self::where('kondisi', 'Baik')->sum('stok');

        // 2. Stok perlu perbaikan (Master alat kondisi Perlu Perbaikan + unit pengembalian butuh perbaikan yang belum selesai diperbaiki)
        $stokPerluPerbaikanMaster = (int) self::where('kondisi', 'Perlu Perbaikan')->sum('stok');
        $stokPerluPerbaikanReturn = (int) DetailPenyewaan::selectRaw('COALESCE(SUM(jumlah_perlu_perbaikan - COALESCE(jumlah_diperbaiki, 0)), 0) as total')->value('total');
        $stokPerluPerbaikan = $stokPerluPerbaikanMaster + $stokPerluPerbaikanReturn;

        // 3. Stok rusak / afkir (Master alat kondisi Rusak + unit pengembalian rusak)
        $stokRusakMaster = (int) self::where('kondisi', 'Rusak')->sum('stok');
        $stokRusakReturn = (int) DetailPenyewaan::sum('jumlah_rusak');
        $stokRusak = $stokRusakMaster + $stokRusakReturn;

        // 4. Stok unit yang sedang aktif disewa di lapangan (status Sedang Disewa, Berjalan)
        $stokSedangDisewa = (int) DetailPenyewaan::whereHas('penyewaan', function ($q) {
            $q->whereIn('status_penyewaan', ['Sedang Disewa', 'Berjalan']);
        })->selectRaw('COALESCE(SUM(CASE WHEN ((CASE WHEN jumlah_diserahkan > 0 THEN jumlah_diserahkan ELSE jumlah END) - COALESCE(jumlah_kembali_baik, 0) - COALESCE(jumlah_perlu_perbaikan, 0) - COALESCE(jumlah_rusak, 0)) > 0 THEN ((CASE WHEN jumlah_diserahkan > 0 THEN jumlah_diserahkan ELSE jumlah END) - COALESCE(jumlah_kembali_baik, 0) - COALESCE(jumlah_perlu_perbaikan, 0) - COALESCE(jumlah_rusak, 0)) ELSE 0 END), 0) as total')->value('total');
        $stokSedangDisewa = max(0, $stokSedangDisewa);

        // 5. Stok unit yang sudah dialokasikan / terpesan di toko tapi belum diambil (status Menunggu Konfirmasi, Menunggu Pembayaran, Disetujui, Siap Diambil)
        $stokDialokasikan = (int) DetailPenyewaan::whereHas('penyewaan', function ($q) {
            $q->whereIn('status_penyewaan', ['Menunggu Konfirmasi', 'Menunggu Pembayaran', 'Disetujui', 'Siap Diambil']);
        })->sum('jumlah');

        // 6. Total seluruh stok fisik inventaris (stok toko + sedang disewa di lapangan + perlu perbaikan + rusak)
        $totalStokFisik = $stokSiapSewa + $stokSedangDisewa + $stokPerluPerbaikan + $stokRusak;

        return [
            'total_stok_fisik' => $totalStokFisik,
            'stok_siap_sewa' => $stokSiapSewa,
            'stok_toko' => $stokSiapSewa,
            'stok_sedang_disewa' => $stokSedangDisewa,
            'stok_dialokasikan' => $stokDialokasikan,
            'stok_perlu_perbaikan' => $stokPerluPerbaikan,
            'stok_rusak' => $stokRusak,
        ];
    }

    /**
     * Hitung stok siap sewa untuk alat ini (Hanya kondisi Baik di toko)
     */
    public function getStokSiapSewaAttribute(): int
    {
        return ($this->kondisi === 'Baik') ? (int) $this->stok : 0;
    }

    /**
     * Hitung stok perlu perbaikan untuk item alat ini (Master + Return belum selesai diperbaiki)
     */
    public function getStokPerluPerbaikanAttribute(): int
    {
        $master = ($this->kondisi === 'Perlu Perbaikan') ? (int) $this->stok : 0;
        $return = (int) $this->detailPenyewaan()->selectRaw('COALESCE(SUM(jumlah_perlu_perbaikan - COALESCE(jumlah_diperbaiki, 0)), 0) as total')->value('total');

        return $master + $return;
    }

    /**
     * Hitung stok rusak / afkir untuk item alat ini
     */
    public function getStokRusakAttribute(): int
    {
        $master = ($this->kondisi === 'Rusak') ? (int) $this->stok : 0;
        $return = (int) $this->detailPenyewaan()->sum('jumlah_rusak');

        return $master + $return;
    }

    /**
     * Hitung total unit yang sedang berada di tangan pelanggan (Sedang Disewa / Berjalan)
     */
    public function getStokSedangDisewaAttribute(): int
    {
        $val = (int) $this->detailPenyewaan()->whereHas('penyewaan', function ($q) {
            $q->whereIn('status_penyewaan', ['Sedang Disewa', 'Berjalan']);
        })->selectRaw('COALESCE(SUM(CASE WHEN ((CASE WHEN jumlah_diserahkan > 0 THEN jumlah_diserahkan ELSE jumlah END) - COALESCE(jumlah_kembali_baik, 0) - COALESCE(jumlah_perlu_perbaikan, 0) - COALESCE(jumlah_rusak, 0)) > 0 THEN ((CASE WHEN jumlah_diserahkan > 0 THEN jumlah_diserahkan ELSE jumlah END) - COALESCE(jumlah_kembali_baik, 0) - COALESCE(jumlah_perlu_perbaikan, 0) - COALESCE(jumlah_rusak, 0)) ELSE 0 END), 0) as total')->value('total');

        return max(0, $val);
    }

    /**
     * Hitung unit yang dialokasikan di toko untuk transaksi aktif sebelum diserahkan
     */
    public function getStokDialokasikanAttribute(): int
    {
        return (int) $this->detailPenyewaan()->whereHas('penyewaan', function ($q) {
            $q->whereIn('status_penyewaan', ['Menunggu Konfirmasi', 'Menunggu Pembayaran', 'Disetujui', 'Siap Diambil']);
        })->sum('jumlah');
    }

    /**
     * Kapasitas total alat layak sewa milik toko (Unit di rak toko + unit yang sedang disewa di lapangan)
     */
    public function getTotalKapasitasLayakAttribute(): int
    {
        if ($this->kondisi !== 'Baik') {
            return 0;
        }

        return (int) $this->stok + $this->stok_sedang_disewa;
    }

    /**
     * Total stok fisik kepemilikan alat ini (Toko siap sewa + Sedang disewa + Perlu Perbaikan + Rusak)
     */
    public function getStokFisikTotalAttribute(): int
    {
        return $this->stok_siap_sewa + $this->stok_sedang_disewa + $this->stok_perlu_perbaikan + $this->stok_rusak;
    }

    /**
     * Dapatkan daftar inventaris lengkap per alat beserta seluruh metrik stok terkalkulasi (bebas N+1)
     *
     * @return Collection<int, object>
     */
    public static function getDaftarInventarisLengkap(?string $search = null, ?string $kategori = null, ?string $kondisi = null): Collection
    {
        $query = self::query();

        if ($search !== null && trim($search) !== '') {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('nama_alat', 'like', "%{$s}%")
                    ->orWhere('kode_alat', 'like', "%{$s}%");
            });
        }

        if ($kategori !== null && trim($kategori) !== '') {
            $query->where('kategori', trim($kategori));
        }

        if ($kondisi !== null && trim($kondisi) !== '') {
            $query->where('kondisi', trim($kondisi));
        }

        $alatList = $query->orderBy('kategori', 'asc')->orderBy('kode_alat', 'asc')->get();

        // 1. Agregasi unit yang sedang disewa di lapangan (status Sedang Disewa, Berjalan)
        $sedangDisewaMap = DetailPenyewaan::whereHas('penyewaan', function ($q) {
            $q->whereIn('status_penyewaan', ['Sedang Disewa', 'Berjalan']);
        })
            ->selectRaw('kode_alat, SUM(CASE WHEN ((CASE WHEN jumlah_diserahkan > 0 THEN jumlah_diserahkan ELSE jumlah END) - COALESCE(jumlah_kembali_baik, 0) - COALESCE(jumlah_perlu_perbaikan, 0) - COALESCE(jumlah_rusak, 0)) > 0 THEN ((CASE WHEN jumlah_diserahkan > 0 THEN jumlah_diserahkan ELSE jumlah END) - COALESCE(jumlah_kembali_baik, 0) - COALESCE(jumlah_perlu_perbaikan, 0) - COALESCE(jumlah_rusak, 0)) ELSE 0 END) as total_sewa')
            ->groupBy('kode_alat')
            ->pluck('total_sewa', 'kode_alat');

        // 2. Agregasi unit yang dialokasikan di toko (status Menunggu Konfirmasi, Menunggu Pembayaran, Disetujui, Siap Diambil)
        $dialokasikanMap = DetailPenyewaan::whereHas('penyewaan', function ($q) {
            $q->whereIn('status_penyewaan', ['Menunggu Konfirmasi', 'Menunggu Pembayaran', 'Disetujui', 'Siap Diambil']);
        })
            ->selectRaw('kode_alat, SUM(jumlah) as total_alokasi')
            ->groupBy('kode_alat')
            ->pluck('total_alokasi', 'kode_alat');

        // 3. Agregasi unit rusak & perbaikan dari pengembalian sewa per kode_alat
        $kondisiReturnMap = DetailPenyewaan::selectRaw('kode_alat, SUM(jumlah_rusak) as total_rusak, SUM(jumlah_perlu_perbaikan - COALESCE(jumlah_diperbaiki, 0)) as total_perbaikan')
            ->groupBy('kode_alat')
            ->get()
            ->keyBy('kode_alat');

        return $alatList->map(function ($alat) use ($sedangDisewaMap, $dialokasikanMap, $kondisiReturnMap) {
            $stokToko = (int) $alat->stok;
            $sedangDisewa = max(0, (int) ($sedangDisewaMap[$alat->kode_alat] ?? 0));
            $dialokasikan = max(0, (int) ($dialokasikanMap[$alat->kode_alat] ?? 0));
            $rusakReturn = (int) ($kondisiReturnMap[$alat->kode_alat]->total_rusak ?? 0);
            $perbaikanReturn = (int) ($kondisiReturnMap[$alat->kode_alat]->total_perbaikan ?? 0);

            $stokSiapSewa = ($alat->kondisi === 'Baik') ? $stokToko : 0;
            $stokPerluPerbaikan = (($alat->kondisi === 'Perlu Perbaikan') ? $stokToko : 0) + $perbaikanReturn;
            $stokRusak = (($alat->kondisi === 'Rusak') ? $stokToko : 0) + $rusakReturn;
            $totalFisik = $stokSiapSewa + $sedangDisewa + $stokPerluPerbaikan + $stokRusak;

            return (object) [
                'alat' => $alat,
                'kode_alat' => $alat->kode_alat,
                'nama_alat' => $alat->nama_alat,
                'kategori' => $alat->kategori,
                'kondisi' => $alat->kondisi,
                'tarif_sewa' => (float) $alat->tarif_sewa,
                'harga_sewa_per_hari' => (float) $alat->tarif_sewa,
                'stok_toko' => $stokToko,
                'stok_siap_sewa' => $stokSiapSewa,
                'stok_sedang_disewa' => $sedangDisewa,
                'stok_dialokasikan' => $dialokasikan,
                'stok_perlu_perbaikan' => $stokPerluPerbaikan,
                'stok_rusak' => $stokRusak,
                'stok_bermasalah' => $stokPerluPerbaikan + $stokRusak,
                'total_stok_fisik' => $totalFisik,
                'stok_fisik_total' => $totalFisik,
            ];
        });
    }

    /**
     * Dapatkan daftar rincian unit alat yang sedang berada dalam transaksi penyewaan aktif di lapangan
     *
     * @return Collection<int, object>
     */
    public static function getDaftarSedangDisewa(?string $search = null): Collection
    {
        $query = DetailPenyewaan::with(['alat', 'penyewaan.pelanggan'])
            ->whereHas('penyewaan', function ($q) {
                $q->whereIn('status_penyewaan', ['Sedang Disewa', 'Berjalan']);
            });

        if ($search !== null && trim($search) !== '') {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('no_transaksi', 'like', "%{$s}%")
                    ->orWhere('kode_alat', 'like', "%{$s}%")
                    ->orWhereHas('alat', fn ($sub) => $sub->where('nama_alat', 'like', "%{$s}%"))
                    ->orWhereHas('penyewaan.pelanggan', fn ($sub) => $sub->where('nama_pelanggan', 'like', "%{$s}%"));
            });
        }

        return $query->get()
            ->map(function ($detail) {
                $unitDisewa = $detail->jumlah_sisa_kembali;

                return (object) [
                    'id_detail' => $detail->id_detail,
                    'no_transaksi' => $detail->no_transaksi,
                    'nomor_transaksi' => $detail->no_transaksi,
                    'kode_alat' => $detail->kode_alat,
                    'nama_alat' => $detail->alat?->nama_alat ?? $detail->kode_alat,
                    'kategori' => $detail->alat?->kategori ?? '-',
                    'kondisi' => $detail->alat?->kondisi ?? '-',
                    'stok_fisik' => (int) ($detail->alat?->stok ?? 0),
                    'jumlah_unit' => $unitDisewa,
                    'unit_disewa' => $unitDisewa,
                    'tarif_sewa' => (float) $detail->tarif_sewa,
                    'nama_pelanggan' => $detail->penyewaan?->pelanggan?->nama_pelanggan ?? 'Umum',
                    'tanggal_sewa' => $detail->penyewaan?->tanggal_sewa,
                    'tanggal_mulai' => $detail->penyewaan?->tanggal_sewa,
                    'tanggal_kembali' => $detail->penyewaan?->tanggal_kembali,
                    'durasi_hari' => $detail->penyewaan?->durasi_hari ?? 1,
                    'status_penyewaan' => $detail->penyewaan?->status_penyewaan ?? '-',
                    'sumber_pesanan' => $detail->penyewaan?->sumber_pesanan ?? 'online',
                ];
            })
            ->filter(fn ($item) => $item->jumlah_unit > 0)
            ->sortBy('tanggal_kembali')
            ->values();
    }

    /**
     * Dapatkan daftar rincian unit alat yang sudah dialokasikan / terpesan di toko sebelum diserahkan
     *
     * @return Collection<int, object>
     */
    public static function getDaftarDialokasikan(?string $search = null): Collection
    {
        $query = DetailPenyewaan::with(['alat', 'penyewaan.pelanggan'])
            ->whereHas('penyewaan', function ($q) {
                $q->whereIn('status_penyewaan', ['Menunggu Konfirmasi', 'Menunggu Pembayaran', 'Disetujui', 'Siap Diambil']);
            });

        if ($search !== null && trim($search) !== '') {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('no_transaksi', 'like', "%{$s}%")
                    ->orWhere('kode_alat', 'like', "%{$s}%")
                    ->orWhereHas('alat', fn ($sub) => $sub->where('nama_alat', 'like', "%{$s}%"))
                    ->orWhereHas('penyewaan.pelanggan', fn ($sub) => $sub->where('nama_pelanggan', 'like', "%{$s}%"));
            });
        }

        return $query->get()
            ->map(function ($detail) {
                return (object) [
                    'id_detail' => $detail->id_detail,
                    'no_transaksi' => $detail->no_transaksi,
                    'nomor_transaksi' => $detail->no_transaksi,
                    'kode_alat' => $detail->kode_alat,
                    'nama_alat' => $detail->alat?->nama_alat ?? $detail->kode_alat,
                    'kategori' => $detail->alat?->kategori ?? '-',
                    'kondisi' => $detail->alat?->kondisi ?? '-',
                    'stok_fisik' => (int) ($detail->alat?->stok ?? 0),
                    'jumlah_unit' => (int) $detail->jumlah,
                    'unit_disewa' => (int) $detail->jumlah,
                    'tarif_sewa' => (float) $detail->tarif_sewa,
                    'nama_pelanggan' => $detail->penyewaan?->pelanggan?->nama_pelanggan ?? 'Umum',
                    'tanggal_sewa' => $detail->penyewaan?->tanggal_sewa,
                    'tanggal_mulai' => $detail->penyewaan?->tanggal_sewa,
                    'tanggal_kembali' => $detail->penyewaan?->tanggal_kembali,
                    'durasi_hari' => $detail->penyewaan?->durasi_hari ?? 1,
                    'status_penyewaan' => $detail->penyewaan?->status_penyewaan ?? '-',
                    'sumber_pesanan' => $detail->penyewaan?->sumber_pesanan ?? 'online',
                ];
            })
            ->filter(fn ($item) => $item->jumlah_unit > 0)
            ->sortBy('tanggal_sewa')
            ->values();
    }
}

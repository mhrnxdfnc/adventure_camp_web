<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

        // 4. Stok unit yang sedang aktif dialokasikan / disewa di lapangan
        $stokSedangDisewa = (int) DetailPenyewaan::whereHas('penyewaan', function ($q) {
            $q->whereIn('status_penyewaan', ['Siap Diambil', 'Sedang Disewa', 'Berjalan']);
        })->selectRaw('COALESCE(SUM(CASE WHEN jumlah_diserahkan > 0 THEN jumlah_diserahkan ELSE jumlah END), 0) as total')->value('total');

        // 5. Total seluruh stok fisik inventaris (stok siap sewa + sedang disewa + perlu perbaikan + rusak)
        $totalStokFisik = $stokSiapSewa + $stokSedangDisewa + $stokPerluPerbaikan + $stokRusak;

        return [
            'total_stok_fisik' => $totalStokFisik,
            'stok_siap_sewa' => $stokSiapSewa,
            'stok_sedang_disewa' => $stokSedangDisewa,
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
     * Hitung total unit yang sedang dialokasikan / disewa untuk alat ini
     */
    public function getStokSedangDisewaAttribute(): int
    {
        return (int) $this->detailPenyewaan()->whereHas('penyewaan', function ($q) {
            $q->whereIn('status_penyewaan', ['Siap Diambil', 'Sedang Disewa', 'Berjalan']);
        })->selectRaw('COALESCE(SUM(CASE WHEN jumlah_diserahkan > 0 THEN jumlah_diserahkan ELSE jumlah END), 0) as total')->value('total');
    }

    /**
     * Total stok fisik kepemilikan alat ini (Toko siap sewa + Sedang disewa + Perlu Perbaikan + Rusak)
     */
    public function getStokFisikTotalAttribute(): int
    {
        return $this->stok_siap_sewa + $this->stok_sedang_disewa + $this->stok_perlu_perbaikan + $this->stok_rusak;
    }
}

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
        return 'Rp ' . number_format((float) $this->tarif_sewa, 0, ',', '.');
    }
}

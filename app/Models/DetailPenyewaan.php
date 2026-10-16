<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPenyewaan extends Model
{
    use HasFactory;

    protected $table = 'detail_penyewaan';

    protected $primaryKey = 'id_detail';

    public $timestamps = false;

    protected $fillable = [
        'no_transaksi',
        'kode_alat',
        'jumlah',
        'tarif_sewa',
        'jumlah_diserahkan',
        'jumlah_kembali_baik',
        'jumlah_rusak',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tarif_sewa' => 'decimal:2',
            'jumlah_diserahkan' => 'integer',
            'jumlah_kembali_baik' => 'integer',
            'jumlah_rusak' => 'integer',
        ];
    }

    /**
     * Relasi ke master data alat
     */
    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class, 'kode_alat', 'kode_alat');
    }

    /**
     * Relasi ke header transaksi penyewaan
     */
    public function penyewaan(): BelongsTo
    {
        return $this->belongsTo(Penyewaan::class, 'no_transaksi', 'no_transaksi');
    }
}

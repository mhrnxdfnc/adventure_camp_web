<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPembelianInventaris extends Model
{
    use HasFactory;

    protected $table = 'detail_pembelian_inventaris';

    protected $primaryKey = 'id_detail';

    public $timestamps = false;

    protected $fillable = [
        'no_pembelian',
        'kode_alat',
        'kuantitas',
        'harga_beli_satuan',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'kuantitas' => 'integer',
            'harga_beli_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * Relasi ke header transaksi pembelian
     */
    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(PembelianInventaris::class, 'no_pembelian', 'no_pembelian');
    }

    /**
     * Relasi ke master data alat
     */
    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class, 'kode_alat', 'kode_alat');
    }
}

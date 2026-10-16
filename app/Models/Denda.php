<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Denda extends Model
{
    use HasFactory;

    protected $table = 'denda';

    protected $primaryKey = 'id_denda';

    public $timestamps = false;

    protected $fillable = [
        'id_pengembalian',
        'tarif_denda',
        'nominal_denda',
        'alasan_denda',
    ];

    protected function casts(): array
    {
        return [
            'id_pengembalian' => 'integer',
            'tarif_denda' => 'decimal:2',
            'nominal_denda' => 'decimal:2',
        ];
    }

    /**
     * Relasi ke transaksi pengembalian
     */
    public function pengembalian(): BelongsTo
    {
        return $this->belongsTo(Pengembalian::class, 'id_pengembalian', 'id_pengembalian');
    }
}

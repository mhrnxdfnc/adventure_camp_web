<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PembelianInventaris extends Model
{
    use HasFactory;

    protected $table = 'pembelian_inventaris';

    protected $primaryKey = 'no_pembelian';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'no_pembelian',
        'tanggal_pembelian',
        'id_supplier',
        'total_pembelian',
        'id_admin',
        'catatan',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pembelian' => 'date',
            'total_pembelian' => 'decimal:2',
            'id_supplier' => 'integer',
            'id_admin' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke data supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }

    /**
     * Relasi ke admin yang mencatat transaksi pembelian
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_admin', 'id_user');
    }

    /**
     * Relasi ke detail rincian alat yang dibeli
     */
    public function detailPembelian(): HasMany
    {
        return $this->hasMany(DetailPembelianInventaris::class, 'no_pembelian', 'no_pembelian');
    }

    /**
     * Format total pembelian dalam Rupiah
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'Rp '.number_format((float) $this->total_pembelian, 0, ',', '.');
    }
}

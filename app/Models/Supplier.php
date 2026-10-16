<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'supplier';

    protected $primaryKey = 'id_supplier';

    public $timestamps = false;

    protected $fillable = [
        'nama_supplier',
        'kontak_person',
        'telepon',
        'alamat',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke seluruh transaksi pembelian dari supplier ini
     */
    public function pembelian(): HasMany
    {
        return $this->hasMany(PembelianInventaris::class, 'id_supplier', 'id_supplier');
    }
}

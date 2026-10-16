<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pengembalian extends Model
{
    use HasFactory;

    protected $table = 'pengembalian';

    protected $primaryKey = 'id_pengembalian';

    public $timestamps = false;

    protected $fillable = [
        'no_transaksi',
        'tanggal_pengembalian_aktual',
        'kondisi_alat',
        'status_pengembalian',
        'id_admin',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengembalian_aktual' => 'date',
            'id_admin' => 'integer',
        ];
    }

    /**
     * Relasi ke transaksi penyewaan
     */
    public function penyewaan(): BelongsTo
    {
        return $this->belongsTo(Penyewaan::class, 'no_transaksi', 'no_transaksi');
    }

    /**
     * Relasi ke data denda keterlambatan atau kerusakan
     */
    public function denda(): HasOne
    {
        return $this->hasOne(Denda::class, 'id_pengembalian', 'id_pengembalian');
    }

    /**
     * Relasi ke Admin yang memproses pengembalian
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_admin', 'id_user');
    }
}

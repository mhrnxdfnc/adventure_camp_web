<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengambilan extends Model
{
    use HasFactory;

    protected $table = 'pengambilan';

    protected $primaryKey = 'id_pengambilan';

    public $timestamps = false;

    protected $fillable = [
        'no_transaksi',
        'tanggal_pengambilan',
        'id_admin',
        'nama_penerima',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengambilan' => 'datetime',
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
     * Relasi ke Admin yang menyerahkan alat
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_admin', 'id_user');
    }
}

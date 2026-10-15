<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pelanggan extends Model
{
    use HasFactory;

    protected $table = 'pelanggan';
    protected $primaryKey = 'id_pelanggan';
    public $timestamps = false;

    protected $fillable = [
        'nama_pelanggan',
        'alamat',
        'nomor_telepon',
        'created_at',
    ];

    /**
     * Relasi ke akun User yang terhubung
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id_pelanggan', 'id_pelanggan');
    }
}

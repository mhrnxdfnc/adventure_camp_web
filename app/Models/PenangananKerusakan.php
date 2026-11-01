<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenangananKerusakan extends Model
{
    use HasFactory;

    protected $table = 'penanganan_kerusakan';

    protected $primaryKey = 'id_penanganan';

    public $timestamps = false;

    protected $fillable = [
        'kode_alat',
        'id_detail',
        'jenis_tindakan',
        'jumlah_unit',
        'biaya_perbaikan',
        'tanggal_tindakan',
        'pelaksana_teknisi',
        'keterangan',
        'id_admin',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'id_detail' => 'integer',
            'jumlah_unit' => 'integer',
            'biaya_perbaikan' => 'float',
            'tanggal_tindakan' => 'date',
            'id_admin' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke data alat
     */
    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class, 'kode_alat', 'kode_alat');
    }

    /**
     * Relasi ke admin pemroses
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_admin', 'id_user');
    }

    /**
     * Relasi ke detail penyewaan terkait jika unit berasal dari transaksi sewa
     */
    public function detailPenyewaan(): BelongsTo
    {
        return $this->belongsTo(DetailPenyewaan::class, 'id_detail', 'id_detail');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class RiwayatStok extends Model
{
    use HasFactory;

    protected $table = 'riwayat_stok';

    protected $primaryKey = 'id_log';

    public $timestamps = false;

    protected $fillable = [
        'kode_alat',
        'jenis_perubahan',
        'stok_sebelum',
        'jumlah_perubahan',
        'stok_sesudah',
        'alasan',
        'id_user',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'stok_sebelum' => 'integer',
            'jumlah_perubahan' => 'integer',
            'stok_sesudah' => 'integer',
            'id_user' => 'integer',
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
     * Relasi ke user / admin yang melakukan perubahan
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    /**
     * Catat riwayat perubahan stok secara terstruktur
     */
    public static function recordChange(
        string $kodeAlat,
        string $jenisPerubahan,
        int $stokSebelum,
        int $jumlahPerubahan,
        int $stokSesudah,
        string $alasan,
        ?int $userId = null
    ): ?self {
        if (! Schema::hasTable('riwayat_stok')) {
            return null;
        }

        return self::create([
            'kode_alat' => $kodeAlat,
            'jenis_perubahan' => $jenisPerubahan,
            'stok_sebelum' => $stokSebelum,
            'jumlah_perubahan' => $jumlahPerubahan,
            'stok_sesudah' => $stokSesudah,
            'alasan' => $alasan,
            'id_user' => $userId ?? Auth::id() ?? 1,
            'created_at' => now(),
        ]);
    }
}

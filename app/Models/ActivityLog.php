<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $primaryKey = 'id_log';

    public $timestamps = false;

    protected $fillable = [
        'id_user',
        'aktivitas',
        'modul',
        'data_diproses',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'id_user' => 'integer',
            'waktu' => 'datetime',
        ];
    }

    /**
     * Relasi ke data user yang melakukan aksi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    /**
     * Catat aktivitas audit trail secara aman
     */
    public static function record(int $userId, string $aktivitas, string $modul, string|array|null $dataDiproses = null): ?self
    {
        try {
            if (Schema::hasTable('activity_logs')) {
                $data = is_array($dataDiproses) ? json_encode($dataDiproses, JSON_UNESCAPED_UNICODE) : (string) $dataDiproses;

                return self::create([
                    'id_user' => $userId,
                    'aktivitas' => $aktivitas,
                    'modul' => $modul,
                    'data_diproses' => $data,
                    'waktu' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // Jangan biarkan kegagalan logging menghentikan transaksi bisnis
        }

        return null;
    }
}

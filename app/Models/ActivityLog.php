<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
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

    /**
     * Daftar kunci atau istilah sensitif yang disensor demi keamanan audit trail
     */
    protected const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'remember_token',
        'secret',
        'api_key',
        'kredensial',
        'credential',
        'authorization',
        'pin',
        'access_token',
    ];

    protected function casts(): array
    {
        return [
            'id_user' => 'integer',
            'waktu' => 'datetime',
        ];
    }

    /**
     * Cegah pembaruan (update) dan penghapusan (delete) catatan audit trail melalui model
     */
    protected static function booted(): void
    {
        static::updating(function () {
            return false;
        });

        static::deleting(function () {
            return false;
        });
    }

    /**
     * Relasi ke data user yang melakukan aksi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    /**
     * Sanitasi data untuk mencegah kebocoran password, token, kredensial, atau rahasia
     */
    public static function sanitizeData(string|array|null $data): ?string
    {
        if ($data === null) {
            return null;
        }

        if (is_array($data)) {
            $cleaned = self::maskSensitiveArray($data);

            return json_encode($cleaned, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $stringData = (string) $data;

        // Masking regex untuk format kunci:nilai (misal: password: 123, token="abc", "password": "xyz")
        $pattern = '/\b(password|password_confirmation|token|remember_token|secret|pin|credential|kredensial|api_key|authorization)\s*([:=])\s*(["\']?)([^"\'\s,;{}]+)(["\']?)/i';
        $stringData = preg_replace($pattern, '$1$2$3***REDACTED***$5', $stringData);

        return $stringData;
    }

    /**
     * Rekursif mask kunci sensitif dalam array
     */
    protected static function maskSensitiveArray(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            $isSensitive = false;
            foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
                if (str_contains($lowerKey, $sensitiveKey)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $result[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $result[$key] = self::maskSensitiveArray($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Catat aktivitas audit trail secara aman dari backend
     */
    public static function record(?int $userId, string $aktivitas, string $modul, string|array|null $dataDiproses = null): ?self
    {
        try {
            if (Schema::hasTable('activity_logs')) {
                $resolvedUserId = $userId ?: (Auth::id() ?: 1);
                $sanitizedData = self::sanitizeData($dataDiproses);

                return self::create([
                    'id_user' => (int) $resolvedUserId,
                    'aktivitas' => trim($aktivitas),
                    'modul' => trim($modul),
                    'data_diproses' => $sanitizedData,
                    'waktu' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // Jangan biarkan kegagalan logging menghentikan atau menggagalkan transaksi bisnis utama
        }

        return null;
    }
}

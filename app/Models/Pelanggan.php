<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /**
     * Relasi ke seluruh riwayat transaksi penyewaan milik pelanggan
     */
    public function penyewaan(): HasMany
    {
        return $this->hasMany(Penyewaan::class, 'id_pelanggan', 'id_pelanggan');
    }

    /**
     * Normalisasi format nomor telepon ke format angka standar (contoh: 08123456789)
     */
    public static function normalizePhoneNumber(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        // Hapus karakter non-digit selain simbol + diawal
        $cleaned = trim($phone);
        $cleaned = preg_replace('/[^\d+]/', '', $cleaned);

        // Jika diawali +62 atau 62, ubah menjadi 0
        if (str_starts_with($cleaned, '+62')) {
            $cleaned = '0'.substr($cleaned, 3);
        } elseif (str_starts_with($cleaned, '62')) {
            $cleaned = '0'.substr($cleaned, 2);
        }

        // Pastikan hanya angka
        return preg_replace('/[^\d]/', '', $cleaned);
    }

    public function getNamaAttribute(): string
    {
        return $this->nama_pelanggan ?? '';
    }

    public function getNoTeleponAttribute(): string
    {
        return $this->nomor_telepon ?? '';
    }

    /**
     * Samarkan nama pelanggan untuk melindungi privasi di tablet umum (contoh: "An** Pr*****")
     */
    public static function maskName(?string $name): string
    {
        if (empty($name)) {
            return '***';
        }

        $words = preg_split('/\s+/', trim($name));
        $masked = [];

        foreach ($words as $word) {
            $len = mb_strlen($word);
            if ($len <= 2) {
                $masked[] = mb_substr($word, 0, 1).'*';
            } else {
                $masked[] = mb_substr($word, 0, 2).str_repeat('*', max(1, $len - 2));
            }
        }

        return implode(' ', $masked);
    }

    /**
     * Periksa kecocokan nama secara aman dengan toleransi variasi penulisan wajar
     */
    public static function isNameMatch(?string $name1, ?string $name2): bool
    {
        if (empty($name1) || empty($name2)) {
            return false;
        }

        $n1 = strtolower(trim(preg_replace('/\s+/', ' ', $name1)));
        $n2 = strtolower(trim(preg_replace('/\s+/', ' ', $name2)));

        if ($n1 === $n2) {
            return true;
        }

        // Periksa kesamaan teks
        similar_text($n1, $n2, $percent);
        if ($percent >= 75.0) {
            return true;
        }

        // Periksa irisan kata nama
        $words1 = explode(' ', $n1);
        $words2 = explode(' ', $n2);
        $intersect = array_intersect($words1, $words2);

        if (count($intersect) >= 2) {
            return true;
        }

        if (count($words1) === 1 && in_array($words1[0], $words2, true)) {
            return true;
        }

        if (count($words2) === 1 && in_array($words2[0], $words1, true)) {
            return true;
        }

        return false;
    }

    /**
     * Cari pelanggan berdasarkan nomor telepon (mendukung nomor mentah maupun nomor ternormalisasi)
     */
    public static function findByPhone(?string $phone): ?self
    {
        if (empty($phone)) {
            return null;
        }

        $rawPhone = trim($phone);
        $cleanPhone = self::normalizePhoneNumber($rawPhone);

        return self::where('nomor_telepon', $cleanPhone)
            ->orWhere('nomor_telepon', $rawPhone)
            ->first();
    }
}

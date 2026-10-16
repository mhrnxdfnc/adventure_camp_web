<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';

    protected $primaryKey = 'no_pembayaran';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'no_pembayaran',
        'no_transaksi',
        'total_biaya',
        'jumlah_pembayaran',
        'metode_pembayaran',
        'tanggal_pembayaran',
        'status_pembayaran',
        'bukti_pembayaran',
        'waktu_unggah_bukti',
        'id_admin',
        'waktu_konfirmasi',
        'alasan_penolakan',
    ];

    protected function casts(): array
    {
        return [
            'total_biaya' => 'decimal:2',
            'jumlah_pembayaran' => 'decimal:2',
            'tanggal_pembayaran' => 'datetime',
            'waktu_unggah_bukti' => 'datetime',
            'waktu_konfirmasi' => 'datetime',
            'id_admin' => 'integer',
        ];
    }

    /**
     * Relasi ke data transaksi penyewaan
     */
    public function penyewaan(): BelongsTo
    {
        return $this->belongsTo(Penyewaan::class, 'no_transaksi', 'no_transaksi');
    }

    /**
     * Relasi ke data user Admin yang memverifikasi/mengonfirmasi
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_admin', 'id_user');
    }

    /**
     * Generate format nomor pembayaran PAY-YYYYMMDD-001
     */
    public static function generateNoPembayaran(): string
    {
        $prefix = 'PAY-'.date('Ymd').'-';
        $latest = self::where('no_pembayaran', 'like', $prefix.'%')
            ->orderBy('no_pembayaran', 'desc')
            ->value('no_pembayaran');

        $sequence = 1;
        if ($latest) {
            $parts = explode('-', $latest);
            $sequence = ((int) end($parts)) + 1;
        }

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Periksa apakah pembayaran telah berstatus Lunas
     */
    public function isLunas(): bool
    {
        return $this->status_pembayaran === 'Lunas';
    }

    /**
     * Periksa apakah pembayaran menunggu verifikasi Admin
     */
    public function isMenungguVerifikasi(): bool
    {
        return $this->status_pembayaran === 'Menunggu Verifikasi';
    }

    /**
     * Periksa apakah bukti pembayaran ditolak Admin
     */
    public function isDitolak(): bool
    {
        return $this->status_pembayaran === 'Ditolak';
    }
}

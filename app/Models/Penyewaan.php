<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Penyewaan extends Model
{
    use HasFactory;

    protected $table = 'penyewaan';

    protected $primaryKey = 'no_transaksi';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'no_transaksi',
        'id_pelanggan',
        'id_user',
        'tanggal_sewa',
        'tanggal_kembali',
        'status_penyewaan',
    ];

    protected function casts(): array
    {
        return [
            'id_pelanggan' => 'integer',
            'id_user' => 'integer',
            'tanggal_sewa' => 'date',
            'tanggal_kembali' => 'date',
        ];
    }

    /**
     * Relasi ke data pelanggan
     */
    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    /**
     * Relasi ke data user (pembuat transaksi)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    /**
     * Relasi ke rincian alat yang disewa
     */
    public function detailPenyewaan(): HasMany
    {
        return $this->hasMany(DetailPenyewaan::class, 'no_transaksi', 'no_transaksi');
    }

    /**
     * Relasi ke data pembayaran
     */
    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class, 'no_transaksi', 'no_transaksi');
    }

    /**
     * Hitung durasi sewa dalam hari (minimal 1 hari)
     */
    public function getDurasiHariAttribute(): int
    {
        if (! $this->tanggal_sewa || ! $this->tanggal_kembali) {
            return 1;
        }

        $sewa = Carbon::parse($this->tanggal_sewa)->startOfDay();
        $kembali = Carbon::parse($this->tanggal_kembali)->startOfDay();

        return max(1, (int) $sewa->diffInDays($kembali));
    }

    /**
     * Total tagihan dihitung dari SUM(jumlah * tarif_sewa * durasi_hari)
     */
    public function getTotalBiayaAttribute(): float
    {
        $durasi = $this->durasi_hari;

        if ($this->relationLoaded('detailPenyewaan') && $this->detailPenyewaan->isNotEmpty()) {
            return (float) $this->detailPenyewaan->sum(function ($item) use ($durasi) {
                return (float) $item->jumlah * (float) $item->tarif_sewa * $durasi;
            });
        }

        // Jika detail belum dimuat, hitung via relasi query
        $calculated = (float) $this->detailPenyewaan()
            ->selectRaw('COALESCE(SUM(jumlah * tarif_sewa), 0) as total')
            ->value('total');

        if ($calculated > 0) {
            return $calculated * $durasi;
        }

        return (float) ($this->pembayaran?->total_biaya ?? 0);
    }

    /**
     * Status pembayaran terpisah dari status pesanan
     */
    public function getStatusPembayaranAttribute(): string
    {
        if ($this->pembayaran && ! empty($this->pembayaran->status_pembayaran)) {
            return $this->pembayaran->status_pembayaran;
        }

        return 'Belum Dibayar';
    }

    /**
     * Validasi status pesanan: hanya pesanan yang telah disetujui Admin yang dapat melihat instruksi pembayaran
     */
    public function isSiapDibayar(): bool
    {
        $status = trim((string) $this->status_penyewaan);

        // Status pesanan yang memenuhi kriteria telah disetujui Admin
        $allowedStatuses = [
            'Menunggu Pembayaran',
            'Disetujui',
            'Berjalan',
            'Siap Diambil',
            'Sedang Disewa',
        ];

        return in_array($status, $allowedStatuses, true);
    }
}

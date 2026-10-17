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
        'sumber_pesanan',
    ];

    protected function casts(): array
    {
        return [
            'id_pelanggan' => 'integer',
            'id_user' => 'integer',
            'tanggal_sewa' => 'date',
            'tanggal_kembali' => 'date',
            'sumber_pesanan' => 'string',
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
     * Hitung durasi sewa dalam hari berdasarkan tanggal sewa dan tanggal kembali.
     * Menggunakan aturan selisih tanggal dalam hari (misalnya sewa tanggal 10 dan kembali tanggal 13 dihitung 3 hari).
     */
    public static function hitungDurasiHari($tanggalSewa, $tanggalKembali): int
    {
        if (! $tanggalSewa || ! $tanggalKembali) {
            return 1;
        }

        $sewa = Carbon::parse($tanggalSewa)->startOfDay();
        $kembali = Carbon::parse($tanggalKembali)->startOfDay();

        $diff = (int) $sewa->diffInDays($kembali, false);

        return max(1, $diff);
    }

    /**
     * Hitung total biaya sewa untuk alat tertentu:
     * Total biaya setiap alat = tarif sewa per hari × jumlah alat × lama sewa dalam hari.
     */
    public static function hitungTotalBiaya(float $tarifSewaPerHari, int $jumlahAlat, int $durasiHari): float
    {
        return $tarifSewaPerHari * max(0, $jumlahAlat) * max(1, $durasiHari);
    }

    /**
     * Hitung sisa stok alat yang benar-benar tersedia untuk periode tanggal sewa tertentu.
     * Memperhitungkan stok fisik toko dikurangi pemesanan aktif lain yang beririsan periode sewanya.
     */
    public static function hitungStokTersediaPeriode(
        string $kodeAlat,
        mixed $tanggalSewa,
        mixed $tanggalKembali,
        ?string $excludeNoTransaksi = null
    ): int {
        $alat = Alat::where('kode_alat', $kodeAlat)->first();
        if (! $alat || $alat->kondisi !== 'Baik') {
            return 0;
        }

        $stokFisik = (int) $alat->stok;

        $sewaStr = Carbon::parse($tanggalSewa)->format('Y-m-d');
        $kembaliStr = Carbon::parse($tanggalKembali)->format('Y-m-d');

        $reserved = (int) DetailPenyewaan::where('kode_alat', $kodeAlat)
            ->whereHas('penyewaan', function ($q) use ($sewaStr, $kembaliStr, $excludeNoTransaksi) {
                $q->whereIn('status_penyewaan', ['Menunggu Pembayaran', 'Disetujui', 'Siap Diambil', 'Sedang Disewa', 'Berjalan'])
                    ->whereDate('tanggal_sewa', '<', $kembaliStr)
                    ->whereDate('tanggal_kembali', '>', $sewaStr);

                if ($excludeNoTransaksi) {
                    $q->where('no_transaksi', '!=', $excludeNoTransaksi);
                }
            })
            ->sum('jumlah');

        return max(0, $stokFisik - $reserved);
    }

    /**
     * Hitung durasi sewa dalam hari (minimal 1 hari)
     */
    public function getDurasiHariAttribute(): int
    {
        return self::hitungDurasiHari($this->tanggal_sewa, $this->tanggal_kembali);
    }

    /**
     * Generate format nomor transaksi TRX-YYYYMMDD-001
     */
    public static function generateNoTransaksi(): string
    {
        $datePrefix = 'TRX-'.date('Ymd');
        $latest = self::where('no_transaksi', 'like', "{$datePrefix}-%")
            ->orderBy('no_transaksi', 'desc')
            ->value('no_transaksi');

        $sequence = 1;
        if ($latest) {
            $parts = explode('-', $latest);
            $sequence = ((int) end($parts)) + 1;
        }

        return "{$datePrefix}-".str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Total tagihan transaksi = jumlah seluruh biaya setiap alat.
     * Jika transaksi legacy tidak memiliki detail, gunakan nilai total_biaya pembayaran historis.
     */
    public function getTotalBiayaAttribute(): float
    {
        $durasi = $this->durasi_hari;

        if ($this->relationLoaded('detailPenyewaan') && $this->detailPenyewaan->isNotEmpty()) {
            return (float) $this->detailPenyewaan->sum(function ($item) use ($durasi) {
                return self::hitungTotalBiaya((float) $item->tarif_sewa, (int) $item->jumlah, $durasi);
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
     * Relasi ke data pengambilan / serah terima alat
     */
    public function pengambilan(): HasOne
    {
        return $this->hasOne(Pengambilan::class, 'no_transaksi', 'no_transaksi');
    }

    /**
     * Relasi ke data pengembalian alat
     */
    public function pengembalian(): HasOne
    {
        return $this->hasOne(Pengembalian::class, 'no_transaksi', 'no_transaksi');
    }

    /**
     * Relasi ke denda melalui pengembalian
     */
    public function getDendaAttribute(): ?Denda
    {
        return $this->pengembalian?->denda;
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
            'Selesai',
        ];

        return in_array($status, $allowedStatuses, true);
    }

    /**
     * Periksa apakah pesanan memenuhi kriteria siap diambil (Disetujui & Pembayaran Lunas)
     */
    public function isSiapDiambil(): bool
    {
        $statusValid = in_array($this->status_penyewaan, ['Siap Diambil', 'Disetujui'], true);
        $pembayaranLunas = $this->pembayaran && $this->pembayaran->isLunas();

        return $statusValid && $pembayaranLunas;
    }

    /**
     * Periksa apakah pesanan sedang aktif disewa di lapangan
     */
    public function isSedangDisewa(): bool
    {
        return in_array($this->status_penyewaan, ['Sedang Disewa', 'Berjalan'], true);
    }

    /**
     * Periksa apakah pesanan telah selesai dikembalikan
     */
    public function isSelesai(): bool
    {
        return $this->status_penyewaan === 'Selesai';
    }

    /**
     * Periksa apakah pesanan dibuat melalui tablet on-site kasir
     */
    public function isOnsite(): bool
    {
        return ($this->sumber_pesanan ?? 'online') === 'on-site';
    }

    /**
     * Periksa apakah pesanan dibuat melalui portal daring (online)
     */
    public function isOnline(): bool
    {
        return ($this->sumber_pesanan ?? 'online') !== 'on-site';
    }
}

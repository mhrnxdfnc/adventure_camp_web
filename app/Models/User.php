<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id_user';
    public $timestamps = false;

    /**
     * Kolom yang dapat diisi secara massal
     */
    protected $fillable = [
        'id_pelanggan',
        'nama_user',
        'username',
        'password',
        'role',
        'status_aktif',
        'remember_token',
        'created_at',
    ];

    /**
     * Kolom yang disembunyikan saat serialisasi
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Type casting atribut model
     */
    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke data profil Pelanggan
     */
    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    /**
     * Pemeriksaan peran Administrator
     */
    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }

    /**
     * Pemeriksaan peran Pemilik (Strictly Read-Only)
     */
    public function isPemilik(): bool
    {
        return $this->role === 'Pemilik';
    }

    /**
     * Pemeriksaan peran Pengguna / Pelanggan
     */
    public function isPengguna(): bool
    {
        return in_array($this->role, ['Pengguna', 'Pelanggan'], true);
    }

    /**
     * Alias peran pelanggan
     */
    public function isPelanggan(): bool
    {
        return $this->isPengguna();
    }

    /**
     * Pemeriksaan status akun aktif
     */
    public function isActive(): bool
    {
        return (bool) $this->status_aktif;
    }

    /**
     * Accessor nama untuk kompatibilitas tampilan
     */
    public function getNameAttribute(): string
    {
        return $this->nama_user ?? '';
    }

    /**
     * Role normalisasi untuk tampilan konsisten
     */
    public function getNormalizedRoleAttribute(): string
    {
        return $this->isPengguna() ? 'Pengguna' : $this->role;
    }
}

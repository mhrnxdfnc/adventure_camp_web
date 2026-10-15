<?php

namespace Tests;

use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            $this->artisan('migrate');
            $this->seedTestUsers();
            $this->seedTestAlat();
        }
    }

    /**
     * Sediakan data uji dasar yang identik dengan database legacy
     */
    protected function seedTestUsers(): void
    {
        if (Schema::hasTable('pelanggan') && Pelanggan::count() === 0) {
            Pelanggan::create([
                'id_pelanggan' => 1,
                'nama_pelanggan' => 'Andi Pratama',
                'alamat' => 'Jl. Soekarno Hatta No. 10, Malang',
                'nomor_telepon' => '081234567890',
                'created_at' => now(),
            ]);
        }

        if (Schema::hasTable('users') && User::count() === 0) {
            User::create([
                'id_user' => 1,
                'nama_user' => 'Administrator',
                'username' => 'admin',
                'password' => 'admin123',
                'role' => 'Admin',
                'status_aktif' => 1,
            ]);

            User::create([
                'id_user' => 2,
                'nama_user' => 'Pemilik Adventure Camp',
                'username' => 'pemilik',
                'password' => 'pemilik123',
                'role' => 'Pemilik',
                'status_aktif' => 1,
            ]);

            User::create([
                'id_user' => 3,
                'id_pelanggan' => 1,
                'nama_user' => 'Pelanggan Demo',
                'username' => 'pelanggan',
                'password' => 'pelanggan123',
                'role' => 'Pelanggan',
                'status_aktif' => 1,
            ]);
        }
    }

    /**
     * Sediakan data uji alat camping dan riwayat detail penyewaan
     */
    protected function seedTestAlat(): void
    {
        if (Schema::hasTable('alat') && \App\Models\Alat::count() === 0) {
            \App\Models\Alat::create([
                'kode_alat' => 'ALT001',
                'nama_alat' => 'Tenda Dome 4 Orang',
                'kategori' => 'Tenda',
                'stok' => 1,
                'kondisi' => 'Baik',
                'tarif_sewa' => 50000.00,
            ]);

            \App\Models\Alat::create([
                'kode_alat' => 'ALT002',
                'nama_alat' => 'Sleeping Bag',
                'kategori' => 'Perlengkapan Tidur',
                'stok' => 4,
                'kondisi' => 'Baik',
                'tarif_sewa' => 25000.00,
            ]);

            \App\Models\Alat::create([
                'kode_alat' => 'ALT003',
                'nama_alat' => 'Carrier 60L',
                'kategori' => 'Tas',
                'stok' => 5,
                'kondisi' => 'Baik',
                'tarif_sewa' => 40000.00,
            ]);

            \App\Models\Alat::create([
                'kode_alat' => 'ALT004',
                'nama_alat' => 'Kompor Portable',
                'kategori' => 'Peralatan Masak',
                'stok' => 2,
                'kondisi' => 'Baik',
                'tarif_sewa' => 30000.00,
            ]);

            \App\Models\Alat::create([
                'kode_alat' => 'ALT005',
                'nama_alat' => 'Matras Camping',
                'kategori' => 'Perlengkapan Tidur',
                'stok' => 7,
                'kondisi' => 'Baik',
                'tarif_sewa' => 15000.00,
            ]);

            \App\Models\Alat::create([
                'kode_alat' => 'ALT006',
                'nama_alat' => 'Lentera Rusak',
                'kategori' => 'Penerangan',
                'stok' => 2,
                'kondisi' => 'Rusak',
                'tarif_sewa' => 10000.00,
            ]);
        }

        if (Schema::hasTable('detail_penyewaan') && \App\Models\DetailPenyewaan::count() === 0) {
            \App\Models\DetailPenyewaan::create([
                'id_detail' => 1,
                'no_transaksi' => 'TRX-20261005-001',
                'kode_alat' => 'ALT001',
                'jumlah' => 1,
                'tarif_sewa' => 50000.00,
            ]);
        }
    }
}

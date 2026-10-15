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
}

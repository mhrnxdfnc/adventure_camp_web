<?php

namespace Tests;

use App\Models\Alat;
use App\Models\DetailPenyewaan;
use App\Models\Pelanggan;
use App\Models\Penyewaan;
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
            $this->seedTestPenyewaan();
        }
    }

    /**
     * Sediakan data uji dasar yang identik dengan database legacy
     */
    protected function seedTestUsers(): void
    {
        if (Schema::hasTable('pelanggan')) {
            if (Pelanggan::find(1) === null) {
                Pelanggan::create([
                    'id_pelanggan' => 1,
                    'nama_pelanggan' => 'Andi Pratama',
                    'alamat' => 'Jl. Soekarno Hatta No. 10, Malang',
                    'nomor_telepon' => '081234567890',
                    'created_at' => now(),
                ]);
            }

            if (Pelanggan::find(2) === null) {
                Pelanggan::create([
                    'id_pelanggan' => 2,
                    'nama_pelanggan' => 'Budi Santoso',
                    'alamat' => 'Jl. Ijen No. 5, Malang',
                    'nomor_telepon' => '089876543210',
                    'created_at' => now(),
                ]);
            }
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

            User::create([
                'id_user' => 4,
                'id_pelanggan' => 2,
                'nama_user' => 'Budi Customer',
                'username' => 'budi',
                'password' => 'budi123',
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
        if (Schema::hasTable('alat') && Alat::count() === 0) {
            Alat::create([
                'kode_alat' => 'ALT001',
                'nama_alat' => 'Tenda Dome 4 Orang',
                'kategori' => 'Tenda',
                'stok' => 1,
                'kondisi' => 'Baik',
                'tarif_sewa' => 50000.00,
            ]);

            Alat::create([
                'kode_alat' => 'ALT002',
                'nama_alat' => 'Sleeping Bag',
                'kategori' => 'Perlengkapan Tidur',
                'stok' => 4,
                'kondisi' => 'Baik',
                'tarif_sewa' => 25000.00,
            ]);

            Alat::create([
                'kode_alat' => 'ALT003',
                'nama_alat' => 'Carrier 60L',
                'kategori' => 'Tas',
                'stok' => 5,
                'kondisi' => 'Baik',
                'tarif_sewa' => 40000.00,
            ]);

            Alat::create([
                'kode_alat' => 'ALT004',
                'nama_alat' => 'Kompor Portable',
                'kategori' => 'Peralatan Masak',
                'stok' => 2,
                'kondisi' => 'Baik',
                'tarif_sewa' => 30000.00,
            ]);

            Alat::create([
                'kode_alat' => 'ALT005',
                'nama_alat' => 'Matras Camping',
                'kategori' => 'Perlengkapan Tidur',
                'stok' => 7,
                'kondisi' => 'Baik',
                'tarif_sewa' => 15000.00,
            ]);

            Alat::create([
                'kode_alat' => 'ALT006',
                'nama_alat' => 'Lentera Rusak',
                'kategori' => 'Penerangan',
                'stok' => 2,
                'kondisi' => 'Rusak',
                'tarif_sewa' => 10000.00,
            ]);
        }
    }

    /**
     * Sediakan data uji transaksi penyewaan untuk pengujian instruksi pembayaran
     */
    protected function seedTestPenyewaan(): void
    {
        if (Schema::hasTable('penyewaan') && Penyewaan::count() === 0) {
            // Transaksi 1: Disetujui Admin (Menunggu Pembayaran), milik Pelanggan 1 (Andi Pratama)
            Penyewaan::create([
                'no_transaksi' => 'TRX-20261015-001',
                'id_pelanggan' => 1,
                'id_user' => 3,
                'tanggal_sewa' => '2026-10-16',
                'tanggal_kembali' => '2026-10-18',
                'status_penyewaan' => 'Menunggu Pembayaran',
            ]);

            // Transaksi 2: Belum disetujui Admin (Menunggu Konfirmasi), milik Pelanggan 1
            Penyewaan::create([
                'no_transaksi' => 'TRX-20261015-002',
                'id_pelanggan' => 1,
                'id_user' => 3,
                'tanggal_sewa' => '2026-10-20',
                'tanggal_kembali' => '2026-10-22',
                'status_penyewaan' => 'Menunggu Konfirmasi',
            ]);

            // Transaksi 3: Milik Pelanggan 2 (Budi Santoso) untuk tes IDOR
            Penyewaan::create([
                'no_transaksi' => 'TRX-20261015-999',
                'id_pelanggan' => 2,
                'id_user' => 4,
                'tanggal_sewa' => '2026-10-16',
                'tanggal_kembali' => '2026-10-18',
                'status_penyewaan' => 'Menunggu Pembayaran',
            ]);
        }

        if (Schema::hasTable('detail_penyewaan') && DetailPenyewaan::count() === 0) {
            // Detail untuk TRX-20261015-001: 2 hari
            // 1 x ALT001 @ 50.000 * 2 hari = 100.000
            DetailPenyewaan::create([
                'id_detail' => 1,
                'no_transaksi' => 'TRX-20261015-001',
                'kode_alat' => 'ALT001',
                'jumlah' => 1,
                'tarif_sewa' => 50000.00,
            ]);

            // 2 x ALT002 @ 25.000 * 2 hari = 100.000
            DetailPenyewaan::create([
                'id_detail' => 2,
                'no_transaksi' => 'TRX-20261015-001',
                'kode_alat' => 'ALT002',
                'jumlah' => 2,
                'tarif_sewa' => 25000.00,
            ]);

            // Detail untuk TRX-20261015-999 (Customer 2)
            DetailPenyewaan::create([
                'id_detail' => 3,
                'no_transaksi' => 'TRX-20261015-999',
                'kode_alat' => 'ALT003',
                'jumlah' => 1,
                'tarif_sewa' => 40000.00,
            ]);
        }
    }
}

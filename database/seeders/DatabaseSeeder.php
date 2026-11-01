<?php

namespace Database\Seeders;

use App\Models\Alat;
use App\Models\Pelanggan;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database secara idempoten dan aman.
     */
    public function run(): void
    {
        // 1. Seed Akun Admin Utama
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'nama_user' => 'Administrator Toko',
                'password' => Hash::make('admin123'),
                'role' => 'Admin',
                'status_aktif' => 1,
                'created_at' => now(),
            ]
        );

        // 2. Seed Akun Pemilik Usaha
        $pemilik = User::firstOrCreate(
            ['username' => 'pemilik'],
            [
                'nama_user' => 'Bapak Pemilik Usaha',
                'password' => Hash::make('pemilik123'),
                'role' => 'Pemilik',
                'status_aktif' => 1,
                'created_at' => now(),
            ]
        );

        // 3. Seed Pelanggan & Akun Pengguna Penyewa
        $pelanggan = Pelanggan::firstOrCreate(
            ['nomor_telepon' => '081234567890'],
            [
                'nama_pelanggan' => 'Ahmad Rinaldi',
                'alamat' => 'Jl. Kaliurang KM 12 No. 45, Sleman, D.I. Yogyakarta',
                'created_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['username' => 'pelanggan'],
            [
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'nama_user' => $pelanggan->nama_pelanggan,
                'password' => Hash::make('pelanggan123'),
                'role' => 'Pengguna',
                'status_aktif' => 1,
                'created_at' => now(),
            ]
        );

        // 4. Seed Master Data Supplier (Jika Kosong)
        if (Supplier::count() === 0) {
            $suppliers = [
                [
                    'nama_supplier' => 'PT Eigerindo Multi Produk',
                    'kontak_person' => 'Budi Santoso',
                    'no_telepon' => '081223344556',
                    'email' => 'sales@eiger-indonesia.com',
                    'alamat' => 'Jl. Kopo No. 120, Bandung, Jawa Barat',
                    'status_aktif' => 1,
                    'keterangan' => 'Distributor resmi perlengkapan outdoor brand Eiger dan bodypack',
                ],
                [
                    'nama_supplier' => 'CV Consina Outdoor Gear',
                    'kontak_person' => 'Siti Aminah',
                    'no_telepon' => '081399887766',
                    'email' => 'distribution@consina.co.id',
                    'alamat' => 'Jl. Buaran Raya No. 15, Duren Sawit, Jakarta Timur',
                    'status_aktif' => 1,
                    'keterangan' => 'Penyedia tenda gunung, carrier, sleeping bag, dan jaket windproof',
                ],
                [
                    'nama_supplier' => 'Arei Outdoorgear Indonesia',
                    'kontak_person' => 'Doni Wicaksono',
                    'no_telepon' => '082155443322',
                    'email' => 'order@arei-outdoorgear.id',
                    'alamat' => 'Jl. Gajah Mada No. 88, Surakarta, Jawa Tengah',
                    'status_aktif' => 1,
                    'keterangan' => 'Mitra pengadaan kompor portable, matras camping, dan hammock',
                ],
            ];

            foreach ($suppliers as $sup) {
                Supplier::create($sup);
            }
        }

        // 5. Seed Master Data Alat Camping (Jika Kosong)
        if (Alat::count() === 0) {
            $alatList = [
                [
                    'kode_alat' => 'ALT001',
                    'nama_alat' => 'Tenda Dome Double Layer 4P Kapasitas 4 Orang',
                    'kategori' => 'Tenda',
                    'tarif_sewa' => 45000,
                    'stok' => 10,
                    'kondisi' => 'Baik',
                ],
                [
                    'kode_alat' => 'ALT002',
                    'nama_alat' => 'Carrier 60L Ergonomic Backsystem Adventure',
                    'kategori' => 'Tas Carrier',
                    'tarif_sewa' => 30000,
                    'stok' => 8,
                    'kondisi' => 'Baik',
                ],
                [
                    'kode_alat' => 'ALT003',
                    'nama_alat' => 'Sleeping Bag Polar Bulu Hangat Suhu 5°C',
                    'kategori' => 'Perlengkapan Tidur',
                    'tarif_sewa' => 15000,
                    'stok' => 15,
                    'kondisi' => 'Baik',
                ],
                [
                    'kode_alat' => 'ALT004',
                    'nama_alat' => 'Kompor Portable Camping & Cookset Nesting DS-308',
                    'kategori' => 'Peralatan Masak',
                    'tarif_sewa' => 20000,
                    'stok' => 12,
                    'kondisi' => 'Baik',
                ],
                [
                    'kode_alat' => 'ALT005',
                    'nama_alat' => 'Headlamp LED Rechargeable 500 Lumens Tahan Air',
                    'kategori' => 'Penerangan',
                    'tarif_sewa' => 10000,
                    'stok' => 20,
                    'kondisi' => 'Baik',
                ],
            ];

            foreach ($alatList as $a) {
                Alat::create($a);
            }
        }
    }
}

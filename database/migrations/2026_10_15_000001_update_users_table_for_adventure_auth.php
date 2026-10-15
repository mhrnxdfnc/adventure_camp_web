<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            // 1. Tambahkan kolom remember_token jika belum ada
            if (!Schema::hasColumn('users', 'remember_token')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->rememberToken()->after('status_aktif');
                });
            }

            // 2. Perbarui enum role untuk mendukung 'Pengguna' secara berdampingan dengan 'Pelanggan'
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('Admin', 'Pemilik', 'Pelanggan', 'Pengguna') NOT NULL DEFAULT 'Pengguna'");
            }

            // 3. Tautkan akun pengguna demo (id 3) ke data pelanggan id 1 jika masih null
            $user3 = DB::table('users')->where('id_user', 3)->first();
            if ($user3 && $user3->id_pelanggan === null) {
                DB::table('users')->where('id_user', 3)->update(['id_pelanggan' => 1]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            if (Schema::hasColumn('users', 'remember_token')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropRememberToken();
                });
            }

            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('Admin', 'Pemilik', 'Pelanggan') NOT NULL DEFAULT 'Pelanggan'");
        }
    }
};

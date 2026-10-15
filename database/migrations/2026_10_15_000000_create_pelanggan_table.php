<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('pelanggan')) {
            Schema::create('pelanggan', function (Blueprint $table) {
                $table->increments('id_pelanggan');
                $table->string('nama_pelanggan', 100);
                $table->string('alamat', 255);
                $table->string('nomor_telepon', 20)->unique();
                $table->dateTime('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pertahankan tabel pelanggan untuk melindungi database legacy
    }
};

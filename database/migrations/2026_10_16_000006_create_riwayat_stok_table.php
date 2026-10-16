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
        if (! Schema::hasTable('riwayat_stok')) {
            Schema::create('riwayat_stok', function (Blueprint $table) {
                $table->increments('id_log');
                $table->string('kode_alat', 10)->index();
                $table->string('jenis_perubahan', 50)->index();
                $table->integer('stok_sebelum');
                $table->integer('jumlah_perubahan');
                $table->integer('stok_sesudah');
                $table->string('alasan', 255);
                $table->unsignedBigInteger('id_user')->index();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_stok');
    }
};

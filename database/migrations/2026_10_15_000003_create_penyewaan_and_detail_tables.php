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
        if (! Schema::hasTable('penyewaan')) {
            Schema::create('penyewaan', function (Blueprint $table) {
                $table->string('no_transaksi', 20)->primary();
                $table->unsignedInteger('id_pelanggan');
                $table->unsignedBigInteger('id_user')->nullable();
                $table->date('tanggal_sewa');
                $table->date('tanggal_kembali');
                $table->string('status_penyewaan', 50)->default('Menunggu Konfirmasi');
                $table->string('sumber_pesanan', 20)->default('online');
            });
        }

        if (! Schema::hasTable('detail_penyewaan')) {
            Schema::create('detail_penyewaan', function (Blueprint $table) {
                $table->increments('id_detail');
                $table->string('no_transaksi', 20);
                $table->string('kode_alat', 10);
                $table->integer('jumlah');
                $table->decimal('tarif_sewa', 12, 2);

                $table->foreign('kode_alat')->references('kode_alat')->on('alat');
            });
        }

        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->increments('id_log');
                $table->unsignedBigInteger('id_user');
                $table->string('aktivitas', 255);
                $table->string('modul', 100);
                $table->text('data_diproses')->nullable();
                $table->timestamp('waktu')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pertahankan tabel-tabel transaksi untuk melindungi database legacy
    }
};

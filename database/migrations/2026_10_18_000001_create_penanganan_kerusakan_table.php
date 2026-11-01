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
        if (! Schema::hasTable('penanganan_kerusakan')) {
            Schema::create('penanganan_kerusakan', function (Blueprint $table) {
                $table->increments('id_penanganan');
                $table->string('kode_alat', 10)->index();
                $table->unsignedInteger('id_detail')->nullable()->index();
                $table->string('jenis_tindakan', 50)->index(); // 'Menunggu Perbaikan', 'Catat Biaya Perbaikan', 'Selesai Perbaikan', 'Afkir/Pensiun'
                $table->integer('jumlah_unit')->default(1);
                $table->decimal('biaya_perbaikan', 14, 2)->default(0);
                $table->date('tanggal_tindakan');
                $table->string('pelaksana_teknisi', 100)->nullable();
                $table->text('keterangan')->nullable();
                $table->unsignedBigInteger('id_admin')->index();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penanganan_kerusakan');
    }
};

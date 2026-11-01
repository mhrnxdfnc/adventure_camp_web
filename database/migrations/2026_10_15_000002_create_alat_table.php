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
        if (! Schema::hasTable('alat')) {
            Schema::create('alat', function (Blueprint $table) {
                $table->string('kode_alat', 10)->primary();
                $table->string('nama_alat', 100);
                $table->string('kategori', 50);
                $table->integer('stok')->default(0);
                $table->enum('kondisi', ['Baik', 'Perlu Perbaikan', 'Rusak'])->default('Baik');
                $table->decimal('tarif_sewa', 12, 2);
                $table->dateTime('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pertahankan tabel alat untuk melindungi database legacy
    }
};

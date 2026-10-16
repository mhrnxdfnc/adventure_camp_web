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
        // 1. Tabel Supplier
        if (! Schema::hasTable('supplier')) {
            Schema::create('supplier', function (Blueprint $table) {
                $table->increments('id_supplier');
                $table->string('nama_supplier', 100);
                $table->string('kontak_person', 100)->nullable();
                $table->string('telepon', 25)->nullable();
                $table->text('alamat')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 2. Tabel Transaksi Pembelian Inventaris dari Supplier
        if (! Schema::hasTable('pembelian_inventaris')) {
            Schema::create('pembelian_inventaris', function (Blueprint $table) {
                $table->string('no_pembelian', 30)->primary();
                $table->date('tanggal_pembelian');
                $table->unsignedInteger('id_supplier')->index();
                $table->decimal('total_pembelian', 14, 2)->default(0);
                $table->unsignedBigInteger('id_admin')->index();
                $table->text('catatan')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 3. Tabel Rincian Detail Item Alat yang Dibeli
        if (! Schema::hasTable('detail_pembelian_inventaris')) {
            Schema::create('detail_pembelian_inventaris', function (Blueprint $table) {
                $table->increments('id_detail');
                $table->string('no_pembelian', 30)->index();
                $table->string('kode_alat', 10)->index();
                $table->integer('kuantitas');
                $table->decimal('harga_beli_satuan', 12, 2);
                $table->decimal('subtotal', 14, 2);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_pembelian_inventaris');
        Schema::dropIfExists('pembelian_inventaris');
        Schema::dropIfExists('supplier');
    }
};

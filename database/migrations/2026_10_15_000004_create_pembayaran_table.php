<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('pembayaran')) {
            Schema::create('pembayaran', function (Blueprint $table) {
                $table->string('no_pembayaran', 20)->primary();
                $table->string('no_transaksi', 20)->unique();
                $table->decimal('total_biaya', 12, 2);
                $table->decimal('jumlah_pembayaran', 12, 2)->default(0);
                $table->string('metode_pembayaran', 50)->default('Cash');
                $table->timestamp('tanggal_pembayaran')->useCurrent();
                $table->string('status_pembayaran', 50)->default('Belum Lunas');
                $table->string('bukti_pembayaran', 255)->nullable();
                $table->timestamp('waktu_unggah_bukti')->nullable();
                $table->unsignedBigInteger('id_admin')->nullable();
                $table->timestamp('waktu_konfirmasi')->nullable();
                $table->text('alasan_penolakan')->nullable();
            });
        } else {
            Schema::table('pembayaran', function (Blueprint $table) {
                if (! Schema::hasColumn('pembayaran', 'bukti_pembayaran')) {
                    $table->string('bukti_pembayaran', 255)->nullable();
                }
                if (! Schema::hasColumn('pembayaran', 'waktu_unggah_bukti')) {
                    $table->timestamp('waktu_unggah_bukti')->nullable();
                }
                if (! Schema::hasColumn('pembayaran', 'id_admin')) {
                    $table->unsignedBigInteger('id_admin')->nullable();
                }
                if (! Schema::hasColumn('pembayaran', 'waktu_konfirmasi')) {
                    $table->timestamp('waktu_konfirmasi')->nullable();
                }
                if (! Schema::hasColumn('pembayaran', 'alasan_penolakan')) {
                    $table->text('alasan_penolakan')->nullable();
                }
            });
        }

        // Pastikan kolom status dapat menampung status alur penyewaan dan pembayaran modern
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE penyewaan MODIFY COLUMN status_penyewaan VARCHAR(50) NOT NULL DEFAULT 'Menunggu Konfirmasi'");
            DB::statement("ALTER TABLE pembayaran MODIFY COLUMN status_pembayaran VARCHAR(50) NOT NULL DEFAULT 'Belum Lunas'");
            DB::statement("ALTER TABLE pembayaran MODIFY COLUMN metode_pembayaran VARCHAR(50) NOT NULL DEFAULT 'Cash'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pertahankan tabel pembayaran untuk menjaga integritas data riwayat transaksi
    }
};

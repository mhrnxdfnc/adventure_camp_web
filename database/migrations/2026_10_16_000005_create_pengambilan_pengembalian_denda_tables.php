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
        // 1. Tabel pencatatan serah terima / pengambilan alat camping
        if (! Schema::hasTable('pengambilan')) {
            Schema::create('pengambilan', function (Blueprint $table) {
                $table->increments('id_pengambilan');
                $table->string('no_transaksi', 20)->unique();
                $table->timestamp('tanggal_pengambilan')->useCurrent();
                $table->unsignedBigInteger('id_admin')->nullable();
                $table->string('nama_penerima', 100)->nullable();
                $table->text('catatan')->nullable();
            });
        }

        // 2. Tabel pengembalian alat camping (kompatibel dengan schema legacy)
        if (! Schema::hasTable('pengembalian')) {
            Schema::create('pengembalian', function (Blueprint $table) {
                $table->increments('id_pengembalian');
                $table->string('no_transaksi', 20)->unique();
                $table->date('tanggal_pengembalian_aktual');
                $table->string('kondisi_alat', 100);
                $table->string('status_pengembalian', 50)->default('Selesai');
                $table->unsignedBigInteger('id_admin')->nullable();
                $table->text('catatan')->nullable();
            });
        } else {
            Schema::table('pengembalian', function (Blueprint $table) {
                if (! Schema::hasColumn('pengembalian', 'id_admin')) {
                    $table->unsignedBigInteger('id_admin')->nullable();
                }
                if (! Schema::hasColumn('pengembalian', 'catatan')) {
                    $table->text('catatan')->nullable();
                }
            });
        }

        // 3. Tabel denda keterlambatan / kerusakan (kompatibel dengan schema legacy)
        if (! Schema::hasTable('denda')) {
            Schema::create('denda', function (Blueprint $table) {
                $table->increments('id_denda');
                $table->unsignedInteger('id_pengembalian')->unique();
                $table->decimal('tarif_denda', 12, 2)->default(0);
                $table->decimal('nominal_denda', 12, 2)->default(0);
                $table->string('alasan_denda', 255)->nullable();
            });
        }

        // 4. Kolom pelacakan kuantitas serah terima dan kondisi aktual pada detail_penyewaan
        if (Schema::hasTable('detail_penyewaan')) {
            Schema::table('detail_penyewaan', function (Blueprint $table) {
                if (! Schema::hasColumn('detail_penyewaan', 'jumlah_diserahkan')) {
                    $table->integer('jumlah_diserahkan')->nullable()->default(0);
                }
                if (! Schema::hasColumn('detail_penyewaan', 'jumlah_kembali_baik')) {
                    $table->integer('jumlah_kembali_baik')->nullable()->default(0);
                }
                if (! Schema::hasColumn('detail_penyewaan', 'jumlah_rusak')) {
                    $table->integer('jumlah_rusak')->nullable()->default(0);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pertahankan tabel riwayat transaksi untuk melindungi integritas database operasional
    }
};

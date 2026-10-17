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
        if (Schema::hasTable('detail_penyewaan')) {
            Schema::table('detail_penyewaan', function (Blueprint $table) {
                if (! Schema::hasColumn('detail_penyewaan', 'jumlah_perlu_perbaikan')) {
                    $table->integer('jumlah_perlu_perbaikan')->nullable()->default(0)->after('jumlah_kembali_baik');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('detail_penyewaan')) {
            Schema::table('detail_penyewaan', function (Blueprint $table) {
                if (Schema::hasColumn('detail_penyewaan', 'jumlah_perlu_perbaikan')) {
                    $table->dropColumn('jumlah_perlu_perbaikan');
                }
            });
        }
    }
};

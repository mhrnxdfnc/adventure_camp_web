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
                if (! Schema::hasColumn('detail_penyewaan', 'jumlah_diperbaiki')) {
                    $table->integer('jumlah_diperbaiki')->nullable()->default(0)->after('jumlah_perlu_perbaikan');
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
                if (Schema::hasColumn('detail_penyewaan', 'jumlah_diperbaiki')) {
                    $table->dropColumn('jumlah_diperbaiki');
                }
            });
        }
    }
};

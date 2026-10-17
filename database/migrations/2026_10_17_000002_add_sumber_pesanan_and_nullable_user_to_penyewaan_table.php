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
        if (Schema::hasTable('penyewaan')) {
            Schema::table('penyewaan', function (Blueprint $table) {
                if (! Schema::hasColumn('penyewaan', 'sumber_pesanan')) {
                    $table->string('sumber_pesanan', 20)->default('online')->after('status_penyewaan');
                }
            });

            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE penyewaan MODIFY id_user INT NULL;');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('penyewaan') && Schema::hasColumn('penyewaan', 'sumber_pesanan')) {
            Schema::table('penyewaan', function (Blueprint $table) {
                $table->dropColumn('sumber_pesanan');
            });
        }
    }
};

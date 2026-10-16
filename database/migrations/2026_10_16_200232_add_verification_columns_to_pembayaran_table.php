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
        Schema::table('pembayaran', function (Blueprint $table) {
            if (! Schema::hasColumn('pembayaran', 'bukti_pembayaran')) {
                $table->string('bukti_pembayaran', 255)->nullable()->after('status_pembayaran');
            }
            if (! Schema::hasColumn('pembayaran', 'waktu_unggah_bukti')) {
                $table->timestamp('waktu_unggah_bukti')->nullable()->after('bukti_pembayaran');
            }
            if (! Schema::hasColumn('pembayaran', 'id_admin')) {
                $table->unsignedBigInteger('id_admin')->nullable()->after('waktu_unggah_bukti');
            }
            if (! Schema::hasColumn('pembayaran', 'waktu_konfirmasi')) {
                $table->timestamp('waktu_konfirmasi')->nullable()->after('id_admin');
            }
            if (! Schema::hasColumn('pembayaran', 'alasan_penolakan')) {
                $table->text('alasan_penolakan')->nullable()->after('waktu_konfirmasi');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('pembayaran', 'alasan_penolakan') ? 'alasan_penolakan' : null,
                Schema::hasColumn('pembayaran', 'waktu_konfirmasi') ? 'waktu_konfirmasi' : null,
                Schema::hasColumn('pembayaran', 'id_admin') ? 'id_admin' : null,
                Schema::hasColumn('pembayaran', 'waktu_unggah_bukti') ? 'waktu_unggah_bukti' : null,
                Schema::hasColumn('pembayaran', 'bukti_pembayaran') ? 'bukti_pembayaran' : null,
            ]));
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('toko_buka')->default(true)->after('no_whatsapp');                      // saklar buka/tutup manual
            $table->unsignedSmallInteger('batas_pembayaran_menit')->default(60)->after('minimum_pesanan'); // batas waktu bayar
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', fn (Blueprint $table) => $table->dropColumn(['toko_buka', 'batas_pembayaran_menit']));
    }
};

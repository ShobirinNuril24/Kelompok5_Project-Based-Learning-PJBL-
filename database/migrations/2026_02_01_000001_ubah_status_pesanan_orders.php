<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Langkah 1: ENUM sementara berisi nilai lama DAN baru, supaya data lama bisa dipindahkan.
        DB::statement("ALTER TABLE orders MODIFY status_pesanan ENUM('Menunggu Pembayaran','Menunggu Konfirmasi','Sedang Disiapkan','Diproses','Siap Diambil','Sedang Diantarkan','Selesai','Dibatalkan','Kedaluwarsa') NOT NULL DEFAULT 'Menunggu Konfirmasi'");

        // Langkah 2: "Sedang Disiapkan" diganti nama menjadi "Diproses".
        DB::table('orders')->where('status_pesanan', 'Sedang Disiapkan')->update(['status_pesanan' => 'Diproses']);

        // Langkah 3: ENUM final.
        DB::statement("ALTER TABLE orders MODIFY status_pesanan ENUM('Menunggu Pembayaran','Menunggu Konfirmasi','Diproses','Siap Diambil','Sedang Diantarkan','Selesai','Dibatalkan','Kedaluwarsa') NOT NULL DEFAULT 'Menunggu Konfirmasi'");

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('batas_bayar_at')->nullable()->after('status_pembayaran')->index();
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('batas_bayar_at'));

        DB::table('orders')->where('status_pesanan', 'Menunggu Pembayaran')->update(['status_pesanan' => 'Menunggu Konfirmasi']);
        DB::table('orders')->where('status_pesanan', 'Kedaluwarsa')->update(['status_pesanan' => 'Dibatalkan']);
        DB::table('orders')->where('status_pesanan', 'Diproses')->update(['status_pesanan' => 'Menunggu Konfirmasi']);

        DB::statement("ALTER TABLE orders MODIFY status_pesanan ENUM('Menunggu Konfirmasi','Sedang Disiapkan','Siap Diambil','Sedang Diantarkan','Selesai','Dibatalkan') NOT NULL DEFAULT 'Menunggu Konfirmasi'");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel ini hanya berisi SATU baris pengaturan toko.
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_toko');
            $table->text('alamat_toko')->nullable();
            $table->string('no_whatsapp', 20);
            $table->json('jadwal_operasional');

            // Tambahan hasil keputusan #4 (pembayaran tanpa payment gateway)
            $table->string('logo')->nullable();
            $table->string('nama_bank', 100)->nullable();
            $table->string('no_rekening', 50)->nullable();
            $table->string('atas_nama_rekening')->nullable();
            $table->string('gambar_qris')->nullable();

            // Tambahan hasil keputusan #9 (nilai ORD §11 dijadikan pengaturan)
            $table->unsignedInteger('ambang_stok_menipis')->default(10);
            $table->unsignedInteger('minimum_pesanan')->default(0);   // 0 = tanpa minimum
            $table->decimal('radius_antar_km', 4, 1)->nullable();     // keterangan saja, admin cek manual
            $table->text('catatan_pengantaran')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};

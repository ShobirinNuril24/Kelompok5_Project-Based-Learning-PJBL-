<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // restrictOnDelete: produk yang punya riwayat pesanan tidak bisa dihapus (nonaktifkan saja)
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('nama_produk_snapshot');   // nama saat checkout
            $table->unsignedInteger('harga_snapshot'); // harga saat checkout
            $table->unsignedInteger('kuantitas');
            $table->unsignedInteger('subtotal');       // harga_snapshot x kuantitas
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

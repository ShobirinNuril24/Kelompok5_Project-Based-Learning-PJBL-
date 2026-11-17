<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: kategori yang masih punya produk tidak bisa dihapus
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('nama_produk');
            $table->string('slug')->unique();          // tambahan dari wireframe
            $table->text('deskripsi')->nullable();
            $table->string('satuan', 50);              // tambahan dari wireframe (kg, dus, pouch, ...)
            $table->unsignedInteger('harga');          // unsigned: tidak boleh negatif
            $table->unsignedInteger('stok')->default(0); // unsigned: stok tidak bisa negatif di level database
            $table->string('gambar')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_unggulan')->default(false); // tambahan dari wireframe (produk unggulan beranda)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

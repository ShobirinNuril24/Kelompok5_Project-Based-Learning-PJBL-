<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Catatan transaksi pembayaran. Saat ini diisi dan diverifikasi manual oleh admin (provider "manual").
    // Struktur ini disiapkan agar integrasi payment gateway di Phase 4 cukup mengisi provider, referensi, dan payload.
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30)->default('manual');
            $table->string('referensi')->nullable()->unique();   // ID transaksi dari gateway
            $table->unsignedInteger('jumlah');
            $table->enum('status', ['Menunggu', 'Berhasil', 'Gagal', 'Kedaluwarsa', 'Dibatalkan'])->default('Menunggu')->index();
            $table->timestamp('dibayar_at')->nullable();
            $table->json('payload')->nullable();                 // respons mentah gateway (Phase 4)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

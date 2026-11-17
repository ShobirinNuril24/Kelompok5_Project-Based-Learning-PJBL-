<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pesanan', 50)->unique();
            $table->string('nama_pelanggan');
            $table->string('no_whatsapp', 20);

            // Nilai ENUM harus sama dengan konstanta di App\Models\Order
            $table->enum('metode_pengambilan', ['Ambil di Toko', 'Diantarkan']);
            $table->text('alamat_pengantaran')->nullable();
            $table->enum('metode_pembayaran', ['Tunai', 'Transfer/QRIS']);

            $table->unsignedInteger('ongkir')->default(0);   // diisi manual oleh admin (keputusan #2)
            $table->unsignedInteger('total_harga');          // jumlah subtotal item + ongkir

            $table->enum('status_pesanan', [
                'Menunggu Konfirmasi', 'Sedang Disiapkan', 'Siap Diambil',
                'Sedang Diantarkan', 'Selesai', 'Dibatalkan',
            ])->default('Menunggu Konfirmasi')->index();
            $table->enum('status_pembayaran', ['Belum Dibayar', 'Menunggu Verifikasi', 'Lunas'])
                ->default('Belum Dibayar')->index();

            $table->text('catatan_admin')->nullable();
            $table->string('public_token', 64)->unique();    // akses struk guest
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

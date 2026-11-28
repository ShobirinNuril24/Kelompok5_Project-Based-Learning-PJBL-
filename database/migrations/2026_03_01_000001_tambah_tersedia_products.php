<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// D-3 (Decision Gate 28 Nov): stok dikelola sebagai Ada/Habis. Kolom `stok` lama tidak dihapus
// (aditif, data aman) tetapi tidak dipakai lagi oleh aplikasi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('tersedia')->default(true)->after('stok');
        });

        // Nilai awal dari data lama: stok 0 = Habis.
        DB::table('products')->update(['tersedia' => DB::raw('stok > 0')]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tersedia');
        });
    }
};

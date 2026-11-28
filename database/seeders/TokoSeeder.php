<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class TokoSeeder extends Seeder
{
    public function run(): void
    {
        // Jadwal awal sesuai Decision Gate 28 Nov (D-5): semua hari 05.30–11.30 WIB.
        $jadwal = [];
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $hari) {
            $jadwal[$hari] = [
                'open' => '05:30',
                'close' => '11:30',
                'is_open' => true,
            ];
        }

        // Hanya dibuat jika belum ada. Nomor WhatsApp di bawah ini contoh: ganti lewat Pengaturan Toko.
        StoreSetting::firstOrCreate([], [
            'nama_toko' => 'Toko Sepuluh',
            'alamat_toko' => 'Blok B-2, Pasar Pagesangan, Surabaya',
            'no_whatsapp' => '081234567890',
            'jadwal_operasional' => $jadwal,
        ]);
    }
}

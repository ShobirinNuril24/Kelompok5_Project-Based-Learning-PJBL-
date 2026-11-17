<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class TokoSeeder extends Seeder
{
    public function run(): void
    {
        // Jadwal awal sesuai ORD 2.1: Jumat tutup 11.00, hari lain 11.30.
        $jadwal = [];
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $hari) {
            $jadwal[$hari] = [
                'open' => '05:30',
                'close' => $hari === 'friday' ? '11:00' : '11:30',
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

<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KatalogSeeder extends Seeder
{
    public function run(): void
    {
        // [nama, satuan, harga, stok, unggulan, aktif]
        $katalog = [
            'Beras' => [
                ['Beras Pandan Wangi 5 kg', 'Kemasan', 78000, 30, true, true],
                ['Beras Rojo Lele 5 kg', 'Karung', 68000, 12, false, true],
            ],
            'Mie' => [
                ['Mie Instan Goreng', 'Bungkus', 3500, 5, false, true],       // stok menipis
                ['Bihun Jagung 250 g', 'Bungkus', 6000, 40, false, true],
            ],
            'Tepung' => [
                ['Tepung Tapioka 500 g', 'Kemasan', 9000, 2, false, true],    // stok menipis
                ['Tepung Terigu 1 kg', 'Kemasan', 13500, 40, true, true],
            ],
            'Minyak' => [
                ['Minyak Goreng 2 Liter', 'Pouch', 36000, 25, true, true],
            ],
            'Gula & Garam' => [
                ['Gula Pasir 1 kg', 'Kemasan', 17500, 8, false, true],        // stok menipis
                ['Garam Dapur 250 g', 'Bungkus', 3000, 60, false, true],
            ],
            'Bumbu' => [
                ['Bawang Merah 500 g', 'Kemasan', 16000, 0, false, true],     // stok habis
                ['Telur Ayam 1 kg', 'Kg', 27000, 15, false, false],           // nonaktif
            ],
        ];

        foreach ($katalog as $namaKategori => $produk) {
            $kategori = Category::firstOrCreate(
                ['slug' => Str::slug($namaKategori)],
                ['nama_kategori' => $namaKategori, 'deskripsi' => "Kategori {$namaKategori}"]
            );

            foreach ($produk as [$nama, $satuan, $harga, $stok, $unggulan, $aktif]) {
                Product::firstOrCreate(['slug' => Str::slug($nama)], [
                    'category_id' => $kategori->id,
                    'nama_produk' => $nama,
                    'deskripsi' => $nama,
                    'satuan' => $satuan,
                    'harga' => $harga,
                    'stok' => $stok,
                    'tersedia' => $stok > 0, // D-3: Ada/Habis
                    'is_unggulan' => $unggulan,
                    'is_active' => $aktif,
                ]);
            }
        }
    }
}

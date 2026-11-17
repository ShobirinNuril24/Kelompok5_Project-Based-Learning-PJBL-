<?php

namespace Tests\Feature;

use Database\Seeders\TokoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutPelangganTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();           // tes tidak butuh hasil build Tailwind
        $this->seed(TokoSeeder::class); // header dan footer membaca pengaturan toko
    }

    public function test_beranda_menampilkan_header_dan_footer(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Toko Sepuluh')
            ->assertSee('Cek Status Pesanan')
            ->assertSee('https://wa.me/6281234567890', false)   // nomor 08xx dinormalisasi
            ->assertSee('05.30–11.00 WIB');                      // jadwal Jumat dari database
    }

    public function test_halaman_menu_bisa_dibuka(): void
    {
        foreach (['/produk', '/keranjang', '/status-pesanan', '/tentang'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}

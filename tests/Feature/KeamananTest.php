<?php

namespace Tests\Feature;

use Database\Seeders\KatalogSeeder;
use Database\Seeders\TokoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class KeamananTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([TokoSeeder::class, KatalogSeeder::class]);
        Carbon::setTestNow(Carbon::parse('2026-11-23 08:00', 'Asia/Jakarta')); // Senin, toko buka
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_header_keamanan_dasar_ada_di_halaman_publik(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    // ENG-07: tautan struk berisi token rahasia; tidak boleh bocor lewat Referer atau diindeks.
    public function test_struk_tidak_mengirim_referer_dan_tidak_diindeks(): void
    {
        $this->post('/keranjang', ['produk_id' => \App\Models\Product::where('slug', 'beras-pandan-wangi-5-kg')->value('id'), 'jumlah' => 1]);
        $this->post('/checkout', [
            'nama_pelanggan' => 'Budi', 'no_whatsapp' => '081234567890',
            'metode_pengambilan' => 'Ambil di Toko', 'metode_pembayaran' => 'Tunai',
        ])->assertRedirect();

        $order = \App\Models\Order::firstOrFail();
        $this->get("/pesanan/{$order->public_token}")
            ->assertOk()
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    // ER03-SEC-003: tanpa debug, error tidak membocorkan detail teknis dan tampil dalam bahasa Indonesia.
    public function test_halaman_404_berbahasa_indonesia(): void
    {
        config(['app.debug' => false]);

        $this->get('/pesanan/token-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Kembali ke beranda');
    }

    public function test_error_server_tidak_membocorkan_detail_saat_debug_mati(): void
    {
        config(['app.debug' => false]);
        Route::get('/_uji-error', fn () => throw new \RuntimeException('rahasia-internal-xyz'));

        $this->get('/_uji-error')
            ->assertStatus(500)
            ->assertSee('Terjadi kesalahan')
            ->assertDontSee('rahasia-internal-xyz')
            ->assertDontSee('RuntimeException');
    }

    // Ditemukan saat uji E2E (SEC-05): file di atas batas PHP ditolak dengan pesan bahasa Inggris.
    public function test_unggahan_gagal_diberi_pesan_bahasa_indonesia(): void
    {
        app()->setLocale('id'); // sama dengan APP_LOCALE=id di .env.example
        $this->actingAs(\App\Models\User::factory()->create());
        $produk = \App\Models\Product::where('slug', 'garam-dapur-250-g')->firstOrFail();
        $gagal = new \Illuminate\Http\UploadedFile(__FILE__, 'besar.png', 'image/png', UPLOAD_ERR_INI_SIZE, true);

        $this->put("/admin/produk/{$produk->id}", [
            'nama_produk' => $produk->nama_produk, 'category_id' => $produk->category_id,
            'satuan' => $produk->satuan, 'harga' => $produk->harga, 'tersedia' => 1,
            'gambar' => $gagal,
        ])->assertSessionHasErrors(['gambar' => 'Gambar produk gagal diunggah. Pastikan ukuran file maksimal 2 MB.']);

        $this->assertNull($produk->fresh()->gambar);
    }

    public function test_cek_status_dibatasi_dan_pesan_429_berbahasa_indonesia(): void
    {
        config(['app.debug' => false]);
        $data = ['nomor_pesanan' => 'SBK-20261123-XXXX', 'no_whatsapp' => '081234567890'];

        for ($i = 0; $i < 10; $i++) {
            $this->post('/status-pesanan', $data)->assertStatus(302);
        }

        $this->post('/status-pesanan', $data)
            ->assertStatus(429)
            ->assertSee('Terlalu banyak permintaan');
    }

    // DEF-UJI-03 (ditemukan saat uji E2E): batas cek status dan checkout dulu berbagi satu kuota per IP.
    public function test_kuota_cek_status_tidak_menghabiskan_kuota_checkout(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/status-pesanan', ['nomor_pesanan' => 'SBK-20261123-XXXX', 'no_whatsapp' => '081234567890']);
        }

        $this->post('/keranjang', ['produk_id' => \App\Models\Product::where('slug', 'beras-pandan-wangi-5-kg')->value('id'), 'jumlah' => 1]);
        $this->post('/checkout', [
            'nama_pelanggan' => 'Budi', 'no_whatsapp' => '081234567890',
            'metode_pengambilan' => 'Ambil di Toko', 'metode_pembayaran' => 'Tunai',
        ])->assertRedirect();

        $this->assertSame(1, \App\Models\Order::count());
    }
}

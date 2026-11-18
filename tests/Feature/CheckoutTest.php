<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Database\Seeders\KatalogSeeder;
use Database\Seeders\TokoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Product $produk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([TokoSeeder::class, KatalogSeeder::class]);
        Carbon::setTestNow(Carbon::parse('2026-11-23 08:00', 'Asia/Jakarta')); // Senin, toko buka
        $this->produk = Product::where('slug', 'beras-pandan-wangi-5-kg')->first(); // stok 30, Rp 78.000
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function kirim(array $ubah = [])
    {
        return $this->post('/checkout', array_merge([
            'nama_pelanggan' => 'Budi',
            'no_whatsapp' => '081234567890',
            'metode_pengambilan' => 'Ambil di Toko',
            'metode_pembayaran' => 'Tunai',
        ], $ubah));
    }

    public function test_checkout_berhasil_mengurangi_stok_satu_kali(): void
    {
        $this->withSession(['keranjang' => [$this->produk->id => 2]])->kirim()->assertRedirect();

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame(28, $this->produk->fresh()->stok);
        $this->assertSame(156000, $order->total_harga);
        $this->assertSame('Menunggu Konfirmasi', $order->status_pesanan);
        $this->assertSame('Belum Dibayar', $order->status_pembayaran);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id, 'nama_produk_snapshot' => 'Beras Pandan Wangi 5 kg',
            'harga_snapshot' => 78000, 'kuantitas' => 2, 'subtotal' => 156000,
        ]);
    }

    public function test_checkout_ditolak_di_luar_jam_operasional(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 05:29', 'Asia/Jakarta'));

        $this->withSession(['keranjang' => [$this->produk->id => 2]])->kirim()
            ->assertRedirect(route('keranjang.index'))
            ->assertSessionHas('toko_tutup');

        $this->assertSame(0, Order::count());
        $this->assertSame(30, $this->produk->fresh()->stok);
    }

    public function test_antar_tanpa_alamat_ditolak(): void
    {
        $this->withSession(['keranjang' => [$this->produk->id => 1]])
            ->kirim(['metode_pengambilan' => 'Diantarkan'])
            ->assertSessionHasErrors('alamat_pengantaran');

        $this->assertSame(0, Order::count());
    }

    public function test_stok_kurang_ditolak_dan_stok_tidak_berubah(): void
    {
        $this->withSession(['keranjang' => [$this->produk->id => 99]])->kirim()
            ->assertSessionHasErrors('keranjang');

        $this->assertSame(0, Order::count());
        $this->assertSame(30, $this->produk->fresh()->stok);
    }

    public function test_pembatalan_mengembalikan_stok_hanya_satu_kali(): void
    {
        $this->withSession(['keranjang' => [$this->produk->id => 2]])->kirim();
        $order = Order::first();
        $this->assertSame(28, $this->produk->fresh()->stok);

        $this->post('/pesanan/'.$order->public_token.'/batal');
        $this->assertSame(30, $this->produk->fresh()->stok);
        $this->assertSame('Dibatalkan', $order->fresh()->status_pesanan);

        $this->post('/pesanan/'.$order->public_token.'/batal'); // request diulang
        $this->assertSame(30, $this->produk->fresh()->stok);     // stok tidak bertambah lagi
    }
}

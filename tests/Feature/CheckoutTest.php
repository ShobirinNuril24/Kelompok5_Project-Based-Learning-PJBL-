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
        $this->produk = Product::where('slug', 'beras-pandan-wangi-5-kg')->first(); // Rp 78.000, tersedia (Ada)
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

    // D-3: checkout memakai status Ada/Habis, bukan angka stok. Angka stok lama diabaikan dan tidak diubah.
    public function test_checkout_tunai_memakai_status_ada_bukan_angka_stok(): void
    {
        $this->produk->update(['stok' => 0, 'tersedia' => true]);
        $this->withSession(['keranjang' => [$this->produk->id => 2]])->kirim()->assertRedirect();

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame(0, $this->produk->fresh()->stok);
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
    }

    public function test_antar_tanpa_alamat_ditolak(): void
    {
        $this->withSession(['keranjang' => [$this->produk->id => 1]])
            ->kirim(['metode_pengambilan' => 'Diantarkan'])
            ->assertSessionHasErrors('alamat_pengantaran');

        $this->assertSame(0, Order::count());
    }

    // D-3: barang yang ditandai Habis setelah masuk keranjang ditolak saat checkout; tidak ada pesanan tersimpan.
    public function test_produk_habis_ditolak_saat_checkout(): void
    {
        $this->produk->update(['tersedia' => false]);
        $this->withSession(['keranjang' => [$this->produk->id => 2]])->kirim()
            ->assertSessionHasErrors('keranjang');

        $this->assertSame(0, Order::count());
        $this->get('/keranjang')->assertSee('Sedang habis.');
    }

    // D-3: produk Habis tidak bisa dimasukkan ke keranjang.
    public function test_produk_habis_tidak_bisa_masuk_keranjang(): void
    {
        $this->produk->update(['tersedia' => false]);
        $this->post('/keranjang', ['produk_id' => $this->produk->id])->assertSessionHas('err');

        $this->assertSame([], session('keranjang', []));
    }

    public function test_pembatalan_hanya_diproses_satu_kali(): void
    {
        $this->withSession(['keranjang' => [$this->produk->id => 2]])->kirim();
        $order = Order::first();

        $this->post('/pesanan/'.$order->public_token.'/batal');
        $this->assertSame('Dibatalkan', $order->fresh()->status_pesanan);
        $catatan = $order->fresh()->catatan_admin;

        $this->post('/pesanan/'.$order->public_token.'/batal')->assertSessionHas('err'); // request diulang
        $this->assertSame($catatan, $order->fresh()->catatan_admin);                      // tidak diproses lagi
    }

    public function test_transfer_ambil_di_toko_langsung_menunggu_pembayaran(): void
    {
        $this->withSession(['keranjang' => [$this->produk->id => 2]])
            ->kirim(['metode_pembayaran' => 'Transfer/QRIS']);

        $order = Order::first();
        $this->assertSame('Menunggu Pembayaran', $order->status_pesanan);
        $this->assertSame('Belum Dibayar', $order->status_pembayaran); // tidak otomatis lunas
        $this->assertTrue($order->batas_bayar_at->equalTo(now()->addMinutes(60)));
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'jumlah' => 156000, 'status' => 'Menunggu']);
    }

    public function test_transfer_antar_menunggu_konfirmasi_ongkir_dulu(): void
    {
        $this->withSession(['keranjang' => [$this->produk->id => 1]])
            ->kirim(['metode_pembayaran' => 'Transfer/QRIS', 'metode_pengambilan' => 'Diantarkan', 'alamat_pengantaran' => 'Jl. Pagesangan 1']);

        $order = Order::first();
        $this->assertSame('Menunggu Konfirmasi', $order->status_pesanan);
        $this->assertSame(0, $order->payments()->count());
    }

    public function test_pesanan_kedaluwarsa_diproses_satu_kali(): void
    {
        $this->withSession(['keranjang' => [$this->produk->id => 2]])->kirim(['metode_pembayaran' => 'Transfer/QRIS']);
        $order = Order::first();

        Carbon::setTestNow(now()->addMinutes(30)); // belum lewat batas bayar
        $this->assertSame(0, Order::prosesKedaluwarsa());
        $this->assertSame('Menunggu Pembayaran', $order->fresh()->status_pesanan);

        Carbon::setTestNow(now()->addMinutes(31)); // lewat batas bayar
        $this->assertSame(1, Order::prosesKedaluwarsa());
        $this->assertSame('Kedaluwarsa', $order->fresh()->status_pesanan);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'Kedaluwarsa']);

        $this->assertSame(0, Order::prosesKedaluwarsa()); // diulang: tidak diproses lagi
    }

    public function test_pesanan_kedaluwarsa_tidak_bisa_dianggap_lunas_atau_dibatalkan(): void
    {
        $this->withSession(['keranjang' => [$this->produk->id => 1]])->kirim(['metode_pembayaran' => 'Transfer/QRIS']);
        $order = Order::first();
        Carbon::setTestNow(now()->addMinutes(61));
        Order::prosesKedaluwarsa();

        $this->assertNotNull($order->fresh()->tandaiLunas());     // ditolak
        $this->assertFalse($order->fresh()->batalkan('admin'));   // status akhir tidak diproses lagi
        $this->assertSame('Kedaluwarsa', $order->fresh()->status_pesanan);
        $this->assertSame('Belum Dibayar', $order->fresh()->status_pembayaran);
    }

    // ---------- Phase 09.4 S1 (FR-027, FR-030, SEC-17) ----------

    public function test_pelanggan_tidak_bisa_membatalkan_pesanan_yang_sudah_lunas(): void
    {
        $this->withSession(['keranjang' => [$this->produk->id => 2]])->kirim(['metode_pembayaran' => 'Transfer/QRIS']);
        $order = Order::first();
        $order->tandaiLunas(); // Menunggu Pembayaran -> Menunggu Konfirmasi + Lunas

        $this->post('/pesanan/'.$order->public_token.'/batal')->assertSessionHas('err');

        $this->assertSame('Menunggu Konfirmasi', $order->fresh()->status_pesanan);
        $this->assertSame('Lunas', $order->fresh()->status_pembayaran);
        $this->get('/pesanan/'.$order->public_token)->assertDontSee('Ajukan Pembatalan');
    }

    public function test_pelanggan_tidak_bisa_membatalkan_pesanan_yang_sedang_diproses(): void
    {
        $this->withSession(['keranjang' => [$this->produk->id => 2]])->kirim();
        $order = Order::first();
        $order->update(['status_pesanan' => 'Diproses']);

        $this->post('/pesanan/'.$order->public_token.'/batal')->assertSessionHas('err');

        $this->assertSame('Diproses', $order->fresh()->status_pesanan);
    }

    public function test_produk_yang_sudah_dihapus_dibuang_dari_keranjang_dan_checkout_tetap_bisa(): void
    {
        $idTerhapus = Product::max('id') + 1000; // id yang tidak ada = produk sudah dihapus admin

        $this->withSession(['keranjang' => [$this->produk->id => 1, $idTerhapus => 2]])
            ->get('/keranjang')
            ->assertOk()
            ->assertSee('1 barang dihapus dari keranjang karena sudah tidak dijual.')
            ->assertSessionHas('keranjang', [$this->produk->id => 1]);

        $this->get('/checkout')->assertOk();
        $this->kirim()->assertRedirect();

        $this->assertSame(1, Order::count());
        $this->assertSame(1, Order::first()->items()->count());
    }

    public function test_katalog_menolak_parameter_array_dan_memperlakukan_wildcard_sebagai_teks(): void
    {
        $this->get('/produk?q[]=beras')->assertStatus(302);
        $this->get('/produk?kategori[]=sembako')->assertStatus(302);
        $this->get('/produk?q='.str_repeat('a', 101))->assertStatus(302);

        $this->get('/produk?q=%25')->assertOk()
            ->assertViewHas('produk', fn ($produk) => $produk->total() === 0); // tidak ada nama produk berisi "%"

        $this->get('/produk?q=Beras')->assertOk()
            ->assertViewHas('produk', fn ($produk) => $produk->getCollection()->contains('id', $this->produk->id));
    }
}

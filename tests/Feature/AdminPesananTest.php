<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\KatalogSeeder;
use Database\Seeders\TokoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPesananTest extends TestCase
{
    use RefreshDatabase;

    private Product $produk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([TokoSeeder::class, KatalogSeeder::class]);
        $this->produk = Product::where('slug', 'beras-pandan-wangi-5-kg')->first(); // stok 30, Rp 78.000
    }

    private function masuk(): void
    {
        $this->actingAs(User::factory()->create());
    }

    private function buatPesanan(array $ubah = []): Order
    {
        $order = Order::create(array_merge([
            'nomor_pesanan' => 'SBK-TEST-'.strtoupper(Str::random(4)),
            'nama_pelanggan' => 'Budi', 'no_whatsapp' => '081234567890',
            'metode_pengambilan' => 'Ambil di Toko', 'metode_pembayaran' => 'Tunai',
            'ongkir' => 0, 'total_harga' => 78000,
            'status_pesanan' => 'Menunggu Konfirmasi', 'status_pembayaran' => 'Belum Dibayar',
        ], $ubah));

        $order->items()->create([
            'product_id' => $this->produk->id, 'nama_produk_snapshot' => $this->produk->nama_produk,
            'harga_snapshot' => 78000, 'kuantitas' => 1, 'subtotal' => 78000,
        ]);
        $this->produk->decrement('stok', 1); // seperti checkout asli: stok sudah terpotong

        return $order;
    }

    public function test_halaman_admin_butuh_login(): void
    {
        foreach (['/admin', '/admin/pesanan'] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
    }

    public function test_dashboard_dan_daftar_pesanan_menampilkan_data_asli(): void
    {
        $this->masuk();
        $this->buatPesanan(['nama_pelanggan' => 'Siti Aminah']);

        $this->get('/admin')->assertOk()->assertSee('Siti Aminah');
        $this->get('/admin/pesanan')->assertOk()->assertSee('Siti Aminah');
    }

    public function test_pencarian_dan_filter_status(): void
    {
        $this->masuk();
        $this->buatPesanan(['nama_pelanggan' => 'Siti Aminah']);
        $this->buatPesanan(['nama_pelanggan' => 'Budi Santoso', 'status_pesanan' => 'Selesai']);

        $this->get('/admin/pesanan?q=Siti')->assertSee('Siti Aminah')->assertDontSee('Budi Santoso');
        $this->get('/admin/pesanan?status=Selesai')->assertSee('Budi Santoso')->assertDontSee('Siti Aminah');
    }

    public function test_detail_pesanan_menampilkan_data_yang_benar(): void
    {
        $this->masuk();
        $o = $this->buatPesanan(['nama_pelanggan' => 'Siti Aminah']);

        $this->get("/admin/pesanan/{$o->id}")->assertOk()
            ->assertSee($o->nomor_pesanan)->assertSee('Siti Aminah')->assertSee('Beras Pandan Wangi 5 kg')
            ->assertSee('https://wa.me/6281234567890', false);
    }

    public function test_status_tidak_boleh_melompat(): void
    {
        $this->masuk();
        $o = $this->buatPesanan();

        $this->patch("/admin/pesanan/{$o->id}/status", ['status' => 'Selesai'])->assertSessionHas('err');
        $this->assertSame('Menunggu Konfirmasi', $o->fresh()->status_pesanan);

        $this->patch("/admin/pesanan/{$o->id}/status", ['status' => 'Diproses'])->assertSessionHas('ok');
        $this->assertSame('Diproses', $o->fresh()->status_pesanan);
    }

    public function test_pesanan_antar_tidak_memakai_siap_diambil(): void
    {
        $this->masuk();
        $o = $this->buatPesanan(['metode_pengambilan' => 'Diantarkan', 'status_pesanan' => 'Diproses']);

        $this->patch("/admin/pesanan/{$o->id}/status", ['status' => 'Siap Diambil'])->assertSessionHas('err');
        $this->patch("/admin/pesanan/{$o->id}/status", ['status' => 'Sedang Diantarkan'])->assertSessionHas('ok'); // tunai/COD boleh
    }

    public function test_selesai_butuh_pembayaran_lunas(): void
    {
        $this->masuk();
        $o = $this->buatPesanan(['status_pesanan' => 'Siap Diambil']);

        $this->patch("/admin/pesanan/{$o->id}/status", ['status' => 'Selesai'])->assertSessionHas('err');

        $this->patch("/admin/pesanan/{$o->id}/pembayaran", ['status_pembayaran' => 'Lunas'])->assertSessionHas('ok');
        $this->patch("/admin/pesanan/{$o->id}/status", ['status' => 'Selesai'])->assertSessionHas('ok');
        $this->assertSame('Selesai', $o->fresh()->status_pesanan);

        // Pesanan selesai bersifat final.
        $this->patch("/admin/pesanan/{$o->id}/status", ['status' => 'Diproses'])->assertSessionHas('err');
    }

    public function test_transfer_antar_harus_lunas_sebelum_dikirim(): void
    {
        $this->masuk();
        $o = $this->buatPesanan(['metode_pengambilan' => 'Diantarkan', 'metode_pembayaran' => 'Transfer/QRIS', 'status_pesanan' => 'Diproses']);

        $this->patch("/admin/pesanan/{$o->id}/status", ['status' => 'Sedang Diantarkan'])->assertSessionHas('err');
    }

    public function test_lunas_memindahkan_menunggu_pembayaran_ke_menunggu_konfirmasi(): void
    {
        $this->masuk();
        $o = $this->buatPesanan(['metode_pembayaran' => 'Transfer/QRIS', 'status_pesanan' => 'Menunggu Pembayaran', 'batas_bayar_at' => now()->addHour()]);
        $o->payments()->create(['jumlah' => 78000]);

        $this->patch("/admin/pesanan/{$o->id}/pembayaran", ['status_pembayaran' => 'Lunas'])->assertSessionHas('ok');

        $this->assertSame('Menunggu Konfirmasi', $o->fresh()->status_pesanan);
        $this->assertSame('Lunas', $o->fresh()->status_pembayaran);
        $this->assertDatabaseHas('payments', ['order_id' => $o->id, 'status' => 'Berhasil']);
    }

    public function test_minta_pembayaran_untuk_pesanan_antar(): void
    {
        $this->masuk();
        $o = $this->buatPesanan(['metode_pengambilan' => 'Diantarkan', 'metode_pembayaran' => 'Transfer/QRIS', 'total_harga' => 83000, 'ongkir' => 5000]);

        $this->post("/admin/pesanan/{$o->id}/minta-pembayaran")->assertSessionHas('ok');

        $this->assertSame('Menunggu Pembayaran', $o->fresh()->status_pesanan);
        $this->assertNotNull($o->fresh()->batas_bayar_at);
        $this->assertDatabaseHas('payments', ['order_id' => $o->id, 'jumlah' => 83000]);
    }

    public function test_ubah_item_menyesuaikan_stok_total_dan_catatan(): void
    {
        $this->masuk();
        $o = $this->buatPesanan();            // stok 29, 1 item
        $item = $o->items()->first();

        $this->put("/admin/pesanan/{$o->id}/item", ['kuantitas' => [$item->id => 3], 'ongkir' => 0, 'disetujui' => '1'])
            ->assertRedirect("/admin/pesanan/{$o->id}");

        $this->assertSame(27, $this->produk->fresh()->stok);       // diambil 2 lagi
        $this->assertSame(234000, $o->fresh()->total_harga);        // 3 x 78.000
        $this->assertStringContainsString('disetujui pelanggan', $o->fresh()->catatan_admin);
    }

    public function test_ubah_item_wajib_persetujuan_pelanggan(): void
    {
        $this->masuk();
        $o = $this->buatPesanan();
        $item = $o->items()->first();

        $this->put("/admin/pesanan/{$o->id}/item", ['kuantitas' => [$item->id => 3], 'ongkir' => 0])
            ->assertSessionHasErrors('disetujui');

        $this->assertSame(78000, $o->fresh()->total_harga);
    }

    public function test_ubah_item_ditolak_jika_stok_kurang_dan_tidak_ada_yang_berubah(): void
    {
        $this->masuk();
        $o = $this->buatPesanan();
        $item = $o->items()->first();

        $this->put("/admin/pesanan/{$o->id}/item", ['kuantitas' => [$item->id => 99], 'ongkir' => 0, 'disetujui' => '1'])
            ->assertSessionHas('err');

        $this->assertSame(29, $this->produk->fresh()->stok);
        $this->assertSame(78000, $o->fresh()->total_harga);
    }

    public function test_item_tidak_bisa_diubah_setelah_selesai(): void
    {
        $this->masuk();
        $o = $this->buatPesanan(['status_pesanan' => 'Selesai']);
        $item = $o->items()->first();

        $this->put("/admin/pesanan/{$o->id}/item", ['kuantitas' => [$item->id => 2], 'ongkir' => 0, 'disetujui' => '1'])
            ->assertSessionHas('err');

        $this->assertSame(78000, $o->fresh()->total_harga);
    }

    public function test_admin_membatalkan_pesanan_mengembalikan_stok_satu_kali(): void
    {
        $this->masuk();
        $o = $this->buatPesanan();            // stok 29

        $this->patch("/admin/pesanan/{$o->id}/status", ['status' => 'Dibatalkan'])->assertSessionHas('ok');
        $this->assertSame(30, $this->produk->fresh()->stok);

        $this->patch("/admin/pesanan/{$o->id}/status", ['status' => 'Dibatalkan'])->assertSessionHas('err');
        $this->assertSame(30, $this->produk->fresh()->stok);
    }
}

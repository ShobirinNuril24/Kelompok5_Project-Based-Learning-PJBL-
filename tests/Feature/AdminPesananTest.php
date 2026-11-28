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
        $this->produk = Product::where('slug', 'beras-pandan-wangi-5-kg')->first(); // Rp 78.000, tersedia (Ada)
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

    public function test_ubah_item_menyesuaikan_total_dan_catatan(): void
    {
        $this->masuk();
        $o = $this->buatPesanan();            // 1 item
        $item = $o->items()->first();

        $this->put("/admin/pesanan/{$o->id}/item", ['kuantitas' => [$item->id => 3], 'ongkir' => 0, 'disetujui' => '1'])
            ->assertRedirect("/admin/pesanan/{$o->id}");

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

    // D-3: jumlah barang yang sedang Habis tidak bisa ditambah; mengurangi tetap boleh.
    public function test_ubah_item_ditolak_jika_produk_habis_dan_tidak_ada_yang_berubah(): void
    {
        $this->masuk();
        $o = $this->buatPesanan();
        $item = $o->items()->first();
        $item->update(['kuantitas' => 2, 'subtotal' => 156000]);
        $o->update(['total_harga' => 156000]);
        $this->produk->update(['tersedia' => false]);

        $this->put("/admin/pesanan/{$o->id}/item", ['kuantitas' => [$item->id => 3], 'ongkir' => 0, 'disetujui' => '1'])
            ->assertSessionHas('err');
        $this->assertSame(156000, $o->fresh()->total_harga);

        $this->put("/admin/pesanan/{$o->id}/item", ['kuantitas' => [$item->id => 1], 'ongkir' => 0, 'disetujui' => '1'])
            ->assertSessionHas('ok');
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

    public function test_admin_membatalkan_pesanan_hanya_satu_kali(): void
    {
        $this->masuk();
        $o = $this->buatPesanan();

        $this->patch("/admin/pesanan/{$o->id}/status", ['status' => 'Dibatalkan'])->assertSessionHas('ok');
        $this->assertSame('Dibatalkan', $o->fresh()->status_pesanan);

        $this->patch("/admin/pesanan/{$o->id}/status", ['status' => 'Dibatalkan'])->assertSessionHas('err');
    }

    // ---------- Phase 09.4 S1 (FR-028) ----------

    // D-8: pesanan Lunas boleh diubah, tetapi penyelesaian selisih wajib dicatat.
    public function test_item_pesanan_lunas_wajib_catatan_selisih(): void
    {
        $this->masuk();
        $o = $this->buatPesanan(['status_pembayaran' => 'Lunas']); // Menunggu Konfirmasi + Lunas
        $item = $o->items()->first();

        $this->get("/admin/pesanan/{$o->id}/item")->assertOk()->assertSee('Penyelesaian selisih pembayaran');

        $this->put("/admin/pesanan/{$o->id}/item", ['kuantitas' => [$item->id => 2], 'ongkir' => 0, 'disetujui' => '1'])
            ->assertSessionHas('err');
        $this->assertSame(78000, $o->fresh()->total_harga);

        $this->put("/admin/pesanan/{$o->id}/item", [
            'kuantitas' => [$item->id => 2], 'ongkir' => 0, 'disetujui' => '1',
            'catatan_selisih' => 'Pelanggan transfer tambahan',
        ])->assertSessionHas('ok');

        $o->refresh();
        $this->assertSame(156000, $o->total_harga);
        $this->assertSame('Lunas', $o->status_pembayaran);
        $this->assertStringContainsString('ditagih tambahan Rp 78.000', $o->catatan_admin);
        $this->assertStringContainsString('Pelanggan transfer tambahan', $o->catatan_admin);
    }

    // D-4: Transfer/QRIS baru boleh disiapkan setelah Lunas. Tunai tetap bisa langsung disiapkan.
    public function test_transfer_baru_disiapkan_setelah_lunas(): void
    {
        $this->masuk();
        $transfer = $this->buatPesanan(['metode_pembayaran' => 'Transfer/QRIS']);

        $this->patch("/admin/pesanan/{$transfer->id}/status", ['status' => 'Diproses'])->assertSessionHas('err');
        $this->assertSame('Menunggu Konfirmasi', $transfer->fresh()->status_pesanan);

        $transfer->update(['status_pembayaran' => 'Lunas']);
        $this->patch("/admin/pesanan/{$transfer->id}/status", ['status' => 'Diproses'])->assertSessionHas('ok');
        $this->assertSame('Diproses', $transfer->fresh()->status_pesanan);

        $tunai = $this->buatPesanan();
        $this->patch("/admin/pesanan/{$tunai->id}/status", ['status' => 'Diproses'])->assertSessionHas('ok');
    }

    // D-6: ongkir gratis bila subtotal barang mulai Rp50.000 (inklusif); di bawahnya ongkir dari admin dipakai.
    public function test_gratis_ongkir_mulai_lima_puluh_ribu(): void
    {
        $this->masuk();
        $antar = ['metode_pengambilan' => 'Diantarkan'];

        $o = $this->buatPesanan($antar); // subtotal 78.000
        $item = $o->items()->first();
        $this->put("/admin/pesanan/{$o->id}/item", ['kuantitas' => [$item->id => 1], 'ongkir' => 10000, 'disetujui' => '1'])
            ->assertSessionHas('ok');
        $this->assertSame(0, $o->fresh()->ongkir);
        $this->assertSame(78000, $o->fresh()->total_harga);

        $murah = Product::where('slug', 'tepung-terigu-1-kg')->first(); // Rp 13.500
        $o2 = Order::create([
            'nomor_pesanan' => 'SBK-TEST-ONGK', 'nama_pelanggan' => 'Sari', 'no_whatsapp' => '081234567891',
            'metode_pengambilan' => 'Diantarkan', 'metode_pembayaran' => 'Tunai', 'ongkir' => 0, 'total_harga' => 13500,
            'status_pesanan' => 'Menunggu Konfirmasi', 'status_pembayaran' => 'Belum Dibayar',
        ]);
        $i2 = $o2->items()->create(['product_id' => $murah->id, 'nama_produk_snapshot' => $murah->nama_produk,
            'harga_snapshot' => 13500, 'kuantitas' => 1, 'subtotal' => 13500]);
        $this->put("/admin/pesanan/{$o2->id}/item", ['kuantitas' => [$i2->id => 1], 'ongkir' => 10000, 'disetujui' => '1'])
            ->assertSessionHas('ok');
        $this->assertSame(10000, $o2->fresh()->ongkir);
        $this->assertSame(23500, $o2->fresh()->total_harga);
    }

    // D-2: pembatalan oleh toko atas pesanan Lunas mencatat refund manual.
    public function test_admin_batal_pesanan_lunas_mencatat_refund(): void
    {
        $this->masuk();
        $o = $this->buatPesanan(['status_pembayaran' => 'Lunas']);

        $this->patch("/admin/pesanan/{$o->id}/status", ['status' => 'Dibatalkan'])->assertSessionHas('ok');

        $this->assertSame('Dibatalkan', $o->fresh()->status_pesanan);
        $this->assertStringContainsString('kembalikan Rp 78.000', $o->fresh()->catatan_admin);
    }

    // D-1: nama status yang tampil mengikuti keputusan pemilik; nilai database tetap.
    public function test_label_status_sesuai_keputusan(): void
    {
        $this->masuk();
        $this->buatPesanan(['status_pesanan' => 'Diproses']);

        $this->get('/admin/pesanan')->assertOk()->assertSee('Sedang Disiapkan')->assertSee('Sedang Diantar')->assertDontSee('Sedang Diantarkan');
    }

    // D-7: pesanan Siap Diambil lebih dari 2 hari ditandai untuk dihubungi lewat WhatsApp, tidak dibatalkan otomatis.
    public function test_pesanan_belum_diambil_dua_hari_ditandai(): void
    {
        $this->masuk();
        $lama = $this->buatPesanan(['status_pesanan' => 'Siap Diambil']);
        $lama->timestamps = false;
        $lama->forceFill(['updated_at' => now()->subDays(3)])->save();
        $baru = $this->buatPesanan(['status_pesanan' => 'Siap Diambil']);

        $this->assertTrue($lama->fresh()->lewatBatasAmbil());
        $this->assertFalse($baru->fresh()->lewatBatasAmbil());
        $this->get("/admin/pesanan/{$lama->id}")->assertOk()->assertSee('belum diambil lebih dari 2 hari');
        $this->assertSame('Siap Diambil', $lama->fresh()->status_pesanan);
    }
}

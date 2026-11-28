<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreClosedDate;
use App\Models\StoreSetting;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\KatalogSeeder;
use Database\Seeders\TokoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

// Phase 3B: produk, kategori, pengaturan toko, hari libur, riwayat transaksi.
// Menguji gambar memakai UploadedFile::fake()->image(), yang butuh ekstensi PHP GD (aktif di Laragon).
class AdminManajemenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([TokoSeeder::class, KatalogSeeder::class]);
    }

    private function masuk(): void
    {
        $this->actingAs(User::factory()->create());
    }

    private function dataProduk(array $ubah = []): array
    {
        return array_merge([
            'category_id' => Category::first()->id,
            'nama_produk' => 'Kacang Hijau 500 g',
            'deskripsi' => 'Kacang pilihan',
            'satuan' => 'Kemasan',
            'harga' => 12000,
            'tersedia' => '1',
            'is_active' => 1,
            'is_unggulan' => 0,
        ], $ubah);
    }

    private function dataPengaturan(array $ubah = []): array
    {
        $jadwal = [];
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $hari) {
            $jadwal[$hari] = ['is_open' => 1, 'open' => '05:30', 'close' => $hari === 'friday' ? '11:00' : '11:30'];
        }

        return array_merge([
            'toko_buka' => 1,
            'nama_toko' => 'Toko Sepuluh',
            'alamat_toko' => 'Pasar Pagesangan',
            'no_whatsapp' => '081234567890',
            'nama_bank' => 'BCA',
            'no_rekening' => '1234567890',
            'atas_nama_rekening' => 'Toko Sepuluh',
            'batas_pembayaran_menit' => 60,
            'minimum_pesanan' => 0,
            'radius_antar_km' => '3.5',
            'catatan_pengantaran' => 'Antar sekitar pasar',
            'jadwal' => $jadwal,
        ], $ubah);
    }

    private function buatPesanan(string $status, int $total, string $metode = 'Tunai'): Order
    {
        return Order::create([
            'nomor_pesanan' => 'SBK-TEST-' . strtoupper(Str::random(4)),
            'nama_pelanggan' => 'Budi',
            'no_whatsapp' => '081234567890',
            'metode_pengambilan' => 'Ambil di Toko',
            'metode_pembayaran' => $metode,
            'ongkir' => 0,
            'total_harga' => $total,
            'status_pesanan' => $status,
            'status_pembayaran' => $status === 'Selesai' ? 'Lunas' : 'Belum Dibayar',
        ]);
    }

    public function test_halaman_pengelolaan_butuh_login(): void
    {
        foreach (['/admin/produk', '/admin/produk/create', '/admin/kategori', '/admin/riwayat', '/admin/hari-libur', '/admin/pengaturan'] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
        $this->post('/admin/produk', $this->dataProduk())->assertRedirect('/admin/login');
        $this->put('/admin/pengaturan', $this->dataPengaturan())->assertRedirect('/admin/login');
    }

    public function test_halaman_pengelolaan_tampil_dengan_data_database(): void
    {
        $this->masuk();

        $this->get('/admin/produk')->assertOk()->assertSee('Beras Pandan Wangi 5 kg');
        $this->get('/admin/produk?status=habis')->assertOk()->assertSee('Bawang Merah 500 g')->assertDontSee('Beras Pandan Wangi 5 kg');
        $this->get('/admin/produk/create')->assertOk();
        $this->get('/admin/kategori')->assertOk()->assertSee('Beras');
        $this->get('/admin/pengaturan')->assertOk()->assertSee('Toko Sepuluh');
        $this->get('/admin/hari-libur')->assertOk();
        $this->get('/admin/riwayat')->assertOk();
    }

    public function test_tambah_produk_menyimpan_gambar_dan_slug_unik(): void
    {
        $this->masuk();
        Storage::fake('public');

        $this->post('/admin/produk', $this->dataProduk(['gambar' => UploadedFile::fake()->image('kacang.jpg')]))
            ->assertRedirect(route('admin.produk.index'));
        $p = Product::where('nama_produk', 'Kacang Hijau 500 g')->firstOrFail();
        $this->assertSame('kacang-hijau-500-g', $p->slug);
        $this->assertTrue($p->is_active);
        Storage::disk('public')->assertExists($p->gambar);

        $this->post('/admin/produk', $this->dataProduk());
        $this->assertSame(1, Product::where('slug', 'kacang-hijau-500-g-2')->count());
    }

    public function test_validasi_produk_menolak_data_tidak_valid(): void
    {
        $this->masuk();

        $this->post('/admin/produk', $this->dataProduk(['harga' => -1, 'category_id' => 9999]))
            ->assertSessionHasErrors(['harga', 'category_id']);
        $this->post('/admin/produk', $this->dataProduk(['gambar' => UploadedFile::fake()->create('virus.exe', 10)]))
            ->assertSessionHasErrors('gambar');
        $this->assertSame(0, Product::where('nama_produk', 'Kacang Hijau 500 g')->count());
    }

    public function test_ubah_produk_mengganti_dan_menghapus_gambar_lama(): void
    {
        $this->masuk();
        Storage::fake('public');
        $p = Product::where('slug', 'garam-dapur-250-g')->firstOrFail();
        $lama = UploadedFile::fake()->image('lama.jpg')->store('produk', 'public');
        $p->update(['gambar' => $lama]);

        $this->put("/admin/produk/{$p->id}", $this->dataProduk(['nama_produk' => 'Garam Halus', 'harga' => 3500, 'gambar' => UploadedFile::fake()->image('baru.png')]))
            ->assertRedirect(route('admin.produk.index'));
        $p->refresh();
        $this->assertSame('Garam Halus', $p->nama_produk);
        $this->assertSame('garam-dapur-250-g', $p->slug, 'slug tidak berubah agar tautan lama tetap berlaku');
        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($p->gambar);

        $this->put("/admin/produk/{$p->id}", $this->dataProduk(['hapus_gambar' => 1]));
        $this->assertNull($p->refresh()->gambar);
    }


    public function test_ubah_aktif_menyembunyikan_produk_dari_katalog(): void
    {
        $this->masuk();

        $p = Product::where('slug', 'beras-pandan-wangi-5-kg')->firstOrFail();

        // Nonaktifkan produk melalui halaman admin.
        $this->patch("/admin/produk/{$p->id}/aktif")
            ->assertRedirect();

        $this->assertFalse($p->refresh()->is_active);

        // Pastikan produk nonaktif tidak masuk ke data katalog.
        $response = $this->get('/produk');

        $response->assertOk()
            ->assertViewHas('produk', function ($produk) use ($p) {
                return ! $produk->getCollection()->contains('id', $p->id);
            });

        // Aktifkan kembali produk.
        $this->patch("/admin/produk/{$p->id}/aktif")
            ->assertRedirect();

        $this->assertTrue($p->refresh()->is_active);
    }


    public function test_produk_yang_pernah_dipesan_tidak_bisa_dihapus(): void
    {
        $this->masuk();
        $dipesan = Product::where('slug', 'beras-pandan-wangi-5-kg')->firstOrFail();
        $this->buatPesanan('Menunggu Konfirmasi', 78000)->items()->create([
            'product_id' => $dipesan->id,
            'nama_produk_snapshot' => $dipesan->nama_produk,
            'harga_snapshot' => 78000,
            'kuantitas' => 1,
            'subtotal' => 78000,
        ]);
        $bebas = Product::where('slug', 'garam-dapur-250-g')->firstOrFail();

        $this->delete("/admin/produk/{$dipesan->id}")->assertSessionHas('err');
        $this->assertModelExists($dipesan);

        $this->delete("/admin/produk/{$bebas->id}")->assertSessionHas('ok');
        $this->assertModelMissing($bebas);
    }

    public function test_kategori_tambah_ubah_dan_hapus_hanya_jika_kosong(): void
    {
        $this->masuk();

        $this->post('/admin/kategori', ['nama_kategori' => 'Snack', 'deskripsi' => 'Camilan'])->assertRedirect(route('admin.kategori.index'));
        $snack = Category::where('slug', 'snack')->firstOrFail();

        $this->post('/admin/kategori', ['nama_kategori' => 'Snack'])->assertSessionHasErrors('nama_kategori');

        $this->put("/admin/kategori/{$snack->id}", ['nama_kategori' => 'Camilan'])->assertRedirect();
        $this->assertSame('Camilan', $snack->refresh()->nama_kategori);
        $this->assertSame('snack', $snack->slug);

        $this->delete("/admin/kategori/{$snack->id}")->assertSessionHas('ok');
        $this->assertModelMissing($snack);

        $beras = Category::where('slug', 'beras')->firstOrFail();
        $this->delete("/admin/kategori/{$beras->id}")->assertSessionHas('err');
        $this->assertModelExists($beras);
    }

    public function test_pengaturan_menyimpan_jadwal_yang_dipakai_validasi_checkout(): void
    {
        $this->masuk();
        $data = $this->dataPengaturan();
        $data['jadwal']['friday']['close'] = '10:00'; // 20-11-2026 adalah hari Jumat

        $this->put('/admin/pengaturan', $data)->assertRedirect()->assertSessionHas('ok');

        $toko = StoreSetting::ambil();
        $this->assertSame('10:00', $toko->jadwal_operasional['friday']['close']);
        $this->assertNull($toko->pesanTutup(Carbon::parse('2026-11-20 09:59', 'Asia/Jakarta')));
        $this->assertNotNull($toko->pesanTutup(Carbon::parse('2026-11-20 10:30', 'Asia/Jakarta')));
    }

    public function test_pengaturan_hari_tutup_dan_saklar_toko(): void
    {
        $this->masuk();
        $data = $this->dataPengaturan(['no_whatsapp' => '+62 812-3456-7890']);
        $data['jadwal']['sunday']['is_open'] = 0;
        $senin = Carbon::parse('2026-11-23 08:00', 'Asia/Jakarta');
        $minggu = Carbon::parse('2026-11-22 08:00', 'Asia/Jakarta');

        $this->put('/admin/pengaturan', $data);
        $toko = StoreSetting::ambil();
        $this->assertSame('081234567890', $toko->no_whatsapp, 'nomor dinormalisasi ke 08xx');
        $this->assertNotNull($toko->pesanTutup($minggu));
        $this->assertNull($toko->pesanTutup($senin));

        unset($data['toko_buka']); // checkbox tidak dicentang
        $this->put('/admin/pengaturan', $data);
        $this->assertNotNull(StoreSetting::ambil()->pesanTutup($senin));
    }

    public function test_pengaturan_menolak_jam_tutup_sebelum_jam_buka_dan_data_salah(): void
    {
        $this->masuk();
        $data = $this->dataPengaturan(['batas_pembayaran_menit' => 1, 'no_whatsapp' => 'abc']);
        $data['jadwal']['monday']['close'] = '05:00';

        $this->put('/admin/pengaturan', $data)->assertSessionHasErrors(['jadwal.monday.close', 'batas_pembayaran_menit', 'no_whatsapp']);
        $this->assertSame('11:30', StoreSetting::ambil()->jadwal_operasional['monday']['close']);
    }

    public function test_pengaturan_qris_diunggah_dan_dihapus(): void
    {
        $this->masuk();
        Storage::fake('public');

        $this->put('/admin/pengaturan', $this->dataPengaturan(['gambar_qris' => UploadedFile::fake()->image('qris.png')]));
        $path = StoreSetting::ambil()->gambar_qris;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->put('/admin/pengaturan', $this->dataPengaturan()); // tanpa file: QRIS lama tetap
        $this->assertSame($path, StoreSetting::ambil()->gambar_qris);

        $this->put('/admin/pengaturan', $this->dataPengaturan(['hapus_qris' => 1]));
        $this->assertNull(StoreSetting::ambil()->gambar_qris);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_hari_libur_membuat_checkout_ditolak_pada_tanggal_itu(): void
    {
        $this->masuk();
        $besok = now()->addDay()->toDateString();

        $this->post('/admin/hari-libur', ['tanggal_tutup' => $besok, 'alasan' => 'Idulfitri'])->assertSessionHas('ok');
        $this->assertNotNull(StoreSetting::ambil()->pesanTutup(Carbon::parse("{$besok} 06:00", 'Asia/Jakarta')));

        $this->post('/admin/hari-libur', ['tanggal_tutup' => $besok])->assertSessionHasErrors('tanggal_tutup'); // duplikat
        $this->post('/admin/hari-libur', ['tanggal_tutup' => now()->subDay()->toDateString()])->assertSessionHasErrors('tanggal_tutup'); // lampau
        $this->get('/admin/hari-libur')->assertSee('Idulfitri');

        $this->delete('/admin/hari-libur/' . StoreClosedDate::first()->id)->assertSessionHas('ok');
        $this->assertSame(0, StoreClosedDate::count());
    }

    public function test_riwayat_hanya_menghitung_pesanan_selesai(): void
    {
        $this->masuk();
        $this->buatPesanan('Selesai', 10000);
        $this->buatPesanan('Selesai', 20000, 'Transfer/QRIS');
        $this->buatPesanan('Menunggu Konfirmasi', 5000);
        $this->buatPesanan('Dibatalkan', 7000);

        $this->get('/admin/riwayat')->assertOk()->assertSee('Rp 30.000')->assertSee('Transfer/QRIS');
        $this->get('/admin/riwayat?hari=15')->assertSessionHasErrors('hari');
        $this->get('/admin')->assertSee('Pendapatan Rp 30.000');
    }

    // D-3: admin menandai produk Habis lewat form; katalog menampilkan Habis dan filter admin menemukannya.
    public function test_admin_menandai_produk_habis(): void
    {
        $this->masuk();
        $p = Product::where('slug', 'beras-pandan-wangi-5-kg')->firstOrFail();

        $this->put("/admin/produk/{$p->id}", $this->dataProduk([
            'nama_produk' => $p->nama_produk, 'tersedia' => '0', 'category_id' => $p->category_id,
        ]))->assertRedirect();

        $this->assertFalse($p->fresh()->tersedia);
        $this->get("/produk/{$p->slug}")->assertOk()->assertSee('Sedang Habis');
        $this->get('/admin/produk?status=habis')->assertOk()->assertSee($p->nama_produk);
    }
}

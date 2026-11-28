<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\Keranjang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $baris = Keranjang::baris();
        if ($baris->isEmpty()) {
            return redirect()->route('keranjang.index')->with('err', 'Keranjang masih kosong.');
        }

        return view('pelanggan.checkout.create', [
            'baris' => $baris,
            'total' => $baris->sum('subtotal'),
            'pesanTutup' => StoreSetting::ambil()->pesanTutup(),
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $toko = StoreSetting::ambil();

        // 1. Jadwal operasional dicek di server. Keranjang dipertahankan, pesanan tidak disimpan.
        if ($pesan = $toko->pesanTutup()) {
            return redirect()->route('keranjang.index')->with('toko_tutup', $pesan);
        }
        if (Keranjang::isi() === []) {
            return redirect()->route('keranjang.index')->with('err', 'Keranjang masih kosong.');
        }

        Order::prosesKedaluwarsa();
        $data = $request->validated();

        // 2. Satu transaksi: cek ketersediaan & harga terbaru, lalu simpan pesanan (D-3: tanpa stok angka).
        //    Jika ada yang gagal, semua perubahan dibatalkan otomatis.
        $order = DB::transaction(function () use ($data, $toko) {
            $isi = Keranjang::isi();

            // Baris produk dikunci agar harga/ketersediaan tidak berubah di tengah checkout.
            $produk = Product::whereIn('id', array_keys($isi))->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $subtotal = 0;
            foreach ($isi as $id => $jumlah) {
                $p = $produk->get($id);
                if (! $p || ! $p->is_active) {
                    throw ValidationException::withMessages(['keranjang' => 'Ada produk yang tidak lagi dijual. Periksa keranjang Anda.']);
                }
                if (! $p->tersedia) {
                    throw ValidationException::withMessages(['keranjang' => "{$p->nama_produk} sedang habis. Hapus dari keranjang untuk melanjutkan."]);
                }
                $subtotal += $p->harga * $jumlah; // harga dari database, bukan dari browser
            }

            if ($subtotal < $toko->minimum_pesanan) {
                throw ValidationException::withMessages(['keranjang' => 'Minimal pesanan Rp '.number_format($toko->minimum_pesanan, 0, ',', '.').'.']);
            }

            $order = Order::create([
                'nomor_pesanan' => $this->buatNomorPesanan(),
                'nama_pelanggan' => $data['nama_pelanggan'],
                'no_whatsapp' => $data['no_whatsapp'],
                'metode_pengambilan' => $data['metode_pengambilan'],
                'alamat_pengantaran' => $data['metode_pengambilan'] === 'Diantarkan' ? ($data['alamat_pengantaran'] ?? null) : null,
                'metode_pembayaran' => $data['metode_pembayaran'],
                'ongkir' => 0, // untuk pesanan antar, ongkir diisi admin setelah konfirmasi WhatsApp
                'total_harga' => $subtotal,
                'status_pesanan' => 'Menunggu Konfirmasi',
                'status_pembayaran' => 'Belum Dibayar', // Transfer/QRIS tidak otomatis lunas
            ]);

            foreach ($isi as $id => $jumlah) {
                $p = $produk[$id];
                $order->items()->create([
                    'product_id' => $p->id,
                    'nama_produk_snapshot' => $p->nama_produk,
                    'harga_snapshot' => $p->harga,
                    'kuantitas' => $jumlah,
                    'subtotal' => $p->harga * $jumlah,
                ]);
            }

            // Transfer/QRIS ambil di toko: total sudah pasti, jadi langsung menunggu pembayaran dengan batas waktu.
            // Pesanan antar menunggu admin menentukan ongkir lebih dulu (tombol "Minta Pembayaran").
            if ($data['metode_pembayaran'] === 'Transfer/QRIS' && $data['metode_pengambilan'] === 'Ambil di Toko') {
                $order->mulaiMenungguPembayaran();
            }

            return $order;
        });

        Keranjang::kosongkan();

        return redirect()->route('pesanan.show', $order->public_token)->with('baru', true);
    }

    // Contoh: SBK-20261123-K7QM
    private function buatNomorPesanan(): string
    {
        do {
            $nomor = 'SBK-'.now('Asia/Jakarta')->format('Ymd').'-'.strtoupper(Str::random(4));
        } while (Order::where('nomor_pesanan', $nomor)->exists());

        return $nomor;
    }
}

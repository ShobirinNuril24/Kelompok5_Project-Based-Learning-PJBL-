<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Keranjang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KeranjangController extends Controller
{
    public function index(): View
    {
        $baris = Keranjang::baris();

        return view('pelanggan.keranjang.index', [
            'baris' => $baris,
            'total' => $baris->sum('subtotal'),
            'bermasalah' => $baris->contains(fn ($b) => $b['masalah']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'produk_id' => ['required', 'exists:products,id'],
            'jumlah' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $produk = Product::findOrFail($data['produk_id']);
        if (! $produk->is_active || $produk->stok < 1) {
            return back()->with('err', "{$produk->nama_produk} sedang tidak tersedia.");
        }

        Keranjang::tambah($produk, $data['jumlah'] ?? 1);

        return back()->with('ok', "{$produk->nama_produk} ditambahkan ke keranjang.");
    }

    public function update(Request $request, Product $produk): RedirectResponse
    {
        $data = $request->validate(['jumlah' => ['required', 'integer', 'min:1', 'max:999']]);
        Keranjang::atur($produk, $data['jumlah']); // otomatis dibatasi stok

        return back();
    }

    public function destroy(Product $produk): RedirectResponse
    {
        Keranjang::hapus($produk);

        return back()->with('ok', "{$produk->nama_produk} dihapus dari keranjang.");
    }
}

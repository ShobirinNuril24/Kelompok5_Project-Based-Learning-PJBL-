<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProdukController extends Controller
{
    public function index(Request $request): View
    {
        // SEC-17: input publik divalidasi tipe dan panjangnya (array di query string sebelumnya memicu error 500).
        $d = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'string', 'max:100'],
        ]);

        $produk = Product::aktif()->with('category')
            // % dan _ di-escape supaya dicari sebagai teks biasa, bukan wildcard LIKE (ENG-08).
            ->when($d['q'] ?? null, fn ($q, $cari) => $q->where('nama_produk', 'like', '%'.addcslashes($cari, '\\%_').'%'))
            ->when($d['kategori'] ?? null, fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->orderBy('nama_produk')
            ->paginate(12)
            ->withQueryString();

        return view('pelanggan.produk.index', [
            'produk' => $produk,
            'kategori' => Category::orderBy('nama_kategori')->get(),
        ]);
    }

    public function show(Product $produk): View
    {
        abort_unless($produk->is_active, 404);

        return view('pelanggan.produk.show', ['produk' => $produk->load('category')]);
    }
}

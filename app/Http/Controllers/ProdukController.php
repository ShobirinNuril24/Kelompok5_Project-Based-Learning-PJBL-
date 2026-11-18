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
        $produk = Product::aktif()->with('category')
            ->when($request->q, fn ($q, $cari) => $q->where('nama_produk', 'like', "%{$cari}%"))
            ->when($request->kategori, fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
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

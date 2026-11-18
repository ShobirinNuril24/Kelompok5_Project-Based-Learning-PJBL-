<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(): View
    {
        return view('pelanggan.beranda', [
            // Produk unggulan lebih dulu, lalu yang terbaru
            'produk' => Product::aktif()->with('category')->orderByDesc('is_unggulan')->latest()->limit(8)->get(),
            'kategori' => Category::withCount(['products' => fn ($q) => $q->where('is_active', true)])->orderBy('nama_kategori')->get(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        Order::prosesKedaluwarsa();

        // Satu query: jumlah pesanan per status (status tanpa pesanan dihitung 0)
        $perStatus = Order::selectRaw('status_pesanan, count(*) as total')->groupBy('status_pesanan')->pluck('total', 'status_pesanan');
        $hitung = collect(Order::STATUS_PESANAN)->mapWithKeys(fn ($s) => [$s => (int) ($perStatus[$s] ?? 0)]);

        return view('admin.dashboard', [
            'hitung' => $hitung,
            'total' => $hitung->sum(),
            'hariIni' => Order::whereDate('created_at', today())->count(),
            'tersedia' => Product::aktif()->where('tersedia', true)->count(),
            'habis' => Product::aktif()->where('tersedia', false)->count(),
            'daftarHabis' => Product::aktif()->where('tersedia', false)->orderBy('nama_produk')->limit(5)->get(),
            'pendapatan' => (int) Order::where('status_pesanan', 'Selesai')->sum('total_harga'),
            'terbaru' => Order::latest()->limit(8)->get(),
        ]);
    }
}

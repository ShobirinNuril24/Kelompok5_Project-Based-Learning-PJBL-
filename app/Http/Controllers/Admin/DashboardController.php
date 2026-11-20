<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        Order::prosesKedaluwarsa();

        // Satu query: jumlah pesanan per status (status tanpa pesanan dihitung 0)
        $perStatus = Order::selectRaw('status_pesanan, count(*) as total')->groupBy('status_pesanan')->pluck('total', 'status_pesanan');
        $hitung = collect(Order::STATUS_PESANAN)->mapWithKeys(fn ($s) => [$s => (int) ($perStatus[$s] ?? 0)]);
        $ambang = StoreSetting::ambil()->ambang_stok_menipis;

        return view('admin.dashboard', [
            'hitung' => $hitung,
            'total' => $hitung->sum(),
            'hariIni' => Order::whereDate('created_at', today())->count(),
            'tersedia' => Product::aktif()->where('stok', '>', 0)->count(),
            'habis' => Product::aktif()->where('stok', 0)->count(),
            'menipis' => Product::aktif()->where('stok', '>', 0)->where('stok', '<=', $ambang)->orderBy('stok')->limit(5)->get(),
            'ambang' => $ambang,
            'terbaru' => Order::latest()->limit(8)->get(),
        ]);
    }
}

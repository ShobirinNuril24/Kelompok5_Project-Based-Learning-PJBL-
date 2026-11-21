<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// Definisi laporan: pendapatan = total pesanan berstatus Selesai (status Selesai mensyaratkan pembayaran Lunas),
// dikelompokkan menurut tanggal pesanan dibuat.
class RiwayatController extends Controller
{
    public function index(Request $request): View
    {
        Order::prosesKedaluwarsa();

        $hari = (int) ($request->validate(['hari' => ['nullable', Rule::in([7, 30, 90])]])['hari'] ?? 30);
        $dari = today()->subDays($hari - 1);
        $selesai = Order::where('status_pesanan', 'Selesai')->where('created_at', '>=', $dari);

        $perHari = (clone $selesai)->selectRaw('date(created_at) as tgl, sum(total_harga) as total')->groupBy('tgl')->pluck('total', 'tgl');
        $grafik = collect(CarbonPeriod::create($dari, today()))
            ->map(fn ($t) => ['tgl' => $t, 'total' => (int) ($perHari[$t->toDateString()] ?? 0)]);

        $ringkas = (clone $selesai)->selectRaw('count(*) as jumlah, coalesce(sum(total_harga), 0) as total')->first();

        return view('admin.riwayat.index', [
            'hari' => $hari,
            'grafik' => $grafik,
            'maks' => $grafik->max('total'),
            'jumlah' => (int) $ringkas->jumlah,
            'pendapatan' => (int) $ringkas->total,
            'perMetode' => (clone $selesai)->selectRaw('metode_pembayaran, count(*) as jumlah, sum(total_harga) as total')->groupBy('metode_pembayaran')->get(),
            'pesanan' => $selesai->latest()->paginate(15)->withQueryString(),
        ]);
    }
}

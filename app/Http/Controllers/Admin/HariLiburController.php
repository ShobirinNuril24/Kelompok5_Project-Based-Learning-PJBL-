<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreClosedDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Tanggal tutup khusus (ORD §2.3). Checkout pada tanggal ini ditolak oleh StoreSetting::pesanTutup().
class HariLiburController extends Controller
{
    public function index(): View
    {
        return view('admin.hari-libur.index', [
            'mendatang' => StoreClosedDate::whereDate('tanggal_tutup', '>=', today())->orderBy('tanggal_tutup')->get(),
            'lalu' => StoreClosedDate::whereDate('tanggal_tutup', '<', today())->orderByDesc('tanggal_tutup')->limit(10)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        StoreClosedDate::create($request->validate([
            'tanggal_tutup' => ['required', 'date', 'after_or_equal:today', 'unique:store_closed_dates,tanggal_tutup'],
            'alasan' => ['nullable', 'string', 'max:255'],
        ], ['tanggal_tutup.unique' => 'Tanggal itu sudah tercatat sebagai hari libur.'], ['tanggal_tutup' => 'tanggal']));

        return back()->with('ok', 'Hari libur ditambahkan.');
    }

    public function destroy(StoreClosedDate $hariLibur): RedirectResponse
    {
        $hariLibur->delete();

        return back()->with('ok', 'Hari libur dihapus.');
    }
}

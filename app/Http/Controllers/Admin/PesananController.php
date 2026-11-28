<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UbahItemPesananRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PesananController extends Controller
{
    public function index(Request $request): View
    {
        Order::prosesKedaluwarsa();

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(Order::STATUS_PESANAN)],
        ]);

        $pesanan = Order::query()
            ->when($data['q'] ?? null, fn ($q, $cari) => $q->where(
                fn ($w) => $w->where('nomor_pesanan', 'like', "%{$cari}%")->orWhere('nama_pelanggan', 'like', "%{$cari}%")
            ))
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status_pesanan', $status))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.pesanan.index', [
            'pesanan' => $pesanan,
            'jumlah' => Order::selectRaw('status_pesanan, count(*) as total')->groupBy('status_pesanan')->pluck('total', 'status_pesanan'),
        ]);
    }

    public function show(Order $order): View
    {
        Order::prosesKedaluwarsa();
        $order->refresh()->load('items', 'payments');

        return view('admin.pesanan.show', [
            'order' => $order,
            'toko' => StoreSetting::ambil(),
            'opsiStatus' => $order->opsiStatus(),
        ]);
    }

    public function ubahStatus(Request $request, Order $order): RedirectResponse
    {
        $ke = $request->validate(['status' => ['required', Rule::in(Order::STATUS_PESANAN)]])['status'];

        return $this->hasil($order->ubahStatus($ke), "Status pesanan diubah menjadi {$ke}.");
    }

    public function ubahPembayaran(Request $request, Order $order): RedirectResponse
    {
        $status = $request->validate(['status_pembayaran' => ['required', Rule::in(Order::STATUS_PEMBAYARAN)]])['status_pembayaran'];

        return $this->hasil($order->ubahPembayaran($status), "Status pembayaran diubah menjadi {$status}.");
    }

    public function mintaPembayaran(Order $order): RedirectResponse
    {
        return $this->hasil($order->mintaPembayaran(), 'Pembayaran diminta. Pesanan kini menunggu pembayaran dengan batas waktu.');
    }

    public function ubahCatatan(Request $request, Order $order): RedirectResponse
    {
        $order->update($request->validate(['catatan_admin' => ['nullable', 'string', 'max:2000']]));

        return back()->with('ok', 'Catatan disimpan.');
    }

    public function editItem(Order $order): View|RedirectResponse
    {
        if (! in_array($order->status_pesanan, ['Menunggu Konfirmasi', 'Diproses'], true)) {
            return redirect()->route('admin.pesanan.show', $order)->with('err', 'Item hanya bisa diubah saat pesanan Menunggu Konfirmasi atau Diproses.');
        }
        $order->load('items');

        return view('admin.pesanan.item', [
            'order' => $order,
            'produkTambah' => Product::aktif()->where('tersedia', true)->whereNotIn('id', $order->items->pluck('product_id'))->orderBy('nama_produk')->get(),
            'tersedia' => Product::whereIn('id', $order->items->pluck('product_id'))->pluck('tersedia', 'id'),
        ]);
    }

    public function updateItem(UbahItemPesananRequest $request, Order $order): RedirectResponse
    {
        $d = $request->validated();

        $galat = $order->ubahItem(
            $d['kuantitas'],
            $d['tambah_product_id'] ?? null,
            (int) ($d['tambah_kuantitas'] ?? 0),
            (int) ($d['ongkir'] ?? $order->ongkir),
            $d['catatan_selisih'] ?? null,
        );

        return $galat
            ? back()->withInput()->with('err', $galat)
            : redirect()->route('admin.pesanan.show', $order)->with('ok', 'Item dan total pesanan diperbarui.');
    }

    // Semua aksi model mengembalikan pesan galat atau null (berhasil).
    private function hasil(?string $galat, string $pesanBerhasil): RedirectResponse
    {
        return back()->with($galat ? 'err' : 'ok', $galat ?? $pesanBerhasil);
    }
}

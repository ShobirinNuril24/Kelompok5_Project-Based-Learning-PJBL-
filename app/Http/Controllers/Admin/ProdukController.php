<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProdukController extends Controller
{
    public function index(Request $request): View
    {
        $d = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['aktif', 'nonaktif', 'habis', 'menipis'])],
        ]);
        $ambang = StoreSetting::ambil()->ambang_stok_menipis;

        $produk = Product::with('category')
            ->when($d['q'] ?? null, fn ($q, $cari) => $q->where('nama_produk', 'like', "%{$cari}%"))
            ->when($d['kategori'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($d['status'] ?? null, fn ($q, $s) => match ($s) {
                'aktif' => $q->where('is_active', true),
                'nonaktif' => $q->where('is_active', false),
                'habis' => $q->where('stok', 0),
                'menipis' => $q->where('stok', '>', 0)->where('stok', '<=', $ambang),
            })
            ->orderBy('nama_produk')->paginate(15)->withQueryString();

        return view('admin.produk.index', [
            'produk' => $produk,
            'kategori' => Category::orderBy('nama_kategori')->get(),
            'ambang' => $ambang,
        ]);
    }

    public function create(): View
    {
        return $this->form(new Product(['is_active' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $data['gambar'] = $request->file('gambar')?->store('produk', 'public');
        Product::create($data + ['slug' => $this->slugUnik(Product::class, $data['nama_produk'])]);

        return redirect()->route('admin.produk.index')->with('ok', 'Produk ditambahkan.');
    }

    public function edit(Product $produk): View
    {
        return $this->form($produk);
    }

    public function update(Request $request, Product $produk): RedirectResponse
    {
        $data = $this->validasi($request);

        if ($request->hasFile('gambar')) {
            $this->hapusGambar($produk);
            $data['gambar'] = $request->file('gambar')->store('produk', 'public');
        } elseif ($request->boolean('hapus_gambar')) {
            $this->hapusGambar($produk);
            $data['gambar'] = null;
        }
        $produk->update($data);

        return redirect()->route('admin.produk.index')->with('ok', 'Produk diperbarui.');
    }

    // Aktif/nonaktif cepat dari daftar. Produk nonaktif hilang dari katalog dan tidak bisa dipesan.
    public function ubahAktif(Product $produk): RedirectResponse
    {
        $produk->update(['is_active' => ! $produk->is_active]);

        return back()->with('ok', $produk->is_active ? "{$produk->nama_produk} diaktifkan." : "{$produk->nama_produk} dinonaktifkan.");
    }

    public function destroy(Product $produk): RedirectResponse
    {
        if ($produk->orderItems()->exists()) {
            return back()->with('err', 'Produk ini sudah pernah dipesan, jadi tidak dihapus agar riwayat transaksi tetap utuh. Nonaktifkan saja.');
        }
        $this->hapusGambar($produk);
        $produk->delete();

        return back()->with('ok', 'Produk dihapus.');
    }

    private function form(Product $produk): View
    {
        return view('admin.produk.form', ['produk' => $produk, 'kategori' => Category::orderBy('nama_kategori')->get()]);
    }

    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'nama_produk' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'satuan' => ['required', 'string', 'max:50'],
            'harga' => ['required', 'integer', 'min:0', 'max:100000000'],
            'stok' => ['required', 'integer', 'min:0', 'max:1000000'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [], ['category_id' => 'kategori', 'nama_produk' => 'nama produk']);
        unset($data['gambar']); // file disimpan terpisah oleh store()/update()

        return $data + ['is_active' => $request->boolean('is_active'), 'is_unggulan' => $request->boolean('is_unggulan')];
    }

    private function hapusGambar(Product $produk): void
    {
        if ($produk->gambar) {
            Storage::disk('public')->delete($produk->gambar);
        }
    }
}

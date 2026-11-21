<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KategoriController extends Controller
{
    public function index(): View
    {
        return view('admin.kategori.index', ['kategori' => Category::withCount('products')->orderBy('nama_kategori')->get()]);
    }

    public function create(): View
    {
        return view('admin.kategori.form', ['kategori' => new Category]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        Category::create($data + ['slug' => $this->slugUnik(Category::class, $data['nama_kategori'])]);

        return redirect()->route('admin.kategori.index')->with('ok', 'Kategori ditambahkan.');
    }

    public function edit(Category $kategori): View
    {
        return view('admin.kategori.form', ['kategori' => $kategori]);
    }

    // Slug tidak diubah saat nama diganti, supaya tautan filter kategori yang sudah beredar tetap berlaku.
    public function update(Request $request, Category $kategori): RedirectResponse
    {
        $kategori->update($this->validasi($request, $kategori));

        return redirect()->route('admin.kategori.index')->with('ok', 'Kategori diperbarui.');
    }

    public function destroy(Category $kategori): RedirectResponse
    {
        $jumlah = $kategori->products()->count();
        if ($jumlah > 0) {
            return back()->with('err', "Kategori {$kategori->nama_kategori} masih dipakai {$jumlah} produk. Pindahkan produknya ke kategori lain dulu.");
        }
        $kategori->delete();

        return back()->with('ok', 'Kategori dihapus.');
    }

    private function validasi(Request $request, ?Category $kategori = null): array
    {
        return $request->validate([
            'nama_kategori' => ['required', 'string', 'max:100', Rule::unique('categories', 'nama_kategori')->ignore($kategori)],
            'deskripsi' => ['nullable', 'string', 'max:500'],
        ], [], ['nama_kategori' => 'nama kategori']);
    }
}

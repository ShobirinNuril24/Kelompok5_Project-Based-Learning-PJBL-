@extends('layouts.admin')

@section('judul', 'Produk')

@section('konten')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('admin.produk.index') }}" class="flex flex-1 flex-wrap gap-2">
            <label for="q" class="sr-only">Cari produk</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama produk..."
                   class="w-full max-w-xs rounded-lg border border-garis bg-kartu px-3 py-2.5 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-sun">
            <label for="kategori" class="sr-only">Kategori</label>
            <select id="kategori" name="kategori" class="rounded-lg border border-garis bg-kartu px-3 py-2.5 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-sun">
                <option value="">Semua kategori</option>
                @foreach ($kategori as $k)
                    <option value="{{ $k->id }}" @selected(request('kategori') == $k->id)>{{ $k->nama_kategori }}</option>
                @endforeach
            </select>
            <label for="status" class="sr-only">Status</label>
            <select id="status" name="status" class="rounded-lg border border-garis bg-kartu px-3 py-2.5 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-sun">
                <option value="">Semua status</option>
                @foreach (['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif', 'habis' => 'Habis'] as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(request('status') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
            <x-button type="submit"><x-icon nama="search" ukuran="size-4" /> Cari</x-button>
        </form>
        <x-button :href="route('admin.produk.create')"><x-icon nama="plus" ukuran="size-4" /> Tambah produk</x-button>
    </div>

    <div class="mt-5 rounded-2xl border border-garis bg-kartu p-5">
        @if ($produk->isEmpty())
            <div class="rounded-xl border border-dashed border-garis p-10 text-center">
                <x-icon nama="box" ukuran="size-10 mx-auto text-teks-lemah" />
                <p class="mt-3 font-semibold">Tidak ada produk yang cocok</p>
                <p class="text-sm text-teks-lemah">Ubah pencarian atau filter, atau tambah produk baru.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-teks-lemah">
                        <tr>
                            <th class="py-2 pr-3 font-semibold">Produk</th><th class="px-3 py-2 font-semibold">Kategori</th>
                            <th class="px-3 py-2 text-right font-semibold">Harga</th><th class="px-3 py-2 font-semibold">Stok</th>
                            <th class="px-3 py-2 font-semibold">Status</th><th class="py-2 pl-3 font-semibold"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($produk as $p)
                            <tr class="border-t border-garis">
                                <td class="py-2.5 pr-3">
                                    <div class="flex items-center gap-3">
                                        <x-gambar-produk :produk="$p" class="size-12 shrink-0 rounded-lg" />
                                        <div><span class="font-bold">{{ $p->nama_produk }}</span>
                                            @if ($p->is_unggulan)<x-badge warna="info" class="ml-1">Unggulan</x-badge>@endif
                                            <span class="block text-xs text-teks-lemah">per {{ $p->satuan }}</span></div>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5">{{ $p->category->nama_kategori }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">@rupiah($p->harga)</td>
                                <td class="px-3 py-2.5">
                                    <x-badge :warna="$p->tersedia ? 'sukses' : 'bahaya'">{{ $p->tersedia ? 'Ada' : 'Habis' }}</x-badge>
                                </td>
                                <td class="px-3 py-2.5"><x-badge :warna="$p->is_active ? 'sukses' : 'netral'">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                                <td class="py-2.5 pl-3">
                                    <div class="flex justify-end gap-2">
                                        <x-button :href="route('admin.produk.edit', $p)" varian="garis" class="!px-3 !py-1.5">Ubah</x-button>
                                        <form method="POST" action="{{ route('admin.produk.aktif', $p) }}">
                                            @csrf @method('PATCH')
                                            <x-button type="submit" varian="garis" class="!px-3 !py-1.5">{{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</x-button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.produk.destroy', $p) }}" onsubmit="return confirm('Hapus produk {{ addslashes($p->nama_produk) }}?')">
                                            @csrf @method('DELETE')
                                            <x-button type="submit" varian="garis" class="!px-3 !py-1.5" aria-label="Hapus {{ $p->nama_produk }}"><x-icon nama="trash" ukuran="size-4" /></x-button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $produk->links() }}</div>
        @endif
    </div>
@endsection

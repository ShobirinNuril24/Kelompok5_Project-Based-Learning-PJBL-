@extends('layouts.admin')

@section('judul', 'Kategori')

@section('konten')
    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-teks-lemah">{{ $kategori->count() }} kategori</p>
        <x-button :href="route('admin.kategori.create')"><x-icon nama="plus" ukuran="size-4" /> Tambah kategori</x-button>
    </div>

    <div class="mt-4 rounded-2xl border border-garis bg-kartu p-5">
        @if ($kategori->isEmpty())
            <div class="rounded-xl border border-dashed border-garis p-10 text-center">
                <x-icon nama="tag" ukuran="size-10 mx-auto text-teks-lemah" />
                <p class="mt-3 font-semibold">Belum ada kategori</p>
                <p class="text-sm text-teks-lemah">Buat kategori dulu sebelum menambah produk.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-teks-lemah">
                        <tr><th class="py-2 pr-3 font-semibold">Kategori</th><th class="px-3 py-2 font-semibold">Deskripsi</th><th class="px-3 py-2 text-right font-semibold">Produk</th><th class="py-2 pl-3 font-semibold"><span class="sr-only">Aksi</span></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($kategori as $k)
                            <tr class="border-t border-garis">
                                <td class="py-2.5 pr-3"><span class="font-bold">{{ $k->nama_kategori }}</span><span class="block text-xs text-teks-lemah">{{ $k->slug }}</span></td>
                                <td class="px-3 py-2.5 text-teks-lemah">{{ $k->deskripsi ?: '—' }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums">{{ $k->products_count }}</td>
                                <td class="py-2.5 pl-3">
                                    <div class="flex justify-end gap-2">
                                        <x-button :href="route('admin.kategori.edit', $k)" varian="garis" class="!px-3 !py-1.5">Ubah</x-button>
                                        <form method="POST" action="{{ route('admin.kategori.destroy', $k) }}" onsubmit="return confirm('Hapus kategori {{ $k->nama_kategori }}?')">
                                            @csrf @method('DELETE')
                                            <x-button type="submit" varian="garis" class="!px-3 !py-1.5" aria-label="Hapus {{ $k->nama_kategori }}"><x-icon nama="trash" ukuran="size-4" /></x-button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

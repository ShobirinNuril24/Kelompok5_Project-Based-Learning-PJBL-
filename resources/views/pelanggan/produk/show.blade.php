@extends('layouts.pelanggan')

@section('judul', $produk->nama_produk)

@section('konten')
    <x-container class="py-8">
        <nav class="text-sm text-teks-lemah" aria-label="Breadcrumb">
            <a href="{{ route('produk.index') }}" class="hover:underline">Produk</a> /
            <a href="{{ route('produk.index', ['kategori' => $produk->category->slug]) }}" class="hover:underline">{{ $produk->category->nama_kategori }}</a>
        </nav>

        <div class="mt-4 grid gap-8 md:grid-cols-2">
            <x-gambar-produk :produk="$produk" class="aspect-square rounded-2xl border border-garis" />

            <div>
                <x-badge>{{ $produk->category->nama_kategori }}</x-badge>
                <h1 class="mt-3 text-2xl font-extrabold md:text-3xl">{{ $produk->nama_produk }}</h1>
                <p class="mt-3 text-3xl font-extrabold tabular-nums">@rupiah($produk->harga) <span class="text-base font-medium text-teks-lemah">/ {{ $produk->satuan }}</span></p>

                <p class="mt-3">
                    @if ($produk->tersedia)
                        <x-badge warna="sukses">Tersedia</x-badge>
                    @else
                        <x-badge warna="bahaya">Habis</x-badge>
                    @endif
                </p>

                @if ($produk->deskripsi)
                    <p class="mt-4 text-teks-lemah">{{ $produk->deskripsi }}</p>
                @endif

                <form method="POST" action="{{ route('keranjang.store') }}" class="mt-6 flex items-end gap-3">
                    @csrf
                    <input type="hidden" name="produk_id" value="{{ $produk->id }}">
                    <div>
                        <label for="jumlah" class="mb-1 block text-sm font-semibold">Jumlah</label>
                        <input id="jumlah" type="number" name="jumlah" min="1" max="{{ \App\Models\Product::MAKS_JUMLAH }}" value="1" @disabled(! $produk->tersedia)
                               class="w-24 rounded-lg border border-garis bg-kartu px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-sun">
                    </div>
                    <x-button type="submit" :disabled="! $produk->tersedia">
                        <x-icon nama="cart" ukuran="size-4" /> {{ $produk->tersedia ? 'Tambah ke Keranjang' : 'Sedang Habis' }}
                    </x-button>
                </form>
            </div>
        </div>
    </x-container>
@endsection

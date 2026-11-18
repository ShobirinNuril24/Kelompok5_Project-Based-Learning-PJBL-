@extends('layouts.pelanggan')

@section('judul', 'Produk')

@section('konten')
    <x-container class="py-8">
        <h1 class="text-2xl font-extrabold">Produk</h1>

        {{-- Pencarian --}}
        <form method="GET" action="{{ route('produk.index') }}" class="mt-4 flex gap-2">
            @if (request('kategori'))
                <input type="hidden" name="kategori" value="{{ request('kategori') }}">
            @endif
            <label for="q" class="sr-only">Cari produk</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama produk..."
                   class="w-full max-w-md rounded-lg border border-garis bg-kartu px-3 py-2.5 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-sun">
            <x-button type="submit"><x-icon nama="search" ukuran="size-4" /> Cari</x-button>
        </form>

        {{-- Filter kategori --}}
        <div class="mt-4 flex flex-wrap gap-2" aria-label="Filter kategori">
            <a href="{{ route('produk.index', array_filter(['q' => request('q')])) }}"
               @class(['rounded-full border px-4 py-1.5 text-sm font-semibold', 'border-sun bg-sun text-ink' => ! request('kategori'), 'border-garis bg-kartu hover:border-sun' => request('kategori')])>Semua</a>
            @foreach ($kategori as $k)
                <a href="{{ route('produk.index', array_filter(['kategori' => $k->slug, 'q' => request('q')])) }}"
                   @class(['rounded-full border px-4 py-1.5 text-sm font-semibold', 'border-sun bg-sun text-ink' => request('kategori') === $k->slug, 'border-garis bg-kartu hover:border-sun' => request('kategori') !== $k->slug])>{{ $k->nama_kategori }}</a>
            @endforeach
        </div>

        @if ($produk->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-garis p-10 text-center">
                <x-icon nama="search" ukuran="size-10 mx-auto text-teks-lemah" />
                <p class="mt-3 font-semibold">Produk tidak ditemukan</p>
                <p class="text-sm text-teks-lemah">Coba kata kunci atau kategori lain.</p>
                <x-button :href="route('produk.index')" varian="garis" class="mt-4">Tampilkan semua produk</x-button>
            </div>
        @else
            <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($produk as $p)
                    <x-kartu-produk :produk="$p" />
                @endforeach
            </div>
            <div class="mt-8">{{ $produk->links() }}</div>
        @endif
    </x-container>
@endsection

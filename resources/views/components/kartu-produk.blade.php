@props(['produk'])

<article class="flex flex-col overflow-hidden rounded-2xl border border-garis bg-kartu">
    <a href="{{ route('produk.show', $produk->slug) }}" class="block" tabindex="-1" aria-hidden="true">
        <x-gambar-produk :produk="$produk" class="aspect-square" />
    </a>
    <div class="flex flex-1 flex-col p-4">
        <x-badge class="self-start">{{ $produk->category->nama_kategori }}</x-badge>
        <h3 class="mt-2 font-bold leading-snug">
            <a href="{{ route('produk.show', $produk->slug) }}" class="hover:underline">{{ $produk->nama_produk }}</a>
        </h3>
        <p class="mt-1 text-sm text-teks-lemah">per {{ $produk->satuan }}</p>
        <p class="mt-2 text-lg font-extrabold tabular-nums">@rupiah($produk->harga)</p>
        <p class="mt-1 text-xs font-semibold {{ $produk->stok > 0 ? 'text-teks-lemah' : 'text-red-600 dark:text-red-400' }}">
            {{ $produk->stok > 0 ? 'Stok: '.$produk->stok : 'Habis' }}
        </p>

        <form method="POST" action="{{ route('keranjang.store') }}" class="mt-4">
            @csrf
            <input type="hidden" name="produk_id" value="{{ $produk->id }}">
            <x-button type="submit" class="w-full" :disabled="$produk->stok < 1">
                <x-icon nama="cart" ukuran="size-4" /> {{ $produk->stok > 0 ? 'Tambah' : 'Habis' }}
            </x-button>
        </form>
    </div>
</article>

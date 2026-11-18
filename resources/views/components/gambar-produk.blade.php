@props(['produk'])

{{-- Skeleton tampil sampai gambar selesai dimuat, lalu diganti gambar asli. Tanpa gambar: ikon pengganti. --}}
<div x-data="{ ok: false }" {{ $attributes->merge(['class' => 'relative overflow-hidden bg-garis/60']) }}>
    @if ($produk->gambar)
        <x-skeleton x-show="!ok" class="absolute inset-0" />
        <img src="{{ asset('storage/'.$produk->gambar) }}" alt="{{ $produk->nama_produk }}" loading="lazy"
             class="size-full object-cover" @load="ok = true" x-init="if ($el.complete) ok = true">
    @else
        <div class="grid size-full place-items-center text-teks-lemah"><x-icon nama="box" ukuran="size-10" /></div>
    @endif
</div>

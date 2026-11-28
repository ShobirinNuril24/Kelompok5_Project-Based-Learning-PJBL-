@extends('layouts.pelanggan')

@section('judul', 'Keranjang')

@section('konten')
    {{-- Popup toko tutup (muncul setelah checkout ditolak di luar jam operasional) --}}
    @if (session('toko_tutup'))
        <div x-data="{ buka: true }" x-show="buka" x-cloak @keydown.escape.window="buka = false"
             class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="judul-tutup">
            <div class="w-full max-w-sm rounded-2xl bg-kartu p-6 text-center">
                <x-icon nama="clock" ukuran="size-10 mx-auto text-sun" />
                <h2 id="judul-tutup" class="mt-3 text-lg font-extrabold">Toko sedang tutup</h2>
                <p class="mt-2 text-sm text-teks-lemah">{{ session('toko_tutup') }}</p>
                <p class="mt-2 text-sm text-teks-lemah">Keranjang Anda tetap tersimpan.</p>
                <div class="mt-5 flex flex-col gap-2">
                    <x-button :href="route('produk.index')">Kembali ke katalog</x-button>
                    <x-button varian="garis" @click="buka = false">Tutup</x-button>
                </div>
            </div>
        </div>
    @endif

    <x-container class="py-8">
        <h1 class="text-2xl font-extrabold">Keranjang</h1>

        @if ($baris->isEmpty())
            <div class="mt-8 rounded-2xl border border-dashed border-garis p-10 text-center">
                <x-icon nama="cart" ukuran="size-10 mx-auto text-teks-lemah" />
                <p class="mt-3 font-semibold">Keranjang masih kosong</p>
                <p class="text-sm text-teks-lemah">Pilih produk dulu di katalog.</p>
                <x-button :href="route('produk.index')" class="mt-4">Lihat Produk</x-button>
            </div>
        @else
            <div class="mt-6 grid gap-6 lg:grid-cols-3">
                <ul class="space-y-3 lg:col-span-2">
                    @foreach ($baris as $b)
                        @php $p = $b['produk']; @endphp
                        <li class="flex gap-4 rounded-2xl border border-garis bg-kartu p-4">
                            <x-gambar-produk :produk="$p" class="size-20 shrink-0 rounded-lg" />
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('produk.show', $p->slug) }}" class="font-bold hover:underline">{{ $p->nama_produk }}</a>
                                <p class="text-sm tabular-nums text-teks-lemah">@rupiah($p->harga) / {{ $p->satuan }}</p>
                                @if ($b['masalah'])
                                    <p class="mt-1 text-sm font-semibold text-red-600 dark:text-red-400">{{ $b['masalah'] }}</p>
                                @endif

                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    <form method="POST" action="{{ route('keranjang.update', $p) }}" x-data>
                                        @csrf @method('PATCH')
                                        <label class="sr-only" for="jumlah-{{ $p->id }}">Jumlah {{ $p->nama_produk }}</label>
                                        <input id="jumlah-{{ $p->id }}" type="number" name="jumlah" min="1" max="{{ max($p->stok, 1) }}" value="{{ $b['jumlah'] }}"
                                               @change="$el.form.requestSubmit()"
                                               class="w-20 rounded-lg border border-garis bg-kartu px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sun">
                                    </form>
                                    <span class="font-bold tabular-nums">@rupiah($b['subtotal'])</span>
                                    <form method="POST" action="{{ route('keranjang.destroy', $p) }}" class="ml-auto">
                                        @csrf @method('DELETE')
                                        <button class="grid size-9 place-items-center rounded-lg text-red-600 hover:bg-red-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-sun dark:text-red-400 dark:hover:bg-red-900/40"
                                                aria-label="Hapus {{ $p->nama_produk }} dari keranjang"><x-icon nama="trash" ukuran="size-4" /></button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <aside class="h-fit rounded-2xl border border-garis bg-kartu p-5 lg:sticky lg:top-24">
                    <h2 class="font-extrabold">Ringkasan</h2>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-teks-lemah">Subtotal</dt><dd class="font-bold tabular-nums">@rupiah($total)</dd></div>
                    </dl>
                    <p class="mt-3 text-xs text-teks-lemah">Gratis ongkir untuk belanja mulai @rupiah(\App\Models\Order::gratisOngkirMinimal()). Di bawah itu, ongkir pengantaran ditentukan admin setelah pesanan dikonfirmasi lewat WhatsApp.</p>
                    @if ($bermasalah)
                        <p class="mt-3 text-sm font-semibold text-red-600 dark:text-red-400">Perbaiki produk yang bermasalah sebelum checkout.</p>
                        <x-button class="mt-4 w-full" disabled>Lanjut ke Checkout</x-button>
                    @else
                        <x-button :href="route('checkout.create')" class="mt-4 w-full">Lanjut ke Checkout</x-button>
                    @endif
                </aside>
            </div>
        @endif
    </x-container>
@endsection

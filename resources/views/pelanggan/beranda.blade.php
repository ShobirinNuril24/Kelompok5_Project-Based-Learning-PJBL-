@extends('layouts.pelanggan')

@section('judul', 'Beranda')

@section('konten')
    @php $tutup = $toko->pesanTutup(); @endphp

    {{-- Pengenalan toko --}}
    <section class="bg-sun-light/60 dark:bg-white/5">
        <x-container class="grid items-center gap-8 py-12 md:grid-cols-2 md:py-16">
            <div>
                <x-badge :warna="$tutup ? 'peringatan' : 'sukses'">{{ $tutup ? 'Sedang tutup' : 'Buka sekarang' }}</x-badge>
                <h1 class="mt-4 text-3xl font-extrabold leading-tight md:text-4xl">{{ $toko->nama_toko }} Menjual sembako terlengkap.</h1>
                <p class="mt-3 text-teks-lemah">Destinasi belanja harian terpercaya Anda di jantung kawasan Pagesangan,
                    Jambangan. Kami hadir menyediakan berbagai kebutuhan pilihan dengan
                    kualitas terbaik dan harga yang ramah di kantong. Kunjungi langsung lapak
                    kami atau pesan dengan mudah dari rumah!
                </p>
                @if ($tutup)
                    <p class="mt-3 text-sm font-semibold text-amber-700 dark:text-amber-300">{{ $tutup }}</p>
                @endif
                <div class="mt-6 flex flex-wrap gap-3">
                    <x-button :href="route('produk.index')">Lihat Produk</x-button>
                    <x-button varian="wa" :href="$toko->waUrl('Halo '.$toko->nama_toko.', saya ingin bertanya.')">
                        <x-icon nama="whatsapp" ukuran="size-4" /> Hubungi Penjual
                    </x-button>
                </div>
            </div>
            <img src="{{ asset('images/logo-toko-10.png') }}" alt="Logo {{ $toko->nama_toko }}" class="mx-auto size-44 md:size-60">
        </x-container>
    </section>

    {{-- Kategori --}}
    @if ($kategori->isNotEmpty())
        <section class="py-10">
            <x-container>
                <h2 class="text-xl font-extrabold">Kategori</h2>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($kategori as $k)
                        <a href="{{ route('produk.index', ['kategori' => $k->slug]) }}"
                           class="rounded-2xl border border-garis bg-kartu p-4 hover:border-sun focus:outline-none focus-visible:ring-2 focus-visible:ring-sun">
                            <span class="block font-bold">{{ $k->nama_kategori }}</span>
                            <span class="text-sm text-teks-lemah">{{ $k->products_count }} produk</span>
                        </a>
                    @endforeach
                </div>
            </x-container>
        </section>
    @endif

    {{-- Produk pilihan --}}
    <section class="pb-10">
        <x-container>
            <div class="flex items-end justify-between gap-4">
                <h2 class="text-xl font-extrabold">Produk pilihan</h2>
                <a href="{{ route('produk.index') }}" class="text-sm font-bold hover:underline">Lihat semua</a>
            </div>
            @if ($produk->isEmpty())
                <p class="mt-4 rounded-2xl border border-dashed border-garis p-8 text-center text-teks-lemah">Belum ada produk yang ditampilkan.</p>
            @else
                <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($produk as $p)
                        <x-kartu-produk :produk="$p" />
                    @endforeach
                </div>
            @endif
        </x-container>
    </section>

    {{-- Cara memesan --}}
    <section class="pb-6">
        <x-container>
            <div class="rounded-2xl border border-garis bg-kartu p-6">
                <h2 class="text-xl font-extrabold">Cara memesan</h2>
                <ol class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                    <li><b>1. Pilih produk.</b><br>Masukkan barang yang Anda butuhkan ke keranjang.</li>
                    <li><b>2. Checkout.</b><br>Isi nama dan WhatsApp, pilih ambil di toko atau diantarkan, lalu metode pembayaran.</li>
                    <li><b>3. Tunggu konfirmasi.</b><br>Admin memeriksa pesanan dan menghubungi Anda lewat WhatsApp bila perlu.</li>
                </ol>
            </div>
        </x-container>
    </section>
@endsection

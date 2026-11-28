@extends('layouts.pelanggan')

@section('judul', 'Checkout')

@section('konten')
    @php
        $bermasalah = $baris->contains(fn ($b) => $b['masalah']);
        $ketPembayaran = [
            'Tunai' => 'Bayar tunai saat ambil di toko, atau saat barang diantar (COD).',
            'Transfer/QRIS' => 'Rekening dan QRIS ditampilkan setelah pesanan dibuat. Admin memverifikasi pembayaran secara manual.',
        ];
    @endphp

    <x-container class="py-8">
        <h1 class="text-2xl font-extrabold">Checkout</h1>

        @if ($pesanTutup)
            <div role="alert" class="mt-4 rounded-lg bg-amber-100 px-4 py-3 text-sm font-semibold text-amber-900 dark:bg-amber-900/40 dark:text-amber-200">{{ $pesanTutup }}</div>
        @endif
        @if ($bermasalah)
            <div role="alert" class="mt-4 rounded-lg bg-red-100 px-4 py-3 text-sm font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-300">
                Ada produk yang bermasalah. <a href="{{ route('keranjang.index') }}" class="underline">Periksa keranjang</a>.
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.store') }}" x-data="{ ambil: @js(old('metode_pengambilan', 'Ambil di Toko')) }" class="mt-6 grid gap-6 lg:grid-cols-3">
            @csrf
            <div class="space-y-6 lg:col-span-2">
                <fieldset class="rounded-2xl border border-garis bg-kartu p-5">
                    <legend class="px-1 font-extrabold">Data pelanggan</legend>
                    <div class="mt-2 grid gap-4 sm:grid-cols-2">
                        <x-input label="Nama" name="nama_pelanggan" required autocomplete="name" />
                        <x-input label="Nomor WhatsApp" name="no_whatsapp" type="tel" inputmode="tel" placeholder="081234567890" required autocomplete="tel" />
                    </div>
                </fieldset>

                <fieldset class="rounded-2xl border border-garis bg-kartu p-5">
                    <legend class="px-1 font-extrabold">Metode pengambilan</legend>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        @foreach (\App\Models\Order::METODE_PENGAMBILAN as $m)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-garis p-3 has-[:checked]:border-sun has-[:checked]:bg-sun/20">
                                <input type="radio" name="metode_pengambilan" value="{{ $m }}" x-model="ambil" @checked(old('metode_pengambilan', 'Ambil di Toko') === $m) class="size-4 accent-amber-500">
                                <span class="font-semibold">{{ $m }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div x-show="ambil === 'Diantarkan'" x-cloak class="mt-4">
                        <label for="alamat_pengantaran" class="mb-1 block text-sm font-semibold">Alamat pengantaran</label>
                        <textarea id="alamat_pengantaran" name="alamat_pengantaran" rows="3"
                                  class="w-full rounded-lg border border-garis bg-kartu px-3 py-2.5 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-sun">{{ old('alamat_pengantaran') }}</textarea>
                        <p class="mt-1 text-xs text-teks-lemah">Admin memeriksa jarak dan kesanggupan pengantaran, lalu menghubungi Anda lewat WhatsApp. Ongkir ditentukan admin.</p>
                    </div>
                </fieldset>

                <fieldset class="rounded-2xl border border-garis bg-kartu p-5">
                    <legend class="px-1 font-extrabold">Metode pembayaran</legend>
                    <div class="mt-2 grid gap-3">
                        @foreach (\App\Models\Order::METODE_PEMBAYARAN as $m)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-garis p-3 has-[:checked]:border-sun has-[:checked]:bg-sun/20">
                                <input type="radio" name="metode_pembayaran" value="{{ $m }}" @checked(old('metode_pembayaran', 'Tunai') === $m) class="mt-1 size-4 accent-amber-500">
                                <span><span class="block font-semibold">{{ $m }}</span><span class="text-sm text-teks-lemah">{{ $ketPembayaran[$m] }}</span></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>

            {{-- Ringkasan pesanan --}}
            <aside class="h-fit rounded-2xl border border-garis bg-kartu p-5 lg:sticky lg:top-24">
                <h2 class="font-extrabold">Ringkasan pesanan</h2>
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($baris as $b)
                        <li class="flex justify-between gap-3">
                            <span>{{ $b['produk']->nama_produk }} <span class="text-teks-lemah">× {{ $b['jumlah'] }}</span></span>
                            <span class="font-semibold tabular-nums">@rupiah($b['subtotal'])</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="mt-4 space-y-2 border-t border-garis pt-4 text-sm">
                    <div class="flex justify-between"><dt class="text-teks-lemah">Ongkir (jika diantar)</dt><dd>{{ $total >= \App\Models\Order::gratisOngkirMinimal() ? 'Gratis' : 'Ditentukan admin' }}</dd></div>
                    <div class="flex justify-between text-base"><dt class="font-bold">Total</dt><dd class="font-extrabold tabular-nums">@rupiah($total)</dd></div>
                </dl>
                <x-button type="submit" class="mt-5 w-full" :disabled="$pesanTutup || $bermasalah">Kirim Pesanan</x-button>
                <p class="mt-3 text-xs text-teks-lemah">Harga dan stok diperiksa ulang oleh sistem saat pesanan dikirim.</p>
            </aside>
        </form>
    </x-container>
@endsection

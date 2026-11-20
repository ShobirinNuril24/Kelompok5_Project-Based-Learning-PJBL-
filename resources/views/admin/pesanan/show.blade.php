@extends('layouts.admin')

@section('judul', 'Pesanan '.$order->nomor_pesanan)

@section('konten')
    @php
        $diantar = $order->metode_pengambilan === 'Diantarkan';
        $final = in_array($order->status_pesanan, ['Selesai', 'Dibatalkan', 'Kedaluwarsa']);
        $bisaUbahItem = in_array($order->status_pesanan, ['Menunggu Konfirmasi', 'Diproses']);
        $bisaMintaBayar = $order->metode_pembayaran === 'Transfer/QRIS' && $order->status_pesanan === 'Menunggu Konfirmasi' && $order->status_pembayaran !== 'Lunas';
        $pesanWa = 'Halo '.$order->nama_pelanggan.', ini '.$toko->nama_toko.' mengenai pesanan '.$order->nomor_pesanan.'.';
        $kelasInput = 'rounded-lg border border-garis bg-latar px-3 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-sun';
    @endphp

    <a href="{{ route('admin.pesanan.index') }}" class="text-sm font-semibold hover:underline">← Daftar pesanan</a>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl font-extrabold tracking-wide">{{ $order->nomor_pesanan }}</h2>
            <div class="mt-2 flex flex-wrap gap-2">
                <x-badge :warna="$order->warnaStatus()">Pesanan: {{ $order->status_pesanan }}</x-badge>
                <x-badge :warna="$order->warnaPembayaran()">Pembayaran: {{ $order->status_pembayaran }}</x-badge>
            </div>
        </div>
        <x-button varian="wa" :href="$order->waUrl($pesanWa)" target="_blank" rel="noopener"><x-icon nama="whatsapp" ukuran="size-4" /> Hubungi Pelanggan</x-button>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Pelanggan dan pengambilan --}}
            <section class="rounded-2xl border border-garis bg-kartu p-5">
                <h3 class="font-extrabold">Pelanggan dan pengambilan</h3>
                <dl class="mt-3 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-teks-lemah">Nama</dt><dd class="font-semibold">{{ $order->nama_pelanggan }}</dd></div>
                    <div><dt class="text-teks-lemah">Nomor WhatsApp</dt><dd class="font-semibold">{{ $order->no_whatsapp }}</dd></div>
                    <div><dt class="text-teks-lemah">Metode pengambilan</dt><dd class="font-semibold">{{ $order->metode_pengambilan }}</dd></div>
                    <div><dt class="text-teks-lemah">Waktu pesan</dt><dd class="font-semibold">{{ $order->created_at->format('d-m-Y H:i') }} WIB</dd></div>
                    @if ($diantar)
                        <div class="sm:col-span-2"><dt class="text-teks-lemah">Alamat pengantaran</dt><dd class="font-semibold">{{ $order->alamat_pengantaran }}</dd></div>
                    @endif
                </dl>
            </section>

            {{-- Rincian barang --}}
            <section class="rounded-2xl border border-garis bg-kartu p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-extrabold">Rincian barang</h3>
                    @if ($bisaUbahItem)
                        <x-button :href="route('admin.pesanan.item', $order)" varian="garis" class="!px-3 !py-1.5"><x-icon nama="pencil" ukuran="size-4" /> Ubah item &amp; total</x-button>
                    @endif
                </div>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-teks-lemah"><tr><th class="py-2 pr-3 font-semibold">Produk</th><th class="px-3 py-2 text-right font-semibold">Jumlah</th><th class="px-3 py-2 text-right font-semibold">Harga</th><th class="py-2 pl-3 text-right font-semibold">Subtotal</th></tr></thead>
                        <tbody class="tabular-nums">
                            @foreach ($order->items as $i)
                                <tr class="border-t border-garis">
                                    <td class="py-2 pr-3">{{ $i->nama_produk_snapshot }}</td>
                                    <td class="px-3 py-2 text-right">{{ $i->kuantitas }}</td>
                                    <td class="px-3 py-2 text-right">@rupiah($i->harga_snapshot)</td>
                                    <td class="py-2 pl-3 text-right font-semibold">@rupiah($i->subtotal)</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <dl class="mt-3 space-y-1 border-t border-garis pt-3 text-sm tabular-nums">
                    <div class="flex justify-between"><dt class="text-teks-lemah">Subtotal barang</dt><dd>@rupiah($order->total_harga - $order->ongkir)</dd></div>
                    @if ($diantar)
                        <div class="flex justify-between"><dt class="text-teks-lemah">Ongkir</dt><dd>@rupiah($order->ongkir)</dd></div>
                    @endif
                    <div class="flex justify-between text-base"><dt class="font-bold">Total</dt><dd class="font-extrabold">@rupiah($order->total_harga)</dd></div>
                </dl>
            </section>

            {{-- Catatan admin --}}
            <section class="rounded-2xl border border-garis bg-kartu p-5">
                <h3 class="font-extrabold">Catatan admin</h3>
                <form method="POST" action="{{ route('admin.pesanan.catatan', $order) }}" class="mt-3">
                    @csrf @method('PATCH')
                    <label for="catatan_admin" class="sr-only">Catatan admin</label>
                    <textarea id="catatan_admin" name="catatan_admin" rows="5" class="{{ $kelasInput }} w-full">{{ old('catatan_admin', $order->catatan_admin) }}</textarea>
                    <p class="mt-1 text-xs text-teks-lemah">Catatan internal, tidak terlihat pelanggan. Perubahan item, pembatalan, dan kedaluwarsa tercatat otomatis di sini.</p>
                    <x-button type="submit" class="mt-3">Simpan catatan</x-button>
                </form>
            </section>
        </div>

        <div class="space-y-6">
            {{-- Status pesanan --}}
            <section class="rounded-2xl border border-garis bg-kartu p-5">
                <h3 class="font-extrabold">Status pesanan</h3>
                @if ($opsiStatus)
                    <form method="POST" action="{{ route('admin.pesanan.status', $order) }}" class="mt-3 flex gap-2" onsubmit="return confirm('Ubah status pesanan?')">
                        @csrf @method('PATCH')
                        <label for="status" class="sr-only">Status baru</label>
                        <select id="status" name="status" class="{{ $kelasInput }} min-w-0 flex-1">
                            @foreach ($opsiStatus as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                        <x-button type="submit">Simpan</x-button>
                    </form>
                @else
                    <p class="mt-3 text-sm text-teks-lemah">{{ $final ? 'Pesanan sudah final. Status tidak bisa diubah.' : 'Belum ada perubahan status yang tersedia.' }}</p>
                @endif
                @if (in_array($order->status_pesanan, ['Siap Diambil', 'Sedang Diantarkan']) && $order->status_pembayaran !== 'Lunas')
                    <p class="mt-3 rounded-lg bg-amber-100 px-3 py-2 text-xs font-semibold text-amber-900 dark:bg-amber-900/40 dark:text-amber-200">Catat pembayaran Lunas terlebih dahulu agar pesanan bisa diselesaikan.</p>
                @endif
            </section>

            {{-- Pembayaran --}}
            <section class="rounded-2xl border border-garis bg-kartu p-5">
                <h3 class="font-extrabold">Pembayaran</h3>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-teks-lemah">Metode</dt><dd class="font-semibold">{{ $order->metode_pembayaran }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-teks-lemah">Jumlah</dt><dd class="font-semibold tabular-nums">@rupiah($order->total_harga)</dd></div>
                    @if ($order->status_pesanan === 'Menunggu Pembayaran' && $order->batas_bayar_at)
                        <div class="flex justify-between gap-3"><dt class="text-teks-lemah">Batas bayar</dt><dd class="font-semibold">{{ $order->batas_bayar_at->format('d-m-Y H:i') }} WIB</dd></div>
                    @endif
                </dl>

                @if ($bisaMintaBayar)
                    <form method="POST" action="{{ route('admin.pesanan.minta-pembayaran', $order) }}" class="mt-4" onsubmit="return confirm('Minta pelanggan membayar sekarang? Batas waktu pembayaran mulai dihitung.')">
                        @csrf
                        <x-button type="submit" varian="garis" class="w-full">Minta pembayaran</x-button>
                        <p class="mt-1 text-xs text-teks-lemah">Gunakan setelah ongkir pasti, agar total yang dibayar sudah benar.</p>
                    </form>
                @endif

                @if ($order->status_pembayaran !== 'Lunas' && ! $final)
                    <form method="POST" action="{{ route('admin.pesanan.pembayaran', $order) }}" class="mt-4" onsubmit="return confirm('Simpan status pembayaran? Pilih Lunas hanya jika uang sudah benar-benar diterima.')">
                        @csrf @method('PATCH')
                        <label for="status_pembayaran" class="mb-1 block text-sm font-semibold">Ubah status pembayaran</label>
                        <div class="flex gap-2">
                            <select id="status_pembayaran" name="status_pembayaran" class="{{ $kelasInput }} min-w-0 flex-1">
                                @foreach (\App\Models\Order::STATUS_PEMBAYARAN as $s)
                                    <option value="{{ $s }}" @selected($order->status_pembayaran === $s)>{{ $s }}</option>
                                @endforeach
                            </select>
                            <x-button type="submit">Simpan</x-button>
                        </div>
                    </form>
                @endif

                @if ($order->payments->isNotEmpty())
                    <h4 class="mt-5 text-sm font-bold">Riwayat transaksi</h4>
                    <ul class="mt-2 space-y-2 text-xs">
                        @foreach ($order->payments as $p)
                            <li class="rounded-lg bg-latar p-3">
                                <span class="font-bold">{{ $p->status }}</span> · @rupiah($p->jumlah) · {{ $p->provider }}
                                <span class="block text-teks-lemah">{{ $p->created_at->format('d-m-Y H:i') }}{{ $p->dibayar_at ? ' · dibayar '.$p->dibayar_at->format('d-m-Y H:i') : '' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
@endsection

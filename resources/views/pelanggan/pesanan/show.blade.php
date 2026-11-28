@extends('layouts.pelanggan')

@section('judul', 'Pesanan '.$order->nomor_pesanan)

@section('konten')
    @php
        $diantar = $order->metode_pengambilan === 'Diantarkan';
        $pesanWa = 'Halo '.$toko->nama_toko.', saya '.$order->nama_pelanggan.' ingin menanyakan pesanan '.$order->nomor_pesanan.'.';
    @endphp

    <x-container class="py-8">
        <div class="mx-auto max-w-3xl space-y-5">
            @if (session('baru'))
                <div class="rounded-2xl bg-green-100 p-5 text-green-900 dark:bg-green-900/40 dark:text-green-200">
                    <p class="flex items-center gap-2 font-extrabold"><x-icon nama="check" /> Pesanan berhasil dicatat</p>
                    <p class="mt-1 text-sm">Simpan nomor pesanan Anda. Status bisa dicek kapan saja lewat menu Cek Status Pesanan dengan nomor pesanan dan nomor WhatsApp.</p>
                </div>
            @endif

            @if ($order->status_pesanan === 'Kedaluwarsa')
                <div role="alert" class="rounded-2xl bg-red-100 p-5 text-sm font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-300">
                    Pesanan ini kedaluwarsa karena pembayaran tidak diterima sebelum batas waktu. Silakan buat pesanan baru.
                </div>
            @endif

            <div class="rounded-2xl border border-garis bg-kartu p-5">
                <p class="text-sm text-teks-lemah">Nomor pesanan</p>
                <h1 class="text-2xl font-extrabold tracking-wide">{{ $order->nomor_pesanan }}</h1>
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-badge :warna="$order->warnaStatus()">Pesanan: {{ $order->labelStatus() }}</x-badge>
                    <x-badge :warna="$order->warnaPembayaran()">Pembayaran: {{ $order->status_pembayaran }}</x-badge>
                </div>
                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-teks-lemah">Waktu pesan</dt><dd class="font-semibold">{{ $order->created_at->format('d-m-Y H:i') }} WIB</dd></div>
                    <div><dt class="text-teks-lemah">Nama</dt><dd class="font-semibold">{{ $order->nama_pelanggan }}</dd></div>
                    <div><dt class="text-teks-lemah">Nomor WhatsApp</dt><dd class="font-semibold">{{ $order->no_whatsapp }}</dd></div>
                    <div><dt class="text-teks-lemah">Metode pengambilan</dt><dd class="font-semibold">{{ $order->metode_pengambilan }}</dd></div>
                    @if ($diantar)
                        <div class="sm:col-span-2"><dt class="text-teks-lemah">Alamat pengantaran</dt><dd class="font-semibold">{{ $order->alamat_pengantaran }}</dd></div>
                    @endif
                    <div><dt class="text-teks-lemah">Metode pembayaran</dt><dd class="font-semibold">{{ $order->metode_pembayaran }}</dd></div>
                </dl>
            </div>

            <div class="rounded-2xl border border-garis bg-kartu p-5">
                <h2 class="font-extrabold">Rincian pesanan</h2>
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
                <dl class="mt-3 space-y-1 border-t border-garis pt-3 text-sm">
                    <div class="flex justify-between"><dt class="text-teks-lemah">Subtotal barang</dt><dd class="tabular-nums">@rupiah($order->total_harga - $order->ongkir)</dd></div>
                    @if ($diantar)
                        <div class="flex justify-between"><dt class="text-teks-lemah">Ongkir</dt>
                            <dd class="tabular-nums">{{ ($order->ongkir == 0 && $order->status_pesanan === 'Menunggu Konfirmasi') ? 'Dikonfirmasi admin via WhatsApp' : 'Rp '.number_format($order->ongkir, 0, ',', '.') }}</dd></div>
                    @endif
                    <div class="flex justify-between text-base"><dt class="font-bold">Total</dt><dd class="font-extrabold tabular-nums">@rupiah($order->total_harga)</dd></div>
                </dl>
            </div>

            {{-- Pembayaran Transfer/QRIS: hanya ditampilkan saat pesanan menunggu pembayaran --}}
            @if ($order->status_pesanan === 'Menunggu Pembayaran')
                <div class="rounded-2xl border border-sun bg-sun/10 p-5 text-sm">
                    <h2 class="font-extrabold">Selesaikan pembayaran</h2>
                    @if ($order->batas_bayar_at)
                        <p class="mt-1 font-semibold">Bayar sebelum {{ $order->batas_bayar_at->format('d-m-Y H:i') }} WIB</p>
                    @endif
                    @if ($toko->nama_bank && $toko->no_rekening)
                        <p class="mt-3">{{ $toko->nama_bank }} · <b class="tabular-nums">{{ $toko->no_rekening }}</b> a.n. {{ $toko->atas_nama_rekening }}</p>
                    @endif
                    @if ($toko->gambar_qris)
                        <img src="{{ asset('storage/'.$toko->gambar_qris) }}" alt="Kode QRIS {{ $toko->nama_toko }}" class="mt-3 size-48 rounded-lg bg-white object-contain p-2">
                    @endif
                    @if (! ($toko->nama_bank && $toko->no_rekening) && ! $toko->gambar_qris)
                        <p class="mt-3">Hubungi admin lewat WhatsApp untuk informasi rekening atau QRIS.</p>
                    @endif
                    <p class="mt-3 text-teks-lemah">Pembayaran dicek oleh admin. Pesanan otomatis kedaluwarsa jika tidak dibayar sebelum batas waktu, dan stoknya dilepas kembali.</p>
                </div>
            @elseif ($order->status_pesanan === 'Menunggu Konfirmasi' && $order->metode_pembayaran === 'Transfer/QRIS')
                <div class="rounded-2xl border border-garis bg-kartu p-5 text-sm text-teks-lemah">
                    {{ $order->status_pembayaran === 'Lunas'
                        ? 'Pembayaran sudah diterima. Admin sedang memeriksa ketersediaan barang.'
                        : 'Admin akan menghubungi Anda lewat WhatsApp untuk konfirmasi dan ongkir, lalu meminta pembayaran.' }}
                </div>
            @endif

            <div class="flex flex-wrap gap-3">
                <x-button varian="wa" :href="$toko->waUrl($pesanWa)"><x-icon nama="whatsapp" ukuran="size-4" /> Hubungi Admin via WhatsApp</x-button>
                @if (in_array($order->status_pesanan, ['Menunggu Konfirmasi', 'Menunggu Pembayaran']) && $order->status_pembayaran === 'Belum Dibayar')
                    <form method="POST" action="{{ route('pesanan.batal', $order->public_token) }}" onsubmit="return confirm('Batalkan pesanan ini?')">
                        @csrf
                        <x-button type="submit" varian="garis">Ajukan Pembatalan</x-button>
                    </form>
                @endif
            </div>
        </div>
    </x-container>
@endsection

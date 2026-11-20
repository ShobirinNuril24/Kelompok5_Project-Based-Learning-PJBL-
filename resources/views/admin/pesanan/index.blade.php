@extends('layouts.admin')

@section('judul', 'Pesanan')

@section('konten')
    {{-- Pencarian --}}
    <form method="GET" action="{{ route('admin.pesanan.index') }}" class="flex flex-wrap gap-2">
        @if (request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        <label for="q" class="sr-only">Cari pesanan</label>
        <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Cari nomor pesanan atau nama pelanggan..."
               class="w-full max-w-md rounded-lg border border-garis bg-kartu px-3 py-2.5 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-sun">
        <x-button type="submit"><x-icon nama="search" ukuran="size-4" /> Cari</x-button>
    </form>

    {{-- Filter status --}}
    <div class="mt-4 flex flex-wrap gap-2" aria-label="Filter status">
        <a href="{{ route('admin.pesanan.index', array_filter(['q' => request('q')])) }}"
           @class(['rounded-full border px-3.5 py-1.5 text-sm font-semibold', 'border-sun bg-sun text-ink' => ! request('status'), 'border-garis bg-kartu hover:border-sun' => request('status')])>
            Semua <span class="tabular-nums">{{ $jumlah->sum() }}</span></a>
        @foreach (\App\Models\Order::STATUS_PESANAN as $s)
            <a href="{{ route('admin.pesanan.index', array_filter(['status' => $s, 'q' => request('q')])) }}"
               @class(['rounded-full border px-3.5 py-1.5 text-sm font-semibold', 'border-sun bg-sun text-ink' => request('status') === $s, 'border-garis bg-kartu hover:border-sun' => request('status') !== $s])>
                {{ $s }} <span class="tabular-nums">{{ $jumlah[$s] ?? 0 }}</span></a>
        @endforeach
    </div>

    <div class="mt-5 rounded-2xl border border-garis bg-kartu p-5">
        @if ($pesanan->isEmpty())
            <div class="rounded-xl border border-dashed border-garis p-10 text-center">
                <x-icon nama="receipt" ukuran="size-10 mx-auto text-teks-lemah" />
                <p class="mt-3 font-semibold">Tidak ada pesanan yang cocok</p>
                <p class="text-sm text-teks-lemah">Ubah kata kunci atau filter status.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-teks-lemah">
                        <tr>
                            <th class="py-2 pr-3 font-semibold">Nomor pesanan</th><th class="px-3 py-2 font-semibold">Pelanggan</th><th class="px-3 py-2 font-semibold">Penerimaan</th>
                            <th class="px-3 py-2 font-semibold">Pembayaran</th><th class="px-3 py-2 text-right font-semibold">Total</th><th class="px-3 py-2 font-semibold">Status</th>
                            <th class="px-3 py-2 font-semibold">Waktu</th><th class="py-2 pl-3 font-semibold"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pesanan as $o)
                            <tr class="border-t border-garis">
                                <td class="whitespace-nowrap py-2.5 pr-3 font-bold">{{ $o->nomor_pesanan }}</td>
                                <td class="px-3 py-2.5">{{ $o->nama_pelanggan }}</td>
                                <td class="px-3 py-2.5">{{ $o->metode_pengambilan }}</td>
                                <td class="px-3 py-2.5">{{ $o->metode_pembayaran }}<br><x-badge :warna="$o->warnaPembayaran()">{{ $o->status_pembayaran }}</x-badge></td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">@rupiah($o->total_harga)</td>
                                <td class="px-3 py-2.5"><x-badge :warna="$o->warnaStatus()">{{ $o->status_pesanan }}</x-badge></td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-teks-lemah">{{ $o->created_at->format('d-m-Y H:i') }}</td>
                                <td class="py-2.5 pl-3"><x-button :href="route('admin.pesanan.show', $o)" varian="garis" class="!px-3 !py-1.5">Detail</x-button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $pesanan->links() }}</div>
        @endif
    </div>
@endsection

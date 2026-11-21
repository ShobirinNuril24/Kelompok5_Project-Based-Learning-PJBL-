@extends('layouts.admin')

@section('judul', 'Riwayat Transaksi')

@section('konten')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-teks-lemah">Pendapatan = total pesanan berstatus <b>Selesai</b> (sudah Lunas), menurut tanggal pesanan dibuat.</p>
        <div class="flex gap-2" aria-label="Periode">
            @foreach ([7 => '7 hari', 30 => '30 hari', 90 => '90 hari'] as $nilai => $label)
                <a href="{{ route('admin.riwayat.index', ['hari' => $nilai]) }}"
                   @class(['rounded-full border px-3.5 py-1.5 text-sm font-semibold', 'border-sun bg-sun text-ink' => $hari === $nilai, 'border-garis bg-kartu hover:border-sun' => $hari !== $nilai])>{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <section class="mt-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-admin.kartu-stat label="Pendapatan" :nilai="'Rp '.number_format($pendapatan, 0, ',', '.')" :ket="$hari.' hari terakhir'" class="col-span-2" />
        <x-admin.kartu-stat label="Pesanan selesai" :nilai="$jumlah" />
        <x-admin.kartu-stat label="Rata-rata per pesanan" :nilai="'Rp '.number_format($jumlah ? intdiv($pendapatan, $jumlah) : 0, 0, ',', '.')" />
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- Grafik pendapatan harian --}}
        <section class="rounded-2xl border border-garis bg-kartu p-5 xl:col-span-2">
            <h2 class="font-extrabold">Pendapatan harian</h2>
            @if ($pendapatan === 0)
                <p class="mt-4 rounded-xl border border-dashed border-garis p-8 text-center text-sm text-teks-lemah">Belum ada pesanan selesai pada periode ini.</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <div class="flex h-48 items-end gap-1" style="min-width: {{ $grafik->count() * 14 }}px" role="img"
                         aria-label="Grafik pendapatan harian {{ $hari }} hari terakhir, total Rp {{ number_format($pendapatan, 0, ',', '.') }}">
                        @foreach ($grafik as $g)
                            <div class="flex h-full flex-1 items-end" title="{{ $g['tgl']->format('d-m-Y') }}: Rp {{ number_format($g['total'], 0, ',', '.') }}">
                                <div class="w-full rounded-t bg-sun" style="height: {{ $g['total'] > 0 ? max(3, round($g['total'] / $maks * 100)) : 0 }}%"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex justify-between text-xs text-teks-lemah" style="min-width: {{ $grafik->count() * 14 }}px">
                        <span>{{ $grafik->first()['tgl']->format('d-m') }}</span><span>{{ $grafik->last()['tgl']->format('d-m') }}</span>
                    </div>
                </div>
            @endif
        </section>

        {{-- Per metode pembayaran --}}
        <section class="rounded-2xl border border-garis bg-kartu p-5">
            <h2 class="font-extrabold">Per metode pembayaran</h2>
            @forelse ($perMetode as $m)
                <div class="mt-3 flex items-center justify-between gap-3 text-sm">
                    <span><b>{{ $m->metode_pembayaran }}</b><span class="block text-xs text-teks-lemah">{{ $m->jumlah }} pesanan</span></span>
                    <span class="font-bold tabular-nums">@rupiah($m->total)</span>
                </div>
            @empty
                <p class="mt-3 text-sm text-teks-lemah">Belum ada data.</p>
            @endforelse
        </section>
    </div>

    {{-- Daftar transaksi --}}
    <section class="mt-6 rounded-2xl border border-garis bg-kartu p-5">
        <h2 class="font-extrabold">Transaksi selesai</h2>
        @if ($pesanan->isEmpty())
            <p class="mt-3 rounded-xl border border-dashed border-garis p-8 text-center text-sm text-teks-lemah">Belum ada transaksi selesai pada periode ini.</p>
        @else
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-teks-lemah">
                        <tr><th class="py-2 pr-3 font-semibold">Nomor pesanan</th><th class="px-3 py-2 font-semibold">Pelanggan</th><th class="px-3 py-2 font-semibold">Metode</th><th class="px-3 py-2 text-right font-semibold">Total</th><th class="py-2 pl-3 font-semibold">Tanggal</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($pesanan as $o)
                            <tr class="border-t border-garis">
                                <td class="whitespace-nowrap py-2.5 pr-3"><a href="{{ route('admin.pesanan.show', $o) }}" class="font-bold hover:underline">{{ $o->nomor_pesanan }}</a></td>
                                <td class="px-3 py-2.5">{{ $o->nama_pelanggan }}</td>
                                <td class="px-3 py-2.5">{{ $o->metode_pembayaran }} · {{ $o->metode_pengambilan }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">@rupiah($o->total_harga)</td>
                                <td class="whitespace-nowrap py-2.5 pl-3 text-teks-lemah">{{ $o->created_at->format('d-m-Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $pesanan->links() }}</div>
        @endif
    </section>
@endsection

@extends('layouts.admin')

@section('judul', 'Dasbor')

@section('konten')
    @php
        $diproses = $hitung['Diproses'] + $hitung['Siap Diambil'] + $hitung['Sedang Diantarkan'];
        $perluTindakan = [
            ['Menunggu Konfirmasi', 'Menunggu konfirmasi', 'Periksa stok lalu proses'],
            ['Menunggu Pembayaran', 'Menunggu pembayaran', 'Belum dibayar pelanggan'],
        ];
    @endphp

    {{-- Sorotan: hal yang butuh tindakan --}}
    <section class="rounded-2xl bg-sun p-5 text-ink sm:p-6">
        <p class="text-sm font-bold">Perlu tindakan Anda</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-3">
            @foreach ($perluTindakan as [$status, $label, $ket])
                <a href="{{ route('admin.pesanan.index', ['status' => $status]) }}"
                   class="rounded-xl bg-white/60 p-4 hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-ink">
                    <span class="block text-4xl font-extrabold tabular-nums">{{ $hitung[$status] }}</span>
                    <span class="block text-sm font-bold">{{ $label }}</span>
                    <span class="text-xs">{{ $ket }}</span>
                </a>
            @endforeach
            <div class="rounded-xl bg-white/60 p-4">
                <span class="block text-4xl font-extrabold tabular-nums">{{ $habis }}</span>
                <span class="block text-sm font-bold">Produk habis</span>
                <span class="text-xs">{{ $menipis->count() }} lainnya hampir habis</span>
            </div>
        </div>
    </section>

    {{-- Ringkasan --}}
    <section class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-admin.kartu-stat label="Pesanan masuk" :nilai="$total" :ket="$hariIni.' hari ini'" :href="route('admin.pesanan.index')" />
        <x-admin.kartu-stat label="Sedang diproses" :nilai="$diproses" ket="Diproses, siap diambil, diantarkan" />
        <x-admin.kartu-stat label="Selesai" :nilai="$hitung['Selesai']" :href="route('admin.pesanan.index', ['status' => 'Selesai'])" />
        <x-admin.kartu-stat label="Dibatalkan" :nilai="$hitung['Dibatalkan']" :ket="$hitung['Kedaluwarsa'].' kedaluwarsa'" :href="route('admin.pesanan.index', ['status' => 'Dibatalkan'])" />
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- Pesanan terbaru --}}
        <section class="rounded-2xl border border-garis bg-kartu p-5 xl:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="font-extrabold">Pesanan terbaru</h2>
                <a href="{{ route('admin.pesanan.index') }}" class="text-sm font-bold hover:underline">Lihat semua</a>
            </div>
            @if ($terbaru->isEmpty())
                <p class="mt-4 rounded-xl border border-dashed border-garis p-8 text-center text-sm text-teks-lemah">Belum ada pesanan masuk.</p>
            @else
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-teks-lemah"><tr><th class="py-2 pr-3 font-semibold">Pesanan</th><th class="px-3 py-2 font-semibold">Pelanggan</th><th class="px-3 py-2 text-right font-semibold">Total</th><th class="py-2 pl-3 font-semibold">Status</th></tr></thead>
                        <tbody>
                            @foreach ($terbaru as $o)
                                <tr class="border-t border-garis">
                                    <td class="py-2.5 pr-3"><a href="{{ route('admin.pesanan.show', $o) }}" class="font-bold hover:underline">{{ $o->nomor_pesanan }}</a>
                                        <span class="block text-xs text-teks-lemah">{{ $o->created_at->format('d-m-Y H:i') }}</span></td>
                                    <td class="px-3 py-2.5">{{ $o->nama_pelanggan }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">@rupiah($o->total_harga)</td>
                                    <td class="py-2.5 pl-3"><x-badge :warna="$o->warnaStatus()">{{ $o->status_pesanan }}</x-badge></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Ketersediaan produk --}}
        <section class="rounded-2xl border border-garis bg-kartu p-5">
            <h2 class="font-extrabold">Ketersediaan produk</h2>
            <div class="mt-3 grid grid-cols-2 gap-3 text-center">
                <div class="rounded-xl bg-green-100 p-3 dark:bg-green-900/40"><p class="text-2xl font-extrabold tabular-nums">{{ $tersedia }}</p><p class="text-xs font-semibold">Tersedia</p></div>
                <div class="rounded-xl bg-red-100 p-3 dark:bg-red-900/40"><p class="text-2xl font-extrabold tabular-nums">{{ $habis }}</p><p class="text-xs font-semibold">Habis</p></div>
            </div>
            <h3 class="mt-5 text-sm font-bold">Stok menipis (≤ {{ $ambang }})</h3>
            @forelse ($menipis as $p)
                <p class="mt-2 flex justify-between gap-3 text-sm"><span>{{ $p->nama_produk }}</span><x-badge warna="peringatan">{{ $p->stok }} {{ $p->satuan }}</x-badge></p>
            @empty
                <p class="mt-2 text-sm text-teks-lemah">Tidak ada produk yang hampir habis.</p>
            @endforelse
        </section>
    </div>
@endsection

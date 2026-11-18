@extends('layouts.pelanggan')

@section('judul', 'Tentang Kami')

@section('konten')
    @php
        $hari = ['monday' => 'Senin', 'tuesday' => 'Selasa', 'wednesday' => 'Rabu', 'thursday' => 'Kamis', 'friday' => 'Jumat', 'saturday' => 'Sabtu', 'sunday' => 'Minggu'];
    @endphp

    <x-container class="py-8">
        <div class="mx-auto max-w-3xl">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/logo-toko-10.png') }}" alt="" class="size-20">
                <div>
                    <h1 class="text-2xl font-extrabold">Tentang {{ $toko->nama_toko }}</h1>
                    <p class="text-teks-lemah">{{ $toko->alamat_toko }}</p>
                </div>
            </div>

            <p class="mt-6">{{ $toko->nama_toko }} melayani kebutuhan sembako harian. Anda bisa memesan lewat website ini, lalu mengambil pesanan di toko atau meminta diantarkan oleh penjual.</p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-garis bg-kartu p-5">
                    <h2 class="flex items-center gap-2 font-extrabold"><x-icon nama="clock" ukuran="size-4 text-sun" /> Jam operasional</h2>
                    <dl class="mt-3 grid grid-cols-[5rem_1fr] gap-y-1 text-sm tabular-nums">
                        @foreach ($hari as $kunci => $nama)
                            @php $j = $toko->jadwal_operasional[$kunci] ?? null; @endphp
                            <dt>{{ $nama }}</dt>
                            <dd>{{ $j && $j['is_open'] ? str_replace(':', '.', $j['open']).'–'.str_replace(':', '.', $j['close']).' WIB' : 'Tutup' }}</dd>
                        @endforeach
                    </dl>
                    <p class="mt-3 text-xs text-teks-lemah">Toko dapat tutup pada hari raya atau tanggal khusus.</p>
                </div>
                <div class="rounded-2xl border border-garis bg-kartu p-5 text-sm">
                    <h2 class="flex items-center gap-2 font-extrabold"><x-icon nama="pin" ukuran="size-4 text-sun" /> Lokasi dan kontak</h2>
                    <p class="mt-3">{{ $toko->alamat_toko }}</p>
                    <p class="mt-2">WhatsApp: <b>{{ $toko->no_whatsapp }}</b></p>
                    @if ($toko->catatan_pengantaran)
                        <p class="mt-2 text-teks-lemah">{{ $toko->catatan_pengantaran }}</p>
                    @endif
                    <x-button varian="wa" :href="$toko->waUrl('Halo '.$toko->nama_toko.', saya ingin bertanya.')" class="mt-4">
                        <x-icon nama="whatsapp" ukuran="size-4" /> Hubungi Penjual
                    </x-button>
                </div>
            </div>
        </div>
    </x-container>
@endsection

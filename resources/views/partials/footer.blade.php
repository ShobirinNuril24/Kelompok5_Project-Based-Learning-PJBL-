@php
    $hari = ['monday' => 'Senin', 'tuesday' => 'Selasa', 'wednesday' => 'Rabu', 'thursday' => 'Kamis', 'friday' => 'Jumat', 'saturday' => 'Sabtu', 'sunday' => 'Minggu'];
@endphp

<footer class="mt-16 bg-ink text-stone-300">
    <x-container class="grid gap-10 py-12 md:grid-cols-3">
        {{-- Identitas --}}
        <div>
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo-toko-10.png') }}" alt="" class="size-12">
                <span class="text-lg font-extrabold text-white">{{ $toko->nama_toko }}</span>
            </div>
            <p class="mt-4 max-w-xs text-sm">Pesan sembako dari rumah, lalu ambil di toko atau minta diantarkan.</p>
        </div>

        {{-- Tautan --}}
        <nav aria-label="Tautan footer">
            <h2 class="text-sm font-bold uppercase tracking-wide text-white">Tautan</h2>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($menu as [$label, $rute])
                    <li><a href="{{ route($rute) }}" class="hover:text-sun focus:outline-none focus-visible:underline">{{ $label }}</a></li>
                @endforeach
            </ul>
        </nav>

        {{-- Kontak dan jam buka (dari pengaturan toko di database) --}}
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wide text-white">Hubungi Kami</h2>
            <ul class="mt-4 space-y-3 text-sm">
                <li class="flex gap-2"><x-icon nama="pin" ukuran="size-4 mt-0.5 shrink-0 text-sun" /> {{ $toko->alamat_toko }}</li>
                <li class="flex gap-2">
                    <x-icon nama="whatsapp" ukuran="size-4 mt-0.5 shrink-0 text-sun" />
                    <a href="{{ $toko->waUrl() }}" class="hover:text-sun">{{ $toko->no_whatsapp }}</a>
                </li>
                <li class="flex gap-2">
                    <x-icon nama="clock" ukuran="size-4 mt-0.5 shrink-0 text-sun" />
                    <dl class="grid grid-cols-[4.5rem_1fr] gap-x-2 gap-y-0.5 tabular-nums">
                        @foreach ($hari as $kunci => $nama)
                            @php $j = $toko->jadwal_operasional[$kunci] ?? null; @endphp
                            <dt>{{ $nama }}</dt>
                            <dd>{{ $j && $j['is_open'] ? str_replace(':', '.', $j['open']).'–'.str_replace(':', '.', $j['close']).' WIB' : 'Tutup' }}</dd>
                        @endforeach
                    </dl>
                </li>
            </ul>
        </div>
    </x-container>

    <div class="border-t border-white/10 py-4 text-center text-xs">
        © {{ date('Y') }} {{ $toko->nama_toko }}. Semua hak dilindungi.
    </div>
</footer>

<header x-data="{ buka: false }" class="sticky top-0 z-40 border-b border-garis bg-latar/90 backdrop-blur">
    <x-container class="flex h-16 items-center gap-3">
        {{-- Logo --}}
        <a href="{{ route('beranda') }}" class="flex items-center gap-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-sun">
            <img src="{{ asset('images/logo-toko-10.png') }}" alt="" class="size-10">
            <span class="font-extrabold leading-tight">
                {{ $toko->nama_toko }}
                <span class="hidden text-xs font-medium text-teks-lemah sm:block">{{ \Illuminate\Support\Str::limit($toko->alamat_toko, 38) }}</span>
            </span>
        </a>

        {{-- Menu desktop --}}
        <nav class="ml-6 hidden items-center gap-1 md:flex" aria-label="Menu utama">
            @foreach ($menu as [$label, $rute])
                <a href="{{ route($rute) }}" @if (request()->routeIs($rute)) aria-current="page" @endif
                   @class([
                       'rounded-lg px-3 py-2 text-sm hover:bg-sun/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-sun',
                       'bg-sun/30 font-bold' => request()->routeIs($rute),
                       'font-medium' => ! request()->routeIs($rute),
                   ])>{{ $label }}</a>
            @endforeach
        </nav>

        {{-- Aksi kanan --}}
        <div class="ml-auto flex items-center gap-2">
            {{-- Tombol mode terang/gelap --}}
            <button type="button"
                    x-data="{ gelap: document.documentElement.classList.contains('dark') }"
                    @click="gelap = !gelap; document.documentElement.classList.toggle('dark', gelap); try { localStorage.tema = gelap ? 'gelap' : 'terang' } catch (e) {}"
                    :aria-label="gelap ? 'Ubah ke mode terang' : 'Ubah ke mode gelap'"
                    class="grid size-10 place-items-center rounded-lg border border-garis hover:bg-sun/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-sun">
                <x-icon nama="moon" x-show="!gelap" />
                <x-icon nama="sun" x-show="gelap" x-cloak />
            </button>

            {{-- Keranjang (jumlah item ditambahkan di Phase 2) --}}
            <a href="{{ route('keranjang.index') }}" aria-label="Keranjang belanja"
               class="relative grid size-10 place-items-center rounded-lg border border-garis hover:bg-sun/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-sun">
                <x-icon nama="cart" />
            </a>

            <x-button varian="wa" :href="$toko->waUrl('Halo '.$toko->nama_toko.', saya ingin bertanya.')" class="hidden lg:inline-flex">
                <x-icon nama="whatsapp" ukuran="size-4" /> Hubungi Penjual
            </x-button>

            {{-- Tombol menu mobile --}}
            <button type="button" @click="buka = !buka" :aria-expanded="buka" aria-label="Menu"
                    class="grid size-10 place-items-center rounded-lg border border-garis hover:bg-sun/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-sun md:hidden">
                <x-icon nama="menu" x-show="!buka" />
                <x-icon nama="x" x-show="buka" x-cloak />
            </button>
        </div>
    </x-container>

    {{-- Menu mobile --}}
    <div x-show="buka" x-cloak x-transition class="border-t border-garis md:hidden">
        <x-container class="flex flex-col gap-1 py-3">
            @foreach ($menu as [$label, $rute])
                <a href="{{ route($rute) }}"
                   @class([
                       'rounded-lg px-3 py-2.5 hover:bg-sun/25',
                       'bg-sun/30 font-bold' => request()->routeIs($rute),
                       'font-medium' => ! request()->routeIs($rute),
                   ])>{{ $label }}</a>
            @endforeach
            <x-button varian="wa" :href="$toko->waUrl('Halo '.$toko->nama_toko.', saya ingin bertanya.')" class="mt-2">
                <x-icon nama="whatsapp" ukuran="size-4" /> Hubungi Penjual
            </x-button>
        </x-container>
    </div>
</header>

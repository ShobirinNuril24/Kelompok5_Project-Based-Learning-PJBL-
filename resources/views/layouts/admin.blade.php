<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Dasbor') — Admin Toko Sepuluh</title>

    <script>
        try {
            if (localStorage.tema === 'gelap' || (!localStorage.tema && matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-latar" x-data="{ menu: false }">
    @php
        // Menu sidebar: [label, route, ikon, pola route aktif]
        $menuAdmin = [
            ['Dasbor', 'admin.dashboard', 'home', 'admin.dashboard'],
            ['Pesanan', 'admin.pesanan.index', 'receipt', 'admin.pesanan.*'],
            ['Produk', 'admin.produk.index', 'box', 'admin.produk.*'],
            ['Kategori', 'admin.kategori.index', 'tag', 'admin.kategori.*'],
            ['Riwayat', 'admin.riwayat.index', 'chart', 'admin.riwayat.*'],
            ['Hari Libur', 'admin.hari-libur.index', 'calendar', 'admin.hari-libur.*'],
            ['Pengaturan', 'admin.pengaturan.edit', 'settings', 'admin.pengaturan.*'],
        ];
    @endphp

    <div class="flex min-h-screen">
        {{-- Latar gelap saat menu mobile terbuka --}}
        <div x-show="menu" x-cloak @click="menu = false" class="fixed inset-0 z-30 bg-black/50 lg:hidden"></div>

        {{-- Sidebar --}}
        <aside :class="menu ? 'flex' : 'hidden'"
               class="fixed inset-y-0 left-0 z-40 w-64 flex-col bg-ink text-stone-300 lg:sticky lg:top-0 lg:flex lg:h-screen">
            <div class="flex items-center gap-3 border-b border-white/10 p-4">
                <img src="{{ asset('images/logo-toko-10.png') }}" alt="" class="size-10">
                <div class="leading-tight">
                    <p class="font-extrabold text-white">Toko Sepuluh</p>
                    <p class="text-xs text-sun">Panel Admin</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto p-3" aria-label="Menu admin">
                @foreach ($menuAdmin as [$label, $rute, $ikon, $pola])
                    <a href="{{ route($rute) }}" @if (request()->routeIs($pola)) aria-current="page" @endif
                       @class([
                           'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-sun',
                           'bg-sun text-ink' => request()->routeIs($pola),
                           'hover:bg-white/10' => ! request()->routeIs($pola),
                       ])>
                        <x-icon :nama="$ikon" /> {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="space-y-1 border-t border-white/10 p-3">
                <a href="{{ route('beranda') }}" target="_blank" rel="noopener"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-sun">
                    <x-icon nama="external" /> Lihat Toko
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-sun">
                        <x-icon nama="logout" /> Logout
                    </button>
                </form>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            {{-- Header --}}
            <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-garis bg-kartu px-4 lg:px-8">
                <button type="button" @click="menu = true" aria-label="Buka menu"
                        class="grid size-10 place-items-center rounded-lg border border-garis hover:bg-sun/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-sun lg:hidden">
                    <x-icon nama="menu" />
                </button>
                <h1 class="text-lg font-extrabold">@yield('judul', 'Dasbor')</h1>

                <div class="ml-auto flex items-center gap-3">
                    <button type="button"
                            x-data="{ gelap: document.documentElement.classList.contains('dark') }"
                            @click="gelap = !gelap; document.documentElement.classList.toggle('dark', gelap); try { localStorage.tema = gelap ? 'gelap' : 'terang' } catch (e) {}"
                            :aria-label="gelap ? 'Ubah ke mode terang' : 'Ubah ke mode gelap'"
                            class="grid size-10 place-items-center rounded-lg border border-garis hover:bg-sun/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-sun">
                        <x-icon nama="moon" x-show="!gelap" />
                        <x-icon nama="sun" x-show="gelap" x-cloak />
                    </button>
                    <span class="hidden items-center gap-2 text-sm font-semibold sm:flex">
                        <span class="grid size-9 place-items-center rounded-full bg-sun font-extrabold text-ink">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        {{ auth()->user()->name }}
                    </span>
                </div>
            </header>

            <main class="p-4 lg:p-8">
                @include('admin.partials.pesan')
                @yield('konten')
            </main>
        </div>
    </div>
</body>
</html>

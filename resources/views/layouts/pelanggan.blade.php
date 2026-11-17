<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Beranda') — {{ $toko->nama_toko }}</title>

    {{-- Pasang mode gelap sebelum halaman tampil supaya tidak berkedip --}}
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
<body class="flex min-h-screen flex-col">
    @php
        // Menu dipakai bersama oleh header dan footer: [label, nama route]
        $menu = [
            ['Beranda', 'beranda'],
            ['Produk', 'produk.index'],
            ['Cek Status Pesanan', 'status.cek'],
            ['Tentang Kami', 'tentang'],
        ];
    @endphp

    <a href="#konten" class="sr-only z-50 rounded-lg bg-sun px-4 py-2 font-bold text-ink focus:not-sr-only focus:fixed focus:left-4 focus:top-4">
        Lewati ke konten
    </a>

    @include('partials.header')

    <main id="konten" class="flex-1">
        @yield('konten')
    </main>

    @include('partials.footer')
</body>
</html>

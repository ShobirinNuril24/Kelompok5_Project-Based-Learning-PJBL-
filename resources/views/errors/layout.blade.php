{{-- Halaman error mandiri: tanpa query database dan tanpa Vite, agar tetap tampil saat aplikasi bermasalah. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('kode') · @yield('judul') — Toko Sepuluh</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #FFFCF4; color: #292524;
               font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
        main { max-width: 28rem; padding: 2rem 1.5rem; text-align: center; }
        .kode { font-size: 3.5rem; font-weight: 800; color: #6B3F2A; margin: 0; }
        h1 { font-size: 1.375rem; margin: .5rem 0; }
        p { color: #57534e; line-height: 1.6; margin: 0 0 1.5rem; }
        a { display: inline-block; background: #F6C945; color: #292524; font-weight: 700; text-decoration: none;
            padding: .75rem 1.25rem; border-radius: .5rem; min-height: 24px; }
        a:focus-visible { outline: 3px solid #6B3F2A; outline-offset: 2px; }
    </style>
</head>
<body>
<main>
    <p class="kode">@yield('kode')</p>
    <h1>@yield('judul')</h1>
    <p>@yield('pesan')</p>
    <a href="{{ url('/') }}">Kembali ke beranda</a>
</main>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dasbor Admin — Toko Sepuluh</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cream p-8 font-sans text-ink">
    <h1 class="text-2xl font-bold text-wood">Dasbor Admin</h1>
    <p class="mt-2">Halo, {{ auth()->user()->name }}. Login berhasil.</p>
    <p class="mt-1 text-sm text-stone-500">Halaman sementara. Dasbor lengkap dibuat di Phase 6.</p>

    <form method="POST" action="{{ route('logout') }}" class="mt-6">
        @csrf
        <button class="rounded-lg bg-sun px-4 py-2 font-semibold text-wood-dark hover:bg-sun/80">Logout</button>
    </form>
</body>
</html>

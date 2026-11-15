<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cek Fondasi — Toko Sepuluh</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cream p-8 font-sans text-ink">
    <h1 class="text-3xl font-bold text-wood">Toko Sepuluh</h1>
    <p class="mt-2">Waktu aplikasi: {{ now()->format('d-m-Y H:i') }} ({{ config('app.timezone') }})</p>
    <p class="mt-1">Database: {{ DB::connection()->getDatabaseName() }}</p>

    <div x-data="{ n: 0 }" class="mt-6">
        <button @click="n++" class="rounded-lg bg-sun px-4 py-2 font-semibold text-wood-dark hover:bg-sun/80">
            Alpine jalan: <span x-text="n"></span>
        </button>
    </div>
</body>
</html>
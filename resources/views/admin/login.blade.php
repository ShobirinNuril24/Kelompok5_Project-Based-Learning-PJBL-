<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin — Toko Sepuluh</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-cream px-4 font-sans text-ink">
    <main class="w-full max-w-sm">
        <img src="{{ asset('images/logo-toko-10.png') }}" alt="Logo Toko Sepuluh" class="mx-auto h-24 w-24">

        <form method="POST" action="{{ route('login.store') }}" x-data="{ lihat: false }"
              class="mt-6 rounded-2xl border border-sun/60 bg-white p-6 shadow-sm">
            @csrf
            <h1 class="text-lg font-bold text-wood">Login Admin Toko 10</h1>

            @error('email')
                <p role="alert" class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p>
            @enderror

            <label for="email" class="mt-4 block text-sm font-semibold">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username"
                   class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-wood focus:outline-none focus:ring-2 focus:ring-sun">

            <label for="password" class="mt-4 block text-sm font-semibold">Password</label>
            <div class="relative mt-1">
                <input id="password" :type="lihat ? 'text' : 'password'" name="password" required
                       autocomplete="current-password"
                       class="w-full rounded-lg border border-stone-300 py-2 pl-3 pr-28 focus:border-wood focus:outline-none focus:ring-2 focus:ring-sun">
                <button type="button" @click="lihat = !lihat" x-text="lihat ? 'Sembunyikan' : 'Lihat'"
                        class="absolute inset-y-0 right-3 text-xs font-semibold text-wood hover:underline">Lihat</button>
            </div>

            <label class="mt-4 flex items-center gap-2 text-sm">
                <input type="checkbox" name="ingat_saya" class="size-4 accent-wood"> Ingat saya
            </label>

            <button type="submit"
                    class="mt-5 w-full rounded-lg bg-sun px-4 py-2.5 font-bold text-wood-dark hover:bg-sun/80 focus:outline-none focus:ring-2 focus:ring-wood">
                Login
            </button>
        </form>
    </main>
</body>
</html>

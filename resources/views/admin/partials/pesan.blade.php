@if (session('ok') || session('err') || $errors->any())
    <div class="mb-6 space-y-2">
        @if (session('ok'))
            <div role="status" class="rounded-lg bg-green-100 px-4 py-3 text-sm font-semibold text-green-800 dark:bg-green-900/40 dark:text-green-300">{{ session('ok') }}</div>
        @endif
        @if (session('err'))
            <div role="alert" class="rounded-lg bg-red-100 px-4 py-3 text-sm font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-300">{{ session('err') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-lg bg-red-100 px-4 py-3 text-sm text-red-800 dark:bg-red-900/40 dark:text-red-300">
                <ul class="list-disc space-y-0.5 pl-5">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif

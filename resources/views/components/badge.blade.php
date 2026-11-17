@props(['warna' => 'netral'])

@php
    $kelas = match ($warna) {
        'sukses' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'peringatan' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        'bahaya' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        'info' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300',
        default => 'bg-stone-200 text-stone-700 dark:bg-stone-700 dark:text-stone-200',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold '.$kelas]) }}>{{ $slot }}</span>

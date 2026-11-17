@props(['href' => null, 'varian' => 'utama'])

@php
    $kelas = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-sun focus-visible:ring-offset-2 focus-visible:ring-offset-latar disabled:cursor-not-allowed disabled:opacity-50 '.match ($varian) {
        'garis' => 'border border-garis bg-kartu text-teks hover:bg-sun/25',
        'wa' => 'bg-green-700 text-white hover:bg-green-800',
        default => 'bg-sun text-ink hover:bg-sun/85',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $kelas]) }}>{{ $slot }}</button>
@endif

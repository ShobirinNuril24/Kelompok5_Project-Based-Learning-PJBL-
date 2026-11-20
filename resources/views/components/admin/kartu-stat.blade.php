@props(['label', 'nilai', 'ket' => null, 'href' => null])

<div {{ $attributes->merge(['class' => 'relative rounded-2xl border border-garis bg-kartu p-5']) }}>
    <p class="text-sm font-semibold text-teks-lemah">{{ $label }}</p>
    <p class="mt-1 text-3xl font-extrabold tabular-nums">{{ $nilai }}</p>
    @if ($ket)
        <p class="mt-1 text-xs text-teks-lemah">{{ $ket }}</p>
    @endif
    @if ($href)
        <a href="{{ $href }}" class="absolute inset-0 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-sun" aria-label="Lihat {{ $label }}"></a>
    @endif
</div>

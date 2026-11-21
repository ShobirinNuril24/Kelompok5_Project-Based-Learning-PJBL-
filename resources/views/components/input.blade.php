@props(['label', 'name', 'type' => 'text', 'value' => null])

<div>
    <label for="{{ $name }}" class="mb-1 block text-sm font-semibold">{{ $label }}</label>
    <input id="{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}"
           {{ $attributes->merge(['class' => 'w-full rounded-lg border border-garis bg-kartu px-3 py-2.5 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-sun']) }}>
    @error($name)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

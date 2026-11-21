@extends('layouts.admin')

@section('judul', $kategori->exists ? 'Ubah Kategori' : 'Tambah Kategori')

@section('konten')
    <form method="POST" action="{{ $kategori->exists ? route('admin.kategori.update', $kategori) : route('admin.kategori.store') }}"
          class="max-w-xl space-y-4 rounded-2xl border border-garis bg-kartu p-5">
        @csrf
        @if ($kategori->exists) @method('PUT') @endif

        <x-input label="Nama kategori" name="nama_kategori" :value="$kategori->nama_kategori" required maxlength="100" />
        <div>
            <label for="deskripsi" class="mb-1 block text-sm font-semibold">Deskripsi (opsional)</label>
            <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="500" class="w-full rounded-lg border border-garis bg-kartu px-3 py-2.5 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-sun">{{ old('deskripsi', $kategori->deskripsi) }}</textarea>
            @error('deskripsi') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="flex gap-3">
            <x-button type="submit">Simpan</x-button>
            <x-button :href="route('admin.kategori.index')" varian="garis">Batal</x-button>
        </div>
    </form>
@endsection

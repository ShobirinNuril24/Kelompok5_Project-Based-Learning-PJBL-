@extends('layouts.admin')

@section('judul', $produk->exists ? 'Ubah Produk' : 'Tambah Produk')

@section('konten')
    @php $in = 'w-full rounded-lg border border-garis bg-kartu px-3 py-2.5 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-sun'; @endphp

    @if ($kategori->isEmpty())
        <div class="max-w-xl rounded-2xl border border-dashed border-garis bg-kartu p-8 text-center">
            <p class="font-semibold">Belum ada kategori</p>
            <p class="text-sm text-teks-lemah">Produk harus punya kategori. Buat kategori dulu.</p>
            <x-button :href="route('admin.kategori.create')" class="mt-4">Tambah kategori</x-button>
        </div>
    @else
        <form method="POST" enctype="multipart/form-data"
              action="{{ $produk->exists ? route('admin.produk.update', $produk) : route('admin.produk.store') }}"
              class="max-w-3xl space-y-4 rounded-2xl border border-garis bg-kartu p-5">
            @csrf
            @if ($produk->exists) @method('PUT') @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><x-input label="Nama produk" name="nama_produk" :value="$produk->nama_produk" required maxlength="150" /></div>
                <div>
                    <label for="category_id" class="mb-1 block text-sm font-semibold">Kategori</label>
                    <select id="category_id" name="category_id" required class="{{ $in }}">
                        <option value="">Pilih kategori</option>
                        @foreach ($kategori as $k)
                            <option value="{{ $k->id }}" @selected(old('category_id', $produk->category_id) == $k->id)>{{ $k->nama_kategori }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
                <x-input label="Satuan (kg, pouch, dus, ...)" name="satuan" :value="$produk->satuan" required maxlength="50" />
                <x-input label="Harga (Rp)" name="harga" type="number" :value="$produk->harga" min="0" max="100000000" step="1" inputmode="numeric" required />
                @php $ada = (bool) old('tersedia', $produk->tersedia ?? true); @endphp
                <fieldset>
                    <legend class="mb-1 block text-sm font-semibold">Ketersediaan</legend>
                    <div class="flex gap-4 pt-2">
                        <label class="flex items-center gap-2"><input type="radio" name="tersedia" value="1" @checked($ada) class="size-4 accent-[#F6C945]"> Ada</label>
                        <label class="flex items-center gap-2"><input type="radio" name="tersedia" value="0" @checked(! $ada) class="size-4 accent-[#F6C945]"> Habis</label>
                    </div>
                </fieldset>
                <div class="sm:col-span-2">
                    <label for="deskripsi" class="mb-1 block text-sm font-semibold">Deskripsi (opsional)</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="2000" class="{{ $in }}">{{ old('deskripsi', $produk->deskripsi) }}</textarea>
                    @error('deskripsi') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-wrap items-start gap-4">
                <x-gambar-produk :produk="$produk" class="size-24 shrink-0 rounded-lg" />
                <div class="min-w-0 flex-1">
                    <label for="gambar" class="mb-1 block text-sm font-semibold">Gambar produk (JPG, PNG, WEBP, maks. 2 MB)</label>
                    <input id="gambar" type="file" name="gambar" accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-sun file:px-4 file:py-2 file:font-bold file:text-ink">
                    @error('gambar') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    @if ($produk->gambar)
                        <label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" name="hapus_gambar" value="1" class="size-4 accent-[#F6C945]"> Hapus gambar saat ini</label>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm font-semibold">
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $produk->is_active)) class="size-4 accent-[#F6C945]"> Aktif (tampil di katalog)</label>
                <input type="hidden" name="is_unggulan" value="0">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_unggulan" value="1" @checked(old('is_unggulan', $produk->is_unggulan)) class="size-4 accent-[#F6C945]"> Produk unggulan (beranda)</label>
            </div>

            <div class="flex gap-3">
                <x-button type="submit">Simpan</x-button>
                <x-button :href="route('admin.produk.index')" varian="garis">Batal</x-button>
            </div>
        </form>
    @endif
@endsection

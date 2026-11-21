@forelse ($daftar as $h)
    <div class="mt-3 flex items-center justify-between gap-3 border-t border-garis pt-3 text-sm first:border-0 first:pt-0">
        <div>
            <p class="font-bold">{{ $h->tanggal_tutup->locale('id')->translatedFormat('l, d F Y') }}</p>
            <p class="text-teks-lemah">{{ $h->alasan ?: 'Tanpa keterangan' }}</p>
        </div>
        <form method="POST" action="{{ route('admin.hari-libur.destroy', $h) }}" onsubmit="return confirm('Hapus tanggal tutup ini?')">
            @csrf @method('DELETE')
            <x-button type="submit" varian="garis" class="!px-3 !py-1.5" aria-label="Hapus tanggal tutup"><x-icon nama="trash" ukuran="size-4" /></x-button>
        </form>
    </div>
@empty
    <p class="mt-3 rounded-xl border border-dashed border-garis p-6 text-center text-sm text-teks-lemah">{{ $kosong }}</p>
@endforelse

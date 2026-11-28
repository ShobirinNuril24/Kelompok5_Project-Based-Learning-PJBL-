@extends('layouts.admin')

@section('judul', 'Ubah item '.$order->nomor_pesanan)

@section('konten')
    @php $kelasInput = 'rounded-lg border border-garis bg-latar px-3 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-sun'; @endphp

    <a href="{{ route('admin.pesanan.show', $order) }}" class="text-sm font-semibold hover:underline">← Detail pesanan</a>
    <h2 class="mt-3 text-2xl font-extrabold">Ubah item &amp; total</h2>

    <div role="note" class="mt-4 rounded-lg bg-amber-100 px-4 py-3 text-sm text-amber-900 dark:bg-amber-900/40 dark:text-amber-200">
        Stok disesuaikan otomatis: menambah jumlah mengambil stok, mengurangi jumlah mengembalikan stok. Ubah hanya setelah pelanggan setuju lewat WhatsApp.
        @if ($order->status_pembayaran === 'Lunas')
            <b>Pembayaran sudah Lunas</b>: selisih total diselesaikan manual dengan pelanggan dan wajib dicatat di bawah.
        @endif
    </div>

    <form method="POST" action="{{ route('admin.pesanan.item.update', $order) }}" class="mt-6 space-y-6">
        @csrf @method('PUT')

        <section class="rounded-2xl border border-garis bg-kartu p-5">
            <h3 class="font-extrabold">Barang dalam pesanan</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-teks-lemah"><tr><th class="py-2 pr-3 font-semibold">Produk</th><th class="px-3 py-2 text-right font-semibold">Harga</th><th class="px-3 py-2 font-semibold">Jumlah</th><th class="py-2 pl-3 text-right font-semibold">Stok tersedia</th></tr></thead>
                    <tbody>
                        @foreach ($order->items as $i)
                            <tr class="border-t border-garis">
                                <td class="py-2 pr-3">{{ $i->nama_produk_snapshot }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">@rupiah($i->harga_snapshot)</td>
                                <td class="px-3 py-2">
                                    <label class="sr-only" for="kuantitas-{{ $i->id }}">Jumlah {{ $i->nama_produk_snapshot }}</label>
                                    <input id="kuantitas-{{ $i->id }}" type="number" min="0" max="999" name="kuantitas[{{ $i->id }}]" value="{{ old('kuantitas.'.$i->id, $i->kuantitas) }}" class="{{ $kelasInput }} w-24">
                                </td>
                                <td class="py-2 pl-3 text-right tabular-nums text-teks-lemah">{{ $stok[$i->product_id] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-2 text-xs text-teks-lemah">Isi 0 untuk menghapus barang dari pesanan. Harga barang tetap memakai harga saat checkout.</p>
        </section>

        <section class="rounded-2xl border border-garis bg-kartu p-5">
            <h3 class="font-extrabold">Tambah barang pengganti</h3>
            <div class="mt-3 grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label for="tambah_product_id" class="mb-1 block text-sm font-semibold">Produk</label>
                    <select id="tambah_product_id" name="tambah_product_id" class="{{ $kelasInput }} w-full">
                        <option value="">— tidak menambah —</option>
                        @foreach ($produkTambah as $p)
                            <option value="{{ $p->id }}" @selected(old('tambah_product_id') == $p->id)>{{ $p->nama_produk }} — Rp {{ number_format($p->harga, 0, ',', '.') }} (stok {{ $p->stok }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="tambah_kuantitas" class="mb-1 block text-sm font-semibold">Jumlah</label>
                    <input id="tambah_kuantitas" type="number" min="1" max="999" name="tambah_kuantitas" value="{{ old('tambah_kuantitas', 1) }}" class="{{ $kelasInput }} w-full">
                </div>
            </div>
        </section>

        @if ($order->metode_pengambilan === 'Diantarkan')
            <section class="rounded-2xl border border-garis bg-kartu p-5">
                <h3 class="font-extrabold">Ongkir</h3>
                <label for="ongkir" class="mt-3 mb-1 block text-sm font-semibold">Ongkos kirim (Rp)</label>
                <input id="ongkir" type="number" min="0" name="ongkir" value="{{ old('ongkir', $order->ongkir) }}" class="{{ $kelasInput }} w-48">
                <p class="mt-1 text-xs text-teks-lemah">Tentukan setelah memeriksa jarak dan kesanggupan pengantaran, lalu sepakati dengan pelanggan lewat WhatsApp.
                    Ongkir otomatis Rp 0 bila subtotal barang mulai Rp {{ number_format(\App\Models\Order::gratisOngkirMinimal(), 0, ',', '.') }}.</p>
            </section>
        @endif

        @if ($order->status_pembayaran === 'Lunas')
            <section class="rounded-2xl border border-garis bg-kartu p-5">
                <label for="catatan_selisih" class="font-extrabold">Penyelesaian selisih pembayaran (wajib)</label>
                <textarea id="catatan_selisih" name="catatan_selisih" rows="2" maxlength="500" required class="{{ $kelasInput }} mt-3 w-full"
                    placeholder="Contoh: selisih Rp 9.000 dikembalikan tunai saat pengambilan">{{ old('catatan_selisih') }}</textarea>
                <p class="mt-1 text-xs text-teks-lemah">Total lama, total baru, dan selisihnya dicatat otomatis bersama catatan ini.</p>
            </section>
        @endif

        <label class="flex items-start gap-3 rounded-2xl border border-garis bg-kartu p-5 text-sm">
            <input type="checkbox" name="disetujui" value="1" class="mt-0.5 size-4 accent-amber-500" @checked(old('disetujui'))>
            <span><b>Pelanggan sudah menyetujui perubahan ini lewat WhatsApp.</b><br><span class="text-teks-lemah">Waktu dan ringkasan perubahan dicatat otomatis di catatan admin.</span></span>
        </label>

        <div class="flex flex-wrap gap-3">
            <x-button type="submit">Simpan perubahan</x-button>
            <x-button :href="route('admin.pesanan.show', $order)" varian="garis">Batal</x-button>
        </div>
    </form>
@endsection

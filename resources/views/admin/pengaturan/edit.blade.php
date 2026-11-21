@extends('layouts.admin')

@section('judul', 'Pengaturan Toko')

@section('konten')
    @php
        $in = 'w-full rounded-lg border border-garis bg-kartu px-3 py-2.5 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-sun';
        $kartu = 'rounded-2xl border border-garis bg-kartu p-5';
        $galat = 'mt-1 text-sm text-red-600 dark:text-red-400';
        $jadwal = $toko->jadwal_operasional ?? [];
    @endphp

    <form method="POST" action="{{ route('admin.pengaturan.update') }}" enctype="multipart/form-data" class="max-w-4xl space-y-6">
        @csrf @method('PUT')

        {{-- Status toko --}}
        <section class="{{ $kartu }}">
            <h2 class="font-extrabold">Status toko</h2>
            <input type="hidden" name="toko_buka" value="0">
            <label class="mt-3 flex items-start gap-3 text-sm">
                <input type="checkbox" name="toko_buka" value="1" @checked(old('toko_buka', $toko->toko_buka)) class="mt-0.5 size-4 accent-[#F6C945]">
                <span><b>Toko menerima pesanan online</b><br><span class="text-teks-lemah">Matikan untuk menutup pemesanan seketika (di luar jadwal). Jika dimatikan, checkout ditolak walau jam operasional sedang buka.</span></span>
            </label>
        </section>

        {{-- Identitas --}}
        <section class="{{ $kartu }}">
            <h2 class="font-extrabold">Informasi toko</h2>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <x-input label="Nama toko" name="nama_toko" :value="$toko->nama_toko" required maxlength="100" />
                <x-input label="Nomor WhatsApp toko" name="no_whatsapp" type="tel" inputmode="tel" :value="$toko->no_whatsapp" placeholder="081234567890" required />
                <div class="sm:col-span-2">
                    <label for="alamat_toko" class="mb-1 block text-sm font-semibold">Alamat toko</label>
                    <textarea id="alamat_toko" name="alamat_toko" rows="2" maxlength="500" class="{{ $in }}">{{ old('alamat_toko', $toko->alamat_toko) }}</textarea>
                    @error('alamat_toko') <p class="{{ $galat }}">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Jadwal --}}
        <section class="{{ $kartu }}">
            <h2 class="font-extrabold">Jam operasional</h2>
            <p class="mt-1 text-sm text-teks-lemah">Jam buka termasuk, jam tutup tidak termasuk (ORD §2.2). Zona waktu WIB.</p>
            <div class="mt-3 space-y-2">
                @foreach ($hari as $kunci => $nama)
                    <div class="grid grid-cols-[5rem_1fr_1fr] items-center gap-3 sm:grid-cols-[7rem_auto_1fr_1fr]">
                        <span class="text-sm font-bold">{{ $nama }}</span>
                        <div class="order-last col-span-3 sm:order-none sm:col-span-1">
                            <input type="hidden" name="jadwal[{{ $kunci }}][is_open]" value="0">
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="jadwal[{{ $kunci }}][is_open]" value="1" @checked(old("jadwal.$kunci.is_open", $jadwal[$kunci]['is_open'] ?? true)) class="size-4 accent-[#F6C945]"> Buka</label>
                        </div>
                        <div>
                            <label for="open_{{ $kunci }}" class="sr-only">Jam buka {{ $nama }}</label>
                            <input id="open_{{ $kunci }}" type="time" name="jadwal[{{ $kunci }}][open]" value="{{ old("jadwal.$kunci.open", $jadwal[$kunci]['open'] ?? '05:30') }}" required class="{{ $in }}">
                        </div>
                        <div>
                            <label for="close_{{ $kunci }}" class="sr-only">Jam tutup {{ $nama }}</label>
                            <input id="close_{{ $kunci }}" type="time" name="jadwal[{{ $kunci }}][close]" value="{{ old("jadwal.$kunci.close", $jadwal[$kunci]['close'] ?? '11:30') }}" required class="{{ $in }}">
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-sm text-teks-lemah">Tanggal tutup khusus (Idulfitri, dll.) diatur di menu <a href="{{ route('admin.hari-libur.index') }}" class="font-bold underline">Hari Libur</a>.</p>
        </section>

        {{-- Pembayaran --}}
        <section class="{{ $kartu }}">
            <h2 class="font-extrabold">Pembayaran Transfer/QRIS</h2>
            <p class="mt-1 text-sm text-teks-lemah">Ditampilkan kepada pelanggan di halaman pesanan yang menunggu pembayaran. Verifikasi tetap manual oleh admin.</p>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <x-input label="Nama bank" name="nama_bank" :value="$toko->nama_bank" placeholder="BCA" maxlength="100" />
                <x-input label="Nomor rekening" name="no_rekening" inputmode="numeric" :value="$toko->no_rekening" maxlength="50" />
                <div class="sm:col-span-2"><x-input label="Atas nama" name="atas_nama_rekening" :value="$toko->atas_nama_rekening" maxlength="255" /></div>
                <x-input label="Batas waktu pembayaran (menit)" name="batas_pembayaran_menit" type="number" :value="$toko->batas_pembayaran_menit" min="5" max="1440" required />
            </div>
            <div class="mt-4 flex flex-wrap items-start gap-4">
                <div class="grid size-28 shrink-0 place-items-center overflow-hidden rounded-lg border border-garis bg-white">
                    @if ($toko->gambar_qris)
                        <img src="{{ asset('storage/'.$toko->gambar_qris) }}" alt="Kode QRIS saat ini" class="size-full object-contain p-1">
                    @else
                        <span class="px-2 text-center text-xs text-teks-lemah">Belum ada QRIS</span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <label for="gambar_qris" class="mb-1 block text-sm font-semibold">Gambar QRIS (JPG, PNG, WEBP, maks. 2 MB)</label>
                    <input id="gambar_qris" type="file" name="gambar_qris" accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-sun file:px-4 file:py-2 file:font-bold file:text-ink">
                    @error('gambar_qris') <p class="{{ $galat }}">{{ $message }}</p> @enderror
                    @if ($toko->gambar_qris)
                        <label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" name="hapus_qris" value="1" class="size-4 accent-[#F6C945]"> Hapus QRIS saat ini</label>
                    @endif
                </div>
            </div>
        </section>

        {{-- Operasional --}}
        <section class="{{ $kartu }}">
            <h2 class="font-extrabold">Aturan pesanan dan pengantaran</h2>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <x-input label="Minimum pesanan (Rp, 0 = tanpa minimum)" name="minimum_pesanan" type="number" :value="$toko->minimum_pesanan" min="0" required />
                <x-input label="Ambang stok menipis" name="ambang_stok_menipis" type="number" :value="$toko->ambang_stok_menipis" min="0" max="1000" required />
                <x-input label="Radius pengantaran (km, keterangan)" name="radius_antar_km" type="number" step="0.1" min="0" max="99.9" :value="$toko->radius_antar_km" />
                <div class="sm:col-span-2">
                    <label for="catatan_pengantaran" class="mb-1 block text-sm font-semibold">Catatan pengantaran (tampil di halaman Tentang Kami)</label>
                    <textarea id="catatan_pengantaran" name="catatan_pengantaran" rows="2" maxlength="1000" class="{{ $in }}">{{ old('catatan_pengantaran', $toko->catatan_pengantaran) }}</textarea>
                    @error('catatan_pengantaran') <p class="{{ $galat }}">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="mt-3 text-sm text-teks-lemah">Radius hanya keterangan. Sesuai ORD, pesanan antar selalu menunggu konfirmasi admin lewat WhatsApp, termasuk bila alamat di luar jangkauan.</p>
        </section>

        <div class="sticky bottom-0 -mx-4 border-t border-garis bg-latar/95 px-4 py-3 backdrop-blur lg:-mx-8 lg:px-8">
            <x-button type="submit">Simpan pengaturan</x-button>
        </div>
    </form>
@endsection

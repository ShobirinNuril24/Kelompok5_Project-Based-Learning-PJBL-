@extends('layouts.admin')

@section('judul', 'Hari Libur')

@section('konten')
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-2xl border border-garis bg-kartu p-5 lg:col-span-1">
            <h2 class="font-extrabold">Tambah tanggal tutup</h2>
            <p class="mt-1 text-sm text-teks-lemah">Pada tanggal ini checkout ditolak walau jam operasional biasanya buka. Pesanan yang sudah masuk tidak berubah.</p>
            <form method="POST" action="{{ route('admin.hari-libur.store') }}" class="mt-4 space-y-4">
                @csrf
                <x-input label="Tanggal" name="tanggal_tutup" type="date" :min="today()->toDateString()" required />
                <x-input label="Alasan (opsional)" name="alasan" placeholder="Contoh: Idulfitri" maxlength="255" />
                <x-button type="submit"><x-icon nama="plus" ukuran="size-4" /> Tambah</x-button>
            </form>
        </section>

        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-2xl border border-garis bg-kartu p-5">
                <h2 class="font-extrabold">Mendatang</h2>
                @include('admin.hari-libur.daftar', ['daftar' => $mendatang, 'kosong' => 'Belum ada tanggal tutup yang dijadwalkan.'])
            </section>
            <section class="rounded-2xl border border-garis bg-kartu p-5">
                <h2 class="font-extrabold">Sudah lewat <span class="text-sm font-normal text-teks-lemah">(10 terakhir)</span></h2>
                @include('admin.hari-libur.daftar', ['daftar' => $lalu, 'kosong' => 'Belum ada riwayat.'])
            </section>
        </div>
    </div>
@endsection

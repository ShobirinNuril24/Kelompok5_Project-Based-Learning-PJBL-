@extends('layouts.pelanggan')

@section('judul', 'Cek Status Pesanan')

@section('konten')
    <x-container class="py-8">
        <div class="mx-auto max-w-md">
            <h1 class="text-2xl font-extrabold">Cek Status Pesanan</h1>
            <p class="mt-2 text-sm text-teks-lemah">Masukkan nomor pesanan dan nomor WhatsApp yang Anda pakai saat checkout.</p>

            <form method="POST" action="{{ route('status.temukan') }}" class="mt-6 space-y-4 rounded-2xl border border-garis bg-kartu p-5">
                @csrf
                <x-input label="Nomor pesanan" name="nomor_pesanan" placeholder="SBK-20261123-ABCD" required />
                <x-input label="Nomor WhatsApp" name="no_whatsapp" type="tel" inputmode="tel" placeholder="081234567890" required />
                <x-button type="submit" class="w-full">Cek Status</x-button>
            </form>
        </div>
    </x-container>
@endsection

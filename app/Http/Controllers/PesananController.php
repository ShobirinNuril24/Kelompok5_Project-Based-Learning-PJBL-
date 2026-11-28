<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PesananController extends Controller
{
    // Form Cek Status Pesanan
    public function cari(): View
    {
        return view('pelanggan.pesanan.cari');
    }

    // Nomor pesanan saja mudah ditebak, jadi wajib disertai nomor WhatsApp yang dipakai saat checkout.
    public function temukan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nomor_pesanan' => ['required', 'string', 'max:50'],
            'no_whatsapp' => ['required', 'string', 'max:30'],
        ]);

        $order = Order::where('nomor_pesanan', strtoupper(trim($data['nomor_pesanan'])))
            ->where('no_whatsapp', Order::normalisasiWa($data['no_whatsapp']))
            ->first();

        if (! $order) {
            return back()->withErrors(['nomor_pesanan' => 'Pesanan tidak ditemukan. Periksa nomor pesanan dan nomor WhatsApp.'])->onlyInput('nomor_pesanan');
        }

        return redirect()->route('pesanan.show', $order->public_token);
    }

    // Struk digital sekaligus halaman status. Diakses lewat token acak, bukan ID berurutan.
    public function show(Order $order): View
    {
        Order::prosesKedaluwarsa(); // pastikan status "Kedaluwarsa" sudah terbarui sebelum ditampilkan

        return view('pelanggan.pesanan.show', ['order' => $order->refresh()->load('items')]);
    }

    // Pelanggan hanya boleh membatalkan sebelum pesanan diproses DAN sebelum ada pembayaran yang dicatat
    // (FR-027). Pesanan yang sudah dibayar dibatalkan admin lewat WhatsApp agar pengembalian dana tertangani.
    public function batal(Order $order): RedirectResponse
    {
        $berhasil = $order->batalkan('pelanggan', ['Menunggu Konfirmasi', 'Menunggu Pembayaran'], ['Belum Dibayar']);

        return redirect()->route('pesanan.show', $order->public_token)->with(
            $berhasil ? 'ok' : 'err',
            $berhasil ? 'Pesanan dibatalkan.' : 'Pesanan ini tidak bisa dibatalkan dari sini. Silakan hubungi admin lewat WhatsApp.'
        );
    }
}

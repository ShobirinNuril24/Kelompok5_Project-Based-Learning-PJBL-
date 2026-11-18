<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Keranjang disimpan di session sebagai [product_id => jumlah].
 * Session BUKAN sumber kebenaran: harga dan stok selalu dibaca ulang dari database.
 */
class Keranjang
{
    private const KUNCI = 'keranjang';

    public static function isi(): array
    {
        return session(self::KUNCI, []);
    }

    public static function jumlah(): int
    {
        return array_sum(self::isi());
    }

    // Atur jumlah (dibatasi stok). Jumlah < 1 berarti hapus.
    public static function atur(Product $produk, int $jumlah): void
    {
        $isi = self::isi();
        $jumlah = min($jumlah, $produk->stok);

        if ($jumlah < 1) {
            unset($isi[$produk->id]);
        } else {
            $isi[$produk->id] = $jumlah;
        }
        session([self::KUNCI => $isi]);
    }

    public static function tambah(Product $produk, int $jumlah = 1): void
    {
        self::atur($produk, (self::isi()[$produk->id] ?? 0) + $jumlah);
    }

    public static function hapus(Product $produk): void
    {
        $isi = self::isi();
        unset($isi[$produk->id]);
        session([self::KUNCI => $isi]);
    }

    public static function kosongkan(): void
    {
        session()->forget(self::KUNCI);
    }

    // Baris keranjang dengan harga dan stok TERBARU. 'masalah' berisi pesan jika baris tidak bisa di-checkout.
    public static function baris(): Collection
    {
        $isi = self::isi();
        $produk = Product::with('category')->whereIn('id', array_keys($isi))->get()->keyBy('id');

        return collect($isi)->map(function ($jumlah, $id) use ($produk) {
            $p = $produk->get($id);
            if (! $p) {
                return null; // produk sudah dihapus admin
            }

            $masalah = match (true) {
                ! $p->is_active => 'Produk ini tidak lagi dijual.',
                $p->stok < 1 => 'Stok habis.',
                $p->stok < $jumlah => "Stok tersisa {$p->stok}. Kurangi jumlahnya.",
                default => null,
            };

            return ['produk' => $p, 'jumlah' => $jumlah, 'subtotal' => $p->harga * $jumlah, 'masalah' => $masalah];
        })->filter()->values();
    }
}

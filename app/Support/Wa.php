<?php

namespace App\Support;

class Wa
{
    // Nomor 08xx dinormalisasi ke format internasional 628xx (ORD §8).
    public static function url(string $nomor, string $pesan = ''): string
    {
        $nomor = preg_replace('/\D/', '', $nomor);
        $nomor = str_starts_with($nomor, '0') ? '62'.substr($nomor, 1) : $nomor;

        return 'https://wa.me/'.$nomor.($pesan ? '?text='.rawurlencode($pesan) : '');
    }
}

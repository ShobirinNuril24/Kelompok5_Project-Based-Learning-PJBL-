<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'nama_toko', 'alamat_toko', 'no_whatsapp', 'jadwal_operasional', 'logo',
        'nama_bank', 'no_rekening', 'atas_nama_rekening', 'gambar_qris',
        'ambang_stok_menipis', 'minimum_pesanan', 'radius_antar_km', 'catatan_pengantaran',
    ];

    protected $casts = ['jadwal_operasional' => 'array'];

    // Pengaturan toko hanya satu baris: StoreSetting::ambil()
    public static function ambil(): self
    {
        return self::firstOrFail();
    }

    // Tautan WhatsApp toko. Nomor 08xx dinormalisasi ke format internasional 628xx (ORD §8).
    public function waUrl(string $pesan = ''): string
    {
        $nomor = preg_replace('/\D/', '', $this->no_whatsapp);
        $nomor = str_starts_with($nomor, '0') ? '62'.substr($nomor, 1) : $nomor;

        return 'https://wa.me/'.$nomor.($pesan ? '?text='.rawurlencode($pesan) : '');
    }
}

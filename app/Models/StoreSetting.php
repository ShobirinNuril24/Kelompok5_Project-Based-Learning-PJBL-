<?php

namespace App\Models;

use Carbon\CarbonInterface;
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

    /**
     * Validasi jadwal sesuai ORD §2.4. Mengembalikan pesan alasan jika toko TUTUP, atau null jika buka.
     * Urutan: tanggal tutup khusus -> hari punya jadwal -> jam buka (awal termasuk, akhir tidak).
     */
    public function pesanTutup(?CarbonInterface $waktu = null): ?string
    {
        $waktu ??= now('Asia/Jakarta');
        $standar = 'Toko sedang tutup. Pemesanan online tersedia kembali pada jam operasional berikutnya.';

        $libur = StoreClosedDate::whereDate('tanggal_tutup', $waktu->toDateString())->first();
        if ($libur) {
            return 'Toko tutup hari ini'.($libur->alasan ? " ({$libur->alasan})" : '').'. Pemesanan online tersedia kembali pada hari buka berikutnya.';
        }

        $jadwal = $this->jadwal_operasional[strtolower($waktu->englishDayOfWeek)] ?? null;
        if (! $jadwal || ! $jadwal['is_open']) {
            return $standar;
        }

        $jam = $waktu->format('H:i'); // "05:30" dibandingkan sebagai teks: aman karena selalu 5 karakter
        return ($jam >= $jadwal['open'] && $jam < $jadwal['close']) ? null : $standar;
    }
}

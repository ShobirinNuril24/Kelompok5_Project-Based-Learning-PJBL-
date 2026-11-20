<?php

namespace App\Models;

use App\Support\Wa;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'nama_toko', 'alamat_toko', 'no_whatsapp', 'toko_buka', 'jadwal_operasional', 'logo',
        'nama_bank', 'no_rekening', 'atas_nama_rekening', 'gambar_qris',
        'ambang_stok_menipis', 'minimum_pesanan', 'batas_pembayaran_menit', 'radius_antar_km', 'catatan_pengantaran',
    ];

    protected $casts = ['jadwal_operasional' => 'array', 'toko_buka' => 'boolean'];

    // Pengaturan toko hanya satu baris: StoreSetting::ambil()
    public static function ambil(): self
    {
        return self::firstOrFail();
    }

    public function waUrl(string $pesan = ''): string
    {
        return Wa::url($this->no_whatsapp, $pesan);
    }

    /**
     * Validasi jadwal sesuai ORD §2.4. Mengembalikan pesan alasan jika toko TUTUP, atau null jika buka.
     * Urutan: saklar manual -> tanggal tutup khusus -> hari punya jadwal -> jam buka (awal termasuk, akhir tidak).
     */
    public function pesanTutup(?CarbonInterface $waktu = null): ?string
    {
        $waktu ??= now('Asia/Jakarta');
        $standar = 'Toko sedang tutup. Pemesanan online tersedia kembali pada jam operasional berikutnya.';

        if (! $this->toko_buka) {
            return $standar;
        }

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

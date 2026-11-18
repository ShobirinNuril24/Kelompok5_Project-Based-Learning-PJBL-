<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Order extends Model
{
    // Daftar nilai ini harus SAMA dengan ENUM di migration orders.
    public const METODE_PENGAMBILAN = ['Ambil di Toko', 'Diantarkan'];
    public const METODE_PEMBAYARAN = ['Tunai', 'Transfer/QRIS'];
    public const STATUS_PESANAN = [
        'Menunggu Konfirmasi', 'Sedang Disiapkan', 'Siap Diambil',
        'Sedang Diantarkan', 'Selesai', 'Dibatalkan',
    ];
    public const STATUS_PEMBAYARAN = ['Belum Dibayar', 'Menunggu Verifikasi', 'Lunas'];

    // public_token sengaja TIDAK ada di sini: dibuat otomatis, tidak boleh diisi dari form.
    protected $fillable = [
        'nomor_pesanan', 'nama_pelanggan', 'no_whatsapp', 'metode_pengambilan',
        'alamat_pengantaran', 'metode_pembayaran', 'ongkir', 'total_harga',
        'status_pesanan', 'status_pembayaran', 'catatan_admin',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->public_token ??= Str::random(40);
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // "+62 812-3456-7890" atau "6281234567890" -> "081234567890"
    public static function normalisasiWa(string $nomor): string
    {
        $nomor = preg_replace('/\D/', '', $nomor);

        return str_starts_with($nomor, '62') ? '0'.substr($nomor, 2) : $nomor;
    }

    public function warnaStatus(): string
    {
        return match ($this->status_pesanan) {
            'Selesai' => 'sukses',
            'Dibatalkan' => 'bahaya',
            'Menunggu Konfirmasi' => 'peringatan',
            default => 'info',
        };
    }

    public function warnaPembayaran(): string
    {
        return match ($this->status_pembayaran) {
            'Lunas' => 'sukses',
            'Menunggu Verifikasi' => 'info',
            default => 'peringatan',
        };
    }

    /**
     * Batalkan pesanan dan kembalikan stok SATU KALI. Dipakai pelanggan dan admin.
     * Baris pesanan dikunci, sehingga request ganda tidak mengembalikan stok dua kali.
     * $statusBoleh: jika diisi, pembatalan hanya sah pada status tersebut (dicek di dalam kunci).
     * Mengembalikan false jika tidak ada yang diubah.
     */
    public function batalkan(string $oleh, ?array $statusBoleh = null): bool
    {
        return DB::transaction(function () use ($oleh, $statusBoleh) {
            $order = self::whereKey($this->id)->lockForUpdate()->first();

            if ($order->status_pesanan === 'Dibatalkan') {
                return false;
            }
            if ($statusBoleh !== null && ! in_array($order->status_pesanan, $statusBoleh, true)) {
                return false;
            }

            foreach ($order->items as $item) {
                Product::whereKey($item->product_id)->increment('stok', $item->kuantitas);
            }

            $catatan = 'Dibatalkan oleh '.$oleh.' pada '.now('Asia/Jakarta')->format('d-m-Y H:i').' WIB.';
            $order->update([
                'status_pesanan' => 'Dibatalkan',
                'catatan_admin' => trim(($order->catatan_admin ? $order->catatan_admin."\n" : '').$catatan),
            ]);

            return true;
        });
    }
}

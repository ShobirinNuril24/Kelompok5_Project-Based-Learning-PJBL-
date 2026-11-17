<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'nama_produk', 'slug', 'deskripsi', 'satuan',
        'harga', 'stok', 'tersedia', 'gambar', 'is_active', 'is_unggulan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_unggulan' => 'boolean',
        'tersedia' => 'boolean',
    ];

    // Batas jumlah per barang dalam satu pesanan (stok angka tidak lagi dipakai, D-3).
    public const MAKS_JUMLAH = 99;

    // Bisa dimasukkan ke keranjang / pesanan: aktif dijual dan ditandai Ada oleh admin.
    public function bisaDipesan(): bool
    {
        return $this->is_active && $this->tersedia;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Produk yang tampil di katalog pelanggan: Product::aktif()->get()
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

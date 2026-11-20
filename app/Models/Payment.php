<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const STATUS = ['Menunggu', 'Berhasil', 'Gagal', 'Kedaluwarsa', 'Dibatalkan'];

    protected $fillable = ['order_id', 'provider', 'referensi', 'jumlah', 'status', 'dibayar_at', 'payload'];

    protected $casts = ['dibayar_at' => 'datetime', 'payload' => 'array'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

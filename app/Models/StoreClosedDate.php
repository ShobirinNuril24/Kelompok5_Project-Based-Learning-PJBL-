<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreClosedDate extends Model
{
    protected $fillable = ['tanggal_tutup', 'alasan'];

    protected $casts = ['tanggal_tutup' => 'date'];
}

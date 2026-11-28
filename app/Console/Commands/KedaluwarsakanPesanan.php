<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class KedaluwarsakanPesanan extends Command
{
    protected $signature = 'pesanan:kedaluwarsa';

    protected $description = 'Kedaluwarsakan pesanan yang lewat batas waktu pembayaran';

    public function handle(): int
    {
        $this->info(Order::prosesKedaluwarsa().' pesanan dikedaluwarsakan.');

        return self::SUCCESS;
    }
}

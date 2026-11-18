<?php

namespace App\Providers;

use App\Models\StoreSetting;
use App\Support\Keranjang;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Pengaturan toko dibaca sekali per request (bukan saat boot, supaya artisan migrate tetap aman).
        $this->app->scoped('toko', fn () => StoreSetting::ambil());
    }

    public function boot(): void
    {
        // Variabel $toko dan $jumlahKeranjang tersedia di layout dan semua view pelanggan.
        View::composer(['layouts.pelanggan', 'pelanggan.*'], fn ($view) => $view
            ->with('toko', app('toko'))
            ->with('jumlahKeranjang', Keranjang::jumlah()));

        // Format rupiah yang konsisten: @rupiah($angka) -> Rp 78.000
        Blade::directive('rupiah', fn (string $angka) => "<?php echo 'Rp '.number_format((int) ({$angka}), 0, ',', '.'); ?>");
    }
}

<?php

namespace App\Providers;

use App\Models\StoreSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Data toko untuk header dan footer. Dibaca saat halaman dirender (bukan saat boot),
        // jadi perintah artisan seperti migrate tetap aman di database kosong.
        View::composer('layouts.pelanggan', fn ($view) => $view->with('toko', StoreSetting::ambil()));
    }
}

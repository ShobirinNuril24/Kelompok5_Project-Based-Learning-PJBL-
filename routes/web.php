<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\ProdukController;

Route::get('/', BerandaController::class)->name('beranda');
Route::get('/produk', [ProdukController::class, 'index'])->name('produk.index');
Route::get('/produk/{produk:slug}', [ProdukController::class, 'show'])->name('produk.show');

Route::get('/keranjang', [KeranjangController::class, 'index'])->name('keranjang.index');
Route::post('/keranjang', [KeranjangController::class, 'store'])->name('keranjang.store');
Route::patch('/keranjang/{produk}', [KeranjangController::class, 'update'])->name('keranjang.update');
Route::delete('/keranjang/{produk}', [KeranjangController::class, 'destroy'])->name('keranjang.destroy');

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');

Route::get('/status-pesanan', [PesananController::class, 'cari'])->name('status.cek');
Route::post('/status-pesanan', [PesananController::class, 'temukan'])->middleware('throttle:10,1')->name('status.temukan');
Route::get('/pesanan/{order:public_token}', [PesananController::class, 'show'])->name('pesanan.show');
Route::post('/pesanan/{order:public_token}/batal', [PesananController::class, 'batal'])->name('pesanan.batal');

Route::view('/tentang', 'pelanggan.tentang')->name('tentang');

Route::middleware('guest')->prefix('admin')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::view('/', 'admin.dashboard')->name('admin.dashboard');
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});
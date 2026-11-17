<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LoginController;

Route::view('/', 'beranda')->name('beranda');
Route::view('/produk', 'kosong', ['judul' => 'Produk'])->name('produk.index');
Route::view('/keranjang', 'kosong', ['judul' => 'Keranjang'])->name('keranjang.index');
Route::view('/status-pesanan', 'kosong', ['judul' => 'Cek Status Pesanan'])->name('status.cek');
Route::view('/tentang', 'kosong', ['judul' => 'Tentang Kami'])->name('tentang');

Route::middleware('guest')->prefix('admin')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::view('/', 'admin.dashboard')->name('admin.dashboard');
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});
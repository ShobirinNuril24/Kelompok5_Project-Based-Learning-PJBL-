<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('toko.admin.email');
        $password = config('toko.admin.password');

        if (! $email || ! $password) {
            throw new \RuntimeException('Isi ADMIN_EMAIL dan ADMIN_PASSWORD di file .env terlebih dahulu.');
        }

        // Hanya satu akun admin: pakai user pertama jika sudah ada, kalau belum buat baru.
        // Password di-hash otomatis oleh cast 'hashed' pada model User.
        $admin = User::first() ?? new User();
        $admin->fill([
            'name' => config('toko.admin.nama'),
            'email' => $email,
            'password' => $password,
        ])->save();
    }
}

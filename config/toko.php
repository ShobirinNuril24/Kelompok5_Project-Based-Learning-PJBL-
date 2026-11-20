<?php

// Kredensial admin dibaca dari file .env supaya tidak tersimpan di source code / GitHub.
return [
    'admin' => [
        'nama' => env('ADMIN_NAME', 'Admin Toko 10'),
        'username' => env('ADMIN_USERNAME'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];

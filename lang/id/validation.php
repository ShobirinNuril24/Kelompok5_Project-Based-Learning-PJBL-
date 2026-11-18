<?php

// Pesan validasi bahasa Indonesia (Laravel tidak menyediakan folder lang secara bawaan).
return [
    'required' => ':attribute wajib diisi.',
    'required_if' => ':attribute wajib diisi.',
    'email' => ':attribute harus berupa email yang valid.',
    'string' => ':attribute harus berupa teks.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'numeric' => ':attribute harus berupa angka.',
    'boolean' => ':attribute harus benar atau salah.',
    'date' => ':attribute bukan tanggal yang valid.',
    'in' => ':attribute yang dipilih tidak valid.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'unique' => ':attribute sudah dipakai.',
    'regex' => ':attribute formatnya tidak valid.',
    'image' => ':attribute harus berupa gambar.',
    'mimes' => ':attribute harus berformat: :values.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'prohibited' => ':attribute tidak boleh diisi.',
    'max' => [
        'string' => ':attribute maksimal :max karakter.',
        'numeric' => ':attribute maksimal :max.',
        'file' => ':attribute maksimal :max KB.',
    ],
    'min' => [
        'string' => ':attribute minimal :min karakter.',
        'numeric' => ':attribute minimal :min.',
    ],
    'custom' => [],
    'attributes' => [
        'nama_pelanggan' => 'Nama',
        'no_whatsapp' => 'Nomor WhatsApp',
        'metode_pengambilan' => 'Metode pengambilan',
        'alamat_pengantaran' => 'Alamat pengantaran',
        'metode_pembayaran' => 'Metode pembayaran',
        'nomor_pesanan' => 'Nomor pesanan',
        'produk_id' => 'Produk',
        'jumlah' => 'Jumlah',
        'email' => 'Email',
        'password' => 'Password',
    ],
];

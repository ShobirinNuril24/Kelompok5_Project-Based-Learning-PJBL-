<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;

abstract class Controller
{
    // Slug unik untuk model yang punya kolom "slug": "Gula Pasir" -> gula-pasir, gula-pasir-2, ...
    protected function slugUnik(string $model, string $nama): string
    {
        $dasar = Str::slug($nama) ?: 'item';
        $slug = $dasar;

        for ($i = 2; $model::where('slug', $slug)->exists(); $i++) {
            $slug = "{$dasar}-{$i}";
        }

        return $slug;
    }
}

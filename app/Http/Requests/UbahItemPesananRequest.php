<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UbahItemPesananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dibatasi middleware auth pada route
    }

    public function rules(): array
    {
        return [
            'kuantitas' => ['required', 'array'],
            'kuantitas.*' => ['integer', 'min:0', 'max:999'],
            'tambah_product_id' => ['nullable', 'exists:products,id'],
            'tambah_kuantitas' => ['nullable', 'required_with:tambah_product_id', 'integer', 'min:1', 'max:999'],
            'ongkir' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'disetujui' => ['accepted'], // perubahan wajib disetujui pelanggan secara eksplisit
        ];
    }

    public function messages(): array
    {
        return ['disetujui.accepted' => 'Centang dulu bahwa pelanggan sudah menyetujui perubahan lewat WhatsApp.'];
    }
}

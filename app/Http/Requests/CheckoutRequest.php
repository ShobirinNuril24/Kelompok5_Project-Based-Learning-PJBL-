<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Nomor WhatsApp dirapikan dulu ke format 08xx sebelum divalidasi.
    protected function prepareForValidation(): void
    {
        $this->merge(['no_whatsapp' => Order::normalisasiWa((string) $this->no_whatsapp)]);
    }

    public function rules(): array
    {
        return [
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'no_whatsapp' => ['required', 'regex:/^08\d{8,12}$/'],
            'metode_pengambilan' => ['required', Rule::in(Order::METODE_PENGAMBILAN)],
            'alamat_pengantaran' => ['required_if:metode_pengambilan,Diantarkan', 'nullable', 'string', 'max:500'],
            'metode_pembayaran' => ['required', Rule::in(Order::METODE_PEMBAYARAN)],
        ];
    }

    public function messages(): array
    {
        return [
            'no_whatsapp.regex' => 'Nomor WhatsApp tidak valid. Contoh: 081234567890.',
            'alamat_pengantaran.required_if' => 'Alamat pengantaran wajib diisi jika memilih Diantarkan.',
        ];
    }
}

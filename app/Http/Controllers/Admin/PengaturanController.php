<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    // Kunci hari harus sama dengan yang dibaca StoreSetting::pesanTutup() (englishDayOfWeek huruf kecil).
    private const HARI = [
        'monday' => 'Senin', 'tuesday' => 'Selasa', 'wednesday' => 'Rabu', 'thursday' => 'Kamis',
        'friday' => 'Jumat', 'saturday' => 'Sabtu', 'sunday' => 'Minggu',
    ];

    public function edit(): View
    {
        return view('admin.pengaturan.edit', ['toko' => StoreSetting::ambil(), 'hari' => self::HARI]);
    }

    public function update(Request $request): RedirectResponse
    {
        $aturan = [
            'nama_toko' => ['required', 'string', 'max:100'],
            'alamat_toko' => ['nullable', 'string', 'max:500'],
            'no_whatsapp' => ['required', 'regex:/^\+?[0-9\s\-]{8,20}$/'],
            'nama_bank' => ['nullable', 'string', 'max:100'],
            'no_rekening' => ['nullable', 'regex:/^[0-9\s\-]{5,50}$/'],
            'atas_nama_rekening' => ['nullable', 'string', 'max:255'],
            'gambar_qris' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'batas_pembayaran_menit' => ['required', 'integer', 'min:5', 'max:1440'],
            'minimum_pesanan' => ['required', 'integer', 'min:0', 'max:10000000'],
            'ambang_stok_menipis' => ['required', 'integer', 'min:0', 'max:1000'],
            'radius_antar_km' => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'catatan_pengantaran' => ['nullable', 'string', 'max:1000'],
        ];
        $pesan = [
            'no_whatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka (boleh diawali +).',
            'no_rekening.regex' => 'Nomor rekening hanya boleh berisi angka.',
        ];
        foreach (self::HARI as $kunci => $nama) {
            $aturan["jadwal.{$kunci}.open"] = ['required', 'date_format:H:i'];
            $aturan["jadwal.{$kunci}.close"] = ['required', 'date_format:H:i', "after:jadwal.{$kunci}.open"];
            $pesan["jadwal.{$kunci}.close.after"] = "Jam tutup hari {$nama} harus setelah jam buka.";
            $pesan["jadwal.{$kunci}.open.date_format"] = "Jam buka hari {$nama} tidak valid.";
            $pesan["jadwal.{$kunci}.close.date_format"] = "Jam tutup hari {$nama} tidak valid.";
        }

        $data = $request->validate($aturan, $pesan, [
            'nama_toko' => 'nama toko', 'alamat_toko' => 'alamat toko', 'no_whatsapp' => 'nomor WhatsApp',
            'batas_pembayaran_menit' => 'batas waktu pembayaran', 'minimum_pesanan' => 'minimum pesanan',
            'ambang_stok_menipis' => 'ambang stok menipis', 'radius_antar_km' => 'radius pengantaran',
        ]);
        $toko = StoreSetting::ambil();

        // Format jadwal sama persis dengan seeder: ["monday" => ["open", "close", "is_open"], ...]
        $jadwal = [];
        foreach (array_keys(self::HARI) as $kunci) {
            $jadwal[$kunci] = [
                'open' => $data['jadwal'][$kunci]['open'],
                'close' => $data['jadwal'][$kunci]['close'],
                'is_open' => $request->boolean("jadwal.{$kunci}.is_open"),
            ];
        }

        if ($request->hasFile('gambar_qris')) {
            $this->hapusQris($toko);
            $data['gambar_qris'] = $request->file('gambar_qris')->store('qris', 'public');
        } elseif ($request->boolean('hapus_qris')) {
            $this->hapusQris($toko);
            $data['gambar_qris'] = null;
        } else {
            unset($data['gambar_qris']);
        }

        unset($data['jadwal']);
        $data['no_whatsapp'] = Order::normalisasiWa($data['no_whatsapp']);
        $data['toko_buka'] = $request->boolean('toko_buka');
        $data['jadwal_operasional'] = $jadwal;
        $toko->update($data);

        return back()->with('ok', 'Pengaturan toko disimpan.');
    }

    private function hapusQris(StoreSetting $toko): void
    {
        if ($toko->gambar_qris) {
            Storage::disk('public')->delete($toko->gambar_qris);
        }
    }
}

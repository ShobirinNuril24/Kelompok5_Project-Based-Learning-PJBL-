<?php

namespace Tests\Feature;

use App\Models\StoreClosedDate;
use App\Models\StoreSetting;
use Database\Seeders\TokoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// Skenario uji ORD §10 untuk jam buka. 2026-11-23 = Senin, 11-25 = Rabu, 11-27 = Jumat.
class JadwalTokoTest extends TestCase
{
    use RefreshDatabase;

    private StoreSetting $toko;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TokoSeeder::class);
        $this->toko = StoreSetting::ambil();
    }

    private function tutup(string $waktu): bool
    {
        return $this->toko->pesanTutup(Carbon::parse($waktu, 'Asia/Jakarta')) !== null;
    }

    public function test_jam_buka_sesuai_ord(): void
    {
        $this->assertTrue($this->tutup('2026-11-23 05:29'));   // Senin 05.29 ditolak
        $this->assertFalse($this->tutup('2026-11-23 05:30'));  // Senin 05.30 boleh (batas awal termasuk)
        $this->assertFalse($this->tutup('2026-11-27 11:29'));  // Jumat 11.29 boleh (D-5: sama dengan hari lain)
        $this->assertTrue($this->tutup('2026-11-27 11:30'));   // Jumat 11.30 ditolak
        $this->assertFalse($this->tutup('2026-11-25 11:29'));  // Rabu 11.29 boleh
        $this->assertTrue($this->tutup('2026-11-25 11:30'));   // Rabu 11.30 ditolak (batas akhir tidak termasuk)
    }

    public function test_tanggal_tutup_khusus_ditolak(): void
    {
        StoreClosedDate::create(['tanggal_tutup' => '2026-11-25', 'alasan' => 'Libur khusus']);

        $this->assertTrue($this->tutup('2026-11-25 08:00'));
        $this->assertFalse($this->tutup('2026-11-24 08:00'));
    }
}

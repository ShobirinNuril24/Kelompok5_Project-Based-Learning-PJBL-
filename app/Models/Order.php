<?php

namespace App\Models;

use App\Support\Wa;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Order extends Model
{
    // Daftar nilai ini harus SAMA dengan ENUM di migration orders.
    public const METODE_PENGAMBILAN = ['Ambil di Toko', 'Diantarkan'];
    public const METODE_PEMBAYARAN = ['Tunai', 'Transfer/QRIS'];
    public const STATUS_PESANAN = [
        'Menunggu Pembayaran', 'Menunggu Konfirmasi', 'Diproses', 'Siap Diambil',
        'Sedang Diantarkan', 'Selesai', 'Dibatalkan', 'Kedaluwarsa',
    ];
    public const STATUS_PEMBAYARAN = ['Belum Dibayar', 'Menunggu Verifikasi', 'Lunas'];

    // Perpindahan status yang boleh dilakukan ADMIN dari form status.
    // "Menunggu Pembayaran" (tombol Minta Pembayaran), "Lunas" (catat pembayaran), dan
    // "Kedaluwarsa" (otomatis oleh sistem) punya jalurnya sendiri. Status akhir tidak punya perpindahan.
    public const TRANSISI = [
        'Menunggu Pembayaran' => ['Dibatalkan'],
        'Menunggu Konfirmasi' => ['Diproses', 'Dibatalkan'],
        'Diproses' => ['Siap Diambil', 'Sedang Diantarkan', 'Dibatalkan'],
        'Siap Diambil' => ['Selesai', 'Dibatalkan'],
        'Sedang Diantarkan' => ['Selesai'], // pengiriman berjalan: pembatalan ditangani manual (ORD §3.3)
    ];

    // public_token sengaja TIDAK ada di sini: dibuat otomatis, tidak boleh diisi dari form.
    protected $fillable = [
        'nomor_pesanan', 'nama_pelanggan', 'no_whatsapp', 'metode_pengambilan',
        'alamat_pengantaran', 'metode_pembayaran', 'ongkir', 'total_harga',
        'status_pesanan', 'status_pembayaran', 'batas_bayar_at', 'catatan_admin',
    ];

    protected $casts = ['batas_bayar_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->public_token ??= Str::random(40);
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function waUrl(string $pesan = ''): string
    {
        return Wa::url($this->no_whatsapp, $pesan);
    }

    // "+62 812-3456-7890" atau "6281234567890" -> "081234567890"
    public static function normalisasiWa(string $nomor): string
    {
        $nomor = preg_replace('/\D/', '', $nomor);

        return str_starts_with($nomor, '62') ? '0'.substr($nomor, 2) : $nomor;
    }

    public function warnaStatus(): string
    {
        return match ($this->status_pesanan) {
            'Selesai' => 'sukses',
            'Dibatalkan', 'Kedaluwarsa' => 'bahaya',
            'Menunggu Pembayaran', 'Menunggu Konfirmasi' => 'peringatan',
            default => 'info',
        };
    }

    public function warnaPembayaran(): string
    {
        return match ($this->status_pembayaran) {
            'Lunas' => 'sukses',
            'Menunggu Verifikasi' => 'info',
            default => 'peringatan',
        };
    }

    // ---------- Aturan perpindahan status ----------

    public function alasanTidakBolehPindah(string $ke): ?string
    {
        if (! in_array($ke, self::TRANSISI[$this->status_pesanan] ?? [], true)) {
            return "Status {$this->status_pesanan} tidak bisa diubah ke {$ke}.";
        }
        if ($ke === 'Siap Diambil' && $this->metode_pengambilan !== 'Ambil di Toko') {
            return 'Pesanan antar tidak memakai status Siap Diambil.';
        }
        if ($ke === 'Sedang Diantarkan' && $this->metode_pengambilan !== 'Diantarkan') {
            return 'Pesanan ambil di toko tidak memakai status Sedang Diantarkan.';
        }
        if ($ke === 'Sedang Diantarkan' && $this->metode_pembayaran === 'Transfer/QRIS' && $this->status_pembayaran !== 'Lunas') {
            return 'Pembayaran Transfer/QRIS harus Lunas sebelum barang dikirim.';
        }
        if ($ke === 'Selesai' && $this->status_pembayaran !== 'Lunas') {
            return 'Catat pembayaran Lunas dulu sebelum menyelesaikan pesanan.';
        }

        return null;
    }

    // Status tujuan yang saat ini valid, untuk pilihan di form admin.
    public function opsiStatus(): array
    {
        return collect(self::TRANSISI[$this->status_pesanan] ?? [])
            ->filter(fn ($ke) => $this->alasanTidakBolehPindah($ke) === null)
            ->values()->all();
    }

    // Admin mengubah status. Mengembalikan pesan galat, atau null jika berhasil.
    public function ubahStatus(string $ke): ?string
    {
        return DB::transaction(function () use ($ke) {
            $order = self::with('items')->whereKey($this->id)->lockForUpdate()->first();

            if ($galat = $order->alasanTidakBolehPindah($ke)) {
                return $galat;
            }

            if ($ke === 'Dibatalkan') {
                $order->akhiri('Dibatalkan', 'Dibatalkan oleh admin. Stok dikembalikan.');
            } else {
                $order->update(['status_pesanan' => $ke]);
            }

            return null;
        });
    }

    // ---------- Pembayaran ----------

    // Mulai menunggu pembayaran online: pasang batas waktu dan catat transaksinya.
    // Dipanggil di dalam transaksi (checkout atau mintaPembayaran). Phase 4: di sini invoice gateway dibuat.
    public function mulaiMenungguPembayaran(): void
    {
        $this->update([
            'status_pesanan' => 'Menunggu Pembayaran',
            'batas_bayar_at' => now()->addMinutes(StoreSetting::ambil()->batas_pembayaran_menit),
        ]);
        $this->payments()->create(['jumlah' => $this->total_harga]);
    }

    // Admin meminta pelanggan membayar (dipakai pesanan antar setelah ongkir pasti).
    public function mintaPembayaran(): ?string
    {
        return DB::transaction(function () {
            $order = self::whereKey($this->id)->lockForUpdate()->first();

            if ($order->metode_pembayaran !== 'Transfer/QRIS' || $order->status_pesanan !== 'Menunggu Konfirmasi' || $order->status_pembayaran === 'Lunas') {
                return 'Permintaan pembayaran hanya untuk pesanan Transfer/QRIS berstatus Menunggu Konfirmasi yang belum lunas.';
            }
            $order->mulaiMenungguPembayaran();

            return null;
        });
    }

    // Pembayaran diterima (admin sekarang, webhook gateway di Phase 4). Pesanan lalu menunggu konfirmasi stok fisik.
    public function tandaiLunas(): ?string
    {
        return DB::transaction(function () {
            $order = self::whereKey($this->id)->lockForUpdate()->first();

            if (in_array($order->status_pesanan, ['Dibatalkan', 'Kedaluwarsa'], true)) {
                return 'Pesanan sudah dibatalkan atau kedaluwarsa. Pembayaran yang masuk harus ditangani manual (refund).';
            }
            if ($order->status_pembayaran === 'Lunas') {
                return null;
            }

            $order->update([
                'status_pembayaran' => 'Lunas',
                'status_pesanan' => $order->status_pesanan === 'Menunggu Pembayaran' ? 'Menunggu Konfirmasi' : $order->status_pesanan,
            ]);
            $order->payments()->where('status', 'Menunggu')->update(['status' => 'Berhasil', 'dibayar_at' => now()]);

            return null;
        });
    }

    // Admin mengubah status pembayaran. "Lunas" lewat tandaiLunas().
    public function ubahPembayaran(string $status): ?string
    {
        if ($status === 'Lunas') {
            return $this->tandaiLunas();
        }

        return DB::transaction(function () use ($status) {
            $order = self::whereKey($this->id)->lockForUpdate()->first();

            if ($order->status_pembayaran === 'Lunas') {
                return 'Pembayaran yang sudah Lunas tidak bisa diubah dari sini.';
            }
            if (in_array($order->status_pesanan, ['Selesai', 'Dibatalkan', 'Kedaluwarsa'], true)) {
                return 'Pesanan sudah final.';
            }
            $order->update(['status_pembayaran' => $status]);

            return null;
        });
    }

    // ---------- Pembatalan dan kedaluwarsa (stok kembali SATU KALI) ----------

    /**
     * Batalkan pesanan (pelanggan atau admin). Baris pesanan dikunci, jadi request ganda tidak
     * mengembalikan stok dua kali. $statusBoleh dan $pembayaranBoleh membatasi status yang sah
     * (dicek di dalam kunci, supaya tidak balapan dengan admin yang sedang mencatat pembayaran).
     */
    public function batalkan(string $oleh, ?array $statusBoleh = null, ?array $pembayaranBoleh = null): bool
    {
        return DB::transaction(function () use ($oleh, $statusBoleh, $pembayaranBoleh) {
            $order = self::with('items')->whereKey($this->id)->lockForUpdate()->first();

            if (in_array($order->status_pesanan, ['Dibatalkan', 'Kedaluwarsa', 'Selesai'], true)) {
                return false;
            }
            if ($statusBoleh !== null && ! in_array($order->status_pesanan, $statusBoleh, true)) {
                return false;
            }
            if ($pembayaranBoleh !== null && ! in_array($order->status_pembayaran, $pembayaranBoleh, true)) {
                return false;
            }
            $order->akhiri('Dibatalkan', "Dibatalkan oleh {$oleh}. Stok dikembalikan.");

            return true;
        });
    }

    public function kedaluwarsakan(): bool
    {
        return DB::transaction(function () {
            $order = self::with('items')->whereKey($this->id)->lockForUpdate()->first();

            if ($order->status_pesanan !== 'Menunggu Pembayaran' || ! $order->batas_bayar_at || $order->batas_bayar_at->isFuture()) {
                return false;
            }
            $order->akhiri('Kedaluwarsa', 'Pembayaran tidak diterima sebelum batas waktu. Stok dikembalikan.');

            return true;
        });
    }

    // Kedaluwarsakan semua pesanan yang lewat batas bayar. Dipanggil oleh perintah terjadwal DAN
    // saat halaman admin/struk dibuka, jadi tetap benar walaupun scheduler belum berjalan.
    public static function prosesKedaluwarsa(): int
    {
        $jumlah = 0;
        foreach (self::where('status_pesanan', 'Menunggu Pembayaran')->where('batas_bayar_at', '<=', now())->get() as $order) {
            $jumlah += $order->kedaluwarsakan() ? 1 : 0;
        }

        return $jumlah;
    }

    // ---------- Perubahan item dan total oleh admin (ORD §3.4) ----------

    /**
     * $kuantitas = [id item => jumlah baru] (0 = hapus). Stok disesuaikan: tambah jumlah mengambil stok,
     * kurang jumlah mengembalikan stok. Semua dalam satu transaksi; gagal = tidak ada yang berubah.
     */
    public function ubahItem(array $kuantitas, ?int $tambahId, int $tambahJumlah, int $ongkir): ?string
    {
        try {
            DB::transaction(function () use ($kuantitas, $tambahId, $tambahJumlah, $ongkir) {
                $order = self::whereKey($this->id)->lockForUpdate()->first();
                if (! in_array($order->status_pesanan, ['Menunggu Konfirmasi', 'Diproses'], true)) {
                    throw new DomainException('Item hanya bisa diubah saat pesanan berstatus Menunggu Konfirmasi atau Diproses.');
                }
                // FR-028: total pesanan yang sudah Lunas tidak boleh berubah tanpa pencatatan selisih (PAY-07 belum diputuskan).
                if ($order->status_pembayaran === 'Lunas') {
                    throw new DomainException('Pesanan sudah Lunas. Perubahan item ditangani manual agar selisih pembayaran tercatat.');
                }

                $items = $order->items()->get();
                $ids = $items->pluck('product_id')->push($tambahId)->filter()->unique()->sort()->values();
                $produk = Product::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $ringkas = [];

                foreach ($items as $item) {
                    $baru = (int) ($kuantitas[$item->id] ?? $item->kuantitas);
                    $selisih = $baru - $item->kuantitas;
                    if ($selisih === 0) {
                        continue;
                    }

                    $p = $produk[$item->product_id];
                    if ($selisih > 0 && $p->stok < $selisih) {
                        throw new DomainException("Stok {$p->nama_produk} tinggal {$p->stok}.");
                    }
                    $selisih > 0 ? $p->decrement('stok', $selisih) : $p->increment('stok', -$selisih);

                    $baru === 0
                        ? $item->delete()
                        : $item->update(['kuantitas' => $baru, 'subtotal' => $item->harga_snapshot * $baru]);
                    $ringkas[] = "{$item->nama_produk_snapshot}: {$item->kuantitas} → {$baru}";
                }

                if ($tambahId) {
                    $p = $produk[$tambahId];
                    if ($items->contains('product_id', $tambahId)) {
                        throw new DomainException("{$p->nama_produk} sudah ada di pesanan. Ubah jumlahnya saja.");
                    }
                    if (! $p->is_active || $p->stok < $tambahJumlah) {
                        throw new DomainException("{$p->nama_produk} tidak tersedia sebanyak itu.");
                    }
                    $p->decrement('stok', $tambahJumlah);
                    $order->items()->create([
                        'product_id' => $p->id,
                        'nama_produk_snapshot' => $p->nama_produk,
                        'harga_snapshot' => $p->harga,
                        'kuantitas' => $tambahJumlah,
                        'subtotal' => $p->harga * $tambahJumlah,
                    ]);
                    $ringkas[] = "+ {$p->nama_produk} × {$tambahJumlah}";
                }

                $subtotal = (int) $order->items()->sum('subtotal');
                if ($subtotal < 1) {
                    throw new DomainException('Pesanan harus punya minimal satu barang. Untuk membatalkan pesanan, ubah statusnya menjadi Dibatalkan.');
                }

                $ongkir = $order->metode_pengambilan === 'Diantarkan' ? $ongkir : 0;
                if ($ongkir !== $order->ongkir) {
                    $ringkas[] = 'Ongkir: Rp '.number_format($order->ongkir, 0, ',', '.').' → Rp '.number_format($ongkir, 0, ',', '.');
                }

                $order->update([
                    'ongkir' => $ongkir,
                    'total_harga' => $subtotal + $ongkir,
                    'catatan_admin' => $ringkas
                        ? $order->gabungCatatan('Item/total diubah admin dan disetujui pelanggan lewat WhatsApp. '.implode('; ', $ringkas).'.')
                        : $order->catatan_admin,
                ]);
            });
        } catch (DomainException $e) {
            return $e->getMessage();
        }

        return null;
    }

    // ---------- Internal ----------

    // Dipanggil dalam transaksi pada baris yang sudah dikunci dan sudah memuat items.
    private function akhiri(string $status, string $catatan): void
    {
        // Urut product_id: sama dengan urutan kunci di checkout dan ubahItem, agar tidak saling menunggu (deadlock).
        foreach ($this->items->sortBy('product_id') as $item) {
            Product::whereKey($item->product_id)->increment('stok', $item->kuantitas);
        }
        $this->payments()->where('status', 'Menunggu')->update(['status' => $status === 'Kedaluwarsa' ? 'Kedaluwarsa' : 'Dibatalkan']);
        $this->update(['status_pesanan' => $status, 'catatan_admin' => $this->gabungCatatan($catatan)]);
    }

    private function gabungCatatan(string $teks): string
    {
        return trim(($this->catatan_admin ? $this->catatan_admin."\n" : '').now('Asia/Jakarta')->format('d-m-Y H:i').' — '.$teks);
    }
}

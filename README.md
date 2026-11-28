# Toko 10 — Sistem Informasi Pemesanan dan Persiapan Barang

Aplikasi web untuk UMKM Toko 10 (sembako, kebutuhan sehari-hari, bahan kue).
Pelanggan memesan tanpa akun. Admin toko mengelola produk, pesanan, pembayaran,
dan pengaturan toko. Proyek PJBL Kelompok 5.

## Fitur utama

- Katalog dan pencarian produk, keranjang, checkout tanpa akun.
- Metode pengambilan: diambil di toko atau diantarkan (ongkir, gratis ongkir mulai subtotal tertentu).
- Pembayaran tunai, atau Transfer/QRIS dengan verifikasi manual oleh admin.
- Alur status pesanan dengan aturan transisi di backend; total dihitung di server dan harga disimpan sebagai snapshot.
- Panel admin: dashboard, pesanan (ubah item, batal, catatan selisih), produk, kategori, pengaturan toko (jam buka, nomor WhatsApp).
- Tautan WhatsApp `wa.me` untuk konfirmasi pesanan.

## Teknologi

| Komponen | Versi yang dipakai tim |
|---|---|
| Laravel | 12.x |
| PHP | ≥ 8.2 (lihat catatan di bawah) |
| Database | MariaDB 10.4 (XAMPP) / MySQL |
| Frontend | Blade, Tailwind CSS v4, Alpine.js, Vite 7 |
| Node.js | ≥ 20.19 (tim memakai 24.x) |

**Catatan PHP.** Aplikasi berjalan di PHP 8.2 (XAMPP). Namun beberapa paket
pengembangan (mis. `laravel/pint`) butuh PHP ≥ 8.3, jadi `composer install`
atau `composer update` sebaiknya dijalankan dengan PHP 8.3+ (misalnya Herd).
Cek dulu PHP mana yang aktif:

```powershell
where.exe php
php -v
```

## Instalasi lokal (Windows + XAMPP)

1. **Clone dan pasang dependency**

   ```powershell
   git clone https://github.com/ShobirinNuril24/kelompok5_project-based-learning-pjbl-.git
   cd kelompok5_project-based-learning-pjbl-
   composer install
   npm ci
   ```

2. **Buat file `.env`**

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

   Buka `.env`, lalu isi `ADMIN_USERNAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD`
   dengan nilai milik sendiri. File `.env` tidak boleh di-commit.

3. **Buat database** (XAMPP: jalankan MySQL dulu)

   ```powershell
   D:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE toko10_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

   Sesuaikan path XAMPP. Jika user `root` memakai password, tambahkan `-p`.

4. **Migrasi dan data awal**

   ```powershell
   php artisan migrate
   php artisan db:seed
   ```

   `db:seed` membuat akun admin, data toko, dan katalog contoh.
   Menjalankan `db:seed` lagi akan **menimpa password admin** dengan nilai
   `ADMIN_PASSWORD` di `.env`.

5. **Tautkan folder upload** (foto produk dan gambar QRIS disimpan di disk `public`)

   ```powershell
   php artisan storage:link
   ```

6. **Build aset dan jalankan**

   ```powershell
   npm run build
   php artisan serve --port=8080
   ```

   Buka `http://127.0.0.1:8080`. Panel admin ada di `/admin/login`.
   Port 8080 dipakai karena di laptop tim port 8000–8010 gagal dipakai.
   Saat mengembangkan tampilan, `npm run dev` bisa dipakai sebagai ganti `npm run build`.

7. **Jalankan scheduler** di terminal terpisah. Perintah `pesanan:kedaluwarsa`
   (dijadwalkan tiap menit di `routes/console.php`) mengakhiri pesanan yang
   melewati batas waktu pembayaran; tanpa scheduler, pesanan itu tidak pernah kedaluwarsa.

   ```powershell
   php artisan schedule:work
   ```

8. **Atur data toko** lewat Admin → Pengaturan: jam buka, nomor WhatsApp,
   rekening dan gambar QRIS, batas waktu pembayaran, minimum pesanan.

## Pengujian

Tes memakai database MySQL terpisah, `toko_sepuluh_test` (diatur di `phpunit.xml`),
jadi data di `toko10_app` tidak tersentuh.

```powershell
D:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS toko_sepuluh_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan test
```

## Backup dan restore database

Lakukan backup sebelum `migrate` di data yang sudah berisi pesanan.

```powershell
D:\xampp\mysql\bin\mysqldump.exe -u root --single-transaction --routines --result-file=backup_toko10_YYYYMMDD.sql toko10_app
```

Restore ke database kosong:

```powershell
D:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE toko10_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cmd /c "D:\xampp\mysql\bin\mysql.exe -u root toko10_restore < backup_toko10_YYYYMMDD.sql"
```

`--result-file` dan `cmd /c` dipakai karena di PowerShell 5.1 operator `>` dan pipe
mengubah encoding file (UTF-16), sehingga dump bisa gagal di-restore.
File backup berisi data pelanggan: simpan di luar repository dan jangan di-commit.

## Keamanan

- Jangan commit `.env`, file backup `.sql`, atau kredensial apa pun.
- Ganti password admin bila pernah terlihat orang lain.
- Cek kerentanan dependency secara berkala: `composer audit` dan `npm audit`.

## Struktur singkat

| Lokasi | Isi |
|---|---|
| `app/Http/Controllers` | Controller pelanggan dan `Admin/` |
| `app/Models` | `Order`, `Product`, dll. (aturan status pesanan ada di `Order`) |
| `app/Support/Keranjang.php` | Logika keranjang berbasis session |
| `config/toko.php` | Akun admin dari `.env`, batas gratis ongkir |
| `database/migrations`, `database/seeders` | Skema dan data awal |
| `resources/views` | Blade: `pelanggan/`, `admin/`, `components/` |
| `tests/Feature` | Tes fitur checkout, pesanan, admin |

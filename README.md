<div align="center">
  <img src="public/images/kuligo-food-mark.png" alt="Kuligo Resto" width="96">
  <h1>🍽️ Kuligo Resto POS</h1>
  <p>Menu QR, pemesanan, dan operasional restoran dalam satu aplikasi.</p>
  <p><strong>8086 Crew</strong></p>
  <p>
    <img alt="Laravel 12" src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white">
    <img alt="PHP 8.2+" src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white">
    <img alt="PostgreSQL" src="https://img.shields.io/badge/Database-PostgreSQL-4169E1?style=for-the-badge&logo=postgresql&logoColor=white">
    <img alt="Midtrans" src="https://img.shields.io/badge/Payments-Midtrans-00A5CF?style=for-the-badge">
  </p>
</div>

---

## 📌 1. Latar Belakang & Solusi

Kuligo Resto dibuat untuk merapikan alur pemesanan di restoran. Pelanggan membuka menu dari QR meja dan mengirim pesanan lewat ponsel. Kasir memantau pembayaran serta status pesanan, sedangkan admin mengelola menu dan operasional harian.

### Fitur utama

- **Menu per meja** — pelanggan melihat menu, memilih varian, dan membuat pesanan dengan memindai QR meja.
- **Pembayaran** — mendukung QRIS dan transfer bank melalui Midtrans Snap, serta tunai yang dibayar langsung ke kasir.
- **Panel kasir** — memantau pesanan dan transaksi, mengonfirmasi pembayaran, serta memperbarui status pesanan.
- **Panel admin** — mengelola menu, kategori, meja, staf, dan laporan.
- **Status dan struk** — pelanggan dapat mengecek perkembangan pesanan dan membuka bukti pembayaran.

## 🛠️ 2. Teknologi & Arsitektur

- **Frontend:** Blade, CSS, JavaScript, Tailwind CSS, Vite.
- **Backend:** PHP 8.2+ dan Laravel 12 dengan pola MVC.
- **Database:** PostgreSQL.
- **Pembayaran:** Midtrans Snap untuk QRIS dan transfer bank; status diperbarui melalui webhook. Tunai tetap dicatat dan dikonfirmasi kasir.
- **Akses:** autentikasi Laravel dan pembatasan peran admin/kasir.

```mermaid
flowchart LR
    Customer["Pelanggan · QR meja"] --> App["Kuligo Resto · Laravel"]
    Staff["Admin & Kasir"] --> App
    App <--> DB[("PostgreSQL")]
    App -->|Buat transaksi QRIS| Midtrans["Midtrans Snap"]
    Midtrans -->|Webhook /midtrans/webhook| App
```

## 🚀 3. Instalasi Lokal

### Prasyarat

- PHP 8.2 atau lebih baru dan Composer
- Node.js dan npm
- PostgreSQL atau database PostgreSQL yang bisa diakses

### Langkah instalasi

```powershell
git clone https://github.com/gilangajahhh/kuliner-kuligo.git
cd kuliner-kuligo
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Di macOS/Linux, gunakan `cp .env.example .env` untuk menyalin berkas environment. Sebelum migrasi, isi koneksi database di `.env`.

```powershell
npm install
npm run build
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Seeder membuat meja dan kategori awal serta akun admin dengan kata sandi sementara yang ditampilkan di terminal. Simpan kredensial tersebut dan ganti kata sandinya setelah login.

Untuk mengembangkan aset frontend, jalankan `npm run dev` di terminal terpisah. Aplikasi biasanya tersedia di `http://127.0.0.1:8000`.

## ⚙️ 4. Environment

Atur variabel berikut di `.env`:

| Variabel | Kegunaan |
| --- | --- |
| `APP_URL` | Alamat aplikasi; gunakan URL publik HTTPS agar webhook Midtrans dapat mengakses aplikasi. |
| `DB_CONNECTION` | Gunakan `pgsql` untuk PostgreSQL. |
| `DB_HOST`, `DB_PORT` | Host dan port database. |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Nama database dan kredensial koneksi. |
| `DB_SSLMODE` | Mode SSL database bila diwajibkan penyedia database. |
| `MIDTRANS_SERVER_KEY` | Secret key Midtrans untuk membuat Snap Token. Jaga kerahasiaannya. |
| `MIDTRANS_CLIENT_KEY` | Client key Midtrans untuk Snap.js di halaman pelanggan. |
| `MIDTRANS_IS_PRODUCTION` | `false` untuk Sandbox; ubah ke `true` untuk Production. |

Jangan masukkan nilai rahasia ke Git atau README.



## 💳 5. Pembayaran QRIS Midtrans

Untuk uji coba, gunakan pasangan kredensial Sandbox dari **Settings → Access Keys → General Credentials → Payment**. Key Sandbox berawalan `SB-Mid-`; atur `MIDTRANS_IS_PRODUCTION=false`, lalu jalankan `php artisan config:clear`. Jangan mencampur key Sandbox dan Production. Di Snap Preferences, aktifkan kanal QRIS dan transfer bank yang ingin digunakan. Pembayaran tunai tetap langsung dibayar ke kasir tanpa membuka Snap.

Atur HTTP Notification URL di dashboard Midtrans menjadi `https://alamat-aplikasi/midtrans/webhook`. Endpoint memverifikasi signature dan nominal transaksi sebelum memperbarui status pembayaran. Untuk Production, gunakan key Production dan ubah `MIDTRANS_IS_PRODUCTION=true`.

## 👥 6. Tim 8086 Crew

| Nama | Bagian |
| --- | --- |
| Muhammad Gilang Ananda Putra | Backend |
| Angga Satria Wijaya | Frontend |
| Fachri Abidzar | Database |

<div align="center">
  <sub>Dibuat bersama oleh 8086 Crew.</sub>
</div>

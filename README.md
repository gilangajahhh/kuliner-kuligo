# Catatan Isi Paket Final

Struktur database final (8 tabel, tanpa `struk`, `id_user` cuma di `pesanan`):
kategori_menu, user, meja, menu, varian_menu, pesanan, detail_pesanan,
pembayaran, log_status_pesanan.

## Cara pasang
1. Timpa/copy folder `app/Models`, `app/Http/Controllers`, `app/Http/Middleware`
   ke project Laravel kamu.
2. Timpa `routes/web.php`.
3. Copy 9 file di `database/migrations/` (HANYA kalau kamu pakai
   `php artisan migrate`; kalau database Supabase sudah dibuat langsung
   lewat SQL Editor, migration ini tidak perlu dijalankan lagi — cukup
   sebagai dokumentasi struktur tabel).
4. Daftarkan RoleMiddleware di bootstrap/app.php (Laravel 11) atau
   app/Http/Kernel.php (Laravel 10 ke bawah):
   'role' => \App\Http\Middleware\RoleMiddleware::class,
5. Pastikan config/auth.php -> providers.users.model diarahkan ke
   App\Models\User::class.

## Yang TIDAK ada di paket ini (perlu dibuat menyusul)
- View Blade (resources/views/**)
- Integrasi payment gateway asli (baru placeholder TODO di PelangganController)

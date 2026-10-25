<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\KategoriMenu;
use App\Models\Meja;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (range(1, 4) as $nomor) {
            $namaMeja = 'Meja ' . str_pad((string) $nomor, 2, '0', STR_PAD_LEFT);
            Meja::firstOrCreate(
                ['nomor_meja' => $namaMeja],
                [
                    'kode_qr' => 'meja-' . str_pad((string) $nomor, 2, '0', STR_PAD_LEFT),
                    'status_meja' => 'kosong',
                ]
            );
        }

        KategoriMenu::firstOrCreate(['nama_kategori' => 'Makanan'], ['urutan_tampil' => 1]);
        KategoriMenu::firstOrCreate(['nama_kategori' => 'Minuman'], ['urutan_tampil' => 2]);

        if (! User::where('username', 'admin')->exists()) {
            $password = Str::password(16);
            User::create([
                'nama' => 'Administrator',
                'username' => 'admin',
                'password_hash' => Hash::make($password),
                'role' => 'admin',
                'status_aktif' => true,
            ]);

            $this->command?->info("Akun admin dibuat. Username: admin · Password sementara: {$password}");
        }
    }
}

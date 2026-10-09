<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const LEGACY_METHODS = "'qris', 'e_wallet', 'kartu_debit', 'kartu_kredit'";

    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE pembayaran DROP CONSTRAINT IF EXISTS pembayaran_metode_pembayaran_check');
            DB::statement("ALTER TABLE pembayaran ADD CONSTRAINT pembayaran_metode_pembayaran_allowed CHECK (metode_pembayaran IN (" . self::LEGACY_METHODS . ", 'tunai'))");

            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pembayaran MODIFY metode_pembayaran ENUM('qris', 'e_wallet', 'kartu_debit', 'kartu_kredit', 'tunai') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::table('pembayaran')->where('metode_pembayaran', 'tunai')->exists()) {
            throw new RuntimeException('Metode tunai sudah dipakai transaksi. Ubah transaksi tunai sebelum rollback migration.');
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE pembayaran DROP CONSTRAINT IF EXISTS pembayaran_metode_pembayaran_allowed');
            DB::statement("ALTER TABLE pembayaran ADD CONSTRAINT pembayaran_metode_pembayaran_check CHECK (metode_pembayaran IN (" . self::LEGACY_METHODS . '))');

            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pembayaran MODIFY metode_pembayaran ENUM('qris', 'e_wallet', 'kartu_debit', 'kartu_kredit') NOT NULL");
        }
    }
};

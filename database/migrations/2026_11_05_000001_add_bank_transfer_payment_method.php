<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->string('kanal_pembayaran', 30)->nullable()->after('metode_pembayaran');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE pembayaran DROP CONSTRAINT IF EXISTS pembayaran_metode_pembayaran_allowed');
            DB::statement("ALTER TABLE pembayaran ADD CONSTRAINT pembayaran_metode_pembayaran_allowed CHECK (metode_pembayaran IN ('qris', 'e_wallet', 'kartu_debit', 'kartu_kredit', 'tunai', 'bank_transfer'))");
        } elseif (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pembayaran MODIFY metode_pembayaran ENUM('qris', 'e_wallet', 'kartu_debit', 'kartu_kredit', 'tunai', 'bank_transfer') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::table('pembayaran')->where('metode_pembayaran', 'bank_transfer')->exists()) {
            throw new RuntimeException('Metode transfer bank sudah dipakai transaksi. Ubah transaksi sebelum rollback migration.');
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE pembayaran DROP CONSTRAINT IF EXISTS pembayaran_metode_pembayaran_allowed');
            DB::statement("ALTER TABLE pembayaran ADD CONSTRAINT pembayaran_metode_pembayaran_allowed CHECK (metode_pembayaran IN ('qris', 'e_wallet', 'kartu_debit', 'kartu_kredit', 'tunai'))");
        } elseif (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pembayaran MODIFY metode_pembayaran ENUM('qris', 'e_wallet', 'kartu_debit', 'kartu_kredit', 'tunai') NOT NULL");
        }

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropColumn('kanal_pembayaran');
        });
    }
};

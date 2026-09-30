<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // id_user di sini: kasir/staff yang menangani pesanan (nullable, terisi setelah diverifikasi)
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id('id_pesanan');
            $table->foreignId('id_meja')->constrained('meja', 'id_meja')->cascadeOnDelete();
            $table->foreignId('id_user')->nullable()->constrained('user', 'id_user')->nullOnDelete();
            $table->string('no_pesanan')->unique();
            $table->string('nama_pelanggan')->nullable();
            $table->timestamp('waktu_pesan')->useCurrent();
            $table->enum('status_pesanan', ['baru', 'diproses', 'siap_diantar', 'selesai', 'batal'])->default('baru');
            $table->decimal('total_harga', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};

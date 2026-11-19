<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reservasi', function (Blueprint $table) {
            $table->id('id_reservasi');
            $table->foreignId('id_meja')->constrained('meja', 'id_meja')->cascadeOnDelete();
            $table->string('kode_booking', 24)->unique();
            $table->string('nama_pemesan', 100);
            $table->string('whatsapp', 30);
            $table->string('keperluan', 160)->nullable();
            $table->date('tanggal_reservasi');
            $table->time('waktu_reservasi');
            $table->unsignedSmallInteger('jumlah_orang');
            $table->enum('status', ['menunggu', 'dikonfirmasi', 'ditolak', 'selesai'])->default('menunggu');
            $table->timestamps();
            $table->index(['id_meja', 'tanggal_reservasi', 'waktu_reservasi', 'status'], 'reservasi_jadwal_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservasi');
    }
};

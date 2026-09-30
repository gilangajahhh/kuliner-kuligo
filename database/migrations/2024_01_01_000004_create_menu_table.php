<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catatan: tabel menu TIDAK punya id_user (relasi user cuma ada di tabel pesanan)
        Schema::create('menu', function (Blueprint $table) {
            $table->id('id_menu');
            $table->foreignId('id_kategori')->constrained('kategori_menu', 'id_kategori')->cascadeOnDelete();
            $table->string('nama_menu');
            $table->text('deskripsi')->nullable();
            $table->decimal('harga_dasar', 12, 2);
            $table->string('url_gambar')->nullable();
            $table->boolean('status_tersedia')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu');
    }
};

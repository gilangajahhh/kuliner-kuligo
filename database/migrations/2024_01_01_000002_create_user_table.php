<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nama tabel "user" (bukan "users" bawaan Laravel) — Admin & Kasir jadi satu tabel,
        // dibedakan lewat kolom "role". Kolom no_hp sengaja tidak ada.
        Schema::create('user', function (Blueprint $table) {
            $table->id('id_user');
            $table->string('nama');
            $table->string('username')->unique();
            $table->string('password_hash');
            $table->enum('role', ['admin', 'kasir'])->default('kasir');
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('varian_menu', function (Blueprint $table) {
            $table->id('id_varian');
            $table->foreignId('id_menu')->constrained('menu', 'id_menu')->cascadeOnDelete();
            $table->string('nama_varian');
            $table->decimal('harga_tambahan', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('varian_menu');
    }
};

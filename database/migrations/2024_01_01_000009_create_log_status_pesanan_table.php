<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_status_pesanan', function (Blueprint $table) {
            $table->id('id_log');
            $table->foreignId('id_pesanan')->constrained('pesanan', 'id_pesanan')->cascadeOnDelete();
            $table->foreignId('id_user')->nullable()->constrained('user', 'id_user')->nullOnDelete();
            $table->string('status_baru');
            $table->timestamp('waktu_update')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_status_pesanan');
    }
};

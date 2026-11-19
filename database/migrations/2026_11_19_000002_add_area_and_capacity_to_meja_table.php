<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('meja', function (Blueprint $table) {
            $table->enum('tipe_meja', ['indoor', 'outdoor'])->default('indoor');
            $table->unsignedSmallInteger('kapasitas')->default(4);
        });
    }

    public function down(): void
    {
        Schema::table('meja', function (Blueprint $table) {
            $table->dropColumn(['tipe_meja', 'kapasitas']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pembayaran', 'snap_token')) {
            return;
        }

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->string('snap_token', 100)->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pembayaran', 'snap_token')) {
            return;
        }

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropColumn('snap_token');
        });
    }
};

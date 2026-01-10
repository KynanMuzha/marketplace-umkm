<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Kode pembayaran untuk QRIS, TF_BANK, E_WALLET
            $table->string('payment_code', 20)->nullable()->after('payment_detail');

            // Tenggat waktu pembayaran
            $table->timestamp('payment_due')->nullable()->after('payment_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_code', 'payment_due']);
        });
    }
};

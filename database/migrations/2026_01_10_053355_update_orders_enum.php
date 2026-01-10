<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['Menunggu Pembayaran','Diproses','Dikirim','Selesai','Dibatalkan'])
                  ->default('Menunggu Pembayaran')
                  ->change();

            $table->enum('payment_status', ['pending','paid','unpaid','failed'])
                  ->default('pending')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['menunggu','diproses','dikirim','selesai'])->default('menunggu')->change();
            $table->enum('payment_status', ['pending','paid','failed'])->default('pending')->change();
        });
    }
};

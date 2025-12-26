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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('total', 15, 2);
            $table->string('shipping_method')->nullable();
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['pending','paid','failed'])->default('pending');
            $table->enum('status', ['menunggu','diproses','dikirim','selesai'])->default('menunggu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

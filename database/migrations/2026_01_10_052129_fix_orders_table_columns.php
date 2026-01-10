<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Kolom customer
            if (!Schema::hasColumn('orders', 'customer_name')) {
                $table->string('customer_name')->nullable();
            } else {
                $table->string('customer_name')->nullable()->change();
            }

            if (!Schema::hasColumn('orders', 'customer_address')) {
                $table->string('customer_address')->nullable();
            } else {
                $table->string('customer_address')->nullable()->change();
            }

            if (!Schema::hasColumn('orders', 'customer_phone')) {
                $table->string('customer_phone')->nullable();
            } else {
                $table->string('customer_phone')->nullable()->change();
            }

            // Kolom payment
            if (!Schema::hasColumn('orders', 'payment_detail')) {
                $table->string('payment_detail')->nullable();
            } else {
                $table->string('payment_detail')->nullable()->change();
            }

            if (!Schema::hasColumn('orders', 'payment_code')) {
                $table->string('payment_code')->nullable();
            } else {
                $table->string('payment_code')->nullable()->change();
            }

            if (!Schema::hasColumn('orders', 'payment_url')) {
                $table->string('payment_url')->nullable();
            } else {
                $table->string('payment_url')->nullable()->change();
            }

            if (!Schema::hasColumn('orders', 'payment_due')) {
                $table->timestamp('payment_due')->nullable();
            } else {
                $table->timestamp('payment_due')->nullable()->change();
            }

            // Kolom total & shipping
            $table->decimal('total', 15, 2)->change();
            $table->decimal('shipping_cost', 15, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        // Optional: bisa dikembalikan ke kondisi sebelumnya jika perlu
    }
};

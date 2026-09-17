<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->string('payment_method')->default('paypal'); // paypal hoặc cod
                $table->string('transaction_id')->nullable();       // Mã giao dịch PayPal trả về
                $table->decimal('amount', 12, 2);
                $table->string('currency', 10)->default('VND');
                $table->string('status')->default('pending');        // pending, completed, failed
                $table->json('payload')->nullable();                // Dữ liệu phản hồi từ PayPal
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

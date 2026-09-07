<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Đơn hàng
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // Phương thức thanh toán
            $table->enum('payment_method', [
                'cod',
                'paypal'
            ]);

            // Mã giao dịch PayPal
            $table->string('transaction_id')->nullable()->unique();

            // Số tiền thanh toán
            $table->decimal('amount', 12, 2);

            // Trạng thái giao dịch
            $table->enum('status', [
                'pending',
                'completed',
                'failed',
                'refunded'
            ])->default('pending');

            // Thời điểm thanh toán thành công
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Thông tin đơn hàng
            $table->string('order_code')->unique();

            // Thông tin người nhận
            $table->string('recipient_name');
            $table->string('recipient_phone', 20);
            $table->text('recipient_address');

            // Thông tin giao hàng
            $table->date('delivery_date');
            $table->foreignId('delivery_slot_id')
                ->constrained('delivery_slots')
                ->restrictOnDelete();

            // Thông tin tiền
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Thanh toán
            $table->enum('payment_method', [
                'cod',
                'paypal'
            ])->default('cod');

            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'refunded'
            ])->default('pending');

            // Trạng thái đơn hàng
            $table->enum('order_status', [
                'pending',
                'confirmed',
                'preparing',
                'shipping',
                'completed',
                'cancelled'
            ])->default('pending');

            // Ghi chú
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

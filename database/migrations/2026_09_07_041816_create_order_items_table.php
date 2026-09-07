<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            // Đơn hàng
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // Sản phẩm
            // Chưa liên kết product_id vì project hiện tại chưa có bảng products
            $table->unsignedBigInteger('product_id')->nullable();

            // Lưu thông tin sản phẩm tại thời điểm đặt hàng
            $table->string('product_name');
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

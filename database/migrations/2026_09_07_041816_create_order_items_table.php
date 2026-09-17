<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();

                // Đơn hàng
                $table->foreignId('order_id')
                    ->constrained('orders')
                    ->cascadeOnDelete();

                // Sản phẩm hoa (đã liên kết trực tiếp với bảng products của TV2)
                $table->foreignId('product_id')
                    ->nullable()
                    ->constrained('products')
                    ->nullOnDelete();

                // Lưu thông tin sản phẩm tại thời điểm đặt hàng
                $table->string('product_name');
                $table->decimal('price', 12, 2)->default(0);
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('subtotal', 12, 2)->default(0);

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

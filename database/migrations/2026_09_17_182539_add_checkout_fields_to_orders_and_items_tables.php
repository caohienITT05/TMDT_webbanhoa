<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Bổ sung các cột thanh toán & giao hàng cho bảng orders
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_code')) {
                $table->string('order_code')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('orders', 'recipient_name')) {
                $table->string('recipient_name')->nullable();
            }
            if (!Schema::hasColumn('orders', 'recipient_phone')) {
                $table->string('recipient_phone')->nullable();
            }
            if (!Schema::hasColumn('orders', 'recipient_address')) {
                $table->text('recipient_address')->nullable();
            }
            if (!Schema::hasColumn('orders', 'delivery_date')) {
                $table->date('delivery_date')->nullable();
            }
            if (!Schema::hasColumn('orders', 'delivery_slot_id')) {
                $table->unsignedBigInteger('delivery_slot_id')->nullable();
            }
            if (!Schema::hasColumn('orders', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'discount')) {
                $table->decimal('discount', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'shipping_fee')) {
                $table->decimal('shipping_fee', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'total')) {
                $table->decimal('total', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method')->default('cod');
            }
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status')->default('pending');
            }
            if (!Schema::hasColumn('orders', 'order_status')) {
                $table->string('order_status')->default('pending');
            }
            if (!Schema::hasColumn('orders', 'note')) {
                $table->text('note')->nullable();
            }
        });

        // 2. Bổ sung các cột chi tiết hoa cho bảng order_items
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'product_name')) {
                $table->string('product_name')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('order_items', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        // Hoàn tác khi cần thiết
    }
};
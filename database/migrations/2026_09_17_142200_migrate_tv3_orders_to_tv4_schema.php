<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * TV3 had old orders/order_items tables.
         * The tables are currently empty, so we can safely
         * replace their schema with TV4's Order/OrderItem schema.
         */

        // order_items depends on orders, so drop it first.
        Schema::dropIfExists('order_items');

        // Drop the old TV3 orders table.
        Schema::dropIfExists('orders');

        /*
         * TV4 Orders
         */
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_code')->unique();

            // Recipient information
            $table->string('recipient_name');
            $table->string('recipient_phone', 20);
            $table->text('recipient_address');

            // Delivery information
            $table->date('delivery_date');

            $table->foreignId('delivery_slot_id')
                ->constrained('delivery_slots')
                ->restrictOnDelete();

            // Money
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Payment
            $table->enum('payment_method', [
                'cod',
                'paypal',
            ])->default('cod');

            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'refunded',
            ])->default('pending');

            // Order status
            $table->enum('order_status', [
                'pending',
                'confirmed',
                'preparing',
                'shipping',
                'completed',
                'cancelled',
            ])->default('pending');

            // Note
            $table->text('note')->nullable();

            $table->timestamps();
        });

        /*
         * TV4 Order Items
         */
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            /*
             * Keep product_id nullable because TV4 originally
             * did not have a products table.
             *
             * The actual product_id will come from TV2 Product.
             */
            $table->unsignedBigInteger('product_id')->nullable();

            // Snapshot product information at checkout time
            $table->string('product_name');
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 12, 2);

            $table->timestamps();
        });

        /*
         * TV4 Payments
         */
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->enum('payment_method', [
                'cod',
                'paypal',
            ]);

            $table->string('transaction_id')
                ->nullable()
                ->unique();

            $table->decimal('amount', 12, 2);

            $table->enum('status', [
                'pending',
                'completed',
                'failed',
                'refunded',
            ])->default('pending');

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        /*
         * Reverse the integration schema.
         *
         * We do not recreate the old TV3 tables here because
         * those tables contained no business data when migration
         * was created.
         */
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
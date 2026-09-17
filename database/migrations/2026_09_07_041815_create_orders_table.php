<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('delivery_slot_id')->nullable();
                $table->string('order_code')->nullable()->unique();
                $table->string('recipient_name')->nullable();
                $table->string('recipient_phone')->nullable();
                $table->text('recipient_address')->nullable();
                $table->date('delivery_date')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('discount', 15, 2)->default(0);
                $table->decimal('shipping_fee', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->string('payment_method')->default('cod');
                $table->string('payment_status')->default('pending');
                $table->string('order_status')->default('pending');
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
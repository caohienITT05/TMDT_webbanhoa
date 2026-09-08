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
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
        $table->string('customer_name');
        $table->string('customer_phone');
        $table->string('customer_address');
        $table->string('note')->nullable();
        $table->date('delivery_date')->nullable();
        $table->string('delivery_time_slot')->nullable();
        $table->text('gift_card_message')->nullable();
        $table->decimal('total_amount', 12, 2);
        $table->string('payment_method')->default('COD'); // COD / PAYPAL
        $table->string('payment_status')->default('UNPAID'); // UNPAID / PAID
        $table->string('status')->default('PENDING'); // PENDING, CONFIRMED, PREPARING, SHIPPING, COMPLETED, CANCELLED
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

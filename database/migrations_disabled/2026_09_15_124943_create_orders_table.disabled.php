<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('delivery_slot_id')->nullable()->constrained('delivery_slots')->nullOnDelete();
            $table->foreignId('gift_card_id')->nullable()->constrained('gift_cards')->nullOnDelete();
            $table->foreignId('gift_wrap_id')->nullable()->constrained('gift_wraps')->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();

            $table->string('recipient_name');
            $table->string('recipient_phone', 20);
            $table->text('recipient_address');
            $table->date('delivery_date');
            $table->text('card_message')->nullable();
            $table->text('order_note')->nullable();

            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);

            $table->enum('status', [
                'PENDING',
                'CONFIRMED',
                'PREPARING',
                'SHIPPING',
                'COMPLETED',
                'CANCELLED'
            ])->default('PENDING');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

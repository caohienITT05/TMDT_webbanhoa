<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('vouchers')) {
            Schema::create('vouchers', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->enum('type', ['percent', 'fixed'])->default('fixed'); // percent: giảm %, fixed: trừ tiền mặt
                $table->decimal('value', 12, 2);                              // Giá trị giảm (% hoặc VNĐ)
                $table->decimal('min_order_amount', 12, 2)->default(0);       // Đơn tối thiểu
                $table->decimal('max_discount', 12, 2)->nullable();           // Giảm tối đa nếu là loại percent
                $table->dateTime('start_time');                               // Khung giờ bắt đầu
                $table->dateTime('end_time');                                 // Khung giờ kết thúc
                $table->unsignedInteger('usage_limit')->default(100);         // Số lượng mã phát hành
                $table->unsignedInteger('used_count')->default(0);            // Đã dùng
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
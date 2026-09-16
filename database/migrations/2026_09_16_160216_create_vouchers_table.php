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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();

            // Mã voucher, ví dụ: BLOOM10
            $table->string('code')->unique();

            // Loại giảm giá: percent hoặc fixed
            $table->string('type')->default('fixed');

            // Giá trị giảm
            // percent: 10 = giảm 10%
            // fixed: 50000 = giảm 50.000đ
            $table->decimal('value', 12, 2);

            // Đơn hàng tối thiểu để được sử dụng voucher
            $table->decimal('min_order_amount', 12, 2)->default(0);

            // Mức giảm tối đa, áp dụng cho voucher phần trăm
            $table->decimal('max_discount', 12, 2)->nullable();

            // Thời gian bắt đầu và kết thúc
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Số lần voucher được sử dụng
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);

            // Voucher có đang hoạt động hay không
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
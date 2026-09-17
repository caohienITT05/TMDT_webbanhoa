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
        // Bảng order_items đã được khởi tạo bởi core TV1, để trống để bỏ qua tạo trùng
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Để trống để tránh xóa nhầm dữ liệu đơn hàng
    }
};
<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // payments đã được tạo bởi migration core 2026_09_07.
    }

    public function down(): void
    {
        // Không xóa bảng payments của migration core.
    }
};

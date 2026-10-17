<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'inventory_reserved_at')) {
                $table->timestamp('inventory_reserved_at')->nullable();
            }
            if (! Schema::hasColumn('orders', 'inventory_released_at')) {
                $table->timestamp('inventory_released_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $columns = [];
            if (Schema::hasColumn('orders', 'inventory_released_at')) {
                $columns[] = 'inventory_released_at';
            }
            if (Schema::hasColumn('orders', 'inventory_reserved_at')) {
                $columns[] = 'inventory_reserved_at';
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

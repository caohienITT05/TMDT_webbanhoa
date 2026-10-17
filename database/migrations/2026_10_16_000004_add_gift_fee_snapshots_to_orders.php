<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'gift_card_fee')) {
                $table->decimal('gift_card_fee', 15, 2)->nullable();
            }

            if (! Schema::hasColumn('orders', 'gift_wrap_fee')) {
                $table->decimal('gift_wrap_fee', 15, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = array_values(array_filter(['gift_card_fee', 'gift_wrap_fee'], fn ($column) => Schema::hasColumn('orders', $column)));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

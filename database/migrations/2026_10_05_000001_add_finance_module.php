<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost_price', 15, 2)->nullable();
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('cost_price_snapshot', 15, 2)->nullable();
        });
        Schema::create('order_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            foreach (['shipping_cost_actual', 'packaging_cost_actual', 'payment_gateway_fee_actual', 'other_direct_cost'] as $column) {
                $table->decimal($column, 15, 2)->nullable();
            }
            $table->text('note')->nullable();
            $table->timestamps();
        });
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_code')->unique();
            $table->string('expense_type')->index();
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->date('expense_date')->index();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('tax_type');
            $table->string('tax_name');
            $table->string('tax_base');
            $table->decimal('rate', 8, 4);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();
        });
        Schema::create('tax_records', function (Blueprint $table) {
            $table->id();
            $table->date('period_start')->index();
            $table->date('period_end');
            $table->string('tax_type');
            $table->decimal('taxable_amount', 15, 2);
            $table->decimal('tax_rate', 8, 4);
            $table->decimal('estimated_tax', 15, 2);
            $table->decimal('actual_tax', 15, 2)->nullable();
            $table->string('status')->default('estimated');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['period_start', 'period_end', 'tax_type']);
        });
    }

    public function down(): void
    {
        // Only removes the additions from this migration. Export finance data before rollback.
        Schema::dropIfExists('tax_records');
        Schema::dropIfExists('tax_settings');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('order_costs');
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('cost_price_snapshot'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('cost_price'));
    }
};

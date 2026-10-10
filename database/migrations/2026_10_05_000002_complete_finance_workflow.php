<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_cost_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('old_cost_price', 15, 2)->nullable();
            $table->decimal('new_cost_price', 15, 2);
            $table->timestamp('effective_at')->index();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_key')->default('default')->unique();
            $table->decimal('default_shipping_cost', 15, 2)->default(0);
            $table->decimal('default_packaging_cost', 15, 2)->default(0);
            $table->decimal('cod_fee', 15, 2)->default(0);
            $table->decimal('paypal_fee_rate', 8, 4)->default(0);
            $table->decimal('paypal_fixed_fee', 15, 2)->default(0);
            $table->string('currency', 3)->default('VND');
            $table->timestamps();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('cost_price_snapshot_source', 40)->nullable()->after('cost_price_snapshot');
        });

        Schema::table('order_costs', function (Blueprint $table) {
            $table->string('source', 40)->nullable()->after('note');
            $table->timestamp('financial_finalized_at')->nullable()->index()->after('source');
        });

        Schema::table('tax_settings', function (Blueprint $table) {
            $table->text('description')->nullable()->after('is_active');
            $table->string('legal_reference', 500)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('tax_settings', function (Blueprint $table) {
            $table->dropColumn(['description', 'legal_reference']);
        });
        Schema::table('order_costs', function (Blueprint $table) {
            $table->dropIndex(['financial_finalized_at']);
            $table->dropColumn(['source', 'financial_finalized_at']);
        });
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('cost_price_snapshot_source'));
        Schema::dropIfExists('finance_settings');
        Schema::dropIfExists('product_cost_histories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vat_policy_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('scope', 20);
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('category_name_snapshot')->nullable();
            $table->json('product_ids')->nullable();
            $table->string('vat_category', 30);
            $table->decimal('vat_rate', 8, 4)->nullable();
            $table->boolean('price_includes_vat')->default(true);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['scope', 'effective_from', 'effective_to'], 'vat_policy_scope_period_idx');
            $table->index(['category_id', 'effective_from', 'effective_to'], 'vat_policy_category_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_policy_rules');
    }
};

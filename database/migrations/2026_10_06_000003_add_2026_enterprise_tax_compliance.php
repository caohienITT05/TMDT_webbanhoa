<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('store_key')->default('default')->unique();
            $table->string('entity_type')->default('enterprise');
            $table->string('business_model')->default('b2c');
            $table->unsignedSmallInteger('tax_year')->default(2026);
            $table->boolean('is_new_business')->default(true);
            $table->boolean('has_non_qualifying_related_party')->default(false);
            $table->decimal('declared_annual_revenue', 15, 2)->nullable();
            $table->string('vat_method')->default('deduction');
            $table->boolean('prices_include_vat')->default(true);
            $table->decimal('default_vat_rate', 8, 4)->default(8);
            $table->string('einvoice_status')->default('demo_not_connected');
            $table->boolean('is_demo')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::table('tax_settings', function (Blueprint $table) {
            $table->string('tax_category', 30)->default('income')->after('tax_name');
            $table->string('calculation_method', 40)->default('exclusive')->after('tax_base');
            $table->boolean('is_demo')->default(false)->after('is_active');
            $table->string('legal_reference_url', 1000)->nullable()->after('legal_reference');
        });

        Schema::table('tax_records', function (Blueprint $table) {
            $table->string('tax_category', 30)->default('income')->after('tax_type');
            $table->string('calculation_method', 40)->default('exclusive')->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('tax_records', function (Blueprint $table) {
            $table->dropColumn(['tax_category', 'calculation_method']);
        });
        Schema::table('tax_settings', function (Blueprint $table) {
            $table->dropColumn(['tax_category', 'calculation_method', 'is_demo', 'legal_reference_url']);
        });
        Schema::dropIfExists('tax_profiles');
    }
};

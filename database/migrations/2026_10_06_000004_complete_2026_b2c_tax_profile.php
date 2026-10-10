<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_profiles', function (Blueprint $table) {
            $table->string('industry')->default('online_flowers_and_gifts')->after('business_model');
            $table->boolean('eligible_small_enterprise')->default(true)->after('is_new_business');
            $table->decimal('projected_annual_revenue', 15, 2)->nullable()->after('declared_annual_revenue');
            $table->decimal('actual_annual_revenue', 15, 2)->nullable()->after('projected_annual_revenue');
            $table->decimal('prior_year_revenue', 15, 2)->nullable()->after('actual_annual_revenue');
            $table->boolean('einvoice_required')->default(true)->after('einvoice_status');
        });

        Schema::create('product_tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('vat_category', 30);
            $table->decimal('vat_rate', 8, 4)->nullable();
            $table->boolean('price_includes_vat')->default(true);
            $table->date('effective_from')->default('2026-01-01');
            $table->date('effective_to')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        DB::table('tax_settings')->where('tax_type', 'tndn_doanh_nghiep_2026')->update([
            'rate' => 15,
            'description' => 'Tự đánh giá trạng thái miễn thuế riêng biệt. Nếu không được miễn, hệ thống chọn 15%, 17% hoặc 20% theo doanh thu và điều kiện hồ sơ doanh nghiệp.',
            'updated_at' => now(),
        ]);
        DB::table('tax_settings')->where('tax_type', 'gtgt_dau_ra_demo_2026')->update([
            'calculation_method' => 'catalog_vat_2026',
            'description' => 'VAT đầu ra tham khảo theo mapping từng sản phẩm; sản phẩm chưa phân loại dùng mức mặc định demo có thể cấu hình. Giá bán demo đang được cấu hình là đã gồm VAT.',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('tax_settings')->where('tax_type', 'tndn_doanh_nghiep_2026')->update([
            'rate' => 0,
            'updated_at' => now(),
        ]);
        DB::table('tax_settings')->where('tax_type', 'gtgt_dau_ra_demo_2026')->update([
            'calculation_method' => 'inclusive',
            'updated_at' => now(),
        ]);

        Schema::dropIfExists('product_tax_profiles');
        Schema::table('tax_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'industry',
                'eligible_small_enterprise',
                'projected_annual_revenue',
                'actual_annual_revenue',
                'prior_year_revenue',
                'einvoice_required',
            ]);
        });
    }
};

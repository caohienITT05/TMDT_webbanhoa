<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BloomGift2026EnterpriseTaxDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        DB::table('tax_profiles')->updateOrInsert(
            ['store_key' => 'default'],
            [
                'entity_type' => 'enterprise',
                'business_model' => 'b2c',
                'industry' => 'online_flowers_and_gifts',
                'tax_year' => 2026,
                'is_new_business' => true,
                'eligible_small_enterprise' => true,
                'has_non_qualifying_related_party' => false,
                'declared_annual_revenue' => null,
                'projected_annual_revenue' => null,
                'actual_annual_revenue' => null,
                'prior_year_revenue' => null,
                'vat_method' => 'deduction',
                'prices_include_vat' => true,
                'default_vat_rate' => 8,
                'einvoice_status' => 'demo_not_connected',
                'einvoice_required' => true,
                'is_demo' => true,
                'note' => 'Hồ sơ demo: doanh nghiệp Việt Nam mới, độc lập, kinh doanh B2C; doanh thu năm lấy tự động từ đơn được ghi nhận trong hệ thống.',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('tax_settings')->updateOrInsert(
            ['tax_type' => 'tndn_doanh_nghiep_2026', 'effective_from' => '2026-01-01'],
            [
                'tax_name' => 'Thuế thu nhập doanh nghiệp 2026',
                'tax_category' => 'income',
                'tax_base' => 'profit_before_tax',
                'calculation_method' => 'corporate_income_2026',
                'rate' => 15,
                'effective_to' => '2026-12-31',
                'is_active' => true,
                'is_demo' => true,
                'description' => 'Tự đánh giá trạng thái miễn thuế riêng biệt. Nếu không được miễn, hệ thống chọn 15%, 17% hoặc 20% theo doanh thu và điều kiện hồ sơ doanh nghiệp.',
                'legal_reference' => 'Nghị định 141/2026/NĐ-CP; Luật Thuế TNDN 67/2025/QH15; Nghị định 320/2025/NĐ-CP.',
                'legal_reference_url' => 'https://vanban.chinhphu.vn/?classid=1&docid=217960&pageid=27160&typegroupid=4',
                'note' => 'Lợi nhuận trước thuế trong ứng dụng là số quản trị; khi quyết toán phải điều chỉnh theo hồ sơ kế toán và chi phí được trừ.',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('tax_settings')->updateOrInsert(
            ['tax_type' => 'gtgt_dau_ra_demo_2026', 'effective_from' => '2026-01-01'],
            [
                'tax_name' => 'VAT đầu ra tham khảo 2026',
                'tax_category' => 'indirect',
                'tax_base' => 'net_revenue',
                'calculation_method' => 'catalog_vat_2026',
                'rate' => 8,
                'effective_to' => '2026-12-31',
                'is_active' => true,
                'is_demo' => true,
                'description' => 'VAT đầu ra tham khảo theo mapping từng sản phẩm; sản phẩm chưa phân loại dùng mức mặc định demo có thể cấu hình. Giá bán demo đang được cấu hình là đã gồm VAT.',
                'legal_reference' => 'Nghị quyết 204/2025/QH15; Nghị định 174/2025/NĐ-CP; Luật Thuế GTGT 48/2024/QH15.',
                'legal_reference_url' => 'https://vanban.chinhphu.vn/default.aspx?docid=214209&pageid=27160',
                'note' => 'Sản phẩm chỉ là hoa tươi chưa chế biến có thể thuộc mức 5%; phải phân loại theo hóa đơn thực tế trước khi kê khai.',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}

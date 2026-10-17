<?php

namespace Tests\Feature\Finance;

use App\Models\OrderItem;
use App\Services\FinanceAnalyticsService;
use App\Services\TaxComplianceService;
use Database\Seeders\BloomGift2026EnterpriseTaxDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesBloomGiftData;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use CreatesBloomGiftData, RefreshDatabase {
        CreatesBloomGiftData::afterRefreshingDatabase insteadof RefreshDatabase;
    }

    private function financialOrder(string $status = 'completed', ?int $cost = 250000): array
    {
        $product = $this->createProduct(['price' => 500000, 'cost_price' => $cost]);
        $order = $this->createOrder(null, ['order_status' => $status, 'subtotal' => 1000000, 'discount' => 50000, 'shipping_fee' => 30000, 'total' => 980000, 'total_amount' => 980000]);
        $item = OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name, 'price' => 500000, 'quantity' => 2, 'subtotal' => 1000000]);
        DB::table('order_costs')->insert(['order_id' => $order->id, 'shipping_cost_actual' => 40000, 'packaging_cost_actual' => 20000, 'payment_gateway_fee_actual' => 10000, 'other_direct_cost' => 0, 'source' => 'manual']);
        DB::table('expenses')->insert(['expense_code' => 'CP-TEST-'.strtoupper(Str::random(8)), 'expense_type' => 'other', 'description' => 'Đã đối chiếu không phát sinh chi phí vận hành trong test', 'amount' => 0, 'expense_date' => now()->toDateString()]);

        return [$order, $item, $product];
    }

    public function test_snapshot_is_captured_once_and_unknown_cost_stays_null(): void
    {
        [$order, $item, $product] = $this->financialOrder();
        $product->update(['cost_price' => 320000]);
        $item->update(['product_name' => 'Historical name']);
        $this->assertEquals('250000.00', $item->fresh()->cost_price_snapshot);
        [, $unknown] = $this->financialOrder('completed', null);
        $this->assertNull($unknown->cost_price_snapshot);
        $this->assertNull(app(FinanceAnalyticsService::class)->getPeriodSummary(now()->toDateString(), now()->toDateString())['totals']['profit_before_tax']);
    }

    public function test_gift_service_fees_are_separated_and_legacy_amount_stays_unallocated(): void
    {
        [$order] = $this->financialOrder();
        $service = app(FinanceAnalyticsService::class);

        $legacy = $service->orderSummary($order->fresh()->load('items'), DB::table('order_costs')->where('order_id', $order->id)->first());
        $this->assertSame(0, $legacy['unallocated_income']);
        $this->assertNull($legacy['gift_service_income']);

        $order->update(['total' => 1150000, 'total_amount' => 1150000, 'gift_card_fee' => 10000, 'gift_wrap_fee' => 30000]);
        $itemCost = DB::table('order_costs')->where('order_id', $order->id)->first();
        $known = $service->orderSummary($order->fresh()->load('items'), $itemCost);
        $this->assertSame(4000000, $known['gift_service_income']);
        $this->assertSame(13000000, $known['unallocated_income']);
        $this->assertNull($known['estimated_order_profit']);
        $this->assertNull($known['order_profit']);

        DB::table('order_costs')->where('order_id', $order->id)->update(['source' => 'system_default']);
        $estimate = $service->orderSummary($order->fresh()->load('items'), DB::table('order_costs')->where('order_id', $order->id)->first());
        $this->assertSame(45000000, $estimate['estimated_order_profit']);
    }

    public function test_completed_forecast_and_cancelled_orders_are_not_mixed(): void
    {
        $this->financialOrder();
        $this->financialOrder('pending');
        $this->financialOrder('cancelled');
        [$refunded] = $this->financialOrder();
        $refunded->update(['payment_status' => 'refunded']);
        DB::table('expenses')->insert(['expense_code' => 'CP-TEST', 'expense_type' => 'rent', 'description' => 'Rent', 'amount' => 100000, 'expense_date' => now()->toDateString()]);
        $service = app(FinanceAnalyticsService::class);
        $actual = $service->getPeriodSummary(now()->toDateString(), now()->toDateString())['totals'];
        $forecast = $service->getPeriodSummary(now()->toDateString(), now()->toDateString(), true)['totals'];
        $this->assertEquals(98000000, $actual['net_revenue']);
        $this->assertEquals(7000000, $actual['cancelled_costs']);
        $this->assertEquals(24000000, $actual['profit_before_tax']);
        $this->assertEquals(41000000, $forecast['order_profit']);
        $this->assertEquals(0, $forecast['operating_expenses']);
        $this->assertNull($actual['net_profit_after_tax']);
    }

    public function test_dashboard_keeps_recorded_costs_and_configured_estimates_separate(): void
    {
        [$order] = $this->financialOrder();
        DB::table('order_costs')->where('order_id', $order->id)->update(['source' => 'system_default']);
        $this->createOrder(null, ['order_status' => 'cancelled', 'status' => 'CANCELLED']);
        DB::table('expenses')->insert([
            'expense_code' => 'CP-TEST-SPLIT', 'expense_type' => 'rent',
            'description' => 'Rent entered for this period', 'amount' => 100000,
            'expense_date' => now()->toDateString(),
        ]);

        $totals = app(FinanceAnalyticsService::class)
            ->getPeriodSummary(now()->toDateString(), now()->toDateString())['totals'];

        $this->assertSame(60000000, $totals['recorded_costs_subtotal']);
        $this->assertSame(7000000, $totals['configured_costs_estimate']);
        $this->assertSame(67000000, $totals['costs_shown_subtotal']);
        $this->assertTrue($totals['costs_shown_incomplete']);
        $this->assertNull($totals['profit_before_tax']);
        $this->assertSame(31000000, $totals['provisional_profit_before_tax']);

        $this->actingAs($this->createAdmin())->get(route('admin.finance.index'))
            ->assertOk()
            ->assertSee('Chi phí đã ghi và tạm tính')
            ->assertSee('670.000 đ')
            ->assertSee('310.000 đ')
            ->assertSee('chưa gồm chi phí đơn hủy chưa rõ');
    }

    public function test_zero_system_defaults_do_not_pretend_missing_cost_data_is_zero(): void
    {
        $product = $this->createProduct(['cost_price' => null]);
        $order = $this->createOrder(null, ['order_status' => 'completed', 'status' => 'COMPLETED']);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id,
            'product_name' => $product->name, 'price' => $product->price,
            'quantity' => 1, 'subtotal' => $product->price,
        ]);
        DB::table('order_costs')->insert([
            'order_id' => $order->id, 'source' => 'system_default',
            'shipping_cost_actual' => 0, 'packaging_cost_actual' => 0,
            'payment_gateway_fee_actual' => 0, 'other_direct_cost' => 0,
        ]);

        $totals = app(FinanceAnalyticsService::class)
            ->getPeriodSummary(now()->toDateString(), now()->toDateString())['totals'];
        $this->assertNull($totals['recorded_costs_subtotal']);
        $this->assertNull($totals['costs_shown_subtotal']);
        $this->assertTrue($totals['costs_shown_incomplete']);
    }

    public function test_order_totals_flow_into_finance_revenue_automatically(): void
    {
        $recognized = $this->createOrder(null, [
            'status' => 'CONFIRMED',
            'order_status' => 'confirmed',
            'total' => 1000000,
            'total_amount' => 1000000,
        ]);
        $awaiting = $this->createOrder(null, [
            'total' => 500000,
            'total_amount' => 500000,
        ]);
        $this->createOrder(null, [
            'status' => 'CANCELLED',
            'order_status' => 'cancelled',
            'total' => 900000,
            'total_amount' => 900000,
        ]);
        $this->createOrder(null, [
            'status' => 'RETURNED', 'order_status' => 'completed', 'payment_status' => 'paid',
            'total' => 2000000, 'total_amount' => 2000000,
        ]);
        $this->createOrder(null, [
            'status' => 'COMPLETED', 'order_status' => 'completed', 'payment_status' => 'refunded',
            'total' => 3000000, 'total_amount' => 3000000,
        ]);

        $service = app(FinanceAnalyticsService::class);
        $day = now()->toDateString();
        $flow = $service->revenueFlow($day, $day);
        $this->assertEquals(100000000, $flow['recognized_revenue']);
        $this->assertEquals(150000000, $flow['orders_value']);
        $this->assertEquals(50000000, $flow['awaiting_revenue']);
        $this->assertSame(2, $flow['orders_count']);
        $this->assertSame(1, $flow['awaiting_count']);

        $awaiting->update(['payment_status' => 'paid']);
        $this->assertEquals(150000000, $service->revenueFlow($day, $day)['recognized_revenue']);

        $this->actingAs($this->createAdmin());
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('1.500.000 đ');
        $this->get(route('admin.finance.index'))->assertOk()->assertSee('1.500.000 đ');
        $this->assertDatabaseHas('orders', ['id' => $recognized->id, 'total_amount' => 1000000]);
    }

    public function test_finance_separates_order_recognition_from_payment_records(): void
    {
        $confirmedCod = $this->createOrder(null, [
            'order_status' => 'confirmed', 'status' => 'CONFIRMED', 'total_amount' => 1000000, 'total' => 1000000,
        ]);
        $paypal = $this->createOrder(null, [
            'order_status' => 'confirmed', 'status' => 'CONFIRMED', 'payment_method' => 'paypal',
            'total_amount' => 800000, 'total' => 800000,
        ]);
        $this->createPayment($paypal, ['status' => 'completed', 'amount' => 650000]);

        $this->actingAs($this->createAdmin())
            ->get(route('admin.finance.index'))
            ->assertOk()
            ->assertViewHas('actual', fn ($actual) => $actual['totals']['recognized_revenue'] === 180000000)
            ->assertViewHas('collectedTotal', 65000000)
            ->assertSee('Tiền đã thu')
            ->assertSee('650.000 đ');
    }

    public function test_finance_overview_uses_active_order_quantity_while_completed_report_stays_separate(): void
    {
        $product = $this->createProduct(['name' => 'Sales scope QA product']);
        $processing = $this->createOrder(null, ['order_status' => 'processing', 'status' => 'PROCESSING']);
        $completed = $this->createOrder(null, ['order_status' => 'completed', 'status' => 'COMPLETED']);
        $failed = $this->createOrder(null, ['order_status' => 'failed', 'status' => 'FAILED', 'payment_status' => 'failed']);

        foreach ([[$processing, 3, 'Old snapshot name'], [$completed, 2, 'Other historical name'], [$failed, 8, $product->name]] as [$order, $quantity, $snapshotName]) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $snapshotName,
                'price' => 500000,
                'quantity' => $quantity,
                'subtotal' => $quantity * 500000,
            ]);
        }

        $this->actingAs($this->createAdmin())
            ->get(route('admin.finance.index', ['period' => 'custom', 'from_date' => now()->toDateString(), 'to_date' => now()->toDateString()]))
            ->assertOk()
            ->assertViewHas('bestSellers', fn ($rows) => $rows->contains(fn ($row) => (int) $row->product_id === $product->id && (int) $row->quantity === 5 && $row->product_name === $product->name))
            ->assertViewHas('completedBestSellers', fn ($rows) => $rows->contains(fn ($row) => (int) $row->product_id === $product->id && (int) $row->quantity === 2 && $row->product_name === $product->name))
            ->assertSee('Sản phẩm được đặt nhiều trong kỳ')
            ->assertSee('không phải doanh thu đã ghi nhận');
    }

    public function test_conflicting_order_statuses_do_not_promote_processing_order_to_completed(): void
    {
        $order = $this->createOrder(null, [
            'order_status' => 'processing', 'status' => 'COMPLETED', 'payment_status' => 'pending',
            'total_amount' => 1200000, 'total' => 1200000,
        ]);
        $day = $order->created_at->toDateString();

        $summary = app(FinanceAnalyticsService::class)->getPeriodSummary($day, $day, false, false);
        $flow = app(FinanceAnalyticsService::class)->revenueFlow($day, $day);

        $this->assertSame(0, $summary['totals']['order_count']);
        $this->assertSame(0, $flow['recognized_count']);
        $this->assertSame(120000000, $flow['awaiting_revenue']);
        $this->assertSame(0.0, app(TaxComplianceService::class)->recognizedRevenueForYear((int) now()->year));
    }

    public function test_legacy_order_total_is_used_when_total_amount_snapshot_is_only_default_zero(): void
    {
        $order = $this->createOrder(null, [
            'order_status' => 'confirmed', 'status' => 'CONFIRMED', 'payment_status' => 'pending',
            'total_amount' => 0, 'total' => 500000,
        ]);
        $day = $order->created_at->toDateString();
        $flow = app(FinanceAnalyticsService::class)->revenueFlow($day, $day);

        $this->assertSame(50000000, $flow['recognized_revenue']);
        $this->assertSame(50000000, $flow['orders_value']);
        $this->assertSame(500000.0, app(TaxComplianceService::class)->recognizedRevenueForYear((int) now()->year));
    }

    public function test_tax_estimates_are_separate_and_actual_requires_exact_period(): void
    {
        $this->financialOrder();
        DB::table('tax_settings')->insert(['tax_type' => 'example', 'tax_name' => 'Test only', 'tax_base' => 'profit_before_tax', 'rate' => 10, 'effective_from' => '2000-01-01', 'is_active' => true]);
        $service = app(FinanceAnalyticsService::class);
        $day = now()->toDateString();
        $summary = $service->getPeriodSummary($day, $day)['totals'];
        $this->assertEquals(4100000, $summary['estimated_tax']);
        $this->assertNull($summary['actual_tax']);
        DB::table('tax_records')->insert(['period_start' => $day, 'period_end' => $day, 'tax_type' => 'example', 'taxable_amount' => 410000, 'tax_rate' => 10, 'estimated_tax' => 41000, 'actual_tax' => 35000, 'status' => 'confirmed']);
        $summary = $service->getPeriodSummary($day, $day)['totals'];
        $this->assertEquals(3500000, $summary['actual_tax']);
        $this->assertEquals(37500000, $summary['net_profit_after_tax']);
        $this->assertNull($service->getPeriodSummary(now()->subDay()->toDateString(), $day)['totals']['actual_tax']);
    }

    public function test_tax_rates_and_catalog_vat_stay_unknown_without_a_business_profile(): void
    {
        $service = app(TaxComplianceService::class);

        $this->assertSame('unconfigured', $service->corporateIncomeTaxEvaluation()['status']);
        $this->assertNull($service->corporateIncomeTaxRate());
        $this->assertNull($service->estimateCatalogOutputVat('2026-01-01', '2026-12-31')['tax']);

        DB::table('tax_settings')->insert([
            'tax_type' => 'corporate_demo',
            'tax_name' => 'Thuế TNDN demo',
            'tax_category' => 'income',
            'tax_base' => 'profit_before_tax',
            'calculation_method' => 'corporate_income_2026',
            'rate' => 15,
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-12-31',
            'is_active' => true,
        ]);

        $taxes = $service->calculatePeriodTaxes('2026-01-01', '2026-12-31', ['profit_before_tax' => 100000000]);
        $this->assertSame('unconfigured', $taxes['cit_status']);
        $this->assertNull($taxes['estimated_tax']);
        $this->assertNull($taxes['cit_rate']);
    }

    public function test_multiple_tax_types_require_all_actual_records_and_mid_period_rules_need_explicit_record(): void
    {
        $this->financialOrder();
        $day = now()->toDateString();
        foreach (['a', 'b'] as $type) {
            DB::table('tax_settings')->insert(['tax_type' => $type, 'tax_name' => $type, 'tax_base' => 'net_revenue', 'rate' => 1, 'effective_from' => $day, 'is_active' => true]);
        }
        DB::table('tax_records')->insert(['period_start' => $day, 'period_end' => $day, 'tax_type' => 'a', 'taxable_amount' => 980000, 'tax_rate' => 1, 'estimated_tax' => 9800, 'actual_tax' => 9800, 'status' => 'confirmed']);
        $service = app(FinanceAnalyticsService::class);
        $this->assertNull($service->getPeriodSummary($day, $day)['totals']['actual_tax']);
        $this->assertNull($service->getPeriodSummary(now()->subDay()->toDateString(), $day)['totals']['estimated_tax']);
    }

    public function test_tax_rates_support_presets_and_custom_values_with_effective_dates(): void
    {
        $this->financialOrder();
        $day = now()->toDateString();
        $service = app(FinanceAnalyticsService::class);
        foreach ([15, 17, 20, 12.5] as $rate) {
            DB::table('tax_settings')->insert([
                'tax_type' => 'rate_'.$rate,
                'tax_name' => 'Rate '.$rate,
                'tax_base' => 'profit_before_tax',
                'rate' => $rate,
                'effective_from' => $day,
                'is_active' => true,
            ]);
            $this->assertEquals((int) round(41000000 * $rate / 100), $service->getPeriodSummary($day, $day)['totals']['estimated_tax']);
            DB::table('tax_settings')->delete();
        }
    }

    public function test_2026_enterprise_b2c_demo_separates_cit_and_output_vat(): void
    {
        $this->seed(BloomGift2026EnterpriseTaxDemoSeeder::class);
        $this->seed(BloomGift2026EnterpriseTaxDemoSeeder::class);
        $this->financialOrder();

        $this->assertDatabaseCount('tax_profiles', 1);
        $this->assertDatabaseCount('tax_settings', 2);
        $summary = app(FinanceAnalyticsService::class)->getPeriodSummary(now()->toDateString(), now()->toDateString())['totals'];

        $this->assertSame(0, $summary['estimated_tax']);
        $this->assertNull($summary['estimated_indirect_tax']);
        $this->assertSame(95000000, $summary['unclassified_indirect_tax_base']);
        $this->assertSame(41000000, $summary['estimated_net_profit_after_tax']);
        $this->assertNull($summary['actual_tax']);
        $this->assertNull($summary['actual_indirect_tax']);

        $this->actingAs($this->createAdmin())->get(route('admin.finance.taxes'))
            ->assertOk()
            ->assertSee('Doanh nghiệp Việt Nam')
            ->assertSee('B2C · bán trực tiếp cho người tiêu dùng')
            ->assertSee('Dự kiến được miễn')
            ->assertSee('Chưa kết nối')
            ->assertSee('Nghị định số 141/2026/NĐ-CP');
    }

    public function test_2026_catalog_vat_is_reported_as_incomplete_until_products_are_classified(): void
    {
        $this->seed(BloomGift2026EnterpriseTaxDemoSeeder::class);
        $this->financialOrder();
        $totals = app(FinanceAnalyticsService::class)->getPeriodSummary(now()->toDateString(), now()->toDateString())['totals'];

        $this->assertNull($totals['estimated_indirect_tax']);
        $this->assertSame(95000000, $totals['unclassified_indirect_tax_base']);
    }

    public function test_catalog_vat_uses_explicit_product_mapping_and_configured_shipping_rate(): void
    {
        $this->seed(BloomGift2026EnterpriseTaxDemoSeeder::class);
        [, , $product] = $this->financialOrder();
        DB::table('product_tax_profiles')->insert([
            'product_id' => $product->id, 'vat_category' => 'eight', 'vat_rate' => 8,
            'price_includes_vat' => true, 'effective_from' => '2026-01-01', 'effective_to' => '2026-12-31',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $totals = app(FinanceAnalyticsService::class)->getPeriodSummary(now()->toDateString(), now()->toDateString())['totals'];

        $this->assertSame(7259259, $totals['estimated_indirect_tax']);
        $this->assertSame(0, $totals['unclassified_indirect_tax_base']);
    }

    public function test_2026_reduction_applies_only_after_annual_revenue_and_split_status_are_confirmed(): void
    {
        $this->seed(BloomGift2026EnterpriseTaxDemoSeeder::class);
        DB::table('tax_profiles')->update([
            'projected_annual_revenue' => 4_000_000_000,
            'actual_annual_revenue' => null,
            'is_split_from_large_enterprise' => false,
        ]);
        $service = app(TaxComplianceService::class);
        $eligible = $service->corporateIncomeTaxEvaluation();
        $this->assertSame('eligible_estimate', $eligible['cit_reduction_status']);
        $this->assertSame(30, $eligible['cit_reduction_percent']);
        $reduced = $service->calculatePeriodTaxes('2026-01-01', '2026-12-31', ['profit_before_tax' => 10_000_000_000]);
        $this->assertSame(1_190_000_000, $reduced['estimated_tax']);

        DB::table('tax_profiles')->update(['is_split_from_large_enterprise' => null]);
        $unknown = $service->corporateIncomeTaxEvaluation();
        $this->assertSame('needs_confirmation', $unknown['cit_reduction_status']);
        $this->assertSame(0, $unknown['cit_reduction_percent']);
    }

    public function test_2026_enterprise_cit_rate_uses_revenue_thresholds_and_related_party_exclusion(): void
    {
        $this->seed(BloomGift2026EnterpriseTaxDemoSeeder::class);
        $service = app(TaxComplianceService::class);

        foreach ([
            [500_000_000, false, 'estimated_exempt', 15.0],
            [1_500_000_000, false, 'taxable', 15.0],
            [3_000_000_001, false, 'taxable', 17.0],
            [50_000_000_001, false, 'taxable', 20.0],
            [500_000_000, true, 'taxable', 20.0],
        ] as [$revenue, $related, $status, $rate]) {
            DB::table('tax_profiles')->update([
                'projected_annual_revenue' => $revenue,
                'actual_annual_revenue' => null,
                'has_non_qualifying_related_party' => $related,
            ]);
            $evaluation = $service->corporateIncomeTaxEvaluation();
            $this->assertSame($status, $evaluation['status']);
            $this->assertSame($rate, $evaluation['applicable_rate']);
        }
    }

    public function test_2026_cit_engine_recalculates_exemption_and_15_17_20_percent_tiers(): void
    {
        $this->seed(BloomGift2026EnterpriseTaxDemoSeeder::class);
        $service = app(TaxComplianceService::class);
        $totals = ['profit_before_tax' => 10_000_000_000, 'net_revenue' => 0];

        foreach ([
            [500_000_000, null, 'estimated_exempt', 0],
            [1_500_000_000, null, 'taxable', 1_500_000_000],
            [4_000_000_000, null, 'taxable', 1_700_000_000],
            [60_000_000_000, null, 'taxable', 2_000_000_000],
            [500_000_000, 1_500_000_000, 'taxable', 1_500_000_000],
        ] as [$projected, $actual, $status, $tax]) {
            DB::table('tax_profiles')->update([
                'projected_annual_revenue' => $projected,
                'actual_annual_revenue' => $actual,
                'has_non_qualifying_related_party' => false,
                'eligible_small_enterprise' => true,
            ]);
            $result = $service->calculatePeriodTaxes('2026-01-01', '2026-12-31', $totals);
            $this->assertSame($status, $result['cit_status']);
            $this->assertSame($tax, $result['estimated_tax']);
        }
    }

    public function test_vat_formulas_handle_tax_inclusive_and_exclusive_prices(): void
    {
        DB::table('tax_settings')->insert([
            'tax_type' => 'vat_test', 'tax_name' => 'VAT test', 'tax_category' => 'indirect',
            'tax_base' => 'net_revenue', 'calculation_method' => 'inclusive', 'rate' => 8,
            'effective_from' => '2026-01-01', 'effective_to' => '2026-12-31', 'is_active' => true,
        ]);
        $service = app(TaxComplianceService::class);
        $inclusive = $service->calculatePeriodTaxes('2026-01-01', '2026-12-31', ['net_revenue' => 10_800_000]);
        $this->assertSame(800_000, $inclusive['estimated_indirect_tax']);

        DB::table('tax_settings')->where('tax_type', 'vat_test')->update(['calculation_method' => 'exclusive']);
        $exclusive = $service->calculatePeriodTaxes('2026-01-01', '2026-12-31', ['net_revenue' => 10_000_000]);
        $this->assertSame(800_000, $exclusive['estimated_indirect_tax']);
    }

    public function test_admin_can_map_a_product_to_a_vat_category_without_changing_product_data(): void
    {
        $product = $this->createProduct()->fresh();
        $businessFields = ['name', 'slug', 'price', 'sale_price', 'stock', 'cost_price', 'season', 'is_active'];
        $before = $product->only($businessFields);
        $beforeUpdatedAt = $product->updated_at?->toISOString();
        $this->actingAs($this->createAdmin())->post(route('admin.finance.product-tax-profiles.save'), [
            'product_id' => $product->id,
            'vat_category' => 'five',
            'price_includes_vat' => 1,
            'effective_from' => now()->toDateString(),
            'effective_to' => '2026-12-31',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_tax_profiles', ['product_id' => $product->id, 'vat_category' => 'five', 'vat_rate' => 5]);
        $product->refresh();
        $this->assertEquals($before, $product->only($businessFields));
        $this->assertSame($beforeUpdatedAt, $product->updated_at?->toISOString());
        $this->actingAs($this->createAdmin())->post(route('admin.finance.product-tax-profiles.save'), [
            'product_id' => $product->id,
            'vat_category' => 'ten',
            'price_includes_vat' => 1,
            'effective_from' => now()->toDateString(),
        ])->assertSessionHasErrors('product_id');
        $this->assertDatabaseHas('product_tax_profiles', ['product_id' => $product->id, 'vat_category' => 'five', 'vat_rate' => 5]);
    }

    public function test_admin_can_add_store_category_and_multi_product_vat_rules_without_overwriting_history(): void
    {
        $one = $this->createProduct();
        $two = $this->createProduct();
        $today = now()->toDateString();
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.finance.vat-products.save'), [
            'scope' => 'store', 'vat_category' => 'eight', 'price_includes_vat' => 1,
            'effective_from' => $today, 'note' => 'Quy tắc demo toàn cửa hàng',
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.finance.vat-products.save'), [
            'scope' => 'category', 'category_id' => $one->category_id, 'vat_category' => 'five', 'price_includes_vat' => 1,
            'effective_from' => $today,
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.finance.vat-products.save'), [
            'scope' => 'products', 'product_ids' => [$one->id, $two->id], 'vat_category' => 'custom', 'vat_rate' => 7.5,
            'price_includes_vat' => 0, 'effective_from' => $today,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('vat_policy_rules', 3);
        $this->assertDatabaseHas('vat_policy_rules', ['scope' => 'category', 'category_id' => $one->category_id, 'vat_rate' => 5]);
        $this->assertDatabaseHas('vat_policy_rules', ['scope' => 'products', 'vat_category' => 'custom', 'vat_rate' => 7.5]);

        $this->post(route('admin.finance.vat-products.save'), [
            'scope' => 'products', 'product_ids' => [$one->id], 'vat_category' => 'ten', 'price_includes_vat' => 1,
            'effective_from' => $today,
        ])->assertSessionHasErrors('effective_from');
        $this->assertDatabaseCount('vat_policy_rules', 3);

        $this->get(route('admin.finance.vat-products'))->assertOk()
            ->assertSee('Xem trước ảnh hưởng của quy tắc')
            ->assertSee('Đối soát VAT đơn hàng cũ')
            ->assertSee('data-price', false)
            ->assertSee('Lịch sử quy tắc VAT')
            ->assertSee('Quy tắc demo toàn cửa hàng')->assertSee($one->name)->assertSee($two->name);
    }

    public function test_vat_policy_specificity_is_product_then_category_then_store_and_uses_order_date(): void
    {
        $this->seed(BloomGift2026EnterpriseTaxDemoSeeder::class);
        [$order, , $product] = $this->financialOrder();
        $base = [
            'vat_rate' => null, 'price_includes_vat' => true, 'effective_from' => $order->created_at->toDateString(),
            'effective_to' => null, 'created_at' => now(), 'updated_at' => now(),
        ];
        DB::table('vat_policy_rules')->insert([
            $base + ['scope' => 'store', 'category_id' => null, 'product_ids' => null, 'vat_category' => 'ten'],
            $base + ['scope' => 'category', 'category_id' => $product->category_id, 'product_ids' => null, 'vat_category' => 'five'],
            $base + ['scope' => 'products', 'category_id' => null, 'product_ids' => json_encode([$product->id]), 'vat_category' => 'eight'],
        ]);

        $vat = app(TaxComplianceService::class)->estimateCatalogOutputVat($order->created_at->toDateString(), $order->created_at->toDateString());
        $this->assertSame(7259259, $vat['tax']);
        $this->assertSame(0, $vat['unclassified_base']);
        $this->assertTrue($vat['complete']);
    }

    public function test_finance_pages_are_admin_only_and_render(): void
    {
        [$order] = $this->financialOrder();
        $day = now()->toDateString();
        $routes = ['admin.finance.index', 'admin.finance.revenue', 'admin.finance.reconciliation', 'admin.finance.orders', 'admin.finance.settings', 'admin.finance.expenses', 'admin.finance.taxes', 'admin.finance.vat-products'];
        foreach ($routes as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
        $this->get(route('admin.finance.export', ['from_date' => $day, 'to_date' => $day]))->assertRedirect(route('login'));
        $this->actingAs($this->createCustomer())->get(route('admin.finance.index'))->assertForbidden();
        $this->actingAs($this->createAdmin());
        foreach ($routes as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('admin.finance.index'))->assertOk()->assertSee('Tổng quan tài chính')->assertSee('Sáu chỉ số tài chính chính')->assertSee('Doanh thu 12 tháng');
        $this->get(route('admin.finance.revenue'))->assertOk()->assertSee('So sánh doanh thu & lợi nhuận', false);
        $this->get(route('admin.finance.reconciliation'))->assertOk()->assertSee('Đơn cần đối soát');
        $this->get(route('admin.finance.order', $order))->assertOk()->assertSee('Chi phí nhập thủ công, chưa xác minh chứng từ');
        $export = $this->get(route('admin.finance.export', ['from_date' => $day, 'to_date' => $day]))->assertOk();
        $this->assertStringContainsString('DỮ LIỆU TÀI CHÍNH BLOOMGIFT', $export->streamedContent());
        $this->assertStringContainsString($order->order_code, $export->streamedContent());
        $this->assertStringContainsString(now()->format('d/m/Y'), $export->streamedContent());
        $this->assertStringContainsString('Giá khuyến mãi', $export->streamedContent());
        $this->assertStringContainsString('Tồn kho', $export->streamedContent());
        $this->assertStringContainsString('Mùa vụ', $export->streamedContent());
        $this->get(route('admin.finance.index', ['period' => 'custom', 'from_date' => '2026-10-06', 'to_date' => '2026-10-01']))->assertSessionHasErrors('to_date');
    }

    public function test_historical_backfill_only_changes_missing_finance_fields(): void
    {
        [$order, $item] = $this->financialOrder('completed', null);
        $before = $order->fresh()->getAttributes();
        $this->actingAs($this->createAdmin())->put(route('admin.finance.order.save', $order), [
            'snapshots' => [$item->id => 250000], 'shipping_cost_actual' => 40000,
            'packaging_cost_actual' => 20000, 'payment_gateway_fee_actual' => 10000, 'other_direct_cost' => 0,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEquals('250000.00', $item->fresh()->cost_price_snapshot);
        $this->assertSame($before, $order->fresh()->getAttributes());
        $this->put(route('admin.finance.order.save', $order), ['snapshots' => [$item->id => 1]])->assertSessionHasNoErrors();
        $this->assertEquals('250000.00', $item->fresh()->cost_price_snapshot);
        [, $other] = $this->financialOrder('pending', null);
        $this->put(route('admin.finance.order.save', $order), ['snapshots' => [$other->id => 1]])->assertSessionHasErrors('snapshots');
        $this->assertNull($other->fresh()->cost_price_snapshot);
    }

    public function test_expenses_can_be_created_edited_and_filtered_without_direct_cost_categories(): void
    {
        $this->actingAs($this->createAdmin());
        $data = ['expense_type' => 'rent', 'description' => 'Rent', 'amount' => 123000, 'expense_date' => now()->toDateString()];
        $this->post(route('admin.finance.expenses.save'), $data)->assertSessionHasNoErrors();
        $id = DB::table('expenses')->value('id');
        $this->post(route('admin.finance.expenses.save'), ['id' => $id, 'amount' => 200000] + $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('expenses', 1);
        $this->assertDatabaseHas('expenses', ['amount' => 200000]);
        $this->post(route('admin.finance.expenses.save'), ['expense_type' => 'shipping'] + $data)->assertSessionHasErrors('expense_type');
        $this->get(route('admin.finance.expenses', ['expense_type' => 'rent', 'edit' => $id]))->assertOk()->assertSee('Rent');
    }

    public function test_tax_input_validates_actual_amount_and_overlapping_periods(): void
    {
        $this->actingAs($this->createAdmin());
        $data = ['period_start' => '2026-10-01', 'period_end' => '2026-10-31', 'tax_type' => 'test', 'taxable_amount' => 1000000, 'tax_rate' => 10, 'status' => 'confirmed'];
        $this->post(route('admin.finance.tax-records.save'), $data)->assertSessionHasErrors('actual_tax');
        $this->post(route('admin.finance.tax-records.save'), $data + ['actual_tax' => 90000])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tax_records', ['actual_tax' => 90000, 'estimated_tax' => 100000]);
        $this->post(route('admin.finance.tax-records.save'), ['period_start' => '2026-10-15', 'actual_tax' => 0] + $data)->assertSessionHasErrors('period_start');

        $vat = ['period_start' => '2026-11-01', 'period_end' => '2026-11-30', 'tax_type' => 'vat', 'tax_category' => 'indirect', 'calculation_method' => 'inclusive', 'taxable_amount' => 1080000, 'tax_rate' => 8, 'status' => 'estimated'];
        $this->post(route('admin.finance.tax-records.save'), $vat)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tax_records', ['tax_type' => 'vat', 'tax_category' => 'indirect', 'estimated_tax' => 80000]);
    }
}

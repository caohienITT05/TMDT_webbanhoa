<?php

namespace Tests\Feature\Finance;

use App\Models\OrderItem;
use App\Services\FinanceAnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesBloomGiftData;
use Tests\TestCase;

class FinanceComparisonTest extends TestCase
{
    use CreatesBloomGiftData, RefreshDatabase {
        CreatesBloomGiftData::afterRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_comparison_dashboard_uses_finance_period_summaries_for_week_and_month(): void
    {
        $today = CarbonImmutable::parse('2026-10-09 12:00:00', config('app.timezone'));
        $weekStart = $today->startOfWeek();
        $this->financialOrderAt($weekStart);
        $this->financialOrderAt($weekStart->addDay());
        $this->financialOrderAt($weekStart->subWeek()->addDay());

        $dashboard = app(FinanceAnalyticsService::class)->comparisonDashboard($today, $weekStart->subWeek(), $today);

        $this->assertSame(100000000, $dashboard['week']['revenue']['current']);
        $this->assertSame(50000000, $dashboard['week']['revenue']['previous']);
        $this->assertSame(100.0, $dashboard['week']['revenue']['percentage']);
        $this->assertSame(50000000, $dashboard['week']['profit']['current']);
        $this->assertSame(25000000, $dashboard['week']['profit']['previous']);
        $this->assertCount(2, $dashboard['weekly_trend']);
        $this->assertCount(2, $dashboard['monthly_trend']);
        $this->assertSame('28/09 – 04/10', $dashboard['weekly_trend'][0]['label']);
        $this->assertSame('05/10 – 09/10', $dashboard['weekly_trend'][1]['label']);
        $this->assertSame('Tháng 09/2026', $dashboard['monthly_trend'][0]['label']);
        $this->assertSame('Tháng 10/2026', $dashboard['monthly_trend'][1]['label']);
        $this->assertCount(2, $dashboard['quarterly_trend']);
        $this->assertSame('Quý 3/2026', $dashboard['quarterly_trend'][0]['label']);
        $this->assertSame('Quý 4/2026', $dashboard['quarterly_trend'][1]['label']);

        $selectedComparison = app(FinanceAnalyticsService::class)->compareSelectedPeriod('2026-10-01', '2026-10-09', 'month');
        $this->assertSame('01/10/2026 – 09/10/2026', $selectedComparison['current_label']);
        $this->assertSame('01/09/2026 – 09/09/2026', $selectedComparison['previous_label']);

        $quarterDashboard = app(FinanceAnalyticsService::class)->comparisonDashboard($today, $today->startOfQuarter(), $today);
        $this->assertCount(2, $quarterDashboard['weekly_trend']);
        $this->assertCount(1, $quarterDashboard['monthly_trend']);
        $this->assertSame('01/10 – 04/10', $quarterDashboard['weekly_trend'][0]['label']);
        $this->assertSame('05/10 – 09/10', $quarterDashboard['weekly_trend'][1]['label']);

        $this->actingAs($this->createAdmin())->get(route('admin.finance.revenue'))
            ->assertOk()
            ->assertSee('So sánh doanh thu & lợi nhuận', false)
            ->assertSee('Doanh thu & lợi nhuận theo tuần')
            ->assertSee('Doanh thu & lợi nhuận theo tháng')
            ->assertSee('Tuần theo lịch; các cột có nhãn mốc thời gian rõ ràng.')
            ->assertSee('Di chuột trên biểu đồ để xem chi tiết từng kỳ.')
            ->assertSee('Doanh thu & lợi nhuận theo quý')
            ->assertSee('Mốc tháng / quý / năm')
            ->assertSee('So sánh kỳ đã chọn với kỳ tương ứng trước đó');
    }

    public function test_date_ranges_are_half_open_and_negative_comparison_base_has_no_percentage(): void
    {
        $lossOrder = $this->financialOrderAt(CarbonImmutable::parse('2026-09-05 12:00:00', config('app.timezone')));
        $lossOrder->items()->first()->update(['cost_price_snapshot' => 750000]);
        $this->financialOrderAt(CarbonImmutable::parse('2026-10-05 12:00:00', config('app.timezone')));
        $this->financialOrderAt(CarbonImmutable::parse('2026-10-10 00:00:00', config('app.timezone')));

        $service = app(FinanceAnalyticsService::class);
        $summary = $service->getPeriodSummary('2026-10-01', '2026-10-09', false, false);
        $this->assertSame(1, $summary['totals']['order_count']);

        $comparison = $service->compareSelectedPeriod('2026-10-01', '2026-10-09', 'month');
        $this->assertLessThan(0, $comparison['profit']['previous']);
        $this->assertNull($comparison['profit']['percentage']);
        $this->assertSame('negative_base', $comparison['profit']['state']);
    }

    public function test_selected_period_time_series_has_equal_daily_intervals_from_first_order(): void
    {
        $this->financialOrderAt(CarbonImmutable::parse('2026-10-02 10:00:00', config('app.timezone')));
        $this->financialOrderAt(CarbonImmutable::parse('2026-10-04 10:00:00', config('app.timezone')));

        $response = $this->actingAs($this->createAdmin())->get(route('admin.finance.revenue', [
            'period' => 'custom',
            'from_date' => '2026-10-01',
            'to_date' => '2026-10-05',
        ]))->assertOk();
        preg_match('/id="finance-breakdown-series">(.*?)<\/script>/s', $response->getContent(), $match);
        $charts = json_decode($match[1] ?? '', true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(['02/10', '03/10', '04/10', '05/10'], $charts['daily_revenue']['labels']);
        $this->assertSame(0, $charts['daily_revenue']['series'][0]['values'][1]);
        $this->assertSame(0, $charts['daily_revenue']['series'][0]['values'][3]);
        $this->assertCount(4, $charts['daily_revenue']['links']);
    }

    private function financialOrderAt(CarbonImmutable $createdAt): \App\Models\Order
    {
        $product = $this->createProduct(['price' => 500000, 'cost_price' => 250000]);
        $order = $this->createOrder(null, [
            'subtotal' => 500000,
            'discount' => 0,
            'shipping_fee' => 0,
            'total' => 500000,
            'total_amount' => 500000,
            'payment_status' => 'paid',
            'order_status' => 'completed',
            'status' => 'COMPLETED',
        ]);
        $order->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 500000,
            'quantity' => 1,
            'subtotal' => 500000,
            'cost_price_snapshot' => 250000,
        ]);
        DB::table('order_costs')->insert([
            'order_id' => $order->id,
            'shipping_cost_actual' => 0,
            'packaging_cost_actual' => 0,
            'payment_gateway_fee_actual' => 0,
            'other_direct_cost' => 0,
            'source' => 'manual',
        ]);
        DB::table('expenses')->insert(['expense_code' => 'CP-' . strtoupper(\Illuminate\Support\Str::random(8)), 'expense_type' => 'other', 'description' => 'Xác nhận không có chi phí vận hành trong test', 'amount' => 0, 'expense_date' => $createdAt->toDateString()]);

        return $order;
    }
}

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

        $quarterDashboard = app(FinanceAnalyticsService::class)->comparisonDashboard($today, $today->startOfQuarter(), $today);
        $this->assertCount(2, $quarterDashboard['weekly_trend']);
        $this->assertCount(1, $quarterDashboard['monthly_trend']);
        $this->assertSame('01/10 – 04/10', $quarterDashboard['weekly_trend'][0]['label']);
        $this->assertSame('05/10 – 09/10', $quarterDashboard['weekly_trend'][1]['label']);

        $this->actingAs($this->createAdmin())->get(route('admin.finance.index'))
            ->assertOk()
            ->assertSee('So sánh doanh thu & lợi nhuận', false)
            ->assertSee('Doanh thu & lợi nhuận theo tuần')
            ->assertSee('Doanh thu & lợi nhuận theo tháng')
            ->assertSee('Theo đúng kỳ báo cáo đang chọn; mỗi cột có một mốc tuần riêng.')
            ->assertSee('Di chuột trên biểu đồ để xem chi tiết từng kỳ.');
    }

    private function financialOrderAt(CarbonImmutable $createdAt): void
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
        ]);
    }
}

<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\FinanceAnalyticsService;
use App\Support\FinanceDateRange;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class FinanceAnalyticsTest extends TestCase
{
    public function test_direct_profit_uses_actual_costs_and_equals_410000(): void
    {
        $order = new Order(['discount' => 50000, 'shipping_fee' => 30000, 'total' => 980000]);
        $order->setRelation('items', new Collection([new OrderItem(['price' => 500000, 'quantity' => 2, 'cost_price_snapshot' => 250000])]));
        $cost = (object) ['shipping_cost_actual' => 40000, 'packaging_cost_actual' => 20000, 'payment_gateway_fee_actual' => 10000, 'other_direct_cost' => 0, 'source' => 'manual'];
        $finance = new FinanceAnalyticsService;
        $this->assertEquals(410000, $finance->calculateDirectOrderProfit($order, $cost));
        $cost->payment_gateway_fee_actual = null;
        $this->assertNull($finance->calculateDirectOrderProfit($order, $cost));
    }

    public function test_system_default_costs_are_estimates_not_actual_profit(): void
    {
        $order = new Order(['discount' => 50000, 'shipping_fee' => 30000, 'total' => 980000]);
        $order->setRelation('items', new Collection([new OrderItem(['price' => 500000, 'quantity' => 2, 'cost_price_snapshot' => 250000])]));
        $cost = (object) ['shipping_cost_actual' => 40000, 'packaging_cost_actual' => 20000, 'payment_gateway_fee_actual' => 10000, 'other_direct_cost' => 0, 'source' => 'system_default'];

        $summary = (new FinanceAnalyticsService)->orderSummary($order, $cost);

        $this->assertNull($summary['order_profit']);
        $this->assertSame(41000000, $summary['estimated_order_profit']);
        $this->assertFalse($summary['financial_data_complete']);
        $this->assertSame('Ước tính theo cấu hình chi phí', $summary['data_status']);
    }

    public function test_finance_day_range_uses_utc_bounds_for_vietnam_timestamp_days(): void
    {
        config(['app.display_timezone' => 'Asia/Ho_Chi_Minh']);

        [$from, $to] = FinanceDateRange::utcBounds('2026-10-16', '2026-10-16');

        $this->assertSame('2026-10-15 17:00:00', $from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-16 17:00:00', $to->format('Y-m-d H:i:s'));
    }
}

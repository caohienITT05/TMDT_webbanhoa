<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\FinanceAnalyticsService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class FinanceAnalyticsTest extends TestCase
{
    public function test_direct_profit_uses_actual_costs_and_equals_410000(): void
    {
        $order = new Order(['discount' => 50000, 'shipping_fee' => 30000, 'total' => 980000]);
        $order->setRelation('items', new Collection([new OrderItem(['price' => 500000, 'quantity' => 2, 'cost_price_snapshot' => 250000])]));
        $cost = (object) ['shipping_cost_actual' => 40000, 'packaging_cost_actual' => 20000, 'payment_gateway_fee_actual' => 10000, 'other_direct_cost' => 0];
        $finance = new FinanceAnalyticsService;
        $this->assertEquals(410000, $finance->calculateDirectOrderProfit($order, $cost));
        $cost->payment_gateway_fee_actual = null;
        $this->assertNull($finance->calculateDirectOrderProfit($order, $cost));
    }
}

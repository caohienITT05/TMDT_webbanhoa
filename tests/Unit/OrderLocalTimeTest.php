<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use Tests\TestCase;

class OrderLocalTimeTest extends TestCase
{
    public function test_order_timestamp_is_presented_in_vietnam_time_without_changing_its_raw_value(): void
    {
        config(['app.timezone' => 'UTC', 'app.display_timezone' => 'Asia/Ho_Chi_Minh']);
        $order = new Order;
        $order->setRawAttributes(['created_at' => '2026-10-16 20:15:00']);

        $this->assertSame('2026-10-17 03:15', $order->created_at_local->format('Y-m-d H:i'));
        $this->assertSame('2026-10-16 20:15:00', $order->getAttributes()['created_at']);
    }

    public function test_order_and_payment_times_convert_across_vietnam_midnight_without_mutating_raw_values(): void
    {
        config(['app.timezone' => 'UTC', 'app.display_timezone' => 'Asia/Ho_Chi_Minh']);
        $beforeMidnight = new Order;
        $beforeMidnight->setRawAttributes(['created_at' => '2026-10-16 16:59:59']);
        $atMidnightPayment = new Payment;
        $atMidnightPayment->setRawAttributes(['paid_at' => '2026-10-16 17:00:00']);

        $this->assertSame('2026-10-16 23:59:59', $beforeMidnight->created_at_local->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-17 00:00:00', $atMidnightPayment->paid_at_local->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-16 16:59:59', $beforeMidnight->getAttributes()['created_at']);
        $this->assertSame('2026-10-16 17:00:00', $atMidnightPayment->getAttributes()['paid_at']);
    }
}

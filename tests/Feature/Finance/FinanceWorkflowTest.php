<?php

namespace Tests\Feature\Finance;

use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesBloomGiftData;
use Tests\TestCase;

class FinanceWorkflowTest extends TestCase
{
    use CreatesBloomGiftData, RefreshDatabase {
        CreatesBloomGiftData::afterRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_completed_transition_finalizes_once_and_preserves_manual_costs(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin)->post(route('admin.finance.settings.save'), [
            'default_shipping_cost' => 40000,
            'default_packaging_cost' => 20000,
            'cod_fee' => 10000,
            'paypal_fee_rate' => 3.5,
            'paypal_fixed_fee' => 5000,
            'currency' => 'VND',
        ])->assertSessionHasNoErrors();

        $product = $this->createProduct(['price' => 500000, 'cost_price' => 250000]);
        $order = $this->createOrder(null, [
            'status' => 'SHIPPING',
            'order_status' => 'shipping',
            'subtotal' => 1000000,
            'total' => 1030000,
            'total_amount' => 1030000,
            'shipping_fee' => 30000,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 500000,
            'quantity' => 2,
            'subtotal' => 1000000,
        ]);
        DB::table('order_costs')->insert([
            'order_id' => $order->id,
            'shipping_cost_actual' => 45000,
            'source' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->patch(route('admin.orders.update-status', $order), ['order_status' => 'completed'])->assertSessionHasNoErrors();
        $first = DB::table('order_costs')->where('order_id', $order->id)->first();
        $this->assertEquals(45000, $first->shipping_cost_actual);
        $this->assertEquals(20000, $first->packaging_cost_actual);
        $this->assertEquals(10000, $first->payment_gateway_fee_actual);
        $this->assertEquals(0, $first->other_direct_cost);
        $this->assertNotNull($first->financial_finalized_at);
        $this->assertSame('manual', $first->source);
        $this->assertSame('paid', $order->fresh()->payment_status);

        $this->patch(route('admin.orders.update-status', $order), ['order_status' => 'completed'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('order_costs', 1);
        $this->assertEquals($first->financial_finalized_at, DB::table('order_costs')->where('order_id', $order->id)->value('financial_finalized_at'));
    }

    public function test_backfill_dry_run_reports_without_writing(): void
    {
        $product = $this->createProduct(['cost_price' => null]);
        $order = $this->createOrder(null, ['order_status' => 'completed', 'status' => 'COMPLETED']);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'subtotal' => $product->price,
        ]);

        $this->artisan('bloomgift:backfill-product-costs', ['--dry-run' => true])->assertSuccessful();
        $this->artisan('bloomgift:backfill-finance', ['--dry-run' => true])->assertSuccessful();
        $this->assertNull($product->fresh()->cost_price);
        $this->assertNull($order->items()->first()->cost_price_snapshot);
        $this->assertDatabaseCount('order_costs', 0);
    }
}

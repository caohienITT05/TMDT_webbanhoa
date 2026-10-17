<?php

namespace Tests\Feature\Inventory;

use App\Models\OrderItem;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesBloomGiftData;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use CreatesBloomGiftData, RefreshDatabase {
        CreatesBloomGiftData::afterRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_order_reserves_stock_once_and_rejects_overselling(): void
    {
        $product = $this->createProduct(['stock' => 1]);
        $first = $this->createOrder();
        $second = $this->createOrder();
        $this->createItem($first->id, $product->id, 1);
        $this->createItem($second->id, $product->id, 1);
        $inventory = app(InventoryService::class);

        $this->assertTrue($inventory->reserveForOrder($first, [[
            'product_id' => $product->id,
            'quantity' => 1,
        ]]));
        $this->assertFalse($inventory->reserveForOrder($first, [[
            'product_id' => $product->id,
            'quantity' => 1,
        ]]));
        $this->assertSame(0, (int) $product->fresh()->stock);

        try {
            $inventory->reserveForOrder($second, [[
                'product_id' => $product->id,
                'quantity' => 1,
            ]]);
            $this->fail('An order must not reserve more stock than is available.');
        } catch (ValidationException) {
            $this->assertSame(0, (int) $product->fresh()->stock);
            $this->assertNull($second->fresh()->inventory_reserved_at);
        }
    }

    public function test_cancelling_pending_cod_releases_stock_only_once(): void
    {
        $product = $this->createProduct(['stock' => 4]);
        $order = $this->createOrder(null, ['payment_method' => 'cod']);
        $this->createItem($order->id, $product->id, 2);
        $inventory = app(InventoryService::class);
        $inventory->reserveForOrder($order, [['product_id' => $product->id, 'quantity' => 2]]);

        $order->update(['order_status' => 'cancelled', 'status' => 'CANCELLED']);
        $this->assertTrue($inventory->releaseForCancellation($order->fresh(), 'pending'));
        $this->assertFalse($inventory->releaseForCancellation($order->fresh(), 'pending'));
        $this->assertSame(4, (int) $product->fresh()->stock);
    }

    public function test_cancelling_pending_paypal_does_not_release_reserved_stock(): void
    {
        $product = $this->createProduct(['stock' => 3]);
        $order = $this->createOrder(null, ['payment_method' => 'paypal']);
        $this->createItem($order->id, $product->id, 1);
        $inventory = app(InventoryService::class);
        $inventory->reserveForOrder($order, [['product_id' => $product->id, 'quantity' => 1]]);

        $order->update(['order_status' => 'cancelled', 'status' => 'CANCELLED']);
        $this->assertFalse($inventory->releaseForCancellation($order->fresh(), 'pending'));
        $this->assertSame(2, (int) $product->fresh()->stock);
        $this->assertNull($order->fresh()->inventory_released_at);
    }

    public function test_historical_order_without_reservation_marker_does_not_change_stock(): void
    {
        $product = $this->createProduct(['stock' => 3]);
        $order = $this->createOrder(null, ['order_status' => 'cancelled']);
        $this->createItem($order->id, $product->id, 5);

        $this->assertFalse(app(InventoryService::class)->releaseForCancellation($order, 'pending'));
        $this->assertSame(3, (int) $product->fresh()->stock);
    }

    private function createItem(int $orderId, int $productId, int $quantity): void
    {
        OrderItem::create([
            'order_id' => $orderId,
            'product_id' => $productId,
            'product_name' => 'Hoa test',
            'price' => 100000,
            'quantity' => $quantity,
            'subtotal' => 100000 * $quantity,
        ]);
    }
}

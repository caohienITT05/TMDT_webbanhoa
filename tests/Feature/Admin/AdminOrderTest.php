<?php

namespace Tests\Feature\Admin;

use App\Models\OrderItem;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesBloomGiftData;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use CreatesBloomGiftData, RefreshDatabase {
        CreatesBloomGiftData::afterRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_admin_can_view_order_index_with_order_code(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder($this->createCustomer());

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));

        $response->assertOk()->assertSee($order->order_code);
    }

    public function test_admin_can_view_order_detail(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder($this->createCustomer());

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));

        $response->assertOk()->assertSee($order->order_code);
    }

    public function test_admin_can_update_order_status_to_shipping(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder($this->createCustomer());

        $response = $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.update-status', $order), ['order_status' => 'shipping']);

        $response->assertRedirect(route('admin.orders.show', $order));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'shipping',
            'status' => 'SHIPPING',
        ]);
    }

    public function test_admin_cancellation_releases_reserved_cod_stock_once(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct(['stock' => 3]);
        $order = $this->createOrder($this->createCustomer(), ['payment_method' => 'cod']);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 350000,
            'quantity' => 2,
            'subtotal' => 700000,
        ]);
        app(InventoryService::class)->reserveForOrder($order, [[
            'product_id' => $product->id,
            'quantity' => 2,
        ]]);
        $this->assertSame(1, (int) $product->fresh()->stock);

        $response = $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.update-status', $order), ['order_status' => 'cancelled']);

        $response->assertRedirect(route('admin.orders.show', $order));
        $this->assertSame(3, (int) $product->fresh()->stock);
        $this->assertNotNull($order->fresh()->inventory_released_at);
    }

    public function test_admin_cannot_mark_pending_paypal_order_completed_as_paid(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder($this->createCustomer(), [
            'payment_method' => 'paypal',
            'payment_status' => 'pending',
        ]);
        $payment = $this->createPayment($order, [
            'payment_method' => 'paypal',
            'transaction_id' => 'PAYPAL-ORDER-PENDING-001',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.update-status', $order), ['order_status' => 'completed']);

        $response->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('error', 'Không thể hoàn tất đơn PayPal khi PayPal chưa xác nhận Capture thành công.');
        $this->assertSame('pending', strtolower((string) $order->fresh()->payment_status));
        $this->assertSame('pending', strtolower((string) $order->fresh()->order_status));
        $this->assertSame('pending', strtolower((string) $payment->fresh()->status));
        $this->assertNull($payment->fresh()->paid_at);
    }

    public function test_customer_cannot_access_admin_orders(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer)->get(route('admin.orders.index'));

        $response->assertForbidden();
    }
}

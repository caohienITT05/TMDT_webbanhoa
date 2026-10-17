<?php

namespace Tests\Feature\Checkout;

use App\Mail\OrderConfirmationMail;
use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesBloomGiftData;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use CreatesBloomGiftData, RefreshDatabase {
        CreatesBloomGiftData::afterRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_checkout_page_displays_authenticated_customers_cart(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct();
        CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 350000,
            'subtotal' => 350000,
        ]);
        $this->createDeliverySlot();

        $response = $this->actingAs($customer)->get(route('checkout.index'));

        $response->assertOk()->assertViewIs('checkout.index')
            ->assertViewHas('cartItems', fn ($items) => $items->contains('product_id', $product->id));
    }

    public function test_customer_can_place_a_cod_order_from_the_cart(): void
    {
        Mail::fake();
        $customer = $this->createCustomer();
        $product = $this->createProduct();
        $slot = $this->createDeliverySlot();
        CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 350000,
            'subtotal' => 700000,
        ]);

        $response = $this->actingAs($customer)->post(route('checkout.process'), $this->checkoutData($slot->id));

        $order = Order::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $customer->id,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'total' => 700000,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 350000,
            'subtotal' => 700000,
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_method' => 'cod',
            'status' => 'pending',
            'amount' => 700000,
        ]);
        $this->assertDatabaseMissing('cart_items', ['user_id' => $customer->id]);
        $this->assertSame(8, (int) $product->fresh()->stock);
        $this->assertNotNull($order->fresh()->inventory_reserved_at);
        Mail::assertSent(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($customer, $order): bool {
            return $mail->hasTo($customer->email) && $mail->order->is($order);
        });
    }

    public function test_checkout_requires_recipient_and_payment_fields(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer)
            ->from(route('checkout.index'))
            ->post(route('checkout.process'), []);

        $response->assertRedirect(route('checkout.index'))
            ->assertSessionHasErrors([
                'recipient_name',
                'recipient_phone',
                'recipient_address',
                'delivery_date',
                'delivery_slot_id',
                'payment_method',
            ]);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_total_includes_shipping_card_and_gift_wrap_fees(): void
    {
        Mail::fake();
        $customer = $this->createCustomer();
        $product = $this->createProduct();
        $slot = $this->createDeliverySlot();
        CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 350000,
            'subtotal' => 700000,
        ]);

        $data = $this->checkoutData($slot->id, [
            'shipping_fee' => 30000,
            'gift_card_fee' => 10000,
            'gift_wrap_fee' => 30000,
        ]);
        $response = $this->actingAs($customer)->post(route('checkout.process'), $data);

        $order = Order::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'subtotal' => 700000,
            'shipping_fee' => 30000,
            'gift_card_fee' => 10000,
            'gift_wrap_fee' => 30000,
            'total' => 770000,
            'total_amount' => 770000,
        ]);
        $this->assertSame(770000.0, (float) $order->total);
        $this->assertSame(10000.0, (float) $order->gift_card_fee);
        $this->assertSame(30000.0, (float) $order->gift_wrap_fee);
    }

    private function checkoutData(int $deliverySlotId, array $overrides = []): array
    {
        return array_merge([
            'recipient_name' => 'Nguyễn Thị Hoa',
            'recipient_phone' => '0901234567',
            'recipient_address' => '123 Đường Hoa, Quận 1, TP.HCM',
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_slot_id' => $deliverySlotId,
            'payment_method' => 'cod',
            'shipping_fee' => 0,
            'gift_card_fee' => 0,
            'gift_wrap_fee' => 0,
        ], $overrides);
    }
}

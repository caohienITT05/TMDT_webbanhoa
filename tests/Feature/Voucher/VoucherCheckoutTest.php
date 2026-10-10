<?php

namespace Tests\Feature\Voucher;

use App\Mail\OrderConfirmationMail;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesBloomGiftData;
use Tests\TestCase;

class VoucherCheckoutTest extends TestCase
{
    use CreatesBloomGiftData, RefreshDatabase {
        CreatesBloomGiftData::afterRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_ajax_voucher_preview_keeps_checkout_state_and_does_not_consume_usage(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct(['price' => 500000]);
        $voucher = $this->createVoucher();
        CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 500000,
            'subtotal' => 500000,
        ]);

        $response = $this->actingAs($customer)->postJson(route('voucher.apply'), [
            'code' => $voucher->code,
            'checkout_mode' => 'cart',
            'shipping_fee' => 30000,
            'gift_card_fee' => 10000,
            'gift_wrap_fee' => 30000,
        ]);

        $response->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('discount_amount', 50000)
            ->assertJsonPath('total', 520000)
            ->assertSessionHas('voucher_id', $voucher->id)
            ->assertSessionHas('voucher_code', $voucher->code);
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'used_count' => 0]);
    }

    public function test_invalid_ajax_voucher_does_not_consume_or_clear_an_applied_voucher(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct(['price' => 500000]);
        $voucher = $this->createVoucher();
        CartItem::create(['user_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 500000, 'subtotal' => 500000]);

        $response = $this->actingAs($customer)->withSession([
            'voucher_id' => $voucher->id,
            'voucher_code' => $voucher->code,
            'voucher_discount' => 50000,
        ])->postJson(route('voucher.apply'), [
            'code' => 'KHONGTONTAI',
            'checkout_mode' => 'cart',
            'shipping_fee' => 0,
            'gift_card_fee' => 0,
            'gift_wrap_fee' => 0,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('valid', false)
            ->assertSessionHas('voucher_id', $voucher->id);
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'used_count' => 0]);
    }

    public function test_cod_order_uses_server_total_and_consumes_the_voucher_once(): void
    {
        Mail::fake();
        $customer = $this->createCustomer();
        $product = $this->createProduct(['price' => 500000]);
        $slot = $this->createDeliverySlot();
        $voucher = $this->createVoucher(['usage_limit' => 1]);
        CartItem::create(['user_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 500000, 'subtotal' => 500000]);

        $response = $this->actingAs($customer)->withSession([
            'voucher_id' => $voucher->id,
            'voucher_code' => $voucher->code,
            'voucher_discount' => 50000,
        ])->post(route('checkout.process'), $this->checkoutData($slot->id, [
            'shipping_fee' => 30000,
            'gift_card_fee' => 10000,
            'gift_wrap_fee' => 30000,
        ]));

        $order = Order::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'discount' => 50000,
            'total' => 520000,
            'total_amount' => 520000,
        ]);
        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame(520000.0, (float) $payment->amount);
        $this->assertSame('pending', $payment->status);
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'used_count' => 1]);
        Mail::assertSent(OrderConfirmationMail::class);
    }

    public function test_cod_checkout_works_when_the_payment_schema_has_no_payload_column(): void
    {
        Schema::table('payments', function ($table): void {
            $table->dropColumn('payload');
        });

        Mail::fake();
        $customer = $this->createCustomer();
        $product = $this->createProduct(['price' => 500000]);
        $slot = $this->createDeliverySlot();
        $voucher = $this->createVoucher(['usage_limit' => 1]);
        CartItem::create(['user_id' => $customer->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 500000, 'subtotal' => 500000]);

        $response = $this->actingAs($customer)->withSession([
            'voucher_id' => $voucher->id,
            'voucher_code' => $voucher->code,
            'voucher_discount' => 50000,
        ])->post(route('checkout.process'), $this->checkoutData($slot->id));

        $order = Order::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'payment_method' => 'cod', 'amount' => 450000]);
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'used_count' => 1]);
    }

    public function test_paypal_success_consumes_a_voucher_only_on_the_first_success_callback(): void
    {
        config()->set('services.paypal.client_id', 'test-client');
        config()->set('services.paypal.client_secret', 'test-secret');
        config()->set('services.paypal.base_url', 'https://paypal.test');
        $customer = $this->createCustomer();
        $voucher = $this->createVoucher(['usage_limit' => 5]);
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $payment = $this->createPayment($order, ['payment_method' => 'paypal']);
        Http::fake([
            'https://paypal.test/v1/oauth2/token' => Http::response(['access_token' => 'access-token'], 200),
            'https://paypal.test/v2/checkout/orders/MOCK-ONE/capture' => Http::response(['status' => 'COMPLETED'], 201),
        ]);

        $first = $this->actingAs($customer)->withSession(['paypal_voucher_for_order_' . $order->id => $voucher->id])->get(route('paypal.success', ['order_id' => $order->id, 'token' => 'MOCK-ONE']));
        $second = $this->actingAs($customer)->get(route('paypal.success', ['order_id' => $order->id, 'token' => 'MOCK-ONE']));

        $first->assertRedirect(route('orders.show', $order));
        $second->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'used_count' => 1]);
        $this->assertSame('completed', $payment->fresh()->status);
    }

    public function test_failed_paypal_capture_does_not_consume_the_voucher(): void
    {
        config()->set('services.paypal.client_id', 'test-client');
        config()->set('services.paypal.client_secret', 'test-secret');
        config()->set('services.paypal.base_url', 'https://paypal.test');
        $customer = $this->createCustomer();
        $voucher = $this->createVoucher(['usage_limit' => 1]);
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $payment = $this->createPayment($order, ['payment_method' => 'paypal']);
        Http::fake([
            'https://paypal.test/v1/oauth2/token' => Http::response(['access_token' => 'access-token'], 200),
            'https://paypal.test/v2/checkout/orders/FAILED-CAPTURE/capture' => Http::response(['name' => 'UNPROCESSABLE_ENTITY'], 422),
        ]);

        $this->actingAs($customer)->withSession(['paypal_voucher_for_order_' . $order->id => $voucher->id])->get(route('paypal.success', [
            'order_id' => $order->id,
            'token' => 'FAILED-CAPTURE',
        ]))->assertRedirect(route('checkout.index'));

        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'used_count' => 0]);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    private function createVoucher(array $overrides = []): Voucher
    {
        return Voucher::create(array_merge([
            'code' => 'GIAM50K',
            'discount_type' => 'fixed',
            'discount_value' => 50000,
            'min_order_value' => 0,
            'expires_at' => now()->addDay(),
            'name' => 'Giảm 50.000đ',
            'type' => 'fixed',
            'value' => 50000,
            'min_order_amount' => 0,
            'max_discount' => null,
            'start_time' => now()->subDay(),
            'end_time' => now()->addDay(),
            'usage_limit' => 5,
            'used_count' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function checkoutData(int $deliverySlotId, array $overrides = []): array
    {
        return array_merge([
            'recipient_name' => 'Nguyễn Văn A',
            'recipient_phone' => '0912345678',
            'recipient_address' => 'Hà Nội',
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_slot_id' => $deliverySlotId,
            'payment_method' => 'cod',
            'shipping_fee' => 0,
            'gift_card_fee' => 0,
            'gift_wrap_fee' => 0,
            'card_message' => 'Chúc mừng sinh nhật',
            'order_note' => 'Giao giờ hành chính',
        ], $overrides);
    }
}

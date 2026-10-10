<?php

namespace Tests\Feature\Payment;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesBloomGiftData;
use Tests\TestCase;

class PayPalCheckoutTest extends TestCase
{
    use CreatesBloomGiftData, RefreshDatabase {
        CreatesBloomGiftData::afterRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_paypal_create_redirects_to_approval_url_with_fake_http_responses(): void
    {
        config()->set('services.paypal.client_id', 'test-client');
        config()->set('services.paypal.client_secret', 'test-secret');
        config()->set('services.paypal.base_url', 'https://paypal.test');

        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $this->createPayment($order, ['payment_method' => 'paypal']);

        Http::fake([
            'https://paypal.test/v1/oauth2/token' => Http::response(['access_token' => 'access-token'], 200),
            'https://paypal.test/v2/checkout/orders' => Http::response([
                'id' => 'TEST-PAYPAL-ORDER-001',
                'links' => [
                    ['rel' => 'approve', 'href' => 'https://paypal.test/approve/TEST-PAYPAL-ORDER-001'],
                ],
            ], 201),
        ]);

        $response = $this->actingAs($customer)->get(route('paypal.create', $order));

        $response->assertRedirect('https://paypal.test/approve/TEST-PAYPAL-ORDER-001');
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://paypal.test/v1/oauth2/token');
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://paypal.test/v2/checkout/orders'
            && data_get($request->data(), 'purchase_units.0.amount.value') === number_format((float) $order->total / 26054, 2, '.', ''));
    }

    public function test_paypal_success_captures_payment_with_fake_http_and_confirms_order(): void
    {
        config()->set('services.paypal.client_id', 'test-client');
        config()->set('services.paypal.client_secret', 'test-secret');
        config()->set('services.paypal.base_url', 'https://paypal.test');

        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $payment = $this->createPayment($order, ['payment_method' => 'paypal']);
        $token = 'TEST-PAYPAL-ORDER-001';

        Http::fake([
            'https://paypal.test/v1/oauth2/token' => Http::response(['access_token' => 'access-token'], 200),
            "https://paypal.test/v2/checkout/orders/{$token}/capture" => Http::response(['status' => 'COMPLETED'], 201),
        ]);

        $response = $this->actingAs($customer)->get(route('paypal.success', [
            'order_id' => $order->id,
            'token' => $token,
        ]));

        $response->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
            'status' => 'CONFIRMED',
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'completed',
            'transaction_id' => $token,
        ]);
        $this->assertNotNull($payment->fresh()->paid_at);
        Http::assertSent(fn (ClientRequest $request) => $request->url() === "https://paypal.test/v2/checkout/orders/{$token}/capture");
    }

    public function test_paypal_cancel_does_not_change_pending_order_or_payment(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $payment = $this->createPayment($order, ['payment_method' => 'paypal']);

        $response = $this->actingAs($customer)->get(route('paypal.cancel', ['order_id' => $order->id]));

        $response->assertRedirect(route('checkout.index'));
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('pending', $order->fresh()->order_status);
        $this->assertSame('pending', $payment->fresh()->status);
    }
}

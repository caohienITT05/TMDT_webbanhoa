<?php

namespace Tests\Feature\Payment;

use App\Models\Order;
use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');

        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $payment = $this->createPayment($order, ['payment_method' => 'paypal']);

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'access-token'], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'TEST-PAYPAL-ORDER-001',
                'links' => [
                    ['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=TEST-PAYPAL-ORDER-001'],
                ],
            ], 201),
        ]);

        $response = $this->actingAs($customer)->get(route('paypal.create', $order));

        $response->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=TEST-PAYPAL-ORDER-001');
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'transaction_id' => 'TEST-PAYPAL-ORDER-001', 'status' => 'pending']);
        $this->assertSame('TEST-PAYPAL-ORDER-001', $payment->fresh()->transaction_id);
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://api-m.sandbox.paypal.com/v1/oauth2/token');
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://api-m.sandbox.paypal.com/v2/checkout/orders'
            && $request->hasHeader('PayPal-Request-Id', 'bg-'.substr(hash('sha256', 'bloomgift-paypal-create:'.$payment->id), 0, 32))
            && data_get($request->data(), 'purchase_units.0.amount.value') === number_format((float) $order->total / 26054, 2, '.', ''));
    }

    public function test_paypal_create_accepts_uppercase_pending_status_from_mysql_enum(): void
    {
        config()->set('services.paypal.client_id', 'test-client');
        config()->set('services.paypal.client_secret', 'test-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');

        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $payment = $this->createPayment($order, ['payment_method' => 'paypal', 'status' => 'PENDING']);
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'access-token'], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'UPPERCASE-PENDING-ORDER',
                'links' => [['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=UPPERCASE-PENDING-ORDER']],
            ], 201),
        ]);

        $this->actingAs($customer)->get(route('paypal.create', $order))
            ->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=UPPERCASE-PENDING-ORDER');

        $this->assertSame('UPPERCASE-PENDING-ORDER', $payment->fresh()->transaction_id);
        Http::assertSentCount(2);
    }

    public function test_duplicate_callback_is_idempotent_with_uppercase_completed_status(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, [
            'payment_method' => 'paypal',
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
            'status' => 'CONFIRMED',
        ]);
        $payment = $this->createPayment($order, [
            'payment_method' => 'paypal',
            'status' => 'COMPLETED',
            'transaction_id' => 'CAPTURE-UPPERCASE-001',
        ]);
        Http::fake();

        $this->actingAs($customer)->get(route('paypal.success', [
            'order_id' => $order->id,
            'token' => 'PAYPAL-ORDER-UPPERCASE-001',
        ]))->assertRedirect(route('orders.show', $order));

        $this->assertSame('COMPLETED', $payment->fresh()->status);
        $this->assertSame('CAPTURE-UPPERCASE-001', $payment->fresh()->transaction_id);
        Http::assertNothingSent();
    }

    public function test_paypal_success_captures_payment_with_fake_http_and_confirms_order(): void
    {
        config()->set('services.paypal.client_id', 'test-client');
        config()->set('services.paypal.client_secret', 'test-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');

        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $product = $this->createProduct(['stock' => 3]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 350000,
            'quantity' => 1,
            'subtotal' => 350000,
        ]);
        app(InventoryService::class)->reserveForOrder($order, [['product_id' => $product->id, 'quantity' => 1]]);
        $token = 'TEST-PAYPAL-ORDER-001';
        $payment = $this->createPayment($order, ['payment_method' => 'paypal', 'transaction_id' => $token]);
        $usdAmount = number_format(round((float) $order->total / 26054, 2), 2, '.', '');
        $paypalOrder = $this->paypalOrderPayload($order, $token);

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'access-token'], 200),
            "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}" => Http::response($paypalOrder, 200),
            "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}/capture" => Http::response([
                'id' => $token,
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'reference_id' => $order->order_code,
                    'payments' => ['captures' => [[
                        'id' => 'TEST-CAPTURE-001',
                        'status' => 'COMPLETED',
                        'amount' => ['currency_code' => 'USD', 'value' => $usdAmount],
                    ]]],
                ]],
            ], 201),
        ]);

        $response = $this->actingAs($customer)->get(route('paypal.success', [
            'order_id' => $order->id,
            'token' => $token,
        ]));

        $response->assertRedirect(route('orders.show', $order));
        $this->actingAs($customer)->get(route('paypal.success', [
            'order_id' => $order->id,
            'token' => $token,
        ]))->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
            'status' => 'CONFIRMED',
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'completed',
            'transaction_id' => 'TEST-CAPTURE-001',
        ]);
        $this->assertNotNull($payment->fresh()->paid_at);
        $this->assertSame(2, (int) $product->fresh()->stock, 'A completed PayPal capture keeps its reserved stock sold.');
        $this->assertNotNull($order->fresh()->inventory_reserved_at);
        Http::assertSent(fn (ClientRequest $request) => $request->url() === "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}/capture");
        Http::assertSent(fn (ClientRequest $request) => $request->url() === "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}/capture"
            && $request->body() === '{}'
            && $request->hasHeader('PayPal-Request-Id', 'bgc-'.substr(hash('sha256', 'bloomgift-paypal-capture:'.$order->id), 0, 32)));
        Http::assertSentCount(3);
    }

    public function test_paypal_cancel_does_not_change_pending_order_or_payment(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $payment = $this->createPayment($order, ['payment_method' => 'paypal']);

        $response = $this->actingAs($customer)->get(route('paypal.cancel', ['order_id' => $order->id]));

        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('pending', $order->fresh()->order_status);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_checkout_paypal_failure_keeps_cart_and_retry_reuses_the_same_order(): void
    {
        Mail::fake();
        config()->set('services.paypal.client_id', 'test-client');
        config()->set('services.paypal.client_secret', 'test-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');

        $customer = $this->createCustomer();
        $product = $this->createProduct();
        $slot = $this->createDeliverySlot();
        CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 350000,
            'subtotal' => 350000,
        ]);

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::sequence()
                ->push(['name' => 'INVALID_CLIENT', 'debug_id' => 'SAFE-DEBUG-ID'], 401)
                ->push(['access_token' => 'test-access-token'], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'RETRY-PAYPAL-ORDER-001',
                'links' => [['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=RETRY-PAYPAL-ORDER-001']],
            ], 201),
        ]);

        $checkout = $this->checkoutData($slot->id, ['payment_method' => 'paypal']);
        $first = $this->actingAs($customer)->post(route('checkout.process'), $checkout);
        $order = Order::query()->latest('id')->firstOrFail();
        $first->assertRedirect(route('paypal.create', ['order' => $order->id]));
        $this->assertSame(9, (int) $product->fresh()->stock);
        $this->assertNotNull($order->fresh()->inventory_reserved_at);

        $failed = $this->get(route('paypal.create', $order));
        $failed->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('cart_items', ['user_id' => $customer->id, 'product_id' => $product->id]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'pending', 'payment_method' => 'paypal']);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'pending']);

        $retry = $this->post(route('checkout.process'), $checkout);
        $retry->assertRedirect(route('paypal.create', ['order' => $order->id]));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(9, (int) $product->fresh()->stock, 'A checkout retry must not reserve inventory twice.');

        $approval = $this->get(route('paypal.create', $order));
        $approval->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=RETRY-PAYPAL-ORDER-001');
        $this->assertDatabaseMissing('cart_items', ['user_id' => $customer->id]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'transaction_id' => 'RETRY-PAYPAL-ORDER-001', 'status' => 'pending']);
        $this->assertSame(9, (int) $product->fresh()->stock, 'PayPal pending approval keeps the reserved stock.');
        Http::assertSentCount(3);
    }

    public function test_paypal_order_creation_retries_use_same_idempotency_key(): void
    {
        config()->set('services.paypal.client_id', 'test-client');
        config()->set('services.paypal.client_secret', 'test-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');

        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $payment = $this->createPayment($order, ['payment_method' => 'paypal']);
        $existingOrder = $this->paypalOrderPayload($order, 'IDEMPOTENT-PAYPAL-ORDER', 'CREATED');
        $existingOrder['links'] = [[
            'rel' => 'approve',
            'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=IDEMPOTENT-PAYPAL-ORDER',
        ]];
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test-access-token'], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'IDEMPOTENT-PAYPAL-ORDER',
                'links' => [['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=IDEMPOTENT-PAYPAL-ORDER']],
            ], 201),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/IDEMPOTENT-PAYPAL-ORDER' => Http::response($existingOrder, 200),
        ]);

        $this->actingAs($customer)->get(route('paypal.create', $order))->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=IDEMPOTENT-PAYPAL-ORDER');
        $this->get(route('paypal.create', $order))->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=IDEMPOTENT-PAYPAL-ORDER');

        $requestIds = [];
        Http::assertSent(function (ClientRequest $request) use (&$requestIds): bool {
            if ($request->url() !== 'https://api-m.sandbox.paypal.com/v2/checkout/orders') {
                return false;
            }
            $requestIds[] = $request->header('PayPal-Request-Id')[0] ?? null;

            return true;
        });
        $this->assertCount(1, $requestIds);
        $this->assertCount(1, array_unique($requestIds));
        Http::assertSentCount(4);
        $this->assertSame('IDEMPOTENT-PAYPAL-ORDER', $payment->fresh()->transaction_id);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_paypal_retry_does_not_recreate_order_when_capture_is_pending(): void
    {
        config()->set('services.paypal.client_id', 'test-client');
        config()->set('services.paypal.client_secret', 'test-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');

        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $token = 'PENDING-CAPTURE-RETRY';
        $payment = $this->createPayment($order, ['payment_method' => 'paypal', 'transaction_id' => $token]);
        $usdAmount = number_format(round((float) $order->total / 26054, 2), 2, '.', '');
        $pendingOrder = $this->paypalOrderPayload($order, $token, 'COMPLETED', [[
            'id' => 'PENDING-CAPTURE-RETRY-001',
            'status' => 'PENDING',
            'amount' => ['currency_code' => 'USD', 'value' => $usdAmount],
        ]]);

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'access-token'], 200),
            "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}" => Http::response($pendingOrder, 200),
        ]);

        $this->actingAs($customer)->get(route('paypal.create', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'PENDING')
                && str_contains($message, 'chưa thể xác nhận đã thanh toán')
                && str_contains($message, 'Không thanh toán lại.'));

        $this->assertSame('pending', strtolower((string) $payment->fresh()->status));
        $this->assertSame($token, $payment->fresh()->transaction_id);
        $this->assertSame('pending', strtolower((string) $order->fresh()->payment_status));
        Http::assertSentCount(2);
        Http::assertNotSent(fn (ClientRequest $request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/v2/checkout/orders'));
        Http::assertNotSent(fn (ClientRequest $request) => str_ends_with($request->url(), "/{$token}/capture"));
    }

    public function test_paypal_does_not_redirect_when_order_id_cannot_be_persisted(): void
    {
        config()->set('services.paypal.client_id', 'test-client');
        config()->set('services.paypal.client_secret', 'test-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');

        $customer = $this->createCustomer();
        $product = $this->createProduct();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $payment = $this->createPayment($order, ['payment_method' => 'paypal']);
        CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 350000,
            'subtotal' => 350000,
        ]);
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test-access-token'], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'UNPERSISTED-PAYPAL-ORDER',
                'links' => [['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=UNPERSISTED-PAYPAL-ORDER']],
            ], 201),
        ]);
        Payment::saving(static function (Payment $payment): ?bool {
            return $payment->transaction_id === 'UNPERSISTED-PAYPAL-ORDER' ? false : null;
        });

        $this->actingAs($customer)->get(route('paypal.create', $order))->assertRedirect(route('orders.show', $order));
        $this->assertNull($payment->fresh()->transaction_id);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseHas('cart_items', ['user_id' => $customer->id, 'product_id' => $product->id]);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_paypal_capture_status_and_amount_must_match_before_payment_is_marked_paid(): void
    {
        config()->set('services.paypal.client_id', 'test-client');
        config()->set('services.paypal.client_secret', 'test-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $payment = $this->createPayment($order, ['payment_method' => 'paypal', 'transaction_id' => 'PENDING-ORDER-001']);
        $usdAmount = number_format(round((float) $order->total / 26054, 2) + 1, 2, '.', '');

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test-access-token'], 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/PENDING-ORDER-001' => Http::response($this->paypalOrderPayload($order, 'PENDING-ORDER-001'), 200),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/PENDING-ORDER-001/capture' => Http::response([
                'id' => 'PENDING-ORDER-001',
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'reference_id' => $order->order_code,
                    'payments' => ['captures' => [[
                        'id' => 'WRONG-AMOUNT-CAPTURE',
                        'amount' => ['currency_code' => 'USD', 'value' => $usdAmount],
                    ]]],
                ]],
            ], 201),
        ]);

        $this->actingAs($customer)->get(route('paypal.success', ['order_id' => $order->id, 'token' => 'PENDING-ORDER-001']))
            ->assertRedirect(route('orders.show', $order));
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_approved_order_stays_pending_when_capture_is_rejected_and_is_looked_up(): void
    {
        config()->set('services.paypal.client_id', 'test-client');
        config()->set('services.paypal.client_secret', 'test-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $token = 'APPROVED-CAPTURE-REJECTED';
        $payment = $this->createPayment($order, ['payment_method' => 'paypal', 'transaction_id' => $token]);
        $usdAmount = number_format(round((float) $order->total / 26054, 2), 2, '.', '');

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test-access-token'], 200),
            "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}/capture" => Http::response([
                'name' => 'INVALID_REQUEST',
                'details' => [['issue' => 'MALFORMED_REQUEST_JSON']],
            ], 400),
            "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}" => Http::sequence()
                ->push($this->paypalOrderPayload($order, $token), 200)
                ->push($this->paypalOrderPayload($order, $token), 200),
        ]);

        $this->actingAs($customer)->get(route('paypal.success', ['order_id' => $order->id, 'token' => $token]))
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame('pending', strtolower((string) $payment->fresh()->status));
        $this->assertSame('pending', $order->fresh()->payment_status);
        Http::assertSent(fn (ClientRequest $request) => $request->url() === "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}/capture"
            && $request->body() === '{}'
            && $request->hasHeader('PayPal-Request-Id', 'bgc-'.substr(hash('sha256', 'bloomgift-paypal-capture:'.$order->id), 0, 32)));
        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'GET'
            && $request->url() === "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}");
        Http::assertSentCount(4);
    }

    public function test_capture_pending_is_not_marked_paid_even_if_paypal_order_is_completed(): void
    {
        config()->set('services.paypal.client_id', 'test-client');
        config()->set('services.paypal.client_secret', 'test-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $token = 'CAPTURE-SETTLEMENT-PENDING';
        $payment = $this->createPayment($order, ['payment_method' => 'paypal', 'transaction_id' => $token]);
        $product = $this->createProduct(['stock' => 3]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 350000,
            'quantity' => 1,
            'subtotal' => 350000,
        ]);
        app(InventoryService::class)->reserveForOrder($order, [['product_id' => $product->id, 'quantity' => 1]]);
        $usdAmount = number_format(round((float) $order->total / 26054, 2), 2, '.', '');
        $pendingOrder = $this->paypalOrderPayload($order, $token, 'COMPLETED', [[
            'id' => 'PENDING-CAPTURE-001',
            'status' => 'PENDING',
            'amount' => ['currency_code' => 'USD', 'value' => $usdAmount],
        ]]);

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test-access-token'], 200),
            "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}" => Http::sequence()
                ->push($this->paypalOrderPayload($order, $token), 200)
                ->push($pendingOrder, 200)
                ->push($pendingOrder, 200),
            "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}/capture" => Http::response($pendingOrder, 201),
        ]);

        $this->actingAs($customer)->followingRedirects()
            ->get(route('paypal.success', ['order_id' => $order->id, 'token' => $token]))
            ->assertOk()
            ->assertSee('PayPal hiện trả Capture ở trạng thái PENDING (chưa COMPLETED), nên BloomGift chưa thể xác nhận đã thanh toán. Hãy bấm “Kiểm tra hoặc tiếp tục PayPal” để truy vấn trạng thái mới nhất; chỉ tải lại trang sẽ không làm Capture được duyệt. Không thanh toán lại. Nếu Capture đang PENDING_REVIEW, PayPal Sandbox cần hoàn tất việc kiểm tra trước khi đơn có thể chuyển sang đã thanh toán.');
        $this->actingAs($customer)->get(route('paypal.success', ['order_id' => $order->id, 'token' => $token]))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('error', 'PayPal hiện trả Capture ở trạng thái PENDING (chưa COMPLETED), nên BloomGift chưa thể xác nhận đã thanh toán. Hãy bấm “Kiểm tra hoặc tiếp tục PayPal” để truy vấn trạng thái mới nhất; chỉ tải lại trang sẽ không làm Capture được duyệt. Không thanh toán lại. Nếu Capture đang PENDING_REVIEW, PayPal Sandbox cần hoàn tất việc kiểm tra trước khi đơn có thể chuyển sang đã thanh toán.');

        $this->assertSame('pending', strtolower((string) $payment->fresh()->status));
        $this->assertSame($token, $payment->fresh()->transaction_id);
        $this->assertNull($payment->fresh()->paid_at);
        $this->assertSame('pending', strtolower((string) $order->fresh()->payment_status));
        $this->assertSame('pending', strtolower((string) $order->fresh()->order_status));
        $this->assertSame(2, (int) $product->fresh()->stock, 'A pending PayPal capture continues to hold stock.');
        $this->assertNotNull($order->fresh()->inventory_reserved_at);
        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'POST'
            && str_ends_with($request->url(), "/{$token}/capture")
            && $request->body() === '{}');
        Http::assertSentCount(6);
        $captureRequests = 0;
        Http::assertSent(function (ClientRequest $request) use (&$captureRequests, $token): bool {
            if (! str_ends_with($request->url(), "/{$token}/capture")) {
                return false;
            }

            $captureRequests++;

            return true;
        });
        $this->assertSame(1, $captureRequests);
    }

    public function test_paypal_does_not_capture_when_order_recipient_does_not_match_configured_merchant(): void
    {
        config()->set('services.paypal.client_id', 'test-client');
        config()->set('services.paypal.client_secret', 'test-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');
        config()->set('services.paypal.merchant_id', 'EXPECTED-MERCHANT');
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $token = 'WRONG-RECIPIENT-ORDER';
        $payment = $this->createPayment($order, ['payment_method' => 'paypal', 'transaction_id' => $token]);
        $wrongRecipientOrder = $this->paypalOrderPayload($order, $token);
        $wrongRecipientOrder['purchase_units'][0]['payee']['merchant_id'] = 'OTHER-MERCHANT';
        config()->set('services.paypal.merchant_id', 'EXPECTED-MERCHANT');

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test-access-token'], 200),
            "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}" => Http::response($wrongRecipientOrder, 200),
        ]);

        $this->actingAs($customer)->get(route('paypal.success', ['order_id' => $order->id, 'token' => $token]))
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame('pending', strtolower((string) $payment->fresh()->status));
        $this->assertSame('pending', strtolower((string) $order->fresh()->payment_status));
        Http::assertSentCount(2);
        Http::assertNotSent(fn (ClientRequest $request) => str_ends_with($request->url(), "/{$token}/capture"));
    }

    public function test_already_completed_capture_is_reconciled_from_paypal_order_without_capturing_again(): void
    {
        config()->set('services.paypal.client_id', 'test-client');
        config()->set('services.paypal.client_secret', 'test-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');

        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $token = 'ALREADY-CAPTURED-ORDER';
        $payment = $this->createPayment($order, ['payment_method' => 'paypal', 'transaction_id' => $token]);
        $usdAmount = number_format(round((float) $order->total / 26054, 2), 2, '.', '');
        $alreadyCompletedOrder = $this->paypalOrderPayload($order, $token, 'COMPLETED', [[
            'id' => 'CAPTURE-ALREADY-DONE',
            'status' => 'COMPLETED',
            'amount' => ['currency_code' => 'USD', 'value' => $usdAmount],
        ]]);

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'access-token'], 200),
            "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}/capture" => Http::response([
                'name' => 'UNPROCESSABLE_ENTITY', 'details' => [['issue' => 'ORDER_ALREADY_CAPTURED']],
            ], 422),
            "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}" => Http::response($alreadyCompletedOrder, 200),
        ]);

        $this->actingAs($customer)->get(route('paypal.success', ['order_id' => $order->id, 'token' => $token]))
            ->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'completed',
            'transaction_id' => 'CAPTURE-ALREADY-DONE',
        ]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
        Http::assertSentCount(2);
        Http::assertNotSent(fn (ClientRequest $request) => $request->url() === "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}/capture");
        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'GET'
            && $request->url() === "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$token}");
    }

    private function checkoutData(int $deliverySlotId, array $overrides = []): array
    {
        return array_merge([
            'recipient_name' => 'Khách QA PayPal',
            'recipient_phone' => '0900000000',
            'recipient_address' => 'Địa chỉ thử nghiệm',
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_slot_id' => $deliverySlotId,
            'payment_method' => 'paypal',
            'shipping_fee' => 0,
            'gift_card_fee' => 0,
            'gift_wrap_fee' => 0,
        ], $overrides);
    }

    private function paypalOrderPayload(Order $order, string $token, string $status = 'APPROVED', array $captures = []): array
    {
        config()->set('services.paypal.merchant_id', 'MERCHANT-TEST-001');

        $payload = [
            'id' => $token,
            'intent' => 'CAPTURE',
            'status' => $status,
            'purchase_units' => [[
                'reference_id' => $order->order_code,
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format(round((float) $order->total / 26054, 2), 2, '.', ''),
                ],
                'payee' => ['merchant_id' => 'MERCHANT-TEST-001'],
            ]],
        ];

        if ($captures !== []) {
            $payload['purchase_units'][0]['payments'] = ['captures' => $captures];
        }

        return $payload;
    }
}

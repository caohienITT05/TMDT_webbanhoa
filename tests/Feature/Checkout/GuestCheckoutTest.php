<?php

namespace Tests\Feature\Checkout;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesBloomGiftData;
use Tests\TestCase;

class GuestCheckoutTest extends TestCase
{
    use CreatesBloomGiftData, RefreshDatabase {
        CreatesBloomGiftData::afterRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_guest_can_browse_and_keep_a_session_cart_but_checkout_and_paypal_require_login(): void
    {
        $product = $this->createProduct();
        $this->get(route('home'))->assertOk();
        $this->get(route('products.index', ['min_rating' => 4]))->assertOk();
        $this->get(route('product.detail', $product->slug))->assertOk();
        $this->get(route('cart.index'))->assertOk();
        $this->post(route('cart.add', ['id' => $product->id]), ['quantity' => 1])->assertRedirect();
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'user_id' => null]);

        $this->get(route('buy-now.checkout'))->assertRedirect(route('login'));
        $this->get(route('checkout.index'))->assertRedirect(route('login'));
        $this->get(route('checkout'))->assertRedirect(route('login'));
        $this->post(route('checkout.store'), ['payment_method' => 'cod'])->assertRedirect(route('login'));
        $this->post(route('checkout.process'), ['payment_method' => 'paypal'])->assertRedirect(route('login'));
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);

        $legacyGuestOrder = $this->createOrder(null, ['payment_method' => 'paypal']);
        $payment = $this->createPayment($legacyGuestOrder);
        $this->get(route('paypal.create', $legacyGuestOrder))->assertRedirect(route('login'));
        $this->get(route('paypal.success', ['order_id' => $legacyGuestOrder->id, 'token' => 'UNTRUSTED']))->assertRedirect(route('login'));
        $this->get(route('paypal.cancel', ['order_id' => $legacyGuestOrder->id]))->assertRedirect(route('login'));
        $this->assertDatabaseHas('orders', ['id' => $legacyGuestOrder->id, 'payment_status' => 'pending']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_guest_cart_is_assigned_to_customer_after_login_and_intended_checkout_resumes(): void
    {
        $customer = $this->createCustomer();
        $product = $this->createProduct();
        $this->post(route('cart.add', ['id' => $product->id]), ['quantity' => 2])->assertRedirect();
        $this->get(route('checkout.index'))->assertRedirect(route('login'));

        $this->post(route('login'), ['email' => $customer->email, 'password' => 'password'])
            ->assertRedirect(route('checkout.index'));

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'session_id' => null,
        ]);
        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertViewHas('cartItems', fn ($items) => $items->contains('product_id', $product->id));
    }

    public function test_guest_cart_is_assigned_to_new_customer_after_registration_and_checkout_resumes(): void
    {
        $product = $this->createProduct();
        $this->post(route('cart.add', ['id' => $product->id]), ['quantity' => 1])->assertRedirect();
        $this->get(route('checkout.index'))->assertRedirect(route('login'));

        $this->post(route('register'), [
            'name' => 'Khách mới',
            'email' => 'new-customer@example.test',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertRedirect(route('checkout.index'));

        $customer = \App\Models\User::query()->where('email', 'new-customer@example.test')->firstOrFail();
        $this->assertSame('customer', $customer->role);
        $this->assertTrue((bool) $customer->is_active);
        $this->assertAuthenticatedAs($customer);
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'session_id' => null,
        ]);
        $this->get(route('checkout.index'))->assertOk();
    }

    public function test_admin_cannot_use_customer_checkout_or_create_an_order(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin)->get(route('checkout.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('checkout.store'), ['payment_method' => 'cod'])->assertForbidden();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_authenticated_customer_can_complete_the_paypal_sandbox_mock_callback(): void
    {
        config(['services.paypal.client_id' => null, 'services.paypal.client_secret' => null]);
        $customer = $this->createCustomer();
        $order = $this->createOrder($customer, ['payment_method' => 'paypal']);
        $payment = $this->createPayment($order);

        $approval = $this->actingAs($customer)->get(route('paypal.create', $order));
        $approval->assertRedirect();
        $callbackUrl = $approval->headers->get('Location');
        $this->assertStringContainsString('/paypal/success', $callbackUrl);

        $this->get($callbackUrl)->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'completed']);
    }

    public function test_customer_cannot_open_another_accounts_paypal_order(): void
    {
        $owner = $this->createCustomer();
        $otherCustomer = $this->createCustomer();
        $order = $this->createOrder($owner, ['payment_method' => 'paypal']);
        $payment = $this->createPayment($order);

        $this->actingAs($otherCustomer)->get(route('paypal.create', $order))->assertForbidden();
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);
    }
}

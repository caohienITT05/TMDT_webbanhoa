<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class GuestOrderController extends Controller
{
    public function show(Request $request, Order $order, string $token)
    {
        abort_unless($order->user_id === null && $order->guest_access_token_hash && $order->guest_access_token_expires_at?->isFuture(), 404);
        abort_unless(hash_equals($order->guest_access_token_hash, hash('sha256', $token)), 404);
        $order->load(['deliverySlot', 'payment', 'orderItems.product', 'items.product']);

        return view('Customer.order-show', compact('order'));
    }
}

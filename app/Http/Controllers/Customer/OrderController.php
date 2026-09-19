<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display one order belonging to the authenticated customer.
     */
    public function show(Request $request, Order $order)
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 403);

        $order->load([
            'deliverySlot',
            'payment',
            'orderItems.product',
            'items.product',
        ]);

        return view('Customer.order-show', compact('order'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function index()
    {
        $deliverySlots = DeliverySlot::where('is_active', true)->get();

        return view('checkout.index', compact('deliverySlots'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'recipient_address' => 'required|string',
            'delivery_date' => 'required|date|after_or_equal:today',
            'delivery_slot_id' => 'required|exists:delivery_slots,id',
            'payment_method' => 'required|in:cod,paypal',
            'note' => 'nullable|string',
        ]);

        // Dữ liệu sản phẩm mẫu
        $products = [
            [
                'product_id' => 1,
                'product_name' => 'Hoa hồng đỏ',
                'price' => 350000,
                'quantity' => 1,
            ],
            [
                'product_id' => 2,
                'product_name' => 'Hoa baby',
                'price' => 150000,
                'quantity' => 1,
            ],
        ];

        $subtotal = 500000;
        $discount = 0;
        $shippingFee = 30000;
        $total = $subtotal - $discount + $shippingFee;

        $order = DB::transaction(function () use (
            $validated,
            $products,
            $subtotal,
            $discount,
            $shippingFee,
            $total
        ) {
            $orderCode = 'BG'
                . now()->format('YmdHis')
                . strtoupper(Str::random(4));

            $order = Order::create([
                'order_code' => $orderCode,

                'recipient_name' => $validated['recipient_name'],
                'recipient_phone' => $validated['recipient_phone'],
                'recipient_address' => $validated['recipient_address'],

                'delivery_date' => $validated['delivery_date'],
                'delivery_slot_id' => $validated['delivery_slot_id'],

                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_fee' => $shippingFee,
                'total' => $total,

                'payment_method' => $validated['payment_method'],

                'payment_status' => 'pending',
                'order_status' => 'pending',

                'note' => $validated['note'] ?? null,
            ]);

            foreach ($products as $product) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product['product_id'],
                    'product_name' => $product['product_name'],
                    'price' => $product['price'],
                    'quantity' => $product['quantity'],
                    'subtotal' => $product['price'] * $product['quantity'],
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $validated['payment_method'],
                'transaction_id' => null,
                'amount' => $total,
                'status' => 'pending',
                'paid_at' => null,
            ]);

            return $order;
        });

        /*
         * COD:
         * Tạo đơn xong -> xem chi tiết đơn hàng.
         */
        if ($validated['payment_method'] === 'cod') {
            return redirect()
                ->route('orders.show', $order)
                ->with('success', 'Đặt hàng thành công!');
        }

        /*
         * PayPal:
         * Tạo đơn xong -> chuyển sang PayPal Sandbox.
         */
        return redirect()
            ->route('paypal.create', $order);
    }
}

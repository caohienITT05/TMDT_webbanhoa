<?php

namespace App\Http\Controllers;

use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function index()
    {
        $deliverySlots = DeliverySlot::where('is_active', true)->get();

        // Tạm thời dùng 2 sản phẩm demo từ bảng products của TV1.
        // Sau này thay bằng dữ liệu Cart của TV3.
        $productIds = [1, 2];

        $products = Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->get();

        $products = $products->map(function ($product) {
            $product->checkout_price = $product->sale_price ?? $product->price;
            $product->checkout_quantity = 1;

            return $product;
        });

        $subtotal = $products->sum(function ($product) {
            return (float) $product->checkout_price
                * $product->checkout_quantity;
        });

        $shippingFee = 30000;
        $discount = 0;
        $total = $subtotal - $discount + $shippingFee;

        return view('checkout.index', compact(
            'deliverySlots',
            'products',
            'subtotal',
            'shippingFee',
            'discount',
            'total'
        ));
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

        // Tạm thời dùng cùng danh sách sản phẩm demo như Checkout.
        // Khi ghép Cart TV3 sẽ thay phần này bằng Cart thực tế.
        $productIds = [1, 2];

        $productModels = Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        if ($productModels->count() !== count($productIds)) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Một hoặc nhiều sản phẩm trong đơn không còn hoạt động.'
                );
        }

        $products = [];

        foreach ($productIds as $productId) {
            $product = $productModels[$productId];

            $price = (float) (
                $product->sale_price ?? $product->price
            );

            $products[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $price,
                'quantity' => 1,
            ];
        }

        $subtotal = collect($products)->sum(
            fn ($product) =>
                $product['price'] * $product['quantity']
        );

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
                    'subtotal' =>
                        $product['price'] * $product['quantity'],
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

        if ($validated['payment_method'] === 'cod') {
            return redirect()
                ->route('orders.show', $order)
                ->with('success', 'Đặt hàng thành công!');
        }

        return redirect()
            ->route('paypal.create', $order);
    }
}
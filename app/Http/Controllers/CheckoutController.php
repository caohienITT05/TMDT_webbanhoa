<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Lấy CartItem của người dùng hiện tại.
     */
    private function getCartItems()
    {
        if (Auth::check()) {
            return CartItem::where(
                'user_id',
                Auth::id()
            )
                ->with('product')
                ->get();
        }

        return CartItem::where(
            'session_id',
            Session::getId()
        )
            ->with('product')
            ->get();
    }

    /**
     * Tính tổng tiền từ Cart TV3.
     */
    private function calculateTotals($cartItems): array
    {
        $subtotal = 0;

        foreach ($cartItems as $item) {
            $price = (float) $item->price;
            $quantity = (int) $item->quantity;

            /*
             * Đồng bộ lại giá từ Product.
             *
             * sale_price được ưu tiên nếu có.
             */
            if ($item->product) {
                $price = (float) (
                    $item->product->sale_price
                    ?? $item->product->price
                );
            }

            $item->price = $price;

            $itemSubtotal =
                $price * $quantity;

            $item->calculated_subtotal =
                round($itemSubtotal, 2);

            $subtotal += $itemSubtotal;
        }

        $subtotal = round($subtotal, 2);

        /*
         * Voucher của TV3.
         */
        $discount = (float) Session::get(
            'voucher_discount',
            0
        );

        $discount = min(
            max($discount, 0),
            $subtotal
        );

        $discount = round($discount, 2);

        /*
         * Thiệp.
         */
        $giftCardFee = (float) Session::get(
            'gift_card_fee',
            0
        );

        $giftCardFee = round(
            max($giftCardFee, 0),
            2
        );

        /*
         * Gói quà.
         */
        $giftWrapFee = (float) Session::get(
            'gift_wrap_fee',
            0
        );

        $giftWrapFee = round(
            max($giftWrapFee, 0),
            2
        );

        /*
         * Phí vận chuyển.
         */
        $shippingFee = (float) Session::get(
            'shipping_fee',
            0
        );

        $shippingFee = round(
            max($shippingFee, 0),
            2
        );

        /*
         * Tổng tiền cuối cùng.
         */
        $total =
            $subtotal
            - $discount
            + $giftCardFee
            + $giftWrapFee
            + $shippingFee;

        $total = round(
            max($total, 0),
            2
        );

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'gift_card_fee' => $giftCardFee,
            'gift_wrap_fee' => $giftWrapFee,
            'shipping_fee' => $shippingFee,
            'total' => $total,
        ];
    }

    /**
     * Hiển thị trang Checkout.
     */
    public function index()
    {
        $cartItems = $this->getCartItems();

        /*
         * Không cho Checkout khi giỏ hàng rỗng.
         */
        if ($cartItems->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with(
                    'error',
                    'Giỏ hàng đang trống.'
                );
        }

        /*
         * Kiểm tra sản phẩm còn hoạt động.
         */
        foreach ($cartItems as $item) {
            if (!$item->product) {
                return redirect()
                    ->route('cart.index')
                    ->with(
                        'error',
                        'Một sản phẩm trong giỏ không còn tồn tại.'
                    );
            }

            if (!$item->product->is_active) {
                return redirect()
                    ->route('cart.index')
                    ->with(
                        'error',
                        'Một sản phẩm trong giỏ không còn hoạt động.'
                    );
            }
        }

        $totals = $this->calculateTotals(
            $cartItems
        );

        $deliverySlots = DeliverySlot::where(
            'is_active',
            true
        )->get();

        return view(
            'checkout.index',
            [
                'cartItems' => $cartItems,

                'subtotal' =>
                    $totals['subtotal'],

                'discount' =>
                    $totals['discount'],

                'giftCardFee' =>
                    $totals['gift_card_fee'],

                'giftWrapFee' =>
                    $totals['gift_wrap_fee'],

                'shippingFee' =>
                    $totals['shipping_fee'],

                'total' =>
                    $totals['total'],

                'deliverySlots' =>
                    $deliverySlots,
            ]
        );
    }

    /**
     * Tạo Order từ Cart TV3.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_name' =>
                'required|string|max:255',

            'recipient_phone' =>
                'required|string|max:20',

            'recipient_address' =>
                'required|string',

            'delivery_date' =>
                'required|date|after_or_equal:today',

            'delivery_slot_id' =>
                'required|exists:delivery_slots,id',

            'payment_method' =>
                'required|in:cod,paypal',

            'note' =>
                'nullable|string',
        ]);

        /*
         * Lấy Cart hiện tại.
         */
        $cartItems = $this->getCartItems();

        if ($cartItems->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with(
                    'error',
                    'Giỏ hàng đang trống.'
                );
        }

        /*
         * Kiểm tra Product và chuẩn bị OrderItem.
         */
        $products = [];

        foreach ($cartItems as $item) {
            $product = $item->product;

            if (!$product) {
                return redirect()
                    ->route('cart.index')
                    ->with(
                        'error',
                        'Một sản phẩm trong giỏ không còn tồn tại.'
                    );
            }

            if (!$product->is_active) {
                return redirect()
                    ->route('cart.index')
                    ->with(
                        'error',
                        "Sản phẩm {$product->name} không còn hoạt động."
                    );
            }

            $quantity = (int) $item->quantity;

            if ($quantity < 1) {
                return redirect()
                    ->route('cart.index')
                    ->with(
                        'error',
                        'Số lượng sản phẩm không hợp lệ.'
                    );
            }

            /*
             * Không trừ stock ở Cart.
             *
             * Việc xử lý stock sẽ không được thêm
             * vào đây để tránh double/triple deduction.
             */
            $price = (float) (
                $product->sale_price
                ?? $product->price
            );

            $products[] = [
                'product_id' =>
                    $product->id,

                'product_name' =>
                    $product->name,

                'price' =>
                    $price,

                'quantity' =>
                    $quantity,

                'subtotal' =>
                    $price * $quantity,
            ];
        }

        /*
         * Tính lại tiền tại thời điểm đặt hàng.
         */
        $subtotal = round(
            collect($products)->sum(
                fn ($product) =>
                    $product['subtotal']
            ),
            2
        );

        $discount = (float) Session::get(
            'voucher_discount',
            0
        );

        $discount = round(
            min(
                max($discount, 0),
                $subtotal
            ),
            2
        );

        $giftCardFee = round(
            max(
                (float) Session::get(
                    'gift_card_fee',
                    0
                ),
                0
            ),
            2
        );

        $giftWrapFee = round(
            max(
                (float) Session::get(
                    'gift_wrap_fee',
                    0
                ),
                0
            ),
            2
        );

        $shippingFee = round(
            max(
                (float) Session::get(
                    'shipping_fee',
                    0
                ),
                0
            ),
            2
        );

        $total = round(
            max(
                $subtotal
                - $discount
                + $giftCardFee
                + $giftWrapFee
                + $shippingFee,
                0
            ),
            2
        );

        /*
         * Tạo Order + OrderItem + Payment
         * trong cùng transaction.
         */
        $order = DB::transaction(
            function () use (
                $validated,
                $products,
                $subtotal,
                $discount,
                $shippingFee,
                $total
            ) {
                $orderCode =
                    'BG'
                    . now()->format('YmdHis')
                    . strtoupper(
                        Str::random(4)
                    );

                $order = Order::create([
                    'order_code' =>
                        $orderCode,

                    'recipient_name' =>
                        $validated['recipient_name'],

                    'recipient_phone' =>
                        $validated['recipient_phone'],

                    'recipient_address' =>
                        $validated['recipient_address'],

                    'delivery_date' =>
                        $validated['delivery_date'],

                    'delivery_slot_id' =>
                        $validated['delivery_slot_id'],

                    'subtotal' =>
                        $subtotal,

                    'discount' =>
                        $discount,

                    /*
                     * Chỉ ghi phí vận chuyển vào
                     * shipping_fee.
                     *
                     * Phí thiệp + gói quà đã được
                     * cộng vào total.
                     */
                    'shipping_fee' =>
                        $shippingFee,

                    'total' =>
                        $total,

                    'payment_method' =>
                        $validated['payment_method'],

                    'payment_status' =>
                        'pending',

                    'order_status' =>
                        'pending',

                    'note' =>
                        $validated['note'] ?? null,
                ]);

                foreach ($products as $product) {
                    OrderItem::create([
                        'order_id' =>
                            $order->id,

                        'product_id' =>
                            $product['product_id'],

                        'product_name' =>
                            $product['product_name'],

                        'price' =>
                            $product['price'],

                        'quantity' =>
                            $product['quantity'],

                        'subtotal' =>
                            $product['subtotal'],
                    ]);
                }

                Payment::create([
                    'order_id' =>
                        $order->id,

                    'payment_method' =>
                        $validated['payment_method'],

                    'transaction_id' =>
                        null,

                    'amount' =>
                        $total,

                    'status' =>
                        'pending',

                    'paid_at' =>
                        null,
                ]);

                return $order;
            }
        );

        /*
         * Xóa Cart sau khi Order đã được tạo.
         */
        $this->getCartItems()->each(
            function ($item) {
                $item->delete();
            }
        );

        /*
         * Xóa các session dữ liệu của Cart.
         */
        Session::forget([
            'voucher_discount',
            'gift_card_fee',
            'gift_wrap_fee',
            'shipping_fee',
        ]);

        /*
         * COD → trang Order.
         */
        if (
            $validated['payment_method']
            === 'cod'
        ) {
            return redirect()
                ->route(
                    'orders.show',
                    $order
                )
                ->with(
                    'success',
                    'Đặt hàng thành công!'
                );
        }

        /*
         * PayPal → PayPalController của TV4.
         */
        return redirect()
            ->route(
                'paypal.create',
                $order
            );
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\GiftCard;
use App\Models\GiftWrap;
use App\Models\Product;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    /**
     * Lấy CartItem của người dùng hiện tại.
     */
    private function getCartQuery()
    {
        $query = CartItem::query();

        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        } else {
            $query->where('session_id', Session::getId());
        }

        return $query;
    }

    /**
     * Lấy giá hiện tại của sản phẩm.
     */
    private function getProductPrice(Product $product): float
    {
        return round(
            (float) (
                $product->sale_price
                ?? $product->price
            ),
            2
        );
    }

    /**
     * Tính tổng tiền giỏ hàng.
     *
     * subtotal
     * - discount
     * + gift_card_fee
     * + gift_wrap_fee
     * + shipping_fee
     * = total
     */
    private function getCartTotals($cartItems): array
    {
        $subtotal = 0;

        foreach ($cartItems as $item) {
            $price = (float) $item->price;
            $quantity = (int) $item->quantity;

            /*
             * Đồng bộ giá hiện tại từ Product.
             */
            if ($item->product) {
                $price = $this->getProductPrice(
                    $item->product
                );
            }

            $itemSubtotal = $price * $quantity;

            $item->calculated_price = round(
                $price,
                2
            );

            $item->calculated_subtotal = round(
                $itemSubtotal,
                2
            );

            $subtotal += $itemSubtotal;
        }

        $subtotal = round($subtotal, 2);

        /*
         * ================================
         * VOUCHER
         * ================================
         */

        $discount = (float) Session::get(
            'voucher_discount',
            0
        );

        $discount = min(
            max($discount, 0),
            $subtotal
        );

        $discount = round(
            $discount,
            2
        );

        /*
         * ================================
         * THIỆP
         * ================================
         */

        $giftCardFee = 0;

        $giftCardId = Session::get(
            'gift_card_id'
        );

        if ($giftCardId) {
            $giftCard = GiftCard::where(
                'id',
                $giftCardId
            )
                ->where('is_active', true)
                ->first();

            if ($giftCard) {
                $giftCardFee = (float) $giftCard->price;

                Session::put(
                    'gift_card_fee',
                    round($giftCardFee, 2)
                );
            } else {
                Session::forget([
                    'gift_card_id',
                    'gift_card_fee',
                ]);
            }
        }

        $giftCardFee = round(
            max($giftCardFee, 0),
            2
        );

        /*
         * ================================
         * GÓI QUÀ
         * ================================
         */

        $giftWrapFee = 0;

        $giftWrapId = Session::get(
            'gift_wrap_id'
        );

        if ($giftWrapId) {
            $giftWrap = GiftWrap::where(
                'id',
                $giftWrapId
            )
                ->where('is_active', true)
                ->first();

            if ($giftWrap) {
                $giftWrapFee = (float) $giftWrap->price;

                Session::put(
                    'gift_wrap_fee',
                    round($giftWrapFee, 2)
                );
            } else {
                Session::forget([
                    'gift_wrap_id',
                    'gift_wrap_fee',
                ]);
            }
        }

        $giftWrapFee = round(
            max($giftWrapFee, 0),
            2
        );

        /*
         * ================================
         * VẬN CHUYỂN
         * ================================
         */

        $shippingFee = 0;

        $shippingMethodId = Session::get(
            'shipping_method_id'
        );

        if ($shippingMethodId) {
            $shippingMethod = ShippingMethod::where(
                'id',
                $shippingMethodId
            )
                ->where('is_active', true)
                ->first();

            if ($shippingMethod) {
                $shippingFee = (float) $shippingMethod->fee;

                Session::put(
                    'shipping_fee',
                    round($shippingFee, 2)
                );

                Session::put(
                    'shipping_method_name',
                    $shippingMethod->name
                );
            } else {
                Session::forget([
                    'shipping_method_id',
                    'shipping_fee',
                    'shipping_method_name',
                ]);
            }
        }

        $shippingFee = round(
            max($shippingFee, 0),
            2
        );

        /*
         * ================================
         * TỔNG TIỀN
         * ================================
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
     * Hiển thị giỏ hàng.
     */
    public function index()
    {
        $cartItems = $this->getCartQuery()
            ->with('product')
            ->get();

        /*
         * Lấy dữ liệu từ phần Admin.
         */
        $giftCards = GiftCard::where(
            'is_active',
            true
        )
            ->orderBy('price')
            ->get();

        $giftWraps = GiftWrap::where(
            'is_active',
            true
        )
            ->orderBy('price')
            ->get();

        $shippingMethods = ShippingMethod::where(
            'is_active',
            true
        )
            ->orderBy('fee')
            ->get();

        $totals = $this->getCartTotals(
            $cartItems
        );

        return view(
            'cart.index',
            [
                'cartItems' => $cartItems,

                'giftCards' => $giftCards,

                'giftWraps' => $giftWraps,

                'shippingMethods' =>
                    $shippingMethods,

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
            ]
        );
    }

    /**
     * Thêm sản phẩm vào giỏ.
     */
    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' =>
                'required|integer|exists:products,id',

            'quantity' =>
                'required|integer|min:1',
        ]);

        $product = Product::where(
            'id',
            $validated['product_id']
        )
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'Sản phẩm không còn hoạt động.'
                );
        }

        $price = $this->getProductPrice(
            $product
        );

        $cartItem = $this->getCartQuery()
            ->where(
                'product_id',
                $product->id
            )
            ->first();

        if ($cartItem) {
            $quantity =
                (int) $cartItem->quantity
                + (int) $validated['quantity'];

            $cartItem->update([
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' =>
                    $price * $quantity,
            ]);
        } else {
            $quantity =
                (int) $validated['quantity'];

            CartItem::create([
                'user_id' =>
                    Auth::check()
                        ? Auth::id()
                        : null,

                'session_id' =>
                    Auth::check()
                        ? null
                        : Session::getId(),

                'product_id' =>
                    $product->id,

                'quantity' =>
                    $quantity,

                'price' =>
                    $price,

                'subtotal' =>
                    $price * $quantity,
            ]);
        }

        return redirect()
            ->back()
            ->with(
                'success',
                'Đã thêm sản phẩm vào giỏ hàng!'
            );
    }

    /**
     * Cập nhật số lượng sản phẩm.
     */
    public function update(
        Request $request,
        $id
    ) {
        $request->validate([
            'quantity' =>
                'required|integer|min:1',
        ]);

        $cartItem = $this->getCartQuery()
            ->findOrFail($id);

        $product = Product::where(
            'id',
            $cartItem->product_id
        )
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'Sản phẩm không còn hoạt động.'
                );
        }

        $price = $this->getProductPrice(
            $product
        );

        $quantity =
            (int) $request->quantity;

        $cartItem->update([
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' =>
                $price * $quantity,
        ]);

        return redirect()
            ->back()
            ->with(
                'success',
                'Đã cập nhật số lượng!'
            );
    }

    /**
     * Xóa sản phẩm khỏi giỏ.
     */
    public function remove($id)
    {
        $cartItem = $this->getCartQuery()
            ->findOrFail($id);

        $cartItem->delete();

        return redirect()
            ->back()
            ->with(
                'success',
                'Đã xóa sản phẩm khỏi giỏ hàng!'
            );
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    /**
     * Lấy các CartItem của người dùng hiện tại.
     */
    private function getCartQuery()
    {
        if (Auth::check()) {
            return CartItem::where('user_id', Auth::id());
        }

        return CartItem::where(
            'session_id',
            Session::getId()
        );
    }


    /**
     * Tính toàn bộ tiền trong giỏ hàng.
     *
     * Công thức:
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
        /*
         * =========================
         * 1. TẠM TÍNH SẢN PHẨM
         * =========================
         */

        $subtotal = 0;

        foreach ($cartItems as $item) {

            $price = (float) $item->price;

            $quantity = (int) $item->quantity;

            $itemSubtotal = (float) $item->subtotal;


            /*
             * Nếu subtotal chưa có nhưng
             * price có giá trị thì tự tính lại.
             */
            if (
                $itemSubtotal <= 0 &&
                $price > 0
            ) {
                $itemSubtotal =
                    $price * $quantity;
            }


            $item->calculated_subtotal =
                round($itemSubtotal, 2);


            $subtotal += $itemSubtotal;
        }


        $subtotal = round(
            $subtotal,
            2
        );


        /*
         * =========================
         * 2. VOUCHER
         * =========================
         */

        $discount = (float) Session::get(
            'voucher_discount',
            0
        );


        /*
         * Không cho discount lớn hơn subtotal.
         */
        $discount = min(
            $discount,
            $subtotal
        );


        /*
         * Không cho discount âm.
         */
        $discount = max(
            $discount,
            0
        );


        $discount = round(
            $discount,
            2
        );


        /*
         * =========================
         * 3. THIỆP
         * =========================
         */

        $giftCardFee = (float) Session::get(
            'gift_card_fee',
            0
        );


        $giftCardFee = max(
            $giftCardFee,
            0
        );


        $giftCardFee = round(
            $giftCardFee,
            2
        );


        /*
         * =========================
         * 4. GÓI QUÀ
         * =========================
         */

        $giftWrapFee = (float) Session::get(
            'gift_wrap_fee',
            0
        );


        $giftWrapFee = max(
            $giftWrapFee,
            0
        );


        $giftWrapFee = round(
            $giftWrapFee,
            2
        );


        /*
         * =========================
         * 5. PHÍ VẬN CHUYỂN
         * =========================
         */

        $shippingFee = (float) Session::get(
            'shipping_fee',
            0
        );


        $shippingFee = max(
            $shippingFee,
            0
        );


        $shippingFee = round(
            $shippingFee,
            2
        );


        /*
         * =========================
         * 6. TỔNG CỘNG
         * =========================
         *
         * subtotal
         * - discount
         * + gift card
         * + gift wrap
         * + shipping
         */

        $total =
            $subtotal
            - $discount
            + $giftCardFee
            + $giftWrapFee
            + $shippingFee;


        /*
         * Không cho tổng tiền âm.
         */
        $total = max(
            $total,
            0
        );


        $total = round(
            $total,
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
        $cartItems =
            $this->getCartQuery()->get();


        $totals =
            $this->getCartTotals(
                $cartItems
            );


        return view(
            'cart.index',
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

            ]
        );
    }


    /**
     * Thêm sản phẩm vào giỏ.
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);


        $query =
            $this->getCartQuery()
                ->where(
                    'product_id',
                    $request->product_id
                );


        $cartItem =
            $query->first();


        if ($cartItem) {

            /*
             * Sản phẩm đã có trong giỏ:
             * tăng số lượng.
             */
            $cartItem->increment(
                'quantity',
                $request->quantity
            );


            /*
             * Nếu đã có giá thì cập nhật subtotal.
             */
            if (
                (float) $cartItem->price > 0
            ) {

                $cartItem->update([
                    'subtotal' =>
                        (float) $cartItem->price
                        *
                        (int) $cartItem->quantity,
                ]);
            }

        } else {

            /*
             * Sản phẩm chưa có trong giỏ.
             */
            CartItem::create([

                'user_id' =>
                    Auth::id(),

                'session_id' =>
                    Auth::check()
                        ? null
                        : Session::getId(),

                'product_id' =>
                    $request->product_id,

                'quantity' =>
                    $request->quantity,

                /*
                 * Tạm thời = 0 vì Product
                 * của TV2 chưa được tích hợp.
                 *
                 * Khi tích hợp Product thật,
                 * giá sẽ lấy từ Product.
                 */
                'price' => 0,

                'subtotal' => 0,

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


        $cartItem =
            $this->getCartQuery()
                ->findOrFail($id);


        $quantity =
            (int) $request->quantity;


        $updateData = [

            'quantity' =>
                $quantity,

        ];


        /*
         * Nếu CartItem đã có giá,
         * cập nhật lại subtotal.
         */
        if (
            (float) $cartItem->price > 0
        ) {

            $updateData['subtotal'] =
                (float) $cartItem->price
                *
                $quantity;
        }


        $cartItem->update(
            $updateData
        );


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
        $cartItem =
            $this->getCartQuery()
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
<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    /**
     * Lấy các CartItem của người dùng hiện tại (kèm thông tin Product).
     */
    private function getCartQuery()
    {
        $query = CartItem::with('product');

        if (Auth::check()) {
            return $query->where('user_id', Auth::id());
        }

        return $query->where('session_id', Session::getId());
    }

    /**
     * Tính toàn bộ tiền trong giỏ hàng.
     */
    private function getCartTotals($cartItems): array
    {
        $subtotal = 0;

        foreach ($cartItems as $item) {
            // Lấy giá từ Product thực tế nếu CartItem chưa có giá
            $price = (float) ($item->product ? $item->product->price : $item->price);
            $quantity = (int) $item->quantity;
            $itemSubtotal = $price * $quantity;

            // Đồng bộ lại giá trị tính toán cho View
            $item->price = $price;
            $item->calculated_subtotal = round($itemSubtotal, 2);

            $subtotal += $itemSubtotal;
        }

        $subtotal = round($subtotal, 2);

        // Voucher
        $discount = (float) Session::get('voucher_discount', 0);
        $discount = min($discount, $subtotal);
        $discount = max($discount, 0);
        $discount = round($discount, 2);

        // Thiệp chúc mừng
        $giftCardFee = (float) Session::get('gift_card_fee', 0);
        $giftCardFee = max($giftCardFee, 0);
        $giftCardFee = round($giftCardFee, 2);

        // Gói quà
        $giftWrapFee = (float) Session::get('gift_wrap_fee', 0);
        $giftWrapFee = max($giftWrapFee, 0);
        $giftWrapFee = round($giftWrapFee, 2);

        // Phí vận chuyển
        $shippingFee = (float) Session::get('shipping_fee', 0);
        $shippingFee = max($shippingFee, 0);
        $shippingFee = round($shippingFee, 2);

        // Tổng thanh toán
        $total = $subtotal - $discount + $giftCardFee + $giftWrapFee + $shippingFee;
        $total = max($total, 0);
        $total = round($total, 2);

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
        $cartItems = $this->getCartQuery()->get();
        $totals = $this->getCartTotals($cartItems);

        return view('cart.index', [
            'cartItems' => $cartItems,
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'giftCardFee' => $totals['gift_card_fee'],
            'giftWrapFee' => $totals['gift_wrap_fee'],
            'shippingFee' => $totals['shipping_fee'],
            'total' => $totals['total'],
        ]);
    }

    /**
     * Thêm sản phẩm hoa vào giỏ hàng.
     */
    /**
     * Thêm sản phẩm hoa vào giỏ hàng (Hỗ trợ linh hoạt cho cả TV2 và TV3)
     */
    public function add(Request $request, $id = null)
    {
        // Lấy product_id từ form POST, query string hoặc route parameter
        $productId = $request->input('product_id') ?? $request->input('id') ?? $id;
        $quantity = (int) ($request->input('quantity') ?? 1);

        if ($quantity < 1) {
            $quantity = 1;
        }

        if (!$productId) {
            return redirect()->back()->with('error', 'Vui lòng chọn sản phẩm hoa cần mua!');
        }

        // Lấy thông tin hoa thật từ database TV2
        $product = Product::find($productId);
        if (!$product) {
            return redirect()->back()->with('error', 'Sản phẩm không tồn tại trong danh mục!');
        }

        $price = (float) $product->price;

        $cartItem = $this->getCartQuery()
            ->where('product_id', $product->id)
            ->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $quantity;
            $cartItem->update([
                'quantity' => $newQuantity,
                'price' => $price,
                'subtotal' => $price * $newQuantity,
            ]);
        } else {
            CartItem::create([
                'user_id' => Auth::id(),
                'session_id' => Auth::check() ? null : Session::getId(),
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $price * $quantity,
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Đã thêm bó hoa "' . $product->name . '" vào giỏ hàng!');
    }

    /**
     * Cập nhật số lượng sản phẩm.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        $cartItem = CartItem::with('product')->findOrFail($id);

        // Lấy giá bán thực tế của sản phẩm
        $unitPrice = (float) ($cartItem->price ?? $cartItem->product?->sale_price ?? $cartItem->product?->price ?? 0);
        $newQuantity = (int) $request->quantity;

        // Cập nhật số lượng và thành tiền
        $cartItem->quantity = $newQuantity;
        $cartItem->price = $unitPrice;

        // Nếu bảng cart_items có cột calculated_subtotal hoặc subtotal thì cập nhật luôn
        if (\Schema::hasColumn('cart_items', 'calculated_subtotal')) {
            $cartItem->calculated_subtotal = $unitPrice * $newQuantity;
        }

        $cartItem->save();

        return redirect()->route('cart.index')->with('success', 'Đã cập nhật số lượng bó hoa thành công!');
    }
    /**
     * Xóa sản phẩm khỏi giỏ.
     */
    public function remove($id)
    {
        $cartItem = $this->getCartQuery()->findOrFail($id);
        $cartItem->delete();

        return redirect()
            ->back()
            ->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng!');
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class VoucherController extends Controller
{
    /**
     * Tính tổng tiền sản phẩm trong giỏ hàng.
     */
    private function getCartSubtotal(): float
    {
        $cartItems = CartItem::query();

        if (auth()->check()) {
            $cartItems->where('user_id', auth()->id());
        } else {
            $cartItems->where('session_id', Session::getId());
        }

        $items = $cartItems->get();

        $subtotal = 0;

        foreach ($items as $item) {
            // Ưu tiên subtotal đã lưu trong CartItem.
            // Nếu subtotal chưa có giá trị thì tính từ price × quantity.
            $itemSubtotal = (float) $item->subtotal;

            if ($itemSubtotal <= 0 && (float) $item->price > 0) {
                $itemSubtotal = (float) $item->price * (int) $item->quantity;
            }

            $subtotal += $itemSubtotal;
        }

        return round($subtotal, 2);
    }

    private function getBuyNowSubtotal(?int $requestedQuantity = null): float
    {
        $buyNow = Session::get('buy_now');
        $product = Product::query()
            ->whereKey($buyNow['product_id'] ?? null)
            ->where('is_active', true)
            ->first();
        $quantity = $requestedQuantity ?? (int) ($buyNow['quantity'] ?? 0);

        if (!$product || $quantity < 1 || $quantity > (int) $product->stock) {
            return 0;
        }

        $regularPrice = (float) $product->price;
        $salePrice = (float) ($product->sale_price ?? 0);
        $price = $salePrice > 0 && $salePrice < $regularPrice ? $salePrice : $regularPrice;

        return round($price * $quantity, 2);
    }

    /**
     * Kiểm tra và áp dụng voucher.
     */
    public function apply(Request $request)
    {
        $checkoutMode = $request->input('checkout_mode') === 'buy_now' ? 'buy_now' : 'cart';
        $voucherKeyPrefix = $checkoutMode === 'buy_now' ? 'buy_now_voucher_' : 'voucher_';
        $code = strtoupper(trim($request->input('code')));
        $voucher = \App\Models\Voucher::valid()->where('code', $code)->first();

        if (!$voucher) {
            return back()->with('error', 'Mã giảm giá không tồn tại, đã hết hạn hoặc chưa tới khung giờ áp dụng!');
        }

        // Tính đúng subtotal của mode hiện tại; Buy Now không lấy dữ liệu từ cart_items.
        if ($checkoutMode === 'buy_now') {
            $quantity = (int) $request->input('buy_now_quantity', Session::get('buy_now.quantity', 0));
            $subtotal = $this->getBuyNowSubtotal($quantity);

            if ($subtotal <= 0) {
                return back()->with('error', 'Sản phẩm hoặc số lượng đặt hàng không còn hợp lệ.');
            }

            Session::put('buy_now', [
                'product_id' => Session::get('buy_now.product_id'),
                'quantity' => $quantity,
            ]);
        } else {
            $subtotal = $this->getCartSubtotal();
        }
        if ($subtotal < $voucher->min_order_amount) {
            return back()->with('error', 'Đơn hàng cần đạt tối thiểu ' . number_format($voucher->min_order_amount) . 'đ để dùng mã này.');
        }

        $discount = $voucher->calculateDiscount($subtotal);

        session()->put($voucherKeyPrefix . 'id', $voucher->id);
        session()->put($voucherKeyPrefix . 'code', $voucher->code);
        session()->put($voucherKeyPrefix . 'discount', $discount);

        return back()->with('success', 'Đã áp dụng mã ' . $voucher->code . ' (Giảm ' . number_format($discount) . 'đ)');
    }
    /**
     * Hủy voucher đang áp dụng.
     */
    public function remove(Request $request)
    {
        $voucherKeyPrefix = $request->input('checkout_mode') === 'buy_now' ? 'buy_now_voucher_' : 'voucher_';

        Session::forget([
            $voucherKeyPrefix . 'id',
            $voucherKeyPrefix . 'code',
            $voucherKeyPrefix . 'discount',
        ]);

        return redirect()->back()
            ->with('voucher_success', 'Đã hủy voucher.');
    }
}

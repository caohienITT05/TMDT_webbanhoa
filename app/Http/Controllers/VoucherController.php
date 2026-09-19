<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
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

    /**
     * Kiểm tra và áp dụng voucher.
     */
    public function apply(Request $request)
    {
        $code = strtoupper(trim($request->input('code')));
        $voucher = \App\Models\Voucher::valid()->where('code', $code)->first();

        if (!$voucher) {
            return back()->with('error', 'Mã giảm giá không tồn tại, đã hết hạn hoặc chưa tới khung giờ áp dụng!');
        }

        // Tính tạm tính đơn hàng
        $subtotal = 500000; // Hoặc tính từ giỏ hàng hiện tại
        if ($subtotal < $voucher->min_order_amount) {
            return back()->with('error', 'Đơn hàng cần đạt tối thiểu ' . number_format($voucher->min_order_amount) . 'đ để dùng mã này.');
        }

        $discount = $voucher->calculateDiscount($subtotal);

        session()->put('voucher_code', $voucher->code);
        session()->put('voucher_discount', $discount);

        return back()->with('success', 'Đã áp dụng mã ' . $voucher->code . ' (Giảm ' . number_format($discount) . 'đ)');
    }
    /**
     * Hủy voucher đang áp dụng.
     */
    public function remove()
    {
        Session::forget([
            'voucher_id',
            'voucher_code',
            'voucher_discount',
        ]);

        return redirect()->back()
            ->with('voucher_success', 'Đã hủy voucher.');
    }
}
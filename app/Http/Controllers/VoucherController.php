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
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $code = strtoupper(trim($request->code));

        $voucher = Voucher::where('code', $code)->first();

        // Không tìm thấy voucher
        if (!$voucher) {
            return redirect()->back()
                ->with('voucher_error', 'Mã voucher không tồn tại.');
        }

        // Voucher bị tắt
        if (!$voucher->is_active) {
            return redirect()->back()
                ->with('voucher_error', 'Voucher hiện không hoạt động.');
        }

        // Kiểm tra thời gian bắt đầu
        if ($voucher->starts_at && now()->lt($voucher->starts_at)) {
            return redirect()->back()
                ->with('voucher_error', 'Voucher chưa bắt đầu sử dụng.');
        }

        // Kiểm tra thời gian hết hạn
        if ($voucher->expires_at && now()->gt($voucher->expires_at)) {
            return redirect()->back()
                ->with('voucher_error', 'Voucher đã hết hạn.');
        }

        // Kiểm tra số lượt sử dụng
        if (
            $voucher->usage_limit !== null &&
            $voucher->used_count >= $voucher->usage_limit
        ) {
            return redirect()->back()
                ->with('voucher_error', 'Voucher đã hết lượt sử dụng.');
        }

        // Lấy subtotal thực tế của Cart
        $subtotal = $this->getCartSubtotal();

        // Kiểm tra giá trị đơn hàng tối thiểu
        if ($subtotal < (float) $voucher->min_order_amount) {
            return redirect()->back()
                ->with(
                    'voucher_error',
                    'Đơn hàng chưa đạt giá trị tối thiểu để sử dụng voucher.'
                );
        }

        // Tính số tiền giảm
        $discount = 0;

        if ($voucher->type === 'percent') {
            // Ví dụ:
            // subtotal = 800000
            // value = 10
            // discount = 80000
            $discount = $subtotal * ((float) $voucher->value / 100);

            // Nếu có mức giảm tối đa
            if ($voucher->max_discount !== null) {
                $discount = min(
                    $discount,
                    (float) $voucher->max_discount
                );
            }
        } elseif ($voucher->type === 'fixed') {
            // Ví dụ:
            // value = 50000
            // discount = 50000
            $discount = (float) $voucher->value;
        } else {
            return redirect()->back()
                ->with(
                    'voucher_error',
                    'Loại voucher không hợp lệ.'
                );
        }

        // Không cho giảm vượt quá tiền sản phẩm
        $discount = min($discount, $subtotal);

        // Làm tròn tiền
        $discount = round($discount, 2);

        // Lưu thông tin voucher vào Session
        Session::put('voucher_id', $voucher->id);
        Session::put('voucher_code', $voucher->code);
        Session::put('voucher_discount', $discount);

        return redirect()->back()
            ->with('voucher_success', 'Áp dụng voucher thành công!');
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
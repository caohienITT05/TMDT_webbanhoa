<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class GiftController extends Controller
{
    /**
     * Lưu lựa chọn thiệp và gói quà.
     */
    public function save(Request $request)
    {
        $request->validate([
            'gift_card' => 'required|in:none,card',
            'gift_message' => 'nullable|string|max:500',
            'gift_wrap' => 'required|in:none,basic,premium',
        ]);

        /*
         * Tính phí thiệp.
         *
         * Không chọn thiệp = 0đ
         * Thiệp chúc = 10.000đ
         */
        $giftCardFee = 0;

        if ($request->gift_card === 'card') {
            $giftCardFee = 10000;
        }

        /*
         * Tính phí gói quà.
         *
         * Không gói = 0đ
         * Gói cơ bản = 20.000đ
         * Gói cao cấp = 30.000đ
         */
        $giftWrapFee = 0;

        if ($request->gift_wrap === 'basic') {
            $giftWrapFee = 20000;
        } elseif ($request->gift_wrap === 'premium') {
            $giftWrapFee = 30000;
        }

        /*
         * Lưu vào Session để CartController
         * sử dụng khi tính tổng tiền.
         */
        Session::put('gift_card', $request->gift_card);
        Session::put('gift_message', trim($request->gift_message ?? ''));
        Session::put('gift_card_fee', $giftCardFee);

        Session::put('gift_wrap', $request->gift_wrap);
        Session::put('gift_wrap_fee', $giftWrapFee);

        return redirect()
            ->back()
            ->with('gift_success', 'Đã lưu lựa chọn thiệp và gói quà!');
    }

    /**
     * Xóa lựa chọn thiệp và gói quà.
     */
    public function remove()
    {
        Session::forget([
            'gift_card',
            'gift_message',
            'gift_card_fee',
            'gift_wrap',
            'gift_wrap_fee',
        ]);

        return redirect()
            ->back()
            ->with('gift_success', 'Đã hủy lựa chọn thiệp và gói quà.');
    }
}
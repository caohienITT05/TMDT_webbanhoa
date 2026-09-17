<?php

namespace App\Http\Controllers;

use App\Models\GiftCard;
use App\Models\GiftWrap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class GiftController extends Controller
{
    /**
     * Lưu lựa chọn thiệp và gói quà.
     *
     * Giá được lấy trực tiếp từ database.
     */
    public function save(Request $request)
    {
        $request->validate([
            'gift_card_id' => 'nullable|integer',
            'gift_message' => 'nullable|string|max:500',
            'gift_wrap_id' => 'nullable|integer',
        ]);

        /*
         * ================================
         * THIỆP
         * ================================
         */

        $giftCardId = null;
        $giftCardFee = 0;

        if ($request->filled('gift_card_id')) {
            $giftCard = GiftCard::where(
                'id',
                $request->gift_card_id
            )
                ->where('is_active', true)
                ->first();

            if (!$giftCard) {
                return redirect()
                    ->back()
                    ->with(
                        'gift_error',
                        'Thiệp đã chọn không tồn tại hoặc không còn hoạt động.'
                    );
            }

            $giftCardId = $giftCard->id;
            $giftCardFee = (float) $giftCard->price;
        }

        /*
         * ================================
         * GÓI QUÀ
         * ================================
         */

        $giftWrapId = null;
        $giftWrapFee = 0;

        if ($request->filled('gift_wrap_id')) {
            $giftWrap = GiftWrap::where(
                'id',
                $request->gift_wrap_id
            )
                ->where('is_active', true)
                ->first();

            if (!$giftWrap) {
                return redirect()
                    ->back()
                    ->with(
                        'gift_error',
                        'Gói quà đã chọn không tồn tại hoặc không còn hoạt động.'
                    );
            }

            $giftWrapId = $giftWrap->id;
            $giftWrapFee = (float) $giftWrap->price;
        }

        /*
         * ================================
         * LƯU SESSION
         * ================================
         */

        Session::put(
            'gift_card_id',
            $giftCardId
        );

        Session::put(
            'gift_card_fee',
            round(
                max($giftCardFee, 0),
                2
            )
        );

        Session::put(
            'gift_message',
            trim(
                $request->gift_message ?? ''
            )
        );

        Session::put(
            'gift_wrap_id',
            $giftWrapId
        );

        Session::put(
            'gift_wrap_fee',
            round(
                max($giftWrapFee, 0),
                2
            )
        );

        return redirect()
            ->back()
            ->with(
                'gift_success',
                'Đã lưu lựa chọn thiệp và gói quà!'
            );
    }

    /**
     * Xóa lựa chọn thiệp và gói quà.
     */
    public function remove()
    {
        Session::forget([
            'gift_card_id',
            'gift_card_fee',
            'gift_message',
            'gift_wrap_id',
            'gift_wrap_fee',
        ]);

        return redirect()
            ->back()
            ->with(
                'gift_success',
                'Đã hủy lựa chọn thiệp và gói quà.'
            );
    }
}

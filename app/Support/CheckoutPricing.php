<?php

namespace App\Support;

final class CheckoutPricing
{
    public const SHIPPING_FEES = [0, 30000, 50000];

    public const GIFT_CARD_FEES = [0, 10000];

    public const GIFT_WRAP_FEES = [0, 20000, 30000];

    /**
     * @param  array{shipping_fee?: mixed, gift_card_fee?: mixed, gift_wrap_fee?: mixed}  $input
     * @return array{shipping_fee: float, gift_card_fee: float, gift_wrap_fee: float}
     */
    public static function fees(array $input): array
    {
        return [
            'shipping_fee' => (float) ($input['shipping_fee'] ?? 0),
            'gift_card_fee' => (float) ($input['gift_card_fee'] ?? 0),
            'gift_wrap_fee' => (float) ($input['gift_wrap_fee'] ?? 0),
        ];
    }

    /**
     * @param  array{shipping_fee: float, gift_card_fee: float, gift_wrap_fee: float}  $fees
     */
    public static function total(float $subtotal, array $fees, float $discount): float
    {
        return max(0, $subtotal + $fees['shipping_fee'] + $fees['gift_card_fee'] + $fees['gift_wrap_fee'] - $discount);
    }
}

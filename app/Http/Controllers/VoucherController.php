<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\Voucher;
use App\Support\CheckoutPricing;
use App\Support\GuestCartSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

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
            $cartItems->where('session_id', GuestCartSession::id());
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
     * @return array{shipping_fee: float, gift_card_fee: float, gift_wrap_fee: float}
     */
    private function validatedFees(Request $request): array
    {
        $validated = $request->validate([
            'shipping_fee' => ['nullable', 'numeric', Rule::in(CheckoutPricing::SHIPPING_FEES)],
            'gift_card_fee' => ['nullable', 'numeric', Rule::in(CheckoutPricing::GIFT_CARD_FEES)],
            'gift_wrap_fee' => ['nullable', 'numeric', Rule::in(CheckoutPricing::GIFT_WRAP_FEES)],
        ]);

        return CheckoutPricing::fees($validated);
    }

    /**
     * @param  array{shipping_fee: float, gift_card_fee: float, gift_wrap_fee: float}  $fees
     */
    private function preview(float $subtotal, array $fees, float $discount): array
    {
        return [
            'subtotal' => $subtotal,
            'shipping_fee' => $fees['shipping_fee'],
            'gift_card_fee' => $fees['gift_card_fee'],
            'gift_wrap_fee' => $fees['gift_wrap_fee'],
            'service_fee' => $fees['gift_card_fee'] + $fees['gift_wrap_fee'],
            'discount_amount' => $discount,
            'total' => CheckoutPricing::total($subtotal, $fees, $discount),
        ];
    }

    private function voucherResponse(Request $request, bool $valid, string $message, array $preview = [])
    {
        if ($request->expectsJson()) {
            return response()->json([
                'valid' => $valid,
                'message' => $message,
                ...$preview,
            ], $valid ? 200 : 422);
        }

        return back()->with($valid ? 'success' : 'error', $message);
    }

    /**
     * Kiểm tra và áp dụng voucher mà không tiêu thụ lượt dùng.
     */
    public function apply(Request $request)
    {
        $checkoutMode = $request->input('checkout_mode') === 'buy_now' ? 'buy_now' : 'cart';
        $voucherKeyPrefix = $checkoutMode === 'buy_now' ? 'buy_now_voucher_' : 'voucher_';
        $fees = $this->validatedFees($request);

        if ($checkoutMode === 'buy_now') {
            $quantity = (int) $request->input('buy_now_quantity', Session::get('buy_now.quantity', 0));
            $subtotal = $this->getBuyNowSubtotal($quantity);

            if ($subtotal <= 0) {
                return $this->voucherResponse($request, false, 'Sản phẩm hoặc số lượng đặt hàng không còn hợp lệ.', $this->preview(0, $fees, 0));
            }

            Session::put('buy_now', [
                'product_id' => Session::get('buy_now.product_id'),
                'quantity' => $quantity,
            ]);
        } else {
            $subtotal = $this->getCartSubtotal();
        }

        $code = strtoupper(trim((string) $request->input('code')));
        $voucher = Voucher::valid()->where('code', $code)->first();

        if (! $voucher) {
            return $this->voucherResponse($request, false, 'Mã giảm giá không tồn tại, đã hết hạn hoặc chưa tới khung giờ áp dụng!', $this->preview($subtotal, $fees, 0));
        }

        if ($subtotal < (float) $voucher->min_order_amount) {
            return $this->voucherResponse($request, false, 'Đơn hàng cần đạt tối thiểu ' . number_format($voucher->min_order_amount) . 'đ để dùng mã này.', $this->preview($subtotal, $fees, 0));
        }

        $discount = (float) $voucher->calculateDiscount($subtotal);

        Session::put($voucherKeyPrefix . 'id', $voucher->id);
        Session::put($voucherKeyPrefix . 'code', $voucher->code);
        Session::put($voucherKeyPrefix . 'discount', $discount);

        return $this->voucherResponse(
            $request,
            true,
            'Đã áp dụng mã ' . $voucher->code . ' (Giảm ' . number_format($discount) . 'đ).',
            ['voucher_code' => $voucher->code, ...$this->preview($subtotal, $fees, $discount)],
        );
    }
    /**
     * Hủy voucher đang áp dụng.
     */
    public function remove(Request $request)
    {
        $voucherKeyPrefix = $request->input('checkout_mode') === 'buy_now' ? 'buy_now_voucher_' : 'voucher_';

        if ($request->expectsJson()) {
            $fees = $this->validatedFees($request);
            $subtotal = $request->input('checkout_mode') === 'buy_now'
                ? $this->getBuyNowSubtotal((int) $request->input('buy_now_quantity', Session::get('buy_now.quantity', 0)))
                : $this->getCartSubtotal();

            Session::forget([
                $voucherKeyPrefix . 'id',
                $voucherKeyPrefix . 'code',
                $voucherKeyPrefix . 'discount',
            ]);

            return $this->voucherResponse($request, true, 'Đã hủy mã giảm giá.', $this->preview($subtotal, $fees, 0));
        }

        Session::forget([
            $voucherKeyPrefix . 'id',
            $voucherKeyPrefix . 'code',
            $voucherKeyPrefix . 'discount',
        ]);

        return redirect()->back()
            ->with('voucher_success', 'Đã hủy voucher.');
    }
}

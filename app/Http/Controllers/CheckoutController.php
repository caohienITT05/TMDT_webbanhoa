<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Voucher;
use App\Mail\OrderConfirmationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Mail\OrderPlacedMail;

class CheckoutController extends Controller
{
    private function cartItemsForCurrentCustomer()
    {
        return CartItem::with('product')
            ->where(function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                } else {
                    $query->where('session_id', Session::getId());
                }
            })
            ->get();
    }

    private function currentProductPrice(Product $product): float
    {
        $regularPrice = (float) $product->price;
        $salePrice = (float) ($product->sale_price ?? 0);

        return $salePrice > 0 && $salePrice < $regularPrice ? $salePrice : $regularPrice;
    }

    private function checkoutView($cartItems, float $subtotal, string $checkoutMode = 'cart', ?Product $buyNowProduct = null, ?int $buyNowQuantity = null)
    {
        $deliverySlots = DeliverySlot::where('is_active', true)->get();
        $voucherKeyPrefix = $checkoutMode === 'buy_now' ? 'buy_now_voucher_' : 'voucher_';
        $voucherDiscount = min((float) Session::get($voucherKeyPrefix . 'discount', 0), $subtotal);
        $voucherCode = Session::get($voucherKeyPrefix . 'code');

        $activeVouchers = Voucher::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('end_time')->orWhere('end_time', '>=', now());
            })->take(2)->get();

        return view('checkout.index', compact(
            'cartItems',
            'subtotal',
            'deliverySlots',
            'voucherDiscount',
            'voucherCode',
            'activeVouchers',
            'checkoutMode',
            'buyNowProduct',
            'buyNowQuantity'
        ));
    }

    /**
     * 1. Hiển thị trang thanh toán với dữ liệu thực tế từ giỏ hàng
     */
    public function index()
    {
        $cartItems = $this->cartItemsForCurrentCustomer();

        // Nếu giỏ hàng trống thì quay về trang cart
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống, vui lòng chọn hoa trước!');
        }

        // Tính tạm tính từ giỏ hàng thực tế
        $subtotal = $cartItems->sum(function ($item) {
            $price = (float) ($item->price ?? $item->product?->sale_price ?? $item->product?->price ?? 0);
            return $price * $item->quantity;
        });

        return $this->checkoutView($cartItems, $subtotal);
    }

    /**
     * Display a one-product checkout without reading or mutating the cart.
     */
    public function buyNow()
    {
        $buyNow = Session::get('buy_now');
        $productId = $buyNow['product_id'] ?? null;
        $quantity = (int) ($buyNow['quantity'] ?? 0);

        $product = Product::query()
            ->whereKey($productId)
            ->where('is_active', true)
            ->first();

        if (!$product || $quantity < 1 || $quantity > (int) $product->stock) {
            Session::forget(['buy_now', 'buy_now_voucher_code', 'buy_now_voucher_discount', 'buy_now_voucher_id']);

            return redirect()->route('products.index')->with('error', 'Sản phẩm đặt hàng không còn đủ điều kiện để thanh toán.');
        }

        $unitPrice = $this->currentProductPrice($product);
        $checkoutItem = (object) [
            'product_id' => $product->id,
            'product' => $product,
            'quantity' => $quantity,
            'price' => $unitPrice,
            'subtotal' => $unitPrice * $quantity,
        ];

        return $this->checkoutView(collect([$checkoutItem]), $checkoutItem->subtotal, 'buy_now', $product, $quantity);
    }

    /**
     * 2. Xử lý tạo đơn hàng khi khách bấm Đặt hàng ngay
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'recipient_address' => 'required|string|max:500',
            'delivery_date' => 'required|date|after_or_equal:today',
            'delivery_slot_id' => 'required',
            'payment_method' => 'required|in:cod,paypal',
            'shipping_fee' => 'nullable|numeric|min:0',
            'gift_card_fee' => 'nullable|numeric|min:0',
            'gift_wrap_fee' => 'nullable|numeric|min:0',
            'card_message' => 'nullable|string|max:500',
            'order_note' => 'nullable|string|max:500',
        ]);

        $checkoutMode = $request->input('checkout_mode') === 'buy_now' ? 'buy_now' : 'cart';
        $subtotal = 0;
        $orderProducts = [];

        if ($checkoutMode === 'buy_now') {
            $buyNow = Session::get('buy_now');
            $productId = $buyNow['product_id'] ?? null;
            $product = Product::query()
                ->whereKey($productId)
                ->where('is_active', true)
                ->first();
            $quantity = (int) $request->input('buy_now_quantity', $buyNow['quantity'] ?? 0);

            if (!$product || $quantity < 1 || $quantity > (int) $product->stock) {
                return redirect()->route('buy-now.checkout')->with('error', 'Số lượng đặt hàng không hợp lệ hoặc đã vượt tồn kho.');
            }

            // Persist only the selected product and quantity in its dedicated session key.
            Session::put('buy_now', [
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);

            $price = $this->currentProductPrice($product);
            $subtotal = $price * $quantity;
            $orderProducts[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ];
        } else {
            $cartItems = $this->cartItemsForCurrentCustomer();

            if ($cartItems->isEmpty()) {
                return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống!');
            }

            foreach ($cartItems as $item) {
                $price = (float) ($item->price ?? ($item->product ? $this->currentProductPrice($item->product) : 0));
                $lineTotal = $price * $item->quantity;
                $subtotal += $lineTotal;

                $orderProducts[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product ? $item->product->name : ('Hoa mã #' . $item->product_id),
                    'price' => $price,
                    'quantity' => $item->quantity,
                    'subtotal' => $lineTotal,
                ];
            }
        }

        // Nhận các loại phí được chọn từ trang Checkout
        $shippingFee = (float) $request->input('shipping_fee', 0);
        $giftCardFee = (float) $request->input('gift_card_fee', 0);
        $giftWrapFee = (float) $request->input('gift_wrap_fee', 0);
        $voucherKeyPrefix = $checkoutMode === 'buy_now' ? 'buy_now_voucher_' : 'voucher_';
        $discount = min((float) Session::get($voucherKeyPrefix . 'discount', 0), $subtotal);

        if ($checkoutMode === 'buy_now') {
            $voucher = Voucher::valid()->find(Session::get('buy_now_voucher_id'));
            $discount = $voucher && $subtotal >= (float) $voucher->min_order_amount
                ? min((float) $voucher->calculateDiscount($subtotal), $subtotal)
                : 0;
        }

        // Tổng tiền cuối cùng
        $total = max(0, $subtotal + $shippingFee + $giftCardFee + $giftWrapFee - $discount);

        // Gom Lời chúc thiệp + Ghi chú vào một chuỗi hoàn chỉnh
        $noteParts = [];
        if ($request->filled('card_message')) {
            $noteParts[] = "💌 Lời chúc thiệp: " . $request->card_message;
        }
        if ($request->filled('order_note')) {
            $noteParts[] = "Ghi chú: " . $request->order_note;
        }
        $fullNote = !empty($noteParts) ? implode(" | ", $noteParts) : null;


        // Lưu đơn hàng an toàn qua DB Transaction
        $order = DB::transaction(function () use ($validated, $orderProducts, $subtotal, $discount, $shippingFee, $total, $fullNote) {
            $orderCode = 'BG' . now()->format('YmdHis') . strtoupper(Str::random(4));

            $order = Order::create([
                'user_id' => Auth::id(),
                'order_code' => $orderCode,
                'recipient_name' => $validated['recipient_name'],
                'recipient_phone' => $validated['recipient_phone'],
                'recipient_address' => $validated['recipient_address'],
                'delivery_date' => $validated['delivery_date'],
                'delivery_slot_id' => $validated['delivery_slot_id'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'total_amount' => $total,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'status' => 'PENDING',
                'note' => $fullNote,
            ]);

            foreach ($orderProducts as $prod) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $prod['product_id'],
                    'product_name' => $prod['product_name'],
                    'price' => $prod['price'],
                    'quantity' => $prod['quantity'],
                    'subtotal' => $prod['subtotal'],
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $validated['payment_method'],
                'transaction_id' => null,
                'amount' => $total,
                'status' => 'pending',
                'paid_at' => null,
            ]);

            return $order;
        });

        // Cart checkout clears only its own cart. Buy Now never touches cart_items.
        if ($checkoutMode === 'cart' && Auth::check()) {
            CartItem::where('user_id', Auth::id())->delete();
        } elseif ($checkoutMode === 'cart') {
            CartItem::where('session_id', Session::getId())->delete();
        } else {
            Session::forget(['buy_now', 'buy_now_voucher_code', 'buy_now_voucher_discount', 'buy_now_voucher_id']);
        }

        // Keep voucher data scoped to the checkout mode that just completed.
        if ($checkoutMode === 'cart') {
            Session::forget(['voucher_discount', 'voucher_code', 'voucher_id', 'shipping_fee']);
        }

        // Gửi email hóa đơn xác nhận đơn hàng tự động
        // Gửi mail xác nhận đặt hàng
        $customerEmail = auth()->user()->email ?? $order->user->email ?? null;
        if ($customerEmail) {
            try {
                Mail::to($customerEmail)->send(new OrderPlacedMail($order));
            } catch (\Throwable $e) {
                \Log::error('Lỗi gửi mail đặt hàng: ' . $e->getMessage());
            }
        }

        // Nếu chọn COD: hoàn tất và chuyển về trang chi tiết đơn
        if ($validated['payment_method'] === 'cod') {
            if (\Illuminate\Support\Facades\Route::has('orders.show')) {
                return redirect()->route('orders.show', $order)->with('success', 'Đặt hàng COD thành công! Đơn hàng hoa #' . $order->order_code . ' đang được chuẩn bị.');
            }

            if (\Illuminate\Support\Facades\Route::has('admin.orders.show')) {
                return redirect()->route('admin.orders.show', $order)->with('success', 'Đặt hàng COD thành công! Đơn hàng hoa #' . $order->order_code . ' đang được chuẩn bị.');
            }

            return redirect()->route('home')->with('success', 'Đặt hàng thành công! Mã đơn: ' . $order->order_code);
        }

        // Nếu chọn PayPal: chuyển sang hàm tạo phiên thanh toán PayPal Sandbox
        $customerEmail = $order->user->email ?? auth()->user()->email ?? null;
        if ($customerEmail) {
            try {
                Mail::to($customerEmail)->send(new OrderPlacedMail($order));
            } catch (\Throwable $e) {
                \Log::error('Lỗi gửi mail đặt hàng PayPal: ' . $e->getMessage());
            }
        }
        return redirect()->route('paypal.create', ['order' => $order->id]);
    }

    /**
     * 3. Tạo phiên thanh toán trên PayPal Sandbox API v2
     */
    public function paypalCreate($orderId)
    {
        $order = Order::findOrFail($orderId);

        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.client_secret');
        $baseUrl = config('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');
        $exchangeRate = config('services.paypal.exchange_rate', 26054);

        // Quy đổi VND sang USD
        $usdAmount = round($order->total / $exchangeRate, 2);
        if ($usdAmount < 0.01) {
            $usdAmount = 1.00;
        }

        if (empty($clientId) || empty($secret)) {
            // Chế độ test giả lập nếu chưa cấu hình Sandbox
            return redirect()->route('paypal.success', [
                'order_id' => $order->id,
                'token' => 'MOCK-SANDBOX-' . strtoupper(Str::random(10)),
                'PayerID' => 'MOCK-PAYER-' . strtoupper(Str::random(6)),
            ]);
        }

        try {
            $authResponse = Http::asForm()
                ->withBasicAuth($clientId, $secret)
                ->post("{$baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if (!$authResponse->successful()) {
                throw new \Exception('Không thể kết nối API PayPal: ' . $authResponse->body());
            }

            $accessToken = $authResponse->json()['access_token'];

            $orderResponse = Http::withToken($accessToken)
                ->post("{$baseUrl}/v2/checkout/orders", [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'reference_id' => $order->order_code,
                            'description' => 'Thanh toán đơn hàng hoa tươi #' . $order->order_code,
                            'amount' => [
                                'currency_code' => 'USD',
                                'value' => number_format($usdAmount, 2, '.', ''),
                            ],
                        ],
                    ],
                    'application_context' => [
                        'return_url' => route('paypal.success', ['order_id' => $order->id]),
                        'cancel_url' => route('paypal.cancel', ['order_id' => $order->id]),
                        'brand_name' => 'BloomGift Shop',
                        'user_action' => 'PAY_NOW',
                    ],
                ]);

            if ($orderResponse->successful()) {
                $orderData = $orderResponse->json();
                foreach ($orderData['links'] as $link) {
                    if ($link['rel'] === 'approve') {
                        return redirect()->away($link['href']);
                    }
                }
            }

            throw new \Exception('Không lấy được link thanh toán từ PayPal.');
        } catch (\Throwable $e) {
            return redirect()->route('checkout.index')->with('error', 'Lỗi PayPal: ' . $e->getMessage());
        }
    }

    /**
     * 4. Xử lý khi thanh toán PayPal thành công
     */
    public function paypalSuccess(Request $request)
    {
        $orderId = $request->query('order_id');
        $order = Order::findOrFail($orderId);

        $token = $request->query('token');
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.client_secret');
        $baseUrl = config('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');

        if ($token && !empty($clientId) && !empty($secret)) {
            try {
                $authResponse = Http::asForm()
                    ->withBasicAuth($clientId, $secret)
                    ->post("{$baseUrl}/v1/oauth2/token", [
                        'grant_type' => 'client_credentials',
                    ]);

                if ($authResponse->successful()) {
                    $accessToken = $authResponse->json()['access_token'];

                    Http::withToken($accessToken)
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->post("{$baseUrl}/v2/checkout/orders/{$token}/capture");
                }
            } catch (\Throwable $e) {
                // Bỏ qua nếu là token giả lập
            }
        }

        // Cập nhật trạng thái đơn hàng sang ĐÃ THANH TOÁN
        $order->update([
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
            'status' => 'CONFIRMED',
        ]);

        Payment::where('order_id', $order->id)->update([
            'status' => 'completed',
            'transaction_id' => $token ?? ('PAYPAL_' . Str::random(10)),
            'paid_at' => now(),
        ]);

        if (\Illuminate\Support\Facades\Route::has('orders.show')) {
            return redirect()->route('orders.show', $order)->with('success', 'Thanh toán PayPal thành công! Đơn hoa #' . $order->order_code . ' đã được thanh toán.');
        }

        if (\Illuminate\Support\Facades\Route::has('admin.orders.show')) {
            return redirect()->route('admin.orders.show', $order)->with('success', 'Thanh toán PayPal thành công! Đơn hoa #' . $order->order_code . ' đã được thanh toán.');
        }

        return redirect()->route('home')->with('success', 'Thanh toán PayPal thành công! Đơn hoa #' . $order->order_code . ' đã được xác nhận.');
    }

    /**
     * 5. Hủy thanh toán PayPal
     */
    public function paypalCancel(Request $request)
    {
        return redirect()->route('checkout.index')->with('error', 'Bạn đã hủy quá trình thanh toán qua PayPal.');
    }
}

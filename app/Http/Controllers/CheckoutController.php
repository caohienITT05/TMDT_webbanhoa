<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\InventoryService;
use App\Support\CheckoutPricing;
use App\Mail\OrderConfirmationMail;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutController extends Controller
{
    private function ensureActiveCustomer(): void
    {
        $customer = Auth::user();
        abort_unless($customer && $customer->role === 'customer' && $customer->is_active, 403);
    }

    private function orderOwnedByCurrentCustomer(int|string|null $orderId): Order
    {
        $this->ensureActiveCustomer();
        $order = Order::findOrFail($orderId);
        abort_unless((int) $order->user_id === (int) Auth::id(), 403);

        return $order;
    }

    private function logTransaction(string $event, Order $order, array $context = []): void
    {
        Log::channel('transactions')->info($event, array_merge([
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'user_id' => $order->user_id,
            'payment_method' => $order->payment_method,
            'amount' => (float) $order->total,
            'payment_status' => $order->payment_status,
            'order_status' => $order->order_status,
        ], $context));
    }

    private function cartItemsForCurrentCustomer()
    {
        return CartItem::with('product')->where('user_id', Auth::id())->get();
    }

    private function currentProductPrice(Product $product): float
    {
        $regularPrice = (float) $product->price;
        $salePrice = (float) ($product->sale_price ?? 0);

        return $salePrice > 0 && $salePrice < $regularPrice ? $salePrice : $regularPrice;
    }

    private function lockedVoucherForCheckout(?int $voucherId, float $subtotal): ?Voucher
    {
        if (! $voucherId) {
            return null;
        }

        $voucher = Voucher::query()->lockForUpdate()->find($voucherId);

        return $voucher && $voucher->isRedeemableFor($subtotal) ? $voucher : null;
    }

    private function paypalVoucherSessionKey(int $orderId): string
    {
        return 'paypal_voucher_for_order_' . $orderId;
    }

    private function rememberVoucherForPayPal(Order $order, ?Voucher $voucher): void
    {
        if ($voucher) {
            Session::put($this->paypalVoucherSessionKey($order->id), $voucher->id);
        }
    }

    private function paypalVoucherIdForOrder(Order $order): ?int
    {
        $voucherId = (int) Session::get($this->paypalVoucherSessionKey($order->id), 0);

        return $voucherId > 0 ? $voucherId : null;
    }

    private function logPayPalFailure(Order $order, string $stage, ?Response $response = null, ?Throwable $exception = null): void
    {
        $payload = $response?->json();
        $this->logTransaction('payment_failed', $order, array_filter([
            'stage' => $stage,
            'http_status' => $response?->status(),
            'paypal_error_code' => data_get($payload, 'name') ?? data_get($payload, 'error'),
            'paypal_issue' => data_get($payload, 'details.0.issue'),
            'paypal_debug_id' => data_get($payload, 'debug_id') ?? $response?->header('paypal-debug-id'),
            'paypal_order_status' => data_get($payload, 'status'),
            'paypal_capture_status' => data_get($payload, 'purchase_units.0.payments.captures.0.status'),
            'paypal_capture_id_present' => filled(data_get($payload, 'purchase_units.0.payments.captures.0.id')),
            'exception_type' => $exception ? get_class($exception) : null,
        ], fn ($value) => $value !== null && $value !== ''));
    }

    private function paypalFailureRedirect(Order $order): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('orders.show', $order)
            ->with('error', 'PayPal chưa khởi tạo được thanh toán. Đơn hàng vẫn được lưu; bạn có thể thử lại tại trang chi tiết đơn hàng.');
    }

    private function clearCartAfterPayPalApproval(Order $order): void
    {
        $orderItems = $order->items()->get(['product_id', 'quantity', 'price'])
            ->map(fn ($item) => [(int) $item->product_id, (int) $item->quantity, round((float) $item->price, 2)])
            ->sortBy(fn ($item) => implode(':', $item))
            ->values()
            ->all();
        $cartItems = CartItem::query()->where('user_id', $order->user_id)->get(['product_id', 'quantity', 'price'])
            ->map(fn ($item) => [(int) $item->product_id, (int) $item->quantity, round((float) $item->price, 2)])
            ->sortBy(fn ($item) => implode(':', $item))
            ->values()
            ->all();

        if ($cartItems && $cartItems === $orderItems) {
            CartItem::query()->where('user_id', $order->user_id)->delete();
            Session::forget(['voucher_discount', 'voucher_code', 'voucher_id', 'shipping_fee']);
        }

        $buyNow = Session::get('buy_now');
        if (count($orderItems) === 1
            && (int) ($buyNow['product_id'] ?? 0) === $orderItems[0][0]
            && (int) ($buyNow['quantity'] ?? 0) === $orderItems[0][1]) {
            Session::forget(['buy_now', 'buy_now_voucher_code', 'buy_now_voucher_discount', 'buy_now_voucher_id']);
        }
    }

    private function capturePayPalOrder(?string $token, Order $order): array
    {
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.client_secret');

        if (empty($clientId) || empty($secret)) {
            if (app()->environment('testing') && str_starts_with((string) $token, 'MOCK-SANDBOX-')) {
                return [
                    'status' => 'COMPLETED',
                    'purchase_units' => [[
                        'reference_id' => $order->order_code,
                        'payments' => ['captures' => [[
                            'id' => $token,
                            'amount' => [
                                'currency_code' => 'USD',
                                'value' => number_format(round((float) $order->total / (float) config('services.paypal.exchange_rate', 26054), 2), 2, '.', ''),
                            ],
                        ]]],
                    ]],
                ];
            }

            throw new \RuntimeException('paypal_not_configured');
        }

        if (! $token) {
            throw new \RuntimeException('paypal_token_missing');
        }

        $baseUrl = config('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');
        $timeout = (int) config('services.paypal.timeout', 12);
        $authResponse = Http::asForm()
            ->connectTimeout(5)
            ->timeout($timeout)
            ->withBasicAuth($clientId, $secret)
            ->post("{$baseUrl}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

        if (! $authResponse->successful()) {
            $this->logPayPalFailure($order, 'capture_oauth', $authResponse);
            throw new \RuntimeException('paypal_oauth_failed');
        }

        $accessToken = $authResponse->json('access_token');
        if (! is_string($accessToken) || $accessToken === '') {
            $this->logPayPalFailure($order, 'capture_oauth', $authResponse);
            throw new \RuntimeException('paypal_access_token_missing');
        }

        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('paypal_token_missing');
        }

        $orderUrl = "{$baseUrl}/v2/checkout/orders/" . rawurlencode($token);
        $orderResponse = Http::withToken($accessToken)
            ->connectTimeout(5)
            ->timeout($timeout)
            ->acceptJson()
            ->get($orderUrl);
        $paypalOrder = $orderResponse->json();
        if (! $orderResponse->successful() || ! $this->isVerifiedPayPalOrder($paypalOrder, $order, $token)) {
            $this->logPayPalFailure($order, 'capture_preflight', $orderResponse);
            throw new \RuntimeException('paypal_order_not_verified');
        }

        $payeeMerchantId = data_get($paypalOrder, 'purchase_units.0.payee.merchant_id');
        if (data_get($paypalOrder, 'status') === 'COMPLETED') {
            if ($this->isVerifiedPayPalCapture($paypalOrder, $order, $token, $payeeMerchantId)) {
                return $paypalOrder;
            }

            if (data_get($paypalOrder, 'purchase_units.0.payments.captures.0.status') === 'PENDING') {
                $this->logTransaction('payment_capture_pending', $order, [
                    'paypal_order_id' => $token,
                    'paypal_capture_id' => data_get($paypalOrder, 'purchase_units.0.payments.captures.0.id'),
                    'paypal_capture_status' => 'PENDING',
                    'paypal_capture_reason' => data_get($paypalOrder, 'purchase_units.0.payments.captures.0.status_details.reason'),
                    'paypal_capture_amount' => data_get($paypalOrder, 'purchase_units.0.payments.captures.0.amount.value'),
                    'paypal_capture_currency' => data_get($paypalOrder, 'purchase_units.0.payments.captures.0.amount.currency_code'),
                ]);

                throw new \RuntimeException('paypal_capture_pending');
            }

            throw new \RuntimeException('paypal_capture_not_verified');
        }

        if (data_get($paypalOrder, 'status') !== 'APPROVED') {
            throw new \RuntimeException('paypal_order_not_approved');
        }

        $captureRequestId = 'bgc-'.substr(hash('sha256', 'bloomgift-paypal-capture:'.$order->getKey()), 0, 32);
        $captureResponse = Http::withToken($accessToken)
            ->connectTimeout(5)
            ->timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders(['PayPal-Request-Id' => $captureRequestId])
            ->post("{$baseUrl}/v2/checkout/orders/" . rawurlencode($token) . '/capture', (object) []);

        $capture = $captureResponse->json();
        if ($captureResponse->successful() && $this->isVerifiedPayPalCapture($capture, $order, $token, $payeeMerchantId)) {
            return $capture;
        }

        // A capture may succeed at PayPal while the response is lost or the
        // local transaction fails. Read the PayPal order before considering a
        // retry; never issue a second capture to reconcile that state.
        $lookupResponse = Http::withToken($accessToken)
            ->connectTimeout(5)
            ->timeout($timeout)
            ->acceptJson()
            ->get($orderUrl);
        $paypalOrder = $lookupResponse->json();
        if ($lookupResponse->successful() && $this->isVerifiedPayPalOrder($paypalOrder, $order, $token)
            && $this->isVerifiedPayPalCapture($paypalOrder, $order, $token, $payeeMerchantId)) {
            return $paypalOrder;
        }

        if ($lookupResponse->successful()
            && data_get($paypalOrder, 'status') === 'COMPLETED'
            && data_get($paypalOrder, 'purchase_units.0.payments.captures.0.status') === 'PENDING') {
            $this->logTransaction('payment_capture_pending', $order, [
                'paypal_order_id' => $token,
                'paypal_capture_id' => data_get($paypalOrder, 'purchase_units.0.payments.captures.0.id'),
                'paypal_capture_status' => 'PENDING',
                'paypal_capture_reason' => data_get($paypalOrder, 'purchase_units.0.payments.captures.0.status_details.reason'),
                'paypal_capture_amount' => data_get($paypalOrder, 'purchase_units.0.payments.captures.0.amount.value'),
                'paypal_capture_currency' => data_get($paypalOrder, 'purchase_units.0.payments.captures.0.amount.currency_code'),
            ]);

            throw new \RuntimeException('paypal_capture_pending');
        }

        $this->logPayPalFailure($order, $captureResponse->successful() ? 'capture_verification' : 'capture', $captureResponse);
        throw new \RuntimeException('paypal_capture_not_verified');
    }

    private function isVerifiedPayPalOrder(array $payload, Order $order, string $token): bool
    {
        $amount = data_get($payload, 'purchase_units.0.amount');
        $referenceId = data_get($payload, 'purchase_units.0.reference_id');
        $payeeMerchantId = data_get($payload, 'purchase_units.0.payee.merchant_id');
        $expectedMerchantId = config('services.paypal.merchant_id');
        $expectedAmount = number_format(round((float) $order->total / (float) config('services.paypal.exchange_rate', 26054), 2), 2, '.', '');

        return is_string(data_get($payload, 'id'))
            && hash_equals($token, data_get($payload, 'id'))
            && data_get($payload, 'intent') === 'CAPTURE'
            && in_array(data_get($payload, 'status'), ['CREATED', 'APPROVED', 'COMPLETED'], true)
            && is_array($amount)
            && ($amount['currency_code'] ?? null) === 'USD'
            && number_format((float) ($amount['value'] ?? -1), 2, '.', '') === $expectedAmount
            && is_string($referenceId)
            && hash_equals((string) $order->order_code, $referenceId)
            && is_string($payeeMerchantId)
            && $payeeMerchantId !== ''
            && (! is_string($expectedMerchantId) || $expectedMerchantId === '' || hash_equals($expectedMerchantId, $payeeMerchantId));
    }

    private function isVerifiedPayPalCapture(array $payload, Order $order, string $token, string $expectedPayeeMerchantId): bool
    {
        $capturedAmount = data_get($payload, 'purchase_units.0.payments.captures.0.amount');
        $captureStatus = data_get($payload, 'purchase_units.0.payments.captures.0.status');
        $referenceId = data_get($payload, 'purchase_units.0.reference_id');
        $captureId = data_get($payload, 'purchase_units.0.payments.captures.0.id');
        $expectedAmount = number_format(round((float) $order->total / (float) config('services.paypal.exchange_rate', 26054), 2), 2, '.', '');

        return data_get($payload, 'status') === 'COMPLETED'
            && $captureStatus === 'COMPLETED'
            && is_string(data_get($payload, 'id'))
            && hash_equals($token, data_get($payload, 'id'))
            && is_array($capturedAmount)
            && is_string($captureId)
            && $captureId !== ''
            && ($capturedAmount['currency_code'] ?? null) === 'USD'
            && number_format((float) ($capturedAmount['value'] ?? -1), 2, '.', '') === $expectedAmount
            && is_string($referenceId)
            && hash_equals((string) $order->order_code, $referenceId)
            && (! filled(data_get($payload, 'purchase_units.0.payee.merchant_id'))
                || hash_equals($expectedPayeeMerchantId, (string) data_get($payload, 'purchase_units.0.payee.merchant_id')));
    }

    private function checkoutItemsMatch(Order $order, array $orderProducts): bool
    {
        $normalize = fn ($rows) => collect($rows)
            ->map(fn ($item) => [
                (int) $item['product_id'],
                (int) $item['quantity'],
                round((float) $item['price'], 2),
                round((float) $item['subtotal'], 2),
            ])
            ->sortBy(fn ($item) => implode(':', $item))
            ->values()
            ->all();

        return $normalize($order->items->toArray()) === $normalize($orderProducts);
    }

    private function findPendingPayPalCheckout(
        array $validated,
        array $orderProducts,
        float $subtotal,
        float $discount,
        array $fees,
        float $total,
        ?string $fullNote
    ): ?Order {
        foreach (['gift_card_fee', 'gift_wrap_fee'] as $field) {
            if (! Schema::hasColumn('orders', $field) && (float) $fees[$field] !== 0.0) {
                return null;
            }
        }

        $query = Order::query()
            ->with('items')
            ->where('user_id', Auth::id())
            ->where('payment_method', 'paypal')
            ->where('payment_status', 'pending')
            ->where('order_status', 'pending')
            ->where('recipient_name', $validated['recipient_name'])
            ->where('recipient_phone', $validated['recipient_phone'])
            ->where('recipient_address', $validated['recipient_address'])
            ->whereDate('delivery_date', $validated['delivery_date'])
            ->where('delivery_slot_id', $validated['delivery_slot_id'])
            ->where('subtotal', $subtotal)
            ->where('discount', $discount)
            ->where('shipping_fee', $fees['shipping_fee'])
            ->where('total', $total)
            ->where('note', $fullNote)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->whereHas('payments', fn ($payments) => $payments->where('payment_method', 'paypal')->where('status', 'pending'))
            ->latest('id');

        foreach (['gift_card_fee', 'gift_wrap_fee'] as $field) {
            if (Schema::hasColumn('orders', $field)) {
                $query->where($field, $fees[$field]);
            }
        }

        return $query->get()->first(fn (Order $order) => $this->checkoutItemsMatch($order, $orderProducts));
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
        $this->ensureActiveCustomer();
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
        $this->ensureActiveCustomer();
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
    public function store(Request $request, InventoryService $inventory)
    {
        $this->ensureActiveCustomer();
        $validated = $request->validate([
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'recipient_address' => 'required|string|max:500',
            'recipient_email' => ['nullable', 'email:rfc', 'max:255'],
            'delivery_date' => 'required|date|after_or_equal:today',
            'delivery_slot_id' => 'required',
            'payment_method' => 'required|in:cod,paypal',
            'shipping_fee' => ['nullable', 'numeric', Rule::in(CheckoutPricing::SHIPPING_FEES)],
            'gift_card_fee' => ['nullable', 'numeric', Rule::in(CheckoutPricing::GIFT_CARD_FEES)],
            'gift_wrap_fee' => ['nullable', 'numeric', Rule::in(CheckoutPricing::GIFT_WRAP_FEES)],
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

        $fees = CheckoutPricing::fees($validated);
        $voucherKeyPrefix = $checkoutMode === 'buy_now' ? 'buy_now_voucher_' : 'voucher_';
        $voucherId = (int) Session::get($voucherKeyPrefix . 'id');

        // Gom Lời chúc thiệp + Ghi chú vào một chuỗi hoàn chỉnh
        $noteParts = [];
        if ($request->filled('card_message')) {
            $noteParts[] = "💌 Lời chúc thiệp: " . $request->card_message;
        }
        if ($request->filled('order_note')) {
            $noteParts[] = "Ghi chú: " . $request->order_note;
        }
        $fullNote = !empty($noteParts) ? implode(" | ", $noteParts) : null;


        // Lock the customer row so repeated PayPal submissions can reuse the
        // matching pending order instead of creating duplicate orders/payments.
        [$order, $voucher, $created] = DB::transaction(function () use ($validated, $orderProducts, $subtotal, $fees, $voucherId, $fullNote, $inventory) {
            DB::table('users')->where('id', Auth::id())->lockForUpdate()->first();
            $voucher = $this->lockedVoucherForCheckout($voucherId, $subtotal);

            if ($voucherId && ! $voucher) {
                throw ValidationException::withMessages([
                    'voucher' => 'Mã giảm giá không còn hợp lệ. Vui lòng áp dụng lại mã khác.',
                ]);
            }

            $discount = $voucher ? min((float) $voucher->calculateDiscount($subtotal), $subtotal) : 0;
            $total = CheckoutPricing::total($subtotal, $fees, $discount);

            if ($validated['payment_method'] === 'paypal') {
                $existingOrder = $this->findPendingPayPalCheckout(
                    $validated,
                    $orderProducts,
                    $subtotal,
                    $discount,
                    $fees,
                    $total,
                    $fullNote
                );

                if ($existingOrder) {
                    return [$existingOrder, $voucher, false];
                }
            }

            $orderCode = 'BG' . now()->format('YmdHis') . strtoupper(Str::random(4));

            $order = Order::create([
                'user_id' => Auth::id(),
                ...(Schema::hasColumn('orders', 'customer_email') ? [
                    'customer_email' => $validated['recipient_email'] ?? Auth::user()?->email,
                ] : []),
                'order_code' => $orderCode,
                'recipient_name' => $validated['recipient_name'],
                'recipient_phone' => $validated['recipient_phone'],
                'recipient_address' => $validated['recipient_address'],
                'delivery_date' => $validated['delivery_date'],
                'delivery_slot_id' => $validated['delivery_slot_id'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_fee' => $fees['shipping_fee'],
                ...(Schema::hasColumn('orders', 'gift_card_fee') ? ['gift_card_fee' => $fees['gift_card_fee']] : []),
                ...(Schema::hasColumn('orders', 'gift_wrap_fee') ? ['gift_wrap_fee' => $fees['gift_wrap_fee']] : []),
                'total' => $total,
                ...(Schema::hasColumn('orders', 'total_amount') ? ['total_amount' => $total] : []),
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'order_status' => 'pending',
                ...(Schema::hasColumn('orders', 'status') ? ['status' => 'PENDING'] : []),
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

            // Reserve stock in the same transaction as order creation. This
            // applies to COD and PayPal pending orders and is idempotent.
            $inventory->reserveForOrder($order, array_map(static fn (array $prod): array => [
                'product_id' => $prod['product_id'],
                'quantity' => $prod['quantity'],
            ], $orderProducts));

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $validated['payment_method'],
                'transaction_id' => null,
                'amount' => $total,
                'status' => 'pending',
                'paid_at' => null,
            ]);

            if ($voucher && $validated['payment_method'] === 'cod') {
                $voucher->increment('used_count');
            }

            return [$order, $voucher, true];
        });

        if ($validated['payment_method'] === 'paypal') {
            $this->rememberVoucherForPayPal($order, $voucher);
        }

        if (! $created) {
            $this->logTransaction('payment_retry_requested', $order);

            return redirect()->route('paypal.create', ['order' => $order->id]);
        }

        $this->logTransaction('order_created', $order);

        // COD completes locally; a PayPal cart stays intact until the provider
        // has returned a valid approval URL.
        if ($validated['payment_method'] === 'cod') {
            if ($checkoutMode === 'cart') {
                CartItem::where('user_id', Auth::id())->delete();
                Session::forget(['voucher_discount', 'voucher_code', 'voucher_id', 'shipping_fee']);
            } else {
                Session::forget(['buy_now', 'buy_now_voucher_code', 'buy_now_voucher_discount', 'buy_now_voucher_id']);
            }
        }

        // Gửi email hóa đơn xác nhận đơn hàng tự động
        try {
            $emailTo = Auth::user()->email;
            if ($emailTo) {
                Mail::to($emailTo)->send(new OrderConfirmationMail($order));
            }
        } catch (\Throwable $e) {
            \Log::error('Lỗi gửi email xác nhận đơn hàng: ' . $e->getMessage());
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
        return redirect()->route('paypal.create', ['order' => $order->id]);
    }

    /**
     * 3. Tạo phiên thanh toán trên PayPal Sandbox API v2
     */
    public function paypalCreate($orderId)
    {
        $this->ensureActiveCustomer();
        $order = $this->orderOwnedByCurrentCustomer($orderId);
        $this->logTransaction('payment_initiated', $order);

        if ($order->payment_method !== 'paypal') {
            return redirect()->route('orders.show', $order)->with('error', 'Đơn hàng này không sử dụng PayPal.');
        }

        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.show', $order)->with('success', 'Đơn hàng đã được thanh toán.');
        }

        if (in_array(strtolower((string) ($order->order_status ?: $order->status)), ['cancelled', 'canceled', 'returned', 'refunded'], true)) {
            return redirect()->route('orders.show', $order)->with('error', 'Đơn hàng đã hủy nên không thể thanh toán.');
        }

        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('payment_method', 'paypal')
            ->latest('id')
            ->first();

        if (! $payment || strtolower((string) $payment->status) !== 'pending') {
            return redirect()->route('orders.show', $order)->with('error', 'Không tìm thấy giao dịch PayPal đang chờ của đơn hàng.');
        }

        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.client_secret');
        $baseUrl = config('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');
        $exchangeRate = (float) config('services.paypal.exchange_rate', 26054);

        if ($exchangeRate <= 0) {
            $this->logPayPalFailure($order, 'configuration');

            return $this->paypalFailureRedirect($order);
        }

        $usdAmount = round((float) $order->total / $exchangeRate, 2);
        if ($usdAmount < 0.01) {
            $this->logPayPalFailure($order, 'amount_conversion');

            return redirect()->route('orders.show', $order)
                ->with('error', 'Tổng đơn hàng thấp hơn mức thanh toán tối thiểu của PayPal. Vui lòng liên hệ BloomGift.');
        }

        if (empty($clientId) || empty($secret)) {
            if (app()->environment('testing')) {
                $token = 'MOCK-SANDBOX-' . strtoupper(Str::random(10));
                $payment->update(['transaction_id' => $token]);
                $this->clearCartAfterPayPalApproval($order);

                return redirect()->route('paypal.success', [
                    'order_id' => $order->id,
                    'token' => $token,
                    'PayerID' => 'MOCK-PAYER-' . strtoupper(Str::random(6)),
                ]);
            }

            $this->logPayPalFailure($order, 'configuration');

            return $this->paypalFailureRedirect($order);
        }

        $timeout = (int) config('services.paypal.timeout', 12);
        $stage = 'oauth';
        $failureResponse = null;
        try {
            $authResponse = Http::asForm()
                ->connectTimeout(5)
                ->timeout($timeout)
                ->withBasicAuth($clientId, $secret)
                ->post("{$baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if (!$authResponse->successful()) {
                $failureResponse = $authResponse;
                throw new \RuntimeException('paypal_oauth_failed');
            }

            $accessToken = $authResponse->json('access_token');
            if (! is_string($accessToken) || $accessToken === '') {
                $failureResponse = $authResponse;
                throw new \RuntimeException('paypal_access_token_missing');
            }

            // Reuse the order created by an earlier attempt. A repeated POST
            // with the same idempotency key can return a completed order
            // without an approval link, which is not a new-order failure.
            if (is_string($payment->transaction_id) && $payment->transaction_id !== '') {
                $existingOrderId = $payment->transaction_id;
                $stage = 'existing_order_lookup';
                $existingOrderResponse = Http::withToken($accessToken)
                    ->connectTimeout(5)
                    ->timeout($timeout)
                    ->acceptJson()
                    ->get("{$baseUrl}/v2/checkout/orders/" . rawurlencode($existingOrderId));
                $existingOrder = $existingOrderResponse->json();

                if (! $existingOrderResponse->successful()
                    || ! is_array($existingOrder)
                    || ! $this->isVerifiedPayPalOrder($existingOrder, $order, $existingOrderId)) {
                    $failureResponse = $existingOrderResponse;
                    throw new \RuntimeException('paypal_existing_order_not_verified');
                }

                $existingStatus = data_get($existingOrder, 'status');
                if ($existingStatus === 'CREATED') {
                    $approvalUrl = collect(data_get($existingOrder, 'links', []))
                        ->firstWhere('rel', 'approve')['href'] ?? null;
                    $approvalHost = is_string($approvalUrl) ? parse_url($approvalUrl, PHP_URL_HOST) : null;
                    $apiHost = (string) parse_url($baseUrl, PHP_URL_HOST);
                    $expectedHost = str_contains(strtolower($apiHost), 'sandbox')
                        ? 'www.sandbox.paypal.com'
                        : 'www.paypal.com';

                    if (is_string($approvalUrl)
                        && parse_url($approvalUrl, PHP_URL_SCHEME) === 'https'
                        && strtolower((string) $approvalHost) === $expectedHost) {
                        return redirect()->away($approvalUrl);
                    }

                    $failureResponse = $existingOrderResponse;
                    throw new \RuntimeException('paypal_existing_approval_url_invalid');
                }

                if ($existingStatus === 'APPROVED') {
                    return redirect()->route('paypal.success', [
                        'order_id' => $order->id,
                        'token' => $existingOrderId,
                    ]);
                }

                if ($existingStatus === 'COMPLETED') {
                    $payeeMerchantId = data_get($existingOrder, 'purchase_units.0.payee.merchant_id');
                    if ($this->isVerifiedPayPalCapture($existingOrder, $order, $existingOrderId, $payeeMerchantId)) {
                        return redirect()->route('paypal.success', [
                            'order_id' => $order->id,
                            'token' => $existingOrderId,
                        ]);
                    }

                    if (data_get($existingOrder, 'purchase_units.0.payments.captures.0.status') === 'PENDING') {
                        $this->logTransaction('payment_capture_pending', $order, [
                            'paypal_order_id' => $existingOrderId,
                            'paypal_capture_id' => data_get($existingOrder, 'purchase_units.0.payments.captures.0.id'),
                            'paypal_capture_status' => 'PENDING',
                            'paypal_capture_reason' => data_get($existingOrder, 'purchase_units.0.payments.captures.0.status_details.reason'),
                        ]);

                        return redirect()->route('orders.show', $order)
                            ->with('error', 'PayPal hiện trả Capture ở trạng thái PENDING (chưa COMPLETED), nên BloomGift chưa thể xác nhận đã thanh toán. Hãy bấm “Kiểm tra hoặc tiếp tục PayPal” để truy vấn trạng thái mới nhất; chỉ tải lại trang sẽ không làm Capture được duyệt. Không thanh toán lại. Nếu Capture đang PENDING_REVIEW, PayPal Sandbox cần hoàn tất việc kiểm tra trước khi đơn có thể chuyển sang đã thanh toán.');
                    }
                }

                $failureResponse = $existingOrderResponse;
                throw new \RuntimeException('paypal_existing_order_not_resumable');
            }

            $stage = 'create_order';
            $orderResponse = Http::withToken($accessToken)
                ->connectTimeout(5)
                ->timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'PayPal-Request-Id' => 'bg-'.substr(hash('sha256', 'bloomgift-paypal-create:'.$payment->getKey()), 0, 32),
                ])
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
                $paypalOrderId = data_get($orderData, 'id');
                $approvalUrl = collect(data_get($orderData, 'links', []))
                    ->firstWhere('rel', 'approve')['href'] ?? null;
                $approvalHost = is_string($approvalUrl) ? parse_url($approvalUrl, PHP_URL_HOST) : null;
                $apiHost = (string) parse_url($baseUrl, PHP_URL_HOST);
                $expectedHost = str_contains(strtolower($apiHost), 'sandbox')
                    ? 'www.sandbox.paypal.com'
                    : 'www.paypal.com';

                if (is_string($paypalOrderId)
                    && $paypalOrderId !== ''
                    && is_string($approvalUrl)
                    && parse_url($approvalUrl, PHP_URL_SCHEME) === 'https'
                    && strtolower((string) $approvalHost) === $expectedHost) {
                    $stored = $payment->update(['transaction_id' => $paypalOrderId]);
                    $payment->refresh();
                    if (! $stored || ! is_string($payment->transaction_id) || ! hash_equals($payment->transaction_id, $paypalOrderId)) {
                        throw new \RuntimeException('paypal_order_id_persistence_failed');
                    }
                    $this->clearCartAfterPayPalApproval($order);
                    $this->logTransaction('payment_approval_ready', $order, [
                        'payment_id' => $payment->id,
                        'paypal_order_id' => $paypalOrderId,
                    ]);

                    return redirect()->away($approvalUrl);
                }

                $failureResponse = $orderResponse;
                throw new \RuntimeException('paypal_approval_url_invalid');
            }

            $failureResponse = $orderResponse;
            throw new \RuntimeException('paypal_order_creation_failed');
        } catch (Throwable $e) {
            $this->logPayPalFailure($order, $stage, $failureResponse, $e);

            return $this->paypalFailureRedirect($order);
        }
    }

    /**
     * 4. Xử lý khi thanh toán PayPal thành công
     */
    public function paypalSuccess(Request $request)
    {
        $this->ensureActiveCustomer();
        $orderId = $request->query('order_id');
        $order = $this->orderOwnedByCurrentCustomer($orderId);
        $voucherId = $this->paypalVoucherIdForOrder($order);
        $token = $request->query('token');

        try {
            $order = DB::transaction(function () use ($order, $token, $voucherId) {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
                $payment = Payment::query()
                    ->where('order_id', $lockedOrder->id)
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                // A customer or PayPal can return to this URL more than once.
                // A completed payment is the idempotency marker for both capture
                // and voucher usage.
                if (! $payment) {
                    throw new \RuntimeException('Không tìm thấy giao dịch thanh toán của đơn hàng.');
                }

                if (strtolower((string) $payment->status) === 'completed') {
                    return $lockedOrder->fresh();
                }

                if ($lockedOrder->payment_method !== 'paypal'
                    || ! is_string($token)
                    || $token === ''
                    || ! is_string($payment->transaction_id)
                    || ! hash_equals($payment->transaction_id, $token)) {
                    throw new \RuntimeException('paypal_order_token_mismatch');
                }

                $voucher = $voucherId
                    ? Voucher::query()->lockForUpdate()->find($voucherId)
                    : null;

                if ($voucherId && (! $voucher || (int) $voucher->used_count >= (int) $voucher->usage_limit)) {
                    throw new \RuntimeException('Mã giảm giá đã hết lượt sử dụng.');
                }

                // Keep the voucher row locked across the capture so another
                // callback cannot capture the last voucher usage concurrently.
                $capture = $this->capturePayPalOrder($token, $lockedOrder);
                $captureId = data_get($capture, 'purchase_units.0.payments.captures.0.id') ?? $token;
                $payment->update([
                    'status' => 'completed',
                    'transaction_id' => $captureId,
                    'paid_at' => now(),
                ]);
                if ($voucher) {
                    $voucher->increment('used_count');
                }

                $lockedOrder->update([
                    'payment_status' => 'paid',
                    'order_status' => 'confirmed',
                    ...(Schema::hasColumn('orders', 'status') ? ['status' => 'CONFIRMED'] : []),
                ]);

                return $lockedOrder->fresh();
            });
        } catch (Throwable $e) {
            $this->logPayPalFailure($order, 'callback', null, $e);

            if ($e->getMessage() === 'paypal_capture_pending') {
                return redirect()->route('orders.show', $order)
                    ->with('error', 'PayPal hiện trả Capture ở trạng thái PENDING (chưa COMPLETED), nên BloomGift chưa thể xác nhận đã thanh toán. Hãy bấm “Kiểm tra hoặc tiếp tục PayPal” để truy vấn trạng thái mới nhất; chỉ tải lại trang sẽ không làm Capture được duyệt. Không thanh toán lại. Nếu Capture đang PENDING_REVIEW, PayPal Sandbox cần hoàn tất việc kiểm tra trước khi đơn có thể chuyển sang đã thanh toán.');
            }

            return redirect()->route('orders.show', $order)
                ->with('error', 'PayPal chưa xác nhận được thanh toán. Đơn vẫn đang chờ; bạn có thể thử lại từ trang đơn hàng.');
        }

        Session::forget($this->paypalVoucherSessionKey($order->id));
        $completedPayment = Payment::query()
            ->where('order_id', $order->id)
            ->whereRaw('LOWER(payment_method) = ?', ['paypal'])
            ->latest('id')
            ->first();
        $this->logTransaction('payment_success', $order, [
            'payment_id' => $completedPayment?->id,
            'paypal_order_id' => is_string($token) ? $token : null,
            'paypal_capture_id' => $completedPayment?->transaction_id,
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
        $this->ensureActiveCustomer();
        $order = $this->orderOwnedByCurrentCustomer($request->query('order_id'));
        $this->logTransaction('payment_cancelled', $order);

        return redirect()->route('orders.show', $order)->with('error', 'Bạn đã hủy thanh toán PayPal. Đơn vẫn đang chờ và có thể thanh toán lại tại đây.');
    }
}

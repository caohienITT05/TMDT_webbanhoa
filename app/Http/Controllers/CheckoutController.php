<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Voucher;
use App\Mail\OrderConfirmationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * 1. Hiển thị trang thanh toán với dữ liệu thực tế từ giỏ hàng
     */
    public function index()
    {
        $userId = Auth::id();

        // Lấy giỏ hàng thật từ database (hỗ trợ cả user đăng nhập lẫn khách vãng lai)
        $cartItems = CartItem::with('product')
            ->where(function ($query) use ($userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', Session::getId());
                }
            })->get();

        // Nếu giỏ hàng trống thì quay về trang cart
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống, vui lòng chọn hoa trước!');
        }

        // Tính tạm tính từ giỏ hàng thực tế
        $subtotal = $cartItems->sum(function ($item) {
            $price = (float) ($item->price ?? $item->product?->sale_price ?? $item->product?->price ?? 0);
            return $price * $item->quantity;
        });

        // Danh sách khung giờ giao hoa
        $deliverySlots = DeliverySlot::where('is_active', true)->get();

        // Voucher giảm giá đã áp dụng trong Session
        $voucherDiscount = (float) Session::get('voucher_discount', 0);
        $voucherCode = Session::get('voucher_code', null);

        // Voucher Flash Sale đang hoạt động để gợi ý cho khách
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
            'activeVouchers'
        ));
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

        $userId = Auth::id();

        // Lấy giỏ hàng thực tế từ database
        $cartItems = CartItem::with('product')
            ->where(function ($query) use ($userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', Session::getId());
                }
            })->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống!');
        }

        // Tính toán tạm tính chính xác từ giỏ
        $subtotal = 0;
        $orderProducts = [];

        foreach ($cartItems as $item) {
            $price = (float) ($item->price ?? $item->product?->sale_price ?? $item->product?->price ?? 0);
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

        // Nhận các loại phí được chọn từ trang Checkout
        $shippingFee = (float) $request->input('shipping_fee', 0);
        $giftCardFee = (float) $request->input('gift_card_fee', 0);
        $giftWrapFee = (float) $request->input('gift_wrap_fee', 0);
        $discount = (float) Session::get('voucher_discount', 0);

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

        // Xóa giỏ hàng sau khi đặt thành công
        if (Auth::check()) {
            CartItem::where('user_id', Auth::id())->delete();
        } else {
            CartItem::where('session_id', Session::getId())->delete();
        }

        // Xóa thông tin voucher khỏi session
        Session::forget(['voucher_discount', 'voucher_code', 'voucher_id', 'shipping_fee']);

        // Gửi email hóa đơn xác nhận đơn hàng tự động
        try {
            $emailTo = Auth::user()->email ?? $request->input('recipient_email') ?? 'customer@bloomgift.vn';
            Mail::to($emailTo)->send(new OrderConfirmationMail($order));
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
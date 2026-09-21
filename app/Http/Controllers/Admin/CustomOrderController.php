<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomOrderRequest;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomOrderController extends Controller
{
    public function index(Request $request): View
    {
        $customOrderRequests = CustomOrderRequest::query()
            ->with(['user', 'order'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.custom-orders.index', compact('customOrderRequests'));
    }

    public function show(CustomOrderRequest $customOrderRequest): View
    {
        if (!$customOrderRequest->admin_viewed_at) {
            $customOrderRequest->forceFill(['admin_viewed_at' => now()])->save();
        }

        $customOrderRequest->load(['user', 'order']);

        return view('admin.custom-orders.show', compact('customOrderRequest'));
    }

    public function contacted(CustomOrderRequest $customOrderRequest): RedirectResponse
    {
        if (!in_array($customOrderRequest->status, ['pending', 'contacted'], true)) {
            return back()->with('error', 'Chỉ có thể đánh dấu đã liên hệ với yêu cầu đang chờ xử lý.');
        }

        $customOrderRequest->update([
            'status' => 'contacted',
            'contacted_at' => $customOrderRequest->contacted_at ?? now(),
        ]);

        return back()->with('success', 'Đã ghi nhận shop đã liên hệ khách hàng.');
    }

    public function accept(Request $request, CustomOrderRequest $customOrderRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if (!in_array($customOrderRequest->status, ['pending', 'contacted', 'accepted'], true)) {
            return back()->with('error', 'Yêu cầu này không thể chuyển sang trạng thái nhận.');
        }

        $customOrderRequest->update([
            'status' => 'accepted',
            'admin_note' => $validated['admin_note'] ?? $customOrderRequest->admin_note,
            'accepted_at' => $customOrderRequest->accepted_at ?? now(),
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Shop đã nhận yêu cầu. Bạn có thể xác nhận để tạo đơn khi đã chốt với khách.');
    }

    public function reject(Request $request, CustomOrderRequest $customOrderRequest): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        if (!in_array($customOrderRequest->status, ['pending', 'contacted', 'accepted', 'rejected'], true)) {
            return back()->with('error', 'Yêu cầu đã tạo đơn không thể bị từ chối.');
        }

        $customOrderRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'rejected_at' => $customOrderRequest->rejected_at ?? now(),
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Đã từ chối yêu cầu và lưu lý do phản hồi.');
    }

    public function createOrder(CustomOrderRequest $customOrderRequest): View|RedirectResponse
    {
        if ($customOrderRequest->status !== 'accepted') {
            return redirect()->route('admin.custom-orders.show', $customOrderRequest)
                ->with('error', 'Chỉ yêu cầu đã được nhận mới có thể tạo đơn hàng.');
        }

        $products = Product::query()->where('is_active', true)->orderBy('name')->get();
        $deliverySlots = DeliverySlot::query()->where('is_active', true)->orderBy('id')->get();

        return view('admin.custom-orders.create-order', compact('customOrderRequest', 'products', 'deliverySlots'));
    }

    public function storeOrder(Request $request, CustomOrderRequest $customOrderRequest): RedirectResponse
    {
        if ($customOrderRequest->status !== 'accepted' || $customOrderRequest->order_id) {
            return redirect()->route('admin.custom-orders.show', $customOrderRequest)
                ->with('error', 'Yêu cầu này không còn đủ điều kiện để tạo đơn hàng.');
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cod,paypal'],
            'delivery_slot_id' => ['nullable', 'exists:delivery_slots,id'],
            'delivery_date' => ['required', 'date', 'after_or_equal:today'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'max:20'],
            'recipient_address' => ['required', 'string', 'max:1000'],
            'order_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $product = Product::query()->whereKey($validated['product_id'])->where('is_active', true)->firstOrFail();

        if ((int) $validated['quantity'] > (int) $product->stock) {
            return back()->withInput()->with('error', 'Số lượng đặt vượt quá tồn kho của sản phẩm đã chọn.');
        }

        $order = DB::transaction(function () use ($validated, $customOrderRequest, $product) {
            $quantity = (int) $validated['quantity'];
            $unitPrice = (float) $validated['unit_price'];
            $subtotal = $unitPrice * $quantity;
            $shippingFee = (float) ($validated['shipping_fee'] ?? 0);
            $discount = min((float) ($validated['discount'] ?? 0), $subtotal + $shippingFee);
            $total = max(0, $subtotal + $shippingFee - $discount);
            $requestSummary = 'Yêu cầu ' . $customOrderRequest->request_code . ': '
                . ($customOrderRequest->message ?: 'Không có nội dung bổ sung.');
            $note = trim($requestSummary . "\n" . ($validated['order_note'] ?? ''));

            $order = Order::create([
                'order_code' => 'BG' . now()->format('YmdHis') . strtoupper(Str::random(4)),
                'user_id' => $customOrderRequest->user_id,
                'delivery_slot_id' => $validated['delivery_slot_id'] ?? null,
                'recipient_name' => $validated['recipient_name'],
                'recipient_phone' => $validated['recipient_phone'],
                'recipient_address' => $validated['recipient_address'],
                'delivery_date' => $validated['delivery_date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'discount_amount' => $discount,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'total_amount' => $total,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'status' => 'PENDING',
                'note' => $note,
                'order_note' => $note,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'price' => $unitPrice,
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $validated['payment_method'],
                'transaction_id' => null,
                'amount' => $total,
                'status' => 'pending',
                'paid_at' => null,
            ]);

            $customOrderRequest->update([
                'order_id' => $order->id,
                'status' => 'converted',
            ]);

            return $order;
        });

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Đã tạo đơn hàng ' . $order->order_code . ' từ yêu cầu ' . $customOrderRequest->request_code . '.');
    }
}

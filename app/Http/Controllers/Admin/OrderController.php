<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Mail\OrderDeliveredMail;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    /**
     * Danh sách đơn hàng.
     */
    public function index()
    {
        $orders = Order::with([
            'deliverySlot',
            'payment',
            'user',
        ])
            ->latest()
            ->paginate(10);

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Chi tiết đơn hàng.
     */
    public function show(Order $order)
    {
        // Tải các quan hệ (hỗ trợ cả orderItems lẫn items)
        $relations = ['deliverySlot', 'payment', 'user'];
        if (method_exists($order, 'orderItems')) {
            $relations[] = 'orderItems.product';
        }
        if (method_exists($order, 'items')) {
            $relations[] = 'items';
        }

        $order->load($relations);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Cập nhật trạng thái đơn hàng và đồng bộ doanh thu.
     */
    /**
     * Cập nhật trạng thái đơn hàng và gửi mail hoàn tất.
     */
    /**
     * Cập nhật trạng thái đơn hàng và gửi mail khi hoàn thành.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $inputStatus = $request->input('status') ?? $request->input('order_status');
        $request->merge(['order_status' => strtolower($inputStatus)]);

        $validated = $request->validate([
            'order_status' => [
                'required',
                'in:pending,confirmed,preparing,processing,shipping,completed,cancelled',
            ],
        ]);

        $statusLower = strtolower($validated['order_status']);
        $statusUpper = strtoupper($statusLower);

        $updateData = [
            'order_status' => $statusLower,
            'status' => $statusUpper,
        ];

        // Nếu chuyển sang hoàn tất / giao thành công
        if (in_array($statusLower, ['completed', 'delivered'])) {
            $updateData['payment_status'] = 'paid';

            Payment::where('order_id', $order->id)->update([
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            // Tải quan hệ để nạp dữ liệu đầy đủ cho giao diện Mail
            $order->loadMissing(['user', 'deliverySlot', 'orderItems']);

            // Lấy email khách hàng
            $customerEmail = $order->user->email ?? $order->recipient_email ?? null;

            if ($customerEmail) {
                try {
                    Mail::to($customerEmail)->send(new OrderDeliveredMail($order));
                } catch (\Throwable $e) {
                    \Log::error('Lỗi gửi mail giao hàng thành công: ' . $e->getMessage());
                }
            }
        }

        // Cập nhật trạng thái vào cơ sở dữ liệu
        $order->update($updateData);

        return redirect()
            ->back()
            ->with('success', 'Cập nhật trạng thái đơn hàng hoa #' . ($order->order_code ?? $order->id) . ' thành công.');
    }
}
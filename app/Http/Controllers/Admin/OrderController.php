<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

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
    public function updateStatus(Request $request, Order $order)
    {
        // Chấp nhận cả giá trị gửi lên từ trường status hoặc order_status
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

        // Chuẩn bị dữ liệu cập nhật đồng bộ cả 2 cột
        $updateData = [
            'order_status' => $statusLower,
            'status' => $statusUpper,
        ];

        // Nếu chuyển sang hoàn tất/giao thành công: tự động xác nhận đã thu tiền
        if (in_array($statusLower, ['completed', 'delivered'])) {
            $updateData['payment_status'] = 'paid';

            // Cập nhật bản ghi giao dịch trong bảng payments nếu có
            Payment::where('order_id', $order->id)->update([
                'status' => 'completed',
                'paid_at' => now(),
            ]);
        }

        $order->update($updateData);

        return redirect()
            ->back()
            ->with('success', 'Cập nhật trạng thái đơn hàng hoa #' . ($order->order_code ?? $order->id) . ' thành công.');
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
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
        $order->load([
            'items',
            'deliverySlot',
            'payment',
        ]);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Cập nhật trạng thái đơn hàng.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'order_status' => [
                'required',
                'in:pending,confirmed,processing,shipping,completed,cancelled',
            ],
        ]);

        $order->update([
            'order_status' => $validated['order_status'],
        ]);

        return redirect()
            ->back()
            ->with('success', 'Cập nhật trạng thái đơn hàng thành công.');
    }
}

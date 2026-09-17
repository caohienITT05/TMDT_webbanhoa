<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Danh sách đơn hàng của khách hàng.
     */
    public function index()
    {
        $orders = Order::with([
            'deliverySlot',
            'payment',
        ])
        ->latest()
        ->paginate(10);

        return view('orders.index', compact('orders'));
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

        return view('orders.show', compact('order'));
    }

    /**
     * Hủy đơn hàng.
     */
    public function cancel(Order $order)
    {
        if (!in_array($order->order_status, [
            'pending',
            'confirmed',
        ])) {
            return back()->with(
                'error',
                'Đơn hàng không thể hủy ở trạng thái hiện tại.'
            );
        }

        $order->update([
            'order_status' => 'cancelled',
        ]);

        return back()->with(
            'success',
            'Đã hủy đơn hàng.'
        );
    }
}

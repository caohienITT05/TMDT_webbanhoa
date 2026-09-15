<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'deliverySlot'])->latest();

        // Lọc theo trạng thái đơn
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Tìm kiếm theo tên hoặc SĐT người nhận
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('recipient_name', 'like', '%' . $request->search . '%')
                    ->orWhere('recipient_phone', 'like', '%' . $request->search . '%');
            });
        }

        $orders = $query->paginate(10)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'deliverySlot', 'payment']);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:PENDING,CONFIRMED,PREPARING,SHIPPING,COMPLETED,CANCELLED',
        ]);

        $order->status = $request->status;
        $order->save();

        return back()->with('success', "Đã cập nhật trạng thái đơn #{$order->id} thành công!");
    }
}
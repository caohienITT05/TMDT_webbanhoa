<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with([
            'deliverySlot',
            'payment',
        ])->latest();

        if ($request->filled('status')) {
            $query->where(
                'order_status',
                strtolower($request->status)
            );
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where(
                    'order_code',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'recipient_name',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'recipient_phone',
                    'like',
                    '%' . $search . '%'
                );
            });
        }

        $orders = $query
            ->paginate(10)
            ->withQueryString();

        return view(
            'admin.orders.index',
            compact('orders')
        );
    }

    public function show(Order $order)
    {
        $order->load([
            'items',
            'deliverySlot',
            'payment',
        ]);

        return view(
            'admin.orders.show',
            compact('order')
        );
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'in:pending,confirmed,preparing,shipping,completed,cancelled'
            ],
        ]);

        $order->update([
            'order_status' => $validated['status'],
        ]);

        return back()->with(
            'success',
            "Đã cập nhật trạng thái đơn #{$order->id} thành công!"
        );
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliverySlot;
use Illuminate\Http\Request;

class DeliverySlotController extends Controller
{
    public function index()
    {
        $slots = DeliverySlot::orderBy('id', 'asc')->get();
        return view('admin.delivery_slots.index', compact('slots'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        DeliverySlot::create([
            'name' => $request->name,
            'time_range' => $request->name, // Đồng bộ giá trị vào cột time_range của DB
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Đã thêm khung giờ giao mới thành công!');
    }

    public function update(Request $request, DeliverySlot $delivery_slot)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $delivery_slot->update([
            'name' => $request->name,
            'time_range' => $request->name, // Đồng bộ giá trị khi cập nhật
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Cập nhật khung giờ thành công!');
    }

    public function destroy(DeliverySlot $delivery_slot)
    {
        $delivery_slot->delete();
        return back()->with('success', 'Đã xóa khung giờ giao!');
    }
}
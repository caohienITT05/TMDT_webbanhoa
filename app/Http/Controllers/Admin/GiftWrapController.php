<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftWrap;
use Illuminate\Http\Request;

class GiftWrapController extends Controller
{
    public function index()
    {
        $giftWraps = GiftWrap::latest()->paginate(10);

        return view('admin.gift_wraps.index', compact('giftWraps'));
    }

    public function create()
    {
        return view('admin.gift_wraps.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'image' => [
                'nullable',
                'string',
                'max:255',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        GiftWrap::create($data);

        return redirect()
            ->route('admin.gift-wraps.index')
            ->with('success', 'Thêm gói quà thành công.');
    }

    public function show(GiftWrap $giftWrap)
    {
        return view('admin.gift_wraps.show', compact('giftWrap'));
    }

    public function edit(GiftWrap $giftWrap)
    {
        return view('admin.gift_wraps.edit', compact('giftWrap'));
    }

    public function update(Request $request, GiftWrap $giftWrap)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'image' => [
                'nullable',
                'string',
                'max:255',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $giftWrap->update($data);

        return redirect()
            ->route('admin.gift-wraps.index')
            ->with('success', 'Cập nhật gói quà thành công.');
    }

    public function destroy(GiftWrap $giftWrap)
    {
        $giftWrap->delete();

        return redirect()
            ->route('admin.gift-wraps.index')
            ->with('success', 'Xóa gói quà thành công.');
    }
}
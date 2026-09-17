<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use Illuminate\Http\Request;

class GiftCardController extends Controller
{
    public function index()
    {
        $giftCards = GiftCard::latest()->paginate(10);

        return view('admin.gift_cards.index', compact('giftCards'));
    }

    public function create()
    {
        return view('admin.gift_cards.create');
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

        GiftCard::create($data);

        return redirect()
            ->route('admin.gift-cards.index')
            ->with('success', 'Thêm thiệp thành công.');
    }

    public function show(GiftCard $giftCard)
    {
        return view('admin.gift_cards.show', compact('giftCard'));
    }

    public function edit(GiftCard $giftCard)
    {
        return view('admin.gift_cards.edit', compact('giftCard'));
    }

    public function update(Request $request, GiftCard $giftCard)
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

        $giftCard->update($data);

        return redirect()
            ->route('admin.gift-cards.index')
            ->with('success', 'Cập nhật thiệp thành công.');
    }

    public function destroy(GiftCard $giftCard)
    {
        $giftCard->delete();

        return redirect()
            ->route('admin.gift-cards.index')
            ->with('success', 'Xóa thiệp thành công.');
    }
}
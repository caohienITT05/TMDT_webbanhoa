<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ShippingController extends Controller
{
    /**
     * Lưu phí vận chuyển vào Session.
     */
    public function save(Request $request)
    {
        $request->validate([
            'shipping_fee' => 'required|in:0,30000,50000',
        ]);

        $shippingFee = (float) $request->shipping_fee;

        Session::put('shipping_fee', $shippingFee);

        return redirect()
            ->back()
            ->with(
                'shipping_success',
                'Đã cập nhật phí vận chuyển!'
            );
    }

    /**
     * Xóa phí vận chuyển.
     */
    public function remove()
    {
        Session::forget('shipping_fee');

        return redirect()
            ->back()
            ->with(
                'shipping_success',
                'Đã hủy phí vận chuyển.'
            );
    }
}
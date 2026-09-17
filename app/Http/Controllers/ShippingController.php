<?php

namespace App\Http\Controllers;

use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ShippingController extends Controller
{
    /**
     * Lưu phương thức vận chuyển.
     *
     * Không nhận shipping_fee từ client.
     * Giá luôn lấy từ database.
     */
    public function save(Request $request)
    {
        $request->validate([
            'shipping_method_id' =>
                'required|integer',
        ]);

        $shippingMethod = ShippingMethod::where(
            'id',
            $request->shipping_method_id
        )
            ->where('is_active', true)
            ->first();

        if (!$shippingMethod) {
            return redirect()
                ->back()
                ->with(
                    'shipping_error',
                    'Phương thức vận chuyển không tồn tại hoặc không còn hoạt động.'
                );
        }

        $shippingFee = round(
            max(
                (float) $shippingMethod->fee,
                0
            ),
            2
        );

        Session::put(
            'shipping_method_id',
            $shippingMethod->id
        );

        Session::put(
            'shipping_method_name',
            $shippingMethod->name
        );

        Session::put(
            'shipping_fee',
            $shippingFee
        );

        return redirect()
            ->back()
            ->with(
                'shipping_success',
                'Đã cập nhật phương thức vận chuyển!'
            );
    }

    /**
     * Xóa phương thức vận chuyển.
     */
    public function remove()
    {
        Session::forget([
            'shipping_method_id',
            'shipping_method_name',
            'shipping_fee',
        ]);

        return redirect()
            ->back()
            ->with(
                'shipping_success',
                'Đã hủy phương thức vận chuyển.'
            );
    }
}

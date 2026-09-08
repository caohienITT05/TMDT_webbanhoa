<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CheckoutController extends Controller
{
    private function getCartItems()
    {
        if (Auth::check()) {
            return CartItem::where('user_id', Auth::id())->with('product')->get();
        }
        return CartItem::where('session_id', Session::getId())->with('product')->get();
    }

    public function index()
    {
        $cartItems = $this->getCartItems();
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống!');
        }

        // Tạm tính tổng tiền (Mặc định 100.000 VNĐ/sản phẩm nếu chưa kết nối bảng product)
        $total = $cartItems->sum(function($item) {
            return $item->quantity * ($item->product->price ?? 100000); 
        });

        return view('checkout.index', compact('cartItems', 'total'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string|max:255',
            'payment_method' => 'required|in:COD,PAYPAL',
        ]);

        $cartItems = $this->getCartItems();
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống!');
        }

        $total = $cartItems->sum(function($item) {
            return $item->quantity * ($item->product->price ?? 100000);
        });

        DB::beginTransaction();
        try {
            // 1. Tạo đơn hàng mới
            $order = Order::create([
                'user_id' => Auth::id(),
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_address' => $request->customer_address,
                'note' => $request->note,
                'delivery_date' => $request->delivery_date,
                'delivery_time_slot' => $request->delivery_time_slot,
                'gift_card_message' => $request->gift_card_message,
                'total_amount' => $total,
                'payment_method' => $request->payment_method,
                'payment_status' => 'UNPAID',
                'status' => 'PENDING',
            ]);

            // 2. Chuyển sản phẩm từ Giỏ sang OrderItem
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price ?? 100000,
                ]);
            }

            // 3. Xóa sản phẩm khỏi giỏ
            if (Auth::check()) {
                CartItem::where('user_id', Auth::id())->delete();
            } else {
                CartItem::where('session_id', Session::getId())->delete();
            }

            DB::commit();

            return redirect()->route('cart.index')->with('success', 'Đặt hàng thành công! Mã đơn hàng của bạn: #' . $order->id);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Đã có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}
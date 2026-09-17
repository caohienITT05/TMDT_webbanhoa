<?php


namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = \App\Models\CartItem::with('product')
            ->where('user_id', auth()->id())
            ->get();

        // Tự động tính lại tổng tiền dựa trên giá thực tế của hoa
        $subtotal = $cartItems->sum(function ($item) {
            $price = $item->product ? $item->product->price : ($item->price ?? 0);
            return $price * $item->quantity;
        });

        return view('cart.index', compact('cartItems', 'subtotal'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $productId = $request->input('product_id');
        $quantity = $request->input('quantity', 1);

        // Lấy thông tin hoa thực tế từ database
        $product = \App\Models\Product::findOrFail($productId);

        $cartItem = \App\Models\CartItem::where('user_id', auth()->id())
            ->where('product_id', $productId)
            ->first();

        if ($cartItem) {
            $cartItem->quantity += $quantity;
            $cartItem->price = $product->price;
            $cartItem->subtotal = $cartItem->quantity * $product->price;
            $cartItem->save();
        } else {
            \App\Models\CartItem::create([
                'user_id' => auth()->id(),
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $product->price,
                'subtotal' => $quantity * $product->price,
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Đã thêm sản phẩm vào giỏ hàng!');
    }

    public function remove($id)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
        }

        session()->put('cart', $cart);

        return redirect()
            ->route('cart.index')
            ->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng!');
    }
}

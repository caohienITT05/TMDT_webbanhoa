<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Hiển thị danh sách sản phẩm yêu thích
     */
    public function index(Request $request)
    {
        $favoriteIds = $request->session()->get('favorites', []);

        $products = Product::with('category')
            ->whereIn('id', $favoriteIds)
            ->where('is_active', true)
            ->latest()
            ->get();

       return view('customer.favorites', compact('products'));
    }

    /**
     * Thêm sản phẩm vào danh sách yêu thích
     */
    public function add(Request $request, $id)
    {
        $product = Product::where('is_active', true)
            ->findOrFail($id);

        $favorites = $request->session()->get('favorites', []);

        if (!in_array($product->id, $favorites)) {
            $favorites[] = $product->id;
        }

        $request->session()->put('favorites', $favorites);

        return back()->with(
            'success',
            'Đã thêm sản phẩm vào danh sách yêu thích!'
        );
    }

    /**
     * Xóa sản phẩm khỏi danh sách yêu thích
     */
    public function remove(Request $request, $id)
    {
        $favorites = $request->session()->get('favorites', []);

        $favorites = array_values(
            array_diff($favorites, [$id, (int) $id])
        );

        $request->session()->put('favorites', $favorites);

        return back()->with(
            'success',
            'Đã bỏ sản phẩm khỏi danh sách yêu thích!'
        );
    }
}

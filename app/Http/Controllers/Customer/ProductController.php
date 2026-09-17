<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')
            ->where('is_active', true);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($request->filled('category')) {
            $query->whereHas('category', function ($categoryQuery) use ($request) {
                $categoryQuery->where('slug', $request->category);
            });
        }

        // Hiển thị sản phẩm theo ID tăng dần
        $products = $query->orderBy('id', 'asc')->get();

        $categories = Category::orderBy('name')->get();
        return view('Customer.products', compact('products', 'categories'));
    }

    public function show($id)
    {
        $product = Product::with('category')
            ->where('is_active', true)
            ->findOrFail($id);

        return view('Customer.product-detail', compact('product'));
    }
}

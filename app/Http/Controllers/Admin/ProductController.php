<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCostHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Lọc theo mùa vụ
        if ($request->filled('season')) {
            $query->season($request->season);
        }

        $products = $query->latest()->paginate(10)->withQueryString();
        $categories = \App\Models\Category::all();

        // SỬA TẠI ĐÂY: Thêm "admin." vào trước "products.index"
        return view('admin.products.index', compact('products', 'categories'));
    }
    public function create()
    {
        $categories = Category::all();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0|max:9999999999999.99|decimal:0,2|lt:price',
            'sale_price' => 'nullable|numeric|lt:price|min:0',
            'season' => ['required', Rule::in(Product::SEASONS)],
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'sale_price.lt' => 'Giá khuyến mãi phải nhỏ hơn giá gốc bán ra!',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->has('is_active');

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($validated);

        return redirect()->route('admin.products.index')->with('success', 'Đã thêm sản phẩm hoa mới thành công!');
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        $product->load('costHistories.changedBy');
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'cost_price' => [
                $product->cost_price === null ? 'nullable' : 'required',
                'numeric', 'min:0', 'max:9999999999999.99', 'decimal:0,2', 'lt:price',
            ],
            'cost_change_reason' => 'nullable|string|max:1000',
            'sale_price' => 'nullable|numeric|lt:price|min:0',
            'season' => ['required', Rule::in(Product::SEASONS)],
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'sale_price.lt' => 'Giá khuyến mãi phải nhỏ hơn giá gốc!',
        ]);

        $costChangeReason = $validated['cost_change_reason'] ?? null;
        unset($validated['cost_change_reason']);
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->has('is_active');

        if ($request->hasFile('image')) {
            // Xóa ảnh cũ nếu có
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $oldCost = $product->cost_price === null ? null : round((float) $product->cost_price, 2);
        $newCost = $validated['cost_price'] === null ? null : round((float) $validated['cost_price'], 2);

        DB::transaction(function () use ($costChangeReason, $newCost, $oldCost, $product, $validated): void {
            $product->update($validated);
            if ($newCost !== null && $oldCost !== $newCost) {
                ProductCostHistory::create([
                    'product_id' => $product->id,
                    'old_cost_price' => $oldCost,
                    'new_cost_price' => $newCost,
                    'effective_at' => now(),
                    'changed_by' => auth()->id(),
                    'reason' => $costChangeReason,
                ]);
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Cập nhật sản phẩm thành công!');
    }

    public function destroy(Product $product)
    {
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Đã xóa sản phẩm thành công!');
    }
}

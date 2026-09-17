<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('parent')->withCount('products')->latest()->paginate(10);
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $parentCategories = Category::whereNull('parent_id')->get();
        return view('admin.categories.create', compact('parentCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
            'is_seasonal' => 'nullable|boolean',
        ]);

        $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $validated['is_seasonal'] = $request->has('is_seasonal');

        Category::create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Thêm danh mục mới thành công!');
    }

    public function edit(Category $category)
    {
        $parentCategories = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->get();
        return view('admin.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
            'parent_id' => 'nullable|exists:categories,id',
            'is_seasonal' => 'nullable|boolean',
        ]);

        $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $validated['is_seasonal'] = $request->has('is_seasonal');

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Cập nhật danh mục thành công!');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Không thể xóa danh mục đang có chứa sản phẩm!');
        }

        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Đã xóa danh mục thành công!');
    }
}
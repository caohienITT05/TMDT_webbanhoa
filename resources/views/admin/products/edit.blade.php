@extends('layouts.admin')
@section('title', 'Chỉnh sửa sản phẩm')

@section('content')
    <div class="max-w-4xl bg-white rounded-xl shadow-md p-6 border border-gray-200">
        <h2 class="text-xl font-bold text-gray-800 mb-6 pb-2 border-b">✏️ Cập nhật thông tin: <span
                class="text-rose-600">{{ $product->name }}</span></h2>

        <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data"
            class="space-y-5">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tên sản phẩm <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Danh mục <span
                            class="text-red-500">*</span></label>
                    <select name="category_id" required
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 bg-white">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Giá bán gốc (VNĐ) <span
                            class="text-red-500">*</span></label>
                    <input type="number" name="price" value="{{ old('price', $product->price) }}" required min="0"
                        step="1000" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Giá khuyến mãi (VNĐ)</label>
                    <input type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" min="0"
                        step="1000" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Tồn kho <span
                            class="text-red-500">*</span></label>
                    <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" required min="0"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Thay ảnh mới (nếu muốn)</label>
                    <input type="file" name="image" accept="image/*"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-rose-50 file:text-rose-700">
                    <div class="mt-2 flex items-center gap-3">
                        <span class="text-xs text-gray-500">Ảnh hiện tại:</span>
                        <img src="{{ $product->image_url }}" alt="" class="w-10 h-10 object-cover rounded border">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Mô tả sản phẩm</label>
                <textarea name="description" rows="4"
                    class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:ring-2 focus:ring-rose-500">{{ old('description', $product->description) }}</textarea>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="w-4 h-4 text-rose-600 rounded">
                <label for="is_active" class="text-sm font-semibold text-gray-700">Đang bật bán</label>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                <a href="{{ route('admin.products.index') }}"
                    class="px-5 py-2.5 border rounded-lg text-sm text-gray-600 hover:bg-gray-50 font-medium">Hủy bỏ</a>
                <button type="submit" style="background-color: #2563eb; color: #ffffff;"
                    class="px-6 py-2.5 font-bold rounded-lg shadow-md hover:opacity-90 text-sm">
                    💾 Cập nhật sản phẩm
                </button>
            </div>
        </form>
    </div>
@endsection
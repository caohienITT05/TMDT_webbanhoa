@extends('layouts.admin')
@section('title', 'Chỉnh sửa danh mục')

@section('content')
    <div class="max-w-3xl bg-white rounded-xl shadow-md p-6 border border-gray-200">
        <h2 class="text-xl font-bold text-gray-800 mb-6 pb-2 border-b">✏️ Chỉnh sửa danh mục: <span
                class="text-rose-600">{{ $category->name }}</span></h2>

        <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tên danh mục <span
                        class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $category->name) }}" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
                @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Đường dẫn thân thiện (Slug)</label>
                <input type="text" name="slug" value="{{ old('slug', $category->slug) }}"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Thuộc danh mục cha</label>
                <select name="parent_id"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none bg-white">
                    <option value="">-- Là danh mục gốc (Không có danh mục cha) --</option>
                    @foreach($parentCategories as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="p-3 bg-rose-50 rounded-lg border border-rose-100 flex items-center gap-3">
                <input type="checkbox" name="is_seasonal" id="is_seasonal" value="1" {{ old('is_seasonal', $category->is_seasonal) ? 'checked' : '' }}
                    class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500 border-gray-300 cursor-pointer">
                <label for="is_seasonal" class="text-sm font-medium text-gray-800 cursor-pointer">
                    Đánh dấu là <b>Danh mục theo mùa / sự kiện đặc biệt</b>
                </label>
            </div>

            <!-- Cụm nút Thao tác -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
                <a href="{{ route('admin.categories.index') }}"
                    class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-100 text-sm transition">
                    Hủy bỏ
                </a>
                <button type="submit" style="background-color: #2563eb; color: #ffffff;"
                    class="px-6 py-2.5 font-bold rounded-lg shadow-md hover:opacity-90 text-sm transition flex items-center gap-2 cursor-pointer">
                    💾 Lưu cập nhật thay đổi
                </button>
            </div>
        </form>
    </div>
@endsection
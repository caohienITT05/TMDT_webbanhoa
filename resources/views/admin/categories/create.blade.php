@extends('layouts.admin')
@section('title', 'Thêm danh mục mới')

@section('content')
    <div class="max-w-3xl bg-white rounded-xl shadow-md p-6 border border-gray-200">
        <h2 class="admin-page-title mb-6 border-b pb-2">Tạo danh mục hoa / quà mới</h2>

        <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tên danh mục <span
                        class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}"
                    placeholder="Ví dụ: Hoa khai trương, Lan hồ điệp..." required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
                @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Đường dẫn thân thiện (Slug - bỏ trống sẽ tự
                    tạo theo tên)</label>
                <input type="text" name="slug" value="{{ old('slug') }}" placeholder="hoa-khai-truong"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Thuộc danh mục cha (Phân cấp hoa/quà)</label>
                <select name="parent_id"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none bg-white">
                    <option value="">-- Là danh mục gốc (Không có danh mục cha) --</option>
                    @foreach($parentCategories as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                            {{ $parent->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="p-3 bg-rose-50 rounded-lg border border-rose-100 flex items-center gap-3">
                <input type="checkbox" name="is_seasonal" id="is_seasonal" value="1" {{ old('is_seasonal') ? 'checked' : '' }} class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500 border-gray-300 cursor-pointer">
                <label for="is_seasonal" class="text-sm font-medium text-gray-800 cursor-pointer">
                    Đánh dấu là <b>Danh mục theo mùa / sự kiện đặc biệt</b> (Tết, Valentine 14/2, Quốc tế Phụ nữ 8/3...)
                </label>
            </div>

            <!-- Cụm nút Thao tác -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
                <x-admin.button :href="route('admin.categories.index')" variant="secondary">Quay lại</x-admin.button>
                <x-admin.button type="submit" variant="primary">Lưu danh mục mới</x-admin.button>
            </div>
        </form>
    </div>
@endsection

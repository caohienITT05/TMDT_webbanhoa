@extends('layouts.admin')
@section('title', 'Thêm sản phẩm mới')

@section('content')
    <div class="max-w-4xl bg-white rounded-xl shadow-md p-6 border border-gray-200">
        <h2 class="text-xl font-bold text-gray-800 mb-6 pb-2 border-b">🌸 Đăng bán sản phẩm hoa / quà mới</h2>

        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- 1. Tên sản phẩm -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Tên sản phẩm <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Ví dụ: Bó hoa hồng đỏ Đà Lạt"
                        required
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500">
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- 2. Thuộc danh mục (Đã tách riêng biệt) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Thuộc danh mục <span class="text-red-500">*</span>
                    </label>
                    <select name="category_id" required
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 bg-white">
                        <option value="">-- Chọn danh mục --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- 3. Mùa vụ / Bộ sưu tập thời tiết (Đã tách riêng biệt) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Mùa vụ / Bộ sưu tập thời tiết <span class="text-red-500">*</span>
                    </label>
                    <select name="season" required
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 bg-white">
                        <option value="all" {{ old('season', 'all') == 'all' ? 'selected' : '' }}>🌿 Hoa bốn mùa (Quanh năm)
                        </option>
                        <option value="spring" {{ old('season') == 'spring' ? 'selected' : '' }}>🌸 Mùa Xuân (Đào, Mai, Tulip,
                            Thược dược...)</option>
                        <option value="summer" {{ old('season') == 'summer' ? 'selected' : '' }}>☀️ Mùa Hạ (Sen, Hướng dương,
                            Cát tường...)</option>
                        <option value="autumn" {{ old('season') == 'autumn' ? 'selected' : '' }}>🍂 Mùa Thu (Cúc họa mi, Thạch
                            thảo, Cam ấm...)</option>
                        <option value="winter" {{ old('season') == 'winter' ? 'selected' : '' }}>❄️ Mùa Đông (Cẩm tú cầu,
                            Trạng nguyên, Baby tuyết...)</option>
                    </select>
                    @error('season') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- 4. Ảnh đại diện sản phẩm -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Ảnh đại diện sản phẩm</label>
                    <input type="file" name="image" accept="image/*"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100">
                    @error('image') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- 5. Giá bán gốc -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Giá bán gốc (VNĐ) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="price" value="{{ old('price') }}" placeholder="350000" required min="0"
                        step="1000"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500">
                    @error('price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- 6. Giá khuyến mãi -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Giá khuyến mãi (VNĐ - nếu có)</label>
                    <input type="number" name="sale_price" value="{{ old('sale_price') }}" placeholder="299000" min="0"
                        step="1000"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500">
                    @error('sale_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- 7. Số lượng tồn kho -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Số lượng tồn kho <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="stock" value="{{ old('stock', 20) }}" required min="0"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500">
                    @error('stock') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Mô tả chi tiết -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Mô tả chi tiết / Ý nghĩa hoa</label>
                <textarea name="description" rows="4" placeholder="Mô tả các loại hoa, ý nghĩa trao gửi..."
                    class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:ring-2 focus:ring-rose-500">{{ old('description') }}</textarea>
            </div>

            <!-- Bật/Tắt hiển thị -->
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-4 h-4 text-rose-600 rounded">
                <label for="is_active" class="text-sm font-semibold text-gray-700 cursor-pointer">
                    Bật hiển thị sản phẩm ngay sau khi tạo
                </label>
            </div>

            <!-- Nút thao tác -->
            <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                <a href="{{ route('admin.products.index') }}"
                    class="px-5 py-2.5 border rounded-lg text-sm text-gray-600 hover:bg-gray-50 font-medium">Hủy bỏ</a>
                <button type="submit" style="background-color: #e11d48; color: #ffffff;"
                    class="px-6 py-2.5 font-bold rounded-lg shadow-md hover:opacity-90 text-sm">
                    💾 Đăng bán sản phẩm
                </button>
            </div>
        </form>
    </div>
@endsection
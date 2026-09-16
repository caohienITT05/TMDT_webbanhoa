@extends('layouts.admin')
@section('title', 'Quản lý Sản phẩm')

@section('content')
    <div class="space-y-4">
        <!-- Tiêu đề & Nút Thêm sản phẩm viên thuốc -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    🌸 Danh sách hoa & quà tặng
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Quản lý kho hoa, giá bán và hình ảnh sản phẩm</p>
            </div>
            <a href="{{ route('admin.products.create') }}"
                class="px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white font-semibold text-xs rounded-full shadow-sm transition flex items-center gap-1.5">
                <span>+</span> Thêm sản phẩm mới
            </a>
        </div>

        <!-- Thanh tìm kiếm & lọc mềm mại bo tròn -->
        <form method="GET" action="{{ route('admin.products.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[220px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Tìm theo tên hoa..."
                    class="w-full bg-white rounded-full border border-rose-200 px-4 py-1.5 text-xs text-gray-700 focus:outline-none focus:border-rose-400">
            </div>

            <select name="category_id"
                class="bg-white rounded-full border border-rose-200 px-4 py-1.5 text-xs text-gray-700 focus:outline-none focus:border-rose-400">
                <option value="">-- Tất cả danh mục --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit"
                class="px-4 py-1.5 bg-rose-400 hover:bg-rose-500 text-white rounded-full text-xs font-semibold shadow-sm transition flex items-center gap-1">
                🔍 Lọc
            </button>
            <a href="{{ route('admin.products.index') }}"
                class="px-3 py-1.5 bg-white border border-rose-200 text-gray-500 rounded-full text-xs hover:bg-rose-50 transition">
                🔄 Đặt lại
            </a>
        </form>

        <!-- Bảng danh sách sản phẩm -->
        <div class="bg-white rounded-2xl border border-rose-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-[#fff0f3] text-rose-900 font-semibold uppercase text-[11px] border-b border-rose-100">
                        <tr>
                            <th class="p-3.5 px-5">Ảnh</th>
                            <th class="p-3.5 px-5">Tên sản phẩm</th>
                            <th class="p-3.5 px-5">Danh mục</th>
                            <th class="p-3.5 px-5">Giá bán</th>
                            <th class="p-3.5 px-5">Giá Sale</th>
                            <th class="p-3.5 px-5 text-center">Tồn kho</th>
                            <th class="p-3.5 px-5">Trạng thái</th>
                            <th class="p-3.5 px-5 text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-50">
                        @forelse($products as $prod)
                            <tr class="hover:bg-rose-50/40 transition">
                                <td class="p-3 px-5">
                                    <img src="{{ $prod->image_url }}" alt="{{ $prod->name }}"
                                        class="w-11 h-11 object-cover rounded-xl border border-rose-100 shadow-sm">
                                </td>
                                <td class="p-3 px-5 font-bold text-gray-800">{{ $prod->name }}</td>
                                <td class="p-3 px-5">
                                    <span
                                        class="px-2.5 py-0.5 bg-rose-50 text-rose-600 rounded-full text-[10px] font-medium border border-rose-100">
                                        {{ $prod->category->name }}
                                    </span>
                                </td>
                                <td class="p-3 px-5 font-bold text-gray-800">{{ number_format($prod->price, 0, ',', '.') }} đ
                                </td>
                                <td class="p-3 px-5 font-bold text-rose-500">
                                    {{ $prod->sale_price ? number_format($prod->sale_price, 0, ',', '.') . ' đ' : '—' }}
                                </td>
                                <td class="p-3 px-5 text-center font-bold text-gray-700">{{ $prod->stock }}</td>
                                <td class="p-3 px-5">
                                    @if($prod->is_active)
                                        <span
                                            class="px-2.5 py-0.5 text-[10px] rounded-full bg-emerald-50 text-emerald-600 font-bold border border-emerald-100">Đang
                                            bán</span>
                                    @else
                                        <span class="px-2.5 py-0.5 text-[10px] rounded-full bg-gray-100 text-gray-500">Ẩn</span>
                                    @endif
                                </td>
                                <td class="p-3 px-5 text-center">
                                    <div class="inline-flex items-center gap-3">
                                        <a href="{{ route('admin.products.edit', $prod) }}"
                                            class="text-rose-500 hover:text-rose-700 font-semibold flex items-center gap-1">
                                            ✏️ Sửa
                                        </a>
                                        <span class="text-rose-200">|</span>
                                        <form action="{{ route('admin.products.destroy', $prod) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-rose-400 hover:text-rose-600 font-semibold flex items-center gap-1">
                                                🗑️ Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-gray-400">Chưa có sản phẩm nào phù hợp.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-rose-50">
                {{ $products->links() }}
            </div>
        </div>
    </div>
@endsection
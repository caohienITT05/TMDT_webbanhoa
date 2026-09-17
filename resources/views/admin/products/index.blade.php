@extends('layouts.admin')
@section('title', 'Quản lý Sản phẩm')

@section('content')
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
        <!-- Tiêu đề & Nút Thêm mới -->
        <div
            class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 pb-4 border-b border-gray-100">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Danh sách hoa & quà tặng</h2>
                <p class="text-sm text-gray-500">Quản lý kho hoa, giá bán và hình ảnh sản phẩm</p>
            </div>
            <a href="{{ route('admin.products.create') }}" style="background-color: #e11d48; color: #ffffff;"
                class="px-5 py-2.5 font-bold text-sm rounded-lg shadow hover:opacity-90 transition flex items-center gap-2">
                <span>+</span> Thêm sản phẩm mới
            </a>
        </div>

        <!-- Thanh tìm kiếm & lọc -->
        <form method="GET" action="{{ route('admin.products.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Tìm theo tên hoa..."
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">
            <select name="category_id"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none bg-white">
                <option value="">-- Tất cả danh mục --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}
                    </option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit"
                    class="px-4 py-2 bg-gray-800 hover:bg-black text-white rounded-lg text-sm font-semibold">Lọc</button>
                <a href="{{ route('admin.products.index') }}"
                    class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50">Đặt lại</a>
            </div>
        </form>

        <!-- Bảng danh sách sản phẩm -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 border border-gray-200 rounded-lg">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs font-bold border-b border-gray-200">
                    <tr>
                        <th class="p-3.5">Ảnh</th>
                        <th class="p-3.5">Tên sản phẩm</th>
                        <th class="p-3.5">Danh mục</th>
                        <th class="p-3.5">Giá bán</th>
                        <th class="p-3.5">Giá Sale</th>
                        <th class="p-3.5">Tồn kho</th>
                        <th class="p-3.5">Trạng thái</th>
                        <th class="p-3.5 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($products as $prod)
                        <tr class="hover:bg-rose-50/40 transition">
                            <td class="p-3">
                                <img src="{{ $prod->image_url }}" alt="{{ $prod->name }}"
                                    class="w-14 h-14 object-cover rounded-lg border border-gray-200 shadow-sm">
                            </td>
                            <td class="p-3 font-bold text-gray-900">{{ $prod->name }}</td>
                            <td class="p-3">
                                <span
                                    class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs">{{ $prod->category->name }}</span>
                            </td>
                            <td class="p-3 font-semibold text-gray-900">{{ number_format($prod->price, 0, ',', '.') }} đ</td>
                            <td class="p-3 font-semibold text-rose-600">
                                {{ $prod->sale_price ? number_format($prod->sale_price, 0, ',', '.') . ' đ' : '—' }}
                            </td>
                            <td class="p-3">
                                <span
                                    class="font-bold {{ $prod->stock < 10 ? 'text-amber-600' : 'text-gray-700' }}">{{ $prod->stock }}</span>
                            </td>
                            <td class="p-3">
                                @if($prod->is_active)
                                    <span class="px-2.5 py-1 text-xs rounded-full bg-green-100 text-green-700 font-semibold">Đang
                                        bán</span>
                                @else
                                    <span class="px-2.5 py-1 text-xs rounded-full bg-gray-200 text-gray-600">Ẩn</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <div class="inline-flex items-center gap-3">
                                    <a href="{{ route('admin.products.edit', $prod) }}"
                                        class="font-semibold text-blue-600 hover:underline text-sm">✏️ Sửa</a>
                                    <span class="text-gray-300">|</span>
                                    <form action="{{ route('admin.products.destroy', $prod) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa hoa này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-semibold text-red-600 hover:underline text-sm">🗑️
                                            Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-400">Không tìm thấy sản phẩm nào. Hãy bấm "Thêm sản
                                phẩm mới"!</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $products->links() }}</div>
    </div>
@endsection
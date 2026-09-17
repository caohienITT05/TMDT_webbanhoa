@extends('layouts.admin')
@section('title', 'Danh sách danh mục')

@section('content')
<div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 pb-4 border-b border-gray-100">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Tất cả danh mục</h2>
            <p class="text-sm text-gray-500">Quản lý cơ cấu phân loại hoa & quà tặng của BloomGift</p>
        </div>
        <a href="{{ route('admin.categories.create') }}" style="background-color: #e11d48; color: #ffffff;" class="px-5 py-2.5 font-bold text-sm rounded-lg shadow hover:opacity-90 transition flex items-center gap-2">
            <span>+</span> Thêm danh mục mới
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600 border border-gray-200 rounded-lg">
            <thead class="bg-gray-100 text-gray-700 uppercase text-xs font-bold border-b border-gray-200">
                <tr>
                    <th class="p-3.5">ID</th>
                    <th class="p-3.5">Tên danh mục</th>
                    <th class="p-3.5">Đường dẫn (Slug)</th>
                    <th class="p-3.5">Danh mục cha</th>
                    <th class="p-3.5">Theo mùa</th>
                    <th class="p-3.5">Số SP</th>
                    <th class="p-3.5 text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($categories as $cat)
                <tr class="hover:bg-rose-50/50 transition">
                    <td class="p-3.5 font-bold text-gray-500">#{{ $cat->id }}</td>
                    <td class="p-3.5 font-bold text-gray-900">{{ $cat->name }}</td>
                    <td class="p-3.5 font-mono text-xs text-gray-500">{{ $cat->slug }}</td>
                    <td class="p-3.5">
                        @if($cat->parent)
                            <span class="px-2.5 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium">{{ $cat->parent->name }}</span>
                        @else
                            <span class="text-gray-400 italic text-xs">Gốc</span>
                        @endif
                    </td>
                    <td class="p-3.5">
                        @if($cat->is_seasonal)
                            <span class="px-2.5 py-1 text-xs rounded-full bg-amber-100 text-amber-800 font-semibold">🌸 Theo mùa</span>
                        @else
                            <span class="px-2.5 py-1 text-xs rounded-full bg-gray-100 text-gray-600 font-medium">Quanh năm</span>
                        @endif
                    </td>
                    <td class="p-3.5 font-semibold text-gray-700">{{ $cat->products_count }}</td>
                    <td class="p-3.5 text-center">
                        <div class="inline-flex items-center gap-3">
                            <a href="{{ route('admin.categories.edit', $cat) }}" class="font-semibold text-blue-600 hover:text-blue-800 text-sm">
                                ✏️ Sửa
                            </a>
                            <span class="text-gray-300">|</span>
                            <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-semibold text-red-600 hover:text-red-800 text-sm cursor-pointer">
                                    🗑️ Xóa
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-8 text-center text-gray-400">
                        Chưa có danh mục nào. Hãy bấm nút Thêm danh mục mới ở trên!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $categories->links() }}
    </div>
</div>
@endsection
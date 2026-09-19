@extends('layouts.admin')
@section('title', 'Danh sách danh mục')

@section('content')
    <div class="space-y-4">
        <!-- Tiêu đề & Nút thêm mới bo tròn viên thuốc -->
        <div class="flex justify-between items-center">
            <div>
                <h2 class="admin-page-title">Tất cả danh mục</h2>
                <p class="text-xs text-gray-500 mt-0.5">Quản lý cơ cấu phân loại hoa & quà tặng của BloomGift</p>
            </div>
            <x-admin.button :href="route('admin.categories.create')" variant="primary">
                <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 4v12M4 10h12" stroke-linecap="round" /></svg>
                Thêm danh mục mới
            </x-admin.button>
        </div>

        <!-- Bảng danh mục nền hồng nhạt -->
        <div class="bg-white rounded-2xl border border-rose-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-[#fff0f3] text-rose-900 font-semibold uppercase text-[11px] border-b border-rose-100">
                        <tr>
                            <th class="p-3.5 px-5">ID</th>
                            <th class="p-3.5 px-5">Tên danh mục</th>
                            <th class="p-3.5 px-5">Đường dẫn (Slug)</th>
                            <th class="p-3.5 px-5">Danh mục cha</th>
                            <th class="p-3.5 px-5">Theo mùa</th>
                            <th class="p-3.5 px-5 text-center">Số SP</th>
                            <th class="p-3.5 px-5 text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-50">
                        @forelse($categories as $cat)
                            <tr class="hover:bg-rose-50/40 transition">
                                <td class="p-3.5 px-5 text-gray-400 font-medium">#{{ $cat->id }}</td>
                                <td class="p-3.5 px-5 font-bold text-gray-800 flex items-center gap-2">
                                    <span>🌹</span> {{ $cat->name }}
                                </td>
                                <td class="p-3.5 px-5 font-mono text-gray-400">{{ $cat->slug }}</td>
                                <td class="p-3.5 px-5 text-gray-400">
                                    {{ $cat->parent ? $cat->parent->name : 'Gốc' }}
                                </td>
                                <td class="p-3.5 px-5">
                                    @if($cat->is_seasonal)
                                        <span
                                            class="px-2.5 py-0.5 text-[10px] rounded-full bg-amber-100 text-amber-700 font-semibold">Theo
                                            mùa</span>
                                    @else
                                        <span class="text-gray-400 text-[11px]">Quanh năm</span>
                                    @endif
                                </td>
                                <td class="p-3.5 px-5 text-center font-bold text-gray-700">
                                    {{ $cat->products_count }}
                                </td>
                                <td class="p-3.5 px-5 text-center">
                                    <div class="admin-table-actions">
                                        <x-admin.button :href="route('admin.categories.edit', $cat)" variant="detail" size="sm">Sửa</x-admin.button>
                                        <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST"
                                            class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');">
                                            @csrf
                                            @method('DELETE')
                                            <x-admin.button type="submit" variant="danger" size="sm">Xóa</x-admin.button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-400">Chưa có danh mục nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-rose-50">
                <x-admin.pagination :paginator="$categories" item-label="danh mục" />
            </div>
        </div>
    </div>
@endsection

@extends('layouts.admin')
@section('title', 'Quản lý khung giờ giao hoa')

@section('content')
    <div class="space-y-6">

        <!-- Tiêu đề -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    🌸 Quản lý khung giờ giao hoa (Delivery Slots)
                </h2>
                <p class="text-xs text-gray-500 mt-1">Thiết lập các ca giao hàng trong ngày. Tạm khóa ca khi lượng đơn cắm
                    hoa quá tải.</p>
            </div>
        </div>

        <!-- Thông báo -->
        @if(session('success'))
            <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-medium">
                ✅ {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Cột trái: Form thêm khung giờ mới -->
            <div class="bg-white p-5 rounded-2xl border border-rose-100 shadow-sm h-fit">
                <h3 class="text-sm font-bold text-gray-800 mb-4 pb-2 border-b border-rose-50 flex items-center gap-2">
                    ➕ Thêm ca giao mới
                </h3>

                <form action="{{ route('admin.delivery-slots.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Tên khung giờ <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" required placeholder="VD: 08:00 - 10:00 (Sáng sớm)"
                            class="w-full text-sm rounded-xl border border-gray-200 px-3.5 py-2.5 focus:ring-2 focus:ring-rose-400 focus:outline-none">
                        <p class="text-[11px] text-gray-400 mt-1">Nên ghi rõ khoảng giờ và ca (Sáng, Trưa, Chiều, Tối).</p>
                    </div>

                    <div class="flex items-center pt-1">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" checked
                                class="w-4 h-4 text-rose-600 rounded border-gray-300 focus:ring-rose-500">
                            <span class="ml-2 text-xs font-semibold text-gray-700">Mở nhận đơn ngay cho ca này</span>
                        </label>
                    </div>

                    <button type="submit" style="background-color: #e11d48; color: #ffffff;"
                        class="w-full py-2.5 rounded-xl font-bold text-xs shadow-sm hover:opacity-95 transition">
                        Thêm khung giờ
                    </button>
                </form>
            </div>

            <!-- Cột phải: Bảng danh sách các khung giờ -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-rose-100 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-rose-50 bg-rose-50/30 flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-700">Danh sách các ca giao hiện có</span>
                    <span class="text-[11px] text-gray-400">Tổng: {{ $slots->count() }} ca</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-rose-50/50 text-[11px] text-gray-500 uppercase">
                            <tr>
                                <th class="py-3 px-4">Mã #</th>
                                <th class="py-3 px-4">Tên ca giao nhận</th>
                                <th class="py-3 px-4 text-center">Trạng thái</th>
                                <th class="py-3 px-4 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rose-50">
                            @forelse($slots as $slot)
                                <tr class="hover:bg-rose-50/20 transition">
                                    <td class="py-3 px-4 text-gray-400 font-mono text-xs">#{{ $slot->id }}</td>
                                    <td class="py-3 px-4 font-semibold text-gray-800">
                                        {{ $slot->name }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if($slot->is_active)
                                            <span
                                                class="px-2.5 py-1 text-[11px] font-bold text-emerald-700 bg-emerald-100 rounded-full">
                                                ● Đang mở
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 text-[11px] font-bold text-gray-500 bg-gray-100 rounded-full">
                                                ✕ Tạm khóa
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <!-- Form bật/tắt nhanh -->
                                            <form action="{{ route('admin.delivery-slots.update', $slot) }}" method="POST"
                                                class="inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="name" value="{{ $slot->name }}">
                                                @if($slot->is_active)
                                                    <button type="submit"
                                                        class="text-xs text-amber-600 hover:underline font-semibold"
                                                        title="Khóa ca khi quá tải đơn">
                                                        Khóa ca
                                                    </button>
                                                @else
                                                    <input type="hidden" name="is_active" value="1">
                                                    <button type="submit"
                                                        class="text-xs text-emerald-600 hover:underline font-semibold"
                                                        title="Mở nhận đơn trở lại">
                                                        Mở lại
                                                    </button>
                                                @endif
                                            </form>

                                            <!-- Form xóa -->
                                            <form action="{{ route('admin.delivery-slots.destroy', $slot) }}" method="POST"
                                                onsubmit="return confirm('Bạn có chắc muốn xóa ca giao này?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-xs text-rose-500 hover:underline font-semibold">
                                                    Xóa
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-8 text-gray-400 text-xs">
                                        Chưa có khung giờ giao nào được tạo. Hãy thêm ca mới ở cột bên trái!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection
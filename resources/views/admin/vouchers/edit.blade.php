@extends('layouts.admin')
@section('title', 'Chỉnh sửa Voucher')

@section('content')
<div class="max-w-4xl bg-white rounded-xl shadow-md p-6 border border-gray-200">
    <div class="flex items-center justify-between mb-6 pb-2 border-b">
        <div>
            <h2 class="admin-page-title">
                Chỉnh sửa voucher: <span class="text-rose-600">{{ $voucher->code }}</span>
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">Cập nhật thông tin mã ưu đãi và khung giờ Flash Sale</p>
        </div>
        <x-admin.button :href="route('admin.vouchers.index')" variant="secondary" size="sm">Quay lại danh sách</x-admin.button>
    </div>

    <form action="{{ route('admin.vouchers.update', $voucher) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- 1. Mã Voucher -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Mã Voucher <span class="text-red-500">*</span>
                </label>
                <input type="text" name="code" value="{{ old('code', $voucher->code) }}" required
                    placeholder="Ví dụ: BLOOM01"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm uppercase font-bold text-gray-800 focus:ring-2 focus:ring-rose-500">
                @error('code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- 2. Tên chương trình -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Tên chương trình khuyến mại <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $voucher->name) }}" required
                    placeholder="Ví dụ: Khuyến mại giờ vàng"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500">
                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- 3. Loại giảm giá -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Loại giảm giá <span class="text-red-500">*</span>
                </label>
                <select name="type" required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 bg-white">
                    <option value="fixed" {{ old('type', $voucher->type ?? $voucher->discount_type) == 'fixed' ? 'selected' : '' }}>
                        💵 Số tiền cố định (VNĐ)
                    </option>
                    <option value="percent" {{ old('type', $voucher->type ?? $voucher->discount_type) == 'percent' ? 'selected' : '' }}>
                        📊 Phần trăm (%)
                    </option>
                </select>
                @error('type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- 4. Giá trị giảm -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Mức giảm <span class="text-red-500">*</span>
                </label>
                <input type="number" name="value" 
                    value="{{ old('value', (int)($voucher->value ?? $voucher->discount_value)) }}" 
                    required min="0" step="any"
                    placeholder="Ví dụ: 20000 (đối với tiền) hoặc 10 (đối với %)"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500">
                @error('value') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- 5. Giá trị đơn tối thiểu -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Đơn hàng tối thiểu (VNĐ)
                </label>
                <input type="number" name="min_order_amount" 
                    value="{{ old('min_order_amount', (int)($voucher->min_order_amount ?? $voucher->min_order_value ?? 0)) }}" 
                    min="0" step="1000" placeholder="0"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500">
                @error('min_order_amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- 6. Mức giảm tối đa (nếu giảm theo %) -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Giảm tối đa (VNĐ - dành cho %)
                </label>
                <input type="number" name="max_discount" 
                    value="{{ old('max_discount', $voucher->max_discount ? (int)$voucher->max_discount : '') }}" 
                    min="0" step="1000" placeholder="Để trống nếu không giới hạn"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500">
                @error('max_discount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- 7. Giới hạn lượt sử dụng -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Tổng lượt dùng tối đa
                </label>
                <input type="number" name="usage_limit" 
                    value="{{ old('usage_limit', $voucher->usage_limit) }}" 
                    min="1" placeholder="Ví dụ: 100"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500">
                <p class="text-[11px] text-gray-400 mt-1">Đã sử dụng: <strong>{{ $voucher->used_count ?? 0 }}</strong> lượt</p>
                @error('usage_limit') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- 8. Trạng thái kích hoạt -->
            <div class="flex items-center pt-5">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" 
                        {{ old('is_active', $voucher->is_active) ? 'checked' : '' }} 
                        class="w-4 h-4 text-rose-600 rounded border-gray-300 focus:ring-rose-500">
                    <span class="ml-2 text-sm font-semibold text-gray-700">Kích hoạt voucher này</span>
                </label>
            </div>

            <!-- 9. Bắt đầu Flash Sale -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Bắt đầu hiệu lực (Flash Sale)
                </label>
                <input type="datetime-local" name="start_time"
                    value="{{ old('start_time', $voucher->start_time ? \Carbon\Carbon::parse($voucher->start_time)->format('Y-m-d\TH:i') : '') }}"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 bg-white">
                @error('start_time') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- 10. Kết thúc Flash Sale -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Hết hạn hiệu lực (Flash Sale)
                </label>
                <input type="datetime-local" name="end_time"
                    value="{{ old('end_time', $voucher->end_time ? \Carbon\Carbon::parse($voucher->end_time)->format('Y-m-d\TH:i') : '') }}"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-rose-500 bg-white">
                @error('end_time') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Nút thao tác -->
        <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
            <x-admin.button :href="route('admin.vouchers.index')" variant="secondary">Hủy bỏ</x-admin.button>
            <x-admin.button type="submit" variant="primary">Cập nhật voucher</x-admin.button>
        </div>
    </form>
</div>
@endsection

@extends('layouts.admin')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6" x-data="{ sending: false }">

        {{-- Tiêu đề trang --}}
        <div class="flex items-center justify-between pb-3 border-b border-rose-200">
            <div>
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <span class="text-2xl">🌸</span> Chiến Dịch Email Marketing Dịp Lễ
                </h2>
                <p class="text-xs text-gray-500 mt-1">Gửi email thông báo ưu đãi và tặng mã giảm giá hoa tươi đồng loạt đến
                    hòm thư khách hàng.</p>
            </div>
            <span
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold text-rose-600 border border-rose-200 bg-rose-50 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                E-Marketing System
            </span>
        </div>

        {{-- Thẻ thống kê số lượng khách --}}
        <div
            class="p-5 rounded-2xl border border-rose-100 bg-gradient-to-r from-rose-50/80 via-pink-50/50 to-white flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-inner border border-rose-200"
                    style="background: #ffe4e6;">
                    💌
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Quy mô tệp khách hàng hợp lệ</p>
                    <p class="text-lg font-bold text-gray-800 mt-0.5">
                        Hệ thống đang có <span class="text-rose-600 text-xl font-extrabold">{{ $totalCustomers }}</span>
                        khách hàng có sẵn email
                    </p>
                </div>
            </div>
            <span
                class="hidden sm:inline-block text-xs font-medium text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full">
                ● Sẵn sàng gửi thư
            </span>
        </div>

        {{-- Khung Form nhập liệu --}}
        <div class="bg-white rounded-2xl border border-rose-200/80 shadow-md p-6 sm:p-8">
            <form action="{{ route('admin.marketing.holiday.send') }}" method="POST" @submit="sending = true">
                @csrf

                <div class="space-y-6">
                    {{-- 1. Tên ngày lễ --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                            Tên dịp lễ sắp tới <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="holiday_name"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-800 focus:border-rose-500 focus:ring-4 focus:ring-rose-100 focus:outline-none transition shadow-sm"
                            placeholder="Ví dụ: 20/10 - Ngày Phụ Nữ Việt Nam"
                            value="{{ old('holiday_name', '20/10 - Ngày Phụ Nữ Việt Nam') }}" required>
                        @error('holiday_name')
                            <p class="text-xs text-rose-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 2. Mã Voucher --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                            Mã Voucher tri ân (Coupon Code) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="voucher_code"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-base font-extrabold uppercase tracking-widest text-rose-600 focus:border-rose-500 focus:ring-4 focus:ring-rose-100 focus:outline-none transition shadow-sm"
                            placeholder="Ví dụ: BLOOM01, VALENTINE20" value="{{ old('voucher_code', 'BLOOM01') }}" required>
                        @error('voucher_code')
                            <p class="text-xs text-rose-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-400 mt-1.5">Mã ưu đãi này sẽ được đóng khung nổi bật trong nội dung thư
                            gửi đến khách.</p>
                    </div>

                    {{-- Nút bấm Submit được thiết kế chắc chắn --}}
                    <div class="pt-4 border-t border-gray-100">
                        <button type="submit" :disabled="sending"
                            style="background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%) !important; color: #ffffff !important;"
                            class="w-full h-14 rounded-xl font-bold text-base shadow-lg shadow-rose-500/25 flex items-center justify-center gap-3 cursor-pointer transition-all duration-200 hover:opacity-95 active:scale-[0.99] disabled:opacity-70 disabled:cursor-not-allowed border-0">

                            {{-- Trạng thái bình thường --}}
                            <span x-show="!sending" class="flex items-center gap-2 tracking-wide text-white">
                                <span class="text-xl"></span>
                                <span>Phát động chiến dịch mail khách hàng</span>
                            </span>

                            {{-- Trạng thái đang gửi (Loading) --}}
                            <span x-show="sending" style="display: none;" class="flex items-center gap-2 text-white">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span>Đang kết nối gửi mail tới từng khách hàng, vui lòng chờ...</span>
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>
@endsection
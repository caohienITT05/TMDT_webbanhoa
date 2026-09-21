<x-customer.layout title="Đặt hoa theo yêu cầu">
    <x-customer.breadcrumb :items="[
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Đặt hoa theo yêu cầu'],
    ]" />

    <section class="bloom-shell pb-14 sm:pb-20">
        <div class="grid overflow-hidden rounded-xl border border-bloom-line bg-white lg:grid-cols-[.82fr_1.18fr]">
            <div class="relative min-h-80 bg-bloom-blush lg:min-h-full">
                <img src="{{ asset('images/Home/dat-hoa-theo-mong-muon.jpg') }}" alt="Đặt hoa theo mong muốn" class="absolute inset-0 h-full w-full object-cover" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                <div class="absolute inset-0 bg-bloom-plum/35"></div>
                <div class="relative flex h-full min-h-80 flex-col justify-end p-7 text-white sm:p-9">
                    <p class="text-xs font-bold uppercase tracking-[.15em] text-white/70">BloomGift bespoke</p>
                    <h1 class="mt-3 max-w-sm font-display text-3xl font-semibold leading-tight">Một bó hoa dành riêng cho dịp của bạn.</h1>
                    <p class="mt-3 max-w-sm text-sm leading-6 text-white/80">Chia sẻ ý tưởng, màu sắc hoặc thông điệp bạn muốn gửi. BloomGift sẽ tiếp nhận và liên hệ để trao đổi.</p>
                </div>
            </div>

            <div class="p-6 sm:p-9 lg:p-11">
                <p class="bloom-eyebrow">Gửi yêu cầu</p>
                <h2 class="mt-2 font-display text-3xl font-semibold tracking-[-.035em] text-bloom-plum">Đặt hoa theo mong muốn</h2>
                <p class="bloom-subtitle mt-3">Bạn không cần đăng nhập. Nếu đã đăng nhập, thông tin của bạn sẽ được điền sẵn và vẫn có thể chỉnh sửa trước khi gửi.</p>

                @if (session('custom_order_request_code'))
                    <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm leading-6 text-emerald-800" role="status">
                        <p class="font-semibold">BloomGift đã nhận yêu cầu của bạn.</p>
                        <p>Chủ cửa hàng sẽ liên hệ để trao đổi và xác nhận.</p>
                        <p class="mt-2 font-bold">Mã yêu cầu: {{ session('custom_order_request_code') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
                @endif

                <form action="{{ route('custom.order.store') }}" method="POST" class="mt-7 grid gap-5 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label class="bloom-label" for="customer_name">Họ và tên <span class="text-bloom-danger">*</span></label>
                        <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', $customer?->name) }}" class="bloom-input h-11 w-full px-3" placeholder="Nhập họ và tên" required>
                    </div>
                    <div>
                        <label class="bloom-label" for="phone">Số điện thoại <span class="text-bloom-danger">*</span></label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone', $customer?->phone) }}" class="bloom-input h-11 w-full px-3" placeholder="Nhập số điện thoại" required>
                    </div>
                    <div>
                        <label class="bloom-label" for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $customer?->email) }}" class="bloom-input h-11 w-full px-3" placeholder="email@example.com">
                    </div>
                    <div>
                        <label class="bloom-label" for="occasion">Dịp tặng</label>
                        <input type="text" id="occasion" name="occasion" value="{{ old('occasion') }}" class="bloom-input h-11 w-full px-3" placeholder="Sinh nhật, khai trương...">
                    </div>
                    <div>
                        <label class="bloom-label" for="flower_type">Loại hoa mong muốn</label>
                        <input type="text" id="flower_type" name="flower_type" value="{{ old('flower_type') }}" class="bloom-input h-11 w-full px-3" placeholder="Hoa hồng, tulip...">
                    </div>
                    <div>
                        <label class="bloom-label" for="quantity">Số lượng</label>
                        <input type="number" id="quantity" name="quantity" min="1" value="{{ old('quantity') }}" class="bloom-input h-11 w-full px-3" placeholder="Ví dụ: 1">
                    </div>
                    <div>
                        <label class="bloom-label" for="budget">Ngân sách dự kiến</label>
                        <input type="number" id="budget" name="budget" min="0" step="1000" value="{{ old('budget') }}" class="bloom-input h-11 w-full px-3" placeholder="Ví dụ: 700000">
                    </div>
                    <div>
                        <label class="bloom-label" for="delivery_date">Ngày cần giao</label>
                        <input type="date" id="delivery_date" name="delivery_date" min="{{ now()->toDateString() }}" value="{{ old('delivery_date') }}" class="bloom-input h-11 w-full px-3">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="bloom-label" for="delivery_address">Địa chỉ giao</label>
                        <input type="text" id="delivery_address" name="delivery_address" value="{{ old('delivery_address', $customer?->address) }}" class="bloom-input h-11 w-full px-3" placeholder="Số nhà, đường, phường/xã, quận/huyện...">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="bloom-label" for="message">Nội dung/yêu cầu chi tiết</label>
                        <textarea id="message" name="message" rows="5" class="bloom-textarea w-full" placeholder="Màu sắc, kiểu bó hoa, thông điệp muốn gửi...">{{ old('message') }}</textarea>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
                        <button type="submit" class="bloom-button">Gửi yêu cầu đặt hoa <x-customer.icon name="arrow-right" class="h-4 w-4" /></button>
                        @auth
                            <a href="{{ route('customer.custom-orders.index') }}" class="bloom-text-link">Yêu cầu của tôi</a>
                        @else
                            <a href="{{ route('products.index') }}" class="bloom-text-link">Xem sản phẩm có sẵn</a>
                        @endauth
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-customer.layout>

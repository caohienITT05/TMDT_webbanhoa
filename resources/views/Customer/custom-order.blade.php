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
                    <p class="mt-3 max-w-sm text-sm leading-6 text-white/80">Chia sẻ ý tưởng, màu sắc hoặc thông điệp bạn muốn gửi. BloomGift sẽ tiếp nhận yêu cầu của bạn.</p>
                </div>
            </div>

            <div class="p-6 sm:p-9 lg:p-11">
                <p class="bloom-eyebrow">Gửi yêu cầu</p>
                <h2 class="mt-2 font-display text-3xl font-semibold tracking-[-.035em] text-bloom-plum">Đặt hoa theo mong muốn</h2>
                <p class="bloom-subtitle mt-3">Điền các thông tin dưới đây để chúng tôi hiểu rõ hơn về lựa chọn bạn đang tìm kiếm.</p>

                @if ($errors->any())
                    <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
                @endif

                <form action="{{ route('custom.order.store') }}" method="POST" class="mt-7 grid gap-5 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label class="bloom-label" for="name">Họ và tên</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" class="bloom-input h-11 w-full px-3" placeholder="Nhập họ và tên" required>
                    </div>
                    <div>
                        <label class="bloom-label" for="phone">Số điện thoại</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" class="bloom-input h-11 w-full px-3" placeholder="Nhập số điện thoại" required>
                    </div>
                    <div>
                        <label class="bloom-label" for="flower_type">Loại hoa mong muốn</label>
                        <select id="flower_type" name="flower_type" class="bloom-select w-full" required>
                            <option value="">Chọn loại hoa</option>
                            @foreach (['Hoa hồng', 'Hoa tulip', 'Hoa cúc', 'Hoa khai trương', 'Loại hoa khác'] as $option)
                                <option value="{{ $option }}" @selected(old('flower_type') === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="bloom-label" for="budget">Ngân sách dự kiến</label>
                        <input type="text" id="budget" name="budget" value="{{ old('budget') }}" class="bloom-input h-11 w-full px-3" placeholder="Ví dụ: 300.000 - 500.000 VNĐ">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="bloom-label" for="message">Yêu cầu chi tiết</label>
                        <textarea id="message" name="message" rows="5" class="bloom-textarea w-full" placeholder="Màu sắc, kiểu bó hoa, ngày nhận hoa, lời chúc..." required>{{ old('message') }}</textarea>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
                        <button type="submit" class="bloom-button">Gửi yêu cầu đặt hoa <x-customer.icon name="arrow-right" class="h-4 w-4" /></button>
                        <a href="{{ route('products.index') }}" class="bloom-text-link">Xem sản phẩm có sẵn</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-customer.layout>

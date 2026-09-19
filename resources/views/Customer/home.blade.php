<x-customer.layout title="Hoa tươi & quà tặng">
    @php
        $categoryImages = [
            'images/Categories/hoa-sinh-nhat.jpg',
            'images/Categories/hoa-tinh-yeu.jpg',
            'images/Categories/hoa-khai-truong.jpg',
            'images/Categories/hoa-dip-le.jpg',
        ];
        $saleProducts = $products->filter(fn ($product) => $product->sale_price !== null && (float) $product->sale_price > 0 && (float) $product->sale_price < (float) $product->price);
    @endphp

    <section class="overflow-hidden border-b border-bloom-line bg-bloom-blush">
        <div class="bloom-shell grid items-center gap-9 py-12 lg:grid-cols-[1.02fr_.98fr] lg:py-16">
            <div class="max-w-xl">
                <p class="bloom-eyebrow">BloomGift · Hoa & quà tặng</p>
                <h1 class="mt-4 font-display text-4xl font-semibold leading-[1.14] tracking-[-0.045em] text-bloom-plum sm:text-5xl">Gửi một món quà,<br>giữ trọn một khoảnh khắc.</h1>
                <p class="mt-5 max-w-lg text-base leading-7 text-bloom-muted">Chọn những bó hoa tươi và món quà phù hợp cho lời cảm ơn, lời chúc mừng hay một điều muốn nhắn gửi.</p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ route('products.index') }}" class="bloom-button">Khám phá sản phẩm <x-customer.icon name="arrow-right" class="h-4 w-4" /></a>
                    <a href="{{ route('custom.order') }}" class="bloom-button bloom-button--ghost">Đặt theo yêu cầu</a>
                </div>
            </div>
            <div class="relative mx-auto w-full max-w-xl">
                <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white/50"></div>
                <div class="relative aspect-[1.12/1] overflow-hidden rounded-[1rem] border border-white/70 bg-white shadow-bloom">
                    <img src="{{ asset('images/Home/hoa-tuoi.jpg') }}" alt="Hoa tươi BloomGift" class="h-full w-full object-cover" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                    <div class="hidden h-full w-full items-center justify-center bg-white text-bloom-muted"><x-customer.icon name="flower" class="h-12 w-12" /></div>
                </div>
                <div class="absolute -bottom-4 left-4 max-w-[15rem] rounded-lg border border-bloom-line bg-white px-4 py-3 shadow-bloom sm:left-8">
                    <p class="text-xs font-semibold uppercase tracking-[.12em] text-bloom-rose">BloomGift</p>
                    <p class="mt-1 text-sm font-medium leading-5 text-bloom-ink">Một lựa chọn tinh tế cho mọi dịp.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="bloom-section bloom-shell">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="bloom-eyebrow">Chọn theo dịp</p>
                <h2 class="bloom-title mt-2">Tìm món quà thật đúng ý</h2>
            </div>
            <a href="{{ route('categories') }}" class="bloom-text-link inline-flex items-center gap-1">Xem tất cả <x-customer.icon name="arrow-right" class="h-4 w-4" /></a>
        </div>

        @if ($categories->isNotEmpty())
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($categories->take(4) as $category)
                    <a href="{{ route('categories.show', $category->slug) }}" class="group relative isolate min-h-56 overflow-hidden rounded-xl bg-bloom-blush">
                        <img src="{{ asset($categoryImages[$loop->index % count($categoryImages)]) }}" alt="{{ $category->name }}" class="absolute inset-0 -z-10 h-full w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.classList.add('hidden');">
                        <div class="absolute inset-0 bg-gradient-to-t from-bloom-plum/75 via-bloom-plum/10 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-5 text-white">
                            <p class="font-display text-xl font-semibold">{{ $category->name }}</p>
                            <span class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-white/90">Khám phá <x-customer.icon name="arrow-right" class="h-4 w-4" /></span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="bloom-empty mt-8">
                <x-customer.icon name="flower" class="h-10 w-10 text-bloom-rose" />
                <h3 class="mt-4 font-display text-xl font-semibold text-bloom-plum">Danh mục đang được cập nhật</h3>
                <p class="mt-2 max-w-sm text-sm leading-6 text-bloom-muted">Hãy xem toàn bộ sản phẩm hiện có tại BloomGift.</p>
                <a href="{{ route('products.index') }}" class="bloom-button mt-5">Xem sản phẩm</a>
            </div>
        @endif
    </section>

    @if ($products->isNotEmpty())
        <section class="border-y border-bloom-line bg-white">
            <div class="bloom-section bloom-shell">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="bloom-eyebrow">Được lựa chọn gần đây</p>
                        <h2 class="bloom-title mt-2">Hoa nổi bật của BloomGift</h2>
                        <p class="bloom-subtitle mt-2">Sản phẩm hiển thị từ danh mục hiện có.</p>
                    </div>
                    <a href="{{ route('products.index') }}" class="bloom-text-link inline-flex items-center gap-1">Xem toàn bộ <x-customer.icon name="arrow-right" class="h-4 w-4" /></a>
                </div>
                <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
                    @foreach ($products as $product)
                        <x-customer.product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($saleProducts->isNotEmpty())
        <section class="bloom-section bloom-shell">
            <div class="grid items-center gap-6 rounded-xl bg-bloom-plum px-6 py-8 text-white lg:grid-cols-[.78fr_1.22fr] lg:px-10 lg:py-10">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.16em] text-white/65">Ưu đãi đang có</p>
                    <h2 class="mt-3 font-display text-3xl font-semibold leading-tight">Một lựa chọn xinh đẹp với mức giá thật vừa vặn.</h2>
                    <p class="mt-3 text-sm leading-6 text-white/70">Các sản phẩm dưới đây đang có giá ưu đãi trong danh mục.</p>
                    <a href="{{ route('products.index') }}" class="mt-5 inline-flex items-center gap-1 text-sm font-bold text-white underline decoration-white/40 underline-offset-4 hover:decoration-white">Xem tất cả sản phẩm <x-customer.icon name="arrow-right" class="h-4 w-4" /></a>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    @foreach ($saleProducts->take(2) as $product)
                        <a href="{{ route('product.detail', $product->slug ?: $product->id) }}" class="group overflow-hidden rounded-lg bg-white text-bloom-ink">
                            <x-customer.product-image :product="$product" class="aspect-[1.35/1] w-full" />
                            <div class="p-3">
                                <p class="truncate text-sm font-semibold">{{ $product->name }}</p>
                                <p class="mt-1 text-sm font-bold text-bloom-rose">{{ number_format($product->sale_price, 0, ',', '.') }}₫</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="border-t border-bloom-line bg-white">
        <div class="bloom-section bloom-shell grid gap-5 sm:grid-cols-3">
            <article class="bloom-panel bloom-panel--padded">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-bloom-blush text-bloom-rose"><x-customer.icon name="gift" class="h-5 w-5" /></span>
                <h2 class="bloom-card-title mt-4">Chọn theo mong muốn</h2>
                <p class="mt-2 text-sm leading-6 text-bloom-muted">Gửi yêu cầu riêng nếu bạn cần một món quà phù hợp với dịp đặc biệt.</p>
            </article>
            <article class="bloom-panel bloom-panel--padded">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-bloom-blush text-bloom-rose"><x-customer.icon name="credit-card" class="h-5 w-5" /></span>
                <h2 class="bloom-card-title mt-4">Thanh toán linh hoạt</h2>
                <p class="mt-2 text-sm leading-6 text-bloom-muted">Chọn COD hoặc PayPal khi hoàn tất đơn hàng theo nhu cầu của bạn.</p>
            </article>
            <article class="bloom-panel bloom-panel--padded">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-bloom-blush text-bloom-rose"><x-customer.icon name="receipt" class="h-5 w-5" /></span>
                <h2 class="bloom-card-title mt-4">Theo dõi đơn dễ dàng</h2>
                <p class="mt-2 text-sm leading-6 text-bloom-muted">Xem lại thông tin và trạng thái các đơn đã đặt trong tài khoản của bạn.</p>
            </article>
        </div>
    </section>

    <section class="bloom-section bloom-shell">
        <div class="grid overflow-hidden rounded-xl border border-bloom-line bg-bloom-blush lg:grid-cols-[1.1fr_.9fr]">
            <div class="p-8 sm:p-10">
                <p class="bloom-eyebrow">Bạn đã sẵn sàng?</p>
                <h2 class="bloom-title mt-3 max-w-lg">Tìm một bó hoa khiến người nhận mỉm cười.</h2>
                <p class="bloom-subtitle mt-4 max-w-lg">Khám phá catalogue, lưu lại sản phẩm yêu thích hoặc bắt đầu một yêu cầu đặt hoa riêng.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('products.index') }}" class="bloom-button">Xem sản phẩm</a>
                    <a href="{{ route('custom.order') }}" class="bloom-button bloom-button--ghost">Gửi yêu cầu</a>
                </div>
            </div>
            <img src="{{ asset('images/Home/goi-qua.jpg') }}" alt="Quà tặng BloomGift" class="h-full min-h-64 w-full object-cover" onerror="this.classList.add('hidden');">
        </div>
    </section>
</x-customer.layout>

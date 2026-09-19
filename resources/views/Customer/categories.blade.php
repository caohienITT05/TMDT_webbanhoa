<x-customer.layout title="Danh mục hoa">
    @php
        $categoryImages = [
            'images/Categories/hoa-sinh-nhat.jpg',
            'images/Categories/hoa-tinh-yeu.jpg',
            'images/Categories/hoa-khai-truong.jpg',
            'images/Categories/hoa-dip-le.jpg',
        ];
    @endphp

    <x-customer.breadcrumb :items="[
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Danh mục hoa'],
    ]" />

    <section class="bloom-shell pb-14 sm:pb-20">
        <div class="max-w-2xl">
            <p class="bloom-eyebrow">BloomGift collection</p>
            <h1 class="bloom-title mt-2">Chọn hoa theo dịp và cảm xúc</h1>
            <p class="bloom-subtitle mt-3">Khám phá các nhóm sản phẩm hiện có để tìm một lựa chọn phù hợp với khoảnh khắc bạn muốn gửi trao.</p>
        </div>

        @if ($categories->isNotEmpty())
            <div class="mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($categories as $category)
                    <article class="group overflow-hidden rounded-xl border border-bloom-line bg-white transition hover:-translate-y-1 hover:shadow-bloom">
                        <a href="{{ route('categories.show', $category->slug) }}" class="block aspect-[1.5/1] overflow-hidden bg-bloom-blush">
                            <img src="{{ asset($categoryImages[$loop->index % count($categoryImages)]) }}" alt="{{ $category->name }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                            <div class="hidden h-full w-full items-center justify-center text-bloom-rose"><x-customer.icon name="flower" class="h-12 w-12" /></div>
                        </a>
                        <div class="flex items-start justify-between gap-4 p-5">
                            <div>
                                <h2 class="font-display text-xl font-semibold text-bloom-plum"><a href="{{ route('categories.show', $category->slug) }}" class="transition hover:text-bloom-rose">{{ $category->name }}</a></h2>
                                <p class="mt-1 text-sm text-bloom-muted">{{ number_format($category->products_count) }} sản phẩm</p>
                            </div>
                            <a href="{{ route('categories.show', $category->slug) }}" class="bloom-icon-button shrink-0 bg-bloom-blush text-bloom-rose" aria-label="Xem {{ $category->name }}"><x-customer.icon name="arrow-right" class="h-4 w-4" /></a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="bloom-empty mt-9">
                <x-customer.icon name="flower" class="h-10 w-10 text-bloom-rose" />
                <h2 class="mt-4 font-display text-xl font-semibold text-bloom-plum">Chưa có danh mục để hiển thị</h2>
                <p class="mt-2 max-w-md text-sm leading-6 text-bloom-muted">Bạn vẫn có thể xem những sản phẩm đang được BloomGift cung cấp.</p>
                <a href="{{ route('products.index') }}" class="bloom-button mt-5">Xem sản phẩm</a>
            </div>
        @endif
    </section>
</x-customer.layout>

<x-customer.layout title="Sản phẩm yêu thích">
    <x-customer.breadcrumb :items="[
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Sản phẩm yêu thích'],
    ]" />

    <section class="bloom-shell pb-14 sm:pb-20">
        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-bloom-line pb-7">
            <div>
                <p class="bloom-eyebrow">Danh sách của bạn</p>
                <h1 class="bloom-title mt-2">Sản phẩm yêu thích</h1>
                <p class="bloom-subtitle mt-3">Lưu lại những lựa chọn bạn muốn xem lại hoặc thêm vào giỏ sau.</p>
            </div>
            @if ($products->count())
                <p class="text-sm text-bloom-muted">{{ number_format($products->total()) }} sản phẩm</p>
            @endif
        </div>

        @if ($products->count())
            <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-customer.product-card :product="$product" />
                @endforeach
            </div>
            @if ($products->hasPages())
                <div class="mt-10 border-t border-bloom-line pt-6">{{ $products->onEachSide(1)->links() }}</div>
            @endif
        @else
            <div class="bloom-empty mt-8">
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-bloom-blush text-bloom-rose"><x-customer.icon name="heart" class="h-6 w-6" /></span>
                <h2 class="mt-4 font-display text-xl font-semibold text-bloom-plum">Danh sách yêu thích đang trống</h2>
                <p class="mt-2 max-w-md text-sm leading-6 text-bloom-muted">Lưu lại sản phẩm bạn thích để thuận tiện so sánh và đặt hàng sau này.</p>
                <a href="{{ route('products.index') }}" class="bloom-button mt-5">Khám phá sản phẩm</a>
            </div>
        @endif
    </section>
</x-customer.layout>

<x-customer.layout :title="isset($category) ? $category->name : 'Sản phẩm'">
    @php
        $pageTitle = isset($category) ? $category->name : 'Sản phẩm';
        $pageDescription = isset($category)
            ? 'Khám phá các sản phẩm thuộc danh mục ' . $category->name . '.'
            : 'Chọn hoa và quà tặng phù hợp cho từng khoảnh khắc.';
    @endphp

    <x-customer.breadcrumb :items="array_filter([
        ['label' => 'Trang chủ', 'url' => route('home')],
        isset($category) ? ['label' => 'Danh mục hoa', 'url' => route('categories')] : null,
        ['label' => $pageTitle],
    ])" />

    <section class="bloom-shell pb-14 sm:pb-20">
        <div class="flex flex-col gap-6 border-b border-bloom-line pb-7 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="bloom-eyebrow">BloomGift collection</p>
                <h1 class="bloom-title mt-2">{{ $pageTitle }}</h1>
                <p class="bloom-subtitle mt-3 max-w-xl">{{ $pageDescription }}</p>
            </div>
            <p class="text-sm text-bloom-muted">{{ number_format($products->total()) }} sản phẩm</p>
        </div>

        <form action="{{ route('products.index') }}" method="GET" class="bloom-panel mt-6 grid gap-3 p-4 sm:grid-cols-[1fr_auto_auto] sm:items-end">
            <div>
                <label class="bloom-label" for="product-search">Tìm kiếm</label>
                <div class="relative">
                    <x-customer.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-bloom-muted" />
                    <input id="product-search" name="search" value="{{ request('search') }}" class="bloom-input h-11 w-full pl-9 pr-3" placeholder="Tên sản phẩm...">
                </div>
            </div>
            <div>
                <label class="bloom-label" for="product-category">Danh mục</label>
                <select id="product-category" name="category" class="bloom-select w-full min-w-44">
                    <option value="">Tất cả danh mục</option>
                    @foreach ($categories as $item)
                        <option value="{{ $item->id }}" @selected((string) request('category') === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bloom-button flex-1 sm:flex-none">Lọc sản phẩm</button>
                @if (request()->filled('search') || request()->filled('category'))
                    <a href="{{ route('products.index') }}" class="bloom-button bloom-button--ghost flex-1 sm:flex-none">Xóa lọc</a>
                @endif
            </div>
        </form>

        @if ($products->count())
            <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-customer.product-card :product="$product" />
                @endforeach
            </div>

            @if ($products->hasPages())
                <div class="mt-10 border-t border-bloom-line pt-6">
                    {{ $products->onEachSide(1)->links() }}
                </div>
            @endif
        @else
            <div class="bloom-empty mt-8">
                <x-customer.icon name="search" class="h-10 w-10 text-bloom-rose" />
                <h2 class="mt-4 font-display text-xl font-semibold text-bloom-plum">Chưa tìm thấy sản phẩm phù hợp</h2>
                <p class="mt-2 max-w-md text-sm leading-6 text-bloom-muted">Hãy thử một từ khóa khác hoặc xem lại toàn bộ danh mục sản phẩm.</p>
                <a href="{{ route('products.index') }}" class="bloom-button mt-5">Xem tất cả sản phẩm</a>
            </div>
        @endif
    </section>
</x-customer.layout>

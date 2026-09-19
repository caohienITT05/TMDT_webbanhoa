@props(['product'])

@php
    $originalPrice = (float) ($product->price ?? 0);
    $salePrice = $product->sale_price !== null ? (float) $product->sale_price : null;
    $hasSale = $salePrice !== null && $salePrice > 0 && $salePrice < $originalPrice;
    $currentPrice = $hasSale ? $salePrice : $originalPrice;
    $discountPercent = $hasSale && $originalPrice > 0 ? (int) round((1 - ($salePrice / $originalPrice)) * 100) : 0;
    $isFavorite = in_array($product->id, session('favorites', []));
    $productUrl = route('product.detail', $product->slug ?: $product->id);
@endphp

<article class="group relative flex h-full flex-col overflow-hidden rounded-xl border border-bloom-line bg-white transition duration-200 hover:-translate-y-1 hover:shadow-bloom">
    <a href="{{ $productUrl }}" class="relative block aspect-[4/4.8] overflow-hidden bg-bloom-blush">
        <x-customer.product-image :product="$product" class="h-full w-full transition duration-500 group-hover:scale-[1.035]" />
        @if ($hasSale && $discountPercent > 0)
            <span class="absolute left-3 top-3 rounded-full bg-bloom-rose px-2.5 py-1 text-[11px] font-semibold text-white">-{{ $discountPercent }}%</span>
        @endif
        @if (isset($product->stock) && (int) $product->stock < 1)
            <span class="absolute inset-x-3 bottom-3 rounded-md bg-bloom-plum/85 px-2 py-1.5 text-center text-xs font-medium text-white">Tạm hết hàng</span>
        @endif
    </a>
    <form method="POST" action="{{ route('favorites.toggle', $product->id) }}" class="absolute right-3 top-3">
        @csrf
        <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-bloom-plum shadow-sm transition hover:bg-white hover:text-bloom-rose" aria-label="{{ $isFavorite ? 'Bỏ yêu thích' : 'Thêm vào yêu thích' }}">
            <x-customer.icon name="heart" class="h-4 w-4 {{ $isFavorite ? 'fill-current text-bloom-rose' : '' }}" />
        </button>
    </form>
    <div class="flex flex-1 flex-col p-4">
        @if ($product->relationLoaded('category') && $product->category)
            <p class="mb-1 text-xs font-medium text-bloom-muted">{{ $product->category->name }}</p>
        @endif
        <h3 class="line-clamp-2 min-h-12 font-display text-lg font-semibold leading-6 text-bloom-ink"><a href="{{ $productUrl }}" class="transition hover:text-bloom-rose">{{ $product->name }}</a></h3>
        <div class="mt-3 flex flex-wrap items-baseline gap-x-2 gap-y-1">
            <span class="text-base font-bold text-bloom-rose">{{ number_format($currentPrice, 0, ',', '.') }}₫</span>
            @if ($hasSale)
                <span class="text-xs text-bloom-muted line-through">{{ number_format($originalPrice, 0, ',', '.') }}₫</span>
            @endif
        </div>
        <div class="mt-4 grid grid-cols-2 gap-2">
            <a href="{{ $productUrl }}" class="bloom-button bloom-button--ghost bloom-button--small justify-center">Chi tiết</a>
            @if (!isset($product->stock) || (int) $product->stock > 0)
                <form method="POST" action="{{ route('cart.add', $product->id) }}">
                    @csrf
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="bloom-button bloom-button--small w-full justify-center">Thêm giỏ</button>
                </form>
            @else
                <span class="flex items-center justify-center text-xs font-medium text-bloom-muted">Hết hàng</span>
            @endif
        </div>
    </div>
</article>

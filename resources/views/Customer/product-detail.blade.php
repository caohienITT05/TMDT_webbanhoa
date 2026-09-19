<x-customer.layout :title="$product->name">
    @php
        $originalPrice = (float) ($product->price ?? 0);
        $salePrice = $product->sale_price !== null ? (float) $product->sale_price : null;
        $hasSale = $salePrice !== null && $salePrice > 0 && $salePrice < $originalPrice;
        $currentPrice = $hasSale ? $salePrice : $originalPrice;
        $discountPercent = $hasSale && $originalPrice > 0 ? (int) round((1 - ($salePrice / $originalPrice)) * 100) : 0;
        $isFavorite = in_array($product->id, session('favorites', []));
        $category = $product->category;
    @endphp

    <x-customer.breadcrumb :items="array_filter([
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Sản phẩm', 'url' => route('products.index')],
        $category ? ['label' => $category->name, 'url' => route('categories.show', $category->slug)] : null,
        ['label' => $product->name],
    ])" />

    <section class="bloom-shell pb-14 sm:pb-20">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1.02fr)_minmax(22rem,.98fr)] lg:gap-12">
            <div class="relative overflow-hidden rounded-xl border border-bloom-line bg-bloom-blush">
                <x-customer.product-image :product="$product" class="aspect-square w-full" />
                @if ($hasSale && $discountPercent > 0)
                    <span class="absolute left-5 top-5 rounded-full bg-bloom-rose px-3 py-1.5 text-xs font-bold text-white">Giảm {{ $discountPercent }}%</span>
                @endif
            </div>

            <div class="flex flex-col">
                @if ($category)
                    <a href="{{ route('categories.show', $category->slug) }}" class="bloom-eyebrow w-fit hover:underline">{{ $category->name }}</a>
                @endif
                <h1 class="mt-3 font-display text-3xl font-semibold leading-tight tracking-[-.035em] text-bloom-plum sm:text-4xl">{{ $product->name }}</h1>
                <div class="mt-5 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <span class="text-2xl font-bold text-bloom-rose">{{ number_format($currentPrice, 0, ',', '.') }}₫</span>
                    @if ($hasSale)
                        <span class="text-base text-bloom-muted line-through">{{ number_format($originalPrice, 0, ',', '.') }}₫</span>
                    @endif
                </div>

                <div class="mt-5 flex items-center gap-2 text-sm">
                    <span class="h-2 w-2 rounded-full {{ (int) $product->stock > 0 ? 'bg-emerald-500' : 'bg-stone-400' }}"></span>
                    <span class="font-medium {{ (int) $product->stock > 0 ? 'text-emerald-700' : 'text-bloom-muted' }}">{{ (int) $product->stock > 0 ? 'Sẵn sàng đặt hàng' : 'Tạm hết hàng' }}</span>
                    @if ((int) $product->stock > 0)
                        <span class="text-bloom-muted">· Còn {{ $product->stock }} sản phẩm</span>
                    @endif
                </div>

                @if ($product->description)
                    <div class="mt-6 border-y border-bloom-line py-5">
                        <h2 class="text-sm font-bold text-bloom-ink">Mô tả sản phẩm</h2>
                        <div class="mt-2 text-sm leading-7 text-bloom-muted">{!! nl2br(e($product->description)) !!}</div>
                    </div>
                @endif

                <div class="mt-6">
                    @if ((int) $product->stock > 0)
                        <form method="POST" action="{{ route('cart.add', $product->id) }}" class="flex flex-wrap items-center gap-3">
                            @csrf
                            <label class="sr-only" for="quantity">Số lượng</label>
                            <div class="bloom-quantity" x-data="{ quantity: {{ max(1, (int) old('quantity', 1)) }} }">
                                <button type="button" @click="quantity = Math.max(1, quantity - 1)" aria-label="Giảm số lượng"><x-customer.icon name="minus" class="h-4 w-4" /></button>
                                <input id="quantity" name="quantity" type="number" min="1" max="99" x-model.number="quantity" value="{{ old('quantity', 1) }}">
                                <button type="button" @click="quantity = Math.min(99, quantity + 1)" aria-label="Tăng số lượng"><x-customer.icon name="plus" class="h-4 w-4" /></button>
                            </div>
                            <button type="submit" class="bloom-button min-w-48">Thêm vào giỏ hàng <x-customer.icon name="bag" class="h-4 w-4" /></button>
                            <button formaction="{{ route('favorites.toggle', $product->id) }}" formmethod="POST" class="bloom-icon-button border border-bloom-line bg-white {{ $isFavorite ? 'text-bloom-rose' : '' }}" title="{{ $isFavorite ? 'Bỏ yêu thích' : 'Thêm yêu thích' }}" aria-label="{{ $isFavorite ? 'Bỏ yêu thích' : 'Thêm yêu thích' }}">
                                <x-customer.icon name="heart" class="h-5 w-5 {{ $isFavorite ? 'fill-current' : '' }}" />
                            </button>
                        </form>
                    @else
                        <div class="rounded-lg bg-bloom-blush px-4 py-4 text-sm leading-6 text-bloom-muted">Sản phẩm này hiện chưa sẵn sàng để thêm vào giỏ. Bạn có thể lưu lại trong danh sách yêu thích để xem sau.</div>
                        <form method="POST" action="{{ route('favorites.toggle', $product->id) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="bloom-button bloom-button--ghost">{{ $isFavorite ? 'Bỏ khỏi yêu thích' : 'Lưu vào yêu thích' }}</button>
                        </form>
                    @endif
                </div>

                <div class="mt-8 grid gap-3 border-t border-bloom-line pt-6 sm:grid-cols-3">
                    <div class="flex gap-2.5"><x-customer.icon name="calendar" class="mt-0.5 h-5 w-5 shrink-0 text-bloom-rose" /><p class="text-xs leading-5 text-bloom-muted"><span class="block font-bold text-bloom-ink">Chọn lịch giao</span>Ngày và khung giờ giao được chọn khi thanh toán.</p></div>
                    <div class="flex gap-2.5"><x-customer.icon name="gift" class="mt-0.5 h-5 w-5 shrink-0 text-bloom-rose" /><p class="text-xs leading-5 text-bloom-muted"><span class="block font-bold text-bloom-ink">Gửi kèm lời nhắn</span>Thêm lời chúc cho người nhận trong đơn hàng.</p></div>
                    <div class="flex gap-2.5"><x-customer.icon name="credit-card" class="mt-0.5 h-5 w-5 shrink-0 text-bloom-rose" /><p class="text-xs leading-5 text-bloom-muted"><span class="block font-bold text-bloom-ink">Thanh toán</span>Hỗ trợ COD và PayPal theo lựa chọn của bạn.</p></div>
                </div>
            </div>
        </div>
    </section>

    @if ($relatedProducts->isNotEmpty())
        <section class="border-t border-bloom-line bg-white">
            <div class="bloom-section bloom-shell">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="bloom-eyebrow">Gợi ý thêm</p>
                        <h2 class="bloom-title mt-2">Sản phẩm cùng danh mục</h2>
                    </div>
                    @if ($category)
                        <a href="{{ route('categories.show', $category->slug) }}" class="bloom-text-link inline-flex items-center gap-1">Xem danh mục <x-customer.icon name="arrow-right" class="h-4 w-4" /></a>
                    @endif
                </div>
                <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
                    @foreach ($relatedProducts as $relatedProduct)
                        <x-customer.product-card :product="$relatedProduct" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-customer.layout>

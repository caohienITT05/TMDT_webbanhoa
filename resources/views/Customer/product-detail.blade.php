@php
    $originalPrice = (float) ($product->price ?? 0);
    $salePrice = $product->sale_price !== null ? (float) $product->sale_price : null;
    $hasSale = $salePrice !== null && $salePrice > 0 && $salePrice < $originalPrice;
    $currentPrice = $hasSale ? $salePrice : $originalPrice;
    $discountPercent = $hasSale && $originalPrice > 0 ? (int) round((1 - ($salePrice / $originalPrice)) * 100) : 0;
    $isFavorite = in_array($product->id, session('favorites', []));
    $category = $product->category;
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => strip_tags($product->description ?: $product->name),
        'url' => route('product.detail', $product->slug),
        'offers' => [
            '@type' => 'Offer',
            'priceCurrency' => 'VND',
            'price' => (string) $currentPrice,
            'availability' => (int) $product->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => route('product.detail', $product->slug),
        ],
    ];
    if ($product->image) {
        $structuredData['image'] = [asset('storage/' . $product->image)];
    }
    if (($reviewSummary->review_count ?? 0) > 0) {
        $structuredData['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => round((float) $reviewSummary->average_rating, 2),
            'reviewCount' => (int) $reviewSummary->review_count,
        ];
    }
@endphp
<x-customer.layout :title="$product->name" :description="\Illuminate\Support\Str::limit(strip_tags($product->description ?: $product->name), 155)" :structured-data="$structuredData">

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
                <div class="mt-2 flex flex-wrap gap-x-4 text-sm text-bloom-muted">
                    @if ((int) ($product->published_reviews_count ?? 0) > 0)
                        <span data-product-rating-summary><span class="text-amber-500">★</span> {{ number_format((float) ($product->published_reviews_avg_rating ?? 0), 1, ',', '.') }} / 5 · {{ $product->published_reviews_count }} đánh giá</span>
                    @else
                        <span>Chưa có đánh giá đã duyệt</span>
                    @endif
                    <span data-sold-quantity>Đã bán {{ (int) ($product->display_sold_quantity ?? $product->ordered_quantity ?? 0) }}</span>
                </div>
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
                        <form method="POST" action="{{ route('cart.add', $product->id) }}" class="flex flex-wrap items-center gap-3" x-data="{ quantity: {{ max(1, (int) old('quantity', 1)) }}, maxQuantity: {{ (int) $product->stock }} }">
                            @csrf
                            <label class="sr-only" for="quantity">Số lượng</label>
                            <div class="bloom-quantity">
                                <button type="button" @click="quantity = Math.max(1, quantity - 1)" aria-label="Giảm số lượng"><x-customer.icon name="minus" class="h-4 w-4" /></button>
                                <input id="quantity" name="quantity" type="number" min="1" :max="maxQuantity" x-model.number="quantity" @input="quantity = Math.min(maxQuantity, Math.max(1, quantity || 1))">
                                <button type="button" @click="quantity = Math.min(maxQuantity, quantity + 1)" aria-label="Tăng số lượng"><x-customer.icon name="plus" class="h-4 w-4" /></button>
                            </div>
                            <button type="submit" class="bloom-button bloom-button--outline min-w-44">Thêm vào giỏ <x-customer.icon name="cart-plus" class="h-4 w-4" /></button>
                            <button type="submit" formaction="{{ route('buy-now.start', $product->id) }}" formmethod="POST" class="bloom-button min-w-36">Đặt hàng <x-customer.icon name="bag" class="h-4 w-4" /></button>
                        </form>
                        <form method="POST" action="{{ route('favorites.toggle', $product->id) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="bloom-icon-button border border-bloom-line bg-white {{ $isFavorite ? 'text-bloom-rose' : '' }}" title="{{ $isFavorite ? 'Bỏ yêu thích' : 'Thêm yêu thích' }}" aria-label="{{ $isFavorite ? 'Bỏ yêu thích' : 'Thêm yêu thích' }}">
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

    @if(\Illuminate\Support\Facades\Schema::hasTable('product_reviews'))
    <section id="reviews" class="border-t border-bloom-line bg-white">
        <div class="bloom-section bloom-shell">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,.75fr)]">
                <div>
                    <p class="bloom-eyebrow">Chia sẻ trải nghiệm</p>
                    <h2 class="bloom-title mt-2">Đánh giá sản phẩm</h2>
                    <p class="mt-2 text-sm text-bloom-muted" data-review-summary>{{ $reviewSummary->review_count ? number_format((float) $reviewSummary->average_rating, 1, ',', '.') . ' / 5 từ ' . $reviewSummary->review_count . ' đánh giá' : 'Chưa có đánh giá.' }}</p>
                    <div id="published-review-list" class="mt-5 space-y-4">
                        @foreach($reviews as $review)
                            <article class="rounded-xl border border-bloom-line p-4" data-review-id="{{ $review->id }}">
                                <div class="flex flex-wrap justify-between gap-2"><strong>{{ $review->reviewer_name }}</strong><span class="text-amber-600">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', 5 - (int) $review->rating) }}</span></div>
                                <p class="mt-1 text-xs text-bloom-muted">{{ $review->published_at?->format('d/m/Y') }} · {{ $review->is_verified_purchase ? 'Đã mua hàng' : 'Đánh giá chưa xác minh đơn mua' }}</p>
                                @if($review->title)<h3 class="mt-3 font-semibold">{{ $review->title }}</h3>@endif
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-bloom-muted">{{ $review->content }}</p>
                                @if($review->image_path)<img class="mt-3 max-h-56 rounded-lg" src="{{ asset('storage/'.$review->image_path) }}" alt="Ảnh đánh giá của khách">@endif
                                @if($review->store_response)<div class="mt-3 rounded-lg bg-bloom-blush p-3 text-sm"><strong>BloomGift phản hồi:</strong> {{ $review->store_response }}</div>@endif
                                @if(\Illuminate\Support\Facades\Schema::hasTable('product_review_reports') && auth()->user()?->role === 'customer' && auth()->user()?->is_active && auth()->id() !== $review->user_id)
                                    <details class="mt-3 border-t border-bloom-line pt-3 text-xs text-bloom-muted">
                                        <summary class="w-fit cursor-pointer">Báo cáo đánh giá</summary>
                                        <form method="POST" action="{{ route('products.reviews.report', $review) }}" class="mt-2 grid gap-2 sm:grid-cols-[1fr_1.5fr_auto]">
                                            @csrf
                                            <select name="reason" required class="bloom-select h-10"><option value="">Chọn lý do</option><option value="spam">Spam</option><option value="abuse">Ngôn từ vi phạm</option><option value="irrelevant">Không liên quan</option><option value="other">Lý do khác</option></select>
                                            <input name="details" maxlength="500" class="bloom-input h-10 px-3" placeholder="Ghi chú (không bắt buộc)">
                                            <button class="rounded-lg border border-bloom-line px-3 py-2 font-semibold text-bloom-plum">Gửi báo cáo</button>
                                        </form>
                                    </details>
                                @endif
                            </article>
                        @endforeach
                    </div>
                    @if($reviews instanceof \Illuminate\Contracts\Pagination\Paginator || $reviews instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)<div class="mt-4">{{ $reviews->links() }}</div>@endif
                </div>
                @if(auth()->guest())
                    <div class="h-fit rounded-xl border border-bloom-line bg-bloom-cream p-5">
                        <h3 class="font-semibold text-bloom-ink">Vui lòng đăng nhập để đánh giá sản phẩm.</h3>
                        <p class="mt-2 text-sm text-bloom-muted">Đăng nhập tài khoản khách hàng để chia sẻ đánh giá của bạn.</p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <a class="bloom-button" href="{{ route('login') }}">Đăng nhập</a>
                            <a class="bloom-button-secondary" href="{{ route('register') }}">Đăng ký</a>
                        </div>
                    </div>
                @elseif(!$isReviewCustomer)
                    <div class="h-fit rounded-xl border border-bloom-line bg-bloom-cream p-5 text-sm text-bloom-muted">Chức năng đánh giá chỉ dành cho tài khoản khách hàng.</div>
                @elseif($alreadyReviewed && !$reviewHistoryReady)
                    <div class="h-fit rounded-xl border border-bloom-line bg-bloom-cream p-5 text-sm text-bloom-muted">Bạn đã gửi đánh giá cho sản phẩm này.</div>
                @elseif(!$reviewPolicyReady)
                    <div class="h-fit rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">Chức năng đánh giá đang được cập nhật an toàn. Vui lòng thử lại sau.</div>
                @elseif($canReview)
                    <form method="POST" enctype="multipart/form-data" action="{{ $existingReview ? route('products.reviews.update', $product) : route('products.reviews.store', $product) }}" class="h-fit rounded-xl border border-bloom-line bg-bloom-cream p-5" data-review-form data-existing-review-id="{{ $existingReview?->id }}">
                        @csrf
                        @if($existingReview) @method('PUT') @endif
                        <h3 class="font-semibold text-bloom-ink">{{ $existingReview ? 'Chỉnh sửa đánh giá' : 'Đánh giá sản phẩm' }}</h3>
                        <div class="mt-3 flex items-center gap-3">
                            <x-customer.product-image :product="$product" class="h-14 w-14 shrink-0 rounded-lg" />
                            <p class="font-semibold text-bloom-ink">{{ $product->name }}</p>
                        </div>
                        <p class="mt-3 text-sm text-bloom-muted">Bạn có thể đánh giá bằng tài khoản BloomGift. Nhãn “Đã mua hàng” chỉ hiển thị khi đơn hàng được xác nhận.</p>
                        <div class="mt-4 grid gap-3">
                            <input type="hidden" name="rating" value="{{ old('rating', $existingReview?->rating ?? '') }}" required data-review-rating>
                            <div role="radiogroup" aria-label="Chọn số sao" class="flex items-center gap-1" data-review-stars>
                                @foreach(range(1,5) as $rating)
                                    <button type="button" role="radio" aria-checked="false" aria-label="{{ $rating }} sao" data-rating="{{ $rating }}" class="rounded-lg p-1 text-4xl leading-none text-gray-300 transition hover:scale-110 focus:outline-none focus:ring-2 focus:ring-rose-400">☆</button>
                                @endforeach
                                <span class="ml-2 text-sm text-bloom-muted" data-rating-label>Chọn số sao</span>
                            </div>
                            <label for="review-content" class="text-sm font-semibold text-bloom-ink">Nội dung đánh giá</label>
                            <textarea id="review-content" class="bloom-input min-h-28 p-3" name="content" minlength="10" maxlength="4000" required placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm…">{{ old('content', $existingReview?->content ?? '') }}</textarea>
                            <label class="text-sm font-semibold text-bloom-ink">Thêm hình ảnh (không bắt buộc, tối đa 4 MB)
                                <input class="mt-1 block w-full text-sm font-normal" type="file" name="image" accept="image/jpeg,image/png,image/webp" data-review-image>
                            </label>
                            <div class="hidden items-start gap-3" data-image-preview>
                                <img src="" alt="Xem trước ảnh đánh giá" class="max-h-32 rounded-lg border border-bloom-line object-contain" data-preview-image>
                                <button type="button" class="text-sm font-semibold text-rose-700 underline" data-remove-image>Xóa ảnh</button>
                            </div>
                            <p class="hidden text-sm text-red-700" role="alert" data-review-errors></p>
                            <p class="text-sm text-emerald-800" role="status" data-review-status></p>
                            <button class="bloom-button" type="submit" data-review-submit>{{ $existingReview ? 'Lưu chỉnh sửa' : 'Gửi đánh giá' }}</button>
                        </div>
                    </form>
                @else
                    <div class="h-fit rounded-xl border border-bloom-line bg-bloom-cream p-5 text-sm text-bloom-muted">Vui lòng dùng một tài khoản khách hàng cho mỗi sản phẩm.</div>
                @endif
            </div>
        </div>
    </section>

    @endif

    <script>
        document.querySelectorAll('[data-review-form]').forEach((form) => {
            const ratingInput = form.querySelector('[data-review-rating]');
            const stars = [...form.querySelectorAll('[data-rating]')];
            const ratingLabel = form.querySelector('[data-rating-label]');
            const imageInput = form.querySelector('[data-review-image]');
            const contentInput = form.querySelector('[name="content"]');
            const preview = form.querySelector('[data-image-preview]');
            const previewImage = form.querySelector('[data-preview-image]');
            const errorBox = form.querySelector('[data-review-errors]');
            const statusBox = form.querySelector('[data-review-status]');
            const submitButton = form.querySelector('[data-review-submit]');
            const idleSubmitLabel = submitButton.textContent;
            const existingReviewId = form.dataset.existingReviewId;
            let previewUrl = null;

            const setRating = (value) => {
                ratingInput.value = value ? String(value) : '';
                stars.forEach((star) => {
                    const selected = Number(star.dataset.rating) <= Number(value || 0);
                    star.textContent = selected ? '★' : '☆';
                    star.classList.toggle('text-amber-500', selected);
                    star.classList.toggle('text-gray-300', !selected);
                    star.setAttribute('aria-checked', Number(star.dataset.rating) === Number(value) ? 'true' : 'false');
                });
                ratingLabel.textContent = value ? value + ' sao' : 'Chọn số sao';
                updateReadyState();
            };

            const updateReadyState = () => {
                submitButton.disabled = !ratingInput.value || contentInput.value.trim().length < 10;
                submitButton.classList.toggle('opacity-50', submitButton.disabled);
                submitButton.classList.toggle('cursor-not-allowed', submitButton.disabled);
            };

            stars.forEach((star) => star.addEventListener('click', () => setRating(star.dataset.rating)));
            setRating(ratingInput.value);
            contentInput.addEventListener('input', updateReadyState);

            imageInput?.addEventListener('change', () => {
                const file = imageInput.files?.[0];
                if (previewUrl) URL.revokeObjectURL(previewUrl);
                if (!file) {
                    preview.classList.add('hidden');
                    return;
                }
                if (!file.type.startsWith('image/') || file.size > 4 * 1024 * 1024) {
                    imageInput.value = '';
                    errorBox.textContent = file.size > 4 * 1024 * 1024 ? 'Ảnh không được vượt quá 4 MB.' : 'Tệp tải lên phải là ảnh hợp lệ.';
                    errorBox.classList.remove('hidden');
                    preview.classList.add('hidden');
                    return;
                }
                errorBox.textContent = '';
                errorBox.classList.add('hidden');
                previewUrl = URL.createObjectURL(file);
                previewImage.src = previewUrl;
                preview.classList.remove('hidden');
                preview.classList.add('flex');
            });
            form.querySelector('[data-remove-image]')?.addEventListener('click', () => {
                imageInput.value = '';
                if (previewUrl) URL.revokeObjectURL(previewUrl);
                previewUrl = null;
                previewImage.removeAttribute('src');
                preview.classList.add('hidden');
                preview.classList.remove('flex');
            });

            const appendReview = (review) => {
                if (review?.id) {
                    const existingArticle = [...document.querySelectorAll('#published-review-list [data-review-id]')]
                        .find((article) => article.dataset.reviewId === String(review.id));
                    existingArticle?.remove();
                }
                const article = document.createElement('article');
                article.className = 'rounded-xl border border-bloom-line p-4';
                article.dataset.reviewId = String(review.id);
                const head = document.createElement('div');
                head.className = 'flex flex-wrap justify-between gap-2';
                const name = document.createElement('strong');
                name.textContent = review.reviewer_name;
                const starsText = document.createElement('span');
                starsText.className = 'text-amber-600';
                starsText.textContent = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
                head.append(name, starsText);
                const date = document.createElement('p');
                date.className = 'mt-1 text-xs text-bloom-muted';
                date.textContent = (review.published_at || '') + ' · Đã mua hàng';
                const content = document.createElement('p');
                content.className = 'mt-2 whitespace-pre-line text-sm leading-6 text-bloom-muted';
                content.textContent = review.content;
                article.append(head, date, content);
                if (review.image_url) {
                    const image = document.createElement('img');
                    image.className = 'mt-3 max-h-56 rounded-lg';
                    image.src = review.image_url;
                    image.alt = 'Ảnh khách gửi kèm đánh giá';
                    image.loading = 'lazy';
                    article.append(image);
                }
                document.querySelector('#published-review-list')?.prepend(article);
            };

            form.addEventListener('submit', async (event) => {
                if (!window.fetch) return;
                event.preventDefault();
                errorBox.textContent = '';
                errorBox.classList.add('hidden');
                statusBox.textContent = '';
                if (!ratingInput.value) {
                    errorBox.textContent = 'Vui lòng chọn số sao.';
                    errorBox.classList.remove('hidden');
                    return;
                }
                if (!contentInput.reportValidity()) return;
                submitButton.disabled = true;
                submitButton.textContent = 'Đang gửi…';
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const result = await response.json();
                    if (!response.ok) {
                        const firstError = result.errors ? Object.values(result.errors).flat()[0] : result.message;
                        throw new Error(firstError || 'Không gửi được đánh giá. Vui lòng thử lại.');
                    }
                    if (result.review) {
                        appendReview(result.review);
                    } else if (existingReviewId) {
                        const existingArticle = [...document.querySelectorAll('#published-review-list [data-review-id]')]
                            .find((article) => article.dataset.reviewId === existingReviewId);
                        existingArticle?.remove();
                    }
                    const summary = document.querySelector('[data-review-summary]');
                    if (summary && result.summary) summary.textContent = result.summary.count ? result.summary.average + ' / 5 từ ' + result.summary.count + ' đánh giá' : 'Chưa có đánh giá.';
                    const topSummary = document.querySelector('[data-product-rating-summary]');
                    if (topSummary && result.summary?.count) topSummary.textContent = '★ ' + result.summary.average + ' / 5 · ' + result.summary.count + ' đánh giá';
                    statusBox.textContent = result.message;
                    const notice = document.createElement('p');
                    notice.className = 'mb-4 rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-800';
                    notice.setAttribute('role', 'status');
                    notice.textContent = result.message;
                    form.before(notice);
                    form.hidden = true;
                } catch (error) {
                    errorBox.textContent = error.message || 'Không gửi được đánh giá. Vui lòng thử lại.';
                    errorBox.classList.remove('hidden');
                } finally {
                    submitButton.disabled = false;
                    submitButton.textContent = idleSubmitLabel;
                }
            });
        });
    </script>

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

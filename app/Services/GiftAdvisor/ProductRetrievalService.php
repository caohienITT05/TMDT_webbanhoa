<?php

namespace App\Services\GiftAdvisor;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Finds and grounds candidate products. No model response is ever rendered as
 * product facts: the Product model remains the sole authority for those facts.
 */
class ProductRetrievalService
{
    /** @return Collection<int, Product> */
    public function candidates(array $context, array $recentProductIds = []): Collection
    {
        $ranked = Product::query()
            ->where('is_active', true)
            ->with('category')
            ->get()
            ->filter(fn (Product $product): bool => in_array(($context['product_focus'] ?? null), [null, 'any'], true) || $this->kind($product) === $context['product_focus'])
            ->filter(fn (Product $product): bool => $this->matchesTargetEntity($product, $context))
            ->filter(fn (Product $product): bool => empty($context['target_entity']) || (int) $product->stock > 0)
            ->filter(fn (Product $product): bool => ! $this->isExcluded($product, $context))
            ->filter(fn (Product $product): bool => $this->matchesRequestedColors($product, $context))
            ->filter(fn (Product $product): bool => $this->matchesRequestedStyle($product, $context))
            ->filter(fn (Product $product): bool => $this->withinBudget($product, $context))
            ->map(fn (Product $product): array => ['product' => $product, 'score' => $this->score($product, $context, $recentProductIds)])
            ->sort(fn (array $left, array $right): int => $right['score'] <=> $left['score'] ?: $this->currentPrice($left['product']) <=> $this->currentPrice($right['product']))
            ->values();

        return $ranked
            ->reject(fn (array $entry): bool => in_array($entry['product']->id, $recentProductIds, true))
            ->pluck('product')->take(8)->values();
    }

    /** @param array<int, int> $ids @return Collection<int, Product> */
    public function productsByIds(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter($ids, fn (mixed $id): bool => is_int($id) || ctype_digit((string) $id))));
        if ($ids === []) {
            return collect();
        }

        $products = Product::query()
            ->where('is_active', true)
            ->with('category')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int|string $id): ?Product => $products->get((int) $id))
            ->filter()
            ->values();
    }

    /** @return array<string, mixed> */
    public function card(Product $product, array $context, ?string $reason = null): array
    {
        $sale = $this->salePrice($product);
        $price = $sale ?? (float) $product->price;

        $description = $this->customerDescription($product);

        return [
            'kind' => 'product',
            'recommendation_type' => ['flower' => 'FLOWER', 'gift' => 'GIFT', 'set' => 'COMBO'][$this->kind($product)] ?? 'PRODUCT',
            'product_id' => $product->id,
            'name' => $product->name,
            'description' => Str::limit($description, 220, ''),
            'short_description' => Str::limit($description, 180, ''),
            'why_recommended' => $reason ?? $this->reason($product, $context),
            'details' => array_filter([
                'type' => $this->typeLabel($this->kind($product)),
                'components' => $description !== '' ? Str::limit($description, 180, '') : null,
                'color' => $this->knownColor($product),
                'occasion' => $this->occasionText($context),
                'recipient' => $this->knownRecipient($product, $context),
                'full_description' => $description !== '' ? Str::limit($description, 1000, '') : null,
            ], fn (mixed $value): bool => $value !== null && $value !== ''),
            'price' => $price,
            'regular_price' => (float) $product->price,
            'sale_price' => $sale,
            'stock' => (int) $product->stock,
            'category' => $product->category?->name,
            'image_url' => $product->image_url,
            'availability' => $product->stock > 0 ? 'Có sẵn tại BloomGift' : 'Tạm hết hàng',
            'available' => $product->stock > 0,
            'budget_fit' => $this->budgetFit($price, $context),
            'recommendation_badge' => $product->stock > 0 && $this->score($product, $context, []) >= 35 ? 'BloomGift AI đề xuất nhất' : null,
            'product_url' => route('product.detail', $product),
            'cart_url' => route('cart.add', $product),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function promptCandidates(Collection $products): array
    {
        return $products->map(fn (Product $product): array => [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category?->name,
            'kind' => $this->kind($product),
            'price' => $this->currentPrice($product),
            'in_stock' => $product->stock > 0,
            'description' => Str::limit($this->sanitizeProductPromptData((string) $product->description), 300, ''),
        ])->all();
    }

    private function score(Product $product, array $context, array $recentProductIds): int
    {
        $text = $this->text($product);
        // Availability matters, but should never outweigh a strong intent match.
        $score = $product->stock > 0 ? 3 : 0;
        $kind = $this->kind($product);
        if (($context['product_focus'] ?? null) === $kind) {
            $score += 18;
        }
        if (($context['product_focus'] ?? null) === 'set' && $kind === 'set') {
            $score += 18;
        }
        if (! empty($context['target_entity'])) {
            $score += 1000;
        }

        foreach ($this->termsFor($context) as $term) {
            if (Str::contains($text, $term)) {
                $score += 12;
            }
        }

        if (in_array($product->id, $recentProductIds, true)) {
            $score -= 16;
        }

        $price = $this->currentPrice($product);
        if (($context['budget_max'] ?? null) !== null && $price <= (int) $context['budget_max']) {
            $score += 10;
        }
        if (($context['budget_min'] ?? null) !== null && $price >= (int) $context['budget_min']) {
            $score += 5;
        }

        return $score;
    }

    private function withinBudget(Product $product, array $context): bool
    {
        if (($context['budget_unlimited'] ?? false) === true) {
            return true;
        }

        $price = $this->currentPrice($product);
        if (($context['budget_max'] ?? null) !== null && $price > (int) $context['budget_max']) {
            return false;
        }
        if (($context['budget_min'] ?? null) !== null && $price < (int) $context['budget_min']) {
            return false;
        }

        return true;
    }

    private function isExcluded(Product $product, array $context): bool
    {
        $text = $this->text($product);
        foreach ((array) ($context['exclude'] ?? []) as $exclusion) {
            if ($exclusion === 'red' && $this->containsAny($text, ['đỏ', 'red'])) {
                return true;
            }
            if ($exclusion === 'flowers' && $this->kind($product) === 'flower') {
                return true;
            }
            if ($exclusion === 'round_bouquet' && $this->containsAny($text, ['bó tròn', 'round bouquet'])) {
                return true;
            }
            if (isset(ConversationContext::ENTITY_TERMS[$exclusion]) && ConversationContext::entityMatches($exclusion, $text)) {
                return true;
            }
        }

        return false;
    }

    private function matchesRequestedColors(Product $product, array $context): bool
    {
        $colors = (array) ($context['colors'] ?? []);
        if ($colors === []) {
            return true;
        }

        $terms = [
            'red' => ['đỏ', 'red', 'burgundy', 'rượu vang'],
            'pink' => ['hồng', 'pink', 'rose'],
            'white' => ['trắng', 'kem', 'ivory', 'white'],
            'yellow' => ['vàng', 'yellow'],
            'purple' => ['tím', 'lavender', 'purple'],
            'blue' => ['xanh', 'blue'],
            'pastel' => ['pastel', 'phấn'],
            'coral' => ['coral', 'san hô'],
        ];
        $text = $this->text($product);

        foreach ($colors as $color) {
            foreach ($terms[$color] ?? [$color] as $term) {
                if (Str::contains($text, Str::lower($term))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function matchesRequestedStyle(Product $product, array $context): bool
    {
        $styles = (array) ($context['styles'] ?? []);
        if ($styles === []) {
            return true;
        }

        $terms = [
            'premium' => ['sang trọng', 'cao cấp', 'premium', 'luxury'],
            'romantic' => ['lãng mạn', 'romantic'],
            'gentle' => ['nhẹ nhàng', 'dịu dàng', 'tinh khôi', 'thanh lịch'],
            'cute' => ['dễ thương', 'cute', 'kẹo ngọt'],
            'minimal' => ['tối giản', 'minimal'],
            'elegant' => ['thanh lịch', 'tinh tế', 'elegant'],
            'vibrant' => ['rực rỡ', 'nổi bật', 'vibrant'],
            'korean' => ['hàn quốc', 'korean'],
            'youthful' => ['trẻ trung', 'youthful'],
        ];
        $text = $this->text($product);

        foreach ($styles as $style) {
            foreach ($terms[$style] ?? [$style] as $term) {
                if (Str::contains($text, Str::lower($term))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function customerDescription(Product $product): string
    {
        $description = $this->sanitizeProductPromptData((string) $product->description);
        $name = trim((string) $product->name);
        if ($name !== '' && Str::startsWith(Str::lower($description), Str::lower($name))) {
            $description = trim(ltrim(mb_substr($description, mb_strlen($name)), " \t\n\r\0\x0B:–—-"));
        }
        $generic = [
            'hoa đẹp cần người chăm',
            'thiết kế hoa và quà tặng dành cho những lời chúc chân thành',
            'sản phẩm chất lượng cao',
        ];

        foreach ($generic as $phrase) {
            if ($description === '' || Str::contains(Str::lower($description), $phrase)) {
                return '';
            }
        }

        return $description;
    }

    /** Strip internal notes at sentence granularity before prompting or display. */
    private function sanitizeProductPromptData(string $description): string
    {
        $description = trim(preg_replace('/\s+/u', ' ', strip_tags($description)) ?? '');
        if ($description === '') {
            return '';
        }

        $sentences = preg_split('/(?<=[.!?])\s+/u', $description) ?: [$description];
        $sentences = array_filter($sentences, fn (string $sentence): bool => ! preg_match(
            '/\b(?:internal|demo|seed|draft|admin\s+note|test|placeholder|development\s+only|ai\s+generated\s+image)\b|ảnh\s+minh\s+họa\s+được\s+tạo\s+bằng\s+ai|cần\s+đối\s+chiếu\s+mẫu\s+thực\s+tế\s+trước\s+khi\s+mở\s+bán/iu',
            $sentence
        ));

        return trim(implode(' ', $sentences));
    }

    /** @return array<int, string> */
    private function termsFor(array $context): array
    {
        $termMap = [
            'rose' => ['hồng', 'rose'], 'tulip' => ['tulip'], 'peony' => ['mẫu đơn', 'peony'],
            'lily' => ['lily', 'ly'], 'hydrangea' => ['cẩm tú cầu', 'hydrangea'],
            'sunflower' => ['hướng dương'], 'chocolate' => ['chocolate', 'socola'],
            'teddy' => ['gấu bông'], 'candle' => ['nến'], 'card' => ['thiệp'], 'tea' => ['trà'],
            'red' => ['đỏ'], 'pink' => ['hồng', 'pastel'], 'white' => ['trắng', 'kem'],
            'yellow' => ['vàng'], 'purple' => ['tím', 'lavender'], 'blue' => ['xanh'],
            'pastel' => ['pastel'], 'coral' => ['coral'], 'premium' => ['sang trọng', 'cao cấp', 'premium'],
            'romantic' => ['lãng mạn'], 'gentle' => ['nhẹ nhàng'], 'cute' => ['dễ thương'],
            'minimal' => ['tối giản'], 'elegant' => ['thanh lịch'], 'vibrant' => ['rực rỡ'],
            'korean' => ['hàn quốc'], 'youthful' => ['trẻ trung'],
        ];
        $terms = [];
        foreach ([...((array) ($context['flower_preferences'] ?? [])), ...((array) ($context['gift_preferences'] ?? [])), ...((array) ($context['colors'] ?? [])), ...((array) ($context['styles'] ?? []))] as $value) {
            $terms = [...$terms, ...($termMap[$value] ?? [$value])];
        }
        $terms = [...$terms, ...(match ($context['occasion'] ?? null) {
            'birthday' => ['sinh nhật', 'birthday'],
            'anniversary', 'valentine' => ['kỷ niệm', 'valentine'],
            'international_womens_day' => ['8/3', '8 tháng 3', 'quốc tế phụ nữ', 'ngày phụ nữ'],
            'vietnamese_womens_day' => ['20/10', '20 tháng 10', 'phụ nữ việt nam', 'ngày phụ nữ'],
            'congratulations' => ['chúc mừng'],
            'get_well' => ['thăm hỏi', 'thăm bệnh'],
            'graduation' => ['tốt nghiệp', 'chúc mừng'],
            'opening' => ['khai trương', 'chúc mừng'],
            'thanks' => ['cảm ơn'],
            default => [],
        })];
        $terms = [...$terms, ...(match ($context['recipient'] ?? null) {
            'girlfriend', 'boyfriend', 'lover' => ['người yêu', 'lãng mạn', 'romance'],
            'mother' => ['mẹ', 'gia đình'],
            'friend' => ['bạn', 'vui tươi'],
            'colleague' => ['đồng nghiệp'],
            default => [],
        })];

        return array_values(array_unique(array_filter($terms)));
    }

    private function kind(Product $product): string
    {
        $category = Str::lower((string) $product->category?->name);
        $text = $this->text($product);
        if (Str::contains($category, 'set') || $this->containsAny($text, ['combo', 'set quà', 'gift set', 'gift box', 'hộp quà'])) {
            return 'set';
        }

        return Str::contains($category, ['quà', 'gấu', 'gift']) || $this->containsAny($text, ['nến', 'chocolate', 'socola', 'gấu bông', 'teddy', 'plush', 'thiệp', 'trà', 'album', 'khung ảnh', 'móc khóa'])
            ? 'gift'
            : 'flower';
    }

    private function currentPrice(Product $product): float
    {
        return $this->salePrice($product) ?? (float) $product->price;
    }

    private function salePrice(Product $product): ?float
    {
        $sale = (float) ($product->sale_price ?? 0);

        return $sale > 0 && $sale < (float) $product->price ? $sale : null;
    }

    private function text(Product $product): string
    {
        return Str::lower(implode(' ', [$product->name, $this->sanitizeProductPromptData((string) $product->description), $product->category?->name, $product->season]));
    }

    private function matchesTargetEntity(Product $product, array $context): bool
    {
        $entity = $context['target_entity'] ?? null;
        if (! is_string($entity) || $entity === '') {
            return true;
        }

        return ConversationContext::entityMatches($entity, implode(' ', [
            (string) $product->name,
            (string) $product->category?->name,
            $this->sanitizeProductPromptData((string) $product->description),
        ]));
    }

    /** @param array<int, string> $needles */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (Str::contains($haystack, Str::lower($needle))) {
                return true;
            }
        }

        return false;
    }

    private function reason(Product $product, array $context): string
    {
        $text = $this->text($product);
        $description = $this->customerDescription($product);
        $kind = $this->kind($product);
        $entity = ConversationContext::entityLabel($context['target_entity'] ?? null);
        if ($entity !== null) {
            $base = 'Mẫu “' . trim(strip_tags((string) $product->name)) . '” là ' . $entity . ' đang có sẵn tại BloomGift.';

            return $base . $this->contextReason($product, $context);
        }

        $fact = match (true) {
            $this->containsAny($text, ['chocolate', 'socola', 'cacao']) => 'Chocolate là món quà nhỏ gọn, dễ chia sẻ trong dịp gặp gỡ.',
            $this->containsAny($text, ['nến thơm', 'candle']) => 'Nến thơm là món quà có thể tiếp tục sử dụng sau khi nhận.',
            $this->containsAny($text, ['trà', 'tea']) => 'Trà phù hợp nếu người nhận thích một món quà để thưởng thức.',
            $this->containsAny($text, ['album ảnh', 'photo album', 'album']) => 'Album ảnh giúp lưu giữ những kỷ niệm qua hình ảnh.',
            $this->containsAny($text, ['khung ảnh', 'photo frame', 'picture frame']) => 'Khung ảnh có thể dùng để trưng bày một khoảnh khắc đáng nhớ.',
            $this->containsAny($text, ['gấu bông', 'teddy', 'plush']) => 'Gấu bông đúng nhóm quà mềm mại, dễ giữ làm kỷ niệm.',
            $this->containsAny($text, ['thiệp', 'greeting card']) => $description !== '' ? Str::limit($description, 190, '') : $this->cardReason($product, $context),
            default => $description !== '' ? Str::limit($description, 190, '') : $this->catalogReason($product, $context, $kind),
        };

        return $fact . $this->contextReason($product, $context);
    }

    private function cardReason(Product $product, array $context): string
    {
        $occasion = match ($context['occasion'] ?? null) {
            'birthday' => 'sinh nhật', 'anniversary' => 'kỷ niệm', 'graduation' => 'tốt nghiệp',
            'opening' => 'khai trương', 'thanks' => 'lời cảm ơn', default => 'dịp đặc biệt',
        };
        $budget = ($context['budget_unlimited'] ?? false) ? null : ($context['budget_max'] ?? null);
        $priceFits = $budget !== null && $this->currentPrice($product) <= (float) $budget;
        $budgetNote = $priceFits ? ' Giá hiện tại cũng nằm trong ngân sách bạn chọn.' : '';

        return 'Thiệp “' . trim(strip_tags((string) $product->name)) . '” là lựa chọn gọn nhẹ để gửi lời chúc dịp ' . $occasion . '.' . $budgetNote;
    }

    private function catalogReason(Product $product, array $context, string $kind): string
    {
        $name = trim(strip_tags((string) $product->name));

        return 'Mẫu “' . $name . '” đang được BloomGift bán ở nhóm ' . ($kind === 'gift' ? 'quà tặng' : ($kind === 'set' ? 'set hoa và quà' : 'hoa')) . '.';
    }

    private function contextReason(Product $product, array $context): string
    {
        $parts = [];
        $color = $this->knownColor($product);
        if ($color !== null) $parts[] = 'tông ' . mb_strtolower($color) . ' khớp yêu cầu màu của bạn';
        $occasion = $this->occasionText($context);
        if ($occasion !== null) $parts[] = 'bạn đang chọn quà cho dịp ' . $occasion;
        $recipient = $this->recipientText($context);
        if ($recipient !== '') $parts[] = 'yêu cầu hiện tại là tặng ' . $recipient;
        if (($context['budget_unlimited'] ?? false) !== true && ($context['budget_max'] ?? null) !== null && $this->currentPrice($product) <= (float) $context['budget_max']) {
            $parts[] = 'giá ' . number_format($this->currentPrice($product), 0, ',', '.') . 'đ nằm trong ngân sách bạn chọn';
        }

        return $parts === [] ? ' Giá và tình trạng hàng được lấy trực tiếp từ danh mục BloomGift.' : ' Lựa chọn này dựa trên việc ' . implode('; ', $parts) . '.';
    }

    private function typeLabel(string $kind): string
    {
        return ['gift' => 'Quà tặng', 'set' => 'Set hoa & quà', 'flower' => 'Hoa'][$kind] ?? 'Sản phẩm';
    }

    private function colorText(array $context): string
    {
        $labels = ['red' => 'đỏ', 'pink' => 'hồng', 'white' => 'trắng / kem', 'yellow' => 'vàng', 'purple' => 'tím / lavender', 'blue' => 'xanh', 'pastel' => 'pastel', 'coral' => 'coral'];
        $colors = array_map(fn (string $color): string => $labels[$color] ?? $color, (array) ($context['colors'] ?? []));

        return $colors === [] ? 'Theo tư vấn của BloomGift' : implode(', ', $colors);
    }

    private function styleText(array $context): string
    {
        $labels = ['premium' => 'sang trọng', 'romantic' => 'lãng mạn', 'gentle' => 'nhẹ nhàng', 'cute' => 'dễ thương', 'minimal' => 'tối giản', 'elegant' => 'thanh lịch', 'vibrant' => 'rực rỡ', 'korean' => 'Hàn Quốc', 'youthful' => 'trẻ trung'];
        $styles = array_map(fn (string $style): string => $labels[$style] ?? $style, (array) ($context['styles'] ?? []));

        return $styles === [] ? 'Tinh tế, dễ tặng' : implode(', ', $styles);
    }

    private function occasionText(array $context): ?string
    {
        return ['birthday' => 'sinh nhật', 'anniversary' => 'kỷ niệm', 'graduation' => 'tốt nghiệp', 'opening' => 'khai trương', 'thanks' => 'cảm ơn', 'apology' => 'xin lỗi', 'valentine' => 'Valentine', 'international_womens_day' => '8/3', 'vietnamese_womens_day' => '20/10', 'congratulations' => 'chúc mừng', 'get_well' => 'thăm hỏi'][$context['occasion'] ?? ''] ?? null;
    }

    private function recipientText(array $context): string
    {
        return ['girlfriend' => 'bạn gái', 'boyfriend' => 'bạn trai', 'lover' => 'người yêu', 'mother' => 'mẹ', 'father' => 'bố', 'friend' => 'bạn bè', 'colleague' => 'đồng nghiệp'][$context['recipient'] ?? ''] ?? '';
    }

    private function meaning(array $context): string
    {
        return match ($context['recipient'] ?? null) {
            'mother', 'father' => 'Thể hiện sự biết ơn và quan tâm.',
            'girlfriend', 'boyfriend', 'lover' => 'Gửi lời yêu thương và sự trân trọng.',
            default => 'Gửi một lời nhắn ý nghĩa tới người nhận.',
        };
    }

    private function knownColor(Product $product): ?string
    {
        $text = Str::lower($product->name . ' ' . strip_tags((string) $product->description));
        $colors = ['đỏ', 'hồng', 'trắng', 'kem', 'vàng', 'tím', 'lavender', 'xanh', 'coral', 'cam'];
        $found = array_values(array_filter($colors, fn (string $color): bool => Str::contains($text, $color)));
        return $found === [] ? null : implode(', ', array_unique($found));
    }

    private function knownRecipient(Product $product, array $context): ?string
    {
        $label = $this->recipientText($context);
        return $label !== '' && Str::contains($this->text($product), Str::lower($label)) ? $label : null;
    }

    /** @return array<int, string> */
    private function occasionTerms(string $occasion): array
    {
        return match ($occasion) {
            'birthday' => ['sinh nhật', 'birthday'],
            'anniversary' => ['kỷ niệm', 'anniversary'],
            'valentine' => ['valentine'],
            'international_womens_day' => ['8/3', '8 tháng 3', 'quốc tế phụ nữ'],
            'vietnamese_womens_day' => ['20/10', '20 tháng 10', 'phụ nữ việt nam'],
            'graduation' => ['tốt nghiệp'],
            'opening' => ['khai trương'],
            'thanks' => ['cảm ơn'],
            'apology' => ['xin lỗi'],
            'congratulations' => ['chúc mừng'],
            'get_well' => ['thăm hỏi', 'thăm bệnh'],
            default => [],
        };
    }

    /** @param array<int, string> $terms */
    private function firstMatchingTerm(string $text, array $terms): string
    {
        foreach ($terms as $term) if (Str::contains($text, Str::lower($term))) return $term;
        return '';
    }

    private function budgetFit(float $price, array $context): string
    {
        if (($context['budget_unlimited'] ?? false) === true || (($context['budget_min'] ?? null) === null && ($context['budget_max'] ?? null) === null)) {
            return 'Giá hiện tại là dữ liệu BloomGift; bạn có thể so sánh với ngân sách của mình.';
        }
        if (($context['budget_max'] ?? null) !== null && $price <= (int) $context['budget_max']) {
            return 'Giá hiện tại nằm trong giới hạn ngân sách bạn đưa ra.';
        }
        if (($context['budget_min'] ?? null) !== null && $price >= (int) $context['budget_min']) {
            return 'Giá hiện tại nằm trong khoảng ngân sách bạn đưa ra.';
        }

        return 'Mẫu gần nhất đang có; giá hiện tại vượt khoảng dự kiến của bạn.';
    }
}

<?php

namespace App\Services\GiftAdvisor;

use Illuminate\Support\Str;

/**
 * Applies the newest customer message to a small, serializable sales context.
 *
 * The context lives in the Laravel session; it is not a customer profile and it
 * is never persisted to the catalogue or order database.
 */
class ConversationContext
{
    /** @var array<string, array<int, string>> */
    public const ENTITY_TERMS = [
        'teddy' => ['gấu bông', 'gấu teddy', 'teddy bear', 'plush toy', 'plush', 'teddy', 'gấu'],
        'rose' => ['hoa hồng', 'bó hồng', 'hồng ecuador', 'hoa roses', 'hoa rose', 'roses', 'rose'],
        'tulip' => ['hoa tulip', 'uất kim hương', 'tulip'],
        'baby_breath' => ['hoa baby', 'baby breath', 'baby'],
        'peony' => ['hoa mẫu đơn', 'mẫu đơn', 'peony'],
        'lily' => ['hoa ly', 'hoa lily', 'lily'],
        'hydrangea' => ['cẩm tú cầu', 'hydrangea'],
        'sunflower' => ['hoa hướng dương', 'hướng dương', 'sunflower'],
        'chocolate' => ['sô cô la', 'socola', 'cacao box', 'chocolate', 'cacao'],
        'candle' => ['scented candle', 'nến thơm', 'nến', 'candle'],
        'card' => ['thiệp', 'greeting card'],
        'tea' => ['trà', 'tea'],
        'album' => ['album ảnh', 'photo album', 'album'],
        'photo_frame' => ['khung ảnh', 'photo frame', 'picture frame'],
        'keychain' => ['móc khóa', 'keychain', 'key ring'],
        'flower_box' => ['flower box', 'hộp hoa'],
        'gift_box' => ['gift box', 'hộp quà'],
        'combo' => ['set hoa và quà', 'set hoa & quà', 'set quà', 'combo hoa', 'combo quà', 'combo', 'sét quà'],
        'bouquet' => ['bó hoa', 'hoa bó', 'bouquet'],
        'flower_basket' => ['giỏ hoa', 'lẵng hoa', 'flower basket'],
    ];

    /** @return array<string, mixed> */
    public function empty(): array
    {
        return [
            'occasion' => null,
            'recipient' => null,
            'relationship' => null,
            'budget_min' => null,
            'budget_max' => null,
            'budget_unlimited' => false,
            'colors' => [],
            'color_flexible' => false,
            'styles' => [],
            'flower_preferences' => [],
            'gift_preferences' => [],
            'must_include' => [],
            'exclude' => [],
            'product_focus' => null,
            'primary_intent' => null,
            'target_entity' => null,
            'request_scope' => null,
            'query_mode' => null,
            'allow_category_expansion' => true,
            'negative_constraints' => [],
            'inventory_only' => false,
            'suppress_suggestions' => false,
            'notes' => [],
            'conversation_summary' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{context: array<string, mixed>, patch: array<string, mixed>, refresh: bool}
     */
    public function apply(array $context, string $message): array
    {
        $context = $this->normalize($context);
        $latest = Str::lower(trim($message));
        $patch = [];

        $this->updateBudget($context, $patch, $latest);
        $this->updateColors($context, $patch, $latest);
        $this->updateRecipient($context, $patch, $latest);
        $this->updateOccasion($context, $patch, $latest);
        $this->updateStyle($context, $patch, $latest);
        $this->updateFocusAndPreferences($context, $patch, $latest);
        $this->updateExclusions($context, $patch, $latest);
        $expansion = $this->isExplicitExpansion($latest);
        if ($expansion) {
            $context['suppress_suggestions'] = false;
            $patch['suppress_suggestions'] = ['action' => 'clear', 'value' => false];
        } elseif ($this->isSuppressionRequest($latest)) {
            $context['suppress_suggestions'] = true;
            $patch['suppress_suggestions'] = ['action' => 'set', 'value' => true];
        }
        if (empty($context['suppress_suggestions']) || $expansion) {
            $this->updateIntentLock($context, $patch, $latest);
        }
        if ($this->containsAny($latest, ['chỉ sản phẩm shop đang có', 'chỉ hàng có sẵn', 'chỉ sản phẩm có sẵn'])) {
            $context['inventory_only'] = true;
            $patch['inventory_only'] = ['action' => 'set', 'value' => true];
        } elseif ($this->containsAny($latest, ['cả mẫu đặt theo yêu cầu', 'thiết kế riêng cũng được'])) {
            $context['inventory_only'] = false;
            $patch['inventory_only'] = ['action' => 'clear', 'value' => false];
        }
        $context['negative_constraints'] = $context['exclude'];
        $context['intent_lock'] = $this->intentLock($context);

        if ($expansion || $this->containsAny($latest, ['loại khác', 'mẫu khác', 'gợi ý khác', 'đổi mẫu'])) {
            $patch['recommendations'] = ['action' => 'refresh'];
        }

        $context['conversation_summary'] = $this->summary($context);

        return [
            'context' => $context,
            'patch' => $patch,
            'refresh' => ($patch['recommendations']['action'] ?? null) === 'refresh',
        ];
    }

    /** @param array<string, mixed> $context */
    private function updateBudget(array &$context, array &$patch, string $message): void
    {
        if ($this->containsAny($message, [
            'không giới hạn giá', 'không giới hạn ngân sách', 'không quan trọng giá',
            'không giới hạn', 'giá nào cũng được', 'bỏ ngân sách', 'không cần quan tâm giá',
        ])) {
            $context['budget_min'] = null;
            $context['budget_max'] = null;
            $context['budget_unlimited'] = true;
            $patch['budget'] = ['action' => 'clear'];

            return;
        }

        $range = $this->budgetRange($message);
        if ($range !== null) {
            $context['budget_min'] = $range['min'];
            $context['budget_max'] = $range['max'];
            $context['budget_unlimited'] = false;
            $patch['budget'] = ['action' => 'set', 'min' => $range['min'], 'max' => $range['max']];

            return;
        }

        $amount = $this->amountFrom($message);
        if ($amount !== null) {
            $context['budget_unlimited'] = false;
            if ($this->containsAny($message, ['dưới', 'tối đa', 'không quá', 'rẻ hơn'])) {
                $context['budget_min'] = null;
                $context['budget_max'] = $amount;
            } elseif ($this->containsAny($message, ['trên', 'ít nhất', 'từ'])) {
                $context['budget_min'] = $amount;
                $context['budget_max'] = null;
            } else {
                $context['budget_min'] = null;
                $context['budget_max'] = $amount;
            }
            $patch['budget'] = ['action' => 'set', 'min' => $context['budget_min'], 'max' => $context['budget_max']];

            return;
        }

        if ($this->containsAny($message, ['rẻ hơn', 'tiết kiệm hơn']) && is_int($context['budget_max'])) {
            $context['budget_max'] = $this->roundBudget((int) floor($context['budget_max'] * 0.8));
            $context['budget_unlimited'] = false;
            $patch['budget'] = ['action' => 'update', 'min' => $context['budget_min'], 'max' => $context['budget_max']];
        }

        if ($this->containsAny($message, ['đắt hơn', 'cao hơn']) && is_int($context['budget_max'])) {
            $context['budget_max'] = $this->roundBudget((int) ceil($context['budget_max'] * 1.3));
            $context['budget_unlimited'] = false;
            $patch['budget'] = ['action' => 'update', 'min' => $context['budget_min'], 'max' => $context['budget_max']];
        }
    }

    /** @param array<string, mixed> $context */
    private function updateColors(array &$context, array &$patch, string $message): void
    {
        if ($this->containsAny($message, ['màu nào cũng được', 'màu sắc đẹp', 'không quan trọng màu', 'bỏ màu'])) {
            $context['colors'] = [];
            $context['color_flexible'] = true;
            $patch['colors'] = ['action' => 'clear'];

            return;
        }

        $colorMessage = preg_replace('/hoa\s+hồng(?!\s*(?:pastel|nhạt|đậm|phấn))/ui', 'hoa', $message) ?? $message;
        $colors = [];
        foreach ([
            'red' => ['đỏ'], 'pink' => ['hồng'], 'white' => ['trắng', 'kem'],
            'yellow' => ['vàng'], 'purple' => ['tím', 'lavender'], 'blue' => ['xanh'],
            'pastel' => ['pastel'], 'coral' => ['coral'],
        ] as $color => $terms) {
            if ($this->containsAny($colorMessage, $terms) && ! $this->containsAny($colorMessage, ['không muốn ' . $terms[0], 'không thích ' . $terms[0]])) {
                $colors[] = $color;
            }
        }

        if ($colors !== []) {
            $context['colors'] = array_values(array_unique($colors));
            $context['color_flexible'] = false;
            $patch['colors'] = ['action' => 'set', 'values' => $context['colors']];
        }
    }

    /** @param array<string, mixed> $context */
    private function updateRecipient(array &$context, array &$patch, string $message): void
    {
        $recipients = [
            'girlfriend' => ['bạn gái', 'người yêu nữ'],
            'boyfriend' => ['bạn trai', 'người yêu nam'],
            'lover' => ['người yêu'],
            'mother' => ['tặng mẹ', 'cho mẹ', 'má'],
            'father' => ['tặng bố', 'cho bố', 'ba'],
            'friend' => ['bạn thân', 'bạn bè'],
            'colleague' => ['đồng nghiệp'],
        ];

        foreach ($recipients as $recipient => $terms) {
            if ($this->containsAny($message, $terms)) {
                $context['recipient'] = $recipient;
                $context['relationship'] = in_array($recipient, ['girlfriend', 'boyfriend', 'lover'], true) ? 'romantic' : null;
                $patch['recipient'] = ['action' => 'set', 'value' => $recipient];

                return;
            }
        }
    }

    /** @param array<string, mixed> $context */
    private function updateOccasion(array &$context, array &$patch, string $message): void
    {
        foreach ([
            'international_womens_day' => ['8/3', '8-3', '8 tháng 3', 'quốc tế phụ nữ', 'ngày phụ nữ'],
            'vietnamese_womens_day' => ['20/10', '20-10', '20 tháng 10', 'phụ nữ việt nam'],
            'birthday' => ['sinh nhật', 'birthday'],
            'anniversary' => ['kỷ niệm', 'anniversary'],
            'graduation' => ['tốt nghiệp'],
            'opening' => ['khai trương'],
            'thanks' => ['cảm ơn'],
            'apology' => ['xin lỗi'],
            'valentine' => ['valentine'],
            'congratulations' => ['chúc mừng'],
            'get_well' => ['thăm hỏi', 'thăm bệnh'],
        ] as $occasion => $terms) {
            if ($this->containsAny($message, $terms)) {
                $context['occasion'] = $occasion;
                $patch['occasion'] = ['action' => 'set', 'value' => $occasion];

                return;
            }
        }
    }

    /** @param array<string, mixed> $context */
    private function updateStyle(array &$context, array &$patch, string $message): void
    {
        $styles = [];
        foreach ([
            'premium' => ['sang hơn', 'sang trọng', 'cao cấp', 'premium', 'luxury'],
            'romantic' => ['lãng mạn'], 'gentle' => ['nhẹ nhàng'], 'cute' => ['dễ thương'],
            'minimal' => ['tối giản'], 'elegant' => ['thanh lịch'], 'vibrant' => ['rực rỡ'],
            'korean' => ['hàn quốc'], 'youthful' => ['trẻ trung'],
        ] as $style => $terms) {
            if ($this->containsAny($message, $terms)) {
                $styles[] = $style;
            }
        }

        if ($styles !== []) {
            $context['styles'] = array_values(array_unique($styles));
            $patch['styles'] = ['action' => 'set', 'values' => $context['styles']];
        }
    }

    /** @param array<string, mixed> $context */
    private function updateFocusAndPreferences(array &$context, array &$patch, string $message): void
    {
        $excludesAllFlowers = $this->containsAny($message, ['không cần hoa', 'không muốn hoa', 'đừng đưa hoa', 'đừng gợi ý hoa'])
            && ! $this->containsAny($message, ['hoa hồng', 'hoa tulip', 'hoa baby', 'mẫu đơn', 'hoa ly', 'cẩm tú cầu', 'hướng dương']);
        if ($excludesAllFlowers) {
            $context['product_focus'] = 'gift';
            $context['exclude'] = $this->merge($context['exclude'], ['flowers']);
            $patch['product_focus'] = ['action' => 'set', 'value' => 'gift'];
        } elseif ($this->containsAny($message, ['set hoa', 'sét hoa', 'combo hoa', 'flower box', 'gift box', 'hộp quà'])) {
            $context['product_focus'] = 'set';
            $patch['product_focus'] = ['action' => 'set', 'value' => 'set'];
        } elseif ($this->containsAny($message, ['quà', 'chocolate', 'socola', 'gấu bông', 'nến', 'thiệp'])
            && ! $this->containsAny($message, ['không cần', 'không muốn', 'không thích', 'đừng đưa', 'đừng gợi ý', 'đừng chọn', 'tránh'])) {
            $addsGiftToFlower = ($context['product_focus'] ?? null) === 'flower' && $this->containsAny($message, ['thêm', 'kèm', 'cùng']);
            $context['product_focus'] = $addsGiftToFlower ? 'set' : 'gift';
            $patch['product_focus'] = ['action' => 'set', 'value' => $context['product_focus']];
        } elseif ($this->containsAny($message, ['hoa', 'bó', 'giỏ', 'lẵng', 'tulip', 'mẫu đơn', 'lily', 'cẩm tú cầu', 'hồng'])) {
            $context['product_focus'] = 'flower';
            $patch['product_focus'] = ['action' => 'set', 'value' => 'flower'];
        }

        $flowers = $this->findTerms($message, ['rose' => ['hoa hồng', 'hồng'], 'tulip' => self::ENTITY_TERMS['tulip'], 'baby_breath' => self::ENTITY_TERMS['baby_breath'], 'peony' => self::ENTITY_TERMS['peony'], 'lily' => self::ENTITY_TERMS['lily'], 'hydrangea' => self::ENTITY_TERMS['hydrangea'], 'sunflower' => self::ENTITY_TERMS['sunflower']]);
        $flowers = array_values(array_filter($flowers, fn (string $flower): bool => ! $this->entityMentionIsNegated($message, $flower === 'rose' ? ['hoa hồng', 'hồng'] : (self::ENTITY_TERMS[$flower] ?? []))));
        if ($flowers !== []) {
            $context['flower_preferences'] = $this->merge($context['flower_preferences'], $flowers);
            $patch['flower_preferences'] = ['action' => 'update', 'values' => $context['flower_preferences']];
        }

        $gifts = $this->findTerms($message, ['chocolate' => self::ENTITY_TERMS['chocolate'], 'teddy' => self::ENTITY_TERMS['teddy'], 'candle' => self::ENTITY_TERMS['candle'], 'card' => self::ENTITY_TERMS['card'], 'tea' => self::ENTITY_TERMS['tea'], 'album' => self::ENTITY_TERMS['album'], 'photo_frame' => self::ENTITY_TERMS['photo_frame'], 'keychain' => self::ENTITY_TERMS['keychain']]);
        $gifts = array_values(array_filter($gifts, function (string $gift) use ($message): bool {
            $terms = self::ENTITY_TERMS[$gift] ?? [];

            return ! $this->entityMentionIsNegated($message, $terms);
        }));
        if ($gifts !== []) {
            $context['gift_preferences'] = $this->merge($context['gift_preferences'], $gifts);
            if (! $this->containsAny($message, ['không cần', 'không muốn', 'không thích'])) {
                $context['must_include'] = $this->merge($context['must_include'], $gifts);
            }
            $patch['gift_preferences'] = ['action' => 'update', 'values' => $context['gift_preferences']];
        }
    }

    /** Apply a deterministic, latest-message intent lock before retrieval. */
    private function updateIntentLock(array &$context, array &$patch, string $message): void
    {
        $expansion = $this->containsAny($message, [
            'quà khác đi', 'món quà khác', 'loại quà khác', 'xem quà khác',
            'chuyển sang quà khác', 'vậy quà khác', 'cho xem loại quà khác',
            'loại khác đi', 'món khác đi', 'gợi ý loại khác',
        ]);

        if ($expansion) {
            $context['target_entity'] = null;
            $context['primary_intent'] = 'recommend_products';
            $context['request_scope'] = 'open_recommendation';
            $context['query_mode'] = 'recommendations';
            $context['allow_category_expansion'] = true;
            if ($this->containsAny($message, ['quà'])) {
                $context['product_focus'] = 'gift';
                $context['gift_preferences'] = [];
                $context['must_include'] = [];
            }
            $patch['intent_lock'] = $this->intentLock($context);

            return;
        }

        $entity = $this->entityFor($message);
        if ($entity !== null) {
            $context['target_entity'] = $entity;
            $context['product_focus'] = $this->entityFocus($entity);
            $context['primary_intent'] = $this->containsAny($message, ['shop', 'có', 'còn', 'xem', 'tìm', 'loại', 'mẫu'])
                ? 'search_shop_product'
                : 'recommend_entity';
            $context['request_scope'] = 'same_entity_only';
            $context['query_mode'] = $this->containsAny($message, ['khác', 'nữa', 'thêm', 'còn']) ? 'alternatives' : 'exact_entity';
            $context['allow_category_expansion'] = false;

            if ($this->entityFocus($entity) === 'flower') {
                $context['flower_preferences'] = [$entity];
                $context['gift_preferences'] = [];
                $context['must_include'] = [];
            } elseif ($this->entityFocus($entity) === 'gift') {
                $context['gift_preferences'] = [$entity];
                $context['flower_preferences'] = [];
                $context['must_include'] = [$entity];
            }

            $patch['product_focus'] = ['action' => 'set', 'value' => $context['product_focus']];
            $patch['target_entity'] = ['action' => 'set', 'value' => $entity];
            $patch['intent_lock'] = $this->intentLock($context);

            return;
        }

        $mentionsFlower = $this->containsAny($message, ['hoa', 'bó', 'giỏ', 'lẵng']);
        $mentionsGift = $this->containsAny($message, ['quà', 'chocolate', 'socola', 'gấu', 'nến', 'thiệp', 'trà', 'album', 'khung ảnh', 'móc khóa']);
        if (! $mentionsFlower && ! $mentionsGift) {
            return;
        }

        $isOpen = $this->containsAny($message, ['gợi ý', 'nên mua gì', 'mua gì', 'tư vấn', 'cho tôi xem', 'có gì hay']);
        $context['target_entity'] = null;
        $context['primary_intent'] = $isOpen ? 'recommend_products' : 'search_shop_product';
        $context['request_scope'] = $isOpen ? 'open_recommendation' : 'category_only';
        $context['query_mode'] = $isOpen ? 'recommendations' : 'category_search';
        $context['allow_category_expansion'] = $isOpen;
        if ($mentionsFlower && ! $mentionsGift) {
            $context['flower_preferences'] = [];
            $context['gift_preferences'] = [];
            $context['must_include'] = [];
        } elseif ($mentionsGift && ! $mentionsFlower) {
            $context['gift_preferences'] = [];
            $context['flower_preferences'] = [];
            $context['must_include'] = [];
        }
        $patch['intent_lock'] = $this->intentLock($context);
    }

    /** @return array<string, mixed> */
    private function intentLock(array $context): array
    {
        return [
            'primary_intent' => $context['primary_intent'],
            'target_entity' => $context['target_entity'],
            'request_scope' => $context['request_scope'],
            'negative_constraints' => $context['exclude'],
            'current_context' => [
                'occasion' => $context['occasion'],
                'recipient' => $context['recipient'],
                'budget_max' => $context['budget_unlimited'] ? null : $context['budget_max'],
                'colors' => $context['colors'],
            ],
            'query_mode' => $context['query_mode'],
            'allow_category_expansion' => $context['allow_category_expansion'],
            'inventory_only' => $context['inventory_only'] ?? false,
            'suppress_suggestions' => $context['suppress_suggestions'] ?? false,
        ];
    }

    private function isSuppressionRequest(string $message): bool
    {
        return $this->containsAny($message, [
            'nếu hết thì thôi', 'đừng gợi ý quà khác', 'đừng gợi ý thêm', 'không cần loại khác',
            'không cần thêm', 'chỉ hỏi vậy thôi', 'dừng gợi ý', 'đừng đưa lựa chọn khác', 'thôi',
        ]);
    }

    private function isExplicitExpansion(string $message): bool
    {
        return $this->containsAny($message, [
            'quà khác đi', 'món quà khác', 'loại quà khác', 'xem quà khác',
            'chuyển sang quà khác', 'vậy quà khác', 'cho xem loại quà khác',
            'loại khác đi', 'món khác đi', 'gợi ý loại khác', 'cho tôi món khác', 'cho tôi quà khác',
        ]);
    }

    private function entityFor(string $message): ?string
    {
        $compositionEdit = $this->containsAny($message, ['thêm ', 'kèm ', 'cùng ', 'phối ', 'với ']);
        $entities = self::ENTITY_TERMS;
        uasort($entities, fn (array $left, array $right): int => max(array_map('mb_strlen', $right)) <=> max(array_map('mb_strlen', $left)));

        foreach ($entities as $entity => $terms) {
            if ($this->containsAny($message, $terms)) {
                if ($compositionEdit && $this->entityFocus($entity) === 'gift') {
                    return null;
                }
                if ($this->entityMentionIsNegated($message, $terms)) {
                    continue;
                }

                return $entity;
            }
        }

        return null;
    }

    /** Keep exclusions from accidentally becoming a positive exact-entity request. */
    private function entityMentionIsNegated(string $message, array $terms): bool
    {
        foreach ($terms as $term) {
            foreach (['không ', 'không có ', 'không cần ', 'không muốn ', 'không thích ', 'bỏ ', 'đừng ', 'đừng có ', 'đừng đưa ', 'đừng gợi ý ', 'đừng chọn ', 'tránh '] as $prefix) {
                if (Str::contains($message, $prefix . $term)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function entityFocus(string $entity): string
    {
        return match ($entity) {
            'rose', 'tulip', 'baby_breath', 'peony', 'lily', 'hydrangea', 'sunflower', 'flower_box', 'bouquet', 'flower_basket' => 'flower',
            'combo' => 'set',
            default => 'gift',
        };
    }

    /** Match aliases once, using Unicode-insensitive text. */
    public static function entityMatches(string $entity, string $text): bool
    {
        $text = Str::lower($text);
        foreach (self::ENTITY_TERMS[$entity] ?? [] as $term) {
            if (Str::contains($text, Str::lower($term))) {
                return true;
            }
        }

        return false;
    }

    public static function entityLabel(?string $entity): ?string
    {
        return [
            'teddy' => 'gấu bông', 'rose' => 'hoa hồng', 'tulip' => 'hoa tulip',
            'baby_breath' => 'hoa baby', 'peony' => 'hoa mẫu đơn', 'lily' => 'hoa ly',
            'hydrangea' => 'cẩm tú cầu', 'sunflower' => 'hoa hướng dương', 'chocolate' => 'chocolate',
            'candle' => 'nến thơm', 'card' => 'thiệp', 'tea' => 'trà', 'album' => 'album ảnh',
            'photo_frame' => 'khung ảnh', 'keychain' => 'móc khóa', 'flower_box' => 'flower box',
            'gift_box' => 'hộp quà', 'combo' => 'set quà', 'bouquet' => 'bó hoa', 'flower_basket' => 'giỏ hoa',
        ][$entity ?? ''] ?? null;
    }

    /** @param array<string, mixed> $context */
    private function updateExclusions(array &$context, array &$patch, string $message): void
    {
        $clear = [];
        foreach (self::ENTITY_TERMS as $entity => $terms) {
            foreach ($terms as $term) {
                if ($this->containsAny($message, ['cho lại ' . $term, 'bỏ điều kiện không ' . $term, 'bỏ loại trừ ' . $term, $term . ' cũng được'])) {
                    $clear[] = $entity;
                    break;
                }
            }
        }
        if ($this->containsAny($message, ['hoa hồng cũng được', 'bỏ loại trừ hoa hồng', 'không cần loại trừ hoa hồng', 'bỏ điều kiện không hoa hồng', 'cho lại hoa hồng'])) $clear[] = 'rose';
        if ($this->containsAny($message, ['màu đỏ cũng được', 'bỏ loại trừ màu đỏ'])) $clear[] = 'red';
        if ($clear !== []) {
            $context['exclude'] = array_values(array_diff($context['exclude'], $clear));
            $patch['exclude'] = ['action' => 'clear', 'values' => $context['exclude']];
        }

        $excluded = [];
        foreach (self::ENTITY_TERMS as $entity => $terms) {
            if ($this->containsAny($message, $terms) && $this->entityMentionIsNegated($message, $terms)) {
                $excluded[] = $entity;
            }
        }
        foreach ([
            'rose' => ['không thích hoa hồng', 'không muốn hoa hồng', 'không hoa hồng', 'không có hoa hồng', 'bỏ hoa hồng', 'đừng có hoa hồng', 'đừng đưa hoa hồng', 'tránh hoa hồng'],
            'red' => ['không muốn màu đỏ', 'không thích màu đỏ'],
            'chocolate' => ['không chocolate', 'không socola', 'không sô cô la', 'không cacao', 'không cần chocolate', 'không cần socola', 'không muốn chocolate', 'không muốn socola', 'đừng chocolate', 'đừng có chocolate', 'đừng đưa chocolate', 'đừng đưa socola', 'đừng gợi ý chocolate', 'tránh chocolate'],
            'candle' => ['không nến', 'không candle', 'không cần nến', 'không muốn nến', 'đừng nến', 'đừng có nến', 'đừng đưa nến', 'đừng gợi ý nến', 'tránh nến'],
            'teddy' => ['không gấu', 'không muốn gấu bông', 'không cần gấu bông', 'đừng đưa gấu bông', 'đừng gợi ý gấu bông', 'tránh gấu bông'],
            'tulip' => ['không cần tulip', 'không muốn tulip', 'không tulip', 'đừng đưa tulip', 'đừng gợi ý tulip', 'tránh tulip'],
            'flowers' => ['không cần hoa', 'không muốn hoa', 'đừng đưa hoa', 'đừng gợi ý hoa'],
            'round_bouquet' => ['không thích kiểu bó tròn', 'không muốn bó tròn'],
        ] as $item => $terms) {
            if ($item === 'flowers' && $this->containsAny($message, ['hoa hồng', 'hoa tulip', 'hoa baby', 'mẫu đơn', 'hoa ly', 'cẩm tú cầu', 'hướng dương'])) {
                continue;
            }
            if ($this->containsAny($message, $terms)) {
                $excluded[] = $item;
            }
        }

        if ($excluded !== []) {
            $context['exclude'] = $this->merge($context['exclude'], $excluded);
            $patch['exclude'] = ['action' => 'update', 'values' => $context['exclude']];
        }
    }

    /** @return array{min: ?int, max: ?int}|null */
    private function budgetRange(string $message): ?array
    {
        $amount = '(\d[\d.,]*)\s*(triệu|tr|nghìn|ngàn|k|đ)';
        if (preg_match('/(?:từ\s*)?' . $amount . '\s*(?:đến|\-|–)\s*' . $amount . '/ui', $message, $match)) {
            return ['min' => $this->toAmount($match[1], $match[2]), 'max' => $this->toAmount($match[3], $match[4])];
        }

        return null;
    }

    private function amountFrom(string $message): ?int
    {
        if (preg_match('/(\d[\d.,]*)\s*(triệu|tr|nghìn|ngàn|k|đ)\b/ui', $message, $match)) {
            return $this->toAmount($match[1], $match[2]);
        }

        if (preg_match('/(?:giá|ngân sách|dưới|khoảng|tầm|tối đa|trên|từ)\s*(\d{4,7})\b/ui', $message, $match)) {
            return (int) $match[1];
        }

        return null;
    }

    private function toAmount(string $raw, string $unit): int
    {
        $unit = Str::lower($unit);
        $multiplier = in_array($unit, ['triệu', 'tr'], true) ? 1000000 : (in_array($unit, ['nghìn', 'ngàn', 'k'], true) ? 1000 : 1);
        $number = $multiplier === 1000000
            ? (float) str_replace(',', '.', $raw)
            : (float) str_replace([',', '.'], '', $raw);

        return (int) round($number * $multiplier);
    }

    private function roundBudget(int $amount): int
    {
        return (int) (round($amount / 10000) * 10000);
    }

    /** @param array<string, mixed> $context */
    private function summary(array $context): string
    {
        $parts = array_filter([
            $this->occasionLabel($context['occasion']),
            $this->recipientLabel($context['recipient']),
            $context['budget_unlimited'] ? 'không giới hạn ngân sách' : $this->budgetLabel($context),
            $context['colors'] === [] ? null : 'màu ' . implode(', ', $context['colors']),
            $context['styles'] === [] ? null : implode(', ', $context['styles']),
            $context['exclude'] === [] ? null : 'tránh ' . implode(', ', $context['exclude']),
            ! empty($context['suppress_suggestions']) ? 'không gợi ý thêm' : null,
        ]);

        return implode('; ', $parts);
    }

    /** @param array<string, mixed> $context */
    private function budgetLabel(array $context): ?string
    {
        if ($context['budget_min'] !== null && $context['budget_max'] !== null) {
            return number_format((int) $context['budget_min'], 0, ',', '.') . '–' . number_format((int) $context['budget_max'], 0, ',', '.') . 'đ';
        }

        if ($context['budget_max'] !== null) {
            return 'tối đa ' . number_format((int) $context['budget_max'], 0, ',', '.') . 'đ';
        }

        if ($context['budget_min'] !== null) {
            return 'từ ' . number_format((int) $context['budget_min'], 0, ',', '.') . 'đ';
        }

        return null;
    }

    private function occasionLabel(?string $occasion): ?string
    {
        return ['birthday' => 'sinh nhật', 'anniversary' => 'kỷ niệm', 'graduation' => 'tốt nghiệp', 'opening' => 'khai trương', 'thanks' => 'cảm ơn', 'apology' => 'xin lỗi', 'valentine' => 'Valentine', 'international_womens_day' => '8/3', 'vietnamese_womens_day' => '20/10', 'congratulations' => 'chúc mừng', 'get_well' => 'thăm hỏi'][ $occasion] ?? null;
    }

    private function recipientLabel(?string $recipient): ?string
    {
        return ['girlfriend' => 'bạn gái', 'boyfriend' => 'bạn trai', 'lover' => 'người yêu', 'mother' => 'mẹ', 'father' => 'bố', 'friend' => 'bạn bè', 'colleague' => 'đồng nghiệp'][$recipient] ?? null;
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function normalize(array $context): array
    {
        $context = array_replace($this->empty(), $context);
        foreach (['colors', 'styles', 'flower_preferences', 'gift_preferences', 'must_include', 'exclude', 'notes', 'negative_constraints'] as $key) {
            $context[$key] = array_values(array_filter((array) $context[$key], 'is_string'));
        }
        $context['budget_min'] = is_numeric($context['budget_min']) ? (int) $context['budget_min'] : null;
        $context['budget_max'] = is_numeric($context['budget_max']) ? (int) $context['budget_max'] : null;
        $context['budget_unlimited'] = (bool) $context['budget_unlimited'];
        $context['suppress_suggestions'] = (bool) ($context['suppress_suggestions'] ?? false);

        return $context;
    }

    /** @param array<int, string> $current @param array<int, string> $new @return array<int, string> */
    private function merge(array $current, array $new): array
    {
        return array_values(array_unique([...$current, ...$new]));
    }

    /** @param array<string, array<int, string>> $terms @return array<int, string> */
    private function findTerms(string $message, array $terms): array
    {
        return array_keys(array_filter($terms, fn (array $values): bool => $this->containsAny($message, $values)));
    }

    /** @param array<int, string> $needles */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (Str::contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}

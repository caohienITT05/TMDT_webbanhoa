<?php

namespace App\Services;

use App\Services\GiftAdvisor\ConversationContext;
use App\Services\GiftAdvisor\OnlineAdvisorProvider;
use App\Services\GiftAdvisor\ProductRetrievalService;
use App\Services\GiftAdvisor\WebSearchService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sales-advisor orchestration. The LLM may understand and rank a request, but
 * Laravel remains authority over conversation state and product facts.
 */
class GiftAdvisorService
{
    public function __construct(
        private readonly ConversationContext $contexts,
        private readonly ProductRetrievalService $products,
        private readonly OnlineAdvisorProvider $provider,
        private readonly WebSearchService $webSearch,
    ) {
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, int>  $recentProductIds
     * @return array<string, mixed>
     */
    public function advise(string $query, array $context = [], array $recentProductIds = [], array $selectedConcept = [], array $recentConceptNames = [], ?callable $onText = null, array $recentRecommendationKeys = [], array $rejectedSuggestionKeys = [], array $lastResponseItems = [], array $selectedItem = []): array
    {
        $turn = $this->contexts->apply($context, $query);
        $context = $turn['context'];
        $strictEntity = is_string($context['target_entity'] ?? null)
            && ($context['allow_category_expansion'] ?? true) === false;
        $recommendationHistory = array_values(array_unique([...$recentRecommendationKeys, ...$rejectedSuggestionKeys]));
        $recentProductIds = array_values(array_unique([...$recentProductIds, ...$this->productIdsFromKeys($recommendationHistory)]));
        $recentConceptKeys = $this->customKeysFromKeys($recommendationHistory);

        if (! empty($context['suppress_suggestions'])) {
            return $this->suppressedResponse($context, $turn['patch'], $recentProductIds, $recentConceptKeys);
        }

        if ($this->isExclusionOnlyTurn($query, $turn['patch'])) {
            $labels = collect($context['exclude'])->map(fn (string $item): string => $this->exclusionLabel($item))->unique()->values()->all();

            return $this->directResponse('Được, mình sẽ loại ' . $this->joinLabels($labels) . ' khỏi các gợi ý tiếp theo.', $context, $turn['patch'], $recentProductIds, $recentConceptKeys);
        }

        $ordinal = $this->requestedOrdinal($query, count($lastResponseItems));
        if ($ordinal !== null) {
            return $this->ordinalResponse($ordinal, $lastResponseItems, $selectedItem, $context, $turn['patch'], $recentProductIds, $recentConceptKeys);
        }

        $selectedItem = $this->selectedItemFromMemory($selectedItem, $lastResponseItems, $context);
        if ($selectedItem !== [] && $this->isEditRequest($query)) {
            return $this->editSelectedResponse($selectedItem, $context, $turn['patch'], $query, $recentProductIds, $recentConceptKeys);
        }

        if ($this->shouldAskRecipient($query, $context)) {
            return [
                'message' => 'Được nhé 🌷 Bạn đang tìm hoa cho dịp ' . $this->occasionLabel($context['occasion']) . '. Bạn định tặng ai để mình chọn đúng kiểu hơn?',
                'context' => $context,
                'context_patch' => $turn['patch'],
                'intent' => $this->legacyIntent($context),
                'suggestions' => [],
                'in_store_suggestions' => [],
                'custom_suggestions' => [],
                'quick_replies' => ['Người yêu', 'Mẹ', 'Bạn bè', 'Đồng nghiệp'],
                'guidance' => null,
                'provider' => ['status' => 'not_needed', 'active' => false, 'model_response_received' => false, 'structured_output_valid' => false],
                'recommendation_mode' => 'clarification',
                'web_search' => ['status' => 'not_needed', 'active' => false, 'requested_by_ai' => false, 'sources' => []],
                'recent_product_ids' => [],
                'recent_custom_concept_keys' => $recentConceptKeys,
                'selected_concept' => [],
            ];
        }

        $candidates = $this->products->candidates($context, $recentProductIds);
        $forceWebSearch = Str::contains(Str::lower($query), ['tìm thêm trên mạng', 'tìm trên mạng', 'tìm thêm online']);
        $searchPlan = $forceWebSearch
            ? ['status' => 'active', 'needs_search' => true, 'queries' => $this->webSearch->queries($context)]
            : ($this->webSearch->shouldSearch($query)
                ? $this->provider->webSearchPlan($query, $context, $candidates)
                : ['status' => 'not_needed', 'needs_search' => false, 'queries' => []]);
        $web = $searchPlan['needs_search']
            ? $this->webSearch->inspiration($query, $context, $searchPlan['queries'])
            : ['status' => $searchPlan['status'], 'sources' => [], 'root_cause' => null, 'http_status' => null];
        $selectedConcept = $this->sanitizeSelectedConcept($selectedConcept);
        $recentConceptNames = collect($recentConceptNames)->filter(fn (mixed $name): bool => is_string($name))->map(fn (string $name): string => Str::limit(trim(strip_tags($name)), 255, ''))->filter()->unique()->take(12)->values()->all();
        if ($this->rejectsSelectedConcept($query) && filled($selectedConcept['name'] ?? null)) {
            $recentConceptNames = array_values(array_unique([...$recentConceptNames, $selectedConcept['name']]));
        }
        // Keep provider streaming enabled, but hold its prose until the final
        // answer passes state, language and pronoun validation.
        $providerTextCallback = ! $strictEntity && $onText !== null
            ? static function (string $chunk): void {}
            : null;
        $online = $this->provider->advise($query, $context, $candidates, $recentProductIds, $web['sources'], $selectedConcept, $recentConceptNames, $providerTextCallback, $recommendationHistory);
        $onlineData = is_array($online['data'] ?? null) ? $online['data'] : null;

        $productCards = $this->productCards($onlineData['product_ids'] ?? [], $candidates, $context);
        $explicitCustomRequest = Str::contains(Str::lower($query), ['đặt theo yêu cầu', 'thiết kế', 'mẫu lạ', 'mẫu độc đáo']);
        $allowSameEntityCustom = $strictEntity && ($context['query_mode'] ?? null) !== 'alternatives'
            && ($context['product_focus'] ?? null) === 'flower'
            && (
                Str::contains(Str::lower($query), ['thêm ', 'kèm ', 'phối ', 'cùng '])
                || ($context['occasion'] ?? null) !== null
                || ($context['budget_max'] ?? null) !== null
                || ($context['colors'] ?? []) !== []
                || ($context['styles'] ?? []) !== []
            );
        $customCards = ! empty($context['inventory_only'])
            ? []
            : ($strictEntity && ! $explicitCustomRequest && ! $allowSameEntityCustom
            ? []
            : $this->customCards($onlineData['custom_suggestions'] ?? [], $context, $query, count($productCards), $web['sources'], $selectedConcept, $recentConceptNames, $recentConceptKeys, ($online['status'] ?? null) === 'active'));
        if ($strictEntity) {
            $customCards = array_values(array_filter($customCards, fn (array $card): bool => ConversationContext::entityMatches(
                $context['target_entity'],
                implode(' ', array_filter([$card['name'] ?? '', $card['description'] ?? '', ...array_values((array) ($card['details'] ?? []))], 'is_string'))
            )));
        }
        $productCards = array_map(fn (array $card): array => [...$card, 'recommendation_key' => 'product:' . $card['product_id']], $productCards);
        $customCards = array_map(function (array $card): array {
            $fingerprint = $this->conceptFingerprint($card);

            return [...$card, 'recommendation_key' => 'custom:' . $fingerprint];
        }, $customCards);
        $suggestions = [...$productCards, ...$customCards];
        $providerActive = ($online['status'] ?? null) === 'active';
        $followUpQuestion = null;
        if ($strictEntity) {
            $entityLabel = ConversationContext::entityLabel($context['target_entity']) ?? 'sản phẩm';
            if ($productCards !== []) {
                $count = count($productCards);
                $followUpQuestion = null;
                $message = $context['query_mode'] === 'alternatives'
                    ? "Có, BloomGift còn {$count} mẫu {$entityLabel} khác đang sẵn hàng phù hợp."
                    : "Có, BloomGift đang có {$count} mẫu {$entityLabel} sẵn hàng phù hợp.";
            } else {
                $message = $context['query_mode'] === 'alternatives'
                    ? "Hiện BloomGift chưa còn mẫu {$entityLabel} khác đang sẵn hàng và khớp các tiêu chí bạn đưa ra."
                    : "Hiện BloomGift chưa có mẫu {$entityLabel} đang sẵn hàng và khớp các tiêu chí bạn đưa ra.";
                $followUpQuestion = "Bạn muốn tìm {$entityLabel} đặt theo yêu cầu hay chuyển sang loại quà khác?";
            }
        } else {
            $message = $providerActive
                ? $this->validatedAssistantMessage($onlineData['assistant_message'], $productCards, $context, $turn['patch'], count($suggestions))
                : $this->fallbackMessage($context, $turn['patch'], count($suggestions));
        }

        if ($providerActive && ! $strictEntity && $onText !== null) {
            foreach (mb_str_split($message, 18) as $chunk) {
                $onText($chunk);
            }
        }

        return [
            'message' => $message,
            'follow_up_question' => $followUpQuestion,
            'quick_replies' => $strictEntity && $followUpQuestion !== null
                ? ['Tìm mẫu ' . (ConversationContext::entityLabel($context['target_entity']) ?? 'này') . ' đặt theo yêu cầu', 'Chuyển sang loại quà khác']
                : [],
            'context' => $context,
            'context_patch' => $turn['patch'],
            'intent' => $this->legacyIntent($context),
            'suggestions' => $suggestions,
            'in_store_suggestions' => $productCards,
            'custom_suggestions' => $customCards,
            'guidance' => $strictEntity ? null : $this->guidance($context, $productCards),
            'provider' => [
                'status' => $online['status'],
                'active' => $providerActive,
                'model_response_received' => (bool) ($online['model_response_received'] ?? false),
                'structured_output_valid' => (bool) ($online['structured_output_valid'] ?? false),
            ],
            'recommendation_mode' => $providerActive ? 'online' : 'fallback',
            'web_search' => [
                'status' => $web['status'],
                'active' => $web['status'] === 'active',
                'requested_by_ai' => $searchPlan['needs_search'],
                'root_cause' => $web['root_cause'] ?? ($searchPlan['status'] ?? null),
                'http_status' => $web['http_status'] ?? null,
                'result_count' => count($web['sources']),
                'sources' => $web['sources'],
            ],
            'recent_product_ids' => collect([...$recentProductIds, ...collect($productCards)->pluck('product_id')->filter()->map(fn (mixed $id): int => (int) $id)->all()])->unique()->take(60)->values()->all(),
            'recommendation_keys' => collect($suggestions)->pluck('recommendation_key')->values()->all(),
            'recent_custom_concept_keys' => collect([...$recentConceptKeys, ...collect($customCards)->map(fn (array $card): string => substr((string) $card['recommendation_key'], 7))->all()])->unique()->take(60)->values()->all(),
            'product_catalog_exhausted' => ($strictEntity && $productCards === []) || ($recentProductIds !== [] && $productCards === []),
            'selected_concept' => $selectedConcept,
            'selected_item' => $selectedItem ?: null,
            'last_response_items' => $suggestions,
        ];
    }

    /** @param array<int, string> $messages @return array<string, mixed> */
    public function contextFromMessages(array $messages): array
    {
        $context = $this->contexts->empty();
        foreach (array_slice($messages, -60) as $message) {
            if (is_string($message) && trim($message) !== '') {
                $context = $this->contexts->apply($context, Str::limit(trim($message), 500, ''))['context'];
            }
        }

        return $context;
    }

    /** @param array<int, int> $requestedIds @return array<int, array<string, mixed>> */
    private function productCards(array $requestedIds, Collection $candidates, array $context): array
    {
        $allowed = $candidates->keyBy('id');
        $requestedIds = array_values(array_unique(array_map('intval', array_filter($requestedIds, fn (mixed $id): bool => is_numeric($id)))));
        $selectedIds = array_values(array_filter($requestedIds, fn (int $id): bool => $allowed->has($id)));
        $selected = $this->products->productsByIds($selectedIds);
        $selected = $selected->isNotEmpty() ? $selected : $candidates->take(3);

        return $selected->take(3)
            ->values()
            ->map(function ($product, int $index) use ($context): array {
                $card = $this->products->card($product, $context);
                if ($index > 0) {
                    $card['recommendation_badge'] = null;
                }

                return $card;
            })
            ->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $onlineIdeas
     * @return array<int, array<string, mixed>>
     */
    private function customCards(array $onlineIdeas, array $context, string $query, int $productCount, array $sources, array $selectedConcept, array $recentConceptNames, array $recentConceptKeys, bool $providerActive): array
    {
        $focus = $context['product_focus'] ?? 'flower';
        $target = min(5, max(1, 6 - $productCount));
        $ideas = collect($onlineIdeas)
            ->filter(fn (mixed $idea): bool => is_array($idea) && $this->isCoherentConcept($idea, $context))
            ->filter(fn (array $idea): bool => ! $this->customMatchesExclusions($idea, $context))
            ->filter(fn (array $idea): bool => ! in_array($this->conceptFingerprint($idea), $recentConceptKeys, true))
            ->filter(fn (array $idea): bool => ! $this->isRecentConcept($idea['name'] ?? '', $recentConceptNames))
            ->unique(fn (array $idea): string => $this->conceptFingerprint($idea))
            ->take($target)->values()->all();

        if ($selectedConcept !== [] && ! $this->rejectsSelectedConcept($query) && Str::contains(Str::lower($query), ['thêm', 'đổi', 'bỏ', 'chuyển', 'chỉnh', 'giữ'])) {
            $ideas[] = $this->selectedVariant($selectedConcept, $context, $query);
        }

        // Static archetypes are a fallback only. A configured model's own
        // quality/quantity decision is respected; we do not pad it with templates.
        if (! $providerActive && $ideas === []) {
            $ideas = collect($this->fallbackCustomIdeas($context, $query))
                ->filter(fn (array $idea): bool => ! in_array($this->conceptFingerprint($idea), $recentConceptKeys, true))
                ->filter(fn (array $idea): bool => ! $this->isRecentConcept($idea['name'] ?? '', $recentConceptNames))
                ->unique(fn (array $idea): string => $this->conceptFingerprint($idea))
                ->take(min(3, $target))->values()->all();
        }

        $seen = [];
        $ideas = array_values(array_filter($ideas, function (array $idea) use (&$seen, $context): bool {
            $fingerprint = $this->conceptFingerprint($idea);
            if (! $this->isCoherentConcept($idea, $context) || isset($seen[$fingerprint])) return false;
            $seen[$fingerprint] = true;
            $text = Str::lower(implode(' ', $idea));
            if ($this->customMatchesExclusions($idea, $context)) return false;

            return true;
        }));

        return collect($ideas)->map(function (array $idea) use ($context, $query, $sources, $focus): array {
            $type = $idea['recommendation_type'] ?? match ($focus) {
                'gift' => 'CUSTOM_GIFT', 'set' => 'CUSTOM_COMBO', default => 'CUSTOM_FLOWER',
            };
            $fullDescription = $idea['full_description'] ?? $idea['design_description'] ?? '';
            $shortDescription = $idea['short_description'] ?? Str::limit($fullDescription, 220, '');
            $details = match ($type) {
                'CUSTOM_GIFT' => array_filter([
                    'type' => 'Quà tặng', 'gift_type' => $idea['gift_type'] ?? null,
                    'main_items' => $idea['main_items'] ?? null, 'supporting_items' => $idea['supporting_items'] ?? null,
                    'container' => $idea['container'] ?? null, 'wrapping' => $idea['wrapping'] ?? null,
                    'color_palette' => $idea['palette'] ?? null, 'style' => $idea['style'] ?? null,
                    'personalization' => $idea['personalization'] ?? null, 'visual_feeling' => $idea['mood'] ?? null,
                    'occasion' => $this->occasionLabel($context['occasion'] ?? null), 'recipient' => $this->recipientLabel($context['recipient'] ?? null),
                    'design_description' => $fullDescription, 'customization_options' => $idea['customization_options'] ?? null,
                ]),
                'CUSTOM_COMBO' => array_filter([
                    'type' => 'Set hoa & quà', 'flowers' => $idea['flowers'] ?? null, 'gifts' => $idea['gifts'] ?? null,
                    'container' => $idea['container'] ?? null, 'color_palette' => $idea['palette'] ?? null,
                    'style' => $idea['style'] ?? null, 'personalization' => $idea['personalization'] ?? null,
                    'occasion' => $this->occasionLabel($context['occasion'] ?? null), 'recipient' => $this->recipientLabel($context['recipient'] ?? null),
                    'design_description' => $fullDescription, 'customization_options' => $idea['customization_options'] ?? null,
                ]),
                default => array_filter([
                    'type' => 'Hoa', 'main_flowers' => $idea['main_flowers'] ?? null,
                    'supporting_flowers' => $idea['supporting_flowers'] ?? $idea['supporting_elements'] ?? null,
                    'greenery' => $idea['greenery'] ?? null, 'components' => implode('; ', array_filter([$idea['main_flowers'] ?? null, $idea['supporting_flowers'] ?? $idea['supporting_elements'] ?? null, $idea['greenery'] ?? null])),
                    'color_palette' => $idea['palette'] ?? $idea['color_palette'] ?? null, 'design_form' => $idea['bouquet_shape'] ?? $idea['design_form'] ?? null,
                    'wrapping' => $idea['wrapping'] ?? null, 'style' => $idea['style'] ?? null,
                    'visual_feeling' => $idea['mood'] ?? $idea['visual_feeling'] ?? null,
                    'occasion' => $this->occasionLabel($context['occasion'] ?? null), 'recipient' => $this->recipientLabel($context['recipient'] ?? null),
                    'meaning' => $idea['meaning'] ?? null, 'design_description' => $fullDescription,
                    'customization_options' => $idea['customization_options'] ?? null,
                ]),
            };

            $mainComponent = match ($type) {
                'CUSTOM_GIFT' => $idea['main_items'] ?? '',
                'CUSTOM_COMBO' => trim(($idea['flowers'] ?? '') . ' + ' . ($idea['gifts'] ?? ''), ' +'),
                default => $idea['main_flowers'] ?? '',
            };
            $customOrderBrief = [
                'recommendation_type' => $type,
                'main_component' => $mainComponent,
                'supporting_component' => $idea['supporting_items'] ?? $idea['supporting_flowers'] ?? $idea['supporting_elements'] ?? $idea['gifts'] ?? '',
                'palette' => $idea['palette'] ?? $idea['color_palette'] ?? '',
                'style' => $idea['style'] ?? '',
                'container' => $idea['container'] ?? $idea['bouquet_shape'] ?? $idea['design_form'] ?? '',
                'wrapping' => $idea['wrapping'] ?? '',
                'full_description' => $fullDescription,
            ];

            return [
                'kind' => 'custom', 'recommendation_type' => $type, 'product_id' => null,
                'name' => $idea['name'], 'description' => $shortDescription, 'short_description' => $shortDescription,
                'full_description' => $fullDescription, 'why_recommended' => $idea['why_recommended'], 'details' => $details,
                'price' => null, 'regular_price' => null, 'sale_price' => null, 'stock' => null,
                'image_url' => null, 'availability' => 'Thiết kế theo yêu cầu',
                'budget_fit' => $context['budget_max'] ? 'BloomGift sẽ xác nhận khả năng thực hiện trong ngân sách ' . number_format((int) $context['budget_max'], 0, ',', '.') . 'đ.' : 'BloomGift sẽ xác nhận giá sau khi chốt cấu hình.',
                'available' => false, 'product_url' => null, 'cart_url' => null,
                'custom_order_url' => $this->customOrderUrl($idea['name'], $context, $customOrderBrief, $query), 'sources' => $sources,
            ];
        })->values()->all();
    }

    /** @return array<string, string> */
    private function sanitizeSelectedConcept(array $concept): array
    {
        $fields = ['name', 'recommendation_type', 'gift_type', 'main_items', 'supporting_items', 'main_flowers', 'supporting_flowers', 'supporting_elements', 'flowers', 'gifts', 'container', 'personalization', 'color_palette', 'palette', 'design_form', 'bouquet_shape', 'wrapping', 'style', 'visual_feeling', 'mood', 'meaning', 'design_description', 'why_recommended', 'customization_options'];
        $safe = [];
        foreach ($fields as $field) {
            if (isset($concept[$field]) && is_string($concept[$field])) {
                $maxLength = in_array($field, ['design_description', 'personalization', 'customization_options'], true) ? 1200 : 300;
                $safe[$field] = Str::limit(strip_tags(trim($concept[$field])), $maxLength, '');
            }
        }

        return $safe;
    }

    /** @return array<int, int> */
    private function productIdsFromKeys(array $keys): array
    {
        return collect($keys)->filter(fn (mixed $key): bool => is_string($key) && preg_match('/^product:([1-9][0-9]*)$/', $key))
            ->map(fn (string $key): int => (int) substr($key, 8))->unique()->values()->all();
    }

    /** @return array<int, string> */
    private function customKeysFromKeys(array $keys): array
    {
        return collect($keys)->filter(fn (mixed $key): bool => is_string($key) && preg_match('/^custom:[a-f0-9]{40}$/', $key))
            ->map(fn (string $key): string => substr($key, 7))->unique()->values()->all();
    }

    private function conceptFingerprint(array $idea): string
    {
        $details = is_array($idea['details'] ?? null) ? $idea['details'] : [];
        $type = $idea['recommendation_type'] ?? $details['type'] ?? 'CUSTOM_FLOWER';
        $parts = match ($type) {
            // The display name/gift_type may be freshly phrased by the model;
            // concept identity comes from the actual contents and presentation.
            'CUSTOM_GIFT' => [$idea['main_items'] ?? $details['main_items'] ?? '', $idea['supporting_items'] ?? $details['supporting_items'] ?? '', $idea['container'] ?? $details['container'] ?? '', $idea['style'] ?? $details['style'] ?? '', $idea['palette'] ?? $details['color_palette'] ?? ''],
            'CUSTOM_COMBO' => [$idea['flowers'] ?? $details['flowers'] ?? '', $idea['gifts'] ?? $details['gifts'] ?? '', $idea['container'] ?? $details['container'] ?? '', $idea['style'] ?? $details['style'] ?? '', $idea['palette'] ?? $details['color_palette'] ?? ''],
            default => [$idea['main_flowers'] ?? $details['main_flowers'] ?? '', $idea['supporting_flowers'] ?? $idea['supporting_elements'] ?? $details['supporting_flowers'] ?? '', $idea['bouquet_shape'] ?? $idea['design_form'] ?? $details['design_form'] ?? '', $idea['style'] ?? $details['style'] ?? '', $idea['palette'] ?? $idea['color_palette'] ?? $details['color_palette'] ?? ''],
        };
        $identity = Str::lower(preg_replace('/[^\pL\pN]+/u', ' ', implode(' ', [$type, ...$parts])) ?? '');

        return sha1(trim(preg_replace('/\s+/u', ' ', $identity) ?? ''));
    }

    private function isRecentConcept(string $name, array $recentNames): bool
    {
        $identity = Str::lower(preg_replace('/[^\pL\pN]+/u', '', $name) ?? '');
        if ($identity === '') {
            return false;
        }

        foreach ($recentNames as $recentName) {
            $recentIdentity = Str::lower(preg_replace('/[^\pL\pN]+/u', '', $recentName) ?? '');
            if ($recentIdentity !== '' && ($identity === $recentIdentity || Str::contains($identity, $recentIdentity) || Str::contains($recentIdentity, $identity))) {
                return true;
            }
        }

        return false;
    }

    private function rejectsSelectedConcept(string $query): bool
    {
        return Str::contains(Str::lower($query), [
            'không thích mẫu này', 'mẫu này không đẹp', 'hơi sến', 'quá đơn giản',
            'quá nhiều hoa', 'không sang', 'không thích kiểu này',
        ]);
    }

    /** @return array<string, string> */
    private function selectedVariant(array $selected, array $context, string $query): array
    {
        if (($context['product_focus'] ?? 'flower') === 'set' && ! in_array($selected['recommendation_type'] ?? null, ['CUSTOM_GIFT', 'CUSTOM_COMBO'], true)) {
            $flowers = trim(implode('; ', array_filter([
                $selected['main_flowers'] ?? '',
                $selected['supporting_flowers'] ?? $selected['supporting_elements'] ?? '',
            ])));
            $gifts = Str::contains(Str::lower($query), ['chocolate', 'socola'])
                ? 'Chocolate theo khẩu vị và thiệp cá nhân hoá'
                : (implode(', ', (array) ($context['gift_preferences'] ?? [])) ?: 'Món quà nhỏ và thiệp cá nhân hoá');
            $container = Str::contains(Str::lower($query), ['hộp', 'box'])
                ? 'Hộp quà có phần đỡ hoa và ngăn quà riêng'
                : 'Set hoa và quà có lớp lót bảo vệ riêng';
            $palette = $selected['color_palette'] ?? $this->colorLabel($context['colors'] ?? [], (bool) ($context['color_flexible'] ?? false));
            $style = $selected['style'] ?? $this->styleLabel($context['styles'] ?? []);
            $personalization = 'Có thể đổi món quà đi kèm, màu card và cách trình bày sau khi BloomGift xác nhận.';
            $description = trim(($selected['design_description'] ?? '') . ' Phần quà được thêm theo yêu cầu: ' . $gifts . '.');

            return [
                'recommendation_type' => 'CUSTOM_COMBO', 'name' => ($selected['name'] ?? 'Thiết kế bạn chọn') . ' & Chocolate',
                'flowers' => $flowers, 'gifts' => $gifts, 'container' => $container, 'palette' => $palette,
                'style' => $style, 'personalization' => $personalization,
                'short_description' => $flowers . ' kết hợp ' . $gifts . '.', 'full_description' => $description,
                'why_recommended' => 'Giữ lại phần hoa bạn đã chọn và thêm món quà theo yêu cầu để tạo thành một set đồng bộ.',
                'customization_options' => $personalization,
            ];
        }
        if (($selected['recommendation_type'] ?? null) === 'CUSTOM_GIFT') {
            $supporting = $selected['supporting_items'] ?? '';
            if (Str::contains(Str::lower($query), ['thêm chocolate', 'kèm chocolate', 'thêm socola']) && ! Str::contains(Str::lower($supporting), ['chocolate', 'socola'])) $supporting .= '; chocolate theo khẩu vị';
            $main = $selected['main_items'] ?? '';
            $palette = $selected['palette'] ?? $selected['color_palette'] ?? 'theo tư vấn BloomGift';
            if (($context['colors'] ?? []) !== [] && Str::contains(Str::lower($query), ['màu', 'tông', 'sắc'])) {
                $palette = $this->colorLabel($context['colors'], false);
            }
            $container = $selected['container'] ?? 'Hộp quà';
            if (Str::contains(Str::lower($query), ['chuyển sang hộp', 'đổi sang hộp', 'hộp quà'])) {
                $container = 'Hộp quà cứng phối theo tông ' . mb_strtolower($palette);
            }
            $name = ($selected['name'] ?? 'Món quà bạn chọn') . ' – phiên bản tuỳ chỉnh';
            $personalization = $selected['personalization'] ?? 'Có thể đổi cách trình bày và nội dung card.';
            $description = trim(($selected['design_description'] ?? '') . ' Bản chỉnh giữ các món quà chính, cập nhật theo yêu cầu: ' . $query);

            return [
                'recommendation_type' => 'CUSTOM_GIFT', 'name' => $name, 'gift_type' => $selected['gift_type'] ?? 'Quà cá nhân hoá',
                'main_items' => $main, 'supporting_items' => $supporting, 'container' => $container,
                'wrapping' => $selected['wrapping'] ?? 'Giấy lót phối đồng bộ với hộp quà', 'palette' => $palette,
                'style' => $selected['style'] ?? $this->styleLabel($context['styles'] ?? []), 'mood' => $selected['visual_feeling'] ?? 'phù hợp với concept đã chọn',
                'personalization' => $personalization, 'short_description' => $main . '; phối cùng ' . $supporting . '.',
                'full_description' => $description, 'why_recommended' => 'Giữ đúng thành phần quà bạn đã chọn và chỉ cập nhật theo yêu cầu mới.',
                'customization_options' => $personalization,
            ];
        }
        if (($selected['recommendation_type'] ?? null) === 'CUSTOM_COMBO') {
            $flowers = $selected['flowers'] ?? '';
            $gifts = $selected['gifts'] ?? '';
            if (Str::contains(Str::lower($query), ['thêm chocolate', 'kèm chocolate', 'thêm socola'])) $gifts .= '; chocolate theo khẩu vị';
            $palette = $selected['palette'] ?? $selected['color_palette'] ?? 'phối màu hài hoà';
            if (($context['colors'] ?? []) !== [] && Str::contains(Str::lower($query), ['màu', 'tông', 'sắc'])) {
                $palette = $this->colorLabel($context['colors'], false);
            }

            return [
                'recommendation_type' => 'CUSTOM_COMBO', 'name' => ($selected['name'] ?? 'Set bạn chọn') . ' – phiên bản tuỳ chỉnh',
                'flowers' => $flowers, 'gifts' => $gifts, 'container' => $selected['container'] ?? 'Hộp quà',
                'palette' => $palette, 'style' => $selected['style'] ?? $this->styleLabel($context['styles'] ?? []),
                'personalization' => $selected['personalization'] ?? 'Có thể điều chỉnh hoa, quà và card.',
                'short_description' => $flowers . ' kết hợp ' . $gifts . '.',
                'full_description' => trim(($selected['design_description'] ?? '') . ' Bản chỉnh theo yêu cầu: ' . $query),
                'why_recommended' => 'Giữ cân bằng giữa phần hoa và món quà đã chọn, đồng thời cập nhật đúng chi tiết bạn muốn.',
                'customization_options' => $selected['personalization'] ?? 'Có thể điều chỉnh hoa, quà và card.',
            ];
        }

        $color = $this->colorLabel($context['colors'] ?? [], (bool) ($context['color_flexible'] ?? false));
        $style = $this->styleLabel($context['styles'] ?? []);
        $baseName = $selected['name'] ?? 'Thiết kế bạn chọn';
        $mainFlowers = $selected['main_flowers'] ?? $this->primaryFlower($context);
        $supporting = $selected['supporting_elements'] ?? 'Hoa phụ theo mùa và chi tiết trang trí phù hợp';
        if (Str::contains(Str::lower($query), ['thêm chocolate', 'kèm chocolate', 'thêm socola'])) {
            $supporting .= '; chocolate thủ công và thiệp nhỏ';
        }
        if (Str::contains(Str::lower($query), ['bỏ baby', 'không cần baby'])) {
            $supporting = preg_replace('/(?:baby\s*trắng?[,;]?\s*)/iu', '', $supporting) ?: $supporting;
        }
        if (Str::contains(Str::lower($query), ['chuyển sang hộp', 'flower box'])) {
            $selected['design_form'] = 'Flower box dáng mở, tạo lớp quanh hoa chủ đạo';
            $selected['wrapping'] = 'Hộp cứng màu kem mờ và ribbon satin';
        }
        $oldDescription = $selected['design_description'] ?? 'Thiết kế được cá nhân hoá theo yêu cầu của bạn.';
        $designForm = $selected['design_form'] ?? 'Garden bouquet dáng mở nhẹ';
        $wrapping = $selected['wrapping'] ?? 'Giấy mỹ thuật và ribbon theo bảng màu';
        $mood = $selected['visual_feeling'] ?? 'giữ vẻ cân bằng, thanh lịch và có điểm nhấn';
        $description = $oldDescription . " Bản chỉnh giữ bố cục {$designForm}, hoàn thiện bằng {$wrapping}; phần phối được cập nhật theo yêu cầu mới: {$query}. BloomGift sẽ xác nhận nguồn hoa, giá và khả năng thực hiện trước khi nhận yêu cầu.";

        return [
            'name' => Str::contains(Str::lower($query), ['đổi sang tông hồng', 'màu hồng'])
                ? preg_replace('/trắng|white/iu', 'hồng pastel', $baseName) . ' – Pink Edition'
                : $baseName . ($context['colors'] !== [] ? ' – phiên bản ' . $color : ' – phiên bản tuỳ chỉnh'),
            'main_flowers' => $mainFlowers,
            'supporting_elements' => $supporting,
            'color_palette' => $color,
            'style' => $style,
            'design_form' => $designForm,
            'wrapping' => $wrapping,
            'visual_feeling' => $mood,
            'meaning' => $selected['meaning'] ?? $this->meaning($this->occasionLabel($context['occasion'] ?? null), $this->recipientLabel($context['recipient'] ?? null)),
            'short_description' => Str::limit("{$mainFlowers} phối {$supporting}, giữ tinh thần concept đã chọn và chuyển sang tông {$color}.", 220, ''),
            'full_description' => $description,
            'design_description' => $description,
            'why_recommended' => 'Mình giữ lại concept ' . $baseName . ' mà bạn đã chọn và chỉ điều chỉnh chi tiết theo yêu cầu mới.',
            'best_for' => 'Bạn đã thích concept này và muốn tinh chỉnh theo sở thích mới.',
            'customization_options' => 'Có thể tiếp tục đổi màu, hoa phụ, kiểu gói, dáng bó/hộp và kích thước.',
            'budget_fit' => $context['budget_unlimited'] ? 'BloomGift sẽ xác nhận giá sau khi chốt loại hoa, kích thước và hoàn thiện.' : ($context['budget_max'] ? 'Có thể gửi yêu cầu theo ngân sách khoảng ' . number_format((int) $context['budget_max'], 0, ',', '.') . 'đ.' : 'Giá sẽ được xác nhận theo hoa và kích thước thực tế.'),
        ];
    }

    /**
     * Local composition rules used only when the optional online provider is
     * unavailable. They generate a brief from the current context, rather than
     * pretending a static design list is the BloomGift catalogue.
     *
     * @return array<int, array<string, string>>
     */
    private function fallbackCustomIdeas(array $context, string $query): array
    {
        $focus = $context['product_focus'] ?? 'flower';
        if ($focus === 'gift') return $this->fallbackGiftIdeas($context, $query);
        if ($focus === 'set') return $this->fallbackComboIdeas($context, $query);
        $style = $this->styleLabel($context['styles'] ?? []);
        $color = $this->colorLabel($context['colors'] ?? [], (bool) ($context['color_flexible'] ?? false));
        $occasion = $this->occasionLabel($context['occasion'] ?? null);
        $recipient = $this->recipientLabel($context['recipient'] ?? null);
        $flower = $this->primaryFlower($context);
        $excluded = (array) ($context['exclude'] ?? []);

        $mainFlowers = $this->fallbackFlowerCompositions($context);
        $bases = [
            ['Moonlit Garden – ' . ucfirst($mainFlowers[0]) . ' & Cẩm Tú Cầu', $mainFlowers[0], 'cẩm tú cầu, baby trắng và eucalyptus bạc'],
            ['Pastel Atelier – ' . ucfirst($mainFlowers[1]) . ' & Baby Trắng', $mainFlowers[1], 'baby trắng, lá phụ thanh mảnh và giấy mỹ thuật nhiều lớp'],
            ['Ivory Keepsake Box – ' . ucfirst($mainFlowers[2]) . ' & Hộp Kem', $mainFlowers[2] . ' được tạo khối trong flower box', 'hoa phụ tông kem và ribbon satin'],
            ['Botanical Basket – ' . ucfirst($mainFlowers[3]) . ' & Lá Bạc', $mainFlowers[3] . ' phối eucalyptus và hoa phụ theo mùa', 'giỏ mây lót vải linen và thiệp nhỏ'],
        ];

        $offset = abs(crc32(Str::lower($query . '|' . json_encode($context)))) % max(1, count($bases));
        $bases = [...array_slice($bases, $offset), ...array_slice($bases, 0, $offset)];

        $customizations = 'Có thể điều chỉnh tông màu, hoa phối và kích thước sau khi BloomGift xác nhận nguồn hoa và mức giá.';

        return collect($bases)->map(function (array $idea, int $index) use ($style, $color, $occasion, $recipient, $focus, $customizations, $context): array {
            [$name, $main, $supporting] = $idea;
            $form = $focus === 'gift' ? 'Hộp / giỏ quà sắp lớp, giữ sản phẩm chính ở trung tâm' : (Str::contains(Str::lower($name), 'box') ? 'Flower box dáng mở, tạo lớp cao thấp quanh hoa chủ đạo' : (Str::contains(Str::lower($name), 'basket') ? 'Giỏ hoa dáng vòm, điểm hoa cao ở tâm và hoa phụ viền ngoài' : 'Garden bouquet dáng mở nhẹ, có khoảng thở giữa các lớp hoa'));
            $wrapping = $focus === 'gift' ? 'Hộp mỹ thuật màu kem, giấy lót và ribbon vải theo palette' : (Str::contains(Str::lower($name), 'box') ? 'Hộp cứng màu kem mờ, lót giấy mềm và ribbon satin' : (Str::contains(Str::lower($name), 'basket') ? 'Giỏ mây lót linen, buộc ribbon màu trung tính' : 'Giấy mỹ thuật màu kem mờ hai lớp, ribbon satin và thiệp nhỏ'));
            $designStyle = ! empty($context['styles']) ? $style : (Str::contains(Str::lower($form), ['box', 'hộp']) ? 'hiện đại, tạo khối' : (Str::contains(Str::lower($form), ['giỏ', 'basket']) ? 'mộc mạc, gần gũi' : 'tự nhiên, thoáng'));
            $palette = $this->paletteForIdea($context, $index, $name);
            $mood = in_array('gentle', (array) ($context['styles'] ?? []), true)
                ? 'nhẹ nhàng, có điểm nhấn nhưng không phô trương'
                : ($index % 2 === 0 ? 'nhẹ nhàng, có điểm nhấn nhưng không phô trương' : 'chỉn chu, tươi sáng và có chiều sâu thị giác');
            $short = "{$main} phối {$supporting}, theo bố cục {$form} với tông {$palette}.";
            $contextFit = array_filter([
                ($context['occasion'] ?? null) !== null ? 'cho dịp ' . $occasion : null,
                ($context['recipient'] ?? null) !== null ? 'dành tặng ' . $recipient : null,
            ]);
            $fitSentence = $contextFit === [] ? '' : ' Hướng thiết kế này phù hợp ' . implode(' ', $contextFit) . '.';
            $full = "Hoa/quà chính là {$main}; phần phối gồm {$supporting}. Bảng màu {$palette}. Bố cục {$form}, hoàn thiện bằng {$wrapping}. Tổng thể theo phong cách {$designStyle}, tạo cảm giác {$mood}.{$fitSentence} {$customizations} BloomGift sẽ xác nhận nguồn hoa, màu thực tế, giá và khả năng thực hiện trước khi nhận yêu cầu.";
            $layoutReason = Str::contains(Str::lower($form), ['box', 'hộp']) ? 'cách xếp hoa theo lớp trong flower box' : (Str::contains(Str::lower($form), ['giỏ', 'basket']) ? 'cách dựng điểm nhấn ở giữa giỏ' : 'bố cục bó mở và phần hoa phối tạo khoảng thoáng');
            $why = 'Mình chọn ' . $main . ' phối ' . $supporting . ', với ' . $layoutReason . (! empty($contextFit) ? ' để hợp ' . implode(' ', $contextFit) : '') . '.';

            return [
                'name' => $name,
                'main_flowers' => $main,
                'supporting_elements' => $supporting,
                'color_palette' => $palette,
                'design_form' => $form,
                'wrapping' => $wrapping,
                'style' => $designStyle,
                'visual_feeling' => $mood,
                'meaning' => $recipient !== '' ? $this->meaning($occasion, $recipient) : '',
                'short_description' => $short,
                'full_description' => $full,
                'design_description' => $full,
                'why_recommended' => $why,
                'best_for' => trim(implode(' ', array_filter([
                    $contextFit !== [] ? 'Thiết kế' : null,
                    $contextFit !== [] ? implode(' ', $contextFit) : null,
                    ! empty($context['styles']) ? 'theo hướng ' . $style : null,
                ]))),
                'customization_options' => $customizations,
                'budget_fit' => $context['budget_unlimited'] ? 'BloomGift sẽ báo giá sau khi chốt hoa, kích thước và mức hoàn thiện.' : ($context['budget_max'] ? 'Có thể gửi yêu cầu theo ngân sách khoảng ' . number_format((int) $context['budget_max'], 0, ',', '.') . 'đ.' : 'Mức giá được xác nhận sau khi chọn hoa và kích thước.'),
            ];
        })->all();
    }

    private function fallbackGiftIdeas(array $context, string $query): array
    {
        $palette = $this->colorLabel($context['colors'] ?? [], (bool) ($context['color_flexible'] ?? false));
        $style = $this->styleLabel($context['styles'] ?? []);
        $recipient = $this->recipientLabel($context['recipient'] ?? null);
        $occasion = $this->occasionLabel($context['occasion'] ?? null);
        $ideas = [
            ['Tea Garden Basket', 'Trà hoa và nến thơm', 'Card lời nhắn', 'Giỏ mây nhỏ', 'Thay loại trà, mùi nến hoặc nội dung card', 'Trà hoa để thưởng thức sau khi nhận; nến thơm tạo trải nghiệm thư giãn, card giúp món quà mang dấu ấn cá nhân.'],
            ['Cacao Note Box', 'Chocolate theo khẩu vị', 'Card cá nhân hoá', 'Hộp giấy cứng', 'Đổi vị chocolate, loại chocolate hoặc card; có thể thêm hoa nếu bạn muốn', 'Chocolate là món quà nhỏ gọn, dễ chia sẻ; card cá nhân hoá làm lựa chọn này gần gũi hơn.'],
            ['Memory Keepsake Album', 'Album ảnh mini', 'Card lời nhắn', 'Hộp quà giấy mỹ thuật', 'Chọn số ảnh, màu bìa, nội dung card và phong cách hộp', 'Album lưu lại những khoảnh khắc chung và tạo dấu ấn cá nhân lâu hơn một món quà dùng một lần.'],
            ['Serein Candle Box', 'Nến thơm', 'Diêm hộp và card', 'Hộp quà lót giấy', 'Đổi mùi nến, màu hộp hoặc nội dung card; có thể thêm chocolate', 'Nến thơm phù hợp nếu người nhận thích không gian thư giãn và món quà còn được sử dụng sau dịp tặng.'],
            ['Keepsake Photo Frame', 'Khung ảnh để bàn', 'Ảnh in và card', 'Hộp trưng bày', 'Chọn kích thước ảnh, màu khung và nội dung card', 'Khung ảnh giữ lại một khoảnh khắc đáng nhớ và có thể trưng bày lâu dài.'],
            ['Letters in a Box', 'Thư kỷ niệm và bộ card', 'Phong bì theo chủ đề', 'Hộp lưu niệm nhỏ', 'Chọn số thư, màu giấy và lời nhắn; thêm ảnh in nếu muốn', 'Hộp thư tạo không gian cho nhiều lời nhắn riêng, phù hợp khi bạn muốn món quà thiên về kỷ niệm.'],
            ['Soft Pause Self-care Set', 'Muối ngâm chân và trà túi lọc', 'Khăn tay mềm', 'Hộp quà gọn', 'Đổi hương, loại trà và card theo sở thích người nhận', 'Bộ chăm sóc cá nhân phù hợp với người cần một khoảng nghỉ nhẹ nhàng sau ngày bận rộn.'],
            ['Coffee & Journal Set', 'Cà phê rang xay và sổ tay', 'Bookmark giấy', 'Hộp quà kraft', 'Chọn loại cà phê, màu sổ và lời nhắn trên bookmark', 'Cà phê và sổ tay tạo một món quà hữu dụng cho người thích ghi chép hoặc thưởng thức cà phê.'],
            ['Little Terrarium Gift', 'Terrarium mini', 'Card chăm sóc', 'Hộp bảo vệ để bàn', 'Chọn kích thước, loại cây và nội dung card chăm sóc', 'Terrarium là món quà trang trí nhỏ có thể đặt trên bàn làm việc và chăm sóc theo thời gian.'],
            ['Name Note Keychain', 'Móc khoá khắc tên', 'Tag lời nhắn', 'Hộp đựng nhỏ', 'Chọn kiểu chữ, nội dung khắc và màu tag', 'Móc khoá cá nhân hoá gọn nhẹ, phù hợp để người nhận sử dụng hằng ngày.'],
        ];
        $offset = abs(crc32(Str::lower($query . '|' . json_encode($context)))) % count($ideas);
        $ideas = [...array_slice($ideas, $offset), ...array_slice($ideas, 0, $offset)];

        return collect($ideas)->map(function (array $row) use ($palette, $style, $recipient, $occasion): array {
            [$name, $main, $supporting, $container, $personalization, $reason] = $row;
            $tail = $recipient !== '' ? ' Phù hợp để tặng ' . $recipient . ($occasion !== 'dịp đặc biệt' ? ' vào dịp ' . $occasion : '') . '.' : '';
            $description = $main . '; phối cùng ' . mb_strtolower($supporting) . '. Bảng màu ' . $palette . ', trình bày theo hướng ' . $style . '.';

            return [
                'recommendation_type' => 'CUSTOM_GIFT', 'name' => $name, 'gift_type' => $name,
                'main_items' => $main, 'supporting_items' => $supporting, 'container' => $container,
                'wrapping' => 'Giấy lót và ribbon được phối đồng bộ với hộp hoặc vật chứa.',
                'palette' => $palette, 'style' => $style, 'mood' => $style,
                'personalization' => $personalization, 'short_description' => $description,
                'full_description' => $description . ' ' . $reason . $tail,
                'why_recommended' => $reason . $tail,
                'customization_options' => $personalization,
            ];
        })->all();
    }

    private function fallbackComboIdeas(array $context, string $query): array
    {
        $flower = $this->primaryFlower($context);
        $palette = $this->colorLabel($context['colors'] ?? [], (bool) ($context['color_flexible'] ?? false));
        $rows = [
            ['Velvet Letter Combo', ucfirst($flower) . ' bó nhỏ', 'Chocolate theo khẩu vị', 'Hộp quà có card cá nhân hoá'],
            ['Tea & Tulip Moment', ucfirst($flower) . ' tông ' . $palette, 'Trà hoa và card', 'Giỏ quà gọn với lớp lót vải'],
            ['Candlelight Keepsake', ucfirst($flower) . ' phối theo mùa', 'Nến thơm và thiệp', 'Túi quà cứng kèm giấy lót'],
            ['Photo Memory Set', ucfirst($flower) . ' bó nhỏ', 'Ảnh in và chocolate', 'Hộp lưu niệm có card'],
        ];
        $offset = abs(crc32(Str::lower($query . '|' . json_encode($context)))) % count($rows);
        $rows = [...array_slice($rows, $offset), ...array_slice($rows, 0, $offset)];

        return collect($rows)->map(fn (array $row): array => [
            'recommendation_type' => 'CUSTOM_COMBO', 'name' => $row[0], 'flowers' => $row[1], 'gifts' => $row[2],
            'container' => $row[3], 'palette' => $palette, 'style' => $this->styleLabel($context['styles'] ?? []),
            'personalization' => 'Có thể đổi tông hoa, món quà phụ và nội dung card sau khi chốt lựa chọn.',
            'short_description' => $row[1] . ' kết hợp ' . mb_strtolower($row[2]) . '.',
            'full_description' => $row[1] . ' kết hợp ' . mb_strtolower($row[2]) . ', trình bày trong ' . mb_strtolower($row[3]) . ' với bảng màu ' . $palette . '.',
            'why_recommended' => $row[1] . ' tạo điểm nhấn, còn ' . mb_strtolower($row[2]) . ' bổ sung trải nghiệm quà tặng.',
            'customization_options' => 'Có thể đổi tông hoa, món quà phụ và nội dung card.',
        ])->all();
    }

    /** @param array<int, array<int, string>> $ideas @return array<int, array<int, string>> */
    private function withoutExcludedGiftIdeas(array $ideas, array $excluded): array
    {
        return array_values(array_filter($ideas, function (array $idea) use ($excluded): bool {
            return ! (in_array('chocolate', $excluded, true) && Str::contains(Str::lower(implode(' ', $idea)), 'chocolate'));
        }));
    }

    private function primaryFlower(array $context): string
    {
        $map = ['rose' => 'hoa hồng', 'tulip' => 'tulip', 'peony' => 'mẫu đơn', 'lily' => 'lily', 'hydrangea' => 'cẩm tú cầu', 'sunflower' => 'hướng dương'];
        foreach ((array) ($context['flower_preferences'] ?? []) as $preference) {
            if (isset($map[$preference]) && ! in_array($preference, (array) ($context['exclude'] ?? []), true)) {
                return $map[$preference];
            }
        }

        return in_array('rose', (array) ($context['exclude'] ?? []), true) ? 'tulip và hoa theo mùa' : 'hoa theo mùa';
    }

    /** @return array<int, string> */
    private function fallbackFlowerCompositions(array $context): array
    {
        if ((array) ($context['flower_preferences'] ?? []) !== []) {
            return array_fill(0, 4, $this->primaryFlower($context));
        }

        $color = ($context['colors'] ?? [])[0] ?? null;
        return match ($color) {
            'white' => ['mẫu đơn trắng', 'tulip trắng', 'hồng kem', 'lily trắng'],
            'pink' => ['mẫu đơn hồng phấn', 'tulip hồng pastel', 'hồng pastel', 'mao lương hồng'],
            'red' => ['hồng đỏ rượu', 'tulip đỏ', 'hồng đỏ', 'mao lương đỏ'],
            'yellow' => ['hướng dương vàng', 'tulip vàng', 'hồng vàng kem', 'mao lương vàng'],
            'purple' => ['tulip lavender', 'hồng tím lavender', 'cẩm tú cầu tím', 'mao lương tím'],
            'blue' => ['cẩm tú cầu xanh khói', 'delphinium xanh', 'tulip xanh phối hoa trắng', 'cẩm tú cầu xanh bạc'],
            default => ['mẫu đơn trắng', 'tulip tím lavender', 'hồng kem', 'hướng dương vàng'],
        };
    }

    private function paletteForIdea(array $context, int $index, string $name): string
    {
        if ((array) ($context['colors'] ?? []) !== []) {
            return $this->colorLabel($context['colors'], (bool) ($context['color_flexible'] ?? false));
        }

        $name = Str::lower($name);

        return match (true) {
            Str::contains($name, 'moonlit') => 'trắng ngà / xanh khói / xanh bạc',
            Str::contains($name, 'pastel') => 'tím lavender / trắng / xanh lá nhạt',
            Str::contains($name, 'ivory') => 'kem / champagne / trắng sữa',
            Str::contains($name, 'botanical') => 'vàng nắng / kem / xanh olive',
            default => ['trắng ngà / xanh khói / xanh bạc', 'tím lavender / trắng / xanh lá nhạt', 'kem / champagne / trắng sữa', 'vàng nắng / kem / xanh olive'][$index % 4],
        };
    }

    /** @return array<string, mixed> */
    private function legacyIntent(array $context): array
    {
        return [
            'requested_type' => $context['product_focus'] ?? 'flower',
            'budget_min' => $context['budget_min'],
            'budget_max' => $context['budget_max'],
            'budget_unlimited' => $context['budget_unlimited'],
            'colors' => $context['colors'],
            'occasion' => $this->occasionLabel($context['occasion']),
            'recipient' => $this->recipientLabel($context['recipient']),
            'style' => $this->styleLabel($context['styles']),
            'excluded_terms' => $context['exclude'],
            'primary_intent' => $context['primary_intent'] ?? null,
            'target_entity' => $context['target_entity'] ?? null,
            'target_entity_label' => ConversationContext::entityLabel($context['target_entity'] ?? null),
            'request_scope' => $context['request_scope'] ?? null,
            'query_mode' => $context['query_mode'] ?? null,
            'allow_category_expansion' => $context['allow_category_expansion'] ?? true,
            'inventory_only' => $context['inventory_only'] ?? false,
            'suppress_suggestions' => $context['suppress_suggestions'] ?? false,
            'answer_mode' => ($context['target_entity'] ?? null) ? 'direct_entity_answer' : (($context['primary_intent'] ?? null) === 'recommend_products' ? 'recommendation' : 'category_answer'),
            'strict_entity' => ($context['target_entity'] ?? null) !== null,
            'negative_constraints' => $context['negative_constraints'] ?? $context['exclude'],
        ];
    }

    private function fallbackMessage(array $context, array $patch, int $suggestionCount): string
    {
        $focus = $context['product_focus'] ?? 'flower';
        $itemType = mb_strtolower($this->typeLabel($focus));
        if (($context['occasion'] ?? null) !== null) {
            $intro = 'Mình đang tìm ' . $itemType . ' cho dịp ' . $this->occasionLabel($context['occasion']);
        } elseif (($context['recipient'] ?? null) !== null) {
            $intro = 'Mình đang tìm ' . $itemType . ' cho ' . $this->recipientLabel($context['recipient']);
        } else {
            $intro = 'Mình đang tìm ' . $itemType . ' theo những gì bạn ưu tiên';
        }
        if (($context['occasion'] ?? null) !== null && ($context['recipient'] ?? null) !== null) $intro .= ' dành tặng ' . $this->recipientLabel($context['recipient']);
        $preferences = [];
        if (! empty($context['colors'])) $preferences[] = 'tông ' . $this->colorLabel($context['colors'], false);
        if (! empty($context['styles'])) $preferences[] = 'phong cách ' . $this->styleLabel($context['styles']);
        if (! ($context['budget_unlimited'] ?? false) && ($context['budget_max'] ?? null) !== null) $preferences[] = 'ngân sách đến khoảng ' . number_format((int) $context['budget_max'], 0, ',', '.') . 'đ';
        if (($patch['budget']['action'] ?? null) === 'clear') $preferences[] = 'không giới hạn ngân sách';
        if ($preferences !== []) $intro .= ', ưu tiên ' . implode(' và ', $preferences);
        if ($suggestionCount === 0) return $intro . '. Hiện chưa có lựa chọn mới khớp đủ yêu cầu; bạn có thể nới một tiêu chí để mình tìm tiếp.';

        $countLabel = $suggestionCount === 1 ? '1 lựa chọn' : $suggestionCount . ' lựa chọn';
        $refresh = ($patch['recommendations']['action'] ?? null) === 'refresh' ? 'Mình đã loại các gợi ý vừa xem. ' : '';

        return $refresh . $intro . '. Mình tìm được ' . $countLabel . ' để bạn tham khảo; giá và tình trạng hàng có sẵn lấy từ BloomGift, còn thiết kế riêng cần được xác nhận trước khi nhận yêu cầu.';
    }

    private function isExclusionOnlyTurn(string $query, array $patch): bool
    {
        if (($patch['exclude']['action'] ?? null) !== 'update') return false;
        $query = Str::lower(trim($query));
        $negativeLead = preg_match('/^(?:tôi\s+)?(?:không|khỏi|đừng|bỏ|tránh)\b/u', $query) === 1;

        return $negativeLead && ! Str::contains($query, ['tìm ', 'gợi ý ', 'cho tôi', 'mua ', 'xem ', 'đề xuất']);
    }

    private function exclusionLabel(string $item): string
    {
        return ConversationContext::entityLabel($item) ?? match ($item) {
            'red' => 'màu đỏ', 'flowers' => 'hoa', 'round_bouquet' => 'kiểu bó tròn', default => str_replace('_', ' ', $item),
        };
    }

    /** @param array<int, string> $labels */
    private function joinLabels(array $labels): string
    {
        if (count($labels) < 2) return $labels[0] ?? 'các loại bạn vừa nêu';

        return implode(' và ', [...array_slice($labels, 0, -1), (string) end($labels)]);
    }

    private function suppressedResponse(array $context, array $patch, array $recentProductIds, array $recentConceptKeys): array
    {
        $strict = is_string($context['target_entity'] ?? null) && ($context['allow_category_expansion'] ?? true) === false;
        if ($strict) {
            $all = $this->products->candidates($context, []);
            $unseen = $all->reject(fn ($product): bool => in_array((int) $product->id, $recentProductIds, true));
            $label = ConversationContext::entityLabel($context['target_entity']) ?? 'sản phẩm';
            if ($unseen->isNotEmpty()) {
                $message = 'Được, mình sẽ không gợi ý loại quà khác. Vẫn còn mẫu ' . $label . ' phù hợp, mình dừng ở đây theo ý bạn.';
            } elseif ($all->isNotEmpty()) {
                $names = $all->take(3)->pluck('name')->map(fn (string $name): string => '“' . $name . '”')->implode(', ');
                $message = 'Được. Hiện BloomGift chưa còn mẫu ' . $label . ' khác phù hợp ngoài ' . $names . '. Mình dừng gợi ý tại đây.';
            } else {
                $message = 'Được. Hiện BloomGift chưa còn mẫu ' . $label . ' phù hợp. Mình dừng gợi ý tại đây.';
            }
        } else {
            $message = 'Được, mình sẽ dừng gợi ý tại đây và không chuyển sang loại sản phẩm khác.';
        }

        return $this->directResponse($message, $context, $patch, $recentProductIds, $recentConceptKeys);
    }

    private function directResponse(string $message, array $context, array $patch, array $recentProductIds, array $recentConceptKeys): array
    {
        return [
            'message' => $message, 'follow_up_question' => null, 'quick_replies' => [],
            'context' => $context, 'context_patch' => $patch, 'intent' => $this->legacyIntent($context),
            'suggestions' => [], 'in_store_suggestions' => [], 'custom_suggestions' => [], 'guidance' => null,
            'provider' => ['status' => 'not_needed', 'active' => false, 'model_response_received' => false, 'structured_output_valid' => false],
            'recommendation_mode' => 'direct_answer',
            'web_search' => ['status' => 'not_needed', 'active' => false, 'requested_by_ai' => false, 'sources' => []],
            'recent_product_ids' => $recentProductIds, 'recommendation_keys' => [],
            'recent_custom_concept_keys' => $recentConceptKeys, 'product_catalog_exhausted' => false,
            'selected_concept' => [], 'selected_item' => null, 'last_response_items' => [],
        ];
    }

    private function requestedOrdinal(string $query, int $itemCount): ?int
    {
        $query = Str::lower(trim($query));
        if (preg_match('/\b(?:mẫu|cái)\s*(?:thứ\s*|số\s*)?(\d+)\b/u', $query, $match)) return max(1, (int) $match[1]);
        if (preg_match('/\b(?:mẫu|cái)\s+(?:thứ\s+)?(đầu tiên|đầu|một|hai|ba|cuối)\b/u', $query, $match)) {
            return match ($match[1]) {
                'đầu tiên', 'đầu', 'một' => 1,
                'hai' => 2,
                'ba' => 3,
                'cuối' => max(1, $itemCount),
                default => null,
            };
        }

        return null;
    }

    private function ordinalResponse(int $ordinal, array $lastResponseItems, array $previousSelectedItem, array $context, array $patch, array $recentProductIds, array $recentConceptKeys): array
    {
        if ($ordinal < 1 || $ordinal > count($lastResponseItems)) {
            $prompt = count($lastResponseItems) === 1
                ? 'Ở lượt vừa rồi mình chỉ đưa 1 mẫu. Bạn muốn chọn mẫu đó hay muốn mình gợi ý thêm vài lựa chọn?'
                : 'Ở lượt vừa rồi mình chỉ đưa ' . count($lastResponseItems) . ' mẫu. Bạn muốn chọn mẫu đó hay muốn mình đưa thêm lựa chọn?';
            $response = $this->directResponse(
                $prompt,
                $context, $patch, $recentProductIds, $recentConceptKeys
            );
            $response['recommendation_mode'] = 'ordinal_invalid';
            $response['selected_item'] = $previousSelectedItem ?: null;
            $response['quick_replies'] = count($lastResponseItems) === 1 ? ['Chọn mẫu 1', 'Gợi ý thêm vài lựa chọn'] : [];

            return $response;
        }

        $item = $lastResponseItems[$ordinal - 1];
        if (! is_array($item) || ! in_array($item['kind'] ?? null, ['product', 'custom'], true) || ! is_string($item['name'] ?? null)) {
            return $this->ordinalResponse(0, [], $previousSelectedItem, $context, $patch, $recentProductIds, $recentConceptKeys);
        }
        if (($item['kind'] ?? null) === 'product') {
            $productId = (int) ($item['product_id'] ?? 0);
            $product = $this->products->candidates($context, [])->first(fn ($candidate): bool => (int) $candidate->id === $productId);
            if ($product === null) {
                return $this->ordinalResponse(0, [], $previousSelectedItem, $context, $patch, $recentProductIds, $recentConceptKeys);
            }
            $item = $this->products->card($product, $context);
            $item['recommendation_key'] = 'product:' . $product->id;
        } elseif ($this->customMatchesExclusions($item, $context)) {
            return $this->ordinalResponse(0, [], $previousSelectedItem, $context, $patch, $recentProductIds, $recentConceptKeys);
        }

        $concept = ($item['kind'] ?? null) === 'custom' ? $this->conceptFromCard($item) : [];
        $response = $this->directResponse('Được, mình đã ghi nhận mẫu thứ ' . $ordinal . ': “' . $item['name'] . '”. Bạn có thể yêu cầu mình chỉnh mẫu này.', $context, $patch, $recentProductIds, $recentConceptKeys);
        $response['recommendation_mode'] = 'selection';
        $response['selected_item'] = $item;
        $response['selected_concept'] = $concept;
        $response['suggestions'] = [$item];
        $response['in_store_suggestions'] = ($item['kind'] ?? null) === 'product' ? [$item] : [];
        $response['custom_suggestions'] = ($item['kind'] ?? null) === 'custom' ? [$item] : [];
        $response['recommendation_keys'] = [$item['recommendation_key'] ?? ''];
        $response['last_response_items'] = [$item];

        return $response;
    }

    private function selectedItemFromMemory(array $selectedItem, array $lastResponseItems, array $context): array
    {
        $key = $selectedItem['recommendation_key'] ?? null;
        if (! is_string($key) || ! in_array($key, array_column($lastResponseItems, 'recommendation_key'), true)) return [];
        $item = collect($lastResponseItems)->firstWhere('recommendation_key', $key);
        if (! is_array($item) || ($item['kind'] ?? null) !== ($selectedItem['kind'] ?? null)) return [];
        if (($item['kind'] ?? null) === 'product') {
            $productId = (int) ($item['product_id'] ?? 0);
            $product = $this->products->candidates($context, [])->first(fn ($candidate): bool => (int) $candidate->id === $productId);
            if ($product === null) return [];
            $item = $this->products->card($product, $context);
            $item['recommendation_key'] = 'product:' . $product->id;
        }

        return $item;
    }

    private function isEditRequest(string $query): bool
    {
        return Str::contains(Str::lower($query), ['đổi ', 'chỉnh ', 'thêm ', 'bỏ ', 'chuyển ', 'giữ ']);
    }

    private function editSelectedResponse(array $selectedItem, array $context, array $patch, string $query, array $recentProductIds, array $recentConceptKeys): array
    {
        $source = ($selectedItem['kind'] ?? null) === 'custom'
            ? $this->conceptFromCard($selectedItem)
            : $this->conceptFromProduct($selectedItem, $context);
        if ($source === []) {
            return $this->directResponse('Mình không tìm thấy mẫu đã chọn trong các gợi ý vừa hiển thị. Bạn chọn lại mẫu trước khi yêu cầu chỉnh nhé.', $context, $patch, $recentProductIds, $recentConceptKeys);
        }

        $designContext = $context;
        $designContext['product_focus'] = match ($source['recommendation_type'] ?? null) {
            'CUSTOM_GIFT' => 'gift', 'CUSTOM_COMBO' => 'set', default => 'flower',
        };
        $variant = $this->selectedVariant($source, $designContext, $query);
        $customCards = $this->customCards([$variant], $designContext, $query, 0, [], [], [], [], false);
        if ($customCards === []) {
            return $this->directResponse('Mình chưa thể tạo phiên bản chỉnh sửa mà vẫn giữ đúng các điều kiện bạn đã nêu. Bạn thử mô tả lại chi tiết muốn thay đổi nhé.', $context, $patch, $recentProductIds, $recentConceptKeys);
        }

        $card = [...$customCards[0], 'recommendation_key' => 'custom:' . $this->conceptFingerprint($customCards[0])];
        $response = $this->directResponse(
            ($selectedItem['kind'] ?? null) === 'product'
                ? 'Mẫu có sẵn không đổi cấu hình trực tiếp. Mình đã tạo một phương án đặt riêng dựa trên “' . $selectedItem['name'] . '” và chỉnh theo yêu cầu của bạn.'
                : 'Mình đã chỉnh concept bạn chọn theo yêu cầu, đồng thời giữ lại các chi tiết không cần thay đổi.',
            $context, $patch, $recentProductIds, $recentConceptKeys
        );
        $response['recommendation_mode'] = 'selected_concept_edit';
        $response['selected_item'] = $card;
        $response['selected_concept'] = $this->conceptFromCard($card);
        $response['suggestions'] = [$card];
        $response['custom_suggestions'] = [$card];
        $response['recommendation_keys'] = [$card['recommendation_key']];
        $response['last_response_items'] = [$card];

        return $response;
    }

    private function conceptFromProduct(array $product, array $context): array
    {
        $type = $product['recommendation_type'] ?? 'FLOWER';
        $details = is_array($product['details'] ?? null) ? $product['details'] : [];
        if ($type === 'GIFT') {
            return [
                'name' => $product['name'], 'recommendation_type' => 'CUSTOM_GIFT', 'gift_type' => $product['name'],
                'main_items' => $product['name'], 'supporting_items' => 'Thiệp lời nhắn cá nhân hoá',
                'container' => 'Hộp quà theo màu sắc bạn chọn', 'wrapping' => 'Giấy gói và ribbon phối đồng bộ',
                'palette' => $details['color'] ?? 'kem và trắng', 'style' => $this->styleLabel($context['styles'] ?? []),
                'personalization' => 'Có thể tùy chỉnh cách gói và nội dung thiệp.',
                'design_description' => 'Phương án đặt riêng lấy cảm hứng từ sản phẩm có sẵn “' . $product['name'] . '”. ' . ($product['description'] ?? ''),
                'why_recommended' => 'Giữ món quà chính bạn đã chọn, chuyển phần hoàn thiện sang thiết kế riêng.',
            ];
        }
        if ($type === 'COMBO') {
            return [
                'name' => $product['name'], 'recommendation_type' => 'CUSTOM_COMBO',
                'flowers' => $details['components'] ?? $product['name'], 'gifts' => 'Thiệp lời nhắn cá nhân hoá',
                'container' => 'Hộp phối hoa và quà', 'palette' => $details['color'] ?? 'kem và trắng',
                'style' => $this->styleLabel($context['styles'] ?? []), 'personalization' => 'Có thể tùy chỉnh hoa, quà và cách gói.',
                'design_description' => 'Phương án đặt riêng lấy cảm hứng từ set có sẵn “' . $product['name'] . '”. ' . ($product['description'] ?? ''),
                'why_recommended' => 'Giữ cảm hứng từ set bạn chọn và điều chỉnh phần hoàn thiện theo yêu cầu.',
            ];
        }

        return [
            'name' => $product['name'], 'recommendation_type' => 'CUSTOM_FLOWER',
            'main_flowers' => $details['components'] ?? $product['name'],
            'supporting_elements' => 'Hoa phụ phối tông nhẹ và lá xanh theo mùa',
            'color_palette' => $details['color'] ?? 'trắng kem', 'design_form' => 'Bó hoa theo dáng của mẫu đã chọn',
            'wrapping' => 'Giấy mỹ thuật và ribbon phối cùng tông', 'style' => $this->styleLabel($context['styles'] ?? []),
            'visual_feeling' => 'giữ tinh thần của mẫu bạn chọn',
            'design_description' => 'Phương án đặt riêng lấy cảm hứng từ hoa có sẵn “' . $product['name'] . '”. ' . ($product['description'] ?? ''),
            'why_recommended' => 'Giữ loại hoa và tinh thần mẫu bạn chọn, chỉ thay đổi phần phối màu hoặc hoàn thiện.',
            'customization_options' => 'Có thể chỉnh màu, dáng bó và hoa phối.',
        ];
    }

    private function conceptFromCard(array $card): array
    {
        $details = is_array($card['details'] ?? null) ? $card['details'] : [];
        $concept = [
            'name' => $card['name'] ?? null,
            'recommendation_type' => $card['recommendation_type'] ?? null,
            'gift_type' => $details['gift_type'] ?? null,
            'main_items' => $details['main_items'] ?? null,
            'supporting_items' => $details['supporting_items'] ?? null,
            'main_flowers' => $details['main_flowers'] ?? null,
            'supporting_flowers' => $details['supporting_flowers'] ?? null,
            'supporting_elements' => $details['supporting_flowers'] ?? $details['components'] ?? null,
            'flowers' => $details['flowers'] ?? null,
            'gifts' => $details['gifts'] ?? null,
            'container' => $details['container'] ?? null,
            'personalization' => $details['personalization'] ?? null,
            'color_palette' => $details['color_palette'] ?? null,
            'palette' => $details['color_palette'] ?? null,
            'design_form' => $details['design_form'] ?? null,
            'wrapping' => $details['wrapping'] ?? null,
            'style' => $details['style'] ?? null,
            'visual_feeling' => $details['visual_feeling'] ?? null,
            'meaning' => $details['meaning'] ?? null,
            'design_description' => $card['full_description'] ?? $details['design_description'] ?? $card['description'] ?? null,
            'why_recommended' => $card['why_recommended'] ?? null,
            'customization_options' => $details['customization_options'] ?? null,
        ];

        return $this->sanitizeSelectedConcept(array_filter($concept, 'is_string'));
    }

    private function guidance(array $context, array $productCards): ?string
    {
        if (! ($context['budget_unlimited'] ?? false) && $context['budget_max'] !== null && $productCards === []) {
            return 'BloomGift chưa có mẫu đang bán phù hợp ngân sách hiện tại. Bạn vẫn có thể chọn thiết kế riêng theo mức chi tiêu mong muốn.';
        }

        return null;
    }

    private function customOrderUrl(string $name, array $context, array $idea, string $query): string
    {
        $messageParts = array_filter([
            "Mẫu mong muốn: {$name}", "Nhu cầu gốc: {$query}",
            'Loại thiết kế: ' . ($idea['recommendation_type'] ?? ''),
            'Thành phần chính: ' . ($idea['main_component'] ?? $idea['main_flowers'] ?? $idea['main_items'] ?? ''),
            'Phối thêm: ' . ($idea['supporting_component'] ?? $idea['supporting_flowers'] ?? $idea['supporting_elements'] ?? $idea['supporting_items'] ?? $idea['gifts'] ?? ''),
            'Màu sắc: ' . ($idea['palette'] ?? $idea['color_palette'] ?? ''),
            'Phong cách: ' . ($idea['style'] ?? ''),
            'Dáng / vật chứa: ' . ($idea['container'] ?? $idea['bouquet_shape'] ?? $idea['design_form'] ?? ''),
            'Cách gói: ' . ($idea['wrapping'] ?? ''),
            'Mô tả: ' . ($idea['full_description'] ?? $idea['design_description'] ?? ''),
            'Nguồn: Trợ lý BloomGift AI.',
        ], fn (string $part): bool => ! str_ends_with($part, ': '));
        if (($context['occasion'] ?? null) !== null) array_splice($messageParts, 2, 0, ['Dịp: ' . $this->occasionLabel($context['occasion'])]);
        if (($context['recipient'] ?? null) !== null) array_splice($messageParts, 3, 0, ['Người nhận: ' . $this->recipientLabel($context['recipient'])]);
        $message = implode("\n", $messageParts);

        return route('custom.order', array_filter([
            'occasion' => $this->occasionLabel($context['occasion'] ?? null),
            'flower_type' => in_array($idea['recommendation_type'] ?? '', ['CUSTOM_FLOWER', 'flower'], true) ? Str::limit((string) ($idea['main_component'] ?? $idea['main_flowers'] ?? ''), 255, '') : null,
            'budget' => $context['budget_unlimited'] ? null : $context['budget_max'],
            'message' => $message,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    private function typeLabel(string $type): string { return ['gift' => 'Quà tặng', 'set' => 'Set hoa & quà', 'flower' => 'Hoa'][$type] ?? 'Thiết kế theo yêu cầu'; }
    private function occasionLabel(?string $occasion): string { return ['birthday' => 'sinh nhật', 'anniversary' => 'kỷ niệm', 'graduation' => 'tốt nghiệp', 'opening' => 'khai trương', 'thanks' => 'cảm ơn', 'apology' => 'xin lỗi', 'valentine' => 'Valentine', 'international_womens_day' => '8/3', 'vietnamese_womens_day' => '20/10', 'congratulations' => 'chúc mừng', 'get_well' => 'thăm hỏi'][$occasion] ?? 'dịp đặc biệt'; }
    private function recipientLabel(?string $recipient): string { return ['girlfriend' => 'bạn gái', 'boyfriend' => 'bạn trai', 'lover' => 'người yêu', 'mother' => 'mẹ', 'father' => 'bố', 'friend' => 'bạn bè', 'colleague' => 'đồng nghiệp'][$recipient] ?? ''; }

    private function shouldAskRecipient(string $query, array $context): bool
    {
        return mb_strlen(trim($query)) <= 28
            && ($context['occasion'] ?? null) !== null
            && ($context['recipient'] ?? null) === null
            && ($context['product_focus'] ?? null) === null
            && ($context['budget_max'] ?? null) === null
            && ($context['colors'] ?? []) === []
            && ($context['styles'] ?? []) === [];
    }

    private function validatedAssistantMessage(string $message, array $productCards, array $context, array $patch, int $suggestionCount): string
    {
        $normalized = Str::lower($message);
        if (preg_match('/\b(?:em|tôi|chúng tôi|anh|chị)\b/u', $normalized)
            || ! preg_match('/[ăâđêôơưàáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹ]/u', $normalized)) {
            return $this->validatedMessageFallback($context, $patch, $suggestionCount, 'language_or_pronoun_mismatch');
        }
        if (preg_match('/\b\d+\s+(?:gợi ý|hướng|lựa chọn|mẫu)\b/u', $message)) {
            if ($suggestionCount === 0) {
                return $this->validatedMessageFallback($context, $patch, $suggestionCount, 'incorrect_suggestion_count');
            }
            $message = preg_replace_callback('/\b\d+\s+(gợi ý|hướng|lựa chọn|mẫu)\b/iu', fn (array $match): string => $suggestionCount . ' ' . $match[1], $message) ?? $message;
            $normalized = Str::lower($message);
        }
        $staleOccasions = [
            'birthday' => ['sinh nhật', 'birthday'],
            'anniversary' => ['kỷ niệm', 'anniversary'],
            'valentine' => ['valentine'],
            'international_womens_day' => ['8/3', 'quốc tế phụ nữ'],
            'vietnamese_womens_day' => ['20/10', 'phụ nữ việt nam'],
            'opening' => ['khai trương'],
            'graduation' => ['tốt nghiệp'],
        ];
        foreach ($staleOccasions as $occasion => $terms) {
            if ($occasion === ($context['occasion'] ?? null)) continue;
            foreach ($terms as $term) {
                if (Str::contains($normalized, $term)) return $this->validatedMessageFallback($context, $patch, $suggestionCount, 'stale_occasion');
            }
        }
        if (($context['recipient'] ?? null) === null && Str::contains($normalized, ['người nhận', 'dành cho người nhận'])) {
            return $this->validatedMessageFallback($context, $patch, $suggestionCount, 'unspecified_recipient');
        }
        foreach ($productCards as $card) {
            $name = trim((string) ($card['name'] ?? ''));
            if ($name !== '' && Str::contains($normalized, Str::lower($name))) {
                return $this->validatedMessageFallback($context, $patch, $suggestionCount, 'repeated_product_title');
            }
        }

        return $message;
    }

    private function validatedMessageFallback(array $context, array $patch, int $suggestionCount, string $reason): string
    {
        Log::info('BloomGift AI answer validation fallback', [
            'AI_PROVIDER_ACTIVE' => config('services.bloomgift_ai.driver'),
            'AI_MODEL' => config('services.bloomgift_ai.model'),
            'AI_FALLBACK_USED' => true,
            'reason' => $reason,
        ]);

        return $this->fallbackMessage($context, $patch, $suggestionCount);
    }

    private function isCoherentConcept(array $idea, array $context): bool
    {
        $focus = $context['product_focus'] ?? 'flower';
        $ideaText = Str::lower(implode(' ', array_filter($idea, 'is_string')));
        if ($focus === 'gift') {
            foreach (['name', 'main_items', 'supporting_items', 'container', 'palette', 'style', 'personalization', 'short_description', 'full_description', 'why_recommended', 'customization_options'] as $field) {
                if (! is_string($idea[$field] ?? null) || trim($idea[$field]) === '') return false;
            }
            if (Str::contains($ideaText, ['flower box', 'xếp hoa theo lớp', 'bố cục bó hoa', 'hoa phụ', 'nguồn hoa'])) return false;
        } elseif ($focus === 'set') {
            foreach (['name', 'flowers', 'gifts', 'container', 'palette', 'style', 'personalization', 'short_description', 'full_description', 'why_recommended', 'customization_options'] as $field) {
                if (! is_string($idea[$field] ?? null) || trim($idea[$field]) === '') return false;
            }
        } else {
            foreach (['name', 'main_flowers', 'color_palette', 'style', 'short_description', 'full_description', 'why_recommended', 'customization_options'] as $field) {
                if (! is_string($idea[$field] ?? null) || trim($idea[$field]) === '') return false;
            }
        }

        $form = Str::lower((string) ($idea['design_form'] ?? ''));
        $wrapping = Str::lower((string) ($idea['wrapping'] ?? ''));
        $reason = Str::lower((string) ($idea['why_recommended'] ?? ''));
        $description = Str::lower(implode(' ', [
            (string) ($idea['short_description'] ?? ''),
            (string) ($idea['full_description'] ?? $idea['design_description'] ?? ''),
        ]));
        $isBouquet = Str::contains($form, ['bouquet', 'bó hoa', 'dáng bó']);
        $isBox = Str::contains($form, ['flower box', 'hộp hoa', 'dáng hộp']);
        $isBasket = Str::contains($form, ['giỏ hoa', 'dáng giỏ', 'basket']);

        if ($isBouquet && (Str::contains($reason, ['hộp', 'box', 'giỏ', 'basket']) || Str::contains($wrapping, ['hộp cứng', 'giỏ mây', 'basket']))) return false;
        if ($isBox && (Str::contains($wrapping, ['giấy gói nhiều lớp', 'giấy mỹ thuật hai lớp']) || Str::contains($reason, ['giỏ mây', 'dáng bó']))) return false;
        if ($isBasket && (Str::contains($wrapping, ['hộp cứng']) || Str::contains($reason, ['flower box', 'dáng bó']))) return false;

        $all = $description . ' ' . $reason;
        if (($context['occasion'] ?? null) !== 'birthday' && Str::contains($all, ['sinh nhật', 'birthday'])) return false;
        if (($context['occasion'] ?? null) !== 'valentine' && Str::contains($all, ['valentine'])) return false;
        if (in_array('rose', $context['exclude'] ?? [], true) && Str::contains(Str::lower(($idea['name'] ?? '') . ' ' . ($idea['main_flowers'] ?? '') . ' ' . ($idea['supporting_elements'] ?? '')), ['hoa hồng', 'rose'])) return false;

        $recipientPhrases = [
            'girlfriend' => ['dành tặng bạn gái', 'tặng bạn gái', 'cho bạn gái'],
            'boyfriend' => ['dành tặng bạn trai', 'tặng bạn trai', 'cho bạn trai'],
            'lover' => ['dành tặng mẹ', 'tặng mẹ', 'cho mẹ', 'dành tặng bố', 'tặng bố', 'cho bố', 'dành tặng bạn bè', 'tặng bạn bè', 'cho bạn bè', 'dành tặng đồng nghiệp', 'tặng đồng nghiệp', 'cho đồng nghiệp'],
            'mother' => ['dành tặng người yêu', 'tặng người yêu', 'cho người yêu', 'dành tặng bạn gái', 'dành tặng bạn trai', 'dành tặng bố'],
            'father' => ['dành tặng người yêu', 'tặng người yêu', 'cho người yêu', 'dành tặng bạn gái', 'dành tặng bạn trai', 'dành tặng mẹ'],
            'friend' => ['dành tặng người yêu', 'tặng người yêu', 'dành tặng mẹ', 'dành tặng bố', 'dành tặng đồng nghiệp'],
            'colleague' => ['dành tặng người yêu', 'tặng người yêu', 'dành tặng mẹ', 'dành tặng bố', 'dành tặng bạn bè'],
        ];
        foreach ($recipientPhrases[$context['recipient'] ?? ''] ?? [] as $phrase) {
            if (Str::contains($all, $phrase)) return false;
        }

        $composition = Str::lower(implode(' ', [(string) ($idea['main_flowers'] ?? ''), (string) ($idea['supporting_flowers'] ?? $idea['supporting_elements'] ?? ''), (string) ($idea['main_items'] ?? ''), (string) ($idea['supporting_items'] ?? ''), (string) ($idea['flowers'] ?? ''), (string) ($idea['gifts'] ?? '')]));
        if (($context['product_focus'] ?? null) === 'flower' && Str::contains($composition, ['chocolate', 'socola', 'nến thơm', 'gấu bông'])) return false;
        if (($context['product_focus'] ?? null) === 'gift' && Str::contains($composition, ['hoa hồng', 'tulip', 'cẩm tú cầu', 'hướng dương', 'bó hoa', 'flower box'])) return false;

        if (($context['colors'] ?? []) !== []) {
            $palette = Str::lower((string) ($idea['palette'] ?? $idea['color_palette'] ?? ''));
            $colorTerms = ['red' => ['đỏ', 'red'], 'pink' => ['hồng', 'pink'], 'white' => ['trắng', 'kem', 'white', 'ivory'], 'yellow' => ['vàng', 'yellow'], 'purple' => ['tím', 'lavender', 'purple'], 'blue' => ['xanh', 'blue'], 'coral' => ['coral', 'san hô'], 'pastel' => ['pastel', 'phấn']];
            foreach ($context['colors'] as $color) {
                if (! collect($colorTerms[$color] ?? [$color])->contains(fn (string $term): bool => Str::contains($palette, $term))) return false;
            }
        }

        return true;
    }

    /** Reject model and fallback concepts that contain any active exclusion. */
    private function customMatchesExclusions(array $idea, array $context): bool
    {
        $composition = Str::lower(implode(' ', array_filter([
            $idea['name'] ?? null, $idea['main_flowers'] ?? null, $idea['supporting_flowers'] ?? null,
            $idea['supporting_elements'] ?? null, $idea['flowers'] ?? null, $idea['gifts'] ?? null,
            $idea['main_items'] ?? null, $idea['supporting_items'] ?? null,
            data_get($idea, 'details.components'), data_get($idea, 'details.main_flowers'),
            data_get($idea, 'details.supporting_flowers'), data_get($idea, 'details.flowers'),
            data_get($idea, 'details.gifts'), data_get($idea, 'details.main_items'),
        ], 'is_string')));

        foreach ((array) ($context['exclude'] ?? []) as $exclusion) {
            if ($exclusion === 'red' && Str::contains($composition, ['đỏ', 'red'])) return true;
            if ($exclusion === 'flowers' && Str::contains($composition, ['hoa hồng', 'rose', 'tulip', 'cẩm tú cầu', 'hướng dương', 'bó hoa', 'flower', 'hoa ly', 'lily', 'mẫu đơn', 'peony'])) return true;
            if ($exclusion === 'round_bouquet' && Str::contains($composition, ['bó tròn', 'round bouquet'])) return true;
            if ($exclusion === 'rose' && preg_match('/hoa\s+hồng|\bhồng\s+(?:ecuador|đỏ|kem|pastel|phấn|trắng)|\brose(?:s)?\b/u', $composition)) return true;
            if ($exclusion !== 'rose' && isset(ConversationContext::ENTITY_TERMS[$exclusion]) && ConversationContext::entityMatches($exclusion, $composition)) return true;
        }

        return false;
    }

    private function colorLabel(array $colors, bool $flexible): string
    {
        $labels = ['red' => 'đỏ', 'pink' => 'hồng', 'white' => 'trắng / kem', 'yellow' => 'vàng', 'purple' => 'tím / lavender', 'blue' => 'xanh', 'pastel' => 'pastel', 'coral' => 'coral'];
        $values = array_map(fn (string $color): string => $labels[$color] ?? $color, $colors);

        return $values === [] ? ($flexible ? 'phối màu hài hoà' : 'theo tư vấn của BloomGift') : implode(', ', $values);
    }

    private function styleLabel(array $styles): string
    {
        $labels = ['premium' => 'sang trọng', 'romantic' => 'lãng mạn', 'gentle' => 'nhẹ nhàng', 'cute' => 'dễ thương', 'minimal' => 'tối giản', 'elegant' => 'thanh lịch', 'vibrant' => 'rực rỡ', 'korean' => 'Hàn Quốc', 'youthful' => 'trẻ trung'];
        $values = array_map(fn (string $style): string => $labels[$style] ?? $style, $styles);

        return $values === [] ? 'tinh tế' : implode(', ', $values);
    }

    private function meaning(string $occasion, string $recipient): string
    {
        if (in_array($recipient, ['mẹ', 'bố'], true)) return 'Thể hiện sự biết ơn và quan tâm.';
        if (in_array($recipient, ['bạn gái', 'bạn trai', 'người yêu'], true)) return 'Gửi lời yêu thương và sự trân trọng.';
        return "Gửi lời chúc ý nghĩa cho dịp {$occasion}.";
    }
}

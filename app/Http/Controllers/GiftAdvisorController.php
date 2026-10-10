<?php

namespace App\Http\Controllers;

use App\Services\GiftAdvisorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GiftAdvisorController extends Controller
{
    public function suggest(Request $request, GiftAdvisorService $advisor): JsonResponse|StreamedResponse
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'max:500'],
            // Context is supplied by the current browser chat only. It is not
            // stored in the session, so opening BloomGift again starts fresh.
            'context' => ['nullable', 'array', 'max:60'],
            'context.*' => ['string', 'max:500'],
            'last_response_items' => ['nullable', 'array', 'max:8'],
            'last_response_items.*' => ['array'],
            'last_response_items.*.kind' => ['required', 'in:product,custom'],
            'last_response_items.*.name' => ['required', 'string', 'max:255'],
            'last_response_items.*.product_id' => ['nullable', 'integer', 'min:1'],
            'last_response_items.*.recommendation_key' => ['nullable', 'regex:/^(?:product:[1-9][0-9]*|custom:[a-f0-9]{40})$/'],
            'last_response_items.*.recommendation_type' => ['nullable', 'in:FLOWER,GIFT,COMBO,PRODUCT,CUSTOM_FLOWER,CUSTOM_GIFT,CUSTOM_COMBO'],
            'last_response_items.*.full_description' => ['nullable', 'string', 'max:1200'],
            'last_response_items.*.description' => ['nullable', 'string', 'max:500'],
            'last_response_items.*.why_recommended' => ['nullable', 'string', 'max:500'],
            'last_response_items.*.details' => ['nullable', 'array:type,components,color,occasion,recipient,full_description,design_description,main_flowers,supporting_flowers,greenery,color_palette,design_form,wrapping,style,visual_feeling,meaning,gift_type,main_items,supporting_items,container,flowers,gifts,personalization,customization_options'],
            'last_response_items.*.details.*' => ['nullable', 'string', 'max:1200'],
            'selected_item' => ['nullable', 'array'],
            'selected_item.kind' => ['nullable', 'in:product,custom'],
            'selected_item.product_id' => ['nullable', 'integer', 'min:1'],
            'selected_item.recommendation_key' => ['nullable', 'regex:/^(?:product:[1-9][0-9]*|custom:[a-f0-9]{40})$/'],
            'selected_item.name' => ['nullable', 'string', 'max:255'],
            'recent_product_ids' => ['nullable', 'array', 'max:60'],
            'recent_product_ids.*' => ['integer', 'min:1'],
            'recent_concept_names' => ['nullable', 'array', 'max:60'],
            'recent_concept_names.*' => ['string', 'max:255'],
            'recent_custom_concept_keys' => ['nullable', 'array', 'max:60'],
            'recent_custom_concept_keys.*' => ['regex:/^[a-f0-9]{40}$/'],
            'rejected_suggestions' => ['nullable', 'array', 'max:60'],
            'rejected_suggestions.*' => ['regex:/^(?:product:[1-9][0-9]*|custom:[a-f0-9]{40})$/'],
            'selected_concept' => ['nullable', 'array'],
            'selected_concept.name' => ['nullable', 'string', 'max:255'],
            'selected_concept.recommendation_type' => ['nullable', 'in:CUSTOM_FLOWER,CUSTOM_GIFT,CUSTOM_COMBO'],
            'selected_concept.gift_type' => ['nullable', 'string', 'max:300'],
            'selected_concept.main_items' => ['nullable', 'string', 'max:300'],
            'selected_concept.supporting_items' => ['nullable', 'string', 'max:300'],
            'selected_concept.container' => ['nullable', 'string', 'max:300'],
            'selected_concept.wrapping' => ['nullable', 'string', 'max:300'],
            'selected_concept.personalization' => ['nullable', 'string', 'max:500'],
            'selected_concept.flowers' => ['nullable', 'string', 'max:300'],
            'selected_concept.gifts' => ['nullable', 'string', 'max:300'],
            'selected_concept.main_flowers' => ['nullable', 'string', 'max:300'],
            'selected_concept.supporting_elements' => ['nullable', 'string', 'max:300'],
            'selected_concept.color_palette' => ['nullable', 'string', 'max:300'],
            'selected_concept.design_form' => ['nullable', 'string', 'max:300'],
            'selected_concept.wrapping' => ['nullable', 'string', 'max:300'],
            'selected_concept.style' => ['nullable', 'string', 'max:300'],
            'selected_concept.visual_feeling' => ['nullable', 'string', 'max:300'],
            'selected_concept.meaning' => ['nullable', 'string', 'max:300'],
            'selected_concept.design_description' => ['nullable', 'string', 'max:1200'],
            'selected_concept.why_recommended' => ['nullable', 'string', 'max:300'],
        ]);

        $recentRecommendationKeys = [
            ...collect($data['recent_product_ids'] ?? [])->map(fn (int $id): string => 'product:' . $id)->all(),
            ...collect($data['recent_custom_concept_keys'] ?? [])->map(fn (string $key): string => 'custom:' . $key)->all(),
        ];
        $arguments = [
            $data['query'],
            $advisor->contextFromMessages($data['context'] ?? []),
            $data['recent_product_ids'] ?? [],
            $data['selected_concept'] ?? [],
            $data['recent_concept_names'] ?? [],
        ];

        if (! str_contains((string) $request->header('Accept'), 'text/event-stream')) {
            return response()->json($advisor->advise(...[...$arguments, null, $recentRecommendationKeys, $data['rejected_suggestions'] ?? [], $data['last_response_items'] ?? [], $data['selected_item'] ?? []]));
        }

        $rejectedSuggestions = $data['rejected_suggestions'] ?? [];

        $lastResponseItems = $data['last_response_items'] ?? [];
        $selectedItem = $data['selected_item'] ?? [];
        return response()->stream(function () use ($advisor, $arguments, $recentRecommendationKeys, $rejectedSuggestions, $lastResponseItems, $selectedItem): void {
            $sentText = false;
            $emit = static function (string $event, array $payload): void {
                echo 'data: ' . json_encode(['event' => $event, 'data' => $payload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                // Flush nested buffers outward without closing buffers owned by
                // Laravel, PHPUnit, or the host application.
                if (app()->environment('testing')) {
                    return;
                }
                for ($level = ob_get_level(); $level > 0; $level--) {
                    @ob_flush();
                }
                flush();
            };

            try {
                $result = $advisor->advise(...[...$arguments, static function (string $text) use ($emit, &$sentText): void {
                    if ($text === '') {
                        return;
                    }
                    $sentText = true;
                    $emit('text', ['delta' => $text]);
                }, $recentRecommendationKeys, $rejectedSuggestions, $lastResponseItems, $selectedItem]);

                // When the online model is unavailable (or only asks a
                // clarification), still reveal the finished fallback reply in
                // small UTF-8 chunks instead of rendering the whole paragraph
                // in one frame. Configured models use their real text deltas.
                if (! $sentText && filled($result['message'] ?? null)) {
                    foreach (mb_str_split((string) $result['message'], 14) as $delta) {
                        $emit('text', ['delta' => $delta]);
                    }
                }

                $emit('result', $result);
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::error('Gift advisor streaming failed', [
                    'exception_class' => $exception::class,
                ]);
                $emit('error', ['message' => 'Trợ lý đang gặp sự cố. Bạn thử lại sau ít phút nhé.']);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-transform',
            'Content-Encoding' => 'identity',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}

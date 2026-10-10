<?php

namespace App\Services\GiftAdvisor;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Server-side inspiration search. Results are untrusted reference material;
 * they never become BloomGift product, price, stock or action data.
 */
class WebSearchService
{
    /** @return array{status: string, sources: array<int, array<string, string>>, root_cause?: string, http_status?: int|null} */
    public function inspiration(string $message, array $context, array $plannedQueries = []): array
    {
        if (! $this->isConfigured()) {
            $provider = (string) config('services.bloomgift_web_search.driver', 'none');
            $reason = $provider !== 'brave' ? 'provider_disabled' : (blank(config('services.bloomgift_web_search.api_key')) ? 'missing_api_key' : 'invalid_base_url');
            $this->recordRuntime($provider, null, 0, false, $reason);

            return ['status' => 'not_configured', 'sources' => [], 'root_cause' => $reason, 'http_status' => null];
        }
        if ($plannedQueries === [] && ! $this->shouldSearch($message)) {
            return ['status' => 'not_needed', 'sources' => [], 'root_cause' => 'search_not_requested', 'http_status' => null];
        }

        $sources = [];
        $responses = [];
        foreach (($plannedQueries === [] ? $this->queries($context) : array_slice($plannedQueries, 0, 3)) as $query) {
            $response = $this->search($query);
            $sources = [...$sources, ...$response['sources']];
            $responses[] = $response;
            if (count($sources) >= 6) {
                break;
            }
        }
        $sources = $this->unique($sources);
        $last = end($responses) ?: ['status' => 'no_queries', 'root_cause' => 'no_search_queries', 'http_status' => null];
        $status = $sources === [] ? $last['status'] : 'active';
        $rootCause = $sources === [] ? $last['root_cause'] : null;
        $httpStatus = $last['http_status'];
        $this->recordRuntime('brave', $httpStatus, count($sources), $sources !== [], $rootCause);

        return ['status' => $status, 'sources' => $sources, 'root_cause' => $rootCause, 'http_status' => $httpStatus];
    }

    public function isConfigured(): bool
    {
        return config('services.bloomgift_web_search.driver') === 'brave'
            && filled(config('services.bloomgift_web_search.api_key'))
            && filter_var(config('services.bloomgift_web_search.base_url'), FILTER_VALIDATE_URL) !== false;
    }

    public function shouldSearch(string $message): bool
    {
        return Str::contains(Str::lower($message), [
            'xu hướng', 'đang được ưa chuộng', 'được yêu thích', 'đặc biệt', 'độc đáo',
            'mẫu lạ', 'shop chưa có', 'internet', 'tham khảo bên ngoài', 'mẫu khác',
            'loại khác', 'tìm thêm', 'khác hẳn', 'đổi mẫu', 'tìm trên mạng', 'nguồn cảm hứng',
        ]);
    }

    /** @return array<int, string> */
    public function queries(array $context): array
    {
        $occasion = ['birthday' => 'birthday', 'anniversary' => 'anniversary', 'graduation' => 'graduation', 'opening' => 'grand opening', 'valentine' => 'Valentine', 'international_womens_day' => '8 March International Womens Day', 'vietnamese_womens_day' => '20 October Vietnamese Womens Day', 'congratulations' => 'congratulations', 'get_well' => 'get well'][ $context['occasion'] ?? ''] ?? 'gift';
        $recipient = ['girlfriend' => 'girlfriend', 'boyfriend' => 'boyfriend', 'lover' => 'romantic partner', 'mother' => 'mother', 'friend' => 'friend', 'colleague' => 'colleague'][$context['recipient'] ?? ''] ?? 'recipient';
        $colors = implode(' ', (array) ($context['colors'] ?? []));
        $style = implode(' ', (array) ($context['styles'] ?? []));
        $focus = ['gift' => 'gift box ideas', 'set' => 'flower gift set ideas', 'flower' => 'flower arrangement ideas'][$context['product_focus'] ?? 'flower'] ?? 'flower arrangement ideas';
        $flowers = implode(' ', (array) ($context['flower_preferences'] ?? []));

        return array_values(array_unique(array_filter([
            trim("{$style} {$colors} {$occasion} {$focus} {$recipient}"),
            trim("{$flowers} {$colors} {$occasion} bouquet design ideas"),
            trim("modern {$occasion} {$focus}"),
        ])));
    }

    /** @return array<int, array<string, string>> */
    private function search(string $query): array
    {
        return $this->request($query);
    }

    /** @return array{status: string, sources: array<int, array<string, string>>, root_cause: string|null, http_status: int|null} */
    private function request(string $query): array
    {
        try {
            $response = Http::acceptJson()
                ->withHeaders(['X-Subscription-Token' => (string) config('services.bloomgift_web_search.api_key')])
                ->timeout((int) config('services.bloomgift_web_search.timeout', 5))
                ->get((string) config('services.bloomgift_web_search.base_url'), ['q' => $query, 'count' => 3]);
        } catch (ConnectionException) {
            return ['status' => 'timeout', 'sources' => [], 'root_cause' => 'connection_timeout', 'http_status' => null];
        } catch (Throwable) {
            return ['status' => 'unavailable', 'sources' => [], 'root_cause' => 'request_exception', 'http_status' => null];
        }

        if (! $response->successful()) {
            $body = $response->json();
            $providerError = data_get($body, 'error.code') ?? data_get($body, 'error.type') ?? null;
            $providerError = is_string($providerError) ? Str::limit(preg_replace('/[^a-z0-9 _.-]/i', '', $providerError) ?? '', 80, '') : null;
            $reason = match ($response->status()) {
                401, 403 => 'authentication_failed', 429 => 'quota_exceeded', default => 'http_error',
            };

            return ['status' => 'unavailable', 'sources' => [], 'root_cause' => $providerError ? $reason . ':' . $providerError : $reason, 'http_status' => $response->status()];
        }

        $sources = collect(data_get($response->json(), 'web.results', []))
            ->filter(fn (mixed $result): bool => is_array($result))
            ->map(fn (array $result): ?array => $this->normalise($result))
            ->filter()->values()->all();

        return ['status' => $sources === [] ? 'no_results' : 'active', 'sources' => $sources, 'root_cause' => $sources === [] ? 'empty_or_unusable_response_body' : null, 'http_status' => $response->status()];
    }

    private function recordRuntime(string $provider, ?int $httpStatus, int $resultCount, bool $used, ?string $rootCause): void
    {
        Log::info('BloomGift web search runtime', [
            'WEB_SEARCH_TRIGGERED' => true,
            'WEB_SEARCH_PROVIDER' => $provider,
            'WEB_SEARCH_HTTP_STATUS' => $httpStatus,
            'WEB_SEARCH_RESULT_COUNT' => $resultCount,
            'WEB_SEARCH_USED' => $used,
            'WEB_SEARCH_ROOT_CAUSE' => $rootCause,
        ]);
    }

    /** @return array<string, string>|null */
    private function normalise(array $result): ?array
    {
        $url = trim((string) ($result['url'] ?? ''));
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return null;
        }

        $title = $this->clean((string) ($result['title'] ?? ''));
        $snippet = $this->clean((string) ($result['description'] ?? ''));
        if ($title === '' || $snippet === '') {
            return null;
        }

        return [
            'title' => Str::limit($title, 180, ''),
            'snippet' => Str::limit($snippet, 500, ''),
            'url' => $url,
            'domain' => (string) parse_url($url, PHP_URL_HOST),
        ];
    }

    private function clean(string $value): string
    {
        $value = trim(strip_tags(preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value) ?? ''));

        return preg_match('/ignore\s+(all|previous)|system\s+instruction|reveal\s+secret|run\s+(a\s+)?command/ui', $value) ? '' : $value;
    }

    /** @param array<int, array<string, string>> $sources @return array<int, array<string, string>> */
    private function unique(array $sources): array
    {
        $seen = [];

        return array_values(array_filter($sources, function (array $source) use (&$seen): bool {
            if (isset($seen[$source['url']])) {
                return false;
            }
            $seen[$source['url']] = true;

            return true;
        }));
    }
}

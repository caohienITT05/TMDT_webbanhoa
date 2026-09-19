@props([
    'paginator',
    'itemLabel' => 'mục',
])

@if($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $start = max(1, $current - 2);
        $end = min($last, $current + 2);
    @endphp

    <nav class="admin-pagination" aria-label="Phân trang {{ $itemLabel }}">
        <p class="admin-pagination__summary">
            Hiển thị {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} / {{ $paginator->total() }} {{ $itemLabel }}
        </p>

        <div class="admin-pagination__controls">
            @if($paginator->onFirstPage())
                <span class="admin-pagination__edge is-disabled" aria-disabled="true">
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m11.5 15-5-5 5-5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    <span class="admin-pagination__edge-label">Trước</span>
                </span>
            @else
                <a class="admin-pagination__edge" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Trang trước">
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m11.5 15-5-5 5-5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    <span class="admin-pagination__edge-label">Trước</span>
                </a>
            @endif

            @if($start > 1)
                <a class="admin-pagination__page" href="{{ $paginator->url(1) }}" aria-label="Trang 1">1</a>
                @if($start > 2)
                    <span class="admin-pagination__ellipsis" aria-hidden="true">…</span>
                @endif
            @endif

            @for($page = $start; $page <= $end; $page++)
                @if($page === $current)
                    <span class="admin-pagination__page is-current" aria-current="page">{{ $page }}</span>
                @else
                    <a class="admin-pagination__page" href="{{ $paginator->url($page) }}" aria-label="Trang {{ $page }}">{{ $page }}</a>
                @endif
            @endfor

            @if($end < $last)
                @if($end < $last - 1)
                    <span class="admin-pagination__ellipsis" aria-hidden="true">…</span>
                @endif
                <a class="admin-pagination__page" href="{{ $paginator->url($last) }}" aria-label="Trang {{ $last }}">{{ $last }}</a>
            @endif

            @if($paginator->hasMorePages())
                <a class="admin-pagination__edge" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Trang sau">
                    <span class="admin-pagination__edge-label">Sau</span>
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m8.5 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </a>
            @else
                <span class="admin-pagination__edge is-disabled" aria-disabled="true">
                    <span class="admin-pagination__edge-label">Sau</span>
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m8.5 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </span>
            @endif
        </div>
    </nav>
@endif

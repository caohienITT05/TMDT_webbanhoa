@props(['items' => []])

<nav class="bloom-shell py-5" aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-1.5 text-xs text-bloom-muted sm:text-sm">
        @foreach ($items as $item)
            <li class="flex items-center gap-1.5">
                @if (!empty($item['url']))
                    <a href="{{ $item['url'] }}" class="transition hover:text-bloom-rose">{{ $item['label'] }}</a>
                @else
                    <span class="font-medium text-bloom-ink">{{ $item['label'] }}</span>
                @endif
                @if (! $loop->last)
                    <x-customer.icon name="chevron-right" class="h-3.5 w-3.5 text-bloom-muted/60" />
                @endif
            </li>
        @endforeach
    </ol>
</nav>

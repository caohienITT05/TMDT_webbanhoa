@props(['product', 'alt' => null])

@php
    $name = $alt ?: ($product->name ?? 'Sản phẩm BloomGift');
    $hasImage = ! empty($product?->image);
    $image = $hasImage ? asset('storage/' . $product->image) : null;
    $initial = mb_strtoupper(mb_substr($name, 0, 1));
    $fallbacks = [
        'linear-gradient(145deg, #f8edeb 0%, #ecd9db 100%)',
        'linear-gradient(145deg, #f4eee6 0%, #ead9c9 100%)',
        'linear-gradient(145deg, #eff1e8 0%, #dfe5d4 100%)',
        'linear-gradient(145deg, #edf0f4 0%, #d9dfe8 100%)',
    ];
    $fallbackStyle = $fallbacks[((int) ($product?->id ?? 0)) % count($fallbacks)];
@endphp

<div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-bloom-blush']) }} style="background: {{ $fallbackStyle }};">
    @if ($image)
        <img src="{{ $image }}" alt="{{ $name }}" class="h-full w-full object-cover" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
    @endif
    <div class="{{ $image ? 'hidden' : '' }} flex h-full w-full flex-col items-center justify-center gap-3 text-bloom-rose">
        <span class="flex h-14 w-14 items-center justify-center rounded-full border border-white/70 bg-white/45"><x-customer.icon name="flower" class="h-7 w-7" /></span>
        <span class="font-display text-xl font-semibold">{{ $initial }}</span>
        <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-bloom-muted">BloomGift</span>
    </div>
</div>

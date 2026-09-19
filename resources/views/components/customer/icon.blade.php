@props(['name', 'class' => ''])

@php
    $paths = [
        'flower' => '<path d="M12 21a5 5 0 0 0 0-10 5 5 0 0 0 0 10Zm0-10a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm-4.3 3.8A5 5 0 1 1 3.2 6.2a5 5 0 0 1 4.5 8.6Zm8.6 0a5 5 0 1 0 4.5-8.6 5 5 0 0 0-4.5 8.6ZM12 12v9" />',
        'search' => '<circle cx="11" cy="11" r="6.5" /><path d="m16 16 4 4" />',
        'eye' => '<path d="M3 12s3.2-5 9-5 9 5 9 5-3.2 5-9 5-9-5-9-5Z" /><circle cx="12" cy="12" r="2.3" />',
        'heart' => '<path d="M20.8 4.8a5.5 5.5 0 0 0-7.8 0L12 6l-1-1.2a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.4a5.5 5.5 0 0 0 0-7.8Z" />',
        'bag' => '<path d="M5 8h14l-1 12H6L5 8Z" /><path d="M9 9V6a3 3 0 0 1 6 0v3" />',
        'cart-plus' => '<path d="M4 5h2l1.5 10h9.8l1.5-7H7" /><circle cx="9" cy="19" r="1.3" /><circle cx="16" cy="19" r="1.3" /><path d="M14 8v4M12 10h4" />',
        'package' => '<path d="m4 7 8-4 8 4-8 4-8-4Z" /><path d="M4 7v10l8 4 8-4V7M12 11v10" />',
        'clock' => '<circle cx="12" cy="12" r="8.5" /><path d="M12 7v5l3.5 2" />',
        'user' => '<circle cx="12" cy="8" r="3.5" /><path d="M4.5 20a7.5 7.5 0 0 1 15 0" />',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16" />',
        'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z" /><path d="M9 8h6M9 12h6" />',
        'grid' => '<rect x="4" y="4" width="6" height="6" rx="1" /><rect x="14" y="4" width="6" height="6" rx="1" /><rect x="4" y="14" width="6" height="6" rx="1" /><rect x="14" y="14" width="6" height="6" rx="1" />',
        'logout' => '<path d="M10 17l5-5-5-5M15 12H3" /><path d="M21 3v18H10" />',
        'chevron-right' => '<path d="m9 18 6-6-6-6" />',
        'arrow-right' => '<path d="M5 12h14m-6-6 6 6-6 6" />',
        'arrow-left' => '<path d="M19 12H5m6 6-6-6 6-6" />',
        'plus' => '<path d="M12 5v14M5 12h14" />',
        'minus' => '<path d="M5 12h14" />',
        'trash' => '<path d="M4 7h16M10 11v5M14 11v5M6 7l1 13h10l1-13M9 7V4h6v3" />',
        'check' => '<path d="m5 12 4 4L19 6" />',
        'truck' => '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7z" /><circle cx="7" cy="18" r="2" /><circle cx="18" cy="18" r="2" />',
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2" /><path d="M8 3v4M16 3v4M4 10h16" />',
        'shield' => '<path d="M12 3 4.5 6v5c0 4.6 3.2 8.3 7.5 10 4.3-1.7 7.5-5.4 7.5-10V6L12 3Z" /><path d="m8.5 12 2.2 2.2 4.8-4.8" />',
        'gift' => '<rect x="3" y="8" width="18" height="13" rx="1" /><path d="M12 8v13M3 12h18M12 8H8.5A2.5 2.5 0 1 1 11 5.5L12 8Zm0 0h3.5A2.5 2.5 0 1 0 13 5.5L12 8Z" />',
        'info' => '<circle cx="12" cy="12" r="9" /><path d="M12 11v5M12 8h.01" />',
        'close' => '<path d="m6 6 12 12M18 6 6 18" />',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" />',
        'phone' => '<path d="M6.5 3.5 9 6.2 7.3 8.6a15.6 15.6 0 0 0 8.1 8.1l2.4-1.7 2.7 2.5-1.4 2.8c-.5 1-1.6 1.5-2.7 1.2C8.8 19.7 4.3 15.2 2.5 7.6 2.2 6.5 2.7 5.4 3.7 5L6.5 3.5Z" />',
        'location' => '<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" /><circle cx="12" cy="10" r="2.5" />',
        'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z" />',
        'credit-card' => '<rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 10h18M7 15h3" />',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $paths[$name] ?? $paths['info'] !!}
</svg>

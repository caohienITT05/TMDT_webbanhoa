@props(['type' => 'success', 'message'])

@php
    $isError = $type === 'error';
@endphp

<div x-data="{ open: true, init() { window.setTimeout(() => this.open = false, 4500) } }" x-cloak x-show="open" x-transition.opacity class="bloom-shell pt-4">
    <div class="flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {{ $isError ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800' }}" role="alert">
        <x-customer.icon :name="$isError ? 'info' : 'check'" class="mt-0.5 h-4 w-4 shrink-0" />
        <p class="flex-1 leading-5">{{ $message }}</p>
        <button type="button" @click="open = false" class="shrink-0 opacity-70 hover:opacity-100" aria-label="Đóng thông báo"><x-customer.icon name="close" class="h-4 w-4" /></button>
    </div>
</div>

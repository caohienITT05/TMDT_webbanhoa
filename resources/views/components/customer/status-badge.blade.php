@props(['status', 'type' => 'order'])

@php
    $normalized = strtolower((string) $status);
    $labels = $type === 'payment'
        ? ['pending' => 'Chờ thanh toán', 'paid' => 'Đã thanh toán', 'completed' => 'Đã thanh toán', 'failed' => 'Thanh toán lỗi', 'cancelled' => 'Đã hủy']
        : ['pending' => 'Chờ xử lý', 'confirmed' => 'Đã xác nhận', 'preparing' => 'Đang chuẩn bị', 'processing' => 'Đang xử lý', 'shipping' => 'Đang giao', 'delivered' => 'Đã giao', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy'];
    $tone = in_array($normalized, ['paid', 'completed', 'delivered']) ? 'success' : (in_array($normalized, ['cancelled', 'failed']) ? 'danger' : (in_array($normalized, ['confirmed', 'preparing', 'processing', 'shipping']) ? 'info' : 'warning'));
@endphp

<span class="bloom-status bloom-status--{{ $tone }}">{{ $labels[$normalized] ?? ucfirst(str_replace('_', ' ', $normalized)) }}</span>

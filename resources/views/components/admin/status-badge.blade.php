@props([
    'status' => null,
    'type' => 'order',
])

@php
    $key = strtolower(trim((string) $status));

    $orderStatuses = [
        'pending' => ['Chờ xác nhận', 'warning'],
        'confirmed' => ['Đã xác nhận', 'info'],
        'processing' => ['Đang chuẩn bị', 'progress'],
        'preparing' => ['Đang chuẩn bị', 'progress'],
        'shipping' => ['Đang giao', 'info'],
        'completed' => ['Hoàn thành', 'success'],
        'delivered' => ['Hoàn thành', 'success'],
        'cancelled' => ['Đã hủy', 'danger'],
        'canceled' => ['Đã hủy', 'danger'],
    ];

    $paymentStatuses = [
        'pending' => ['Chưa thanh toán', 'warning'],
        'paid' => ['Đã thanh toán', 'success'],
        'completed' => ['Đã thanh toán', 'success'],
        'failed' => ['Thất bại', 'danger'],
        'cancelled' => ['Đã hủy', 'danger'],
        'canceled' => ['Đã hủy', 'danger'],
    ];

    $statuses = $type === 'payment' ? $paymentStatuses : $orderStatuses;
    $fallback = $key === '' ? 'Chưa cập nhật' : ucwords(str_replace(['-', '_'], ' ', $key));
    [$label, $tone] = $statuses[$key] ?? [$fallback, 'neutral'];
@endphp

<span {{ $attributes->class('admin-badge admin-badge--' . $tone) }}>{{ $label }}</span>

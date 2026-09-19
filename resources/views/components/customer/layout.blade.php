@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#9f3657">
    <title>{{ $title ? $title . ' · ' : '' }}BloomGift</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700%7Clora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bloom-canvas font-sans text-bloom-ink antialiased">
    <div class="flex min-h-screen flex-col">
        <x-customer.header />

        <main class="flex-1">
            @if (session('success'))
                <x-customer.flash-alert type="success" :message="session('success')" />
            @endif

            @if (session('error'))
                <x-customer.flash-alert type="error" :message="session('error')" />
            @endif

            {{ $slot }}
        </main>

        <x-customer.footer />
    </div>
</body>
</html>

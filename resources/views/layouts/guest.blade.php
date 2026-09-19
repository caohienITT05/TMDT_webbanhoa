<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BloomGift</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700%7Clora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bloom-auth-page font-sans text-bloom-ink antialiased">
    <main class="bloom-shell flex min-h-screen flex-col items-center justify-center py-10">
        <a href="{{ route('home') }}" class="group mb-7 flex items-center gap-2.5" aria-label="BloomGift - Trang chủ">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-bloom-rose text-white transition group-hover:bg-bloom-plum"><x-customer.icon name="flower" class="h-5 w-5" /></span>
            <span class="font-display text-2xl font-semibold text-bloom-plum">BloomGift</span>
        </a>
        <div class="bloom-auth-card overflow-hidden">
            {{ $slot }}
        </div>
        <a href="{{ route('home') }}" class="mt-6 inline-flex items-center gap-1 text-sm font-semibold text-bloom-muted transition hover:text-bloom-rose"><x-customer.icon name="arrow-left" class="h-4 w-4" />Quay lại trang chủ</a>
    </main>
</body>
</html>

<x-guest-layout>
    <div class="border-b border-bloom-line bg-bloom-blush px-6 py-7 sm:px-8">
        <p class="bloom-eyebrow">Xác minh email</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-[-.035em] text-bloom-plum">Kiểm tra hộp thư của bạn</h1>
        <p class="mt-2 text-sm leading-6 text-bloom-muted">Trước khi bắt đầu, hãy xác minh email bằng liên kết BloomGift vừa gửi đến bạn.</p>
    </div>
    <div class="px-6 py-7 sm:px-8">
        @if (session('status') === 'verification-link-sent')
            <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-5 text-emerald-800">Một liên kết xác minh mới đã được gửi đến email của bạn.</div>
        @endif
        <div class="flex flex-col gap-3 sm:flex-row">
            <form method="POST" action="{{ route('verification.send') }}" class="flex-1">
                @csrf
                <button type="submit" class="bloom-button w-full">Gửi lại email xác minh</button>
            </form>
            <form method="POST" action="{{ route('logout') }}" class="sm:w-auto">
                @csrf
                <button type="submit" class="bloom-button bloom-button--ghost w-full">Đăng xuất</button>
            </form>
        </div>
    </div>
</x-guest-layout>

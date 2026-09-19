<x-guest-layout>
    <div class="border-b border-bloom-line bg-bloom-blush px-6 py-7 sm:px-8">
        <p class="bloom-eyebrow">Khôi phục truy cập</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-[-.035em] text-bloom-plum">Quên mật khẩu?</h1>
        <p class="mt-2 text-sm leading-6 text-bloom-muted">Nhập email của bạn, BloomGift sẽ gửi liên kết để đặt lại mật khẩu.</p>
    </div>
    <div class="px-6 py-7 sm:px-8">
        @if (session('status'))
            <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-5 text-emerald-800">{{ session('status') }}</div>
        @endif
        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <div>
                <label class="bloom-label" for="email">Địa chỉ email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="bloom-input h-11 w-full px-3" placeholder="name@example.com">
                @error('email') <p class="bloom-field-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="bloom-button w-full">Gửi liên kết đặt lại mật khẩu <x-customer.icon name="mail" class="h-4 w-4" /></button>
        </form>
        <p class="mt-6 text-center text-sm text-bloom-muted"><a href="{{ route('login') }}" class="font-bold text-bloom-rose hover:underline">Quay lại đăng nhập</a></p>
    </div>
</x-guest-layout>

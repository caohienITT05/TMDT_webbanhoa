<x-guest-layout>
    <div class="border-b border-bloom-line bg-bloom-blush px-6 py-7 sm:px-8">
        <p class="bloom-eyebrow">Bảo mật tài khoản</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-[-.035em] text-bloom-plum">Đặt lại mật khẩu</h1>
        <p class="mt-2 text-sm leading-6 text-bloom-muted">Tạo một mật khẩu mới để quay lại tài khoản BloomGift của bạn.</p>
    </div>
    <div class="px-6 py-7 sm:px-8">
        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div>
                <label class="bloom-label" for="email">Địa chỉ email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" class="bloom-input h-11 w-full px-3">
                @error('email') <p class="bloom-field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="bloom-label" for="password">Mật khẩu mới</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" class="bloom-input h-11 w-full px-3">
                @error('password') <p class="bloom-field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="bloom-label" for="password_confirmation">Xác nhận mật khẩu mới</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="bloom-input h-11 w-full px-3">
                @error('password_confirmation') <p class="bloom-field-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="bloom-button w-full">Đặt lại mật khẩu <x-customer.icon name="shield" class="h-4 w-4" /></button>
        </form>
    </div>
</x-guest-layout>

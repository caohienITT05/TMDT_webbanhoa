<x-guest-layout>
    <div class="border-b border-bloom-line bg-bloom-blush px-6 py-7 sm:px-8">
        <p class="bloom-eyebrow">Chào mừng trở lại</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-[-.035em] text-bloom-plum">Đăng nhập</h1>
        <p class="mt-2 text-sm leading-6 text-bloom-muted">Theo dõi đơn hàng, lưu sản phẩm yêu thích và mua sắm thuận tiện hơn.</p>
    </div>
    <div class="px-6 py-7 sm:px-8">
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm leading-5 text-red-800">{{ $errors->first() }}</div>
        @endif
        @if (session('status'))
            <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-5 text-emerald-800">{{ session('status') }}</div>
        @endif
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf
            <div>
                <label class="bloom-label" for="email">Địa chỉ email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="bloom-input h-11 w-full px-3" placeholder="name@example.com">
                @error('email') <p class="bloom-field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <div class="flex items-center justify-between gap-3"><label class="bloom-label !mb-0" for="password">Mật khẩu</label>@if (Route::has('password.request'))<a href="{{ route('password.request') }}" class="text-xs font-semibold text-bloom-rose hover:underline">Quên mật khẩu?</a>@endif</div>
                <input id="password" type="password" name="password" required autocomplete="current-password" class="bloom-input mt-2 h-11 w-full px-3" placeholder="Nhập mật khẩu">
                @error('password') <p class="bloom-field-error">{{ $message }}</p> @enderror
            </div>
            <label class="flex cursor-pointer items-center gap-2 text-sm text-bloom-muted"><input id="remember_me" name="remember" type="checkbox" class="h-4 w-4 rounded border-bloom-line text-bloom-rose focus:ring-bloom-rose">Ghi nhớ đăng nhập</label>
            <button type="submit" class="bloom-button w-full">Đăng nhập <x-customer.icon name="arrow-right" class="h-4 w-4" /></button>
        </form>
        <p class="mt-6 border-t border-bloom-line pt-5 text-center text-sm text-bloom-muted">Chưa có tài khoản? <a href="{{ route('register') }}" class="font-bold text-bloom-rose hover:underline">Đăng ký ngay</a></p>
    </div>
</x-guest-layout>

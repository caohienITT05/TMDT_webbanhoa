<x-guest-layout>
    <div class="border-b border-bloom-line bg-bloom-blush px-6 py-7 sm:px-8">
        <p class="bloom-eyebrow">Khu vực bảo mật</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-[-.035em] text-bloom-plum">Xác nhận mật khẩu</h1>
        <p class="mt-2 text-sm leading-6 text-bloom-muted">Để tiếp tục thao tác này, hãy xác nhận lại mật khẩu tài khoản của bạn.</p>
    </div>
    <div class="px-6 py-7 sm:px-8">
        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
            @csrf
            <div>
                <label class="bloom-label" for="password">Mật khẩu</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" class="bloom-input h-11 w-full px-3" placeholder="Nhập mật khẩu">
                @error('password') <p class="bloom-field-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="bloom-button w-full">Xác nhận <x-customer.icon name="check" class="h-4 w-4" /></button>
        </form>
    </div>
</x-guest-layout>

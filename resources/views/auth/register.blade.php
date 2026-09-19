<x-guest-layout>
    <div class="border-b border-bloom-line bg-bloom-blush px-6 py-7 sm:px-8">
        <p class="bloom-eyebrow">Tài khoản BloomGift</p>
        <h1 class="mt-2 font-display text-3xl font-semibold tracking-[-.035em] text-bloom-plum">Tạo tài khoản</h1>
        <p class="mt-2 text-sm leading-6 text-bloom-muted">Lưu lại lựa chọn yêu thích và quản lý đơn hàng của bạn ở một nơi.</p>
    </div>
    <div class="px-6 py-7 sm:px-8">
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm leading-5 text-red-800">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf
            <div>
                <label class="bloom-label" for="name">Họ và tên</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="bloom-input h-11 w-full px-3" placeholder="Ví dụ: Nguyễn Minh Anh">
                @error('name') <p class="bloom-field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="bloom-label" for="email">Địa chỉ email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="bloom-input h-11 w-full px-3" placeholder="name@example.com">
                @error('email') <p class="bloom-field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="bloom-label" for="password">Mật khẩu</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" class="bloom-input h-11 w-full px-3" placeholder="Tối thiểu 8 ký tự">
                @error('password') <p class="bloom-field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="bloom-label" for="password_confirmation">Xác nhận mật khẩu</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="bloom-input h-11 w-full px-3" placeholder="Nhập lại mật khẩu">
            </div>
            <button type="submit" class="bloom-button w-full">Hoàn tất đăng ký <x-customer.icon name="arrow-right" class="h-4 w-4" /></button>
        </form>
        <p class="mt-6 border-t border-bloom-line pt-5 text-center text-sm text-bloom-muted">Đã có tài khoản? <a href="{{ route('login') }}" class="font-bold text-bloom-rose hover:underline">Đăng nhập</a></p>
    </div>
</x-guest-layout>

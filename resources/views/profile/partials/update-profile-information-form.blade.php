<section>
    <header>
        <h2 class="bloom-card-title">Thông tin cá nhân</h2>
        <p class="mt-2 text-sm leading-6 text-bloom-muted">Cập nhật thông tin liên hệ để việc giao nhận đơn hàng thuận tiện hơn.</p>
    </header>

    <form id="send-verification" method="POST" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="POST" action="{{ route('profile.update') }}" class="mt-6 grid gap-5 sm:grid-cols-2">
        @csrf
        @method('PATCH')
        <div>
            <label class="bloom-label" for="name">Họ và tên</label>
            <input id="name" name="name" type="text" class="bloom-input h-11 w-full px-3" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            @error('name') <p class="bloom-field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="bloom-label" for="email">Địa chỉ email</label>
            <input id="email" name="email" type="email" class="bloom-input h-11 w-full px-3" value="{{ old('email', $user->email) }}" required autocomplete="username">
            @error('email') <p class="bloom-field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="bloom-label" for="phone">Số điện thoại</label>
            <input id="phone" name="phone" type="text" class="bloom-input h-11 w-full px-3" value="{{ old('phone', $user->phone) }}" placeholder="09xxxxxxxx" autocomplete="tel">
            @error('phone') <p class="bloom-field-error">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label class="bloom-label" for="address">Địa chỉ giao hàng</label>
            <input id="address" name="address" type="text" class="bloom-input h-11 w-full px-3" value="{{ old('address', $user->address) }}" placeholder="Số nhà, tên đường, quận/huyện, tỉnh/thành..." autocomplete="street-address">
            @error('address') <p class="bloom-field-error">{{ $message }}</p> @enderror
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900 sm:col-span-2">
                <p>Email của bạn chưa được xác minh. <button form="send-verification" class="font-bold underline underline-offset-2">Gửi lại email xác minh</button>.</p>
                @if (session('status') === 'verification-link-sent')
                    <p class="mt-1 font-semibold text-emerald-700">Liên kết xác minh mới đã được gửi.</p>
                @endif
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
            <button type="submit" class="bloom-button">Lưu thay đổi</button>
            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="text-sm font-medium text-emerald-700">Đã lưu thông tin.</p>
            @endif
        </div>
    </form>
</section>

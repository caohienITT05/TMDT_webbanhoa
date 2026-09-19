<section>
    <header>
        <h2 class="bloom-card-title">Đổi mật khẩu</h2>
        <p class="mt-2 text-sm leading-6 text-bloom-muted">Dùng một mật khẩu mạnh và chỉ bạn biết để bảo vệ tài khoản.</p>
    </header>

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 grid gap-5 sm:grid-cols-2">
        @csrf
        @method('PUT')
        <div class="sm:col-span-2">
            <label class="bloom-label" for="update_password_current_password">Mật khẩu hiện tại</label>
            <input id="update_password_current_password" name="current_password" type="password" class="bloom-input h-11 w-full px-3" autocomplete="current-password">
            @if ($errors->updatePassword->has('current_password')) <p class="bloom-field-error">{{ $errors->updatePassword->first('current_password') }}</p> @endif
        </div>
        <div>
            <label class="bloom-label" for="update_password_password">Mật khẩu mới</label>
            <input id="update_password_password" name="password" type="password" class="bloom-input h-11 w-full px-3" autocomplete="new-password">
            @if ($errors->updatePassword->has('password')) <p class="bloom-field-error">{{ $errors->updatePassword->first('password') }}</p> @endif
        </div>
        <div>
            <label class="bloom-label" for="update_password_password_confirmation">Xác nhận mật khẩu mới</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="bloom-input h-11 w-full px-3" autocomplete="new-password">
            @if ($errors->updatePassword->has('password_confirmation')) <p class="bloom-field-error">{{ $errors->updatePassword->first('password_confirmation') }}</p> @endif
        </div>
        <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
            <button type="submit" class="bloom-button">Cập nhật mật khẩu</button>
            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="text-sm font-medium text-emerald-700">Đã cập nhật mật khẩu.</p>
            @endif
        </div>
    </form>
</section>

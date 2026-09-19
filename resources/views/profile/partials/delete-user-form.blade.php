<section x-data="{ open: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }" @keydown.escape.window="open = false">
    <header>
        <h2 class="bloom-card-title">Xóa tài khoản</h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-bloom-muted">Thao tác này không thể hoàn tác. Hãy chắc chắn bạn đã lưu những thông tin cần thiết trước khi tiếp tục.</p>
    </header>

    <button type="button" @click="open = true" class="bloom-button bloom-button--danger mt-5">Xóa tài khoản</button>

    <div x-cloak x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-bloom-plum/45 p-4" role="dialog" aria-modal="true" aria-labelledby="delete-account-title">
        <div @click.outside="open = false" class="w-full max-w-md rounded-xl bg-white p-6 shadow-bloom">
            <div class="flex items-start justify-between gap-4"><div><h3 id="delete-account-title" class="font-display text-2xl font-semibold text-bloom-plum">Xác nhận xóa tài khoản</h3><p class="mt-2 text-sm leading-6 text-bloom-muted">Nhập mật khẩu để xác nhận rằng bạn muốn xóa tài khoản này.</p></div><button type="button" @click="open = false" class="bloom-icon-button shrink-0" aria-label="Đóng"><x-customer.icon name="close" class="h-4 w-4" /></button></div>
            <form method="POST" action="{{ route('profile.destroy') }}" class="mt-6">
                @csrf
                @method('DELETE')
                <label class="bloom-label" for="delete_account_password">Mật khẩu</label>
                <input id="delete_account_password" name="password" type="password" class="bloom-input h-11 w-full px-3" placeholder="Nhập mật khẩu" autocomplete="current-password">
                @if ($errors->userDeletion->has('password')) <p class="bloom-field-error">{{ $errors->userDeletion->first('password') }}</p> @endif
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button type="button" @click="open = false" class="bloom-button bloom-button--ghost">Hủy</button><button type="submit" class="bloom-button bloom-button--danger">Xóa vĩnh viễn</button></div>
            </form>
        </div>
    </div>
</section>

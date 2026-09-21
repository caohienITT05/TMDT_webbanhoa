<x-customer.layout title="Tài khoản của tôi">
    <x-customer.breadcrumb :items="[
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Tài khoản của tôi'],
    ]" />

    <section class="bloom-shell pb-14 sm:pb-20">
        <div class="border-b border-bloom-line pb-7">
            <p class="bloom-eyebrow">Tài khoản BloomGift</p>
            <h1 class="bloom-title mt-2">Thông tin cá nhân</h1>
            <p class="bloom-subtitle mt-3">Quản lý thông tin liên hệ và các thiết lập tài khoản của bạn.</p>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[15.5rem_minmax(0,1fr)] lg:items-start">
            <aside class="bloom-panel bloom-panel--padded lg:sticky lg:top-28">
                <div class="border-b border-bloom-line pb-4">
                    <p class="truncate font-display text-lg font-semibold text-bloom-plum">{{ $user->name }}</p>
                    <p class="mt-1 truncate text-xs text-bloom-muted">{{ $user->email }}</p>
                </div>
                <nav class="bloom-account-nav mt-4 space-y-1" aria-label="Tài khoản">
                    <a href="{{ route('profile.edit') }}" class="is-active"><x-customer.icon name="user" class="h-4 w-4" />Thông tin cá nhân</a>
                    <a href="{{ route('customer.orders') }}"><x-customer.icon name="receipt" class="h-4 w-4" />Đơn hàng của tôi</a>
                    <a href="{{ route('customer.custom-orders.index') }}"><x-customer.icon name="clock" class="h-4 w-4" />Yêu cầu của tôi</a>
                    <a href="{{ route('favorites.index') }}"><x-customer.icon name="heart" class="h-4 w-4" />Sản phẩm yêu thích</a>
                    <a href="#password"><x-customer.icon name="shield" class="h-4 w-4" />Đổi mật khẩu</a>
                </nav>
                <form method="POST" action="{{ route('logout') }}" class="mt-4 border-t border-bloom-line pt-4">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 px-2 text-sm font-semibold text-bloom-danger hover:underline"><x-customer.icon name="logout" class="h-4 w-4" />Đăng xuất</button>
                </form>
            </aside>

            <div class="space-y-5">
                <section class="bloom-panel bloom-panel--padded">
                    @include('profile.partials.update-profile-information-form')
                </section>
                <section id="password" class="bloom-panel bloom-panel--padded">
                    @include('profile.partials.update-password-form')
                </section>
                <section class="bloom-panel bloom-panel--padded">
                    @include('profile.partials.delete-user-form')
                </section>
            </div>
        </div>
    </section>
</x-customer.layout>

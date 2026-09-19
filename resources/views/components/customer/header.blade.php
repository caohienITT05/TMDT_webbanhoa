@php
    $cartItemCount = \App\Models\CartItem::query()
        ->when(auth()->check(), fn ($query) => $query->where('user_id', auth()->id()), fn ($query) => $query->where('session_id', session()->getId()))
        ->sum('quantity');
@endphp

<header x-data="{ mobileOpen: false, accountOpen: false }" class="sticky top-0 z-40 border-b border-bloom-line bg-white/95 backdrop-blur">
    <div class="border-b border-bloom-line bg-bloom-plum text-white">
        <div class="bloom-shell flex min-h-9 items-center justify-center text-center text-xs font-medium tracking-wide text-white/90 sm:justify-between">
            <span>Hoa tươi và quà tặng được chọn lựa cho những dịp đáng nhớ</span>
            <span class="hidden sm:inline">BloomGift · Gửi trọn lời thương</span>
        </div>
    </div>

    <div class="bloom-shell flex h-18 items-center gap-3 lg:gap-7">
        <a href="{{ route('home') }}" class="group flex shrink-0 items-center gap-2.5" aria-label="BloomGift - Trang chủ">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-bloom-rose text-white transition group-hover:bg-bloom-plum">
                <x-customer.icon name="flower" class="h-5 w-5" />
            </span>
            <span class="font-display text-xl font-semibold tracking-tight text-bloom-plum sm:text-2xl">BloomGift</span>
        </a>

        <form action="{{ route('products.index') }}" method="GET" class="hidden min-w-0 flex-1 lg:block">
            <label class="sr-only" for="site-search">Tìm kiếm sản phẩm</label>
            <div class="relative max-w-xl">
                <x-customer.icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-bloom-muted" />
                <input id="site-search" name="search" value="{{ request('search') }}" class="bloom-input h-11 w-full pl-10 pr-4 text-sm" placeholder="Tìm bó hoa, quà tặng...">
            </div>
        </form>

        <nav class="ml-auto hidden items-center gap-1 lg:flex" aria-label="Điều hướng chính">
            <a href="{{ route('categories') }}" class="bloom-nav-link {{ request()->routeIs('categories*') ? 'is-active' : '' }}">Danh mục</a>
            <a href="{{ route('products.index') }}" class="bloom-nav-link {{ request()->routeIs('products*', 'product.detail') ? 'is-active' : '' }}">Sản phẩm</a>
            <a href="{{ route('custom.order') }}" class="bloom-nav-link {{ request()->routeIs('custom.order*') ? 'is-active' : '' }}">Đặt theo yêu cầu</a>
            <a href="{{ route('policies.terms') }}" class="bloom-nav-link {{ request()->routeIs('policies.*') ? 'is-active' : '' }}">Chính sách</a>
        </nav>

        <div class="ml-auto flex items-center gap-1 sm:ml-0 sm:gap-2">
            <a href="{{ route('favorites.index') }}" class="bloom-icon-button" title="Yêu thích" aria-label="Sản phẩm yêu thích">
                <x-customer.icon name="heart" class="h-5 w-5" />
            </a>
            <a href="{{ route('cart.index') }}" class="bloom-icon-button relative" title="Giỏ hàng" aria-label="Giỏ hàng{{ $cartItemCount ? ': ' . $cartItemCount . ' sản phẩm' : '' }}">
                <x-customer.icon name="bag" class="h-5 w-5" />
                @if ($cartItemCount > 0)
                    <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-bloom-rose px-1 text-[9px] font-bold leading-none text-white">{{ $cartItemCount > 99 ? '99+' : $cartItemCount }}</span>
                @endif
            </a>

            @auth
                <div class="relative hidden sm:block">
                    <button type="button" @click="accountOpen = ! accountOpen" @click.outside="accountOpen = false" class="bloom-icon-button" :aria-expanded="accountOpen.toString()" aria-label="Tài khoản">
                        <x-customer.icon name="user" class="h-5 w-5" />
                    </button>
                    <div x-cloak x-show="accountOpen" x-transition.origin.top.right class="absolute right-0 top-[calc(100%+0.65rem)] w-56 overflow-hidden rounded-lg border border-bloom-line bg-white p-2 shadow-bloom">
                        <div class="border-b border-bloom-line px-3 py-2.5">
                            <p class="truncate text-sm font-semibold text-bloom-ink">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-bloom-muted">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="bloom-menu-link"><x-customer.icon name="user" class="h-4 w-4" />Tài khoản</a>
                        <a href="{{ route('customer.orders') }}" class="bloom-menu-link"><x-customer.icon name="package" class="h-4 w-4" />Đơn hàng của tôi</a>
                        @if (auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="bloom-menu-link"><x-customer.icon name="grid" class="h-4 w-4" />Quản trị</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="bloom-menu-link w-full text-left text-bloom-danger"><x-customer.icon name="logout" class="h-4 w-4" />Đăng xuất</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="hidden items-center gap-2 sm:flex">
                    <a href="{{ route('login') }}" class="bloom-text-link">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="bloom-button bloom-button--small">Đăng ký</a>
                </div>
            @endauth

            <button type="button" @click="mobileOpen = ! mobileOpen" class="bloom-icon-button lg:hidden" :aria-expanded="mobileOpen.toString()" aria-label="Mở menu">
                <x-customer.icon name="menu" class="h-5 w-5" />
            </button>
        </div>
    </div>

    <div x-cloak x-show="mobileOpen" x-transition class="border-t border-bloom-line bg-white lg:hidden">
        <div class="bloom-shell space-y-3 py-4">
            <form action="{{ route('products.index') }}" method="GET" class="relative">
                <label class="sr-only" for="mobile-site-search">Tìm kiếm sản phẩm</label>
                <x-customer.icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-bloom-muted" />
                <input id="mobile-site-search" name="search" value="{{ request('search') }}" class="bloom-input h-11 w-full pl-10 pr-4 text-sm" placeholder="Tìm bó hoa, quà tặng...">
            </form>
            <nav class="grid grid-cols-2 gap-2 text-sm font-medium" aria-label="Điều hướng trên điện thoại">
                <a href="{{ route('categories') }}" class="bloom-mobile-link">Danh mục</a>
                <a href="{{ route('products.index') }}" class="bloom-mobile-link">Sản phẩm</a>
                <a href="{{ route('custom.order') }}" class="bloom-mobile-link">Đặt theo yêu cầu</a>
                <a href="{{ route('policies.terms') }}" class="bloom-mobile-link">Thông tin</a>
            </nav>
            @auth
                <div class="grid grid-cols-2 gap-2 border-t border-bloom-line pt-3 text-sm font-medium">
                    <a href="{{ route('profile.edit') }}" class="bloom-mobile-link">Tài khoản</a>
                    <a href="{{ route('customer.orders') }}" class="bloom-mobile-link">Đơn hàng của tôi</a>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="bloom-mobile-link w-full text-left text-bloom-danger">Đăng xuất</button>
                </form>
            @else
                <div class="grid grid-cols-2 gap-2 border-t border-bloom-line pt-3 text-sm font-medium">
                    <a href="{{ route('login') }}" class="bloom-mobile-link">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="bloom-mobile-link">Tạo tài khoản</a>
                </div>
            @endauth
        </div>
    </div>
</header>

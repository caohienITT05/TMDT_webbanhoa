<footer class="mt-16 bg-bloom-plum text-white">
    <div class="bloom-shell grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
        <div class="lg:col-span-1">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-white">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/15"><x-customer.icon name="flower" class="h-5 w-5" /></span>
                <span class="font-display text-2xl font-semibold">BloomGift</span>
            </a>
            <p class="mt-4 max-w-xs text-sm leading-6 text-white/70">Những bó hoa và món quà được gửi đi bằng sự trân trọng, cho mọi khoảnh khắc muốn nói thành lời.</p>
        </div>
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-white/70">Khám phá</h2>
            <ul class="mt-4 space-y-3 text-sm text-white/80">
                <li><a class="bloom-footer-link" href="{{ route('products.index') }}">Sản phẩm</a></li>
                <li><a class="bloom-footer-link" href="{{ route('categories') }}">Danh mục hoa</a></li>
                <li><a class="bloom-footer-link" href="{{ route('custom.order') }}">Đặt hoa theo yêu cầu</a></li>
                <li><a class="bloom-footer-link" href="{{ route('favorites.index') }}">Sản phẩm yêu thích</a></li>
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-white/70">Tài khoản</h2>
            <ul class="mt-4 space-y-3 text-sm text-white/80">
                @auth
                    <li><a class="bloom-footer-link" href="{{ route('profile.edit') }}">Thông tin cá nhân</a></li>
                    <li><a class="bloom-footer-link" href="{{ route('customer.orders') }}">Đơn hàng của tôi</a></li>
                @else
                    <li><a class="bloom-footer-link" href="{{ route('login') }}">Đăng nhập</a></li>
                    <li><a class="bloom-footer-link" href="{{ route('register') }}">Tạo tài khoản</a></li>
                @endauth
                <li><a class="bloom-footer-link" href="{{ route('cart.index') }}">Giỏ hàng</a></li>
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-white/70">Thông tin</h2>
            <ul class="mt-4 space-y-3 text-sm text-white/80">
                <li><a class="bloom-footer-link" href="{{ route('policies.terms') }}">Điều khoản giao dịch</a></li>
                <li><a class="bloom-footer-link" href="{{ route('policies.returns') }}">Chính sách đổi trả</a></li>
                <li><a class="bloom-footer-link" href="{{ route('policies.privacy') }}">Chính sách bảo mật</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="bloom-shell flex flex-col gap-2 py-5 text-xs text-white/55 sm:flex-row sm:items-center sm:justify-between">
            <span>© {{ now()->year }} BloomGift. All rights reserved.</span>
            <span>Thanh toán theo lựa chọn · Giao hoa theo thông tin đơn hàng</span>
        </div>
    </div>
</footer>

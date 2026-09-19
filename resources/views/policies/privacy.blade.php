<x-customer.layout title="Chính sách bảo mật">
    <x-customer.breadcrumb :items="[
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Chính sách bảo mật'],
    ]" />

    <section class="bloom-shell pb-14 sm:pb-20">
        <div class="mx-auto max-w-3xl">
            <p class="bloom-eyebrow">Thông tin BloomGift</p>
            <h1 class="bloom-title mt-2">Chính sách bảo vệ dữ liệu cá nhân</h1>
            <div class="bloom-policy bloom-panel bloom-panel--padded mt-8 sm:p-9">
                <p class="!mt-0 italic">Tuân thủ theo Nghị định số 13/2023/NĐ-CP về Bảo vệ dữ liệu cá nhân của Chính phủ.</p>

                <h2>1. Loại dữ liệu thu thập</h2>
                <p>Để phục vụ quá trình xử lý đơn hàng và giao nhận hoa, BloomGift chỉ thu thập các trường thông tin cần thiết:</p>
                <ul>
                    <li>Họ và tên của người đặt hàng và người nhận hoa.</li>
                    <li>Số điện thoại và địa chỉ giao nhận hoa chi tiết.</li>
                    <li>Địa chỉ thư điện tử (Email) để gửi thông báo xác nhận đơn và nhắc dịp lễ.</li>
                    <li>Thông tin lịch sử đơn hàng và mã giao dịch thanh toán trực tuyến.</li>
                </ul>

                <h2>2. Mục đích xử lý dữ liệu</h2>
                <ul>
                    <li>Thực hiện liên lạc xác nhận và điều phối người giao hàng đến đúng địa chỉ và khung giờ.</li>
                    <li>Xử lý thanh toán, hóa đơn và giải quyết các khiếu nại phát sinh.</li>
                    <li>Gửi email thông báo về trạng thái chuẩn bị đơn hoa hoặc chương trình khuyến mại (khi được đồng ý).</li>
                </ul>

                <h2>3. Cam kết an toàn và bảo mật thông tin</h2>
                <p>BloomGift áp dụng các biện pháp kỹ thuật tiêu chuẩn (mã hóa mật khẩu một chiều, phòng chống SQL Injection, CSRF Token và bảo mật phiên Session) để ngăn chặn truy cập trái phép. Tuyệt đối không chia sẻ, bán hoặc chuyển giao dữ liệu khách hàng cho bên thứ ba vì mục đích thương mại.</p>

                <h2>4. Quyền của chủ thể dữ liệu</h2>
                <p>Khách hàng có toàn quyền xem, chỉnh sửa thông tin cá nhân trong mục <strong>Tài khoản của tôi</strong> hoặc gửi yêu cầu xóa tài khoản khỏi hệ thống bất cứ lúc nào qua email hỗ trợ.</p>
            </div>
        </div>
    </section>
</x-customer.layout>

<x-customer.layout title="Điều khoản giao dịch">
    <x-customer.breadcrumb :items="[
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Điều khoản giao dịch'],
    ]" />

    <section class="bloom-shell pb-14 sm:pb-20">
        <div class="mx-auto max-w-3xl">
            <p class="bloom-eyebrow">Thông tin BloomGift</p>
            <h1 class="bloom-title mt-2">Điều kiện giao dịch chung & thông tin người bán</h1>
            <div class="bloom-policy bloom-panel bloom-panel--padded mt-8 sm:p-9">
                <div class="rounded-lg border-l-4 border-bloom-rose bg-bloom-blush px-5 py-4">
                    <h2 class="!mt-0 !text-base">Thông tin đơn vị bán hàng (BloomGift Shop)</h2>
                    <p class="!mt-2 text-sm">Thông tin dưới đây được sử dụng cho hệ thống demo học tập, không phải công bố pháp lý của một doanh nghiệp đang hoạt động.</p>
                    <ul class="!mt-3 !list-none !pl-0">
                        <li><strong>Đơn vị hiển thị:</strong> Dự án Cửa hàng Hoa tươi BloomGift</li>
                        <li><strong>Mã số thuế:</strong> Chưa cập nhật</li>
                        <li><strong>Địa chỉ liên hệ demo:</strong> Hà Đông, Hà Nội, Việt Nam</li>
                        <li><strong>Hotline hỗ trợ demo:</strong> 0988.123.456 (8h00 - 21h00 hàng ngày)</li>
                        <li><strong>Email liên hệ demo:</strong> support@bloomgift.local</li>
                    </ul>
                </div>

                <h2>1. Nguyên tắc chung</h2>
                <p>Website BloomGift cung cấp dịch vụ đặt hoa tươi, hoa thiết kế theo yêu cầu, giao hàng tận nơi và thiệp chúc mừng. Khách hàng tham gia giao dịch trên website bao gồm cá nhân có đầy đủ năng lực hành vi dân sự.</p>

                <h2>2. Quy trình giao kết hợp đồng điện tử</h2>
                <p>Hợp đồng mua bán được xác lập qua các bước rõ ràng:</p>
                <ol>
                    <li>Khách hàng chọn sản phẩm hoa, chọn ngày và khung giờ nhận hoa.</li>
                    <li>Khách hàng điền thông tin người nhận, ghi chú thiệp chúc mừng.</li>
                    <li>Khách hàng kiểm tra tóm tắt đơn hàng, áp mã khuyến mại (nếu có).</li>
                    <li>Chọn phương thức thanh toán (COD hoặc PayPal Sandbox) và nhấn <strong>"Đặt hàng ngay"</strong> để xác nhận giao kết.</li>
                    <li>Hệ thống gửi mã đơn hàng và lưu vết giao dịch tại mục Chi tiết đơn hàng.</li>
                </ol>

                <h2>3. Giá cả và chi phí giao nhận</h2>
                <p>Tất cả giá hoa niêm yết trên website đã bao gồm chi phí đóng gói tiêu chuẩn và thiệp chúc kèm theo. Phí giao hàng được tính minh bạch theo từng khu vực trước khi khách bấm đặt đơn.</p>

                <h2>4. Thời gian giao nhận</h2>
                <p>Cửa hàng hỗ trợ giao theo các khung giờ đăng ký trong ngày từ 08:00 đến 21:00. Trường hợp có sự cố thời tiết hoặc sự kiện bất khả kháng, BloomGift sẽ liên hệ báo trước tối thiểu 60 phút.</p>

                <h2>5. Hủy đơn hàng</h2>
                <p>Khách hàng có thể gửi yêu cầu hủy đơn trước thời điểm cửa hàng xác nhận chuẩn bị hoa. Với đơn đã chuẩn bị hoặc đã bàn giao giao hàng, việc hủy và hoàn tiền sẽ được xem xét theo tình trạng thực tế của đơn và chính sách đổi trả.</p>

                <h2>6. Trách nhiệm của các bên</h2>
                <p>Khách hàng chịu trách nhiệm cung cấp chính xác thông tin người nhận, địa chỉ và thời gian giao. BloomGift chịu trách nhiệm tiếp nhận đơn, thông báo trạng thái và giao đúng sản phẩm đã xác nhận trong phạm vi điều kiện giao dịch đã công bố.</p>
            </div>
        </div>
    </section>
</x-customer.layout>

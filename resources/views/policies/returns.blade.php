<x-customer.layout title="Chính sách đổi trả">
    <x-customer.breadcrumb :items="[
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Chính sách đổi trả'],
    ]" />

    <section class="bloom-shell pb-14 sm:pb-20">
        <div class="mx-auto max-w-3xl">
            <p class="bloom-eyebrow">Thông tin BloomGift</p>
            <h1 class="bloom-title mt-2">Chính sách đổi trả & hoàn tiền</h1>
            <div class="bloom-policy bloom-panel bloom-panel--padded mt-8 sm:p-9">
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-950">
                    <strong>Lưu ý về hoa tươi:</strong> Hoa tươi là mặt hàng sinh học có đặc tính mau tàn và tính thẩm mỹ thủ công, do đó chính sách đổi trả áp dụng các tiêu chuẩn đặc thù dưới đây.
                </div>

                <h2>1. Trường hợp chấp nhận đổi trả / hoàn tiền</h2>
                <ul>
                    <li>Hoa nhận được bị dập nát, gãy cành, héo úa trên 30% tại thời điểm giao.</li>
                    <li>Giao sai mẫu mã, chủng loại hoa so với ảnh và mô tả đặt hàng.</li>
                    <li>Giao trễ quá 2 giờ so với khung giờ đã hẹn mà không có thông báo thỏa thuận trước.</li>
                    <li>Thiếu quà tặng kèm (thiệp chúc, băng rôn) đã xác nhận trong đơn.</li>
                </ul>

                <h2>2. Thời hạn tiếp nhận khiếu nại</h2>
                <p>Quý khách vui lòng kiểm tra hoa ngay khi nhận và phản hồi khiếu nại trong vòng <strong>04 giờ</strong> kể từ thời điểm giao thành công kèm hình ảnh/video thực tế của sản phẩm.</p>

                <h2>3. Quy trình xử lý</h2>
                <p><strong>Bước 1:</strong> Khách hàng liên hệ hotline hoặc gửi ảnh xác nhận qua hệ thống.</p>
                <p><strong>Bước 2:</strong> BloomGift thẩm định chất lượng hoa trong tối đa 30 phút.</p>
                <p><strong>Bước 3:</strong> Đổi một sản phẩm hoa mới tương đương hoặc hoàn tiền 100%:</p>
                <ul>
                    <li><strong>Với đơn COD:</strong> Chuyển khoản trực tiếp vào tài khoản ngân hàng của khách hàng.</li>
                    <li><strong>Với đơn PayPal:</strong> Hoàn trả trực tiếp qua cổng thanh toán PayPal trong vòng 24 - 48 giờ làm việc.</li>
                </ul>
            </div>
        </div>
    </section>
</x-customer.layout>

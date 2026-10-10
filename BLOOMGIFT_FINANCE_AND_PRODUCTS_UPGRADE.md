# Báo cáo nâng cấp BloomGift: Tài chính và sản phẩm

Ngày kiểm tra cuối: 06/10/2026 (Asia/Saigon)

## 1. Mốc an toàn trước khi sửa

- Commit ban đầu: `2268d56d4f729f6054aafabe1c9af81b9826cde9` trên nhánh `do-an-co-so-cntt`.
- Working tree ban đầu sạch.
- Test ban đầu: **48 test đạt, 149 assertions**.
- Backup trước migration: `storage/app/private/upgrade-backups/20261005-062316`.
- Số liệu trước nâng cấp: 8 users, 10 products, 26 orders, 31 order_items, 26 payments, 2 custom_order_requests.
- Không chạy `migrate:fresh`, `migrate:refresh`, `db:wipe`, truncate, reset hoặc lệnh xóa dữ liệu.

Laravel Boost không được cài vì môi trường thực chạy Laravel 12.69.1/PHP 8.2 nhưng composer.json/lock yêu cầu Laravel 13/PHP >= 8.3. Người dùng đã cho phép bỏ qua riêng bước Boost để không nâng cấp framework ngoài phạm vi.

## 2. Phần Tài chính đã hoàn thành

- Menu và module riêng **Tài chính & Lợi nhuận**, không thay Dashboard cũ.
- Doanh thu tự động đọc từ đơn hàng, không cần nhập lại. Mốc ban đầu là **10.929.000đ từ 16 đơn**. Khi đơn `BG20260923104247ONP0` trị giá 529.000đ chuyển sang đã thanh toán/hoàn thành lúc 16:27:43, báo cáo tự tăng lên **11.458.000đ từ 17 đơn**; 3.600.000đ của 9 đơn còn lại được ghi là chờ ghi nhận.
- Bộ lọc hôm nay, 7 ngày, tháng, quý, năm và khoảng ngày tùy chọn.
- Phân tách đơn `completed` (thực hiện) khỏi `pending/confirmed/processing/shipping` (dự kiến).
- Đơn `cancelled` không ghi doanh thu; chỉ trừ chi phí trực tiếp đã được Admin ghi nhận.
- Các card doanh thu, doanh thu thuần, COGS, lợi nhuận gộp, ship thu khách, ship thực tế, đóng gói, phí thanh toán, chi phí trực tiếp khác, chi phí vận hành, lợi nhuận trước thuế, thuế dự tính/thực tế, lợi nhuận sau thuế và biên lợi nhuận.
- Biểu đồ doanh thu, giá vốn, chi phí trực tiếp và lợi nhuận trực tiếp theo ngày, viết bằng canvas nội bộ nên không thêm CDN/hotlink.
- Trang bổ sung giá vốn lịch sử và chi phí thực tế từng đơn.
- Khi đơn chuyển sang `completed`, hệ thống tự tạo/hoàn thiện bản ghi chi phí theo cấu hình. Dữ liệu thực tế quản trị viên đã nhập được giữ nguyên; thao tác lặp lại không làm nhân đôi chi phí.
- Trang **Cấu hình tài chính** quản lý chi phí vận chuyển, đóng gói, COD, phí PayPal và đơn vị tiền tệ mặc định.
- Trang thêm/sửa/lọc chi phí vận hành; không có loại ship/payment để giảm nguy cơ ghi trùng với `order_costs`.
- Trang cấu hình quy tắc thuế theo thời gian và hồ sơ thuế theo kỳ; chặn khoảng thời gian giao nhau.
- Báo “Thiếu dữ liệu chi phí” và trả lợi nhuận `null` khi thiếu giá vốn snapshot hoặc một chi phí trực tiếp.
- Chênh lệch do Checkout cũ gộp tiền thiệp/gói quà vào tổng đơn được hiển thị để đối chiếu, không tự phân bổ.

## 3. Migration và schema mới

Bốn migration bổ sung đã chạy đúng thứ tự, không migration nào bị chạy lại:

- `2026_10_05_000001_add_finance_module` (batch 10).
- `2026_10_05_000002_complete_finance_workflow` (batch 11).
- `2026_10_06_000003_add_2026_enterprise_tax_compliance` (batch 12).
- `2026_10_06_000004_complete_2026_b2c_tax_profile` (batch 13).

- `products.cost_price DECIMAL(15,2) NULL`.
- `order_items.cost_price_snapshot DECIMAL(15,2) NULL`.
- Bảng `order_costs`: mỗi order tối đa một bản ghi; ship, đóng gói, phí cổng thanh toán, chi phí trực tiếp khác đều nullable để phân biệt “chưa biết” với 0.
- Bảng `expenses`: mã chi phí, loại, mô tả, số tiền, ngày chi, ghi chú và người tạo.
- Bảng `tax_settings`: loại/tên thuế, cơ sở tính, thuế suất, ngày hiệu lực và trạng thái.
- Bảng `tax_records`: kỳ, loại thuế, số tiền chịu thuế, thuế suất, thuế dự tính, thuế thực tế và trạng thái.
- Bảng `tax_profiles`: loại chủ thể, mô hình B2C, ngành, năm thuế, trạng thái doanh nghiệp mới, điều kiện loại trừ, doanh thu ghi nhận/dự kiến/quyết toán/năm trước, phương pháp VAT và trạng thái hóa đơn điện tử.
- Bảng `product_tax_profiles`: mapping VAT riêng theo sản phẩm, hỗ trợ không chịu thuế, 0%, 5%, 8%, 10%, tùy chỉnh, giá gồm/chưa gồm VAT và khoảng hiệu lực.
- Bảng `product_cost_histories`: lưu giá vốn cũ/mới, thời điểm hiệu lực, lý do và Admin thực hiện.
- Bảng `finance_settings`: cấu hình mặc định để hoàn thiện chi phí khi đơn hoàn thành.
- `order_items.cost_price_snapshot_source` lưu nguồn snapshot; `order_costs.source` và `financial_finalized_at` lưu nguồn/thời điểm chốt dữ liệu.
- `tax_settings.description` và `legal_reference` bổ sung diễn giải/căn cứ do Admin cung cấp.
- Quy tắc TNDN lưu mức cơ sở 15% khi thuộc diện chịu thuế; trạng thái miễn được đánh giá riêng thay vì ghi thuế suất 0%.
- Migration chỉ ADD COLUMN/CREATE TABLE/INDEX/FOREIGN KEY; có `down()` cho phần bổ sung.

## 4. Công thức và đơn vị tính

Service tính nội bộ bằng số nguyên đơn vị 1/100 VNĐ để tránh sai số cộng dồn của số thực.

- `product_revenue = SUM(order_items.price × quantity)`.
- `cogs = SUM(order_items.cost_price_snapshot × quantity)`.
- `net_product_revenue = product_revenue - orders.discount`.
- `gross_profit = net_product_revenue - cogs`.
- `order_profit_before_overhead = net_product_revenue + shipping_income - cogs - shipping_cost_actual - packaging_cost_actual - payment_gateway_fee_actual - other_direct_cost`.
- `period_profit_before_tax = SUM(order_profit_before_overhead) - operating_expenses - recorded_cancelled_costs`.
- `net_profit_after_tax = period_profit_before_tax - actual_corporate_income_tax` chỉ khi thuế TNDN thực tế của đúng kỳ đã được xác nhận.
- Giá đã gồm VAT: `VAT = gross × rate / (100 + rate)`; ví dụ 108.000đ ở mức 8% có VAT 8.000đ.
- Giá chưa gồm VAT: `VAT = net × rate / 100`; ví dụ 100.000đ ở mức 8% có VAT 8.000đ.
- VAT đầu ra được báo cáo riêng, không bị trừ thêm khỏi lợi nhuận sau thuế TNDN nên không phát sinh double counting.
- Nếu chưa có thuế thực tế, chỉ hiển thị **Lợi nhuận sau thuế dự tính** từ cấu hình/hồ sơ dự tính.

Test mẫu bắt buộc cho kết quả **410.000đ** đã đạt.

## 5. Snapshot giá vốn

`OrderItem::creating` đọc `products.cost_price` đúng thời điểm tạo dòng đơn và lưu vào `cost_price_snapshot`. Giá vốn sản phẩm thay đổi sau này không làm đổi đơn cũ. Nếu giá vốn sản phẩm chưa có, snapshot giữ `null`, quy trình thanh toán vẫn tiếp tục và báo cáo đánh dấu thiếu dữ liệu. Quản trị viên chỉ được bổ sung snapshot còn thiếu; không ghi đè snapshot lịch sử đã tồn tại. Mỗi lần đổi giá vốn sản phẩm, hệ thống lưu lịch sử giá cũ/mới, lý do và người sửa.

## 6. Nhập chi phí thật và thuế

- Admin vào **Tài chính & Lợi nhuận → Chi phí đơn hàng / lịch sử** để nhập snapshot còn thiếu, ship thực trả, đóng gói, phí cổng thanh toán và chi phí trực tiếp khác.
- Để trống nghĩa là chưa biết; nhập 0 nghĩa là đã xác nhận không phát sinh.
- Chi phí vận hành chung được nhập ở trang **Chi phí vận hành**.
- Engine thuế TNDN 2026 tự đánh giá trạng thái miễn và các mức 15%/17%/20% theo doanh thu cùng điều kiện hồ sơ; quy tắc tùy chỉnh vẫn hỗ trợ `effective_from/effective_to`.
- Giá trị `actual_tax` chỉ được dùng khi hồ sơ ở trạng thái đã xác nhận/đã nộp hoặc đã xác nhận miễn và kỳ khớp chính xác bộ lọc.
- VAT có mapping riêng theo sản phẩm. Mức 8% hiện chỉ là fallback cấu hình cho sản phẩm chưa phân loại, không phải kết luận pháp lý cho toàn bộ catalog.
- Mục **Tài chính & Lợi nhuận** nằm cuối sidebar Admin, trong nhóm **Báo cáo**.
- Ngày hiển thị trên bảng, biểu đồ và file xuất dùng thứ tự ngày/tháng/năm.
- Dashboard có nút xuất CSV theo kỳ đang xem. File gồm tổng hợp thực hiện/dự kiến, toàn bộ đơn trong kỳ, chi phí vận hành, cấu hình/hồ sơ thuế và giá bán/giá vốn sản phẩm. Giá trị chưa có tiếp tục được ghi là `Thiếu dữ liệu`.
- Bản xuất dữ liệu mới nhất đã được tạo lại sau khi hoàn thiện thuế: `storage/app/private/finance-reports/du-lieu-tai-chinh-01-01-2026-den-06-10-2026.csv` (13.686 byte), gồm số liệu Finance, TNDN/VAT tách riêng và giá bán, giá khuyến mãi, giá vốn, tồn kho, mùa vụ, trạng thái bán của 46 sản phẩm.
- Hai lệnh backfill `bloomgift:backfill-product-costs` và `bloomgift:backfill-finance` đã chạy thật sau khi dry-run đạt. Dry-run cuối xác nhận 0 sản phẩm thiếu giá vốn, 0 dòng đơn thiếu snapshot, 0 đơn hoàn thành thiếu `order_costs` và 0 đơn chưa chốt.

## 7. THUẾ DOANH NGHIỆP B2C – 2026

Hồ sơ demo chính thức là **doanh nghiệp thành lập theo pháp luật Việt Nam**, mô hình **B2C**, ngành bán lẻ trực tuyến hoa và quà, mới thành lập trong năm thuế 2026. Giao diện công khai các giả định: doanh nghiệp độc lập, không thuộc quan hệ liên kết hoặc công ty con làm mất điều kiện, không kinh doanh ngành chịu thuế TNDN đặc biệt, không khai thác dầu khí/tài nguyên và được áp dụng quy định doanh nghiệp thông thường.

Căn cứ đang hiển thị cho bản demo gồm Luật Thuế TNDN số 67/2025/QH15, Nghị định 320/2025/NĐ-CP, Nghị định 141/2026/NĐ-CP áp dụng từ 01/01/2026, Luật Thuế GTGT số 48/2024/QH15, Nghị quyết 204/2025/QH15 và Nghị định 70/2025/NĐ-CP về hóa đơn điện tử. Đây là mô phỏng quản trị; nghĩa vụ thật vẫn cần đối chiếu hồ sơ kế toán, pháp lý và nhóm hàng tại kỳ tính thuế.

### TNDN

- Doanh thu đánh giá không quá 1 tỷ đồng và đủ điều kiện: trạng thái `estimated_exempt` / **Dự kiến được miễn**, thuế dự tính 0đ. Miễn thuế là trạng thái riêng, không phải thuế suất 0%.
- Trên 1 tỷ đến 3 tỷ đồng: 15% nếu đủ điều kiện.
- Trên 3 tỷ đến 50 tỷ đồng: 17% nếu đủ điều kiện.
- Trên 50 tỷ đồng hoặc có điều kiện loại trừ: 20%.
- Engine phân biệt doanh thu ghi nhận hiện tại, doanh thu dự kiến năm, doanh thu quyết toán và doanh thu năm trước. Nếu doanh thu dự kiến hoặc quyết toán vượt 1 tỷ đồng, hệ thống tự rời trạng thái miễn và tính lại theo mức phù hợp.
- Dữ liệu thật hiện tại: doanh thu ghi nhận 11.458.000đ; doanh thu dự kiến đang dùng cùng số này vì chưa có kế hoạch riêng; quyết toán chưa có; trạng thái **Dự kiến được miễn**; mức áp dụng nếu chịu thuế 15%; thuế TNDN dự tính 0đ; thuế thực tế thiếu dữ liệu.

### VAT

- VAT tách hoàn toàn khỏi TNDN. Lợi nhuận sau thuế TNDN không trừ VAT đầu ra thêm lần nữa.
- Mapping theo sản phẩm hỗ trợ không chịu thuế, 0%, 5%, 8%, 10% và tùy chỉnh; mỗi mapping có phương pháp giá và ngày hiệu lực.
- Hiện 0/46 sản phẩm đã được phân loại. 46/46 sản phẩm đang dùng fallback demo 8% và UI cảnh báo rõ đây không phải kết luận mọi sản phẩm chịu VAT 8%.
- Giá đang cấu hình đã bao gồm VAT, nên công thức dùng `gross × 8 / 108`. Cơ sở VAT từ 9 đơn hoàn thành là 7.079.000đ; VAT đầu ra tham khảo là **524.370,37đ**. VAT đầu vào và VAT thực tế chưa có dữ liệu.

### Hóa đơn điện tử và giới hạn

- Trạng thái tích hợp: **Chưa kết nối · đang mô phỏng**; có 17 đơn ghi nhận cần theo dõi hóa đơn.
- Không tạo API giả và chưa gửi dữ liệu tới cơ quan thuế. Khi vận hành thật phải tích hợp giải pháp hóa đơn điện tử hợp lệ.
- Admin cần phân loại VAT từng sản phẩm, nhập doanh thu kế hoạch/quyết toán và hồ sơ thuế thực tế trước khi dùng cho kê khai.

### Kiểm thử thuế

- Có test trạng thái miễn không quá 1 tỷ, tự rời miễn khi vượt 1 tỷ, các mức 15%/17%/20%, điều kiện loại trừ, doanh nghiệp mới, doanh thu dự kiến/quyết toán và trạng thái miễn đã xác nhận.
- Có test công thức VAT giá gồm thuế 8/108, giá chưa gồm thuế 8%, mapping sản phẩm không sửa dữ liệu Product và VAT không làm giảm lợi nhuận sau thuế TNDN lần hai.
- Finance/Tax: **18 test đạt, 144 assertions**; full suite: **72 test đạt, 659 assertions**.

## 8. Sản phẩm mở rộng

Đã thêm **36 sản phẩm mới** bằng dữ liệu demo do người dùng cho phép: mỗi sản phẩm có giá bán, giá vốn riêng, giá sale, tồn kho dương, mùa vụ, trạng thái đang bán và một ảnh WebP riêng. Không dùng một tỷ lệ giá vốn chung. Tổng số sản phẩm hiện tại là 46; 46/46 có giá vốn, giá sale và tồn kho.

Seeder đã chạy bằng class riêng:

```powershell
php artisan db:seed --class=BloomGift36ProductsSeeder
```

Seeder bỏ qua slug đã tồn tại và không cập nhật/xóa sản phẩm cũ. Lần chạy lặp lại trong test không tạo bản ghi trùng.

| # | Sản phẩm | Giá bán | Giá vốn demo | Giá sale | Tồn kho | Ảnh |
|---:|---|---:|---:|---:|---:|---|
| 1 | Bó hồng đỏ Eternal Love | 550.000 đ | 310.000 đ | 499.000 đ | 24 | `bo-hong-do-eternal-love.webp` |
| 2 | Bó hồng phấn Dịu Êm | 480.000 đ | 270.000 đ | 439.000 đ | 28 | `bo-hong-phan-diu-em.webp` |
| 3 | Bó hồng trắng Thuần Khiết | 520.000 đ | 295.000 đ | 469.000 đ | 18 | `bo-hong-trang-thuan-khiet.webp` |
| 4 | Tulip hồng Ban Mai | 750.000 đ | 445.000 đ | 689.000 đ | 16 | `tulip-hong-ban-mai.webp` |
| 5 | Tulip vàng Nắng Mới | 720.000 đ | 410.000 đ | 659.000 đ | 14 | `tulip-vang-nang-moi.webp` |
| 6 | Tulip tím Mộng Mơ | 850.000 đ | 520.000 đ | 779.000 đ | 12 | `tulip-tim-mong-mo.webp` |
| 7 | Hướng dương Sunny Day | 390.000 đ | 205.000 đ | 349.000 đ | 30 | `huong-duong-sunny-day.webp` |
| 8 | Baby trắng Mây Nhỏ | 290.000 đ | 145.000 đ | 259.000 đ | 35 | `baby-trang-may-nho.webp` |
| 9 | Baby hồng Kẹo Ngọt | 320.000 đ | 165.000 đ | 289.000 đ | 27 | `baby-hong-keo-ngot.webp` |
| 10 | Cẩm tú cầu Biển Xanh | 590.000 đ | 340.000 đ | 539.000 đ | 16 | `cam-tu-cau-bien-xanh.webp` |
| 11 | Cẩm tú cầu Tím Chiều | 620.000 đ | 360.000 đ | 569.000 đ | 14 | `cam-tu-cau-tim-chieu.webp` |
| 12 | Mẫu đơn Hồng Ngọc | 1.250.000 đ | 790.000 đ | 1.149.000 đ | 8 | `mau-don-hong-ngoc.webp` |
| 13 | Lily trắng Thanh Tao | 650.000 đ | 365.000 đ | 589.000 đ | 15 | `lily-trang-thanh-tao.webp` |
| 14 | Lily hồng Rạng Rỡ | 680.000 đ | 390.000 đ | 619.000 đ | 13 | `lily-hong-rang-ro.webp` |
| 15 | Bó hoa Pastel Harmony | 790.000 đ | 455.000 đ | 719.000 đ | 18 | `bo-hoa-pastel-harmony.webp` |
| 16 | Cúc họa mi An Nhiên | 350.000 đ | 175.000 đ | 319.000 đ | 32 | `cuc-hoa-mi-an-nhien.webp` |
| 17 | Cẩm chướng Tri Ân | 420.000 đ | 220.000 đ | 379.000 đ | 26 | `cam-chuong-tri-an.webp` |
| 18 | Cát tường Thanh Xuân | 460.000 đ | 250.000 đ | 419.000 đ | 22 | `cat-tuong-thanh-xuan.webp` |
| 19 | Giỏ hoa Sinh Nhật Hồng | 890.000 đ | 520.000 đ | 819.000 đ | 12 | `gio-hoa-sinh-nhat-hong.webp` |
| 20 | Giỏ hoa Sunshine | 850.000 đ | 475.000 đ | 779.000 đ | 14 | `gio-hoa-sunshine.webp` |
| 21 | Hộp hoa Trái Tim Đỏ | 1.150.000 đ | 680.000 đ | 1.059.000 đ | 9 | `hop-hoa-trai-tim-do.webp` |
| 22 | Hộp hoa Luxury Ivory | 1.800.000 đ | 1.120.000 đ | 1.659.000 đ | 6 | `hop-hoa-luxury-ivory.webp` |
| 23 | Giỏ hoa Chúc Mừng | 1.450.000 đ | 860.000 đ | 1.329.000 đ | 8 | `gio-hoa-chuc-mung.webp` |
| 24 | Hộp hoa Garden Pastel | 1.350.000 đ | 810.000 đ | 1.239.000 đ | 9 | `hop-hoa-garden-pastel.webp` |
| 25 | Chocolate Sweet Moments | 250.000 đ | 120.000 đ | 229.000 đ | 40 | `chocolate-sweet-moments.webp` |
| 26 | Gấu bông Teddy Hug | 350.000 đ | 165.000 đ | 319.000 đ | 25 | `gau-bong-teddy-hug.webp` |
| 27 | Nến thơm Amber Glow | 280.000 đ | 115.000 đ | 249.000 đ | 34 | `nen-thom-amber-glow.webp` |
| 28 | Gift Box Trà & Nến | 690.000 đ | 370.000 đ | 629.000 đ | 20 | `gift-box-tra-nen.webp` |
| 29 | Set thiệp Floral Notes | 150.000 đ | 55.000 đ | 129.000 đ | 50 | `set-thiep-floral-notes.webp` |
| 30 | Set quà Hồng & Chocolate | 950.000 đ | 535.000 đ | 869.000 đ | 14 | `set-qua-hong-chocolate.webp` |
| 31 | Valentine Đắm Say | 1.650.000 đ | 1.050.000 đ | 1.519.000 đ | 8 | `valentine-dam-say.webp` |
| 32 | 8 Tháng 3 Rực Rỡ | 980.000 đ | 575.000 đ | 899.000 đ | 12 | `8-thang-3-ruc-ro.webp` |
| 33 | 20 Tháng 10 Yêu Thương | 1.050.000 đ | 620.000 đ | 959.000 đ | 11 | `20-thang-10-yeu-thuong.webp` |
| 34 | Ngày Của Mẹ Dịu Dàng | 1.100.000 đ | 655.000 đ | 999.000 đ | 10 | `ngay-cua-me-diu-dang.webp` |
| 35 | Kỷ Niệm Golden Love | 2.500.000 đ | 1.580.000 đ | 2.299.000 đ | 5 | `ky-niem-golden-love.webp` |
| 36 | Cảm Ơn Little Joy | 220.000 đ | 105.000 đ | 199.000 đ | 36 | `cam-on-little-joy.webp` |

## 9. Ảnh sản phẩm

- 36/36 ảnh tạo bằng OpenAI image generation; không dùng ảnh Internet.
- WebP 800×800, quality 86, tổng dung lượng 2.629.822 byte.
- Lưu source có thể commit trong `public/images/expanded`; khi seeder chạy sẽ sao chép vào disk `public/products/expanded`.
- Kiểm tra decode: 36/36 hợp lệ.
- Kiểm tra HTTP: 36/36 trả 200 OK.
- Nguồn và prompt từng ảnh: [PRODUCT_IMAGE_SOURCES.md](PRODUCT_IMAGE_SOURCES.md).

## 10. Mùa vụ sản phẩm

- Tái sử dụng cột `products.season`; không tạo schema trùng.
- Command `bloomgift:backfill-product-seasons` chỉ sửa trường `season`, có `--dry-run` và idempotent.
- Trước backfill: 46/46 sản phẩm là `all`. Sau backfill: Mùa xuân 10, Mùa hạ 6, Mùa thu 4, Mùa đông 3, Quanh năm 23.
- Dry-run sau cùng báo 0 sản phẩm cần đổi.
- Form tạo/sửa sản phẩm validate đúng 5 giá trị hiện có: `spring`, `summer`, `autumn`, `winter`, `all`; giao diện hiển thị hoàn toàn bằng tiếng Việt.
- Mapping đầy đủ ID/tên/mùa cũ/mùa mới/lý do: `PRODUCT_SEASON_MAPPING.md`.

## 11. File mới

- `database/migrations/2026_10_05_000001_add_finance_module.php`
- `database/migrations/2026_10_05_000002_complete_finance_workflow.php`
- `database/migrations/2026_10_06_000003_add_2026_enterprise_tax_compliance.php`
- `database/migrations/2026_10_06_000004_complete_2026_b2c_tax_profile.php`
- `app/Http/Controllers/Admin/FinanceController.php`
- `app/Models/ProductCostHistory.php`
- `app/Services/FinanceAnalyticsService.php`
- `app/Services/TaxComplianceService.php`
- `app/Console/Commands/BackfillProductCosts.php`
- `app/Console/Commands/BackfillFinance.php`
- `app/Console/Commands/BackfillProductSeasons.php`
- `routes/finance.php`
- `resources/views/admin/finance/*`
- `public/js/finance-chart.js`
- `database/data/bloomgift-expanded-products.json`
- `database/data/product-cost-backfill.json`
- `database/data/product-season-mapping.json`
- `database/seeders/BloomGiftExpandedProductSeeder.php`
- `database/seeders/BloomGift36ProductsSeeder.php`
- `database/seeders/BloomGift2026EnterpriseTaxDemoSeeder.php`
- `public/images/expanded/*.webp` (36 file)
- `tests/Unit/FinanceAnalyticsTest.php`
- `tests/Feature/Finance/FinanceTest.php`
- `tests/Feature/Product/ExpandedProductSeederTest.php`
- `tests/Feature/Product/ProductCostHistoryTest.php`
- `tests/Feature/Product/ProductSeasonTest.php`
- `tests/Feature/Finance/FinanceWorkflowTest.php`
- `PRODUCT_IMAGE_SOURCES.md`, `PRODUCT_SEASON_MAPPING.md`, `CODEX_PROGRESS.md`, file báo cáo này.

## 12. File đã chỉnh sửa

- `app/Models/Product.php`: fillable/cast giá vốn.
- `app/Models/OrderItem.php`: cast, nguồn và hook snapshot nhỏ nhất.
- `app/Http/Controllers/Admin/ProductController.php`: validate giá vốn, ghi lịch sử thay đổi; tạo sản phẩm mới bắt buộc có giá vốn thật.
- `app/Http/Controllers/Admin/OrderController.php`: chỉ thêm lời gọi chốt Finance khi trạng thái chuyển sang `completed`.
- `resources/views/admin/products/create.blade.php`, `edit.blade.php`, `index.blade.php`: nhập/hiển thị giá vốn và mùa vụ tiếng Việt.
- `resources/views/admin/finance/*`: Việt hóa toàn bộ nhãn hiển thị; không đổi internal identifiers.
- `resources/views/layouts/admin.blade.php`: thêm một link Finance.
- `routes/web.php`: nạp route Finance tách riêng.

CheckoutController, PayPalController/Service, COD và CustomOrderController không bị rewrite. Snapshot hoạt động qua model event cho mọi nơi tạo OrderItem. Logic cũ của OrderController được giữ nguyên, chỉ nối thêm bước Finance ở đúng chuyển trạng thái hoàn thành.

## 13. Kiểm thử

- Trước nâng cấp: **48 test đạt, 149 assertions**.
- Sau nâng cấp: **72 test đạt, 659 assertions**.
- Frontend production build: đạt.
- Blade cache/compile: đạt.
- Pint cho các file PHP mới/chạm tới: đạt; `git diff --check`: đạt.
- Kiểm thử giao diện thật bằng trình duyệt: Tài chính, Cấu hình tài chính, Cấu hình & Hồ sơ thuế, Chi phí vận hành và Quản lý sản phẩm đều mở đúng. Doanh thu là 11.458.000đ; lợi nhuận trước thuế 2.598.640đ; TNDN dự tính 0đ với trạng thái Dự kiến được miễn; VAT tham khảo 524.370đ. Trang thuế desktop/mobile không tràn ngang toàn trang; menu ở cuối nhóm Báo cáo; danh sách Admin hiển thị 46 sản phẩm và đủ cột giá bán, giá vốn, giá sale, tồn kho, mùa vụ.
- Đối chiếu độc lập trực tiếp từ database khớp service: doanh thu sản phẩm 7.059.000đ − giảm giá 100.000đ + phí giao hàng khách trả 120.000đ − COGS 3.935.000đ − chi phí trực tiếp khác 545.360đ = **2.598.640đ**.
- Test regression bao phủ đăng nhập/đăng ký, hồ sơ, cart, checkout COD, PayPal Sandbox fake HTTP, order admin, custom order và email.

## 14. Xác nhận bảo toàn dữ liệu

Sau migration, backfill và seeder class riêng:

- users: 8 (không giảm)
- products: 46 (10 sản phẩm cũ giữ nguyên và được bổ sung giá vốn; thêm đúng 36 sản phẩm demo)
- orders: 26 (không giảm)
- order_items: 31 (không giảm)
- payments: 26 (không giảm)
- custom_order_requests: 2 (không giảm)
- product_cost_histories: 10; order_items có snapshot: 31/31; order_costs đã chốt: 9/9 đơn hoàn thành; finance_settings: 1
- season: xuân 10, hạ 6, thu 4, đông 3, quanh năm 23
- expenses: 0; tax_profiles: 1; tax_settings: 2; tax_records: 0; product_tax_profiles: 0

Đối chiếu với bản sao lưu trước migration cho thấy dữ liệu `users`, `products`,
`order_items` và `custom_order_requests` vẫn giữ nguyên. Trong thời gian thực hiện,
đơn hàng và thanh toán ID 19 được chuyển từ `pending/PENDING` sang
`completed/COMPLETED` với thời điểm cập nhật `2026-10-05 06:52:44`; ID 26 chuyển
sang hoàn thành lúc `2026-10-05 16:27:43`. Seeder/backfill không sửa trạng thái
`orders`/`payments`; trạng thái hiện tại được giữ lại thay vì ghi đè bằng dữ liệu
backup. Số lượng bản ghi `orders` và `payments` không thay đổi.

Giá vốn demo được mapping riêng theo từng sản phẩm sau khi người dùng cho phép;
không dùng một tỷ lệ chung. Các snapshot lịch sử chỉ điền vào trường trống và có
nguồn `backfill_current_product`; dữ liệu đã có không bị ghi đè.

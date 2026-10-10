# CODEX PROGRESS

## BASELINE COMMIT

- `2268d56d4f729f6054aafabe1c9af81b9826cde9` trên nhánh `do-an-co-so-cntt`.
- Working tree chứa toàn bộ thay đổi nâng cấp chưa commit; database không bị reset.
- Backup gốc: `storage/app/private/upgrade-backups/20261005-062316`.
- Backup trước dữ liệu demo: `storage/app/private/upgrade-backups/20261005-before-demo-catalog`.
- Backup trước backfill mùa vụ: `storage/app/private/upgrade-backups/20261006-before-season-backfill`.
- Backup trước hồ sơ thuế 2026: `storage/app/private/upgrade-backups/20261006-before-2026-tax-profile`.

## FINANCE STATUS

- Doanh thu tự động ghi nhận: **11.458.000đ / 17 đơn**; không hard-code.
- Giá trị chờ ghi nhận: **3.600.000đ / 9 đơn**.
- 9/9 đơn hoàn thành đã có `OrderCost` và `financial_finalized_at`.
- 31/31 dòng đơn có snapshot giá vốn.
- Lợi nhuận trước thuế đối soát từ database và service: **2.598.640đ**.
- Lợi nhuận sau thuế TNDN dự tính: **2.598.640đ**; VAT được báo cáo riêng và không bị trừ lần hai.

## TAX PROFILE

- **ENTITY:** Doanh nghiệp thành lập theo pháp luật Việt Nam.
- **BUSINESS MODEL:** B2C, bán lẻ trực tuyến hoa và quà trực tiếp cho người tiêu dùng.
- **TAX YEAR:** 2026; doanh nghiệp mới trong năm tính thuế.
- **ANNUAL REVENUE:** ghi nhận hiện tại 11.458.000đ; dự kiến 11.458.000đ do chưa nhập kế hoạch riêng; quyết toán năm chưa có; doanh thu năm trước không áp dụng cho hồ sơ mới.
- **CIT STATUS:** `estimated_exempt` / Dự kiến được miễn; đây là trạng thái miễn, không phải thuế suất 0%.
- **CIT RATE IF TAXABLE:** 15%; engine tự chuyển 15%/17%/20% theo doanh thu và điều kiện loại trừ.
- **CIT ESTIMATED:** 0đ; **CIT ACTUAL:** thiếu dữ liệu, chưa quyết toán.
- **VAT STATUS:** VAT đầu ra tham khảo theo cấu hình demo; tách khỏi TNDN.
- **VAT BASE:** 7.079.000đ từ 9 đơn hoàn thành trong kỳ.
- **VAT ESTIMATED:** 524.370,37đ theo công thức giá đã gồm thuế; **VAT ACTUAL:** thiếu dữ liệu.
- **PRODUCT VAT MAPPING:** 0/46 đã phân loại; 46/46 đang dùng mức mặc định demo 8% và được cảnh báo rõ, không khẳng định toàn bộ danh mục thuộc mức 8%.
- **E-INVOICE:** 17 đơn cần theo dõi; trạng thái chưa kết nối, đang mô phỏng, không có API giả tới cơ quan thuế.
- Căn cứ hiển thị: Luật 67/2025/QH15, Nghị định 320/2025/NĐ-CP, Nghị định 141/2026/NĐ-CP, Luật 48/2024/QH15, Nghị quyết 204/2025/QH15 và Nghị định 70/2025/NĐ-CP.

## MIGRATIONS APPLIED

- Batch 10: `2026_10_05_000001_add_finance_module`.
- Batch 11: `2026_10_05_000002_complete_finance_workflow`.
- Batch 12: `2026_10_06_000003_add_2026_enterprise_tax_compliance`.
- Batch 13: `2026_10_06_000004_complete_2026_b2c_tax_profile`.
- Migration `000003` và seeder thuế không bị chạy lại; `000004` là migration bổ sung duy nhất còn pending và đã chạy đúng một lần.
- Không chạy fresh, refresh, wipe, truncate hoặc drop.

## PRODUCT STATUS

- Tổng số: 46, gồm 10 sản phẩm cũ và đúng 36 sản phẩm mới.
- 46/46 có giá bán, giá khuyến mãi, giá vốn và tồn kho; giá vốn demo được gán riêng theo từng sản phẩm.
- 36/36 ảnh WebP cục bộ 800×800 hợp lệ; trang Admin hiện tại tải 10/10 ảnh của trang đầu, không có ảnh lỗi.
- Mùa vụ giữ nguyên: xuân 10, hạ 6, thu 4, đông 3, quanh năm 23.
- `product_cost_histories`: 10 bản ghi; thay đổi giá vốn mới tạo lịch sử và không đổi snapshot đơn cũ.

## UI STATUS

- Menu **Tài chính & Lợi nhuận** nằm cuối sidebar dưới nhóm **Báo cáo**.
- Giao diện Finance/Tax đã Việt hóa; ngày hiển thị theo `dd/mm/yyyy`.
- Browser đã kiểm tra trực tiếp: Tài chính, Cấu hình tài chính, Cấu hình & Hồ sơ thuế, Chi phí vận hành và Quản lý sản phẩm.
- Trang thuế desktop và mobile không tràn ngang toàn trang; bảng rộng dùng vùng cuộn riêng.
- CSV mới nhất: `storage/app/private/finance-reports/du-lieu-tai-chinh-01-01-2026-den-06-10-2026.csv`.

## TEST STATUS

- Finance/Tax: **18 passed, 144 assertions**.
- Product: **6 passed, 366 assertions**.
- **FULL TEST:** **72 passed, 659 assertions**.
- Frontend production build: đạt.
- Blade compile/cache: đạt.
- `node --check public/js/finance-chart.js`: đạt.
- `git diff --check`: đạt; chỉ có cảnh báo line-ending CRLF/LF của `routes/web.php`.

## DATABASE COUNTS

- users 8; products 46; orders 26; order_items 31; payments 26; custom_order_requests 2.
- order_costs 9; expenses 0; finance_settings 1; product_cost_histories 10.
- tax_profiles 1; tax_settings 2; tax_records 0; product_tax_profiles 0.
- Không bảng nghiệp vụ cũ nào giảm số lượng.

## BROWSER VERIFIED

- **YES — 06/10/2026.** Các số liệu Finance/Tax khớp service và database; trang sản phẩm hiển thị đủ cột giá bán, giá vốn, giá sale, tồn kho, mùa vụ; 46 sản phẩm còn nguyên.

## NEXT ACTION

- Không còn thay đổi bắt buộc cho bản demo. Trước khi vận hành thật, kế toán cần phân loại VAT từng sản phẩm, nhập doanh thu dự kiến/quyết toán và thuế thực tế, rồi kết nối một giải pháp hóa đơn điện tử hợp lệ.

## CHECKLIST GIẢNG VIÊN — BASELINE 07/10/2026

- **BASELINE COMMIT:** `2268d56d4f729f6054aafabe1c9af81b9826cde9` trên nhánh `do-an-co-so-cntt`.
- **FILES CURRENTLY MODIFIED:** các thay đổi Finance/Product hiện hữu được liệt kê bởi `git status`; không reset, không restore và không sửa migration/seeder/dữ liệu trong nhiệm vụ checklist này.
- **BASELINE TEST:** lần chạy đầy đủ ghi nhận 71 test pass và 1 lỗi không ổn định tại `EmailDeliveryTest`; khi chạy độc lập, đúng test email này pass (1 test, 2 assertions). Các nhóm Checkout, PayPal, Product và Finance đang pass.
- **ĐÃ ĐẠT:** trang chủ, chi tiết sản phẩm, giỏ hàng, đăng ký/đăng nhập, checkout, theo dõi đơn, Admin sản phẩm/đơn/người dùng, báo cáo doanh thu, COD, PayPal Sandbox, gợi ý, voucher, yêu thích, email, hashing/role/CSRF/upload/rate limit, trang đổi trả và bảo mật, URL thân thiện.
- **MỘT PHẦN:** danh mục khách hàng chưa render cha/con; danh sách sản phẩm mới có tìm tên và danh mục; Terms thiếu hủy đơn/trách nhiệm và nhãn demo; robots/sitemap hard-code URL local; chưa có mô tả meta/JSON-LD Product; chưa có transaction log/file backup/document marketing.
- **THIẾU/PHẢI XỬ LÝ:** bỏ route public `/fix-db`; xác thực sở hữu đơn ở hai điểm PayPal; hoàn thiện phần checklist còn lại mà không đổi schema hay dữ liệu.
- **DATABASE CHANGES:** NONE.
- **DATA CHANGES:** NONE.

## CHECKLIST GIẢNG VIÊN — HOÀN THÀNH 07/10/2026

- **FILES CHANGED CHO CHECKLIST:** `routes/web.php`, `CheckoutController`, `Admin/OrderController`, `SitemapController`, `BackupDatabase`, `config/logging.php`, customer layout/category/product views, Terms, ba tài liệu trong `docs/`, `SUBMISSION_REQUIREMENTS_AUDIT.md` và `ChecklistCompletionTest`.
- **ĐÃ XỬ LÝ:** `/fix-db` không còn route public; PayPal kiểm tra chủ sở hữu order; UI danh mục cha/con; GET multi-filter; meta description; JSON-LD Product; sitemap/robots lấy `APP_URL`; log giao dịch private; lệnh backup private; nội dung seller demo/Terms; marketing plan và kịch bản quảng cáo.
- **TESTS ADDED:** `tests/Feature/ChecklistCompletionTest.php` — 8 test, 35 assertions cho bảo mật PayPal, hierarchy, filter, SEO, sitemap/robots, backup dry-run và Terms.
- **CURRENT TEST STATUS:** `php artisan test` đạt **80 passed, 694 assertions**. Nhóm Checkout/PayPal/Admin/checklist đạt **19 passed, 82 assertions**. Production build và Blade cache đạt; `git diff --check` đạt.
- **BACKUP VERIFICATION:** `php artisan bloomgift:backup-database --dry-run` đạt, chỉ xác minh cấu hình/mysqldump và không tạo file backup.
- **BROWSER VERIFICATION:** chưa thể hoàn tất bằng dữ liệu live vì MySQL80 dừng. Đã thử khởi động dịch vụ nhưng Windows từ chối; truy cập trang chủ local trả lỗi kết nối `127.0.0.1:3306`. Không thay đổi database để vượt qua blocker này.
- **DATABASE CHANGES:** NONE.
- **DATA CHANGES:** NONE.

## NEXT ACTION

- Khởi động MySQL80 rồi mở lại các trang customer/admin trong browser để hoàn tất bước kiểm tra trực tiếp. Mã nguồn và regression test đã sẵn sàng; không cần migrate hoặc seed lại.

## VOUCHER, TRỢ LÝ GỢI Ý VÀ SO SÁNH FINANCE — 09/10/2026

### Voucher checkout

- **VOUCHER CHECKOUT RESET BUG:** FIXED.
- **ROOT CAUSE:** nút áp mã tạo một form POST riêng rồi `redirect back`, nên toàn bộ dữ liệu checkout chưa gửi bị mất khi trang tải lại.
- **FIX:** áp/bỏ mã qua JSON; giao diện chỉ cập nhật giảm giá và tổng tiền, không reload form.
- **VOUCHER USAGE BUG:** FIXED.
- **USAGE FIELD:** `vouchers.used_count`.
- **LIMIT FIELD:** `vouchers.usage_limit`.
- **UNLIMITED SEMANTICS:** hệ thống hiện không hỗ trợ không giới hạn; mã chỉ hợp lệ khi `used_count < usage_limit`.
- **COD CONSUME POINT:** trong transaction tạo Order/Payment sau khi kiểm tra lại voucher bằng `lockForUpdate`.
- **PAYPAL CONSUME POINT:** sau khi PayPal capture trả về thành công; voucher được khóa đến khi Payment chuyển `completed`, nên callback lặp không cộng lại và callback cạnh tranh không thể capture lượt cuối cùng cùng lúc.
- **IDEMPOTENCY:** metadata voucher của Payment hiện có được dùng để giữ voucher id/code/trạng thái tiêu thụ; không thêm cột hay bảng.
- **TOTAL:** server kiểm tra phí giao/thiệp/gói quà theo danh sách tùy chọn hiện có rồi dùng chung công thức cho COD và PayPal.

### Trợ lý chọn hoa & quà

- Thêm khối trên trang chủ, API `POST /tro-ly-chon-hoa-qua/goi-y` và `GiftAdvisorService` theo keyword/scoring.
- Ưu tiên Product thực, lấy đúng giá/sale price/tồn kho; hàng hết chỉ hiển thị “Tạm hết hàng”.
- Mẫu ngoài catalog chỉ nằm trong `config/gift_advisor.php`, không tạo Product, giá hay tồn kho giả.
- Nút đặt theo yêu cầu chỉ mở form hiện có với nội dung đã điền sẵn; không tự gửi yêu cầu.
- Kết quả luôn tách thành hai phần: **Sản phẩm có sẵn tại BloomGift** và **Ý tưởng chưa có sẵn – đặt theo yêu cầu**. Với câu tìm kiếm không khớp, mỗi phần vẫn có phương án dự phòng phù hợp với nguồn dữ liệu của phần đó.
- Ngân sách nhận cả dạng `200k`, `200.000đ` và `giá 200000`. Sản phẩm vượt ngân sách không được đưa vào phần sản phẩm có sẵn; khi không có mẫu đúng giá, giao diện nói rõ và giữ các phương án đặt riêng.
- Bộ gợi ý nhận diện loại yêu cầu trước khi xếp hạng: `hoa`, `quà`, hoặc `set` (bao gồm `sét`, `combo`, `box`). Hai phần kết quả chỉ dùng đúng một loại đã hỏi; không trộn hoa, quà và set.
- Catalog đặt riêng có 8 mẫu hoa, 8 món quà và 8 set với thành phần, loại hoa và màu sắc cụ thể. Mỗi lần hiển thị tối đa 4 sản phẩm có sẵn và 6 mẫu ngoài catalog.
- Trợ lý hiện là widget chat thu gọn ở góc dưới màn hình. Khi đóng chỉ còn nút mở chat; khi mở, toàn bộ câu hỏi và kết quả nằm trong một panel cuộn, không chiếm diện tích trang chủ.

### So sánh Finance

- Thêm so sánh tuần/tháng tại Admin > Tài chính & Lợi nhuận. Biểu đồ bám đúng kỳ lọc Hôm nay/7 ngày/Tháng/Quý/Năm/Tùy chọn, các cột cách đều, có nhãn cho từng kỳ và cuộn ngang khi một năm có nhiều tuần.
- Tất cả doanh thu/lợi nhuận lấy qua `FinanceAnalyticsService::getPeriodSummary`; không dùng dữ liệu minh họa hoặc ghi dữ liệu khi mở báo cáo.
- Mỗi biểu đồ có bảng số liệu toàn bộ kỳ (doanh thu, lợi nhuận, biên lợi nhuận) và phần chi tiết cập nhật khi rê chuột lên cột biểu đồ.

### Verification

- **FILES CHANGED:** Checkout/Voucher/Payment, Gift Advisor, Custom Order prefill, Finance service/controller/view/chart và test mới.
- **DATABASE CHANGE:** NONE. Không migration, seed, reset, cập nhật hàng loạt hay xóa dữ liệu trong nhiệm vụ này.
- **TEST RESULT:** 94 passed, 785 assertions (`phpunit` với autoload prepend do lỗi `is_readable` của môi trường PHP local).
- **BROWSER VERIFIED:** một trang chủ local mới trên cổng 8000 hiển thị và gọi được Trợ lý. Câu `Hoa tặng người yêu giá 200000` trả ba Product thực có giá 129.000–200.000đ và ba phương án đặt theo yêu cầu. Câu `Set hoa quà sinh nhật` trả 4 set có sẵn và 6 set đặt riêng, mỗi set nêu rõ thành phần; không có lỗi console. Trang Đặt theo yêu cầu nhận đúng `occasion`, `flower_type`, `budget`. Voucher/Admin Finance cần phiên đăng nhập customer/admin hiện hữu nên đã kiểm bằng Feature Test, không tạo tài khoản/đơn/dữ liệu live để ép kiểm tra.

## PAYMENT & TRỢ LÝ CHAT — 10/10/2026

- **COD BUG:** FIXED.
- **COD ROOT CAUSE:** mã checkout ghi cột `payments.payload` nhưng MySQL thực tế không có cột này; toàn bộ transaction vì thế rollback với lỗi 500.
- **FIX:** bỏ hoàn toàn phụ thuộc vào `payload`; COD vẫn tạo Order, OrderItem, Payment và tăng lượt voucher trong một transaction. PayPal chỉ giữ voucher đã áp dụng trong session của chính đơn đến callback; `payments.status = completed` là mốc idempotency.
- **COD NO VOUCHER:** PASS.
- **COD WITH VOUCHER:** PASS. Có regression test mô phỏng chính xác bảng `payments` không có `payload`.
- **PAYPAL:** PASS bằng HTTP fake cho create/approval, capture thành công, cancel, ownership và trạng thái Order/Payment.
- **PAYPAL VOUCHER:** PASS; capture thất bại không dùng voucher, callback lặp chỉ dùng một lần; amount lấy từ `Order.total` đã tính lại ở server.
- **FULL TEST AFTER PAYMENT & AI CHAT:** 99 passed, 821 assertions.
- **AI CHATBOX:** DONE. Nút nổi và panel chat có tin nhắn hai phía, quick prompts, loading, thẻ Product, fallback và tối đa sáu lượt ngữ cảnh trong frontend/session request; không có bảng chat hay DB mutation.
- **AI PROVIDER:** Không có provider/API key trong project. `GiftAdvisorService` là fallback server-side có cấu trúc, chỉ dựa trên Product thật và catalog mẫu đặt riêng, không bịa Product/giá/tồn kho.
- **PRODUCT DATABASE GROUNDING:** YES — product id, ảnh, giá thường/giá sale, stock, category và URL lấy từ database.
- **CUSTOM SUGGESTIONS:** YES — không có giá hoặc tồn kho giả, có CTA đến form đặt theo yêu cầu.
- **CONVERSATION CONTEXT:** YES — giữ tối đa 6 lượt để xử lý follow-up như màu sắc hoặc ngân sách.
- **CUSTOM ORDER PREFILL:** YES — query chỉ điền sẵn dịp, loại, ngân sách và brief chi tiết; không tự gửi request.
- **DATABASE CHANGE:** NONE.
- **BROWSER VERIFIED:** Chưa thể chạy browser smoke test trong phiên này: local server không lắng nghe cổng 8000/8877 và MySQL `127.0.0.1:3306` từ chối kết nối. Không tạo đơn live chỉ để ép kiểm tra.

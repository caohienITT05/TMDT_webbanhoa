# Đối chiếu yêu cầu tối thiểu — BloomGift

Ngày đối chiếu: 07/10/2026. Phạm vi là checklist nộp bài; không tạo migration, không thay schema và không thay dữ liệu hiện có.

| Yêu cầu | Trạng thái trước | Thay đổi đã thực hiện | Minh chứng | Test | Trạng thái cuối |
| --- | --- | --- | --- | --- | --- |
| Trang chủ, chi tiết sản phẩm, giỏ hàng, đăng ký/đăng nhập, checkout, theo dõi đơn | Đã có | Giữ nguyên | Routes/views hiện hữu | Auth, Cart, Checkout hiện hữu | Đạt |
| Danh mục nhiều cấp | Một phần | Render danh mục cha và danh mục con từ `parent_id` sẵn có | `/danh-muc`, `Category::children` | `ChecklistCompletionTest` | Đạt về hỗ trợ UI; dữ liệu demo hiện có quyết định số nhánh hiển thị |
| Tìm kiếm và lọc nhiều tiêu chí | Một phần | GET: giá, danh mục, mùa vụ, còn hàng, sale, sắp xếp | `/san-pham` và form tiếng Việt | `ChecklistCompletionTest` | Đạt |
| Admin sản phẩm, đơn, người dùng, báo cáo doanh thu | Đã có | Không sửa luồng quản trị hiện có | Controllers/Admin views và Finance | Admin/Finance hiện hữu | Đạt |
| Gợi ý, voucher/khuyến mại, yêu thích, email | Đã có | Không thêm tính năng | Product detail, voucher, favorite, mail | Existing tests | Đạt (ít nhất 3) |
| COD và PayPal Sandbox | Đã có | Giữ nguyên luồng PayPal; thêm kiểm tra chủ sở hữu đơn | `CheckoutController` | `PayPalCheckoutTest`, `ChecklistCompletionTest` | Đạt |
| `/fix-db` không public | Không đạt | Xóa route sửa schema công khai, không chạy logic cũ | `route:list --path=fix-db` không có route | `ChecklistCompletionTest` | Đạt |
| Hash, role, SQL injection, XSS, CSRF, upload, rate limit | Đã có | Không rewrite các lớp bảo vệ hiện có | User cast, middleware, Blade, validation/auth hiện hữu | Auth/Admin/Product hiện hữu | Đạt theo audit source/test hiện có |
| Ghi vết giao dịch | Thiếu | Thêm channel private `transactions` cho tạo đơn, PayPal và đổi trạng thái đơn | `storage/logs/transactions-YYYY-MM-DD.log` | Checkout/PayPal/Admin tests đã sinh log | Đạt |
| Sao lưu database | Thiếu | Thêm `bloomgift:backup-database`; file SQL private; không in password | `app/Console/Commands/BackupDatabase.php`, tài liệu restore | `--dry-run` thực tế và `ChecklistCompletionTest` | Đạt |
| Thông tin người bán, điều khoản, đổi trả, bảo vệ dữ liệu | Một phần | Ghi rõ demo, MST chưa cập nhật; thêm hủy đơn và trách nhiệm | Trang Điều khoản và các policy có sẵn | `ChecklistCompletionTest` | Đạt cho hệ thống demo |
| Xác nhận đơn hàng | Đã có | Giữ nguyên | Checkout và email xác nhận hiện hữu | `CheckoutTest` | Đạt |
| Title, URL thân thiện | Đã có | Giữ nguyên | Layout và routes hiện hữu | Existing routes/views | Đạt |
| Meta description | Thiếu | Bổ sung cho home, danh mục, danh sách và chi tiết sản phẩm | Customer layout/pages | `ChecklistCompletionTest` | Đạt |
| Structured data Product | Thiếu | JSON-LD chỉ dùng trường Product hiện có, không tạo rating/review | Product detail | `ChecklistCompletionTest` | Đạt |
| Sitemap và robots | Một phần | Endpoint động dùng `APP_URL`, gồm home, danh mục, sản phẩm, policy | `SitemapController`, `/sitemap.xml`, `/robots.txt` | `ChecklistCompletionTest` | Đạt |
| Kế hoạch nội dung | Thiếu | Tạo tài liệu kế hoạch 4 tuần/KPI demo | `docs/MARKETING_CONTENT_PLAN.md` | Rà soát tài liệu | Đạt |
| Kịch bản quảng cáo trực tuyến | Thiếu | Tạo kịch bản Facebook/Instagram, CTA, UTM, ngân sách/KPI demo | `docs/ONLINE_AD_CAMPAIGN.md` | Rà soát tài liệu | Đạt |
| Kiểm tra browser trực tiếp | Chưa hoàn tất | Đã thử mở ứng dụng local | MySQL80 đang dừng và Windows từ chối khởi động dịch vụ; trang chủ trả 500 kết nối database | Browser trực tiếp | Một phần — cần khởi động MySQL rồi kiểm tra lại các màn hình |

## Tài liệu nộp kèm

- `docs/DATABASE_BACKUP_RESTORE.md`
- `docs/MARKETING_CONTENT_PLAN.md`
- `docs/ONLINE_AD_CAMPAIGN.md`

## Cam kết phạm vi

- **DATABASE SCHEMA CHANGED:** NO.
- **DATABASE DATA MODIFIED:** NO.
- Không chạy migration, seeder, fresh, refresh, wipe, truncate, backup thật hay restore.

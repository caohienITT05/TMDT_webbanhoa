# Sao lưu và khôi phục cơ sở dữ liệu BloomGift

## Sao lưu

Từ thư mục dự án, chạy:

```powershell
php artisan bloomgift:backup-database
```

Lệnh đọc cấu hình kết nối MySQL hiện có và dùng `mysqldump`. Mật khẩu không được in ra màn hình hay đưa vào tham số dòng lệnh. File `.sql` được đặt tại `storage/app/private/backups` nên không truy cập công khai từ web.

Để chỉ kiểm tra cấu hình và sự sẵn có của `mysqldump`, không tạo file:

```powershell
php artisan bloomgift:backup-database --dry-run
```

Nếu máy không có `mysqldump`, lệnh dừng với lỗi rõ ràng và không báo sao lưu thành công. Trên Windows, lệnh cũng thử đường dẫn cài đặt MySQL Server thông dụng. Có thể cài MySQL client hoặc cấu hình biến môi trường `MYSQLDUMP_BINARY` đến file thực thi phù hợp.

## Kiểm tra file backup

- Xác nhận file có tên dạng `bloomgift-YYYYMMDD-HHMMSS.sql` trong `storage/app/private/backups`.
- Kiểm tra file có kích thước lớn hơn 0 byte.
- Mở bản sao trong môi trường an toàn để kiểm tra phần đầu có cấu trúc SQL của `mysqldump`.

## Khôi phục thủ công

Không chạy khôi phục trực tiếp trên môi trường đang sử dụng. Sao lưu database đích trước, sau đó dùng một database thử nghiệm hoặc môi trường bảo trì:

```powershell
mysql -h <host> -P <port> -u <user> -p <database_name> < storage/app/private/backups/bloomgift-YYYYMMDD-HHMMSS.sql
```

Thay các giá trị trong dấu ngoặc bằng thông tin môi trường đích. Sau khi khôi phục, kiểm tra các bảng `users`, `products`, `orders`, `order_items` và `payments` trước khi cho hệ thống hoạt động lại.

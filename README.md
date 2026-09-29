# 🌸 BloomGift – Website bán hoa và quà tặng trực tuyến

> **Website thương mại điện tử B2C hỗ trợ khách hàng tìm kiếm, lựa chọn, đặt mua hoa tươi và quà tặng cho các dịp đặc biệt.**

BloomGift được xây dựng theo kiến trúc **MVC của Laravel**, sử dụng **MySQL** để quản lý dữ liệu và tích hợp **PayPal Sandbox** để mô phỏng thanh toán trực tuyến. Hệ thống hỗ trợ cả **Customer** và **Administrator**, từ quá trình xem sản phẩm, giỏ hàng, checkout, thanh toán cho đến quản lý đơn hàng.

---

## 📌 Mục lục

- [Giới thiệu](#-giới-thiệu)
- [Mục tiêu](#-mục-tiêu)
- [Tính năng](#-tính-năng)
- [Quy trình đặt hàng](#️-quy-trình-đặt-hàng)
- [Trạng thái đơn hàng](#-trạng-thái-đơn-hàng)
- [Công nghệ sử dụng](#️-công-nghệ-sử-dụng)
- [Kiến trúc hệ thống](#️-kiến-trúc-hệ-thống)
- [Cơ sở dữ liệu](#️-cơ-sở-dữ-liệu)
- [Cài đặt](#-cài-đặt)
- [Cấu hình PayPal Sandbox](#-cấu-hình-paypal-sandbox)
- [Bảo mật](#-bảo-mật)
- [Phạm vi dự án](#-phạm-vi-dự-án)
- [Thành viên](#-thành-viên)
- [Kế hoạch phát triển](#-kế-hoạch-phát-triển)
- [Tài liệu tham khảo](#-tài-liệu-tham-khảo)

---

## 🌷 Giới thiệu

BloomGift là website thương mại điện tử được xây dựng nhằm hỗ trợ khách hàng **tìm kiếm, lựa chọn và đặt mua hoa tươi, quà tặng trực tuyến** cho các dịp như sinh nhật, Valentine, 8/3, 20/10, lễ tốt nghiệp, kỷ niệm, khai trương,...

Khách hàng có thể:

- 🔎 Tìm kiếm và lọc sản phẩm
- 🌹 Xem thông tin chi tiết sản phẩm
- 🛒 Thêm sản phẩm vào giỏ hàng
- ❤️ Quản lý danh sách yêu thích
- 📅 Lựa chọn ngày và khung giờ giao hàng
- 🎁 Lựa chọn gói quà
- 💌 Thêm thiệp và lời chúc
- 🏷️ Sử dụng voucher
- 💳 Thanh toán COD hoặc PayPal Sandbox
- 📦 Theo dõi trạng thái đơn hàng
- ⭐ Đánh giá sản phẩm

Phía quản trị viên có thể quản lý sản phẩm, danh mục, khách hàng, đơn hàng, voucher, thiệp, gói quà, khung giờ giao hàng và các chức năng quản trị liên quan.

---

## 🎯 Mục tiêu

### Mục tiêu tổng quát

Xây dựng một website thương mại điện tử hỗ trợ hoạt động kinh doanh **hoa tươi và quà tặng trực tuyến**, đáp ứng các chức năng cơ bản của một hệ thống bán hàng trực tuyến.

### Mục tiêu cụ thể

- Xây dựng giao diện website bán hoa và quà tặng.
- Xây dựng hệ thống đăng ký, đăng nhập và phân quyền.
- Tìm kiếm và lọc sản phẩm.
- Quản lý giỏ hàng.
- Đặt hàng trực tuyến.
- Lựa chọn ngày và khung giờ giao hàng.
- Thêm thiệp, lời chúc và gói quà.
- Áp dụng voucher.
- Thanh toán COD và PayPal Sandbox.
- Theo dõi trạng thái đơn hàng.
- Đánh giá sản phẩm.
- Quản trị sản phẩm, danh mục, khách hàng và đơn hàng.
- Hỗ trợ báo cáo và thống kê cơ bản.

---

## ✨ Tính năng

### 👤 Customer

| Tính năng | Mô tả |
|---|---|
| 🔐 Đăng ký / Đăng nhập | Tạo tài khoản và truy cập hệ thống |
| 🔎 Tìm kiếm | Tìm sản phẩm theo từ khóa |
| 🏷️ Lọc sản phẩm | Lọc theo danh mục và nhu cầu |
| 🌹 Chi tiết sản phẩm | Xem thông tin và hình ảnh sản phẩm |
| 🛒 Giỏ hàng | Thêm, cập nhật số lượng và xóa sản phẩm |
| ❤️ Wishlist | Lưu sản phẩm yêu thích |
| 📦 Đặt hàng | Tạo đơn hàng trực tuyến |
| 📅 Lịch giao hàng | Chọn ngày và khung giờ giao |
| 🎁 Gói quà | Chọn dịch vụ gói quà |
| 💌 Thiệp | Thêm thiệp và lời chúc |
| 🏷️ Voucher | Áp dụng mã giảm giá |
| 💳 Thanh toán | COD / PayPal Sandbox |
| 📍 Theo dõi đơn | Theo dõi trạng thái đơn hàng |
| ⭐ Đánh giá | Đánh giá sản phẩm đã mua |

### 👨‍💼 Administrator

Admin có thể:

- 📦 Quản lý sản phẩm
- 📂 Quản lý danh mục
- 👥 Quản lý khách hàng
- 🧾 Quản lý đơn hàng
- 🏷️ Quản lý voucher
- 💌 Quản lý thiệp
- 🎁 Quản lý gói quà
- 🕐 Quản lý khung giờ giao hàng
- 📊 Xem báo cáo và thống kê

---

## 🛍️ Quy trình đặt hàng

```text
Đăng nhập
    ↓
Chọn sản phẩm
    ↓
Xem chi tiết sản phẩm
    ↓
Thêm vào giỏ hàng
    ↓
Kiểm tra giỏ hàng / tồn kho
    ↓
Checkout
    ↓
Nhập thông tin người nhận
    ↓
Chọn ngày và khung giờ giao hàng
    ↓
Voucher / Thiệp / Gói quà
    ↓
Chọn phương thức thanh toán
    ↓
Xác nhận đơn hàng
    ↓
Tạo đơn hàng
    ↓
COD hoặc PayPal Sandbox
    ↓
Theo dõi trạng thái đơn hàng
```

Nếu khách hàng chọn **PayPal**, hệ thống chuyển sang PayPal Sandbox để mô phỏng thanh toán, sau đó quay lại hệ thống để cập nhật giao dịch và đơn hàng.

---

## 📦 Trạng thái đơn hàng

Luồng trạng thái chính:

```text
PENDING
   ↓
CONFIRMED
   ↓
PREPARING
   ↓
SHIPPING
   ↓
COMPLETED
```

Trường hợp đơn hàng được hủy hợp lệ:

```text
PENDING
   ↓
CANCELLED
```

| Trạng thái | Ý nghĩa |
|---|---|
| `PENDING` | Chờ xác nhận |
| `CONFIRMED` | Đã xác nhận |
| `PREPARING` | Đang chuẩn bị |
| `SHIPPING` | Đang giao |
| `COMPLETED` | Hoàn thành |
| `CANCELLED` | Đã hủy |

---

## 🛠️ Công nghệ sử dụng

### Backend

- **PHP**
- **Laravel**
- **Laravel MVC**
- **Blade Template**

Laravel đảm nhiệm:

- Routing
- Authentication
- Authorization
- Business Logic
- Controller
- Model
- Database Interaction

### Database

- **MySQL**

Dữ liệu chính gồm:

- Users
- Products
- Categories
- Cart Items
- Orders
- Order Items
- Payments
- Vouchers
- Reviews
- Wishlists
- Delivery Slots
- Gift Cards
- Gift Wraps

### Frontend

- HTML5
- CSS3
- JavaScript
- Blade
- Tailwind CSS
- Vite

### Payment

- **COD**
- **PayPal Sandbox**

### Development Tools

- Visual Studio Code
- Git
- GitHub
- Composer
- npm
- MySQL
- Docker / Docker Compose

---

## 🏗️ Kiến trúc hệ thống

BloomGift được thiết kế theo mô hình **MVC của Laravel**.

```text
┌─────────────────────────┐
│ Customer / Administrator│
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│        Frontend         │
│ Blade / HTML / CSS / JS │
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│         Laravel         │
│           MVC           │
│ Route / Controller      │
│ Model / Business Logic  │
└───────┬───────────┬─────┘
        │           │
        ▼           ▼
┌──────────────┐  ┌───────────────┐
│    MySQL     │  │ PayPal Sandbox│
│   Database   │  │    Payment    │
└──────────────┘  └───────────────┘
```

Luồng xử lý chính:

**User → Frontend → Laravel → MySQL**

Với PayPal:

**Laravel → PayPal Sandbox → Laravel → Payment / Order**

---

## 🗄️ Cơ sở dữ liệu

Các thực thể chính của hệ thống:

```text
USER
CATEGORY
PRODUCT
CART_ITEM
ORDER
ORDER_ITEM
PAYMENT
VOUCHER
REVIEW
WISHLIST
DELIVERY_SLOT
GIFT_CARD
GIFT_WRAP
```

Một số quan hệ chính:

- Một `Category` có nhiều `Product`.
- Một `User` có thể tạo nhiều `Order`.
- Một `Order` có nhiều `OrderItem`.
- Một `Order` có thông tin `Payment`.
- `Product` có thể liên kết với `CartItem`, `OrderItem`, `Review` và `Wishlist`.
- `DeliverySlot` được sử dụng khi khách hàng lựa chọn thời gian giao hàng.
- Voucher, thiệp và gói quà được sử dụng trong quá trình checkout theo chức năng của hệ thống.

---

## 🚀 Cài đặt

### 1. Clone repository

```bash
git clone https://github.com/chien23010209/DO-AN-CO-SO-CNTT.git
cd DO-AN-CO-SO-CNTT
```

### 2. Cài đặt thư viện PHP

```bash
composer install
```

### 3. Cài đặt thư viện frontend

```bash
npm install
```

### 4. Tạo file `.env`

Linux / macOS:

```bash
cp .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

### 5. Tạo Application Key

```bash
php artisan key:generate
```

### 6. Cấu hình MySQL

Mở file `.env` và cấu hình:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bloomgift
DB_USERNAME=root
DB_PASSWORD=
```

Tạo database nếu chưa có:

```sql
CREATE DATABASE bloomgift
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Sau khi thay đổi `.env`, chạy:

```bash
php artisan optimize:clear
```

### 7. Chạy Migration

Đối với **database mới**:

```bash
php artisan migrate
```

Nếu project có dữ liệu mẫu:

```bash
php artisan db:seed
```

Hoặc:

```bash
php artisan migrate --seed
```

> ⚠️ **Không sử dụng `php artisan migrate:fresh` trên database đang chứa dữ liệu chung hoặc dữ liệu cần giữ**, vì lệnh này sẽ xóa toàn bộ bảng trước khi tạo lại.

### 8. Build frontend

Chế độ phát triển:

```bash
npm run dev
```

Hoặc build production:

```bash
npm run build
```

### 9. Chạy Laravel

```bash
php artisan serve
```

Truy cập:

```text
http://127.0.0.1:8000
```

---

## 💳 Cấu hình PayPal Sandbox

BloomGift sử dụng **PayPal Sandbox** để mô phỏng giao dịch thanh toán trong môi trường phát triển.

Cấu hình trong `.env`:

```env
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_client_secret
```

> 🔐 Không commit `Client Secret`, API key, mật khẩu database hoặc các thông tin bảo mật khác lên GitHub.

---

## 🔒 Bảo mật

Hệ thống hướng tới các yêu cầu bảo mật cơ bản:

- Mật khẩu được mã hóa khi lưu trữ.
- Phân quyền giữa `Customer` và `Administrator`.
- Customer không được phép truy cập khu vực quản trị.
- Các chức năng quản trị phải kiểm tra quyền truy cập.
- Dữ liệu đơn hàng và thanh toán phải được kiểm tra trước khi cập nhật.
- Không lưu thông tin bí mật trực tiếp trong source code.
- File `.env` không được commit lên repository công khai.

---

## 📱 Responsive Design

Website được định hướng tương thích với:

- 💻 Desktop
- 💻 Laptop
- 📱 Tablet
- 📱 Mobile

Giao diện hướng tới sự đơn giản, dễ sử dụng và phù hợp với website thương mại điện tử.

---

## 📋 Phạm vi dự án

### Chức năng thuộc phạm vi

- Website thương mại điện tử B2C
- Quản lý tài khoản
- Quản lý sản phẩm
- Quản lý danh mục
- Giỏ hàng
- Wishlist
- Checkout
- Đơn hàng
- Voucher
- Thiệp và lời chúc
- Gói quà
- Lịch giao hàng
- Theo dõi đơn hàng
- Đánh giá sản phẩm
- Khu vực quản trị
- COD
- PayPal Sandbox

### Ngoài phạm vi

Dự án hiện chưa tập trung vào:

- Hệ thống quản lý kho quy mô lớn
- Tích hợp trực tiếp với đơn vị vận chuyển thực tế
- Thanh toán ngân hàng production
- Ứng dụng mobile riêng
- Hệ thống kế toán doanh nghiệp

---

## 👥 Thành viên

Dự án được thực hiện theo hình thức làm việc nhóm.

| STT | Thành viên | Vai trò chính |
|---:|---|---|
| 1 | Thành viên 1 | Core / Authentication / Admin |
| 2 | Thành viên 2 | Customer / Catalog / Giao diện |
| 3 | Thành viên 3 | Cart / Chức năng khách hàng |
| 4 | Thành viên 4 | Checkout / Order / Payment / PayPal |
| 5 | Thành viên 5 | Review / Statistics / Hỗ trợ hoàn thiện |

> Có thể thay tên “Thành viên 1…5” bằng họ tên và MSSV thực tế của từng thành viên trước khi nộp đồ án.

---

## 📅 Kế hoạch phát triển

### Week 1 – Analysis & Design

- Phân tích yêu cầu
- Khảo sát website tương tự
- Xác định Actor
- Xây dựng Use Case
- Thiết kế ERD
- Thiết kế Sitemap
- Thiết kế Wireframe
- Lựa chọn công nghệ

### Week 2 – Development

- Khởi tạo Laravel
- Cấu hình MySQL
- Xây dựng Database
- Authentication
- Product / Category
- Customer
- Cart
- Checkout
- Order
- Admin
- PayPal Sandbox

### Week 3 – Testing & Finalization

- Functional Testing
- Cart Testing
- Checkout Testing
- Order Testing
- Payment Testing
- Authorization Testing
- Responsive Testing
- Bug Fixing
- Hoàn thiện báo cáo
- Chuẩn bị demo

---

## 📚 Tài liệu tham khảo

- Laravel Documentation
- MySQL Documentation
- PayPal Developer Documentation
- FlowerCorner – Website bán hoa trực tuyến
- Dalat Hasfarm Online Shop
- Liti Florist
- Tài liệu môn học và tài liệu phục vụ đồ án

---

## 📄 Mục đích sử dụng

Project được xây dựng phục vụ **mục đích học tập và đồ án**, không phải hệ thống thương mại điện tử production.

---

## 🌸 BloomGift

**BloomGift – Gửi yêu thương qua từng đóa hoa và món quà.**

> *Choose a flower. Add a gift. Send your love.* 💐

# 🌸 BloomGift – Hoa Tươi & Quà Tặng

> **Website thương mại điện tử B2C chuyên cung cấp hoa tươi và quà tặng theo dịp lễ.**

BloomGift là một website thương mại điện tử được xây dựng nhằm hỗ trợ khách hàng **tìm kiếm, lựa chọn và đặt mua hoa tươi và quà tặng trực tuyến** cho các dịp đặc biệt như sinh nhật, Valentine, 8/3, 20/10, lễ tốt nghiệp, kỷ niệm, khai trương,...

Hệ thống được phát triển theo kiến trúc **MVC của Laravel**, sử dụng **MySQL** để quản lý dữ liệu và tích hợp **PayPal Sandbox** để mô phỏng thanh toán trực tuyến.

---

## 📌 Mục lục

* [Giới thiệu](#-giới-thiệu)
* [Mục tiêu](#-mục-tiêu)
* [Tính năng](#-tính-năng)
* [Đối tượng sử dụng](#-đối-tượng-sử-dụng)
* [Quy trình đặt hàng](#-quy-trình-đặt-hàng)
* [Trạng thái đơn hàng](#-trạng-thái-đơn-hàng)
* [Công nghệ sử dụng](#-công-nghệ-sử-dụng)
* [Kiến trúc hệ thống](#-kiến-trúc-hệ-thống)
* [Cơ sở dữ liệu](#-cơ-sở-dữ-liệu)
* [Cài đặt](#-cài-đặt)
* [Cấu hình](#-cấu-hình)
* [Phạm vi dự án](#-phạm-vi-dự-án)
* [Thành viên](#-thành-viên)
* [Kế hoạch phát triển](#-kế-hoạch-phát-triển)
* [Tài liệu tham khảo](#-tài-liệu-tham-khảo)

---

## 🌷 Giới thiệu

BloomGift được xây dựng với mục tiêu tạo ra một nền tảng mua sắm hoa và quà tặng trực tuyến **đơn giản, thuận tiện và dễ sử dụng**.

Khách hàng có thể:

* 🔎 Tìm kiếm và lọc sản phẩm
* 🌹 Xem thông tin chi tiết sản phẩm
* 🛒 Thêm sản phẩm vào giỏ hàng
* ❤️ Quản lý danh sách yêu thích
* 📅 Lựa chọn ngày và khung giờ giao hàng
* 🎁 Lựa chọn gói quà
* 💌 Thêm thiệp và lời chúc
* 🏷️ Sử dụng voucher
* 💳 Thanh toán COD hoặc PayPal Sandbox
* 📦 Theo dõi trạng thái đơn hàng
* ⭐ Đánh giá sản phẩm

Phía quản trị viên có thể quản lý sản phẩm, danh mục, khách hàng, đơn hàng, voucher và các dịch vụ đi kèm.

---

## 🎯 Mục tiêu

### Mục tiêu tổng quát

Xây dựng một website thương mại điện tử hỗ trợ hoạt động kinh doanh **hoa tươi và quà tặng trực tuyến**, đáp ứng các chức năng cơ bản của một hệ thống bán hàng trực tuyến.

### Mục tiêu cụ thể

* Xây dựng giao diện website bán hoa và quà tặng.
* Xây dựng hệ thống đăng ký và đăng nhập.
* Tìm kiếm và lọc sản phẩm.
* Quản lý giỏ hàng.
* Đặt hàng trực tuyến.
* Lựa chọn lịch giao hàng.
* Thêm thiệp và lời chúc.
* Lựa chọn dịch vụ gói quà.
* Áp dụng voucher.
* Thanh toán COD và PayPal Sandbox.
* Theo dõi trạng thái đơn hàng.
* Đánh giá sản phẩm.
* Quản trị sản phẩm và đơn hàng.
* Quản lý khách hàng, voucher và dịch vụ.
* Báo cáo và thống kê cơ bản.

---

## ✨ Tính năng

### 👤 Customer

| Tính năng              | Mô tả                         |
| ---------------------- | ----------------------------- |
| 🔐 Đăng ký / Đăng nhập | Tạo và quản lý tài khoản      |
| 🔎 Tìm kiếm            | Tìm sản phẩm theo từ khóa     |
| 🏷️ Lọc sản phẩm       | Lọc theo danh mục và nhu cầu  |
| 🌹 Chi tiết sản phẩm   | Xem thông tin và hình ảnh     |
| 🛒 Giỏ hàng            | Thêm, sửa, xóa sản phẩm       |
| ❤️ Wishlist            | Lưu sản phẩm yêu thích        |
| 📦 Đặt hàng            | Tạo đơn hàng trực tuyến       |
| 📅 Lịch giao hàng      | Chọn ngày và khung giờ        |
| 🎁 Gói quà             | Lựa chọn dịch vụ gói quà      |
| 💌 Thiệp               | Thêm thiệp và lời chúc        |
| 🏷️ Voucher            | Áp dụng mã giảm giá           |
| 💳 Thanh toán          | COD / PayPal Sandbox          |
| 📍 Theo dõi đơn        | Theo dõi tiến trình giao hàng |
| ⭐ Đánh giá             | Đánh giá sản phẩm đã mua      |

Các chức năng khách hàng được xây dựng dựa trên phạm vi chức năng đã xác định trong tài liệu phân tích của dự án.

---

### 👨‍💼 Administrator

Admin có thể:

* 📦 Quản lý sản phẩm
* 📂 Quản lý danh mục
* 👥 Quản lý khách hàng
* 🧾 Quản lý đơn hàng
* 🏷️ Quản lý voucher
* 💌 Quản lý thiệp
* 🎁 Quản lý gói quà
* 🕐 Quản lý khung giờ giao hàng
* 📊 Xem báo cáo và thống kê

---

## 🛍️ Quy trình đặt hàng

Quy trình mua hàng chính:

```text
Đăng nhập
    ↓
Chọn sản phẩm
    ↓
Thêm vào giỏ hàng
    ↓
Kiểm tra tồn kho
    ↓
Nhập thông tin nhận hàng
    ↓
Chọn ngày giao
    ↓
Chọn khung giờ giao
    ↓
Voucher / Thiệp / Gói quà
    ↓
Chọn phương thức thanh toán
    ↓
Xác nhận đơn hàng
    ↓
Tạo đơn hàng
    ↓
Theo dõi trạng thái đơn
```

Nếu khách hàng chọn PayPal, hệ thống thực hiện thêm bước thanh toán và xác nhận giao dịch thông qua **PayPal Sandbox**.

---

## 📦 Trạng thái đơn hàng

BloomGift sử dụng các trạng thái:

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

| Trạng thái  | Ý nghĩa       |
| ----------- | ------------- |
| `PENDING`   | Chờ xác nhận  |
| `CONFIRMED` | Đã xác nhận   |
| `PREPARING` | Đang chuẩn bị |
| `SHIPPING`  | Đang giao     |
| `COMPLETED` | Hoàn thành    |
| `CANCELLED` | Đã hủy        |

---

## 🛠️ Công nghệ sử dụng

### Backend

* **PHP**
* **Laravel**
* Laravel MVC

Laravel đảm nhiệm:

* Routing
* Authentication
* Business Logic
* Controller
* Model
* Database Interaction

### Database

* **MySQL**

Dữ liệu được sử dụng để quản lý:

* Users
* Products
* Categories
* Carts
* Orders
* Payments
* Vouchers
* Reviews

### Frontend

* HTML5
* CSS3
* JavaScript
* Bootstrap hoặc Tailwind CSS

### Payment

* **PayPal Sandbox**

PayPal Sandbox được sử dụng để mô phỏng thanh toán trực tuyến trong quá trình phát triển và kiểm thử.

### Development Tools

* Visual Studio Code
* XAMPP
* Git
* GitHub
* MySQL

---

## 🏗️ Kiến trúc hệ thống

BloomGift được thiết kế theo kiến trúc **MVC của Laravel**.

```text
┌─────────────────────┐
│       Customer      │
│   / Administrator   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│      Frontend       │
│ HTML / CSS / JS     │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│       Laravel       │
│        MVC          │
│                     │
│ Route / Controller  │
│ Model / Business    │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│        MySQL        │
│      Database       │
└─────────────────────┘

           │
           │ Payment
           ▼
┌─────────────────────┐
│   PayPal Sandbox    │
└─────────────────────┘
```

Luồng xử lý chính của hệ thống:

**User → Frontend → Laravel → MySQL**

Đối với thanh toán PayPal:

**Laravel → PayPal Sandbox → Laravel → Payment/Order**.

---

## 🗄️ Cơ sở dữ liệu

Các thực thể chính của hệ thống:

```text
CATEGORY
PRODUCT
CART
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

* Một `Category` có nhiều `Product`.
* Một `User` có thể tạo nhiều `Order`.
* Một `Order` bao gồm nhiều `OrderItem`.
* Một `Order` có thông tin `Payment`.
* `Product` liên kết với `CartItem`, `OrderItem`, `Review` và `Wishlist`.
* `Voucher` và `DeliverySlot` được liên kết với `Order`.

---

## 🚀 Cài đặt

### 1. Clone repository

```bash
git clone https://github.com/USERNAME/BloomGift.git

cd BloomGift
```

> Thay `USERNAME/BloomGift` bằng đường dẫn repository thực tế của bạn.

### 2. Cài đặt dependencies

```bash
composer install
```

### 3. Tạo file `.env`

```bash
cp .env.example .env
```

Trên Windows có thể tạo bản sao `.env.example` và đổi tên thành:

```text
.env
```

### 4. Tạo Application Key

```bash
php artisan key:generate
```

### 5. Cấu hình Database

Mở file `.env` và cấu hình MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bloomgift
DB_USERNAME=root
DB_PASSWORD=
```

Tạo database:

```sql
CREATE DATABASE bloomgift;
```

### 6. Chạy Migration

```bash
php artisan migrate
```

Nếu project có Seeder:

```bash
php artisan db:seed
```

Hoặc:

```bash
php artisan migrate --seed
```

### 7. Chạy Laravel

```bash
php artisan serve
```

Sau đó truy cập:

```text
http://127.0.0.1:8000
```

---

## 💳 Cấu hình PayPal Sandbox

BloomGift sử dụng **PayPal Sandbox** để mô phỏng giao dịch thanh toán trong môi trường phát triển.

Các thông tin cấu hình PayPal nên được đặt trong `.env`:

```env
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_client_secret
PAYPAL_MODE=sandbox
```

> **Lưu ý:** Không commit `Client Secret`, API key hoặc các thông tin bảo mật lên GitHub.

---

## 🔒 Bảo mật

Hệ thống định hướng đáp ứng một số yêu cầu bảo mật cơ bản:

* Mật khẩu được mã hóa khi lưu trữ.
* Phân quyền giữa `Customer` và `Administrator`.
* Customer không được truy cập khu vực Admin.
* Kiểm tra quyền đối với các chức năng quản trị.
* Đảm bảo tính toàn vẹn dữ liệu đơn hàng.
* Không lưu thông tin bí mật trực tiếp trong source code.

Các yêu cầu phi chức năng và quy tắc phân quyền được xác định trong tài liệu phân tích của dự án.

---

## 📱 Responsive Design

Website được định hướng tương thích với:

* 💻 Desktop
* 💻 Laptop
* 📱 Tablet
* 📱 Mobile

Giao diện ưu tiên sự đơn giản, dễ sử dụng và hỗ trợ SEO.

---

## 📋 Phạm vi dự án

### Đã triển khai / định hướng triển khai

* Website thương mại điện tử B2C.
* Quản lý tài khoản.
* Quản lý sản phẩm.
* Giỏ hàng.
* Wishlist.
* Checkout.
* Đơn hàng.
* Voucher.
* Thiệp và lời chúc.
* Gói quà.
* Lịch giao hàng.
* Theo dõi đơn hàng.
* Đánh giá sản phẩm.
* Admin.
* COD.
* PayPal Sandbox.

### Ngoài phạm vi

Dự án hiện chưa bao gồm:

* Hệ thống quản lý kho quy mô lớn.
* Tích hợp trực tiếp với đơn vị vận chuyển.
* Thanh toán ngân hàng thực tế.
* Ứng dụng mobile riêng.
* Hệ thống kế toán doanh nghiệp.

---

## 👥 Thành viên

Dự án được thực hiện bởi **05 thành viên** trong thời gian **03 tuần**.

| STT | Thành viên   | Vai trò                              |
| --: | ------------ | ------------------------------------ |
|   1 | Thành viên 1 | Phân tích yêu cầu, Backend, kiểm thử |
|   2 | Thành viên 2 | UI/UX, Trang chủ, Danh mục           |
|   3 | Thành viên 3 | Use Case, Cart, Checkout, Order      |
|   4 | Thành viên 4 | ERD, Database, Migration, Model      |
|   5 | Thành viên 5 | Sitemap, Wireframe, Admin, Báo cáo   |

Phân công chi tiết được xây dựng theo kế hoạch 3 tuần của nhóm.

---

## 📅 Kế hoạch phát triển

### Week 1 – Analysis & Design

* Phân tích yêu cầu
* Khảo sát website tương tự
* Xác định Actor
* Xây dựng Use Case
* Thiết kế ERD
* Thiết kế Sitemap
* Thiết kế Wireframe
* Lựa chọn công nghệ

### Week 2 – Development

* Khởi tạo Laravel
* Cấu hình MySQL
* Xây dựng Database
* Authentication
* Product
* Category
* Cart
* Checkout
* Order
* Admin
* PayPal Sandbox

### Week 3 – Testing & Finalization

* Functional Testing
* Cart Testing
* Order Testing
* Payment Testing
* Authorization Testing
* Responsive Testing
* Bug Fixing
* Hoàn thiện báo cáo
* Chuẩn bị Demo

---

## 📚 Tài liệu tham khảo

* FlowerCorner – Website bán hoa trực tuyến.
* Dalat Hasfarm Online Shop.
* Liti Florist.
* Laravel Documentation.
* MySQL Documentation.
* PayPal Developer Documentation.
* Tài liệu và slide môn học Thương mại điện tử.

---

## 📄 License

This project is developed for **educational purposes** as part of the E-commerce course project.

---

## 🌸 BloomGift

**BloomGift – Gửi yêu thương qua từng đóa hoa và món quà.**

> *Choose a flower. Add a gift. Send your love.* 💐

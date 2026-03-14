# Hướng dẫn Cài Đặt và Sử Dụng trên InfinityFree

## 1. Cấu trúc Thư mục
Dưới đây là sơ đồ cấu trúc thư mục chính của dự án:
```text
dichvu_php/
├── api/                # Các API xử lý nạp tiền, giỏ hàng, khuyến mãi
│   ├── sepay_webhook.php # Xử lý Webhook từ SePay
│   └── cart_api.php      # API xử lý giỏ hàng
├── assets/             # Tài nguyên tĩnh (CSS, JS, Images)
├── config/             # Cấu hình hệ thống (Database, Env)
├── includes/           # Các hàm bổ trợ, PHPMailer, Logic nạp tiền
│   ├── functions.php     # Các hàm hệ thống dùng chung
│   └── sepay_api.php     # Tích hợp API SePay
├── migrations/         # Các file SQL để khởi tạo Database
├── uploads/            # Thư mục chứa ảnh tải lên (sản phẩm, v.v.)
├── views/              # Chứa các giao diện hiển thị (Blade-like logic)
│   ├── admin/            # Giao diện quản trị viên
│   ├── auth/             # Giao diện đăng nhập, đăng ký, quên mk
│   ├── layout/           # Layout chung (Header, Footer, Sidebar)
│   └── user/             # Giao diện người dùng (Lịch sử, Nạp tiền)
├── index.php           # Front Controller - Điểm tiếp nhận mọi request
└── .htaccess           # Cấu hình rewrite URL sạch (Friendly URL)
```

## 2. Chức năng Chính
Hệ thống được xây dựng với các tính năng cốt lõi sau:
- **Hệ thống Người dùng**: Đăng ký, đăng nhập, bảo mật 2 lớp, quên mật khẩu qua Email.
- **Quản lý Sản phẩm**: Hiển thị danh sách sản phẩm, chi tiết sản phẩm, quản lý kho hàng (stock).
- **Hệ thống Nạp tiền Tự động**: Tích hợp **SePay** để xác nhận nạp tiền qua ngân hàng tự động 24/7.
- **Giỏ hàng & Thanh toán**: Xử lý mua hàng nhanh chóng, áp dụng mã giảm giá.
- **Bảng điều khiển Admin**: Quản lý thành viên, đơn hàng, sản phẩm, danh mục, và thống kê doanh thu.
- **URL Thân thiện**: Sử dụng kiến trúc PHP Router đơn giản để tạo URL sạch (VD: `/login`, `/admin/users`).

## 3. Upload Code Lên Hosting
1. Đăng nhập vào htdocs của bạn qua FTP hoặc File Manager trên InfinityFree.
2. Xóa các file mặc định (như `index2.html` nếu có).
3. Upload toàn bộ nội dung trong thư mục `dichvu_php` (bao gồm cả file `.htaccess`, thư mục `config`, `includes`, `views` và `index.php`) lên thư mục `htdocs`.

## 2. Cấu hình Database
1. Truy cập **Control Panel** -> **MySQL Databases** trên InfinityFree.
2. Tạo một Database mới (ví dụ: `epiz_12345678_dichvu_db`).
3. Truy cập **phpMyAdmin**.
4. Import file SQL trong thư mục `migrations/` vào Database này.
5. Tạo file `.env` từ file `.env.example` (nếu có) hoặc cấu hình trực tiếp trong `config/database.php`.

## 4. Quản Trị Hệ Thống (Admin)
- Truy cập vào đường dẫn: `tenmien.epizy.com/login`
- Đăng nhập bằng tài khoản Admin mặc định có trong database:
  - Tài khoản: `admin`
  - Mật khẩu: `123456`
- Sau khi đăng nhập, bạn có thể click vào nút **Quản Trị** trên thanh menu để vào trang quản lý (Dashboard, Thành viên, Danh mục, Sản phẩm, Đơn hàng...).

### Một số lưu ý về Bảo Mật & URL:
- Project đã sử dụng kiến trúc **Front Controller**. 
- Mọi request đều được gửi qua `index.php` do `.htaccess` xử lý. 
- URL của bạn sẽ trông rất sạch sẽ (VD: `domain.com/login`, `domain.com/history`, `domain.com/admin/products`).
- Tuyệt đối không ai có thể truy cập trực tiếp vào các file thực thi bên trong thư mục `views/` hay `includes/`.

Chúc bạn thành công với dự án này!

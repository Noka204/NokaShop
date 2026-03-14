
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

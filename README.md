# Hướng dẫn Cài Đặt và Sử Dụng trên InfinityFree

## 1. Upload Code Lên Hosting
1. Đăng nhập vào htdocs của bạn qua FTP hoặc File Manager trên InfinityFree.
2. Xóa các file mặc định (như `index2.html` nếu có).
3. Upload toàn bộ nội dung trong thư mục `dichvu_php` (bao gồm cả file `.htaccess`, thư mục `config`, `includes`, `views` và `index.php`) lên thư mục `htdocs`.

## 2. Cấu hình Database
1. Truy cập **Control Panel** -> **MySQL Databases** trên InfinityFree.
2. Tạo một Database mới (ví dụ: `epiz_12345678_dichvu_db`).
3. Truy cập **phpMyAdmin**.
4. Import file SQL mà bạn đã cung cấp ở đầu vào Database này (nhớ xóa bỏ 2 dòng `CREATE DATABASE...` và `USE...` đi để tránh lỗi phân quyền trên host free).
5. Mở file `config/database.php` và thay đổi các thông tin kết nối thành thông tin của InfinityFree:
   ```php
   $host = 'sqlXXX.epizy.com'; // Ví dụ
   $dbname = 'epiz_12345678_dichvu_db';
   $user = 'epiz_12345678';
   $pass = 'mật_khẩu_hosting_của_bạn';
   ```

## 3. Quản Trị Hệ Thống (Admin)
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

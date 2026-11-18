# UniStationery – Hệ Thống Thương Mại Điện Tử Chuyên Doanh Văn Phòng Phẩm

Dự án học phần Thương mại Điện tử – Khoa Công nghệ Thông tin và Kinh tế số, Học viện Ngân hàng.

## 1. Đường dẫn sản phẩm chạy thực tế (Live Demo)
- **Website công khai:** https://unistationery.free.nf
- **Tài khoản Quản trị viên (Admin):** `admin@gmail.com`
- **Tài khoản Nhân viên (Staff):** `nhanvien1@gmail.com`
- **Tài khoản Khách hàng (Customer):** `vuduydai020405@gmail.com`

## 2. Công nghệ & Môi trường sử dụng
- **Môi trường phát triển:** XAMPP (Apache 2.4, PHP 8.x, MySQL / MariaDB)
- **Backend:** PHP (PDO Extension, Prepared Statements, Database Transactions)
- **Frontend:** HTML5, CSS3, Bootstrap 5, CSS Grid & Flexbox, JavaScript

## 3. Hướng dẫn cài đặt trên XAMPP
1. Tải hoặc giải nén thư mục mã nguồn vào đường dẫn `C:\xampp\htdocs\unistationery`.
2. Mở **XAMPP Control Panel**, nhấn **Start** tại 2 dịch vụ **Apache** và **MySQL**.
3. Truy cập `http://localhost/phpmyadmin`, tạo cơ sở dữ liệu tên **`unistationery`** và nhập (Import) tệp `unistationery.sql` đính kèm trong thư mục gốc.
4. Kiểm tra thông số kết nối trong `includes/database.php` (`host = localhost`, `dbname = unistationery`, `user = root`, `password = ''`).
5. Mở trình duyệt và truy cập: `http://localhost/unistationery/`
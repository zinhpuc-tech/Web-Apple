<?php
// PHP/db_connect.php
$host     = 'localhost';
$dbname   = 'itronic_db';
$username = 'root';
$password = ''; // XAMPP mặc định để trống

// 1. Nên dùng try-catch hoặc kiểm tra kỹ lỗi kết nối
$conn = new mysqli($host, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    // Trong môi trường học tập, die() là ổn. 
    // Nhưng sau này nên ghi log thay vì hiện lỗi trực tiếp ra màn hình.
    die("Lỗi kết nối database: " . $conn->connect_error);
}

// 2. Ép kiểu charset chuẩn để không bị lỗi font tiếng Việt khi lấy từ DB
$conn->set_charset("utf8mb4");

// 3. (Tùy chọn) Thiết lập múi giờ Việt Nam nếu bạn có lưu thời gian đặt hàng/đăng ký
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Không nên đóng thẻ ?> ở cuối file nếu file này chỉ chứa code PHP 
// để tránh lỗi "Headers already sent" do khoảng trắng thừa.
<?php
// PHP/db_connect.php
$host     = 'localhost';
$dbname   = 'itronic_db';
$username = 'root';
$password = ''; 

$conn = new mysqli($host, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Lỗi kết nối database: " . $conn->connect_error);
}

// Thiết lập bộ mã và múi giờ
$conn->set_charset("utf8mb4");
date_default_timezone_set('Asia/Ho_Chi_Minh');

// KHÔNG để các hàm loadCart hay saveCart ở đây để tránh lỗi chồng chéo
?>
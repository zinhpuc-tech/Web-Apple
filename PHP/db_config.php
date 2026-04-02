<?php
$host = "localhost";
$user = "root";
$pass = ""; // Mặc định XAMPP để trống
$dbname = "itronic_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");
?>
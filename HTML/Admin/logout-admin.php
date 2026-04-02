<?php
session_start();
// Xóa sạch dữ liệu phiên làm việc
session_unset();
session_destroy();

// Quay lại trang đăng nhập (kiểm tra lại đường dẫn này)
header("Location: admin-login.php");
exit();
?>
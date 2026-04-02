<?php
session_start();

// Lưu lại giỏ hàng tạm thời vào một biến
$temp_cart = $_SESSION['cart'] ?? [];

// Xóa các thông tin định danh người dùng
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_role']);
// Bạn có thể thêm các biến khác nếu có (email, avatar, v.v.)

// Nạp lại giỏ hàng vào session mới (để nó không bị mất)
$_SESSION['cart'] = $temp_cart;

// Nếu bạn dùng Cookie backup như tôi hướng dẫn ở câu trước, 
// hãy đảm bảo Cookie vẫn tồn tại (đừng xóa nó ở đây).

// Quay về trang đăng nhập
header("Location: ../HTML/User/Sign.php");
exit();
?>
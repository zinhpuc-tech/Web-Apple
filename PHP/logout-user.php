<?php
session_start();
include '../PHP/db_connect.php';     // ← Chỉnh đường dẫn cho đúng (tùy vị trí file của bạn)

// ====================== LOGOUT USER ======================
if (isset($_SESSION['user_id'])) {

    // 1. LƯU GIỎ HÀNG HIỆN TẠI VÀO DATABASE TRƯỚC KHI LOGOUT
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        saveCartToDB($conn, $_SESSION['user_id'], $_SESSION['cart']);
    }

    // 2. XÓA TOÀN BỘ SESSION
    session_unset();
    session_destroy();
}

// 3. XÓA COOKIE BACKUP CỦA KHÁCH (nếu có)
setcookie('itronic_cart_backup', '', time() - 3600, '/');

// 4. Chuyển về trang đăng nhập
header("Location: ../HTML/User/Sign.php");
exit();
?>
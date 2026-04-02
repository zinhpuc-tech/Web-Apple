<?php
session_start();
include __DIR__ . '/../../PHP/db_config.php';

// Kiểm tra quyền Admin trước khi cho phép update
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("Bạn không có quyền thực hiện hành động này.");
}

if (isset($_POST['btn_save'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);

    // Câu lệnh UPDATE database thực tế
    $sql = "UPDATE orders SET status = '$new_status' WHERE id = $order_id";

    if ($conn->query($sql)) {
        // Sau khi update xong, quay lại trang quản lý đơn hàng
        header("Location: orders.php?msg=success");
        exit();
    } else {
        echo "Lỗi cập nhật: " . $conn->error;
    }
} else {
    header("Location: orders.php");
    exit();
}
?>
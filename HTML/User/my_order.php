<?php
session_start();
include "../../PHP/db_connect.php";

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: Sign.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy danh sách đơn hàng + sản phẩm
$sql = "SELECT o.id as order_id, o.status, o.created_at,
               oi.product_name, oi.quantity, oi.price,
               p.image_url
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = $user_id
        ORDER BY o.created_at DESC";

$result = $conn->query($sql);

// Hàm xử lý ảnh
function resolveImageSrc($url) {
    if (!$url) return 'https://via.placeholder.com/100x80';
    if (preg_match('#^https?://#i', $url)) return $url;
    return '/Web-Apple/' . ltrim($url, './');
}

// Hàm hiển thị trạng thái
function getStatusText($status) {
    switch ($status) {
        case 'pending': return 'Chờ duyệt';
        case 'processing': return 'Đang giao';
        case 'completed': return 'Hoàn tất';
        case 'cancelled': return 'Đã hủy';
        default: return $status;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đơn hàng của tôi</title>

<style>
.container { max-width: 1100px; margin: 40px auto; padding: 20px; }

.order-item {
    display: grid;
    grid-template-columns: 100px 2fr 1fr 1fr;
    gap: 20px;
    align-items: center;
    padding: 20px 0;
    border-bottom: 1px solid #eee;
}

.order-item img {
    width: 100%;
    height: 80px;
    object-fit: contain;
    background: #f5f5f7;
    border-radius: 8px;
}

.status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-align: center;
}

.pending { background: #fff1e0; color: #ff9500; }
.processing { background: #e3f2fd; color: #0071e3; }
.completed { background: #e3f9e5; color: #34c759; }
.cancelled { background: #ffe5e5; color: #ff3b30; }

.order-group {
    margin-bottom: 40px;
    padding: 20px;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}
.return_btn{
    padding: 15px;
    background-color: black;
    color: white;
    border-radius: 8px;
}
.return_btn:hover{
    transform: scale(1.06);
}
</style>
</head>

<body>
<div class="container">
    <h1>Đơn hàng của bạn</h1>

    <?php
    if ($result && $result->num_rows > 0):

        $current_order = null;

        while ($row = $result->fetch_assoc()):
            
            // Nếu là đơn mới
            if ($current_order != $row['order_id']):
                if ($current_order !== null) echo "</div>"; // đóng group cũ

                $current_order = $row['order_id'];
    ?>
                <div class="order-group">
                    <h3>Đơn hàng #<?= $row['order_id'] ?></h3>
                    <p style="color:#86868b;">
                        <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                    </p>
    <?php endif; ?>

            <div class="order-item">
                <img src="<?= resolveImageSrc($row['image_url']) ?>">

                <div>
                    <b><?= htmlspecialchars($row['product_name']) ?></b><br>
                    <?= number_format($row['price'], 0, ',', '.') ?>đ
                </div>

                <div>Số lượng: <?= $row['quantity'] ?></div>

                <div>
                    <span class="status <?= $row['status'] ?>">
                        <?= getStatusText($row['status']) ?>
                    </span>
                </div>
            </div>

    <?php
        endwhile;
        echo "</div>"; // đóng group cuối

    else:
    ?>
        <p>Chưa có đơn hàng nào.</p>
    <?php endif; ?>
    <button type="button" onclick="window.location.href='homepage.php'" class="return_btn">
        Quay về
    </button>
</div>

</body>
</html>
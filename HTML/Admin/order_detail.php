<?php
session_start();
include __DIR__ . '/../../PHP/db_config.php';

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Bạn không có quyền truy cập!'); window.location='../HTML/User/Sign.php';</script>";
    exit();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. Lấy thông tin đơn hàng và khách hàng
$sql_order = "SELECT o.*, u.fullname, u.email 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.id = $order_id";
$order_res = $conn->query($sql_order);
$order_data = $order_res->fetch_assoc();

if (!$order_data) {
    die("<script>alert('Đơn hàng không tồn tại!'); window.location='orders.php';</script>");
}

// 3. Lấy danh sách sản phẩm - Đã sửa cột p.image_url theo ảnh của bạn
$sql_items = "SELECT oi.*, p.name, p.image_url 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = $order_id";
$items = $conn->query($sql_items);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../hinhanh/apple-icon.ico">
    <title>Itronic - Chi tiết đơn hàng #<?= $order_id ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --apple-blue: #0071e3; --apple-dark: #1d1d1f; 
            --apple-gray: #86868b; --apple-bg: #f5f5f7; 
            --sidebar-width: 260px; --danger: #ff3b30; --success: #34c759;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--apple-bg); display: flex; min-height: 100vh; color: var(--apple-dark); }

        /* SIDEBAR FIXED ĐỒNG BỘ */
        .sidebar { 
            width: var(--sidebar-width); background: var(--apple-dark); color: white; 
            position: fixed; top: 0; left: 0; height: 100vh; z-index: 1000; padding: 20px 0;
        }
        .logo-admin { 
            text-align: center; padding: 20px; font-size: 22px; font-weight: bold; 
            border-bottom: 1px solid #333; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .menu a { display: flex; align-items: center; padding: 15px 25px; color: #ddd; text-decoration: none; transition: 0.3s; font-size: 14px; }
        .menu a i { margin-right: 12px; width: 20px; text-align: center; }
        .menu a:hover, .menu a.active { background: var(--apple-blue); color: white; }

        /* MAIN CONTENT */
        .main-content { flex: 1; margin-left: var(--sidebar-width); padding: 40px; width: calc(100% - var(--sidebar-width)); }
        
        .btn-back { 
            display: inline-flex; align-items: center; gap: 8px; color: var(--apple-gray); 
            text-decoration: none; font-weight: 600; margin-bottom: 25px; transition: 0.2s;
        }
        .btn-back:hover { color: var(--apple-dark); }

        .detail-card { 
            background: white; border-radius: 24px; padding: 40px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02);
        }

        .order-header { display: flex; justify-content: space-between; border-bottom: 1px solid #f2f2f7; padding-bottom: 30px; margin-bottom: 30px; }
        
        .status-badge { 
            padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; 
            text-transform: uppercase; background: #e3f2fd; color: var(--apple-blue); 
        }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: var(--apple-gray); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #eee; }
        td { padding: 20px 15px; border-bottom: 1px solid #f9f9f9; font-size: 15px; }
        
        .product-img { width: 70px; height: 70px; object-fit: contain; border-radius: 12px; background: #f5f5f7; padding: 5px; }

        .total-row { margin-top: 40px; text-align: right; }
        .total-label { color: var(--apple-gray); font-size: 14px; }
        .total-value { font-size: 28px; font-weight: 700; color: var(--apple-blue); margin-top: 5px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-admin"><i class="fa-brands fa-apple"></i> <span>Itronic Admin</span></div>
    <div class="menu">
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a href="users.php"><i class="fas fa-users"></i> <span>Quản lý người dùng</span></a>
        <a href="products.php"><i class="fas fa-box"></i> <span>Quản lý sản phẩm</span></a>
        <a href="import-goods.php"><i class="fas fa-file-import"></i> <span>Nhập kho hàng</span></a>
        <a href="inventory.php"><i class="fas fa-warehouse"></i> <span>Tồn kho</span></a>
        <a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> <span>Đơn đặt hàng</span></a>
        <a href="../../PHP/logout-admin.php" style="color: var(--danger); border-top: 1px solid #333; margin-top: 30px;">
            <i class="fas fa-sign-out-alt"></i> <span>Đăng xuất</span>
        </a>
    </div>
</div>

<div class="main-content">
    <a href="orders.php" class="btn-back"><i class="fas fa-chevron-left"></i> Quay lại danh sách</a>
    
    <div class="detail-card">
        <div class="order-header">
            <div>
                <h2 style="font-size: 28px; letter-spacing: -0.5px;">Đơn hàng #<?= $order_id ?></h2>
                <p style="color: var(--apple-gray); margin-top: 5px;">
                    Khách hàng: <b style="color: var(--apple-dark);"><?= htmlspecialchars($order_data['fullname']) ?></b> 
                    • <?= htmlspecialchars($order_data['email']) ?>
                </p>
            </div>
            <div style="text-align: right;">
                <div style="margin-bottom: 10px; color: var(--apple-gray); font-size: 13px;">Trạng thái đơn hàng</div>
                <span class="status-badge"><?= $order_data['status'] ?></span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Tên sản phẩm</th>
                    <th>Đơn giá</th>
                    <th>Số lượng</th>
                    <th style="text-align: right;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php while($item = $items->fetch_assoc()): ?>
                <tr>
                    <td style="width: 100px;">
                        <img src="../../<?= htmlspecialchars($item['image_url']) ?>" class="product-img" onerror="this.src='../../hinhanh/default.png'">
                    </td>
                    <td>
                        <div style="font-weight: 600;"><?= htmlspecialchars($item['name']) ?></div>
                    </td>
                    <td><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                    <td>x<?= $item['quantity'] ?></td>
                    <td style="text-align: right; font-weight: 700;">
                        <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="total-row">
            <div class="total-label">Tổng giá trị đơn hàng</div>
            <div class="total-value"><?= number_format($order_data['total_amount'], 0, ',', '.') ?>đ</div>
            <p style="font-size: 13px; color: var(--apple-gray); margin-top: 10px;">Đã bao gồm thuế GTGT và phí vận chuyển (nếu có).</p>
        </div>
    </div>
</div>

</body>
</html>
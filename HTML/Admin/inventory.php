<?php
session_start();
include __DIR__ . '/../../PHP/db_config.php';

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Bạn không có quyền truy cập!'); window.location='../HTML/User/Sign.php';</script>";
    exit();
}

// 2. Lấy dữ liệu thống kê kho thực tế
$total_q = $conn->query("SELECT 
    SUM(quantity) as total_qty, 
    SUM(quantity * cost_price) as total_value 
    FROM products");
$stats = $total_q->fetch_assoc();

// Lấy danh sách sản phẩm (Ưu tiên hàng sắp hết lên đầu)
$products = $conn->query("SELECT * FROM products ORDER BY quantity ASC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../hinhanh/apple-icon.ico">
    <title>Itronic - Quản lý tồn kho</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --apple-blue: #0071e3; 
            --apple-dark: #1d1d1f; 
            --apple-gray: #86868b; 
            --apple-bg: #f5f5f7; 
            --sidebar-width: 260px;
            --danger: #ff3b30;
            --success: #34c759;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--apple-bg); display: flex; min-height: 100vh; color: var(--apple-dark); }

        /* SIDEBAR FIXED - KHỚP VỚI DASHBOARD */
        .sidebar { 
            width: var(--sidebar-width); 
            background: var(--apple-dark); 
            color: white; 
            position: fixed; 
            height: 100vh; 
            padding: 20px 0;
            z-index: 1000;
        }

        .logo-admin { 
            text-align: center; padding: 20px; font-size: 22px; font-weight: bold; 
            border-bottom: 1px solid #333; margin-bottom: 20px; 
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }

        .menu a { 
            display: flex; align-items: center; padding: 15px 25px; 
            color: #ddd; text-decoration: none; transition: 0.3s; font-size: 14px; 
        }

        .menu a i { margin-right: 12px; width: 20px; text-align: center; font-size: 16px; }
        .menu a:hover, .menu a.active { background: var(--apple-blue); color: white; }

        /* MAIN CONTENT - ĐẨY SANG PHẢI */
        .main-content { 
            flex: 1; 
            margin-left: var(--sidebar-width); 
            padding: 40px; 
        }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        .header h2 { font-size: 28px; font-weight: 700; }

        /* THẺ THỐNG KÊ NHANH */
        .inventory-summary { display: flex; gap: 20px; margin-bottom: 30px; }
        .summary-card { 
            background: white; flex: 1; padding: 25px; border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02);
        }
        .summary-card h3 { font-size: 12px; color: var(--apple-gray); text-transform: uppercase; margin-bottom: 10px; }
        .summary-card h2 { font-size: 28px; font-weight: 700; }

        /* BẢNG TỒN KHO */
        .table-container { 
            background: white; border-radius: 20px; padding: 20px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); 
        }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #fbfbfd; color: var(--apple-gray); font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #f2f2f2; font-size: 14px; }

        /* TRẠNG THÁI KHO */
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-success { background: #e3f9e5; color: var(--success); }
        .badge-danger { background: #fff1f0; color: var(--danger); }

        code { background: #f5f5f7; padding: 2px 6px; border-radius: 4px; color: var(--apple-blue); font-weight: 600; }

        @media (max-width: 1024px) {
            :root { --sidebar-width: 80px; }
            .logo-admin span, .menu a span { display: none; }
            .main-content { margin-left: 80px; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-admin">
        <i class="fa-brands fa-apple"></i> <span>Itronic Admin</span>
    </div>
    <div class="menu">
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a href="users.php"><i class="fas fa-users"></i> <span>Người dùng</span></a>
        <a href="products.php"><i class="fas fa-box"></i> <span>Sản phẩm</span></a>
        <a href="import-goods.php"><i class="fas fa-file-import"></i> <span>Nhập hàng</span></a>
        <a href="inventory.php" class="active"><i class="fas fa-warehouse"></i> <span>Tồn kho</span></a>
        <a href="orders.php"><i class="fas fa-shopping-cart"></i> <span>Đơn hàng</span></a>
        
        <a href="../../PHP/logout-admin.php" style="color: var(--danger); border-top: 1px solid #333; margin-top: 30px;">
            <i class="fas fa-sign-out-alt"></i> <span>Đăng xuất</span>
        </a>
    </div>
</div>

<div class="main-content">
    <div class="header">
        <h2>Kiểm kê tồn kho</h2>
        <div style="color: var(--apple-gray); font-size: 14px; font-weight: 500;">
            Cập nhật lần cuối: <?= date('d/m/Y H:i') ?>
        </div>
    </div>

    <div class="inventory-summary">
        <div class="summary-card">
            <h3>Số lượng máy hiện có</h3>
            <h2><?= number_format($stats['total_qty'] ?? 0) ?> <small style="font-size: 14px; color: var(--apple-gray);">chiếc</small></h2>
        </div>
        <div class="summary-card" style="border-left: 4px solid var(--apple-blue);">
            <h3>Vốn lưu kho dự kiến</h3>
            <h2 style="color: var(--apple-blue);"><?= number_format($stats['total_value'] ?? 0, 0, ',', '.') ?>đ</h2>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Mã SKU</th>
                    <th>Tên sản phẩm</th>
                    <th>Tồn kho</th>
                    <th>Đơn vị</th>
                    <th>Giá vốn đơn vị</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $products->fetch_assoc()): ?>
                <tr>
                    <td><code><?= $row['sku'] ?? 'N/A' ?></code></td>
                    <td><b><?= htmlspecialchars($row['name']) ?></b></td>
                    <td style="font-weight: 600;"><?= $row['quantity'] ?></td>
                    <td><?= $row['unit'] ?? 'Cái' ?></td>
                    <td><?= number_format($row['cost_price'] ?? 0, 0, ',', '.') ?>đ</td>
                    <td>
                        <?php if($row['quantity'] > 5): ?>
                            <span class="badge badge-success">Còn hàng</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Cần nhập kho</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
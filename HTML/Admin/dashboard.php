<?php
session_start();
include __DIR__ . '/../../PHP/db_config.php';

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Bạn không có quyền truy cập!'); window.location='../HTML/User/Sign.php';</script>";
    exit();
}

// 2. TRUY VẤN DỮ LIỆU THỰC TẾ TỪ DATABASE
// A. Thống kê Đơn hàng & Doanh thu
// Tổng số đơn hàng
$order_count_q = $conn->query("SELECT COUNT(id) as total FROM orders");
$total_orders = ($order_count_q) ? $order_count_q->fetch_assoc()['total'] : 0;

// Doanh thu thực tế (Chỉ tính các đơn hàng đã 'completed' hoặc 'Đã giao')
$revenue_q = $conn->query("SELECT SUM(total_amount) as total_rev FROM orders WHERE status = 'completed' OR status = 'Đã giao'");
$total_revenue = ($revenue_q) ? $revenue_q->fetch_assoc()['total_rev'] : 0;

// B. Thống kê Người dùng & Sản phẩm
$user_q = $conn->query("SELECT COUNT(id) as total FROM users WHERE role = 'customer'");
$total_users = ($user_q) ? $user_q->fetch_assoc()['total'] : 0;

// C. Thống kê Kho hàng
$stock_q = $conn->query("SELECT SUM(quantity) as total_stock FROM products");
$total_stock = ($stock_q) ? $stock_q->fetch_assoc()['total_stock'] : 0;

$out_of_stock_q = $conn->query("SELECT COUNT(id) as total FROM products WHERE quantity <= 0");
$out_of_stock = ($out_of_stock_q) ? $out_of_stock_q->fetch_assoc()['total'] : 0;

$low_stock_q = $conn->query("SELECT COUNT(id) as total FROM products WHERE quantity > 0 AND quantity <= 5");
$low_stock = ($low_stock_q) ? $low_stock_q->fetch_assoc()['total'] : 0;

$inventory_q = $conn->query("SELECT SUM(price * quantity) as total_value FROM products");
$inventory_value = ($inventory_q) ? $inventory_q->fetch_assoc()['total_value'] : 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../hinhanh/apple-icon.ico">
    <title>Itronic - Dashboard Quản trị</title>
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
            --warning: #ff9500;
            --success: #34c759;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--apple-bg); display: flex; min-height: 100vh; color: var(--apple-dark); overflow-x: hidden; }

        /* SIDEBAR */
        .sidebar { 
            width: var(--sidebar-width); background: var(--apple-dark); color: white; 
            position: fixed; top: 0; left: 0; height: 100vh; z-index: 1000; padding: 20px 0;
        }
        .logo-admin { 
            text-align: center; padding: 20px; font-size: 22px; font-weight: bold; 
            border-bottom: 1px solid #333; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .menu a { display: flex; align-items: center; padding: 15px 25px; color: #ddd; text-decoration: none; transition: 0.3s; font-size: 14px; }
        .menu a i { margin-right: 12px; width: 20px; text-align: center; font-size: 16px; }
        .menu a:hover, .menu a.active { background: var(--apple-blue); color: white; }

        /* MAIN CONTENT */
        .main-content { flex: 1; margin-left: var(--sidebar-width); padding: 40px; width: calc(100% - var(--sidebar-width)); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .header h2 { font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }

        /* STATS GRID */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .card { 
            background: white; border-radius: 20px; padding: 25px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02);
            transition: transform 0.3s ease;
        }
        .card:hover { transform: translateY(-5px); }
        .card h3 { font-size: 11px; color: var(--apple-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; font-weight: 600; }
        .card h2 { font-size: 28px; font-weight: 700; margin-bottom: 5px; }
        .card p { font-size: 13px; color: var(--apple-gray); }

        /* Card Highlights */
        .card-primary { background: var(--apple-blue); color: white; border: none; }
        .card-primary h3, .card-primary p { color: rgba(255,255,255,0.8); }
        .card-primary h2 { color: white; }
        
        .card-warning { border-top: 4px solid var(--warning); }
        .card-danger { border-top: 4px solid var(--danger); }
        .card-success { border-top: 4px solid var(--success); }

        /* QUICK ACTIONS */
        .quick-actions { background: white; border-radius: 20px; padding: 35px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .quick-actions h3 { margin-bottom: 25px; font-size: 20px; font-weight: 600; }
        .btn-group { display: flex; gap: 15px; flex-wrap: wrap; }
        .btn { padding: 14px 28px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--apple-blue); color: white; }
        .btn-secondary { background: #f5f5f7; color: var(--apple-dark); border: 1px solid #e5e5e7; }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; }
            .sidebar .logo-admin span, .menu a span { display: none; }
            .main-content { margin-left: 80px; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-admin"><i class="fa-brands fa-apple"></i> <span>Itronic Admin</span></div>
    <div class="menu">
        <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a href="users.php"><i class="fas fa-users"></i> <span>Quản lý người dùng</span></a>
        <a href="products.php"><i class="fas fa-box"></i> <span>Quản lý sản phẩm</span></a>
        <a href="import-goods.php"><i class="fas fa-file-import"></i> <span>Nhập kho hàng</span></a>
        <a href="inventory.php"><i class="fas fa-warehouse"></i> <span>Tồn kho</span></a>
        <a href="orders.php"><i class="fas fa-shopping-cart"></i> <span>Đơn đặt hàng</span></a>
        <a href="../../PHP/logout-admin.php" style="color: var(--danger); border-top: 1px solid #333; margin-top: 30px;"><i class="fas fa-sign-out-alt"></i> <span>Đăng xuất</span></a>
    </div>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h2>Tổng quan hệ thống</h2>
            <p style="color: var(--apple-gray); margin-top: 5px;">Dữ liệu kinh doanh Itronic cập nhật thời gian thực.</p>
        </div>
        <div style="text-align: right;">
            <div style="font-weight: 600; font-size: 18px;"><?= date('H:i') ?></div>
            <div style="color: var(--apple-gray); font-size: 14px;"><?= date('d/m/Y') ?></div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="card card-primary">
            <h3>Doanh thu thực tế</h3>
            <h2><?= number_format($total_revenue, 0, ',', '.') ?>đ</h2>
            <p>Từ các đơn hàng hoàn tất</p>
        </div>

        <div class="card">
            <h3>Đơn đặt hàng</h3>
            <h2><?= number_format($total_orders) ?></h2>
            <p>Tổng đơn hàng trên hệ thống</p>
        </div>

        <div class="card">
            <h3>Khách hàng</h3>
            <h2><?= number_format($total_users) ?></h2>
            <p>Tài khoản đã đăng ký</p>
        </div>

        <div class="card">
            <h3>Máy trong kho</h3>
            <h2 style="color: var(--apple-blue);"><?= number_format($total_stock) ?></h2>
            <p>Số thiết bị Apple hiện có</p>
        </div>

        <div class="card card-warning">
            <h3 style="color: var(--warning);">Sắp hết hàng</h3>
            <h2 style="color: var(--warning);"><?= $low_stock ?></h2>
            <p>Sản phẩm số lượng ≤ 5</p>
        </div>

        <div class="card card-danger">
            <h3 style="color: var(--danger);">Đã hết hàng</h3>
            <h2 style="color: var(--danger);"><?= $out_of_stock ?></h2>
            <p>Cần nhập hàng ngay</p>
        </div>

        <div class="card card-success">
            <h3>Giá trị tồn kho</h3>
            <h2 style="color: var(--success);"><?= number_format($inventory_value, 0, ',', '.') ?>đ</h2>
            <p>Ước tính vốn hàng hóa</p>
        </div>
    </div>

    <div class="quick-actions">
        <h3>Lối tắt quản trị</h3>
        <div class="btn-group">
            <a href="import-goods.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Nhập kho</a>
            <a href="orders.php" class="btn btn-secondary"><i class="fas fa-shopping-cart"></i> Đơn hàng</a>
            <a href="products.php" class="btn btn-secondary"><i class="fas fa-list"></i> Sản phẩm</a>
            <a href="inventory.php" class="btn btn-secondary"><i class="fas fa-chart-pie"></i> Tồn kho</a>
        </div>
    </div>
</div>

</body>
</html>
<?php
session_start();
include __DIR__ . '/../../PHP/db_config.php';

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Bạn không có quyền truy cập!'); window.location='../HTML/User/Sign.php';</script>";
    exit();
}

// 2. Xử lý cập nhật trạng thái đơn hàng
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $update_sql = "UPDATE orders SET status = '$new_status' WHERE id = $order_id";
    
    if ($conn->query($update_sql)) {
        // Refresh trang để cập nhật giao diện mới nhất
        header("Location: orders.php");
        exit();
    }
}

// 3. Truy vấn danh sách đơn hàng thực tế
$sql = "SELECT o.*, u.fullname, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC";

$orders = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Itronic - Quản lý đơn hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ĐỒNG BỘ TỪ DASHBOARD CỦA BẠN */
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
        body { background: var(--apple-bg); display: flex; min-height: 100vh; color: var(--apple-dark); }

        /* SIDEBAR FIXED (GIỮ NGUYÊN TỪ DASHBOARD) */
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

        /* MAIN CONTENT LAYOUT */
        .main-content { flex: 1; margin-left: var(--sidebar-width); padding: 40px; width: calc(100% - var(--sidebar-width)); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .header h2 { font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }

        /* TABLE CARD STYLE */
        .table-container { 
            background: white; border-radius: 20px; padding: 30px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02);
        }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: var(--apple-gray); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #eee; }
        td { padding: 18px 15px; border-bottom: 1px solid #f9f9f9; font-size: 14px; vertical-align: middle; }

        /* STATUS BADGE */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .pending { background: #fff1e0; color: var(--warning); }
        .processing { background: #e3f2fd; color: var(--apple-blue); }
        .completed { background: #e3f9e5; color: var(--success); }

        /* ACTION COMPONENTS */
        .action-group { display: flex; align-items: center; gap: 12px; }
        .btn-view { color: var(--apple-blue); font-size: 18px; transition: 0.2s; text-decoration: none; }
        .btn-view:hover { transform: scale(1.2); }
        
        select { padding: 8px; border-radius: 8px; border: 1px solid #d2d2d7; outline: none; font-size: 13px; background: #fff; }
        .btn-save { 
            background: var(--apple-dark); color: white; border: none; padding: 8px 14px; 
            border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 600; transition: 0.2s;
        }
        .btn-save:hover { background: var(--apple-blue); }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-admin">
        <i class="fa-brands fa-apple"></i> <span>Itronic Admin</span>
    </div>
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
    <div class="header">
        <div>
            <h2>Quản lý đơn đặt hàng</h2>
            <p style="color: var(--apple-gray); margin-top: 5px;">Theo dõi và duyệt đơn hàng của khách hàng.</p>
        </div>
        <div style="text-align: right;">
            <div style="font-weight: 600; font-size: 18px;"><?= date('H:i') ?></div>
            <div style="color: var(--apple-gray); font-size: 14px;"><?= date('d/m/Y') ?></div>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Mã ĐH</th>
                    <th>Khách hàng</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $orders->fetch_assoc()): ?>
                <tr>
                    <td><b>#<?= $row['id'] ?></b></td>
                    <td>
                        <div style="font-weight: 600;"><?= htmlspecialchars($row['fullname']) ?></div>
                        <div style="font-size: 12px; color: var(--apple-gray);"><?= htmlspecialchars($row['email']) ?></div>
                    </td>
                    <td><b style="color: var(--apple-blue);"><?= number_format($row['total_amount'], 0, ',', '.') ?>đ</b></td>
                    <td>
                        <span class="badge <?= $row['status'] ?>">
                            <?= $row['status'] == 'pending' ? 'Chờ duyệt' : ($row['status'] == 'processing' ? 'Đang giao' : 'Hoàn tất') ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-group">
                            <a href="order_detail.php?id=<?= $row['id'] ?>" class="btn-view" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>

                            <td>
    <form action="update_order_status.php" method="POST" style="display: flex; gap: 5px;">
        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
        <select name="new_status" class="status-select">
            <option value="Chờ duyệt" <?= $row['status'] == 'Chờ duyệt' ? 'selected' : '' ?>>Chờ duyệt</option>
            <option value="Đang giao" <?= $row['status'] == 'Đang giao' ? 'selected' : '' ?>>Đang giao</option>
            <option value="Hoàn tất" <?= $row['status'] == 'Hoàn tất' ? 'selected' : '' ?>>HOÀN TẤT</option>
        </select>
        <button type="submit" name="btn_save" class="btn-save">Lưu</button>
    </form>
</td>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
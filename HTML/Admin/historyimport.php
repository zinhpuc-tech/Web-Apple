<?php
session_start();
include __DIR__ . '/../../PHP/db_connect.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo "<script>alert('Bạn không có quyền truy cập!'); window.location='admin-login.php';</script>";
    exit();
}

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$sql = "
    SELECT 
        i.created_at,
        p.name AS product_name,
        i.quantity,
        i.price,
        (i.quantity * i.price) AS total
    FROM import_details i
    JOIN products p ON p.id = i.product_id
    WHERE 1=1
";

if (!empty($from)) {
    $sql .= " AND DATE(i.created_at) >= '$from'";
}
if (!empty($to)) {
    $sql .= " AND DATE(i.created_at) <= '$to'";
}

$sql .= " ORDER BY i.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../hinhanh/apple-icon.ico">
    <title>Itronic - Lịch sử nhập kho</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --apple-blue: #0071e3;
            --apple-dark: #1d1d1f;
            --apple-gray: #86868b;
            --apple-bg: #f5f5f7;
            --sidebar-width: 260px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: var(--apple-bg);
            display: flex;
            min-height: 100vh;
            color: var(--apple-dark);
        }

        .sidebar {
            width: var(--sidebar-width);
            background: var(--apple-dark);
            color: white;
            position: fixed;
            height: 100vh;
            padding: 20px 0;
        }

        .logo-admin {
            text-align: center;
            padding: 20px;
            font-size: 22px;
            font-weight: bold;
            border-bottom: 1px solid #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .menu a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: #ddd;
            text-decoration: none;
            transition: 0.3s;
            font-size: 14px;
        }

        .menu a i {
            margin-right: 12px;
        }

        .menu a:hover,
        .menu a.active {
            background: var(--apple-blue);
            color: white;
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .header h2 {
            font-size: 28px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-right: 10px;
        }

        button {
            background: var(--apple-blue);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 10px;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #fbfbfd;
            color: var(--apple-gray);
            font-size: 12px;
            text-transform: uppercase;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .total-box {
            margin-top: 20px;
            font-weight: 600;
            color: var(--apple-blue);
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="logo-admin"><i class="fa-brands fa-apple"></i> Itronic Admin</div>
        <div class="menu">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="users.php"><i class="fas fa-users"></i> Quản lý người dùng</a>
            <a href="products.php"><i class="fas fa-box"></i> Quản lý sản phẩm</a>
            <a href="import-goods.php"><i class="fas fa-file-import"></i> Nhập kho</a>
            <a href="historyimport.php" class="active"><i class="fas fa-clock"></i> Lịch sử nhập kho</a>
            <a href="inventory.php"><i class="fas fa-warehouse"></i> Tồn kho</a>
            <a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn đặt hàng</a>
            <a href="../../PHP/logout-admin.php" style="color:#ff453a; margin-top:20px; border-top:1px solid #333;"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </div>

    <div class="main-content">

        <div class="header">
            <h2>Lịch sử nhập kho</h2>
            <div style="color:var(--apple-gray)">
                <i class="far fa-calendar-alt"></i> <?= date('d/m/Y H:i') ?>
            </div>
        </div>

        <div class="card">

            <form method="GET">
                <input type="date" name="from" value="<?= $from ?>">
                <input type="date" name="to" value="<?= $to ?>">
                <button type="submit"><i class="fas fa-filter"></i> Lọc</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Giá nhập</th>
                        <th>Tổng</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $grand = 0;
                    while ($row = $result->fetch_assoc()):
                        $grand += $row['total'];
                    ?>
                        <tr>
                            <td><?= $row['created_at'] ?></td>
                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td><?= $row['quantity'] ?></td>
                            <td><?= number_format($row['price']) ?></td>
                            <td><?= number_format($row['total']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="total-box">
                Tổng tiền nhập: <?= number_format($grand) ?> VNĐ
            </div>

        </div>
    </div>

</body>

</html>
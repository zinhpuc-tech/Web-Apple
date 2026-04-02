<?php
session_start();
include __DIR__ . '/../../PHP/db_config.php';

if (isset($_GET['toggle_id'])) {
    $id = (int)$_GET['toggle_id'];
    if ($id != $_SESSION['user_id']) {
        $conn->query("UPDATE users SET status = 1 - status WHERE id = $id");
    }
    header("Location: users.php");
}

if (isset($_GET['reset_id'])) {
    $id = (int)$_GET['reset_id'];
    $pw = password_hash('123456', PASSWORD_DEFAULT);
    $conn->query("UPDATE users SET password = '$pw' WHERE id = $id");
    header("Location: users.php?msg=reset_ok");
}

$users = $conn->query("SELECT * FROM users ORDER BY role DESC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../hinhanh/apple-icon.ico">
    <title>Itronic - Quản lý người dùng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --apple-blue: #0071e3; --apple-dark: #1d1d1f; --apple-gray: #86868b; --apple-bg: #f5f5f7; --apple-red: #ff3b30; --apple-green: #34c759; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--apple-bg); display: flex; min-height: 100vh; }

        /* SIDEBAR THỐNG NHẤT VỚI DASHBOARD */
        .sidebar { width: 260px; background: var(--apple-dark); color: white; padding: 20px 0; position: sticky; top: 0; height: 100vh; }
        .logo-admin { text-align: center; padding: 20px; font-size: 22px; font-weight: bold; border-bottom: 1px solid #333; margin-bottom: 10px; }
        .menu a { display: flex; align-items: center; padding: 15px 25px; color: #ddd; text-decoration: none; transition: 0.3s; font-size: 14px; }
        .menu a i { margin-right: 12px; width: 20px; text-align: center; }
        .menu a:hover, .menu a.active { background: var(--apple-blue); color: white; }

        /* MAIN CONTENT */
        .main-content { flex: 1; padding: 30px; }
        .header { background: white; padding: 20px 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        
        .card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .card h3 { margin-bottom: 20px; font-weight: 600; color: var(--apple-dark); }

        /* TABLE STYLING */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #fafafa; color: var(--apple-gray); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; color: #333; }
        tr:hover { background-color: #f9f9fb; }

        /* BADGES & BUTTONS */
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-active { background: #e8f5e9; color: var(--apple-green); }
        .status-locked { background: #ffebee; color: var(--apple-red); }

        .btn { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; }
        .btn-lock { background: #fff1f0; color: var(--apple-red); border: 1px solid #ffa39e; }
        .btn-unlock { background: #f6ffed; color: var(--apple-green); border: 1px solid #b7eb8f; }
        .btn-delete { background: #f5f5f5; color: #555; border: 1px solid #d9d9d9; margin-left: 5px; }
        .btn:hover { opacity: 0.8; transform: translateY(-1px); }

        /* ALERT */
        .alert { padding: 15px 25px; border-radius: 8px; margin-bottom: 25px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #f6ffed; color: #389e0d; border: 1px solid #b7eb8f; }
        .alert-error { background: #fff2f0; color: #cf1322; border: 1px solid #ffa39e; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-admin"><i class="fa-brands fa-apple"></i> Itronic Admin</div>
    <div class="menu">
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="users.php" class="active"><i class="fas fa-users"></i> Quản lý người dùng</a>
        <a href="products.php"><i class="fas fa-box"></i> Quản lý sản phẩm</a>
        <a href="import-goods.php"><i class="fas fa-file-import"></i> Nhập kho hàng</a>
        <a href="inventory.php"><i class="fas fa-warehouse"></i> Tồn kho</a>
        <a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn đặt hàng</a>
        <a href="../../PHP/logout-admin.php" style="color: var(--apple-red); border-top: 1px solid #333; margin-top: 20px;">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </a>
    </div>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h2 style="font-weight: 600;">Danh sách người dùng</h2>
            <p style="color: var(--apple-gray); font-size: 13px;">Quản lý tài khoản khách hàng và phân quyền</p>
        </div>
        <div style="text-align: right;">
            <div style="font-weight: 500;"><i class="far fa-calendar-alt"></i> <?= date('d/m/Y') ?></div>
        </div>
    </div>

    <?php if (isset($message)): ?>
        <div class="alert <?= ($type == 'success') ? 'alert-success' : 'alert-error' ?>">
            <i class="<?= ($type == 'success') ? 'fas fa-check-circle' : 'fas fa-exclamation-circle' ?>"></i>
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Tất cả thành viên</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Ngày tham gia</th>
                    <th style="text-align: right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT id, fullname, email, role, status, created_at FROM users ORDER BY created_at DESC");
                if ($result && $result->num_rows > 0):
                    while($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td>#<?= $row['id'] ?></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($row['fullname']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><span style="letter-spacing: 0.5px; font-size: 12px;"><?= strtoupper($row['role']) ?></span></td>
                    <td>
                        <span class="status-badge <?= $row['status'] == 1 ? 'status-active' : 'status-locked' ?>">
                            <?= $row['status'] == 1 ? '● Hoạt động' : '● Đã khóa' ?>
                        </span>
                    </td>
                    <td style="color: var(--apple-gray);"><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                    <td style="text-align: right;">
                        <?php if ($row['id'] != ($_SESSION['user_id'] ?? 0)): ?>
                            <?php if ($row['status'] == 1): ?>
                                <a href="users.php?toggle_id=<?= $row['id'] ?>" class="btn btn-lock" onclick="return confirm('Khóa tài khoản này?')">
                                    <i class="fas fa-user-slash"></i> Khóa
                                </a>
                            <?php else: ?>
                                <a href="users.php?toggle_id=<?= $row['id'] ?>" class="btn btn-unlock" onclick="return confirm('Mở khóa tài khoản?')">
                                    <i class="fas fa-user-check"></i> Mở
                                </a>
                            <?php endif; ?>
                            <a href="users.php?delete_id=<?= $row['id'] ?>" class="btn btn-delete" onclick="return confirm('Xóa vĩnh viễn?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php else: ?>
                            <span style="color: var(--apple-gray); font-style: italic; font-size: 12px;">Đang đăng nhập</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="7" style="text-align:center; padding: 40px; color: var(--apple-gray);">Không tìm thấy người dùng nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
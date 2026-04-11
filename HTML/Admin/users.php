<?php
session_start();
// Bật báo lỗi để nếu có vấn đề gì nó sẽ hiện chữ chứ không trắng trang
ini_set('display_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/../../PHP/db_connect.php';

$message = "";
$type = "";

// 1. XỬ LÝ KHÓA/MỞ KHÓA
if (isset($_GET['toggle_id'])) {
    $id = (int)$_GET['toggle_id'];
    if (isset($_SESSION['user_id']) && $id != $_SESSION['user_id']) {
        $conn->query("UPDATE users SET status = 1 - status WHERE id = $id");
        header("Location: users.php");
        exit();
    }
}

// 2. XỬ LÝ RESET MẬT KHẨU
if (isset($_GET['reset_id'])) {
    $id = (int)$_GET['reset_id'];
    $pw = password_hash('123456', PASSWORD_DEFAULT);
    $conn->query("UPDATE users SET password = '$pw' WHERE id = $id");
    header("Location: users.php?msg=reset_ok");
    exit();
}

// 3. XỬ LÝ THÊM TÀI KHOẢN (Đã sửa tên cột theo database của bạn)
if (isset($_POST['add_user'])) {
    $fullname      = $conn->real_escape_string($_POST['fullname']);
    $email         = $conn->real_escape_string($_POST['email']);
    $phone         = $conn->real_escape_string($_POST['phone']);
    $gender        = $conn->real_escape_string($_POST['gender']);
    $date_of_birth = $conn->real_escape_string($_POST['date_of_birth']); // Khớp với ảnh
    $address       = $conn->real_escape_string($_POST['address']);
    $role          = $conn->real_escape_string($_POST['role']);
    $password      = password_hash('123456', PASSWORD_DEFAULT);

    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check && $check->num_rows > 0) {
        $message = "Email này đã tồn tại!";
        $type = "error";
    } else {
        // Câu lệnh INSERT khớp chính xác với ảnh database bạn gửi
        $sql = "INSERT INTO users (fullname, email, phone, gender, date_of_birth, address, password, status, role) 
                VALUES ('$fullname', '$email', '$phone', '$gender', '$date_of_birth', '$address', '$password', 1, '$role')";

        if ($conn->query($sql)) {
            $message = "Thêm thành công! Mật khẩu mặc định là 123456";
            $type = "success";
        } else {
            $message = "Lỗi SQL: " . $conn->error;
            $type = "error";
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'reset_ok') {
    $message = "Mật khẩu đã được đưa về 123456";
    $type = "success";
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Itronic - Quản lý người dùng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --apple-blue: #0071e3;
            --apple-dark: #1d1d1f;
            --apple-gray: #86868b;
            --apple-bg: #f5f5f7;
            --apple-red: #ff3b30;
            --apple-green: #34c759;
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
        }

        /* SIDEBAR CỦA BẠN */
        .sidebar {
            width: 260px;
            background: var(--apple-dark);
            color: white;
            padding: 20px 0;
            position: sticky;
            top: 0;
            height: 100vh;
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
            width: 20px;
            text-align: center;
        }

        .menu a:hover,
        .menu a.active {
            background: var(--apple-blue);
            color: white;
        }

        /* NỘI DUNG CHÍNH */
        .main-content {
            flex: 1;
            padding: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .reg-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: var(--apple-gray);
            text-transform: uppercase;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
        }

        .btn-add {
            background: var(--apple-blue);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px;
            color: var(--apple-gray);
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 1px solid #eee;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .status-active {
            background: #e8f5e9;
            color: var(--apple-green);
        }

        .status-locked {
            background: #fff5f5;
            color: var(--apple-red);
        }

        .btn-group {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn-action {
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            border: 1px solid transparent;
        }

        .btn-reset {
            color: var(--apple-blue);
            background: #eaf4ff;
            border-color: #c2e0ff;
        }

        .btn-lock {
            color: var(--apple-red);
            background: #fff1f0;
            border-color: #ffccc7;
        }

        .btn-unlock {
            color: var(--apple-green);
            background: #f6ffed;
            border-color: #b7eb8f;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .alert-error {
            background: #fff5f5;
            color: #c62828;
            border: 1px solid #ffcccc;
        }
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
            <a href="historyimport.php"><i class="fas fa-clock"></i> Lịch sử nhập kho</a>
            <a href="inventory.php"><i class="fas fa-warehouse"></i> Tồn kho</a>
            <a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn đặt hàng</a>
            <a href="../../PHP/logout-admin.php" style="color: var(--apple-red); border-top: 1px solid #333; margin-top: 20px;">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </div>
    </div>

    <div class="main-content">
        <h2 style="margin-bottom: 20px;">Hệ thống người dùng</h2>

        <?php if ($message): ?>
            <div class="alert <?= ($type == 'success') ? 'alert-success' : 'alert-error' ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-bottom: 15px; font-size: 16px;">Đăng ký tài khoản</h3>
            <form method="POST">
                <div class="reg-grid">
                    <div class="form-group"><label>Họ và tên</label><input type="text" name="fullname" required></div>
                    <div class="form-group"><label>Email đăng nhập</label><input type="email" name="email" required></div>
                    <div class="form-group"><label>Số điện thoại</label><input type="text" name="phone"></div>
                    <div class="form-group">
                        <label>Giới tính</label>
                        <select name="gender">
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Ngày sinh</label><input type="date" name="date_of_birth"></div>
                    <div class="form-group">
                        <label>Vai trò</label>
                        <select name="role">
                            <option value="customer">CUSTOMER</option>
                            <option value="admin">ADMIN</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: span 2;"><label>Địa chỉ</label><textarea name="address" rows="2"></textarea></div>
                </div>
                <button type="submit" name="add_user" class="btn-add">Khởi tạo thành viên</button>
            </form>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ và tên</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th style="text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = $conn->query("SELECT * FROM users ORDER BY id DESC");
                    while ($row = $res->fetch_assoc()):
                    ?>
                        <tr>
                            <td style="color: #888;">#<?= $row['id'] ?></td>
                            <td><b><?= htmlspecialchars($row['fullname']) ?></b></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td style="font-size: 11px; font-weight: bold;"><?= strtoupper($row['role']) ?></td>
                            <td>
                                <span class="status-badge <?= $row['status'] == 1 ? 'status-active' : 'status-locked' ?>">
                                    ● <?= $row['status'] == 1 ? 'Hoạt động' : 'Đã khóa' ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($row['id'] != ($_SESSION['user_id'] ?? 0)): ?>
                                        <a href="users.php?reset_id=<?= $row['id'] ?>" class="btn-action btn-reset" onclick="return confirm('Reset về 123456?')"><i class="fas fa-sync-alt"></i> Reset</a>

                                        <?php if ($row['status'] == 1): ?>
                                            <a href="users.php?toggle_id=<?= $row['id'] ?>" class="btn-action btn-lock"><i class="fas fa-lock"></i> Khóa</a>
                                        <?php else: ?>
                                            <a href="users.php?toggle_id=<?= $row['id'] ?>" class="btn-action btn-unlock"><i class="fas fa-unlock"></i> Mở</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #999; font-style: italic;">Đang đăng nhập</span>
                                    <?php endif; ?>
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
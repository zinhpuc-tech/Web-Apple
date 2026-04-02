<?php
session_start();
include '../../PHP/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Sign.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// ==================== XỬ LÝ CẬP NHẬT ====================

$message = '';
$success = false;

// Cập nhật Họ và Tên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_fullname = trim($_POST['fullname']);

    if (!empty($new_fullname)) {
        $new_fullname = mysqli_real_escape_string($conn, $new_fullname);
        $sql = "UPDATE users SET fullname = '$new_fullname' WHERE id = $user_id";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['user_name'] = $new_fullname;
            $message = "Cập nhật họ tên thành công!";
            $success = true;
        } else {
            $message = "Cập nhật họ tên thất bại!";
        }
    }
}

// Đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Lấy mật khẩu hiện tại từ DB
    $result = mysqli_query($conn, "SELECT password FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($old_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = '$hashed' WHERE id = $user_id";
                
                if (mysqli_query($conn, $sql)) {
                    $message = "Đổi mật khẩu thành công!";
                    $success = true;
                } else {
                    $message = "Đổi mật khẩu thất bại!";
                }
            } else {
                $message = "Mật khẩu mới phải có ít nhất 6 ký tự!";
            }
        } else {
            $message = "Mật khẩu mới và xác nhận không khớp!";
        }
    } else {
        $message = "Mật khẩu cũ không đúng!";
    }
}

// Lấy thông tin người dùng
$query = "SELECT id, fullname, email, created_at FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../hinhanh/apple-icon.ico">
    <title>Itronic - Apple Shop</title>
    <link rel="stylesheet" href="../../CSS/homepage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        .profile-container {
            max-width: 750px;
            margin: 60px auto;
            padding: 50px 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .profile-header i {
            font-size: 70px;
            color: #0071e3;
        }
        .section-title {
            margin: 30px 0 15px;
            font-size: 18px;
            color: #1d1d1f;
            border-bottom: 2px solid #0071e3;
            padding-bottom: 8px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d2d2d7;
            border-radius: 10px;
            font-size: 16px;
        }
        input[readonly] {
            background-color: #f5f5f7;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success { background: #e8f5e9; color: #2e7d32; }
        .alert-error   { background: #ffebee; color: #c62828; }
        .btn-group {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 32px;
            border: none;
            border-radius: 999px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
        }
        .btn-save { background: #0071e3; color: white; }
        .btn-back  { background: #f5f5f7; color: #1d1d1f; }
    </style>
</head>
<body>

<div class="profile-container">
    <div class="profile-header">
        <i class="fa-solid fa-circle-user"></i>
        <h1>Thông tin tài khoản</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Phần chỉnh sửa Họ và Tên -->
    <h2 class="section-title">Thông tin cá nhân</h2>
    <form method="POST">
        <div class="form-group">
            <label>Họ và tên</label>
            <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
        </div>

        <div class="form-group">
            <label>Email (không thể thay đổi)</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
        </div>

        <div class="btn-group">
            <button type="submit" name="update_profile" class="btn btn-save">Lưu thay đổi thông tin</button>
        </div>
    </form>

    <!-- Phần đổi mật khẩu -->
    <h2 class="section-title">Đổi mật khẩu</h2>
    <form method="POST">
        <div class="form-group">
            <label>Mật khẩu cũ</label>
            <input type="password" name="old_password" required>
        </div>
        <div class="form-group">
            <label>Mật khẩu mới</label>
            <input type="password" name="new_password" required minlength="6">
        </div>
        <div class="form-group">
            <label>Xác nhận mật khẩu mới</label>
            <input type="password" name="confirm_password" required minlength="6">
        </div>

        <div class="btn-group">
            <button type="submit" name="change_password" class="btn btn-save">Đổi mật khẩu</button>
            <a href="homepage.php" class="btn btn-back">Quay về Trang chủ</a>
        </div>
    </form>
</div>

</body>
</html>
<?php
session_start();
include '../../PHP/db_connect.php';

// LOAD GIỎ HÀNG MỚI
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['user_id'])) {
    $_SESSION['cart'] = loadCartFromDB($conn, $_SESSION['user_id']);
} else if (empty($_SESSION['cart']) && isset($_COOKIE['itronic_cart_backup'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['itronic_cart_backup'], true) ?? [];
}

if (!isset($_SESSION['user_id'])) {
    header("Location: Sign.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// ==================== XỬ LÝ CẬP NHẬT ====================

$message = '';
$success = false;

// Cập nhật thông tin cá nhân (đã thêm phone, gender, date_of_birth)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_fullname   = trim($_POST['fullname']);
    $new_phone      = trim($_POST['phone']);
    $new_gender     = $_POST['gender'] ?? '';
    $new_dob        = $_POST['date_of_birth'] ?? '';

    if (!empty($new_fullname)) {
        $new_fullname = mysqli_real_escape_string($conn, $new_fullname);
        $new_phone    = mysqli_real_escape_string($conn, $new_phone);
        $new_gender   = mysqli_real_escape_string($conn, $new_gender);
        $new_dob      = mysqli_real_escape_string($conn, $new_dob);

        $sql = "UPDATE users SET 
                    fullname = '$new_fullname', 
                    phone = '$new_phone', 
                    gender = '$new_gender', 
                    date_of_birth = '$new_dob' 
                WHERE id = $user_id";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['user_name'] = $new_fullname;
            $message = "Cập nhật thông tin thành công!";
            $success = true;
        } else {
            $message = "Cập nhật thông tin thất bại! " . mysqli_error($conn);
        }
    }
}

// Đổi mật khẩu (giữ nguyên code cũ của bạn)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

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

// Lấy thông tin người dùng (đã thêm phone, gender, date_of_birth)
$query = "SELECT id, fullname, email, phone, gender, date_of_birth, created_at 
          FROM users WHERE id = $user_id";
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
        /* Style cũ của bạn giữ nguyên */
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
        input[type="text"], input[type="email"], input[type="tel"], input[type="date"], select {
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
        /* ===== PASSWORD SECTION ===== */
        .password-section {
            background: #fafafa;
            padding: 30px;
            border-radius: 16px;
            border: 1px solid #e5e5e7;
            margin-top: 10px;
        }

        .password-section .form-group {
            position: relative;
            margin-bottom: 22px;
        }

        .password-section input {
            width: 100%;
            height: 52px;
            padding: 0 45px 0 15px;
            border: 1.5px solid #d2d2d7;
            border-radius: 12px;
            font-size: 15px;
            box-sizing: border-box;
        }

        .password-section input:focus {
            border-color: #0071e3;
            box-shadow: 0 0 0 3px rgba(0,113,227,0.1);
        }

        /* icon con mắt */
        .toggle-password {
            position: absolute;
            top: 70%;
            right: 14px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #86868b;
            font-size: 17px;
        }

        .toggle-password:hover {
            color: #0071e3;
        }

        /* highlight ô mật khẩu mới */
        #new_password {
            border: 2px solid #1d1d1f;
        }

        /* thanh độ mạnh mật khẩu */
        .password-strength {
            height: 6px;
            border-radius: 10px;
            margin-top: 6px;
            background: #ddd;
            overflow: hidden;
        }

        .password-strength div {
            height: 100%;
            width: 0%;
            transition: 0.3s;
        }
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

    <!-- Phần chỉnh sửa Thông tin cá nhân -->
    <h2 class="section-title">Thông tin cá nhân</h2>
    <form method="POST">
        <div class="form-group">
            <label>Họ và tên</label>
            <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label>Số điện thoại</label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="0987654321">
        </div>

        <div class="form-group">
            <label>Email (không thể thay đổi)</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
        </div>

        <div class="form-group">
            <label>Giới tính</label>
            <select name="gender">
                <option value="">Chọn giới tính</option>
                <option value="Nam" <?php echo ($user['gender'] ?? '') === 'Nam' ? 'selected' : ''; ?>>Nam</option>
                <option value="Nữ" <?php echo ($user['gender'] ?? '') === 'Nữ' ? 'selected' : ''; ?>>Nữ</option>
                <option value="Khác" <?php echo ($user['gender'] ?? '') === 'Khác' ? 'selected' : ''; ?>>Khác</option>
            </select>
        </div>

        <div class="form-group">
            <label>Ngày tháng năm sinh</label>
            <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
        </div>

        <div class="btn-group">
            <button type="submit" name="update_profile" class="btn btn-save">Lưu thay đổi thông tin</button>
        </div>
    </form>

    <!-- Phần đổi mật khẩu (giữ nguyên) -->
    <h2 class="section-title">Đổi mật khẩu</h2>

    <div class="password-section">
        <form method="POST">

            <div class="form-group">
                <label>Mật khẩu cũ</label>
                <input type="password" name="old_password" id="old_password" required>
                <i class="fa-solid fa-eye-slash toggle-password" onclick="togglePassword('old_password')"></i>
            </div>

            <div class="form-group">
                <label>Mật khẩu mới</label>
                <input type="password" name="new_password" id="new_password" required minlength="6" onkeyup="checkStrength()">
                <i onclick="togglePassword('new_password')"></i>

                <div class="password-strength">
                    <div id="strength-bar"></div>
                </div>
            </div>

            <div class="form-group">
                <label>Xác nhận mật khẩu mới</label>
                <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
                <i class="fa-solid fa-eye-slash toggle-password" onclick="togglePassword('confirm_password')"></i>
            </div>

            <div class="btn-group">
                <button type="submit" name="change_password" class="btn btn-save">Đổi mật khẩu</button>
                <a href="homepage.php" class="btn btn-back">Quay về Trang chủ</a>
            </div>

        </form>
    </div>
</div>

</body>
<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    const icon = input.nextElementSibling;

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    }
}

function checkStrength() {
    const pass = document.getElementById("new_password").value;
    const bar = document.getElementById("strength-bar");

    let strength = 0;

    if (pass.length >= 6) strength++;
    if (/[A-Z]/.test(pass)) strength++;
    if (/[0-9]/.test(pass)) strength++;
    if (/[^A-Za-z0-9]/.test(pass)) strength++;

    bar.style.width = (strength * 25) + "%";

    if (strength <= 1) bar.style.background = "red";
    else if (strength == 2) bar.style.background = "orange";
    else if (strength == 3) bar.style.background = "yellowgreen";
    else bar.style.background = "green";
}
</script>
</html>
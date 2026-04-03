<?php
session_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../hinhanh/apple-icon.ico">
    <title>Itronic - Apple Shop</title>
    <link rel="stylesheet" href="../../CSS/Sign.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>Đăng nhập Admin - Itronic</title>
    <style>
        .admin-title {
            color: #0071e3;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="apple-logo">
            <i class="fa-brands fa-apple"></i>
        </div>

        <div class="wrapper active">
            <form action="../../PHP/auth.php" method="POST">
                <h1>Đăng nhập Admin</h1>
                <p class="subtitle">Chỉ dành cho quản trị viên</p>
                
                <div class="input-box">
                    <input type="email" name="email" required>
                    <label>Email Admin</label>
                    <i class="fa-solid fa-user"></i>
                </div>
                
                <div class="input-box">
                    <input type="password" name="password" required>
                    <label>Mật khẩu</label>
                    <i class="fa-solid fa-lock"></i>
                </div>

                <button type="submit" class="btn" name="admin_login">Đăng nhập Admin</button>
            </form>
        </div>
    </div>

    <!-- Hiển thị thông báo lỗi nếu có -->
    <?php if (isset($_GET['error'])): ?>
        <script>
            alert("<?php echo htmlspecialchars($_GET['error']); ?>");
        </script>
    <?php endif; ?>
</body>
</html>
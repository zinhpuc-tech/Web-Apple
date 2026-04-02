<?php
session_start();

// Nếu đã đăng nhập rồi thì tự động đẩy sang homepage
if (isset($_SESSION['user_name'])) {
    header("Location: homepage.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../hinhanh/apple-icon.ico">
    <link rel="stylesheet" href="../../CSS/Sign.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>Itronic Web - Apple Shop</title>
</head>
<body>
    <div class="main-container">
        <div class="apple-logo">
            <i class="fa-brands fa-apple"></i>
        </div>

        <div class="wrapper active" id="login-form">
            <form action="../../PHP/auth.php" method="POST">
                <h1>Đăng nhập Itronic</h1>
                <div class="input-box">
                    <input type="email" name="email" required id="user">
                    <label for="user">Email</label>
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" required id="pass">
                    <label for="pass">Mật khẩu</label>
                    <i class="fa-solid fa-lock"></i>
                </div>

                <div class="remember-forget">
                    <label><input type="checkbox" name="remember"> Duy trì đăng nhập</label>
                    <a href="javascript:void(0)" onclick="toggleForm('forgot-form')">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn" name="login">Tiếp tục</button>

                <div class="register-link">
                    <p>Bạn chưa có ID? <a href="javascript:void(0)" onclick="toggleForm('signup-form')">Tạo của bạn ngay bây giờ.</a></p>
                </div>
            </form>
        </div>

        <!-- Form Đăng ký -->
        <div class="wrapper" id="signup-form">
            <form action="../../PHP/auth.php" method="POST">
                <h1>Tạo ID Itronic</h1>
                <div class="input-box">
                    <input type="text" name="fullname" required>
                    <label>Họ và Tên</label>
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <div class="input-box">
                    <input type="email" name="email" required>
                    <label>Email</label>
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" required>
                    <label>Mật khẩu</label>
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <button type="submit" name="register" class="btn btn-dark">Đăng ký</button>
                <div class="register-link">
                    <p>Đã có tài khoản? <a href="javascript:void(0)" onclick="toggleForm('login-form')">Đăng nhập</a></p>
                </div>
            </form>
        </div>

        <!-- Form Quên mật khẩu -->
        <div class="wrapper" id="forgot-form">
            <form action="../../PHP/auth.php" method="POST">
                <h1>Quên mật khẩu?</h1>
                <p class="subtitle">Nhập email để nhận mật khẩu mới.</p>

                <div class="input-box">
                    <input type="email" name="recover_email" required>
                    <label>Email khôi phục</label>
                    <i class="fa-solid fa-paper-plane"></i>
                </div>

                <button type="submit" name="forgot_password" class="btn">Gửi yêu cầu</button>

                <div class="register-link">
                    <p><a href="javascript:void(0)" onclick="toggleForm('login-form')">
                        <i class="fa-solid fa-arrow-left"></i> Quay lại đăng nhập</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleForm(formId) {
            const forms = document.querySelectorAll('.wrapper');
            forms.forEach(f => f.classList.remove('active'));
            document.getElementById(formId).classList.add('active');
        }

        // Hiển thị thông báo nếu có lỗi từ PHP (ví dụ: tài khoản admin cố đăng nhập)
        <?php if (isset($_GET['error'])): ?>
            alert("<?php echo htmlspecialchars($_GET['error']); ?>");
        <?php endif; ?>
    </script>
</body>
</html>
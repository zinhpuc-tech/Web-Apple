<?php
session_start();

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>Itronic Web - Apple Shop</title>

<style>
@media (max-width: 420px) {
    .wrapper {
        padding: 25px 18px;
    }
}

* {
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: #f5f5f7;
}

.main-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 20px;
}

.apple-logo i {
    font-size: 42px;
    margin-bottom: 20px;
}

.wrapper {
    width: 100%;
    max-width: 360px;
    padding: 30px 25px;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 10px 35px rgba(0,0,0,0.08);
    display: none;
}

.wrapper.active {
    display: block;
}

.wrapper h1 {
    text-align: center;
    font-size: 22px;
    margin-bottom: 25px;
}

/* ================= INPUT ================= */
.input-box {
    position: relative;
    margin-bottom: 18px;
}

.input-box input,
.input-box select {
    width: 100%;
    padding: 14px 45px 14px 14px;
    border: 1.5px solid #d2d2d7;
    border-radius: 12px;
    font-size: 15px;
    transition: 0.2s;
    background: white;
}

/* focus đẹp hơn */
.input-box input:focus,
.input-box select:focus {
    border-color: #1d1d1f;
    box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
}

/* ===== FIX SELECT ===== */
.input-box select {
    appearance: none;
    cursor: pointer;
}

/* mũi tên dropdown */
.input-box::after {
    content: "▾";
    position: absolute;
    right: 32px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #888;
}

/* ===== FIX DATE ===== */
.input-box input[type="date"] {
    color: #333;
    cursor: pointer;
}

/* ================= ICON ================= */
.input-box i {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 13px;
    cursor: pointer;
}

/* ================= LABEL ================= */
.input-box label {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #888;
    font-size: 14px;
    background: white;
    padding: 0 5px;
    transition: 0.2s;
    pointer-events: none;
}

/* fix nổi label chuẩn cho ALL */
.input-box input:focus + i + label,
.input-box input:valid + i + label,
.input-box select:valid + i + label,
.input-box input[type="date"]:valid + i + label {
    top: -8px;
    font-size: 12px;
    color: #1d1d1f;
}

/* ================= BUTTON ================= */
.btn {
    width: 100%;
    padding: 12px;
    border-radius: 999px;
    border: none;
    background: #1d1d1f; /* 🔥 đổi sang đen */
    color: white;
    font-weight: 500;
    cursor: pointer;
    transition: 0.2s;
}

.btn:hover {
    background: #000;
}

.register-link {
    text-align: center;
    margin-top: 15px;
    font-size: 14px;
}

.register-link a {
    color: #0071e3;
    text-decoration: none;
}

.remember-forget {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin-bottom: 15px;
}
</style>
</head>

<body>

<div class="main-container">

    <div class="apple-logo">
        <i class="fa-brands fa-apple"></i>
    </div>

    <!-- LOGIN -->
    <div class="wrapper active" id="login-form">
        <form action="../../PHP/auth.php" method="POST">
            <h1>Đăng nhập Itronic</h1>

            <div class="input-box">
                <input type="email" name="email" required>
                <i class="fa-solid fa-envelope"></i>
                <label>Email</label>
            </div>

            <div class="input-box">
                <input type="password" name="password" id="login_pass" required>
                <i class="fa-solid fa-eye" onclick="togglePass('login_pass', this)"></i>
                <label>Mật khẩu</label>
            </div>

            <div class="remember-forget">
                <label><input type="checkbox"> Duy trì đăng nhập</label>
                <a href="javascript:void(0)" onclick="toggleForm('forgot-form')">Quên mật khẩu?</a>
            </div>

            <button type="submit" name="login" class="btn">Tiếp tục</button>

            <div class="register-link">
                <p>Chưa có tài khoản? 
                    <a href="javascript:void(0)" onclick="toggleForm('signup-form')">Đăng ký</a>
                </p>
            </div>
        </form>
    </div>

    <!-- REGISTER -->
    <div class="wrapper" id="signup-form">
        <form action="../../PHP/auth.php" method="POST">
            <h1>Tạo tài khoản</h1>

            <div class="input-box">
                <input type="text" name="fullname" required>
                <i class="fa-solid fa-user"></i>
                <label>Họ và tên</label>
            </div>

            <div class="input-box">
                <input type="tel" name="phone" required>
                <i class="fa-solid fa-phone"></i>
                <label>Số điện thoại</label>
            </div>

            <div class="input-box">
                <input type="email" name="email" required>
                <i class="fa-solid fa-envelope"></i>
                <label>Email</label>
            </div>

            <div class="input-box">
                <select name="gender" required>
                    <option value="" disabled selected></option>
                    <option value="Nam">Nam</option>
                    <option value="Nữ">Nữ</option>
                    <option value="Khác">Khác</option>
                </select>
                <i class="fa-solid fa-venus-mars"></i>
                <label>Giới tính</label>
            </div>

            <div class="input-box">
                <input type="date" name="date_of_birth" required>
                <i class="fa-solid fa-calendar"></i>
                <label>Ngày tháng sinh</label>
            </div>

            <div class="input-box">
                <input type="password" name="password" id="reg_pass" required>
                <i class="fa-solid fa-eye" onclick="togglePass('reg_pass', this)"></i>
                <label>Mật khẩu</label>
            </div>

            <button type="submit" name="register" class="btn">Đăng ký</button>

            <div class="register-link">
                <p>Đã có tài khoản? 
                    <a href="javascript:void(0)" onclick="toggleForm('login-form')">Đăng nhập</a>
                </p>
            </div>
        </form>
    </div>

    <!-- FORGOT -->
    <div class="wrapper" id="forgot-form">
        <form action="../../PHP/auth.php" method="POST">
            <h1>Quên mật khẩu</h1>

            <div class="input-box">
                <input type="email" name="recover_email" required>
                <i class="fa-solid fa-paper-plane"></i>
                <label>Email</label>
            </div>

            <button type="submit" name="forgot_password" class="btn">Gửi</button>

            <div class="register-link">
                <a href="javascript:void(0)" onclick="toggleForm('login-form')">← Quay lại</a>
            </div>
        </form>
    </div>

</div>

<script>
function toggleForm(id) {
    document.querySelectorAll('.wrapper').forEach(f => f.classList.remove('active'));
    document.getElementById(id).classList.add('active');
}

function togglePass(id, el) {
    const input = document.getElementById(id);
    if (input.type === "password") {
        input.type = "text";
        el.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        input.type = "password";
        el.classList.replace("fa-eye-slash", "fa-eye");
    }
}

// show error
<?php if (isset($_GET['error'])): ?>
alert("<?= htmlspecialchars($_GET['error']); ?>");
<?php endif; ?>
</script>

</body>
</html>
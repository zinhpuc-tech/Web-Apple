<?php
session_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../hinhanh/apple-icon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <title>Đăng nhập Admin - Itronic</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f7; /* Nền xám nhạt Apple */
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .main-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }

        /* LOGO */
        .apple-logo i {
            font-size: 48px;
            color: #1d1d1f;
            margin-bottom: 25px;
        }

        /* CARD */
        .wrapper {
            width: 100%;
            padding: 48px 40px;
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.04);
        }

        /* TITLE */
        .wrapper h1 {
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 35px;
            color: #1d1d1f;
        }

        /* INPUT BOX */
        .input-box {
            position: relative;
            margin-bottom: 12px;
        }

        .input-box input {
            width: 100%;
            padding: 16px 15px;
            padding-right: 45px;
            border: 1px solid #d2d2d7; /* Viền mảnh như hình */
            border-radius: 12px;
            font-size: 15px;
            background: #fff;
            color: #000;
            outline: none;
            transition: all 0.2s ease;
        }

        .input-box input:focus {
            border-color: #0071e3;
            box-shadow: 0 0 0 4px rgba(0,113,227,0.1);
        }

        /* ICON */
        .input-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #86868b;
            font-size: 14px;
            cursor: pointer;
        }

        /* Nút đăng nhập - Màu đen Apple */
        .btn {
            width: 100%;
            padding: 14px;
            margin-top: 15px;
            border-radius: 12px;
            border: none;
            background: #1d1d1f;
            color: #fff;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn:hover {
            background: #000;
        }

        /* Ẩn Label gốc để dùng placeholder sạch sẽ */
        .input-box label {
            display: none;
        }
    </style>
</head>

<body>

<div class="main-container">

    <div class="apple-logo">
        <i class="fa-brands fa-apple"></i>
    </div>

    <div class="wrapper">
        <form action="../../PHP/auth.php" method="POST">

            <h1>Đăng nhập Admin Itronic</h1>

            <div class="input-box">
                <input type="email" name="email" placeholder="Email" required>
                <i class="fa-solid fa-envelope"></i>
            </div>

            <div class="input-box">
                <input type="password" name="password" id="admin_pass" placeholder="Mật khẩu" required>
                <i class="fa-solid fa-eye" onclick="togglePass('admin_pass', this)"></i>
            </div>

            <button type="submit" class="btn" name="admin_login">
                Tiếp tục
            </button>

        </form>
    </div>

</div>

<script>
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
</script>

<?php if (isset($_GET['error'])): ?>
<script>
alert("<?= htmlspecialchars($_GET['error']); ?>");
window.history.replaceState({}, document.title, window.location.pathname);
</script>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
<script>
alert("<?= htmlspecialchars($_GET['success']); ?>");
window.history.replaceState({}, document.title, window.location.pathname);
</script>
<?php endif; ?>

</body>
</html>
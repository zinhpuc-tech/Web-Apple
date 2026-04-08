<?php
session_start();
include 'db_connect.php';

// ====================== ĐĂNG NHẬP ADMIN ======================
if (isset($_POST['admin_login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header("Location: ../HTML/Admin/admin-login.php?error=" . urlencode("Vui lòng nhập đầy đủ!"));
        exit();
    }

    $stmt = $conn->prepare("SELECT id, fullname, email, role, status, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {

            if (strtolower(trim($row['role'])) !== 'admin') {
                header("Location: ../HTML/Admin/admin-login.php?error=" . urlencode("Tài khoản này không có quyền Admin!"));
                exit();
            }

            if ($row['status'] == 0) {
                header("Location: ../HTML/Admin/admin-login.php?error=" . urlencode("Tài khoản Admin này đã bị khóa!"));
                exit();
            }

            // Login Admin thành công
            session_unset(); 
            $_SESSION['user_id']   = $row['id'];
            $_SESSION['user_name'] = $row['fullname'];
            $_SESSION['user_role'] = 'admin';

            unset($_SESSION['cart']);
            $_SESSION['cart'] = loadCartFromDB($conn, $_SESSION['user_id']);

            header("Location: ../HTML/Admin/dashboard.php");
            exit();
        }
    }
    header("Location: ../HTML/Admin/admin-login.php?error=" . urlencode("Email hoặc mật khẩu không đúng!"));
    exit();
}

// ====================== ĐĂNG KÝ (Đã chỉnh theo data mẫu) ======================
if (isset($_POST['register'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($fullname) || empty($email) || empty($password)) {
        echo "<script>alert('Vui lòng điền đầy đủ thông tin!'); window.history.back();</script>";
        exit();
    }

    if (strlen($password) < 6) {
        echo "<script>alert('Mật khẩu phải có ít nhất 6 ký tự!'); window.history.back();</script>";
        exit();
    }

    // Kiểm tra email đã tồn tại chưa
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email này đã được sử dụng! Vui lòng dùng email khác.'); window.history.back();</script>";
        $stmt->close();
        exit();
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, status) 
                            VALUES (?, ?, ?, 'customer', 1)");
    $stmt->bind_param("sss", $fullname, $email, $hashed_password);

    if ($stmt->execute()) {
        echo "<script>alert('Đăng ký thành công! Vui lòng đăng nhập.'); window.location='../HTML/User/Sign.php';</script>";
    } else {
        echo "<script>alert('Đăng ký thất bại! Vui lòng thử lại.'); window.history.back();</script>";
    }
    $stmt->close();
    exit();
}

// ====================== ĐĂNG NHẬP USER ======================
if (isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo "<script>alert('Vui lòng nhập email và mật khẩu!'); window.history.back();</script>";
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        
        if ($user['status'] == 0) {
            echo "<script>alert('Tài khoản của bạn hiện đang bị khóa!'); window.history.back();</script>";
            exit();
        }

        // Login User thành công
        session_unset(); 
        
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['fullname'];
        $_SESSION['user_role'] = strtolower(trim($user['role'] ?? 'customer'));

        unset($_SESSION['cart']);
        $_SESSION['cart'] = loadCartFromDB($conn, $_SESSION['user_id']);

        setcookie('itronic_cart_backup', '', time() - 3600, '/');

        header("Location: ../HTML/User/homepage.php");
        exit();
        
    } else {
        echo "<script>alert('Email hoặc mật khẩu không chính xác!'); window.history.back();</script>";
        exit();
    }
}
// ====================== QUÊN MẬT KHẨU ======================
if (isset($_POST['forgot_password'])) {
    $email = trim($_POST['recover_email'] ?? '');

    if (empty($email)) {
        header("Location: ../HTML/User/Sign.php?error=" . urlencode("Vui lòng nhập email!"));
        exit();
    }

    // Kiểm tra email có tồn tại không
    $stmt = $conn->prepare("SELECT id, fullname FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {

        // 👉 Demo: reset mật khẩu về 123456
        $new_password = "123456";
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update->bind_param("ss", $hashed, $email);
        $update->execute();

        // Redirect về login
        header("Location: ../HTML/User/Sign.php?success=" . urlencode("Mật khẩu mới là 123456 (nên đổi lại!)"));
        exit();

    } else {
        header("Location: ../HTML/User/Sign.php?error=" . urlencode("Email không tồn tại!"));
        exit();
    }
}
?>
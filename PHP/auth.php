<?php
session_start();
include 'db_connect.php';        // ← Sửa thành db_connect.php (khuyến nghị)

// ====================== ĐĂNG NHẬP ADMIN ======================
if (isset($_POST['admin_login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

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

            // === LOGIN ADMIN THÀNH CÔNG ===
            session_unset(); 
            $_SESSION['user_id']   = $row['id'];
            $_SESSION['user_name'] = $row['fullname'];
            $_SESSION['user_role'] = 'admin';

            // Load giỏ hàng từ database theo user_id
            unset($_SESSION['cart']);
            $_SESSION['cart'] = loadCartFromDB($conn, $_SESSION['user_id']);

            header("Location: ../HTML/Admin/dashboard.php");
            exit();
        }
    }
    header("Location: ../HTML/Admin/admin-login.php?error=" . urlencode("Email hoặc mật khẩu không đúng!"));
    exit();
}

// ====================== ĐĂNG KÝ ======================
if (isset($_POST['register'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($fullname) || empty($email) || empty($password)) {
        echo "<script>alert('Vui lòng điền đầy đủ thông tin!'); window.history.back();</script>";
        exit();
    }

    $fullname = mysqli_real_escape_string($conn, $fullname);
    $email    = mysqli_real_escape_string($conn, $email);
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);   // Nên hash password

    $sql = "INSERT INTO users (fullname, email, password, role, status) 
            VALUES ('$fullname', '$email', '$hashed_password', 'member', 1)";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Đăng ký thành công! Vui lòng đăng nhập.'); window.location='../HTML/User/Sign.php';</script>";
    } else {
        echo "<script>alert('Lỗi: Email đã tồn tại!'); window.history.back();</script>";
    }
    exit();
}

// ====================== ĐĂNG NHẬP USER (Quan trọng nhất) ======================
if (isset($_POST['login'])) {
    $email    = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        echo "<script>alert('Vui lòng nhập email và mật khẩu!'); window.history.back();</script>";
        exit();
    }

    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        
        if ($user['status'] == 0) {
            echo "<script>alert('Tài khoản của bạn hiện đang bị khóa!'); window.history.back();</script>";
            exit();
        }

        // === LOGIN USER THÀNH CÔNG - ĐÃ SỬA ĐỂ FIX GIỎ HÀNG ===
        session_unset(); 
        
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['fullname'];
        $_SESSION['user_role'] = strtolower(trim($user['role']));

        // Load giỏ hàng cá nhân từ database
        unset($_SESSION['cart']);
        $_SESSION['cart'] = loadCartFromDB($conn, $_SESSION['user_id']);

        // Xóa cookie backup của khách vãng lai
        setcookie('itronic_cart_backup', '', time() - 3600, '/');

        header("Location: ../HTML/User/homepage.php");
        exit();
        
    } else {
        echo "<script>alert('Email hoặc mật khẩu không chính xác!'); window.history.back();</script>";
        exit();
    }
}
?>
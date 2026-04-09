<?php
// PHP/auth.php
session_start();
include 'db_connect.php';
include 'cart_functions.php';

// ====================== ĐĂNG KÝ ======================
if (isset($_POST['register'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $gender   = $_POST['gender'] ?? '';
    $dob      = $_POST['date_of_birth'] ?? '';
    $address  = trim($_POST['address'] ?? '');   // ← Bắt buộc
    $password = $_POST['password'] ?? '';

    // 1. Kiểm tra đầy đủ thông tin (đặc biệt là address)
    if (empty($fullname) || empty($email) || empty($phone) || empty($address) || empty($password)) {
        echo "<script>alert('Vui lòng điền đầy đủ thông tin, đặc biệt là Địa chỉ giao hàng!'); window.history.back();</script>";
        exit();
    }

    // 2. Kiểm tra email đã tồn tại chưa
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email này đã được sử dụng!'); window.history.back();</script>";
        exit();
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 3. INSERT - Phải khớp đúng thứ tự cột trong database
    // Giả sử thứ tự cột: fullname, email, phone, gender, date_of_birth, address, password, role, status
    $stmt = $conn->prepare("INSERT INTO users 
        (fullname, email, phone, gender, date_of_birth, address, password, role, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'customer', 1)");

    // Bind theo đúng thứ tự
    $stmt->bind_param("sssssss", $fullname, $email, $phone, $gender, $dob, $address, $hashed_password);

    if ($stmt->execute()) {
        echo "<script>
            alert('Đăng ký thành công! Bạn có thể đăng nhập ngay.');
            window.location='../HTML/User/Sign.php';
        </script>";
    } else {
        echo "<script>
            alert('Lỗi khi đăng ký: " . addslashes($stmt->error) . "');
            window.history.back();
        </script>";
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
            echo "<script>alert('Tài khoản của bạn đã bị khóa!'); window.history.back();</script>";
            exit();
        }

        session_unset(); 
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['fullname'];
        $_SESSION['user_role'] = strtolower(trim($user['role'] ?? 'customer'));

        // Load giỏ hàng từ DB
        unset($_SESSION['cart']);
        $_SESSION['cart'] = loadCartFromDB($conn, $_SESSION['user_id']);

        header("Location: ../HTML/User/homepage.php");
        exit();
        
    } else {
        echo "<script>alert('Email hoặc mật khẩu không chính xác!'); window.history.back();</script>";
        exit();
    }
}

// ====================== ĐĂNG NHẬP ADMIN (giữ nguyên) ======================
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

// ====================== QUÊN MẬT KHẨU (giữ nguyên) ======================
if (isset($_POST['forgot_password'])) {
    $email = trim($_POST['recover_email'] ?? '');

    if (empty($email)) {
        header("Location: ../HTML/User/Sign.php?error=" . urlencode("Vui lòng nhập email!"));
        exit();
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $hashed = password_hash("123456", PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update->bind_param("ss", $hashed, $email);
        $update->execute();

        header("Location: ../HTML/User/Sign.php?success=" . urlencode("Mật khẩu mới là 123456"));
        exit();
    } else {
        header("Location: ../HTML/User/Sign.php?error=" . urlencode("Email không tồn tại!"));
        exit();
    }
}
?>
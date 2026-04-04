<?php
session_start();
include 'db_config.php';

// ====================== ĐĂNG NHẬP ADMIN (Từ trang admin-login.php) ======================
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
        // So sánh mật khẩu thuần
        if (password_verify($password,$row['password'])) {

            // Kiểm tra quyền Admin
            if (strtolower(trim($row['role'])) !== 'admin') {
                header("Location: ../HTML/Admin/admin-login.php?error=" . urlencode("Tài khoản này không có quyền Admin!"));
                exit();
            }

            // Kiểm tra trạng thái
            if ($row['status'] == 0) {
                header("Location: ../HTML/Admin/admin-login.php?error=" . urlencode("Tài khoản Admin này đã bị khóa!"));
                exit();
            }

            // Thiết lập Session và đẩy vào Dashboard vì đăng nhập từ cổng Admin
            session_unset(); 
            $_SESSION['user_id']   = $row['id'];
            $_SESSION['user_name'] = $row['fullname'];
            $_SESSION['user_role'] = 'admin';
            header("Location: ../HTML/Admin/dashboard.php");
            exit();
        }
    }
    header("Location: ../HTML/Admin/admin-login.php?error=" . urlencode("Email hoặc mật khẩu không đúng!"));
    exit();
}

// ====================== ĐĂNG KÝ (Lưu pass thuần) ======================
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
    
    // Mặc định role là member
    $sql = "INSERT INTO users (fullname, email, password, role, status) VALUES ('$fullname', '$email', '$password', 'member', 1)";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Đăng ký thành công!'); window.location='../HTML/User/Sign.php';</script>";
    } else {
        echo "<script>alert('Lỗi: Email đã tồn tại!'); window.history.back();</script>";
    }
    exit();
}

// ====================== ĐĂNG NHẬP USER (Từ Modal hoặc Sign.php) ======================
if (isset($_POST['login'])) {
    $email    = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo "<script>alert('Vui lòng nhập email và mật khẩu!'); window.history.back();</script>";
        exit();
    }

    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);

    // Kiểm tra mật khẩu và tồn tại user
    if ($user && $password === $user['password']) {
        
        // Kiểm tra trạng thái khóa
        if ($user['status'] == 0) {
            echo "<script>alert('Tài khoản của bạn hiện đang bị khóa!'); window.history.back();</script>";
            exit();
        }

        // LÀM SẠCH SESSION CŨ VÀ THIẾT LẬP MỚI
        session_unset(); 
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['fullname'];
        $_SESSION['user_role'] = strtolower(trim($user['role'])); // Lưu role từ DB (admin hoặc member)

        /* QUAN TRỌNG: 
           Dù là Admin hay Member, nếu đăng nhập từ form này 
           thì đều dẫn về homepage.php để không bị "đá" sang dashboard.
        */
        header("Location: ../HTML/User/homepage.php");
        exit();
        
    } else {
        echo "<script>alert('Email hoặc mật khẩu không chính xác!'); window.history.back();</script>";
        exit();
    }
}
?>
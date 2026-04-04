<?php
session_start();
// Thêm đoạn này ngay sau session_start(); ở homepage.php, iphone.php...
if (empty($_SESSION['cart']) && isset($_COOKIE['itronic_cart_backup'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['itronic_cart_backup'], true);
}
include "../../PHP/db_connect.php";

// ====================== 1. BẢO VỆ GIỎ HÀNG ======================
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    // Nếu đã đặt hàng thành công (vừa unset xong) thì không chuyển hướng ngay để khách xem thông báo
    if (!isset($_GET['ordered'])) {
        header("Location: cart.php");
        exit;
    }
}

// ====================== 2. TÍNH TỔNG TIỀN ======================
$total_price = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_price += (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 0);
    }
}

// ====================== 3. XỬ LÝ ĐẶT HÀNG & TRỪ KHO ======================
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $fullname       = trim($_POST['fullname'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $note           = trim($_POST['note'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'COD';

    if ($fullname && $phone && $address) {
        // Bắt đầu Transaction để đảm bảo an toàn dữ liệu
        $conn->begin_transaction();

        try {
            // A. Lưu đơn hàng chính
            $stmt = $conn->prepare("INSERT INTO orders 
                (user_id, full_name, phone, address, note, payment_method, total_amount, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            $user_id = $_SESSION['user_id'] ?? null;
            $stmt->bind_param("issssds", $user_id, $fullname, $phone, $address, $note, $payment_method, $total_price);
            $stmt->execute();
            $order_id = $conn->insert_id;

            // B. Chuẩn bị truy vấn chi tiết và cập nhật kho
            $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
            $stmt_update_stock = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");

            foreach ($_SESSION['cart'] as $item) {
                $p_id = $item['id'];
                $p_qty = (int)$item['quantity'];
                $p_name = $item['name'];
                $p_price = $item['price'];

                // Lưu chi tiết
                $stmt_item->bind_param("iisid", $order_id, $p_id, $p_name, $p_qty, $p_price);
                $stmt_item->execute();

                // Cập nhật kho (Trừ số lượng)
                $stmt_update_stock->bind_param("iii", $p_qty, $p_id, $p_qty);
                $stmt_update_stock->execute();

                if ($stmt_update_stock->affected_rows === 0) {
                    throw new Exception("Sản phẩm <strong>$p_name</strong> không đủ số lượng trong kho!");
                }
            }

            // Hoàn tất
            $conn->commit();
            $success = "Đặt hàng thành công! Mã đơn hàng: #" . str_pad($order_id, 6, '0', STR_PAD_LEFT);
            unset($_SESSION['cart']); // Xóa giỏ sau khi đặt thành công
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Lỗi: " . $e->getMessage();
        }
    } else {
        $error = "Vui lòng nhập đầy đủ thông tin bắt buộc (*)!";
    }
}
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Itronic</title>
    <link rel="stylesheet" href="../../CSS/homepage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .checkout-container { max-width: 1200px; margin: 40px auto; padding: 20px; display: grid; grid-template-columns: 1fr 420px; gap: 40px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 14px; border: 1px solid #ddd; border-radius: 12px; font-size: 16px; outline: none; }
        .order-summary { background: #f8f9fa; padding: 25px; border-radius: 16px; position: sticky; top: 20px; }
        .btn-checkout { width: 100%; padding: 16px; background: #0071e3; color: white; border: none; border-radius: 30px; font-size: 18px; font-weight: 600; margin-top: 20px; cursor: pointer; transition: 0.3s; }
        .btn-checkout:hover { background: #005bb5; }
        .bank-info { background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 12px; margin-top: 15px; }
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-content">
            <a href="homepage.php" class="logo"><i class="fa-brands fa-apple"></i></a>
            <ul class="nav-links">
                <li><a href="homepage.php">Cửa hàng</a></li>
                <li><a href="ipad.php">iPad</a></li>
                <li><a href="iphone.php">iPhone</a></li>
            </ul>
            <div class="nav-icons" style="display: flex; align-items: center; gap: 20px;">
                <a href="cart.php" style="color: inherit; text-decoration: none;"><i class="fa-solid fa-bag-shopping" style="font-size: 22px;"></i></a>
                <?php if(isset($_SESSION['user_name'])): ?>
                    <span style="font-size: 14px; font-weight: 500;">Hi, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="checkout-container">
        <div>
            <h1>Thông tin thanh toán</h1>

            <?php if($error): ?>
                <div class="alert" style="color:red; background:#ffebee;"><?= $error ?></div>
            <?php endif; ?>

            <?php if($success): ?>
                <div style="text-align:center; padding: 40px; background:#e8f5e9; border-radius:16px;">
                    <h2 style="color:green;"><?= $success ?></h2>
                    <p>Đơn hàng của bạn đang được xử lý.</p>
                    <a href="homepage.php" style="display:inline-block; margin-top:20px; background:#0071e3; color:white; padding:12px 30px; border-radius:25px; text-decoration:none;">Tiếp tục mua sắm</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Họ và tên <span style="color:red;">*</span></label>
                        <input type="text" name="fullname" required value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại <span style="color:red;">*</span></label>
                        <input type="tel" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Địa chỉ giao hàng <span style="color:red;">*</span></label>
                        <input type="text" name="address" required value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Ghi chú đơn hàng</label>
                        <textarea name="note" rows="3"><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Phương thức thanh toán</label>
                        <select name="payment_method" id="payment_method" onchange="toggleBankInfo()">
                            <option value="COD">Thanh toán khi nhận hàng (COD)</option>
                            <option value="bank_transfer">Chuyển khoản ngân hàng</option>
                        </select>
                    </div>
                    <div id="bank_info" class="bank-info" style="display:none;">
                        <p><strong>Ngân hàng:</strong> Vietcombank</p>
                        <p><strong>Số tài khoản:</strong> 1234567890</p>
                        <p><strong>Nội dung:</strong> Thanh toan don hang Itronic</p>
                    </div>
                    <button type="submit" name="place_order" class="btn-checkout">Hoàn tất đặt hàng</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if(!$success): ?>
        <div class="order-summary">
            <h2>Tóm tắt đơn hàng</h2>
            <?php if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])): ?>
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <div style="display:flex; justify-content:space-between; margin:12px 0;">
                        <span><?= htmlspecialchars($item['name'] ?? 'Sản phẩm') ?> × <?= $item['quantity'] ?></span>
                        <span><?= number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 0, ',', '.') ?>đ</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <hr>
            <div style="display:flex; justify-content:space-between; font-size:20px; font-weight:600;">
                <span>Tổng cộng</span>
                <span style="color:#0071e3;"><?= number_format($total_price, 0, ',', '.') ?>đ</span>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script>
        function toggleBankInfo() {
            const method = document.getElementById('payment_method').value;
            document.getElementById('bank_info').style.display = (method === 'bank_transfer') ? 'block' : 'none';
        }
    </script>
</body>
</html>
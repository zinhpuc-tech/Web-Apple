<?php
session_start();

if (empty($_SESSION['cart']) && isset($_COOKIE['itronic_cart_backup'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['itronic_cart_backup'], true);
}

include "../../PHP/db_connect.php";
include "../../PHP/cart_functions.php";
include "../../phpqrcode/qrlib.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: Sign.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin user (địa chỉ mặc định)
$user_stmt = $conn->prepare("SELECT fullname, phone, address FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc() ?? [];

// Tính tổng tiền
$total_price = 0;
foreach ($_SESSION['cart'] ?? [] as $item) {
    $total_price += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $fullname       = trim($_POST['fullname'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $note           = trim($_POST['note'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'COD';

    if ($fullname && $phone && $address) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO orders 
                (user_id, full_name, phone, address, note, payment_method, total_amount, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            $stmt->bind_param("isssssd", $user_id, $fullname, $phone, $address, $note, $payment_method, $total_price);
            $stmt->execute();
            $order_id = $conn->insert_id;

            $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
            $stmt_stock = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");

            foreach ($_SESSION['cart'] as $item) {
                $stmt_item->bind_param("iisid", $order_id, $item['id'], $item['name'], $item['quantity'], $item['price']);
                $stmt_item->execute();

                $stmt_stock->bind_param("iii", $item['quantity'], $item['id'], $item['quantity']);
                $stmt_stock->execute();

                if ($stmt_stock->affected_rows === 0) {
                    throw new Exception("Sản phẩm " . htmlspecialchars($item['name']) . " không đủ hàng!");
                }
            }

            $conn->commit();

            $_SESSION['cart'] = [];
            $conn->query("DELETE FROM user_carts WHERE user_id = $user_id");
            setcookie('itronic_cart_backup', '', time() - 3600, '/');

            header("Location: my_order.php?success=1");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    } else {
        $error = "Vui lòng nhập đầy đủ thông tin bắt buộc!";
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
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 14px; border: 1px solid #ddd; border-radius: 12px; font-size: 16px; }
        .order-summary { background: #f8f9fa; padding: 25px; border-radius: 16px; position: sticky; top: 20px; }
        .btn-checkout { width: 100%; padding: 16px; background: #0071e3; color: white; border: none; border-radius: 30px; font-size: 18px; font-weight: 600; margin-top: 20px; cursor: pointer; }
        .bank-info { background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 12px; margin-top: 10px; display: none; }
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
        <div class="nav-icons" style="display:flex; align-items:center; gap:20px;">
            <a href="cart.php"><i class="fa-solid fa-bag-shopping" style="font-size:22px;"></i></a>
            <?php if(isset($_SESSION['user_name'])): ?>
                <span>Hi, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="checkout-container">
    <div>
        <h1>Thông tin thanh toán</h1>

        <?php if($error): ?>
            <div class="alert" style="background:#ffebee; color:red;"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Họ và tên <span style="color:red;">*</span></label>
                <input type="text" name="fullname" required value="<?= htmlspecialchars($_POST['fullname'] ?? $user['fullname'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Số điện thoại <span style="color:red;">*</span></label>
                <input type="tel" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? '') ?>">
            </div>

            <!-- Địa chỉ -->
            <div class="form-group">
                <label>Địa chỉ giao hàng <span style="color:red;">*</span></label>
                <select name="use_address" id="use_address" onchange="toggleAddressInput()" style="margin-bottom:10px;">
                    <option value="default">Sử dụng địa chỉ mặc định từ tài khoản</option>
                    <option value="new">Nhập địa chỉ giao hàng mới</option>
                </select>
                <input type="text" name="address" id="address_input" required 
                       value="<?= htmlspecialchars($user['address'] ?? '') ?>"
                       placeholder="Nhập địa chỉ mới nếu chọn tùy chỉnh">
            </div>

            <div class="form-group">
                <label>Ghi chú đơn hàng</label>
                <textarea name="note" rows="3"><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Phương thức thanh toán</label>
                <select name="payment_method" id="payment_method" onchange="togglePaymentInfo()">
                    <option value="COD">Thanh toán khi nhận hàng (COD)</option>
                    <option value="bank_transfer">Chuyển khoản ngân hàng</option>
                    <option value="momo">Thanh toán qua ví MoMo</option>
                </select>
            </div>

            <div id="bank_info" class="bank-info">
                <p><strong>Ngân hàng:</strong> Vietcombank</p>
                <p><strong>Số tài khoản:</strong> 1234567890</p>
                <p><strong>Chủ tài khoản:</strong> CÔNG TY ITRONIC</p>
                <p><strong>Nội dung:</strong> Thanh toán đơn hàng #<span id="order_preview">......</span></p>
            </div>

            <div id="momo_info" class="bank-info" style="text-align: center;">
                <p><strong>Quét mã MoMo để thanh toán</strong></p>
                <img src="../../hinhanh/momo.jpg" alt="Mã QR MoMo" style="max-width: 250px; margin-top: 10px; border-radius: 8px;">
                <p style="font-size: 13px; color: #666; margin-top: 5px;">Vui lòng nhập nội dung là Số điện thoại của bạn</p>
            </div>

            <button type="submit" name="place_order" class="btn-checkout">Hoàn tất đặt hàng</button>
        </form>
    </div>

    <!-- Tóm tắt đơn hàng -->
    <div class="order-summary">
        <h2>Tóm tắt đơn hàng</h2>
        <?php foreach ($_SESSION['cart'] ?? [] as $item): ?>
            <div style="display:flex; justify-content:space-between; margin:10px 0;">
                <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                <span><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</span>
            </div>
        <?php endforeach; ?>
        <hr>
        <div style="font-size:20px; font-weight:600; display:flex; justify-content:space-between;">
            <span>Tổng cộng</span>
            <span style="color:#0071e3;"><?= number_format($total_price, 0, ',', '.') ?>đ</span>
        </div>
    </div>
</main>

<script>
function toggleAddressInput() {
    const select = document.getElementById('use_address');
    const input = document.getElementById('address_input');
    if (select.value === 'new') {
        input.value = '';
        input.focus();
    } else {
        input.value = "<?= addslashes($user['address'] ?? '') ?>";
    }
}

function togglePaymentInfo() {
    const method = document.getElementById('payment_method').value;
    const bankInfo = document.getElementById('bank_info');
    const momoInfo = document.getElementById('momo_info');

    // Ẩn tất cả trước
    bankInfo.style.display = 'none';
    momoInfo.style.display = 'none';

    // Hiển thị cái tương ứng
    if (method === 'bank_transfer') {
        bankInfo.style.display = 'block';
    } else if (method === 'momo') {
        momoInfo.style.display = 'block';
    }
}

// Khởi tạo
document.addEventListener('DOMContentLoaded', () => {
    toggleAddressInput();
    togglePaymentInfo();
});
</script>
</body>
</html>
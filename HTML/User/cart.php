<?php
session_start();
include "../../PHP/db_connect.php";

// ====================== LOAD GIỎ HÀNG THEO USER ======================
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Ưu tiên load từ DB nếu đã login
if (isset($_SESSION['user_id'])) {
    $_SESSION['cart'] = loadCartFromDB($conn, $_SESSION['user_id']);
} 
// Chỉ dùng cookie nếu chưa login
else if (empty($_SESSION['cart']) && isset($_COOKIE['itronic_cart_backup'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['itronic_cart_backup'], true) ?? [];
}

if (!is_array($_SESSION['cart'])) $_SESSION['cart'] = [];

// Hàm đồng bộ giỏ hàng
function syncCart() {
    global $conn;
    if (isset($_SESSION['user_id'])) {
        saveCartToDB($conn, $_SESSION['user_id'], $_SESSION['cart']);
    } else {
        // Lưu cookie cho khách
        if (!empty($_SESSION['cart'])) {
            setcookie('itronic_cart_backup', json_encode($_SESSION['cart']), time() + (86400 * 30), "/");
        } else {
            setcookie('itronic_cart_backup', "", time() - 3600, "/");
        }
    }
}

// Xử lý POST (cập nhật, xóa, xóa hết)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['update_cart'])) {
        if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
            foreach ($_POST['quantity'] as $id => $qty) {
                $qty = max(1, (int)$qty);
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['id'] == $id) {
                        $item['quantity'] = $qty;
                        break;
                    }
                }
            }
        }
    }

    if (isset($_POST['remove_id'])) {
        $remove_id = (int)$_POST['remove_id'];
        $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($remove_id) {
            return $item['id'] != $remove_id;
        });
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }

    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
    }

    syncCart();           // ← Quan trọng: lưu lại sau khi thay đổi
    header("Location: cart.php");
    exit;
}

// Tính tổng tiền
$total_price = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - Itronic</title>
    <link rel="stylesheet" href="../../CSS/homepage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        .cart-container { max-width: 1100px; margin: 40px auto; padding: 20px; }
        .cart-item {
            display: grid;
            grid-template-columns: 100px 2fr 1fr 1fr 80px;
            align-items: center;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        .cart-item img { width: 100%; height: 80px; object-fit: contain; background: #f8f8f8; border-radius: 8px; }
        .cart-item h4 { margin: 0 0 5px 0; font-size: 16px; }
        .quantity-input { width: 70px; padding: 8px; text-align: center; border: 1px solid #ddd; border-radius: 8px; }
        .total { font-size: 24px; font-weight: 600; color: #0071e3; text-align: right; margin: 30px 0; }
        .btn {
            padding: 12px 28px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
        }
        .btn-primary { background: #0071e3; color: white; border: none; }
        .btn-primary:hover { background: #005bb5; }
        .btn-black { background: #1d1d1f; color: white; border: none; }
        .btn-black:hover { background: #000; }
        .btn-danger { background: #ff3b30; color: white; border: none; }
        .empty-cart { text-align: center; padding: 80px 20px; color: #86868b; }
        .empty-cart i { font-size: 60px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <!-- Navbar giống các trang khác (có tên tk + đăng xuất) -->
    <nav class="navbar">
        <div class="nav-content">
            <a href="homepage.php" class="logo"><i class="fa-brands fa-apple"></i></a>
            <ul class="nav-links">
                <li><a href="homepage.php">Cửa hàng</a></li>
                <li><a href="ipad.php">iPad</a></li>
                <li><a href="iphone.php">iPhone</a></li>
            </ul>

            <form action="search.php" method="GET" style="margin:0 20px; position:relative;">
                <input type="text" name="q" placeholder="Tìm kiếm..." 
                       style="padding:12px 20px 12px 50px; width:300px; border-radius:30px; border:1px solid #ddd;">
                <button type="submit" style="position:absolute; left:18px; top:50%; transform:translateY(-50%); border:none; background:none; cursor:pointer;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>

            <div class="nav-icons" style="display: flex; align-items: center; gap: 20px;">
                <a href="cart.php" style="color: inherit; text-decoration: none; position: relative;">
                    <i class="fa-solid fa-bag-shopping" style="font-size: 22px;"></i>
                </a>

                <?php if(isset($_SESSION['user_name'])): ?>
                    <div class="user-info" style="display: flex; align-items: center; gap: 12px;">
                        <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <a href="../Admin/dashboard.php" style="background: #0071e3; color: #fff; padding: 6px 16px; border-radius: 20px; font-size: 13px; text-decoration: none;">
                                <i class="fa-solid fa-gauge-high"></i> Quản trị
                            </a>
                        <?php endif; ?>

                        <a href="profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-circle-user" style="font-size: 18px;"></i>
                            <span style="font-size: 14px; font-weight: 500;">Hi, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                        </a>

                        <a href="../../PHP/logout-user.php" title="Đăng xuất" style="color: #ff3b30;">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" style="color: inherit;">
                        <i class="fa-solid fa-user-circle" style="cursor:pointer; font-size: 24px;"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="cart-container">
        <h1 style="text-align:center; margin-bottom:40px;">Giỏ hàng của bạn</h1>

        <?php if (!empty($_SESSION['cart'])): ?>
            <form method="POST">
                <div class="cart-items">
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <div class="cart-item">
                            <img src="<?= htmlspecialchars($item['image']) ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                 onerror="this.src='https://via.placeholder.com/100x80/F5F5F7/666?text=<?= urlencode($item['name']) ?>';">

                            <div>
                                <h4><?= htmlspecialchars($item['name']) ?></h4>
                                <p style="color:#86868b; font-size:14px;">
                                    <?= number_format($item['price'], 0, ',', '.') ?>đ
                                </p>
                            </div>

                            <div>
                                <input type="number" name="quantity[<?= $item['id'] ?>]" 
                                       value="<?= $item['quantity'] ?>" min="1" class="quantity-input">
                            </div>

                            <div style="text-align:right; font-weight:600; color:#0071e3;">
                                <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ
                            </div>

                            <div>
                                <button type="submit" name="remove_id" value="<?= $item['id'] ?>" 
                                        class="btn btn-danger" style="padding:8px 12px; font-size:14px;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="total">
                    Tổng tiền: <?= number_format($total_price, 0, ',', '.') ?>đ
                </div>

                <div style="text-align:center; margin-top:40px;">
                    <button type="submit" name="update_cart" class="btn btn-primary">
                        <i class="fa-solid fa-rotate"></i> Cập nhật giỏ hàng
                    </button>
                    
                    <a href="homepage.php" class="btn btn-black" style="margin:0 10px;">
                        ← Tiếp tục mua sắm
                    </a>
                    
                    <a href="checkout.php" class="btn btn-primary" style="background:#34c759; margin:0 10px;">
                        Thanh toán ngay →
                    </a>
                    
                    <button type="button" onclick="if(confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')) { document.getElementById('clearForm').submit(); }" 
                            class="btn btn-danger">
                        Xóa giỏ hàng
                    </button>
                </div>
            </form>

            <form id="clearForm" method="POST" style="display:none;">
                <input type="hidden" name="clear_cart" value="1">
            </form>

        <?php else: ?>
            <div class="empty-cart">
                <i class="fa-solid fa-bag-shopping" style="color:#ddd;"></i>
                <h2>Giỏ hàng của bạn đang trống</h2>
                <p style="margin:15px 0 30px;">Hãy thêm một số sản phẩm vào giỏ hàng nhé!</p>
                <a href="homepage.php" class="btn btn-primary">Quay về cửa hàng</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
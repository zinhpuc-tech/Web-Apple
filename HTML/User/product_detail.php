<?php
session_start();
// Thêm đoạn này ngay sau session_start(); ở homepage.php, iphone.php...
if (empty($_SESSION['cart']) && isset($_COOKIE['itronic_cart_backup'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['itronic_cart_backup'], true);
}
include "../../PHP/db_connect.php";

// 1. Lấy ID sản phẩm
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: homepage.php");
    exit;
}

// 2. Query lấy chi tiết sản phẩm và số lượng kho
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: homepage.php?error=notfound");
    exit;
}

// 3. Xử lý khi nhấn nút Mua ngay / Thêm vào giỏ
if (isset($_POST['add_to_cart'])) {
    $qty_buy = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $stock = (int)$product['quantity'];

    if ($stock <= 0) {
        $_SESSION['error'] = "Sản phẩm hiện đang hết hàng!";
    } elseif ($qty_buy > $stock) {
        $_SESSION['error'] = "Kho chỉ còn $stock sản phẩm, vui lòng giảm số lượng.";
    } else {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $product['id']) {
                if (($item['quantity'] + $qty_buy) > $stock) {
                    $_SESSION['error'] = "Tổng số lượng trong giỏ vượt quá tồn kho!";
                    $found = true; break;
                }
                $item['quantity'] += $qty_buy;
                $found = true; break;
            }
        }
        
        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image_url'],
                'quantity' => $qty_buy
            ];
        }
        if (!isset($_SESSION['error'])) {
            $_SESSION['success'] = "Đã thêm <strong>" . htmlspecialchars($product['name']) . "</strong> vào giỏ hàng!";
        }
    }
    header("Location: product_detail.php?id=" . $id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Itronic</title>
    <link rel="stylesheet" href="../../CSS/homepage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        .detail-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 60px; }
        .product-image { background: #f8f8f8; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; }
        .product-image img { width: 100%; max-width: 500px; height: auto; padding: 20px; }
        .product-info h1 { font-size: 34px; margin-bottom: 10px; color: #1d1d1f; }
        .price { font-size: 28px; font-weight: 600; color: #0071e3; margin: 15px 0; }
        
        /* Trạng thái kho */
        .stock-tag { display: inline-block; padding: 6px 12px; border-radius: 6px; font-size: 14px; font-weight: 600; margin-bottom: 15px; }
        .status-ok { background: #eafaf1; color: #2ecc71; }
        .status-out { background: #fdf2f2; color: #e74c3c; }

        /* Nút bấm */
        .btn-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn-buy { background: #0071e3; color: white; border: none; padding: 15px 35px; border-radius: 30px; font-weight: 600; cursor: pointer; flex: 1; transition: 0.3s; }
        .btn-cart { background: white; color: #0071e3; border: 2px solid #0071e3; padding: 15px 30px; border-radius: 30px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-buy:hover { background: #005bb5; }
        .btn-buy:disabled, .btn-cart:disabled { background: #d2d2d7 !important; border-color: #d2d2d7 !important; color: #86868b !important; cursor: not-allowed; }

        /* Thông báo */
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
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

        <form action="search.php" method="GET" class="main-search" style="position:relative; width:350px; margin: 0 20px;">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:15px; top:50%; transform:translateY(-50%); color:#86868b;"></i>
            <input type="text" name="q" placeholder="Tìm sản phẩm..." style="width:100%; padding:10px 15px 10px 45px; border-radius:20px; border:1px solid #d2d2d7; outline:none;">
        </form>

        <div class="nav-icons" style="display: flex; align-items: center; gap: 20px;">
            <a href="cart.php" style="position:relative; color:inherit; text-decoration:none;">
                <i class="fa-solid fa-bag-shopping" style="font-size:22px;"></i>
                <?php if(!empty($_SESSION['cart'])): ?>
                    <span style="position:absolute; top:-8px; right:-10px; background:#ff3b30; color:white; font-size:11px; width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                        <?= array_sum(array_column($_SESSION['cart'], 'quantity')) ?>
                    </span>
                <?php endif; ?>
            </a>

            <?php if(isset($_SESSION['user_name'])): ?>
                <div class="user-info" style="display: flex; align-items: center; gap: 12px;">
                    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="../Admin/dashboard.php" style="background: #0071e3; color: #fff; padding: 6px 16px; border-radius: 20px; font-size: 13px; text-decoration: none;">
                            <i class="fa-solid fa-gauge-high"></i> Admin
                        </a>
                    <?php endif; ?>

                    <a href="profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-circle-user" style="font-size: 20px;"></i>
                        <span style="font-size: 14px; font-weight: 500;">Hi, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    </a>

                    <a href="../../PHP/logout-user.php" title="Đăng xuất" style="color: #ff3b30; font-size: 18px;">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                </div>
            <?php else: ?>
                <a href="login.php" style="color: inherit; text-decoration: none;">
                    <i class="fa-solid fa-circle-user" style="font-size: 24px;"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

    <main class="store-container">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="detail-container">
            <div class="product-image">
                <img src="<?= htmlspecialchars($product['image_url']) ?>" onerror="this.src='https://via.placeholder.com/600';">
            </div>

            <div class="product-info">
                <?php if($product['quantity'] > 0): ?>
                    <span class="stock-tag status-ok"><i class="fa-solid fa-circle-check"></i> Còn hàng (<?= $product['quantity'] ?>)</span>
                <?php else: ?>
                    <span class="stock-tag status-out"><i class="fa-solid fa-circle-xmark"></i> Tạm hết hàng</span>
                <?php endif; ?>

                <h1><?= htmlspecialchars($product['name']) ?></h1>
                <div class="price"><?= number_format($product['price'], 0, ',', '.') ?>đ</div>

                <div style="background:#f5f5f7; padding:20px; border-radius:15px; margin: 20px 0;">
                    <h3 style="margin-bottom:10px; font-size:16px;">Thông số kỹ thuật</h3>
                    <p style="color:#515154; line-height:1.6; font-size:15px;"><?= nl2br(htmlspecialchars($product['technical_info'])) ?></p>
                </div>

                <form method="POST">
                    <div style="margin-bottom:20px;">
                        <label style="font-weight:600; display:block; margin-bottom:8px;">Số lượng:</label>
                        <input type="number" name="quantity" value="1" min="1" max="<?= $product['quantity'] ?>" 
                               <?= ($product['quantity'] <= 0) ? 'disabled' : '' ?>
                               style="width:80px; padding:10px; border-radius:10px; border:1px solid #d2d2d7; text-align:center;">
                    </div>

                    <div class="btn-group">
                        <button type="submit" name="add_to_cart" class="btn-buy" <?= ($product['quantity'] <= 0) ? 'disabled' : '' ?>>
                             <?= ($product['quantity'] > 0) ? 'Mua ngay' : 'Hết hàng' ?>
                        </button>
                        <button type="submit" name="add_to_cart" class="btn-cart" <?= ($product['quantity'] <= 0) ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-cart-plus"></i>
                        </button>
                    </div>
                </form>

                <div style="margin-top:30px;">
                    <a href="javascript:history.back()" style="color:#0071e3; text-decoration:none; font-size:15px;">
                        <i class="fa-solid fa-chevron-left"></i> Quay lại
                    </a>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
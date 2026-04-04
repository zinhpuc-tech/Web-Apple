<?php
session_start();
// Nếu session mất nhưng cookie còn, thì nạp lại ngay
if (empty($_SESSION['cart']) && isset($_COOKIE['itronic_cart_backup'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['itronic_cart_backup'], true);
}
include "../../PHP/db_connect.php";
// LOAD GIỎ HÀNG MỚI
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['user_id'])) {
    $_SESSION['cart'] = loadCartFromDB($conn, $_SESSION['user_id']);
} else if (empty($_SESSION['cart']) && isset($_COOKIE['itronic_cart_backup'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['itronic_cart_backup'], true) ?? [];
}

// Lấy danh sách iPhone và iPad (giới hạn 10 sản phẩm mới nhất)
$sql_iphone = "SELECT * FROM products WHERE category = 'iphone' ORDER BY id DESC LIMIT 10";
$res_iphone = $conn->query($sql_iphone);

$sql_ipad = "SELECT * FROM products WHERE category = 'ipad' ORDER BY id DESC LIMIT 10";
$res_ipad = $conn->query($sql_ipad);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../hinhanh/apple-icon.ico">
    <title>Itronic - Apple Shop</title>
    <link rel="stylesheet" href="../../CSS/homepage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .store-card, .acc-item {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .store-card:hover, .acc-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .store-card img, .acc-item img {
            width: 100%;
            height: 280px;
            object-fit: contain;
            background: #f5f5f7;
            transition: transform 0.4s ease;
        }
        .store-card:hover img {
            transform: scale(1.05);
        }
        .acc-item img {
            height: 220px;
        }
        .store-card .card-info, .acc-item .acc-info {
            padding: 16px;
        }
        .store-card h3, .acc-item h4 {
            margin: 8px 0 6px;
            font-size: 17px;
        }
        .price {
            font-size: 18px;
            font-weight: 600;
            color: #0071e3;
        }
    </style>
</head>
<body>

    <!-- Navbar đồng bộ với các trang khác -->
    <nav class="navbar">
        <div class="nav-content">
            <a href="homepage.php" class="logo"><i class="fa-brands fa-apple"></i></a>
            
            <ul class="nav-links">
                <li><a href="homepage.php">Cửa hàng</a></li>
                <li><a href="ipad.php">iPad</a></li>
                <li><a href="iphone.php">iPhone</a></li>
            </ul>

            <!-- Thanh tìm kiếm -->
            <form action="search.php" method="GET" class="main-search" style="margin:0 20px; position:relative; width:340px;">
                <button type="submit" style="position:absolute; left:18px; top:50%; transform:translateY(-50%); background:none; border:none; color:#86868b;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <input type="text" name="q" placeholder="Tìm kiếm iPhone, iPad..." 
                       style="padding:12px 20px 12px 50px; width:100%; border-radius:30px; border:1px solid #ddd;">
            </form>

            <div class="nav-icons" style="display: flex; align-items: center; gap: 20px;">
                <!-- Giỏ hàng -->
                <a href="cart.php" style="color: inherit; text-decoration: none; position:relative;">
                    <i class="fa-solid fa-bag-shopping" style="cursor:pointer; font-size: 22px;"></i>
                    <?php if(!empty($_SESSION['cart'])): ?>
                        <span style="position:absolute; top:-6px; right:-6px; background:#e00; color:white; font-size:12px; min-width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; padding:0 5px;">
                            <?= array_sum(array_column($_SESSION['cart'], 'quantity')) ?>
                        </span>
                    <?php endif; ?>
                </a>

                <?php if(isset($_SESSION['user_name'])): ?>
                    <div class="user-info" style="display: flex; align-items: center; gap: 12px;">
                        <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <a href="../Admin/dashboard.php" 
                               style="background: #0071e3; color: #fff; padding: 6px 16px; border-radius: 20px; font-size: 13px; text-decoration: none;">
                                <i class="fa-solid fa-gauge-high"></i> Quản trị
                            </a>
                        <?php endif; ?>

                        <a href="profile.php" style="text-decoration: none; color: inherit; display:flex; align-items:center; gap:6px;">
                            <i class="fa-solid fa-circle-user" style="font-size:18px;"></i>
                            <span style="font-size:14px;">Hi, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                        </a>

                        <a href="../../PHP/logout-user.php" title="Đăng xuất" style="color:#ff3b30;">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" style="color: inherit;">
                        <i class="fa-solid fa-user-circle" id="user-icon" style="cursor:pointer; font-size: 24px;"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="store-container">
        <header class="store-header">
            <h1><span>Cửa hàng.</span> Cách tốt nhất để mua sản phẩm bạn yêu thích.</h1>
        </header>

        <!-- Icon nhanh iPhone & iPad -->
        <section class="shelf-icons">
            <a href="iphone.php" class="icon-item">
                <img src="https://store.storeimages.cdn-apple.com/4982/as-images.apple.com/is/store-card-13-iphone-nav-202309?wid=200&hei=130&fmt=png-alpha" alt="iPhone">
            </a>
            <a href="ipad.php" class="icon-item">
                <img src="https://store.storeimages.cdn-apple.com/4982/as-images.apple.com/is/store-card-13-ipad-nav-202405?wid=200&hei=130&fmt=png-alpha" alt="iPad">
            </a>
        </section>

        <!-- iPhone Carousel -->
        <section class="shelf-products">
            <h2 class="shelf-title">Thế hệ mới nhất. <span>Xem có gì mới.</span></h2>
            <div class="carousel-wrapper">
                <div class="carousel-track" style="display: flex; gap: 20px; overflow-x: auto; padding: 10px 0;">
                    <?php if ($res_iphone && $res_iphone->num_rows > 0): ?>
                        <?php while($row = $res_iphone->fetch_assoc()): ?>
                            <div class="store-card" onclick="window.location.href='product_detail.php?id=<?= $row['id'] ?>'">
                                <img src="<?= htmlspecialchars($row['image_url']) ?>" 
                                     alt="<?= htmlspecialchars($row['name']) ?>"
                                     onerror="this.src='https://via.placeholder.com/400x280/F5F5F7/666?text=<?= urlencode($row['name']) ?>';">
                                <div class="card-info">
                                    <span style="color:#0071e3; font-size:13px; font-weight:500;">IPHONE</span>
                                    <h3><?= htmlspecialchars($row['name']) ?></h3>
                                    <p style="color: #86868b; font-size: 14px; margin:8px 0;">
                                        <?= htmlspecialchars($row['technical_info']) ?>
                                    </p>
                                    <p class="price">
                                        <?= number_format($row['price'], 0, ',', '.') ?>đ
                                    </p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="padding:40px;">Đang cập nhật sản phẩm iPhone...</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- iPad Grid -->
        <section class="shelf-accessories">
            <div class="section-header">
                <h2 class="shelf-title">iPad. <span>Sáng tạo và linh hoạt.</span></h2>
            </div>
            <div class="accessory-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
                <?php if ($res_ipad && $res_ipad->num_rows > 0): ?>
                    <?php while($row = $res_ipad->fetch_assoc()): ?>
                        <div class="acc-item" onclick="window.location.href='product_detail.php?id=<?= $row['id'] ?>'">
                            <img src="<?= htmlspecialchars($row['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($row['name']) ?>"
                                 onerror="this.src='https://via.placeholder.com/300x220/F5F5F7/666?text=<?= urlencode($row['name']) ?>';">
                            <div class="acc-info">
                                <h4><?= htmlspecialchars($row['name']) ?></h4>
                                <p style="color: #86868b; font-size:14px;"><?= htmlspecialchars($row['technical_info']) ?></p>
                                <p class="price" style="margin-top:8px;">
                                    <?= number_format($row['price'], 0, ',', '.') ?>đ
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Đang cập nhật sản phẩm iPad...</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Login Modal -->
    <div class="login-modal" id="loginModal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <div class="wrapper-login active">
                <div class="apple-logo-small"><i class="fa-brands fa-apple"></i></div>
                <h2>Đăng nhập Itronic</h2>
                <form action="../../PHP/auth.php" method="POST">
                    <div class="input-box">
                        <input type="text" name="email" placeholder=" " required>
                        <label>ID Itronic (Email)</label>
                    </div>
                    <div class="input-box">
                        <input type="password" name="password" placeholder=" " required>
                        <label>Mật khẩu</label>
                    </div>
                    <button type="submit" name="login" class="btn-apple">Tiếp tục</button>
                </form>
                <a href="Sign.php" class="forgot-pwd">Chưa có tài khoản? Đăng ký ngay</a>
            </div>
        </div>
    </div>

    <script>
        // Modal đăng nhập
        const modalLogin = document.getElementById("loginModal");
        const iconUser = document.getElementById("user-icon");
        const btnCloseLogin = document.querySelector(".close-btn");

        if (iconUser) {
            iconUser.addEventListener('click', () => modalLogin.style.display = "flex");
        }
        if (btnCloseLogin) {
            btnCloseLogin.addEventListener('click', () => modalLogin.style.display = "none");
        }
        window.addEventListener('click', (e) => {
            if (e.target === modalLogin) modalLogin.style.display = "none";
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === "Escape") modalLogin.style.display = "none";
        });
    </script>
</body>
</html>
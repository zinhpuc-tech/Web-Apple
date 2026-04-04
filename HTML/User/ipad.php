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

$per_page = 6;                    // ← Đổi lại 6 như ban đầu
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$where = "WHERE category = 'ipad'";
if ($filter === 'pro') $where .= " AND name LIKE '%Pro%'";
if ($filter === 'air') $where .= " AND name LIKE '%Air%'";
if ($filter === 'mini') $where .= " AND name LIKE '%mini%'";

$sql = "SELECT * FROM products $where ORDER BY id DESC LIMIT $per_page OFFSET $offset";
$result = $conn->query($sql);

$total_sql = "SELECT COUNT(*) as total FROM products $where";
$total_result = $conn->query($total_sql);
$total = $total_result ? $total_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total / $per_page);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iPad - Itronic</title>
    <link rel="stylesheet" href="../../CSS/homepage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            padding: 30px 0;
        }
        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .product-card img {
            width: 100%;
            height: 280px;
            object-fit: contain;
            background: #f8f8f8;
        }
        .filter-bar {
            text-align: center;
            margin: 30px 0;
        }
        .filter-bar a {
            margin: 0 8px;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            color: #333;
        }
        .filter-bar a.active {
            background: #0071e3;
            color: white;
        }
        .pagination {
            text-align: center;
            margin: 50px 0 40px;
        }
        .pagination a {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 8px;
            background: linear-gradient(135deg, #0071e3, #005bb5);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            box-shadow: 0 4px 8px rgba(0,113,227,0.3);
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .pagination a:hover {
            background: linear-gradient(135deg, #005bb5, #004080);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,113,227,0.4);
        }
        .pagination span {
            display: inline-block;
            padding: 12px 20px;
            margin: 0 10px;
            background: #f8f8f8;
            color: #333;
            border-radius: 25px;
            font-weight: 500;
            border: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
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
                <!-- Giỏ hàng có badge -->
                <a href="cart.php" style="color: inherit; text-decoration: none; position: relative;">
                    <i class="fa-solid fa-bag-shopping" style="font-size: 22px;"></i>
                    <?php if(!empty($_SESSION['cart'])): ?>
                        <span style="position:absolute; top:-6px; right:-6px; background:#e00; color:white; font-size:12px; min-width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                            <?= array_sum(array_column($_SESSION['cart'], 'quantity')) ?>
                        </span>
                    <?php endif; ?>
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

    <main class="store-container">
        <h1 style="text-align:center; margin:40px 0;">iPad</h1>

        <!-- Bộ lọc -->
        <div class="filter-bar">
            <a href="ipad.php" class="<?= $filter=='all'?'active':'' ?>">Tất cả</a>
            <a href="ipad.php?filter=pro" class="<?= $filter=='pro'?'active':'' ?>">iPad Pro</a>
            <a href="ipad.php?filter=air" class="<?= $filter=='air'?'active':'' ?>">iPad Air</a>
            <a href="ipad.php?filter=mini" class="<?= $filter=='mini'?'active':'' ?>">iPad Mini</a>
        </div>

        <div class="product-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <!-- Click toàn bộ card để xem chi tiết -->
                    <div class="product-card" onclick="window.location.href='product_detail.php?id=<?= $row['id'] ?>'">
                        <img src="<?= htmlspecialchars($row['image_url']) ?>" 
                             alt="<?= htmlspecialchars($row['name']) ?>"
                             onerror="this.src='https://via.placeholder.com/400x280/F5F5F7/666?text=<?= urlencode($row['name']) ?>';">
                        <div style="padding:15px;">
                            <h3><?= htmlspecialchars($row['name']) ?></h3>
                            <p style="color:#86868b;"><?= htmlspecialchars($row['technical_info']) ?></p>
                            <p style="font-size:18px; font-weight:600; color:#0071e3;">
                                <?= number_format($row['price'], 0, ',', '.') ?>đ
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center; grid-column: 1 / -1;">Không có sản phẩm nào.</p>
            <?php endif; ?>
        </div>

        <!-- Phân trang -->
        <?php if($total_pages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="ipad.php?page=<?= $page-1 ?>&filter=<?= $filter ?>">← Trang trước</a>
            <?php endif; ?>
            
            <span> Trang <?= $page ?> / <?= $total_pages ?> </span>
            
            <?php if($page < $total_pages): ?>
                <a href="ipad.php?page=<?= $page+1 ?>&filter=<?= $filter ?>">Trang sau →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
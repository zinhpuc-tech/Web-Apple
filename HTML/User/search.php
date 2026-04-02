<?php
session_start();
// Thêm đoạn này ngay sau session_start(); ở homepage.php, iphone.php...
if (empty($_SESSION['cart']) && isset($_COOKIE['itronic_cart_backup'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['itronic_cart_backup'], true);
}
include "../../PHP/db_connect.php";

$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$per_page = 6;
$offset = ($page - 1) * $per_page;

$results = null;
$total = 0;
$total_pages = 1;

if (!empty($keyword)) {
    $like = "%$keyword%";
    $sub_where = "";

    if ($filter === 'promax') $sub_where = " AND name LIKE '%Pro Max%'";
    elseif ($filter === 'pro') $sub_where = " AND name LIKE '%Pro%' AND name NOT LIKE '%Pro Max%'";
    elseif ($filter === 'normal' || $filter === 'standard') $sub_where = " AND name NOT LIKE '%Pro%'";
    elseif ($filter === 'air') $sub_where = " AND name LIKE '%Air%'";
    elseif ($filter === 'mini') $sub_where = " AND name LIKE '%mini%'";

    // Đếm tổng
    $count_sql = "SELECT COUNT(*) as total FROM products WHERE (name LIKE ? OR technical_info LIKE ?) $sub_where";
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $total_pages = ceil($total / $per_page);

    // Lấy dữ liệu
    $sql = "SELECT * FROM products WHERE (name LIKE ? OR technical_info LIKE ?) $sub_where 
            ORDER BY id DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $like, $like, $per_page, $offset);
    $stmt->execute();
    $results = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../hinhanh/apple-icon.ico">
    <title>Tìm kiếm - Itronic</title>
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
            transition: all 0.3s;
            font-weight: 500;
        }
        .pagination a:hover {
            background: linear-gradient(135deg, #005bb5, #004080);
            transform: translateY(-2px);
        }
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

            <form action="search.php" method="GET" style="margin:0 20px; position:relative; width:350px;">
                <button type="submit" style="position:absolute; left:18px; top:50%; transform:translateY(-50%); background:none; border:none; color:#86868b;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <input type="text" name="q" value="<?= htmlspecialchars($keyword ?? '') ?>" 
                       placeholder="Tìm kiếm iPhone, iPad..." 
                       style="padding:12px 20px 12px 50px; width:100%; border-radius:30px; border:1px solid #ddd;">
            </form>

            <div class="nav-icons" style="display: flex; align-items: center; gap: 20px;">
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
        <h1 style="text-align:center; margin:40px 0 20px;">
            <?= !empty($keyword) ? 'Kết quả tìm kiếm cho: "<strong>' . htmlspecialchars($keyword) . '</strong>"' : 'Tìm kiếm sản phẩm' ?>
        </h1>

        <?php if(!empty($keyword)): ?>
        <div class="filter-bar">
            <a href="search.php?q=<?= urlencode($keyword) ?>&filter=all" class="<?= $filter=='all'?'active':'' ?>">Tất cả</a>
            <a href="search.php?q=<?= urlencode($keyword) ?>&filter=promax" class="<?= $filter=='promax'?'active':'' ?>">Pro Max</a>
            <a href="search.php?q=<?= urlencode($keyword) ?>&filter=pro" class="<?= $filter=='pro'?'active':'' ?>">Pro</a>
            <a href="search.php?q=<?= urlencode($keyword) ?>&filter=normal" class="<?= $filter=='normal' || $filter=='standard'?'active':'' ?>">Thường</a>
        </div>
        <?php endif; ?>

        <div class="product-grid">
            <?php if(!empty($keyword) && $results && $results->num_rows > 0): ?>
                <?php while($row = $results->fetch_assoc()): ?>
                    <div class="product-card" onclick="window.location.href='product_detail.php?id=<?= $row['id'] ?>'">
                        <img src="<?= htmlspecialchars($row['image_url']) ?>" 
                             alt="<?= htmlspecialchars($row['name']) ?>"
                             onerror="this.src='https://via.placeholder.com/400x280/F5F5F7/666?text=<?= urlencode($row['name']) ?>';">
                        <div style="padding:20px;">
                            <h3><?= htmlspecialchars($row['name']) ?></h3>
                            <p style="color:#86868b; font-size:14px;"><?= htmlspecialchars($row['technical_info']) ?></p>
                            <p style="font-size:18px; font-weight:600; color:#0071e3;">
                                <?= number_format($row['price'], 0, ',', '.') ?>đ
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php elseif(!empty($keyword)): ?>
                <p style="grid-column: 1/-1; text-align:center; color:#86868b; padding:60px 20px;">
                    Không tìm thấy sản phẩm nào phù hợp với "<strong><?= htmlspecialchars($keyword) ?></strong>"
                </p>
            <?php endif; ?>
        </div>

        <?php if(!empty($keyword) && $total_pages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="search.php?q=<?= urlencode($keyword) ?>&page=<?= $page-1 ?>&filter=<?= $filter ?>">← Trang trước</a>
            <?php endif; ?>
            
            <span>Trang <?= $page ?> / <?= $total_pages ?></span>
            
            <?php if($page < $total_pages): ?>
                <a href="search.php?q=<?= urlencode($keyword) ?>&page=<?= $page+1 ?>&filter=<?= $filter ?>">Trang sau →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
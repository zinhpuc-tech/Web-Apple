<?php
session_start();
if (empty($_SESSION['cart']) && isset($_COOKIE['itronic_cart_backup'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['itronic_cart_backup'], true);
}
include "../../PHP/db_connect.php";
include "../../PHP/cart_functions.php";

// LOAD GIỎ HÀNG
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (isset($_SESSION['user_id'])) {
    $_SESSION['cart'] = loadCartFromDB($conn, $_SESSION['user_id']);
} else if (empty($_SESSION['cart']) && isset($_COOKIE['itronic_cart_backup'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['itronic_cart_backup'], true) ?? [];
}

// === THAM SỐ TÌM KIẾM NÂNG CAO ===
$keyword    = isset($_GET['q']) ? trim($_GET['q']) : '';
$category   = isset($_GET['category']) ? $_GET['category'] : 'all';
$min_price  = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (int)$_GET['min_price'] : 0;
$max_price  = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (int)$_GET['max_price'] : 0;
$page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page   = 6;
$offset     = ($page - 1) * $per_page;

// Xây dựng điều kiện WHERE
$where = [];
$params = [];
$types = "";

if (!empty($keyword)) {
    $where[] = "(name LIKE ? OR technical_info LIKE ?)";
    $like = "%$keyword%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

if ($category !== 'all') {
    $where[] = "category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($min_price > 0) {
    $where[] = "price >= ?";
    $params[] = $min_price;
    $types .= "i";
}

if ($max_price > 0) {
    $where[] = "price <= ?";
    $params[] = $max_price;
    $types .= "i";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Đếm tổng số sản phẩm
$count_sql = "SELECT COUNT(*) as total FROM products $where_clause";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total / $per_page);

// Lấy dữ liệu
$sql = "SELECT * FROM products $where_clause ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$types_pag = $types . "ii";
$params_pag = array_merge($params, [$per_page, $offset]);
if (!empty($params_pag)) $stmt->bind_param($types_pag, ...$params_pag);
$stmt->execute();
$results = $stmt->get_result();

// Hàm resolve ảnh
function resolveImageSrc($url) {
    $url = trim($url ?? '');
    if ($url === '') return 'https://via.placeholder.com/400x280/F5F5F7/666?text=No%20Image';
    if (preg_match('#^https?://#i', $url)) return $url;
    if (strpos($url, '/') === 0) return $url;
    if (strpos($url, './') === 0 || strpos($url, '../') === 0) return $url;
    return '../../' . ltrim($url, './');
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
        .advanced-filter {
            background: #f8f8f8;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 30px;
        }
        .pagination a, .pagination span {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 8px;
            border-radius: 25px;
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

            <!-- Form tìm kiếm đơn giản ở navbar -->
            <form action="search.php" method="GET" style="margin:0 20px; position:relative; width:350px;">
                <button type="submit" style="position:absolute; left:18px; top:50%; transform:translateY(-50%); background:none; border:none; color:#86868b;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" 
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
                    <a href="Sign.php" style="color: inherit;">
                        <i class="fa-solid fa-user-circle" style="cursor:pointer; font-size: 24px;"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="store-container">
        <h1 style="text-align:center; margin:40px 0 20px;">
            <?= !empty($keyword) ? 'Kết quả tìm kiếm cho: "<strong>' . htmlspecialchars($keyword) . '</strong>"' : 'Tìm kiếm nâng cao' ?>
        </h1>

        <!-- === FORM TÌM KIẾM NÂNG CAO === -->
        <div class="advanced-filter">
            <form action="search.php" method="GET">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 0.5fr; gap: 15px; align-items: end;">
                    <div>
                        <label>Từ khóa</label><br>
                        <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" 
                               placeholder="Nhập tên sản phẩm..." style="width:100%; padding:10px; border-radius:8px;">
                    </div>
                    <div>
                        <label>Phân loại</label><br>
                        <select name="category" style="width:100%; padding:10px; border-radius:8px;">
                            <option value="all" <?= $category=='all'?'selected':'' ?>>Tất cả</option>
                            <option value="iphone" <?= $category=='iphone'?'selected':'' ?>>iPhone</option>
                            <option value="ipad" <?= $category=='ipad'?'selected':'' ?>>iPad</option>
                        </select>
                    </div>
                    <div>
                        <label>Giá từ</label><br>
                        <input type="number" name="min_price" value="<?= $min_price ?: '' ?>" 
                               placeholder="0" style="width:100%; padding:10px; border-radius:8px;">
                    </div>
                    <div>
                        <label>Giá đến</label><br>
                        <input type="number" name="max_price" value="<?= $max_price ?: '' ?>" 
                               placeholder="999999999" style="width:100%; padding:10px; border-radius:8px;">
                    </div>
                    <div>
                        <button type="submit" style="width:100%; padding:10px; background:#0071e3; color:white; border:none; border-radius:8px; font-weight:600;">
                            <i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="product-grid">
            <?php if ($results && $results->num_rows > 0): ?>
                <?php while($row = $results->fetch_assoc()): ?>
                    <div class="product-card" onclick="window.location.href='product_detail.php?id=<?= $row['id'] ?>'">
                        <img src="<?= htmlspecialchars(resolveImageSrc($row['image_url'])) ?>" 
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
            <?php else: ?>
                <p style="grid-column: 1/-1; text-align:center; color:#86868b; padding:80px 20px;">
                    Không tìm thấy sản phẩm nào phù hợp với tiêu chí tìm kiếm.
                </p>
            <?php endif; ?>
        </div>

        <!-- Phân trang -->
        <?php if($total_pages > 1): ?>
        <div class="pagination" style="text-align:center; margin:50px 0;">
            <?php if($page > 1): ?>
                <a href="search.php?q=<?= urlencode($keyword) ?>&category=<?= $category ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&page=<?= $page-1 ?>">← Trang trước</a>
            <?php endif; ?>
            
            <span style="background:#f8f8f8; padding:12px 20px; border-radius:25px;">Trang <?= $page ?> / <?= $total_pages ?></span>
            
            <?php if($page < $total_pages): ?>
                <a href="search.php?q=<?= urlencode($keyword) ?>&category=<?= $category ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&page=<?= $page+1 ?>">Trang sau →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
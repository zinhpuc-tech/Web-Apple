<?php
session_start();
include __DIR__ . '/../../PHP/db_connect.php';

// Giữ nguyên hàm Check đường dẫn hình ảnh của bạn
function resolveImageSrc($url)
{
    $url = trim($url ?? '');
    if ($url === '') return 'https://via.placeholder.com/600';

    if (preg_match('#^https?://#i', $url)) return $url;
    if (strpos($url, '/') === 0) return $url;
    if (strpos($url, './') === 0 || strpos($url, '../') === 0) return $url;

    return '../../' . ltrim($url, './');
}

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$message = "";
$type = "";

// 2. GIỮ NGUYÊN LOGIC XÓA SẢN PHẨM THÔNG MINH CỦA BẠN
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];

    // Kiểm tra lịch sử nhập hàng (import_details)
    $check_import = $conn->query("SELECT id FROM import_details WHERE product_id = $del_id LIMIT 1");

    if ($check_import && $check_import->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE products SET status = 0 WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            $message = "Sản phẩm đã có lịch sử nhập hàng nên hệ thống đã chuyển sang trạng thái ẨN.";
            $type = "success";
        }
    } else {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            $message = "Đã xóa vĩnh viễn sản phẩm Itronic thành công!";
            $type = "success";
        }
    }
}

// 3. GIỮ NGUYÊN XỬ LÝ CẬP NHẬT SẢN PHẨM
if (isset($_POST['update_product'])) {
    $id = (int)$_POST['id'];
    $name = $_POST['name'];
    $sku = $_POST['sku'];
    $unit = $_POST['unit'];
    $cost_price = $_POST['cost_price'];
    $profit_margin = $_POST['profit_margin'];
    $category = $_POST['category'];
    $image_url = $_POST['image_url'];
    $status = $_POST['status'];
    $tech_info = $_POST['technical_info'];

    $price = $cost_price * (1 + ($profit_margin / 100));

    $stmt = $conn->prepare("UPDATE products SET name=?, sku=?, unit=?, cost_price=?, profit_margin=?, price=?, category=?, image_url=?, status=?, technical_info=? WHERE id=?");
    $stmt->bind_param("sssdidssisi", $name, $sku, $unit, $cost_price, $profit_margin, $price, $category, $image_url, $status, $tech_info, $id);

    if ($stmt->execute()) {
        header("Location: products.php?status=success");
        exit();
    } else {
        $message = "Lỗi khi cập nhật dữ liệu Itronic!";
        $type = "error";
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $message = "Cập nhật dữ liệu sản phẩm thành công!";
    $type = "success";
}

// 4. LẤY DỮ LIỆU ĐỂ ĐỔ VÀO FORM SỬA
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM products WHERE id = $edit_id");
    $edit_data = $res->fetch_assoc();
}

// 5. GIỮ NGUYÊN BAR SEARCH VÀ THÊM FILTER PHÂN LOẠI (Để hiện đủ 300+ sản phẩm)
$keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($conn, $_GET['keyword']) : "";
$filter_cat = isset($_GET['filter_cat']) ? mysqli_real_escape_string($conn, $_GET['filter_cat']) : "";

$sql = "SELECT * FROM products WHERE 1=1";

if ($keyword !== "") {
    $sql .= " AND (name LIKE '%$keyword%' OR sku LIKE '%$keyword%' OR category LIKE '%$keyword%')";
}

if ($filter_cat !== "") {
    $sql .= " AND category = '$filter_cat'";
}

$sql .= " ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../hinhanh/apple-icon.ico">
    <title>Itronic - Quản lý sản phẩm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* GIỮ NGUYÊN TOÀN BỘ STYLE CỦA BẠN */
        :root {
            --apple-blue: #0071e3;
            --apple-dark: #1d1d1f;
            --apple-gray: #86868b;
            --apple-bg: #f5f5f7;
            --sidebar-width: 260px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: var(--apple-bg);
            display: flex;
            min-height: 100vh;
            color: var(--apple-dark);
        }

        .sidebar {
            width: var(--sidebar-width);
            background: var(--apple-dark);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            padding: 20px 0;
        }

        .logo-admin {
            text-align: center;
            padding: 20px;
            font-size: 22px;
            font-weight: bold;
            border-bottom: 1px solid #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .menu a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: #ddd;
            text-decoration: none;
            transition: 0.3s;
            font-size: 14px;
        }

        .menu a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .menu a:hover,
        .menu a.active {
            background: var(--apple-blue);
            color: white;
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 40px;
            width: calc(100% - var(--sidebar-width));
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h2 {
            font-size: 28px;
            font-weight: 700;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.02);
            margin-bottom: 30px;
        }

        .bar_search {
            display: flex;
            gap: 10px;
            margin: 5px 10px;
        }

        .bar_search input {
            padding: 15px;
            flex: 2;
            border: 1px solid #d2d2d7;
            border-radius: 8px;
        }

        /* Dropdown mới của bạn */
        .bar_search select {
            padding: 10px;
            flex: 1;
            border: 1px solid #d2d2d7;
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
        }

        .find_btn {
            padding: 15px;
            height: 48px;
            border: none;
            border-radius: 8px;
            background-color: black;
            color: white;
            cursor: pointer;
            font-weight: 600;
        }

        .clear_btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            background: #ff3b30;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 18px;
        }

        .edit-form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 15px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 12px;
            color: var(--apple-gray);
            text-transform: uppercase;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d2d2d7;
            border-radius: 10px;
            outline: none;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #fbfbfd;
            color: var(--apple-gray);
            font-size: 12px;
            border-bottom: 1px solid #eee;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f2f2f2;
        }

        .product-img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .price {
            color: var(--apple-blue);
            font-weight: 600;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .status-show {
            background: #e3f9e5;
            color: #1f7a28;
        }

        .status-hide {
            background: #f5f5f7;
            color: var(--apple-gray);
        }

        .btn-action {
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-edit {
            background: #eef6ff;
            color: var(--apple-blue);
        }

        .btn-del {
            background: #fff1f0;
            color: #ff3b30;
        }

        .alert {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            background: #e3f9e5;
            color: #1f7a28;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="logo-admin"><i class="fa-brands fa-apple"></i> Itronic Admin</div>
        <div class="menu">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="users.php"><i class="fas fa-users"></i> Quản lý người dùng</a>
            <a href="products.php" class="active"><i class="fas fa-box"></i> Quản lý sản phẩm</a>
            <a href="import-goods.php"><i class="fas fa-file-import"></i> Nhập kho hàng</a>
            <a href="historyimport.php"><i class="fas fa-clock"></i> Lịch sử nhập kho</a>
            <a href="inventory.php"><i class="fas fa-warehouse"></i> Tồn kho</a>
            <a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn đặt hàng</a>
            <a href="../../PHP/logout-admin.php" style="color:#ff453a; margin-top:20px; border-top:1px solid #333;"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Quản lý kho (<?= $result->num_rows ?> SP)</h2>
            <div style="color: var(--apple-gray); font-weight: 500;"><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y'); ?></div>
        </div>

        <form method="GET" class="bar_search">
            <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="Nhập sản phẩm bạn muốn tìm kiếm">

            <select name="filter_cat">
                <option value="">-- Loại hàng --</option>
                <option value="iphone" <?= $filter_cat == 'iphone' ? 'selected' : '' ?>>iPhone</option>
                <option value="ipad" <?= $filter_cat == 'ipad' ? 'selected' : '' ?>>iPad</option>
                <option value="mac" <?= $filter_cat == 'mac' ? 'selected' : '' ?>>MacBook</option>
                <option value="watch" <?= $filter_cat == 'watch' ? 'selected' : '' ?>>Watch</option>
            </select>

            <button class="find_btn" type="submit">Tìm kiếm</button>
            <?php if (!empty($keyword) || !empty($filter_cat)): ?>
                <a href="products.php" class="clear_btn">✖</a>
            <?php endif; ?>
        </form>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $message ?></div>
        <?php endif; ?>

        <?php if ($edit_data): ?>
            <div class="card" style="border: 1px solid var(--apple-blue);">
                <h3 style="color: var(--apple-blue); margin-bottom: 20px;"><i class="fas fa-edit"></i> Chỉnh sửa sản phẩm</h3>
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                    <div class="edit-form-grid">
                        <div><label>Tên sản phẩm</label><input type="text" name="name" value="<?= htmlspecialchars($edit_data['name']) ?>" required></div>
                        <div><label>Mã SKU</label><input type="text" name="sku" value="<?= htmlspecialchars($edit_data['sku']) ?>"></div>
                        <div><label>Đơn vị</label><input type="text" name="unit" value="<?= htmlspecialchars($edit_data['unit'] ?? 'Cái') ?>"></div>
                        <div><label>Giá vốn</label><input type="number" name="cost_price" value="<?= $edit_data['cost_price'] ?>" required></div>
                        <div><label>Lợi nhuận (%)</label><input type="number" name="profit_margin" value="<?= $edit_data['profit_margin'] ?>" required></div>
                        <div>
                            <label>Phân loại</label>
                            <select name="category">
                                <option value="iphone" <?= $edit_data['category'] == 'iphone' ? 'selected' : '' ?>>iPhone</option>
                                <option value="ipad" <?= $edit_data['category'] == 'ipad' ? 'selected' : '' ?>>iPad</option>
                                <option value="mac" <?= $edit_data['category'] == 'mac' ? 'selected' : '' ?>>MacBook</option>
                                <option value="watch" <?= $edit_data['category'] == 'watch' ? 'selected' : '' ?>>Apple Watch</option>
                            </select>
                        </div>
                        <div><label>URL Ảnh</label><input type="text" name="image_url" value="<?= htmlspecialchars($edit_data['image_url']) ?>"></div>
                        <div>
                            <label>Hiện trạng</label>
                            <select name="status">
                                <option value="1" <?= $edit_data['status'] == 1 ? 'selected' : '' ?>>Đang bán</option>
                                <option value="0" <?= $edit_data['status'] == 0 ? 'selected' : '' ?>>Ngừng bán</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top:20px;">
                        <label>Thông số kỹ thuật</label>
                        <textarea name="technical_info" rows="3"><?= htmlspecialchars($edit_data['technical_info']) ?></textarea>
                    </div>
                    <div style="margin-top: 20px;">
                        <button type="submit" name="update_product" class="find_btn" style="background:var(--apple-blue); height: 40px; padding: 0 20px;">Lưu thay đổi</button>
                        <a href="products.php" class="btn-cancel" style="margin-left: 10px; text-decoration: none; color: #888;">Hủy</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Thông tin SP</th>
                        <th>Giá vốn / Lãi</th>
                        <th>Giá bán</th>
                        <th>Kho</th>
                        <th>Trạng thái</th>
                        <th style="text-align: right;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><img src="<?= resolveImageSrc($row['image_url']) ?>" class="product-img" onerror="this.src='https://via.placeholder.com/150'"></td>
                            <td>
                                <div style="font-weight: 600;"><?= htmlspecialchars($row['name']) ?></div>
                                <div style="font-size: 11px; color: var(--apple-gray);">SKU: <?= htmlspecialchars($row['sku']) ?></div>
                            </td>
                            <td>
                                <div style="font-size: 13px;"><?= number_format($row['cost_price']) ?>đ</div>
                                <div style="font-size: 11px; color: #1f7a28;">+<?= $row['profit_margin'] ?>% lãi</div>
                            </td>
                            <td class="price"><?= number_format($row['price']) ?>đ</td>
                            <td><?= $row['quantity'] ?> <?= $row['unit'] ?></td>
                            <td>
                                <span class="badge <?= $row['status'] == 1 ? 'status-show' : 'status-hide' ?>">
                                    <?= $row['status'] == 1 ? 'Đang bán' : 'Đang ẩn' ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="products.php?edit_id=<?= $row['id'] ?>" class="btn-action btn-edit"><i class="fas fa-pen"></i></a>
                                <a href="products.php?delete_id=<?= $row['id'] ?>" class="btn-action btn-del" onclick="return confirm('Hệ thống sẽ xóa hoặc ẩn tùy theo lịch sử nhập hàng. Xác nhận?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
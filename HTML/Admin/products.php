<?php
session_start();
include __DIR__ . '/../../PHP/db_config.php';

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php"); exit();
}

$message = ""; $type = "";

// 2. XỬ LÝ XÓA SẢN PHẨM (LOGIC THÔNG MINH)
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    
    // Kiểm tra xem sản phẩm đã có trong phiếu nhập hàng chưa (giả sử bảng là import_details)
    // Nếu bạn chưa tạo bảng này, bạn có thể tạm thời kiểm tra quantity > 0
    $check_import = $conn->query("SELECT id FROM import_details WHERE product_id = $del_id LIMIT 1");
    
    if ($check_import && $check_import->num_rows > 0) {
        // Đã có lịch sử nhập hàng -> Đánh dấu ẩn (status = 0)
        $stmt = $conn->prepare("UPDATE products SET status = 0 WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            $message = "Sản phẩm đã có lịch sử nhập hàng nên hệ thống đã chuyển sang trạng thái ẨN.";
            $type = "success";
        }
    } else {
        // Chưa có lịch sử nhập hàng -> Xóa vĩnh viễn
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            $message = "Đã xóa vĩnh viễn sản phẩm Itronic thành công!";
            $type = "success";
        }
    }
}

// 3. XỬ LÝ CẬP NHẬT SẢN PHẨM (BỔ SUNG CÁC TRƯỜNG MỚI)
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

    // Tính toán lại giá bán lẻ (price) dựa trên giá vốn và % lợi nhuận
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
        :root { 
            --apple-blue: #0071e3; 
            --apple-dark: #1d1d1f; 
            --apple-gray: #86868b; 
            --apple-bg: #f5f5f7; 
            --sidebar-width: 260px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--apple-bg); display: flex; min-height: 100vh; overflow-x: hidden; color: var(--apple-dark); }

        .sidebar { 
            width: var(--sidebar-width); background: var(--apple-dark); color: white; 
            position: fixed; top: 0; left: 0; height: 100vh; z-index: 1000; padding: 20px 0;
        }
        .logo-admin { 
            text-align: center; padding: 20px; font-size: 22px; font-weight: bold; 
            border-bottom: 1px solid #333; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .menu a { display: flex; align-items: center; padding: 15px 25px; color: #ddd; text-decoration: none; transition: 0.3s; font-size: 14px; }
        .menu a i { margin-right: 12px; width: 20px; text-align: center; }
        .menu a:hover, .menu a.active { background: var(--apple-blue); color: white; }

        .main-content { flex: 1; margin-left: var(--sidebar-width); padding: 40px; width: calc(100% - var(--sidebar-width)); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h2 { font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }

        .card { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02); margin-bottom: 30px; }

        .edit-form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 15px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 12px; color: var(--apple-gray); text-transform: uppercase; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid #d2d2d7; border-radius: 10px; outline: none; transition: 0.2s; font-size: 14px; }
        input:focus { border-color: var(--apple-blue); box-shadow: 0 0 0 3px rgba(0,113,227,0.1); }
        
        .btn-save { background: var(--apple-blue); color: white; border: none; padding: 12px 25px; border-radius: 10px; cursor: pointer; font-weight: 600; }
        .btn-cancel { background: #f5f5f7; color: var(--apple-dark); padding: 12px 20px; border-radius: 10px; text-decoration: none; margin-left: 10px; font-size: 14px; font-weight: 500; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #fbfbfd; color: var(--apple-gray); font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #f2f2f2; vertical-align: middle; }
        
        .product-img { width: 50px; height: 50px; object-fit: contain; background: #fff; border-radius: 8px; border: 1px solid #eee; }
        .price { color: var(--apple-blue); font-weight: 600; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .status-show { background: #e3f9e5; color: #1f7a28; }
        .status-hide { background: #f5f5f7; color: var(--apple-gray); }
        
        .stock-warning { color: #ff9500; font-weight: 700; background: #fff8eb; padding: 4px 10px; border-radius: 20px; font-size: 12px; }
        .stock-danger { color: #ff3b30; font-weight: 700; background: #fff1f0; padding: 4px 10px; border-radius: 20px; font-size: 12px; }

        .btn-action { padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; }
        .btn-edit { background: #eef6ff; color: var(--apple-blue); margin-right: 5px; }
        .btn-del { background: #fff1f0; color: #ff3b30; }

        .alert { padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 500; }
        .alert-success { background: #e3f9e5; color: #1f7a28; border: 1px solid #cdedcf; }
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
        <a href="inventory.php"><i class="fas fa-warehouse"></i> Tồn kho</a>
        <a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn đặt hàng</a>
        <a href="../../PHP/logout-admin.php" style="color:#ff453a; margin-top:20px; border-top:1px solid #333;"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
    </div>
</div>

<div class="main-content">
    <div class="header">
        <h2>Quản lý kho sản phẩm</h2>
        <div style="color: var(--apple-gray); font-weight: 500;"><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y'); ?></div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $message ?></div>
    <?php endif; ?>

    <?php if($edit_data): ?>
    <div class="card" style="border: 1px solid var(--apple-blue);">
        <h3 style="color: var(--apple-blue); margin-bottom: 20px;"><i class="fas fa-edit"></i> Chỉnh sửa sản phẩm</h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
            <div class="edit-form-grid">
                <div>
                    <label>Tên sản phẩm</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($edit_data['name']) ?>" required>
                </div>
                <div>
                    <label>Mã sản phẩm (SKU)</label>
                    <input type="text" name="sku" value="<?= htmlspecialchars($edit_data['sku']) ?>" placeholder="VD: IP16PM-256">
                </div>
                <div>
                    <label>Đơn vị tính</label>
                    <input type="text" name="unit" value="<?= htmlspecialchars($edit_data['unit'] ?? 'Cái') ?>">
                </div>
                <div>
                    <label>Giá vốn (VNĐ)</label>
                    <input type="number" name="cost_price" value="<?= $edit_data['cost_price'] ?>" step="0.01" required>
                </div>
                <div>
                    <label>Lợi nhuận (%)</label>
                    <input type="number" name="profit_margin" value="<?= $edit_data['profit_margin'] ?? 20 ?>" required>
                </div>
                <div>
                    <label>Phân loại</label>
                    <select name="category">
                        <option value="iphone" <?= $edit_data['category']=='iphone'?'selected':'' ?>>iPhone</option>
                        <option value="ipad" <?= $edit_data['category']=='ipad'?'selected':'' ?>>iPad</option>
                        <option value="mac" <?= $edit_data['category']=='mac'?'selected':'' ?>>MacBook</option>
                        <option value="watch" <?= $edit_data['category']=='watch'?'selected':'' ?>>Apple Watch</option>
                    </select>
                </div>
                <div>
                    <label>URL Hình ảnh</label>
                    <input type="text" name="image_url" value="<?= htmlspecialchars($edit_data['image_url']) ?>">
                </div>
                <div>
                    <label>Hiện trạng bán hàng</label>
                    <select name="status">
                        <option value="1" <?= $edit_data['status']==1?'selected':'' ?>>Đang bán (Hiển thị)</option>
                        <option value="0" <?= $edit_data['status']==0?'selected':'' ?>>Ngừng bán (Ẩn)</option>
                    </select>
                </div>
            </div>
            <div style="margin: 20px 0;">
                <label>Mô tả & Thông số kỹ thuật</label>
                <textarea name="technical_info" rows="3"><?= htmlspecialchars($edit_data['technical_info']) ?></textarea>
            </div>
            <button type="submit" name="update_product" class="btn-save"><i class="fas fa-save"></i> Lưu thay đổi</button>
            <a href="products.php" class="btn-cancel">Hủy bỏ</a>
        </form>
    </div>
    <?php endif; ?>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Thông tin SP</th>
                    <th>Giá vốn / Lợi nhuận</th>
                    <th>Giá bán lẻ</th>
                    <th>Kho</th>
                    <th>Trạng thái</th>
                    <th style="text-align: right;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM products ORDER BY id DESC");
                while($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><img src="<?= $row['image_url'] ?>" class="product-img"></td>
                    <td>
                        <div style="font-weight: 600;"><?= htmlspecialchars($row['name']) ?></div>
                        <div style="font-size: 11px; color: var(--apple-gray);">SKU: <?= htmlspecialchars($row['sku']) ?></div>
                    </td>
                    <td>
                        <div style="font-size: 13px;"><?= number_format($row['cost_price']) ?>đ</div>
                        <div style="font-size: 11px; color: #1f7a28;">+<?= $row['profit_margin'] ?>% lãi</div>
                    </td>
                    <td class="price"><?= number_format($row['price']) ?>đ</td>
                    <td>
                        <?php if($row['quantity'] == 0): ?>
                            <span class="stock-danger">Hết hàng</span>
                        <?php elseif($row['quantity'] <= 5): ?>
                            <span class="stock-warning">Sắp hết: <?= $row['quantity'] ?></span>
                        <?php else: ?>
                            <span><?= $row['quantity'] ?> <?= $row['unit'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($row['status'] == 1): ?>
                            <span class="badge status-show">Đang bán</span>
                        <?php else: ?>
                            <span class="badge status-hide">Đang ẩn</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <a href="products.php?edit_id=<?= $row['id'] ?>" class="btn-action btn-edit"><i class="fas fa-pen"></i></a>
                        <a href="products.php?delete_id=<?= $row['id'] ?>" 
                           class="btn-action btn-del" 
                           onclick="return confirm('Hệ thống sẽ xóa hoặc ẩn tùy theo lịch sử nhập hàng. Xác nhận?')">
                           <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div> 

</body>
</html>